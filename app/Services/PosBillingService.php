<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\InventoryWarehouse;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RecipeDeduction;
use App\Models\StockCard;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class PosBillingService
{
    public const PB1_TAX_RATE = 0.11; // Pajak Restoran 11%

    /**
     * Calculate PB1 tax and final order amount.
     */
    public function calculatePricing(array $items, float $discountAmount = 0.0, bool $isNonRevenue = false): array
    {
        if ($isNonRevenue) {
            return [
                'subtotal' => 0.00,
                'discount_amount' => 0.00,
                'taxable_amount' => 0.00,
                'tax_amount' => 0.00,
                'total_amount' => 0.00,
            ];
        }

        $subtotal = 0.00;
        foreach ($items as $item) {
            $subtotal += ((float) $item['unit_price'] * (int) $item['quantity']);
        }

        $taxableAmount = max(0.00, $subtotal - $discountAmount);
        $taxAmount = round($taxableAmount * self::PB1_TAX_RATE, 2);
        $totalAmount = $taxableAmount + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'taxable_amount' => round($taxableAmount, 2),
            'tax_amount' => $taxAmount,
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Calculate equal split bill amounts across multiple splits.
     */
    public function calculateSplitBill(float $totalAmount, int $numberOfSplits): array
    {
        if ($numberOfSplits <= 0) {
            throw new \InvalidArgumentException('Jumlah split bill minimal 1.');
        }

        $baseAmount = floor(($totalAmount / $numberOfSplits) * 100) / 100;
        $splits = array_fill(0, $numberOfSplits, $baseAmount);

        // Distribute rounding cents
        $remainder = round($totalAmount - ($baseAmount * $numberOfSplits), 2);
        $cents = (int) round($remainder * 100);

        for ($i = 0; $i < $cents; $i++) {
            $splits[$i] = round($splits[$i] + 0.01, 2);
        }

        return $splits;
    }

    /**
     * Atomic POS order checkout with pessimistic locking recipe auto-deductions.
     * Prevents race conditions and negative inventory under concurrent checkout traffic.
     *
     * @throws InsufficientStockException
     * @throws \DomainException
     */
    public function checkout(
        PosShift $shift,
        User $cashier,
        array $orderData
    ): PosOrder {
        if ($shift->status !== 'OPEN') {
            throw new \DomainException("Sesi shift [{$shift->shift_code}] tidak aktif atau telah ditutup.");
        }

        return DB::transaction(function () use ($shift, $cashier, $orderData) {
            $branch = $shift->branch ?? Branch::findOrFail($shift->branch_id);
            $barWarehouse = InventoryWarehouse::where('branch_id', $branch->id)
                ->where('type', 'RETAIL_BAR')
                ->first() ?? InventoryWarehouse::where('branch_id', $branch->id)->first();

            if (! $barWarehouse) {
                throw new \DomainException("Gudang bar cabang [{$branch->name}] tidak ditemukan.");
            }

            $orderType = $orderData['order_type'] ?? 'DINE_IN';
            $isNonRevenue = ($orderType === 'NON_REVENUE');
            $nonRevenueReason = $isNonRevenue ? ($orderData['non_revenue_reason'] ?? 'CALIBRATION') : null;

            $itemsData = $orderData['items'] ?? [];
            if (empty($itemsData)) {
                throw new \InvalidArgumentException('Daftar pesanan tidak boleh kosong.');
            }

            $discountAmount = (float) ($orderData['discount_amount'] ?? 0.0);
            $pricing = $this->calculatePricing($itemsData, $discountAmount, $isNonRevenue);

            // Generate order number: ORD-YYYYMMDD-XXXX
            $datePrefix = date('Ymd');
            $latestOrder = PosOrder::where('order_number', 'LIKE', "ORD-{$datePrefix}-%")
                ->orderBy('id', 'desc')
                ->first();

            $seq = 1;
            if ($latestOrder && preg_match('/-(\d{4})$/', $latestOrder->order_number, $matches)) {
                $seq = (int) $matches[1] + 1;
            }
            $orderNumber = sprintf('ORD-%s-%04d', $datePrefix, $seq);

            $paymentMethod = $isNonRevenue ? 'NON_REVENUE' : ($orderData['payment_method'] ?? 'CASH');
            $paymentStatus = $isNonRevenue ? 'PAID' : ($orderData['payment_status'] ?? 'PAID');

            $customerName = trim((string) ($orderData['customer_name'] ?? 'Tamu Kafeina')) ?: 'Tamu Kafeina';
            $tableNumber = $orderData['table_number'] ?? null;
            $customerPhone = $orderData['customer_phone'] ?? null;
            $notes = $orderData['notes'] ?? null;
            $paymentReference = $orderData['payment_reference'] ?? null;

            $order = PosOrder::create([
                'order_number' => $orderNumber,
                'shift_id' => $shift->id,
                'cashier_id' => $cashier->id,
                'branch_id' => $branch->id,
                'order_type' => $orderType,
                'customer_name' => $customerName,
                'table_number' => $tableNumber,
                'customer_phone' => $customerPhone,
                'notes' => $notes,
                'non_revenue_reason' => $nonRevenueReason,
                'subtotal' => $pricing['subtotal'],
                'tax_amount' => $pricing['tax_amount'],
                'discount_amount' => $pricing['discount_amount'],
                'total_amount' => $pricing['total_amount'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'payment_reference' => $paymentReference,
            ]);

            // Process line items & recipe deductions with pessimistic locking (SELECT FOR UPDATE)
            foreach ($itemsData as $itemInput) {
                $product = Product::findOrFail($itemInput['product_id']);
                $quantity = (int) $itemInput['quantity'];
                $unitPrice = $isNonRevenue ? 0.00 : (float) ($itemInput['unit_price'] ?? $product->selling_price);
                $subtotal = $unitPrice * $quantity;

                $orderItem = PosOrderItem::create([
                    'pos_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                // Check recipes for this product
                $recipes = ProductRecipe::with('ingredientProduct')->where('product_id', $product->id)->get();

                if ($recipes->isNotEmpty()) {
                    // Deduct each recipe ingredient
                    foreach ($recipes as $recipe) {
                        $qtyRequired = (float) $recipe->quantity_required * $quantity;
                        $ingredient = $recipe->ingredientProduct;

                        // PESSIMISTIC LOCKING: SELECT ... FOR UPDATE
                        $stock = WarehouseStock::where('warehouse_id', $barWarehouse->id)
                            ->where('product_id', $recipe->ingredient_product_id)
                            ->lockForUpdate()
                            ->first();

                        $available = $stock ? (float) ($stock->quantity - $stock->reserved_quantity) : 0.0;
                        if (! $stock || $available < $qtyRequired) {
                            $ingName = $ingredient ? $ingredient->name : "ID #{$recipe->ingredient_product_id}";
                            throw new InsufficientStockException("Stok bahan baku [{$ingName}] tidak mencukupi di bar cabang! Tersedia: {$available} {$recipe->unit}, Dibutuhkan: {$qtyRequired} {$recipe->unit}. Transaksi dibatalkan untuk mencegah stok negatif.");
                        }

                        $stock->quantity -= $qtyRequired;
                        $stock->save();

                        RecipeDeduction::create([
                            'pos_order_item_id' => $orderItem->id,
                            'ingredient_product_id' => $recipe->ingredient_product_id,
                            'warehouse_id' => $barWarehouse->id,
                            'quantity_deducted' => $qtyRequired,
                            'unit' => $recipe->unit,
                            'deduction_timestamp' => now(),
                        ]);

                        StockCard::create([
                            'warehouse_id' => $barWarehouse->id,
                            'product_id' => $recipe->ingredient_product_id,
                            'lot_id' => null,
                            'reference_type' => $isNonRevenue ? 'ADJUSTMENT' : 'POS_SALE',
                            'reference_id' => $order->id,
                            'quantity_in' => 0,
                            'quantity_out' => $qtyRequired,
                            'balance_after' => $stock->quantity,
                            'cost_per_unit' => (float) ($ingredient?->cost_price ?? 0),
                            'notes' => $isNonRevenue
                                ? "Auto-deduct Non-Revenue [{$nonRevenueReason}] Order [{$order->order_number}]"
                                : "Auto-deduct POS Resep [{$product->name}] Order [{$order->order_number}]",
                        ]);
                    }
                } else {
                    // Standalone retail product deduction (e.g. coffee beans 250g bag, merchandise)
                    $stock = WarehouseStock::where('warehouse_id', $barWarehouse->id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    $available = $stock ? (float) ($stock->quantity - $stock->reserved_quantity) : 0.0;
                    if (! $stock || $available < $quantity) {
                        throw new InsufficientStockException("Stok produk [{$product->name}] tidak mencukupi di bar cabang! Tersedia: {$available}, Dibutuhkan: {$quantity}. Transaksi dibatalkan.");
                    }

                    $stock->quantity -= $quantity;
                    $stock->save();

                    StockCard::create([
                        'warehouse_id' => $barWarehouse->id,
                        'product_id' => $product->id,
                        'lot_id' => null,
                        'reference_type' => $isNonRevenue ? 'ADJUSTMENT' : 'POS_SALE',
                        'reference_id' => $order->id,
                        'quantity_in' => 0,
                        'quantity_out' => $quantity,
                        'balance_after' => $stock->quantity,
                        'cost_per_unit' => (float) $product->cost_price,
                        'notes' => $isNonRevenue
                            ? "Auto-deduct Retail Non-Revenue [{$nonRevenueReason}] [{$order->order_number}]"
                            : "POS Retail Sale [{$order->order_number}]",
                    ]);
                }
            }

            return $order->load(['items.product', 'items.recipeDeductions.ingredientProduct']);
        });
    }
}
