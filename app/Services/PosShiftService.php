<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PosOrder;
use App\Models\PosShift;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PosShiftService
{
    public const DISCREPANCY_TOLERANCE = 10000.00; // Rp 10.000

    public function __construct(
        protected CashierPinAuthService $pinAuthService
    ) {}

    /**
     * Open a new cashier shift with initial opening cash.
     */
    public function openShift(User $cashier, Branch $branch, float $openingCash): PosShift
    {
        if ($openingCash < 0) {
            throw new \InvalidArgumentException('Modal awal kasir (opening cash) tidak boleh bernilai negatif.');
        }

        $activeShift = PosShift::where('cashier_user_id', $cashier->id)
            ->where('status', 'OPEN')
            ->first();

        if ($activeShift) {
            throw new \DomainException("Kasir [{$cashier->name}] masih memiliki sesi shift aktif ({$activeShift->shift_code}). Selesaikan shift sebelumnya terlebih dahulu.");
        }

        $datePrefix = date('Ymd');
        $latestShift = PosShift::where('shift_code', 'LIKE', "SHIFT-{$datePrefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        $seq = 1;
        if ($latestShift && preg_match('/-(\d{4})$/', $latestShift->shift_code, $matches)) {
            $seq = (int) $matches[1] + 1;
        }
        $shiftCode = sprintf('SHIFT-%s-%04d', $datePrefix, $seq);

        return PosShift::create([
            'shift_code' => $shiftCode,
            'cashier_user_id' => $cashier->id,
            'branch_id' => $branch->id,
            'opening_cash' => $openingCash,
            'opened_at' => now(),
            'status' => 'OPEN',
        ]);
    }

    /**
     * Close cashier shift with physical cash count and supervisor verification on discrepancy.
     */
    public function closeShift(
        PosShift $shift,
        float $closingCashActual,
        ?string $supervisorPin = null,
        ?User $supervisor = null
    ): PosShift {
        return DB::transaction(function () use ($shift, $closingCashActual, $supervisorPin, $supervisor) {
            if ($shift->status !== 'OPEN') {
                throw new \DomainException("Shift [{$shift->shift_code}] sudah ditutup sebelumnya.");
            }

            // Calculate expected cash from opening cash + all paid cash orders
            $totalCashSales = (float) PosOrder::where('shift_id', $shift->id)
                ->where('payment_method', 'CASH')
                ->where('payment_status', 'PAID')
                ->sum('total_amount');

            $expectedCash = (float) $shift->opening_cash + $totalCashSales;
            $cashDifference = $closingCashActual - $expectedCash;
            $absDiff = abs($cashDifference);

            // If discrepancy exceeds Rp 10.000, enforce supervisor PIN
            if ($absDiff > self::DISCREPANCY_TOLERANCE) {
                if (empty($supervisorPin) || ! $supervisor) {
                    $formattedDiff = number_format($cashDifference, 2, ',', '.');
                    throw new \DomainException("Selisih kas fisik vs sistem (Rp {$formattedDiff}) melebihi ambang batas toleransi Rp 10.000. Otorisasi 6-digit PIN Supervisor wajib dimasukkan!");
                }

                // Verify supervisor role
                $supervisorRole = $supervisor->role?->name;
                $allowedSupervisorRoles = ['super_admin', 'supervisor', 'store_manager', 'head_roaster'];
                if ($supervisorRole && ! in_array($supervisorRole, $allowedSupervisorRoles, true)) {
                    throw new \DomainException("Pengguna otorisator [{$supervisor->name}] tidak memiliki peran Supervisor/Manager.");
                }

                // Verify supervisor PIN
                $pinValid = $this->pinAuthService->verifyPin($supervisor, $supervisorPin);
                if (! $pinValid) {
                    throw new \DomainException('PIN Supervisor tidak valid atau otorisasi gagal.');
                }
            }

            $shift->update([
                'closing_cash_actual' => $closingCashActual,
                'closing_cash_system' => $expectedCash,
                'cash_difference' => $cashDifference,
                'status' => 'CLOSED',
                'closed_at' => now(),
            ]);

            return $shift->fresh();
        });
    }
}
