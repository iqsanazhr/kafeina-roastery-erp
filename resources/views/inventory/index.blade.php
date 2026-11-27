@extends('layouts.app')

@section('title', 'Inventori Multi-Cabang & ROP - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="inventoryApp()">
    
    <!-- PAGE HEADER & PRIMARY ACTIONS -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-neutral-400 font-mono mb-1">
                <span>Kafeina ERP</span>
                <span>/</span>
                <span class="text-neutral-600 font-medium">Inventori Multi-Cabang & ROP</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">Inventori & Reorder Point</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Monitoring saldo stok bahan baku, batas minimum pemesanan ulang (ROP), dan ketersediaan gerai.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <a href="{{ route('logistics.index') }}" 
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Surat Jalan & Material Requests</span>
            </a>

            <button type="button" 
                    @click="openSafetyModal = true"
                    class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Audit Safety Stock</span>
            </button>
        </div>
    </div>

    <!-- Toast / Flash Message -->
    <div x-show="alertMessage" x-cloak 
         class="mb-5 p-3.5 rounded-xl border flex items-center justify-between text-xs font-sans shadow-xs transition-transform duration-150"
         :class="alertType === 'success' ? 'bg-emerald-50 border-emerald-200/80 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'">
        <div class="flex items-center gap-2.5">
            <span class="w-2 h-2 rounded-full animate-pulse" :class="alertType === 'success' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
            <span class="font-medium" x-text="alertMessage"></span>
        </div>
        <button type="button" @click="alertMessage = ''" class="hover:opacity-70 font-bold p-1 cursor-pointer">✕</button>
    </div>

    <!-- BENTO KPI METRIC CARDS (Clean Minimalist Style) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- CARD 1: Total SKU -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors font-sans">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider">Total SKU Persediaan</span>
                <span class="text-[10px] font-mono text-neutral-400">Ledger</span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight" x-text="products.length"></div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Beans, Dairy & Packaging</span>
                <span class="font-mono text-neutral-700 font-semibold">100% Aktif</span>
            </div>
        </div>

        <!-- CARD 2: Gudang Terhubung -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors font-sans">
            <div class="flex items-center justify-between gap-2 text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider truncate">Gudang & Hub Aktif</span>
                <span class="text-[10px] font-mono text-neutral-400 shrink-0">Jaringan</span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight" x-text="warehouses.length"></div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>1 Sentral Hub + 3 Gerai Ritel</span>
                <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Terhubung
                </span>
            </div>
        </div>

        <!-- CARD 3: ROP Critical Alerts -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-rose-300 transition-colors font-sans">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider text-rose-700">Peringatan ROP Kritis</span>
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-rose-600 tracking-tight" x-text="criticalAlertsCount"></div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Stok &lt; 30% safety limit</span>
                <span class="text-rose-600 font-semibold font-mono">Perlu Restock</span>
            </div>
        </div>

        <!-- CARD 4: Gudang Sentral -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors font-sans">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider">Stok Central Roastery</span>
                <span class="text-[10px] font-mono text-neutral-400">Green Beans</span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight">{{ number_format($centralGreenStockKg ?? 1130, 0, ',', '.') }} <span class="text-sm font-normal text-neutral-400">kg</span></div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Biji mentah siap sangrai</span>
                <span class="font-mono text-emerald-700 font-semibold">Tersertifikasi</span>
            </div>
        </div>
    </div>

    <!-- SAFETY STOCK & ROP ALERT COMPACT STRIP (Clean & Non-cluttered) -->
    <div x-show="safetyAlerts.length > 0" class="mb-6 bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <h3 class="text-xs sm:text-sm font-semibold text-neutral-900 font-sans">Item Mendekati Batas Minimum Operasional (ROP)</h3>
            </div>
            <span class="text-[11px] text-neutral-400 font-mono" x-text="safetyAlerts.length + ' item perlu perhatian'"></span>
        </div>

        <div class="mt-3.5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <template x-for="(alert, idx) in safetyAlerts" :key="idx">
                <div class="p-3 rounded-lg border border-[#eaeaea] hover:border-neutral-300 transition-colors bg-white flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="text-xs font-semibold text-neutral-900 leading-snug" x-text="alert.product_name"></h4>
                            <span class="text-[10px] font-mono font-semibold"
                                  :class="alert.level === 'CRITICAL' ? 'text-rose-600' : 'text-amber-600'"
                                  x-text="alert.level === 'CRITICAL' ? 'Kritis' : 'Menipis'">
                            </span>
                        </div>
                        <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                            <span x-text="alert.branch"></span>
                        </div>
                    </div>

                    <!-- Clean Minimal Stock Meter -->
                    <div class="mt-2.5 pt-2 border-t border-[#f5f5f5] font-sans">
                        <div class="flex items-center justify-between text-[11px] mb-1 font-mono">
                            <span class="text-neutral-500">Sisa: <strong class="text-neutral-900" x-text="alert.current + ' ' + alert.unit"></strong></span>
                            <span class="text-neutral-400">Min: <span x-text="alert.min + ' ' + alert.unit"></span></span>
                        </div>
                        <div class="w-full bg-neutral-100 h-1 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300"
                                 :class="alert.level === 'CRITICAL' ? 'bg-rose-500' : 'bg-amber-500'"
                                 :style="'width: ' + Math.min(100, Math.round((alert.current / (alert.min || 1)) * 100)) + '%'"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- TAB SWITCHER: SALDO STOK VS BUKU MUTASI KARTU STOK -->
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-neutral-200/90 pb-3">
        <div class="inline-flex p-1 rounded-xl bg-neutral-100/90 border border-neutral-200/80 font-sans text-xs">
            <button type="button" 
                    @click="activeTab = 'balances'"
                    class="px-3.5 py-1.5 rounded-lg font-medium transition-all cursor-pointer"
                    :class="activeTab === 'balances' ? 'bg-white text-neutral-950 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'">
                Saldo Stok Multi-Gudang
            </button>
            <button type="button" 
                    @click="activeTab = 'ledger'"
                    class="px-3.5 py-1.5 rounded-lg font-medium transition-all cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'ledger' ? 'bg-white text-neutral-950 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'">
                <span>Buku Mutasi & Kartu Stok</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono" :class="activeTab === 'ledger' ? 'bg-neutral-900 text-white' : 'bg-neutral-200 text-neutral-600'">{{ $stockCards->total() }}</span>
            </button>
        </div>

        <div x-show="activeTab === 'ledger'" x-cloak class="flex items-center gap-2">
            <a href="{{ route('inventory.stock-cards.export.csv', request()->query()) }}"
               class="px-3 py-1.5 rounded-lg bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 text-xs font-semibold flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Unduh CSV Mutasi (.csv)</span>
            </a>
        </div>
    </div>

    <!-- TAB 1: SALDO STOK MULTI-GUDANG -->
    <div x-show="activeTab === 'balances'">
        <!-- UNIFIED TOOLBAR: SEARCH & BRANCH DROPDOWN (Dashboard Style) -->
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Search Input with Keyboard Shortcut -->
            <div class="relative w-full sm:w-80 md:w-96">
                <div class="flex items-center w-full px-3 py-2 rounded-xl bg-white border border-[#eaeaea] text-neutral-500 text-xs hover:border-[#ccc] focus-within:border-black transition-colors font-sans">
                    <svg class="w-4 h-4 text-neutral-400 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" 
                           x-model="searchQuery"
                           placeholder="Cari SKU atau nama item (cth: Gayo, Oat, Cup)..." 
                           @keydown.escape="searchQuery = ''"
                           class="bg-transparent border-none text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none flex-1 font-sans">
                    <kbd class="px-1.5 py-0.5 rounded bg-neutral-100 border border-neutral-200 text-[10px] font-sans font-medium text-neutral-400">ESC</kbd>
                </div>
            </div>

            <!-- Branch Select Dropdown (Clean & Compact) -->
            <div class="flex items-center gap-2">
                <label class="text-xs text-neutral-400 font-sans hidden sm:inline">Lokasi Gerai:</label>
                <select x-model="selectedBranch"
                        class="px-3 py-2 rounded-xl bg-white border border-[#eaeaea] hover:border-neutral-300 text-xs font-sans text-neutral-800 outline-none transition-colors cursor-pointer">
                    <option value="ALL">Semua Cabang & Gudang (Konsolidasi Grup)</option>
                    <template x-for="b in branches" :key="b.id">
                        <option :value="b.id" x-text="b.name"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- MULTI-BRANCH WAREHOUSE STOCK TABLE (Linear / High-Craft Grid) -->
        <div class="bg-white border border-[#eaeaea] rounded-xl overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#eaeaea] flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-semibold text-neutral-900 font-sans">Buku Saldo Stok Multi-Gudang</h3>
                </div>
                <div class="text-xs font-mono text-neutral-400">
                    Menampilkan <span class="text-neutral-900 font-semibold" x-text="filteredStocks.length"></span> dari <span x-text="warehouseStocks.length"></span> catatan
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-sans">
                    <thead>
                        <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/50">
                            <th class="py-3 px-5">SKU / Nama Bahan Baku</th>
                            <th class="py-3 px-5">Gudang & Cabang</th>
                            <th class="py-3 px-5 text-right">Stok Fisik</th>
                            <th class="py-3 px-5 text-right">Safety Limit (ROP)</th>
                            <th class="py-3 px-5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f5f5f5]">
                        <template x-for="stock in filteredStocks" :key="stock.id">
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="py-3 px-5">
                                    <div class="font-semibold text-neutral-900 leading-snug" x-text="stock.product ? stock.product.name : 'Item Persediaan'"></div>
                                    <div class="text-[11px] text-neutral-400 font-mono mt-0.5" x-text="stock.product ? stock.product.sku : '-'"></div>
                                </td>
                                <td class="py-3 px-5 text-neutral-700">
                                    <div class="font-medium text-neutral-800" x-text="stock.warehouse ? stock.warehouse.name : 'Gudang Utama'"></div>
                                    <div class="text-[11px] text-neutral-400 font-sans mt-0.5" x-text="stock.warehouse && stock.warehouse.branch ? stock.warehouse.branch.name : '-'"></div>
                                </td>
                                <td class="py-3 px-5 text-right tabular-nums">
                                    <span class="font-bold text-neutral-900 text-sm font-mono" x-text="formatNumber(stock.quantity)"></span>
                                    <span class="text-[10px] text-neutral-400 uppercase font-mono ml-0.5" x-text="stock.product ? stock.product.unit : 'pcs'"></span>
                                </td>
                                <td class="py-3 px-5 text-right tabular-nums text-neutral-500 font-mono">
                                    <span x-text="formatNumber(stock.safety_stock_threshold || 100)"></span>
                                    <span class="text-[10px] text-neutral-400 uppercase" x-text="stock.product ? stock.product.unit : 'pcs'"></span>
                                </td>
                                <td class="py-3 px-5 text-center">
                                    <template x-if="stock.quantity <= (stock.safety_stock_threshold || 50)">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 font-medium text-[11px]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                            Perlu Restock
                                        </span>
                                    </template>
                                    <template x-if="stock.quantity > (stock.safety_stock_threshold || 50)">
                                        <span class="inline-flex items-center gap-1.5 text-neutral-600 font-medium text-xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Tercukupi
                                        </span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredStocks.length === 0">
                            <td colspan="5" class="py-12 text-center text-neutral-400 text-xs">
                                Tidak ada item stok yang sesuai dengan filter atau pencarian Anda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: BUKU MUTASI & KARTU STOK (STOCK LEDGER) -->
    <div x-show="activeTab === 'ledger'" x-cloak>
        <!-- FILTER TOOLBAR MUTASI -->
        <form method="GET" action="{{ route('inventory.index') }}" class="mb-4 bg-white border border-[#eaeaea] rounded-xl p-3 sm:p-4">
            <input type="hidden" name="tab" value="ledger">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- 1. Search Box -->
                <div>
                    <div class="flex items-center w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs text-neutral-800 focus-within:border-black focus-within:bg-white transition-colors">
                        <svg class="w-3.5 h-3.5 text-neutral-400 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" 
                               name="stock_search" 
                               value="{{ $stockCardSearch }}" 
                               placeholder="Cari SKU, nama bahan baku, catatan..." 
                               class="w-full bg-transparent border-none text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none font-sans">
                    </div>
                </div>

                <!-- 2. Gudang & Cabang Filter -->
                <div>
                    <select name="warehouse_id" onchange="this.form.submit()" 
                            class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs text-neutral-800 focus:border-black focus:bg-white outline-none cursor-pointer">
                        <option value="ALL">Semua Gudang & Cabang</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)$selectedWarehouseId === (string)$wh->id ? 'selected' : '' }}>
                                {{ $wh->name }} ({{ $wh->branch?->name ?? 'Pusat' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Tipe Mutasi Filter -->
                <div>
                    <select name="reference_type" onchange="this.form.submit()" 
                            class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs text-neutral-800 focus:border-black focus:bg-white outline-none cursor-pointer">
                        <option value="ALL">Semua Tipe Mutasi</option>
                        <option value="POS_SALE" {{ $selectedRefType === 'POS_SALE' ? 'selected' : '' }}>POS Sale (Auto-Deduct Kasir)</option>
                        <option value="PO_RECEIPT" {{ $selectedRefType === 'PO_RECEIPT' ? 'selected' : '' }}>PO Receipt (Penerimaan Supplier)</option>
                        <option value="ROAST_CHARGE" {{ $selectedRefType === 'ROAST_CHARGE' ? 'selected' : '' }}>Roast Charge (Bahan Baku Sangrai)</option>
                        <option value="ROAST_OUTPUT" {{ $selectedRefType === 'ROAST_OUTPUT' ? 'selected' : '' }}>Roast Output (Biji Matang)</option>
                        <option value="TRANSFER_DISPATCH" {{ $selectedRefType === 'TRANSFER_DISPATCH' ? 'selected' : '' }}>Transfer Dispatch (Surat Jalan Out)</option>
                        <option value="TRANSFER_RECEIVE" {{ $selectedRefType === 'TRANSFER_RECEIVE' ? 'selected' : '' }}>Transfer Receive (Penerimaan Cabang)</option>
                        <option value="ADJUSTMENT" {{ $selectedRefType === 'ADJUSTMENT' ? 'selected' : '' }}>Adjustment (Kalibrasi / Selisih)</option>
                    </select>
                </div>

                <!-- 4. Tombol Submit & Reset -->
                <div class="flex items-center gap-2">
                    <button type="submit" 
                            class="flex-1 py-2 px-3 rounded-lg bg-neutral-950 hover:bg-neutral-800 text-white font-semibold text-xs transition-colors cursor-pointer">
                        Terapkan Filter
                    </button>
                    @if($stockCardSearch || $selectedWarehouseId !== 'ALL' || $selectedRefType !== 'ALL')
                    <a href="{{ route('inventory.index', ['tab' => 'ledger']) }}" 
                       class="px-3 py-2 rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-medium cursor-pointer">
                        Reset
                    </a>
                    @endif
                </div>
            </div>
        </form>

        <!-- STOCK CARDS TABLE (LEDGER) -->
        <div class="bg-white border border-[#eaeaea] rounded-xl overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#eaeaea] flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-semibold text-neutral-900 font-sans">Buku Jurnal Mutasi Persediaan (Kartu Stok)</h3>
                </div>
                <div class="text-xs font-mono text-neutral-400">
                    Menampilkan <span class="text-neutral-900 font-semibold">{{ $stockCards->count() }}</span> dari <span class="text-neutral-900 font-semibold">{{ $stockCards->total() }}</span> catatan mutasi
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-sans">
                    <thead>
                        <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/50">
                            <th class="py-3 px-4">Waktu & Lokasi</th>
                            <th class="py-3 px-4">SKU / Nama Bahan Baku</th>
                            <th class="py-3 px-4">Tipe Mutasi & Ref</th>
                            <th class="py-3 px-4 text-right text-emerald-700">Masuk (+)</th>
                            <th class="py-3 px-4 text-right text-rose-700">Keluar (-)</th>
                            <th class="py-3 px-4 text-right">Saldo Akhir</th>
                            <th class="py-3 px-4 text-right">HPP Satuan</th>
                            <th class="py-3 px-4">Catatan Mutasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f5f5f5]">
                        @forelse($stockCards as $card)
                        <tr class="hover:bg-neutral-50/80 transition-colors">
                            <!-- 1. Waktu & Lokasi -->
                            <td class="py-3 px-4">
                                <div class="font-mono text-neutral-900 font-medium">
                                    {{ $card->created_at->format('d M Y, H:i') }} WIB
                                </div>
                                <div class="text-[11px] text-neutral-500 font-sans mt-0.5">
                                    {{ $card->warehouse?->name ?? 'Gudang' }}
                                    <span class="text-neutral-400">({{ $card->warehouse?->branch?->name ?? 'Pusat' }})</span>
                                </div>
                            </td>

                            <!-- 2. SKU & Bahan -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-neutral-950">
                                    {{ $card->product?->name ?? 'Item Persediaan' }}
                                </div>
                                <div class="text-[11px] text-neutral-400 font-mono mt-0.5">
                                    {{ $card->product?->sku ?? '-' }}
                                </div>
                            </td>

                            <!-- 3. Tipe Mutasi -->
                            <td class="py-3 px-4">
                                @if($card->reference_type === 'POS_SALE')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                        POS Auto-Deduct
                                    </span>
                                @elseif($card->reference_type === 'PO_RECEIPT')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        PO Supplier Masuk
                                    </span>
                                @elseif($card->reference_type === 'ROAST_OUTPUT')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Hasil Sangrai (Output)
                                    </span>
                                @elseif($card->reference_type === 'ROAST_CHARGE')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                        Charge Mesin Roasting
                                    </span>
                                @elseif($card->reference_type === 'TRANSFER_RECEIVE')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-sky-50 text-sky-700 border border-sky-200">
                                        Surat Jalan Terima
                                    </span>
                                @elseif($card->reference_type === 'TRANSFER_DISPATCH')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-orange-50 text-orange-700 border border-orange-200">
                                        Surat Jalan Kirim
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-medium bg-neutral-100 text-neutral-600 border border-neutral-200">
                                        {{ $card->reference_type }}
                                    </span>
                                @endif
                                <div class="text-[10px] text-neutral-400 font-mono mt-0.5">
                                    Ref #{{ $card->reference_id ?? '-' }}
                                </div>
                            </td>

                            <!-- 4. Masuk (+) -->
                            <td class="py-3 px-4 text-right tabular-nums">
                                @if((float)$card->quantity_in > 0)
                                    <span class="font-bold text-emerald-700 font-mono">
                                        +{{ number_format($card->quantity_in, 2, ',', '.') }}
                                    </span>
                                    <span class="text-[10px] text-neutral-400 font-mono uppercase">{{ $card->product?->unit ?? 'pcs' }}</span>
                                @else
                                    <span class="text-neutral-300 font-mono">-</span>
                                @endif
                            </td>

                            <!-- 5. Keluar (-) -->
                            <td class="py-3 px-4 text-right tabular-nums">
                                @if((float)$card->quantity_out > 0)
                                    <span class="font-bold text-rose-600 font-mono">
                                        -{{ number_format($card->quantity_out, 2, ',', '.') }}
                                    </span>
                                    <span class="text-[10px] text-neutral-400 font-mono uppercase">{{ $card->product?->unit ?? 'pcs' }}</span>
                                @else
                                    <span class="text-neutral-300 font-mono">-</span>
                                @endif
                            </td>

                            <!-- 6. Saldo Akhir -->
                            <td class="py-3 px-4 text-right tabular-nums">
                                <span class="font-bold text-neutral-950 font-mono">
                                    {{ number_format($card->balance_after, 2, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-neutral-400 font-mono uppercase">{{ $card->product?->unit ?? 'pcs' }}</span>
                            </td>

                            <!-- 7. HPP Satuan -->
                            <td class="py-3 px-4 text-right tabular-nums font-mono text-neutral-600">
                                Rp {{ number_format($card->cost_per_unit, 0, ',', '.') }}
                            </td>

                            <!-- 8. Catatan Mutasi -->
                            <td class="py-3 px-4 text-neutral-600 max-w-xs">
                                <div class="truncate text-[11px]" title="{{ $card->notes }}">
                                    {{ $card->notes ?: '-' }}
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-neutral-400 font-sans">
                                <p class="font-medium text-neutral-700">Belum ada catatan mutasi persediaan yang sesuai filter.</p>
                                <p class="text-xs text-neutral-400 mt-1">Setiap transaksi POS, penerimaan PO, roasting, atau surat jalan logistik akan tercatat otomatis di sini.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($stockCards->hasPages())
            <div class="px-5 py-3 border-t border-[#eaeaea] bg-white">
                {{ $stockCards->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

<script>
function inventoryApp() {
    return {
        activeTab: new URLSearchParams(window.location.search).get('tab') === 'ledger' || new URLSearchParams(window.location.search).has('stock_search') || new URLSearchParams(window.location.search).has('stock_page') ? 'ledger' : 'balances',
        alertMessage: '',
        alertType: 'success',
        selectedBranch: 'ALL',
        searchQuery: '',
        openSafetyModal: false,

        warehouseStocks: @json($warehouseStocks),
        branches: @json($branches),
        warehouses: @json($warehouses),
        products: @json($products),
        safetyAlerts: @json($safetyStockAlerts),

        get criticalAlertsCount() {
            return this.safetyAlerts.filter(a => a.level === 'CRITICAL').length;
        },

        get filteredStocks() {
            let list = this.warehouseStocks;
            if (this.selectedBranch !== 'ALL') {
                list = list.filter(s => s.warehouse && s.warehouse.branch_id == this.selectedBranch);
            }
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(s => {
                    const name = s.product?.name?.toLowerCase() || '';
                    const sku = s.product?.sku?.toLowerCase() || '';
                    const wh = s.warehouse?.name?.toLowerCase() || '';
                    return name.includes(q) || sku.includes(q) || wh.includes(q);
                });
            }
            return list;
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
        }
    };
}
</script>
@endsection
