<?php

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

            $woNumber = 'WO-'.date('Ym').'-'.str_pad((string) (WorkOrder::count() + 1), 3, '0', STR_PAD_LEFT);
            $roasterId = auth()->id() ?? (User::where('role_id', function ($q) {
                $q->select('id')->from('roles')->whereIn('name', ['roaster', 'head_roaster', 'HEAD_ROASTER']);
            })->value('id') ?? User::first()?->id ?? 1);

            $wo = WorkOrder::create([
                'wo_number' => $woNumber,
                'lot_id' => $validated['lot_id'],
                'profile_id' => $validated['profile_id'],
                'roaster_id' => $roasterId,
                'green_weight_grams' => $validated['green_weight_kg'] * 1000,
                'roasted_weight_grams' => null,
                'yield_loss_percentage' => null,
                'status' => 'SCHEDULED',
                'roast_start_time' => null,
                'roast_end_time' => null,
                'notes' => $validated['notes'] ?? 'Jadwal batch sangrai mesin roaster sentral Giesen W15A',
            ]);

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
                'roast_duration_minutes' => 'nullable|numeric',
            ]);

            $wo = WorkOrder::with('lot')->findOrFail($validated['wo_id']);
            $greenGrams = $validated['green_weight_kg'] * 1000;
            $roastedGrams = $validated['roasted_weight_kg'] * 1000;

            // Yield Loss formula: ((green - roasted) / green) * 100
            $lossPct = (($greenGrams - $roastedGrams) / $greenGrams) * 100;
            $yieldLossRounded = round($lossPct, 2);

            // Quality check
            $statusNote = 'OPTIMAL (14-18%)';
            if ($yieldLossRounded < 14.0) {
                $statusNote = 'WARNING: Under-developed (<14%)';
            } elseif ($yieldLossRounded > 18.0) {
                $statusNote = 'WARNING: Over-roasted (>18%)';
            }

            // Real Cost per Gram (HPP Riil Sangrai)
            $basePricePerGram = $wo->lot ? (float) $wo->lot->price_per_gram : 130.0;
            $realHppPerGram = round(($basePricePerGram * ($greenGrams / max(1, $roastedGrams))) + 15.0, 2);

            $wo->update([
                'green_weight_grams' => $greenGrams,
                'roasted_weight_grams' => $roastedGrams,
                'yield_loss_percentage' => $yieldLossRounded,
                'status' => 'COMPLETED',
                'roast_start_time' => now()->subMinutes(12),
                'roast_end_time' => now(),
                'notes' => "Hasil sangrai: Susut {$yieldLossRounded}% [{$statusNote}]. HPP Riil: Rp {$realHppPerGram}/g.",
            ]);

            // Mutasi Stok Riil Gudang Sentral (Stock Movement)
            $whRaw = InventoryWarehouse::where('code', 'WH-RAW-HQ')->orWhere('type', 'CENTRAL_RAW')->first();
            $whRoast = InventoryWarehouse::where('code', 'WH-ROAST-HQ')->orWhere('type', 'CENTRAL_ROASTED')->first();

            // 1. Potong Green Beans dari Silo Sentral
            if ($whRaw && $wo->lot) {
                // Temukan produk raw material yang berasosiasi
                $greenProd = Product::where('is_raw_material', true)
                    ->where(function ($q) use ($wo) {
                        $origin = strtolower($wo->lot->origin_region ?? '');
                        if (str_contains($origin, 'gayo')) {
                            $q->where('sku', 'RAW-GB-GAYO');
                        } elseif (str_contains($origin, 'ijen')) {
                            $q->where('sku', 'RAW-GB-IJEN');
                        } else {
                            $q->where('sku', 'LIKE', 'RAW-GB%');
                        }
                    })->first() ?? Product::where('sku', 'LIKE', 'RAW-GB%')->first();

                if ($greenProd) {
                    $stockRaw = WarehouseStock::firstOrCreate(
                        ['warehouse_id' => $whRaw->id, 'product_id' => $greenProd->id],
                        ['quantity' => 0, 'safety_stock_threshold' => 100000]
                    );

                    $stockRaw->decrement('quantity', min($greenGrams, (float) $stockRaw->quantity));

                    StockCard::create([
                        'warehouse_id' => $whRaw->id,
                        'product_id' => $greenProd->id,
                        'lot_id' => $wo->lot_id,
                        'reference_type' => 'ROAST_CHARGE',
                        'reference_id' => $wo->id,
                        'quantity_in' => 0,
                        'quantity_out' => $greenGrams,
                        'balance_after' => $stockRaw->fresh()->quantity,
                        'cost_per_unit' => $basePricePerGram,
                        'notes' => "Konsumsi bahan baku sangrai Work Order {$wo->wo_number}",
                    ]);
                }

                // Update saldo lot
                if ($wo->lot->current_weight_grams > 0) {
                    $wo->lot->decrement('current_weight_grams', min($greenGrams, (float) $wo->lot->current_weight_grams));
                }
            }

            // 2. Tambah Roasted Beans ke Gudang Biji Sangrai Sentral
            if ($whRoast) {
                $roastedProd = Product::where('sku', 'RAW-RB-HOUSE')->first()
                    ?? Product::where('sku', 'LIKE', 'RAW-RB%')->first();

                if ($roastedProd) {
                    $stockRoast = WarehouseStock::firstOrCreate(
                        ['warehouse_id' => $whRoast->id, 'product_id' => $roastedProd->id],
                        ['quantity' => 0, 'safety_stock_threshold' => 15000]
                    );

                    $stockRoast->increment('quantity', $roastedGrams);

                    StockCard::create([
                        'warehouse_id' => $whRoast->id,
                        'product_id' => $roastedProd->id,
                        'lot_id' => $wo->lot_id,
                        'reference_type' => 'ROAST_OUTPUT',
                        'reference_id' => $wo->id,
                        'quantity_in' => $roastedGrams,
                        'quantity_out' => 0,
                        'balance_after' => $stockRoast->fresh()->quantity,
                        'cost_per_unit' => $realHppPerGram,
                        'notes' => "Hasil produksi biji sangrai matang Work Order {$wo->wo_number} (Yield Loss: {$yieldLossRounded}%)",
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Batch sangrai selesai. Susut: {$yieldLossRounded}% ({$statusNote}). HPP Riil: Rp {$realHppPerGram}/gram.",
                'work_order' => $wo->load(['lot', 'roastingProfile', 'roaster']),
                'yield_loss_pct' => $yieldLossRounded,
                'real_hpp_per_gram' => $realHppPerGram,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengeksekusi batch sangrai: '.$e->getMessage(),
            ], 422);
        }
    }
}
