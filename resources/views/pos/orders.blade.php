@extends('layouts.app')

@section('title', 'Riwayat Order & Penjualan Multi-Cabang - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="salesOrdersApp()">
    
    <!-- PAGE HEADER & PRIMARY ACTIONS -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-neutral-400 font-mono mb-1">
                <span>Kafeina ERP</span>
                <span>/</span>
                <span class="text-neutral-600 font-medium">Penjualan & Kasir</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">Riwayat Order & Penjualan Multi-Cabang</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Log transaksi riil pelanggan, rekonsiliasi pembayaran (QRIS Midtrans & Tunai), dan jejak auto-deduct persediaan bar.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <a href="{{ route('sales.export.csv', request()->query()) }}" 
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Unduh CSV Penjualan</span>
            </a>

            <a href="{{ route('pos.index') }}" 
               class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>Buka Terminal Kasir POS</span>
            </a>
        </div>
    </div>

    <!-- BENTO KPI METRIC CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- CARD 1: Total Omzet Kotor -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider">Total Omzet Bersih</span>
                <span class="text-[10px] font-mono text-neutral-400">Net + PB1</span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-950 tracking-tight">
                Rp {{ number_format($totalNetCollected, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Diskon Promo: <strong class="text-neutral-700 font-mono">Rp {{ number_format($totalDiscount, 0, ',', '.') }}</strong></span>
                <span class="text-emerald-600 font-semibold font-mono">Real-time</span>
            </div>
        </div>

        <!-- CARD 2: Volume Pesanan & AOV -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider">Volume Transaksi</span>
                <span class="text-[10px] font-mono text-neutral-400">Pesanan</span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-950 tracking-tight">
                {{ number_format($totalOrdersCount, 0, ',', '.') }} <span class="text-sm font-normal text-neutral-400">Order</span>
            </div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Rata-rata / Tiket (AOV):</span>
                <span class="font-mono text-neutral-900 font-bold">Rp {{ number_format($averageOrderValue, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- CARD 3: Pajak Daerah PB1 -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider text-amber-700">Pajak Restoran PB1 ({{ $pb1RateLabel ?? '11' }}%)</span>
                <span class="text-[10px] font-mono text-amber-600 font-semibold">Fiskal Pemda</span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-950 tracking-tight">
                Rp {{ number_format($totalTaxPb1, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Tertib Pajak 100%</span>
                <span class="text-neutral-700 font-mono font-medium">Bapenda Valid</span>
            </div>
        </div>

        <!-- CARD 4: Metode Pembayaran (QRIS Midtrans & Kas) -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider">Komposisi Pembayaran</span>
                <span class="text-[10px] font-mono text-neutral-400">Channel</span>
            </div>
            <div class="flex items-baseline gap-2">
                <div class="text-2xl font-bold font-sans tabular-nums text-neutral-950 tracking-tight">
                    {{ $paymentBreakdown->has('QRIS_MIDTRANS') ? round(($paymentBreakdown['QRIS_MIDTRANS']->count / max(1, $totalOrdersCount)) * 100) : 0 }}%
                </div>
                <span class="text-xs font-semibold text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded border border-sky-200">QRIS Midtrans</span>
            </div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Tunai: <strong class="text-neutral-700 font-mono">{{ $paymentBreakdown->has('CASH') ? $paymentBreakdown['CASH']->count : 0 }}</strong></span>
                <span>Transfer VA: <strong class="text-neutral-700 font-mono">{{ $paymentBreakdown->has('BANK_TRANSFER') ? $paymentBreakdown['BANK_TRANSFER']->count : 0 }}</strong></span>
            </div>
        </div>
    </div>

    <!-- FILTER TOOLBAR & LIVE SEARCH -->
    <form method="GET" action="{{ route('sales.index') }}" class="mb-4 bg-white border border-[#eaeaea] rounded-xl p-3 sm:p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- 1. Search Box -->
            <div class="relative lg:col-span-2">
                <div class="flex items-center w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs text-neutral-800 focus-within:border-black focus-within:bg-white transition-colors">
                    <svg class="w-3.5 h-3.5 text-neutral-400 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Cari no. order, nama pelanggan, meja..." 
                           class="w-full bg-transparent border-none text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none font-sans">
                    @if($search)
                    <a href="{{ route('sales.index', array_merge(request()->except('search'), [])) }}" class="text-neutral-400 hover:text-neutral-900 font-bold px-1 text-xs">✕</a>
                    @endif
                </div>
            </div>

            <!-- 2. Cabang Filter -->
            <div>
                @if(auth()->user() && !auth()->user()->canSwitchBranch() && auth()->user()->branch)
                    <div class="w-full px-3 py-2 rounded-lg bg-neutral-100 border border-neutral-200 text-xs text-neutral-800 flex items-center justify-between font-medium cursor-not-allowed" title="Akses terbatas hanya untuk cabang penugasan Anda">
                        <span class="truncate">{{ auth()->user()->branch->name }}</span>
                        <span class="text-[10px] font-mono text-neutral-500 bg-white px-1.5 py-0.5 rounded border border-neutral-200 shrink-0">Terkunci</span>
                    </div>
                    <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                @else
                    <select name="branch_id" onchange="this.form.submit()" 
                            class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs text-neutral-800 focus:border-black focus:bg-white outline-none cursor-pointer">
                        <option value="ALL">Semua Cabang (Grup)</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string)$selectedBranchId === (string)$b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- 3. Periode Filter -->
            <div>
                <select name="period" onchange="this.form.submit()" 
                        class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs text-neutral-800 focus:border-black focus:bg-white outline-none cursor-pointer">
                    <option value="today" {{ $selectedPeriod === 'today' ? 'selected' : '' }}>Hari Ini (Real-time)</option>
                    <option value="7d" {{ $selectedPeriod === '7d' ? 'selected' : '' }}>7 Hari Terakhir</option>
                    <option value="30d" {{ $selectedPeriod === '30d' ? 'selected' : '' }}>30 Hari Terakhir</option>
                    <option value="all" {{ $selectedPeriod === 'all' ? 'selected' : '' }}>Semua Riwayat</option>
                </select>
            </div>

            <!-- 4. Metode Bayar Filter -->
            <div>
                <select name="payment_method" onchange="this.form.submit()" 
                        class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs text-neutral-800 focus:border-black focus:bg-white outline-none cursor-pointer">
                    <option value="ALL">Semua Pembayaran</option>
                    <option value="QRIS_MIDTRANS" {{ $selectedPayment === 'QRIS_MIDTRANS' ? 'selected' : '' }}>QRIS Midtrans</option>
                    <option value="CASH" {{ $selectedPayment === 'CASH' ? 'selected' : '' }}>Tunai (Cash)</option>
                    <option value="BANK_TRANSFER" {{ $selectedPayment === 'BANK_TRANSFER' ? 'selected' : '' }}>Transfer Bank / VA</option>
                    <option value="NON_REVENUE" {{ $selectedPayment === 'NON_REVENUE' ? 'selected' : '' }}>Non-Revenue (Tester/Staf)</option>
                </select>
            </div>
        </div>
    </form>

    <!-- ORDERS TRANSACTION TABLE -->
    <div class="bg-white border border-[#eaeaea] rounded-xl overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#eaeaea] flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <h3 class="text-xs sm:text-sm font-semibold text-neutral-900 font-sans">Buku Transaksi Penjualan</h3>
            </div>
            <div class="text-xs font-mono text-neutral-400">
                Menampilkan <span class="text-neutral-900 font-semibold">{{ $orders->count() }}</span> dari <span class="text-neutral-900 font-semibold">{{ $orders->total() }}</span> pesanan
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans">
                <thead>
                    <tr class="text-neutral-400 border-b border-[#eaeaea] text-[10px] uppercase tracking-wider font-semibold bg-neutral-50/50">
                        <th class="py-2.5 px-3 whitespace-nowrap">No. Order & Waktu</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">Cabang & Meja</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">Pelanggan</th>
                        <th class="py-2.5 px-3 whitespace-nowrap">Rincian Menu</th>
                        <th class="py-2.5 px-3 text-center whitespace-nowrap">Metode</th>
                        <th class="py-2.5 px-3 text-right whitespace-nowrap">Total</th>
                        <th class="py-2.5 px-3 text-center whitespace-nowrap">Status</th>
                        <th class="py-2.5 px-3 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($orders as $order)
                    <tr class="hover:bg-neutral-50/80 transition-colors group">
                        <!-- 1. No. Order & Waktu -->
                        <td class="py-2.5 px-3 whitespace-nowrap">
                            <div class="font-mono font-semibold text-neutral-900 text-[11px]">
                                {{ $order->order_number }}
                            </div>
                            <div class="text-[10px] text-neutral-400 font-mono mt-0.5">
                                {{ $order->created_at->format('d M Y, H:i') }}
                            </div>
                        </td>

                        <!-- 2. Cabang & Meja -->
                        <td class="py-2.5 px-3 max-w-[155px]">
                            <div class="font-medium text-neutral-900 truncate" title="{{ $order->branch?->name ?? 'Kafeina Outlet' }}">
                                {{ $order->branch?->name ?? 'Kafeina Outlet' }}
                            </div>
                            <div class="text-[10px] text-neutral-400 font-sans flex items-center gap-1 mt-0.5 whitespace-nowrap">
                                <span class="px-1.5 py-0.2 rounded bg-neutral-100 text-neutral-600 font-mono text-[9px]">
                                    {{ $order->order_type }}
                                </span>
                                @if($order->table_number && $order->table_number !== '-')
                                    <span class="text-neutral-500">Meja {{ $order->table_number }}</span>
                                @endif
                            </div>
                        </td>

                        <!-- 3. Pelanggan -->
                        <td class="py-2.5 px-3 max-w-[140px]">
                            <div class="font-medium text-neutral-900 truncate" title="{{ $order->customer_name ?: 'Tamu Kafeina' }}">
                                {{ $order->customer_name ?: 'Tamu Kafeina' }}
                            </div>
                            <div class="text-[10px] text-neutral-400 font-sans mt-0.5 truncate">
                                {{ explode(' (', $order->cashier?->name ?? 'Kasir')[0] }}
                            </div>
                        </td>

                        <!-- 4. Menu & Rincian Item -->
                        <td class="py-2.5 px-3 max-w-[190px]">
                            <div class="space-y-0.5">
                                @foreach($order->items as $item)
                                    <div class="flex items-center justify-between text-[11px] gap-1.5 min-w-0">
                                        <span class="text-neutral-700 truncate font-medium">
                                            <strong class="font-mono text-neutral-900">{{ $item->quantity }}x</strong> 
                                            {{ $item->product?->name ?? 'Item Menu' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </td>

                        <!-- 5. Metode Bayar -->
                        <td class="py-2.5 px-3 text-center whitespace-nowrap">
                            @if($order->payment_method === 'QRIS_MIDTRANS')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-sky-50 text-sky-700 border border-sky-200">
                                    <svg class="w-3 h-3 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    QRIS Midtrans
                                </span>
                            @elseif($order->payment_method === 'CASH')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Tunai (Cash)
                                </span>
                            @elseif($order->payment_method === 'DEBIT_CARD')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                    Kartu Debit EDC
                                </span>
                            @elseif($order->payment_method === 'BANK_TRANSFER')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                    Transfer VA
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-neutral-100 text-neutral-600 border border-neutral-200">
                                    {{ $order->non_revenue_reason ?? 'Non-Revenue' }}
                                </span>
                            @endif

                            @if($order->payment_reference)
                                <div class="text-[9px] font-mono text-neutral-400 mt-1 max-w-[140px] mx-auto truncate" title="{{ $order->payment_reference }}">
                                    {{ $order->payment_reference }}
                                </div>
                            @endif
                        </td>

                        <!-- 6. Total Bayar -->
                        <td class="py-3 px-4 text-right">
                            <div class="font-bold font-mono text-neutral-950 tabular-nums">
                                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                            </div>
                            <div class="text-[10px] font-mono text-neutral-400">
                                Termasuk PB1: Rp {{ number_format($order->tax_amount, 0, ',', '.') }}
                            </div>
                        </td>

                        <!-- 7. Status -->
                        <td class="py-3 px-4 text-center">
                            @if($order->payment_status === 'PAID')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>LUNAS
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    {{ $order->payment_status }}
                                </span>
                            @endif
                        </td>

                        <!-- 8. Aksi (Struk & Resep) -->
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" 
                                        @click="viewReceipt({{ $order->id }})"
                                        title="Buka Struk Digital"
                                        class="p-1.5 rounded-lg border border-neutral-200 hover:border-neutral-900 text-neutral-600 hover:text-neutral-950 transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </button>
                                <button type="button" 
                                        @click="viewRecipeAudit({{ $order->id }})"
                                        title="Audit Auto-Deduct Resep Bahan Baku"
                                        class="p-1.5 rounded-lg border border-neutral-200 hover:border-neutral-900 text-neutral-600 hover:text-neutral-950 transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-neutral-400 font-sans">
                            <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center mx-auto mb-3 text-neutral-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p class="font-medium text-neutral-700">Tidak ada data pesanan penjualan yang cocok dengan filter.</p>
                            <p class="text-xs text-neutral-400 mt-1">Coba ubah cabang, periode rentang tanggal, atau kata kunci pencarian.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
        <div class="px-5 py-3 border-t border-[#eaeaea] bg-white">
            {{ $orders->links() }}
        </div>
        @endif
    </div>

    <!-- MODAL 1: PREVIEW STRUK DIGITAL KASIR -->
    <div x-show="openReceiptModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/60 backdrop-blur-xs font-sans"
         @keydown.escape.window="openReceiptModal = false">
        
        <div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl border border-neutral-200 overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-4 py-3 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between shrink-0">
                <span class="text-xs font-semibold text-neutral-900 font-mono">SALINAN STRUK RESMI KASIR</span>
                <button type="button" @click="openReceiptModal = false" class="text-neutral-400 hover:text-neutral-900 font-bold p-1 cursor-pointer">✕</button>
            </div>

            <!-- Authentic Thermal Paper Receipt View -->
            <div class="p-6 overflow-y-auto font-mono text-[11px] leading-relaxed text-neutral-800 bg-[#fffdfa]" id="printableReceiptArea">
                <div class="text-center pb-3 border-b border-dashed border-neutral-300">
                    <div class="font-bold text-sm text-neutral-950 uppercase tracking-widest">KAFEINA ROASTERY</div>
                    <div class="text-[10px] text-neutral-500 uppercase" x-text="receiptOrder?.branch?.name || 'SPECIALTY COFFEE LAB'"></div>
                    <div class="text-[9px] text-neutral-400" x-text="receiptOrder?.branch?.address || 'Jakarta Selatan'"></div>
                    <div class="text-[9px] text-neutral-400" x-text="'Telp: ' + (receiptOrder?.branch?.phone || '021-5234567')"></div>
                </div>

                <div class="py-2.5 border-b border-dashed border-neutral-300 space-y-0.5 text-[10px]">
                    <div class="flex justify-between">
                        <span class="text-neutral-500">No. Order:</span>
                        <span class="font-bold text-neutral-900" x-text="receiptOrder?.order_number"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Waktu:</span>
                        <span x-text="formatDate(receiptOrder?.created_at)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Kasir / Shift:</span>
                        <span x-text="receiptOrder?.cashier?.name || 'Kasir'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Pelanggan:</span>
                        <span class="font-semibold text-neutral-900" x-text="receiptOrder?.customer_name || 'Tamu Kafeina'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Tipe / Meja:</span>
                        <span x-text="(receiptOrder?.order_type || 'DINE_IN') + ' • Meja ' + (receiptOrder?.table_number || '-')"></span>
                    </div>
                </div>

                <!-- Receipt Items -->
                <div class="py-3 border-b border-dashed border-neutral-300 space-y-2">
                    <template x-for="item in (receiptOrder?.items || [])" :key="item.id">
                        <div>
                            <div class="flex justify-between font-semibold text-neutral-900">
                                <span x-text="item.product?.name || 'Menu'"></span>
                                <span x-text="'Rp ' + formatNumber(item.subtotal)"></span>
                            </div>
                            <div class="text-[10px] text-neutral-500 flex justify-between">
                                <span x-text="item.quantity + 'x @Rp ' + formatNumber(item.unit_price)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Totals -->
                <div class="py-2.5 border-b border-dashed border-neutral-300 space-y-1 text-[10px]">
                    <div class="flex justify-between text-neutral-600">
                        <span>Subtotal:</span>
                        <span x-text="'Rp ' + formatNumber(receiptOrder?.subtotal || 0)"></span>
                    </div>
                    <div class="flex justify-between text-neutral-600" x-show="Number(receiptOrder?.discount_amount) > 0">
                        <span>Diskon Promo:</span>
                        <span class="text-rose-600" x-text="'-Rp ' + formatNumber(receiptOrder?.discount_amount || 0)"></span>
                    </div>
                    <div class="flex justify-between text-neutral-600">
                        <span>PB1 Restoran (11%):</span>
                        <span x-text="'Rp ' + formatNumber(receiptOrder?.tax_amount || 0)"></span>
                    </div>
                    <div class="flex justify-between text-xs font-bold text-neutral-950 pt-1 border-t border-neutral-200">
                        <span>TOTAL AKHIR:</span>
                        <span x-text="'Rp ' + formatNumber(receiptOrder?.total_amount || 0)"></span>
                    </div>
                    <div class="flex justify-between text-neutral-600 pt-1">
                        <span>Metode Pembayaran:</span>
                        <span class="font-semibold text-neutral-900" x-text="receiptOrder?.payment_method"></span>
                    </div>
                </div>

                <div class="text-center pt-3 text-[9px] text-neutral-400 space-y-1">
                    <p>Terima kasih atas kunjungan Anda ke Kafeina Roastery.</p>
                    <p>Biji kopi kami disangrai artisan dengan mesin Giesen W15A.</p>
                    <div class="pt-1 text-[8px] text-neutral-300">*** STRUK SISTEM VALID TERVERIFIKASI ***</div>
                </div>
            </div>

            <div class="p-3 bg-neutral-50 border-t border-neutral-200 flex gap-2 shrink-0">
                <button type="button" @click="printReceipt()" 
                        class="flex-1 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white font-semibold text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Cetak Struk Fisik</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: AUDIT RESEP & BAHAN BAKU (AUTO-DEDUCT) -->
    <div x-show="openRecipeAuditModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/60 backdrop-blur-xs font-sans"
         @keydown.escape.window="openRecipeAuditModal = false">
        
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-neutral-200 overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-5 py-4 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-neutral-900 font-sans">Jejak Resep & Auto-Deduct Persediaan</h3>
                    <p class="text-[11px] text-neutral-400 font-mono" x-text="'Order #' + (recipeOrder?.order_number || '-')"></p>
                </div>
                <button type="button" @click="openRecipeAuditModal = false" class="text-neutral-400 hover:text-neutral-900 font-bold p-1 cursor-pointer">✕</button>
            </div>

            <div class="p-5 overflow-y-auto space-y-4 text-xs font-sans">
                <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-200/80">
                    <div class="text-[11px] font-semibold text-neutral-500 uppercase tracking-wider mb-1">Mekanisme Pengurangan Otomatis (FIFO)</div>
                    <p class="text-neutral-700 text-xs leading-relaxed">
                        Setiap transaksi kasir di bar langsung memotong stok bahan baku ideal per cup secara atomik dengan <em class="font-mono text-neutral-900">pessimistic row-locking</em> untuk mengeliminasi potensi stok negatif.
                    </p>
                </div>

                <div class="space-y-3">
                    <template x-for="item in (recipeOrder?.items || [])" :key="item.id">
                        <div class="p-3.5 rounded-xl border border-neutral-200 bg-white">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold text-neutral-950" x-text="item.product?.name"></div>
                                <span class="text-xs font-mono text-neutral-500" x-text="item.quantity + ' Porsi / Cup'"></span>
                            </div>

                            <div class="space-y-1.5 pt-2 border-t border-neutral-100">
                                <template x-for="ded in (item.recipe_deductions || [])" :key="ded.id">
                                    <div class="flex items-center justify-between text-[11px] font-mono bg-neutral-50 px-2 py-1 rounded">
                                        <span class="text-neutral-700" x-text="ded.ingredient_product?.name || 'Bahan Baku Resep'"></span>
                                        <span class="font-bold text-rose-600" x-text="'-' + ded.quantity_deducted + ' ' + ded.unit"></span>
                                    </div>
                                </template>
                                <div x-show="!item.recipe_deductions || item.recipe_deductions.length === 0" class="text-[11px] text-neutral-400 italic">
                                    Produk retail/merchandise (pengurangan stok 1:1 langsung per unit pouch/pack).
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="p-3 bg-neutral-50 border-t border-neutral-200 flex justify-end shrink-0">
                <button type="button" @click="openRecipeAuditModal = false" 
                        class="px-4 py-2 rounded-xl bg-neutral-900 hover:bg-neutral-800 text-white font-semibold text-xs transition-colors cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function salesOrdersApp() {
    return {
        openReceiptModal: false,
        openRecipeAuditModal: false,
        receiptOrder: null,
        recipeOrder: null,

        async viewReceipt(orderId) {
            try {
                const res = await fetch(`/pos/orders/${orderId}`);
                const data = await res.json();
                if (data.success) {
                    this.receiptOrder = data.order;
                    this.openReceiptModal = true;
                }
            } catch (e) {
                alert('Gagal mengambil data struk order.');
            }
        },

        async viewRecipeAudit(orderId) {
            try {
                const res = await fetch(`/pos/orders/${orderId}`);
                const data = await res.json();
                if (data.success) {
                    this.recipeOrder = data.order;
                    this.openRecipeAuditModal = true;
                }
            } catch (e) {
                alert('Gagal mengambil jejak audit resep.');
            }
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) + ' WIB';
        },

        printReceipt() {
            window.print();
        }
    };
}
</script>
@endsection
