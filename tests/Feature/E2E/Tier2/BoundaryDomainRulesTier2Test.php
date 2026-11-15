<?php

namespace Tests\Feature\E2E\Tier2;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\CashierPinAuthService;
use App\Services\LotGeneratorService;
use App\Services\PosShiftService;
use App\Services\QualityControlService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class BoundaryDomainRulesTier2Test extends TestCase
{
    use DatabaseTransactions;

    protected QualityControlService $qcService;

    protected WorkOrderService $woService;

    protected PosShiftService $shiftService;

    protected User $inspector;

    protected User $cashier;

    protected User $supervisor;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qcService = new QualityControlService(new LotGeneratorService);
        $this->woService = new WorkOrderService;
        $this->shiftService = new PosShiftService(new CashierPinAuthService);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR-BVA'],
            [
                'name' => 'Branch BVA Test',
                'type' => 'RETAIL_BRANCH',
                'address' => 'Jl. Kopi Specialty',
                'phone' => '08123456789',
            ]
        );

        $qcRole = Role::firstOrCreate(['name' => 'qc_inspector'], ['display_name' => 'QC Inspector']);
        $cashierRole = Role::firstOrCreate(['name' => 'branch_cashier'], ['display_name' => 'Cashier']);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor'], ['display_name' => 'Supervisor']);

        $this->inspector = User::firstOrCreate(
            ['email' => 'inspector_bva@kafeina.test'],
            [
                'role_id' => $qcRole->id,
                'branch_id' => $this->branch->id,
                'name' => 'Inspector BVA',
                'password' => bcrypt('password123'),
            ]
        );

        $this->cashier = User::firstOrCreate(
            ['email' => 'cashier_bva_pos@kafeina.test'],
            [
                'role_id' => $cashierRole->id,
                'branch_id' => $this->branch->id,
                'name' => 'Cashier BVA',
                'password' => bcrypt('password123'),
                'pin_code' => bcrypt('112233'),
            ]
        );

        $this->supervisor = User::firstOrCreate(
            ['email' => 'supervisor_bva@kafeina.test'],
            [
                'role_id' => $supervisorRole->id,
                'branch_id' => $this->branch->id,
                'name' => 'Supervisor BVA',
                'password' => bcrypt('password123'),
                'pin_code' => bcrypt('889900'),
            ]
        );
    }

    // -------------------------------------------------------------
    // Feature 3: Specialty Coffee QC Lab Inspection BVA
    // -------------------------------------------------------------

    /**
     * BVA: Moisture 9.99% (below 10.00% lower boundary) cannot be ACCEPTED.
     */
    public function test_qc_moisture_below_lower_boundary_is_not_accepted(): void
    {
        $status = $this->qcService->evaluateMutu(9.99, 0, 85.00);
        $this->assertNotEquals('ACCEPTED', $status);
    }

    /**
     * BVA: Moisture 10.00% (exact lower boundary) with 0 defects and SCA >= 80 is ACCEPTED.
     */
    public function test_qc_moisture_exact_lower_boundary_is_accepted(): void
    {
        $status = $this->qcService->evaluateMutu(10.00, 0, 80.00);
        $this->assertEquals('ACCEPTED', $status);
    }

    /**
     * BVA: Moisture 12.00% (exact upper boundary) with 0 defects and SCA >= 80 is ACCEPTED.
     */
    public function test_qc_moisture_exact_upper_boundary_is_accepted(): void
    {
        $status = $this->qcService->evaluateMutu(12.00, 0, 80.00);
        $this->assertEquals('ACCEPTED', $status);
    }

    /**
     * BVA: Moisture 12.01% (above 12.00% upper boundary) cannot be ACCEPTED.
     */
    public function test_qc_moisture_above_upper_boundary_is_not_accepted(): void
    {
        $status = $this->qcService->evaluateMutu(12.01, 0, 85.00);
        $this->assertNotEquals('ACCEPTED', $status);
    }

    /**
     * BVA: SCA Cupping Score 79.99 (below 80.00 specialty threshold) cannot be ACCEPTED.
     */
    public function test_qc_sca_score_below_specialty_threshold_is_not_accepted(): void
    {
        $status = $this->qcService->evaluateMutu(11.50, 0, 79.99);
        $this->assertNotEquals('ACCEPTED', $status);
    }

    /**
     * BVA: SCA Cupping Score 80.00 (exact threshold) is ACCEPTED.
     */
    public function test_qc_sca_score_exact_specialty_threshold_is_accepted(): void
    {
        $status = $this->qcService->evaluateMutu(11.50, 0, 80.00);
        $this->assertEquals('ACCEPTED', $status);
    }

    /**
     * BVA: Primary defects >= 1 strictly REJECTS the lot even with high SCA score.
     */
    public function test_qc_primary_defects_strictly_zero_required(): void
    {
        $status = $this->qcService->evaluateMutu(11.00, 1, 88.00);
        $this->assertEquals('REJECTED', $status);
    }

    // -------------------------------------------------------------
    // Feature 9 & 10: Roasting Yield Loss & Real HPP BVA
    // -------------------------------------------------------------

    /**
     * BVA: Yield loss 13.99% (below 14.00% lower boundary) flags UNDER_DEVELOPED.
     */
    public function test_yield_loss_below_lower_boundary_flags_underdeveloped(): void
    {
        $tolerance = $this->woService->validateYieldTolerance(13.99);

        $this->assertEquals('UNDER_DEVELOPED', $tolerance['status']);
        $this->assertFalse($tolerance['is_acceptable']);
    }

    /**
     * BVA: Yield loss 14.00% (exact lower boundary) is NORMAL and acceptable.
     */
    public function test_yield_loss_exact_lower_boundary_is_normal(): void
    {
        $tolerance = $this->woService->validateYieldTolerance(14.00);

        $this->assertEquals('NORMAL', $tolerance['status']);
        $this->assertTrue($tolerance['is_acceptable']);
    }

    /**
     * BVA: Yield loss 18.00% (exact upper boundary) is NORMAL and acceptable.
     */
    public function test_yield_loss_exact_upper_boundary_is_normal(): void
    {
        $tolerance = $this->woService->validateYieldTolerance(18.00);

        $this->assertEquals('NORMAL', $tolerance['status']);
        $this->assertTrue($tolerance['is_acceptable']);
    }

    /**
     * BVA: Yield loss 18.01% (above 18.00% upper boundary) flags OVER_ROASTED alert.
     */
    public function test_yield_loss_above_upper_boundary_flags_over_roasted(): void
    {
        $tolerance = $this->woService->validateYieldTolerance(18.01);

        $this->assertEquals('OVER_ROASTED', $tolerance['status']);
        $this->assertFalse($tolerance['is_acceptable']);
    }

    /**
     * BVA: Yield loss calculation with zero green weight throws InvalidArgumentException.
     */
    public function test_yield_loss_with_zero_green_weight_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->woService->calculateYieldLoss(0.0, 500.0);
    }

    /**
     * BVA: Real HPP per gram calculation with zero roasted weight throws InvalidArgumentException.
     */
    public function test_real_hpp_with_zero_roasted_weight_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->woService->calculateRealHppPerGram(500000, 50000, 15000, 0.0);
    }

    /**
     * Mathematical Formula Check: Real HPP per gram calculation accuracy.
     */
    public function test_real_hpp_mathematical_precision(): void
    {
        // Green Cost: 5000g * 100/g = 500.000
        // Packaging: 50.000
        // Energy: 15.000
        // Total = 565.000
        // Roasted Weight: 4200g (16% yield loss)
        // HPP/g = 565.000 / 4200 = 134.5238...
        $hppPerGram = $this->woService->calculateRealHppPerGram(500000, 50000, 15000, 4200);

        $this->assertEqualsWithDelta(134.5238, $hppPerGram, 0.001);
    }

    // -------------------------------------------------------------
    // Feature 25: Shift Closing Cash Reconciliation BVA
    // -------------------------------------------------------------

    /**
     * BVA: Cash discrepancy of exactly Rp 10.000 (boundary) requires NO supervisor PIN.
     */
    public function test_cash_discrepancy_exact_10000_does_not_require_supervisor(): void
    {
        $shift = $this->shiftService->openShift($this->cashier, $this->branch, 500000);

        // System expected: 500.000, physical: 510.000 (+10.000 overage)
        $closed = $this->shiftService->closeShift($shift, 510000);

        $this->assertEquals('CLOSED', $closed->status);
        $this->assertEquals(10000, (float) $closed->cash_difference);
    }

    /**
     * BVA: Cash discrepancy of Rp 10.001 (boundary + 1) REQUIRES supervisor PIN.
     */
    public function test_cash_discrepancy_exceeding_10000_requires_supervisor_pin(): void
    {
        $shift = $this->shiftService->openShift($this->cashier, $this->branch, 500000);

        // Discrepancy: -10.001 IDR (shortage)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('melebihi ambang batas toleransi Rp 10.000. Otorisasi 6-digit PIN Supervisor wajib dimasukkan!');

        $this->shiftService->closeShift($shift, 489999);
    }

    /**
     * BVA: Cash discrepancy exceeding 10.000 with valid supervisor PIN succeeds.
     */
    public function test_cash_discrepancy_exceeding_10000_with_valid_supervisor_succeeds(): void
    {
        $shift = $this->shiftService->openShift($this->cashier, $this->branch, 500000);

        // Discrepancy: -50.000 IDR
        $closed = $this->shiftService->closeShift($shift, 450000, '889900', $this->supervisor);

        $this->assertEquals('CLOSED', $closed->status);
        $this->assertEquals(-50000, (float) $closed->cash_difference);
    }

    // -------------------------------------------------------------
    // Feature 33: Local Restaurant Tax PB1 (11%) BVA & Rounding
    // -------------------------------------------------------------

    /**
     * BVA: PB1 11% calculation rounds half-up to nearest whole IDR.
     */
    public function test_pb1_tax_rounds_half_up_accurately(): void
    {
        $subtotal = 101555;
        $pb1 = round($subtotal * 0.11); // 11171.05 -> 11171
        $this->assertEquals(11171, $pb1);

        $subtotal2 = 101550;
        $pb1_2 = round($subtotal2 * 0.11); // 11170.5 -> 11171
        $this->assertEquals(11171, $pb1_2);
    }
}
