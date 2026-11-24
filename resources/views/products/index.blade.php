@extends('layouts.app')

@section('title', 'Katalog Produk, Resep & Varian - KAFEINA ERP')

@section('content')
<div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 bg-[#fafafa] text-neutral-900 font-sans" x-data="productsApp()">
    
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

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" 
         class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-900 flex items-center justify-between text-xs font-sans shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
        <button type="button" @click="show = false" class="text-rose-700 hover:text-rose-950 font-bold p-1 cursor-pointer">✕</button>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-sans">
        <div class="font-bold mb-1">Gagal menyimpan data:</div>
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
                <span class="text-neutral-600 font-medium">Manajemen Menu & Bar</span>
                <span class="text-neutral-300">/</span>
                <span class="text-neutral-400 font-mono text-[11px]">Katalog & Resep</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-950 flex items-center gap-2.5">
                <span>Katalog Produk, Resep & Varian</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full font-medium bg-neutral-100 text-neutral-700 border border-neutral-200/70">
                    {{ $currentUser?->role?->display_name ?? 'Staff' }}
                </span>
            </h1>
            <p class="text-xs text-neutral-500 mt-0.5">Kelola daftar menu POS, takaran formula resep auto-deduct bahan baku, varian harga ukuran cup, dan add-ons barista.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <!-- Buka Kasir POS -->
            <a href="{{ route('pos.index') }}" 
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 hover:text-neutral-950 text-xs font-semibold flex items-center gap-2 shadow-2xs transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Terminal Kasir POS</span>
            </a>

            <!-- Tombol Tambah Produk Baru -->
            @if($currentUser?->isSuperAdmin() || $currentUser?->hasRole(['store_manager', 'supervisor']))
            <button type="button" 
                    @click="openProductModal = true; editingProduct = null; resetProductForm();"
                    class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold flex items-center gap-2 shadow-xs transition-colors cursor-pointer active:scale-[0.98]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Produk</span>
            </button>
            @endif
        </div>
    </div>

    <!-- STATS OVERVIEW CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-xs font-medium">Total SKU Master</span>
                <span class="w-7 h-7 rounded-lg bg-neutral-100 flex items-center justify-center text-neutral-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 font-mono">{{ $stats['total_products'] }}</div>
            <div class="text-[11px] text-neutral-400 mt-0.5">Katalog terdaftar</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-xs font-medium">Menu Minuman & Retail</span>
                <span class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center text-amber-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 font-mono">{{ $stats['active_beverages'] }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-0.5">Siap dijual di POS</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-xs font-medium">Formula Resep (BOM)</span>
                <span class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 font-mono">{{ $stats['recipes_count'] }}</div>
            <div class="text-[11px] text-neutral-400 mt-0.5">Produk ber-resep aktif</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs">
            <div class="flex items-center justify-between text-neutral-500 mb-1.5">
                <span class="text-xs font-medium">Bahan Baku & Kemasan</span>
                <span class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-neutral-900 font-mono">{{ $stats['raw_count'] }}</div>
            <div class="text-[11px] text-neutral-400 mt-0.5">Green bean, susu, cup</div>
        </div>
    </div>

    <!-- TAB NAVIGATION BAR -->
    <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-neutral-200/50 border border-neutral-200/80 mb-6 overflow-x-auto max-w-fit">
        <button type="button" @click="activeTab = 'products'" 
                :class="activeTab === 'products' ? 'bg-white text-neutral-950 font-semibold shadow-2xs' : 'text-neutral-600 hover:text-neutral-900'"
                class="px-4 py-2 rounded-xl text-xs font-sans transition-all cursor-pointer flex items-center gap-2 shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            <span>Katalog Produk ({{ $products->count() }})</span>
        </button>

        <button type="button" @click="activeTab = 'recipes'" 
                :class="activeTab === 'recipes' ? 'bg-white text-neutral-950 font-semibold shadow-2xs' : 'text-neutral-600 hover:text-neutral-900'"
                class="px-4 py-2 rounded-xl text-xs font-sans transition-all cursor-pointer flex items-center gap-2 shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            <span>Formula Resep & HPP Bar</span>
        </button>

        <button type="button" @click="activeTab = 'variants'" 
                :class="activeTab === 'variants' ? 'bg-white text-neutral-950 font-semibold shadow-2xs' : 'text-neutral-600 hover:text-neutral-900'"
                class="px-4 py-2 rounded-xl text-xs font-sans transition-all cursor-pointer flex items-center gap-2 shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            <span>Varian Harga & Modifiers</span>
        </button>

        <button type="button" @click="activeTab = 'categories'" 
                :class="activeTab === 'categories' ? 'bg-white text-neutral-950 font-semibold shadow-2xs' : 'text-neutral-600 hover:text-neutral-900'"
                class="px-4 py-2 rounded-xl text-xs font-sans transition-all cursor-pointer flex items-center gap-2 shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            <span>Kategori Menu ({{ $categories->count() }})</span>
        </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: KATALOG PRODUK & MENU -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'products'" class="space-y-4">
        
        <!-- Filter Bar -->
        <div class="p-3.5 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-3">
            <form method="GET" action="{{ route('products.index') }}" class="flex-1 flex flex-col sm:flex-row items-center gap-2.5 w-full">
                <input type="hidden" name="tab" value="products">
                
                <!-- Search Input -->
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Cari nama atau SKU..." 
                           class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:border-neutral-900 transition-colors">
                </div>

                <!-- Kategori Filter -->
                <select name="category_id" onchange="this.form.submit()" 
                        class="w-full sm:w-44 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900 transition-colors cursor-pointer">
                    <option value="all">Semua Kategori</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $filters['category_id'] == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }} ({{ $cat->products_count }})
                    </option>
                    @endforeach
                </select>

                <!-- Tipe Filter -->
                <select name="type" onchange="this.form.submit()" 
                        class="w-full sm:w-36 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900 transition-colors cursor-pointer">
                    <option value="all" {{ $filters['type'] === 'all' ? 'selected' : '' }}>Semua Tipe</option>
                    <option value="beverage" {{ $filters['type'] === 'beverage' ? 'selected' : '' }}>Minuman Bar</option>
                    <option value="retail" {{ $filters['type'] === 'retail' ? 'selected' : '' }}>Biji Retail</option>
                    <option value="raw" {{ $filters['type'] === 'raw' ? 'selected' : '' }}>Bahan Mentah</option>
                </select>

                <!-- Status Filter -->
                <select name="status" onchange="this.form.submit()" 
                        class="w-full sm:w-32 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900 transition-colors cursor-pointer">
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>

                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-800 text-xs font-semibold cursor-pointer transition-colors">
                    Filter
                </button>
            </form>
        </div>

        <!-- Tabel Produk -->
        <div class="rounded-2xl bg-white border border-neutral-200/80 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs font-sans">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold text-neutral-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Produk & SKU</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4">Tipe</th>
                            <th class="py-3 px-4 text-right">Harga Jual</th>
                            <th class="py-3 px-4 text-right">HPP Resep (Est.)</th>
                            <th class="py-3 px-4 text-center">Gross Margin</th>
                            <th class="py-3 px-4 text-center">Resep Bahan</th>
                            <th class="py-3 px-4 text-center">Varian</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($products as $prod)
                        @php
                            $hpp = $prod->calculateRecipeCost();
                            $selling = (float) $prod->selling_price;
                            $margin = $prod->calculateGrossMargin();
                            $recipesCount = $prod->recipes->count();
                            $variantsCount = $prod->variants->count();
                        @endphp
                        <tr class="hover:bg-neutral-50/70 transition-colors {{ ! $prod->is_active ? 'opacity-60 bg-neutral-50/40' : '' }}">
                            <td class="py-3 px-4">
                                <div class="font-semibold text-neutral-900">{{ $prod->name }}</div>
                                <div class="text-[11px] font-mono text-neutral-400 mt-0.5">{{ $prod->sku }} • Satuan: {{ $prod->unit }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-neutral-100 text-neutral-700 border border-neutral-200/60">
                                    {{ $prod->category?->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($prod->is_raw_material)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">Bahan Mentah</span>
                                @elseif($prod->unit === 'cup')
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">Minuman Bar</span>
                                @else
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200/60">Barang Jadi</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-semibold text-neutral-900">
                                @if($prod->is_raw_material)
                                <span class="text-neutral-400">-</span>
                                @else
                                Rp {{ number_format($selling, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-neutral-600">
                                Rp {{ number_format($hpp, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono">
                                @if(! $prod->is_raw_material && $selling > 0)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $margin >= 60 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : ($margin >= 40 ? 'bg-amber-50 text-amber-700 border border-amber-200/60' : 'bg-rose-50 text-rose-700 border border-rose-200/60') }}">
                                        {{ $margin }}%
                                    </span>
                                @else
                                    <span class="text-neutral-400 text-[10px]">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($recipesCount > 0)
                                <button type="button" @click="openRecipeFor({{ $prod->id }}, '{{ addslashes($prod->name) }}')"
                                        class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-200/70 hover:bg-emerald-100 transition-colors cursor-pointer">
                                    {{ $recipesCount }} Bahan
                                </button>
                                @else
                                <button type="button" @click="openRecipeFor({{ $prod->id }}, '{{ addslashes($prod->name) }}')"
                                        class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-neutral-100 text-neutral-500 hover:text-neutral-800 hover:bg-neutral-200 transition-colors cursor-pointer">
                                    + Atur Resep
                                </button>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($variantsCount > 0)
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-neutral-100 text-neutral-700">{{ $variantsCount }} varian</span>
                                @else
                                <span class="text-neutral-400 text-[10px]">Dasar</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('products.toggle', $prod) }}" class="inline">
                                    @csrf
                                    <button type="submit" title="Klik untuk ubah status aktif" 
                                            class="px-2 py-0.5 rounded-full text-[10px] font-semibold cursor-pointer transition-colors {{ $prod->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60 hover:bg-emerald-100' : 'bg-neutral-100 text-neutral-500 border border-neutral-200/60 hover:bg-neutral-200' }}">
                                        {{ $prod->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" 
                                            @click="editProduct({{ json_encode($prod) }})"
                                            class="p-1 rounded-lg hover:bg-neutral-100 text-neutral-600 hover:text-neutral-950 transition-colors cursor-pointer" title="Edit Data Produk">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-neutral-400 text-xs">
                                Tidak ada produk yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: FORMULA RESEP & BOM BAR -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'recipes'" class="space-y-6">
        <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/70 text-xs text-amber-900 flex items-start gap-3">
            <span class="w-6 h-6 rounded-lg bg-amber-100 flex items-center justify-center text-amber-800 shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <span class="font-bold">Ketentuan Auto-Deduct Kasir:</span> Setiap kali pesanan menu di kasir POS diselesaikan (*checkout*), sistem secara otomatis memotong stok bahan baku berikut dari gudang bar (`RETAIL_BAR`) dan mencatatnya ke kartu stok (*Stock Cards*) untuk audit pemakaian riil vs standar.
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($products->where('is_raw_material', false) as $prod)
            @php
                $hpp = $prod->calculateRecipeCost();
                $margin = $prod->calculateGrossMargin();
            @endphp
            <div class="p-4 rounded-2xl bg-white border border-neutral-200/80 shadow-2xs space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-neutral-900">{{ $prod->name }}</h3>
                        <div class="text-[11px] font-mono text-neutral-400 mt-0.5">{{ $prod->sku }} • {{ $prod->category?->name }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-mono font-bold text-neutral-900">Rp {{ number_format($prod->selling_price, 0, ',', '.') }}</div>
                        <div class="text-[10px] font-mono text-emerald-700 font-semibold mt-0.5">HPP: Rp {{ number_format($hpp, 0, ',', '.') }} ({{ $margin }}%)</div>
                    </div>
                </div>

                <!-- Daftar Bahan Baku Resep -->
                <div class="space-y-1.5 pt-2 border-t border-neutral-100">
                    <div class="text-[11px] font-semibold text-neutral-600 flex items-center justify-between">
                        <span>Komposisi Bahan Baku:</span>
                        <span class="text-[10px] text-neutral-400">{{ $prod->recipes->count() }} komponen</span>
                    </div>

                    @forelse($prod->recipes as $rec)
                    @php
                        $subCost = (float) ($rec->ingredientProduct?->cost_price ?? 0) * (float) $rec->quantity_required;
                    @endphp
                    <div class="flex items-center justify-between p-2 rounded-xl bg-neutral-50/70 border border-neutral-200/60 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span class="font-medium text-neutral-800">{{ $rec->ingredientProduct?->name ?? 'Bahan Tidak Diketahui' }}</span>
                        </div>
                        <div class="flex items-center gap-3 font-mono text-neutral-600 text-[11px]">
                            <span>{{ (float) $rec->quantity_required }} {{ $rec->unit }}</span>
                            <span class="text-neutral-400 font-sans">(@ Rp {{ number_format($subCost, 0, ',', '.') }})</span>
                            @if($currentUser?->canManageRecipes())
                            <form method="POST" action="{{ route('products.recipes.destroy', $rec) }}" onsubmit="return confirm('Hapus bahan ini dari resep?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold ml-1 cursor-pointer">✕</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="p-3 rounded-xl bg-neutral-50 border border-dashed border-neutral-200 text-center text-neutral-400 text-xs">
                        Belum ada komposisi bahan baku yang dirumuskan.
                    </div>
                    @endforelse
                </div>

                <!-- Form Tambah Bahan Baku Inline -->
                @if($currentUser?->canManageRecipes())
                <form method="POST" action="{{ route('products.recipes.store', $prod) }}" class="pt-2 border-t border-neutral-100 flex items-center gap-2">
                    @csrf
                    <select name="ingredient_product_id" required class="flex-1 px-2.5 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-[11px] text-neutral-800 focus:outline-none focus:border-neutral-900">
                        <option value="">+ Pilih Bahan Baku...</option>
                        @foreach($rawMaterials as $raw)
                        <option value="{{ $raw->id }}">{{ $raw->name }} ({{ $raw->unit }})</option>
                        @endforeach
                    </select>

                    <input type="number" step="0.01" name="quantity_required" placeholder="Jml" required 
                           class="w-16 px-2 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-[11px] text-neutral-800 font-mono text-right focus:outline-none focus:border-neutral-900">

                    <input type="text" name="unit" placeholder="Satuan" required 
                           class="w-16 px-2 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-[11px] text-neutral-800 focus:outline-none focus:border-neutral-900">

                    <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-neutral-900 hover:bg-neutral-800 text-white text-[11px] font-semibold cursor-pointer shrink-0">
                        Tambah
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: VARIAN HARGA & MODIFIERS -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'variants'" class="space-y-6">
        
        <!-- SECTION 1: Varian Ukuran & Kemasan -->
        <div class="rounded-2xl bg-white border border-neutral-200/80 shadow-2xs p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">Varian Ukuran Cup & Kemasan Berat</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Varian yang terikat ke produk dasar tertentu (misal: Regular 8oz vs Large 12oz, atau Kemasan Pouch 250g vs 1kg).</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs font-sans">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold text-neutral-500 uppercase">
                            <th class="py-2.5 px-3">Produk Induk</th>
                            <th class="py-2.5 px-3">Nama Varian</th>
                            <th class="py-2.5 px-3">SKU Varian</th>
                            <th class="py-2.5 px-3 text-right">Penyesuaian Harga</th>
                            <th class="py-2.5 px-3 text-right">Harga Final di Kasir</th>
                            <th class="py-2.5 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($variants as $var)
                        @php
                            $basePrice = (float) ($var->product?->selling_price ?? 0);
                            $finalPrice = $basePrice + (float) $var->price_adjustment;
                        @endphp
                        <tr class="hover:bg-neutral-50/60">
                            <td class="py-2.5 px-3 font-medium text-neutral-900">{{ $var->product?->name ?? '-' }}</td>
                            <td class="py-2.5 px-3 font-semibold text-neutral-800">{{ $var->name }}</td>
                            <td class="py-2.5 px-3 font-mono text-[11px] text-neutral-500">{{ $var->sku ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-neutral-700">
                                {{ (float) $var->price_adjustment >= 0 ? '+' : '' }}Rp {{ number_format($var->price_adjustment, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-neutral-950">
                                Rp {{ number_format($finalPrice, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                @if($currentUser?->canManagePricing())
                                <form method="POST" action="{{ route('products.variants.destroy', $var) }}" onsubmit="return confirm('Hapus varian ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold p-1 cursor-pointer" title="Hapus varian">✕</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-neutral-400 text-xs">Belum ada varian ukuran yang didaftarkan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Form Tambah Varian Cepat -->
            @if($currentUser?->canManagePricing())
            <div class="pt-3 border-t border-neutral-100">
                <form method="POST" action="#" id="variantCreateForm" @submit.prevent="submitVariantForm" class="flex flex-col sm:flex-row items-center gap-2.5">
                    @csrf
                    <select id="variant_product_id" required class="flex-1 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">
                        <option value="">+ Pilih Produk Induk...</option>
                        @foreach($products->where('is_raw_material', false) as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->name }} (Dasar: Rp {{ number_format($prod->selling_price, 0, ',', '.') }})</option>
                        @endforeach
                    </select>

                    <input type="text" id="variant_name" placeholder="Nama Varian (misal: Large 12oz)" required 
                           class="flex-1 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">

                    <input type="number" id="variant_price_adj" step="500" placeholder="Selisih Harga (+/- Rp)" required 
                           class="w-36 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono text-neutral-900 text-right focus:outline-none focus:border-neutral-900">

                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold cursor-pointer shrink-0">
                        Simpan Varian
                    </button>
                </form>
            </div>
            @endif
        </div>

        <!-- SECTION 2: Modifiers & Add-ons Barista -->
        <div class="rounded-2xl bg-white border border-neutral-200/80 shadow-2xs p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">Modifiers, Ekstra & Add-ons Barista</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Pilihan kustomisasi kasir (Susu Gandum, Sirup Artisan, Extra Shot) dengan opsi auto-deduct bahan baku gudang.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs font-sans">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold text-neutral-500 uppercase">
                            <th class="py-2.5 px-3">Grup Modifier</th>
                            <th class="py-2.5 px-3">Nama Opsi Ekstra</th>
                            <th class="py-2.5 px-3 text-right">Harga Tambahan</th>
                            <th class="py-2.5 px-3">Auto-Deduct Bahan Baku</th>
                            <th class="py-2.5 px-3 text-right">Takaran Pemotongan</th>
                            <th class="py-2.5 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($modifiers as $mod)
                        <tr class="hover:bg-neutral-50/60">
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-neutral-100 text-neutral-700">
                                    {{ $mod->group_name }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-neutral-900">{{ $mod->name }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-amber-900">
                                + Rp {{ number_format($mod->price, 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-neutral-700">
                                @if($mod->ingredientProduct)
                                <span class="font-medium text-emerald-700 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    {{ $mod->ingredientProduct->name }}
                                </span>
                                @else
                                <span class="text-neutral-400 text-[11px]">- (Tanpa potong bahan)</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-neutral-600">
                                @if($mod->quantity_deducted > 0)
                                {{ (float) $mod->quantity_deducted }} {{ $mod->unit }}
                                @else
                                -
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                @if($currentUser?->canManagePricing())
                                <form method="POST" action="{{ route('products.modifiers.destroy', $mod) }}" onsubmit="return confirm('Hapus modifier ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold p-1 cursor-pointer" title="Hapus modifier">✕</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-neutral-400 text-xs">Belum ada modifier terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Form Tambah Modifier -->
            @if($currentUser?->canManagePricing())
            <div class="pt-3 border-t border-neutral-100">
                <form method="POST" action="{{ route('products.modifiers.store') }}" class="grid grid-cols-1 sm:grid-cols-6 gap-2.5">
                    @csrf
                    <input type="text" name="name" placeholder="Nama Ekstra (misal: Oat Milk)" required 
                           class="sm:col-span-2 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">

                    <select name="group_name" required class="px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">
                        <option value="Susu Alternatif">Susu Alternatif</option>
                        <option value="Sirup & Flavour">Sirup & Flavour</option>
                        <option value="Custom Shot">Custom Shot</option>
                        <option value="Topping & Float">Topping & Float</option>
                        <option value="Pemanis Alami">Pemanis Alami</option>
                    </select>

                    <input type="number" step="500" name="price" placeholder="Harga (+Rp)" required 
                           class="px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono text-neutral-900 text-right focus:outline-none focus:border-neutral-900">

                    <select name="ingredient_product_id" class="px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">
                        <option value="">Potong Bahan (Opsional)...</option>
                        @foreach($rawMaterials as $raw)
                        <option value="{{ $raw->id }}">{{ $raw->name }}</option>
                        @endforeach
                    </select>

                    <div class="flex items-center gap-1.5">
                        <input type="number" step="0.1" name="quantity_deducted" placeholder="Jml" 
                               class="w-16 px-2 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono text-neutral-900 text-right focus:outline-none focus:border-neutral-900">
                        <input type="text" name="unit" placeholder="Satuan" 
                               class="w-16 px-2 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">
                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold cursor-pointer shrink-0">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: KATEGORI MENU -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'categories'" class="space-y-4">
        <div class="rounded-2xl bg-white border border-neutral-200/80 shadow-2xs p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900">Daftar Kategori Produk & Bahan</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Kelola struktur kategori untuk navigasi tablet kasir POS dan pengelompokan inventori gudang.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs font-sans">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold text-neutral-500 uppercase">
                            <th class="py-2.5 px-3">Nama Kategori</th>
                            <th class="py-2.5 px-3">Deskripsi</th>
                            <th class="py-2.5 px-3 text-center">Jumlah Produk Terkait</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($categories as $cat)
                        <tr class="hover:bg-neutral-50/60">
                            <td class="py-2.5 px-3 font-semibold text-neutral-900">{{ $cat->name }}</td>
                            <td class="py-2.5 px-3 text-neutral-500">{{ $cat->description ?? '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-neutral-800">{{ $cat->products_count }} item</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($currentUser?->isSuperAdmin() || $currentUser?->hasRole(['store_manager', 'supervisor']))
            <div class="pt-3 border-t border-neutral-100">
                <form method="POST" action="{{ route('products.categories.store') }}" class="flex flex-col sm:flex-row items-center gap-2.5">
                    @csrf
                    <input type="text" name="name" placeholder="Nama Kategori Baru" required 
                           class="flex-1 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">

                    <input type="text" name="description" placeholder="Deskripsi Singkat (Opsional)" 
                           class="flex-2 px-3 py-1.5 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">

                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold cursor-pointer shrink-0">
                        + Tambah Kategori
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: TAMBAH / EDIT PRODUK -->
    <!-- ========================================== -->
    <div x-show="openProductModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="w-full max-w-lg rounded-2xl bg-white border border-neutral-200 p-5 sm:p-6 shadow-xl space-y-4" @click.away="openProductModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                <h3 class="text-base font-bold text-neutral-950" x-text="editingProduct ? 'Perbarui Data Produk' : 'Tambah Produk Baru'"></h3>
                <button type="button" @click="openProductModal = false" class="text-neutral-400 hover:text-neutral-700 text-sm font-bold p-1 cursor-pointer">✕</button>
            </div>

            <form :action="editingProduct ? ('/products/' + editingProduct.id) : '{{ route('products.store') }}'" method="POST" class="space-y-3.5">
                @csrf
                <template x-if="editingProduct">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Nama Produk</label>
                    <input type="text" name="name" x-model="productForm.name" required 
                           class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Kode SKU Master</label>
                        <input type="text" name="sku" x-model="productForm.sku" required 
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono text-neutral-900 focus:outline-none focus:border-neutral-900">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Kategori</label>
                        <select name="category_id" x-model="productForm.category_id" required 
                                class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Satuan Unit</label>
                        <input type="text" name="unit" x-model="productForm.unit" required placeholder="cup, pouch, ml" 
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Harga Jual (POS)</label>
                        <input type="number" step="500" name="selling_price" x-model="productForm.selling_price" required 
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono text-neutral-900 text-right focus:outline-none focus:border-neutral-900">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1">Harga Beli / HPP</label>
                        <input type="number" step="500" name="cost_price" x-model="productForm.cost_price" 
                               class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs font-mono text-neutral-900 text-right focus:outline-none focus:border-neutral-900">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-neutral-700 mb-1">Deskripsi Singkat</label>
                    <textarea name="description" x-model="productForm.description" rows="2" 
                              class="w-full px-3 py-2 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-900 focus:outline-none focus:border-neutral-900"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_raw_material" value="1" x-model="productForm.is_raw_material" 
                               class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900">
                        <span class="text-xs text-neutral-700 font-medium">Bahan Mentah / Penunjang (Bukan Menu Jual)</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="openProductModal = false" class="px-4 py-2 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-xs font-semibold text-neutral-700 cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold cursor-pointer">Simpan Produk</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function productsApp() {
    return {
        activeTab: '{{ $activeTab }}',
        openProductModal: false,
        editingProduct: null,
        productForm: {
            name: '',
            sku: '',
            category_id: '{{ $categories->first()?->id ?? 1 }}',
            unit: 'cup',
            selling_price: 35000,
            cost_price: 5000,
            is_raw_material: false,
            description: '',
        },

        resetProductForm() {
            this.productForm = {
                name: '',
                sku: 'PRD-NEW-' + Math.floor(100 + Math.random() * 900),
                category_id: '{{ $categories->first()?->id ?? 1 }}',
                unit: 'cup',
                selling_price: 35000,
                cost_price: 5000,
                is_raw_material: false,
                description: '',
            };
        },

        editProduct(prod) {
            this.editingProduct = prod;
            this.productForm = {
                name: prod.name,
                sku: prod.sku,
                category_id: prod.category_id,
                unit: prod.unit,
                selling_price: prod.selling_price,
                cost_price: prod.cost_price,
                is_raw_material: Boolean(prod.is_raw_material),
                description: prod.description || '',
            };
            this.openProductModal = true;
        },

        openRecipeFor(productId, productName) {
            this.activeTab = 'recipes';
        },

        submitVariantForm(e) {
            const prodId = document.getElementById('variant_product_id').value;
            const name = document.getElementById('variant_name').value;
            const priceAdj = document.getElementById('variant_price_adj').value;

            if (!prodId || !name || priceAdj === '') {
                alert('Silakan lengkapi produk, nama varian, dan selisih harga.');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/products/' + prodId + '/variants';
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const inputName = document.createElement('input');
            inputName.type = 'hidden';
            inputName.name = 'name';
            inputName.value = name;
            form.appendChild(inputName);

            const inputPrice = document.createElement('input');
            inputPrice.type = 'hidden';
            inputPrice.name = 'price_adjustment';
            inputPrice.value = priceAdj;
            form.appendChild(inputPrice);

            document.body.appendChild(form);
            form.submit();
        }
    };
}
</script>
@endsection
