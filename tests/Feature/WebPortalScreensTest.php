<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebPortalScreensTest extends TestCase
{
    /**
     * Test all 6 core web ERP portals render successfully with HTTP 200.
     */
    public function test_all_six_web_portals_render_successfully(): void
    {
        // 1. Dashboard
        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Roastery Analytics');
        $dashboardResponse->assertSee('KAFEINA');

        // 2. POS Terminal
        $posResponse = $this->get('/pos');
        $posResponse->assertStatus(200);
        $posResponse->assertSee('Terminal POS');

        // 3. Procurement & QC Portal
        $procurementResponse = $this->get('/procurement');
        $procurementResponse->assertStatus(200);
        $procurementResponse->assertSee('Pengadaan');

        // 4. Roastery Manufacturing Portal
        $roasteryResponse = $this->get('/roastery');
        $roasteryResponse->assertStatus(200);
        $roasteryResponse->assertSee('Roastery');

        // 5. Inventory & Multi-Branch Logistics Portal
        $inventoryResponse = $this->get('/inventory');
        $inventoryResponse->assertStatus(200);
        $inventoryResponse->assertSee('Inventori');

        // 6. Financial Reports & P&L Portal
        $financeResponse = $this->get('/finance');
        $financeResponse->assertStatus(200);
        $financeResponse->assertSee('Laporan Keuangan');

        // 7. QC Lab Portal (Separated)
        $qcResponse = $this->get('/qc-lab');
        $qcResponse->assertStatus(200);
        $qcResponse->assertSee('QC Lab');

        // 8. Yield Loss Monitor Portal (Separated)
        $yieldResponse = $this->get('/yield-loss');
        $yieldResponse->assertStatus(200);
        $yieldResponse->assertSee('Yield Loss');

        // 9. Logistics & Inter-Branch Distribution Portal (Separated)
        $logisticsResponse = $this->get('/logistics');
        $logisticsResponse->assertStatus(200);
        $logisticsResponse->assertSee('Logistik');
    }

    /**
     * Test Financial CSV export endpoint.
     */
    public function test_finance_csv_export_endpoint(): void
    {
        $response = $this->get('/finance/export-csv');

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type') ?? '', 'text/csv') ||
            str_contains($response->headers->get('content-disposition') ?? '', '.csv')
        );
    }

    /**
     * Test Financial PDF / printable report view.
     */
    public function test_finance_pdf_report_view_renders(): void
    {
        $response = $this->get('/finance/export-pdf');

        $response->assertStatus(200);
        $response->assertSee('KAFEINA');
        $response->assertSee('LAPORAN LABA RUGI');
    }
}
