<!DOCTYPE html>
<html lang="id" class="h-full bg-[#fafafa] text-neutral-900 antialiased selection:bg-black selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KAFEINA ERP') - Sistem Kopi Spesialti Terintegrasi</title>
    
    <!-- Fonts (Vercel Geist & Mono Clean Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800&family=Geist+Mono:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Critical Anti-FOUC Style (Instant Hide Before Scripts & CSS Load) -->
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <!-- Compiled CSS & Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="h-full flex overflow-hidden font-sans bg-[#fafafa] text-neutral-900" x-data="kafeinaApp()">

    <!-- MOBILE SIDEBAR BACKDROP -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/40 backdrop-blur-xs lg:hidden"
         @click="sidebarOpen = false"
         style="display: none;"></div>

    <!-- VERCEL-STYLE MINIMALIST LIGHT SIDEBAR (Collapsible Slim Rail Desktop & Mobile) -->
    <aside :class="{
               'translate-x-0': sidebarOpen,
               '-translate-x-full': !sidebarOpen,
               'lg:translate-x-0 lg:w-64 xl:w-72': !sidebarCollapsed,
               'lg:translate-x-0 lg:w-14 xl:w-14': sidebarCollapsed
           }"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-white text-neutral-900 flex flex-col border-r border-[#eaeaea] transition-[width,transform] duration-200 ease-out select-none shrink-0 lg:static overflow-visible">
        
        <!-- SIDEBAR HEADER: Workspace & Single Unified Toggle Button -->
        <div class="p-3 border-b border-[#eaeaea] shrink-0" :class="sidebarCollapsed ? 'px-1.5 py-3' : 'space-y-3 p-3.5'">
            <!-- Workspace Selector & Toggle Button -->
            <div class="flex items-center" :class="sidebarCollapsed ? 'flex-col items-center gap-2' : 'justify-between'">
                <a href="{{ route('dashboard.index') }}" class="flex items-center gap-2.5 min-w-0 group" :class="sidebarCollapsed ? 'justify-center' : 'flex-1'" :title="sidebarCollapsed ? 'KAFEINA Roastery' : ''">
                    <div class="w-7 h-7 rounded-lg bg-neutral-950 flex items-center justify-center text-white shadow-xs shrink-0 border border-neutral-800 group-hover:scale-105 transition-all">
                        <svg class="w-4.5 h-4.5" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="8" y="7" width="5" height="26" rx="2.5" fill="#FFFFFF"/>
                            <path d="M16 19.5C16 13 22 8 29 8C29 15 23 20 16 19.5Z" fill="#FFFFFF"/>
                            <path d="M16 20.5C21 21 31 23 31 32C24 32 18 28 16 20.5Z" fill="#EA580C"/>
                            <path d="M21 11.5C22.5 13.5 24 16 23.5 18" stroke="#171717" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span x-show="!sidebarCollapsed" class="font-semibold text-[13px] text-neutral-900 truncate tracking-tight">kafeina-roastery</span>
                </a>

                <div class="flex items-center">
                    <button @click="sidebarOpen = false" class="lg:hidden p-1 text-neutral-400 hover:text-black rounded-md hover:bg-neutral-100 ml-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <!-- SINGLE TOGGLE BUTTON: Flips between « and » in place -->
                    <button @click="toggleSidebar()" 
                            class="hidden lg:flex p-1.5 text-neutral-500 hover:text-neutral-900 rounded-lg hover:bg-neutral-100 transition-colors cursor-pointer" 
                            :title="sidebarCollapsed ? 'Tampilkan Bar Samping (Ctrl+B)' : 'Sembunyikan Bar Samping (Ctrl+B)'">
                        <svg x-show="!sidebarCollapsed" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                        <svg x-show="sidebarCollapsed" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Vercel Search 'Find [F]' Interactive Input (Visible when expanded) -->
            <div x-show="!sidebarCollapsed" class="relative">
                <div class="flex items-center w-full px-2.5 py-1.5 rounded-lg bg-neutral-50 hover:bg-neutral-100/80 border border-neutral-200/90 text-neutral-800 text-xs transition-colors focus-within:border-neutral-900 focus-within:bg-white group shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-neutral-400 group-hover:text-neutral-700 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text"
                           id="sidebar-search-input"
                           x-model="sidebarSearch"
                           placeholder="Cari modul..."
                           class="w-full bg-transparent border-none text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none font-sans"
                           @keydown.escape="$el.blur(); sidebarSearch = ''" />
                    <template x-if="!sidebarSearch">
                        <kbd class="px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-[10px] text-neutral-400 shadow-2xs tabular-nums shrink-0">F</kbd>
                    </template>
                    <button x-show="sidebarSearch" 
                            x-cloak
                            @click="sidebarSearch = ''; $el.previousElementSibling.focus()"
                            class="text-neutral-400 hover:text-neutral-900 font-mono text-xs px-1 cursor-pointer">
                        &times;
                    </button>
                </div>
            </div>
        </div>

        <!-- SIDEBAR NAVIGATION MENU (Vercel Clean Nav List) -->
        <div class="flex-1 overflow-y-auto px-2.5 py-3 space-y-4 scrollbar-none">
            
            @php
                $canSection1 = $currentUser?->canAccessModule('dashboard') || $currentUser?->canAccessModule('pos') || $currentUser?->canAccessModule('vouchers') || $currentUser?->canAccessModule('products');
                $canSection2 = $currentUser?->canAccessModule('procurement') || $currentUser?->canAccessModule('qc-lab') || $currentUser?->canAccessModule('roastery') || $currentUser?->canAccessModule('yield-loss') || $currentUser?->canAccessModule('inventory') || $currentUser?->canAccessModule('logistics');
                $canSection3 = $currentUser?->canAccessModule('finance');
            @endphp

            <!-- SECTION 1: CORE PORTAL -->
            @if($canSection1)
            <div>
                <div x-show="!sidebarCollapsed" class="px-2.5 mb-1 text-[10px] font-semibold uppercase tracking-wider text-neutral-400">
                    Utama & Penjualan
                </div>
                <div class="space-y-0.5">
                    <!-- Dasbor -->
                    @if($currentUser?->canAccessModule('dashboard'))
                    <a href="{{ route('dashboard.index') }}"
                       x-show="!sidebarSearch || 'dasbor eksekutif overview analytics home'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Dasbor Eksekutif (⌘0)' : ''" {{ request()->routeIs('dashboard.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Dasbor Eksekutif</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘0</span>
                    </a>
                    @endif

                    <!-- Web POS -->
                    @if($currentUser?->canAccessModule('pos'))
                    <a href="{{ route('pos.index') }}"
                       x-show="!sidebarSearch || 'kasir pos point of sale terminal barista cashier'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Kasir POS (⌘1)' : ''" {{ request()->routeIs('pos.index') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('pos.index') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Kasir POS</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘1</span>
                    </a>
                    @endif

                    <!-- Riwayat Penjualan & Order -->
                    @if($currentUser?->canAccessModule('pos') || $currentUser?->canAccessModule('sales'))
                    <a href="{{ route('sales.index') }}"
                       x-show="!sidebarSearch || 'riwayat order penjualan transaksi customer sales history receipt'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Riwayat Order (⌘2)' : ''" {{ request()->routeIs('sales.*') || request()->routeIs('pos.orders*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('sales.*') || request()->routeIs('pos.orders*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Riwayat Order & Penjualan</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘2</span>
                    </a>
                    @endif

                    <!-- Voucher & Kuota Diskon -->
                    @if($currentUser?->canAccessModule('vouchers'))
                    <a href="{{ route('vouchers.index') }}"
                       x-show="!sidebarSearch || 'voucher diskon promo kuota kode coupon discount'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Voucher & Kuota Promo (⌘3)' : ''" {{ request()->routeIs('vouchers.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('vouchers.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Voucher & Kuota Promo</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘3</span>
                    </a>
                    @endif

                    <!-- Katalog Produk, Resep & Varian -->
                    @if($currentUser?->canAccessModule('products'))
                    <a href="{{ route('products.index') }}"
                       x-show="!sidebarSearch || 'produk resep varian harga katalog menu modifier bom product recipe'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Katalog & Resep (⌘4 / ⌘P)' : ''" {{ request()->routeIs('products.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('products.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Katalog Produk & Resep</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘4</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- SECTION 2: OPERATIONS & MANUFACTURING (6 INDEPENDENT MODULES) -->
            @if($canSection2)
            <div>
                <div x-show="!sidebarCollapsed" class="px-2.5 mb-1 text-[10px] font-semibold uppercase tracking-wider text-neutral-400">
                    Operasional Roastery
                </div>
                <div class="space-y-0.5">
                    <!-- 1. Pengadaan Biji Mentah & Mitra Petani -->
                    @if($currentUser?->canAccessModule('procurement'))
                    <a href="{{ route('procurement.index') }}"
                       x-show="!sidebarSearch || 'pengadaan procurement mitra petani kopi purchase order po'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Pengadaan (Procurement) (⌘5)' : ''" {{ request()->routeIs('procurement.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('procurement.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Pengadaan (Procurement)</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘5</span>
                    </a>
                    @endif

                    <!-- 2. QC Lab & Pengujian Mutu Green Beans -->
                    @if($currentUser?->canAccessModule('qc-lab'))
                    <a href="{{ route('qc-lab.index') }}"
                       x-show="!sidebarSearch || 'qc lab laboratorium mutu fisik green beans cupping score'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'QC Lab & Laboratorium (⌘6)' : ''" {{ request()->routeIs('qc-lab.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('qc-lab.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">QC Lab & Laboratorium</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘6</span>
                    </a>
                    @endif

                    <!-- 3. Roastery & Work Order Sangrai -->
                    @if($currentUser?->canAccessModule('roastery'))
                    <a href="{{ route('roastery.index') }}"
                       x-show="!sidebarSearch || 'roastery work orders bom sangrai giesen diedrich produksi'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Roastery & Work Orders (⌘7)' : ''" {{ request()->routeIs('roastery.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('roastery.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Roastery & Work Orders</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘7</span>
                    </a>
                    @endif

                    <!-- 4. Yield Loss & Monitor Susut Sangrai -->
                    @if($currentUser?->canAccessModule('yield-loss'))
                    <a href="{{ route('yield-loss.index') }}"
                       x-show="!sidebarSearch || 'yield loss monitor susut sangrai hpp riil profil suhu agtron'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Yield Loss (Monitor Susut) (⌘8)' : ''" {{ request()->routeIs('yield-loss.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('yield-loss.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Yield Loss (Monitor Susut)</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘8</span>
                    </a>
                    @endif

                    <!-- 5. Inventori Multi-Cabang & ROP -->
                    @if($currentUser?->canAccessModule('inventory'))
                    <a href="{{ route('inventory.index') }}"
                       x-show="!sidebarSearch || 'inventori multi-cabang stok saldo rop safety stock gudang warehouse'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Inventori Multi-Cabang (⌘9)' : ''" {{ request()->routeIs('inventory.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('inventory.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Inventori Multi-Cabang</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘9</span>
                    </a>
                    @endif

                    <!-- 6. Logistik & Distribusi Antar-Cabang -->
                    @if($currentUser?->canAccessModule('logistics'))
                    <a href="{{ route('logistics.index') }}"
                       x-show="!sidebarSearch || 'logistik distribusi surat jalan delivery notes mr qr transit armada'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Logistik & Distribusi (⌘D)' : ''" {{ request()->routeIs('logistics.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('logistics.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Logistik & Distribusi</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘D</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- SECTION 3: FINANCIAL & AUDIT -->
            @if($canSection3)
            <div>
                <div x-show="!sidebarCollapsed" class="px-2.5 mb-1 text-[10px] font-semibold uppercase tracking-wider text-neutral-400">
                    Finansial & Audit
                </div>
                <div class="space-y-0.5">
                    <!-- Finansial & P&L -->
                    @if($currentUser?->canAccessModule('finance'))
                    <a href="{{ route('finance.index') }}"
                       x-show="!sidebarSearch || 'laba rugi riil finance p&l keuangan laporan audit omzet'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Laba Rugi Riil (P&L) (⌘L)' : ''" {{ request()->routeIs('finance.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('finance.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Laba Rugi Riil (P&L)</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘L</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- SECTION 4: ADMINISTRATION & SETTINGS (SUPER ADMIN ONLY) -->
            @if($currentUser?->isSuperAdmin())
            <div>
                <div x-show="!sidebarCollapsed" class="px-2.5 mb-1 text-[10px] font-semibold uppercase tracking-wider text-neutral-400">
                    Administrasi & Sistem
                </div>
                <div class="space-y-0.5">
                    <a href="{{ route('settings.index') }}"
                       x-show="!sidebarSearch || 'pengaturan sistem master data branches warehouses users rbac midtrans payment database audit'.includes(sidebarSearch.toLowerCase())" 
                       class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition-craft-btn group" :class="sidebarCollapsed ? 'justify-center px-1' : 'justify-between px-2.5'" :title="sidebarCollapsed ? 'Pengaturan Sistem (⌘S)' : ''" {{ request()->routeIs('settings.*') ? 'bg-neutral-100 text-black font-semibold' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}>
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('settings.*') ? 'text-black' : 'text-neutral-500 group-hover:text-black' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span x-show="!sidebarCollapsed">Pengaturan Sistem</span>
                        </div>
                        <span x-show="!sidebarCollapsed" class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-neutral-200 text-neutral-400 group-hover:text-neutral-600 tabular-nums shrink-0 ml-auto">⌘S</span>
                    </a>
                </div>
            </div>
            @endif
        </div>

        <!-- SIDEBAR FOOTER: USER PROFILE & ACCOUNT SWITCHER (Real ERP Integration) -->
        <div class="px-3 py-2.5 border-t border-[#eaeaea] shrink-0 bg-white relative z-50">
            
            <!-- Real Account Popover (Z-[99999] Topmost Stacking) -->
            <div x-show="popoverOpen"
                 x-cloak
                 @click.away="popoverOpen = false"
                 x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100 motion-reduce:transition-none"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="absolute bottom-full left-2 mb-2 w-72 sm:w-80 bg-white border border-[#eaeaea] rounded-2xl shadow-[0_24px_60px_rgba(0,0,0,0.22)] z-[99999] origin-bottom-left font-sans divide-y divide-[#f0f0f0] overflow-hidden"
                 style="display: none;">
                
                <!-- 1. Header: Profil Akun & Role Terverifikasi -->
                <div class="px-4 py-3 flex items-start justify-between bg-neutral-50/50">
                    <div class="min-w-0 pr-2">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-neutral-900 text-white font-mono text-[11px] font-bold flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($currentUser?->name ?? 'D', 0, 1)) }}
                            </div>
                            <div class="text-[13px] font-semibold text-neutral-900 truncate">
                                {{ explode(' (', $currentUser?->name ?? 'Pengguna')[0] }}
                            </div>
                        </div>
                        <div class="text-xs text-neutral-500 truncate mt-1">
                            {{ $currentUser?->email ?? 'admin@kafeina.id' }}
                        </div>
                        <div class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-white border border-neutral-200/80 text-[10px] font-mono font-medium text-neutral-800 shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ $currentUser?->role?->display_name ?? 'User' }}
                            </span>
                            <span class="text-[10px] text-neutral-400 font-mono">•</span>
                            <span class="text-[10px] text-neutral-500 font-mono truncate max-w-[130px]">
                                {{ $currentUser?->branch?->name ? explode(' (', explode(' - ', $currentUser->branch->name)[0])[0] : 'Sentral Roastery' }}
                            </span>
                        </div>
                    </div>
                    <button type="button" 
                            title="Pengaturan Akun Pengguna" 
                            @click="userAccountModalOpen = true; popoverOpen = false"
                            class="text-neutral-400 hover:text-black transition-colors p-1.5 rounded-lg hover:bg-neutral-100 cursor-pointer shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>

                <!-- 2. Alih Role & Akun Cepat (Super Admin / Active Impersonator Only) -->
                @if($currentUser?->isSuperAdmin() || session()->has('impersonator_id'))
                <div class="py-2">
                    <div class="px-4 pb-1.5 flex items-center justify-between text-[10px] font-semibold text-neutral-400 uppercase tracking-wider font-mono">
                        <span>Beralih Akun & Peran</span>
                        <span class="text-neutral-400 font-normal">Super Admin</span>
                    </div>
                    <div class="max-h-44 overflow-y-auto px-2 space-y-0.5 custom-scrollbar">
                        @foreach($allUsers as $u)
                        <a href="{{ route('switch-user.get', $u->id) }}" 
                           class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-colors group {{ $currentUser && $currentUser->id === $u->id ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-600 hover:text-black hover:bg-neutral-50' }}">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $currentUser && $currentUser->id === $u->id ? 'bg-emerald-500' : 'bg-neutral-300 group-hover:bg-neutral-400' }}"></span>
                                <div class="truncate">
                                    <span class="text-neutral-900 group-hover:text-black">{{ explode(' (', $u->name)[0] }}</span>
                                    <span class="text-[10px] text-neutral-400 font-mono ml-1">({{ $u->role?->display_name ?? 'User' }})</span>
                                </div>
                            </div>
                            @if($currentUser && $currentUser->id === $u->id)
                            <span class="text-[10px] font-mono font-bold text-emerald-600 px-1 py-0.2 bg-emerald-50 rounded">AKTIF</span>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- 3. Tema Tampilan & Pintasan Operasional -->
                <div class="py-1.5">
                    <!-- Theme Toggle -->
                    <div class="px-4 py-1.5 flex items-center justify-between text-xs text-neutral-800">
                        <span>Tema Tampilan</span>
                        <div class="inline-flex items-center p-0.5 rounded-full border border-neutral-200 bg-neutral-50 text-neutral-500">
                            <!-- System -->
                            <button type="button" @click="setTheme('system')" title="Sistem" class="p-1 rounded-full transition-colors" :class="theme === 'system' ? 'bg-white text-black shadow-2xs font-semibold' : 'hover:text-black'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="12" rx="2" stroke-width="1.8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 20h8m-4-4v4" />
                                </svg>
                            </button>
                            <!-- Light -->
                            <button type="button" @click="setTheme('light')" title="Terang" class="p-1 rounded-full transition-colors" :class="theme === 'light' ? 'bg-white text-black shadow-2xs font-semibold' : 'hover:text-black'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="4" stroke-width="1.8" />
                                    <path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                                </svg>
                            </button>
                            <!-- Dark -->
                            <button type="button" @click="setTheme('dark')" title="Gelap" class="p-1 rounded-full transition-colors" :class="theme === 'dark' ? 'bg-white text-black shadow-2xs font-semibold' : 'hover:text-black'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- SOP Roastery & Panduan Mutu -->
                    <button type="button" 
                            @click="sopModalOpen = true; popoverOpen = false" 
                            class="w-full flex items-center justify-between px-4 py-2 hover:bg-neutral-50 text-neutral-800 hover:text-black text-xs transition-colors cursor-pointer text-left">
                        <span class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>Standar SOP Mutu & Roasting</span>
                        </span>
                        <span class="text-[10px] font-mono text-neutral-400">SCAA 80+</span>
                    </button>

                    <!-- Pengaturan Akun & Profil Pengguna -->
                    <button type="button" 
                            @click="userAccountModalOpen = true; popoverOpen = false" 
                            class="w-full flex items-center justify-between px-4 py-2 hover:bg-neutral-50 text-neutral-800 hover:text-black text-xs transition-colors cursor-pointer text-left">
                        <span class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Pengaturan Akun & Profil</span>
                        </span>
                        <span class="text-[10px] font-mono text-neutral-400">Profil</span>
                    </button>

                    <!-- Log Out Form (Real CSRF Session Termination) -->
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" 
                                class="w-full flex items-center justify-between px-4 py-2 hover:bg-red-50 text-neutral-700 hover:text-red-600 text-xs transition-colors cursor-pointer text-left group">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-neutral-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Keluar dari Sistem (Log Out)</span>
                            </span>
                            <span class="text-[10px] font-mono text-neutral-400 group-hover:text-red-500">Sesi</span>
                        </button>
                    </form>
                </div>

                <!-- 4. Real System Health Status Footer -->
                <div class="px-4 py-2.5 bg-neutral-50/80 border-t border-[#eaeaea] flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-neutral-700 font-medium text-xs">Semua Sistem Normal</span>
                    </div>
                    <span class="text-[11px] font-mono text-neutral-400">Latensi 8ms</span>
                </div>
            </div>

            <!-- Real Kafeina Notification Popover (Z-[99999] Topmost Stacking) -->
            <div x-show="notifOpen"
                 x-cloak
                 @click.away="notifOpen = false"
                 x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100 motion-reduce:transition-none"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="absolute bottom-full left-2 mb-2 w-[360px] sm:w-[410px] bg-white border border-[#eaeaea] rounded-2xl shadow-[0_24px_60px_rgba(0,0,0,0.22)] z-[99999] origin-bottom-left font-sans flex flex-col divide-y divide-[#f0f0f0] overflow-hidden"
                 style="display: none;">
                
                <!-- Notification Header: Drag Handle + Real Tabs + Action -->
                <div class="flex items-center justify-between px-3 bg-neutral-50/60 border-b border-[#eaeaea]">
                    <div class="flex items-center gap-2">
                        <!-- 6-dots grip icon -->
                        <div class="text-neutral-400 select-none">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="currentColor">
                                <circle cx="5" cy="4" r="1.2" />
                                <circle cx="11" cy="4" r="1.2" />
                                <circle cx="5" cy="8" r="1.2" />
                                <circle cx="11" cy="8" r="1.2" />
                                <circle cx="5" cy="12" r="1.2" />
                                <circle cx="11" cy="12" r="1.2" />
                            </svg>
                        </div>
                        
                        <!-- Navigation Tabs -->
                        <div class="flex items-center gap-3 text-xs font-medium ml-1">
                            <button @click="activeNotifTab = 'all'"
                                    type="button"
                                    class="py-2.5 relative transition-colors focus:outline-none cursor-pointer flex items-center gap-1.5"
                                    :class="activeNotifTab === 'all' ? 'text-black font-semibold' : 'text-neutral-500 hover:text-black'">
                                <span>Semua</span>
                                <span x-show="unreadCount > 0" class="px-1.5 py-0.2 rounded-full bg-red-100 text-red-600 text-[10px] font-mono font-bold" x-text="unreadCount"></span>
                                <span x-show="activeNotifTab === 'all'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-black"></span>
                            </button>
                            <button @click="activeNotifTab = 'stock'"
                                    type="button"
                                    class="py-2.5 relative transition-colors focus:outline-none cursor-pointer flex items-center gap-1.5"
                                    :class="activeNotifTab === 'stock' ? 'text-black font-semibold' : 'text-neutral-500 hover:text-black'">
                                <span>Stok & ROP</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span x-show="activeNotifTab === 'stock'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-black"></span>
                            </button>
                            <button @click="activeNotifTab = 'roastery'"
                                    type="button"
                                    class="py-2.5 relative transition-colors focus:outline-none cursor-pointer flex items-center gap-1.5"
                                    :class="activeNotifTab === 'roastery' ? 'text-black font-semibold' : 'text-neutral-500 hover:text-black'">
                                <span>Roastery & QC</span>
                                <span x-show="activeNotifTab === 'roastery'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-black"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Mark all read action icon -->
                    <button type="button" 
                            @click="markAllAsRead()"
                            title="Tandai Semua Telah Dibaca" 
                            class="text-neutral-400 hover:text-black transition-colors p-1 rounded hover:bg-neutral-100 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>

                <!-- Real Notifications List -->
                <div class="divide-y divide-[#f5f5f5] max-h-72 overflow-y-auto custom-scrollbar">
                    
                    <!-- Alert 1: Green Beans Gayo Kritis (Stock) -->
                    <a href="{{ route('inventory.index') }}" 
                       x-show="activeNotifTab === 'all' || activeNotifTab === 'stock'"
                       class="p-3.5 flex items-start gap-3 hover:bg-neutral-50/80 transition-colors cursor-pointer group block">
                        <div class="w-7 h-7 rounded-full bg-red-50 border border-red-200/80 flex items-center justify-center text-red-600 shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="px-1 py-0.2 rounded bg-red-100 text-red-700 text-[9px] font-mono font-bold uppercase">Batas ROP</span>
                                <span class="text-[11px] text-neutral-400 font-mono">Central Hub</span>
                            </div>
                            <div class="text-xs text-neutral-800 leading-snug">
                                <span class="font-semibold text-neutral-900">Green Beans Aceh Gayo Honey</span> tersisa <span class="font-bold text-red-600">35.0 kg</span> (Batas ROP: 100.0 kg).
                            </div>
                            <div class="text-[11px] text-[#0070f3] font-medium mt-1 group-hover:underline">Buka Inventori →</div>
                        </div>
                        <span class="text-[10px] text-neutral-400 shrink-0 tabular-nums font-mono">12 mnt</span>
                    </a>

                    <!-- Alert 2: Cold Cup PET 12oz Menipis (Stock) -->
                    <a href="{{ route('logistics.index') }}" 
                       x-show="activeNotifTab === 'all' || activeNotifTab === 'stock'"
                       class="p-3.5 flex items-start gap-3 hover:bg-neutral-50/80 transition-colors cursor-pointer group block">
                        <div class="w-7 h-7 rounded-full bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-600 shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="px-1 py-0.2 rounded bg-amber-100 text-amber-800 text-[9px] font-mono font-bold uppercase">Menipis</span>
                                <span class="text-[11px] text-neutral-400 font-mono">Gerai Senopati</span>
                            </div>
                            <div class="text-xs text-neutral-800 leading-snug">
                                <span class="font-semibold text-neutral-900">Cold Cup PET 12oz</span> tersisa <span class="font-bold text-amber-700">250 pcs</span> (Minimum: 1.000 pcs).
                            </div>
                            <div class="text-[11px] text-[#0070f3] font-medium mt-1 group-hover:underline">Minta Pasokan Logistik →</div>
                        </div>
                        <span class="text-[10px] text-neutral-400 shrink-0 tabular-nums font-mono">35 mnt</span>
                    </a>

                    <!-- Alert 3: Roasting Work Order Selesai (Roastery) -->
                    <a href="{{ route('roastery.index') }}" 
                       x-show="activeNotifTab === 'all' || activeNotifTab === 'roastery'"
                       class="p-3.5 flex items-start gap-3 hover:bg-neutral-50/80 transition-colors cursor-pointer group block">
                        <div class="w-7 h-7 rounded-full bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600 shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="px-1 py-0.2 rounded bg-emerald-100 text-emerald-800 text-[9px] font-mono font-bold uppercase">Sangrai Selesai</span>
                                <span class="text-[11px] text-neutral-400 font-mono">Giesen W15A</span>
                            </div>
                            <div class="text-xs text-neutral-800 leading-snug">
                                <span class="font-semibold text-neutral-900">WO-202611-001 (Gayo Honey)</span> selesai. Susut sangrai <span class="font-bold text-emerald-600">15.6%</span> (Optimal dalam toleransi 14–18%).
                            </div>
                            <div class="text-[11px] text-[#0070f3] font-medium mt-1 group-hover:underline">Periksa Batch Sangrai →</div>
                        </div>
                        <span class="text-[10px] text-neutral-400 shrink-0 tabular-nums font-mono">1 jam</span>
                    </a>

                    <!-- Alert 4: QC Cupper SCA 87.5 (Roastery) -->
                    <a href="{{ route('qc-lab.index') }}" 
                       x-show="activeNotifTab === 'all' || activeNotifTab === 'roastery'"
                       class="p-3.5 flex items-start gap-3 hover:bg-neutral-50/80 transition-colors cursor-pointer group block">
                        <div class="w-7 h-7 rounded-full bg-blue-50 border border-blue-200/80 flex items-center justify-center text-blue-600 shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="px-1 py-0.2 rounded bg-blue-100 text-blue-800 text-[9px] font-mono font-bold uppercase">Specialty 87.5</span>
                                <span class="text-[11px] text-neutral-400 font-mono">Q-Grader</span>
                            </div>
                            <div class="text-xs text-neutral-800 leading-snug">
                                <span class="font-semibold text-neutral-900">Lot GB-GYO-2026-0001</span> lolos verifikasi cupping SCA Grade 1. Siap dialokasikan ke roastery.
                            </div>
                            <div class="text-[11px] text-[#0070f3] font-medium mt-1 group-hover:underline">Lihat Hasil Laboratorium →</div>
                        </div>
                        <span class="text-[10px] text-neutral-400 shrink-0 tabular-nums font-mono">3 jam</span>
                    </a>

                </div>

                <!-- Push Notification Enable Box -->
                <div x-show="showPushBanner" class="p-3 bg-neutral-50/80 border border-[#eaeaea] rounded-2xl m-3 space-y-2.5">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-[#0070f3] mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-neutral-800 leading-snug">
                                Aktifkan notifikasi desktop untuk menerima peringatan stok kritis dan jadwal sangrai secara langsung.
                            </div>
                            <div x-show="notifPermissionStatus === 'denied'" class="mt-1.5 p-1.5 rounded-lg bg-amber-50 border border-amber-200 text-[11px] text-amber-800 leading-tight">
                                <span class="font-semibold">Izin Ditolak Peramban:</span> Silakan izinkan "Notifications" pada ikon gembok di sebelah kiri address bar browser Anda.
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-0.5">
                        <button @click="dismissPushBanner()" 
                                type="button" 
                                class="w-full py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-100 text-xs font-medium text-neutral-700 transition-colors text-center cursor-pointer">
                            Nanti
                        </button>
                        <button @click="enablePushNotifications()" 
                                type="button" 
                                class="w-full py-1.5 rounded-lg bg-black hover:bg-neutral-800 text-xs font-medium text-white transition-colors text-center cursor-pointer shadow-2xs">
                            Aktifkan
                        </button>
                    </div>
                </div>

                <!-- Footer: Archive & Mark All Read -->
                <div class="p-2.5 flex items-center justify-between bg-neutral-50/50">
                    <button @click="markAllAsRead()" 
                            type="button" 
                            class="text-xs font-medium text-neutral-600 hover:text-black transition-colors cursor-pointer flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Tandai Semua Dibaca</span>
                    </button>
                    <span class="text-[11px] font-mono text-neutral-400" x-text="unreadCount === 0 ? 'Semua telah dibaca' : unreadCount + ' belum dibaca'"></span>
                </div>
            </div>

            <!-- Bottom Row: User Avatar + Name + Action Buttons -->
            <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">
                <!-- User Profile Info Button (Clickable to trigger popover) -->
                <button @click="popoverOpen = !popoverOpen; notifOpen = false"
                        type="button"
                        class="flex items-center gap-2.5 min-w-0 text-left cursor-pointer group focus:outline-none">
                    <!-- Green Identicon Avatar (Exact 1:1 match with Vercel design) -->
                    <div class="w-6 h-6 rounded-full bg-[#edf2ec] flex items-center justify-center shrink-0 overflow-hidden ring-1 ring-black/5 group-hover:ring-black/20 transition-all">
                        <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="2" y="2" width="3" height="3" fill="#6fba65" rx="0.5" />
                            <rect x="11" y="2" width="3" height="3" fill="#6fba65" rx="0.5" />
                            <rect x="2" y="7" width="12" height="3" fill="#6fba65" rx="0.5" />
                            <rect x="2" y="11" width="3" height="3" fill="#6fba65" rx="0.5" />
                            <rect x="11" y="11" width="3" height="3" fill="#6fba65" rx="0.5" />
                        </svg>
                    </div>
                    <!-- Username text -->
                    <span x-show="!sidebarCollapsed" class="text-[13px] font-normal text-neutral-900 group-hover:text-black truncate max-w-[115px] tracking-tight">
                        {{ $currentUser ? Str::slug(explode(' (', $currentUser->name)[0], '-') : 'kelompok-1-erp' }}
                    </span>
                </button>

                <!-- Action Buttons: [...] and [Notification Bell] -->
                <div x-show="!sidebarCollapsed" class="flex items-center gap-1.5 shrink-0">
                    <!-- Three Dots Options Button (triggers popover) -->
                    <button @click="popoverOpen = !popoverOpen; notifOpen = false"
                            type="button"
                            title="Opsi Akun & Alih Role"
                            class="w-7 h-7 rounded-full border border-neutral-200/90 hover:bg-neutral-100/80 active:bg-neutral-200 flex items-center justify-center text-neutral-600 hover:text-black transition-colors cursor-pointer"
                            :class="popoverOpen ? 'bg-neutral-100 text-black border-neutral-400' : ''">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="5" cy="12" r="1.6" />
                            <circle cx="12" cy="12" r="1.6" />
                            <circle cx="19" cy="12" r="1.6" />
                        </svg>
                    </button>

                    <!-- Notification Bell Button with Reactive Blue Dot Badge -->
                    <button @click="notifOpen = !notifOpen; popoverOpen = false"
                            type="button"
                            title="Notifikasi ERP"
                            class="w-7 h-7 rounded-full border border-neutral-200/90 hover:bg-neutral-100/80 active:bg-neutral-200 flex items-center justify-center text-neutral-600 hover:text-black transition-colors cursor-pointer relative"
                            :class="notifOpen ? 'bg-neutral-100 text-black border-neutral-400' : ''">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <!-- Notification Blue Dot (Disappears when marked as read) -->
                        <span x-show="hasUnreadNotif && unreadCount > 0" class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-[#0070f3] ring-2 ring-white"></span>
                    </button>
                </div>
            </div>
        </div>
        </div>
    </aside>


    <!-- RIGHT MAIN CONTENT WORKSPACE -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden bg-[#fafafa]">
        
        <!-- TOP BREADCRUMB & UTILITY HEADER (Vercel Style: Left Scope | Center Page Title | Right [+ Create New] & [Agent]) -->
        <header class="h-12 sm:h-14 bg-white border-b border-[#eaeaea] px-4 sm:px-6 lg:px-8 flex items-center justify-between shrink-0 sticky top-0 z-30 relative" x-data="{ createNewOpen: false, agentModalOpen: false }">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                <!-- Mobile Hamburger Toggle -->
                <button @click="sidebarOpen = true" class="lg:hidden p-1.5 rounded-lg bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 text-neutral-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>



                <!-- Left: Scope / Branch Selector (Role-Guarded) -->
                @if($currentUser?->canSwitchBranch())
                <div class="relative shrink-0" x-data="{ branchDropdownOpen: false }">
                    <button @click="branchDropdownOpen = !branchDropdownOpen"
                            type="button"
                            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-medium text-neutral-900 hover:text-black transition-colors focus:outline-none cursor-pointer py-1"
                            title="Beralih Lingkup Cabang (Multi-Cabang)">
                        <span class="truncate max-w-[140px]" x-text="(branchShortLabels[activeBranch] || 'Semua Cabang').slice(0, 15)">Semua Cabang</span>
                        <!-- Vercel Chevrons Up-Down SVG -->
                        <svg class="w-3.5 h-3.5 text-neutral-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                        </svg>
                    </button>

                    <!-- Dropdown Popover Menu -->
                    <div x-show="branchDropdownOpen"
                         x-cloak
                         style="display: none;"
                         @click.away="branchDropdownOpen = false"
                         x-transition:enter="transition ease-out duration-100 motion-reduce:transition-none"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75 motion-reduce:transition-none"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute left-0 mt-1.5 w-44 p-1 bg-white border border-[#eaeaea] rounded-xl shadow-xl z-50 origin-top-left divide-y divide-[#f5f5f5]">
                        <div class="p-1 space-y-0.5 text-xs">
                            @if(request()->routeIs('pos.*'))
                                <!-- Khusus Terminal Kasir (POS): Block 'Semua Cabang', Sediakan 4 Gerai Fisik (Ritel & Roastery Tasting Bar) -->
                                <div class="px-2.5 py-1 text-[10px] font-semibold text-neutral-400 uppercase tracking-wider border-b border-neutral-100 mb-1">
                                    <span>Pilih Gerai Ritel & Lab POS</span>
                                </div>
                                <button @click="changeBranch('senopati'); branchDropdownOpen = false" 
                                        type="button"
                                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left transition-colors cursor-pointer"
                                        :class="activeBranch === 'senopati' ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-700 hover:bg-neutral-50'">
                                    <div class="flex flex-col">
                                        <span>Senopati</span>
                                        <span class="text-[10px] text-neutral-400 font-normal">Flagship Store (WH-BAR-SENO)</span>
                                    </div>
                                    <span x-show="activeBranch === 'senopati'"><svg class="w-3.5 h-3.5 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                                <button @click="changeBranch('braga'); branchDropdownOpen = false" 
                                        type="button"
                                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left transition-colors cursor-pointer"
                                        :class="activeBranch === 'braga' ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-700 hover:bg-neutral-50'">
                                    <div class="flex flex-col">
                                        <span>Braga Bandung</span>
                                        <span class="text-[10px] text-neutral-400 font-normal">Heritage Bar (WH-BAR-BRG)</span>
                                    </div>
                                    <span x-show="activeBranch === 'braga'"><svg class="w-3.5 h-3.5 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                                <button @click="changeBranch('malioboro'); branchDropdownOpen = false" 
                                        type="button"
                                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left transition-colors cursor-pointer"
                                        :class="activeBranch === 'malioboro' ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-700 hover:bg-neutral-50'">
                                    <div class="flex flex-col">
                                        <span>Malioboro</span>
                                        <span class="text-[10px] text-neutral-400 font-normal">Slow Bar & Hub (WH-BAR-MLO)</span>
                                    </div>
                                    <span x-show="activeBranch === 'malioboro'"><svg class="w-3.5 h-3.5 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                                <button @click="changeBranch('central'); branchDropdownOpen = false" 
                                        type="button"
                                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left transition-colors cursor-pointer"
                                        :class="activeBranch === 'central' ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-700 hover:bg-neutral-50'">
                                    <div class="flex flex-col">
                                        <span>Central Roastery & Lab</span>
                                        <span class="text-[10px] text-neutral-400 font-normal">Tasting Bar & Lab Hub (WH-BAR-HQ)</span>
                                    </div>
                                    <span x-show="activeBranch === 'central'"><svg class="w-3.5 h-3.5 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                            @else
                                <!-- Scope Global untuk Modul Manajemen & Analitik Non-POS -->
                                <button @click="changeBranch('all'); branchDropdownOpen = false" 
                                        type="button"
                                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left transition-colors cursor-pointer"
                                        :class="activeBranch === 'all' ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-700 hover:bg-neutral-50'">
                                    <span>Semua Cabang</span>
                                    <span x-show="activeBranch === 'all'"><svg class="w-3.5 h-3.5 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                                <button @click="changeBranch('senopati'); branchDropdownOpen = false" 
                                        type="button"
                                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left transition-colors cursor-pointer"
                                        :class="activeBranch === 'senopati' ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-700 hover:bg-neutral-50'">
                                    <span>Senopati</span>
                                    <span x-show="activeBranch === 'senopati'"><svg class="w-3.5 h-3.5 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                                <button @click="changeBranch('braga'); branchDropdownOpen = false" 
                                        type="button"
                                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left transition-colors cursor-pointer"
                                        :class="activeBranch === 'braga' ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-700 hover:bg-neutral-50'">
                                    <span>Braga Bandung</span>
                                    <span x-show="activeBranch === 'braga'"><svg class="w-3.5 h-3.5 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                                <button @click="changeBranch('malioboro'); branchDropdownOpen = false" 
                                        type="button"
                                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left transition-colors cursor-pointer"
                                        :class="activeBranch === 'malioboro' ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-700 hover:bg-neutral-50'">
                                    <span>Malioboro</span>
                                    <span x-show="activeBranch === 'malioboro'"><svg class="w-3.5 h-3.5 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                                <button @click="changeBranch('central'); branchDropdownOpen = false" 
                                        type="button"
                                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left transition-colors cursor-pointer"
                                        :class="activeBranch === 'central' ? 'bg-neutral-100 font-semibold text-black' : 'text-neutral-700 hover:bg-neutral-50'">
                                    <span>Central Hub</span>
                                    <span x-show="activeBranch === 'central'"><svg class="w-3.5 h-3.5 text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                @else
                <!-- Locked Branch Indicator for Cashier / Staff (Role-Protected) -->
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-neutral-100/90 text-neutral-800 text-xs font-medium border border-neutral-200/80 cursor-default select-none shadow-2xs"
                     title="Cabang terkunci sesuai profil penugasan outlet akun kasir ini">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="truncate max-w-[150px] font-semibold text-neutral-900">
                        {{ $currentUser?->branch?->name ? explode(' (', explode(' - ', $currentUser->branch->name)[0])[0] : 'Outlet Cabang' }}
                    </span>
                    <svg class="w-3.5 h-3.5 text-neutral-400 shrink-0 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Terkunci pada cabang ini">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                @endif
            </div>

            <!-- Center: Page Name / Nama Page di Tengah (Vercel Style: "Team Overview") -->
            <div class="absolute left-1/2 -translate-x-1/2 hidden sm:flex items-center justify-center font-medium text-xs sm:text-sm text-neutral-900 tracking-tight select-none">
                @if(request()->routeIs('dashboard.*'))
                    Dasbor Eksekutif
                @elseif(request()->routeIs('pos.orders*') || request()->routeIs('sales.*'))
                    Riwayat Order & Penjualan
                @elseif(request()->routeIs('pos.*'))
                    Kasir POS Touchscreen
                @elseif(request()->routeIs('products.*'))
                    Katalog Produk, Resep & Varian
                @elseif(request()->routeIs('vouchers.*'))
                    Kelola Voucher & Kuota Diskon
                @elseif(request()->routeIs('roastery.*'))
                    Roastery Manufacturing & Analytics
                @elseif(request()->routeIs('procurement.*'))
                    Pengadaan Biji Mentah & QC Lab
                @elseif(request()->routeIs('inventory.*'))
                    Inventori Multi-Cabang & Logistik
                @elseif(request()->routeIs('finance.*'))
                    Laporan Keuangan & Laba Rugi
                @else
                    @yield('title', 'KAFEINA ERP')
                @endif
            </div>

            <!-- Right: Exact Buttons from Reference (+ Create New & ⸫ Agent) -->
            <div class="flex items-center gap-5 sm:gap-6 shrink-0">
                <!-- + Create New Dropdown (Role-Based Adaptive Popover) -->
                @php
                    $createMenuItems = [
                        [
                            'title' => 'Produk & Resep',
                            'route' => route('products.index'),
                            'can' => $currentUser?->canAccessModule('products'),
                            'keywords' => 'produk resep varian harga katalog menu modifier bom product recipe',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />',
                        ],
                        [
                            'title' => 'Transaksi POS',
                            'route' => route('pos.index'),
                            'can' => $currentUser?->canAccessModule('pos'),
                            'keywords' => 'kasir pos point of sale terminal barista cashier transaksi',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />',
                        ],
                        [
                            'title' => 'Work Order',
                            'route' => route('roastery.index'),
                            'can' => $currentUser?->canAccessModule('roastery'),
                            'keywords' => 'roastery work orders bom sangrai giesen diedrich produksi',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />',
                        ],
                        [
                            'title' => 'Purchase Order',
                            'route' => route('procurement.index'),
                            'can' => $currentUser?->canAccessModule('procurement'),
                            'keywords' => 'pengadaan procurement mitra petani kopi purchase order po',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />',
                        ],
                        [
                            'title' => 'Transfer MR',
                            'route' => route('inventory.index'),
                            'can' => $currentUser?->canAccessModule('inventory'),
                            'keywords' => 'inventori multi-cabang stok saldo rop safety stock gudang warehouse transfer mr material request',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />',
                        ],
                        [
                            'title' => 'Uji QC Lab',
                            'route' => route('qc-lab.index'),
                            'can' => $currentUser?->canAccessModule('qc-lab'),
                            'keywords' => 'qc lab laboratorium cupping scaa mutu kadar air defect',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />',
                        ],
                        [
                            'title' => 'Surat Jalan (DN)',
                            'route' => route('logistics.index'),
                            'can' => $currentUser?->canAccessModule('logistics') || $currentUser?->canAccessModule('inventory'),
                            'keywords' => 'surat jalan delivery note dn distribusi logistik ekspedisi',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                        ],
                        [
                            'title' => 'Mitra Petani',
                            'route' => route('procurement.index'),
                            'can' => $currentUser?->canAccessModule('procurement'),
                            'keywords' => 'mitra petani supplier pemasok green beans kelompok tani',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
                        ],
                        [
                            'title' => 'Akun Staff',
                            'route' => route('settings.index', ['tab' => 'users']),
                            'can' => $currentUser?->isSuperAdmin(),
                            'keywords' => 'akun staff staf pengguna user karyawan hak akses pengaturan',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />',
                        ],
                    ];

                    $permittedItems = array_values(array_filter($createMenuItems, fn($i) => (bool) $i['can']));
                    $totalPermitted = count($permittedItems);
                    $useTwoColumns = $totalPermitted > 4;
                    $half = $useTwoColumns ? (int) ceil($totalPermitted / 2) : $totalPermitted;
                    $col1Items = array_slice($permittedItems, 0, $half);
                    $col2Items = $useTwoColumns ? array_slice($permittedItems, $half) : [];
                @endphp

                @if($totalPermitted > 0)
                <div class="relative">
                    <button @click="createNewOpen = !createNewOpen"
                            type="button"
                            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-medium text-neutral-800 hover:text-black transition-colors focus:outline-none cursor-pointer py-1">
                        <svg class="w-3.5 h-3.5 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Create New</span>
                    </button>

                    <!-- Exact 2-Column / Adaptive Flyout Menu Card from Reference -->
                    <div x-show="createNewOpen"
                         x-cloak
                         style="display: none;"
                         @click.away="createNewOpen = false"
                         x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100 motion-reduce:transition-none"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute right-0 top-full mt-2 {{ $useTwoColumns ? 'w-[340px] sm:w-[400px]' : 'w-52 sm:w-56' }} bg-white border border-[#eaeaea] rounded-2xl shadow-[0_16px_36px_rgba(0,0,0,0.12)] z-50 origin-top-right overflow-hidden">
                        
                        <div class="{{ $useTwoColumns ? 'grid grid-cols-2 divide-x divide-[#f0f0f0]' : '' }}">
                            <!-- Column 1 -->
                            <div class="p-2 space-y-1">
                                @foreach($col1Items as $item)
                                <a href="{{ $item['route'] }}"
                                   x-show="!sidebarSearch || '{{ $item['keywords'] }}'.includes(sidebarSearch.toLowerCase())"
                                   @click="createNewOpen = false"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-neutral-50 transition-colors text-neutral-800 hover:text-black text-xs sm:text-[13px] font-medium tracking-tight group">
                                    <svg class="w-4 h-4 text-neutral-600 group-hover:text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $item['icon'] !!}
                                    </svg>
                                    <span>{{ $item['title'] }}</span>
                                </a>
                                @endforeach
                            </div>

                            @if($useTwoColumns)
                            <!-- Column 2 -->
                            <div class="p-2 space-y-1">
                                @foreach($col2Items as $item)
                                <a href="{{ $item['route'] }}"
                                   x-show="!sidebarSearch || '{{ $item['keywords'] }}'.includes(sidebarSearch.toLowerCase())"
                                   @click="createNewOpen = false"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-neutral-50 transition-colors text-neutral-800 hover:text-black text-xs sm:text-[13px] font-medium tracking-tight group">
                                    <svg class="w-4 h-4 text-neutral-600 group-hover:text-black shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $item['icon'] !!}
                                    </svg>
                                    <span>{{ $item['title'] }}</span>
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- ⸫ Agent Button -->
                <button @click="agentModalOpen = true"
                        type="button"
                        class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-medium text-neutral-800 hover:text-black transition-colors focus:outline-none cursor-pointer py-1">
                    <!-- Exact Vercel Agent Pyramid Dot Icon (6 dots) -->
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <circle cx="10" cy="4" r="1.5" />
                        <circle cx="6.5" cy="10" r="1.5" />
                        <circle cx="13.5" cy="10" r="1.5" />
                        <circle cx="3" cy="16" r="1.5" />
                        <circle cx="10" cy="16" r="1.5" />
                        <circle cx="17" cy="16" r="1.5" />
                    </svg>
                    <span>Agent</span>
                </button>
            </div>

            <!-- Kafeina AI Agent Modal (Vercel Style) -->
            <div x-show="agentModalOpen"
                 x-cloak
                 style="display: none;"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <div @click.away="agentModalOpen = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                     class="w-full max-w-lg bg-white border border-[#eaeaea] rounded-2xl shadow-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#f0f0f0]">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-neutral-900 text-white flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <circle cx="10" cy="4" r="1.5" />
                                    <circle cx="6.5" cy="10" r="1.5" />
                                    <circle cx="13.5" cy="10" r="1.5" />
                                    <circle cx="3" cy="16" r="1.5" />
                                    <circle cx="10" cy="16" r="1.5" />
                                    <circle cx="17" cy="16" r="1.5" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-neutral-900">Kafeina AI Agent</span>
                            <span class="flex items-center gap-1 text-[11px] font-medium text-emerald-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Online
                            </span>
                        </div>
                        <button @click="agentModalOpen = false" class="text-neutral-400 hover:text-neutral-700 text-lg leading-none cursor-pointer">×</button>
                    </div>

                    <div class="space-y-2.5 text-xs text-neutral-600">
                        <p class="leading-relaxed">
                            Halo! Saya asisten AI Kafeina ERP. Saya memantau data operasional dan analitik cabang secara real-time:
                        </p>
                        <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-100 space-y-2">
                            <div class="font-medium text-neutral-900">Ringkasan Otomatis Hari Ini:</div>
                            <ul class="space-y-1 list-disc list-inside text-neutral-600">
                                <li><strong>Omzet Penjualan:</strong> Rp 38.450.000 (+14.2% vs target)</li>
                                <li><strong>Roasting Yield:</strong> Rata-rata susut sangrai 15.7% (optimal dalam toleransi 14–18%)</li>
                                <li><strong>Safety Stock:</strong> 5 item inventori mencapai ROP kritis</li>
                            </ul>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button @click="agentModalOpen = false" class="px-4 py-2 bg-black hover:bg-neutral-800 text-white text-xs font-medium rounded-lg transition-colors cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT INJECTION -->
        <main class="flex-1 flex flex-col min-h-0 overflow-y-auto bg-[#fafafa]">
            @yield('content')
        </main>
    </div>

    @stack('scripts')

<script>
function kafeinaApp() {
    return {
        // Popovers & Notifications State (Z-[99999] Stacking)
        popoverOpen: false,
        notifOpen: false,
        activeNotifTab: 'all',
        notifPermissionStatus: (typeof window !== 'undefined' && 'Notification' in window) ? Notification.permission : 'unsupported',
        showPushBanner: !localStorage.getItem('kafeina_push_banner_dismissed') && (typeof window !== 'undefined' && 'Notification' in window ? Notification.permission !== 'granted' : false),
        hasUnreadNotif: true,
        unreadCount: 4,
        sopModalOpen: false,
        userAccountModalOpen: {{ (session('open_user_account_modal') || $errors->has('current_password') || $errors->has('new_password') || $errors->has('pin_code') || $errors->has('name') || $errors->has('email')) ? 'true' : 'false' }},

        // Theme Support
        theme: localStorage.getItem('kafeina_theme') || 'light',
        setTheme(t) {
            this.theme = t;
            localStorage.setItem('kafeina_theme', t);
            if (t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        markAllAsRead() {
            this.hasUnreadNotif = false;
            this.unreadCount = 0;
        },

        dismissPushBanner() {
            this.showPushBanner = false;
            localStorage.setItem('kafeina_push_banner_dismissed', '1');
        },

        async enablePushNotifications() {
            if (!('Notification' in window)) {
                alert('Peramban (browser) Anda saat ini tidak mendukung Web Desktop Notification API.');
                this.dismissPushBanner();
                return;
            }

            this.notifPermissionStatus = Notification.permission;

            if (Notification.permission === 'granted') {
                try {
                    new Notification('Kafeina Specialty Coffee ERP', {
                        body: 'Notifikasi desktop aktif! Anda akan menerima update stok kritis dan batch sangrai secara real-time.',
                        icon: '/favicon.ico'
                    });
                } catch (e) {}
                this.dismissPushBanner();
                return;
            }

            if (Notification.permission === 'denied') {
                alert('Izin notifikasi diblokir oleh peramban (browser) Anda.\n\nUntuk mengaktifkannya:\n1. Klik ikon pengaturan situs / gembok di sebelah kiri address bar browser.\n2. Ubah setelan "Notifications" / "Notifikasi" menjadi "Allow" (Izinkan).\n3. Muat ulang halaman ini.');
                return;
            }

            try {
                const permission = await Notification.requestPermission();
                this.notifPermissionStatus = permission;

                if (permission === 'granted') {
                    try {
                        new Notification('Kafeina Specialty Coffee ERP', {
                            body: 'Izin berhasil diberikan! Notifikasi desktop siap menerima pemantauan operasional.',
                            icon: '/favicon.ico'
                        });
                    } catch (e) {}
                    this.dismissPushBanner();
                } else if (permission === 'denied') {
                    alert('Izin notifikasi ditolak. Anda dapat mengaktifkannya kapan saja dengan mengeklik ikon gembok pada address bar.');
                }
            } catch (err) {
                console.error('Notification permission error:', err);
            }
        },
        sidebarOpen: false,
        sidebarCollapsed: JSON.parse(localStorage.getItem('kafeina_sidebar_collapsed') || 'false'),
        sidebarSearch: '',
        liveTime: '',
        isFullscreen: false,
        isOnline: navigator.onLine,
        canSwitchBranch: {{ $currentUser?->canSwitchBranch() ? 'true' : 'false' }},
        activeBranch: {!! $currentUser?->canSwitchBranch() 
            ? (request()->routeIs('pos.*') 
                ? "((['senopati', 'braga', 'malioboro', 'central'].includes((new URLSearchParams(window.location.search)).get('branch')) ? (new URLSearchParams(window.location.search)).get('branch') : 'senopati'))" 
                : "((new URLSearchParams(window.location.search)).get('branch') || 'all')")
            : json_encode($currentUser?->getAssignedBranchSlug() ?? 'senopati') !!},
        branchLabels: {
            'all': 'Semua Cabang',
            'senopati': 'Senopati',
            'braga': 'Braga Bandung',
            'malioboro': 'Malioboro',
            'central': 'Central Hub'
        },
        branchShortLabels: {
            'all': 'Semua Cabang',
            'senopati': 'Senopati',
            'braga': 'Braga Bandung',
            'malioboro': 'Malioboro',
            'central': 'Central Hub'
        },

        init() {
            this.updateTime(); 
            setInterval(() => this.updateTime(), 1000);
            window.addEventListener('online', () => { this.isOnline = true; });
            window.addEventListener('offline', () => { this.isOnline = false; });
            this.setupKeyboardShortcuts();
        },

        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('kafeina_sidebar_collapsed', JSON.stringify(this.sidebarCollapsed));
        },

        changeBranch(val) {
            if (!this.canSwitchBranch) {
                return; // Kasir dilarang mengubah cabang!
            }
            this.activeBranch = val;
            window.dispatchEvent(new CustomEvent('kafeina-branch-changed', { detail: { branch: val } }));
            const url = new URL(window.location.href);
            if (url.searchParams.get('branch') !== val) {
                url.searchParams.set('branch', val);
                window.location.href = url.toString();
            }
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
                this.isFullscreen = true;
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {});
                    this.isFullscreen = false;
                }
            }
        },

        updateTime() {
            const now = new Date();
            this.liveTime = now.toLocaleTimeString('id-ID', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        },

        setupKeyboardShortcuts() {
            window.addEventListener('keydown', (e) => {
                const activeTag = document.activeElement ? document.activeElement.tagName.toUpperCase() : '';
                const isInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeTag) || document.activeElement?.isContentEditable;

                // 1. Escape: Blur search input & close mobile sidebar
                if (e.key === 'Escape') {
                    if (isInput) {
                        document.activeElement.blur();
                    }
                    this.sidebarOpen = false;
                    this.sidebarSearch = '';
                    return;
                }

                // 2. Ctrl/Cmd + 0..9 & Huruf Khusus: Rapid Page Navigation
                if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey) {
                    const routeMap = {
                        '0': "{{ route('dashboard.index') }}",
                        '1': "{{ route('pos.index') }}",
                        '2': "{{ route('sales.index') }}",
                        '3': "{{ route('vouchers.index') }}",
                        '4': "{{ route('products.index') }}",
                        '5': "{{ route('procurement.index') }}",
                        '6': "{{ route('qc-lab.index') }}",
                        '7': "{{ route('roastery.index') }}",
                        '8': "{{ route('yield-loss.index') }}",
                        '9': "{{ route('inventory.index') }}"
                    };

                    if (routeMap[e.key]) {
                        e.preventDefault();
                        window.location.href = routeMap[e.key];
                        return;
                    }

                                        // Ctrl/Cmd + D: Logistik & Distribusi (⌘D)
                    if (e.key.toLowerCase() === 'd' && !isInput) {
                        e.preventDefault();
                        window.location.href = "{{ route('logistics.index') }}";
                        return;
                    }

                    // Ctrl/Cmd + P: Katalog Produk & Resep (⌘P alias ⌘4)
                    if (e.key.toLowerCase() === 'p' && !isInput) {
                        e.preventDefault();
                        window.location.href = "{{ route('products.index') }}";
                        return;
                    }

                    // Ctrl/Cmd + L: Laba Rugi Riil (P&L) (⌘L)
                    if (e.key.toLowerCase() === 'l' && !isInput) {
                        e.preventDefault();
                        window.location.href = "{{ route('finance.index') }}";
                        return;
                    }

                    // Ctrl/Cmd + S: Settings (⌘S)
                    if (e.key.toLowerCase() === 's' && !isInput) {
                        e.preventDefault();
                        window.location.href = "{{ route('settings.index') }}";
                        return;
                    }

                    // Ctrl/Cmd + B: Toggle sidebar
                    if (e.key.toLowerCase() === 'b') {
                        e.preventDefault();
                        this.toggleSidebar();
                        return;
                    }
                }

                // 3. Do NOT intercept single keys if user is typing
                if (isInput) {
                    return;
                }

                // 4. Single Key 'f' or 'F': Focus Left Sidebar Search
                if ((e.key === 'f' || e.key === 'F') && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    e.preventDefault();
                    if (this.sidebarCollapsed) {
                        this.sidebarCollapsed = false;
                        localStorage.setItem('kafeina_sidebar_collapsed', 'false');
                    }
                    this.$nextTick(() => {
                        const sInput = document.getElementById('sidebar-search-input');
                        if (sInput) {
                            sInput.focus();
                            sInput.select();
                        }
                    });
                    return;
                }

                // 5. Single Key '/': Focus Main Dashboard / Page Search
                if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    e.preventDefault();
                    const pageSearch = document.querySelector('[data-page-search], #page-search-input, input[placeholder*="Cari"]:not(#sidebar-search-input)');
                    if (pageSearch) {
                        pageSearch.focus();
                        pageSearch.select();
                    }
                    return;
                }
            });
        }
    };
}
</script>
    <!-- STANDAR OPERASIONAL PROSEDUR (SOP) MODAL (Z-[999999] Topmost) -->
    <div x-show="sopModalOpen" 
         x-cloak
         class="fixed inset-0 z-[999999] overflow-y-auto flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans"
         style="display: none;">
        <div @click.away="sopModalOpen = false" 
             x-show="sopModalOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl border border-neutral-200 shadow-2xl max-w-lg w-full overflow-hidden">
            <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between bg-neutral-50/60">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <h3 class="text-sm font-semibold text-neutral-900">SOP Standar Kafeina Specialty Coffee</h3>
                </div>
                <button @click="sopModalOpen = false" type="button" class="text-neutral-400 hover:text-black cursor-pointer p-1 rounded-md hover:bg-neutral-100 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-5 space-y-4 text-xs text-neutral-700 leading-relaxed max-h-96 overflow-y-auto custom-scrollbar">
                <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200/80 space-y-1">
                    <div class="font-semibold text-amber-900 flex items-center gap-1.5">
                        <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /></svg><span>Toleransi Susut Sangrai (Yield Loss)</span></span>
                    </div>
                    <p class="text-amber-800">Standar susut sangrai optimal KAFEINA adalah <span class="font-mono font-bold">14.0% – 18.0%</span>. Jika susut &gt; 18.5%, batch dinyatakan *overdeveloped/dark defect* dan memerlukan persetujuan Head Roaster.</p>
                </div>
                <div class="p-3 rounded-xl bg-blue-50/70 border border-blue-200/80 space-y-1">
                    <div class="font-semibold text-blue-900 flex items-center gap-1.5">
                        <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg><span>Pengujian Mutu Laboratorium (SCAA Cupping)</span></span>
                    </div>
                    <p class="text-blue-800">Green beans wajib memiliki kadar air <span class="font-mono font-bold">10.0% – 12.0%</span> dan skor cupping SCAA &ge; 80.0 untuk disetujui masuk silo Central Hub.</p>
                </div>
                <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-200 space-y-1">
                    <div class="font-semibold text-neutral-900 flex items-center gap-1.5">
                        <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-neutral-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h2m4 0h4"/></svg><span>Protokol Buka/Tutup Shift Kasir POS</span></span>
                    </div>
                    <p class="text-neutral-600">Kas awal laci Rp 500.000 wajib dihitung fisik sebelum transaksi pertama. Setiap penutupan shift wajib diverifikasi Store Supervisor.</p>
                </div>
            </div>
            <div class="px-5 py-3 border-t border-neutral-100 bg-neutral-50/50 flex justify-end">
                <button @click="sopModalOpen = false" type="button" class="px-4 py-2 bg-black hover:bg-neutral-800 text-white rounded-xl text-xs font-semibold cursor-pointer">
                    Tutup Panduan
                </button>
            </div>
        </div>
    </div>

    <!-- PENGATURAN AKUN PENGGUNA MODAL (Z-[999999] Topmost) -->
    <div x-show="userAccountModalOpen" 
         x-cloak
         class="fixed inset-0 z-[999999] overflow-y-auto flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs font-sans"
         style="display: none;">
        <div @click.away="userAccountModalOpen = false" 
             x-show="userAccountModalOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl border border-neutral-200 shadow-2xl max-w-lg w-full overflow-hidden">
            
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-neutral-100 flex items-center justify-between bg-neutral-50/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-black text-white font-mono text-xs font-bold flex items-center justify-center shrink-0 shadow-2xs">
                        {{ strtoupper(substr($currentUser?->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-900 leading-tight">Pengaturan Akun Pengguna</h3>
                        <p class="text-[11px] text-neutral-500">Kelola profil staf dan kata sandi login Anda.</p>
                    </div>
                </div>
                <button @click="userAccountModalOpen = false" type="button" class="text-neutral-400 hover:text-black cursor-pointer p-1 rounded-md hover:bg-neutral-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Body Form -->
            <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col">
                @csrf

                <div class="p-5 space-y-4 max-h-[62vh] overflow-y-auto custom-scrollbar">
                    <!-- Current Role & Branch Badge Card -->
                    <div class="p-3 rounded-xl bg-neutral-50 border border-neutral-200/80 flex items-center justify-between">
                        <div class="min-w-0">
                            <div class="text-[11px] font-mono text-neutral-400 uppercase tracking-wider font-semibold">Peran & Penugasan</div>
                            <div class="text-xs font-semibold text-neutral-900 truncate mt-0.5">
                                {{ $currentUser?->role?->display_name ?? 'Staf Operasional' }}
                            </div>
                            <div class="text-[11px] text-neutral-500 truncate">
                                {{ $currentUser?->branch?->name ?? 'Sentral Roastery & Lab Hub' }}
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-mono font-semibold shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            AKTIF
                        </span>
                    </div>

                    <!-- Error Alert if any -->
                    @if($errors->any())
                    <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 space-y-1">
                        <div class="font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Periksa kembali data yang dimasukkan:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-red-600 pl-1">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- Section 1: Profil Dasar -->
                    <div class="space-y-3">
                        <div class="text-[11px] font-semibold text-neutral-900 uppercase tracking-wider font-mono">1. Informasi Profil</div>
                        <div>
                            <label class="block text-xs font-medium text-neutral-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ old('name', $currentUser?->name) }}" required
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-neutral-200 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors"
                                   placeholder="Nama lengkap staf">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-neutral-700 mb-1">Alamat Email (Login)</label>
                            <input type="email" name="email" value="{{ old('email', $currentUser?->email) }}" required
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-neutral-200 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors"
                                   placeholder="email@kafeina.id">
                        </div>
                    </div>

                    <!-- Section 2: Ganti Password -->
                    <div class="space-y-3 pt-2 border-t border-neutral-100">
                        <div class="flex items-center justify-between">
                            <div class="text-[11px] font-semibold text-neutral-900 uppercase tracking-wider font-mono">2. Ubah Password Login</div>
                            <span class="text-[10px] font-mono text-neutral-400">Opsional</span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-neutral-700 mb-1">Password Saat Ini</label>
                            <input type="password" name="current_password"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-neutral-200 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors"
                                   placeholder="Wajib diisi jika ingin mengganti password baru">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-neutral-700 mb-1">Password Baru</label>
                                <input type="password" name="new_password" minlength="8"
                                       class="w-full px-3 py-2 text-xs rounded-xl border border-neutral-200 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors"
                                       placeholder="Minimal 8 karakter">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-neutral-700 mb-1">Konfirmasi Password Baru</label>
                                <input type="password" name="new_password_confirmation" minlength="8"
                                       class="w-full px-3 py-2 text-xs rounded-xl border border-neutral-200 focus:outline-none focus:border-black focus:ring-1 focus:ring-black transition-colors"
                                       placeholder="Ulangi password baru">
                            </div>
                        </div>
                    </div>

                    <!-- Super Admin Deep Settings Shortcut -->
                    @if($currentUser?->isSuperAdmin())
                    <div class="p-3 rounded-xl bg-blue-50/70 border border-blue-200/80 flex items-center justify-between text-xs">
                        <div class="text-blue-900">
                            <div class="font-semibold flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Kelola Pengaturan Sistem Lengkap</span>
                            </div>
                            <p class="text-[11px] text-blue-700 mt-0.5">Kelola seluruh staf, cabang, gudang, dan master database.</p>
                        </div>
                        <a href="{{ route('settings.index') }}" class="px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-[11px] shrink-0 transition-colors">
                            Buka (⌘S) →
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Sticky Modal Actions Footer -->
                <div class="px-5 py-3.5 border-t border-neutral-100 bg-neutral-50/70 flex items-center justify-end gap-2 shrink-0">
                    <button @click="userAccountModalOpen = false" type="button" 
                            class="px-4 py-2 border border-neutral-200 bg-white hover:bg-neutral-100 text-neutral-700 text-xs font-medium rounded-xl transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-black hover:bg-neutral-800 text-white text-xs font-medium rounded-xl transition-colors cursor-pointer shadow-2xs">
                        Simpan Perubahan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODERN FLOATING TOAST NOTIFICATION SYSTEM                       -->
    <!-- ============================================================== -->
    <div x-data="iosNotificationEngine()" 
         x-cloak
         style="display: none;"
         class="fixed top-4 sm:top-5 inset-x-0 z-[9999999] flex justify-center pointer-events-none px-4 select-none">
        
        <div x-show="visible"
             x-cloak
             style="display: none;"
             x-transition:enter="transition-all duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]"
             x-transition:enter-start="opacity-0 -translate-y-6 scale-90"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition-all duration-200 ease-in"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-5 scale-95"
             class="pointer-events-auto max-w-sm sm:max-w-md w-full bg-white/95 backdrop-blur-2xl border border-neutral-200/90 text-neutral-900 shadow-[0_20px_50px_rgba(0,0,0,0.12),0_4px_16px_rgba(0,0,0,0.06),0_0_0_1px_rgba(0,0,0,0.04)] rounded-3xl p-3.5 flex items-center gap-3.5 ring-1 ring-black/5">
             
             <!-- Leading Status Icon / Squircle (Light Theme) -->
             <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-xs"
                  :class="{
                      'bg-emerald-50 text-emerald-600 border border-emerald-200/90': current.type === 'success',
                      'bg-rose-50 text-rose-600 border border-rose-200/90': current.type === 'error',
                      'bg-amber-50 text-amber-600 border border-amber-200/90': current.type === 'warning',
                      'bg-orange-50 text-orange-600 border border-orange-200/90': current.type === 'coffee' || current.type === 'po',
                      'bg-sky-50 text-sky-600 border border-sky-200/90': current.type === 'info'
                  }">
                 <!-- Success Checkmark -->
                 <template x-if="current.type === 'success'">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                 </template>
                 <!-- Error Cross -->
                 <template x-if="current.type === 'error'">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                 </template>
                 <!-- Warning / Alert -->
                 <template x-if="current.type === 'warning'">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                 </template>
                 <!-- Coffee / PO Icon -->
                 <template x-if="current.type === 'po' || current.type === 'coffee'">
                     <svg class="w-5 h-5" viewBox="0 0 40 40" fill="none">
                         <rect x="8" y="7" width="5" height="26" rx="2.5" fill="#171717"/>
                         <path d="M16 19.5C16 13 22 8 29 8C29 15 23 20 16 19.5Z" fill="#171717"/>
                         <path d="M16 20.5C21 21 31 23 31 32C24 32 18 28 16 20.5Z" fill="#F97316"/>
                     </svg>
                 </template>
                 <!-- Info Bell -->
                 <template x-if="current.type === 'info'">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                 </template>
             </div>

             <!-- Content (Light Mode Clean: Without KAFEINA OS / SEKARANG) -->
             <div class="flex-1 min-w-0 pr-1">
                 <div class="text-xs font-bold text-neutral-900 tracking-tight truncate" x-text="current.title"></div>
                 <div class="text-[11px] text-neutral-600 leading-snug mt-0.5 line-clamp-2" x-text="current.message"></div>
             </div>

             <!-- Close Action -->
             <button @click="dismiss()" type="button" class="text-neutral-400 hover:text-neutral-900 p-1.5 rounded-full hover:bg-neutral-100 transition-colors shrink-0 cursor-pointer">
                 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
             </button>
        </div>
    </div>

    <script>
    function iosNotificationEngine() {
        return {
            visible: false,
            timeoutId: null,
            current: {
                title: '',
                message: '',
                type: 'info',
            },
            init() {
                // 1. Initial Session flash if present
                @if(session('success'))
                    this.notify('Berhasil!', @json(session('success')), 'success');
                @elseif(session('error'))
                    this.notify('Perhatian', @json(session('error')), 'error');
                @elseif(session('info'))
                    this.notify('Informasi', @json(session('info')), 'info');
                @endif

                // 2. Global Event Listener for iOS 27 notifications
                window.addEventListener('ios-notify', (e) => {
                    const d = e.detail || {};
                    const msg = d.message || d.subtitle || '';
                    this.notify(d.title || 'Pemberitahuan Sistem', msg, d.type || 'info', d.duration || 4500);
                });

                // 3. Register global JS function
                window.showIosNotify = (opts) => {
                    if (!opts) return;
                    const title = typeof opts === 'string' ? 'Pemberitahuan Sistem' : (opts.title || 'Pemberitahuan Sistem');
                    const message = typeof opts === 'string' ? opts : (opts.message || opts.subtitle || '');
                    const type = typeof opts === 'string' ? 'info' : (opts.type || 'info');
                    const duration = typeof opts === 'string' ? 4500 : (opts.duration || 4500);
                    this.notify(title, message, type, duration);
                };

                // 4. Override native window.alert to automatically use iOS 27 notification!
                window.alert = (msg) => {
                    this.notify('Pemberitahuan Sistem', String(msg), 'info', 4500);
                };
            },
            notify(title, message, type = 'info', duration = 4500) {
                clearTimeout(this.timeoutId);
                this.current = { title, message, type };
                this.visible = true;
                if (navigator.vibrate) {
                    navigator.vibrate([25, 35, 25]);
                }
                this.timeoutId = setTimeout(() => {
                    this.dismiss();
                }, duration);
            },
            dismiss() {
                this.visible = false;
            }
        };
    }
    </script>

</body>
</html>
