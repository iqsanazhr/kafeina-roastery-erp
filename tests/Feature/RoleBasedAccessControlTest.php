<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PosOrder;
use App\Models\PosShift;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleBasedAccessControlTest extends TestCase
{
    use DatabaseTransactions;

    protected User $cashierUser;

    protected User $roasterUser;

    protected User $superAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::first() ?? Branch::create([
            'code' => 'OUT-TEST',
            'name' => 'Outlet Uji',
            'type' => 'RETAIL_BRANCH',
        ]);

        $cashierRole = Role::whereIn('name', ['cashier', 'branch_cashier'])->first();
        $roasterRole = Role::whereIn('name', ['roaster', 'head_roaster'])->first();
        $adminRole = Role::where('name', 'super_admin')->first();

        $this->cashierUser = User::create([
            'email' => 'cashier_rbac_test@kafeina.test',
            'name' => 'Kasir Uji RBAC',
            'role_id' => $cashierRole->id,
            'branch_id' => $branch->id,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->roasterUser = User::create([
            'email' => 'roaster_rbac_test@kafeina.test',
            'name' => 'Roaster Uji RBAC',
            'role_id' => $roasterRole->id,
            'branch_id' => $branch->id,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->superAdminUser = User::create([
            'email' => 'admin_rbac_test@kafeina.test',
            'name' => 'Admin Uji RBAC',
            'role_id' => $adminRole->id,
            'branch_id' => $branch->id,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
    }

    public function test_cashier_can_access_pos_and_is_forbidden_from_vouchers(): void
    {
        $response = $this->actingAs($this->cashierUser)->get('/pos');
        $response->assertStatus(200);

        $voucherResponse = $this->actingAs($this->cashierUser)->get('/vouchers');
        $voucherResponse->assertStatus(403);
    }

    public function test_cashier_is_forbidden_from_finance_pnl_portal(): void
    {
        $response = $this->actingAs($this->cashierUser)->get('/finance');
        $response->assertStatus(403);
        $response->assertSee('Akses Ditolak');
        $response->assertSee('FORBIDDEN');

        // Security breach logged
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->cashierUser->id,
            'event_type' => 'SECURITY_BREACH',
        ]);
    }

    public function test_cashier_is_forbidden_from_roastery_and_procurement(): void
    {
        $this->actingAs($this->cashierUser)->get('/roastery')->assertStatus(403);
        $this->actingAs($this->cashierUser)->get('/procurement')->assertStatus(403);
        $this->actingAs($this->cashierUser)->get('/yield-loss')->assertStatus(403);
    }

    public function test_roaster_can_access_roastery_and_yield_loss_but_not_finance(): void
    {
        $this->actingAs($this->roasterUser)->get('/roastery')->assertStatus(200);
        $this->actingAs($this->roasterUser)->get('/yield-loss')->assertStatus(200);

        // Roaster cannot access financial P&L
        $this->actingAs($this->roasterUser)->get('/finance')->assertStatus(403);
    }

    public function test_super_admin_has_unrestricted_access(): void
    {
        $this->actingAs($this->superAdminUser)->get('/finance')->assertStatus(200);
        $this->actingAs($this->superAdminUser)->get('/dashboard')->assertStatus(200);
        $this->actingAs($this->superAdminUser)->get('/pos')->assertStatus(200);
        $this->actingAs($this->superAdminUser)->get('/roastery')->assertStatus(200);
        $this->actingAs($this->superAdminUser)->get('/procurement')->assertStatus(200);
    }

    public function test_sidebar_only_renders_allowed_modules_for_cashier(): void
    {
        // When cashier views the app
        $response = $this->actingAs($this->cashierUser)->get('/pos');
        $response->assertStatus(200);

        // Allowed in cashier sidebar
        $response->assertSee('Kasir POS');
        $response->assertSee('Riwayat Order');

        // Forbidden in cashier sidebar
        $response->assertDontSee('Voucher &amp; Kuota Promo', false);
        $response->assertDontSee('Laba Rugi Riil (P&L)');
        $response->assertDontSee('Operasional Roastery');
        $response->assertDontSee('Pengadaan (Procurement)');
    }

    public function test_cashier_only_sees_orders_from_their_assigned_branch(): void
    {
        $otherBranch = Branch::where('id', '!=', $this->cashierUser->branch_id)->first() ?? Branch::create([
            'code' => 'OUT-OTHER',
            'name' => 'Outlet Lain',
            'type' => 'RETAIL_BRANCH',
        ]);

        $shift = PosShift::first() ?? PosShift::create([
            'shift_code' => 'SHIFT-TEST-01',
            'cashier_user_id' => $this->cashierUser->id,
            'branch_id' => $this->cashierUser->branch_id,
            'opening_cash' => 500000,
            'opened_at' => now(),
            'status' => 'OPEN',
        ]);

        $myOrder = PosOrder::create([
            'order_number' => 'ORD-MY-BRANCH-'.uniqid(),
            'shift_id' => $shift->id,
            'branch_id' => $this->cashierUser->branch_id,
            'cashier_id' => $this->cashierUser->id,
            'order_type' => 'DINE_IN',
            'customer_name' => 'Pelanggan Cabang Sendiri',
            'subtotal' => 50000,
            'tax_amount' => 5500,
            'discount_amount' => 0,
            'total_amount' => 55500,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        $otherOrder = PosOrder::create([
            'order_number' => 'ORD-OTHER-BRANCH-'.uniqid(),
            'shift_id' => $shift->id,
            'branch_id' => $otherBranch->id,
            'cashier_id' => $this->cashierUser->id,
            'order_type' => 'DINE_IN',
            'customer_name' => 'Pelanggan Cabang Lain',
            'subtotal' => 60000,
            'tax_amount' => 6600,
            'discount_amount' => 0,
            'total_amount' => 66600,
            'payment_method' => 'CASH',
            'payment_status' => 'PAID',
        ]);

        $response = $this->actingAs($this->cashierUser)->get('/pos/orders');
        $response->assertStatus(200);
        $response->assertSee($myOrder->order_number);
        $response->assertSee('Pelanggan Cabang Sendiri');
        $response->assertDontSee($otherOrder->order_number);
        $response->assertDontSee('Pelanggan Cabang Lain');

        // Cashier can view detail of order from their branch
        $detailMyBranch = $this->actingAs($this->cashierUser)->getJson("/pos/orders/{$myOrder->id}");
        $detailMyBranch->assertStatus(200)->assertJson(['success' => true]);

        // Cashier is forbidden (403) from viewing detail of order from another branch
        $detailOtherBranch = $this->actingAs($this->cashierUser)->getJson("/pos/orders/{$otherOrder->id}");
        $detailOtherBranch->assertStatus(403)->assertJson(['success' => false]);
    }

    public function test_guest_and_non_admin_cannot_hijack_super_admin_via_switch_user(): void
    {
        // Guest cannot use GET /switch-user/{id}
        $this->get(route('switch-user.get', $this->superAdminUser->id))
            ->assertRedirect(route('login'));

        // Guest cannot use POST /switch-account
        $this->post(route('switch-account'), ['user_id' => $this->superAdminUser->id])
            ->assertRedirect(route('login'));

        // Non-admin (cashier) cannot escalate via GET /switch-user/{id} or POST /switch-account
        $this->actingAs($this->cashierUser)
            ->get(route('switch-user.get', $this->superAdminUser->id))
            ->assertStatus(403);

        $this->actingAs($this->cashierUser)
            ->post(route('switch-account'), ['user_id' => $this->superAdminUser->id])
            ->assertStatus(403);

        // Super Admin can legitimately switch to another user and return back
        $this->actingAs($this->superAdminUser)
            ->get(route('switch-user.get', $this->cashierUser->id))
            ->assertRedirect();
    }

    public function test_roaster_cannot_create_product_directly_via_http(): void
    {
        $this->actingAs($this->roasterUser)
            ->post(route('products.store'), [
                'name' => 'Unauthorized Roaster Product',
                'sku' => 'PRD-ROAST-UNAUTH-'.uniqid(),
                'category_id' => 1,
                'unit' => 'cup',
                'selling_price' => 30000,
            ])
            ->assertStatus(403);
    }
}
