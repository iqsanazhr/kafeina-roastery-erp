<?php

namespace Tests\Feature\E2E\Tier3;

use App\Models\Branch;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\MaterialRequest;
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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PairwiseIntegrationTier3Test extends TestCase
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

        $this->lotService = new LotGeneratorService;
        $this->qcService = new QualityControlService($this->lotService);
        $this->woService = new WorkOrderService;
        $this->fifoService = new FifoStockAllocatorService;
        $this->dispatchService = new InventoryDispatchService($this->fifoService);
        $this->pinService = new CashierPinAuthService;
        $this->shiftService = new PosShiftService($this->pinService);

        // Branches
        $this->hqBranch = Branch::firstOrCreate(
            ['code' => 'HQ-PAIR'],
            ['name' => 'HQ Roastery Pairwise', 'type' => 'CENTRAL_ROASTERY', 'address' => 'Jl. Kopi No. 1', 'phone' => '021-111']
        );

        $this->retailBranch = Branch::firstOrCreate(
            ['code' => 'BR-PAIR'],
            ['name' => 'Retail Bar Pairwise', 'type' => 'RETAIL_BRANCH', 'address' => 'Jl. Senopati No. 8', 'phone' => '021-222']
        );

        // Warehouses
        $this->centralRawWarehouse = InventoryWarehouse::firstOrCreate(
            ['code' => 'WH-RAW-PAIR'],
            ['branch_id' => $this->hqBranch->id, 'name' => 'Central Raw Warehouse', 'type' => 'CENTRAL_RAW']
        );

        $this->centralRoastWarehouse = InventoryWarehouse::firstOrCreate(
            ['code' => 'WH-ROAST-PAIR'],
            ['branch_id' => $this->hqBranch->id, 'name' => 'Central Roasted Warehouse', 'type' => 'CENTRAL_ROASTED']
        );

        $this->transitWarehouse = InventoryWarehouse::firstOrCreate(
            ['code' => 'WH-TRANSIT-PAIR'],
            ['branch_id' => $this->hqBranch->id, 'name' => 'Virtual Transit Warehouse', 'type' => 'TRANSIT']
        );

        $this->retailBarWarehouse = InventoryWarehouse::firstOrCreate(
            ['code' => 'WH-BAR-PAIR'],
            ['branch_id' => $this->retailBranch->id, 'name' => 'Retail Bar Warehouse', 'type' => 'RETAIL_BAR']
        );

        // Roles & Users
        $adminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Admin']);
        $roasterRole = Role::firstOrCreate(['name' => 'head_roaster'], ['display_name' => 'Roaster']);
        $cashierRole = Role::firstOrCreate(['name' => 'branch_cashier'], ['display_name' => 'Cashier']);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor'], ['display_name' => 'Supervisor']);

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_pair@kafeina.test'],
            ['role_id' => $adminRole->id, 'branch_id' => $this->hqBranch->id, 'name' => 'Admin Pair', 'password' => bcrypt('pass')]
        );

        $this->roasterUser = User::firstOrCreate(
            ['email' => 'roaster_pair@kafeina.test'],
            ['role_id' => $roasterRole->id, 'branch_id' => $this->hqBranch->id, 'name' => 'Roaster Pair', 'password' => bcrypt('pass')]
        );

        $this->cashierUser = User::firstOrCreate(
            ['email' => 'cashier_pair@kafeina.test'],
            ['role_id' => $cashierRole->id, 'branch_id' => $this->retailBranch->id, 'name' => 'Cashier Pair', 'password' => bcrypt('pass'), 'pin_code' => bcrypt('123123')]
        );

        $this->supervisorUser = User::firstOrCreate(
            ['email' => 'super_pair@kafeina.test'],
            ['role_id' => $supervisorRole->id, 'branch_id' => $this->retailBranch->id, 'name' => 'Supervisor Pair', 'password' => bcrypt('pass'), 'pin_code' => bcrypt('998877')]
        );
    }

    /**
     * Pairwise F3 & F4: QC Lab Acceptance -> Automatic Traceable Lot Number Generation.
     */
    public function test_pairwise_qc_acceptance_triggers_traceable_lot_registration(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-GAYO-'.rand(100, 999),
            'name' => 'Koperasi Kopi Gayo Mandiri',
            'contact_person' => 'Aman Gayo',
            'phone' => '08123456789',
            'address' => 'Aceh Tengah',
            'farm_location' => 'Gayo Highlands',
            'altitude_masl' => 1500,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-PAIR-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->hqBranch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(3),
            'total_amount' => 10000000,
            'status' => 'ISSUED',
        ]);

        $lot = GreenBeanLot::create([
            'lot_number' => 'TEMP-'.uniqid(),
            'purchase_order_id' => $po->id,
            'variety' => 'Arabica Gayo Typica',
            'origin_region' => 'Aceh Gayo',
            'process_type' => 'WASHED',
            'harvest_year' => (int) date('Y'),
            'initial_weight_grams' => 60000.00,
            'current_weight_grams' => 60000.00,
            'price_per_gram' => 120.00,
        ]);

        $qc = $this->qcService->inspectLot($lot, $this->adminUser, [
            'moisture_content' => 11.20,
            'bulk_density_g_l' => 720.00,
            'primary_defects' => 0,
            'secondary_defects' => 2,
            'sca_cupping_score' => 85.50,
        ]);

        $lot->refresh();

        $this->assertEquals('ACCEPTED', $qc->qc_status);
        $this->assertStringStartsWith('GB-', $lot->lot_number);
        $this->assertStringContainsString((string) date('Y'), $lot->lot_number);
    }

    /**
     * Pairwise F4 & F8: Green Bean Lot Registration -> Work Order Green Bean Reservation.
     */
    public function test_pairwise_lot_registration_to_work_order_scheduling(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-KERINCI-'.rand(100, 999),
            'name' => 'Kelompok Tani Kerinci',
            'contact_person' => 'Pak Joko',
            'phone' => '08123456789',
            'address' => 'Kayu Aro',
            'farm_location' => 'Gunung Kerinci',
            'altitude_masl' => 1600,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-PAIR-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->hqBranch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(3),
            'total_amount' => 5000000,
            'status' => 'ISSUED',
        ]);

        $lot = GreenBeanLot::create([
            'lot_number' => 'GB-KERINCI-2026-0001',
            'purchase_order_id' => $po->id,
            'variety' => 'Arabica Kerinci Natural',
            'origin_region' => 'Kerinci',
            'process_type' => 'NATURAL',
            'harvest_year' => 2026,
            'initial_weight_grams' => 10000.00,
            'current_weight_grams' => 10000.00,
            'price_per_gram' => 135.00,
        ]);

        $profile = RoastingProfile::create([
            'profile_code' => 'ROAST-LIGHT-'.rand(100, 999),
            'name' => 'Nordic Light Filter',
            'roast_level' => 'LIGHT',
            'charge_temp_celsius' => 200.00,
            'turning_point_celsius' => 95.00,
            'development_time_ratio' => 14.50,
            'target_air_flow' => 'High Airflow',
        ]);

        $wo = $this->woService->createWorkOrder([
            'lot_id' => $lot->id,
            'profile_id' => $profile->id,
            'green_weight_grams' => 5000.00,
            'notes' => 'Batch Roasting Uji Pairwise',
        ], $this->roasterUser);

        $this->assertEquals('SCHEDULED', $wo->status);
        $this->assertEquals(5000.00, (float) $wo->green_weight_grams);
        $this->assertStringStartsWith('WO-', $wo->wo_number);
    }

    /**
     * Pairwise F8 & F11: Work Order Completion -> Atomic Stock Conversion & Real HPP.
     */
    public function test_pairwise_work_order_completion_to_finished_stock_conversion(): void
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Single Origin Roasted Beans']);
        $roastedProduct = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-ROAST-'.uniqid(),
            'name' => 'Aceh Gayo Roasted 250g',
            'unit' => 'gram',
            'cost_price' => 0,
            'selling_price' => 95000,
            'is_raw_material' => false,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'code' => 'SUP-PAIR-'.rand(100, 999),
            'name' => 'Supplier Pairwise',
            'contact_person' => 'Staff',
            'phone' => '081234',
            'address' => 'Sumatra',
            'farm_location' => 'Takengon',
            'altitude_masl' => 1450,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-WO-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->hqBranch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(2),
            'total_amount' => 2000000,
            'status' => 'ISSUED',
        ]);

        $lot = GreenBeanLot::create([
            'lot_number' => 'GB-PAIR-2026-0002',
            'purchase_order_id' => $po->id,
            'variety' => 'Arabica Typica',
            'origin_region' => 'Gayo',
            'process_type' => 'WASHED',
            'harvest_year' => 2026,
            'initial_weight_grams' => 10000.00,
            'current_weight_grams' => 10000.00,
            'price_per_gram' => 100.00,
        ]);

        $profile = RoastingProfile::create([
            'profile_code' => 'ROAST-MED-'.rand(100, 999),
            'name' => 'Omni Roast Espresso',
            'roast_level' => 'MEDIUM',
            'charge_temp_celsius' => 210.00,
            'turning_point_celsius' => 100.00,
            'development_time_ratio' => 16.00,
            'target_air_flow' => 'Standard',
        ]);

        $wo = $this->woService->createWorkOrder([
            'lot_id' => $lot->id,
            'profile_id' => $profile->id,
            'green_weight_grams' => 5000.00,
        ], $this->roasterUser);

        $this->woService->startRoasting($wo);

        // Roasted weight 4250g (15% yield loss, optimal)
        $outcome = $this->woService->completeWorkOrder(
            $wo,
            4250.00,
            15000.00,
            $roastedProduct->id,
            $this->centralRoastWarehouse->id
        );

        $this->assertEquals('COMPLETED', $outcome['work_order']->status);
        $this->assertEquals(15.00, $outcome['yield_loss_percentage']);
        $this->assertEquals('NORMAL', $outcome['tolerance']['status']);
        $this->assertGreaterThan(0, $outcome['real_hpp_per_gram']);

        // Check stock intake in central roastery warehouse
        $stock = WarehouseStock::where('warehouse_id', $this->centralRoastWarehouse->id)
            ->where('product_id', $roastedProduct->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(4250.00, (float) $stock->quantity);
    }

    /**
     * Pairwise F12 & F13 & F14: MR Submission -> FIFO Central Dispatch -> Virtual TRANSIT Isolation.
     */
    public function test_pairwise_fifo_dispatch_to_virtual_transit_isolation(): void
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Retail Coffee Beans']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-FIFO-'.uniqid(),
            'name' => 'Flores Bajawa Roasted 250g',
            'unit' => 'gram',
            'cost_price' => 150,
            'selling_price' => 85000,
            'is_raw_material' => false,
            'is_active' => true,
        ]);

        // Place 20.000g in Central Roast Warehouse
        $centralStock = WarehouseStock::create([
            'warehouse_id' => $this->centralRoastWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 20000.00,
            'reserved_quantity' => 0.00,
        ]);

        StockCard::create([
            'warehouse_id' => $this->centralRoastWarehouse->id,
            'product_id' => $product->id,
            'reference_type' => 'ROAST_OUTPUT',
            'reference_id' => 1,
            'quantity_in' => 20000.00,
            'quantity_out' => 0,
            'balance_after' => 20000.00,
            'cost_per_unit' => 150,
        ]);

        // Branch submits Material Request for 5.000g
        $mr = $this->dispatchService->submitMaterialRequest(
            $this->retailBranch,
            $this->hqBranch,
            $this->cashierUser,
            [['product_id' => $product->id, 'quantity' => 5000.00]],
            'Requisition 5kg beans for weekend'
        );

        $this->assertEquals('PENDING', $mr->status);

        // Central approves MR
        $this->dispatchService->approveMaterialRequest($mr, $this->adminUser);

        // Dispatch MR via FIFO and isolate into TRANSIT
        $dn = $this->dispatchService->dispatchMaterialRequest(
            $mr,
            $this->centralRoastWarehouse,
            $this->transitWarehouse,
            'SiCepat Cargo',
            $this->adminUser
        );

        $this->assertEquals('DISPATCHED', $dn->status);
        $this->assertStringStartsWith('SJ-', $dn->delivery_number);

        // Verify central warehouse stock was decremented
        $centralStock->refresh();
        $this->assertEquals(15000.00, (float) $centralStock->quantity);

        // Verify transit warehouse holds isolated stock
        $transitStock = WarehouseStock::where('warehouse_id', $this->transitWarehouse->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($transitStock);
        $this->assertEquals(5000.00, (float) $transitStock->quantity);
    }

    /**
     * Pairwise F15 & F16: Delivery Note Receipt Verification -> Credit Retail Bar Warehouse.
     */
    public function test_pairwise_delivery_note_verification_credits_retail_bar(): void
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Bar Supplies']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-BAR-'.uniqid(),
            'name' => 'Fresh Milk Diamond 1L',
            'unit' => 'pcs',
            'cost_price' => 20000,
            'selling_price' => 0,
            'is_raw_material' => true,
            'is_active' => true,
        ]);

        // Stock in Transit
        WarehouseStock::create([
            'warehouse_id' => $this->transitWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $mr = MaterialRequest::create([
            'request_number' => 'MR-TEST-'.uniqid(),
            'from_branch_id' => $this->retailBranch->id,
            'to_branch_id' => $this->hqBranch->id,
            'requested_by_user_id' => $this->cashierUser->id,
            'status' => 'IN_TRANSIT',
        ]);

        $dn = DeliveryNote::create([
            'delivery_number' => 'SJ-TEST-'.uniqid(),
            'request_id' => $mr->id,
            'source_warehouse_id' => $this->centralRoastWarehouse->id,
            'dest_warehouse_id' => $this->transitWarehouse->id,
            'dispatched_by_user_id' => $this->adminUser->id,
            'courier_name' => 'Kurir Internal',
            'tracking_code' => 'TRK-'.uniqid(),
            'status' => 'DISPATCHED',
        ]);

        DeliveryNoteItem::create([
            'delivery_note_id' => $dn->id,
            'product_id' => $product->id,
            'quantity_sent' => 10,
        ]);

        // Branch manager receives and verifies all 10 pcs
        $verifiedDn = $this->dispatchService->verifyBranchReceipt(
            $dn,
            $this->retailBarWarehouse,
            [$product->id => 10],
            $this->cashierUser
        );

        $this->assertEquals('DELIVERED', $verifiedDn->status);

        // Check transit warehouse is emptied
        $transitStock = WarehouseStock::where('warehouse_id', $this->transitWarehouse->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertEquals(0, (float) ($transitStock->quantity ?? 0));

        // Check retail bar warehouse received stock
        $barStock = WarehouseStock::where('warehouse_id', $this->retailBarWarehouse->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($barStock);
        $this->assertEquals(10, (float) $barStock->quantity);
    }

    /**
     * Pairwise F18 & F25: Cashier Shift Opening -> Shift Closing with Reconciliation.
     */
    public function test_pairwise_cashier_shift_lifecycle(): void
    {
        // Cashier opens shift with 500.000 IDR float
        $shift = $this->shiftService->openShift($this->cashierUser, $this->retailBranch, 500000);

        $this->assertEquals('OPEN', $shift->status);
        $this->assertEquals(500000, (float) $shift->opening_cash);

        // Cashier closes shift with 505.000 IDR (+5.000 within 10k tolerance)
        $closed = $this->shiftService->closeShift($shift, 505000);

        $this->assertEquals('CLOSED', $closed->status);
        $this->assertEquals(5000, (float) $closed->cash_difference);
    }
}
