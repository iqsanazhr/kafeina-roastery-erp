<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\QualityControl;
use App\Models\StockCard;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QcLabController extends Controller
{
    public function index()
    {
        $qualityControls = QualityControl::with(['lot.purchaseOrder.supplier', 'inspector'])->latest()->get();
        $lots = GreenBeanLot::with('purchaseOrder.supplier')->latest()->get();

        // Tampilkan PO yang masih aktif (ISSUED / PARTIAL_RECEIVED) khusus Green Beans atau Custom Lot
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items.product'])
            ->whereIn('status', ['ISSUED', 'PARTIAL_RECEIVED', 'DRAFT'])
            ->latest()
            ->get()
            ->filter(function (PurchaseOrder $po): bool {
                $firstItem = $po->items->first();
                if (! $firstItem) {
                    return true;
                }

                if ($firstItem->product) {
                    return str_starts_with((string) $firstItem->product->sku, 'RAW-GB');
                }

                return str_contains(strtolower((string) $firstItem->product_name), 'green bean');
            })
            ->values();

        $branches = Branch::all();

        $totalTests = $qualityControls->count();
        $acceptedCount = $qualityControls->where('qc_status', 'ACCEPTED')->count();
        $avgMoisture = $qualityControls->avg('moisture_content') ? round((float) $qualityControls->avg('moisture_content'), 1) : 11.2;
        $avgSca = $qualityControls->avg('sca_cupping_score') ? round((float) $qualityControls->avg('sca_cupping_score'), 1) : 84.5;

        return view('qc_lab.index', compact(
            'qualityControls',
            'lots',
            'purchaseOrders',
            'branches',
            'totalTests',
            'acceptedCount',
            'avgMoisture',
            'avgSca'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'po_id' => 'required|exists:purchase_orders,id',
                'origin_code' => 'required|string|max:20',
                'variety' => 'required|string|max:255',
                'process_type' => 'required|string|max:100',
                'harvest_year' => 'required|integer|min:2020|max:2100',
                'weight_kg' => 'required|numeric|min:1',
                'moisture_content' => 'required|numeric|between:5,20',
                'bulk_density_g_l' => 'required|numeric|min:300|max:1200',
                'primary_defects' => 'required|integer|min:0',
                'secondary_defects' => 'required|integer|min:0',
                'sca_cupping_score' => 'required|numeric|between:60,100',
                'notes' => 'nullable|string',
            ]);

            $result = self::recordInspection($validated);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'qc' => $result['qc'],
                'po_id' => $result['po_id'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses hasil QC: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eksekusi inspeksi QC Lab secara atomik:
     * 1. Validasi PO belum COMPLETED.
     * 2. Hitung price_per_gram riil dari PurchaseOrderItem.
     * 3. Terbitkan GreenBeanLot & QualityControl.
     * 4. Jika ACCEPTED/DOWNGRADED: tambah saldo WH-RAW-HQ + catat StockCard (PO_RECEIPT) + sinkronisasi cost_price.
     * 5. Perbarui status PurchaseOrder menjadi COMPLETED.
     *
     * @param  array<string, mixed>  $validated
     * @return array{qc: QualityControl, message: string, po_id: int}
     */
    public static function recordInspection(array $validated): array
    {
        return DB::transaction(function () use ($validated): array {
            $po = PurchaseOrder::with('items.product')
                ->lockForUpdate()
                ->findOrFail((int) $validated['po_id']);

            if ($po->status === 'COMPLETED') {
                throw new RuntimeException("PO {$po->po_number} sudah selesai diproses (COMPLETED) dan tidak dapat diinspeksi ulang.");
            }

            if ($po->status === 'CANCELLED') {
                throw new RuntimeException("PO {$po->po_number} berstatus dibatalkan (CANCELLED).");
            }

            $year = (int) $validated['harvest_year'];
            $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $validated['origin_code']) ?: 'GB');
            $seq = GreenBeanLot::max('id') ? ((int) GreenBeanLot::max('id') + 1) : 1;
            $lotNumber = sprintf('GB-%s-%d-%03d', $code, $year, $seq);
            while (GreenBeanLot::where('lot_number', $lotNumber)->exists()) {
                $seq++;
                $lotNumber = sprintf('GB-%s-%d-%03d', $code, $year, $seq);
            }

            $moisture = (float) $validated['moisture_content'];
            $primary = (int) $validated['primary_defects'];
            $sca = (float) $validated['sca_cupping_score'];

            if ($moisture >= 10.0 && $moisture <= 12.0 && $primary === 0 && $sca >= 80.0) {
                $status = 'ACCEPTED';
            } elseif ($moisture <= 13.0 && $primary <= 2 && $sca >= 75.0) {
                $status = 'DOWNGRADED';
            } else {
                $status = 'REJECTED';
            }

            // Ambil harga beli riil dari PO Item (Rp/kg -> Rp/gram)
            $poItem = $po->items->first();
            $pricePerGram = 120.00;
            if ($poItem && (float) $poItem->unit_price > 0) {
                $unitPrice = (float) $poItem->unit_price;
                $pricePerGram = $unitPrice > 1000 ? round($unitPrice / 1000, 2) : round($unitPrice, 2);
            }

            $weightGrams = round((float) $validated['weight_kg'] * 1000, 4);

            $lot = GreenBeanLot::create([
                'lot_number' => $lotNumber,
                'purchase_order_id' => $po->id,
                'variety' => (string) $validated['variety'],
                'origin_region' => (string) $validated['origin_code'],
                'process_type' => (string) $validated['process_type'],
                'harvest_year' => $year,
                'initial_weight_grams' => $weightGrams,
                'current_weight_grams' => $status === 'REJECTED' ? 0 : $weightGrams,
                'price_per_gram' => $pricePerGram,
            ]);

            $inspectorId = auth()->id() ?? (User::where('role_id', function ($q) {
                $q->select('id')->from('roles')->whereIn('name', ['qc_inspector', 'qc']);
            })->value('id') ?? User::first()?->id ?? 1);

            $qc = QualityControl::create([
                'lot_id' => $lot->id,
                'inspector_id' => $inspectorId,
                'inspection_date' => now(),
                'moisture_content' => $moisture,
                'bulk_density_g_l' => (float) $validated['bulk_density_g_l'],
                'primary_defects' => $primary,
                'secondary_defects' => (int) $validated['secondary_defects'],
                'sca_cupping_score' => $sca,
                'qc_status' => $status,
                'notes' => $validated['notes'] ?? 'Pemeriksaan laboratorium mutu biji kopi mentah standar SCAA',
            ]);

            $stockSyncNote = '';
            if ($status !== 'REJECTED') {
                $whRaw = InventoryWarehouse::where('code', 'WH-RAW-HQ')
                    ->orWhere('type', 'CENTRAL_RAW')
                    ->first();

                $greenProd = self::resolveGreenBeanProduct($poItem?->product, (string) $validated['origin_code'], (string) $validated['variety']);

                if ($whRaw && $greenProd) {
                    $stock = WarehouseStock::where('warehouse_id', $whRaw->id)
                        ->where('product_id', $greenProd->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock) {
                        $stock = WarehouseStock::create([
                            'warehouse_id' => $whRaw->id,
                            'product_id' => $greenProd->id,
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                            'safety_stock_threshold' => 100000,
                        ]);
                    }

                    $stock->increment('quantity', $weightGrams);
                    $greenProd->update(['cost_price' => $pricePerGram]);

                    StockCard::create([
                        'warehouse_id' => $whRaw->id,
                        'product_id' => $greenProd->id,
                        'lot_id' => $lot->id,
                        'reference_type' => 'PO_RECEIPT',
                        'reference_id' => $po->id,
                        'quantity_in' => $weightGrams,
                        'quantity_out' => 0,
                        'balance_after' => (float) $stock->fresh()->quantity,
                        'cost_per_unit' => $pricePerGram,
                        'notes' => "Penerimaan QC Lab ({$status}) Lot {$lotNumber} dari {$po->po_number}",
                    ]);

                    $stockSyncNote = " Stok {$greenProd->sku} di {$whRaw->code} bertambah ".number_format((float) $validated['weight_kg'], 1, ',', '.').' kg.';
                }
            }

            $po->update(['status' => 'COMPLETED']);

            return [
                'qc' => $qc->load(['lot.purchaseOrder.supplier', 'inspector']),
                'po_id' => $po->id,
                'message' => "Pengujian mutu selesai. Kode Lot '{$lotNumber}' diterbitkan ({$status}, HPP Rp ".number_format($pricePerGram, 0, ',', '.')."/g).{$stockSyncNote}",
            ];
        });
    }

    /**
     * Resolusi Master SKU Green Beans berdasarkan link PO atau origin/varietas.
     */
    private static function resolveGreenBeanProduct(?Product $poProduct, string $originCode, string $variety): ?Product
    {
        if ($poProduct && str_starts_with((string) $poProduct->sku, 'RAW-GB')) {
            return $poProduct;
        }

        $haystack = strtolower($originCode.' '.$variety);

        $skuMap = [
            'RAW-GB-GAYO' => ['gayo', 'gyo', 'aceh'],
            'RAW-GB-IJEN' => ['ijen', 'ijn', 'bondowoso'],
            'RAW-GB-FLORES' => ['flores', 'bajawa', 'flr', 'ngada'],
            'RAW-GB-KINTA' => ['kintamani', 'kinta', 'knt', 'bali'],
            'RAW-GB-TORAJA' => ['toraja', 'trj', 'sapan', 'sulawesi'],
        ];

        foreach ($skuMap as $sku => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($haystack, $kw)) {
                    $found = Product::where('sku', $sku)->first();
                    if ($found) {
                        return $found;
                    }
                }
            }
        }

        return Product::where('sku', 'LIKE', 'RAW-GB%')->first();
    }
}
