<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $materialRequests = MaterialRequest::with(['fromBranch', 'toBranch', 'requestedBy', 'items.product'])->latest()->get();
        $deliveryNotes = DeliveryNote::with(['materialRequest', 'sourceWarehouse', 'destWarehouse', 'dispatchedBy', 'items.product'])->latest()->get();
        $warehouseStocks = WarehouseStock::with(['warehouse.branch', 'product'])->get();
        $branches = Branch::all();
        $warehouses = InventoryWarehouse::with('branch')->get();
        $products = Product::all();

        // Safety Stock Alerts dinamis dari saldo stok <= threshold
        $safetyStockAlerts = WarehouseStock::with(['warehouse.branch', 'product'])
            ->whereColumn('quantity', '<=', 'safety_stock_threshold')
            ->get()
            ->map(function ($stock) {
                $current = (float) $stock->quantity;
                $min = (float) ($stock->safety_stock_threshold ?: 100);
                $percent = $min > 0 ? round(($current / $min) * 100) : 100;

                return [
                    'product_name' => $stock->product?->name ?? 'Item Persediaan',
                    'branch' => $stock->warehouse?->branch?->name ?? 'Gudang',
                    'current' => $current,
                    'min' => $min,
                    'unit' => $stock->product?->unit ?? 'pcs',
                    'percent' => $percent,
                    'level' => $percent <= 30 ? 'CRITICAL' : 'WARNING',
                ];
            })
            ->toArray();

        // Total green beans di Central Roastery (dihitung dinamis kg)
        $centralGreenStockKg = round(WarehouseStock::whereHas('warehouse', function ($q) {
            $q->where('type', 'CENTRAL_RAW');
        })->sum('quantity') / 1000, 1);

        // Stock Movement Ledger (Kartu Mutasi Persediaan)
        $selectedWarehouseId = $request->query('warehouse_id');
        $selectedRefType = $request->query('reference_type', 'ALL');
        $stockCardSearch = trim((string) $request->query('stock_search', ''));

        $stockCardsQuery = StockCard::with(['warehouse.branch', 'product', 'lot'])
            ->latest('created_at');

        if ($selectedWarehouseId && $selectedWarehouseId !== 'ALL') {
            $stockCardsQuery->where('warehouse_id', (int) $selectedWarehouseId);
        }

        if ($selectedRefType && $selectedRefType !== 'ALL') {
            $stockCardsQuery->where('reference_type', $selectedRefType);
        }

        if ($stockCardSearch !== '') {
            $stockCardsQuery->where(function ($q) use ($stockCardSearch) {
                $q->whereHas('product', function ($pq) use ($stockCardSearch) {
                    $pq->where('name', 'LIKE', "%{$stockCardSearch}%")
                        ->orWhere('sku', 'LIKE', "%{$stockCardSearch}%");
                })->orWhere('notes', 'LIKE', "%{$stockCardSearch}%");
            });
        }

        $stockCards = $stockCardsQuery->paginate(35, ['*'], 'stock_page')->withQueryString();

        $totalMutationsCount = StockCard::count();
        $totalQtyIn = (float) StockCard::sum('quantity_in');
        $totalQtyOut = (float) StockCard::sum('quantity_out');

        return view('inventory.index', compact(
            'materialRequests',
            'deliveryNotes',
            'warehouseStocks',
            'branches',
            'warehouses',
            'products',
            'safetyStockAlerts',
            'centralGreenStockKg',
            'stockCards',
            'selectedWarehouseId',
            'selectedRefType',
            'stockCardSearch',
            'totalMutationsCount',
            'totalQtyIn',
            'totalQtyOut'
        ));
    }

    /**
     * Export Stock Cards / Inventory Ledger to CSV.
     */
    public function exportStockCardsCsv(Request $request): StreamedResponse
    {
        $selectedWarehouseId = $request->query('warehouse_id');
        $selectedRefType = $request->query('reference_type', 'ALL');
        $stockCardSearch = trim((string) $request->query('stock_search', ''));

        $query = StockCard::with(['warehouse.branch', 'product', 'lot'])
            ->latest('created_at');

        if ($selectedWarehouseId && $selectedWarehouseId !== 'ALL') {
            $query->where('warehouse_id', (int) $selectedWarehouseId);
        }

        if ($selectedRefType && $selectedRefType !== 'ALL') {
            $query->where('reference_type', $selectedRefType);
        }

        if ($stockCardSearch !== '') {
            $query->where(function ($q) use ($stockCardSearch) {
                $q->whereHas('product', function ($pq) use ($stockCardSearch) {
                    $pq->where('name', 'LIKE', "%{$stockCardSearch}%")
                        ->orWhere('sku', 'LIKE', "%{$stockCardSearch}%");
                })->orWhere('notes', 'LIKE', "%{$stockCardSearch}%");
            });
        }

        $filename = 'kafeina_buku_mutasi_stok_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['KAFEINA ROASTERY ERP - BUKU MUTASI PERSEDIAAN & KARTU STOK MULTI-CABANG']);
            fputcsv($handle, ['Waktu Ekspor', now()->format('Y-m-d H:i:s').' WIB']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Waktu Mutasi',
                'Cabang',
                'Gudang',
                'SKU Produk',
                'Nama Bahan Baku / Produk',
                'Tipe Mutasi',
                'Ref ID',
                'Masuk (Qty In)',
                'Keluar (Qty Out)',
                'Saldo Akhir',
                'Satuan',
                'HPP Satuan (IDR)',
                'Catatan Mutasi',
            ]);

            $query->chunk(100, function ($cards) use ($handle) {
                foreach ($cards as $c) {
                    fputcsv($handle, [
                        $c->created_at->format('Y-m-d H:i:s'),
                        $c->warehouse?->branch?->name ?? 'Pusat',
                        $c->warehouse?->name ?? 'Gudang',
                        $c->product?->sku ?? '-',
                        $c->product?->name ?? 'Item',
                        $c->reference_type,
                        $c->reference_id,
                        $c->quantity_in,
                        $c->quantity_out,
                        $c->balance_after,
                        $c->product?->unit ?? 'unit',
                        $c->cost_per_unit,
                        $c->notes,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function storeMaterialRequest(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_branch_id' => 'required|exists:branches,id',
                'to_branch_id' => 'required|exists:branches,id',
                'product_name' => 'required|string',
                'quantity' => 'required|numeric|min:1',
                'notes' => 'nullable|string',
            ]);

            $mrNumber = 'MR-'.date('Ym').'-'.str_pad(MaterialRequest::count() + 1, 3, '0', STR_PAD_LEFT);
            $user = User::first();

            $mr = MaterialRequest::create([
                'request_number' => $mrNumber,
                'from_branch_id' => $validated['from_branch_id'],
                'to_branch_id' => $validated['to_branch_id'],
                'requested_by_user_id' => $user ? $user->id : 1,
                'status' => 'PENDING',
                'notes' => $validated['notes'] ?? 'Permintaan stok pasokan reguler cabang',
            ]);

            $product = Product::where('name', 'LIKE', '%'.$validated['product_name'].'%')->first() ?? Product::first();

            MaterialRequestItem::create([
                'material_request_id' => $mr->id,
                'product_id' => $product ? $product->id : 1,
                'quantity_requested' => $validated['quantity'],
                'quantity_fulfilled' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Material Request '{$mr->request_number}' berhasil dibuat.",
                'material_request' => $mr->load(['fromBranch', 'toBranch', 'requestedBy', 'items.product']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat Material Request: '.$e->getMessage(),
            ], 422);
        }
    }

    public function storeDeliveryNote(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'request_id' => 'required|exists:material_requests,id',
                'courier_name' => 'required|string',
                'source_warehouse_id' => 'required|exists:inventory_warehouses,id',
                'dest_warehouse_id' => 'required|exists:inventory_warehouses,id',
                'notes' => 'nullable|string',
            ]);

            $dnNumber = 'SJ-'.date('Ym').'-'.str_pad(DeliveryNote::count() + 1, 3, '0', STR_PAD_LEFT);
            $trackingCode = 'TRK-'.strtoupper(substr(md5(uniqid()), 0, 8));
            $user = User::first();

            $dn = DeliveryNote::create([
                'delivery_number' => $dnNumber,
                'request_id' => $validated['request_id'],
                'source_warehouse_id' => $validated['source_warehouse_id'],
                'dest_warehouse_id' => $validated['dest_warehouse_id'],
                'dispatched_by_user_id' => $user ? $user->id : 1,
                'courier_name' => $validated['courier_name'],
                'tracking_code' => $trackingCode,
                'status' => 'DISPATCHED',
                'notes' => $validated['notes'] ?? 'Surat Jalan Pengiriman Stok Antar Cabang Terisolasi',
            ]);

            $mr = MaterialRequest::with('items')->find($validated['request_id']);
            if ($mr) {
                $mr->update(['status' => 'IN_TRANSIT']);
                foreach ($mr->items as $item) {
                    DeliveryNoteItem::create([
                        'delivery_note_id' => $dn->id,
                        'product_id' => $item->product_id,
                        'quantity_dispatched' => $item->quantity_requested,
                        'quantity_received' => 0,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Surat Jalan '{$dn->delivery_number}' ber-QR code berhasil diterbitkan. Status: DISPATCHED (IN_TRANSIT).",
                'delivery_note' => $dn->load(['materialRequest', 'sourceWarehouse', 'destWarehouse', 'dispatchedBy', 'items.product']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerbitkan Surat Jalan: '.$e->getMessage(),
            ], 422);
        }
    }

    public function confirmReceipt(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'delivery_note_id' => 'nullable|exists:delivery_notes,id',
                'damaged_items' => 'nullable|numeric|min:0',
                'discrepancy_notes' => 'nullable|string',
            ]);

            $dnId = $validated['delivery_note_id'] ?? $request->route('id');
            $dn = DeliveryNote::findOrFail($dnId);
            $hasDiscrepancy = ($validated['damaged_items'] ?? 0) > 0;

            $finalStatus = $hasDiscrepancy ? 'RECONCILED' : 'DELIVERED';

            $dn->update([
                'status' => $finalStatus,
                'notes' => $hasDiscrepancy
                    ? "Diterima dengan selisih: {$validated['damaged_items']} unit rusak/bocor. Catatan: ".($validated['discrepancy_notes'] ?? '')
                    : 'Diterima utuh tanpa selisih (100% verifikasi cocok).',
            ]);

            if ($dn->materialRequest) {
                $dn->materialRequest->update(['status' => 'RECEIVED']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Konfirmasi penerimaan barang cabang selesai.'.($hasDiscrepancy ? ' Berita acara selisih tercatat.' : ''),
                'delivery_note' => $dn->load(['materialRequest', 'sourceWarehouse', 'destWarehouse', 'dispatchedBy', 'items.product']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal verifikasi penerimaan: '.$e->getMessage(),
            ], 422);
        }
    }

    public function approveMaterialRequest(int $id): JsonResponse
    {
        try {
            $mr = MaterialRequest::findOrFail($id);
            $mr->update(['status' => 'APPROVED']);

            return response()->json([
                'success' => true,
                'message' => "Material Request '{$mr->request_number}' berhasil disetujui (APPROVED).",
                'material_request' => $mr,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui Material Request: '.$e->getMessage(),
            ], 422);
        }
    }

    public function dispatchTransfer(Request $request, int $id): JsonResponse
    {
        try {
            $mr = MaterialRequest::findOrFail($id);
            $validated = $request->validate([
                'courier_name' => 'required|string',
                'source_warehouse_id' => 'required|exists:inventory_warehouses,id',
                'dest_warehouse_id' => 'required|exists:inventory_warehouses,id',
                'notes' => 'nullable|string',
            ]);

            $req = new Request(array_merge($validated, ['request_id' => $mr->id]));

            return $this->storeDeliveryNote($req);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan dispatch transfer: '.$e->getMessage(),
            ], 422);
        }
    }
}
