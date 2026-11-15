<?php

namespace App\Services;

use App\Models\InventoryWarehouse;
use App\Models\Product;
use App\Models\StockCard;
use App\Models\WarehouseStock;

class FifoStockAllocatorService
{
    /**
     * Allocate stock based on FIFO (First-In, First-Out) principle.
     * Orders batches/lots by oldest creation date first.
     *
     * @return array<int, array{lot_id: ?int, quantity: float, cost_per_unit: float}>
     *
     * @throws \DomainException if stock is insufficient
     */
    public function allocateFifoStock(Product $product, InventoryWarehouse $warehouse, float $requestedQuantity): array
    {
        if ($requestedQuantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas alokasi harus lebih besar dari 0.');
        }

        $stock = WarehouseStock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $availableQty = $stock ? (float) ($stock->quantity - $stock->reserved_quantity) : 0.0;

        if ($availableQty < $requestedQuantity) {
            throw new \DomainException("Stok tidak mencukupi untuk produk [{$product->name}]. Tersedia: {$availableQty}, diminta: {$requestedQuantity}.");
        }

        // Search for oldest lots associated with this product in stock_cards with positive balance
        $oldestInbounds = StockCard::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->where('quantity_in', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $allocations = [];
        $remainingToAllocate = $requestedQuantity;

        if ($oldestInbounds->isNotEmpty()) {
            foreach ($oldestInbounds as $inbound) {
                if ($remainingToAllocate <= 0) {
                    break;
                }

                $takeQty = min($remainingToAllocate, (float) $inbound->quantity_in);
                $allocations[] = [
                    'lot_id' => $inbound->lot_id,
                    'quantity' => $takeQty,
                    'cost_per_unit' => (float) $inbound->cost_per_unit,
                ];
                $remainingToAllocate -= $takeQty;
            }
        }

        // If there's still remainder not matched to specific inbounds (e.g. general balance)
        if ($remainingToAllocate > 0) {
            $allocations[] = [
                'lot_id' => null,
                'quantity' => $remainingToAllocate,
                'cost_per_unit' => (float) ($product->cost_price ?? 0),
            ];
        }

        return $allocations;
    }
}
