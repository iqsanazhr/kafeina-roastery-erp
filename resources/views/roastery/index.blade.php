@extends('layouts.app')

@section('title', 'Roastery Manufacturing & Work Orders - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="roasteryApp()">
    
    <!-- PAGE HEADER & PRIMARY ACTIONS -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-neutral-400 mb-1">
                <span>Kafeina ERP</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-600 font-medium">Roastery & Manufacturing</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-400 font-mono text-[11px]">Central Hub</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">Roastery & Jadwal Work Orders</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Penjadwalan batch sangrai, alokasi drum roaster sentral, dan Bill of Materials (BOM) kemasan retail.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <a href="{{ route('yield-loss.index') }}" 
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <span>Stasiun Eksekusi Sangrai & Yield Loss</span>
            </a>
            <button type="button" 
                    @click="openWoModal = true" 
                    class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Jadwalkan Work Order</span>
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
        <!-- CARD 1: Total Work Orders -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Total Work Orders</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums" x-text="workOrders.length"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span>Antrean batch sangrai berjalan</span>
            </div>
        </div>

        <!-- CARD 2: Batch Terjadwal -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Batch Terjadwal</div>
            <div class="text-2xl font-bold tracking-tight text-amber-700 tabular-nums" x-text="workOrders.filter(w => w.status === 'SCHEDULED').length"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                <span>Siap loading green beans</span>
            </div>
        </div>

        <!-- CARD 3: Batch Selesai -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Batch Selesai</div>
            <div class="text-2xl font-bold tracking-tight text-emerald-700 tabular-nums" x-text="workOrders.filter(w => w.status === 'COMPLETED').length"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Biji matang degassing</span>
            </div>
        </div>

        <!-- CARD 4: Mesin Roaster Sentral -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Roaster Utama</div>
            <div class="text-xl font-bold tracking-tight text-neutral-900 font-sans">Giesen W15A</div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span>Cast iron drum • 15 kg/batch</span>
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
                       placeholder="Cari nomor WO, varietas, atau roaster..." 
                       @keydown.escape="searchQuery = ''"
                       class="bg-transparent border-none text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none flex-1 font-sans">
                <kbd class="px-1.5 py-0.5 rounded bg-neutral-200/60 border border-neutral-300/80 text-[10px] font-mono text-neutral-500">ESC</kbd>
            </div>
        </div>

        <!-- Segmented Tab Switcher (Vercel Style) -->
        <div class="flex items-center p-0.5 rounded-xl bg-neutral-100 border border-neutral-200 text-xs shrink-0 font-sans">
            <button type="button" 
                    @click="activeTab = 'wo'"
                    :class="activeTab === 'wo' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Daftar Work Orders</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-md font-mono" 
                      :class="activeTab === 'wo' ? 'bg-neutral-100 text-neutral-900' : 'bg-neutral-200/80 text-neutral-600'" 
                      x-text="workOrders.length"></span>
            </button>
            <button type="button" 
                    @click="activeTab = 'boms'"
                    :class="activeTab === 'boms' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>BOM Kemasan</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-md font-mono" 
                      :class="activeTab === 'boms' ? 'bg-neutral-100 text-neutral-900' : 'bg-neutral-200/80 text-neutral-600'" 
                      x-text="boms.length"></span>
            </button>
            <button type="button" 
                    @click="activeTab = 'machines'"
                    :class="activeTab === 'machines' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Mesin & Profil</span>
            </button>
        </div>
    </div>

    <!-- TAB 1: DAFTAR WORK ORDERS TABLE -->
    <div x-show="activeTab === 'wo'" class="space-y-4">
        <div class="bg-white border border-[#eaeaea] rounded-2xl overflow-hidden shadow-2xs">
            <div class="p-4 border-b border-[#eaeaea] flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-neutral-950 font-sans">Antrean Perintah Kerja Sangrai (WO)</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Alokasi silo biji mentah Roastery Central Hub dan target profil sangrai.</p>
                </div>
                <div class="text-xs font-mono text-neutral-500">
                    <strong class="text-neutral-900" x-text="filteredWorkOrders.length"></strong> Antrean
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-sans">
                    <thead>
                        <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/70">
                            <th class="py-3 px-4">Nomor WO</th>
                            <th class="py-3 px-4">Green Bean Lot</th>
                            <th class="py-3 px-4">Profil Sangrai</th>
                            <th class="py-3 px-4">Head Roaster</th>
                            <th class="py-3 px-4 text-right">Alokasi In</th>
                            <th class="py-3 px-4 text-right">Hasil Out</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f5f5f5]">
                        <template x-for="wo in filteredWorkOrders" :key="wo.id">
                            <tr class="hover:bg-neutral-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-neutral-900" x-text="wo.wo_number"></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-neutral-900 leading-snug" x-text="wo.lot ? wo.lot.variety : '-'"></div>
                                    <div class="text-[11px] text-neutral-400 font-mono mt-0.5" x-text="wo.lot ? wo.lot.lot_number : '-'"></div>
                                </td>
                                <td class="py-3.5 px-4 text-neutral-800 font-medium">
                                    <span x-text="wo.roasting_profile ? wo.roasting_profile.name : 'Omni Filter / Medium-Light'"></span>
                                </td>
                                <td class="py-3.5 px-4 text-neutral-700">
                                    <span class="font-medium text-neutral-900" x-text="wo.roaster ? wo.roaster.name : 'Bramantyo W.'"></span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-medium text-neutral-800 tabular-nums" x-text="(wo.green_weight_grams / 1000).toFixed(1) + ' kg'"></td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-900 tabular-nums">
                                    <span x-text="wo.roasted_weight_grams ? (wo.roasted_weight_grams / 1000).toFixed(1) + ' kg' : '-'"></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5 text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="{
                                                  'bg-emerald-500': wo.status === 'COMPLETED',
                                                  'bg-sky-500': wo.status === 'IN_PROGRESS',
                                                  'bg-amber-500': wo.status === 'SCHEDULED'
                                              }"></span>
                                        <span class="font-medium"
                                              :class="{
                                                  'text-neutral-700': wo.status === 'COMPLETED',
                                                  'text-sky-700': wo.status === 'IN_PROGRESS',
                                                  'text-amber-800': wo.status === 'SCHEDULED'
                                              }"
                                              x-text="wo.status === 'COMPLETED' ? 'Selesai' : (wo.status === 'IN_PROGRESS' ? 'Berjalan' : 'Dijadwalkan')"></span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredWorkOrders.length === 0">
                            <td colspan="7" class="py-12 text-center text-neutral-400 text-xs">
                                Tidak ada Work Order yang sesuai dengan pencarian Anda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: BILL OF MATERIALS (BOM) KEMASAN -->
    <div x-show="activeTab === 'boms'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="bom in boms" :key="bom.id">
                <div class="bg-white border border-[#eaeaea] hover:border-neutral-300 rounded-2xl p-5 shadow-2xs transition-colors flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[10px] font-mono font-bold text-neutral-400 uppercase tracking-wider">Formula Kemasan Retail</span>
                                <h4 class="text-sm font-bold text-neutral-950 mt-0.5 leading-snug" x-text="bom.product ? bom.product.name : 'Roasted Beans Retail 250g'"></h4>
                            </div>
                            <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-md bg-neutral-100 border border-neutral-200 text-neutral-800 shrink-0">
                                250g Pouch
                            </span>
                        </div>

                        <div class="mt-4 pt-3 border-t border-neutral-100 space-y-2 text-xs font-sans">
                            <div class="text-neutral-400 font-medium text-[11px]">Material Yang Diperlukan:</div>
                            <div class="flex items-center justify-between text-neutral-700">
                                <span>• Roasted Single Origin</span>
                                <span class="font-mono font-semibold text-neutral-900">250 gram</span>
                            </div>
                            <div class="flex items-center justify-between text-neutral-700">
                                <span>• Matte Valve Pouch Degassing</span>
                                <span class="font-mono font-semibold text-neutral-900">1 pcs</span>
                            </div>
                            <div class="flex items-center justify-between text-neutral-700">
                                <span>• Label Tasting Notes Sticker</span>
                                <span class="font-mono font-semibold text-neutral-900">1 pcs</span>
                            </div>
                            <div class="flex items-center justify-between text-neutral-700">
                                <span>• Tin Tie Sealer Strip</span>
                                <span class="font-mono font-semibold text-neutral-900">1 pcs</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#f5f5f5] flex items-center justify-between text-xs">
                        <span class="text-neutral-400 font-mono text-[11px]">ID BOM: #BOM-00<span x-text="bom.id"></span></span>
                        <div class="inline-flex items-center gap-1.5 text-[11px] text-neutral-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Aktif Produksi</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- TAB 3: MESIN ROASTER & PROFIL SANGRAI -->
    <div x-show="activeTab === 'machines'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Machine 1 -->
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
                <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                    <div>
                        <h4 class="text-sm font-bold text-neutral-950 font-sans">Giesen W15A Central Roaster</h4>
                        <span class="text-xs text-neutral-400 font-mono">Serial: GSN-2023-W15A-09</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 text-xs text-neutral-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Online & Siap</span>
                    </div>
                </div>
                <div class="mt-3.5 space-y-2 text-xs text-neutral-600 font-sans">
                    <div class="flex justify-between"><span class="text-neutral-400">Tipe Konstruksi:</span> <b class="text-neutral-800">Drum Roaster Double Wall Cast Iron</b></div>
                    <div class="flex justify-between"><span class="text-neutral-400">Kapasitas Batch:</span> <span class="font-mono text-neutral-900">5.0 kg – 15.0 kg green beans</span></div>
                    <div class="flex justify-between"><span class="text-neutral-400">Sensor Suhu:</span> <span class="text-neutral-800">PT100 Dual Probe (BT & ET Digital)</span></div>
                    <div class="flex justify-between"><span class="text-neutral-400">Airflow Control:</span> <span class="font-mono text-neutral-900">Inverter Digital 0 – 100%</span></div>
                </div>
            </div>

            <!-- Machine 2 -->
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
                <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                    <div>
                        <h4 class="text-sm font-bold text-neutral-950 font-sans">Diedrich IR-12 Sample & Small Batch</h4>
                        <span class="text-xs text-neutral-400 font-mono">Serial: DDR-2022-IR12-04</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 text-xs text-neutral-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Online & Siap</span>
                    </div>
                </div>
                <div class="mt-3.5 space-y-2 text-xs text-neutral-600 font-sans">
                    <div class="flex justify-between"><span class="text-neutral-400">Teknologi Pembakaran:</span> <b class="text-neutral-800">Infrared Ceramic Burner Clean Gas</b></div>
                    <div class="flex justify-between"><span class="text-neutral-400">Kapasitas Batch:</span> <span class="font-mono text-neutral-900">2.0 kg – 12.0 kg green beans</span></div>
                    <div class="flex justify-between"><span class="text-neutral-400">Spesialisasi Profil:</span> <span class="text-neutral-800">Light Roast Competition & Geisha Reserve</span></div>
                    <div class="flex justify-between"><span class="text-neutral-400">Data Logging:</span> <span class="font-mono text-neutral-900">Artisan & Cropster Live Telemetry</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Jadwalkan Work Order Baru -->
    <div x-show="openWoModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
        <div class="bg-white border border-[#eaeaea] rounded-2xl w-full max-w-md p-6 shadow-2xl" @click.away="openWoModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950 font-sans">Jadwalkan Work Order Baru</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Alokasikan lot green beans ke drum mesin sangrai sentral.</p>
                </div>
                <button type="button" @click="openWoModal = false" class="text-neutral-400 hover:text-neutral-800 p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="submitWorkOrder()" class="mt-4 space-y-3.5 text-xs font-sans">
                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Pilih Lot Biji Mentah:</label>
                    <select x-model="woForm.lot_id" required 
                            class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all">
                        <option value="">-- Pilih Lot Biji Mentah --</option>
                        <template x-for="lot in lots" :key="lot.id">
                            <option :value="lot.id" x-text="lot.lot_number + ' • ' + lot.variety + ' (Sisa: ' + (lot.current_weight_grams/1000).toFixed(1) + ' kg)'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Profil Sangrai (Roast Profile):</label>
                    <select x-model="woForm.profile_id" required 
                            class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all">
                        <option value="">-- Pilih Profil Sangrai --</option>
                        <template x-for="p in roastingProfiles" :key="p.id">
                            <option :value="p.id" x-text="p.name + ' (' + p.target_roast_level + ')'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Alokasi Berat Green Beans (kg):</label>
                    <input type="number" step="0.5" x-model.number="woForm.green_weight_kg" required 
                           class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono font-bold" 
                           placeholder="12.0" />
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Catatan Instruksi Roaster:</label>
                    <textarea x-model="woForm.notes" rows="2" 
                              class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all" 
                              placeholder="Target Agtron 75, first crack 09:15, development time ratio 16.5%"></textarea>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openWoModal = false" class="px-3.5 py-2 rounded-xl border border-neutral-200 text-neutral-700 hover:bg-neutral-50 font-medium cursor-pointer transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white font-semibold cursor-pointer shadow-xs transition-colors active:scale-[0.98]">Terbitkan Work Order</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function roasteryApp() {
    return {
        activeTab: 'wo',
        searchQuery: '',
        openWoModal: false,
        alertMessage: '',
        alertType: 'success',

        workOrders: @json($workOrders),
        roastingProfiles: @json($roastingProfiles),
        lots: @json($lots),
        boms: @json($boms),

        woForm: {
            lot_id: '',
            profile_id: '',
            green_weight_kg: 12.0,
            notes: 'Batch sangrai standar filter coffee single origin'
        },

        get filteredWorkOrders() {
            if (!this.searchQuery.trim()) return this.workOrders;
            const q = this.searchQuery.toLowerCase();
            return this.workOrders.filter(w => 
                (w.wo_number && w.wo_number.toLowerCase().includes(q)) ||
                (w.lot && w.lot.variety && w.lot.variety.toLowerCase().includes(q)) ||
                (w.roaster && w.roaster.name && w.roaster.name.toLowerCase().includes(q)) ||
                (w.status && w.status.toLowerCase().includes(q))
            );
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
        },

        async submitWorkOrder() {
            try {
                const res = await fetch("{{ route('roastery.wo.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.woForm)
                });
                const data = await res.json();
                if (data.success) {
                    this.alertType = 'success';
                    this.alertMessage = data.message;
                    this.workOrders.unshift(data.work_order);
                    this.openWoModal = false;
                    this.woForm = { lot_id: '', profile_id: '', green_weight_kg: 12.0, notes: '' };
                } else {
                    this.alertType = 'error';
                    this.alertMessage = data.message;
                }
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi kesalahan saat membuat Work Order.';
            }
        }
    };
}
</script>
@endsection
