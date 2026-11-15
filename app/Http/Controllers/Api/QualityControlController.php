<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GreenBeanLot;
use App\Models\QualityControl;
use App\Services\QualityControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualityControlController extends Controller
{
    public function __construct(
        protected QualityControlService $qcService
    ) {}

    /**
     * List all QC inspection records.
     */
    public function index(): JsonResponse
    {
        $records = QualityControl::with(['lot.purchaseOrder.supplier', 'inspector'])->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $records,
        ]);
    }

    /**
     * Perform quality control evaluation on a Green Bean Lot.
     */
    public function inspect(Request $request, int $lotId): JsonResponse
    {
        $request->validate([
            'moisture_content' => 'required|numeric|min:0|max:100',
            'bulk_density_g_l' => 'nullable|numeric|min:0',
            'primary_defects' => 'required|integer|min:0',
            'secondary_defects' => 'nullable|integer|min:0',
            'sca_cupping_score' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:inventory_warehouses,id',
            'product_id' => 'nullable|exists:products,id',
        ]);

        $lot = GreenBeanLot::with('purchaseOrder.supplier')->findOrFail($lotId);
        $inspector = $request->user();

        $qc = $this->qcService->inspectLot(
            $lot,
            $inspector,
            $request->all(),
            $request->warehouse_id,
            $request->product_id
        );

        return response()->json([
            'status' => 'success',
            'message' => "Inspeksi QC selesai dengan status [{$qc->qc_status}].",
            'quality_control' => $qc->load(['lot', 'inspector']),
            'lot' => $lot->fresh(),
        ]);
    }
}
