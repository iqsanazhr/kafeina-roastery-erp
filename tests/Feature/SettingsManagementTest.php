<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that Super Admin can access settings dashboard and tabs.
     */
    public function test_super_admin_can_access_settings_dashboard_and_tabs(): void
    {
        $superAdmin = User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->first();
        $this->assertNotNull($superAdmin, 'Akun super admin harus ada.');

        // Test default tab (branches)
        $response = $this->actingAs($superAdmin)
            ->withSession(['active_user_id' => $superAdmin->id])
            ->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Sistem & Master Data');
        $response->assertSee('Daftar Cabang Resmi');

        // Test other tabs
        $tabs = ['warehouses', 'users', 'payment', 'database'];
        foreach ($tabs as $tab) {
            $tabResponse = $this->actingAs($superAdmin)
                ->withSession(['active_user_id' => $superAdmin->id])
                ->get(route('settings.index', ['tab' => $tab]));

            $tabResponse->assertStatus(200);
        }
    }

    /**
     * Test that non-super admin users are forbidden (403).
     */
    public function test_non_super_admin_is_forbidden_from_settings(): void
    {
        $cashier = User::whereHas('role', fn ($q) => $q->whereIn('name', ['branch_cashier', 'cashier']))->first();
        if (! $cashier) {
            $this->markTestSkipped('Akun kasir tidak ditemukan.');
        }

        $response = $this->actingAs($cashier)
            ->withSession(['active_user_id' => $cashier->id])
            ->get(route('settings.index'));

        $response->assertStatus(403);
    }

    /**
     * Test Super Admin can create and update a branch.
     */
    public function test_super_admin_can_create_and_update_branch(): void
    {
        $superAdmin = User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->first();

        $testCode = 'OUT-TEST-'.rand(100, 999);
        $storeResponse = $this->actingAs($superAdmin)
            ->withSession(['active_user_id' => $superAdmin->id])
            ->post(route('settings.branch.store'), [
                'code' => $testCode,
                'name' => 'Kafeina Uji Coba Surabaya',
                'type' => 'RETAIL_BRANCH',
                'address' => 'Jl. Pemuda No. 12, Surabaya',
                'phone' => '031-123456',
            ]);

        $storeResponse->assertRedirect(route('settings.index', ['tab' => 'branches']));
        $this->assertDatabaseHas('branches', ['code' => $testCode]);

        $branch = Branch::where('code', $testCode)->first();
        $this->assertNotNull($branch);

        // Toggle active status
        $toggleResponse = $this->actingAs($superAdmin)
            ->withSession(['active_user_id' => $superAdmin->id])
            ->post(route('settings.branch.toggle', $branch->id));

        $toggleResponse->assertRedirect(route('settings.index', ['tab' => 'branches']));
        $branch->refresh();
        $this->assertFalse($branch->is_active);

        // Clean up test branch
        $branch->delete();
    }

    /**
     * Test Super Admin can update payment & tax settings.
     */
    public function test_super_admin_can_update_payment_and_tax_settings(): void
    {
        $superAdmin = User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->first();

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_user_id' => $superAdmin->id])
            ->post(route('settings.payment.update'), [
                'midtrans_server_key' => 'SB-Mid-server-TEST-KEY-123',
                'midtrans_client_key' => 'Mid-client-TEST-KEY-456',
                'midtrans_is_production' => '0',
                'tax_pb1_rate' => 10.0,
                'service_charge_rate' => 0.0,
                'roastery_min_yield_loss' => 12.0,
                'roastery_max_yield_loss' => 18.0,
                'inventory_default_safety_stock' => 100.0,
            ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'payment']));

        $this->assertEquals(10.0, SystemSetting::get('tax_pb1_rate'));
        $this->assertEquals('SB-Mid-server-TEST-KEY-123', SystemSetting::get('midtrans_server_key'));
    }

    /**
     * Test Super Admin can download database backup dump.
     */
    public function test_super_admin_can_download_database_backup(): void
    {
        $superAdmin = User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->first();

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_user_id' => $superAdmin->id])
            ->get(route('settings.database.backup'));

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('application/sql', (string) $response->headers->get('content-type'));
    }

    /**
     * Test Super Admin can export CSV of branches and warehouses.
     */
    public function test_super_admin_can_export_csv(): void
    {
        $superAdmin = User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->first();

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_user_id' => $superAdmin->id])
            ->get(route('settings.database.export', 'branches'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }
}
