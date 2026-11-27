<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\InventoryWarehouse;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\RedeemCode;
use App\Models\StockCard;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\FinancialReportService;
use Database\Seeders\KafeinaRealDataSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinanceSettingsAndOperationsHardeningTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $cashier;

    protected Branch $senopatiBranch;

    protected Branch $bragaBranch;

    protected InventoryWarehouse $whBarSeno;

    protected InventoryWarehouse $whBarBrg;

    protected InventoryWarehouse $whRoastHq;

    protected function setUp(): void
    {
        parent::setUp();

        if (Branch::where('code', 'OUT-SENO')->doesntExist()) {
            $this->seed(KafeinaRealDataSeeder::class);
        }

        $this->superAdmin = User::where('email', 'admin@kafeina.id')->firstOrFail();
        $this->cashier = User::where('email', 'cashier.senopati@kafeina.id')->firstOrFail();
        $this->senopatiBranch = Branch::where('code', 'OUT-SENO')->firstOrFail();
        $this->bragaBranch = Branch::where('code', 'OUT-BRG')->firstOrFail();
        $this->whBarSeno = InventoryWarehouse::where('code', 'WH-BAR-SENO')->firstOrFail();
        $this->whBarBrg = InventoryWarehouse::where('code', 'WH-BAR-BRG')->firstOrFail();
        $this->whRoastHq = InventoryWarehouse::where('code', 'WH-ROAST-HQ')->firstOrFail();
    }

    public function test_material_leakage_respects_branch_date_range_and_excludes_retail_bom_packing(): void
    {
        $milk = Product::where('sku', 'RAW-MILK-FRESH')->firstOrFail();

        // Catat waste khusus di Senopati hari ini (200 ml) dan di Braga (500 ml) + Kemas Retail BOM di HQ (999 ml)
        StockCard::create([
            'warehouse_id' => $this->whBarSeno->id,
            'product_id' => $milk->id,
            'reference_type' => 'ADJUSTMENT',
            'reference_id' => 9001,
            'quantity_in' => 0,
            'quantity_out' => 200,
            'balance_after' => 1000,
            'cost_per_unit' => 25,
            'notes' => 'Tumpah bar Senopati uji audit',
            'created_at' => now(),
        ]);

        StockCard::create([
            'warehouse_id' => $this->whBarBrg->id,
            'product_id' => $milk->id,
            'reference_type' => 'ADJUSTMENT',
            'reference_id' => 9002,
            'quantity_in' => 0,
            'quantity_out' => 500,
            'balance_after' => 1000,
            'cost_per_unit' => 25,
            'notes' => 'Tumpah bar Braga uji audit',
            'created_at' => now(),
        ]);

        StockCard::create([
            'warehouse_id' => $this->whRoastHq->id,
            'product_id' => $milk->id,
            'reference_type' => 'ADJUSTMENT',
            'reference_id' => 9003,
            'quantity_in' => 0,
            'quantity_out' => 999,
            'balance_after' => 1000,
            'cost_per_unit' => 25,
            'notes' => 'Kemas Retail BOM uji eksklusi',
            'created_at' => now(),
        ]);

        $service = app(FinancialReportService::class);
        $today = now()->toDateString();

        $senoReport = $service->analyzeMaterialLeakage($this->senopatiBranch->id, null, $today, $today);
        $bragaReport = $service->analyzeMaterialLeakage($this->bragaBranch->id, null, $today, $today);
        $pastReport = $service->analyzeMaterialLeakage($this->senopatiBranch->id, null, '2020-01-01', '2020-01-02');

        $senoMilk = collect($senoReport['items'])->firstWhere('sku', 'RAW-MILK-FRESH');
        $bragaMilk = collect($bragaReport['items'])->firstWhere('sku', 'RAW-MILK-FRESH');
        $pastMilk = collect($pastReport['items'])->firstWhere('sku', 'RAW-MILK-FRESH');

        $this->assertNotNull($senoMilk);
        $this->assertNotNull($bragaMilk);
        $this->assertNotNull($pastMilk);

        // Braga dan Senopati harus terisolasi, dan periode lama (2020) harus 0
        $this->assertNotEquals($senoMilk['actual_stock_outflow'], $bragaMilk['actual_stock_outflow']);
        $this->assertEquals(0.0, (float) $pastMilk['actual_stock_outflow']);

        // Pastikan RAW-GB tidak muncul pada kebocoran bar
        $this->assertNull(collect($senoReport['items'])->firstWhere('sku', 'RAW-GB-GAYO'));
    }

    public function test_finance_pdf_uses_dynamic_company_profile_from_system_settings(): void
    {
        SystemSetting::set('company_name', 'PT Kafeina Audit Nusantara', 'company');
        SystemSetting::set('company_phone', '021-88887777', 'company');

        $res = $this->actingAs($this->superAdmin)->get(route('finance.export.pdf'));
        $res->assertOk();
        $res->assertSee('PT KAFEINA AUDIT NUSANTARA');
        $res->assertSee('021-88887777');
    }

    public function test_settings_update_branch_and_user_preserve_active_status_and_validate_yield_bounds(): void
    {
        // 1. Update cabang tanpa field is_active tidak boleh menonaktifkan cabang!
        $branch = $this->senopatiBranch;
        $this->assertTrue($branch->is_active);

        $branchRes = $this->actingAs($this->superAdmin)->put(route('settings.branch.update', $branch->id), [
            'name' => $branch->name,
            'type' => $branch->type,
            'address' => 'Jl. Senopati Raya Baru No. 99',
            'phone' => '021-99990000',
        ]);
        $branchRes->assertRedirect(route('settings.index', ['tab' => 'branches']));
        $this->assertTrue($branch->fresh()->is_active);

        // 2. Update user tanpa field is_active tidak boleh menonaktifkan user!
        $userRes = $this->actingAs($this->superAdmin)->put(route('settings.user.update', $this->cashier->id), [
            'name' => $this->cashier->name,
            'email' => $this->cashier->email,
            'role_id' => $this->cashier->role_id,
            'branch_id' => $this->cashier->branch_id,
        ]);
        $userRes->assertRedirect(route('settings.index', ['tab' => 'users']));
        $this->assertTrue($this->cashier->fresh()->is_active);

        // 3. Validasi yield loss max harus > min
        $badYield = $this->actingAs($this->superAdmin)->post(route('settings.payment.update'), [
            'tax_pb1_rate' => 11.0,
            'service_charge_rate' => 0.0,
            'roastery_min_yield_loss' => 20.0,
            'roastery_max_yield_loss' => 15.0,
            'inventory_default_safety_stock' => 100.0,
        ]);
        $badYield->assertSessionHasErrors('roastery_max_yield_loss');

        // 4. Halaman /settings memuat modal edit gudang dan edit staf + tidak membocorkan server key mentah
        SystemSetting::set('midtrans_server_key', 'SB-Mid-server-SUPERSECRET9999', 'payment');
        $settingsPage = $this->actingAs($this->superAdmin)->get(route('settings.index', ['tab' => 'payment']));
        $settingsPage->assertOk();
        $settingsPage->assertSee('editWarehouseModalOpen');
        $settingsPage->assertSee('editUserModalOpen');
        $settingsPage->assertDontSee('SB-Mid-server-SUPERSECRET9999');
    }

    public function test_pos_checkout_uses_dynamic_tax_and_service_charge_and_shift_persists_to_db(): void
    {
        // 1. Ubah tarif PB1 menjadi 10% dan Service Charge 5% via SystemSetting
        SystemSetting::set('tax_pb1_rate', 10.0, 'tax', 'decimal');
        SystemSetting::set('service_charge_rate', 5.0, 'tax', 'decimal');

        $product = Product::where('sku', 'PRD-ESP-01')->firstOrFail();

        $checkoutRes = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), [
            'order_type' => 'Dine In',
            'table_number' => '05',
            'customer_name' => 'Audit Pajak Dinamis',
            'payment_method' => 'cash',
            'paid_amount' => 100000,
            'items' => [
                [
                    'id' => $product->sku,
                    'name' => $product->name,
                    'price' => 40000,
                    'qty' => 1,
                ],
            ],
        ]);

        $checkoutRes->assertOk()->assertJson(['success' => true]);
        // Subtotal 40.000, Service Charge 5% = 2.000, PB1 10% dari 42.000 = 4.200, Total = 46.200
        $this->assertEquals(4200.0, (float) $checkoutRes->json('order.tax_pb1'));
        $this->assertEquals(46200.0, (float) $checkoutRes->json('order.total'));

        // 2. Open Shift harus tersimpan ke tabel pos_shifts
        $openRes = $this->actingAs($this->cashier)->postJson(route('pos.shift.open'), [
            'opening_cash' => 750000,
            'branch_code' => 'OUT-SENO',
        ]);
        $openRes->assertOk()->assertJson(['success' => true]);
        $shiftCode = (string) $openRes->json('shift.shift_id');

        $shiftRecord = PosShift::where('shift_code', $shiftCode)->first();
        $this->assertNotNull($shiftRecord);
        $this->assertEquals('OPEN', $shiftRecord->status);
        $this->assertEquals(750000.0, (float) $shiftRecord->opening_cash);

        // 3. Close Shift dengan selisih > Rp 10.000 menolak PIN palsu dan menerima PIN Supervisor asli dari DB (889900)
        $badPinRes = $this->actingAs($this->cashier)->postJson(route('pos.shift.close'), [
            'branch_code' => 'OUT-SENO',
            'total_physical_cash' => 700000,
            'system_cash_expected' => 750000,
            'supervisor_pin' => '000000',
        ]);
        $badPinRes->assertStatus(422);

        $goodCloseRes = $this->actingAs($this->cashier)->postJson(route('pos.shift.close'), [
            'branch_code' => 'OUT-SENO',
            'total_physical_cash' => 700000,
            'system_cash_expected' => 750000,
            'supervisor_pin' => '889900',
            'notes' => 'Tutup kasir audit',
        ]);
        $goodCloseRes->assertOk()->assertJson(['success' => true]);

        $shiftRecord->refresh();
        $this->assertEquals('CLOSED', $shiftRecord->status);
        $this->assertEquals(700000.0, (float) $shiftRecord->closing_cash_actual);
        $this->assertEquals(-50000.0, (float) $shiftRecord->cash_difference);
        $this->assertNotNull($shiftRecord->closed_at);
    }

    public function test_voucher_quota_validation_rejects_empty_value_for_add_and_set(): void
    {
        $voucher = RedeemCode::create([
            'code' => 'AUDITVCH'.rand(100, 999),
            'description' => 'Uji validasi kuota',
            'discount_type' => 'FIXED',
            'discount_value' => 5000,
            'min_order_amount' => 0,
            'max_redemptions' => 10,
            'times_redeemed' => 0,
            'is_active' => true,
        ]);

        $res = $this->actingAs($this->superAdmin)->post(route('vouchers.quota', $voucher->id), [
            'action_type' => 'set',
            'quota_value' => null,
        ]);
        $res->assertSessionHasErrors('quota_value');
        $this->assertEquals(10, $voucher->fresh()->max_redemptions);
    }
}
