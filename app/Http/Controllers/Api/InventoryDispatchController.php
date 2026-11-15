<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\InventoryWarehouse;
use App\Models\MaterialRequest;
use App\Services\InventoryDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryDispatchController extends Controller
{
    public function __construct(
        protected InventoryDispatchService $dispatchService
    ) {}

    /**
     * Branch submits a Material Request.
     */
    public function submitMaterialRequest(Request $request): JsonResponse
    {
        $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'notes' => 'nullable|string',
        ]);

        $fromBranch = Branch::findOrFail($request->from_branch_id);
        $toBranch = Branch::findOrFail($request->to_branch_id);

        $mr = $this->dispatchService->submitMaterialRequest(
            $fromBranch,
            $toBranch,
            $request->user(),
            $request->items,
            $request->notes
        );

        return response()->json([
            'status' => 'success',
            'message' => "Material Request [{$mr->request_number}] berhasil diajukan.",
            'material_request' => $mr,
        ], 201);
    }

    /**
     * Approve Material Request.
     */
    public function approveMaterialRequest(int $id, Request $request): JsonResponse
    {
        $mr = MaterialRequest::findOrFail($id);
        $approved = $this->dispatchService->approveMaterialRequest($mr, $request->user());

        return response()->json([
            'status' => 'success',
            'message' => "Material Request [{$approved->request_number}] telah disetujui.",
            'material_request' => $approved,
        ]);
    }

    /**
     * Dispatch Material Request: FIFO allocation & isolation to IN_TRANSIT warehouse.
     */
    public function dispatch(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'source_warehouse_id' => 'required|exists:inventory_warehouses,id',
            'transit_warehouse_id' => 'required|exists:inventory_warehouses,id',
            'courier_name' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $mr = MaterialRequest::with('items.product')->findOrFail($id);
        $sourceWarehouse = InventoryWarehouse::findOrFail($request->source_warehouse_id);
        $transitWarehouse = InventoryWarehouse::findOrFail($request->transit_warehouse_id);

        $deliveryNote = $this->dispatchService->dispatchMaterialRequest(
            $mr,
            $sourceWarehouse,
            $transitWarehouse,
            $request->courier_name,
            $request->user(),
            $request->notes
        );

        return response()->json([
            'status' => 'success',
            'message' => "Surat Jalan [{$deliveryNote->delivery_number}] diterbitkan. Stok diisolasi ke status IN_TRANSIT.",
            'delivery_note' => $deliveryNote,
        ]);
    }

    /**
     * Branch confirms receipt and reconciles any discrepancies.
     */
    public function verifyReceipt(Request $request, int $deliveryNoteId): JsonResponse
    {
        $request->validate([
            'branch_warehouse_id' => 'required|exists:inventory_warehouses,id',
            'received_quantities' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        $deliveryNote = DeliveryNote::with(['items.product', 'materialRequest'])->findOrFail($deliveryNoteId);
        $branchWarehouse = InventoryWarehouse::findOrFail($request->branch_warehouse_id);

        $reconciledDn = $this->dispatchService->verifyBranchReceipt(
            $deliveryNote,
            $branchWarehouse,
            $request->received_quantities,
            $request->user(),
            $request->notes
        );

        return response()->json([
            'status' => 'success',
            'message' => "Penerimaan Surat Jalan [{$reconciledDn->delivery_number}] berhasil diverifikasi dengan status [{$reconciledDn->status}].",
            'delivery_note' => $reconciledDn,
        ]);
    }
}
