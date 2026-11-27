@extends('layouts.app')

@section('title', 'Roastery Analytics - KAFEINA')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="dashboardApp()">
    
    <!-- DASHBOARD ACTION TOOLBAR (Balanced & Simple Design) -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <!-- Left: Search input with / shortcut & Spotlight Live Results -->
        <div class="relative w-full sm:w-80 md:w-96" @click.away="searchOpen = false">
            <div class="flex items-center w-full px-3 py-2 rounded-xl bg-white border border-[#eaeaea] text-neutral-500 text-xs shadow-none hover:border-[#ccc] focus-within:border-black transition-colors font-sans"
                 :class="searchOpen ? 'border-black ring-1 ring-black/5' : ''">
                <svg class="w-4 h-4 text-neutral-400 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" 
                       x-model="searchQuery"
                       @focus="searchOpen = true"
                       @input="searchOpen = true"
                       @keydown.escape="searchOpen = false; $el.blur()"
                       @keydown.down.prevent="navigateSearch(1)"
                       @keydown.up.prevent="navigateSearch(-1)"
                       @keydown.enter.prevent="selectActiveSearchResult()"
                       id="page-search-input" data-page-search placeholder="Cari batch sangrai, biji mentah, atau gerai..." 
                       class="bg-transparent border-none text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none flex-1 font-sans">
                <template x-if="!searchQuery">
                    <kbd class="px-1.5 py-0.5 rounded bg-neutral-100 border border-neutral-200 text-[10px] font-sans font-medium text-neutral-400">/</kbd>
                </template>
                <button x-show="searchQuery" 
                        @click="searchQuery = ''; searchOpen = false; $el.previousElementSibling.focus()"
                        class="text-neutral-400 hover:text-neutral-900 text-xs px-1 cursor-pointer font-mono" type="button">
                    &times;
                </button>
            </div>

            <!-- SPOTLIGHT COMMAND-PALETTE DROPDOWN PANEL -->
            <div x-show="searchOpen"
                 x-transition:enter="transition-[opacity,transform] duration-150 ease-out"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-[0.98]"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition-[opacity,transform] duration-100 ease-out"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
                 class="absolute left-0 right-0 top-full mt-2 z-50 bg-white/95 backdrop-blur-xl border border-neutral-200/90 rounded-2xl shadow-xl overflow-hidden text-xs max-h-[360px] overflow-y-auto divide-y divide-neutral-100"
                 style="display: none;">
                 
                <!-- WHEN QUERY IS EMPTY: QUICK SUGGESTIONS -->
                <template x-if="!searchQuery.trim()">
                    <div class="p-3">
                        <div class="text-[10px] font-mono font-semibold uppercase tracking-wider text-neutral-400 px-2 mb-2">
                            Pencarian Cepat & Filter Instan
                        </div>
                        <div class="space-y-1">
                            <button type="button" @click="setSearch('Gayo Honey')" class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-left hover:bg-neutral-100 transition-colors text-xs text-neutral-700 group cursor-pointer">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>Green Beans Aceh Gayo Honey</span>
                                </span>
                                <span class="text-[10px] font-mono text-neutral-400 group-hover:text-neutral-600">Biji Mentah ROP</span>
                            </button>
                            <button type="button" @click="setSearch('Senopati')" class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-left hover:bg-neutral-100 transition-colors text-xs text-neutral-700 group cursor-pointer">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                    <span>Gerai Kafeina Flagship Senopati</span>
                                </span>
                                <span class="text-[10px] font-mono text-neutral-400 group-hover:text-neutral-600">Cabang Retail</span>
                            </button>
                            <button type="button" @click="setSearch('WO-202611-001')" class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-left hover:bg-neutral-100 transition-colors text-xs text-neutral-700 group cursor-pointer">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Batch Sangrai Giesen W15A (WO-001)</span>
                                </span>
                                <span class="text-[10px] font-mono text-neutral-400 group-hover:text-neutral-600">Work Order</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- WHEN QUERY EXISTS: LIVE MATCHES -->
                <template x-if="searchQuery.trim()">
                    <div>
                        <!-- Summary Header -->
                        <div class="px-3.5 py-2 bg-neutral-50/70 border-b border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500 font-medium">
                            <span>Hasil pencarian "<strong class="text-neutral-900" x-text="searchQuery"></strong>"</span>
                            <span class="font-mono text-[10px] text-neutral-400" x-text="spotlightResults.length + ' item ditemukan'"></span>
                        </div>

                        <!-- Result list -->
                        <div class="p-1.5 space-y-0.5">
                            <template x-for="(res, idx) in spotlightResults" :key="res.id">
                                <a :href="res.url" 
                                   @click="handleResultClick(res, $event)"
                                   :class="activeSearchIdx === idx ? 'bg-neutral-100 text-black' : 'text-neutral-700 hover:bg-neutral-50'"
                                   class="flex items-center justify-between px-3 py-2 rounded-xl transition-colors cursor-pointer group">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0"
                                             :class="{
                                                 'bg-orange-100 text-orange-700': res.type === 'batch',
                                                 'bg-amber-100 text-amber-700': res.type === 'stock',
                                                 'bg-neutral-100 text-neutral-800': res.type === 'branch',
                                                 'bg-blue-100 text-blue-700': res.type === 'menu'
                                             }">
                                            <span class="text-[10px] font-mono font-bold" x-text="res.badge"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-semibold text-neutral-900 truncate" x-text="res.title"></div>
                                            <div class="text-[10px] text-neutral-400 truncate" x-text="res.subtitle"></div>
                                        </div>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-neutral-300 group-hover:text-black shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </template>

                            <template x-if="spotlightResults.length === 0">
                                <div class="py-6 text-center text-xs text-neutral-400">
                                    Tidak ada data yang cocok dengan "<span x-text="searchQuery"></span>".
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

            </div>
        </div>

        <!-- Right: Time Range Filter (Disimpan di kanan ujung) -->
        <div class="flex items-center justify-end">
            <!-- Period Segmented Control (1 baris simpel) -->
            <div class="flex items-center p-0.5 rounded-lg bg-neutral-100 border border-neutral-200 text-xs">
                <button @click="periodFilter = 'today'"
                        :class="periodFilter === 'today' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer font-sans whitespace-nowrap">
                    Hari Ini
                </button>
                <button @click="periodFilter = '7d'"
                        :class="periodFilter === '7d' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer font-sans whitespace-nowrap">
                    7 Hari
                </button>
                <button @click="periodFilter = '30d'"
                        :class="periodFilter === '30d' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                        class="px-2.5 py-1 rounded-md transition-colors cursor-pointer font-sans whitespace-nowrap">
                    30 Hari
                </button>
            </div>
        </div>
    </div>

    <!-- BENTO KPI CARDS (Simple High-Craft Design with Specialty Coffee Palette) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        
        <!-- CARD 1: Omzet Penjualan Konsolidasi (Warm Coffee Amber Palette) -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-amber-300 transition-colors flex flex-col justify-between font-sans group">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <span class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider font-sans" x-text="'Omzet Penjualan' + (periodFilter === '7d' ? ' (7 Hari)' : (periodFilter === '30d' ? ' (30 Hari)' : ' (Hari Ini)'))">Omzet Penjualan</span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 bg-amber-50/80 border border-amber-200/60 px-1.5 py-0.5 rounded-md font-sans whitespace-nowrap shrink-0">
                        <svg class="w-3 h-3 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        <span x-text="currentKpi.growth">+14.2%</span>
                    </span>
                </div>
                <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight">
                    Rp <span x-text="formatNumber(currentKpi.revenue)">{{ number_format($kpi['daily_revenue'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Micro Area Sparkline (Warm Amber Gradient: Sen - Min) -->
            <div class="mt-3.5 mb-2.5">
                <div class="h-11 w-full relative">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 160 44" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="spark-grad-revenue" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.25" />
                                <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.0" />
                            </linearGradient>
                        </defs>
                        <!-- Area Fill -->
                        <path :d="revenueSpark.area" fill="url(#spark-grad-revenue)" />
                        <!-- Line Stroke -->
                        <path :d="revenueSpark.path" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <!-- Current Day Dot -->
                        <circle :cx="revenueSpark.lastPoint?.x || 148" :cy="revenueSpark.lastPoint?.y || 20" r="3.5" fill="#d97706" stroke="#ffffff" stroke-width="1.75" />
                    </svg>
                </div>
                <div class="flex items-center justify-between text-[10px] font-sans text-neutral-400 mt-1 px-1">
                    <template x-for="(lbl, idx) in sparkLabels" :key="idx">
                        <span :class="idx === sparkLabels.length - 1 ? 'font-semibold text-amber-700' : ''" x-text="lbl"></span>
                    </template>
                </div>
            </div>

            <!-- Dual Ratio Breakdown (Tunai vs QRIS) -->
            <div class="pt-3 border-t border-[#f5f5f5]">
                <div class="w-full h-2 bg-neutral-100 rounded-full border border-neutral-200/90 overflow-hidden flex mb-2 shadow-2xs">
                    <div class="h-full bg-neutral-300 transition-all duration-300" :style="'width: ' + ((currentKpi.cash_revenue / (currentKpi.revenue || 1)) * 100) + '%;'" title="Tunai"></div>
                    <div class="h-full bg-amber-500 transition-all duration-300" :style="'width: ' + ((currentKpi.qris_revenue / (currentKpi.revenue || 1)) * 100) + '%;'" title="QRIS"></div>
                </div>
                <div class="flex items-center justify-between gap-2 text-[11px] font-sans tabular-nums text-neutral-500 whitespace-nowrap"
                     :title="'Tunai: Rp ' + formatNumber(currentKpi.cash_revenue) + ' | QRIS: Rp ' + formatNumber(currentKpi.qris_revenue)">
                    <span class="truncate">Tunai: <b class="text-neutral-800 font-semibold" x-text="formatShortIdr(currentKpi.cash_revenue)">Rp 0</b></span>
                    <span class="truncate">QRIS: <b class="text-amber-700 font-semibold" x-text="formatShortIdr(currentKpi.qris_revenue)">Rp 0</b></span>
                </div>
            </div>
        </div>

        <!-- CARD 2: Volume Transaksi & Struk POS (Indigo Digital POS Palette) -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-indigo-300 transition-colors flex flex-col justify-between font-sans group">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <span class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider font-sans" x-text="'Volume Struk' + (periodFilter === '7d' ? ' (7 Hari)' : (periodFilter === '30d' ? ' (30 Hari)' : ' (Hari Ini)'))">Volume Struk POS</span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-700 bg-indigo-50 border border-indigo-200/60 px-1.5 py-0.5 rounded-md font-sans whitespace-nowrap shrink-0">
                        <svg class="w-3 h-3 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span x-text="currentKpi.orders_badge">Sab: 548</span>
                    </span>
                </div>
                <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight">
                    <span x-text="formatNumber(currentKpi.orders)">{{ $kpi['transaction_count'] }}</span> <span class="text-sm font-sans font-normal text-neutral-400">Struk</span>
                </div>
            </div>

            <!-- Mini 7-Day Bar Chart (Indigo Accent) -->
            <div class="mt-3.5 mb-2.5">
                <div class="h-11 w-full relative flex items-end justify-between px-1.5 pt-1">
                    <template x-for="bar in ordersBars" :key="bar.day">
                        <div class="flex flex-col items-center gap-1 group/bar flex-1">
                            <div class="w-2.5 sm:w-3 rounded-t-xs transition-colors duration-150"
                                 :class="bar.isCurrent ? 'bg-indigo-600' : (bar.day === 'Sab' ? 'bg-indigo-400' : 'bg-indigo-100 group-hover/bar:bg-indigo-300')"
                                 :style="'height: ' + bar.h + 'px;'"
                                 :title="bar.day + ': ' + bar.val + ' struk'"></div>
                        </div>
                    </template>
                </div>
                <div class="flex items-center justify-between text-[10px] font-sans text-neutral-400 mt-1 px-1">
                    <template x-for="(lbl, idx) in sparkLabels" :key="idx">
                        <span :class="idx === sparkLabels.length - 1 ? 'font-semibold text-indigo-700' : ''" x-text="lbl"></span>
                    </template>
                </div>
            </div>

            <!-- Footer Stats -->
            <div class="pt-3 border-t border-[#f5f5f5] flex items-center justify-between text-[11px] font-sans tabular-nums text-neutral-500">
                <span>AOV Rata-Rata:</span>
                <span class="text-neutral-900 font-semibold">Rp <span x-text="formatNumber(currentKpi.aov)">{{ number_format($kpi['average_order_value'], 0, ',', '.') }}</span></span>
            </div>
        </div>

        <!-- CARD 3: Gross Profit Margin Riil (Emerald Sage Palette) -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-emerald-300 transition-colors flex flex-col justify-between font-sans group">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <span class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider font-sans" x-text="'Gross Profit Margin' + (periodFilter === '7d' ? ' (7 Hari)' : (periodFilter === '30d' ? ' (30 Hari)' : ' (Hari Ini)'))">Gross Profit Margin</span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50/80 border border-emerald-200/60 px-1.5 py-0.5 rounded-md font-sans whitespace-nowrap shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                        <span>>65% Target</span>
                    </span>
                </div>
                <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight">
                    <span x-text="currentKpi.margin">{{ $kpi['gross_profit_margin'] }}</span>%
                </div>
            </div>

            <!-- Benchmark Target Sparkline (With 65% Target Dashed Line) -->
            <div class="mt-3.5 mb-2.5">
                <div class="h-11 w-full relative">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 160 44" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="spark-grad-margin" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#059669" stop-opacity="0.18" />
                                <stop offset="100%" stop-color="#059669" stop-opacity="0.0" />
                            </linearGradient>
                        </defs>
                        <!-- Target 65% Dashed Baseline -->
                        <line x1="6" :y1="marginSpark.targetY || 32" x2="154" :y2="marginSpark.targetY || 32" stroke="#a7f3d0" stroke-width="1" stroke-dasharray="3,3" />
                        <!-- Area Fill -->
                        <path :d="marginSpark.area" fill="url(#spark-grad-margin)" />
                        <!-- Trend Curve -->
                        <path :d="marginSpark.path" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <!-- Current Dot -->
                        <circle :cx="marginSpark.lastPoint?.x || 148" :cy="marginSpark.lastPoint?.y || 16" r="3.5" fill="#059669" stroke="#ffffff" stroke-width="1.75" />
                    </svg>
                </div>
                <div class="flex items-center justify-between text-[10px] font-sans text-neutral-400 mt-1 px-1">
                    <template x-for="(lbl, idx) in sparkLabels" :key="idx">
                        <span :class="idx === sparkLabels.length - 1 ? 'font-semibold text-emerald-700' : ''" x-text="lbl"></span>
                    </template>
                </div>
            </div>

            <!-- Footer Stats -->
            <div class="pt-3 border-t border-[#f5f5f5] flex items-center justify-between text-[11px] font-sans tabular-nums text-neutral-500">
                <span>Laba Kotor:</span>
                <span class="text-neutral-900 font-semibold">Rp <span x-text="formatNumber(currentKpi.profit)">{{ number_format($kpi['gross_profit_amount'], 0, ',', '.') }}</span></span>
            </div>
        </div>

        <!-- CARD 4: Roastery Yield Loss & Susut Sangrai -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors flex flex-col justify-between font-sans group">
            <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <span class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider font-sans" x-text="'Rata-Rata Susut' + (periodFilter === '7d' ? ' (7 Hari)' : (periodFilter === '30d' ? ' (30 Hari)' : ' (Hari Ini)'))">Rata-Rata Susut Sangrai</span>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-emerald-700 bg-emerald-50/80 border border-emerald-200/60 px-1.5 py-0.5 rounded-md font-sans whitespace-nowrap shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                        <span>Optimal</span>
                    </span>
                </div>
                <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight">
                    <span x-text="currentKpi.yield_loss">{{ $kpi['average_yield_loss_pct'] }}</span>%
                </div>
            </div>

            <!-- Tolerance Band Spectrum Bar (Sweet Spot 14% - 18%) -->
            <div class="mt-3.5 mb-2.5">
                <div class="h-11 w-full flex flex-col justify-center">
                    <!-- Spectrum Scale Bar -->
                    <div class="relative w-full h-3 rounded-full bg-neutral-100 overflow-hidden flex items-center border border-neutral-200/60">
                        <!-- Zone 1: <14% Underdeveloped -->
                        <div class="h-full bg-amber-100/70 border-r border-amber-200/60" style="width: 33.3%;" title="Underdeveloped (<14%)"></div>
                        <!-- Zone 2: 14%-18% Sweet Spot Optimal (Shaded Sage) -->
                        <div class="h-full bg-emerald-100 border-r border-emerald-300 flex items-center justify-center" style="width: 33.3%;" title="Zona Aman Optimal (14-18%)">
                            <span class="text-[8px] font-bold text-emerald-800 tracking-tight font-sans select-none">14–18%</span>
                        </div>
                        <!-- Zone 3: >18% Over-roasted -->
                        <div class="h-full bg-rose-100/70" style="width: 33.4%;" title="Over-roasted (>18%)"></div>
                    </div>

                    <!-- Current Indicator Pin -->
                    <div class="relative w-full h-4 mt-1">
                        <div class="absolute -top-1 -translate-x-1/2 flex flex-col items-center"
                             :style="'left: ' + yieldBandLeft + '%;'">
                            <div class="w-2.5 h-2.5 rounded-full bg-neutral-900 border-2 border-white shadow-xs"></div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[10px] font-sans text-neutral-400 mt-1 px-1">
                    <span>10% (Mentah)</span>
                    <span class="text-emerald-700 font-medium">14–18% Optimal</span>
                    <span>22% (Gosong)</span>
                </div>
            </div>

            <!-- Footer Stats -->
            <div class="pt-3 border-t border-[#f5f5f5] flex items-center justify-between text-[11px] font-sans tabular-nums text-neutral-500">
                <span>Yield Rate: <b class="text-neutral-800" x-text="currentKpi.yield_rate">{{ $kpi['average_roast_yield_pct'] }}%</b></span>
                <span>Toleransi: <b class="text-neutral-800">14–18%</b></span>
            </div>
        </div>

    </div>

    <!-- UNIFIED INTERACTIVE TIME-SERIES CHART PANEL (Specialty Palette & Rich Hover Popover) -->
    <div class="bg-white border border-[#eaeaea] rounded-xl p-5 mb-8 shadow-none font-sans" @mouseleave="heroHoverIdx = null">
        <!-- Header Controls: Title & Metric Tabs & Style Toggle -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#eaeaea]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-neutral-100 border border-neutral-200/60 flex items-center justify-center text-neutral-700 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-neutral-900 font-sans">Tren Performa & Analisis Waktu</h3>
                    <p class="text-xs text-neutral-500 font-sans mt-0.5">Visualisasi pergerakan omzet, ritme transaksi, dan jam sibuk kedai</p>
                </div>
            </div>

            <div class="flex items-center flex-wrap gap-2">
                <!-- Metric Selector Tabs (Neutral White Segmented Switch) -->
                <div class="flex items-center p-0.5 rounded-lg bg-neutral-100 border border-neutral-200 text-xs">
                    <button @click="heroMetric = 'revenue'"
                            :class="heroMetric === 'revenue' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                            class="px-3 py-1.5 rounded-md transition-colors font-sans cursor-pointer"
                            x-text="periodFilter === 'today' ? 'Omzet Hari Ini' : (periodFilter === '7d' ? 'Omzet 7 Hari' : 'Omzet 30 Hari')">
                        Omzet 7 Hari
                    </button>
                    <button @click="heroMetric = 'orders'"
                            :class="heroMetric === 'orders' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                            class="px-3 py-1.5 rounded-md transition-colors font-sans cursor-pointer"
                            x-text="periodFilter === 'today' ? 'Volume Struk (Hari Ini)' : (periodFilter === '7d' ? 'Volume Struk' : 'Volume Struk (30 Hari)')">
                        Volume Struk
                    </button>
                    <button @click="heroMetric = 'hourly'"
                            :class="heroMetric === 'hourly' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                            class="px-3 py-1.5 rounded-md transition-colors font-sans cursor-pointer"
                            x-text="periodFilter === '30d' ? 'Distribusi Jam Sibuk' : 'Jam Sibuk (Hari Ini)'">
                        Jam Sibuk (Hari Ini)
                    </button>
                </div>

                <!-- Chart Style Toggle: Line vs Bar -->
                <div class="flex items-center p-0.5 rounded-lg bg-neutral-100 border border-neutral-200 text-xs">
                    <button @click="heroStyle = 'line'"
                            :class="heroStyle === 'line' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                            class="px-2.5 py-1.5 rounded-md transition-colors cursor-pointer" title="Line Chart">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19l6-6 4 4 6-9"/>
                        </svg>
                    </button>
                    <button @click="heroStyle = 'bar'"
                            :class="heroStyle === 'bar' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                            class="px-2.5 py-1.5 rounded-md transition-colors cursor-pointer" title="Bar Chart">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Active Chart Canvas Area -->
        <div class="pt-6 pb-2">
            <div class="h-48 sm:h-56 w-full relative select-none">
                <!-- Background Gridlines -->
                <div class="absolute inset-0 flex flex-col justify-between pointer-events-none text-[10px] text-neutral-400 font-mono">
                    <div class="border-b border-dashed border-neutral-100 pb-1 flex justify-between">
                        <span x-text="heroChartMeta.maxLabel"></span>
                    </div>
                    <div class="border-b border-dashed border-neutral-100 pb-1 flex justify-between">
                        <span x-text="heroChartMeta.midLabel"></span>
                    </div>
                    <div class="border-b border-neutral-200 pb-1 flex justify-between">
                        <span>0</span>
                    </div>
                </div>

                <!-- LINE CHART VIEW -->
                <div x-show="heroStyle === 'line'" class="w-full h-full relative">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 700 160" preserveAspectRatio="none">
                        <defs>
                            <!-- Warm Amber Gradient for Revenue -->
                            <linearGradient id="hero-grad-revenue" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.30" />
                                <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.0" />
                            </linearGradient>
                            <!-- Indigo Gradient for Orders -->
                            <linearGradient id="hero-grad-orders" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#6366f1" stop-opacity="0.28" />
                                <stop offset="100%" stop-color="#6366f1" stop-opacity="0.0" />
                            </linearGradient>
                            <!-- Terracotta Gradient for Hourly -->
                            <linearGradient id="hero-grad-hourly" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#ea580c" stop-opacity="0.28" />
                                <stop offset="100%" stop-color="#ea580c" stop-opacity="0.0" />
                            </linearGradient>
                        </defs>

                        <!-- Area Fill with Active Theme Gradient -->
                        <path :d="heroChartData.area" :fill="'url(#' + activeTheme.gradId + ')'" />
                        
                        <!-- Line Stroke with Active Theme Color -->
                        <path :d="heroChartData.path" fill="none" :stroke="activeTheme.color" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

                        <!-- Vertical Guideline / Crosshair on Hover -->
                        <line x-show="heroHoverIdx !== null && activeHoverPoint" 
                              :x1="activeHoverPoint ? activeHoverPoint.x : 0" y1="0" 
                              :x2="activeHoverPoint ? activeHoverPoint.x : 0" y2="160" 
                              :stroke="activeTheme.color" 
                              stroke-width="1.5" 
                              stroke-dasharray="3,3" 
                              opacity="0.6" />

                        <!-- Active Point Outer Halo when Hovered -->
                        <circle x-show="heroHoverIdx !== null && activeHoverPoint"
                                :cx="activeHoverPoint ? activeHoverPoint.x : 0" :cy="activeHoverPoint ? activeHoverPoint.y : 0" r="8" 
                                :fill="activeTheme.color" 
                                fill-opacity="0.25" 
                                class="animate-ping" />

                        <!-- Active Point Circle -->
                        <circle x-show="heroHoverIdx !== null && activeHoverPoint"
                                :cx="activeHoverPoint ? activeHoverPoint.x : 0" :cy="activeHoverPoint ? activeHoverPoint.y : 0" 
                                r="5" 
                                :fill="activeTheme.color" 
                                stroke="#ffffff" 
                                stroke-width="2.5" />
                    </svg>

                    <!-- HTML Hit-Testing Overlay Columns (100% Alpine compatible) -->
                    <div class="absolute inset-0 flex items-stretch z-10" @mouseleave="heroHoverIdx = null">
                        <template x-for="(pt, idx) in heroChartData.points" :key="'hit-col-' + idx">
                            <div class="flex-1 h-full cursor-pointer relative"
                                 @mouseenter="heroHoverIdx = idx" 
                                 @mousemove="heroHoverIdx = idx">
                            </div>
                        </template>
                    </div>
                </div>

                <!-- BAR CHART VIEW -->
                <div x-show="heroStyle === 'bar'" class="w-full h-full flex items-end justify-between px-3 sm:px-6 relative z-10" @mouseleave="heroHoverIdx = null">
                    <template x-for="(bar, idx) in heroBarData" :key="'bar-' + idx">
                        <div class="flex-1 flex flex-col items-center justify-end h-full group/bar cursor-pointer relative"
                             @mouseenter="heroHoverIdx = idx"
                             @mousemove="heroHoverIdx = idx">
                            <div class="w-6 sm:w-8 md:w-10 rounded-t-md transition-all duration-150"
                                 :class="heroHoverIdx === idx || bar.isCurrent ? activeTheme.barActive : activeTheme.barInactive"
                                 :style="'height: ' + bar.hPercent + '%;'"></div>
                        </div>
                    </template>
                </div>

                <!-- RICH INFORMATIVE HOVER POPOVER CARD -->
                <div x-show="heroHoverIdx !== null && activeHoverPoint" 
                     x-transition:enter="transition-opacity duration-150 ease-out"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="absolute z-30 pointer-events-none top-2 bg-white/95 backdrop-blur-md border border-[#eaeaea] text-neutral-900 rounded-xl p-3 shadow-xl font-sans w-56 sm:w-64 text-left"
                     :style="popoverPositionStyle">
                    
                    <!-- Card Header: Full Day/Time & Status Pill -->
                    <div class="flex items-center justify-between gap-1 pb-1.5 mb-1.5 border-b border-neutral-100">
                        <span class="text-[11px] font-bold text-neutral-800 font-sans truncate" x-text="activeHoverPoint?.fullTitle"></span>
                        <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded whitespace-nowrap"
                              :class="activeTheme.badgeClass"
                              x-text="activeHoverPoint?.status"></span>
                    </div>

                    <!-- Main Value Highlight -->
                    <div class="flex items-baseline justify-between">
                        <span class="text-[11px] text-neutral-500 font-medium" x-text="activeTheme.label"></span>
                        <span class="text-base font-bold tabular-nums tracking-tight font-sans"
                              :class="activeTheme.textClass"
                              x-text="activeHoverPoint?.displayVal"></span>
                    </div>

                    <!-- Contextual Micro Breakdown List -->
                    <div class="mt-2 pt-1.5 border-t border-dashed border-neutral-100 space-y-1 text-[10px] font-sans">
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-500" x-text="activeHoverPoint?.detail1Label"></span>
                            <span class="font-medium text-neutral-800" x-text="activeHoverPoint?.detail1Val"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-500" x-text="activeHoverPoint?.detail2Label"></span>
                            <span class="font-medium text-neutral-800" x-text="activeHoverPoint?.detail2Val"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- X-Axis Labels -->
            <div class="flex items-center justify-between text-[11px] font-sans text-neutral-500 mt-2 px-2 sm:px-6">
                <template x-for="(item, idx) in heroXLabels" :key="idx">
                    <span :class="heroHoverIdx === idx ? activeTheme.textClass + ' font-bold' : ''"
                          class="transition-colors"
                          x-text="item"></span>
                </template>
            </div>
        </div>

        <!-- Bottom Insights Summary Strip -->
        <div class="mt-4 pt-3 border-t border-[#f5f5f5] flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs font-sans text-neutral-600">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full" :class="activeTheme.bgClass"></span>
                <span class="font-medium text-neutral-900">Konteks Analisis:</span>
                <span x-text="heroMetricSummary"></span>
            </div>
            <div class="flex items-center gap-4 tabular-nums">
                <span>Puncak: <b class="text-neutral-900" x-text="heroPeakStat"></b></span>
                <span>Rata-Rata: <b class="text-neutral-900" x-text="heroAvgStat"></b></span>
            </div>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN SECTION (Vercel Architecture) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start font-sans">
        
        <!-- ============================================== -->
        <!-- KOLOM KIRI: Safety Stock Alerts & Kategori     -->
        <!-- ============================================== -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- SECTION: Safety Stock Alerts -->
            <div>
                <div class="flex items-center justify-between mb-2.5">
                    <h2 class="text-sm font-semibold text-neutral-900 font-sans">Safety Stock Alerts</h2>
                    <span class="text-xs font-sans font-medium"
                          :class="stockAlerts.length > 0 ? 'text-rose-600' : 'text-emerald-600'"
                          x-text="stockAlerts.length > 0 ? (stockAlerts.length + ' Item Kritis') : '0 Item Kritis'"></span>
                </div>

                <div class="bg-white border border-[#eaeaea] rounded-xl p-5 shadow-none hover:border-[#ccc] transition-colors font-sans">
                    <div class="flex items-center gap-3 pb-3 border-b border-[#f0f0f0]">
                        <div class="w-8 h-8 rounded-full border flex items-center justify-center shrink-0"
                             :class="stockAlerts.length > 0 ? 'border-neutral-200 bg-neutral-50 text-neutral-700' : 'border-emerald-200 bg-emerald-50 text-emerald-600'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="stockAlerts.length > 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                <path x-show="stockAlerts.length === 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-neutral-900 font-sans"
                                 x-text="stockAlerts.length > 0 ? 'Peringatan Reorder Point (ROP)' : 'Seluruh Stok Aman (Di Atas ROP)'"></div>
                            <div class="text-[11px] text-neutral-500 font-sans"
                                 x-text="stockAlerts.length > 0 ? 'Stok bahan baku di bawah batas aman minimum' : 'Tidak ada bahan baku di bawah ambang batas minimum saat ini'"></div>
                        </div>
                    </div>

                    <!-- Empty State bila tidak ada stok kritis -->
                    <div x-show="stockAlerts.length === 0" class="mt-3 py-4 px-3 rounded-xl bg-neutral-50 border border-dashed border-neutral-200 text-center">
                        <div class="text-xs font-medium text-neutral-700">Semua stok gudang berada pada level aman</div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">Tombol PO Konsolidasi otomatis aktif saat ada bahan baku di bawah batas ROP.</div>
                    </div>

                    <!-- List of Critical Stock Items (Simple Clean Rows) -->
                    <div x-show="stockAlerts.length > 0" class="mt-2 divide-y divide-neutral-100 font-sans">
                        <template x-for="(item, sIdx) in stockAlerts" :key="(item.sku || item.item_name) + '-' + item.warehouse + '-' + sIdx">
                            <div class="py-2.5 first:pt-1 last:pb-1 group/item">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-medium text-neutral-900 truncate" x-text="item.item_name"></div>
                                        <div class="text-[11px] text-neutral-400 truncate mt-0.5" x-text="item.warehouse"></div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-xs font-semibold tabular-nums font-mono"
                                              :class="item.percent <= 30 ? 'text-rose-600' : 'text-amber-600'"
                                              x-text="item.percent + '%'"></div>
                                        <div class="text-[11px] tabular-nums text-neutral-500 mt-0.5">
                                            <span class="font-medium text-neutral-800" x-text="item.current_stock"></span>
                                            <span class="text-neutral-400">/</span>
                                            <span x-text="item.min_stock"></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Minimal Outlined Capsule Depletion Track (Full Height, Edge-to-Edge) -->
                                <div class="w-full h-2.5 bg-neutral-100 rounded-full border border-neutral-200/90 overflow-hidden flex mt-1.5 shadow-2xs">
                                    <div class="h-full rounded-full transition-[width] duration-300 ease-out"
                                         :class="item.percent <= 30 ? 'bg-rose-500' : 'bg-amber-500'"
                                         :style="'width: ' + item.percent + '%;'"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- TOMBOL RESMI BUAT PO KONSOLIDASI (DINONAKTIFKAN BILA TIDAK ADA ITEM KRITIS) -->
                    <button @click="createConsolidatedPo()" 
                            :disabled="poLoading || purchasableCriticalCount === 0"
                            class="mt-3.5 w-full py-2.5 px-3 rounded-xl text-xs font-semibold transition-[background-color,color,transform] duration-150 ease-out flex items-center justify-center gap-2 text-center font-sans shadow-xs"
                            :class="purchasableCriticalCount > 0 ? 'bg-neutral-900 hover:bg-neutral-800 disabled:bg-neutral-400 text-white cursor-pointer active:scale-[0.99]' : 'bg-neutral-100 text-neutral-400 border border-neutral-200 cursor-not-allowed shadow-none'">
                        <template x-if="poLoading">
                            <svg class="animate-spin h-3.5 w-3.5 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <template x-if="!poLoading && !poCreated && purchasableCriticalCount > 0">
                            <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </template>
                        <template x-if="poCreated">
                            <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <span x-text="poLoading ? 'Menerbitkan PO ke Vendor...' : (poCreated ? 'PO Berhasil Diterbitkan ✓' : (purchasableCriticalCount > 0 ? ('Buat PO Konsolidasi (' + purchasableCriticalCount + ' Item)') : (stockAlerts.length > 0 ? 'Item Kritis Internal — Jadwalkan Roastery' : 'Semua Stok Aman — PO Tidak Diperlukan')))"></span>
                    </button>
                </div>
            </div>

            <!-- SECTION: Kontribusi Kategori Penjualan -->
            <div>
                <div class="flex items-center justify-between mb-2.5">
                    <h2 class="text-sm font-semibold text-neutral-900 font-sans">Kontribusi Kategori</h2>
                    <span class="text-xs text-neutral-500 font-sans">5 Lini Produk</span>
                </div>

                <div class="bg-white border border-[#eaeaea] rounded-xl p-5 shadow-none hover:border-[#ccc] transition-colors font-sans">
                    <div class="space-y-3.5">
                        <template x-for="cat in categoryShare" :key="cat.name">
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1.5 font-sans">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-2 h-2 rounded-full shrink-0 shadow-2xs" 
                                              :style="'background-color: ' + cat.color"></span>
                                        <span class="font-medium text-neutral-800 truncate" x-text="cat.name"></span>
                                    </div>
                                    <div class="tabular-nums whitespace-nowrap text-right pl-2">
                                        <span class="font-bold text-neutral-900" x-text="cat.pct + '%'"></span>
                                        <span class="text-neutral-400 font-normal text-[11px] ml-1" x-text="'(Rp ' + formatNumber(cat.revenue) + ')'"></span>
                                    </div>
                                </div>
                                <!-- Refined Outlined Capsule Track (Full Height, Edge-to-Edge) -->
                                <div class="w-full h-2.5 bg-neutral-100 rounded-full border border-neutral-200/90 overflow-hidden flex shadow-2xs">
                                    <div class="h-full rounded-full transition-all duration-300"
                                         :style="'width: ' + cat.pct + '%; background-color: ' + cat.color + ';'">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#f5f5f5] flex items-center justify-between text-[11px] font-sans text-neutral-500">
                        <span>Peak Rush: 18:00 - 21:00 WIB</span>
                        <span class="font-semibold text-neutral-900">100% Online</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- ============================================== -->
        <!-- KOLOM KANAN: Gerai & Roasting Yield Monitor    -->
        <!-- ============================================== -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- SECTION: Monitoring Multi-Cabang (Gerai & Roastery Hub) -->
            <div>
                <div class="flex items-center justify-between mb-2.5">
                    <h2 class="text-sm font-semibold text-neutral-900 font-sans">Monitoring Multi-Cabang</h2>
                    <span class="text-xs text-neutral-500 font-sans">4 Unit Operasional</span>
                </div>

                <!-- SECTION: Monitoring Multi-Cabang (Clean Unified List) -->
                <div class="bg-white border border-[#eaeaea] rounded-xl overflow-hidden divide-y divide-[#eaeaea] shadow-none font-sans">
                    
                    <!-- 1. Central Roastery Hub & Lab -->
                    <div class="px-4 py-3 hover:bg-neutral-50/80 transition-colors cursor-pointer flex items-center justify-between gap-3"
                         @click="changeBranch('central')">
                        <div class="min-w-0">
                            <div class="font-medium text-xs sm:text-[13px] text-neutral-900 truncate">
                                Central Roastery & Lab <span class="text-neutral-400 font-normal text-xs">• Cilandak</span>
                            </div>
                            <div class="text-[11px] text-neutral-500 mt-0.5 truncate">
                                Stok: 4.250 kg Green • 380 kg Roasted
                            </div>
                        </div>

                        <div class="flex items-center gap-5 shrink-0">
                            <div class="text-right">
                                <div class="text-xs font-semibold text-neutral-900 tabular-nums">Rp 8.950.000</div>
                                <div class="text-[10px] text-neutral-400">24 Pengiriman</div>
                            </div>

                        </div>
                    </div>

                    <!-- 2. Flagship Store Senopati -->
                    <div class="px-4 py-3 hover:bg-neutral-50/80 transition-colors cursor-pointer flex items-center justify-between gap-3"
                         @click="changeBranch('senopati')">
                        <div class="min-w-0">
                            <div class="font-medium text-xs sm:text-[13px] text-neutral-900 truncate">
                                Flagship Store Senopati <span class="text-neutral-400 font-normal text-xs">• Jakarta Selatan</span>
                            </div>
                            <div class="text-[11px] text-neutral-500 mt-0.5 truncate">
                                28.5 kg Beans • Susu 18/50 pack
                            </div>
                        </div>

                        <div class="flex items-center gap-5 shrink-0">
                            <div class="text-right">
                                <div class="text-xs font-semibold text-neutral-900 tabular-nums">Rp 16.850.000</div>
                                <div class="text-[10px] text-neutral-400">214 Struk</div>
                            </div>

                        </div>
                    </div>

                    <!-- 3. Branch Braga Heritage -->
                    <div class="px-4 py-3 hover:bg-neutral-50/80 transition-colors cursor-pointer flex items-center justify-between gap-3"
                         @click="changeBranch('braga')">
                        <div class="min-w-0">
                            <div class="font-medium text-xs sm:text-[13px] text-neutral-900 truncate">
                                Branch Braga Heritage <span class="text-neutral-400 font-normal text-xs">• Bandung</span>
                            </div>
                            <div class="text-[11px] text-neutral-500 mt-0.5 truncate">
                                22.0 kg Beans • Susu 34/50 pack
                            </div>
                        </div>

                        <div class="flex items-center gap-5 shrink-0">
                            <div class="text-right">
                                <div class="text-xs font-semibold text-neutral-900 tabular-nums">Rp 12.400.000</div>
                                <div class="text-[10px] text-neutral-400">158 Struk</div>
                            </div>

                        </div>
                    </div>

                    <!-- 4. Branch Malioboro Sanctuary -->
                    <div class="px-4 py-3 hover:bg-neutral-50/80 transition-colors cursor-pointer flex items-center justify-between gap-3"
                         @click="changeBranch('malioboro')">
                        <div class="min-w-0">
                            <div class="font-medium text-xs sm:text-[13px] text-neutral-900 truncate">
                                Branch Malioboro Sanctuary <span class="text-neutral-400 font-normal text-xs">• Yogyakarta</span>
                            </div>
                            <div class="text-[11px] text-neutral-500 mt-0.5 truncate">
                                18.5 kg Beans • Susu 28/50 pack
                            </div>
                        </div>

                        <div class="flex items-center gap-5 shrink-0">
                            <div class="text-right">
                                <div class="text-xs font-semibold text-neutral-900 tabular-nums">Rp 9.200.000</div>
                                <div class="text-[10px] text-neutral-400">110 Struk</div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <!-- SECTION: Roasting Yield Monitor (Tabel Bisnis Ergonomis x Color Palette & Button Vercel) -->
            <div>
                <!-- Header with Title & Filter Switch (User-Friendly & Simple) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold text-neutral-900 font-sans">Roasting Yield Monitor</h2>
                        <span class="text-xs text-neutral-400 font-sans" x-text="filteredBatches.length + ' Batch'">5 Batch</span>
                    </div>

                    <!-- Clean Segmented Filter: Semua | Optimal | Perlu Review -->
                    <div class="flex items-center p-0.5 rounded-lg bg-neutral-100 border border-neutral-200 text-xs">
                        <button @click="batchFilter = 'ALL'"
                                :class="batchFilter === 'ALL' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer font-sans whitespace-nowrap">
                            Semua
                        </button>
                        <button @click="batchFilter = 'OPTIMAL'"
                                :class="batchFilter === 'OPTIMAL' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer font-sans whitespace-nowrap">
                            Optimal
                        </button>
                        <button @click="batchFilter = 'WARNING'"
                                :class="batchFilter === 'WARNING' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer font-sans whitespace-nowrap">
                            Perlu Review
                        </button>
                    </div>
                </div>

                <!-- Ergonomic ERP Table Card (100% Width Fit, Zero Horizontal Scroll) -->
                <div class="bg-white border border-[#eaeaea] rounded-xl overflow-hidden shadow-none font-sans">
                    <table class="w-full text-left font-sans border-collapse table-fixed">
                        <thead>
                            <tr class="bg-[#fafafa] border-b border-[#eaeaea] text-[11px] font-semibold text-neutral-500 uppercase tracking-wider">
                                <th class="px-3 py-2.5 w-[37%]">Batch & Profil</th>
                                <th class="px-2 py-2.5 w-[24%] whitespace-nowrap">Bobot (In → Out)</th>
                                <th class="px-2 py-2.5 w-[12%] whitespace-nowrap">Susut</th>
                                <th class="px-2 py-2.5 w-[14%] whitespace-nowrap">Status</th>
                                <th class="px-2.5 py-2.5 w-[12%] text-right whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#eaeaea]">
                            <template x-for="batch in filteredBatches" :key="batch.batch_code">
                                <tr class="hover:bg-[#fafafa]/80 transition-colors text-xs">
                                    <!-- Col 1: Bean Origin & Lot Code & Profile (Clean 2-Line Hierarchy) -->
                                    <td class="px-3 py-2.5 min-w-0" :title="batch.bean_origin + ' • ' + batch.roast_profile">
                                        <div class="font-medium text-neutral-900 truncate" x-text="batch.bean_origin"></div>
                                        <div class="text-[11px] text-neutral-400 font-sans mt-0.5 truncate flex items-center gap-1">
                                            <span class="font-mono text-neutral-500 shrink-0" x-text="'#' + batch.batch_code.replace('BATCH-ROAST-', 'LOT-')"></span>
                                            <span class="text-neutral-300 shrink-0">•</span>
                                            <span class="text-neutral-500 truncate" x-text="batch.roast_profile"></span>
                                        </div>
                                    </td>

                                    <!-- Col 2: Bobot Konversi (In -> Out) -->
                                    <td class="px-2 py-2.5 font-medium tabular-nums text-neutral-800 whitespace-nowrap text-xs" 
                                        x-text="batch.green_weight_kg.toFixed(1) + ' → ' + batch.roasted_weight_kg.toFixed(1) + ' kg'">
                                    </td>

                                    <!-- Col 3: Susut Loss % -->
                                    <td class="px-2 py-2.5 font-bold tabular-nums text-neutral-900 whitespace-nowrap text-xs" 
                                        x-text="batch.yield_loss_pct.toFixed(1) + '%'">
                                    </td>

                                    <!-- Col 4: Status Mutu (Minimal Dot + Text) -->
                                    <td class="px-2 py-2.5 whitespace-nowrap text-xs">
                                        <div class="inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                  :class="batch.status === 'OPTIMAL' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                            <span class="font-medium"
                                                  :class="batch.status === 'OPTIMAL' ? 'text-emerald-700' : 'text-amber-700'"
                                                  x-text="batch.status === 'OPTIMAL' ? 'Optimal' : 'Review'"></span>
                                        </div>
                                    </td>

                                    <!-- Col 5: Clean Minimal Action Link -->
                                    <td class="px-2.5 py-2.5 text-right whitespace-nowrap text-xs">
                                        <a href="/roastery" class="font-medium text-neutral-400 hover:text-black transition-colors">
                                            Detail →
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <!-- Formula Bar<!-- Formula Bar<!-- Formula Bar at Bottom of Table -->
                    <div class="bg-[#fafafa] px-4 py-2.5 border-t border-[#eaeaea] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-xs text-neutral-500 font-sans">
                        <span>Formula: <span class="font-mono text-neutral-700">Loss % = ((Green - Roasted) / Green) × 100</span></span>
                        <span class="font-medium text-neutral-800">Toleransi Standar: 14.0% – 18.0% (Mesin Giesen W15A)</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboardApp() {
    return {
        kpi: @json($kpi),
        kpiMatrix: @json($kpiMatrix ?? []),
        roastBatches: @json($roastBatches),
        stockAlerts: @json($stockAlerts),
        branches: @json($branches),
        categoryShare: @json($categoryShare),
        batchFilter: 'ALL',
        searchQuery: '',
        searchOpen: false,
        activeSearchIdx: -1,
        poLoading: false,
        poCreated: false,
        periodFilter: '7d',

        // Hero Chart Interactive State
        heroMetric: 'revenue',
        heroStyle: 'line',
        heroHoverIdx: null,

        init() {
            window.addEventListener('kafeina-branch-changed', (e) => {
                const b = e.detail && e.detail.branch;
                if (b && this.kpiMatrix && this.kpiMatrix[b]) {
                    this.kpi = this.kpiMatrix[b];
                }
            });
        },

        calculateCurve(vals, width, height, padTop, chartH) {
            if (!vals || vals.length === 0) return { path: '', area: '', points: [], lastPoint: {x: 0, y: 0} };
            const min = Math.min(...vals);
            const max = Math.max(...vals);
            const range = (max - min) || 1;
            const stepX = (width - 24) / (vals.length - 1);
            
            const points = vals.map((v, i) => {
                const x = 12 + i * stepX;
                const norm = (v - min) / range;
                const y = (padTop + chartH) - (norm * chartH);
                return { x: Math.round(x * 10) / 10, y: Math.round(y * 10) / 10, val: v };
            });

            let path = `M ${points[0].x} ${points[0].y}`;
            for (let i = 1; i < points.length; i++) {
                const prev = points[i - 1];
                const curr = points[i];
                const cp1x = prev.x + (curr.x - prev.x) / 2;
                const cp1y = prev.y;
                const cp2x = prev.x + (curr.x - prev.x) / 2;
                const cp2y = curr.y;
                path += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${curr.x} ${curr.y}`;
            }

            const last = points[points.length - 1];
            const first = points[0];
            const area = `${path} L ${last.x} ${height - 2} L ${first.x} ${height - 2} Z`;

            return { path, area, points, lastPoint: last };
        },

        // Dynamic Period-Aware KPIs from Real-Time Database (Hari Ini, 7 Hari, 30 Hari)
        get currentKpi() {
            const period = this.periodFilter;
            const k = this.kpi || {};
            const periods = k.periods || {};
            const p = periods[period] || periods['7d'] || periods['today'];

            if (p) {
                return {
                    revenue: Number(p.revenue || 0),
                    cash_revenue: Number(p.cash_revenue || 0),
                    qris_revenue: Number(p.qris_revenue || 0),
                    orders: Number(p.orders || 0),
                    orders_badge: p.orders_badge || 'Live DB',
                    margin: Number(p.margin || 0),
                    profit: Number(p.profit || 0),
                    aov: Number(p.aov || 0),
                    yield_loss: Number(p.yield_loss || k.average_yield_loss_pct || 0),
                    yield_rate: p.yield_rate || ((100 - Number(k.average_yield_loss_pct || 0)).toFixed(1) + '%'),
                    growth: p.growth || 'Live DB',
                };
            }

            return {
                revenue: Number(k.daily_revenue || 0),
                cash_revenue: Number(k.cash_revenue || 0),
                qris_revenue: Number(k.qris_revenue || 0),
                orders: Number(k.transaction_count || 0),
                orders_badge: 'Live DB',
                margin: Number(k.gross_profit_margin || 0),
                profit: Number(k.gross_profit_amount || 0),
                aov: Number(k.average_order_value || 0),
                yield_loss: Number(k.average_yield_loss_pct || 0),
                yield_rate: (100 - Number(k.average_yield_loss_pct || 0)).toFixed(1) + '%',
                growth: 'Live DB',
            };
        },

        get sparkLabels() {
            if (this.periodFilter === 'today') {
                const hourly = this.kpi?.hourly_revenue || [];
                return hourly.length ? hourly.slice(0, 7).map(h => h.hour) : ['07:00', '09:00', '11:00', '13:00', '15:00', '17:00', '19:00'];
            }
            if (this.periodFilter === '30d') {
                const buckets = this.kpi?.trend_30d || [];
                return buckets.length ? buckets.map(b => b.label) : ['P1', 'P2', 'P3', 'P4', 'P5', 'P6'];
            }
            const days7 = this.kpi?.revenue_trend_7d || [];
            return days7.length ? days7.map(d => d.day) : ['H-6', 'H-5', 'H-4', 'H-3', 'H-2', 'H-1', 'Ini'];
        },

        get revenueSpark() {
            let vals = [];
            if (this.periodFilter === 'today') {
                const hourly = this.kpi?.hourly_revenue || [];
                vals = hourly.length ? hourly.slice(0, 7).map(h => Number(h.val || 0)) : [0, 0, 0, 0, 0, 0, 0];
            } else if (this.periodFilter === '30d') {
                const buckets = this.kpi?.trend_30d || [];
                vals = buckets.length ? buckets.map(b => Number(b.rev || 0)) : [0, 0, 0, 0, 0, 0];
            } else {
                const list = this.kpi?.revenue_trend_7d || [];
                vals = list.length ? list.map(i => Number(i.val || 0)) : [0, 0, 0, 0, 0, 0, 0];
            }
            return this.calculateCurve(vals, 160, 44, 6, 32);
        },

        get ordersBars() {
            let list = [];
            if (this.periodFilter === 'today') {
                const hourly = this.kpi?.hourly_revenue || [];
                list = hourly.slice(0, 7).map(h => ({ day: h.hour, val: Number(h.orders || 0) }));
            } else if (this.periodFilter === '30d') {
                const buckets = this.kpi?.trend_30d || [];
                list = buckets.map(b => ({ day: b.label, val: Number(b.orders || 0) }));
            } else {
                const days7 = this.kpi?.orders_trend_7d || [];
                list = days7.map(i => ({ day: i.day, val: Number(i.val || 0) }));
            }

            const max = Math.max(...list.map(i => i.val), 1);
            const chartH = 34;

            return list.map((item, i) => {
                const h = item.val > 0 ? Math.max(6, Math.round((item.val / max) * chartH)) : 3;
                const isCurrent = i === list.length - 1;
                return {
                    day: item.day,
                    val: item.val,
                    h: h,
                    isCurrent: isCurrent
                };
            });
        },

        get marginSpark() {
            let vals = [];
            if (this.periodFilter === '30d') {
                const buckets = this.kpi?.trend_30d || [];
                vals = buckets.length ? buckets.map(b => Number(b.margin || 0)) : [0, 0, 0, 0, 0, 0];
            } else {
                const list = this.kpi?.margin_trend_7d || [];
                vals = list.length ? list.map(i => Number(i.val || 0)) : [0, 0, 0, 0, 0, 0, 0];
            }
            const curve = this.calculateCurve(vals, 160, 44, 6, 30);
            const min = Math.min(...vals, 60.0);
            const max = Math.max(...vals, 75.0);
            const targetY = (6 + 30) - (((65.0 - min) / ((max - min) || 1)) * 30);
            return {
                ...curve,
                targetY: Math.round(targetY * 10) / 10
            };
        },

        get yieldBandLeft() {
            const val = parseFloat(this.currentKpi.yield_loss) || 15.5;
            const pct = Math.max(5, Math.min(95, ((val - 10.0) / 12.0) * 100));
            return Math.round(pct * 10) / 10;
        },

        // Dynamic Theme Palette by Active Metric
        get activeTheme() {
            if (this.heroMetric === 'revenue') {
                return {
                    color: '#d97706',
                    gradId: 'hero-grad-revenue',
                    label: this.periodFilter === 'today' ? 'Omzet Sesi' : (this.periodFilter === '30d' ? 'Omzet 5 Hari' : 'Omzet Harian'),
                    pillLabel: 'Omzet',
                    textClass: 'text-amber-600',
                    bgClass: 'bg-amber-500',
                    iconBgClass: 'bg-neutral-100 border border-neutral-200/60 text-neutral-700',
                    badgeClass: 'bg-amber-50 text-amber-700 border border-amber-200/60',
                    barActive: 'bg-amber-500 shadow-xs',
                    barInactive: 'bg-amber-100 hover:bg-amber-200',
                };
            }
            if (this.heroMetric === 'orders') {
                return {
                    color: '#4f46e5',
                    gradId: 'hero-grad-orders',
                    label: 'Volume Struk',
                    pillLabel: 'Kasir POS',
                    textClass: 'text-indigo-600',
                    bgClass: 'bg-indigo-600',
                    iconBgClass: 'bg-neutral-100 border border-neutral-200/60 text-neutral-700',
                    badgeClass: 'bg-indigo-50 text-indigo-700 border border-indigo-200/60',
                    barActive: 'bg-indigo-600 shadow-xs',
                    barInactive: 'bg-indigo-100 hover:bg-indigo-200',
                };
            }
            return {
                color: '#ea580c',
                gradId: 'hero-grad-hourly',
                label: 'Omzet Sesi',
                pillLabel: 'Rush Hour',
                textClass: 'text-orange-600',
                bgClass: 'bg-orange-500',
                iconBgClass: 'bg-neutral-100 border border-neutral-200/60 text-neutral-700',
                badgeClass: 'bg-orange-50 text-orange-700 border border-orange-200/60',
                barActive: 'bg-orange-500 shadow-xs',
                barInactive: 'bg-orange-100 hover:bg-orange-200',
            };
        },

        // Hero Chart Logic powered by 100% Real-Time Database Aggregates & Dynamic Calendar Dates
        get heroRawData() {
            const p = this.periodFilter;
            const m = this.heroMetric;

            // 1. TODAY DATASET (Real Today Hourly Slots)
            if (p === 'today') {
                const hourly = this.kpi?.hourly_revenue || [];
                if (m === 'revenue') {
                    return hourly.map(i => ({
                        label: i.hour,
                        val: Number(i.val || 0),
                        fullTitle: i.title || (i.hour + ' WIB (Hari Ini)'),
                        status: i.orders > 0 ? 'Aktif' : 'Belum Ada Order',
                        displayVal: this.formatShortIdr(i.raw_revenue || 0),
                        detail1Label: 'QRIS & Transfer:',
                        detail1Val: this.formatShortIdr(i.qris_revenue || 0),
                        detail2Label: 'Tunai Kasir:',
                        detail2Val: this.formatShortIdr(i.cash_revenue || 0),
                    }));
                }
                if (m === 'orders') {
                    return hourly.map(i => {
                        const aovVal = i.orders > 0 ? Math.round((i.raw_revenue || 0) / i.orders) : 0;
                        return {
                            label: i.hour,
                            val: Number(i.orders || 0),
                            fullTitle: i.title || (i.hour + ' WIB (Hari Ini)'),
                            status: i.orders > 0 ? 'Tercatat' : 'Sepi',
                            displayVal: (i.orders || 0) + ' Struk POS',
                            detail1Label: 'Rata-Rata Transaksi:',
                            detail1Val: 'Rp ' + this.formatNumber(aovVal) + ' / Struk',
                            detail2Label: 'Total Omzet Sesi:',
                            detail2Val: this.formatShortIdr(i.raw_revenue || 0),
                        };
                    });
                }
                return hourly.map(i => ({
                    label: i.hour,
                    val: Number(i.val || 0),
                    fullTitle: i.title || (i.hour + ' WIB'),
                    status: i.orders > 0 ? 'Aktif' : 'Standby',
                    displayVal: this.formatShortIdr(i.raw_revenue || 0),
                    detail1Label: 'Volume Struk:',
                    detail1Val: (i.orders || 0) + ' Transaksi',
                    detail2Label: 'Non-Tunai:',
                    detail2Val: this.formatShortIdr(i.qris_revenue || 0),
                }));
            }

            // 2. 30 DAYS DATASET (Real 6 x 5-Day Calendar Buckets)
            if (p === '30d') {
                const periods = this.kpi?.trend_30d || [];
                if (m === 'revenue') {
                    return periods.map(i => ({
                        label: i.label,
                        val: Number(i.rev || 0),
                        fullTitle: i.full_title || i.label,
                        status: i.orders > 0 ? (i.orders + ' Struk') : 'Tanpa Order',
                        displayVal: this.formatShortIdr(i.raw_revenue || 0),
                        detail1Label: 'QRIS & Transfer:',
                        detail1Val: this.formatShortIdr(i.qris_revenue || 0),
                        detail2Label: 'Tunai Kasir:',
                        detail2Val: this.formatShortIdr(i.cash_revenue || 0),
                    }));
                }
                if (m === 'orders') {
                    return periods.map(i => ({
                        label: i.label,
                        val: Number(i.orders || 0),
                        fullTitle: i.full_title || i.label,
                        status: '5 Hari',
                        displayVal: this.formatNumber(i.orders || 0) + ' Struk POS',
                        detail1Label: 'Rata-Rata Transaksi:',
                        detail1Val: 'Rp ' + this.formatNumber(i.aov || 0) + ' / Struk',
                        detail2Label: 'Total Omzet:',
                        detail2Val: this.formatShortIdr(i.raw_revenue || 0),
                    }));
                }
                const hourly30 = this.kpi?.hourly_revenue_30d || this.kpi?.hourly_revenue || [];
                const total30 = Math.max(1, hourly30.reduce((s, x) => s + Number(x.raw_revenue || 0), 0));
                return hourly30.map(i => ({
                    label: i.hour,
                    val: Number(i.val || 0),
                    fullTitle: (i.title || i.hour + ' WIB') + ' (30 Hari)',
                    status: '30 Hari',
                    displayVal: this.formatShortIdr(i.raw_revenue || 0),
                    detail1Label: 'Total Transaksi:',
                    detail1Val: (i.orders || 0) + ' Struk',
                    detail2Label: 'Kontribusi Omzet:',
                    detail2Val: (((i.raw_revenue || 0) / total30) * 100).toFixed(1) + '%',
                }));
            }

            // 3. 7 DAYS DATASET (Real Last 7 Calendar Days)
            if (m === 'revenue') {
                return (this.kpi?.revenue_trend_7d || []).map((i, idx, arr) => ({
                    label: i.day,
                    val: Number(i.val || 0),
                    fullTitle: i.full_date || i.day,
                    status: idx === arr.length - 1 ? 'Hari Ini' : 'Live DB',
                    displayVal: this.formatShortIdr(i.raw_revenue || 0),
                    detail1Label: 'QRIS & Transfer:',
                    detail1Val: this.formatShortIdr(i.qris_revenue || 0),
                    detail2Label: 'Tunai Kasir:',
                    detail2Val: this.formatShortIdr(i.cash_revenue || 0),
                }));
            }
            if (m === 'orders') {
                return (this.kpi?.orders_trend_7d || []).map((i, idx, arr) => ({
                    label: i.day,
                    val: Number(i.val || 0),
                    fullTitle: i.full_date || i.day,
                    status: idx === arr.length - 1 ? 'Hari Ini' : 'Live DB',
                    displayVal: (i.val || 0) + ' Struk POS',
                    detail1Label: 'Rata-Rata Transaksi:',
                    detail1Val: 'Rp ' + this.formatNumber(i.aov || 0) + ' / Struk',
                    detail2Label: 'Sumber Data:',
                    detail2Val: 'Database POS Real-Time',
                }));
            }
            const hourly = this.kpi?.hourly_revenue || [];
            return hourly.map(i => ({
                label: i.hour,
                val: Number(i.val || 0),
                fullTitle: i.title || (i.hour + ' WIB'),
                status: i.orders > 0 ? 'Aktif' : 'Standby',
                displayVal: this.formatShortIdr(i.raw_revenue || 0),
                detail1Label: 'Volume Transaksi:',
                detail1Val: (i.orders || 0) + ' Struk POS',
                detail2Label: 'QRIS & Transfer:',
                detail2Val: this.formatShortIdr(i.qris_revenue || 0),
            }));
        },

        get heroChartMeta() {
            const data = this.heroRawData;
            if (!data.length) return { maxLabel: '', midLabel: '' };
            const vals = data.map(d => d.val);
            const max = Math.max(...vals) || 1;
            if (this.heroMetric === 'revenue' || this.heroMetric === 'hourly') {
                return {
                    maxLabel: 'Rp ' + max.toFixed(1).replace('.', ',') + ' Jt',
                    midLabel: 'Rp ' + (max / 2).toFixed(1).replace('.', ',') + ' Jt'
                };
            }
            return {
                maxLabel: max + ' Struk',
                midLabel: Math.round(max / 2) + ' Struk'
            };
        },

        get heroChartData() {
            const data = this.heroRawData;
            if (!data.length) return { path: '', area: '', points: [] };
            const vals = data.map(d => d.val);
            const min = 0;
            const max = Math.max(...vals) * 1.15 || 1;
            const range = max - min;
            const width = 700;
            const height = 160;
            const padTop = 15;
            const chartH = 130;
            const stepX = data.length > 1 ? (width - 40) / (data.length - 1) : 0;

            const points = data.map((item, i) => {
                const x = 20 + i * stepX;
                const norm = (item.val - min) / range;
                const y = (padTop + chartH) - (norm * chartH);
                const percentX = (x / width) * 100;
                const hitW = stepX;
                const hitX = Math.max(0, x - hitW / 2);
                return {
                    x: Math.round(x * 10) / 10,
                    y: Math.round(y * 10) / 10,
                    hitX: Math.round(hitX * 10) / 10,
                    hitW: Math.round(hitW * 10) / 10,
                    percentX: Math.round(percentX * 10) / 10,
                    label: item.label,
                    val: item.val,
                    fullTitle: item.fullTitle,
                    status: item.status,
                    displayVal: item.displayVal,
                    detail1Label: item.detail1Label,
                    detail1Val: item.detail1Val,
                    detail2Label: item.detail2Label,
                    detail2Val: item.detail2Val,
                };
            });

            let path = `M ${points[0].x} ${points[0].y}`;
            for (let i = 1; i < points.length; i++) {
                const prev = points[i - 1];
                const curr = points[i];
                const cp1x = prev.x + (curr.x - prev.x) / 2;
                const cp1y = prev.y;
                const cp2x = prev.x + (curr.x - prev.x) / 2;
                const cp2y = curr.y;
                path += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${curr.x} ${curr.y}`;
            }

            const last = points[points.length - 1];
            const first = points[0];
            const area = `${path} L ${last.x} ${height} L ${first.x} ${height} Z`;

            return { path, area, points };
        },

        get heroBarData() {
            const data = this.heroRawData;
            if (!data.length) return [];
            const vals = data.map(d => d.val);
            const max = Math.max(...vals) || 1;
            return data.map((item, i) => {
                const hPercent = Math.max(8, Math.round((item.val / max) * 92));
                const percentX = ((i + 0.5) / data.length) * 100;
                return {
                    label: item.label,
                    val: item.val,
                    fullTitle: item.fullTitle,
                    status: item.status,
                    displayVal: item.displayVal,
                    detail1Label: item.detail1Label,
                    detail1Val: item.detail1Val,
                    detail2Label: item.detail2Label,
                    detail2Val: item.detail2Val,
                    hPercent: hPercent,
                    percentX: percentX,
                    isCurrent: i === data.length - 1
                };
            });
        },

        get heroXLabels() {
            return this.heroRawData.map(d => d.label);
        },

        get popoverPositionStyle() {
            if (!this.activeHoverPoint) return 'display: none;';
            const px = this.activeHoverPoint.percentX;
            let transform = '-50%';
            if (px < 18) transform = '0%';
            else if (px > 82) transform = '-100%';
            return `left: ${px}%; transform: translateX(${transform});`;
        },

        get activeHoverPoint() {
            if (this.heroHoverIdx === null) return null;
            if (this.heroStyle === 'line') {
                return this.heroChartData.points[this.heroHoverIdx] || null;
            }
            return this.heroBarData[this.heroHoverIdx] || null;
        },

        get heroMetricSummary() {
            if (this.periodFilter === 'today') {
                if (this.heroMetric === 'revenue') return 'Omzet hari ini menunjukkan lonjakan tajam pada sesi makan siang dan sore menjelang malam.';
                if (this.heroMetric === 'orders') return 'Ritme struk kasir hari ini terpadat pada shift malam (19:00 - 21:00 WIB).';
                return 'Puncak jam sibuk gerai kopi hari ini terjadi pada sesi santai sore hingga malam.';
            }
            if (this.periodFilter === '30d') {
                if (this.heroMetric === 'revenue') return 'Tren performa 30 hari menunjukkan konsistensi omzet di atas rata-rata target bulanan kedai.';
                if (this.heroMetric === 'orders') return 'Volume transaksi bulanan stabil dengan akselerasi tajam pada pekan gajian (Payday).';
                return 'Distribusi jam sibuk selama 30 hari konsisten didominasi oleh segmen WFC dan Evening Hangout.';
            }
            if (this.heroMetric === 'revenue') return 'Omzet mingguan terpantau tumbuh sehat dengan kenaikan signifikan di akhir pekan.';
            if (this.heroMetric === 'orders') return 'Lonjakan transaksi terpusat pada hari Jumat hingga Minggu.';
            return 'Puncak jam sibuk gerai kopi terjadi pada sesi 18:00 - 21:00 WIB (Evening Social Rush).';
        },

        get heroPeakStat() {
            const data = this.heroRawData;
            if (!data.length) return '-';
            const highest = [...data].sort((a, b) => b.val - a.val)[0];
            return highest ? highest.label + ' (' + highest.displayVal + ')' : '-';
        },

        get heroAvgStat() {
            const data = this.heroRawData;
            if (!data.length) return '-';
            const sum = data.reduce((acc, c) => acc + c.val, 0);
            const avg = sum / data.length;
            const unit = this.periodFilter === 'today' ? 'Jam' : (this.periodFilter === '30d' ? 'Periode' : 'Hari');
            if (this.heroMetric === 'revenue' || this.heroMetric === 'hourly') {
                return 'Rp ' + avg.toFixed(1).replace('.', ',') + ' Jt / ' + unit;
            }
            return Math.round(avg) + ' Struk / ' + unit;
        },
        get filteredBatches() {
            let res = this.roastBatches;
            if (this.batchFilter === 'OPTIMAL') {
                res = res.filter(b => b.status === 'OPTIMAL');
            } else if (this.batchFilter === 'WARNING') {
                res = res.filter(b => b.status !== 'OPTIMAL');
            }
            if (this.searchQuery && this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(b => 
                    (b.bean_origin && b.bean_origin.toLowerCase().includes(q)) ||
                    (b.batch_code && b.batch_code.toLowerCase().includes(q)) ||
                    (b.roast_profile && b.roast_profile.toLowerCase().includes(q))
                );
            }
            return res;
        },

        setSearch(term) {
            this.searchQuery = term;
            this.searchOpen = true;
            this.activeSearchIdx = -1;
        },

        navigateSearch(delta) {
            const list = this.spotlightResults;
            if (!list.length) return;
            this.activeSearchIdx += delta;
            if (this.activeSearchIdx < 0) this.activeSearchIdx = list.length - 1;
            if (this.activeSearchIdx >= list.length) this.activeSearchIdx = 0;
        },

        selectActiveSearchResult() {
            const list = this.spotlightResults;
            if (this.activeSearchIdx >= 0 && this.activeSearchIdx < list.length) {
                const item = list[this.activeSearchIdx];
                this.handleResultClick(item, new Event('click'));
            } else if (list.length > 0) {
                this.handleResultClick(list[0], new Event('click'));
            }
        },

        handleResultClick(res, event) {
            if (res.type === 'branch') {
                if (event && event.preventDefault) event.preventDefault();
                window.dispatchEvent(new CustomEvent('kafeina-branch-changed', {
                    detail: { branch: res.rawName }
                }));
                this.searchOpen = false;
                if (window.showIosNotify) {
                    window.showIosNotify({
                        title: 'Filter Cabang Aktif',
                        subtitle: 'Menampilkan analitik untuk ' + res.title,
                        type: 'info',
                    });
                }
                return;
            }
            if (res.url && res.url !== '#') {
                window.location.href = res.url;
            }
        },

        get purchasableCriticalCount() {
            return (this.stockAlerts || []).filter(item => item && item.purchasable === true).length;
        },

        async createConsolidatedPo() {
            if (this.poLoading || this.purchasableCriticalCount === 0) return;
            this.poLoading = true;
            try {
                const response = await fetch('{{ route('dashboard.consolidated-po') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        branch: this.selectedBranch || 'all',
                    }),
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    this.poCreated = true;
                    if (window.showIosNotify) {
                        window.showIosNotify({
                            title: 'PO Konsolidasi Diterbitkan',
                            message: data.message,
                            type: 'success',
                            duration: 5500,
                        });
                    }
                } else {
                    throw new Error(data.message || 'Gagal menerbitkan PO Konsolidasi.');
                }
            } catch (err) {
                if (window.showIosNotify) {
                    window.showIosNotify({
                        title: 'Gagal Menerbitkan PO',
                        message: err.message || 'Terjadi kesalahan sistem saat menghubungi server.',
                        type: 'error',
                        duration: 5000,
                    });
                }
            } finally {
                this.poLoading = false;
                setTimeout(() => {
                    this.poCreated = false;
                }, 7000);
            }
        },

        get spotlightResults() {
            const q = (this.searchQuery || '').trim().toLowerCase();
            if (!q) return [];

            const results = [];

            // 1. Cabang / Outlet Filter
            (this.branches || []).forEach(b => {
                const name = b.name || '';
                const code = b.code || '';
                const city = b.city || '';
                if (name.toLowerCase().includes(q) || code.toLowerCase().includes(q) || city.toLowerCase().includes(q)) {
                    results.push({
                        id: 'branch-' + (b.id || name),
                        title: name,
                        subtitle: (city ? city + ' • ' : '') + 'Filter analitik cabang ' + (code || ''),
                        badge: 'OUTLET',
                        type: 'branch',
                        rawName: (code ? code.toLowerCase() : (b.slug || name.toLowerCase())),
                        url: '#',
                    });
                }
            });

            // 2. Roast Batches / Work Orders
            (this.roastBatches || []).forEach(b => {
                const code = b.batch_code || '';
                const origin = b.bean_origin || '';
                const profile = b.roast_profile || '';
                if (code.toLowerCase().includes(q) || origin.toLowerCase().includes(q) || profile.toLowerCase().includes(q)) {
                    results.push({
                        id: 'batch-' + code,
                        title: code + ' • ' + origin,
                        subtitle: 'Profil ' + profile + ' • ' + (b.status === 'OPTIMAL' ? 'Mutu Optimal' : 'Perlu Review') + ' • ' + b.roasted_weight_kg + ' kg',
                        badge: 'BATCH',
                        type: 'batch',
                        url: '{{ route('roastery.index') }}',
                    });
                }
            });

            // 3. Safety Stock Alerts / Green Beans / Bahan Baku
            (this.stockAlerts || []).forEach((s, idx) => {
                const name = s.item_name || '';
                const wh = s.warehouse || '';
                if (name.toLowerCase().includes(q) || wh.toLowerCase().includes(q)) {
                    results.push({
                        id: 'stock-' + idx,
                        title: name,
                        subtitle: wh + ' • Sisa ' + s.current_stock + ' (' + s.percent + '% ROP minimum)',
                        badge: 'STOK',
                        type: 'stock',
                        url: '{{ route('inventory.index') }}',
                    });
                }
            });

            // 4. Quick Module Navigation
            const modules = [
                { id: 'menu-pos', title: 'POS Kasir Terminal', subtitle: 'Akses transaksi kasir & pesanan meja', badge: 'POS', type: 'menu', keywords: ['pos', 'kasir', 'struk', 'bayar', 'order'], url: '{{ route('pos.index') }}' },
                { id: 'menu-roastery', title: 'Work Orders & Roastery', subtitle: 'Log mesin sangrai & penjadwalan batch', badge: 'ROAST', type: 'menu', keywords: ['roast', 'sangrai', 'giesen', 'diedrich', 'batch', 'profil'], url: '{{ route('roastery.index') }}' },
                { id: 'menu-inventory', title: 'Inventaris Stok Bahan Baku', subtitle: 'Manajemen persediaan green beans & cup', badge: 'STOK', type: 'menu', keywords: ['inventori', 'stok', 'gudang', 'warehouse', 'rop'], url: '{{ route('inventory.index') }}' },
                { id: 'menu-procurement', title: 'Pengadaan & Purchase Order (PO)', subtitle: 'Penerbitan PO vendor & purchase tracking', badge: 'PO', type: 'menu', keywords: ['po', 'procurement', 'vendor', 'pembelian', 'pengadaan'], url: '{{ route('procurement.index') }}' },
                { id: 'menu-qc', title: 'QC Lab & Cupping Score', subtitle: 'Evaluasi sensori SCA & skor kelayakan mutu', badge: 'QC', type: 'menu', keywords: ['qc', 'lab', 'cupping', 'sensori', 'sca'], url: '{{ route('qc-lab.index') }}' },
                { id: 'menu-sales', title: 'Riwayat Struk & Penjualan', subtitle: 'Arsip transaksi & pelaporan penjualan kasir', badge: 'SALES', type: 'menu', keywords: ['sales', 'transaksi', 'struk', 'omzet', 'riwayat'], url: '{{ route('sales.index') }}' },
                { id: 'menu-vouchers', title: 'Voucher & Kuota Diskon', subtitle: 'Manajemen kupon promo & kode diskon', badge: 'PROMO', type: 'menu', keywords: ['voucher', 'promo', 'diskon', 'kuota', 'kupon'], url: '{{ route('vouchers.index') }}' },
            ];

            modules.forEach(m => {
                if (m.title.toLowerCase().includes(q) || m.subtitle.toLowerCase().includes(q) || m.keywords.some(k => k.includes(q))) {
                    results.push(m);
                }
            });

            return results.slice(0, 8);
        },

        formatNumber(val) {
            if (!val || isNaN(val)) return '0';
            return new Intl.NumberFormat('id-ID').format(Math.round(val));
        },

        formatShortIdr(val) {
            const n = Number(val || 0);
            if (n >= 1000000) {
                return 'Rp ' + (n / 1000000).toFixed(2).replace('.', ',') + ' Jt';
            }
            if (n >= 1000) {
                return 'Rp ' + Math.round(n / 1000).toLocaleString('id-ID') + ' Rb';
            }
            return 'Rp ' + this.formatNumber(n);
        }
    };
}
</script>
@endpush
