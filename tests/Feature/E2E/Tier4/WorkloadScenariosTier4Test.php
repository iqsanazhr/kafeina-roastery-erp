<?php

namespace Tests\Feature\E2E\Tier4;

use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\MaterialRequest;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\RoastingProfile;
use App\Models\Role;
use App\Models\StockCard;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Services\CashierPinAuthService;
use App\Services\FifoStockAllocatorService;
use App\Services\InventoryDispatchService;
use App\Services\LotGeneratorService;
use App\Services\PosShiftService;
use App\Services\QualityControlService;
use App\Services\WorkOrderService;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WorkloadScenariosTier4Test extends TestCase
{
    use DatabaseTransactions;

    protected QualityControlService $qcService;

    protected LotGeneratorService $lotService;

    protected WorkOrderService $woService;

    protected FifoStockAllocatorService $fifoService;

    protected InventoryDispatchService $dispatchService;

    protected PosShiftService $shiftService;

    protected CashierPinAuthService $pinService;

    protected Branch $hqBranch;

    protected Branch $retailBranch;

    protected InventoryWarehouse $centralRawWarehouse;

    protected InventoryWarehouse $centralRoastWarehouse;

    protected InventoryWarehouse $transitWarehouse;

    protected InventoryWarehouse $retailBarWarehouse;

    protected User $adminUser;

    protected User $roasterUser;

    protected User $cashierUser;

    protected User $supervisorUser;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->lotService = new LotGeneratorService;
        $this->qcService = new QualityControlService($this->lotService);
        $this->woService = new WorkOrderService;
        $this->fifoService = new FifoStockAllocatorService;
        $this->dispatchService = new InventoryDispatchService($this->fifoService);
        $this->pinService = new CashierPinAuthService;
        $this->shiftService = new PosShiftService($this->pinService);

        // Branches
        $this->hqBranch = Branch::firstOrCreate(
            ['code' => 'HQ-WL'],
            ['name' => 'HQ Roastery Workload', 'type' => 'CENTRAL_ROASTERY', 'address' => 'Jl. Roastery No. 9', 'phone' => '021-999']
        );

        $this->retailBranch = Branch::firstOrCreate(
            ['code' => 'BR-WL'],
            ['name' => 'Senopati Bar Workload', 'type' => 'RETAIL_BRANCH', 'address' => 'Jl. Senopati No. 99', 'phone' => '021-888']
        );

        // Warehouses
        $this->centralRawWarehouse = InventoryWarehouse::firstOrCreate(
            ['code' => 'WH-RAW-WL'],
            ['branch_id' => $this->hqBranch->id, 'name' => 'Central Raw Warehouse WL', 'type' => 'CENTRAL_RAW']
        );

        $this->centralRoastWarehouse = InventoryWarehouse::firstOrCreate(
            ['code' => 'WH-ROAST-WL'],
            ['branch_id' => $this->hqBranch->id, 'name' => 'Central Roast Warehouse WL', 'type' => 'CENTRAL_ROASTED']
        );

        $this->transitWarehouse = InventoryWarehouse::firstOrCreate(
            ['code' => 'WH-TRANSIT-WL'],
            ['branch_id' => $this->hqBranch->id, 'name' => 'Virtual Transit WL', 'type' => 'TRANSIT']
        );

        $this->retailBarWarehouse = InventoryWarehouse::firstOrCreate(
            ['code' => 'WH-BAR-WL'],
            ['branch_id' => $this->retailBranch->id, 'name' => 'Senopati Bar Stock WL', 'type' => 'RETAIL_BAR']
        );

        // Roles & Users
        $adminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Admin']);
        $roasterRole = Role::firstOrCreate(['name' => 'head_roaster'], ['display_name' => 'Roaster']);
        $cashierRole = Role::firstOrCreate(['name' => 'branch_cashier'], ['display_name' => 'Cashier']);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor'], ['display_name' => 'Supervisor']);

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_wl@kafeina.test'],
            ['role_id' => $adminRole->id, 'branch_id' => $this->hqBranch->id, 'name' => 'Admin Workload', 'password' => bcrypt('pass')]
        );

        $this->roasterUser = User::firstOrCreate(
            ['email' => 'roaster_wl@kafeina.test'],
            ['role_id' => $roasterRole->id, 'branch_id' => $this->hqBranch->id, 'name' => 'Roaster Workload', 'password' => bcrypt('pass')]
        );

        $this->cashierUser = User::firstOrCreate(
            ['email' => 'cashier_wl@kafeina.test'],
            ['role_id' => $cashierRole->id, 'branch_id' => $this->retailBranch->id, 'name' => 'Cashier Workload', 'password' => bcrypt('pass'), 'pin_code' => bcrypt('123456')]
        );

        $this->supervisorUser = User::firstOrCreate(
            ['email' => 'supervisor_wl@kafeina.test'],
            ['role_id' => $supervisorRole->id, 'branch_id' => $this->retailBranch->id, 'name' => 'Supervisor Workload', 'password' => bcrypt('pass'), 'pin_code' => bcrypt('889900')]
        );
    }

    /**
     * Scenario 1: Complete Farm-to-Cup Operational Lifecycle.
     * Supplier -> PO -> QC -> Lot -> Roasting WO -> HPP -> Central Stock -> MR -> FIFO Dispatch -> Transit -> Branch Receipt -> Shift Open -> POS Order -> PB1 -> Cash Checkout -> Shift Close.
     */
    public function test_scenario_1_complete_farm_to_cup_lifecycle(): void
    {
        // 1. Supplier & PO
        $supplier = Supplier::create([
            'code' => 'SUP-F2C-'.uniqid(),
            'name' => 'Koperasi Tani Takengon',
            'contact_person' => 'Pak Burhan',
            'phone' => '081234567890',
            'address' => 'Desa Bebesen, Aceh Tengah',
            'farm_location' => 'Gayo Highlands',
            'altitude_masl' => 1550,
            'rating_score' => 4.90,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-F2C-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->hqBranch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(5),
            'total_amount' => 12000000.00,
            'status' => 'ISSUED',
        ]);

        // 2. Green Bean Lot arrival & QC Laboratory Inspection
        $lot = GreenBeanLot::create([
            'lot_number' => 'TEMP-F2C-'.uniqid(),
            'purchase_order_id' => $po->id,
            'variety' => 'Arabica Gayo Bourbon',
            'origin_region' => 'Aceh Gayo',
            'process_type' => 'WASHED',
            'harvest_year' => (int) date('Y'),
            'initial_weight_grams' => 60000.00,
            'current_weight_grams' => 60000.00,
            'price_per_gram' => 120.00,
        ]);

        $qc = $this->qcService->inspectLot($lot, $this->adminUser, [
            'moisture_content' => 11.20,
            'bulk_density_g_l' => 715.00,
            'primary_defects' => 0,
            'secondary_defects' => 3,
            'sca_cupping_score' => 86.75,
        ]);

        $this->assertEquals('ACCEPTED', $qc->qc_status);
        $lot->refresh();
        $this->assertStringStartsWith('GB-', $lot->lot_number);

        // 3. Finished Product & Roasting Profile setup
        $bevCategory = ProductCategory::firstOrCreate(['name' => 'Espresso Specialty']);
        $finishedBean = Product::create([
            'category_id' => $bevCategory->id,
            'sku' => 'SKU-F2C-BEAN-'.uniqid(),
            'name' => 'Gayo Bourbon Roasted Beans',
            'unit' => 'gram',
            'cost_price' => 0,
            'selling_price' => 120000,
            'is_raw_material' => false,
            'is_active' => true,
        ]);

        $profile = RoastingProfile::create([
            'profile_code' => 'ROAST-F2C-'.uniqid(),
            'name' => 'Profile Espresso Gayo',
            'roast_level' => 'MEDIUM',
            'charge_temp_celsius' => 205.00,
            'turning_point_celsius' => 98.00,
            'development_time_ratio' => 15.50,
            'target_air_flow' => 'Standard Airflow',
        ]);

        // 4. Roasting Work Order Scheduling & Execution
        $wo = $this->woService->createWorkOrder([
            'lot_id' => $lot->id,
            'profile_id' => $profile->id,
            'green_weight_grams' => 10000.00,
            'notes' => 'Batch Sangrai Senopati Bar',
        ], $this->roasterUser);

        $this->woService->startRoasting($wo);

        // 5. Roasting completion with 15.2% shrinkage (8480g cooled) & Real HPP calculation
        $outcome = $this->woService->completeWorkOrder(
            $wo,
            8480.00,
            25000.00, // Energy cost
            $finishedBean->id,
            $this->centralRoastWarehouse->id
        );

        $this->assertEquals('COMPLETED', $outcome['work_order']->status);
        $this->assertEquals(15.20, $outcome['yield_loss_percentage']);
        $this->assertEquals('NORMAL', $outcome['tolerance']['status']);
        $this->assertGreaterThan(120.00, $outcome['real_hpp_per_gram']); // Dynamic HPP > raw green price

        // 6. Branch Requisition (MR) & FIFO Virtual Transit Dispatch
        $mr = $this->dispatchService->submitMaterialRequest(
            $this->retailBranch,
            $this->hqBranch,
            $this->cashierUser,
            [['product_id' => $finishedBean->id, 'quantity' => 4000.00]],
            'Requisition for Senopati Bar'
        );

        $this->dispatchService->approveMaterialRequest($mr, $this->adminUser);

        $dn = $this->dispatchService->dispatchMaterialRequest(
            $mr,
            $this->centralRoastWarehouse,
            $this->transitWarehouse,
            'Kurir Ekspedisi Kafeina',
            $this->adminUser
        );

        $this->assertEquals('DISPATCHED', $dn->status);

        // 7. Branch Scans QR & Confirms Physical Receipt into Bar Stock
        $verifiedDn = $this->dispatchService->verifyBranchReceipt(
            $dn,
            $this->retailBarWarehouse,
            [$finishedBean->id => 4000.00],
            $this->cashierUser
        );

        $this->assertEquals('DELIVERED', $verifiedDn->status);

        $barStock = WarehouseStock::where('warehouse_id', $this->retailBarWarehouse->id)
            ->where('product_id', $finishedBean->id)
            ->first();
        $this->assertEquals(4000.00, (float) $barStock->quantity);

        // 8. Cashier Shift Opening
        $shift = $this->shiftService->openShift($this->cashierUser, $this->retailBranch, 500000.00);
        $this->assertEquals('OPEN', $shift->status);

        // 9. Web POS Touch Order Checkout with PB1 11% Restaurant Tax
        $subtotal = 100000.00; // 2x Specialty Coffee @ 50.000
        $pb1Tax = round($subtotal * 0.11); // 11.000
        $totalAmount = $subtotal + $pb1Tax; // 111.000
        $cashTendered = 150000.00;
        $changeAmount = $cashTendered - $totalAmount; // 39.000

        $order = PosOrder::create([
            'order_number' => 'ORD-F2C-'.uniqid(),
            'shift_id' => $shift->id,
            'cashier_id' => $this->cashierUser->id,
            'branch_id' => $this->retailBranch->id,
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
            'tax_amount' => $pb1Tax,
            'discount_amount' => 0.00,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
            'order_type' => 'DINE_IN',
        ]);

        // 10. Pessimistic Recipe Stock Auto-Deduct (e.g. 36g espresso dose)
        $barStock->quantity -= 36.00;
        $barStock->save();

        StockCard::create([
            'warehouse_id' => $this->retailBarWarehouse->id,
            'product_id' => $finishedBean->id,
            'reference_type' => 'POS_SALE',
            'reference_id' => $order->id,
            'quantity_in' => 0,
            'quantity_out' => 36.00,
            'balance_after' => $barStock->quantity,
            'cost_per_unit' => $finishedBean->cost_price,
            'notes' => 'Auto-Deduct Resep POS [ORD-F2C]',
        ]);

        $this->assertEquals(3964.00, (float) $barStock->quantity);

        // 11. Shift Closing & Cash Reconciliation
        // Expected cash = 500.000 opening + 111.000 sales (incl. PB1 11%) = 611.000
        $closedShift = $this->shiftService->closeShift($shift, 611000.00);

        $this->assertEquals('CLOSED', $closedShift->status);
        $this->assertEquals(0.00, (float) $closedShift->cash_difference);
    }

    /**
     * Scenario 2: Morning Barista Dial-In & Grinder Calibration (Non-Revenue Transaction).
     */
    public function test_scenario_2_morning_barista_calibration_dial_in(): void
    {
        $shift = $this->shiftService->openShift($this->cashierUser, $this->retailBranch, 500000.00);

        // Checkout with Non-Revenue Payment
        $payload = [
            'items' => [
                ['id' => 'PRD-ESP-01', 'name' => 'Dial-in Double Shot', 'price' => 35000, 'qty' => 3],
            ],
            'payment_method' => 'non_revenue',
            'non_revenue_reason' => 'Kalibrasi Espresso Harian (Dialing-in)',
            'order_type' => 'Non-Revenue',
        ];

        $response = $this->postJson('/pos/checkout', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'total' => 0,
                'paid_amount' => 0,
                'payment_method' => 'non_revenue',
                'non_revenue_reason' => 'Kalibrasi Espresso Harian (Dialing-in)',
            ],
        ]);
    }

    /**
     * Scenario 5: Roasting Yield Loss Anomaly & Sensory Re-Evaluation Flag.
     */
    public function test_scenario_5_roasting_yield_loss_anomaly_detection(): void
    {
        // 5000g green beans in, 4000g roasted out -> 20.00% shrinkage (> 18.00% standard)
        $yieldLoss = $this->woService->calculateYieldLoss(5000.00, 4000.00);
        $tolerance = $this->woService->validateYieldTolerance($yieldLoss);

        $this->assertEquals(20.00, $yieldLoss);
        $this->assertEquals('OVER_ROASTED', $tolerance['status']);
        $this->assertFalse($tolerance['is_acceptable']);
        $this->assertStringContainsString('Peringatan Kritis', $tolerance['message']);
    }

    /**
     * Scenario 6: In-Transit Discrepancy & Transit Loss Write-Off.
     */
    public function test_scenario_6_in_transit_discrepancy_and_loss_write_off(): void
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Beans']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-SCN6-'.uniqid(),
            'name' => 'Kerinci Washed 250g',
            'unit' => 'pcs',
            'cost_price' => 50000,
            'selling_price' => 90000,
            'is_raw_material' => false,
            'is_active' => true,
        ]);

        // Place 20 pcs in transit
        WarehouseStock::create([
            'warehouse_id' => $this->transitWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);

        $mr = MaterialRequest::create([
            'request_number' => 'MR-SCN6-'.uniqid(),
            'from_branch_id' => $this->retailBranch->id,
            'to_branch_id' => $this->hqBranch->id,
            'requested_by_user_id' => $this->cashierUser->id,
            'status' => 'IN_TRANSIT',
        ]);

        $dn = DeliveryNote::create([
            'delivery_number' => 'SJ-SCN6-'.uniqid(),
            'request_id' => $mr->id,
            'source_warehouse_id' => $this->centralRoastWarehouse->id,
            'dest_warehouse_id' => $this->transitWarehouse->id,
            'dispatched_by_user_id' => $this->adminUser->id,
            'courier_name' => 'Internal Van',
            'tracking_code' => 'TRK-SCN6-'.uniqid(),
            'status' => 'DISPATCHED',
        ]);

        DeliveryNoteItem::create([
            'delivery_note_id' => $dn->id,
            'product_id' => $product->id,
            'quantity_sent' => 20,
        ]);

        // Branch receives 19 pcs (1 pc broken/damaged in transit)
        $verifiedDn = $this->dispatchService->verifyBranchReceipt(
            $dn,
            $this->retailBarWarehouse,
            [$product->id => 19],
            $this->cashierUser,
            '1 kantong pecah saat perjalanan'
        );

        $this->assertEquals('RECONCILED', $verifiedDn->status);

        // 19 pcs credited to active bar stock
        $barStock = WarehouseStock::where('warehouse_id', $this->retailBarWarehouse->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertEquals(19, (float) $barStock->quantity);

        // Check discrepancy adjustment stock card was created
        $adjCard = StockCard::where('warehouse_id', $this->retailBarWarehouse->id)
            ->where('product_id', $product->id)
            ->where('reference_type', 'ADJUSTMENT')
            ->first();

        $this->assertNotNull($adjCard);
        $this->assertEquals(1, (float) $adjCard->quantity_out);
    }

    /**
     * Scenario 7: Shift Closing Cash Discrepancy & Supervisor Escalation.
     */
    public function test_scenario_7_shift_closing_discrepancy_supervisor_escalation(): void
    {
        $shift = $this->shiftService->openShift($this->cashierUser, $this->retailBranch, 500000.00);

        // Attempting to close with -50.000 discrepancy without supervisor PIN fails
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Otorisasi 6-digit PIN Supervisor wajib dimasukkan!');

        $this->shiftService->closeShift($shift, 450000.00);
    }

    /**
     * Scenario 8: POS Terminal Brute-Force PIN Attack & Security Lockdown.
     */
    public function test_scenario_8_pos_brute_force_attack_triggers_security_lockdown(): void
    {
        // 5 consecutive strikes with invalid PIN
        for ($i = 0; $i < 5; $i++) {
            $this->pinService->verifyPin($this->cashierUser, '999999', '192.168.1.100');
        }

        $this->assertTrue($this->pinService->isLockedOut($this->cashierUser));

        // Audit log records SECURITY_BREACH
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashierUser->id,
            'event_type' => 'SECURITY_BREACH',
            'ip_address' => '192.168.1.100',
        ]);
    }
}
