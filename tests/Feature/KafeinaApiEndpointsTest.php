<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GreenBeanLot;
use App\Models\InventoryWarehouse;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\RoastingProfile;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KafeinaApiEndpointsTest extends TestCase
{
    use DatabaseTransactions;

    protected Role $adminRole;

    protected Role $cashierRole;

    protected Branch $hqBranch;

    protected Branch $retailBranch;

    protected User $adminUser;

    protected User $cashierUser;

    protected InventoryWarehouse $barWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Administrator']);
        $this->cashierRole = Role::firstOrCreate(['name' => 'branch_cashier'], ['display_name' => 'Kasir Barista']);

        $this->hqBranch = Branch::firstOrCreate(['code' => 'HQ-API'], [
            'name' => 'HQ Api Test',
            'type' => 'HEADQUARTERS',
            'address' => 'Jl. Test 1',
            'phone' => '021-1111',
        ]);

        $this->retailBranch = Branch::firstOrCreate(['code' => 'BR-API'], [
            'name' => 'Branch Api Test',
            'type' => 'RETAIL_BRANCH',
            'address' => 'Jl. Test 2',
            'phone' => '021-2222',
        ]);

        $this->adminUser = User::firstOrCreate(['email' => 'api_admin@kafeina.id'], [
            'role_id' => $this->adminRole->id,
            'branch_id' => $this->hqBranch->id,
            'name' => 'API Admin',
            'password' => Hash::make('password123'),
            'pin_code' => Hash::make('654321'),
            'is_active' => true,
        ]);

        $this->cashierUser = User::firstOrCreate(['email' => 'api_cashier@kafeina.id'], [
            'role_id' => $this->cashierRole->id,
            'branch_id' => $this->retailBranch->id,
            'name' => 'API Cashier',
            'password' => Hash::make('password123'),
            'pin_code' => Hash::make('112233'),
            'is_active' => true,
        ]);

        $this->barWarehouse = InventoryWarehouse::firstOrCreate(['code' => 'WH-BAR-API'], [
            'branch_id' => $this->retailBranch->id,
            'name' => 'Bar Warehouse API',
            'type' => 'RETAIL_BAR',
            'is_active' => true,
        ]);
    }

    /**
     * Test Auth: login with email/password and verify 6-digit cashier PIN.
     */
    public function test_auth_login_and_pin_endpoints(): void
    {
        // 1. Login with credentials
        $loginRes = $this->postJson('/api/auth/login', [
            'email' => 'api_admin@kafeina.id',
            'password' => 'password123',
        ]);

        $loginRes->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);

        // 2. Verify 6-digit PIN
        $pinRes = $this->postJson('/api/auth/verify-pin', [
            'user_id' => $this->cashierUser->id,
            'pin' => '112233',
        ]);

        $pinRes->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    /**
     * Test Quality Control Inspection Endpoint.
     */
    public function test_qc_inspect_endpoint(): void
    {
        $supplier = Supplier::firstOrCreate(['code' => 'SUP-QC'], [
            'name' => 'Petani Ijen',
            'contact_person' => 'Pak Slamet',
            'phone' => '0812345',
            'address' => 'Bondowoso',
            'farm_location' => 'Ijen',
            'altitude_masl' => 1400,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-QC-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->hqBranch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'status' => 'ISSUED',
        ]);

        $lot = GreenBeanLot::create([
            'lot_number' => 'TEMP-IJEN-001',
            'purchase_order_id' => $po->id,
            'variety' => 'Arabica Blue Mountain',
            'origin_region' => 'Ijen Highland',
            'process_type' => 'WASHED',
            'harvest_year' => 2026,
            'initial_weight_grams' => 60000,
            'current_weight_grams' => 60000,
            'price_per_gram' => 120.0,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')->postJson("/api/qc/{$lot->id}/inspect", [
            'moisture_content' => 11.20,
            'bulk_density_g_l' => 710.0,
            'primary_defects' => 0,
            'secondary_defects' => 2,
            'sca_cupping_score' => 84.75,
            'notes' => 'Aroma jasmine, acidity bright citrus, clean finish.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('quality_control.qc_status', 'ACCEPTED');

        // Lot number must be auto-generated to standard format
        $lot->refresh();
        $this->assertStringStartsWith('GB-', $lot->lot_number);
    }

    /**
     * Test Work Order Roasting Life-Cycle Endpoints.
     */
    public function test_work_order_workflow_endpoints(): void
    {
        $supplier = Supplier::firstOrCreate(['code' => 'SUP-ROAST'], [
            'name' => 'Petani Kerinci',
            'contact_person' => 'Bang Edi',
            'phone' => '08123456',
            'address' => 'Kerinci, Jambi',
            'farm_location' => 'Kayu Aro',
            'altitude_masl' => 1600,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-ROAST-'.uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $this->hqBranch->id,
            'user_id' => $this->adminUser->id,
            'order_date' => now(),
            'status' => 'ISSUED',
        ]);

        $lot = GreenBeanLot::create([
            'lot_number' => 'GB-KRC-2026-0001',
            'purchase_order_id' => $po->id,
            'variety' => 'Arabica Typica',
            'origin_region' => 'Kerinci',
            'process_type' => 'NATURAL',
            'harvest_year' => 2026,
            'initial_weight_grams' => 50000,
            'current_weight_grams' => 50000,
            'price_per_gram' => 130.0,
        ]);

        $profile = RoastingProfile::firstOrCreate(['profile_code' => 'PRF-MED-01'], [
            'name' => 'Filter Profile Medium',
            'roast_level' => 'MEDIUM',
            'charge_temp_celsius' => 200.0,
            'turning_point_celsius' => 90.0,
            'development_time_ratio' => 15.5,
            'target_air_flow' => 'Medium High',
        ]);

        // 1. Create WO
        $createRes = $this->actingAs($this->adminUser, 'sanctum')->postJson('/api/work-orders', [
            'lot_id' => $lot->id,
            'profile_id' => $profile->id,
            'green_weight_grams' => 10000,
            'notes' => 'Batch uji cita rasa',
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('work_order.status', 'SCHEDULED');

        $woId = $createRes->json('work_order.id');

        // 2. Start WO
        $startRes = $this->actingAs($this->adminUser, 'sanctum')->postJson("/api/work-orders/{$woId}/start");
        $startRes->assertStatus(200)
            ->assertJsonPath('work_order.status', 'IN_PROGRESS');

        // 3. Complete WO (10.000g green -> 8.500g roasted = 15.00% yield loss)
        $completeRes = $this->actingAs($this->adminUser, 'sanctum')->postJson("/api/work-orders/{$woId}/complete", [
            'roasted_weight_grams' => 8500,
            'energy_cost' => 25000,
        ]);

        $completeRes->assertStatus(200)
            ->assertJsonPath('data.yield_loss_percentage', 15)
            ->assertJsonPath('data.tolerance.status', 'NORMAL');
    }

    /**
     * Test POS Shift & Checkout Endpoints.
     */
    public function test_pos_shift_and_checkout_endpoints(): void
    {
        $category = ProductCategory::firstOrCreate(['name' => 'Manual Brew Menu'], ['description' => 'Filter']);
        $product = Product::firstOrCreate(['sku' => 'V60-GEISHA'], [
            'category_id' => $category->id,
            'name' => 'V60 Geisha Anaerobic',
            'unit' => 'cup',
            'cost_price' => 25000,
            'selling_price' => 60000,
            'is_raw_material' => false,
            'is_active' => true,
        ]);

        // Stock in bar warehouse
        WarehouseStock::updateOrCreate(
            ['warehouse_id' => $this->barWarehouse->id, 'product_id' => $product->id],
            ['quantity' => 10, 'reserved_quantity' => 0]
        );

        // 1. Open shift
        $openRes = $this->actingAs($this->cashierUser, 'sanctum')->postJson('/api/pos/shift/open', [
            'branch_id' => $this->retailBranch->id,
            'opening_cash' => 400000,
        ]);

        $openRes->assertStatus(201)
            ->assertJsonPath('shift.status', 'OPEN');

        $shiftId = $openRes->json('shift.id');

        // 2. Checkout Order: 1 cup of V60 Geisha
        $checkoutRes = $this->actingAs($this->cashierUser, 'sanctum')->postJson('/api/pos/checkout', [
            'shift_id' => $shiftId,
            'order_type' => 'DINE_IN',
            'payment_method' => 'CASH',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 60000],
            ],
        ]);

        $checkoutRes->assertStatus(201)
            ->assertJsonPath('order.total_amount', '66600.00'); // 60.000 + 11% PB1 (6.600)

        // 3. Split bill calculation
        $splitRes = $this->actingAs($this->cashierUser, 'sanctum')->postJson('/api/pos/split-bill', [
            'total_amount' => 66600,
            'splits' => 2,
        ]);

        $splitRes->assertStatus(200)
            ->assertJson(['splits' => [33300, 33300]]);

        // 4. Close Shift (Expected: 400.000 + 66.000 = 466.000, Actual: 466.000 -> Diff 0)
        $closeRes = $this->actingAs($this->cashierUser, 'sanctum')->postJson("/api/pos/shift/{$shiftId}/close", [
            'closing_cash_actual' => 466000,
        ]);

        $closeRes->assertStatus(200)
            ->assertJsonPath('shift.status', 'CLOSED');
    }
}
