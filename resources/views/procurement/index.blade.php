@extends('layouts.app')

@section('title', 'Pengadaan Bahan Baku & Mitra Pemasok - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="procurementApp()">
    
    <!-- PAGE HEADER & PRIMARY ACTIONS -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-neutral-400 font-mono mb-1">
                <span>Kafeina ERP</span>
                <span>/</span>
                <span class="text-neutral-600 font-medium">Pengadaan Bahan Baku & Mitra Pemasok</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">Pengadaan Bahan Baku & Mitra Pemasok</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Kontrak pembelian Green Beans, Susu & Dairy, Sirup Gourmet, Topping, serta Kemasan langsung dari mitra pemasok resmi.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <button type="button" 
                    @click="openSupplierModal = true" 
                    class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer active:scale-[0.97]">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <span>+ Mitra Pemasok</span>
            </button>
            <button type="button" 
                    @click="openPoModal = true" 
                    class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.97]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Terbitkan PO Baru</span>
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

    <!-- BENTO KPI CARDS (Dashboard Minimalist Style) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- CARD 1: Total Mitra Petani -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors font-sans">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider">Total Mitra Petani</span>
                <span class="text-[10px] font-mono text-neutral-400">Direct Trade</span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight" x-text="suppliers.length"></div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Gayo, Ijen, Toraja & Bajawa</span>
                <span class="font-mono text-neutral-700 font-semibold">100% Aktif</span>
            </div>
        </div>

        <!-- CARD 2: Total PO -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors font-sans">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider">Kontrak PO Pengadaan</span>
                <span class="text-[10px] font-mono text-neutral-400">Purchase Orders</span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight" x-text="purchaseOrders.length"></div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span class="text-amber-700 font-semibold" x-text="purchaseOrders.filter(p => p.status === 'ISSUED').length + ' aktif'"></span>
                <span class="text-neutral-500">dalam pengiriman</span>
            </div>
        </div>

        <!-- CARD 3: Estimasi Green Beans -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors font-sans">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider">Total Biji Terkontrak</span>
                <span class="text-[10px] font-mono text-neutral-400">Kapasitas</span>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight">{{ number_format($totalContractedBeansKg ?? 500, 0, ',', '.') }} <span class="text-sm font-normal text-neutral-400">kg</span></div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Stok aman Roastery Central Hub</span>
                <span class="text-emerald-700 font-semibold">Tersimpan</span>
            </div>
        </div>

        <!-- CARD 4: Rating Mitra -->
        <div class="bg-white border border-[#eaeaea] rounded-xl p-4 sm:p-5 hover:border-neutral-300 transition-colors font-sans">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-[11px] font-medium uppercase tracking-wider">Rata-rata Rating Panen</span>
                <svg class="w-3.5 h-3.5 text-amber-500 fill-amber-500 shrink-0" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <div class="text-2xl font-bold font-sans tabular-nums text-neutral-900 tracking-tight">{{ number_format($avgSupplierRating ?? 4.85, 2, ',', '.') }} <span class="text-sm font-normal text-neutral-400">/ 5.0</span></div>
            <div class="text-[11px] text-neutral-400 mt-2 pt-2 border-t border-[#f5f5f5] flex items-center justify-between">
                <span>Petik merah & moisture 11%</span>
                <span class="text-neutral-700 font-semibold">Specialty</span>
            </div>
        </div>
    </div>

    <!-- TOOLBAR: SEARCH & SEGMENTED TABS (Dashboard Style) -->
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative w-full sm:w-80 md:w-96">
            <div class="flex items-center w-full px-3 py-2 rounded-xl bg-white border border-[#eaeaea] text-neutral-500 text-xs hover:border-[#ccc] focus-within:border-black transition-colors font-sans">
                <svg class="w-4 h-4 text-neutral-400 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari mitra petani, lokasi, atau nomor PO..." 
                       @keydown.escape="searchQuery = ''"
                       class="bg-transparent border-none text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none flex-1 font-sans">
                <kbd class="px-1.5 py-0.5 rounded bg-neutral-100 border border-neutral-200 text-[10px] font-sans font-medium text-neutral-400">ESC</kbd>
            </div>
        </div>

        <!-- Segmented Tab Switcher (Apple Minimalist Pill) -->
        <div class="flex items-center p-0.5 rounded-lg bg-neutral-100 border border-neutral-200 text-xs shrink-0 font-sans">
            <button type="button" 
                    @click="activeTab = 'suppliers'"
                    :class="activeTab === 'suppliers' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3 py-1.5 rounded-md transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Mitra Petani Kopi</span>
                <span class="text-[10px] font-mono font-bold text-neutral-400" x-text="'(' + suppliers.length + ')'"></span>
            </button>
            <button type="button" 
                    @click="activeTab = 'po'"
                    :class="activeTab === 'po' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                    class="px-3 py-1.5 rounded-md transition-colors cursor-pointer flex items-center gap-1.5 whitespace-nowrap">
                <span>Purchase Orders (PO)</span>
                <span class="text-[10px] font-mono font-bold text-neutral-400" x-text="'(' + purchaseOrders.length + ')'"></span>
            </button>
        </div>
    </div>

    <!-- TAB 1: DAFTAR MITRA PETANI (Clean Estate Cards, No Badge Slop) -->
    <div x-show="activeTab === 'suppliers'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="sup in filteredSuppliers" :key="sup.id">
                <div class="bg-white border border-[#eaeaea] hover:border-neutral-300 rounded-xl p-5 flex flex-col justify-between transition-colors">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[10px] font-mono font-medium text-neutral-400 uppercase tracking-wider" x-text="sup.code"></span>
                                <h3 class="text-sm font-semibold text-neutral-900 mt-0.5 leading-snug" x-text="sup.name"></h3>
                            </div>
                            <span class="text-xs font-mono font-bold text-amber-700 shrink-0">
                                <span class='flex items-center gap-1'><svg class="w-3.5 h-3.5 text-amber-500 fill-amber-500 shrink-0" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg><span x-text='sup.rating_score'></span></span>
                            </span>
                        </div>

                        <div class="mt-4 space-y-1.5 text-xs text-neutral-600 font-sans">
                            <div class="flex items-center justify-between">
                                <span class="text-neutral-400">Wilayah Kebun:</span> 
                                <span class="text-neutral-800 font-medium" x-text="sup.farm_location"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-neutral-400">Elevasi Tanam:</span> 
                                <span class="font-mono text-neutral-700" x-text="sup.altitude_masl + ' mdpl'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-neutral-400">Kontak:</span> 
                                <span class="text-neutral-700" x-text="sup.contact_person + ' (' + sup.phone + ')'"></span>
                            </div>
                            <div class="pt-1.5 text-[11px] text-neutral-400 line-clamp-1 border-t border-[#f5f5f5]" x-text="sup.address"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#f5f5f5] flex items-center justify-between text-xs font-sans">
                        <span class="text-neutral-400 font-mono text-[11px]" x-text="(sup.purchase_orders_count || 0) + ' Kontrak PO'"></span>
                        <span class="inline-flex items-center gap-1.5 text-neutral-600 text-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Fair Trade Certified
                        </span>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="filteredSuppliers.length === 0" class="py-12 bg-white border border-[#eaeaea] rounded-xl text-center text-neutral-400 text-xs">
            Tidak ada mitra petani yang cocok dengan pencarian Anda.
        </div>
    </div>

    <!-- TAB 2: DAFTAR PURCHASE ORDER (PO) TABLE (Linear Grid) -->
    <div x-show="activeTab === 'po'" class="space-y-4">
        <div class="bg-white border border-[#eaeaea] rounded-xl overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#eaeaea] flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-semibold text-neutral-900 font-sans">Buku Kontrak Pengadaan Bahan Baku (PO)</h3>
                </div>
                <div class="text-xs font-mono text-neutral-400">
                    <span class="text-neutral-900 font-semibold" x-text="filteredPos.length"></span> Kontrak Tercatat
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-sans">
                    <thead>
                        <tr class="text-neutral-400 border-b border-[#eaeaea] text-[11px] uppercase tracking-wider font-semibold bg-neutral-50/50">
                            <th class="py-3 px-4">Nomor PO</th>
                            <th class="py-3 px-4">Mitra Pemasok</th>
                            <th class="py-3 px-4">Bahan Baku & Volume</th>
                            <th class="py-3 px-4">Tanggal Pesan</th>
                            <th class="py-3 px-4">Estimasi Tiba</th>
                            <th class="py-3 px-4 text-right">Nilai Kontrak</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi Penerimaan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f5f5f5]">
                        <template x-for="po in filteredPos" :key="po.id">
                            <tr class="hover:bg-neutral-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-neutral-900 whitespace-nowrap" x-text="po.po_number"></td>
                                <td class="py-3 px-4 font-medium text-neutral-800">
                                    <div x-text="po.supplier ? po.supplier.name : '-'"></div>
                                    <div class="text-[10px] text-neutral-400 font-sans" x-text="po.supplier ? po.supplier.farm_location : ''"></div>
                                </td>
                                <td class="py-3 px-4 text-neutral-600">
                                    <template x-if="po.items && po.items.length > 0">
                                        <div>
                                            <span class="font-medium text-neutral-800" x-text="po.items[0].product_name"></span>
                                            <span class="text-neutral-400 font-mono text-[11px] whitespace-nowrap" x-text="' (' + Number(po.items[0].quantity_kg) + ' ' + getPoItemUnit(po.items[0]) + ')'"></span>
                                        </div>
                                    </template>
                                    <template x-if="!po.items || po.items.length === 0">
                                        <span class="text-neutral-400">Green Beans Lot</span>
                                    </template>
                                </td>
                                <td class="py-3 px-4 text-neutral-500 font-mono whitespace-nowrap" x-text="formatDate(po.order_date)"></td>
                                <td class="py-3 px-4 text-neutral-500 font-mono whitespace-nowrap" x-text="formatDate(po.expected_delivery_date)"></td>
                                <td class="py-3 px-4 text-right font-bold text-neutral-900 font-mono tabular-nums whitespace-nowrap" x-text="'Rp ' + formatNumber(po.total_amount)"></td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium"
                                          :class="{
                                              'text-emerald-700': po.status === 'COMPLETED',
                                              'text-amber-700': po.status === 'ISSUED' || po.status === 'PARTIAL_RECEIVED',
                                              'text-neutral-500': po.status === 'DRAFT' || po.status === 'CANCELLED'
                                          }">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="{
                                                  'bg-emerald-500': po.status === 'COMPLETED',
                                                  'bg-amber-500': po.status === 'ISSUED' || po.status === 'PARTIAL_RECEIVED',
                                                  'bg-neutral-400': po.status === 'DRAFT' || po.status === 'CANCELLED'
                                              }"></span>
                                        <span x-text="po.status === 'COMPLETED' ? 'Diterima' : (po.status === 'ISSUED' ? 'Dikirim' : po.status)"></span>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <template x-if="po.status !== 'COMPLETED' && po.status !== 'CANCELLED' && isGreenBeanPo(po)">
                                        <a href="{{ route('qc-lab.index') }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 text-[11px] font-semibold transition-colors">
                                            Uji QC Lab →
                                        </a>
                                    </template>
                                    <template x-if="po.status !== 'COMPLETED' && po.status !== 'CANCELLED' && !isGreenBeanPo(po)">
                                        <button type="button"
                                                @click="openReceiveModalFor(po)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-neutral-950 hover:bg-neutral-800 text-white text-[11px] font-semibold transition-colors cursor-pointer active:scale-[0.97]">
                                            Terima Barang
                                        </button>
                                    </template>
                                    <template x-if="po.status === 'COMPLETED'">
                                        <span class="text-[11px] text-emerald-700 font-mono">✓ Masuk Stok</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredPos.length === 0">
                            <td colspan="8" class="py-12 text-center text-neutral-400 text-xs">
                                Tidak ada data Purchase Order (PO) yang sesuai.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL: Tambah Mitra Pemasok Baru -->
    <div x-show="openSupplierModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
        <div class="bg-white border border-[#eaeaea] rounded-2xl w-full max-w-lg p-6 shadow-2xl" @click.away="openSupplierModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950 font-sans">Registrasi Mitra Pemasok Baru</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Kemitraan petani kopi, distributor susu/sirup, maupun vendor kemasan.</p>
                </div>
                <button type="button" @click="openSupplierModal = false" class="text-neutral-400 hover:text-neutral-800 p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="submitSupplier()" class="mt-4 space-y-3 text-xs font-sans">
                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Nama Kelompok Tani / Distributor:</label>
                    <input type="text" x-model="supplierForm.name" required 
                           class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors" 
                           placeholder="Cth: PT Greenfields Indonesia / Koperasi Gayo" />
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Contact Person:</label>
                        <input type="text" x-model="supplierForm.contact_person" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors" 
                               placeholder="Pak Aman" />
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Nomor Telepon / WA:</label>
                        <input type="text" x-model="supplierForm.phone" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors font-mono" 
                               placeholder="0812-xxxx-xxxx" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Wilayah Kebun / Kota Distributor:</label>
                        <input type="text" x-model="supplierForm.farm_location"
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors" 
                               placeholder="Jakarta / Takengon" />
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Elevasi (mdpl, 0 jika non-kopi):</label>
                        <input type="number" min="0" x-model.number="supplierForm.altitude_masl"
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors font-mono" 
                               placeholder="0" />
                    </div>
                </div>
                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Alamat Lengkap Koperasi / Gudang:</label>
                    <textarea x-model="supplierForm.address" required rows="2" 
                              class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors" 
                              placeholder="Jl. Raya Takengon - Isaq km 5"></textarea>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openSupplierModal = false" class="px-3.5 py-2 rounded-xl border border-neutral-200 text-neutral-700 hover:bg-neutral-50 font-medium cursor-pointer transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white font-semibold cursor-pointer shadow-xs transition-colors active:scale-[0.97]">Simpan Mitra</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Terbitkan PO Baru -->
    <div x-show="openPoModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
        <div class="bg-white border border-[#eaeaea] rounded-2xl w-full max-w-md p-6 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="openPoModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950 font-sans">Terbitkan Purchase Order (PO)</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5">Pengadaan Green Beans, Susu, Sirup, Topping, atau Kemasan.</p>
                </div>
                <button type="button" @click="openPoModal = false" class="text-neutral-400 hover:text-neutral-800 p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="submitPo()" class="mt-4 space-y-3.5 text-xs font-sans">
                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Pilih Mitra Pemasok:</label>
                    <select x-model="poForm.supplier_id" required 
                            class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors">
                        <option value="">-- Pilih Mitra Pemasok --</option>
                        <template x-for="s in suppliers" :key="s.id">
                            <option :value="s.id" x-text="s.name + ' (' + s.farm_location + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Master SKU Dropdown Selector (Grouped: Green Beans + Non-Coffee) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-neutral-700 font-medium">Bahan Baku / Master SKU:</label>
                        <span class="text-[10px] text-neutral-400 font-mono">Semua Kategori</span>
                    </div>
                    <select x-model="poForm.product_id" @change="onRawMaterialChange()" required 
                            class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors font-medium">
                        <option value="">-- Pilih Master SKU Bahan Baku --</option>
                        <template x-for="group in groupedRawMaterials" :key="group.label">
                            <optgroup :label="group.label">
                                <template x-for="rm in group.items" :key="rm.id">
                                    <option :value="rm.id" x-text="'[' + rm.sku + '] ' + rm.name + ' — Acuan: Rp ' + formatNumber(rm.unit_price_ref) + '/' + rm.order_unit"></option>
                                </template>
                            </optgroup>
                        </template>
                        <option value="CUSTOM">+ Varietas Baru / Lot Khusus Petani</option>
                    </select>
                </div>

                <!-- Conditional Input if CUSTOM Variety -->
                <div x-show="poForm.product_id === 'CUSTOM'" class="p-3 bg-amber-50/70 rounded-xl border border-amber-200/80 space-y-1">
                    <label class="block text-[11px] font-semibold text-amber-900">Nama Varietas Baru / Lot Petani <span class="text-red-500">*</span>:</label>
                    <input type="text" x-model="poForm.custom_variety" :required="poForm.product_id === 'CUSTOM'"
                           class="w-full px-3 py-2 bg-white border border-amber-300 focus:border-amber-900 rounded-lg text-neutral-900 outline-none text-xs" 
                           placeholder="Contoh: Yellow Caturra Anaerobic / Ateng Super" />
                    <p class="text-[10px] text-amber-700">Akan dicatat sebagai komoditas varietas baru pada kontrak PO ini.</p>
                </div>

                <!-- Spesifikasi Sub-Lot / Catatan Vendor -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-neutral-700 font-medium">Spesifikasi Sub-Lot / Catatan Pemasok:</label>
                        <span class="text-[10px] text-neutral-400">Opsional</span>
                    </div>
                    <input type="text" x-model="poForm.sub_variety" 
                           class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors" 
                           placeholder="Contoh: Exp minimal 6 bulan / Lot #04" />
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1" x-text="'Volume (' + selectedOrderUnit + '):'"></label>
                        <input type="number" step="1" min="1" x-model.number="poForm.weight_kg" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors font-mono font-bold" 
                               placeholder="50" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-neutral-700 font-medium" x-text="'Harga Beli / ' + selectedOrderUnit + ':'"></label>
                            <span x-show="selectedReferenceCost > 0" class="text-[10px] text-emerald-700 font-mono font-semibold" x-text="'Acuan: ' + formatNumber(selectedReferenceCost)"></span>
                        </div>
                        <input type="number" step="100" min="100" x-model.number="poForm.price_per_kg" required 
                               class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors font-mono font-bold" 
                               placeholder="130000" />
                    </div>
                </div>

                <!-- Total Kontrak Highlight Box -->
                <div class="p-3.5 bg-neutral-50/80 rounded-xl border border-neutral-200/80 flex justify-between items-center font-mono">
                    <span class="text-neutral-500 text-xs font-sans">Total Nilai Kontrak:</span>
                    <span class="text-base font-bold text-neutral-950 tabular-nums" x-text="'Rp ' + formatNumber((poForm.weight_kg || 0) * (poForm.price_per_kg || 0))"></span>
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openPoModal = false" class="px-3.5 py-2 rounded-xl border border-neutral-200 text-neutral-700 hover:bg-neutral-50 font-medium cursor-pointer transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white font-semibold cursor-pointer shadow-xs transition-colors active:scale-[0.97]">Terbitkan PO</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Terima Barang PO Non-Kopi ke Gudang (Goods Receipt) -->
    <div x-show="openReceiveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs font-sans">
        <div class="bg-white border border-[#eaeaea] rounded-2xl w-full max-w-md p-6 shadow-2xl" @click.away="openReceiveModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <div>
                    <h3 class="text-sm font-bold text-neutral-950 font-sans">Konfirmasi Terima Barang (Goods Receipt)</h3>
                    <p class="text-[11px] text-neutral-400 mt-0.5" x-text="receiveTargetPo ? (receiveTargetPo.po_number + ' • ' + (receiveTargetPo.items?.[0]?.product_name ?? '')) : ''"></p>
                </div>
                <button type="button" @click="openReceiveModal = false" class="text-neutral-400 hover:text-neutral-800 p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="submitReceiveGoods()" class="mt-4 space-y-3.5 text-xs font-sans">
                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Gudang Tujuan Penyimpanan:</label>
                    <select x-model="receiveForm.warehouse_id" required
                            class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors">
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name + ' (' + (wh.branch ? wh.branch.name : 'Pusat') + ')'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-neutral-700 font-medium mb-1">Catatan Penerimaan / No. Batch Vendor:</label>
                    <input type="text" x-model="receiveForm.notes"
                           class="w-full px-3 py-2 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-neutral-900 outline-none transition-colors"
                           placeholder="Cth: Diterima utuh, suhu chiller 4°C, ED Des 2027" />
                </div>

                <div class="p-3 bg-emerald-50/70 border border-emerald-200/80 rounded-xl text-[11px] text-emerald-900">
                    Stok akan dikonversi otomatis ke satuan dasar gudang (liter → ml, kg → gram, pcs → pcs) dan dicatat pada Kartu Stok (<code>PO_RECEIPT</code>).
                </div>

                <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                    <button type="button" @click="openReceiveModal = false" class="px-3.5 py-2 rounded-xl border border-neutral-200 text-neutral-700 hover:bg-neutral-50 font-medium cursor-pointer transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white font-semibold cursor-pointer shadow-xs transition-colors active:scale-[0.97]">Konfirmasi Masuk Gudang</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function procurementApp() {
    return {
        activeTab: 'suppliers',
        searchQuery: '',
        openSupplierModal: false,
        openPoModal: false,
        openReceiveModal: false,
        alertMessage: '',
        alertType: 'success',

        suppliers: @json($suppliers),
        purchaseOrders: @json($purchaseOrders),
        rawMaterials: @json($rawMaterials),
        warehouses: @json($warehouses),
        selectedReferenceCost: 0,
        selectedOrderUnit: 'kg',
        receiveTargetPo: null,

        supplierForm: {
            name: '',
            contact_person: '',
            phone: '',
            farm_location: '',
            altitude_masl: 0,
            address: ''
        },

        poForm: {
            supplier_id: '',
            product_id: '',
            custom_variety: '',
            sub_variety: '',
            weight_kg: 50,
            price_per_kg: 130000,
            notes: ''
        },

        receiveForm: {
            warehouse_id: @json($warehouses->firstWhere('code', 'WH-ROAST-HQ')?->id ?? ($warehouses->first()?->id ?? '')),
            notes: ''
        },

        get groupedRawMaterials() {
            const map = new Map();
            for (const rm of this.rawMaterials) {
                const label = rm.category_name || 'Bahan Baku Lainnya';
                if (!map.has(label)) {
                    map.set(label, []);
                }
                map.get(label).push(rm);
            }
            return Array.from(map.entries()).map(([label, items]) => ({ label, items }));
        },

        onRawMaterialChange() {
            if (this.poForm.product_id === 'CUSTOM') {
                this.selectedReferenceCost = 0;
                this.selectedOrderUnit = 'kg';
                return;
            }
            const found = this.rawMaterials.find(rm => rm.id == this.poForm.product_id);
            if (found) {
                this.selectedReferenceCost = found.unit_price_ref;
                this.selectedOrderUnit = found.order_unit || 'kg';
                this.poForm.price_per_kg = found.unit_price_ref;
            } else {
                this.selectedReferenceCost = 0;
                this.selectedOrderUnit = 'kg';
            }
        },

        isGreenBeanPo(po) {
            const item = po.items && po.items.length > 0 ? po.items[0] : null;
            if (!item) return true;
            if (item.product && item.product.sku) {
                return item.product.sku.startsWith('RAW-GB');
            }
            return (item.product_name || '').toLowerCase().includes('green bean');
        },

        getPoItemUnit(item) {
            if (!item || !item.product) return 'kg';
            if (item.product.unit === 'gram') return 'kg';
            if (item.product.unit === 'ml') return 'liter';
            return item.product.unit || 'pcs';
        },

        openReceiveModalFor(po) {
            this.receiveTargetPo = po;
            this.receiveForm.notes = '';
            this.openReceiveModal = true;
        },

        get filteredSuppliers() {
            if (!this.searchQuery.trim()) return this.suppliers;
            const q = this.searchQuery.toLowerCase();
            return this.suppliers.filter(s => 
                (s.name && s.name.toLowerCase().includes(q)) ||
                (s.farm_location && s.farm_location.toLowerCase().includes(q)) ||
                (s.code && s.code.toLowerCase().includes(q))
            );
        },

        get filteredPos() {
            if (!this.searchQuery.trim()) return this.purchaseOrders;
            const q = this.searchQuery.toLowerCase();
            return this.purchaseOrders.filter(p => 
                (p.po_number && p.po_number.toLowerCase().includes(q)) ||
                (p.supplier && p.supplier.name && p.supplier.name.toLowerCase().includes(q)) ||
                (p.status && p.status.toLowerCase().includes(q)) ||
                (p.items && p.items[0] && p.items[0].product_name && p.items[0].product_name.toLowerCase().includes(q))
            );
        },

        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        async submitSupplier() {
            try {
                const res = await fetch("{{ route('procurement.supplier.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.supplierForm)
                });
                const data = await res.json();
                if (data.success) {
                    this.alertType = 'success';
                    this.alertMessage = data.message;
                    this.suppliers.unshift(data.supplier);
                    this.openSupplierModal = false;
                    this.supplierForm = { name: '', contact_person: '', phone: '', farm_location: '', altitude_masl: 0, address: '' };
                } else {
                    this.alertType = 'error';
                    this.alertMessage = data.message;
                }
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi kesalahan jaringan.';
            }
        },

        async submitPo() {
            if (!this.poForm.product_id) {
                alert('Silakan pilih Master SKU Bahan Baku atau Varietas Baru.');
                return;
            }
            if (this.poForm.product_id === 'CUSTOM' && !this.poForm.custom_variety.trim()) {
                alert('Nama varietas baru / lot petani wajib diisi.');
                return;
            }

            let varietyName = '';
            if (this.poForm.product_id === 'CUSTOM') {
                varietyName = this.poForm.custom_variety.trim();
            } else {
                const found = this.rawMaterials.find(rm => rm.id == this.poForm.product_id);
                varietyName = found ? found.name : '';
            }

            const payload = {
                supplier_id: this.poForm.supplier_id,
                product_id: this.poForm.product_id === 'CUSTOM' ? null : this.poForm.product_id,
                variety: varietyName,
                custom_variety: this.poForm.custom_variety,
                sub_variety: this.poForm.sub_variety,
                weight_kg: this.poForm.weight_kg,
                price_per_kg: this.poForm.price_per_kg,
                notes: this.poForm.sub_variety ? ('Sub-lot: ' + this.poForm.sub_variety) : ''
            };

            try {
                const res = await fetch("{{ route('procurement.po.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    this.alertType = 'success';
                    this.alertMessage = data.message;
                    this.purchaseOrders.unshift(data.po);
                    this.activeTab = 'po';
                    this.openPoModal = false;
                    this.poForm = { supplier_id: '', product_id: '', custom_variety: '', sub_variety: '', weight_kg: 50, price_per_kg: 130000, notes: '' };
                    this.selectedReferenceCost = 0;
                    this.selectedOrderUnit = 'kg';
                } else {
                    this.alertType = 'error';
                    this.alertMessage = data.message;
                }
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi kesalahan jaringan.';
            }
        },

        async submitReceiveGoods() {
            if (!this.receiveTargetPo) return;
            try {
                const res = await fetch(`/procurement/po/${this.receiveTargetPo.id}/receive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.receiveForm)
                });
                const data = await res.json();
                if (data.success) {
                    this.alertType = 'success';
                    this.alertMessage = data.message;
                    const idx = this.purchaseOrders.findIndex(p => p.id === this.receiveTargetPo.id);
                    if (idx !== -1 && data.po) {
                        this.purchaseOrders[idx] = data.po;
                    }
                    this.openReceiveModal = false;
                    this.receiveTargetPo = null;
                } else {
                    this.alertType = 'error';
                    this.alertMessage = data.message;
                }
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi kesalahan saat memproses penerimaan barang.';
            }
        }
    };
}
</script>
@endsection
