<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $financialService
    ) {}

    /**
     * Display financial P&L and Material Leakage report.
     */
    public function index(Request $request)
    {
        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $branchId = $request->query('branch_id');

        $pnl = $this->financialService->calculateProfitAndLoss($startDate, $endDate, $branchId ? (int) $branchId : null);
        $leakage = $this->financialService->analyzeMaterialLeakage($branchId ? (int) $branchId : null);
        $branches = Branch::where('is_active', true)->get();

        return view('finance.index', compact('pnl', 'leakage', 'branches', 'startDate', 'endDate', 'branchId'));
    }

    /**
     * Export P&L and Material Leakage Report to CSV / Excel spreadsheet.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $branchId = $request->query('branch_id');

        $pnl = $this->financialService->calculateProfitAndLoss($startDate, $endDate, $branchId ? (int) $branchId : null);
        $leakage = $this->financialService->analyzeMaterialLeakage($branchId ? (int) $branchId : null);

        $filename = 'kafeina_laporan_keuangan_pnl_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($pnl, $leakage) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel compatibility

            // 1. Title Block
            fputcsv($handle, ['KAFEINA ROASTERY ERP - LAPORAN LABA RUGI RIIL & AUDIT KEBOCORAN']);
            fputcsv($handle, ['Periode', $pnl['period']['start_date'].' s/d '.$pnl['period']['end_date']]);
            fputcsv($handle, ['Tanggal Unduh', now()->format('Y-m-d H:i:s').' WIB']);
            fputcsv($handle, []);

            // 2. Financial Metrics P&L
            fputcsv($handle, ['RINGKASAN METRIK KEUANGAN RIIL']);
            fputcsv($handle, ['Metrik', 'Nilai (IDR)', 'Keterangan']);
            fputcsv($handle, ['Total Omzet Kotor (Gross Sales)', $pnl['metrics']['gross_sales'], 'Sebelum diskon & pajak']);
            fputcsv($handle, ['Total Potongan & Diskon', $pnl['metrics']['discounts'], 'Voucher promo']);
            fputcsv($handle, ['Pendapatan Bersih (Net Sales)', $pnl['metrics']['net_sales'], 'Omzet kotor dikurangi diskon']);
            fputcsv($handle, ['Pajak Restoran PB1 (11%)', $pnl['metrics']['tax_pb1'], 'Kewajiban fiskal pemda']);
            fputcsv($handle, ['Total Kas Terkumpul (Net + PB1)', $pnl['metrics']['total_revenue_collected'], 'Total kas masuk']);
            fputcsv($handle, ['HPP Riil Sangrai & Resep (COGS)', $pnl['metrics']['real_cogs'], 'Memperhitungkan susut sangrai riil & auto-deduct']);
            fputcsv($handle, ['Laba Kotor (Gross Profit)', $pnl['metrics']['gross_profit'], 'Net Sales dikurangi HPP Riil']);
            fputcsv($handle, ['Gross Profit Margin (%)', $pnl['metrics']['gross_margin_percentage'].'%', 'Margin laba kotor operasional']);
            fputcsv($handle, ['Beban Non-Revenue (Waste/Tester/Barista)', $pnl['metrics']['non_revenue_expense'], 'Beban kalibrasi & jatah staf']);
            fputcsv($handle, ['Laba Operasional Bersih', $pnl['metrics']['net_operating_profit'], 'Gross Profit dikurangi Beban Non-Revenue']);
            fputcsv($handle, []);

            // 3. Transactions Breakdown
            fputcsv($handle, ['RINCIAN VOLUME TRANSAKSI']);
            fputcsv($handle, ['Total Transaksi', $pnl['transactions']['total']]);
            fputcsv($handle, ['Dine-In', $pnl['transactions']['dine_in']]);
            fputcsv($handle, ['Take-Away', $pnl['transactions']['take_away']]);
            fputcsv($handle, ['Non-Revenue', $pnl['transactions']['non_revenue']]);
            fputcsv($handle, []);

            // 4. Material Leakage Audit Table
            fputcsv($handle, ['ANALISIS KEBOCORAN BAHAN BAKU (STANDARD RECIPE VS ACTUAL STOCK USAGE)']);
            fputcsv($handle, [
                'SKU',
                'Nama Bahan Baku',
                'Satuan',
                'Harga Pokok per Unit (IDR)',
                'Kebutuhan Resep Ideal',
                'Pengeluaran Fisik Aktual',
                'Selisih (Discrepancy)',
                '% Kebocoran',
                'Estimasi Kerugian (IDR)',
                'Status Pengawasan',
            ]);

            foreach ($leakage['items'] as $item) {
                fputcsv($handle, [
                    $item['sku'],
                    $item['name'],
                    $item['unit'],
                    $item['cost_price'],
                    $item['ideal_recipe_usage'],
                    $item['actual_stock_outflow'],
                    $item['discrepancy'],
                    $item['leakage_percentage'].'%',
                    $item['estimated_loss'],
                    $item['status'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Total Estimasi Kerugian Kebocoran Bahan Baku', $leakage['total_estimated_loss']]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Preview printable PDF HTML Report.
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $branchId = $request->query('branch_id');

        $pnl = $this->financialService->calculateProfitAndLoss($startDate, $endDate, $branchId ? (int) $branchId : null);
        $leakage = $this->financialService->analyzeMaterialLeakage($branchId ? (int) $branchId : null);
        $branch = $branchId ? Branch::find($branchId) : null;

        return view('finance.pdf_report', compact('pnl', 'leakage', 'branch', 'startDate', 'endDate'));
    }
}
