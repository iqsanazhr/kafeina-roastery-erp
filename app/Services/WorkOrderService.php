<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\Product;
use App\Models\StockCard;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    /**
     * Calculate Roasting Yield Loss percentage:
     * ((green_weight - roasted_weight) / green_weight) * 100
     */
    public function calculateYieldLoss(float $greenWeightGrams, float $roastedWeightGrams): float
    {
        if ($greenWeightGrams <= 0) {
            throw new \InvalidArgumentException('Bobot green beans awal harus lebih besar dari 0 gram.');
        }

        if ($roastedWeightGrams < 0) {
            throw new \InvalidArgumentException('Bobot biji matang tidak boleh bernilai negatif.');
        }

        return (($greenWeightGrams - $roastedWeightGrams) / $greenWeightGrams) * 100.0;
    }

    /**
     * Validate Yield Loss against roasting tolerance standards (14.00% - 18.00%).
     *
     * Standards:
     * - < 14.00%: UNDER_DEVELOPED (Warning)
     * - 14.00% - 18.00%: NORMAL (Optimal)
     * - > 18.00%: OVER_ROASTED (Critical Alert)
     */
    public function validateYieldTolerance(float $yieldLossPercentage): array
    {
        if ($yieldLossPercentage < 14.00) {
            return [
                'status' => 'UNDER_DEVELOPED',
                'is_acceptable' => false,
                'message' => 'Peringatan: Susut sangrai di bawah 14.00% (< 14%). Biji berisiko underdeveloped / rasa mentah (grassy/baked).',
            ];
        }

        if ($yieldLossPercentage > 18.00) {
            return [
                'status' => 'OVER_ROASTED',
                'is_acceptable' => false,
                'message' => 'Peringatan Kritis: Susut sangrai melebihi 18.00% (> 18%). Biji berisiko over-roasted / gosong (carbon/burnt).',
            ];
        }

        return [
            'status' => 'NORMAL',
            'is_acceptable' => true,
            'message' => 'Susut sangrai dalam batas toleransi standar spesialti (14.00% - 18.00%).',
        ];
    }

    /**
     * Calculate Real Batch HPP per gram:
     * (Cost_green + Cost_kemasan + Cost_energi) / roasted_weight_grams
     */
    public function calculateRealHppPerGram(float $costGreen, float $costPackaging, float $costEnergy, float $roastedWeightGrams): float
    {
        if ($roastedWeightGrams <= 0) {
            throw new \InvalidArgumentException('Bobot matang harus lebih besar dari 0 untuk menghitung HPP per gram.');
        }

        $totalCost = $costGreen + $costPackaging + $costEnergy;

        return $totalCost / $roastedWeightGrams;
    }

    /**
     * Create and schedule a new Work Order, reserving green bean stock.
     */
    public function createWorkOrder(array $data, User $roaster): WorkOrder
    {
        return DB::transaction(function () use ($data, $roaster) {
            $lot = GreenBeanLot::findOrFail($data['lot_id']);
            $greenWeight = (float) $data['green_weight_grams'];

            if ((float) $lot->current_weight_grams < $greenWeight) {
                throw new \DomainException("Stok green bean lot [{$lot->lot_number}] tidak mencukupi. Tersedia: {$lot->current_weight_grams}g, diminta: {$greenWeight}g.");
            }

            // Generate unique WO number: WO-YYYYMMDD-XXXX
            $datePrefix = date('Ymd');
            $latestWo = WorkOrder::where('wo_number', 'LIKE', "WO-{$datePrefix}-%")
                ->orderBy('id', 'desc')
                ->first();

            $seq = 1;
            if ($latestWo && preg_match('/-(\d{4})$/', $latestWo->wo_number, $matches)) {
                $seq = (int) $matches[1] + 1;
            }
            $woNumber = sprintf('WO-%s-%04d', $datePrefix, $seq);

            $wo = WorkOrder::create([
                'wo_number' => $woNumber,
                'lot_id' => $lot->id,
                'profile_id' => $data['profile_id'],
                'roaster_id' => $roaster->id,
                'green_weight_grams' => $greenWeight,
                'status' => 'SCHEDULED',
                'notes' => $data['notes'] ?? null,
            ]);

            // Reserve stock in raw warehouse if exists
            $rawWarehouse = InventoryWarehouse::where('type', 'CENTRAL_RAW')->first();
            if ($rawWarehouse) {
                $rawProduct = Product::where('is_raw_material', true)
                    ->where(function ($q) use ($lot) {
                        $q->where('name', 'LIKE', "%{$lot->variety}%")
                            ->orWhere('sku', 'LIKE', "%{$lot->variety}%");
                    })->first() ?? Product::where('is_raw_material', true)->first();

                if ($rawProduct) {
                    $stock = WarehouseStock::firstOrCreate(
                        ['warehouse_id' => $rawWarehouse->id, 'product_id' => $rawProduct->id],
                        ['quantity' => $lot->current_weight_grams, 'reserved_quantity' => 0]
                    );
                    $stock->increment('reserved_quantity', $greenWeight);
                }
            }

            return $wo;
        });
    }

    /**
     * Start roasting execution.
     */
    public function startRoasting(WorkOrder $wo): WorkOrder
    {
        if ($wo->status !== 'SCHEDULED') {
            throw new \DomainException("Hanya Perintah Kerja berstatus SCHEDULED yang dapat dimulai. Status saat ini: {$wo->status}.");
        }

        $wo->update([
            'status' => 'IN_PROGRESS',
            'roast_start_time' => now(),
        ]);

        return $wo;
    }

    /**
     * Complete roasting execution atomically with Yield Loss & real HPP reconciliation.
     */
    public function completeWorkOrder(
        WorkOrder $wo,
        float $roastedWeightGrams,
        float $energyCost = 0.0,
        ?int $targetProductId = null,
        ?int $roasteryWarehouseId = null
    ): array {
        return DB::transaction(function () use ($wo, $roastedWeightGrams, $energyCost, $targetProductId, $roasteryWarehouseId) {
            if ($wo->status !== 'IN_PROGRESS') {
                throw new \DomainException("Hanya Perintah Kerja berstatus IN_PROGRESS yang dapat diselesaikan. Status saat ini: {$wo->status}.");
            }

            $greenWeight = (float) $wo->green_weight_grams;
            $yieldLoss = $this->calculateYieldLoss($greenWeight, $roastedWeightGrams);
            $tolerance = $this->validateYieldTolerance($yieldLoss);

            $lot = $wo->lot;
            $costGreen = $greenWeight * (float) $lot->price_per_gram;

            // Target product and BOM secondary packaging costs
            $targetProduct = null;
            if ($targetProductId) {
                $targetProduct = Product::find($targetProductId);
            } else {
                // Find matching roasted beans product
                $targetProduct = Product::where('is_raw_material', false)
                    ->where('unit', 'gram')
                    ->first() ?? Product::where('is_raw_material', false)->first();
            }

            $costPackaging = 0.0;
            $packagingDeductions = [];

            if ($targetProduct) {
                $bom = Bom::with('items.materialProduct')->where('product_id', $targetProduct->id)->first();
                if ($bom) {
                    foreach ($bom->items as $item) {
                        $qtyRequired = (float) $item->quantity_required;
                        $matCost = (float) ($item->materialProduct?->cost_price ?? 0);
                        $itemCost = $qtyRequired * $matCost;
                        $costPackaging += $itemCost;
                        $packagingDeductions[] = [
                            'product_id' => $item->material_product_id,
                            'quantity' => $qtyRequired,
                            'cost' => $itemCost,
                        ];
                    }
                }
            }

            $realHppPerGram = $this->calculateRealHppPerGram($costGreen, $costPackaging, $energyCost, $roastedWeightGrams);
            $totalBatchCost = $costGreen + $costPackaging + $energyCost;

            // Identify Warehouses
            $rawWarehouse = InventoryWarehouse::where('type', 'CENTRAL_RAW')->first();
            $roasteryWarehouse = null;
            if ($roasteryWarehouseId) {
                $roasteryWarehouse = InventoryWarehouse::findOrFail($roasteryWarehouseId);
            } else {
                $roasteryWarehouse = InventoryWarehouse::where('type', 'CENTRAL_ROASTED')->first() ?? $rawWarehouse;
            }

            // Deduct green bean lot current weight
            $lot->current_weight_grams = max(0, (float) $lot->current_weight_grams - $greenWeight);
            $lot->save();

            // Deduct green bean warehouse stock
            if ($rawWarehouse) {
                $rawProduct = Product::where('is_raw_material', true)
                    ->where(function ($q) use ($lot) {
                        $q->where('name', 'LIKE', "%{$lot->variety}%")
                            ->orWhere('sku', 'LIKE', "%{$lot->variety}%");
                    })->first() ?? Product::where('is_raw_material', true)->first();

                if ($rawProduct) {
                    $rawStock = WarehouseStock::where('warehouse_id', $rawWarehouse->id)
                        ->where('product_id', $rawProduct->id)
                        ->lockForUpdate()
                        ->first();

                    if ($rawStock) {
                        $rawStock->reserved_quantity = max(0, (float) $rawStock->reserved_quantity - $greenWeight);
                        $rawStock->quantity = max(0, (float) $rawStock->quantity - $greenWeight);
                        $rawStock->save();

                        StockCard::create([
                            'warehouse_id' => $rawWarehouse->id,
                            'product_id' => $rawProduct->id,
                            'lot_id' => $lot->id,
                            'reference_type' => 'ROAST_CHARGE',
                            'reference_id' => $wo->id,
                            'quantity_in' => 0,
                            'quantity_out' => $greenWeight,
                            'balance_after' => $rawStock->quantity,
                            'cost_per_unit' => $lot->price_per_gram,
                            'notes' => "Penggunaan Green Beans untuk WO Sangrai [{$wo->wo_number}]",
                        ]);
                    }
                }
            }

            // Deduct packaging materials from roastery warehouse
            if ($roasteryWarehouse && ! empty($packagingDeductions)) {
                foreach ($packagingDeductions as $pDeduct) {
                    $pkgStock = WarehouseStock::where('warehouse_id', $roasteryWarehouse->id)
                        ->where('product_id', $pDeduct['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($pkgStock) {
                        $pkgStock->quantity = max(0, (float) $pkgStock->quantity - $pDeduct['quantity']);
                        $pkgStock->save();

                        StockCard::create([
                            'warehouse_id' => $roasteryWarehouse->id,
                            'product_id' => $pDeduct['product_id'],
                            'lot_id' => null,
                            'reference_type' => 'ROAST_CHARGE',
                            'reference_id' => $wo->id,
                            'quantity_in' => 0,
                            'quantity_out' => $pDeduct['quantity'],
                            'balance_after' => $pkgStock->quantity,
                            'cost_per_unit' => 0,
                            'notes' => "Konsumsi bahan kemasan sekunder BOM untuk WO [{$wo->wo_number}]",
                        ]);
                    }
                }
            }

            // Add output roasted beans to roastery warehouse stock
            if ($roasteryWarehouse && $targetProduct) {
                $roastStock = WarehouseStock::firstOrCreate(
                    ['warehouse_id' => $roasteryWarehouse->id, 'product_id' => $targetProduct->id],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );

                $roastStock->quantity = (float) $roastStock->quantity + $roastedWeightGrams;
                $roastStock->save();

                // Update product cost price with real HPP
                $targetProduct->cost_price = round($realHppPerGram, 2);
                $targetProduct->save();

                StockCard::create([
                    'warehouse_id' => $roasteryWarehouse->id,
                    'product_id' => $targetProduct->id,
                    'lot_id' => $lot->id,
                    'reference_type' => 'ROAST_OUTPUT',
                    'reference_id' => $wo->id,
                    'quantity_in' => $roastedWeightGrams,
                    'quantity_out' => 0,
                    'balance_after' => $roastStock->quantity,
                    'cost_per_unit' => round($realHppPerGram, 4),
                    'notes' => "Output biji sangrai WO [{$wo->wo_number}] - Susut: ".round($yieldLoss, 2).'%',
                ]);
            }

            // Update Work Order record
            $woNotes = ($wo->notes ? $wo->notes."\n" : '').
                'Yield Loss: '.round($yieldLoss, 2)."% ({$tolerance['status']}). HPP Riil: Rp ".number_format($realHppPerGram, 2, ',', '.').'/g.';

            $wo->update([
                'roasted_weight_grams' => $roastedWeightGrams,
                'yield_loss_percentage' => round($yieldLoss, 2),
                'status' => 'COMPLETED',
                'roast_end_time' => now(),
                'notes' => $woNotes,
            ]);

            return [
                'work_order' => $wo->fresh(),
                'yield_loss_percentage' => round($yieldLoss, 2),
                'tolerance' => $tolerance,
                'cost_green' => $costGreen,
                'cost_packaging' => $costPackaging,
                'cost_energy' => $energyCost,
                'total_batch_cost' => $totalBatchCost,
                'real_hpp_per_gram' => round($realHppPerGram, 4),
            ];
        });
    }
}
