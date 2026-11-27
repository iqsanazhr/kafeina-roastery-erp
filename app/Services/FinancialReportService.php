<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryWarehouse;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\RecipeDeduction;
use App\Models\StockCard;
use Carbon\Carbon;

class FinancialReportService
{
    /**
     * Calculate Real Profit and Loss (P&L) Report based on real batch HPP and actual recipe consumption.
     *
     * @return array<string, mixed>
     */
    public function calculateProfitAndLoss(?string $startDate = null, ?string $endDate = null, ?int $branchId = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        $orderQuery = PosOrder::where('payment_status', 'PAID')
            ->whereBetween('created_at', [$start, $end]);

        if ($branchId) {
            $orderQuery->where('branch_id', $branchId);
        }

        $orders = $orderQuery->with(['items.product', 'items.recipeDeductions.ingredientProduct'])->get();

        $grossSales = 0.0;
        $totalDiscounts = 0.0;
        $totalTaxPb1 = 0.0;
        $realCogs = 0.0;
        $nonRevenueCost = 0.0;

        $totalTransactions = $orders->count();
        $dineInCount = 0;
        $takeAwayCount = 0;
        $nonRevenueCount = 0;

        foreach ($orders as $order) {
            if ($order->order_type === 'NON_REVENUE') {
                $nonRevenueCount++;
                foreach ($order->items as $item) {
                    if ($item->recipeDeductions->isNotEmpty()) {
                        foreach ($item->recipeDeductions as $deduct) {
                            $ingCost = (float) ($deduct->ingredientProduct?->cost_price ?? 0);
                            $nonRevenueCost += ((float) $deduct->quantity_deducted * $ingCost);
                        }
                    } else {
                        $pCost = (float) ($item->product?->cost_price ?? 0);
                        $nonRevenueCost += ((float) $item->quantity * $pCost);
                    }
                }
            } else {
                if ($order->order_type === 'TAKE_AWAY') {
                    $takeAwayCount++;
                } else {
                    $dineInCount++;
                }

                $grossSales += (float) $order->subtotal;
                $totalDiscounts += (float) $order->discount_amount;
                $totalTaxPb1 += (float) $order->tax_amount;

                foreach ($order->items as $item) {
                    if ($item->recipeDeductions->isNotEmpty()) {
                        foreach ($item->recipeDeductions as $deduct) {
                            $ingCost = (float) ($deduct->ingredientProduct?->cost_price ?? 0);
                            $realCogs += ((float) $deduct->quantity_deducted * $ingCost);
                        }
                    } else {
                        $pCost = (float) ($item->product?->cost_price ?? 0);
                        $realCogs += ((float) $item->quantity * $pCost);
                    }
                }
            }
        }

        $netSales = max(0.0, $grossSales - $totalDiscounts);
        $grossProfit = $netSales - $realCogs;
        $profitMarginPercentage = $netSales > 0 ? ($grossProfit / $netSales) * 100.0 : 0.0;
        $netOperatingProfit = $grossProfit - $nonRevenueCost;

        return [
            'period' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'metrics' => [
                'gross_sales' => round($grossSales, 2),
                'discounts' => round($totalDiscounts, 2),
                'net_sales' => round($netSales, 2),
                'tax_pb1' => round($totalTaxPb1, 2),
                'total_revenue_collected' => round($netSales + $totalTaxPb1, 2),
                'real_cogs' => round($realCogs, 2),
                'gross_profit' => round($grossProfit, 2),
                'gross_margin_percentage' => round($profitMarginPercentage, 2),
                'non_revenue_expense' => round($nonRevenueCost, 2),
                'net_operating_profit' => round($netOperatingProfit, 2),
            ],
            'transactions' => [
                'total' => $totalTransactions,
                'dine_in' => $dineInCount,
                'take_away' => $takeAwayCount,
                'non_revenue' => $nonRevenueCount,
            ],
        ];
    }

    /**
     * Analyze Material Leakage (Recipe Standard vs Actual Stock Outflow) scoped by branch, warehouse, and period.
     *
     * @return array{total_estimated_loss: float, items: array<int, array<string, mixed>>}
     */
    public function analyzeMaterialLeakage(
        ?int $branchId = null,
        ?int $warehouseId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        // Tentukan cakupan gudang: gudang spesifik -> gudang cabang terpilih -> seluruh gudang RETAIL_BAR
        $targetWarehouseIds = null;
        if ($warehouseId) {
            $targetWarehouseIds = [$warehouseId];
        } elseif ($branchId) {
            $targetWarehouseIds = InventoryWarehouse::where('branch_id', $branchId)
                ->where('type', '!=', 'TRANSIT')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            $barIds = InventoryWarehouse::where('type', 'RETAIL_BAR')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $targetWarehouseIds = count($barIds) > 0 ? $barIds : null;
        }

        // Ambil bahan baku operasional bar (kecualikan biji mentah RAW-GB% yang hanya disangrai di silo pusat)
        $rawProducts = Product::where('is_raw_material', true)
            ->where('is_active', true)
            ->where('sku', 'NOT LIKE', 'RAW-GB%')
            ->orderBy('sku')
            ->get();

        $leakageReport = [];
        $totalEstimatedLoss = 0.0;

        foreach ($rawProducts as $product) {
            // 1. Pemakaian teoritis berdasarkan resep POS
            $recipeQuery = RecipeDeduction::where('ingredient_product_id', $product->id);
            if ($targetWarehouseIds !== null) {
                $recipeQuery->whereIn('warehouse_id', $targetWarehouseIds);
            }
            if ($start && $end) {
                $recipeQuery->whereBetween('deduction_timestamp', [$start, $end]);
            }
            $idealUsage = (float) $recipeQuery->sum('quantity_deducted');

            // 2. Pengeluaran fisik aktual pada kartu stok bar (kecualikan konversi Kemas Retail BOM)
            $stockCardQuery = StockCard::where('product_id', $product->id)
                ->whereIn('reference_type', ['POS_SALE', 'ADJUSTMENT'])
                ->where(function ($q) {
                    $q->whereNull('notes')
                        ->orWhere('notes', 'NOT LIKE', 'Kemas Retail BOM%');
                });

            if ($targetWarehouseIds !== null) {
                $stockCardQuery->whereIn('warehouse_id', $targetWarehouseIds);
            }
            if ($start && $end) {
                $stockCardQuery->whereBetween('created_at', [$start, $end]);
            }
            $actualUsage = (float) $stockCardQuery->sum('quantity_out');

            // 3. Hitung selisih & estimasi kerugian
            $discrepancy = $actualUsage - $idealUsage;
            $leakagePercentage = $idealUsage > 0
                ? ($discrepancy / $idealUsage) * 100.0
                : ($discrepancy > 0 ? 100.0 : 0.0);
            $costPrice = (float) ($product->cost_price ?? 0);
            $estimatedLoss = max(0.0, $discrepancy * $costPrice);
            $totalEstimatedLoss += $estimatedLoss;

            $status = 'OPTIMAL';
            if ($leakagePercentage > 5.0) {
                $status = 'CRITICAL_LEAK';
            } elseif ($leakagePercentage > 2.0) {
                $status = 'WARNING';
            }

            $leakageReport[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'unit' => $product->unit,
                'cost_price' => $costPrice,
                'ideal_recipe_usage' => round($idealUsage, 2),
                'actual_stock_outflow' => round($actualUsage, 2),
                'discrepancy' => round($discrepancy, 2),
                'leakage_percentage' => round($leakagePercentage, 2),
                'estimated_loss' => round($estimatedLoss, 2),
                'status' => $status,
            ];
        }

        return [
            'total_estimated_loss' => round($totalEstimatedLoss, 2),
            'items' => $leakageReport,
        ];
    }
}
