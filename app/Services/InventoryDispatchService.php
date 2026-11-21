<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\InventoryWarehouse;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Product;
use App\Models\StockCard;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryDispatchService
{
    public function __construct(
        protected FifoStockAllocatorService $fifoAllocator
    ) {}

    /**
     * Branch submits a Material Request (MR).
     */
    public function submitMaterialRequest(Branch $fromBranch, Branch $toBranch, User $user, array $items, ?string $notes = null): MaterialRequest
    {
        return DB::transaction(function () use ($fromBranch, $toBranch, $user, $items, $notes) {
            $datePrefix = date('Ymd');
            $latestMr = MaterialRequest::where('request_number', 'LIKE', "MR-{$datePrefix}-%")
                ->orderBy('id', 'desc')
                ->first();

            $seq = 1;
            if ($latestMr && preg_match('/-(\d{4})$/', $latestMr->request_number, $matches)) {
                $seq = (int) $matches[1] + 1;
            }
            $requestNumber = sprintf('MR-%s-%04d', $datePrefix, $seq);

            $mr = MaterialRequest::create([
                'request_number' => $requestNumber,
                'from_branch_id' => $fromBranch->id,
                'to_branch_id' => $toBranch->id,
                'requested_by_user_id' => $user->id,
                'status' => 'PENDING',
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                MaterialRequestItem::create([
                    'material_request_id' => $mr->id,
                    'product_id' => $item['product_id'],
                    'quantity_requested' => $item['quantity'],
                    'quantity_fulfilled' => 0,
                ]);
            }

            return $mr->load('items.product');
        });
    }

    /**
     * Central Warehouse approves the Material Request.
     */
    public function approveMaterialRequest(MaterialRequest $mr, User $approver): MaterialRequest
    {
        if ($mr->status !== 'PENDING') {
            throw new \DomainException("Hanya Material Request berstatus PENDING yang dapat disetujui. Status saat ini: {$mr->status}.");
        }

        $mr->update(['status' => 'APPROVED']);

        return $mr;
    }

    /**
     * Dispatch Material Request: allocates stock via FIFO, isolates into IN_TRANSIT warehouse, and issues Delivery Note.
     */
    public function dispatchMaterialRequest(
        MaterialRequest $mr,
        InventoryWarehouse $sourceWarehouse,
        InventoryWarehouse $transitWarehouse,
        string $courierName,
        User $dispatcher,
        ?string $notes = null
    ): DeliveryNote {
        return DB::transaction(function () use ($mr, $sourceWarehouse, $transitWarehouse, $courierName, $dispatcher, $notes) {
            if ($mr->status !== 'APPROVED') {
                throw new \DomainException("Hanya Material Request berstatus APPROVED yang dapat dikirimkan. Status saat ini: {$mr->status}.");
            }

            $datePrefix = date('Ymd');
            $latestDn = DeliveryNote::where('delivery_number', 'LIKE', "SJ-{$datePrefix}-%")
                ->orderBy('id', 'desc')
                ->first();

            $seq = 1;
            if ($latestDn && preg_match('/-(\d{4})$/', $latestDn->delivery_number, $matches)) {
                $seq = (int) $matches[1] + 1;
            }
            $deliveryNumber = sprintf('SJ-%s-%04d', $datePrefix, $seq);
            $trackingCode = 'TRK-'.strtoupper(Str::random(12));

            $dn = DeliveryNote::create([
                'delivery_number' => $deliveryNumber,
                'request_id' => $mr->id,
                'source_warehouse_id' => $sourceWarehouse->id,
                'dest_warehouse_id' => $transitWarehouse->id,
                'dispatched_by_user_id' => $dispatcher->id,
                'courier_name' => $courierName,
                'tracking_code' => $trackingCode,
                'status' => 'DISPATCHED',
                'notes' => $notes,
            ]);

            foreach ($mr->items as $mrItem) {
                $product = $mrItem->product;
                if (! $product instanceof Product) {
                    continue;
                }
                $qtyNeeded = (float) $mrItem->quantity_requested;

                // FIFO Allocation from source warehouse
                $allocations = $this->fifoAllocator->allocateFifoStock($product, $sourceWarehouse, $qtyNeeded);

                foreach ($allocations as $alloc) {
                    $allocQty = (float) $alloc['quantity'];

                    // Deduct from source warehouse
                    $sourceStock = WarehouseStock::where('warehouse_id', $sourceWarehouse->id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $sourceStock || $sourceStock->quantity < $allocQty) {
                        throw new \DomainException("Stok di gudang asal tidak mencukupi untuk [{$product->name}].");
                    }

                    $sourceStock->quantity -= $allocQty;
                    $sourceStock->save();

                    StockCard::create([
                        'warehouse_id' => $sourceWarehouse->id,
                        'product_id' => $product->id,
                        'lot_id' => $alloc['lot_id'],
                        'reference_type' => 'TRANSFER_DISPATCH',
                        'reference_id' => $dn->id,
                        'quantity_in' => 0,
                        'quantity_out' => $allocQty,
                        'balance_after' => $sourceStock->quantity,
                        'cost_per_unit' => $alloc['cost_per_unit'],
                        'notes' => "Pengiriman Surat Jalan [{$dn->delivery_number}] ke Transit",
                    ]);

                    // Isolate into IN_TRANSIT warehouse stock
                    $transitStock = WarehouseStock::firstOrCreate(
                        ['warehouse_id' => $transitWarehouse->id, 'product_id' => $product->id],
                        ['quantity' => 0, 'reserved_quantity' => 0]
                    );
                    $transitStock->quantity += $allocQty;
                    $transitStock->save();

                    StockCard::create([
                        'warehouse_id' => $transitWarehouse->id,
                        'product_id' => $product->id,
                        'lot_id' => $alloc['lot_id'],
                        'reference_type' => 'TRANSFER_DISPATCH',
                        'reference_id' => $dn->id,
                        'quantity_in' => $allocQty,
                        'quantity_out' => 0,
                        'balance_after' => $transitStock->quantity,
                        'cost_per_unit' => $alloc['cost_per_unit'],
                        'notes' => "Isolasi IN_TRANSIT Surat Jalan [{$dn->delivery_number}]",
                    ]);

                    // Record item in delivery note
                    DeliveryNoteItem::create([
                        'delivery_note_id' => $dn->id,
                        'product_id' => $product->id,
                        'lot_id' => $alloc['lot_id'],
                        'quantity_sent' => $allocQty,
                        'quantity_received' => null,
                    ]);
                }

                $mrItem->update(['quantity_fulfilled' => $qtyNeeded]);
            }

            $mr->update(['status' => 'IN_TRANSIT']);

            return $dn->load('items.product');
        });
    }

    /**
     * Branch confirms receipt of delivery note, reconciling physical quantities.
     */
    public function verifyBranchReceipt(
        DeliveryNote $dn,
        InventoryWarehouse $branchWarehouse,
        array $receivedQuantities,
        User $receiver,
        ?string $notes = null
    ): DeliveryNote {
        return DB::transaction(function () use ($dn, $branchWarehouse, $receivedQuantities, $notes) {
            if ($dn->status !== 'DISPATCHED') {
                throw new \DomainException("Hanya Surat Jalan berstatus DISPATCHED yang dapat diverifikasi penerimaannya. Status saat ini: {$dn->status}.");
            }

            $transitWarehouseId = $dn->dest_warehouse_id;
            $hasDiscrepancy = false;

            foreach ($dn->items as $dnItem) {
                $productId = $dnItem->product_id;
                $sentQty = (float) $dnItem->quantity_sent;
                $recvQty = isset($receivedQuantities[$productId]) ? (float) $receivedQuantities[$productId] : $sentQty;
                $discrepancy = $sentQty - $recvQty;

                if ($discrepancy > 0) {
                    $hasDiscrepancy = true;
                }

                $dnItem->update([
                    'quantity_received' => $recvQty,
                    'discrepancy_notes' => $discrepancy > 0 ? "Selisih fisik rusak/hilang: {$discrepancy} unit" : null,
                ]);

                // Deduct from transit warehouse
                $transitStock = WarehouseStock::where('warehouse_id', $transitWarehouseId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if ($transitStock) {
                    $transitStock->quantity = max(0, (float) $transitStock->quantity - $sentQty);
                    $transitStock->save();
                }

                // Add received quantity to branch active bar stock
                $branchStock = WarehouseStock::firstOrCreate(
                    ['warehouse_id' => $branchWarehouse->id, 'product_id' => $productId],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );
                $branchStock->quantity += $recvQty;
                $branchStock->save();

                // Stock card in branch warehouse
                StockCard::create([
                    'warehouse_id' => $branchWarehouse->id,
                    'product_id' => $productId,
                    'lot_id' => $dnItem->lot_id,
                    'reference_type' => 'TRANSFER_RECEIVE',
                    'reference_id' => $dn->id,
                    'quantity_in' => $recvQty,
                    'quantity_out' => 0,
                    'balance_after' => $branchStock->quantity,
                    'cost_per_unit' => (float) ($dnItem->product?->cost_price ?? 0),
                    'notes' => "Penerimaan barang Surat Jalan [{$dn->delivery_number}] di cabang",
                ]);

                // Discrepancy write-off card
                if ($discrepancy > 0) {
                    StockCard::create([
                        'warehouse_id' => $branchWarehouse->id,
                        'product_id' => $productId,
                        'lot_id' => $dnItem->lot_id,
                        'reference_type' => 'ADJUSTMENT',
                        'reference_id' => $dn->id,
                        'quantity_in' => 0,
                        'quantity_out' => $discrepancy,
                        'balance_after' => $branchStock->quantity,
                        'cost_per_unit' => (float) ($dnItem->product?->cost_price ?? 0),
                        'notes' => "Pencatatan selisih barang rusak/hilang saat transit SJ [{$dn->delivery_number}]",
                    ]);
                }
            }

            $finalStatus = $hasDiscrepancy ? 'RECONCILED' : 'DELIVERED';
            $dn->update([
                'status' => $finalStatus,
                'notes' => $notes ?? $dn->notes,
            ]);

            if ($dn->materialRequest) {
                $dn->materialRequest->update(['status' => 'RECEIVED']);
            }

            return $dn->load('items.product');
        });
    }
}
