<?php

namespace App\Services;

use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\Product;
use App\Models\StockCard;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class LotGeneratorService
{
    /**
     * Generate unique standard lot number: GB-[FARM_CODE]-[YEAR]-[SEQ]
     * Example: GB-GYO-2026-0001
     */
    public function generateLotNumber(string $farmCode, int $year): string
    {
        $cleanFarmCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($farmCode, 0, 5)));
        if (empty($cleanFarmCode)) {
            $cleanFarmCode = 'GEN';
        }

        $prefix = "GB-{$cleanFarmCode}-{$year}-";

        // Lock or fetch latest sequence
        $latestLot = GreenBeanLot::where('lot_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($latestLot && preg_match('/-(\d{4})$/', $latestLot->lot_number, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return sprintf('%s%04d', $prefix, $nextSeq);
    }

    /**
     * Process an accepted lot into warehouse stock and stock card.
     */
    public function processAcceptedLot(GreenBeanLot $lot, int $warehouseId, ?int $productId = null): void
    {
        DB::transaction(function () use ($lot, $warehouseId, $productId) {
            // Ensure warehouse is valid
            $warehouse = InventoryWarehouse::findOrFail($warehouseId);

            // Determine target raw product
            $product = null;
            if ($productId) {
                $product = Product::findOrFail($productId);
            } else {
                // Find matching or fallback raw material product
                $product = Product::where('is_raw_material', true)
                    ->where(function ($q) use ($lot) {
                        $q->where('name', 'LIKE', "%{$lot->variety}%")
                            ->orWhere('sku', 'LIKE', "%{$lot->variety}%");
                    })->first() ?? Product::where('is_raw_material', true)->first();

                if (! $product) {
                    // Fallback to any product or create placeholder
                    $product = Product::first();
                }
            }

            if (! $product) {
                throw new \RuntimeException('Tidak ditemukan produk bahan baku yang sesuai untuk Green Bean Lot ini.');
            }

            // Find or create warehouse stock with lock
            $stock = WarehouseStock::where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = new WarehouseStock([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $quantityIn = (float) $lot->initial_weight_grams;
            $stock->quantity += $quantityIn;
            $stock->save();

            // Record mutation in stock_cards
            StockCard::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'lot_id' => $lot->id,
                'reference_type' => 'PO_RECEIPT',
                'reference_id' => $lot->purchase_order_id ?? $lot->id,
                'quantity_in' => $quantityIn,
                'quantity_out' => 0,
                'balance_after' => $stock->quantity,
                'cost_per_unit' => $lot->price_per_gram,
                'notes' => "Penerimaan Green Bean Lot [{$lot->lot_number}] lolos uji mutu QC",
            ]);
        });
    }
}
