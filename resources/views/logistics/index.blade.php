@extends('layouts.app')

@section('title', 'Logistik Distribusi Antar-Cabang & Surat Jalan - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="logisticsApp()">
    
    <!-- PAGE HEADER & PRIMARY ACTIONS -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-neutral-400 mb-1">
                <span>Kafeina ERP</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-600 font-medium">Fleet & Distribution</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-400 font-mono text-[11px]">Multi-Branch Fulfillment</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">Logistik Distribusi & Surat Jalan</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Pemenuhan Material Requests (MR) antar gerai, penerbitan Surat Jalan (Delivery Notes), dan pelacakan QR segel transit.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <button type="button" 
                    @click="openMrModal = true" 
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Ajukan MR Cabang</span>
            </button>
            <button type="button" 
                    @click="openDnModal = true" 
                    class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Terbitkan Surat Jalan</span>
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
        <!-- CARD 1: Material Requests -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Permintaan Cabang (MR)</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums" x-text="materialRequests.length"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                <span><span class="text-neutral-700 font-semibold" x-text="materialRequests.filter(m => m.status === 'PENDING').length + ' antrean'"></span> menanti</span>
            </div>
        </div>

        <!-- CARD 2: Surat Jalan In-Transit -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Surat Jalan In-Transit</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums" x-text="deliveryNotes.filter(d => d.status === 'DISPATCHED').length"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                <span>Muatan perjalanan armada</span>
            </div>
        </div>

        <!-- CARD 3: Pengiriman Tuntas -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Pengiriman Tuntas</div>
            <div class="text-2xl font-bold tracking-tight text-emerald-700 tabular-nums" x-text="deliveryNotes.filter(d => d.status === 'RECEIVED').length"></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Verifikasi fisik outlet</span>
            </div>
        </div>

        <!-- CARD 4: Rute Distribusi -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Rute Logistik Aktif</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums">3 Rute Gerai</div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span>Jakarta, Bandung & Jogja</span>
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
                       placeholder="Cari nomor MR, Surat Jalan, cabang, atau driver..." 
                       @keydown.escape="searchQuery = ''"
                       class="bg-transparent border-none text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none flex-1 font-sans">
                <kbd class="px-1.5 py-0.5 rounded bg-neutral-200/60 border border-neutral-300/80 text-[10px] font-mono text-neutral-500">ESC</kbd>
            </div>
        </div>

        <!-- Segmented Tab Switcher (Vercel Style) -->
        <div class="flex items-center p-0.5 rounded-xl bg-neutral-100 border border-neutral-200 text-xs shrink-0 font-sans">
            <button type="button" 
                    @click="activeTab = 'mr'"
                    :class="activeTab === 'mr' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Material Requests (MR)</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-md font-mono" 
                      :class="activeTab === 'mr' ? 'bg-neutral-100 text-neutral-900' : 'bg-neutral-200/80 text-neutral-600'" 
                      x-text="materialRequests.length"></span>
            </button>
            <button type="button" 
                    @click="activeTab = 'dn'"
                    :class="activeTab === 'dn' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Surat Jalan & Segel QR</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-md font-mono" 
                      :class="activeTab === 'dn' ? 'bg-neutral-100 text-neutral-900' : 'bg-neutral-200/80 text-neutral-600'" 
                      x-text="deliveryNotes.length"></span>
            </button>
            <button type="button" 
                    @click="activeTab = 'receive'"
                    :class="activeTab === 'receive' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3.5 py-1.5 rounded-lg transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Protokol SOP Terima</span>
            </button>
        </div>
    </div>

    <!-- TAB 1: MATERIAL REQUESTS TABLE -->
    <div x-show="activeTab === 'mr'" class="space-y-4">
        <div class="bg-white border border-[#eaeaea] rounded-2xl overflow-hidden shadow-2xs">
            <div class="p-4 border-b border-[#eaeaea] flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-neutral-950 font-sans">Permintaan Pasokan Stok Antar-Cabang (MR)</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Pengajuan kebutuhan roasted beans, fresh milk, sirup & kemasan dari bar outlet ke Roastery Central.</p>
                </div>
                <div class="text-xs font-mono text-neutral-500">
                    <strong class="text-neutral-900" x-text="filteredMr.length"></strong> Permintaan
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-sans">
                    <thead>
                        <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/70">
                            <th class="py-3 px-4">Nomor MR</th>
                            <th class="py-3 px-4">Cabang Pemohon</th>
                            <th class="py-3 px-4">Gudang Pemenuhan</th>
                            <th class="py-3 px-4">Item & Jumlah</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Rincian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f5f5f5]">
                        <template x-for="mr in filteredMr" :key="mr.id">
                            <tr class="hover:bg-neutral-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-neutral-900" x-text="mr.request_number"></td>
                                <td class="py-3.5 px-4 font-medium text-neutral-900" x-text="mr.from_branch ? mr.from_branch.name : 'Outlet Senopati'"></td>
                                <td class="py-3.5 px-4 text-neutral-600" x-text="mr.to_branch ? mr.to_branch.name : 'Central Roastery Hub'"></td>
                                <td class="py-3.5 px-4 text-neutral-800">
                                    <template x-if="mr.items && mr.items.length > 0">
                                        <div>
                                            <span class="font-medium text-neutral-900" x-text="mr.items[0].product ? mr.items[0].product.name : 'Roasted Beans'"></span>
                                            <span class="text-neutral-400 font-mono text-[11px]" x-text="' (' + mr.items[0].quantity + ')'"></span>
                                        </div>
                                    </template>
                                    <template x-if="!mr.items || mr.items.length === 0">
                                        <span class="text-neutral-400">Bahan Baku Multi-Item</span>
                                    </template>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5 text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="{
                                                  'bg-amber-500': mr.status === 'PENDING',
                                                  'bg-sky-500': mr.status === 'APPROVED',
                                                  'bg-emerald-500': mr.status === 'DISPATCHED'
                                              }"></span>
                                        <span class="font-medium"
                                              :class="{
                                                  'text-amber-800': mr.status === 'PENDING',
                                                  'text-sky-700': mr.status === 'APPROVED',
                                                  'text-neutral-700': mr.status === 'DISPATCHED'
                                              }"
                                              x-text="mr.status === 'PENDING' ? 'Menunggu' : (mr.status === 'APPROVED' ? 'Disetujui' : 'Dikirim')"></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button type="button" class="px-2.5 py-1 rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-[11px] font-medium cursor-pointer transition-colors">
                                        Detail →
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredMr.length === 0">
                            <td colspan="6" class="py-12 text-center text-neutral-400 text-xs">
                                Tidak ada data Material Request yang cocok.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: SURAT JALAN & QR TRANSIT TABLE -->
    <div x-show="activeTab === 'dn'" class="space-y-4">
        <div class="bg-white border border-[#eaeaea] rounded-2xl overflow-hidden shadow-2xs">
            <div class="p-4 border-b border-[#eaeaea] flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-neutral-950 font-sans">Manifest Surat Jalan (Delivery Notes)</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Surat jalan resmi armada logistik dengan verifikasi token segel QR transit.</p>
                </div>
                <div class="text-xs font-mono text-neutral-500">
                    <strong class="text-neutral-900" x-text="filteredDn.length"></strong> Dokumen Jalan
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-sans">
                    <thead>
                        <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/70">
                            <th class="py-3 px-4">Nomor Surat Jalan</th>
                            <th class="py-3 px-4">Rujukan MR</th>
                            <th class="py-3 px-4">Asal → Tujuan</th>
                            <th class="py-3 px-4">Driver / Armada</th>
                            <th class="py-3 px-4">Segel QR Transit</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f5f5f5]">
                        <template x-for="dn in filteredDn" :key="dn.id">
                            <tr class="hover:bg-neutral-50/80 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-neutral-900" x-text="dn.dn_number"></td>
                                <td class="py-3.5 px-4 font-mono text-neutral-500" x-text="dn.material_request ? dn.material_request.request_number : '-'"></td>
                                <td class="py-3.5 px-4 font-sans">
                                    <span class="text-neutral-500" x-text="dn.source_warehouse ? dn.source_warehouse.name : 'Sentral'"></span>
                                    <span class="text-neutral-300 mx-1">→</span>
                                    <strong class="text-neutral-900 font-semibold" x-text="dn.dest_warehouse ? dn.dest_warehouse.name : 'Bar Outlet'"></strong>
                                </td>
                                <td class="py-3.5 px-4 text-neutral-700" x-text="dn.driver_name || 'Armada Logistik 01'"></td>
                                <td class="py-3.5 px-4 font-mono text-neutral-600">
                                    <span class="px-2 py-0.5 rounded-md bg-neutral-100 border border-neutral-200 text-[11px]" 
                                          x-text="dn.qr_transit_token ? dn.qr_transit_token.substring(0, 14) + '...' : 'QR-TRN-2026-X8'"></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border font-sans"
                                          :class="{
                                              'bg-blue-50 text-blue-800 border-blue-200': dn.status === 'DISPATCHED',
                                              'bg-emerald-50 text-emerald-800 border-emerald-200': dn.status === 'RECEIVED',
                                              'bg-neutral-100 text-neutral-700 border-neutral-200': dn.status === 'PREPARED'
                                          }">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="{
                                                  'bg-blue-500': dn.status === 'DISPATCHED',
                                                  'bg-emerald-500': dn.status === 'RECEIVED',
                                                  'bg-neutral-400': dn.status === 'PREPARED'
                                              }"></span>
                                        <span x-text="dn.status"></span>
                                    </span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredDn.length === 0">
                            <td colspan="6" class="py-12 text-center text-neutral-400 text-xs">
                                Tidak ada data Surat Jalan yang cocok.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: PROTOKOL SERAH TERIMA BARANG (BENTO CARDS) -->
    <div x-show="activeTab === 'receive'" class="space-y-4">
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 sm:p-6 shadow-2xs">
            <div class="pb-3 border-b border-neutral-100">
                <h3 class="text-sm font-bold text-neutral-950 font-sans">Protokol Serah Terima Barang Outlet (Receiving SOP)</h3>
                <p class="text-xs text-neutral-500 mt-0.5">Barista penerima wajib melakukan audit fisik terhadap segel barcode pengiriman sebelum verifikasi serah terima.</p>
            </div>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl border border-neutral-200/80 bg-neutral-50/50">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 font-mono font-bold text-xs flex items-center justify-center mb-2.5">01</div>
                    <div class="text-xs font-bold text-neutral-900 mb-1">Pindai Segel QR Transit</div>
                    <p class="text-xs text-neutral-500 leading-relaxed font-sans">
                        Gunakan kamera POS atau scanner handheld untuk memindai token unik pada box pengiriman tertutup sebelum membuka segel.
                    </p>
                </div>
                <div class="p-4 rounded-xl border border-neutral-200/80 bg-neutral-50/50">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 font-mono font-bold text-xs flex items-center justify-center mb-2.5">02</div>
                    <div class="text-xs font-bold text-neutral-900 mb-1">Periksa Keutuhan Fisik Biji</div>
                    <p class="text-xs text-neutral-500 leading-relaxed font-sans">
                        Pastikan degassing valve pada pouch roasted beans tidak robek dan suhu susu segar tetap terjaga di bawah 4°C.
                    </p>
                </div>
                <div class="p-4 rounded-xl border border-neutral-200/80 bg-neutral-50/50">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 font-mono font-bold text-xs flex items-center justify-center mb-2.5">03</div>
                    <div class="text-xs font-bold text-neutral-900 mb-1">Auto Mutasi Saldo Stok</div>
                    <p class="text-xs text-neutral-500 leading-relaxed font-sans">
                        Setelah konfirmasi ditekan, status berubah menjadi RECEIVED dan saldo stok outlet bertambah seketika secara otomatis.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Ajukan MR Baru -->
    <div x-show="openMrModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
        <div class="bg-white border border-[#eaeaea] rounded-2xl w-full max-w-md p-6 shadow-2xl" @click.away="openMrModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950 font-sans">Ajukan Permintaan Stok (MR)</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Permintaan pasokan bahan dari bar outlet ke Roastery Sentral.</p>
                </div>
                <button type="button" @click="openMrModal = false" class="text-neutral-400 hover:text-neutral-800 p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="submitMr()" class="mt-4 space-y-3.5 text-xs font-sans">
                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Cabang Pemohon (Outlet Ritel):</label>
                    <select x-model="mrForm.from_branch_id" required 
                            class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all">
                        <template x-for="b in branches.filter(br => br.type === 'RETAIL_BRANCH')" :key="b.id">
                            <option :value="b.id" x-text="b.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Gudang Penyedia (Sentral):</label>
                    <select x-model="mrForm.to_branch_id" required 
                            class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all">
                        <template x-for="b in branches.filter(br => br.type === 'CENTRAL_ROASTERY')" :key="b.id">
                            <option :value="b.id" x-text="b.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Nama Produk / Bahan:</label>
                    <input type="text" x-model="mrForm.product_name" required 
                           class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all" 
                           placeholder="Cth: Espresso Blend Roasted 1kg / Oatly Barista" />
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Jumlah Kuantitas:</label>
                    <input type="number" step="1" x-model.number="mrForm.quantity" required 
                           class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono font-bold" 
                           placeholder="10" />
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Catatan / Alasan Permintaan:</label>
                    <textarea x-model="mrForm.notes" rows="2" 
                              class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all" 
                              placeholder="Kebutuhan weekend rush hour"></textarea>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openMrModal = false" class="px-3.5 py-2 rounded-xl border border-neutral-200 text-neutral-700 hover:bg-neutral-50 font-medium cursor-pointer transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white font-semibold cursor-pointer shadow-xs transition-colors active:scale-[0.98]">Kirim Pengajuan MR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Terbitkan Surat Jalan (DN) -->
    <div x-show="openDnModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
        <div class="bg-white border border-[#eaeaea] rounded-2xl w-full max-w-md p-6 shadow-2xl" @click.away="openDnModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950 font-sans">Terbitkan Surat Jalan (Delivery Note)</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Manifest resmi pengiriman barang dari Roastery Sentral.</p>
                </div>
                <button type="button" @click="openDnModal = false" class="text-neutral-400 hover:text-neutral-800 p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="submitDn()" class="mt-4 space-y-3.5 text-xs font-sans">
                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Rujukan Material Request (MR):</label>
                    <select x-model="dnForm.mr_id" required 
                            class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all">
                        <option value="">-- Pilih MR Terbuka --</option>
                        <template x-for="m in materialRequests" :key="m.id">
                            <option :value="m.id" x-text="m.request_number + ' • ' + (m.from_branch ? m.from_branch.name : '-')"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Gudang Asal (Sentral):</label>
                        <select x-model="dnForm.source_warehouse_id" required 
                                class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all">
                            <template x-for="w in warehouses.filter(wh => wh.type === 'CENTRAL_ROASTED' || wh.type === 'CENTRAL_RAW')" :key="w.id">
                                <option :value="w.id" x-text="w.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Gudang Tujuan (Bar Outlet):</label>
                        <select x-model="dnForm.dest_warehouse_id" required 
                                class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all">
                            <template x-for="w in warehouses.filter(wh => wh.type === 'RETAIL_BAR')" :key="w.id">
                                <option :value="w.id" x-text="w.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Nama Driver:</label>
                        <input type="text" x-model="dnForm.driver_name" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all" 
                               placeholder="Budi Santoso" />
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Plat Nomor Armada:</label>
                        <input type="text" x-model="dnForm.vehicle_number" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-all font-mono uppercase" 
                               placeholder="B 1234 ROA" />
                    </div>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openDnModal = false" class="px-3.5 py-2 rounded-xl border border-neutral-200 text-neutral-700 hover:bg-neutral-50 font-medium cursor-pointer transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white font-semibold cursor-pointer shadow-xs transition-colors active:scale-[0.98]">Terbitkan Surat Jalan</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function logisticsApp() {
    return {
        activeTab: 'mr',
        searchQuery: '',
        openMrModal: false,
        openDnModal: false,
        alertMessage: '',
        alertType: 'success',

        materialRequests: @json($materialRequests),
        deliveryNotes: @json($deliveryNotes),
        branches: @json($branches),
        warehouses: @json($warehouses),

        mrForm: {
            from_branch_id: @json($branches->first() ? $branches->first()->id : 1),
            to_branch_id: @json($branches->count() > 1 ? $branches[1]->id : 1),
            product_name: 'Espresso Velvet Blend Roasted 1kg',
            quantity: 15,
            notes: 'Restock mingguan bar utama'
        },

        dnForm: {
            mr_id: '',
            source_warehouse_id: @json($warehouses->first() ? $warehouses->first()->id : 1),
            dest_warehouse_id: @json($warehouses->count() > 1 ? $warehouses[1]->id : 1),
            driver_name: 'Dedi Kurniawan',
            vehicle_number: 'B 9281 KAF'
        },

        get filteredMr() {
            if (!this.searchQuery.trim()) return this.materialRequests;
            const q = this.searchQuery.toLowerCase();
            return this.materialRequests.filter(m => 
                (m.request_number && m.request_number.toLowerCase().includes(q)) ||
                (m.from_branch && m.from_branch.name && m.from_branch.name.toLowerCase().includes(q)) ||
                (m.to_branch && m.to_branch.name && m.to_branch.name.toLowerCase().includes(q)) ||
                (m.status && m.status.toLowerCase().includes(q))
            );
        },

        get filteredDn() {
            if (!this.searchQuery.trim()) return this.deliveryNotes;
            const q = this.searchQuery.toLowerCase();
            return this.deliveryNotes.filter(d => 
                (d.dn_number && d.dn_number.toLowerCase().includes(q)) ||
                (d.driver_name && d.driver_name.toLowerCase().includes(q)) ||
                (d.vehicle_number && d.vehicle_number.toLowerCase().includes(q)) ||
                (d.status && d.status.toLowerCase().includes(q))
            );
        },

        async submitMr() {
            try {
                const res = await fetch("{{ route('logistics.mr.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.mrForm)
                });
                const data = await res.json();
                if (data.success) {
                    this.alertType = 'success';
                    this.alertMessage = data.message;
                    if (data.material_request) {
                        this.materialRequests.unshift(data.material_request);
                    }
                    this.openMrModal = false;
                } else {
                    this.alertType = 'error';
                    this.alertMessage = data.message;
                }
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi kesalahan saat mengajukan Material Request.';
            }
        },

        async submitDn() {
            try {
                const res = await fetch("{{ route('logistics.dn.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.dnForm)
                });
                const data = await res.json();
                if (data.success) {
                    this.alertType = 'success';
                    this.alertMessage = data.message;
                    if (data.delivery_note) {
                        this.deliveryNotes.unshift(data.delivery_note);
                    }
                    this.openDnModal = false;
                } else {
                    this.alertType = 'error';
                    this.alertMessage = data.message;
                }
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi kesalahan saat menerbitkan Surat Jalan.';
            }
        }
    };
}
</script>
@endsection
