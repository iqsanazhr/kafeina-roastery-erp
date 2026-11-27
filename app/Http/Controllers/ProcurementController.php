<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\QualityControl;
use App\Models\StockCard;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcurementController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount('purchaseOrders')->get();
        $purchaseOrders = PurchaseOrder::with(['supplier', 'branch', 'items.product'])->latest()->get();
        $qualityControls = QualityControl::with(['lot.purchaseOrder.supplier', 'inspector'])->latest()->get();
        $lots = GreenBeanLot::with('purchaseOrder')->latest()->get();
        $branches = Branch::all();
        $warehouses = InventoryWarehouse::with('branch')
            ->where('type', '!=', 'TRANSIT')
            ->orderBy('id')
            ->get();

        $totalContractedBeansKg = round((float) PurchaseOrderItem::where(function ($q) {
            $q->whereHas('product', function ($pq) {
                $pq->where('sku', 'LIKE', 'RAW-GB%');
            })->orWhere(function ($oq) {
                $oq->whereNull('product_id')->where('product_name', 'LIKE', '%Green Bean%');
            });
        })->sum('quantity_kg'), 1);

        if ($totalContractedBeansKg <= 0) {
            $totalContractedBeansKg = round((float) GreenBeanLot::sum('initial_weight_grams') / 1000, 1);
        }

        $avgSupplierRating = $suppliers->count() > 0
            ? round((float) $suppliers->avg('rating_score'), 2)
            : 4.85;

        // Semua bahan baku yang dapat dibeli dari vendor eksternal (kecuali RAW-RB hasil sangrai internal)
        $rawMaterialsQuery = Product::with('category')
            ->where('is_raw_material', true)
            ->where('sku', 'NOT LIKE', 'RAW-RB%')
            ->orderBy('category_id')
            ->orderBy('sku')
            ->get();

        $rawMaterials = [];
        foreach ($rawMaterialsQuery as $p) {
            $isGreenBean = str_starts_with((string) $p->sku, 'RAW-GB');

            if ($p->unit === 'gram') {
                $orderUnit = 'kg';
                $unitPriceRef = round((float) $p->cost_price * 1000);
            } elseif ($p->unit === 'ml') {
                $orderUnit = 'liter';
                $unitPriceRef = round((float) $p->cost_price * 1000);
            } else {
                $orderUnit = (string) ($p->unit ?: 'pcs');
                $unitPriceRef = round((float) $p->cost_price);
            }

            $rawMaterials[] = [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category_name' => $p->category?->name ?? ($isGreenBean ? 'Biji Mentah (Green Beans)' : 'Bahan Baku Operasional'),
                'is_green_bean' => $isGreenBean,
                'unit' => $p->unit,
                'order_unit' => $orderUnit,
                'cost_per_kg' => $unitPriceRef,
                'unit_price_ref' => $unitPriceRef,
            ];
        }

        return view('procurement.index', compact(
            'suppliers',
            'purchaseOrders',
            'qualityControls',
            'lots',
            'branches',
            'warehouses',
            'totalContractedBeansKg',
            'avgSupplierRating',
            'rawMaterials'
        ));
    }

    public function storeSupplier(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'contact_person' => 'required|string|max:255',
                'phone' => 'required|string|max:50',
                'address' => 'required|string',
                'farm_location' => 'nullable|string|max:255',
                'altitude_masl' => 'nullable|integer|min:0',
                'rating_score' => 'nullable|numeric|between:0,5',
            ]);

            $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $validated['name']) ?: 'SUP', 0, 4));
            $code = 'SUP-'.$prefix.'-'.rand(10, 99);
            while (Supplier::where('code', $code)->exists()) {
                $code = 'SUP-'.$prefix.'-'.rand(100, 999);
            }

            $validated['code'] = $code;
            $validated['farm_location'] = trim((string) ($validated['farm_location'] ?? '')) ?: 'Distributor Nasional';
            $validated['altitude_masl'] = (int) ($validated['altitude_masl'] ?? 0);
            $validated['rating_score'] = $validated['rating_score'] ?? 4.8;
            $validated['is_active'] = true;

            $supplier = Supplier::create($validated);

            return response()->json([
                'success' => true,
                'message' => "Mitra Pemasok '{$supplier->name}' berhasil ditambahkan ke database.",
                'supplier' => $supplier,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data pemasok: '.$e->getMessage(),
            ], 422);
        }
    }

    public function storePo(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'product_id' => 'nullable',
                'variety' => 'nullable|string',
                'custom_variety' => 'nullable|string',
                'sub_variety' => 'nullable|string',
                'weight_kg' => 'required|numeric|min:1',
                'price_per_kg' => 'required|numeric|min:100',
                'notes' => 'nullable|string',
            ]);

            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $branch = Branch::where('type', 'CENTRAL_ROASTERY')->first() ?? Branch::first();
            $user = auth()->user() ?? User::first();

            $totalAmount = round((float) $validated['weight_kg'] * (float) $validated['price_per_kg'], 2);

            $seq = PurchaseOrder::max('id') ? ((int) PurchaseOrder::max('id') + 1) : 1;
            $poNumber = 'PO-'.date('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            while (PurchaseOrder::where('po_number', $poNumber)->exists()) {
                $seq++;
                $poNumber = 'PO-'.date('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            }

            $product = null;
            $productIdInput = $validated['product_id'] ?? null;
            if ($productIdInput && is_numeric($productIdInput)) {
                $product = Product::find((int) $productIdInput);
            }

            $rawVariety = trim((string) ($validated['custom_variety'] ?? $validated['variety'] ?? ''));
            if ($product) {
                $displayItemName = "[{$product->sku}] {$product->name}";
                $defaultNote = str_starts_with((string) $product->sku, 'RAW-GB')
                    ? 'Pengadaan Green Beans Terverifikasi'
                    : "Pengadaan Bahan Baku {$product->name}";
            } else {
                $varietyName = $rawVariety ?: 'Green Beans Specialty';
                $displayItemName = "Green Beans {$varietyName}";
                $defaultNote = 'Pengadaan Green Beans Terverifikasi';
            }

            $subVariety = trim((string) ($validated['sub_variety'] ?? ''));
            if ($subVariety !== '') {
                $displayItemName .= " • {$subVariety}";
            }

            $notes = trim((string) ($validated['notes'] ?? '')) ?: ($subVariety !== '' ? "Sub-lot: {$subVariety}" : $defaultNote);

            $po = DB::transaction(function () use ($poNumber, $supplier, $branch, $user, $totalAmount, $notes, $product, $displayItemName, $validated): PurchaseOrder {
                $po = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'supplier_id' => $supplier->id,
                    'branch_id' => $branch ? $branch->id : 1,
                    'user_id' => $user ? $user->id : 1,
                    'order_date' => now(),
                    'expected_delivery_date' => now()->addDays(7),
                    'total_amount' => $totalAmount,
                    'status' => 'ISSUED',
                    'notes' => $notes,
                ]);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $product?->id,
                    'product_name' => $displayItemName,
                    'quantity_kg' => (float) $validated['weight_kg'],
                    'unit_price' => (float) $validated['price_per_kg'],
                    'subtotal' => $totalAmount,
                ]);

                return $po;
            });

            return response()->json([
                'success' => true,
                'message' => "Purchase Order {$po->po_number} berhasil diterbitkan senilai Rp ".number_format($totalAmount, 0, ',', '.'),
                'po' => $po->load(['supplier', 'items.product']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerbitkan Purchase Order: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Goods Receipt (Penerimaan Barang) untuk PO Bahan Baku Non-Kopi (Susu, Sirup, Madu, Topping, Kemasan).
     * PO Green Beans (RAW-GB%) wajib melewati QC Lab terlebih dahulu.
     */
    public function receiveGoods(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $validated = $request->validate([
                'warehouse_id' => 'nullable|exists:inventory_warehouses,id',
                'notes' => 'nullable|string|max:500',
            ]);

            $result = DB::transaction(function () use ($purchaseOrder, $validated): array {
                $po = PurchaseOrder::with(['supplier', 'items.product'])
                    ->lockForUpdate()
                    ->findOrFail($purchaseOrder->id);

                if ($po->status === 'COMPLETED') {
                    throw new RuntimeException("PO {$po->po_number} sudah berstatus COMPLETED dan diterima sebelumnya.");
                }

                if ($po->status === 'CANCELLED') {
                    throw new RuntimeException("PO {$po->po_number} telah dibatalkan (CANCELLED).");
                }

                if ($po->items->isEmpty()) {
                    throw new RuntimeException("PO {$po->po_number} tidak memiliki item untuk diterima.");
                }

                foreach ($po->items as $item) {
                    $isGreenBeanSku = $item->product && str_starts_with((string) $item->product->sku, 'RAW-GB');
                    $isCustomGreenBean = ! $item->product && str_contains(strtolower((string) $item->product_name), 'green bean');
                    if ($isGreenBeanSku || $isCustomGreenBean) {
                        throw new RuntimeException('PO Biji Mentah (Green Beans) wajib melalui inspeksi QC Lab terlebih dahulu sebelum masuk gudang.');
                    }
                }

                $targetWarehouse = ! empty($validated['warehouse_id'])
                    ? InventoryWarehouse::findOrFail((int) $validated['warehouse_id'])
                    : (InventoryWarehouse::where('code', 'WH-ROAST-HQ')->first()
                        ?? InventoryWarehouse::where('type', '!=', 'TRANSIT')->firstOrFail());

                if ($targetWarehouse->type === 'TRANSIT') {
                    throw new RuntimeException('Gudang tujuan penerimaan PO tidak boleh berupa akun virtual TRANSIT.');
                }

                $receivedSummaries = [];

                foreach ($po->items as $item) {
                    $product = $item->product;
                    if (! $product) {
                        continue;
                    }

                    $orderedQty = (float) $item->quantity_kg;
                    $orderedUnitPrice = (float) $item->unit_price;

                    if (in_array($product->unit, ['gram', 'ml'], true)) {
                        $qtyInBaseUnit = round($orderedQty * 1000, 4);
                        $costPerBaseUnit = $orderedUnitPrice > 0 ? round($orderedUnitPrice / 1000, 2) : (float) $product->cost_price;
                    } else {
                        $qtyInBaseUnit = round($orderedQty, 4);
                        $costPerBaseUnit = $orderedUnitPrice > 0 ? round($orderedUnitPrice, 2) : (float) $product->cost_price;
                    }

                    $stock = WarehouseStock::where('warehouse_id', $targetWarehouse->id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock) {
                        $stock = WarehouseStock::create([
                            'warehouse_id' => $targetWarehouse->id,
                            'product_id' => $product->id,
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                            'safety_stock_threshold' => $product->unit === 'pcs' ? 200 : 5000,
                        ]);
                    }

                    $stock->increment('quantity', $qtyInBaseUnit);
                    if ($costPerBaseUnit > 0) {
                        $product->update(['cost_price' => $costPerBaseUnit]);
                    }

                    $extraNote = trim((string) ($validated['notes'] ?? ''));
                    $cardNote = "Penerimaan PO {$po->po_number} — {$product->name}".($extraNote !== '' ? " ({$extraNote})" : '');

                    StockCard::create([
                        'warehouse_id' => $targetWarehouse->id,
                        'product_id' => $product->id,
                        'lot_id' => null,
                        'reference_type' => 'PO_RECEIPT',
                        'reference_id' => $po->id,
                        'quantity_in' => $qtyInBaseUnit,
                        'quantity_out' => 0,
                        'balance_after' => (float) $stock->fresh()->quantity,
                        'cost_per_unit' => $costPerBaseUnit,
                        'notes' => $cardNote,
                    ]);

                    $receivedSummaries[] = "{$product->sku} (+".number_format($qtyInBaseUnit, 0, ',', '.')." {$product->unit})";
                }

                $po->update(['status' => 'COMPLETED']);

                return [
                    'po' => $po->fresh(['supplier', 'items.product']),
                    'warehouse_name' => $targetWarehouse->name,
                    'summary' => implode(', ', $receivedSummaries),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Penerimaan PO {$result['po']->po_number} berhasil masuk ke {$result['warehouse_name']}: {$result['summary']}.",
                'po' => $result['po'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses penerimaan PO: '.$e->getMessage(),
            ], 422);
        }
    }

    public function storeQc(Request $request): JsonResponse
    {
        return app(QcLabController::class)->store($request);
    }
}
