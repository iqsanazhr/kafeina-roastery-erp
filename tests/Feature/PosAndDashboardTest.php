<?php

namespace Tests\Feature;

use Tests\TestCase;

class PosAndDashboardTest extends TestCase
{
    /**
     * Test Web POS Touchscreen Terminal page renders successfully.
     */
    public function test_pos_terminal_screen_renders_successfully(): void
    {
        $response = $this->get('/pos');

        $response->assertStatus(200);
        $response->assertSee('KAFEINA');
        $response->assertSee('Kasir POS');
        $response->assertSee('Espresso Based');
        $response->assertSee('Manual Brew');
        $response->assertSee('Pajak Restoran');
        $response->assertSee('Split Bill');
        $response->assertSee('Non-Revenue');
        $response->assertSee('Kafeina Flagship Senopati');
        $response->assertSee('Rizky Ramadhan');
    }

    /**
     * Test Web POS enforces retail branch scoping (defaults to Senopati, switches to Braga/Malioboro).
     */
    public function test_pos_terminal_switches_retail_branches_and_cashiers(): void
    {
        // 1. When switching to Braga
        $responseBraga = $this->get('/pos?branch=braga');
        $responseBraga->assertStatus(200);
        $responseBraga->assertSee('Kafeina Specialty Braga Bandung');
        $responseBraga->assertSee('Dinda Kirana');

        // 2. When switching to Malioboro
        $responseMalio = $this->get('/pos?branch=malioboro');
        $responseMalio->assertStatus(200);
        $responseMalio->assertSee('Kafeina Heritage Malioboro Jogja');
        $responseMalio->assertSee('Bagus Wicaksono');

        // 3. When switching to Central Roastery & Lab
        $responseCentral = $this->get('/pos?branch=central');
        $responseCentral->assertStatus(200);
        $responseCentral->assertSee('Kafeina Central Roastery & Lab Hub');
        $responseCentral->assertSee('Fajar Nugraha');

        // 4. When passing 'all', POS safely defaults to Senopati Flagship
        $responseAll = $this->get('/pos?branch=all');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Kafeina Flagship Senopati');
    }

    /**
     * Test Executive Dashboard & Roastery Analytics renders with Bento Grid & Roasting Yield Monitor.
     */
    public function test_executive_dashboard_renders_successfully(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Roastery Analytics');
        $response->assertSee('Roasting Yield Monitor');
        $response->assertSee('Safety Stock Alerts');
        $response->assertSee('38.450.000');
        $response->assertSee('14–18%');
    }

    /**
     * Test Procurement & QC Lab portal renders successfully.
     */
    public function test_procurement_portal_renders_successfully(): void
    {
        $response = $this->get('/procurement');

        $response->assertStatus(200);
        $response->assertSee('Pengadaan Biji Mentah');
        $response->assertSee('Mitra Petani Kopi');
        $response->assertSee('Purchase Order');
        $response->assertSee('QC Lab');
    }

    /**
     * Test Roastery Manufacturing & Yield Loss portal renders successfully.
     */
    public function test_roastery_portal_renders_successfully(): void
    {
        $response = $this->get('/roastery');

        $response->assertStatus(200);
        $response->assertSee('Roastery Manufacturing');
        $response->assertSee('Stasiun Eksekusi Sangrai');
        $response->assertSee('Daftar Work Orders');
        $response->assertSee('Profil Sangrai');
    }

    /**
     * Test Multi-Branch Inventory & Logistics portal renders successfully.
     */
    public function test_inventory_portal_renders_successfully(): void
    {
        $response = $this->get('/inventory');

        $response->assertStatus(200);
        $response->assertSee('Inventori Multi-Cabang');
        $response->assertSee('Material Requests');
        $response->assertSee('Surat Jalan');
    }

    /**
     * Test Finance & P&L portal renders successfully.
     */
    public function test_finance_portal_renders_successfully(): void
    {
        $response = $this->get('/finance');

        $response->assertStatus(200);
        $response->assertSee('Laporan Keuangan');
        $response->assertSee('Laporan Laba Rugi Komprehensif');
        $response->assertSee('Analisis Kebocoran Bahan Baku');
    }

    /**
     * Test POS checkout API calculates PB1 10% correctly.
     */
    public function test_pos_checkout_calculates_pb1_tax_accurately(): void
    {
        $payload = [
            'items' => [
                ['id' => 'PRD-ESP-01', 'name' => 'Espresso Single Origin', 'price' => 28000, 'qty' => 2], // 56.000
                ['id' => 'PRD-MB-01', 'name' => 'V60 Gayo Honey', 'price' => 45000, 'qty' => 1], // 45.000
            ],
            'payment_method' => 'cash',
            'paid_amount' => 120000,
            'order_type' => 'Dine In',
            'table_number' => '07',
            'discount_amount' => 0,
        ];

        $response = $this->postJson('/pos/checkout', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'subtotal' => 101000,
                'tax_pb1' => 11110, // 11% of 101.000
                'total' => 112110,
                'change_amount' => 7890, // 120.000 - 112.110
                'drawer_pulse_sent' => true,
            ],
        ]);
    }

    /**
     * Test Non-Revenue transaction checkout produces 0 IDR total.
     */
    public function test_non_revenue_transaction_produces_zero_total(): void
    {
        $payload = [
            'items' => [
                ['id' => 'PRD-ESP-01', 'name' => 'Espresso Calibration Shot', 'price' => 28000, 'qty' => 3],
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
     * Test Shift Closing within tolerance (<= 10.000) approves without supervisor PIN.
     */
    public function test_shift_closing_within_tolerance_is_approved(): void
    {
        $payload = [
            'system_cash_expected' => 4350000,
            'total_physical_cash' => 4355000, // discrepancy +5.000 (within 10.000 tolerance)
        ];

        $response = $this->postJson('/pos/shift/close', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'reconciliation' => [
                'status' => 'OVERAGE',
                'discrepancy' => 5000,
                'supervisor_authorized' => false,
            ],
        ]);
    }

    /**
     * Test Shift Closing exceeding tolerance (> 10.000) rejects without valid supervisor PIN.
     */
    public function test_shift_closing_exceeding_tolerance_requires_supervisor_pin(): void
    {
        $payload = [
            'system_cash_expected' => 4350000,
            'total_physical_cash' => 4300000, // discrepancy -50.000 (exceeds 10.000)
            'supervisor_pin' => '000000', // invalid PIN
        ];

        $response = $this->postJson('/pos/shift/close', $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'SUPERVISOR_PIN_REQUIRED',
        ]);
    }

    /**
     * Test Shift Closing exceeding tolerance with valid supervisor PIN succeeds.
     */
    public function test_shift_closing_with_valid_supervisor_pin_succeeds(): void
    {
        $payload = [
            'system_cash_expected' => 4350000,
            'total_physical_cash' => 4300000, // discrepancy -50.000
            'supervisor_pin' => '889900', // Valid supervisor PIN
        ];

        $response = $this->postJson('/pos/shift/close', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'reconciliation' => [
                'status' => 'SHORTAGE',
                'discrepancy' => -50000,
                'supervisor_authorized' => true,
            ],
        ]);
    }

    /**
     * Test checkout requires customer name and table number before payment.
     */
    public function test_checkout_requires_customer_name_and_table_number(): void
    {
        // 1. Missing customer_name should return 422
        $resNoName = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => 'PRD-ESP-01', 'name' => 'Espresso', 'price' => 28000, 'qty' => 1],
            ],
            'customer_name' => '',
            'table_number' => '05',
            'order_type' => 'Dine In',
            'payment_method' => 'cash',
        ]);
        $resNoName->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Atas nama pelanggan wajib diisi sebelum pembayaran.',
            ]);

        // 2. Dine In missing table_number should return 422
        $resNoTable = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => 'PRD-ESP-01', 'name' => 'Espresso', 'price' => 28000, 'qty' => 1],
            ],
            'customer_name' => 'Aryanto',
            'table_number' => '',
            'order_type' => 'Dine In',
            'payment_method' => 'cash',
        ]);
        $resNoTable->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Nomor meja wajib diisi untuk pesanan Dine In.',
            ]);

        // 3. Valid Dine In order succeeds
        $resValid = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => 'PRD-ESP-01', 'name' => 'Espresso', 'price' => 28000, 'qty' => 1],
            ],
            'customer_name' => 'Aryanto',
            'table_number' => '05',
            'order_type' => 'Dine In',
            'payment_method' => 'cash',
            'paid_amount' => 50000,
        ]);
        $resValid->assertStatus(200)
            ->assertJson([
                'success' => true,
                'order' => [
                    'customer_name' => 'Aryanto',
                    'table_number' => '05',
                ],
            ]);
    }

    /**
     * Test checkout with Debit Card saves payment method and EDC reference.
     */
    public function test_checkout_with_debit_card_saves_payment_method_and_reference(): void
    {
        $response = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => 'PRD-ESP-01', 'name' => 'Single Origin Espresso', 'price' => 28000, 'qty' => 1],
            ],
            'customer_name' => 'Rian Pratama',
            'table_number' => '12',
            'order_type' => 'Dine In',
            'payment_method' => 'debit',
            'debit_bank' => 'BCA',
            'debit_last4' => '4821',
            'debit_approval_code' => '918234',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'payment_method' => 'debit',
                'customer_name' => 'Rian Pratama',
            ],
        ]);

        $this->assertDatabaseHas('pos_orders', [
            'customer_name' => 'Rian Pratama',
            'payment_method' => 'DEBIT_CARD',
            'payment_reference' => 'BCA EDC • ****4821 • APP-918234',
        ]);
    }

    /**
     * Test checkout with Bank Transfer / Virtual Account saves payment method and reference.
     */
    public function test_checkout_with_bank_transfer_va_saves_payment_method_and_reference(): void
    {
        $response = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => 'PRD-V60-01', 'name' => 'V60 Pour Over', 'price' => 35000, 'qty' => 1],
            ],
            'customer_name' => 'Siti Nurhaliza',
            'table_number' => '08',
            'order_type' => 'Dine In',
            'payment_method' => 'transfer',
            'va_bank' => 'mandiri',
            'va_number' => '88012081299281',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'payment_method' => 'transfer',
                'customer_name' => 'Siti Nurhaliza',
            ],
        ]);

        $this->assertDatabaseHas('pos_orders', [
            'customer_name' => 'Siti Nurhaliza',
            'payment_method' => 'BANK_TRANSFER',
            'payment_reference' => 'mandiri VA • 88012081299281',
        ]);
    }

    /**
     * Test Midtrans Virtual Account generate, status polling, and simulation endpoints.
     */
    public function test_midtrans_va_generate_status_and_simulation(): void
    {
        $orderNum = 'KAF-VA-'.time();

        // 1. Generate Dynamic VA for BCA
        $generateRes = $this->postJson('/pos/va/generate', [
            'order_number' => $orderNum,
            'amount' => 62160,
            'bank' => 'bca',
            'customer_name' => 'Budi Sudarsono',
        ]);

        $generateRes->assertStatus(200);
        $generateRes->assertJson([
            'success' => true,
            'order_id' => $orderNum,
            'bank' => 'BCA',
        ]);
        $this->assertNotEmpty($generateRes->json('va_number'));

        // 2. Poll status before payment
        $statusRes = $this->getJson("/pos/va/status/{$orderNum}");
        $statusRes->assertStatus(200);
        $this->assertFalse($statusRes->json('paid'));

        // 3. Simulate Payment Settlement
        $simulateRes = $this->postJson('/pos/va/simulate-pay', [
            'order_number' => $orderNum,
        ]);
        $simulateRes->assertStatus(200);
        $simulateRes->assertJson([
            'success' => true,
            'status' => 'settlement',
            'paid' => true,
        ]);

        // 4. Poll status after settlement
        $statusSettledRes = $this->getJson("/pos/va/status/{$orderNum}");
        $statusSettledRes->assertStatus(200);
        $this->assertTrue($statusSettledRes->json('paid'));
    }

    /**
     * Test application timezone is configured to Asia/Jakarta (WIB).
     */
    public function test_application_timezone_is_wib(): void
    {
        $this->assertEquals('Asia/Jakarta', config('app.timezone'));
        $now = now();
        $this->assertEquals('+07:00', $now->format('P'));
    }
}
