<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan & Laba Rugi Riil - KAFEINA ERP</title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            color: #1a1a1a;
            line-height: 1.4;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #b45309;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .logo-title {
            font-size: 20pt;
            font-weight: 800;
            color: #78350f;
            letter-spacing: -0.5px;
        }
        .subtitle {
            font-size: 9pt;
            color: #555;
            margin-top: 2px;
        }
        .report-meta {
            text-align: right;
            font-size: 9pt;
            color: #444;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #78350f;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin-top: 24px;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 16px;
        }
        th {
            background-color: #f5f5f5;
            color: #333;
            text-align: left;
            padding: 6px 8px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        .total-row {
            background-color: #fef3c7;
            font-weight: bold;
            border-top: 2px solid #d97706;
            border-bottom: 2px solid #d97706;
        }
        .net-profit-row {
            background-color: #d1fae5;
            font-weight: bold;
            border-top: 2px solid #059669;
            border-bottom: 2px solid #059669;
        }
        .sign-off-block {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .sign-box {
            width: 40%;
            text-align: center;
        }
        .sign-line {
            margin-top: 60px;
            border-bottom: 1px solid #333;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="background: #fef3c7; padding: 10px; margin-bottom: 20px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 10pt; color: #92400e;">Pratinjau Cetak Laporan PDF Resmi KAFEINA ERP</span>
        <button onclick="window.print()" style="padding: 6px 14px; background: #b45309; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
            Cetak Sekarang (Print)
        </button>
    </div>

    <!-- Header -->
    <div class="header">
        <div>
            <div class="logo-title">{{ strtoupper($company['name'] ?? 'KAFEINA ROASTERY ERP') }}</div>
            <div class="subtitle">Sistem Terintegrasi Roastery Kopi Spesialti & Manajemen Multi-Cabang</div>
            <div class="subtitle">Alamat: {{ $company['address'] ?? '-' }} | Telp: {{ $company['phone'] ?? '-' }}</div>
            @if(!empty($company['npwp']))
                <div class="subtitle">NPWP: {{ $company['npwp'] }} | Email: {{ $company['email'] ?? '-' }}</div>
            @endif
        </div>
        <div class="report-meta">
            <div><strong>LAPORAN LABA RUGI RIIL</strong></div>
            <div>Periode: {{ $startDate }} s/d {{ $endDate }}</div>
            <div>Cabang: {{ $branch ? $branch->name : 'Seluruh Cabang (Konsolidasi)' }}</div>
            <div>Dicetak: {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
        </div>
    </div>

    <!-- Section 1: Income Statement -->
    <div class="section-title">I. LAPORAN LABA RUGI KOMPREHENSIF (REAL P&L)</div>
    <table>
        <thead>
            <tr>
                <th>Komponen Pendapatan & Beban</th>
                <th class="text-right">Nominal (IDR)</th>
                <th class="text-right">% Net Sales</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Omzet Penjualan Kotor (Gross Sales)</td>
                <td class="text-right font-mono">Rp {{ number_format($pnl['metrics']['gross_sales'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">-</td>
            </tr>
            <tr>
                <td style="color: #b91c1c;">( - ) Potongan Diskon Penjualan & Promo</td>
                <td class="text-right font-mono" style="color: #b91c1c;">- Rp {{ number_format($pnl['metrics']['discounts'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">{{ $pnl['metrics']['net_sales'] > 0 ? round(($pnl['metrics']['discounts'] / $pnl['metrics']['net_sales']) * 100, 2) : 0 }}%</td>
            </tr>
            <tr class="total-row">
                <td>PENDAPATAN BERSIH (NET SALES)</td>
                <td class="text-right font-mono">Rp {{ number_format($pnl['metrics']['net_sales'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">100.00%</td>
            </tr>
            <tr>
                <td style="color: #b91c1c;">( - ) Beban Pokok Penjualan Riil (Real COGS Sangrai & Resep)</td>
                <td class="text-right font-mono" style="color: #b91c1c;">- Rp {{ number_format($pnl['metrics']['real_cogs'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">{{ $pnl['metrics']['net_sales'] > 0 ? round(($pnl['metrics']['real_cogs'] / $pnl['metrics']['net_sales']) * 100, 2) : 0 }}%</td>
            </tr>
            <tr class="total-row">
                <td>LABA KOTOR OPERASIONAL (GROSS PROFIT)</td>
                <td class="text-right font-mono">Rp {{ number_format($pnl['metrics']['gross_profit'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">{{ $pnl['metrics']['gross_margin_percentage'] }}%</td>
            </tr>
            <tr>
                <td>( - ) Beban Non-Revenue (Kalibrasi Grinder, Tester, Staf)</td>
                <td class="text-right font-mono">- Rp {{ number_format($pnl['metrics']['non_revenue_expense'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">{{ $pnl['metrics']['net_sales'] > 0 ? round(($pnl['metrics']['non_revenue_expense'] / $pnl['metrics']['net_sales']) * 100, 2) : 0 }}%</td>
            </tr>
            <tr class="net-profit-row">
                <td>LABA OPERASIONAL BERSIH (OPERATING INCOME)</td>
                <td class="text-right font-mono">Rp {{ number_format($pnl['metrics']['net_operating_profit'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">{{ $pnl['metrics']['net_sales'] > 0 ? round(($pnl['metrics']['net_operating_profit'] / $pnl['metrics']['net_sales']) * 100, 2) : 0 }}%</td>
            </tr>
            <tr>
                <td style="color: #4b5563;">Pajak Restoran PB1 {{ $pb1RateLabel ?? '11' }}% Terkumpul (Kewajiban Fiskal Pemda)</td>
                <td class="text-right font-mono" style="color: #4b5563;">Rp {{ number_format($pnl['metrics']['tax_pb1'], 2, ',', '.') }}</td>
                <td class="text-right font-mono">-</td>
            </tr>
        </tbody>
    </table>

    <!-- Section 2: Material Leakage -->
    <div class="section-title">II. AUDIT KEBOCORAN BAHAN BAKU (STANDARD RECIPE VS ACTUAL USAGE)</div>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Bahan Baku</th>
                <th>Unit</th>
                <th class="text-right">Resep Ideal</th>
                <th class="text-right">Aktual Fisik</th>
                <th class="text-right">Selisih</th>
                <th class="text-right">% Selisih</th>
                <th class="text-right">Estimasi Rugi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leakage['items'] as $item)
                <tr>
                    <td class="font-mono">{{ $item['sku'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['unit'] }}</td>
                    <td class="text-right font-mono">{{ number_format($item['ideal_recipe_usage'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format($item['actual_stock_outflow'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono" style="color: {{ $item['discrepancy'] > 0 ? '#dc2626' : '#16a34a' }};">
                        {{ $item['discrepancy'] > 0 ? '+' : '' }}{{ number_format($item['discrepancy'], 2, ',', '.') }}
                    </td>
                    <td class="text-right font-mono">{{ number_format($item['leakage_percentage'], 2, ',', '.') }}%</td>
                    <td class="text-right font-mono font-bold" style="color: #dc2626;">Rp {{ number_format($item['estimated_loss'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="7" class="text-right">TOTAL ESTIMASI KERUGIAN KEBOCORAN BAHAN:</td>
                <td class="text-right font-mono font-bold" style="color: #b91c1c;">Rp {{ number_format($leakage['total_estimated_loss'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Sign-off Block -->
    <div class="sign-off-block">
        <div class="sign-box">
            <div>Disiapkan Oleh,</div>
            <div class="subtitle">{{ auth()->user()?->role?->display_name ?? 'Operasional & Keuangan' }}</div>
            <div class="sign-line">{{ auth()->user()?->name ?? 'Manajemen Kafeina' }}</div>
            <div class="subtitle">{{ $company['name'] ?? 'PT Kafeina Roastery Indonesia' }}</div>
        </div>

        <div class="sign-box">
            <div>Disetujui Oleh,</div>
            <div class="subtitle">Direksi / Financial Controller</div>
            <div class="sign-line">__________________________</div>
            <div class="subtitle">{{ $company['name'] ?? 'PT Kafeina Roastery Indonesia' }}</div>
        </div>
    </div>

</body>
</html>
