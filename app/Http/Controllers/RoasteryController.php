<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\Product;
use App\Models\RoastingProfile;
use App\Models\StockCard;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RoasteryController extends Controller
{
    public function index()
    {
        $workOrders = WorkOrder::with(['lot', 'roastingProfile', 'roaster'])->latest()->get();
        $roastingProfiles = RoastingProfile::all();
        $lots = GreenBeanLot::where('current_weight_grams', '>', 0)->get();
        if ($lots->isEmpty()) {
            $lots = GreenBeanLot::all();
        }
        $boms = Bom::with(['product', 'items.materialProduct'])->get();
        $roasters = User::all();

        return view('roastery.index', compact('workOrders', 'roastingProfiles', 'lots', 'boms', 'roasters'));
    }

    public function storeWorkOrder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lot_id' => 'required|exists:green_bean_lots,id',
                'profile_id' => 'required|exists:roasting_profiles,id',
                'green_weight_kg' => 'required|numeric|min:1',
                'notes' => 'nullable|string',
            ]);

            $wo = DB::transaction(function () use ($validated): WorkOrder {
                $lot = GreenBeanLot::lockForUpdate()->findOrFail((int) $validated['lot_id']);
                $greenGrams = round((float) $validated['green_weight_kg'] * 1000, 4);

                if ((float) $lot->current_weight_grams < $greenGrams) {
                    $availableKg = round((float) $lot->current_weight_grams / 1000, 2);
                    throw new RuntimeException(
                        "Stok lot {$lot->lot_number} tidak mencukupi (tersedia {$availableKg} kg, diminta {$validated['green_weight_kg']} kg)."
                    );
                }

                $seq = WorkOrder::max('id') ? ((int) WorkOrder::max('id') + 1) : 1;
                $woNumber = 'WO-'.date('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                while (WorkOrder::where('wo_number', $woNumber)->exists()) {
                    $seq++;
                    $woNumber = 'WO-'.date('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                }

                $roasterId = auth()->id() ?? (User::where('role_id', function ($q) {
                    $q->select('id')->from('roles')->whereIn('name', ['roaster', 'head_roaster', 'HEAD_ROASTER']);
                })->value('id') ?? User::first()?->id ?? 1);

                $wo = WorkOrder::create([
                    'wo_number' => $woNumber,
                    'lot_id' => $lot->id,
                    'profile_id' => (int) $validated['profile_id'],
                    'roaster_id' => $roasterId,
                    'green_weight_grams' => $greenGrams,
                    'roasted_weight_grams' => null,
                    'yield_loss_percentage' => null,
                    'status' => 'SCHEDULED',
                    'roast_start_time' => null,
                    'roast_end_time' => null,
                    'notes' => $validated['notes'] ?? 'Jadwal batch sangrai mesin roaster sentral Giesen W15A',
                ]);

                // Reservasi stok biji mentah di WH-RAW-HQ agar tidak bentrok dengan jadwal lain
                $whRaw = InventoryWarehouse::where('code', 'WH-RAW-HQ')->orWhere('type', 'CENTRAL_RAW')->first();
                $greenProd = self::resolveGreenBeanProductForLot($lot);
                if ($whRaw && $greenProd) {
                    $stockRaw = WarehouseStock::where('warehouse_id', $whRaw->id)
                        ->where('product_id', $greenProd->id)
                        ->lockForUpdate()
                        ->first();
                    if ($stockRaw) {
                        $stockRaw->increment('reserved_quantity', $greenGrams);
                    }
                }

                return $wo;
            });

            return response()->json([
                'success' => true,
                'message' => "Work Order '{$wo->wo_number}' berhasil dijadwalkan.",
                'work_order' => $wo->load(['lot', 'roastingProfile', 'roaster']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat Work Order: '.$e->getMessage(),
            ], 422);
        }
    }

    public function executeRoast(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'wo_id' => 'required|exists:work_orders,id',
                'green_weight_kg' => 'required|numeric|min:1',
                'roasted_weight_kg' => 'required|numeric|min:0.5',
                'roast_duration_minutes' => 'nullable|numeric|min:1|max:120',
                'target_product_id' => 'nullable|exists:products,id',
            ]);

            $greenGrams = round((float) $validated['green_weight_kg'] * 1000, 4);
            $roastedGrams = round((float) $validated['roasted_weight_kg'] * 1000, 4);

            if ($roastedGrams >= $greenGrams) {
                return response()->json([
                    'success' => false,
                    'message' => 'Berat hasil sangrai (roasted) harus lebih kecil dari berat biji mentah (green beans).',
                ], 422);
            }

            $result = DB::transaction(function () use ($validated, $greenGrams, $roastedGrams): array {
                $wo = WorkOrder::with(['lot', 'roastingProfile', 'roaster'])
                    ->lockForUpdate()
                    ->findOrFail((int) $validated['wo_id']);

                if ($wo->status === 'COMPLETED') {
                    throw new RuntimeException("Work Order {$wo->wo_number} sudah berstatus COMPLETED dan tidak dapat dieksekusi ulang.");
                }

                $originalAllocatedGrams = (float) $wo->green_weight_grams;

                // Yield Loss formula: ((green - roasted) / green) * 100
                $lossPct = (($greenGrams - $roastedGrams) / $greenGrams) * 100;
                $yieldLossRounded = round($lossPct, 2);

                $statusNote = 'OPTIMAL (14-18%)';
                if ($yieldLossRounded < 14.0) {
                    $statusNote = 'WARNING: Under-developed (<14%)';
                } elseif ($yieldLossRounded > 18.0) {
                    $statusNote = 'WARNING: Over-roasted (>18%)';
                }

                // Real Cost per Gram (HPP Riil Sangrai)
                $basePricePerGram = ($wo->lot && (float) $wo->lot->price_per_gram > 0)
                    ? (float) $wo->lot->price_per_gram
                    : 130.0;
                $realHppPerGram = round(($basePricePerGram * ($greenGrams / max(1.0, $roastedGrams))) + 15.0, 2);

                $durationMin = (int) round((float) ($validated['roast_duration_minutes'] ?? 12));

                $wo->update([
                    'green_weight_grams' => $greenGrams,
                    'roasted_weight_grams' => $roastedGrams,
                    'yield_loss_percentage' => $yieldLossRounded,
                    'status' => 'COMPLETED',
                    'roast_start_time' => now()->subMinutes(max(1, $durationMin)),
                    'roast_end_time' => now(),
                    'notes' => "Hasil sangrai: Susut {$yieldLossRounded}% [{$statusNote}]. HPP Riil: Rp {$realHppPerGram}/g.",
                ]);

                $whRaw = InventoryWarehouse::where('code', 'WH-RAW-HQ')->orWhere('type', 'CENTRAL_RAW')->first();
                $whRoast = InventoryWarehouse::where('code', 'WH-ROAST-HQ')->orWhere('type', 'CENTRAL_ROASTED')->first();

                // 1. Potong Green Beans dari Silo Sentral
                if ($whRaw && $wo->lot_id) {
                    $lotLocked = GreenBeanLot::lockForUpdate()->find($wo->lot_id);
                    $greenProd = $lotLocked instanceof GreenBeanLot
                        ? self::resolveGreenBeanProductForLot($lotLocked)
                        : null;

                    if ($greenProd) {
                        $stockRaw = WarehouseStock::where('warehouse_id', $whRaw->id)
                            ->where('product_id', $greenProd->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $stockRaw) {
                            $stockRaw = WarehouseStock::create([
                                'warehouse_id' => $whRaw->id,
                                'product_id' => $greenProd->id,
                                'quantity' => 0,
                                'reserved_quantity' => 0,
                                'safety_stock_threshold' => 100000,
                            ]);
                        }

                        $deductQty = min($greenGrams, (float) $stockRaw->quantity);
                        if ($deductQty > 0) {
                            $stockRaw->decrement('quantity', $deductQty);
                        }

                        $releaseReserve = min($originalAllocatedGrams, (float) $stockRaw->reserved_quantity);
                        if ($releaseReserve > 0) {
                            $stockRaw->decrement('reserved_quantity', $releaseReserve);
                        }

                        StockCard::create([
                            'warehouse_id' => $whRaw->id,
                            'product_id' => $greenProd->id,
                            'lot_id' => $wo->lot_id,
                            'reference_type' => 'ROAST_CHARGE',
                            'reference_id' => $wo->id,
                            'quantity_in' => 0,
                            'quantity_out' => $greenGrams,
                            'balance_after' => (float) $stockRaw->fresh()->quantity,
                            'cost_per_unit' => $basePricePerGram,
                            'notes' => "Konsumsi bahan baku sangrai Work Order {$wo->wo_number}",
                        ]);
                    }

                    if ($lotLocked && (float) $lotLocked->current_weight_grams > 0) {
                        $lotLocked->decrement('current_weight_grams', min($greenGrams, (float) $lotLocked->current_weight_grams));
                    }
                }

                // 2. Tambah Roasted Beans ke Gudang Biji Sangrai Sentral
                $outputSku = 'RAW-RB-HOUSE';
                if ($whRoast) {
                    $roastedProd = ! empty($validated['target_product_id'])
                        ? Product::find((int) $validated['target_product_id'])
                        : null;

                    if (! $roastedProd) {
                        $roastedProd = Product::where('sku', 'RAW-RB-HOUSE')->first()
                            ?? Product::where('sku', 'LIKE', 'RAW-RB%')->first();
                    }

                    if ($roastedProd) {
                        $outputSku = (string) $roastedProd->sku;
                        $stockRoast = WarehouseStock::where('warehouse_id', $whRoast->id)
                            ->where('product_id', $roastedProd->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $stockRoast) {
                            $stockRoast = WarehouseStock::create([
                                'warehouse_id' => $whRoast->id,
                                'product_id' => $roastedProd->id,
                                'quantity' => 0,
                                'reserved_quantity' => 0,
                                'safety_stock_threshold' => 15000,
                            ]);
                        }

                        $stockRoast->increment('quantity', $roastedGrams);
                        $roastedProd->update(['cost_price' => $realHppPerGram]);

                        StockCard::create([
                            'warehouse_id' => $whRoast->id,
                            'product_id' => $roastedProd->id,
                            'lot_id' => $wo->lot_id,
                            'reference_type' => 'ROAST_OUTPUT',
                            'reference_id' => $wo->id,
                            'quantity_in' => $roastedGrams,
                            'quantity_out' => 0,
                            'balance_after' => (float) $stockRoast->fresh()->quantity,
                            'cost_per_unit' => $realHppPerGram,
                            'notes' => "Hasil produksi biji sangrai matang Work Order {$wo->wo_number} → {$roastedProd->sku} (Yield Loss: {$yieldLossRounded}%)",
                        ]);
                    }
                }

                return [
                    'work_order' => $wo->fresh(['lot', 'roastingProfile', 'roaster']),
                    'yield_loss_pct' => $yieldLossRounded,
                    'real_hpp_per_gram' => $realHppPerGram,
                    'status_note' => $statusNote,
                    'output_sku' => $outputSku,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Batch sangrai selesai ({$result['output_sku']}). Susut: {$result['yield_loss_pct']}% ({$result['status_note']}). HPP Riil: Rp {$result['real_hpp_per_gram']}/gram.",
                'work_order' => $result['work_order'],
                'yield_loss_pct' => $result['yield_loss_pct'],
                'real_hpp_per_gram' => $result['real_hpp_per_gram'],
                'output_sku' => $result['output_sku'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengeksekusi batch sangrai: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eksekusi Pengemasan Retail 250g berdasarkan BOM:
     * Potong Bulk Roasted + Pouch + Label dari WH-ROAST-HQ -> Tambah produk jadi retail (PRD-RET-01/02).
     */
    public function packRetail(Request $request, Bom $bom): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity_packs' => 'required|integer|min:1|max:5000',
                'notes' => 'nullable|string|max:500',
            ]);

            $packs = (int) $validated['quantity_packs'];

            $result = DB::transaction(function () use ($bom, $packs, $validated) {
                $bom->load(['product', 'items.materialProduct']);
                $finishedProduct = $bom->product;

                if (! $finishedProduct instanceof Product || $bom->items->isEmpty()) {
                    throw new RuntimeException('Formula BOM kemasan belum lengkap atau produk tujuan tidak ditemukan.');
                }

                $whRoast = InventoryWarehouse::where('code', 'WH-ROAST-HQ')
                    ->orWhere('type', 'CENTRAL_ROASTED')
                    ->firstOrFail();

                $totalProductionCost = 0.0;

                // 1. Validasi & potong seluruh komponen BOM secara atomik
                foreach ($bom->items as $item) {
                    $material = $item->materialProduct;
                    if (! $material instanceof Product) {
                        continue;
                    }

                    $requiredQty = round((float) $item->quantity_required * $packs, 4);

                    $stock = WarehouseStock::where('warehouse_id', $whRoast->id)
                        ->where('product_id', $material->id)
                        ->lockForUpdate()
                        ->first();

                    $available = $stock ? (float) $stock->quantity : 0.0;
                    if ($available < $requiredQty) {
                        throw new RuntimeException(
                            "Stok {$material->sku} ({$material->name}) di {$whRoast->code} tidak mencukupi (tersedia ".number_format($available, 0, ',', '.')." {$material->unit}, butuh ".number_format($requiredQty, 0, ',', '.')." {$material->unit})."
                        );
                    }

                    $stock->decrement('quantity', $requiredQty);
                    $unitCost = (float) $material->cost_price;
                    $totalProductionCost += $requiredQty * $unitCost;

                    StockCard::create([
                        'warehouse_id' => $whRoast->id,
                        'product_id' => $material->id,
                        'lot_id' => null,
                        'reference_type' => 'ADJUSTMENT',
                        'reference_id' => $bom->id,
                        'quantity_in' => 0,
                        'quantity_out' => $requiredQty,
                        'balance_after' => (float) $stock->fresh()->quantity,
                        'cost_per_unit' => $unitCost,
                        'notes' => "Kemas Retail BOM {$bom->name}: konsumsi untuk {$packs} pack {$finishedProduct->sku}",
                    ]);
                }

                // 2. Tambahkan stok produk jadi retail ke WH-ROAST-HQ
                $unitFinishedCost = $packs > 0 ? round($totalProductionCost / $packs, 2) : (float) $finishedProduct->cost_price;

                $finishedStock = WarehouseStock::where('warehouse_id', $whRoast->id)
                    ->where('product_id', $finishedProduct->id)
                    ->lockForUpdate()
                    ->first();

                if (! $finishedStock) {
                    $finishedStock = WarehouseStock::create([
                        'warehouse_id' => $whRoast->id,
                        'product_id' => $finishedProduct->id,
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                        'safety_stock_threshold' => 20,
                    ]);
                }

                $finishedStock->increment('quantity', $packs);
                if ($unitFinishedCost > 0) {
                    $finishedProduct->update(['cost_price' => $unitFinishedCost]);
                }

                $extraNote = trim((string) ($validated['notes'] ?? ''));
                StockCard::create([
                    'warehouse_id' => $whRoast->id,
                    'product_id' => $finishedProduct->id,
                    'lot_id' => null,
                    'reference_type' => 'ADJUSTMENT',
                    'reference_id' => $bom->id,
                    'quantity_in' => $packs,
                    'quantity_out' => 0,
                    'balance_after' => (float) $finishedStock->fresh()->quantity,
                    'cost_per_unit' => $unitFinishedCost,
                    'notes' => "Hasil Kemas Retail BOM {$bom->name}: +{$packs} {$finishedProduct->unit}".($extraNote !== '' ? " ({$extraNote})" : ''),
                ]);

                return [
                    'product_name' => $finishedProduct->name,
                    'sku' => $finishedProduct->sku,
                    'packs' => $packs,
                    'balance_after' => (float) $finishedStock->fresh()->quantity,
                    'unit_cost' => $unitFinishedCost,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Kemas retail selesai: +{$result['packs']} pack [{$result['sku']}] {$result['product_name']} (HPP Rp ".number_format($result['unit_cost'], 0, ',', '.').'/pack, saldo gudang: '.number_format($result['balance_after'], 0, ',', '.').').',
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengemas produk retail: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cocokkan lot biji mentah ke Master SKU RAW-GB-* (mencakup seluruh 5 origin).
     */
    private static function resolveGreenBeanProductForLot(GreenBeanLot $lot): ?Product
    {
        $haystack = strtolower(($lot->origin_region ?? '').' '.($lot->variety ?? ''));

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

        return Product::where('is_raw_material', true)
            ->where('sku', 'LIKE', 'RAW-GB%')
            ->first();
    }
}
