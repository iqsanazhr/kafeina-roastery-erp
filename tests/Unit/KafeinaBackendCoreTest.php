<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientStockException;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\InventoryWarehouse;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRecipe;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\StockCard;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Services\CashierPinAuthService;
use App\Services\FifoStockAllocatorService;
use App\Services\InventoryDispatchService;
use App\Services\LotGeneratorService;
use App\Services\PosBillingService;
use App\Services\PosShiftService;
use App\Services\QualityControlService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KafeinaBackendCoreTest extends TestCase
{
    use DatabaseTransactions;

    protected Role $adminRole;

    protected Role $cashierRole;

    protected Role $supervisorRole;

    protected Branch $hqBranch;

    protected Branch $retailBranch;

    protected User $adminUser;

    protected User $cashierUser;

    protected User $supervisorUser;

    protected InventoryWarehouse $rawWarehouse;

    protected InventoryWarehouse $roastWarehouse;

    protected InventoryWarehouse $transitWarehouse;

    protected InventoryWarehouse $barWarehouse;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // Seed basic RBAC & branches
        $this->adminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Administrator']);
        $this->cashierRole = Role::firstOrCreate(['name' => 'branch_cashier'], ['display_name' => 'Kasir Barista']);
        $this->supervisorRole = Role::firstOrCreate(['name' => 'supervisor'], ['display_name' => 'Supervisor Cabang']);

        $this->hqBranch = Branch::firstOrCreate(['code' => 'HQ-01'], [
            'name' => 'Kafeina Headquarters & Roastery',
            'type' => 'CENTRAL_ROASTERY',
            'address' => 'Jl. Kopi No. 1, Jakarta',
            'phone' => '021-5551234',
        ]);

        $this->retailBranch = Branch::firstOrCreate(['code' => 'BR-01'], [
            'name' => 'Kafeina Express Bar Senopati',
            'type' => 'RETAIL_BRANCH',
            'address' => 'Jl. Senopati No. 88, Jakarta Selatan',
            'phone' => '021-5555678',
        ]);

        $this->adminUser = User::firstOrCreate(['email' => 'admin_test@kafeina.id'], [
            'role_id' => $this->adminRole->id,
            'branch_id' => $this->hqBranch->id,
            'name' => 'Admin Test',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $this->cashierUser = User::firstOrCreate(['email' => 'cashier_test@kafeina.id'], [
            'role_id' => $this->cashierRole->id,
            'branch_id' => $this->retailBranch->id,
            'name' => 'Budi Kasir',
            'password' => Hash::make('password123'),
            'pin_code' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $this->supervisorUser = User::firstOrCreate(['email' => 'spv_test@kafeina.id'], [
            'role_id' => $this->supervisorRole->id,
            'branch_id' => $this->retailBranch->id,
            'name' => 'Siti Supervisor',
            'password' => Hash::make('password123'),
            'pin_code' => Hash::make('998877'),
            'is_active' => true,
        ]);

        // Warehouses
        $this->rawWarehouse = InventoryWarehouse::firstOrCreate(['code' => 'WH-RAW-TEST'], [
            'branch_id' => $this->hqBranch->id,
            'name' => 'Gudang Bahan Baku Mentah',
            'type' => 'CENTRAL_RAW',
            'is_active' => true,
        ]);

        $this->roastWarehouse = InventoryWarehouse::firstOrCreate(['code' => 'WH-ROAST-TEST'], [
            'branch_id' => $this->hqBranch->id,
            'name' => 'Gudang Biji Matang Roastery',
            'type' => 'CENTRAL_ROASTED',
            'is_active' => true,
        ]);

        $this->transitWarehouse = InventoryWarehouse::firstOrCreate(['code' => 'WH-TR-TEST'], [
            'branch_id' => $this->hqBranch->id,
            'name' => 'Gudang Virtual In-Transit',
            'type' => 'TRANSIT',
            'is_active' => true,
        ]);

        $this->barWarehouse = InventoryWarehouse::firstOrCreate(['code' => 'WH-BAR-TEST'], [
            'branch_id' => $this->retailBranch->id,
            'name' => 'Bar Operasional Senopati',
            'type' => 'RETAIL_BAR',
            'is_active' => true,
        ]);
    }

    /**
     * TICK-004: Test 6-digit PIN verification, 5-strike lockout, and audit breach logging.
     */
    public function test_cashier_pin_verification_and_lockout(): void
    {
        $service = new CashierPinAuthService;

        // 1. Valid PIN succeeds
        $this->assertTrue($service->verifyPin($this->cashierUser, '123456'));
        $this->assertFalse($service->isLockedOut($this->cashierUser));

        // 2. 4 failed attempts do not lockout
        for ($i = 1; $i <= 4; $i++) {
            $this->assertFalse($service->verifyPin($this->cashierUser, '000000'));
        }
        $this->assertFalse($service->isLockedOut($this->cashierUser));
        $this->assertEquals(4, $service->getFailedAttempts($this->cashierUser));

        // 3. 5th strike triggers lockout
        $this->assertFalse($service->verifyPin($this->cashierUser, '000000'));
        $this->assertTrue($service->isLockedOut($this->cashierUser));
        $this->assertGreaterThan(0, $service->getRemainingLockoutSeconds($this->cashierUser));

        // 4. Assert locked out user throws DomainException on next attempt
        $this->expectException(\DomainException::class);
        $service->verifyPin($this->cashierUser, '123456');
    }

    /**
     * TICK-006: Global Audit Trail Observer records INSERT, UPDATE, DELETE with old and new values.
     */
    public function test_audit_log_observer_records_lifecycle_events(): void
    {
        // Set authenticated user for audit
        $this->actingAs($this->adminUser);

        $supplier = Supplier::firstOrCreate(['code' => 'SUP-TEST'], [
            'name' => 'Koperasi Tani Gayo',
            'contact_person' => 'Aman Gayo',
            'phone' => '08123456789',
            'address' => 'Takengon, Aceh Tengah',
            'farm_location' => 'Bener Meriah',
            'altitude_masl' => 1500,
        ]);

        // 1. INSERT event
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->hqBranch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'status' => 'DRAFT',
            'notes' => 'Test PO Initial',
        ]);

        $insertLog = AuditLog::where('auditable_type', PurchaseOrder::class)
            ->where('auditable_id', $po->id)
            ->where('event_type', 'INSERT')
            ->first();

        $this->assertNotNull($insertLog);
        $this->assertEquals($this->adminUser->id, $insertLog->user_id);

        // 2. UPDATE event
        $po->update(['status' => 'ISSUED', 'notes' => 'PO Issued Note']);

        $updateLog = AuditLog::where('auditable_type', PurchaseOrder::class)
            ->where('auditable_id', $po->id)
            ->where('event_type', 'UPDATE')
            ->first();

        $this->assertNotNull($updateLog);
        $newVals = is_array($updateLog->new_values) ? $updateLog->new_values : json_decode($updateLog->new_values, true);
        $this->assertEquals('ISSUED', $newVals['status']);

        // 3. DELETE event
        $poId = $po->id;
        $po->delete();

        $deleteLog = AuditLog::where('auditable_type', PurchaseOrder::class)
            ->where('auditable_id', $poId)
            ->where('event_type', 'DELETE')
            ->first();

        $this->assertNotNull($deleteLog);
    }

    /**
     * TICK-012 & TICK-013: QualityControlService & LotGeneratorService evaluation standards.
     */
    public function test_quality_control_mutu_evaluation_and_lot_generator(): void
    {
        $lotGen = new LotGeneratorService;
        $qcService = new QualityControlService($lotGen);

        // Evaluasi mutu rules
        // Standard pass: 10.00 - 12.00% moisture, 0 defects, >= 80 SCA
        $this->assertEquals('ACCEPTED', $qcService->evaluateMutu(11.20, 0, 84.50));

        // Moisture below 10.00% -> REJECTED
        $this->assertEquals('REJECTED', $qcService->evaluateMutu(9.40, 0, 84.50));

        // Moisture above 12.50% -> REJECTED
        $this->assertEquals('REJECTED', $qcService->evaluateMutu(12.80, 0, 84.50));

        // Primary defect > 0 -> REJECTED immediately
        $this->assertEquals('REJECTED', $qcService->evaluateMutu(11.00, 1, 85.00));

        // Commercial grade: 0 primary defect, SCA 75-79.99 -> DOWNGRADED
        $this->assertEquals('DOWNGRADED', $qcService->evaluateMutu(11.50, 0, 77.00));

        // Test lot number generator format: GB-[KODE]-[TAHUN]-[SEQ]
        $lotNumber = $lotGen->generateLotNumber('GYO', 2026);
        $this->assertMatchesRegularExpression('/^GB-GYO-2026-\d{4}$/', $lotNumber);
    }

    /**
     * TICK-022, TICK-023, TICK-024, TICK-025: WorkOrderService Yield Loss (14-18%) & Real HPP.
     */
    public function test_work_order_yield_loss_and_real_hpp_engine(): void
    {
        $woService = new WorkOrderService;

        // 1. Yield Loss formula: ((green - roasted) / green) * 100
        $green = 10000.0; // 10 kg
        $roasted = 8400.0; // 8.4 kg
        $yieldLoss = $woService->calculateYieldLoss($green, $roasted);
        $this->assertEquals(16.0, $yieldLoss); // 16% loss

        // 2. Tolerance validation
        $tolNormal = $woService->validateYieldTolerance(16.0);
        $this->assertTrue($tolNormal['is_acceptable']);
        $this->assertEquals('NORMAL', $tolNormal['status']);

        $tolUnder = $woService->validateYieldTolerance(12.5);
        $this->assertFalse($tolUnder['is_acceptable']);
        $this->assertEquals('UNDER_DEVELOPED', $tolUnder['status']);

        $tolOver = $woService->validateYieldTolerance(19.2);
        $this->assertFalse($tolOver['is_acceptable']);
        $this->assertEquals('OVER_ROASTED', $tolOver['status']);

        // 3. Real HPP per gram calculation
        // Cost Green: Rp 1.000.000, Packaging: Rp 50.000, Energy: Rp 30.000 -> Total Rp 1.080.000
        // Roasted weight: 8.400 g -> Rp 1.080.000 / 8.400 = 128.5714 / gram
        $hppPerGram = $woService->calculateRealHppPerGram(1000000, 50000, 30000, 8400);
        $this->assertEquals(round(1080000 / 8400, 4), round($hppPerGram, 4));
    }

    /**
     * TICK-031, TICK-033, TICK-034: FIFO Stock Allocation, Virtual Transit, and Discrepancy Reconciliation.
     */
    public function test_fifo_stock_allocation_and_in_transit_isolation(): void
    {
        $fifo = new FifoStockAllocatorService;
        $dispatchService = new InventoryDispatchService($fifo);

        $category = ProductCategory::firstOrCreate(['name' => 'Biji Sangrai'], ['description' => 'Roasted Beans']);
        $product = Product::firstOrCreate(['sku' => 'RB-GYO-TEST'], [
            'category_id' => $category->id,
            'name' => 'Aceh Gayo Roasted Beans 1kg',
            'unit' => 'gram',
            'cost_price' => 150.0,
            'selling_price' => 300.0,
            'is_raw_material' => false,
            'is_active' => true,
        ]);

        // Provide 20,000g stock in roastery warehouse
        $stock = WarehouseStock::firstOrCreate(
            ['warehouse_id' => $this->roastWarehouse->id, 'product_id' => $product->id],
            ['quantity' => 20000, 'reserved_quantity' => 0]
        );
        $stock->update(['quantity' => 20000]);

        // 1. Submit Material Request from retail branch
        $mr = $dispatchService->submitMaterialRequest(
            $this->retailBranch,
            $this->hqBranch,
            $this->cashierUser,
            [['product_id' => $product->id, 'quantity' => 5000]]
        );
        $this->assertEquals('PENDING', $mr->status);

        // 2. Central warehouse approves
        $mr = $dispatchService->approveMaterialRequest($mr, $this->adminUser);
        $this->assertEquals('APPROVED', $mr->status);

        // 3. Dispatch: isolates stock to TRANSIT warehouse
        $dn = $dispatchService->dispatchMaterialRequest(
            $mr,
            $this->roastWarehouse,
            $this->transitWarehouse,
            'JNE Express - Pak Slamet',
            $this->adminUser
        );

        $this->assertEquals('DISPATCHED', $dn->status);
        $this->assertEquals('IN_TRANSIT', $mr->fresh()->status);

        // Assert roastery warehouse decreased to 15,000 and transit warehouse has 5,000
        $this->assertEquals(15000, (float) WarehouseStock::where('warehouse_id', $this->roastWarehouse->id)->where('product_id', $product->id)->value('quantity'));
        $this->assertEquals(5000, (float) WarehouseStock::where('warehouse_id', $this->transitWarehouse->id)->where('product_id', $product->id)->value('quantity'));

        // 4. Branch receives with 200g transit discrepancy (received 4,800g instead of 5,000g)
        $reconciledDn = $dispatchService->verifyBranchReceipt(
            $dn,
            $this->barWarehouse,
            [$product->id => 4800],
            $this->cashierUser,
            'Kemasan robek di jalan selisih 200 gram'
        );

        $this->assertEquals('RECONCILED', $reconciledDn->status);
        $this->assertEquals(4800, (float) WarehouseStock::where('warehouse_id', $this->barWarehouse->id)->where('product_id', $product->id)->value('quantity'));
        $this->assertEquals(0, (float) WarehouseStock::where('warehouse_id', $this->transitWarehouse->id)->where('product_id', $product->id)->value('quantity'));
    }

    /**
     * TICK-040, TICK-046: POS Shift opening, closing cash reconciliation, and supervisor PIN authorization.
     */
    public function test_pos_shift_reconciliation_and_supervisor_pin_lockout(): void
    {
        $pinAuth = new CashierPinAuthService;
        $shiftService = new PosShiftService($pinAuth);

        // 1. Open shift with Rp 500.000 opening cash
        $shift = $shiftService->openShift($this->cashierUser, $this->retailBranch, 500000);
        $this->assertEquals('OPEN', $shift->status);
        $this->assertEquals(500000, (float) $shift->opening_cash);

        // Cannot open another shift while active
        $this->expectException(\DomainException::class);
        $shiftService->openShift($this->cashierUser, $this->retailBranch, 500000);
    }

    /**
     * Test shift closing within Rp 10.000 tolerance closes automatically.
     */
    public function test_pos_shift_close_within_tolerance(): void
    {
        $pinAuth = new CashierPinAuthService;
        $shiftService = new PosShiftService($pinAuth);

        $shift = PosShift::create([
            'shift_code' => 'SHIFT-TOL-'.uniqid(),
            'cashier_user_id' => $this->cashierUser->id,
            'branch_id' => $this->retailBranch->id,
            'opening_cash' => 500000,
            'opened_at' => now(),
            'status' => 'OPEN',
        ]);

        // Difference: Expected 500.000, Actual 505.000 (Selisih +Rp 5.000 <= Rp 10.000)
        $closed = $shiftService->closeShift($shift, 505000);
        $this->assertEquals('CLOSED', $closed->status);
        $this->assertEquals(5000, (float) $closed->cash_difference);
    }

    /**
     * Test shift closing exceeding Rp 10.000 tolerance requires supervisor PIN.
     */
    public function test_pos_shift_close_exceeding_tolerance_requires_supervisor_pin(): void
    {
        $pinAuth = new CashierPinAuthService;
        $shiftService = new PosShiftService($pinAuth);

        $shift = PosShift::create([
            'shift_code' => 'SHIFT-EXC-'.uniqid(),
            'cashier_user_id' => $this->cashierUser->id,
            'branch_id' => $this->retailBranch->id,
            'opening_cash' => 500000,
            'opened_at' => now(),
            'status' => 'OPEN',
        ]);

        // Difference: Expected 500.000, Actual 480.000 (-Rp 20.000 > Rp 10.000)
        // Closing without supervisor PIN must fail!
        try {
            $shiftService->closeShift($shift, 480000);
            $this->fail('Harus gagal jika selisih > Rp 10.000 tanpa PIN supervisor.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Supervisor', $e->getMessage());
        }

        // Closing with valid supervisor PIN succeeds!
        $closed = $shiftService->closeShift($shift, 480000, '998877', $this->supervisorUser);
        $this->assertEquals('CLOSED', $closed->status);
        $this->assertEquals(-20000, (float) $closed->cash_difference);
    }

    /**
     * TICK-042, TICK-043: Pessimistic Locking Auto-Deduct, Non-Revenue, and Negative Stock Prevention.
     */
    public function test_pos_auto_deduct_pessimistic_locking_and_negative_stock_prevention(): void
    {
        $billingService = new PosBillingService;

        $category = ProductCategory::firstOrCreate(['name' => 'Espresso Menu'], ['description' => 'Coffee']);

        // Products
        $espressoBean = Product::firstOrCreate(['sku' => 'ING-ESP-BEANS'], [
            'category_id' => $category->id,
            'name' => 'House Blend Espresso Beans',
            'unit' => 'gram',
            'cost_price' => 150.0,
            'selling_price' => 0.0,
            'is_raw_material' => true,
        ]);

        $freshMilk = Product::firstOrCreate(['sku' => 'ING-FRESH-MILK'], [
            'category_id' => $category->id,
            'name' => 'Fresh Milk Diamond',
            'unit' => 'ml',
            'cost_price' => 20.0,
            'selling_price' => 0.0,
            'is_raw_material' => true,
        ]);

        $cafeLatte = Product::firstOrCreate(['sku' => 'BEV-LATTE'], [
            'category_id' => $category->id,
            'name' => 'Hot Cafe Latte',
            'unit' => 'cup',
            'cost_price' => 5700.0,
            'selling_price' => 35000.0,
            'is_raw_material' => false,
        ]);

        // Recipe: 1 Cafe Latte = 18g espresso beans + 150ml milk
        ProductRecipe::firstOrCreate(['product_id' => $cafeLatte->id, 'ingredient_product_id' => $espressoBean->id], [
            'quantity_required' => 18.0,
            'unit' => 'gram',
        ]);
        ProductRecipe::firstOrCreate(['product_id' => $cafeLatte->id, 'ingredient_product_id' => $freshMilk->id], [
            'quantity_required' => 150.0,
            'unit' => 'ml',
        ]);

        // Provide exact stock for 2 cups of Latte in bar warehouse: 36g beans and 300ml milk
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $espressoBean->id],
            ['quantity' => 36.0, 'reserved_quantity' => 0]
        );
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $freshMilk->id],
            ['quantity' => 300.0, 'reserved_quantity' => 0]
        );

        $shift = PosShift::create([
            'shift_code' => 'SHIFT-BILL-'.uniqid(),
            'cashier_user_id' => $this->cashierUser->id,
            'branch_id' => $this->retailBranch->id,
            'opening_cash' => 500000,
            'opened_at' => now(),
            'status' => 'OPEN',
        ]);

        // 1. Order 2 cups of Latte -> Should succeed and reduce stock to exactly 0
        $order = $billingService->checkout($shift, $this->cashierUser, [
            'order_type' => 'DINE_IN',
            'payment_method' => 'CASH',
            'items' => [
                ['product_id' => $cafeLatte->id, 'quantity' => 2, 'unit_price' => 35000],
            ],
        ]);

        $this->assertEquals(77700.0, (float) $order->total_amount); // 70.000 + 11% PB1 (7.700)
        $this->assertEquals(0.0, (float) WarehouseStock::where('warehouse_id', $this->barWarehouse->id)->where('product_id', $espressoBean->id)->value('quantity'));
        $this->assertEquals(0.0, (float) WarehouseStock::where('warehouse_id', $this->barWarehouse->id)->where('product_id', $freshMilk->id)->value('quantity'));

        // 2. Order 1 more cup of Latte when stock is 0 -> Must throw InsufficientStockException and prevent negative stock!
        $this->expectException(InsufficientStockException::class);
        $billingService->checkout($shift, $this->cashierUser, [
            'order_type' => 'DINE_IN',
            'payment_method' => 'CASH',
            'items' => [
                ['product_id' => $cafeLatte->id, 'quantity' => 1, 'unit_price' => 35000],
            ],
        ]);
    }

    /**
     * TICK-043: Non-Revenue Transaction (Espresso Grinder Calibration).
     */
    public function test_pos_non_revenue_calibration_transaction(): void
    {
        $billingService = new PosBillingService;

        $category = ProductCategory::firstOrCreate(['name' => 'Espresso Menu'], ['description' => 'Coffee']);
        $espressoBean = Product::firstOrCreate(['sku' => 'ING-ESP-BEANS-2'], [
            'category_id' => $category->id,
            'name' => 'Espresso Dial-in Beans',
            'unit' => 'gram',
            'cost_price' => 150.0,
            'selling_price' => 0.0,
            'is_raw_material' => true,
        ]);

        $espressoShot = Product::firstOrCreate(['sku' => 'BEV-ESP-SHOT'], [
            'category_id' => $category->id,
            'name' => 'Double Espresso Shot',
            'unit' => 'cup',
            'cost_price' => 3000.0,
            'selling_price' => 25000.0,
            'is_raw_material' => false,
        ]);

        ProductRecipe::firstOrCreate(['product_id' => $espressoShot->id, 'ingredient_product_id' => $espressoBean->id], [
            'quantity_required' => 18.0,
            'unit' => 'gram',
        ]);

        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $espressoBean->id],
            ['quantity' => 100.0, 'reserved_quantity' => 0]
        );

        $shift = PosShift::create([
            'shift_code' => 'SHIFT-NR-'.uniqid(),
            'cashier_user_id' => $this->cashierUser->id,
            'branch_id' => $this->retailBranch->id,
            'opening_cash' => 500000,
            'opened_at' => now(),
            'status' => 'OPEN',
        ]);

        // Calibration 2 double shots (36g)
        $nrOrder = $billingService->checkout($shift, $this->cashierUser, [
            'order_type' => 'NON_REVENUE',
            'non_revenue_reason' => 'CALIBRATION',
            'items' => [
                ['product_id' => $espressoShot->id, 'quantity' => 2],
            ],
        ]);

        $this->assertEquals(0.00, (float) $nrOrder->total_amount);
        $this->assertEquals('NON_REVENUE', $nrOrder->payment_method);
        $this->assertEquals('CALIBRATION', $nrOrder->non_revenue_reason);

        // Stock must be deducted: 100g - 36g = 64g
        $this->assertEquals(64.0, (float) WarehouseStock::where('warehouse_id', $this->barWarehouse->id)->where('product_id', $espressoBean->id)->value('quantity'));

        // StockCard must have reference_type = 'ADJUSTMENT'
        $card = StockCard::where('reference_id', $nrOrder->id)->where('reference_type', 'ADJUSTMENT')->first();
        $this->assertNotNull($card);
    }

    /**
     * TICK-044: Split bill calculation and PB1 tax breakdown.
     */
    public function test_split_bill_and_pb1_tax_calculation(): void
    {
        $billing = new PosBillingService;

        // 1. Pricing calculation with PB1 11%
        $pricing = $billing->calculatePricing([
            ['unit_price' => 50000, 'quantity' => 2], // 100.000
        ], 10000); // 10.000 discount

        // Subtotal: 100.000, Discount: 10.000, Taxable: 90.000, PB1 11%: 9.900, Total: 99.900
        $this->assertEquals(100000.0, $pricing['subtotal']);
        $this->assertEquals(10000.0, $pricing['discount_amount']);
        $this->assertEquals(9900.0, $pricing['tax_amount']);
        $this->assertEquals(99900.0, $pricing['total_amount']);

        // 2. Split Bill across 3 splits (99.900 / 3 = 33.300 each)
        $splits3 = $billing->calculateSplitBill(99900.0, 3);
        $this->assertCount(3, $splits3);
        $this->assertEquals([33300.0, 33300.0, 33300.0], $splits3);

        // 3. Split Bill with odd cents distribution (100.000 / 3 = 33.333,34, 33.333,33, 33.333,33)
        $splitsOdd = $billing->calculateSplitBill(100.0, 3);
        $this->assertEquals(100.0, array_sum($splitsOdd));
    }
}
