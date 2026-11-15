<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\GreenBeanLot;
use App\Models\PurchaseOrder;
use App\Models\QualityControl;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QcLabController extends Controller
{
    public function index()
    {
        $qualityControls = QualityControl::with(['lot.purchaseOrder.supplier', 'inspector'])->latest()->get();
        $lots = GreenBeanLot::with('purchaseOrder.supplier')->latest()->get();
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items'])->latest()->get();
        $branches = Branch::all();

        // Calculate summary metrics
        $totalTests = $qualityControls->count();
        $acceptedCount = $qualityControls->where('qc_status', 'ACCEPTED')->count();
        $avgMoisture = $qualityControls->avg('moisture_content') ? round($qualityControls->avg('moisture_content'), 1) : 11.2;
        $avgSca = $qualityControls->avg('sca_cupping_score') ? round($qualityControls->avg('sca_cupping_score'), 1) : 84.5;

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

            $year = $validated['harvest_year'];
            $code = strtoupper($validated['origin_code']);
            $nextNo = str_pad(GreenBeanLot::count() + 1, 3, '0', STR_PAD_LEFT);
            $lotNumber = "GB-{$code}-{$year}-{$nextNo}";

            $po = PurchaseOrder::findOrFail($validated['po_id']);

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

            $inspectorId = auth()->id() ?? (User::where('role_id', function ($q) {
                $q->select('id')->from('roles')->whereIn('name', ['qc_inspector', 'qc']);
            })->value('id') ?? User::first()?->id ?? 1);

            $qc = QualityControl::create([
                'lot_id' => $lot->id,
                'inspector_id' => $inspectorId,
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
