<?php

namespace App\Http\Controllers;

use App\Models\RedeemCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    /**
     * Display the Voucher & Promo Quota Management page.
     */
    public function index(Request $request): View
    {
        $query = RedeemCode::query();

        // Search filter (code or description)
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Discount type filter (PERCENTAGE or FIXED)
        if ($type = $request->input('type')) {
            if (in_array(strtoupper($type), ['PERCENTAGE', 'FIXED'])) {
                $query->where('discount_type', strtoupper($type));
            }
        }

        // Status filter (active, inactive, depleted)
        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('max_redemptions')
                            ->orWhereColumn('times_redeemed', '<', 'max_redemptions');
                    });
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'depleted') {
                $query->whereNotNull('max_redemptions')
                    ->whereColumn('times_redeemed', '>=', 'max_redemptions');
            }
        }

        $vouchers = $query->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        // Summary KPI statistics
        $allVouchers = RedeemCode::all();
        $totalCount = $allVouchers->count();
        $activeCount = $allVouchers->where('is_active', true)->count();
        $totalRedeemed = $allVouchers->sum('times_redeemed');
        $depletedCount = $allVouchers->filter(function ($v) {
            return $v->max_redemptions !== null && $v->times_redeemed >= $v->max_redemptions;
        })->count();
        $totalQuotaIssued = $allVouchers->whereNotNull('max_redemptions')->sum('max_redemptions');

        return view('vouchers.index', compact(
            'vouchers',
            'totalCount',
            'activeCount',
            'totalRedeemed',
            'depletedCount',
            'totalQuotaIssued'
        ));
    }

    /**
     * Store a newly created voucher in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:redeem_codes,code'],
            'description' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', 'in:PERCENTAGE,FIXED'],
            'discount_value' => ['required', 'numeric', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'is_unlimited' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $isUnlimited = $request->boolean('is_unlimited');

        RedeemCode::create([
            'code' => $code,
            'description' => trim($validated['description']),
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'max_discount_amount' => $validated['discount_type'] === 'PERCENTAGE' ? ($validated['max_discount_amount'] ?? null) : null,
            'max_redemptions' => $isUnlimited ? null : ($validated['max_redemptions'] ?? 50),
            'times_redeemed' => 0,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()->route('vouchers.index')
            ->with('success', "Voucher [{$code}] berhasil diterbitkan dan siap digunakan di kasir!");
    }

    /**
     * Update the specified voucher in storage.
     */
    public function update(Request $request, RedeemCode $voucher): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:redeem_codes,code,'.$voucher->id],
            'description' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', 'in:PERCENTAGE,FIXED'],
            'discount_value' => ['required', 'numeric', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'max_redemptions' => ['nullable', 'integer', 'min:0'],
            'is_unlimited' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isUnlimited = $request->boolean('is_unlimited');

        $voucher->update([
            'code' => strtoupper(trim($validated['code'])),
            'description' => trim($validated['description']),
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'max_discount_amount' => $validated['discount_type'] === 'PERCENTAGE' ? ($validated['max_discount_amount'] ?? null) : null,
            'max_redemptions' => $isUnlimited ? null : $validated['max_redemptions'],
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('vouchers.index')
            ->with('success', "Data voucher [{$voucher->code}] berhasil diperbarui.");
    }

    /**
     * Quick update or increase voucher quota.
     */
    public function updateQuota(Request $request, RedeemCode $voucher): RedirectResponse
    {
        $request->validate([
            'action_type' => ['required', 'in:add,set,unlimited'],
            'quota_value' => ['nullable', 'integer', 'min:1'],
        ]);

        $action = $request->input('action_type');
        $value = (int) $request->input('quota_value', 0);

        if ($action === 'unlimited') {
            $voucher->update(['max_redemptions' => null]);
            $msg = "Kuota voucher [{$voucher->code}] berhasil diubah menjadi Tanpa Batas (Unlimited).";
        } elseif ($action === 'add') {
            $current = $voucher->max_redemptions ?? 0;
            $newMax = $current + $value;
            $voucher->update(['max_redemptions' => $newMax]);
            $msg = "Kuota voucher [{$voucher->code}] berhasil ditambah +{$value} kuota (Total baru: {$newMax}x).";
        } else {
            $voucher->update(['max_redemptions' => $value]);
            $msg = "Batas kuota voucher [{$voucher->code}] ditetapkan menjadi {$value}x penukaran.";
        }

        return redirect()->route('vouchers.index')->with('success', $msg);
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggle(Request $request, RedeemCode $voucher): JsonResponse|RedirectResponse
    {
        $voucher->update([
            'is_active' => ! $voucher->is_active,
        ]);

        $statusStr = $voucher->is_active ? 'diaktifkan' : 'dinonaktifkan';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $voucher->is_active,
                'message' => "Voucher [{$voucher->code}] berhasil {$statusStr}.",
            ]);
        }

        return redirect()->route('vouchers.index')
            ->with('success', "Voucher [{$voucher->code}] berhasil {$statusStr}.");
    }

    /**
     * Remove the specified voucher from storage.
     */
    public function destroy(RedeemCode $voucher): RedirectResponse
    {
        $code = $voucher->code;
        $voucher->delete();

        return redirect()->route('vouchers.index')
            ->with('success', "Voucher [{$code}] telah dihapus dari sistem.");
    }
}
