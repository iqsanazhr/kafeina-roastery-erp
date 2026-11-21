<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\GreenBeanLot;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\QualityControl;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcurementController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount('purchaseOrders')->get();
        $purchaseOrders = PurchaseOrder::with(['supplier', 'branch', 'items'])->latest()->get();
        $qualityControls = QualityControl::with(['lot.purchaseOrder.supplier', 'inspector'])->latest()->get();
        $lots = GreenBeanLot::with('purchaseOrder')->latest()->get();
        $branches = Branch::all();
        $totalContractedBeansKg = round((float) PurchaseOrderItem::sum('quantity_kg'), 1);
        if ($totalContractedBeansKg <= 0) {
            $totalContractedBeansKg = round((float) GreenBeanLot::sum('initial_weight_grams') / 1000, 1);
        }

        $rawMaterials = Product::where('is_raw_material', true)
            ->where('sku', 'LIKE', 'RAW-GB%')
            ->orderBy('sku')
            ->get()
            ->map(function ($p) {
                $costPerKg = $p->unit === 'gram' ? (float) $p->cost_price * 1000 : (float) $p->cost_price;

                return [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'cost_per_kg' => round($costPerKg),
                    'unit' => $p->unit,
                ];
            });

        return view('procurement.index', compact('suppliers', 'purchaseOrders', 'qualityControls', 'lots', 'branches', 'totalContractedBeansKg', 'rawMaterials'));
    }

    public function storeSupplier(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'contact_person' => 'required|string|max:255',
                'phone' => 'required|string|max:50',
                'address' => 'required|string',
                'farm_location' => 'required|string',
                'altitude_masl' => 'required|integer|min:0',
                'rating_score' => 'nullable|numeric|between:0,5',
            ]);

            $code = 'SUP-'.strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $validated['name']), 0, 4)).'-'.rand(10, 99);
            $validated['code'] = $code;
            $validated['rating_score'] = $validated['rating_score'] ?? 4.8;
            $validated['is_active'] = true;

            $supplier = Supplier::create($validated);

            return response()->json([
                'success' => true,
                'message' => "Mitra Petani '{$supplier->name}' berhasil ditambahkan ke database.",
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
                'price_per_kg' => 'required|numeric|min:1000',
                'notes' => 'nullable|string',
            ]);

            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $branch = Branch::first();
            $user = User::first();

            $totalAmount = $validated['weight_kg'] * $validated['price_per_kg'];
            $poNumber = 'PO-'.date('Ym').'-'.str_pad((string) (PurchaseOrder::count() + 1), 3, '0', STR_PAD_LEFT);

            // Determine Product and Display Name
            $product = null;
            $productIdInput = $validated['product_id'] ?? null;
            if ($productIdInput && is_numeric($productIdInput)) {
                $product = Product::find($productIdInput);
            }

            $rawVariety = trim((string) ($validated['custom_variety'] ?? $validated['variety'] ?? ''));
            if ($product) {
                $varietyName = $product->name;
                $displayItemName = "[{$product->sku}] {$product->name}";
            } else {
                $varietyName = $rawVariety ?: 'Green Beans Specialty';
                $displayItemName = "Green Beans {$varietyName}";
            }

            $subVariety = trim((string) ($validated['sub_variety'] ?? ''));
            if ($subVariety !== '') {
                $displayItemName .= " • {$subVariety}";
            }

            $notes = ($validated['notes'] ?? null) ?: ($subVariety ? "Sub-lot: {$subVariety}" : 'Pengadaan Green Beans Terverifikasi');

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
                'quantity_kg' => $validated['weight_kg'],
                'unit_price' => $validated['price_per_kg'],
                'subtotal' => $totalAmount,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Purchase Order {$po->po_number} berhasil diterbitkan senilai Rp ".number_format($totalAmount, 0, ',', '.'),
                'po' => $po->load(['supplier', 'items']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerbitkan Purchase Order: '.$e->getMessage(),
            ], 422);
        }
    }

    public function storeQc(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'po_id' => 'required|exists:purchase_orders,id',
                'origin_code' => 'required|string',
                'variety' => 'required|string',
                'process_type' => 'required|string',
                'harvest_year' => 'required|integer',
                'weight_kg' => 'required|numeric|min:1',
                'moisture_content' => 'required|numeric|between:5,20',
                'bulk_density_g_l' => 'required|numeric',
                'primary_defects' => 'required|integer|min:0',
                'secondary_defects' => 'required|integer|min:0',
                'sca_cupping_score' => 'required|numeric|between:60,100',
                'notes' => 'nullable|string',
            ]);

            // Auto-generate Lot Number: GB-[KODE]-[TAHUN]-[NO]
            $year = $validated['harvest_year'];
            $code = strtoupper($validated['origin_code']);
            $nextNo = str_pad((string) (GreenBeanLot::count() + 1), 3, '0', STR_PAD_LEFT);
            $lotNumber = "GB-{$code}-{$year}-{$nextNo}";

            $po = PurchaseOrder::findOrFail($validated['po_id']);

            // Determine Status based on SCA rules:
            $moisture = $validated['moisture_content'];
            $primary = $validated['primary_defects'];
            $sca = $validated['sca_cupping_score'];

            if ($moisture >= 10.0 && $moisture <= 12.0 && $primary === 0 && $sca >= 80.0) {
                $status = 'ACCEPTED';
            } elseif ($moisture <= 13.0 && $primary <= 2 && $sca >= 75.0) {
                $status = 'DOWNGRADED';
            } else {
                $status = 'REJECTED';
            }

            $lot = GreenBeanLot::create([
                'lot_number' => $lotNumber,
                'purchase_order_id' => $po->id,
                'variety' => $validated['variety'],
                'origin_region' => $validated['origin_code'],
                'process_type' => $validated['process_type'],
                'harvest_year' => $year,
                'initial_weight_grams' => $validated['weight_kg'] * 1000,
                'current_weight_grams' => $status === 'REJECTED' ? 0 : ($validated['weight_kg'] * 1000),
                'price_per_gram' => 120.00,
            ]);

            $qc = QualityControl::create([
                'lot_id' => $lot->id,
                'inspector_id' => User::first()->id ?? 1,
                'inspection_date' => now(),
                'moisture_content' => $moisture,
                'bulk_density_g_l' => $validated['bulk_density_g_l'],
                'primary_defects' => $primary,
                'secondary_defects' => $validated['secondary_defects'],
                'sca_cupping_score' => $sca,
                'qc_status' => $status,
                'notes' => $validated['notes'] ?? 'Pemeriksaan laboratorium mutu biji kopi mentah standar SCAA',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pengujian mutu selesai. Kode Lot '{$lotNumber}' diterbitkan dengan status: {$status}.",
                'qc' => $qc->load('lot'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses hasil QC: '.$e->getMessage(),
            ], 422);
        }
    }
}
