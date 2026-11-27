@extends('layouts.app')

@section('title', 'Pengaturan Sistem & Master Data Center - KAFEINA ERP')

@section('content')
<div x-data="{
        activeTab: '{{ $activeTab }}',
        branchModalOpen: false,
        editBranchModalOpen: false,
        warehouseModalOpen: false,
        editWarehouseModalOpen: false,
        userModalOpen: false,
        editUserModalOpen: false,
        resetPasswordModalOpen: false,
        resetPinModalOpen: false,
        dangerResetModalOpen: false,
        showServerKey: false,
        selectedBranch: {},
        selectedWarehouse: {},
        selectedUser: {}
    }" 
     class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans">
    
    <!-- NOTIFIKASI SUCCESS / ERROR FLASH -->
    @if(session('success'))
    <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200/90 text-emerald-900 text-xs flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        <button @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-sm font-bold">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200/90 text-rose-900 text-xs flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
        <button @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-800 text-sm font-bold">&times;</button>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200/90 text-rose-900 text-xs shadow-2xs">
        <div class="font-bold mb-1">Periksa kembali input pengaturan:</div>
        <ul class="list-disc pl-4 space-y-0.5 text-[11px]">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- PAGE HEADER -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-neutral-400 mb-1">
                <span>Kafeina ERP</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-600 font-medium">Administration</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-400 font-mono text-[11px]">System Settings</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">Pengaturan Sistem & Master Data</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Kontrol terpusat profil entitas, master cabang & gudang, manajemen staf/RBAC, kredensial pembayaran, dan tata kelola basis data.</p>
        </div>

        <!-- QUICK BACKUP ACTION -->
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('settings.database.backup') }}" 
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-800 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Cadangkan Database (.sql)</span>
            </a>
        </div>
    </div>

    <!-- TAB HEADER NAVIGATION (Vercel Style Clean Pill Strip) -->
    <div class="mb-6 border-b border-[#eaeaea] flex items-center flex-wrap gap-1 pb-px">
        <button @click="activeTab = 'branches'"
                :class="activeTab === 'branches' ? 'border-neutral-900 text-neutral-900 font-semibold bg-white shadow-2xs' : 'border-transparent text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100/50'"
                class="px-2.5 py-2 rounded-t-xl text-[11px] flex items-center gap-1.5 border-b-2 transition-colors cursor-pointer whitespace-nowrap">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span>1. Profil & Cabang</span>
            <span class="px-1.5 py-0.2 rounded bg-neutral-100 text-[10px] font-mono text-neutral-600">{{ $branches->count() }}</span>
        </button>

        <button @click="activeTab = 'warehouses'"
                :class="activeTab === 'warehouses' ? 'border-neutral-900 text-neutral-900 font-semibold bg-white shadow-2xs' : 'border-transparent text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100/50'"
                class="px-2.5 py-2 rounded-t-xl text-[11px] flex items-center gap-1.5 border-b-2 transition-colors cursor-pointer whitespace-nowrap">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span>2. Gudang</span>
            <span class="px-1.5 py-0.2 rounded bg-neutral-100 text-[10px] font-mono text-neutral-600">{{ $warehouses->count() }}</span>
        </button>

        <button @click="activeTab = 'users'"
                :class="activeTab === 'users' ? 'border-neutral-900 text-neutral-900 font-semibold bg-white shadow-2xs' : 'border-transparent text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100/50'"
                class="px-2.5 py-2 rounded-t-xl text-[11px] flex items-center gap-1.5 border-b-2 transition-colors cursor-pointer whitespace-nowrap">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span>3. Staf & RBAC</span>
            <span class="px-1.5 py-0.2 rounded bg-neutral-100 text-[10px] font-mono text-neutral-600">{{ $users->count() }}</span>
        </button>

        <button @click="activeTab = 'payment'"
                :class="activeTab === 'payment' ? 'border-neutral-900 text-neutral-900 font-semibold bg-white shadow-2xs' : 'border-transparent text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100/50'"
                class="px-2.5 py-2 rounded-t-xl text-[11px] flex items-center gap-1.5 border-b-2 transition-colors cursor-pointer whitespace-nowrap">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <span>4. Pembayaran & Pajak</span>
        </button>

        <button @click="activeTab = 'database'"
                :class="activeTab === 'database' ? 'border-neutral-900 text-neutral-900 font-semibold bg-white shadow-2xs' : 'border-transparent text-neutral-500 hover:text-neutral-800 hover:bg-neutral-100/50'"
                class="px-2.5 py-2 rounded-t-xl text-[11px] flex items-center gap-1.5 border-b-2 transition-colors cursor-pointer whitespace-nowrap">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
            </svg>
            <span>5. Database & Audit</span>
            <span class="px-1.5 py-0.2 rounded bg-neutral-100 text-[10px] font-mono text-neutral-600">{{ $tableStats['audit_logs'] }}</span>
        </button>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 1: PROFIL PERUSAHAAN & MASTER CABANG                          -->
    <!-- ================================================================= -->
    <div x-show="activeTab === 'branches'" x-cloak class="space-y-6">
        <!-- 1.1 Profil Legal Perusahaan -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-bold text-neutral-950">Profil Legal & Identitas Bisnis</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Informasi perusahaan untuk faktur pengadaan, dokumen surat jalan logistik, dan pelaporan pajak.</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-neutral-100 text-neutral-600">Company Profile</span>
            </div>

            <form method="POST" action="{{ route('settings.company.update') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nama Legal Entitas / PT</label>
                    <input type="text" name="company_name" 
                           value="{{ \App\Models\SystemSetting::get('company_name', 'PT Kafeina Roastery Indonesia') }}" 
                           required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-all font-sans">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nomor NPWP Perusahaan</label>
                    <input type="text" name="company_npwp" 
                           value="{{ \App\Models\SystemSetting::get('company_npwp', '01.234.567.8-012.000') }}" 
                           class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-all font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Telepon Kantor / Hotline</label>
                    <input type="text" name="company_phone" 
                           value="{{ \App\Models\SystemSetting::get('company_phone', '021-7890123') }}" 
                           required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-all font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Email Resmi Manajemen</label>
                    <input type="email" name="company_email" 
                           value="{{ \App\Models\SystemSetting::get('company_email', 'management@kafeina.id') }}" 
                           required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-all font-mono">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Alamat Kantor Pusat & Sentral Pabrik</label>
                    <input type="text" name="company_address" 
                           value="{{ \App\Models\SystemSetting::get('company_address', 'Kawasan Industri Cilandak Commercial Estate Kav. 12, Jakarta Selatan') }}" 
                           required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-all font-sans">
                </div>
                <div class="sm:col-span-2 lg:col-span-3 flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                        Simpan Profil Perusahaan
                    </button>
                </div>
            </form>
        </div>

        <!-- 1.2 Master Cabang (Branches) -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-neutral-950">Daftar Cabang Resmi (Official Branches)</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Kelola entitas fisik pabrik sentral dan seluruh gerai ritel spesialisasi kopi Kafeina.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('settings.database.export', 'branches') }}" 
                       class="px-3 py-1.5 rounded-xl border border-neutral-200 text-neutral-700 hover:text-black hover:bg-neutral-50 text-xs font-medium flex items-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Ekspor CSV
                    </a>
                    <button @click="branchModalOpen = true" 
                            class="px-3.5 py-1.5 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Cabang Baru
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto border border-neutral-100 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-neutral-50/80 text-neutral-500 uppercase tracking-wider font-semibold border-b border-neutral-200/80 text-[10px]">
                        <tr>
                            <th class="px-3 py-2.5 font-medium whitespace-nowrap">Kode</th>
                            <th class="px-3 py-2.5 font-medium whitespace-nowrap">Nama Cabang</th>
                            <th class="px-3 py-2.5 font-medium whitespace-nowrap">Tipe</th>
                            <th class="px-3 py-2.5 font-medium whitespace-nowrap">Alamat & Kontak</th>
                            <th class="px-2.5 py-2.5 font-medium text-center whitespace-nowrap">Gudang</th>
                            <th class="px-2.5 py-2.5 font-medium text-center whitespace-nowrap">Staf</th>
                            <th class="px-2.5 py-2.5 font-medium text-center whitespace-nowrap">Status</th>
                            <th class="px-3 py-2.5 font-medium text-right whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($branches as $branch)
                        <tr class="hover:bg-neutral-50/60 transition-colors {{ !$branch->is_active ? 'opacity-60 bg-neutral-50/30' : '' }}">
                            <td class="px-3 py-2.5 font-mono font-bold text-neutral-900 whitespace-nowrap">{{ $branch->code }}</td>
                            <td class="px-3 py-2.5 max-w-[180px]">
                                <div class="font-semibold text-neutral-900 truncate" title="{{ $branch->name }}">{{ $branch->name }}</div>
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                @if($branch->type === 'CENTRAL_ROASTERY')
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-100 text-amber-900 border border-amber-200/80">CENTRAL ROASTERY</span>
                                @else
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-100 text-blue-900 border border-blue-200/80">RETAIL BRANCH</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-neutral-600 max-w-[175px]">
                                <div class="truncate" title="{{ $branch->address }}">{{ $branch->address }}</div>
                                <div class="text-[10px] text-neutral-400 font-mono whitespace-nowrap">{{ $branch->phone ?? '-' }}</div>
                            </td>
                            <td class="px-2.5 py-2.5 text-center font-mono font-semibold whitespace-nowrap">{{ $branch->warehouses_count }}</td>
                            <td class="px-2.5 py-2.5 text-center font-mono font-semibold whitespace-nowrap">{{ $branch->users_count }}</td>
                            <td class="px-2.5 py-2.5 text-center whitespace-nowrap">
                                @if($branch->is_active)
                                    <span class="inline-flex items-center whitespace-nowrap gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center whitespace-nowrap gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-neutral-100 text-neutral-500 border border-neutral-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="selectedBranch = {{ json_encode($branch) }}; editBranchModalOpen = true" 
                                            class="p-1.5 text-neutral-500 hover:text-black hover:bg-neutral-100 rounded-lg transition-colors cursor-pointer" title="Edit Cabang">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    @if($branch->code !== 'HQ-CENTRAL')
                                    <form method="POST" action="{{ route('settings.branch.toggle', $branch->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                onclick="return confirm('Apakah Anda yakin ingin mengubah status cabang ini?')"
                                                class="p-1.5 text-neutral-400 hover:text-rose-600 hover:bg-neutral-100 rounded-lg transition-colors cursor-pointer" 
                                                title="{{ $branch->is_active ? 'Nonaktifkan Cabang' : 'Aktifkan Cabang' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 2: MASTER GUDANG & ALOKASI STOK                               -->
    <!-- ================================================================= -->
    <div x-show="activeTab === 'warehouses'" x-cloak class="space-y-6">
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-neutral-950">Daftar Gudang Baku Kafeina (Official Warehouses)</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Pemetaan gudang fisik green beans, roasted beans, in-transit ekspedisi, dan stok bar aktif cabang.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('settings.database.export', 'warehouses') }}" 
                       class="px-3 py-1.5 rounded-xl border border-neutral-200 text-neutral-700 hover:text-black hover:bg-neutral-50 text-xs font-medium flex items-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Ekspor CSV
                    </a>
                    <button @click="warehouseModalOpen = true" 
                            class="px-3.5 py-1.5 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Gudang Baru
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto border border-neutral-100 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-neutral-50/80 text-neutral-500 uppercase tracking-wider font-semibold border-b border-neutral-200/80">
                        <tr>
                            <th class="px-4 py-3 font-medium">Kode Gudang</th>
                            <th class="px-4 py-3 font-medium">Nama Gudang</th>
                            <th class="px-4 py-3 font-medium">Cabang Pemilik</th>
                            <th class="px-4 py-3 font-medium">Tipe Gudang</th>
                            <th class="px-4 py-3 font-medium text-center">Varian Stok</th>
                            <th class="px-4 py-3 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($warehouses as $wh)
                        <tr class="hover:bg-neutral-50/60 transition-colors">
                            <td class="px-4 py-3 font-mono font-bold text-neutral-900">{{ $wh->code }}</td>
                            <td class="px-4 py-3 font-semibold text-neutral-900">{{ $wh->name }}</td>
                            <td class="px-4 py-3 text-neutral-600">
                                <span class="font-mono text-neutral-400 text-[11px] mr-1">[{{ $wh->branch?->code ?? '-' }}]</span>
                                <span>{{ $wh->branch?->name ?? 'Tidak Terikat' }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($wh->type === 'CENTRAL_RAW')
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-100 text-emerald-900 border border-emerald-200/80">CENTRAL RAW</span>
                                @elseif($wh->type === 'CENTRAL_ROASTED')
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-100 text-amber-900 border border-amber-200/80">CENTRAL ROASTED</span>
                                @elseif($wh->type === 'TRANSIT')
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-md text-[10px] font-semibold bg-purple-100 text-purple-900 border border-purple-200/80">IN-TRANSIT BUFFER</span>
                                @else
                                    <span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-100 text-blue-900 border border-blue-200/80">RETAIL BAR</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-mono font-semibold">{{ $wh->stocks_count }} item</td>
                            <td class="px-4 py-3 text-right">
                                <button @click="selectedWarehouse = {{ json_encode($wh) }}; editWarehouseModalOpen = true" 
                                        class="p-1.5 text-neutral-500 hover:text-black hover:bg-neutral-100 rounded-lg transition-colors cursor-pointer" title="Edit Gudang">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 3: STAF, AKUN & HAK AKSES (RBAC)                              -->
    <!-- ================================================================= -->
    <div x-show="activeTab === 'users'" x-cloak class="space-y-6">
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-neutral-950">Manajemen Karyawan & Hak Akses (User RBAC)</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Kelola akun staf, penempatan cabang aktif, peran operasional, dan reset PIN 6-digit kasir.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('settings.database.export', 'users') }}" 
                       class="px-3 py-1.5 rounded-xl border border-neutral-200 text-neutral-700 hover:text-black hover:bg-neutral-50 text-xs font-medium flex items-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Ekspor CSV
                    </a>
                    <button @click="userModalOpen = true" 
                            class="px-3.5 py-1.5 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Tambah Karyawan Baru
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto border border-neutral-100 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-neutral-50/80 text-neutral-500 uppercase tracking-wider font-semibold border-b border-neutral-200/80">
                        <tr>
                            <th class="px-4 py-3 font-medium">Karyawan / Staf</th>
                            <th class="px-4 py-3 font-medium">Peran (Role)</th>
                            <th class="px-4 py-3 font-medium">Penugasan Cabang</th>
                            <th class="px-4 py-3 font-medium text-center">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Aksi & Kredensial</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($users as $u)
                        <tr class="hover:bg-neutral-50/60 transition-colors {{ !$u->is_active ? 'opacity-60 bg-neutral-50/30' : '' }}">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-neutral-900">{{ $u->name }}</div>
                                <div class="text-[11px] text-neutral-500 font-mono">{{ $u->email }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-neutral-100 text-neutral-800 border border-neutral-200">
                                    {{ $u->role?->display_name ?? $u->role?->name ?? 'No Role' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-neutral-700">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono text-neutral-400 text-[11px]">[{{ $u->branch?->code ?? '-' }}]</span>
                                    <span>{{ $u->branch?->name ?? 'Semua Cabang (Global)' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-neutral-100 text-neutral-500 border border-neutral-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="selectedUser = {{ json_encode($u) }}; editUserModalOpen = true" 
                                            class="px-2 py-1 text-[11px] text-neutral-600 hover:text-black hover:bg-neutral-100 rounded-lg transition-colors cursor-pointer">
                                        Edit
                                    </button>
                                    <button @click="selectedUser = {{ json_encode($u) }}; resetPasswordModalOpen = true" 
                                            class="px-2 py-1 text-[11px] text-neutral-600 hover:text-black hover:bg-neutral-100 rounded-lg transition-colors cursor-pointer" title="Reset Password">
                                        Pass
                                    </button>
                                    <button @click="selectedUser = {{ json_encode($u) }}; resetPinModalOpen = true" 
                                            class="px-2 py-1 text-[11px] text-neutral-600 hover:text-black hover:bg-neutral-100 rounded-lg transition-colors cursor-pointer font-mono" title="Reset PIN Kasir">
                                        PIN
                                    </button>
                                    @if($u->id !== $currentUser?->id)
                                    <form method="POST" action="{{ route('settings.user.toggle', $u->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                onclick="return confirm('Ubah status aktif akun {{ $u->name }}?')"
                                                class="px-2 py-1 text-[11px] {{ $u->is_active ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded-lg transition-colors cursor-pointer">
                                            {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 4: PEMBAYARAN, MIDTRANS & PARAMETER SISTEM                    -->
    <!-- ================================================================= -->
    <div x-show="activeTab === 'payment'" x-cloak class="space-y-6">
        <form method="POST" action="{{ route('settings.payment.update') }}" class="space-y-6">
            @csrf

            <!-- 4.1 Midtrans Gateway Settings -->
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
                <div class="mb-4">
                    <h2 class="text-sm font-bold text-neutral-950">Integrasi Payment Gateway Midtrans (Dynamic QRIS)</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Konfigurasi kredensial API Midtrans untuk penerbitan barcode QRIS otomatis dan verifikasi pelunasan kasir.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Midtrans Server Key -->
                    @php
                        $storedServerKey = (string) \App\Models\SystemSetting::get('midtrans_server_key', config('services.midtrans.server_key', ''));
                        $maskedServerKeyHint = $storedServerKey !== ''
                            ? 'Tersimpan (••••' . substr($storedServerKey, -4) . ') — kosongkan jika tidak diubah'
                            : 'SB-Mid-server-...';
                    @endphp
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Midtrans Server Key (Secret)</label>
                        <div class="relative">
                            <input :type="showServerKey ? 'text' : 'password'" 
                                   name="midtrans_server_key" 
                                   value=""
                                   autocomplete="new-password"
                                   placeholder="{{ $maskedServerKeyHint }}"
                                   class="w-full px-3 py-2 pr-16 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-colors font-mono">
                            <button type="button" 
                                    @click="showServerKey = !showServerKey" 
                                    class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 text-[11px] text-neutral-500 hover:text-black transition-colors font-mono cursor-pointer">
                                <span x-text="showServerKey ? 'Sembunyikan' : 'Lihat'"></span>
                            </button>
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1">Kunci rahasia terenkripsi di server. Biarkan kosong jika tidak ingin mengganti kunci aktif.</p>
                    </div>

                    <!-- Midtrans Client Key -->
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Midtrans Client Key (Public)</label>
                        <input type="text" 
                               name="midtrans_client_key" 
                               value="{{ \App\Models\SystemSetting::get('midtrans_client_key', config('services.midtrans.client_key', '')) }}" 
                               placeholder="Mid-client-..."
                               class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-colors font-mono">
                        <p class="text-[11px] text-neutral-400 mt-1">Kunci publik frontend untuk memuat komponen Snap popup jika diperlukan.</p>
                    </div>

                    <!-- Switch Mode Sandbox vs Production -->
                    <div class="md:col-span-2 pt-2 border-t border-neutral-100 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold text-neutral-900">Mode Produksi Midtrans (Production Live)</div>
                            <div class="text-[11px] text-neutral-500">Aktifkan hanya jika akun Midtrans telah disetujui untuk transaksi uang nyata. Biarkan nonaktif untuk sandbox pengujian.</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="midtrans_is_production" value="1" 
                                   {{ \App\Models\SystemSetting::get('midtrans_is_production', false) ? 'checked' : '' }} 
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-neutral-900"></div>
                        </label>
                    </div>

                    <!-- Webhook URL Display -->
                    <div class="md:col-span-2 bg-neutral-50 border border-neutral-200/80 rounded-xl p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <div class="text-[11px] font-semibold text-neutral-600 uppercase font-mono">Midtrans Notification Webhook Endpoint</div>
                            <div class="text-xs font-mono text-neutral-900 select-all">{{ url('/api/midtrans/webhook') }}</div>
                        </div>
                        <button type="button" 
                                @click="navigator.clipboard.writeText('{{ url('/api/midtrans/webhook') }}'); alert('Webhook URL berhasil disalin!')"
                                class="px-3 py-1.5 rounded-lg bg-white border border-neutral-200 text-neutral-700 hover:text-black text-xs font-medium shrink-0 cursor-pointer shadow-2xs">
                            Salin URL Webhook
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4.2 Pajak & Finansial -->
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
                <h2 class="text-sm font-bold text-neutral-950 mb-1">Kebijakan Pajak Restoran & Biaya Layanan</h2>
                <p class="text-xs text-neutral-500 mb-4">Pengaturan tarif persentase otomatis yang diterapkan pada kasir POS ritel.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Tarif Pajak Restoran (PB1 %)</label>
                        <div class="relative">
                            <input type="number" step="0.1" name="tax_pb1_rate" 
                                   value="{{ \App\Models\SystemSetting::get('tax_pb1_rate', 11.0) }}" 
                                   required class="w-full px-3 py-2 pr-8 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-colors font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 font-mono text-xs">%</span>
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1">Tarif pajak restoran standar pemerintah daerah (default: 11%).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Service Charge (%)</label>
                        <div class="relative">
                            <input type="number" step="0.1" name="service_charge_rate" 
                                   value="{{ \App\Models\SystemSetting::get('service_charge_rate', 0.0) }}" 
                                   required class="w-full px-3 py-2 pr-8 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-colors font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 font-mono text-xs">%</span>
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1">Biaya pelayanan tambahan untuk pesanan ritel (opsional: 0-5%).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Batas Bawah Susut Sangrai (%)</label>
                        <div class="relative">
                            <input type="number" step="0.1" name="roastery_min_yield_loss" 
                                   value="{{ \App\Models\SystemSetting::get('roastery_min_yield_loss', 14.0) }}" 
                                   required class="w-full px-3 py-2 pr-8 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-colors font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 font-mono text-xs">%</span>
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1">Toleransi minimum susut green bean ke roasted bean (default: 14%).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Batas Atas Susut Sangrai (%)</label>
                        <div class="relative">
                            <input type="number" step="0.1" name="roastery_max_yield_loss" 
                                   value="{{ \App\Models\SystemSetting::get('roastery_max_yield_loss', 18.0) }}" 
                                   required class="w-full px-3 py-2 pr-8 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-colors font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 font-mono text-xs">%</span>
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1">Toleransi maksimum susut sangrai sebelum ditandai anomali (default: 18%).</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Ambang Peringatan Stok Minimum Default (kg)</label>
                        <div class="relative">
                            <input type="number" step="1" name="inventory_default_safety_stock" 
                                   value="{{ \App\Models\SystemSetting::get('inventory_default_safety_stock', 100.0) }}" 
                                   required class="w-full px-3 py-2 pr-10 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 focus:bg-white transition-all font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 font-mono text-xs">kg</span>
                        </div>
                        <p class="text-[11px] text-neutral-400 mt-1">Batas safety stock ROP untuk memicu peringatan restock di tabel stok.</p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                        Simpan Semua Pengaturan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 5: DATABASE & JEJAK AUDIT                                     -->
    <!-- ================================================================= -->
    <div x-show="activeTab === 'database'" x-cloak class="space-y-6">
        <!-- 5.1 Metrik Ukuran & Status Database -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 shadow-2xs">
                <div class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider mb-1">Estimasi Ukuran Basis Data</div>
                <div class="text-2xl font-bold font-mono text-neutral-900 tabular-nums">{{ $dbSizeMb }} <span class="text-xs font-normal text-neutral-500">MB</span></div>
                <div class="text-[11px] text-neutral-400 mt-1">MySQL Engine InnoDb</div>
            </div>
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 shadow-2xs">
                <div class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider mb-1">Total Mutasi Kartu Stok</div>
                <div class="text-2xl font-bold font-mono text-neutral-900 tabular-nums">{{ number_format($tableStats['stock_cards'], 0, ',', '.') }}</div>
                <div class="text-[11px] text-neutral-400 mt-1">Jejak Ledger Persediaan</div>
            </div>
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 shadow-2xs">
                <div class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider mb-1">Total Transaksi POS Kasir</div>
                <div class="text-2xl font-bold font-mono text-neutral-900 tabular-nums">{{ number_format($tableStats['pos_orders'], 0, ',', '.') }}</div>
                <div class="text-[11px] text-neutral-400 mt-1">Penjualan Seluruh Gerai</div>
            </div>
            <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 shadow-2xs">
                <div class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider mb-1">Total Catatan Audit Log</div>
                <div class="text-2xl font-bold font-mono text-neutral-900 tabular-nums">{{ number_format($tableStats['audit_logs'], 0, ',', '.') }}</div>
                <div class="text-[11px] text-neutral-400 mt-1">Aktivitas Terinspeksi</div>
            </div>
        </div>

        <!-- 5.2 Alat Ekspor & Cadangan Basis Data -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 sm:p-6 shadow-2xs">
            <div class="mb-4">
                <h2 class="text-sm font-bold text-neutral-950">Pencadangan & Ekspor Data Master (Data Governance)</h2>
                <p class="text-xs text-neutral-500 mt-0.5">Unduh snapshot basis data SQL lengkap atau ekspor data per modul ke format CSV untuk arsip dan pembukuan eksternal.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- 1. Cadangan SQL Dump Penuh -->
                <div class="p-4 rounded-xl bg-neutral-50/80 border border-neutral-200/80 flex flex-col justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-4 h-4 text-neutral-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                            </svg>
                            <span class="text-xs font-semibold text-neutral-900">Snapshot Basis Data Penuh</span>
                        </div>
                        <p class="text-[11px] text-neutral-500 leading-relaxed">
                            Cadangkan seluruh skema dan record data tabel (PO, QC, Sangrai, Surat Jalan, Kasir POS, dan Stok) dalam format SQL standar.
                        </p>
                    </div>
                    <a href="{{ route('settings.database.backup') }}" 
                       class="self-start px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Unduh Database (.sql)</span>
                    </a>
                </div>

                <!-- 2. Ekspor CSV Terpilah -->
                <div class="p-4 rounded-xl bg-neutral-50/80 border border-neutral-200/80 flex flex-col justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-4 h-4 text-neutral-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-xs font-semibold text-neutral-900">Ekspor Modul ke Format CSV</span>
                        </div>
                        <p class="text-[11px] text-neutral-500 leading-relaxed">
                            Pilih modul data yang ingin diunduh sebagai lembar kerja spreadsheet:
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('settings.database.export', 'branches') }}" 
                           class="px-3 py-1.5 rounded-lg bg-white border border-neutral-200 hover:border-neutral-900 text-xs text-neutral-700 hover:text-black font-medium transition-colors shadow-2xs flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span> Cabang
                        </a>
                        <a href="{{ route('settings.database.export', 'warehouses') }}" 
                           class="px-3 py-1.5 rounded-lg bg-white border border-neutral-200 hover:border-neutral-900 text-xs text-neutral-700 hover:text-black font-medium transition-colors shadow-2xs flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span> Gudang
                        </a>
                        <a href="{{ route('settings.database.export', 'users') }}" 
                           class="px-3 py-1.5 rounded-lg bg-white border border-neutral-200 hover:border-neutral-900 text-xs text-neutral-700 hover:text-black font-medium transition-colors shadow-2xs flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span> Staf / Akun
                        </a>
                        <a href="{{ route('settings.database.export', 'products') }}" 
                           class="px-3 py-1.5 rounded-lg bg-white border border-neutral-200 hover:border-neutral-900 text-xs text-neutral-700 hover:text-black font-medium transition-colors shadow-2xs flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span> Menu Produk
                        </a>
                        <a href="{{ route('settings.database.export', 'audit_logs') }}" 
                           class="px-3 py-1.5 rounded-lg bg-white border border-neutral-200 hover:border-neutral-900 text-xs text-neutral-700 hover:text-black font-medium transition-colors shadow-2xs flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span> Audit Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5.3 Jejak Audit Terintegrasi (Audit Logs Viewer) -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-bold text-neutral-950">Jejak Aktivitas & Integritas Sistem (Audit Trail)</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Catatan forensik setiap pembuatan, perubahan, atau penghapusan data penting.</p>
                </div>
                <span class="text-xs text-neutral-400 font-mono">Menampilkan 35 peristiwa terakhir</span>
            </div>

            <div class="overflow-x-auto border border-neutral-100 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-neutral-50/80 text-neutral-500 uppercase tracking-wider font-semibold border-b border-neutral-200/80">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Waktu</th>
                            <th class="px-4 py-2.5 font-medium">Tipe Event</th>
                            <th class="px-4 py-2.5 font-medium">Entitas</th>
                            <th class="px-4 py-2.5 font-medium">Pengguna</th>
                            <th class="px-4 py-2.5 font-medium">Alamat IP</th>
                            <th class="px-4 py-2.5 font-medium">Rincian Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 font-mono text-[11px]">
                        @forelse($auditLogs as $log)
                        <tr class="hover:bg-neutral-50/60 transition-colors">
                            <td class="px-4 py-2.5 text-neutral-400">{{ $log->created_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td class="px-4 py-2.5">
                                @if($log->event_type === 'INSERT')
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-semibold">INSERT</span>
                                @elseif($log->event_type === 'UPDATE')
                                    <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-semibold">UPDATE</span>
                                @elseif($log->event_type === 'DELETE')
                                    <span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 font-semibold">DELETE</span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">{{ $log->event_type }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 font-semibold text-neutral-900">
                                {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                            </td>
                            <td class="px-4 py-2.5 text-neutral-700 font-sans">
                                {{ $log->user?->name ?? 'System Automated' }}
                            </td>
                            <td class="px-4 py-2.5 text-neutral-400">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                            @php
                                $safeLogKeys = array_keys((array) $log->new_values);
                                $rawLogJson = (string) json_encode($log->new_values);
                                $isSensitiveLog = class_basename((string) $log->auditable_type) === 'SystemSetting'
                                    || collect($safeLogKeys)->contains(fn ($k) => preg_match('/key|secret|password|pin|token/i', (string) $k))
                                    || preg_match('/mid-server|sb-mid|secret/i', $rawLogJson);
                            @endphp
                            <td class="px-4 py-2.5 text-neutral-500 max-w-xs truncate" title="{{ $isSensitiveLog ? '[REDACTED]' : $rawLogJson }}">
                                @if($log->event_type === 'UPDATE' && !empty($log->new_values))
                                    Diubah: {{ implode(', ', $safeLogKeys) }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-neutral-400 font-sans">Belum ada aktivitas audit yang terekam.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5.4 Zona Bahaya Terkendali (Danger Zone Guard) -->
        <div class="bg-rose-50/40 border border-rose-200/80 rounded-2xl p-5 shadow-2xs">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <h2 class="text-sm font-bold text-rose-950">Zona Bahaya Terkendali (Controlled Danger Zone)</h2>
                    </div>
                    <p class="text-xs text-rose-800/80 mt-1 max-w-2xl">
                        Fitur reset basis data hanya dapat dieksekusi pada lingkungan lokal (<code class="font-mono text-[11px] bg-rose-100 px-1 py-0.5 rounded">APP_ENV=local</code>). Tindakan ini akan mengosongkan seluruh transaksi dan men-generate ulang data master kanonikal bersih Kafeina.
                    </p>
                </div>
                @if(app()->environment('local'))
                <button @click="dangerResetModalOpen = true" 
                        class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shrink-0 shadow-xs transition-colors cursor-pointer">
                    Reset Database Lokal
                </button>
                @else
                <span class="px-3 py-1.5 rounded-xl bg-neutral-200 text-neutral-500 text-xs font-semibold shrink-0 cursor-not-allowed">
                    Terkunci di Produksi
                </span>
                @endif
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL POPUPS (Vercel Style Clean Monochromatic Modals)            -->
    <!-- ================================================================= -->

    <!-- MODAL 1: Tambah Cabang Baru -->
    <div x-show="branchModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="branchModalOpen = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white border border-[#eaeaea] rounded-2xl p-6 max-w-lg w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-sm font-bold text-neutral-950">Tambah Cabang Resmi Baru</h3>
                <button @click="branchModalOpen = false" class="text-neutral-400 hover:text-black font-mono text-base">&times;</button>
            </div>
            <form method="POST" action="{{ route('settings.branch.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Kode Cabang (Unik)</label>
                        <input type="text" name="code" placeholder="Misal: OUT-SBY" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 uppercase font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Tipe Cabang</label>
                        <select name="type" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                            <option value="RETAIL_BRANCH">RETAIL_BRANCH (Gerai Ritel)</option>
                            <option value="CENTRAL_ROASTERY">CENTRAL_ROASTERY (Pabrik Sentral)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nama Cabang / Gerai</label>
                    <input type="text" name="name" placeholder="Misal: Kafeina Tunjungan Surabaya" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Alamat Lengkap</label>
                    <textarea name="address" rows="2" placeholder="Jl. Tunjungan No. 10..." required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nomor Telepon Kontak</label>
                    <input type="text" name="phone" placeholder="031-..." class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 font-mono">
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                    <button type="button" @click="branchModalOpen = false" class="px-3 py-2 rounded-xl text-xs text-neutral-600 hover:text-black">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold">Simpan Cabang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Cabang -->
    <div x-show="editBranchModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="editBranchModalOpen = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white border border-[#eaeaea] rounded-2xl p-6 max-w-lg w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-sm font-bold text-neutral-950">Edit Cabang: <span x-text="selectedBranch.code" class="font-mono"></span></h3>
                <button @click="editBranchModalOpen = false" class="text-neutral-400 hover:text-black font-mono text-base">&times;</button>
            </div>
            <form method="POST" :action="'{{ url('/settings/branch') }}/' + selectedBranch.id" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nama Cabang</label>
                    <input type="text" name="name" x-model="selectedBranch.name" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Tipe Cabang</label>
                    <select name="type" x-model="selectedBranch.type" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                        <option value="RETAIL_BRANCH">RETAIL_BRANCH (Gerai Ritel)</option>
                        <option value="CENTRAL_ROASTERY">CENTRAL_ROASTERY (Pabrik Sentral)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Alamat Lengkap</label>
                    <textarea name="address" rows="2" x-model="selectedBranch.address" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nomor Telepon Kontak</label>
                    <input type="text" name="phone" x-model="selectedBranch.phone" class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 font-mono">
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                    <button type="button" @click="editBranchModalOpen = false" class="px-3 py-2 rounded-xl text-xs text-neutral-600 hover:text-black">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold">Perbarui Cabang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Tambah Gudang Baru -->
    <div x-show="warehouseModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="warehouseModalOpen = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white border border-[#eaeaea] rounded-2xl p-6 max-w-lg w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-sm font-bold text-neutral-950">Tambah Gudang Baru</h3>
                <button @click="warehouseModalOpen = false" class="text-neutral-400 hover:text-black font-mono text-base">&times;</button>
            </div>
            <form method="POST" action="{{ route('settings.warehouse.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Kode Gudang (Unik)</label>
                        <input type="text" name="code" placeholder="WH-BAR-..." required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 uppercase font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Cabang Pemilik</label>
                        <select name="branch_id" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->code }} - {{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nama Gudang</label>
                    <input type="text" name="name" placeholder="Misal: Stok Bar Utama..." required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Tipe Gudang</label>
                    <select name="type" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                        <option value="RETAIL_BAR">RETAIL_BAR (Stok Bar Aktif Cabang Ritel)</option>
                        <option value="CENTRAL_RAW">CENTRAL_RAW (Biji Kopi Mentah Sentral)</option>
                        <option value="CENTRAL_ROASTED">CENTRAL_ROASTED (Biji Sangrai Sentral)</option>
                        <option value="TRANSIT">TRANSIT (Akun In-Transit Ekspedisi)</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                    <button type="button" @click="warehouseModalOpen = false" class="px-3 py-2 rounded-xl text-xs text-neutral-600 hover:text-black">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold cursor-pointer active:scale-[0.97]">Simpan Gudang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3B: Edit Gudang -->
    <div x-show="editWarehouseModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="editWarehouseModalOpen = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white border border-[#eaeaea] rounded-2xl p-6 max-w-lg w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-sm font-bold text-neutral-950">Edit Gudang: <span x-text="selectedWarehouse.code" class="font-mono"></span></h3>
                <button type="button" @click="editWarehouseModalOpen = false" class="text-neutral-400 hover:text-black font-mono text-base cursor-pointer">&times;</button>
            </div>
            <form method="POST" :action="'{{ url('/settings/warehouse') }}/' + selectedWarehouse.id" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Cabang Pemilik</label>
                    <select name="branch_id" x-model="selectedWarehouse.branch_id" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->code }} - {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nama Gudang</label>
                    <input type="text" name="name" x-model="selectedWarehouse.name" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Tipe Gudang</label>
                    <select name="type" x-model="selectedWarehouse.type" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                        <option value="RETAIL_BAR">RETAIL_BAR (Stok Bar Aktif Cabang Ritel)</option>
                        <option value="CENTRAL_RAW">CENTRAL_RAW (Biji Kopi Mentah Sentral)</option>
                        <option value="CENTRAL_ROASTED">CENTRAL_ROASTED (Biji Sangrai Sentral)</option>
                        <option value="TRANSIT">TRANSIT (Akun In-Transit Ekspedisi)</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                    <button type="button" @click="editWarehouseModalOpen = false" class="px-3 py-2 rounded-xl text-xs text-neutral-600 hover:text-black cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold cursor-pointer active:scale-[0.97]">Perbarui Gudang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Tambah User Baru -->
    <div x-show="userModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="userModalOpen = false" 
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white border border-[#eaeaea] rounded-2xl p-6 max-w-lg w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-sm font-bold text-neutral-950">Tambah Karyawan / Staf Baru</h3>
                <button @click="userModalOpen = false" class="text-neutral-400 hover:text-black font-mono text-base">&times;</button>
            </div>
            <form method="POST" action="{{ route('settings.user.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" placeholder="Misal: Budi Santoso" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Email Perusahaan (@kafeina.id)</label>
                    <input type="email" name="email" placeholder="budi@kafeina.id" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 font-mono">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Peran (Role)</label>
                        <select name="role_id" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                            @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->display_name ?? $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Cabang Penempatan</label>
                        <select name="branch_id" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->code }} - {{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Password Awal (Min 8)</label>
                        <input type="password" name="password" placeholder="••••••••" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">PIN Kasir (6 Digit)</label>
                        <input type="password" name="pin_code" maxlength="6" placeholder="123456" class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 font-mono">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                    <button type="button" @click="userModalOpen = false" class="px-3 py-2 rounded-xl text-xs text-neutral-600 hover:text-black">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold cursor-pointer active:scale-[0.97]">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4B: Edit Staf / User -->
    <div x-show="editUserModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="editUserModalOpen = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white border border-[#eaeaea] rounded-2xl p-6 max-w-lg w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-sm font-bold text-neutral-950">Edit Staf: <span x-text="selectedUser.name"></span></h3>
                <button type="button" @click="editUserModalOpen = false" class="text-neutral-400 hover:text-black font-mono text-base cursor-pointer">&times;</button>
            </div>
            <form method="POST" :action="'{{ url('/settings/user') }}/' + selectedUser.id" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" x-model="selectedUser.name" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Email Perusahaan</label>
                    <input type="email" name="email" x-model="selectedUser.email" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 font-mono">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Peran (Role)</label>
                        <select name="role_id" x-model="selectedUser.role_id" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                            @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->display_name ?? $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Cabang Penempatan</label>
                        <select name="branch_id" x-model="selectedUser.branch_id" required class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900">
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->code }} - {{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                    <button type="button" @click="editUserModalOpen = false" class="px-3 py-2 rounded-xl text-xs text-neutral-600 hover:text-black cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold cursor-pointer active:scale-[0.97]">Perbarui Staf</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: Reset Password User -->
    <div x-show="resetPasswordModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="resetPasswordModalOpen = false" class="bg-white border border-[#eaeaea] rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <h3 class="text-sm font-bold text-neutral-950">Reset Password: <span x-text="selectedUser.name"></span></h3>
            <form method="POST" :action="'{{ url('/settings/user') }}/' + selectedUser.id + '/reset-password'" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Password Baru (Minimal 8 Karakter)</label>
                    <input type="password" name="new_password" required placeholder="••••••••" class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 font-mono">
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                    <button type="button" @click="resetPasswordModalOpen = false" class="px-3 py-2 rounded-xl text-xs text-neutral-600 hover:text-black">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 6: Reset PIN Kasir User -->
    <div x-show="resetPinModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="resetPinModalOpen = false" class="bg-white border border-[#eaeaea] rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <h3 class="text-sm font-bold text-neutral-950">Reset PIN Otorisasi 6-Digit: <span x-text="selectedUser.name"></span></h3>
            <p class="text-xs text-neutral-500">Mereset PIN kasir dan membuka blokir akun jika sebelumnya terblokir akibat salah input.</p>
            <form method="POST" :action="'{{ url('/settings/user') }}/' + selectedUser.id + '/reset-pin'" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">PIN 6-Digit Baru</label>
                    <input type="password" name="new_pin" maxlength="6" required placeholder="123456" class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-xs text-neutral-900 outline-none focus:border-neutral-900 font-mono tracking-widest text-center text-sm">
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                    <button type="button" @click="resetPinModalOpen = false" class="px-3 py-2 rounded-xl text-xs text-neutral-600 hover:text-black">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold">Simpan PIN Baru</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 7: Danger Zone Confirmation -->
    <div x-show="dangerResetModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div @click.away="dangerResetModalOpen = false" class="bg-white border border-rose-300 rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex items-center gap-2 text-rose-600">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <h3 class="text-sm font-bold text-rose-950">Konfirmasi Pembersihan Database</h3>
            </div>
            <p class="text-xs text-neutral-600">
                Aksi ini akan menjalankan <code class="font-mono bg-neutral-100 px-1 py-0.5 rounded">migrate:fresh --seed</code>. Seluruh data transaksi, work order, dan pesanan akan dihapus dan di-generate ulang ke data kanonikal standar.
            </p>
            <form method="POST" action="{{ route('settings.database.reset-local') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Ketik <strong class="font-mono text-neutral-950">RESET-KAFEINA-LOCAL</strong> untuk melanjutkan:</label>
                    <input type="text" name="confirmation_text" required placeholder="RESET-KAFEINA-LOCAL" class="w-full px-3 py-2 bg-neutral-50 border border-neutral-300 rounded-xl text-xs font-mono text-neutral-900 outline-none focus:border-rose-600">
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100">
                    <button type="button" @click="dangerResetModalOpen = false" class="px-3 py-2 rounded-xl text-xs text-neutral-600 hover:text-black">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold">Hapus & Seed Ulang</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
