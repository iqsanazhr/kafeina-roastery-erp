<?php

declare(strict_types=1);

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
use Illuminate\Support\Facades\DB;
use RuntimeException;
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

        // Safety Stock Alerts dinamis dari saldo stok <= threshold (kecualikan gudang virtual TRANSIT)
        $safetyStockAlerts = [];
        $lowStocks = WarehouseStock::with(['warehouse.branch', 'product'])
            ->whereHas('warehouse', fn ($q) => $q->where('type', '!=', 'TRANSIT'))
            ->whereColumn('quantity', '<=', 'safety_stock_threshold')
            ->get();

        foreach ($lowStocks as $stock) {
            $current = (float) $stock->quantity;
            $min = (float) ($stock->safety_stock_threshold ?: 100);
            $percent = $min > 0 ? round(($current / $min) * 100) : 100;

            $safetyStockAlerts[] = [
                'product_name' => $stock->product?->name ?? 'Item Persediaan',
                'sku' => $stock->product?->sku ?? '-',
                'warehouse' => $stock->warehouse?->name ?? 'Gudang',
                'branch' => $stock->warehouse?->branch?->name ?? 'Gudang',
                'current' => $current,
                'min' => $min,
                'unit' => $stock->product?->unit ?? 'pcs',
                'percent' => $percent,
                'level' => $percent <= 30 ? 'CRITICAL' : 'WARNING',
            ];
        }

        // Total green beans di Central Roastery (dihitung dinamis kg)
        $centralGreenStockKg = round((float) WarehouseStock::whereHas('warehouse', function ($q) {
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
                'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
                'product_id' => 'nullable|exists:products,id',
                'product_name' => 'nullable|string',
                'quantity' => 'required|numeric|min:1',
                'notes' => 'nullable|string',
            ], [
                'to_branch_id.different' => 'Cabang peminta dan cabang pengirim tidak boleh sama.',
            ]);

            if (empty($validated['product_id']) && empty($validated['product_name'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan pilih produk atau bahan baku yang diminta.',
                ], 422);
            }

            $product = null;
            if (! empty($validated['product_id'])) {
                $product = Product::find((int) $validated['product_id']);
            }
            if (! $product && ! empty($validated['product_name'])) {
                $searchName = trim((string) $validated['product_name']);
                if ($searchName !== '') {
                    $product = Product::where('name', 'LIKE', '%'.$searchName.'%')
                        ->orWhere('sku', $searchName)
                        ->first();
                }
            }

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk / bahan baku tidak ditemukan di master data.',
                ], 422);
            }

            $mr = DB::transaction(function () use ($validated, $product): MaterialRequest {
                $seq = MaterialRequest::max('id') ? ((int) MaterialRequest::max('id') + 1) : 1;
                $mrNumber = 'MR-'.date('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                while (MaterialRequest::where('request_number', $mrNumber)->exists()) {
                    $seq++;
                    $mrNumber = 'MR-'.date('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                }

                $user = auth()->user() ?? User::first();

                $mr = MaterialRequest::create([
                    'request_number' => $mrNumber,
                    'from_branch_id' => (int) $validated['from_branch_id'],
                    'to_branch_id' => (int) $validated['to_branch_id'],
                    'requested_by_user_id' => $user ? $user->id : 1,
                    'status' => 'PENDING',
                    'notes' => $validated['notes'] ?? 'Permintaan stok pasokan reguler cabang',
                ]);

                MaterialRequestItem::create([
                    'material_request_id' => $mr->id,
                    'product_id' => $product->id,
                    'quantity_requested' => (float) $validated['quantity'],
                    'quantity_fulfilled' => 0,
                ]);

                return $mr;
            });

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
            // Normalisasi alias field dari UI lama maupun kanonikal
            $normalizedRequestId = $request->input('request_id') ?? $request->input('mr_id');
            $rawCourier = trim((string) ($request->input('courier_name') ?? $request->input('driver_name') ?? ''));
            $vehicleNo = trim((string) ($request->input('vehicle_number') ?? ''));
            if ($vehicleNo !== '' && $rawCourier !== '' && ! str_contains($rawCourier, $vehicleNo)) {
                $rawCourier = "{$rawCourier} ({$vehicleNo})";
            }

            $request->merge([
                'request_id' => $normalizedRequestId,
                'courier_name' => $rawCourier,
            ]);

            $validated = $request->validate([
                'request_id' => 'required|exists:material_requests,id',
                'courier_name' => 'required|string|max:255',
                'source_warehouse_id' => 'required|exists:inventory_warehouses,id',
                'dest_warehouse_id' => 'required|exists:inventory_warehouses,id',
                'notes' => 'nullable|string|max:500',
            ]);

            if ((int) $validated['source_warehouse_id'] === (int) $validated['dest_warehouse_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gudang asal dan gudang tujuan tidak boleh sama.',
                ], 422);
            }

            $dn = DB::transaction(function () use ($validated): DeliveryNote {
                $mr = MaterialRequest::with('items.product')
                    ->lockForUpdate()
                    ->findOrFail((int) $validated['request_id']);

                if (in_array($mr->status, ['IN_TRANSIT', 'RECEIVED'], true)) {
                    throw new RuntimeException("Material Request {$mr->request_number} sudah berstatus {$mr->status} dan tidak dapat diterbitkan Surat Jalan ganda.");
                }

                if ($mr->items->isEmpty()) {
                    throw new RuntimeException("Material Request {$mr->request_number} tidak memiliki item untuk dikirim.");
                }

                $sourceWh = InventoryWarehouse::findOrFail((int) $validated['source_warehouse_id']);
                $destWh = InventoryWarehouse::findOrFail((int) $validated['dest_warehouse_id']);
                $transitWh = InventoryWarehouse::where('code', 'WH-TRANSIT')
                    ->orWhere('type', 'TRANSIT')
                    ->first();

                $seq = DeliveryNote::max('id') ? ((int) DeliveryNote::max('id') + 1) : 1;
                $dnNumber = 'SJ-'.date('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                while (DeliveryNote::where('delivery_number', $dnNumber)->exists()) {
                    $seq++;
                    $dnNumber = 'SJ-'.date('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                }

                $trackingCode = 'TRK-'.strtoupper(substr(md5(uniqid('', true)), 0, 8));
                $user = auth()->user() ?? User::first();

                $dn = DeliveryNote::create([
                    'delivery_number' => $dnNumber,
                    'request_id' => $mr->id,
                    'source_warehouse_id' => $sourceWh->id,
                    'dest_warehouse_id' => $destWh->id,
                    'dispatched_by_user_id' => $user ? $user->id : 1,
                    'courier_name' => $validated['courier_name'],
                    'tracking_code' => $trackingCode,
                    'status' => 'DISPATCHED',
                    'notes' => $validated['notes'] ?? 'Surat Jalan Pengiriman Stok Antar Cabang Terisolasi',
                ]);

                foreach ($mr->items as $item) {
                    $qtyToSend = (float) $item->quantity_requested;
                    $product = $item->product;

                    // 1. Validasi & potong stok Gudang Asal secara atomik
                    $sourceStock = WarehouseStock::where('warehouse_id', $sourceWh->id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    $available = $sourceStock ? (float) $sourceStock->quantity : 0.0;
                    if ($available < $qtyToSend) {
                        $prodLabel = $product ? "[{$product->sku}] {$product->name}" : "Produk #{$item->product_id}";
                        $unit = $product?->unit ?? 'unit';
                        throw new RuntimeException(
                            "Stok {$prodLabel} di {$sourceWh->name} tidak mencukupi (tersedia ".number_format($available, 0, ',', '.')." {$unit}, diminta ".number_format($qtyToSend, 0, ',', '.')." {$unit})."
                        );
                    }

                    $sourceStock->decrement('quantity', $qtyToSend);
                    $unitCost = (float) ($product?->cost_price ?? 0);

                    StockCard::create([
                        'warehouse_id' => $sourceWh->id,
                        'product_id' => $item->product_id,
                        'lot_id' => null,
                        'reference_type' => 'TRANSFER_DISPATCH',
                        'reference_id' => $dn->id,
                        'quantity_in' => 0,
                        'quantity_out' => $qtyToSend,
                        'balance_after' => (float) $sourceStock->fresh()->quantity,
                        'cost_per_unit' => $unitCost,
                        'notes' => "Pengiriman Surat Jalan {$dn->delivery_number} ke {$destWh->name} (MR {$mr->request_number})",
                    ]);

                    // 2. Isolasi stok di akun virtual WH-TRANSIT selama perjalanan armada
                    if ($transitWh) {
                        $transitStock = WarehouseStock::where('warehouse_id', $transitWh->id)
                            ->where('product_id', $item->product_id)
                            ->lockForUpdate()
                            ->first();

                        if (! $transitStock) {
                            $transitStock = WarehouseStock::create([
                                'warehouse_id' => $transitWh->id,
                                'product_id' => $item->product_id,
                                'quantity' => 0,
                                'reserved_quantity' => 0,
                                'safety_stock_threshold' => 0,
                            ]);
                        }

                        $transitStock->increment('quantity', $qtyToSend);

                        StockCard::create([
                            'warehouse_id' => $transitWh->id,
                            'product_id' => $item->product_id,
                            'lot_id' => null,
                            'reference_type' => 'TRANSFER_DISPATCH',
                            'reference_id' => $dn->id,
                            'quantity_in' => $qtyToSend,
                            'quantity_out' => 0,
                            'balance_after' => (float) $transitStock->fresh()->quantity,
                            'cost_per_unit' => $unitCost,
                            'notes' => "Isolasi transit Surat Jalan {$dn->delivery_number} ({$sourceWh->code} → {$destWh->code})",
                        ]);
                    }

                    // 3. Catat item Surat Jalan menggunakan kolom kanonikal quantity_sent
                    DeliveryNoteItem::create([
                        'delivery_note_id' => $dn->id,
                        'product_id' => $item->product_id,
                        'quantity_sent' => $qtyToSend,
                        'quantity_received' => 0,
                    ]);
                }

                $mr->update(['status' => 'IN_TRANSIT']);

                return $dn;
            });

            return response()->json([
                'success' => true,
                'message' => "Surat Jalan '{$dn->delivery_number}' berhasil diterbitkan. Stok dipotong dari gudang asal & diisolasi ke WH-TRANSIT.",
                'delivery_note' => $dn->load(['materialRequest', 'sourceWarehouse', 'destWarehouse', 'dispatchedBy', 'items.product']),
                'mr_id' => $dn->request_id,
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
                'discrepancy_notes' => 'nullable|string|max:500',
            ]);

            $dnId = $validated['delivery_note_id'] ?? $request->route('id');
            if (! $dnId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID Surat Jalan wajib disertakan.',
                ], 422);
            }

            $damagedTotal = (float) ($validated['damaged_items'] ?? 0);
            $hasDiscrepancy = $damagedTotal > 0;

            $dn = DB::transaction(function () use ($dnId, $damagedTotal, $hasDiscrepancy, $validated): DeliveryNote {
                $dn = DeliveryNote::with(['materialRequest.items', 'items.product', 'sourceWarehouse', 'destWarehouse'])
                    ->lockForUpdate()
                    ->findOrFail((int) $dnId);

                if (in_array($dn->status, ['DELIVERED', 'RECONCILED'], true)) {
                    throw new RuntimeException("Surat Jalan {$dn->delivery_number} sudah dikonfirmasi diterima ({$dn->status}) dan tidak dapat dikonfirmasi ulang.");
                }

                $transitWh = InventoryWarehouse::where('code', 'WH-TRANSIT')
                    ->orWhere('type', 'TRANSIT')
                    ->first();

                $destWh = $dn->destWarehouse;
                $damagedRemaining = $damagedTotal;

                foreach ($dn->items as $dnItem) {
                    $qtySent = (float) $dnItem->quantity_sent;
                    $itemDamaged = min($qtySent, $damagedRemaining);
                    $damagedRemaining = max(0.0, $damagedRemaining - $itemDamaged);
                    $qtyReceived = max(0.0, round($qtySent - $itemDamaged, 4));
                    $unitCost = (float) ($dnItem->product?->cost_price ?? 0);

                    $dnItem->update([
                        'quantity_received' => $qtyReceived,
                        'discrepancy_notes' => $itemDamaged > 0
                            ? trim((string) ($validated['discrepancy_notes'] ?? "Selisih/rusak {$itemDamaged} unit saat serah terima"))
                            : null,
                    ]);

                    // 1. Lepas stok dari WH-TRANSIT
                    if ($transitWh) {
                        $transitStock = WarehouseStock::where('warehouse_id', $transitWh->id)
                            ->where('product_id', $dnItem->product_id)
                            ->lockForUpdate()
                            ->first();

                        if ($transitStock) {
                            $releaseQty = min($qtySent, (float) $transitStock->quantity);
                            if ($releaseQty > 0) {
                                $transitStock->decrement('quantity', $releaseQty);
                            }

                            StockCard::create([
                                'warehouse_id' => $transitWh->id,
                                'product_id' => $dnItem->product_id,
                                'lot_id' => null,
                                'reference_type' => 'TRANSFER_RECEIVE',
                                'reference_id' => $dn->id,
                                'quantity_in' => 0,
                                'quantity_out' => $qtySent,
                                'balance_after' => (float) $transitStock->fresh()->quantity,
                                'cost_per_unit' => $unitCost,
                                'notes' => "Pelepasan transit Surat Jalan {$dn->delivery_number}",
                            ]);
                        }
                    }

                    // 2. Tambah stok ke Gudang Tujuan (Bar Outlet) sejumlah qty fisik diterima
                    if ($destWh && $qtyReceived > 0) {
                        $destStock = WarehouseStock::where('warehouse_id', $destWh->id)
                            ->where('product_id', $dnItem->product_id)
                            ->lockForUpdate()
                            ->first();

                        if (! $destStock) {
                            $destStock = WarehouseStock::create([
                                'warehouse_id' => $destWh->id,
                                'product_id' => $dnItem->product_id,
                                'quantity' => 0,
                                'reserved_quantity' => 0,
                                'safety_stock_threshold' => 1000,
                            ]);
                        }

                        $destStock->increment('quantity', $qtyReceived);

                        StockCard::create([
                            'warehouse_id' => $destWh->id,
                            'product_id' => $dnItem->product_id,
                            'lot_id' => null,
                            'reference_type' => 'TRANSFER_RECEIVE',
                            'reference_id' => $dn->id,
                            'quantity_in' => $qtyReceived,
                            'quantity_out' => 0,
                            'balance_after' => (float) $destStock->fresh()->quantity,
                            'cost_per_unit' => $unitCost,
                            'notes' => "Penerimaan Surat Jalan {$dn->delivery_number}".($itemDamaged > 0 ? " (selisih rusak: {$itemDamaged})" : ' (utuh 100%)'),
                        ]);
                    }

                    // 3. Perbarui quantity_fulfilled di MaterialRequestItem
                    if ($dn->materialRequest) {
                        $mrItem = $dn->materialRequest->items
                            ->firstWhere('product_id', $dnItem->product_id);
                        if ($mrItem) {
                            $mrItem->update(['quantity_fulfilled' => $qtyReceived]);
                        }
                    }
                }

                $finalStatus = $hasDiscrepancy ? 'RECONCILED' : 'DELIVERED';
                $dn->update([
                    'status' => $finalStatus,
                    'notes' => $hasDiscrepancy
                        ? "Diterima dengan selisih: {$damagedTotal} unit rusak/bocor. Catatan: ".($validated['discrepancy_notes'] ?? '-')
                        : 'Diterima utuh tanpa selisih (100% verifikasi cocok).',
                ]);

                if ($dn->materialRequest) {
                    $dn->materialRequest->update(['status' => 'RECEIVED']);
                }

                return $dn;
            });

            return response()->json([
                'success' => true,
                'message' => 'Konfirmasi penerimaan barang cabang selesai & saldo stok gudang tujuan telah diperbarui.'.($hasDiscrepancy ? ' Berita acara selisih tercatat.' : ''),
                'delivery_note' => $dn->load(['materialRequest', 'sourceWarehouse', 'destWarehouse', 'dispatchedBy', 'items.product']),
                'mr_id' => $dn->request_id,
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
            $mr = MaterialRequest::with(['fromBranch', 'toBranch', 'requestedBy', 'items.product'])->findOrFail($id);

            if ($mr->status !== 'PENDING') {
                return response()->json([
                    'success' => false,
                    'message' => "Material Request '{$mr->request_number}' sudah berstatus {$mr->status}.",
                ], 422);
            }

            $mr->update(['status' => 'APPROVED']);

            return response()->json([
                'success' => true,
                'message' => "Material Request '{$mr->request_number}' berhasil disetujui (APPROVED).",
                'material_request' => $mr->fresh(['fromBranch', 'toBranch', 'requestedBy', 'items.product']),
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
