@extends('layouts.app')

@section('title', 'Voucher & Kuota Diskon - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="vouchersApp()">
    
    <!-- Toast / Flash Message -->
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
         class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 flex items-center justify-between text-xs font-sans shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        <button type="button" @click="show = false" class="text-emerald-700 hover:text-emerald-950 font-bold p-1 cursor-pointer">✕</button>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-sans">
        <div class="font-bold mb-1">Gagal menyimpan data voucher:</div>
        <ul class="list-disc pl-4 space-y-0.5 text-[11px]">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- PAGE HEADER & PRIMARY ACTIONS -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-1.5 text-xs text-neutral-400 mb-1">
                <span>Kafeina ERP</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-600 font-medium">POS & Marketing</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-400 font-mono text-[11px]">Voucher & Kuota</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950">Daftar & Kuota Voucher Diskon</h1>
            <p class="text-xs text-neutral-500 mt-0.5">Pantau penggunaan voucher promo kasir, batasi kuota penukaran agar omzet terjaga, dan terbitkan kode diskon baru.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <!-- Link Buka Kasir POS -->
            <a href="{{ route('pos.index') }}" 
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Buka Kasir POS</span>
            </a>

            <!-- Tombol Terbitkan Voucher Baru -->
            <button type="button" 
                    @click="openCreateModal = true"
                    class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Terbitkan Voucher Baru</span>
            </button>
        </div>
    </div>

    <!-- BENTO STATS CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- CARD 1: Total Voucher -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Total Voucher</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums">{{ $totalCount }}</div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                <span class="text-neutral-600 font-semibold">{{ $activeCount }} aktif</span> dari total kampanye
            </div>
        </div>

        <!-- CARD 2: Voucher Aktif di Kasir -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Aktif Siap Klaim</div>
            <div class="text-2xl font-bold tracking-tight text-emerald-700 tabular-nums">{{ $activeCount }}</div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1.5 font-sans">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Bisa diklaim langsung di POS</span>
            </div>
        </div>

        <!-- CARD 3: Total Penukaran Sukses -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Total Klaim Terpakai</div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 tabular-nums">{{ number_format($totalRedeemed, 0, ',', '.') }}<span class="text-xs text-neutral-400 font-normal ml-1">x</span></div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Penukaran kasir terekam
            </div>
        </div>

        <!-- CARD 4: Kuota Habis / Depleted -->
        <div class="bg-white border border-[#eaeaea] rounded-2xl p-4 sm:p-5 shadow-2xs hover:border-neutral-300 transition-colors">
            <div class="text-[11px] font-semibold text-neutral-400 tracking-wider uppercase mb-2">Voucher Kuota Habis</div>
            <div class="text-2xl font-bold tracking-tight text-rose-600 tabular-nums">{{ $depletedCount }}</div>
            <div class="text-[11px] text-neutral-400 mt-1 flex items-center gap-1 font-sans">
                Memerlukan isi ulang kuota
            </div>
        </div>
    </div>

    <!-- SEARCH & FILTER TOOLBAR -->
    <div class="mb-5 bg-white border border-[#eaeaea] rounded-2xl p-3 sm:p-4 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Search Form -->
        <form method="GET" action="{{ route('vouchers.index') }}" class="flex-1 flex items-center gap-2">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Cari kode promo atau deskripsi (misal: KAFEINA10)..."
                       class="w-full pl-9 pr-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 text-xs font-sans outline-none transition-all" />
            </div>

            <!-- Preserve other query params -->
            @if(request('type'))
            <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <button type="submit" class="px-3 py-2 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-medium cursor-pointer transition-colors">
                Cari
            </button>
            @if(request()->hasAny(['search', 'type', 'status']))
            <a href="{{ route('vouchers.index') }}" class="px-2.5 py-2 text-xs text-neutral-400 hover:text-neutral-700 transition-colors">
                Reset
            </a>
            @endif
        </form>

        <!-- Filter Segmented Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
            <!-- Filter Tipe -->
            <div class="flex items-center p-0.5 rounded-xl bg-neutral-100 border border-neutral-200 text-xs shrink-0">
                <a href="{{ request()->fullUrlWithQuery(['type' => null]) }}" 
                   class="px-2.5 py-1 rounded-lg transition-colors font-sans {{ !request('type') ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900' }}">
                    Semua Tipe
                </a>
                <a href="{{ request()->fullUrlWithQuery(['type' => 'percentage']) }}" 
                   class="px-2.5 py-1 rounded-lg transition-colors font-sans {{ request('type') === 'percentage' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900' }}">
                    Persen (%)
                </a>
                <a href="{{ request()->fullUrlWithQuery(['type' => 'fixed']) }}" 
                   class="px-2.5 py-1 rounded-lg transition-colors font-sans {{ request('type') === 'fixed' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900' }}">
                    Nominal (Rp)
                </a>
            </div>

            <!-- Filter Status -->
            <div class="flex items-center p-0.5 rounded-xl bg-neutral-100 border border-neutral-200 text-xs shrink-0">
                <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" 
                   class="px-2.5 py-1 rounded-lg transition-colors font-sans {{ !request('status') ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900' }}">
                    Semua Status
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'active']) }}" 
                   class="px-2.5 py-1 rounded-lg transition-colors font-sans {{ request('status') === 'active' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900' }}">
                    Aktif
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'depleted']) }}" 
                   class="px-2.5 py-1 rounded-lg transition-colors font-sans {{ request('status') === 'depleted' ? 'bg-white text-neutral-900 font-semibold shadow-2xs' : 'text-neutral-500 hover:text-neutral-900' }}">
                    Kuota Habis
                </a>
            </div>
        </div>
    </div>

    <!-- VOUCHERS LIST / BENTO CARDS -->
    <div class="space-y-3">
        @forelse($vouchers as $voucher)
        @php
            $isUnlimited = is_null($voucher->max_redemptions);
            $redeemed = (int) $voucher->times_redeemed;
            $maxRedemptions = (int) $voucher->max_redemptions;
            $remaining = $isUnlimited ? null : max(0, $maxRedemptions - $redeemed);
            $usagePct = $isUnlimited ? 0 : ($maxRedemptions > 0 ? min(100, round(($redeemed / $maxRedemptions) * 100)) : 100);
            $isDepleted = ! $isUnlimited && $redeemed >= $maxRedemptions;
        @endphp

        <div class="bg-white border rounded-2xl p-4 sm:p-5 transition-colors shadow-2xs hover:shadow-xs {{ $voucher->is_active ? 'border-[#eaeaea]' : 'border-neutral-200/60 opacity-75 bg-neutral-50/50' }}">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                
                <!-- Left: Code & Promo Details (lg:col-span-5) -->
                <div class="lg:col-span-5 flex items-start gap-3.5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-neutral-100 text-neutral-800 font-mono font-bold text-xs border border-neutral-200">
                        <span>{{ $voucher->discount_type === 'PERCENTAGE' ? '%' : 'Rp' }}</span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <!-- Code Tag with Copy -->
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-neutral-900 text-white font-mono font-bold text-xs tracking-wider cursor-pointer hover:bg-neutral-800 transition-colors"
                                 @click="copyCode('{{ $voucher->code }}')"
                                 title="Klik untuk salin kode voucher">
                                <span>{{ $voucher->code }}</span>
                                <svg class="w-3 h-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>

                            <!-- Status Indicators -->
                            @if(! $voucher->is_active)
                                <div class="inline-flex items-center gap-1.5 text-xs text-neutral-400 font-sans">
                                    <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
                                    <span>Nonaktif</span>
                                </div>
                            @elseif($isDepleted)
                                <div class="inline-flex items-center gap-1.5 text-xs text-rose-700 font-sans">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span>Kuota Habis</span>
                                </div>
                            @else
                                <div class="inline-flex items-center gap-1.5 text-xs text-emerald-700 font-sans">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Aktif di POS</span>
                                </div>
                            @endif

                            <span class="text-[11px] text-neutral-400 font-mono">ID: #{{ $voucher->id }}</span>
                        </div>

                        <h3 class="font-semibold text-sm text-neutral-900 mt-1 leading-snug">{{ $voucher->description }}</h3>
                        
                        <div class="flex items-center gap-2 mt-1.5 text-xs text-neutral-500 font-sans flex-wrap">
                            <span class="font-bold text-neutral-900">
                                @if($voucher->discount_type === 'PERCENTAGE')
                                    Diskon {{ rtrim(rtrim(number_format((float)$voucher->discount_value, 2, ',', '.'), '0'), ',') }}%
                                    @if($voucher->max_discount_amount)
                                        <span class="font-normal text-neutral-500">(Maks. Rp {{ number_format((float)$voucher->max_discount_amount, 0, ',', '.') }})</span>
                                    @endif
                                @else
                                    Potongan Rp {{ number_format((float)$voucher->discount_value, 0, ',', '.') }}
                                @endif
                            </span>
                            
                            @if((float)$voucher->min_order_amount > 0)
                                <span class="text-neutral-300">•</span>
                                <span>Min. Belanja: <strong class="text-neutral-700 font-mono">Rp {{ number_format((float)$voucher->min_order_amount, 0, ',', '.') }}</strong></span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Middle: Kuota Penukaran & Progress Bar (lg:col-span-4) -->
                <div class="lg:col-span-4 p-3 rounded-xl bg-neutral-50 border border-neutral-100 flex flex-col justify-center">
                    <div class="flex items-center justify-between text-xs font-sans mb-1.5">
                        <span class="text-neutral-600 font-medium flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Pemakaian Kuota Kasir:
                        </span>
                        
                        @if($isUnlimited)
                            <span class="font-bold text-emerald-700 font-mono text-[11px] bg-emerald-100 px-2 py-0.5 rounded-md">UNLIMITED</span>
                        @else
                            <span class="font-bold tabular-nums text-neutral-900 font-mono text-[11px]">
                                {{ $redeemed }} / {{ $maxRedemptions }} <span class="text-neutral-400 font-normal">({{ $usagePct }}%)</span>
                            </span>
                        @endif
                    </div>

                    <!-- Progress Bar Track -->
                    @if($isUnlimited)
                        <div class="w-full bg-emerald-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full w-full"></div>
                        </div>
                        <div class="text-[10px] text-neutral-400 mt-1 font-sans">
                            Total penukaran tercatat: <strong class="text-neutral-700 font-mono">{{ $redeemed }}x</strong> (Tanpa batas kuota)
                        </div>
                    @else
                        <div class="w-full bg-neutral-200 h-2 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 {{ $isDepleted ? 'bg-rose-500' : ($usagePct >= 80 ? 'bg-amber-500' : 'bg-emerald-600') }}"
                                 style="width: {{ $usagePct }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-neutral-500 mt-1 font-sans">
                            <span>Sisa Kuota: <strong class="{{ $remaining === 0 ? 'text-rose-600 font-bold' : 'text-neutral-800 font-mono' }}">{{ $remaining }} penukaran</strong></span>
                            <span>{{ $isDepleted ? 'Kuota habis!' : 'Tersedia' }}</span>
                        </div>
                    @endif
                </div>

                <!-- Right: Actions & Toggle (lg:col-span-3) -->
                <div class="lg:col-span-3 flex items-center justify-start lg:justify-end gap-1.5">
                    <!-- Quick Kuota Adjust Button (Icon Only) -->
                    <button type="button"
                            @click="openQuotaModalFor({{ json_encode($voucher) }})"
                            class="w-8 h-8 rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-700 flex items-center justify-center transition-colors cursor-pointer"
                            title="Atur / Tambah Kuota"
                            aria-label="Atur Kuota">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </button>

                    <!-- Edit Button (Icon Only) -->
                    <button type="button"
                            @click="openEditModalFor({{ json_encode($voucher) }})"
                            class="w-8 h-8 rounded-lg bg-white border border-neutral-200 hover:bg-neutral-50 text-neutral-700 flex items-center justify-center transition-colors cursor-pointer"
                            title="Edit data voucher"
                            aria-label="Edit voucher">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>

                    <!-- Toggle Active Form (Icon Only) -->
                    <form method="POST" action="{{ route('vouchers.toggle', $voucher->id) }}" class="inline">
                        @csrf
                        <button type="submit" 
                                class="w-8 h-8 rounded-lg flex items-center justify-center cursor-pointer transition-colors {{ $voucher->is_active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200/80' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/80' }}"
                                title="{{ $voucher->is_active ? 'Nonaktifkan voucher ini di kasir' : 'Aktifkan voucher ini agar bisa diklaim di kasir' }}"
                                aria-label="{{ $voucher->is_active ? 'Nonaktifkan voucher' : 'Aktifkan voucher' }}">
                            @if($voucher->is_active)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </button>
                    </form>

                    <!-- Delete Form (Icon Only) -->
                    <form method="POST" action="{{ route('vouchers.destroy', $voucher->id) }}" 
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus voucher [{{ $voucher->code }}]?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-8 h-8 text-neutral-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 border border-transparent hover:border-rose-200/60 flex items-center justify-center transition-colors cursor-pointer" 
                                title="Hapus voucher"
                                aria-label="Hapus voucher">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="py-16 text-center bg-white border border-[#eaeaea] rounded-2xl p-6 text-neutral-500">
            <div class="w-12 h-12 rounded-2xl bg-neutral-100 flex items-center justify-center mx-auto mb-3 text-neutral-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-neutral-900">Tidak ada voucher ditemukan</h3>
            <p class="text-xs text-neutral-400 mt-1 max-w-sm mx-auto">Tidak ada voucher yang cocok dengan filter pencarian Anda, atau belum ada voucher yang diterbitkan.</p>
            <button type="button" @click="openCreateModal = true" class="mt-4 px-4 py-2 rounded-xl bg-neutral-900 text-white text-xs font-semibold hover:bg-neutral-800 transition-colors cursor-pointer">
                Terbitkan Voucher Pertama
            </button>
        </div>
        @endforelse
    </div>

    <!-- MODAL 1: TERBITKAN VOUCHER BARU -->
    <div x-show="openCreateModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-out duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="openCreateModal = false">
        
        <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
             @click.away="openCreateModal = false">
            
            <div class="px-5 py-3.5 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">Terbitkan Voucher Promo Baru</h3>
                    <p class="text-[11px] text-neutral-500 font-sans mt-0.5">Atur kode, tipe diskon, dan batas kuota penukaran kasir.</p>
                </div>
                <button type="button" @click="openCreateModal = false" class="text-neutral-400 hover:text-neutral-900 p-1 cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('vouchers.store') }}" class="p-5 overflow-y-auto space-y-4">
                @csrf

                <!-- Kode Voucher -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Kode Voucher (Otomatis Kapital):</label>
                    <input type="text" 
                           name="code" 
                           required 
                           placeholder="Contoh: KAFEINAPAYDAY"
                           class="w-full px-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 text-xs font-mono font-bold uppercase tracking-wider outline-none transition-all" />
                </div>

                <!-- Deskripsi Promo -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Deskripsi / Nama Promo:</label>
                    <input type="text" 
                           name="description" 
                           required 
                           placeholder="Contoh: Diskon Spesial Gajian 20%"
                           class="w-full px-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 text-xs font-sans outline-none transition-all" />
                </div>

                <!-- Tipe Diskon & Nilai -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ type: 'PERCENTAGE' }">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Tipe Diskon:</label>
                        <select name="discount_type" 
                                x-model="type"
                                class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-sans outline-none focus:border-neutral-900">
                            <option value="PERCENTAGE">Persentase (%)</option>
                            <option value="FIXED">Potongan Nominal (Rp)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">
                            <span x-text="type === 'PERCENTAGE' ? 'Nilai Diskon (%):' : 'Potongan Nominal (Rp):'"></span>
                        </label>
                        <input type="number" 
                               name="discount_value" 
                               required 
                               min="1" 
                               placeholder="Contoh: 15 atau 20000"
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 text-xs font-mono outline-none transition-all" />
                    </div>

                    <!-- Maksimal Potongan (Khusus Persen) -->
                    <div class="sm:col-span-2" x-show="type === 'PERCENTAGE'">
                        <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Maksimal Diskon (Rp, Opsional):</label>
                        <input type="number" 
                               name="max_discount_amount" 
                               placeholder="Contoh: 25000 (Kosongkan jika tanpa batas potongan)"
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 text-xs font-mono outline-none transition-all" />
                    </div>
                </div>

                <!-- Minimal Belanja -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Minimal Belanja (Rp):</label>
                    <input type="number" 
                           name="min_order_amount" 
                           value="0" 
                           min="0"
                           placeholder="Contoh: 50000"
                           class="w-full px-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 text-xs font-mono outline-none transition-all" />
                </div>

                <!-- PENGATURAN KUOTA PENUKARAN -->
                <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/80 space-y-2.5" x-data="{ unlimited: false }">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-amber-950 font-sans flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <span>Batas Kuota Penukaran:</span>
                        </label>

                        <label class="flex items-center gap-1.5 text-xs text-amber-900 cursor-pointer font-sans">
                            <input type="checkbox" name="is_unlimited" value="1" x-model="unlimited" class="rounded text-neutral-900 focus:ring-0">
                            <span>Tanpa Batas Kuota</span>
                        </label>
                    </div>

                    <div x-show="!unlimited">
                        <input type="number" 
                               name="max_redemptions" 
                               value="50" 
                               min="1"
                               placeholder="Jumlah maksimal pemakaian kode (cth: 50)"
                               class="w-full px-3 py-2 rounded-xl bg-white border border-amber-300 text-xs font-mono outline-none focus:border-neutral-900" />
                        <p class="text-[10px] text-amber-800/80 mt-1 font-sans">Voucher akan otomatis tidak bisa diklaim lagi di kasir setelah mencapai jumlah ini.</p>
                    </div>

                    <div x-show="unlimited" class="text-xs font-medium text-amber-900 bg-white/70 p-2 rounded-lg border border-amber-200 font-sans">
                        ✓ Voucher ini dapat ditukarkan tanpa batas jumlah penukaran.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-neutral-100">
                    <button type="button" @click="openCreateModal = false" class="px-4 py-2 rounded-xl text-xs text-neutral-600 hover:text-neutral-900 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors cursor-pointer">
                        Terbitkan Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: SESUAIKAN / TAMBAH KUOTA CEPAT -->
    <div x-show="openQuotaModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         @keydown.escape.window="openQuotaModal = false">
        
        <div class="w-full max-w-md bg-white border border-neutral-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col"
             @click.away="openQuotaModal = false">
            
            <div class="px-5 py-3.5 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">Sesuaikan Kuota Voucher</h3>
                    <p class="text-[11px] text-neutral-500 font-mono mt-0.5" x-text="targetVoucher ? ('[' + targetVoucher.code + '] ' + targetVoucher.description) : ''"></p>
                </div>
                <button type="button" @click="openQuotaModal = false" class="text-neutral-400 hover:text-neutral-900 p-1 cursor-pointer">✕</button>
            </div>

            <form :action="'/vouchers/' + (targetVoucher ? targetVoucher.id : '0') + '/quota'" method="POST" class="p-5 space-y-4">
                @csrf

                <!-- Current Quota Info -->
                <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-sans space-y-1">
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Klaim Terpakai:</span>
                        <strong class="font-mono text-neutral-900" x-text="targetVoucher ? (targetVoucher.times_redeemed + 'x') : '0x'"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500">Kuota Saat Ini:</span>
                        <strong class="font-mono text-neutral-900" x-text="targetVoucher && targetVoucher.max_redemptions !== null ? (targetVoucher.max_redemptions + 'x') : 'Tanpa Batas (Unlimited)'"></strong>
                    </div>
                </div>

                <div x-data="{ actionType: 'add' }" class="space-y-3">
                    <label class="block text-xs font-semibold text-neutral-700 font-sans">Aksi Penyesuaian:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" 
                                @click="actionType = 'add'"
                                :class="actionType === 'add' ? 'bg-neutral-900 text-white font-semibold' : 'bg-neutral-100 text-neutral-700'"
                                class="py-1.5 px-2 rounded-lg text-xs font-sans transition-all text-center cursor-pointer">
                            + Tambah Kuota
                        </button>
                        <button type="button" 
                                @click="actionType = 'set'"
                                :class="actionType === 'set' ? 'bg-neutral-900 text-white font-semibold' : 'bg-neutral-100 text-neutral-700'"
                                class="py-1.5 px-2 rounded-lg text-xs font-sans transition-all text-center cursor-pointer">
                            Tetapkan Total
                        </button>
                        <button type="button" 
                                @click="actionType = 'unlimited'"
                                :class="actionType === 'unlimited' ? 'bg-neutral-900 text-white font-semibold' : 'bg-neutral-100 text-neutral-700'"
                                class="py-1.5 px-2 rounded-lg text-xs font-sans transition-all text-center cursor-pointer">
                            Unlimited
                        </button>
                    </div>

                    <input type="hidden" name="action_type" :value="actionType">

                    <div x-show="actionType === 'add'" class="space-y-2">
                        <label class="block text-xs font-semibold text-neutral-700 font-sans">Jumlah Kuota Tambahan:</label>
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="quotaInput = 10" class="px-2.5 py-1 rounded-md bg-neutral-100 hover:bg-neutral-200 text-xs font-mono">+10</button>
                            <button type="button" @click="quotaInput = 25" class="px-2.5 py-1 rounded-md bg-neutral-100 hover:bg-neutral-200 text-xs font-mono">+25</button>
                            <button type="button" @click="quotaInput = 50" class="px-2.5 py-1 rounded-md bg-neutral-100 hover:bg-neutral-200 text-xs font-mono">+50</button>
                        </div>
                        <input type="number" 
                               name="quota_value" 
                               x-model="quotaInput" 
                               min="1" 
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono outline-none focus:border-neutral-900" />
                    </div>

                    <div x-show="actionType === 'set'" class="space-y-1">
                        <label class="block text-xs font-semibold text-neutral-700 font-sans">Batas Total Kuota Baru:</label>
                        <input type="number" 
                               name="quota_value" 
                               :value="targetVoucher ? targetVoucher.max_redemptions : 50" 
                               min="1" 
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono outline-none focus:border-neutral-900" />
                    </div>

                    <div x-show="actionType === 'unlimited'" class="text-xs text-neutral-600 bg-neutral-50 p-2.5 rounded-xl border border-neutral-200">
                        Voucher ini akan diubah menjadi <strong>tanpa batas kuota (unlimited)</strong> dan kasir dapat mengklaimnya kapan saja selama statusnya aktif.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-neutral-100">
                    <button type="button" @click="openQuotaModal = false" class="px-4 py-2 rounded-xl text-xs text-neutral-600 hover:text-neutral-900 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors cursor-pointer">
                        Simpan Perubahan Kuota
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: EDIT DETAIL VOUCHER -->
    <div x-show="openEditModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         @keydown.escape.window="openEditModal = false">
        
        <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
             @click.away="openEditModal = false">
            
            <div class="px-5 py-3.5 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">Edit Detail Voucher Promo</h3>
                    <p class="text-[11px] text-neutral-500 font-mono mt-0.5" x-text="targetVoucher ? targetVoucher.code : ''"></p>
                </div>
                <button type="button" @click="openEditModal = false" class="text-neutral-400 hover:text-neutral-900 p-1 cursor-pointer">✕</button>
            </div>

            <form :action="'/vouchers/' + (targetVoucher ? targetVoucher.id : '0')" method="POST" class="p-5 overflow-y-auto space-y-4">
                @csrf
                @method('PUT')

                <!-- Kode Voucher -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Kode Voucher:</label>
                    <input type="text" 
                           name="code" 
                           required 
                           :value="targetVoucher ? targetVoucher.code : ''"
                           class="w-full px-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 text-xs font-mono font-bold uppercase tracking-wider outline-none transition-all" />
                </div>

                <!-- Deskripsi Promo -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Deskripsi / Nama Promo:</label>
                    <input type="text" 
                           name="description" 
                           required 
                           :value="targetVoucher ? targetVoucher.description : ''"
                           class="w-full px-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 text-xs font-sans outline-none transition-all" />
                </div>

                <!-- Tipe & Nilai -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ editType: targetVoucher ? targetVoucher.discount_type : 'PERCENTAGE' }">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Tipe Diskon:</label>
                        <select name="discount_type" 
                                x-model="editType"
                                class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-sans outline-none focus:border-neutral-900">
                            <option value="PERCENTAGE">Persentase (%)</option>
                            <option value="FIXED">Potongan Nominal (Rp)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Nilai Diskon:</label>
                        <input type="number" 
                               name="discount_value" 
                               required 
                               min="1" 
                               :value="targetVoucher ? parseFloat(targetVoucher.discount_value) : 10"
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono outline-none focus:border-neutral-900" />
                    </div>

                    <div class="sm:col-span-2" x-show="editType === 'PERCENTAGE'">
                        <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Maksimal Diskon (Rp):</label>
                        <input type="number" 
                               name="max_discount_amount" 
                               :value="targetVoucher && targetVoucher.max_discount_amount ? parseFloat(targetVoucher.max_discount_amount) : ''"
                               placeholder="Opsional (cth: 25000)"
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono outline-none focus:border-neutral-900" />
                    </div>
                </div>

                <!-- Minimal Belanja -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1 font-sans">Minimal Belanja (Rp):</label>
                    <input type="number" 
                           name="min_order_amount" 
                           :value="targetVoucher ? parseFloat(targetVoucher.min_order_amount) : 0" 
                           min="0"
                           class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono outline-none focus:border-neutral-900" />
                </div>

                <!-- Batas Kuota -->
                <div class="p-3.5 rounded-xl bg-neutral-50 border border-neutral-200 space-y-2" x-data="{ editUnlimited: targetVoucher ? (targetVoucher.max_redemptions === null) : false }">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-neutral-900 font-sans">Batas Maksimal Kuota:</label>
                        <label class="flex items-center gap-1.5 text-xs text-neutral-700 cursor-pointer font-sans">
                            <input type="checkbox" name="is_unlimited" value="1" x-model="editUnlimited" class="rounded text-neutral-900 focus:ring-0">
                            <span>Unlimited</span>
                        </label>
                    </div>

                    <div x-show="!editUnlimited">
                        <input type="number" 
                               name="max_redemptions" 
                               :value="targetVoucher && targetVoucher.max_redemptions !== null ? targetVoucher.max_redemptions : 50" 
                               min="0" 
                               class="w-full px-3 py-2 rounded-xl bg-white border border-neutral-200 text-xs font-mono outline-none focus:border-neutral-900" />
                    </div>
                </div>

                <!-- Status Aktif Checkbox -->
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" 
                           name="is_active" 
                           id="edit_is_active" 
                           value="1" 
                           :checked="targetVoucher ? targetVoucher.is_active : true"
                           class="rounded text-neutral-900 focus:ring-0">
                    <label for="edit_is_active" class="text-xs font-medium text-neutral-800 cursor-pointer font-sans">
                        Aktifkan voucher ini (siap diklaim oleh kasir)
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-neutral-100">
                    <button type="button" @click="openEditModal = false" class="px-4 py-2 rounded-xl text-xs text-neutral-600 hover:text-neutral-900 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold transition-colors cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function vouchersApp() {
    return {
        openCreateModal: false,
        openQuotaModal: false,
        openEditModal: false,
        targetVoucher: null,
        quotaInput: 20,

        openQuotaModalFor(voucher) {
            this.targetVoucher = voucher;
            this.quotaInput = 20;
            this.openQuotaModal = true;
        },

        openEditModalFor(voucher) {
            this.targetVoucher = voucher;
            this.openEditModal = true;
        },

        copyCode(code) {
            if (navigator && navigator.clipboard) {
                navigator.clipboard.writeText(code);
                alert('Kode voucher [' + code + '] berhasil disalin!');
            }
        }
    };
}
</script>
@endsection
