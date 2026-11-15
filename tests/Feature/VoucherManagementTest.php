<?php

namespace Tests\Feature;

use App\Models\RedeemCode;
use Tests\TestCase;

class VoucherManagementTest extends TestCase
{
    /**
     * Test Vouchers Index management page renders successfully with statistics.
     */
    public function test_vouchers_index_page_renders_successfully(): void
    {
        $response = $this->get('/vouchers');

        $response->assertStatus(200);
        $response->assertSee('Voucher Diskon');
        $response->assertSee('Buka Kasir POS');
        $response->assertSee('Terbitkan Voucher Baru');
        $response->assertSee('Total Voucher');
        $response->assertSee('KAFEINA10');
    }

    /**
     * Test creating a new voucher with quota limit.
     */
    public function test_can_store_new_voucher_with_quota(): void
    {
        $code = 'TESTPROMO'.uniqid();

        $response = $this->post('/vouchers', [
            'code' => $code,
            'description' => 'Diskon Eksklusif Akhir Pekan',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 20,
            'max_discount_amount' => 30000,
            'min_order_amount' => 50000,
            'max_redemptions' => 25,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/vouchers');
        $this->assertDatabaseHas('redeem_codes', [
            'code' => $code,
            'discount_value' => 20,
            'max_redemptions' => 25,
            'times_redeemed' => 0,
        ]);
    }

    /**
     * Test updating an existing voucher.
     */
    public function test_can_update_voucher_details(): void
    {
        $voucher = RedeemCode::create([
            'code' => 'UPDATEME'.uniqid(),
            'description' => 'Deskripsi Lama',
            'discount_type' => 'FIXED',
            'discount_value' => 15000,
            'min_order_amount' => 40000,
            'max_redemptions' => 10,
            'times_redeemed' => 0,
            'is_active' => true,
        ]);

        $response = $this->put("/vouchers/{$voucher->id}", [
            'code' => $voucher->code,
            'description' => 'Deskripsi Baru Terupdate',
            'discount_type' => 'FIXED',
            'discount_value' => 18000,
            'min_order_amount' => 45000,
            'max_redemptions' => 20,
            'is_active' => 1,
        ]);

        $response->assertRedirect('/vouchers');
        $this->assertDatabaseHas('redeem_codes', [
            'id' => $voucher->id,
            'description' => 'Deskripsi Baru Terupdate',
            'discount_value' => 18000,
            'max_redemptions' => 20,
        ]);
    }

    /**
     * Test quick quota adjustments (add, set, unlimited).
     */
    public function test_can_adjust_voucher_quota(): void
    {
        $voucher = RedeemCode::create([
            'code' => 'QUOTA'.uniqid(),
            'description' => 'Uji Penambahan Kuota',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 10,
            'min_order_amount' => 0,
            'max_redemptions' => 15,
            'times_redeemed' => 15,
            'is_active' => true,
        ]);

        // Add +25 quota
        $response = $this->post("/vouchers/{$voucher->id}/quota", [
            'action_type' => 'add',
            'quota_value' => 25,
        ]);

        $response->assertRedirect('/vouchers');
        $voucher->refresh();
        $this->assertEquals(40, $voucher->max_redemptions);

        // Set to unlimited
        $response = $this->post("/vouchers/{$voucher->id}/quota", [
            'action_type' => 'unlimited',
        ]);

        $voucher->refresh();
        $this->assertNull($voucher->max_redemptions);
    }

    /**
     * Test toggling voucher active/inactive status.
     */
    public function test_can_toggle_voucher_status(): void
    {
        $voucher = RedeemCode::create([
            'code' => 'TOGGLE'.uniqid(),
            'description' => 'Uji Toggle Status',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 5,
            'min_order_amount' => 0,
            'max_redemptions' => 50,
            'times_redeemed' => 0,
            'is_active' => true,
        ]);

        $response = $this->post("/vouchers/{$voucher->id}/toggle");
        $response->assertRedirect('/vouchers');

        $voucher->refresh();
        $this->assertFalse($voucher->is_active);

        // Toggle back to active
        $this->post("/vouchers/{$voucher->id}/toggle");
        $voucher->refresh();
        $this->assertTrue($voucher->is_active);
    }

    /**
     * Test deleting a voucher.
     */
    public function test_can_delete_voucher(): void
    {
        $voucher = RedeemCode::create([
            'code' => 'DELETEME'.uniqid(),
            'description' => 'Akan Dihapus',
            'discount_type' => 'FIXED',
            'discount_value' => 5000,
            'min_order_amount' => 0,
            'max_redemptions' => 10,
            'times_redeemed' => 0,
            'is_active' => true,
        ]);

        $response = $this->delete("/vouchers/{$voucher->id}");
        $response->assertRedirect('/vouchers');

        $this->assertDatabaseMissing('redeem_codes', [
            'id' => $voucher->id,
        ]);
    }
}
