@extends('layouts.app')

@section('title', 'QC Lab & Laboratorium Mutu Green Beans - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="qcLabApp()">
    
    <!-- PAGE HEADER & PRIMARY ACTIONS -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-neutral-400 mb-1">
                <span>Kafeina ERP</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-600 font-medium">Quality Assurance</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-400 font-mono text-[11px]">SCAA Standards</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">QC Lab & Laboratorium Mutu</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Pengujian fisik biji mentah, kadar air, densitas, defect count, dan cupping score standar Specialty Coffee Association.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <button type="button" 
                    @click="openQcModal = true" 
                    class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Input Uji QC Baru</span>
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

    <!-- BENTO KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- CARD 1: Total Pengujian QC -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Total Uji Laboratorium</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums" x-text="qualityControls.length"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Sampel kedatangan terverifikasi
            </div>
        </div>

        <!-- CARD 2: Lolos Specialty -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Lolos Specialty Grade</div>
            <div class="text-2xl font-bold tracking-tight text-emerald-700 tabular-nums" x-text="qualityControls.filter(q => q.qc_status === 'ACCEPTED').length"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Skor SCA ≥ 80.00 • Siap sangrai</span>
            </div>
        </div>

        <!-- CARD 3: Rata-rata Kadar Air -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Rata-rata Kadar Air</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums">{{ $avgMoisture }}%</div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Rentang aman: 10.0% – 12.0%
            </div>
        </div>

        <!-- CARD 4: Rata-rata Cupping Score -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Rata-rata Cupping SCA</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums">{{ $avgSca }}</div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Specialty Grade 1 Terverifikasi
            </div>
        </div>
    </div>

    <!-- TOOLBAR: SEARCH & TABS -->
    <div class="mb-5 bg-white border border-[#eaeaea] rounded-2xl p-3 sm:p-4 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative flex-1 max-w-md">
            <div class="flex items-center w-full px-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus-within:bg-white border border-neutral-200 focus-within:border-neutral-900 text-neutral-800 text-xs transition-colors group">
                <svg class="w-4 h-4 text-neutral-400 group-focus-within:text-neutral-800 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" 
                       x-model="searchQuery"
                       placeholder="Cari kode lot, origin, varietas, atau status..." 
                       @keydown.escape="searchQuery = ''"
                       class="bg-transparent border-none text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none flex-1 font-sans">
                <kbd class="px-1.5 py-0.5 rounded bg-neutral-200/60 border border-neutral-300/80 text-[10px] font-mono text-neutral-500">ESC</kbd>
            </div>
        </div>

        <!-- Segmented Tab Switcher (Vercel Style) -->
        <div class="flex items-center p-0.5 rounded-xl bg-neutral-100 border border-neutral-200 text-xs shrink-0 font-sans">
            <button type="button" 
                    @click="activeTab = 'qc_log'"
                    :class="activeTab === 'qc_log' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Log Uji Mutu</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-md font-mono" 
                      :class="activeTab === 'qc_log' ? 'bg-neutral-100 text-neutral-900' : 'bg-neutral-200/80 text-neutral-600'" 
                      x-text="qualityControls.length"></span>
            </button>
            <button type="button" 
                    @click="activeTab = 'lots'"
                    :class="activeTab === 'lots' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Lot Biji Siap Sangrai</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-md font-mono" 
                      :class="activeTab === 'lots' ? 'bg-neutral-100 text-neutral-900' : 'bg-neutral-200/80 text-neutral-600'" 
                      x-text="lots.length"></span>
            </button>
            <button type="button" 
                    @click="activeTab = 'standards'"
                    :class="activeTab === 'standards' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Standar SCAA</span>
            </button>
        </div>
    </div>

    <!-- TAB 1: LOG PENGUJIAN MUTU (QC) TABLE -->
    <div x-show="activeTab === 'qc_log'" class="space-y-4">
        <div class="bg-white border border-[#eaeaea] rounded-2xl overflow-hidden shadow-2xs">
            <div class="p-4 border-b border-[#eaeaea] flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-neutral-950 font-sans">Riwayat Inspeksi Mutu Green Beans</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Penilaian fisik laboratorium, kadar air, densitas, dan cupping score standar SCAA.</p>
                </div>
                <div class="text-xs font-mono text-neutral-500">
                    <strong class="text-neutral-900" x-text="filteredQc.length"></strong> Laporan Uji
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-sans">
                    <thead>
                        <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/70">
                            <th class="py-3 px-4">Kode Lot</th>
                            <th class="py-3 px-4">Origin & Varietas</th>
                            <th class="py-3 px-4 text-center">Kadar Air</th>
                            <th class="py-3 px-4 text-center">Densitas</th>
                            <th class="py-3 px-4 text-center">Defect P / S</th>
                            <th class="py-3 px-4 text-center">SCA Cupping</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Inspektor Lab</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f5f5f5]">
                        <template x-for="qc in filteredQc" :key="qc.id">
                            <tr class="hover:bg-neutral-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-neutral-900" x-text="qc.lot ? qc.lot.lot_number : '-'"></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-neutral-900 leading-snug" x-text="qc.lot ? qc.lot.variety : '-'"></div>
                                    <div class="text-[11px] text-neutral-400 font-mono mt-0.5" x-text="qc.lot ? qc.lot.origin_region : '-'"></div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold tabular-nums" 
                                    :class="qc.moisture_content >= 10 && qc.moisture_content <= 12 ? 'text-emerald-700' : 'text-amber-700'" 
                                    x-text="qc.moisture_content + '%'"></td>
                                <td class="py-3.5 px-4 text-center text-neutral-600 font-mono tabular-nums" x-text="qc.bulk_density_g_l + ' g/L'"></td>
                                <td class="py-3.5 px-4 text-center font-mono tabular-nums">
                                    <span class="font-bold" :class="qc.primary_defects === 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="qc.primary_defects"></span>
                                    <span class="text-neutral-300">/</span>
                                    <span class="text-neutral-600" x-text="qc.secondary_defects"></span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-sm font-mono tabular-nums" 
                                    :class="qc.sca_cupping_score >= 80 ? 'text-neutral-950' : 'text-neutral-400'" 
                                    x-text="qc.sca_cupping_score"></td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5 text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="{
                                                  'bg-emerald-500': qc.qc_status === 'ACCEPTED',
                                                  'bg-amber-500': qc.qc_status === 'DOWNGRADED',
                                                  'bg-rose-500': qc.qc_status === 'REJECTED'
                                              }"></span>
                                        <span class="font-medium"
                                              :class="{
                                                  'text-neutral-700': qc.qc_status === 'ACCEPTED',
                                                  'text-amber-800': qc.qc_status === 'DOWNGRADED',
                                                  'text-rose-700': qc.qc_status === 'REJECTED'
                                              }"
                                              x-text="qc.qc_status === 'ACCEPTED' ? 'Lolos SCAA' : (qc.qc_status === 'DOWNGRADED' ? 'Commercial' : 'Ditolak')"></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-neutral-700">
                                    <span class="font-medium text-neutral-900" x-text="qc.inspector ? qc.inspector.name : 'QC Specialist'"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredQc.length === 0">
                            <td colspan="8" class="py-12 text-center text-neutral-400 text-xs">
                                Tidak ada data pengujian QC yang sesuai dengan pencarian Anda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: DAFTAR LOT BIJI MENTAH SIAP SANGRAI TABLE -->
    <div x-show="activeTab === 'lots'" class="space-y-4">
        <div class="bg-white border border-[#eaeaea] rounded-2xl overflow-hidden shadow-2xs">
            <div class="p-4 border-b border-[#eaeaea] flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-neutral-950 font-sans">Lot Biji Mentah Terverifikasi Mutu</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Seluruh lot green beans yang telah dinyatakan lolos uji dan tersedia di silo Central Roastery.</p>
                </div>
                <div class="text-xs font-mono text-neutral-500">
                    <strong class="text-neutral-900" x-text="lots.length"></strong> Lot Tersedia
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-sans">
                    <thead>
                        <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/70">
                            <th class="py-3 px-4">Kode Lot</th>
                            <th class="py-3 px-4">Varietas & Proses</th>
                            <th class="py-3 px-4">Asal Daerah</th>
                            <th class="py-3 px-4 text-right">Berat Awal</th>
                            <th class="py-3 px-4 text-right">Sisa Stok</th>
                            <th class="py-3 px-4 text-right">HPP Modal / g</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f5f5f5]">
                        <template x-for="lot in lots" :key="lot.id">
                            <tr class="hover:bg-neutral-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-neutral-900" x-text="lot.lot_number"></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-neutral-900 leading-snug" x-text="lot.variety"></div>
                                    <div class="text-[11px] text-neutral-400 font-mono mt-0.5" x-text="lot.process_type || 'Anaerobic / Washed'"></div>
                                </td>
                                <td class="py-3.5 px-4 text-neutral-700" x-text="lot.origin_region"></td>
                                <td class="py-3.5 px-4 text-right font-mono text-neutral-500 tabular-nums" x-text="(lot.initial_weight_grams / 1000).toFixed(1) + ' kg'"></td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700 tabular-nums" x-text="(lot.current_weight_grams / 1000).toFixed(1) + ' kg'"></td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-900 tabular-nums" x-text="'Rp ' + formatNumber(lot.price_per_gram || 120)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: PROTOKOL & STANDAR SCAA (BENTO CARDS) -->
    <div x-show="activeTab === 'standards'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
                <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 flex items-center justify-center font-bold text-xs mb-3 font-mono">01</div>
                <h4 class="text-sm font-bold text-neutral-950 font-sans">Kadar Air (Moisture Content)</h4>
                <p class="text-xs text-neutral-500 mt-2 leading-relaxed font-sans">
                    Standar baku SCAA untuk green beans specialty berkisar antara <strong class="text-neutral-900">10.0% – 12.0%</strong> pada suhu 20°C. Nilai di atas 12% berisiko jamur mikotoksin; di bawah 10% rentan kehilangan prekursor aroma aromatis.
                </p>
            </div>
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
                <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-800 flex items-center justify-center font-bold text-xs mb-3 font-mono">02</div>
                <h4 class="text-sm font-bold text-neutral-950 font-sans">Toleransi Cacat Fisik (Defects)</h4>
                <p class="text-xs text-neutral-500 mt-2 leading-relaxed font-sans">
                    Kategori Specialty Grade 1 <strong class="text-neutral-900">TIDAK MENOLERIR CACAT PRIMER (0 Defect)</strong> per sampel 350g (biji hitam penuh, biji asam, lubang serangga parah). Cacat sekunder dibatasi maksimal 5 poin.
                </p>
            </div>
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
                <div class="w-8 h-8 rounded-xl bg-blue-50 border border-blue-200/80 text-blue-800 flex items-center justify-center font-bold text-xs mb-3 font-mono">03</div>
                <h4 class="text-sm font-bold text-neutral-950 font-sans">SCA Cupping Score (≥ 80)</h4>
                <p class="text-xs text-neutral-500 mt-2 leading-relaxed font-sans">
                    Hanya biji kopi dengan total nilai organoleptik <strong class="text-neutral-900">≥ 80.00</strong> (Fragrance, Flavor, Aftertaste, Acidity, Body, Balance, Clean Cup, Sweetness) yang lolos ke silo sangrai Kafeina.
                </p>
            </div>
        </div>
    </div>

    <!-- MODAL: Input Uji QC Lab -->
    <div x-show="openQcModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
        <div class="bg-white border border-[#eaeaea] rounded-2xl w-full max-w-lg p-6 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="openQcModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950 font-sans">Formulir Pengujian Mutu Green Beans (QC)</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Penilaian fisik laboratorium & verifikasi sampel kedatangan.</p>
                </div>
                <button type="button" @click="openQcModal = false" class="text-neutral-400 hover:text-neutral-800 p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="submitQc()" class="mt-4 space-y-3.5 text-xs font-sans">
                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Pilih Purchase Order Masuk (Green Beans Aktif):</label>
                    <select x-model="qcForm.po_id" @change="onPoChange()" required 
                            class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors">
                        <option value="">-- Pilih Kontrak PO Aktif --</option>
                        <template x-for="p in purchaseOrders" :key="p.id">
                            <option :value="p.id" x-text="p.po_number + ' • ' + (p.supplier ? p.supplier.name : '-') + (p.items && p.items[0] ? (' (' + Number(p.items[0].quantity_kg) + ' kg)') : '')"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Kode Region (Origin):</label>
                        <input type="text" x-model="qcForm.origin_code" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono uppercase" 
                               placeholder="GYO / IJN / TRJ" />
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Varietas Biji:</label>
                        <input type="text" x-model="qcForm.variety" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all" 
                               placeholder="Honey Anaerobic" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Metode Pasca Panen:</label>
                        <input type="text" x-model="qcForm.process_type" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all" 
                               placeholder="Washed / Natural / Carbonic" />
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Tahun Panen:</label>
                        <input type="number" x-model.number="qcForm.harvest_year" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Berat Sampel / Batch (kg):</label>
                        <input type="number" step="0.5" x-model.number="qcForm.weight_kg" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono font-bold" />
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Kadar Air (%):</label>
                        <input type="number" step="0.1" x-model.number="qcForm.moisture_content" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono font-bold" 
                               placeholder="11.2" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Kerapatan (g/L):</label>
                        <input type="number" step="1" x-model.number="qcForm.bulk_density_g_l" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono" 
                               placeholder="710" />
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Cacat Primer:</label>
                        <input type="number" x-model.number="qcForm.primary_defects" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono" 
                               placeholder="0" />
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Cacat Sekunder:</label>
                        <input type="number" x-model.number="qcForm.secondary_defects" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono" 
                               placeholder="2" />
                    </div>
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Skor Cupping SCAA (60 - 100):</label>
                    <input type="number" step="0.25" x-model.number="qcForm.sca_cupping_score" required 
                           class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono font-bold text-sm" 
                           placeholder="85.50" />
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Catatan Organoleptik & Tasting Notes:</label>
                    <textarea x-model="qcForm.notes" rows="2" 
                              class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all" 
                              placeholder="Notes: Peach, Bergamot, Wild Honey, Clean Crema"></textarea>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openQcModal = false" class="px-3.5 py-2 rounded-xl border border-neutral-200 text-neutral-700 hover:bg-neutral-50 font-medium cursor-pointer transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white font-semibold cursor-pointer shadow-xs transition-colors active:scale-[0.98]">Verifikasi & Terbitkan Lot</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function qcLabApp() {
    return {
        activeTab: 'qc_log',
        searchQuery: '',
        openQcModal: false,
        alertMessage: '',
        alertType: 'success',

        qualityControls: @json($qualityControls),
        lots: @json($lots),
        purchaseOrders: @json($purchaseOrders),

        qcForm: {
            po_id: '',
            origin_code: 'GYO',
            variety: 'Aceh Gayo Honey Anaerobic',
            process_type: 'Honey Anaerobic',
            harvest_year: 2026,
            weight_kg: 50,
            moisture_content: 11.2,
            bulk_density_g_l: 710,
            primary_defects: 0,
            secondary_defects: 1,
            sca_cupping_score: 86.5,
            notes: 'Aroma peach cerah, body seimbang, clean cup'
        },

        get filteredQc() {
            if (!this.searchQuery.trim()) return this.qualityControls;
            const q = this.searchQuery.toLowerCase();
            return this.qualityControls.filter(item => 
                (item.lot && item.lot.lot_number && item.lot.lot_number.toLowerCase().includes(q)) ||
                (item.lot && item.lot.variety && item.lot.variety.toLowerCase().includes(q)) ||
                (item.qc_status && item.qc_status.toLowerCase().includes(q)) ||
                (item.inspector && item.inspector.name && item.inspector.name.toLowerCase().includes(q))
            );
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
        },

        onPoChange() {
            const po = this.purchaseOrders.find(p => p.id == this.qcForm.po_id);
            if (!po || !po.items || po.items.length === 0) return;
            const item = po.items[0];
            if (item.quantity_kg) {
                this.qcForm.weight_kg = Number(item.quantity_kg);
            }
            const label = (item.product?.name || item.product_name || '').toLowerCase();
            if (item.product?.name) {
                this.qcForm.variety = item.product.name;
            }
            if (label.includes('gayo')) this.qcForm.origin_code = 'GYO';
            else if (label.includes('ijen')) this.qcForm.origin_code = 'IJN';
            else if (label.includes('flores') || label.includes('bajawa')) this.qcForm.origin_code = 'FLR';
            else if (label.includes('kintamani')) this.qcForm.origin_code = 'KNT';
            else if (label.includes('toraja')) this.qcForm.origin_code = 'TRJ';
        },

        async submitQc() {
            try {
                const res = await fetch("{{ route('qc-lab.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.qcForm)
                });
                const data = await res.json();
                if (data.success) {
                    this.alertType = 'success';
                    this.alertMessage = data.message;
                    this.qualityControls.unshift(data.qc);
                    if (data.qc.lot) {
                        this.lots.unshift(data.qc.lot);
                    }
                    if (data.po_id) {
                        this.purchaseOrders = this.purchaseOrders.filter(p => p.id !== data.po_id);
                        this.qcForm.po_id = '';
                    }
                    this.openQcModal = false;
                } else {
                    this.alertType = 'error';
                    this.alertMessage = data.message;
                }
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi kesalahan saat memproses QC.';
            }
        }
    };
}
</script>
@endsection
