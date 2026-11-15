<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PosShift;
use App\Models\User;
use App\Services\PosBillingService;
use App\Services\PosShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        protected PosShiftService $shiftService,
        protected PosBillingService $billingService
    ) {}

    /**
     * Get active cashier shift.
     */
    public function activeShift(Request $request): JsonResponse
    {
        $shift = PosShift::with(['branch', 'cashier'])
            ->where('cashier_user_id', $request->user()->id)
            ->where('status', 'OPEN')
            ->first();

        return response()->json([
            'status' => 'success',
            'has_active_shift' => (bool) $shift,
            'shift' => $shift,
        ]);
    }

    /**
     * Open POS cashier shift with opening cash fund.
     */
    public function openShift(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $cashier = $request->user();
        $branch = Branch::findOrFail($request->branch_id);

        $shift = $this->shiftService->openShift($cashier, $branch, (float) $request->opening_cash);

        return response()->json([
            'status' => 'success',
            'message' => "Sesi kasir shift [{$shift->shift_code}] berhasil dibuka.",
            'shift' => $shift->load('branch'),
        ], 201);
    }

    /**
     * Close cashier shift with cash reconciliation (> Rp 10.000 requires supervisor PIN).
     */
    public function closeShift(Request $request, int $shiftId): JsonResponse
    {
        $request->validate([
            'closing_cash_actual' => 'required|numeric|min:0',
            'supervisor_pin' => 'nullable|string|size:6',
            'supervisor_id' => 'nullable|exists:users,id',
        ]);

        $shift = PosShift::findOrFail($shiftId);

        $supervisor = null;
        if ($request->filled('supervisor_id')) {
            $supervisor = User::with('role')->find($request->supervisor_id);
        }

        try {
            $closedShift = $this->shiftService->closeShift(
                $shift,
                (float) $request->closing_cash_actual,
                $request->supervisor_pin,
                $supervisor
            );

            return response()->json([
                'status' => 'success',
                'message' => "Shift [{$closedShift->shift_code}] berhasil ditutup.",
                'shift' => $closedShift,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'requires_supervisor_pin' => true,
            ], 422);
        }
    }

    /**
     * Checkout POS order with pessimistic locking recipe auto-deduction.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'shift_id' => 'required|exists:pos_shifts,id',
            'order_type' => 'nullable|in:DINE_IN,TAKE_AWAY,NON_REVENUE',
            'non_revenue_reason' => 'nullable|required_if:order_type,NON_REVENUE|in:CALIBRATION,TESTER,STAFF_QUOTA',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:CASH,QRIS_MIDTRANS,BANK_TRANSFER,NON_REVENUE',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        $shift = PosShift::findOrFail($request->shift_id);
        $cashier = $request->user();

        try {
            $order = $this->billingService->checkout($shift, $cashier, $request->all());

            return response()->json([
                'status' => 'success',
                'message' => "Transaksi [{$order->order_number}] berhasil diproses dengan auto-deduct resep.",
                'order' => $order,
            ], 201);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'status' => 'insufficient_stock',
                'message' => $e->getMessage(),
            ], 409); // 409 Conflict / Out of Stock
        } catch (\DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Calculate split bill breakdown.
     */
    public function splitBill(Request $request): JsonResponse
    {
        $request->validate([
            'total_amount' => 'required|numeric|min:1',
            'splits' => 'required|integer|min:1|max:50',
        ]);

        $splits = $this->billingService->calculateSplitBill(
            (float) $request->total_amount,
            (int) $request->splits
        );

        return response()->json([
            'status' => 'success',
            'total_amount' => (float) $request->total_amount,
            'split_count' => (int) $request->splits,
            'splits' => $splits,
        ]);
    }
}
