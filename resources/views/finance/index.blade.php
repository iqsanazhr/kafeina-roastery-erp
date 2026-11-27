@extends('layouts.app')

@section('title', 'Laporan Keuangan & Analisis Kebocoran Bar - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans">
    
    <!-- PAGE HEADER & PRIMARY ACTIONS -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-neutral-400 mb-1">
                <span>Kafeina ERP</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-600 font-medium">Finance & Controlling</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-400 font-mono text-[11px]">Real P&L</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">Laporan Keuangan & Analisis Kebocoran Bar</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Laba rugi riil berbasis pemotongan bahan baku otomatis, audit beban non-revenue, dan analisis selisih resep bar.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <a href="{{ route('finance.export.csv', request()->query()) }}" 
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Unduh CSV</span>
            </a>
            <a href="{{ route('finance.export.pdf', request()->query()) }}" 
               target="_blank"
               class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Cetak PDF</span>
            </a>
        </div>
    </div>

    <!-- FILTER TOOLBAR (DATE RANGE & BRANCH SELECTION) -->
    <form method="GET" action="{{ route('finance.index') }}" 
          class="mb-6 bg-white border border-[#eaeaea] rounded-2xl p-3 sm:p-4 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3 font-sans">
        <div class="flex flex-wrap items-center gap-2.5 flex-1">
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs">
                <span class="text-neutral-400 text-[11px]">Dari:</span>
                <input type="date" name="start_date" value="{{ $startDate }}" 
                       class="bg-transparent text-xs font-mono text-neutral-900 outline-none cursor-pointer">
            </div>
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs">
                <span class="text-neutral-400 text-[11px]">Sampai:</span>
                <input type="date" name="end_date" value="{{ $endDate }}" 
                       class="bg-transparent text-xs font-mono text-neutral-900 outline-none cursor-pointer">
            </div>
            <div class="min-w-[200px]">
                <select name="branch_id" 
                        class="w-full px-3 py-1.5 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-xs text-neutral-900 outline-none transition-all">
                    <option value="">Semua Cabang (Konsolidasi)</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button type="submit" 
                    class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                Terapkan Filter
            </button>
            @if(request()->hasAny(['start_date', 'end_date', 'branch_id']))
                <a href="{{ route('finance.index') }}" class="px-2.5 py-2 rounded-xl text-xs text-neutral-400 hover:text-neutral-700 transition-colors">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- BENTO KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- CARD 1: Net Sales -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Pendapatan Bersih (Net)</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 font-mono tabular-nums">
                Rp {{ number_format($pnl['metrics']['net_sales'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Setelah potongan diskon & promo
            </div>
        </div>

        <!-- CARD 2: Gross Profit -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Laba Kotor Operasional</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 font-mono tabular-nums">
                Rp {{ number_format($pnl['metrics']['gross_profit'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Gross Margin: <strong class="text-neutral-900 font-mono">{{ $pnl['metrics']['gross_margin_percentage'] ?? 0 }}%</strong>
            </div>
        </div>

        <!-- CARD 3: Laba Bersih Operasional -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Laba Bersih Riil (EBITDA)</div>
            <div class="text-2xl font-bold tracking-tight text-emerald-700 font-mono tabular-nums">
                Rp {{ number_format($pnl['metrics']['net_operating_profit'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Setelah beban kalibrasi & operasional
            </div>
        </div>

        <!-- CARD 4: Kebocoran Bar -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Estimasi Kerugian Bar</div>
            <div class="text-2xl font-bold tracking-tight text-rose-600 font-mono tabular-nums">
                Rp {{ number_format($leakage['total_estimated_loss'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Selisih resep vs konsumsi aktual bar
            </div>
        </div>
    </div>

    <!-- MAIN P&L STATEMENTS & VOLUME SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- P&L Financial Statement Card (2 Columns) -->
        <div class="lg:col-span-2 bg-white border border-[#eaeaea] rounded-2xl p-5 sm:p-6 shadow-2xs">
            <div class="pb-3 border-b border-neutral-100 flex justify-between items-center">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950 font-sans">Laporan Laba Rugi Komprehensif (Real P&L)</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Periode: {{ $pnl['period']['start_date'] ?? $startDate }} s/d {{ $pnl['period']['end_date'] ?? $endDate }}</p>
                </div>
                <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg bg-neutral-100 border border-neutral-200 text-neutral-800">
                    Gross Margin: {{ $pnl['metrics']['gross_margin_percentage'] ?? 0 }}%
                </span>
            </div>

            <div class="mt-4 divide-y divide-[#f5f5f5] text-xs font-sans">
                <div class="py-2.5 flex justify-between items-center">
                    <span class="text-neutral-600 font-medium">1. Omzet Penjualan Kotor (Gross Sales)</span>
                    <span class="font-bold text-neutral-900 font-mono tabular-nums">Rp {{ number_format($pnl['metrics']['gross_sales'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="py-2.5 flex justify-between items-center text-neutral-500">
                    <span>2. Potongan Diskon / Kode Promo Kasir</span>
                    <span class="font-bold text-rose-600 font-mono tabular-nums">- Rp {{ number_format($pnl['metrics']['discounts'] ?? 0, 0, ',', '.') }}</span>
                </div>
                
                <!-- Net Sales Subtotal -->
                <div class="py-3 flex justify-between items-center bg-neutral-50/80 px-3 rounded-xl font-bold border border-neutral-200/60 my-1">
                    <span class="text-neutral-900">Pendapatan Bersih (Net Sales)</span>
                    <span class="text-neutral-950 text-sm font-mono tabular-nums">Rp {{ number_format($pnl['metrics']['net_sales'] ?? 0, 0, ',', '.') }}</span>
                </div>

                <div class="py-2.5 flex justify-between items-center text-neutral-500">
                    <span>3. Beban Pokok Penjualan Riil (Real COGS Sangrai & Resep)</span>
                    <span class="font-bold text-neutral-900 font-mono tabular-nums">- Rp {{ number_format($pnl['metrics']['real_cogs'] ?? 0, 0, ',', '.') }}</span>
                </div>

                <!-- Gross Profit Subtotal -->
                <div class="py-3 flex justify-between items-center px-3 bg-amber-50/60 border border-amber-200/80 rounded-xl my-1">
                    <span class="text-amber-950 font-bold uppercase tracking-wide text-xs">Laba Kotor Operasional (Gross Profit)</span>
                    <span class="text-amber-900 font-mono font-bold text-sm tabular-nums">Rp {{ number_format($pnl['metrics']['gross_profit'] ?? 0, 0, ',', '.') }}</span>
                </div>

                <div class="py-2.5 flex justify-between items-center text-neutral-500">
                    <span>4. Beban Non-Revenue (Tester Cupping, Kalibrasi, Barista)</span>
                    <span class="font-bold text-neutral-700 font-mono tabular-nums">- Rp {{ number_format($pnl['metrics']['non_revenue_expense'] ?? 0, 0, ',', '.') }}</span>
                </div>

                <!-- Net Income Grand Total -->
                <div class="py-3.5 flex justify-between items-center px-3.5 bg-neutral-950 text-white rounded-xl my-1 shadow-xs">
                    <span class="uppercase tracking-wider text-xs font-semibold">Laba Bersih Operasional (Net Income)</span>
                    <span class="font-mono text-base font-bold tabular-nums">Rp {{ number_format($pnl['metrics']['net_operating_profit'] ?? 0, 0, ',', '.') }}</span>
                </div>

                <div class="py-2 flex justify-between text-neutral-400 pt-3 text-[11px] font-mono">
                    <span class="font-sans">*Pajak Restoran PB1 {{ rtrim(rtrim(number_format((float) \App\Models\SystemSetting::get('tax_pb1_rate', 11), 1, '.', ''), '0'), '.') }}% Terkumpul:</span>
                    <span>Rp {{ number_format($pnl['metrics']['tax_pb1'] ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Volume Transaksi & Summary (1 Column) -->
        <div class="space-y-4">
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] text-neutral-400 font-semibold uppercase tracking-wider font-sans">Volume Transaksi Kasir</span>
                <div class="text-3xl font-bold text-neutral-950 font-mono mt-1 tabular-nums">
                    {{ $pnl['transactions']['total'] ?? 0 }} <span class="text-xs text-neutral-400 font-normal font-sans">tiket</span>
                </div>
                <div class="mt-4 pt-3 border-t border-neutral-100 text-xs font-sans space-y-2 text-neutral-600">
                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Dine-In:</span>
                        <strong class="font-mono text-neutral-900">{{ $pnl['transactions']['dine_in'] ?? 0 }}</strong>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Take-Away:</span>
                        <strong class="font-mono text-neutral-900">{{ $pnl['transactions']['take_away'] ?? 0 }}</strong>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-neutral-400"></span> Non-Revenue:</span>
                        <strong class="font-mono text-neutral-900">{{ $pnl['transactions']['non_revenue'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] text-neutral-400 font-semibold uppercase tracking-wider font-sans">Akurasi HPP Terpadu</span>
                <p class="text-xs text-neutral-600 mt-2 leading-relaxed">
                    Setiap transaksi di kasir otomatis memotong persediaan berdasarkan Bill of Materials (BOM) dan resep standar barista, menghasilkan angka HPP riil tanpa estimasi manual.
                </p>
            </div>
        </div>

    </div>

    <!-- INGREDIENT VARIANCE & WASTE ANALYSIS SECTION -->
    <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 sm:p-6 shadow-2xs">
        <div class="pb-3 border-b border-neutral-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold text-neutral-950 font-sans">Analisis Kebocoran Bahan Baku Bar (Recipe vs Actual Usage)</h3>
                <p class="text-xs text-neutral-500 mt-0.5">Membandingkan standar resep teoritis dengan pengeluaran aktual stok di gerai terpilih.</p>
            </div>
            <span class="text-xs font-mono font-bold text-rose-800 bg-rose-50 border border-rose-200/80 px-2.5 py-1 rounded-lg">
                Estimasi Waste: Rp {{ number_format($leakage['total_estimated_loss'] ?? 0, 0, ',', '.') }}
            </span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-xs font-sans">
                <thead>
                    <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/70">
                        <th class="py-3 px-4">SKU & Bahan Baku</th>
                        <th class="py-3 px-4 text-right">Resep Ideal</th>
                        <th class="py-3 px-4 text-right">Pengeluaran Aktual</th>
                        <th class="py-3 px-4 text-right">Selisih (Waste)</th>
                        <th class="py-3 px-4 text-right">% Kebocoran</th>
                        <th class="py-3 px-4 text-right">Estimasi Kerugian</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f5f5f5]">
                    @forelse($leakage['items'] ?? [] as $item)
                        <tr class="hover:bg-neutral-50/80 transition-colors">
                            <td class="py-3.5 px-4 font-sans font-bold text-neutral-900">
                                {{ $item['name'] }}
                                <div class="text-[11px] text-neutral-400 font-mono font-normal mt-0.5">{{ $item['sku'] }} • @ Rp {{ number_format($item['cost_price'], 0, ',', '.') }}/{{ $item['unit'] }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-neutral-600 tabular-nums">{{ number_format($item['ideal_recipe_usage'], 2, ',', '.') }} {{ $item['unit'] }}</td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-900 tabular-nums">{{ number_format($item['actual_stock_outflow'], 2, ',', '.') }} {{ $item['unit'] }}</td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold tabular-nums {{ $item['discrepancy'] > 0 ? 'text-rose-600' : 'text-neutral-900' }}">
                                {{ ($item['discrepancy'] > 0 ? '+' : '') . number_format($item['discrepancy'], 2, ',', '.') }} {{ $item['unit'] }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold tabular-nums {{ $item['leakage_percentage'] > 5 ? 'text-rose-600' : 'text-neutral-800' }}">
                                {{ number_format($item['leakage_percentage'], 2, ',', '.') }}%
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-950 tabular-nums">
                                Rp {{ number_format($item['estimated_loss'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @php
                                    $st = $item['status'] ?? 'OPTIMAL';
                                    $isCritical = in_array($st, ['CRITICAL_LEAK', 'CRITICAL', 'LEAKAGE'], true);
                                    $isWarning = $st === 'WARNING';
                                @endphp
                                <div class="inline-flex items-center gap-1.5 text-xs">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $isCritical ? 'bg-rose-500 animate-pulse' : ($isWarning ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                    <span class="font-medium {{ $isCritical ? 'text-rose-700' : ($isWarning ? 'text-amber-700' : 'text-emerald-700') }}">
                                        {{ $isCritical ? 'Kebocoran' : ($isWarning ? 'Peringatan' : 'Normal') }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-neutral-400 font-sans text-xs">
                                Tidak ada data kebocoran bahan baku pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
