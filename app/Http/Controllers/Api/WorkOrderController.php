<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function __construct(
        protected WorkOrderService $workOrderService
    ) {}

    /**
     * List roasting Work Orders.
     */
    public function index(): JsonResponse
    {
        $orders = WorkOrder::with(['lot', 'roastingProfile', 'roaster'])->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    /**
     * Create and schedule a Work Order.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'lot_id' => 'required|exists:green_bean_lots,id',
            'profile_id' => 'required|exists:roasting_profiles,id',
            'green_weight_grams' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $wo = $this->workOrderService->createWorkOrder($request->all(), $request->user());

        return response()->json([
            'status' => 'success',
            'message' => "Work Order [{$wo->wo_number}] berhasil dijadwalkan.",
            'work_order' => $wo->load(['lot', 'roastingProfile']),
        ], 201);
    }

    /**
     * Start roasting execution.
     */
    public function start(int $id): JsonResponse
    {
        $wo = WorkOrder::findOrFail($id);
        $startedWo = $this->workOrderService->startRoasting($wo);

        return response()->json([
            'status' => 'success',
            'message' => "Proses sangrai untuk WO [{$startedWo->wo_number}] telah dimulai.",
            'work_order' => $startedWo,
        ]);
    }

    /**
     * Complete roasting execution: calculate Yield Loss (14-18% validation) and Real HPP.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'roasted_weight_grams' => 'required|numeric|min:1',
            'energy_cost' => 'nullable|numeric|min:0',
            'target_product_id' => 'nullable|exists:products,id',
            'roastery_warehouse_id' => 'nullable|exists:inventory_warehouses,id',
        ]);

        $wo = WorkOrder::with('lot')->findOrFail($id);
        $result = $this->workOrderService->completeWorkOrder(
            $wo,
            (float) $request->roasted_weight_grams,
            (float) ($request->energy_cost ?? 0.0),
            $request->target_product_id,
            $request->roastery_warehouse_id
        );

        return response()->json([
            'status' => 'success',
            'message' => "Work Order [{$wo->wo_number}] berhasil diselesaikan.",
            'data' => $result,
        ]);
    }
}
