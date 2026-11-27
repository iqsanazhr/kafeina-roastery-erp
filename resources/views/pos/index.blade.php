@extends('layouts.app')

@section('title', 'Terminal POS Kasir Layar Sentuh - KAFEINA')

@section('content')
<div class="flex-1 flex flex-col lg:flex-row overflow-hidden h-[calc(100vh-3.5rem)] bg-[#fafafa]" x-data="posApp()">

    <!-- LEFT PANEL: Product Catalog & Fast Category Filters (65% width) -->
    <div class="flex-1 flex flex-col min-w-0 bg-white border-r border-[#eaeaea]">
        
        <!-- Header Controls: Search, Quick Category Chips & Active Shift Info -->
        <div class="p-3 sm:p-4 bg-white border-b border-[#eaeaea] flex flex-col gap-3 shrink-0">
            <div class="flex items-center justify-between gap-3">
                <!-- Live Search Bar with / shortcut -->
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" 
                           x-model="searchQuery" 
                           id="page-search-input" data-page-search placeholder="Cari menu, single origin, atau beans... (Tekan /)" @keydown.escape="$el.blur()" 
                           class="w-full pl-9 pr-8 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 border border-neutral-200 text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-neutral-900 focus:bg-white transition-colors font-sans"
                           @keydown.window.prevent.slash="$el.focus()" />
                    <button x-show="searchQuery" 
                            @click="searchQuery = ''" 
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-neutral-400 hover:text-black cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Shift Status & Terminal Controls -->
                <div class="flex items-center gap-2">
                    <!-- Offline Queue Alert Badge -->
                    <div x-show="offlineQueue.length > 0" 
                         class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-neutral-900 text-white text-xs font-mono font-bold">
                        <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                        <span x-text="offlineQueue.length + ' Offline Disimpan'"></span>
                        <button @click="syncOfflineOrders()" class="ml-1 underline text-[11px] hover:text-neutral-300 cursor-pointer">Sync</button>
                    </div>

                    <!-- Riwayat Transaksi Trigger -->
                    <a href="{{ route('sales.index') }}" 
                       class="px-2.5 py-1.5 rounded-lg bg-white border border-neutral-200 hover:border-neutral-900 text-xs font-semibold text-neutral-800 hover:text-black transition-colors cursor-pointer font-sans flex items-center gap-1.5"
                       title="Buka Riwayat Order & Transaksi Pelanggan">
                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span>Riwayat Order</span>
                    </a>

                    <!-- Shift Info Modal Trigger -->
                    <button @click="openShiftModal = true" 
                            class="px-2.5 py-1.5 rounded-lg bg-neutral-100 hover:bg-neutral-200/70 text-xs font-medium text-neutral-700 transition-colors cursor-pointer font-sans"
                            title="Lihat Rincian Jadwal Shift & Rekonsiliasi Otomatis">
                        Detail Shift
                    </button>
                </div>
            </div>

        </div>

        <!-- Unified Vertical Scrollable Product Catalog -->
        <div class="flex-1 overflow-y-auto overscroll-y-contain p-3.5 sm:p-5 space-y-8 bg-neutral-50/40 scroll-smooth">
            
            <!-- Category Sections -->
            <template x-for="category in categories" :key="category.id">
                <section x-show="getCategoryProducts(category.id).length > 0"
                         :id="'section-' + category.id"
                         class="space-y-3.5">
                    
                    <!-- Static Category Heading Bar (No Sticky) -->
                    <div class="flex items-center justify-between pb-2.5 border-b border-neutral-200/80 select-none">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="getCategoryDotClass(category.id)"></span>
                            <h2 class="text-sm sm:text-base font-bold text-neutral-900 tracking-tight font-sans" x-text="category.name"></h2>
                        </div>
                    </div>

                    <!-- Responsive Category Product Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 sm:gap-3.5">
                        <template x-for="product in getCategoryProducts(category.id)" :key="product.id">
                            <div @click="openProductDetail(product)"
                                 class="group relative bg-white hover:bg-neutral-50/80 active:scale-[0.98] border border-[#eaeaea] hover:border-neutral-300 rounded-2xl p-2.5 sm:p-3 flex flex-col justify-between cursor-pointer select-none transition-transform transition-colors duration-150 shadow-2xs hover:shadow-xs min-h-[220px]">
                                
                                <!-- Visual Studio Image Box (Edge-to-Edge Rounded Frame) -->
                                <div class="relative w-full aspect-square rounded-xl bg-neutral-50/50 border border-neutral-100 mb-2.5 overflow-hidden">
                                    <!-- Stock Indicator (Clean Minimalist Text, No Badge) -->
                                    <div class="absolute top-2 right-2.5 z-10">
                                        <span class="text-[11px] font-mono font-semibold tabular-nums select-none"
                                              :class="product.stock === 0 ? 'text-rose-500' : (product.stock <= 10 ? 'text-amber-600' : 'text-neutral-500')"
                                              x-text="product.stock > 0 ? 'Stok ' + product.stock : 'Habis'"></span>
                                    </div>

                                    <!-- Studio Product Image with Fallback (Full Frame Cover) -->
                                    <img :src="product.image || ('/images/pos/' + product.id.toLowerCase() + '.png')"
                                         :alt="product.name"
                                         loading="lazy"
                                         class="w-full h-full object-cover block"
                                         x-on:error.once="$el.src = '/images/pos/placeholder.svg'"
                                         onerror="this.onerror=null;this.src='/images/pos/placeholder.svg';" />
                                </div>

                                <!-- Product Name & Subtitle -->
                                <div class="mb-2 px-0.5">
                                    <h3 class="font-semibold text-xs sm:text-[13px] text-neutral-900 leading-snug group-hover:text-black line-clamp-2"
                                        x-text="product.name"></h3>
                                    <p class="text-[10px] sm:text-[11px] text-neutral-400 mt-0.5 line-clamp-1 font-sans"
                                       x-text="product.image_tag"></p>
                                </div>

                                <!-- Price & Quick Add (+) Action -->
                                <div class="pt-2 border-t border-neutral-100 flex items-center justify-between px-0.5 mt-auto">
                                    <div class="tabular-nums">
                                        <span class="text-[10px] text-neutral-400 font-normal">Rp</span>
                                        <span class="text-xs sm:text-sm font-bold text-neutral-900 tracking-tight"
                                              x-text="formatNumber(product.price)"></span>
                                    </div>

                                    <!-- Kustomisasi & Tambah (+) Button: Opens Product Customizer Popup -->
                                    <button type="button"
                                            @click.stop="openProductDetail(product)"
                                            title="Kustomisasi & Tambah Pesanan"
                                            class="w-8 h-8 rounded-lg bg-neutral-100 group-hover:bg-neutral-900 group-hover:text-white text-neutral-700 flex items-center justify-center transition-colors shadow-2xs shrink-0 cursor-pointer active:scale-95">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </template>

            <!-- Empty Search State -->
            <div x-show="totalFilteredCount === 0" class="py-20 text-center text-neutral-500">
                <div class="w-12 h-12 rounded-2xl bg-neutral-100 flex items-center justify-center mx-auto mb-3 text-neutral-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-neutral-800">Tidak ada produk ditemukan</p>
                <p class="text-xs text-neutral-400 mt-1">Kata kunci "<span x-text="searchQuery" class="font-mono text-neutral-600"></span>" tidak cocok dengan menu manapun.</p>
                <button @click="searchQuery = ''" class="mt-3 px-3 py-1.5 rounded-lg bg-neutral-900 text-white text-xs font-medium cursor-pointer shadow-2xs hover:bg-neutral-800 transition-colors">
                    Reset Pencarian
                </button>
            </div>
        </div>
    </div>

    <!-- DRAGGABLE SPLITTER / RESIZE HANDLE (Left/Right Drag to Expand/Shrink Cart) -->
    <div @pointerdown="startCartResize($event)"
         @dblclick="resetCartWidth()"
         :class="{ 'bg-neutral-900 ring-2 ring-neutral-400': isResizingCart, 'hover:bg-neutral-200': !isResizingCart }"
         class="hidden lg:flex items-center justify-center w-2 -mx-1 z-30 cursor-col-resize select-none shrink-0 transition-colors group relative border-x border-transparent hover:border-neutral-300"
         title="Geser ke kiri/kanan untuk perlebar/persempit jendela pesanan (Klik ganda untuk reset 400px)">
        <!-- Visual Gripper Handle Pill -->
        <div class="w-1 h-8 rounded-full bg-neutral-300 group-hover:bg-neutral-700 transition-colors"
             :class="isResizingCart ? 'bg-white h-12' : ''"></div>
        
        <!-- Live Pixel Width Indicator during drag -->
        <div x-show="isResizingCart" x-cloak
             class="absolute top-4 right-3 px-2 py-0.5 rounded bg-neutral-900 text-white text-[10px] font-mono shadow-md whitespace-nowrap pointer-events-none">
            <span x-text="cartWidth + 'px'"></span>
        </div>
    </div>

    <!-- RIGHT PANEL: Real-time Order Cart, Touch Stepper, PB1 10% & Checkout (Resizable) -->
    <div id="pos-order-cart-pane"
         :style="'width: ' + (window.innerWidth >= 1024 ? cartWidth + 'px' : '100%')"
         :class="{ 'transition-[width] duration-150': !isResizingCart }"
         class="w-full lg:w-[400px] bg-white border-l border-[#eaeaea] flex flex-col shrink-0 relative">
        
        <!-- Cart Header: Order Type (Dine In / Take Away) & Table Selector -->
        <div class="p-3 sm:p-3.5 bg-white border-b border-[#eaeaea] flex flex-col gap-2.5 shrink-0">
            <!-- Title & Quick Actions Bar -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-neutral-900 text-white flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-xs sm:text-sm font-semibold text-neutral-900 leading-none font-sans">Pesanan Aktif</h2>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <!-- Reset / Clear Cart Button (visible when cart has items) -->
                    <button x-show="cart.length > 0"
                            x-cloak
                            @click="cart = []; customerName = ''; tableNumber = ''; validationError = ''"
                            title="Kosongkan Keranjang"
                            class="px-2 py-1 rounded-md text-neutral-400 hover:text-rose-600 hover:bg-rose-50 text-[11px] font-medium transition-colors cursor-pointer flex items-center gap-1 font-sans">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Reset</span>
                    </button>

                    <!-- Non-Revenue Order Trigger -->
                    <button @click="openNonRevenueModal = true"
                            title="Cupping QC / Kalibrasi Barista / Staff Allowance"
                            class="px-2 py-1 rounded-md bg-neutral-100 hover:bg-neutral-200/80 border border-neutral-200/80 text-[11px] font-medium text-neutral-700 transition-colors cursor-pointer font-sans">
                        <span>Non-Rev</span>
                    </button>
                </div>
            </div>

            <!-- Order Mode Segmented Control (Apple Minimalist Pill: Dine In vs Take Away) -->
            <div class="grid grid-cols-2 gap-1 p-0.5 bg-neutral-100 border border-neutral-200/70 rounded-lg text-xs font-sans">
                <button type="button"
                        @click="orderType = 'Dine In'"
                        :class="orderType === 'Dine In' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                        class="py-1.5 rounded-md transition-all text-center cursor-pointer text-xs flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Dine In</span>
                </button>
                <button type="button"
                        @click="orderType = 'Take Away'"
                        :class="orderType === 'Take Away' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                        class="py-1.5 rounded-md transition-all text-center cursor-pointer text-xs flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Take Away</span>
                </button>
            </div>

            <!-- Dine In Input Fields: Atas Nama Pelanggan & Nomor Meja -->
            <div x-show="orderType === 'Dine In'" 
                 x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="grid grid-cols-12 gap-1.5 font-sans">
                <!-- Input Atas Nama Pelanggan -->
                <div class="col-span-7 relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none"
                         :class="validationError && !customerName.trim() ? 'text-rose-500' : 'text-neutral-400'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <input type="text"
                           x-ref="customerNameDineIn"
                           x-model="customerName"
                           @input="validationError = ''"
                           placeholder="Atas Nama (Wajib)..."
                           :class="validationError && !customerName.trim() ? 'border-rose-400 ring-2 ring-rose-200 bg-rose-50/40 text-rose-900 placeholder:text-rose-400' : 'border-neutral-200 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 text-neutral-900 placeholder:text-neutral-400'"
                           class="w-full pl-8 pr-6 py-1.5 border rounded-lg text-xs font-sans transition-all outline-none" />
                    <button type="button"
                            x-show="customerName"
                            @click="customerName = ''; validationError = ''"
                            title="Hapus nama pelanggan"
                            class="absolute inset-y-0 right-0 pr-1.5 flex items-center text-neutral-400 hover:text-neutral-700 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Input Nomor Meja -->
                <div class="col-span-5 relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none"
                         :class="validationError && !tableNumber.trim() ? 'text-rose-500' : 'text-neutral-400'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <input type="text"
                           x-ref="tableNumberDineIn"
                           x-model="tableNumber"
                           @input="validationError = ''"
                           placeholder="No. Meja (Wajib)..."
                           :class="validationError && !tableNumber.trim() ? 'border-rose-400 ring-2 ring-rose-200 bg-rose-50/40 text-rose-900 placeholder:text-rose-400' : 'border-neutral-200 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 text-neutral-900 placeholder:text-neutral-400'"
                           class="w-full pl-8 pr-6 py-1.5 border rounded-lg text-xs font-sans transition-all outline-none" />
                    <button type="button"
                            x-show="tableNumber"
                            @click="tableNumber = ''; validationError = ''"
                            title="Hapus nomor meja"
                            class="absolute inset-y-0 right-0 pr-1.5 flex items-center text-neutral-400 hover:text-neutral-700 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Take Away Input Field: Atas Nama Pelanggan (Full Width) -->
            <div x-show="orderType === 'Take Away'" 
                 x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="relative flex items-center font-sans">
                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none"
                     :class="validationError && !customerName.trim() ? 'text-rose-500' : 'text-neutral-400'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <input type="text"
                       x-ref="customerNameTakeAway"
                       x-model="customerName"
                       @input="validationError = ''"
                       placeholder="Atas Nama Pelanggan (Wajib diisi)..."
                       :class="validationError && !customerName.trim() ? 'border-rose-400 ring-2 ring-rose-200 bg-rose-50/40 text-rose-900 placeholder:text-rose-400' : 'border-neutral-200 bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 text-neutral-900 placeholder:text-neutral-400'"
                       class="w-full pl-8 pr-7 py-1.5 border rounded-lg text-xs font-sans transition-all outline-none" />
                <button type="button"
                        x-show="customerName"
                        @click="customerName = ''; validationError = ''"
                        title="Hapus nama pelanggan"
                        class="absolute inset-y-0 right-0 pr-2 flex items-center text-neutral-400 hover:text-neutral-700 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Pesan Peringatan Validasi Wajib Isi -->
            <div x-show="validationError" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-1.5 px-2.5 py-1.5 bg-rose-50 border border-rose-200 rounded-lg flex items-center justify-between text-xs text-rose-700 font-sans shadow-2xs">
                <div class="flex items-center gap-1.5 min-w-0">
                    <svg class="w-3.5 h-3.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span x-text="validationError" class="font-medium truncate"></span>
                </div>
                <button type="button" @click="validationError = ''" class="text-rose-400 hover:text-rose-700 ml-1 cursor-pointer shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Cart Item List -->
        <div class="flex-1 overflow-y-auto p-3 divide-y divide-neutral-100">
            <template x-for="(item, index) in cart" :key="item.id + '-' + index">
                <div class="py-2.5 first:pt-0.5 last:pb-0.5 flex items-start gap-2.5 group">
                    <!-- Thumbnail Preview -->
                    <div class="w-10 h-10 rounded-lg bg-neutral-50 border border-neutral-200/80 p-0.5 shrink-0 overflow-hidden flex items-center justify-center">
                        <img :src="getItemImage(item)" :alt="item.name" class="w-full h-full object-contain" />
                    </div>

                    <!-- Item Details & Wrap -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-1.5">
                            <h4 class="text-xs font-semibold text-neutral-900 line-clamp-2 leading-snug font-sans" x-text="item.name"></h4>
                            <span class="text-xs font-bold text-neutral-900 font-mono shrink-0 tabular-nums text-right ml-1" x-text="'Rp ' + formatNumber(item.price * item.qty)"></span>
                        </div>
                        
                        <!-- Modifiers, Custom Shots, Extras & Price -->
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <span x-show="item.temp" class="text-[10px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-700 font-medium font-sans" x-text="item.temp"></span>
                            
                            <!-- Custom Extra Shots Badge -->
                            <template x-if="item.extraShots > 0">
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 font-mono font-medium" 
                                      x-text="'+' + item.extraShots + ' Shot (+Rp ' + formatNumber(item.extraShotsTotal || (item.extraShots * 6000)) + ')'"></span>
                            </template>

                            <!-- Selected Extras Badges -->
                            <template x-for="extra in (item.selectedExtras || [])" :key="extra.id">
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-800 border border-neutral-200/70 font-sans"
                                      x-text="extra.name + ' (+Rp ' + formatNumber(extra.price) + ')'"></span>
                            </template>

                            <!-- Sugar Level (if customized) -->
                            <span x-show="item.sweetness && item.sweetness !== 'Normal (100%)'" class="text-[10px] px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-600 font-sans" x-text="item.sweetness"></span>

                            <!-- Barista Notes -->
                            <span x-show="item.notes && (!item.selectedExtras || item.selectedExtras.length === 0)" class="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200/60 font-sans font-medium" x-text="item.notes"></span>

                            <!-- Unit Price with Extras -->
                            <span class="text-[10px] text-neutral-400 font-mono" 
                                  x-text="item.price !== item.basePrice ? ('@ Rp ' + formatNumber(item.price) + ' (Dasar: ' + formatNumber(item.basePrice) + ')') : ('@ Rp ' + formatNumber(item.price))"></span>
                        </div>

                        <!-- Stepper Capsule & Actions -->
                        <div class="flex items-center justify-between mt-2 pt-0.5">
                            <div class="flex items-center bg-neutral-50 border border-neutral-200/80 rounded-lg p-0.5 shadow-2xs">
                                <button type="button"
                                        @click="updateQty(index, -1)" 
                                        class="w-6 h-6 rounded-md bg-white hover:bg-neutral-100 active:scale-95 text-neutral-700 flex items-center justify-center font-bold text-xs cursor-pointer shadow-2xs transition-all">
                                    <template x-if="item.qty > 1">
                                        <span>-</span>
                                    </template>
                                    <template x-if="item.qty === 1">
                                        <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </template>
                                </button>
                                <span class="w-6 text-center text-xs font-semibold text-neutral-900 font-mono tabular-nums select-none" x-text="item.qty"></span>
                                <button type="button"
                                        @click="updateQty(index, 1)" 
                                        class="w-6 h-6 rounded-md bg-white hover:bg-neutral-100 active:scale-95 text-neutral-700 flex items-center justify-center font-bold text-xs cursor-pointer shadow-2xs transition-all">
                                    +
                                </button>
                            </div>

                            <button @click="removeItem(index)" 
                                    title="Hapus Item"
                                    class="w-6 h-6 rounded-md hover:bg-rose-50 text-neutral-300 hover:text-rose-600 flex items-center justify-center transition-colors cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty Cart Placeholder -->
            <div x-show="cart.length === 0" class="h-48 flex flex-col items-center justify-center text-center text-neutral-400">
                <div class="w-10 h-10 rounded-full bg-neutral-100 flex items-center justify-center text-neutral-400 mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <p class="text-xs font-semibold text-neutral-700 font-sans">Keranjang Kosong</p>
                <p class="text-[11px] text-neutral-400 mt-0.5 font-sans">Pilih produk pada katalog menu di sebelah kiri.</p>
            </div>
        </div>

        <!-- Calculations & Tax Summary -->
        <div class="p-3 sm:p-4 bg-neutral-50/70 border-t border-[#eaeaea] flex flex-col gap-2 shrink-0">
            
            <!-- Redeem Code Voucher Bar -->
            <div class="space-y-1.5 font-sans">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-neutral-600 font-medium flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        <span>Kode Promo / Voucher</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="openPromoModal = true" class="text-[11px] text-neutral-500 hover:text-neutral-900 underline cursor-pointer font-medium">
                            Katalog Cepat
                        </button>
                        @if(auth()->user()?->canAccessModule('vouchers'))
                        <span class="text-neutral-300">•</span>
                        <a href="{{ route('vouchers.index') }}" target="_blank" class="text-[11px] text-neutral-900 hover:text-black font-semibold underline flex items-center gap-0.5 cursor-pointer" title="Buka Halaman Lengkap Voucher & Kuota">
                            <span>Kelola Halaman Voucher ↗</span>
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Input row when no code applied -->
                <div x-show="!appliedPromo" class="flex gap-1.5">
                    <input type="text"
                           x-model="promoCodeInput"
                           @keydown.enter.prevent="applyRedeemCode()"
                           placeholder="Ketik kode (cth: KAFEINA10)..."
                           class="flex-1 px-2.5 py-1.5 rounded-lg bg-white border border-neutral-200 text-xs font-mono uppercase tracking-wider text-neutral-900 placeholder:normal-case placeholder:font-sans placeholder:text-neutral-400 focus:outline-none focus:border-neutral-900 transition-colors" />
                    <button type="button"
                            @click="applyRedeemCode()"
                            :disabled="!promoCodeInput.trim() || subtotal <= 0"
                            class="px-3 py-1.5 rounded-lg bg-neutral-900 hover:bg-neutral-800 disabled:opacity-40 disabled:pointer-events-none text-white text-xs font-medium cursor-pointer transition-colors shadow-2xs">
                        Klaim
                    </button>
                </div>

                <!-- Applied Promo Tag Pill -->
                <div x-show="appliedPromo" x-cloak class="flex items-center justify-between p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-xs">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                        <div class="truncate">
                            <span class="font-mono font-bold text-emerald-900 tracking-wider" x-text="appliedPromo?.code"></span>
                            <span class="text-[11px] text-emerald-700 ml-1 font-mono" x-text="'(- Rp ' + formatNumber(discountAmount) + ')'"></span>
                        </div>
                    </div>
                    <button type="button" @click="removeRedeemCode()" class="text-emerald-700 hover:text-rose-600 p-0.5 ml-2 cursor-pointer transition-colors" title="Hapus Promo">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Error message -->
                <p x-show="promoError" x-cloak class="text-[11px] text-rose-600 font-sans" x-text="promoError"></p>
            </div>

            <!-- Financial Line Items -->
            <div class="space-y-1 text-xs pt-1 border-t border-neutral-200/60 font-sans tabular-nums">
                <div class="flex justify-between text-neutral-500">
                    <span>Subtotal</span>
                    <span class="font-medium text-neutral-800" x-text="'Rp ' + formatNumber(subtotal)"></span>
                </div>
                <div class="flex justify-between text-emerald-700 font-medium" x-show="discountAmount > 0">
                    <span x-text="'Potongan Promo (' + (appliedPromo?.code || 'Diskon') + ')'"></span>
                    <span class="font-mono" x-text="'- Rp ' + formatNumber(discountAmount)"></span>
                </div>
                <div class="flex justify-between text-neutral-500" x-show="serviceChargeRate > 0">
                    <span x-text="'Service Charge (' + serviceChargeRate + '%)'"></span>
                    <span class="font-medium text-neutral-800" x-text="'Rp ' + formatNumber(serviceCharge)"></span>
                </div>
                <div class="flex justify-between text-neutral-500">
                    <span x-text="'Pajak Restoran (PB1 ' + taxPb1Rate + '%)'"></span>
                    <span class="font-medium text-neutral-800" x-text="'Rp ' + formatNumber(taxPb1)"></span>
                </div>
            </div>

            <!-- Grand Total Display -->
            <div class="pt-2 border-t border-neutral-200 flex items-baseline justify-between font-sans">
                <div>
                    <span class="text-xs text-neutral-600 uppercase tracking-wider font-semibold">Total Tagihan</span>
                    <p class="text-[10px] text-neutral-400" x-text="'Termasuk PB1 ' + taxPb1Rate + '%' + (serviceChargeRate > 0 ? (' + Service ' + serviceChargeRate + '%') : '')"></p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-neutral-400 font-normal">Rp</span>
                    <span class="text-2xl font-bold text-neutral-900 font-mono tracking-tight" x-text="formatNumber(grandTotal)"></span>
                </div>
            </div>

            <!-- Split Bill & Checkout Buttons -->
            <div class="grid grid-cols-4 gap-2 pt-1 font-sans">
                <button type="button" 
                        @click="openSplitBill()" 
                        :disabled="cart.length === 0"
                        class="col-span-1 min-h-[48px] rounded-xl bg-white hover:bg-neutral-100 disabled:opacity-40 disabled:pointer-events-none border border-neutral-200 text-xs font-semibold text-neutral-700 flex flex-col items-center justify-center gap-0.5 transition-colors cursor-pointer py-2 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span class="text-[10px]">Split Bill</span>
                </button>

                <button type="button" 
                        @click="openCheckoutModal()" 
                        :disabled="cart.length === 0"
                        class="col-span-3 min-h-[48px] rounded-xl bg-neutral-900 hover:bg-neutral-800 disabled:opacity-40 disabled:pointer-events-none text-white font-semibold text-xs sm:text-sm flex items-center justify-between px-4 shadow-xs transition-colors duration-150 active:scale-[0.99] cursor-pointer">
                    <span>Bayar Sekarang (F4)</span>
                    <span class="text-xs bg-neutral-800 border border-neutral-700/60 px-2 py-1 rounded-lg font-mono font-bold tabular-nums">
                        Rp <span x-text="formatNumber(grandTotal)"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 0: Product Detail Pop-Up (Specialty Spec, Temperature, Custom Shot & Extra Customizer) -->
    <div x-show="openProductDetailModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="openProductDetailModal = false">
        
        <div x-show="openProductDetailModal"
             x-transition:enter="transition-all duration-200 ease-out"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition-all duration-150 ease-in"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-xl bg-white border border-neutral-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
             @click.away="openProductDetailModal = false">
            
            <!-- Modal Header -->
            <div class="px-5 py-3.5 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="selectedProduct ? getCategoryDotClass(selectedProduct.category) : 'bg-neutral-400'"></span>
                    <span class="text-xs font-semibold text-neutral-600 uppercase tracking-wider font-mono" x-text="selectedProduct ? formatCategoryName(selectedProduct.category) : ''"></span>
                    <span class="text-neutral-300">•</span>
                    <span class="text-xs font-mono text-neutral-400" x-text="selectedProduct ? selectedProduct.id : ''"></span>
                </div>
                <button type="button" @click="openProductDetailModal = false" class="text-neutral-400 hover:text-neutral-900 cursor-pointer p-1 rounded-md hover:bg-neutral-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-5 overflow-y-auto space-y-4 text-neutral-800" x-show="selectedProduct">
                <!-- Product Hero (Image + Description + Base Price) -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 pb-3 border-b border-neutral-100">
                    <!-- Left: Studio Image (Edge-to-Edge Rounded Frame) -->
                    <div class="sm:col-span-5 flex flex-col">
                        <div class="relative w-full aspect-square rounded-xl bg-neutral-50/50 border border-neutral-100 overflow-hidden shadow-xs">
                            <img :src="selectedProduct?.image || ('/images/pos/' + (selectedProduct?.id ? selectedProduct.id.toLowerCase() : 'prd-esp-01') + '.png')"
                                 :alt="selectedProduct?.name"
                                 class="w-full h-full object-cover block"
                                 x-on:error.once="$el.src = '/images/pos/placeholder.svg'"
                                 onerror="this.onerror=null;this.src='/images/pos/placeholder.svg';" />
                            <div class="absolute top-2 right-2.5 z-10">
                                <span class="text-[11px] font-mono font-semibold tabular-nums select-none"
                                      :class="selectedProduct?.stock === 0 ? 'text-rose-500' : (selectedProduct?.stock <= 10 ? 'text-amber-600' : 'text-neutral-500')"
                                      x-text="selectedProduct?.stock > 0 ? 'Stok ' + selectedProduct.stock : 'Habis'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Product Info & Pricing -->
                    <div class="sm:col-span-7 flex flex-col justify-between">
                        <div>
                            <h2 class="text-base sm:text-lg font-bold text-neutral-900 leading-snug font-sans" x-text="selectedProduct?.name"></h2>
                            <p class="text-xs text-neutral-500 mt-1 font-sans leading-relaxed" x-text="selectedProduct?.image_tag"></p>
                            
                            <!-- Dynamic Real-time Unit Price Display & Live Calculation -->
                            <div class="mt-2.5 p-2.5 rounded-xl bg-neutral-50 border border-neutral-200/80 space-y-1.5">
                                <div class="flex items-baseline justify-between">
                                    <span class="text-xs font-semibold text-neutral-600 font-sans">Harga Satuan (Real-time):</span>
                                    <div class="text-base sm:text-lg font-bold text-neutral-950 font-mono tabular-nums">
                                        <span class="text-xs text-neutral-400 font-normal">Rp</span>
                                        <span x-text="formatNumber(detailUnitPrice)"></span>
                                        <span class="text-[10px] text-neutral-400 font-normal font-sans">/ porsi</span>
                                    </div>
                                </div>
                                <!-- Visual breakdown formula tag -->
                                <div class="text-[11px] text-neutral-500 font-mono flex items-center gap-1.5 flex-wrap pt-1 border-t border-neutral-200/60">
                                    <span class="text-neutral-700">Rp <span x-text="selectedProduct ? formatNumber(selectedProduct.price) : '0'"></span> (Dasar)</span>
                                    <template x-if="detailExtraShotsTotal > 0">
                                        <span class="text-amber-800 font-semibold" x-text="'+ Rp ' + formatNumber(detailExtraShotsTotal) + ' (' + detailExtraShots + ' Shot)'"></span>
                                    </template>
                                    <template x-if="detailExtrasTotal > 0">
                                        <span class="text-emerald-700 font-semibold" x-text="'+ Rp ' + formatNumber(detailExtrasTotal) + ' (' + selectedExtras.length + ' Ekstra)'"></span>
                                    </template>
                                </div>
                            </div>

                            <!-- Recipe / Specification Callout -->
                            <div x-show="selectedProduct?.recipe_yield" class="mt-2.5 p-2 rounded-lg bg-neutral-50 border border-neutral-200/80 text-[11px] text-neutral-600 font-sans space-y-0.5">
                                <div class="text-[9px] font-mono uppercase tracking-wider text-neutral-400 font-semibold">Resep & Profil Seduh:</div>
                                <div class="font-mono text-neutral-700 leading-relaxed text-[10.5px]" x-text="selectedProduct?.recipe_yield"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1. Temperature Selection (Hot / Iced / Penyajian) -->
                <div x-show="selectedProduct?.temperature && selectedProduct.temperature.length > 0" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-neutral-800 font-sans">
                            <span x-text="selectedProduct?.category === 'food' ? 'Penyajian Makanan:' : (selectedProduct?.category === 'retail' ? 'Pilihan Gilingan Biji Kopi:' : 'Suhu Penyajian:')"></span>
                        </label>
                        <span class="text-[10px] text-neutral-400 font-mono" x-show="selectedProduct?.temperature && selectedProduct.temperature.length > 1">Pilih salah satu</span>
                    </div>

                    <!-- Multiple Options (e.g. Hot / Iced) -->
                    <template x-if="selectedProduct?.temperature && selectedProduct.temperature.length > 1">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <template x-for="t in selectedProduct.temperature" :key="t">
                                <button type="button" 
                                        @click="detailTemp = t"
                                        :class="detailTemp === t ? 'bg-neutral-900 text-white font-semibold shadow-xs border-neutral-900' : 'bg-neutral-50 hover:bg-neutral-100 text-neutral-700 border-neutral-200 hover:border-neutral-300'"
                                        class="py-2 px-3 rounded-xl text-xs font-sans border transition-all text-center cursor-pointer flex items-center justify-center gap-1.5">
                                    <span x-show="t.toLowerCase().includes('hot')"><svg class="w-3.5 h-3.5 shrink-0" :class="detailTemp === t ? 'text-white' : 'text-neutral-800'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg></span>
                                    <span x-show="t.toLowerCase().includes('ice')"><svg class="w-3.5 h-3.5 shrink-0" :class="detailTemp === t ? 'text-white' : 'text-neutral-800'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20m10-10H2m15.07-7.07l-14.14 14.14m0-14.14l14.14 14.14" /></svg></span>
                                    <span x-show="t.toLowerCase().includes('warm')"><svg class="w-3.5 h-3.5 shrink-0" :class="detailTemp === t ? 'text-white' : 'text-neutral-800'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /></svg></span>
                                    <span x-show="t.toLowerCase().includes('grind') || t.toLowerCase().includes('bean')"><svg class="w-3.5 h-3.5 shrink-0" :class="detailTemp === t ? 'text-white' : 'text-neutral-800'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg></span>
                                    <span x-text="t"></span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <!-- Single Option Indicator (Hot Only / Iced Only - Ultra Clean Neutral) -->
                    <template x-if="selectedProduct?.temperature && selectedProduct.temperature.length === 1">
                        <div class="p-2.5 rounded-xl bg-neutral-50/80 border border-neutral-200/80 flex items-center justify-between text-xs font-medium text-neutral-800">
                            <div class="flex items-center gap-2">
                                <span x-show="selectedProduct.temperature[0].toLowerCase().includes('hot')">
                                    <svg class="w-4 h-4 text-neutral-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </span>
                                <span x-show="selectedProduct.temperature[0].toLowerCase().includes('ice')">
                                    <svg class="w-4 h-4 text-neutral-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20m10-10H2m15.07-7.07l-14.14 14.14m0-14.14l14.14 14.14" /></svg>
                                </span>
                                <span x-show="!selectedProduct.temperature[0].toLowerCase().includes('hot') && !selectedProduct.temperature[0].toLowerCase().includes('ice')">
                                    <svg class="w-4 h-4 text-neutral-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /></svg>
                                </span>
                                <span class="text-neutral-600">Penyajian Baku:</span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-md bg-white border border-neutral-200/90 font-semibold text-neutral-900 text-xs shadow-2xs" x-text="selectedProduct.temperature[0]"></span>
                        </div>
                    </template>
                </div>

                <!-- 2. Custom Espresso Shot (Vercel-Style Neutral Monochrome) -->
                <div x-show="isCoffeeProduct(selectedProduct)" class="p-3.5 rounded-2xl bg-neutral-50/70 border border-neutral-200/90 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-neutral-900 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <h4 class="text-xs font-semibold text-neutral-900 font-sans">Custom Espresso Shot</h4>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-white border border-neutral-200 text-neutral-700 font-mono font-medium shadow-2xs">+Rp 6.000 / Extra Shot</span>
                            </div>
                            <p class="text-[11px] text-neutral-500 font-sans mt-0.5">
                                Resep bawaan: <strong class="text-neutral-800" x-text="(selectedProduct?.base_shots || 1) + ' Shot ' + (selectedProduct?.base_shots === 2 ? 'Double' : 'Standard')"></strong>
                            </p>
                        </div>

                        <!-- Stepper Counter for Extra Shot -->
                        <div class="flex items-center bg-white border border-neutral-200 rounded-xl p-0.5 shadow-2xs">
                            <button type="button"
                                    @click="decrementExtraShots()"
                                    :disabled="detailExtraShots <= 0"
                                    class="w-7 h-7 rounded-lg text-neutral-700 hover:bg-neutral-100 disabled:opacity-30 disabled:pointer-events-none flex items-center justify-center font-bold text-xs cursor-pointer transition-colors">
                                -
                            </button>
                            <span class="w-16 text-center text-xs font-mono font-bold text-neutral-900 tabular-nums select-none" 
                                  x-text="detailExtraShots > 0 ? ('+' + detailExtraShots + ' Shot') : 'Standard'"></span>
                            <button type="button"
                                    @click="incrementExtraShots()"
                                    :disabled="detailExtraShots >= 5"
                                    class="w-7 h-7 rounded-lg text-neutral-700 hover:bg-neutral-100 disabled:opacity-30 disabled:pointer-events-none flex items-center justify-center font-bold text-xs cursor-pointer transition-colors">
                                +
                            </button>
                        </div>
                    </div>

                    <!-- Quick Preset Pills for Extra Shot (Clean Neutral Segmented) -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button type="button" 
                                @click="setExtraShots(0)"
                                :class="detailExtraShots === 0 ? 'bg-neutral-900 text-white font-medium shadow-2xs border-neutral-900' : 'bg-white text-neutral-700 border border-neutral-200 hover:bg-neutral-100/70'"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-sans border transition-all cursor-pointer">
                            Standard (0 Extra)
                        </button>
                        <button type="button" 
                                @click="setExtraShots(1)"
                                :class="detailExtraShots === 1 ? 'bg-neutral-900 text-white font-medium shadow-2xs border-neutral-900' : 'bg-white text-neutral-700 border border-neutral-200 hover:bg-neutral-100/70'"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-sans border transition-all cursor-pointer">
                            +1 Extra Shot (+Rp 6.000)
                        </button>
                        <button type="button" 
                                @click="setExtraShots(2)"
                                :class="detailExtraShots === 2 ? 'bg-neutral-900 text-white font-medium shadow-2xs border-neutral-900' : 'bg-white text-neutral-700 border border-neutral-200 hover:bg-neutral-100/70'"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-sans border transition-all cursor-pointer">
                            +2 Extra Shots (+Rp 12.000)
                        </button>
                    </div>

                    <!-- Live Calculation Indicator for Shot -->
                    <div x-show="detailExtraShots > 0" class="text-[11px] font-sans text-neutral-900 bg-white p-2.5 rounded-xl border border-neutral-200/90 flex items-center justify-between shadow-2xs">
                        <span class="text-neutral-600">Total Racikan: <strong class="text-neutral-900"><span x-text="(selectedProduct?.base_shots || 1) + detailExtraShots"></span> Shots Espresso</strong></span>
                        <span class="font-mono font-bold text-neutral-900" x-text="'+ Rp ' + formatNumber(detailExtraShotsTotal)"></span>
                    </div>
                </div>

                <!-- 3. Varian Ekstra & Topping (Add-ons dengan Penambahan Harga) -->
                <div x-show="selectedProduct?.extras && selectedProduct.extras.length > 0" class="space-y-2 pt-1 border-t border-neutral-100">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-neutral-800 font-sans">
                            Pilihan Varian Ekstra & Topping:
                        </label>
                        <span class="text-[10px] text-neutral-400 font-mono">Bisa pilih lebih dari satu</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="extra in (selectedProduct?.extras || [])" :key="extra.id">
                            <button type="button"
                                    @click="toggleExtra(extra)"
                                    :class="isExtraSelected(extra.id) 
                                        ? 'border-neutral-900 bg-neutral-900 text-white shadow-xs' 
                                        : 'border-neutral-200 bg-white text-neutral-800 hover:border-neutral-300 hover:bg-neutral-50/60'"
                                    class="p-2.5 rounded-xl border text-left transition-all cursor-pointer flex items-center justify-between gap-2">
                                <div class="min-w-0 flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-md border flex items-center justify-center shrink-0 text-[10px] font-bold"
                                         :class="isExtraSelected(extra.id) ? 'bg-white text-neutral-900 border-white' : 'border-neutral-300 bg-neutral-50'">
                                        <span x-show="isExtraSelected(extra.id)">✓</span>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium leading-snug line-clamp-1" x-text="extra.name"></div>
                                        <div class="text-[10px] font-mono" :class="isExtraSelected(extra.id) ? 'text-neutral-300' : 'text-neutral-400'" x-text="extra.category || 'Ekstra'"></div>
                                    </div>
                                </div>
                                <span class="text-xs font-mono font-bold shrink-0 tabular-nums"
                                      :class="isExtraSelected(extra.id) ? 'text-white' : 'text-neutral-900'"
                                      x-text="'+Rp ' + formatNumber(extra.price)"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 4. Sweetness Level (Sugar Option) -->
                <div x-show="selectedProduct?.category === 'espresso' || selectedProduct?.category === 'tea_bev'" class="space-y-1.5 pt-1 border-t border-neutral-100">
                    <label class="block text-xs font-semibold text-neutral-800 font-sans">Tingkat Kemanisan (Sugar Level):</label>
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="s in ['Normal (100%)', 'Less Sweet (50%)', 'No Sugar (0%)']" :key="s">
                            <button type="button" 
                                    @click="detailSweetness = s"
                                    :class="detailSweetness === s ? 'bg-neutral-900 text-white font-medium shadow-xs' : 'bg-neutral-50 hover:bg-neutral-100 text-neutral-700 border border-neutral-200'"
                                    class="py-1.5 px-2 rounded-lg text-xs font-sans text-center transition-all cursor-pointer"
                                    x-text="s"></button>
                        </template>
                    </div>
                </div>

                <!-- 5. Special Notes / Catatan Barista -->
                <div class="space-y-1.5 pt-1 border-t border-neutral-100">
                    <label class="block text-xs font-semibold text-neutral-800 font-sans">Catatan Tambahan untuk Barista:</label>
                    <input type="text" 
                           x-model="detailNotes"
                           placeholder="Contoh: Es sedikit, susu oat dipisah, minta cup hangat..."
                           class="w-full px-3 py-2 rounded-xl bg-neutral-50 hover:bg-neutral-100/70 focus:bg-white border border-neutral-200 focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 text-xs font-sans outline-none transition-all" />
                </div>
            </div>

            <!-- Modal Footer (Quantity Stepper & Add to Order CTA) -->
            <div class="p-4 bg-neutral-50 border-t border-neutral-200 flex items-center justify-between gap-3 shrink-0">
                <!-- Quantity Stepper -->
                <div class="flex items-center gap-1 bg-white border border-neutral-200 rounded-xl p-1 shadow-2xs">
                    <button type="button" 
                            @click="if (detailQty > 1) detailQty--"
                            :disabled="detailQty <= 1"
                            class="w-8 h-8 rounded-lg text-neutral-600 hover:bg-neutral-100 flex items-center justify-center font-bold text-sm disabled:opacity-30 disabled:pointer-events-none cursor-pointer">
                        -
                    </button>
                    <span class="w-8 text-center text-xs font-mono font-bold text-neutral-900 tabular-nums select-none" x-text="detailQty"></span>
                    <button type="button" 
                            @click="detailQty++"
                            class="w-8 h-8 rounded-lg text-neutral-600 hover:bg-neutral-100 flex items-center justify-center font-bold text-sm cursor-pointer">
                        +
                    </button>
                </div>

                <!-- Add to Order Button with Dynamic Real-time Price -->
                <button type="button"
                        @click="addDetailToCart()"
                        :disabled="!selectedProduct || selectedProduct.stock === 0"
                        class="flex-1 py-2.5 px-4 rounded-xl bg-neutral-900 hover:bg-neutral-800 disabled:opacity-40 disabled:pointer-events-none text-white text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs transition-colors duration-150 active:scale-[0.99] cursor-pointer">
                    <div class="flex items-center gap-2">
                        <span>Tambah ke Pesanan</span>
                        <span x-show="detailQty > 1" class="text-[11px] px-1.5 py-0.5 rounded bg-neutral-800 text-neutral-300 font-mono" x-text="detailQty + 'x'"></span>
                    </div>
                    <div class="flex items-baseline gap-1 font-mono font-bold tabular-nums text-right">
                        <span class="text-[11px] text-neutral-400 font-normal">Total</span>
                        <span class="text-sm sm:text-base">Rp <span x-text="formatNumber(detailTotalPrice)"></span></span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 1: Checkout & Touch Payment (Clean Specialty Tone) -->
    <div x-show="openPaymentModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         @keydown.escape.window="closePaymentModal()">
        
        <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
             @click.away="closePaymentModal()">
            
            <div class="px-5 py-3.5 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-sm font-semibold text-neutral-900">Pembayaran Kasir</h3>
                    <p class="text-xs text-neutral-500 font-mono mt-0.5">Order: <span x-text="orderType"></span> • Meja: <span x-text="tableNumber || '-'"></span></p>
                </div>
                <button @click="closePaymentModal()" class="text-neutral-400 hover:text-neutral-900 cursor-pointer p-1">✕</button>
            </div>

            <div class="p-5 flex flex-col gap-4 overflow-y-auto">
                <!-- Payment Method Segmented Tabs (3 Channels: Cash, QRIS Midtrans, Transfer VA) -->
                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px;" class="p-1 bg-neutral-100 rounded-xl border border-neutral-200/80 text-xs shrink-0">
                    <button @click="switchPaymentMethod('cash')"
                            :class="paymentMethod === 'cash' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                            class="py-2.5 rounded-lg transition-colors cursor-pointer text-center flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Tunai</span>
                    </button>
                    <button @click="switchPaymentMethod('qris')"
                            :class="paymentMethod === 'qris' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                            class="py-2.5 rounded-lg transition-colors cursor-pointer text-center flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span>QRIS</span>
                    </button>
                    <button @click="switchPaymentMethod('transfer')"
                            :class="paymentMethod === 'transfer' ? 'bg-white text-neutral-900 font-semibold shadow-xs' : 'text-neutral-500 hover:text-neutral-900'"
                            class="py-2.5 rounded-lg transition-colors cursor-pointer text-center flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        <span>Transfer VA</span>
                    </button>
                </div>

                <!-- QRIS Section (Midtrans Dynamic QRIS) -->
                <div x-show="paymentMethod === 'qris'" class="space-y-3">
                    <div class="border border-neutral-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white">
                        <!-- Top Official Red Header Bar (QRIS ASPI Standard) -->
                        <div class="bg-gradient-to-r from-red-600 via-red-600 to-rose-700 px-4 py-2.5 text-white flex items-center justify-between shadow-xs">
                            <div class="flex items-center gap-2">
                                <div class="bg-white text-red-600 font-black text-xs px-1.5 py-0.5 rounded font-mono tracking-wider">
                                    QRIS
                                </div>
                                <div class="text-[11px] font-medium leading-tight">
                                    <div class="font-semibold tracking-wide">QRIS INTERAKSI NASIONAL</div>
                                    <div class="text-[9px] text-red-100 opacity-90">Standar Pembayaran Digital Bank Indonesia & ASPI</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[9px] uppercase px-2 py-0.5 rounded-full bg-white/20 text-white font-mono font-bold tracking-wider">
                                    GPN
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4 space-y-3">
                            <!-- Merchant Header -->
                            <div class="text-center pb-2 border-b border-neutral-100">
                                <h4 class="text-sm font-bold text-neutral-900 tracking-tight">KAFEINA ROASTERY & COFFEE LAB</h4>
                                <p class="text-[11px] text-neutral-500 font-mono mt-0.5">
                                    MID: <span class="font-semibold text-neutral-700">G620295717</span> • NMID: <span class="font-semibold text-neutral-700">ID102003889123</span>
                                </p>
                            </div>

                            <!-- State 1: Loading State -->
                            <div x-show="qrisLoading" class="py-10 flex flex-col items-center justify-center gap-3">
                                <div class="w-10 h-10 rounded-full border-3 border-neutral-200 border-t-red-600 animate-spin"></div>
                                <div class="text-center">
                                    <p class="text-xs font-semibold text-neutral-800">Menghubungkan ke Midtrans Core API...</p>
                                    <p class="text-[11px] text-neutral-500 mt-0.5">Membuat QR Code dinamis dengan nominal terkunci</p>
                                </div>
                            </div>

                            <!-- State 2: Error State -->
                            <div x-show="qrisError && !qrisLoading" class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-center space-y-2">
                                <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <p class="text-xs font-medium text-rose-800" x-text="qrisError"></p>
                                <button type="button" @click="initQrisPayment()" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-2xs transition-colors cursor-pointer">
                                    Coba Generate Ulang
                                </button>
                            </div>

                            <!-- State 3: Payment Settled / Success State -->
                            <div x-show="qrisSettled" class="py-6 flex flex-col items-center justify-center text-center gap-3 bg-emerald-50/70 border border-emerald-200 rounded-xl p-4">
                                <div class="w-12 h-12 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-md animate-bounce">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold tracking-widest text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                        SETTLEMENT VERIFIED
                                    </span>
                                    <h4 class="text-base font-bold text-neutral-900 mt-1">Pembayaran QRIS Berhasil!</h4>
                                    <div class="text-xl font-black font-mono text-emerald-800 mt-0.5">
                                        Rp <span x-text="formatNumber(grandTotal)"></span>
                                    </div>
                                    <p class="text-xs text-neutral-600 mt-1">
                                        Dana terkonfirmasi masuk dari Midtrans. Mencetak struk kasir...
                                    </p>
                                </div>
                            </div>

                            <!-- State 4: Active QR Display -->
                            <div x-show="!qrisLoading && !qrisError && !qrisSettled && qrisData" class="space-y-3">
                                <!-- QR Image Frame -->
                                <div class="flex flex-col items-center justify-center p-3 bg-neutral-50/80 rounded-xl border border-neutral-100">
                                    <div class="p-2.5 bg-white rounded-2xl border-2 border-neutral-800 shadow-sm relative cursor-pointer hover:ring-2 hover:ring-neutral-400 transition-all group"
                                         @click="copyQrString()"
                                         title="Klik gambar untuk salin QR string ke Simulator Midtrans">
                                        <img :src="qrisData?.qr_url" 
                                             alt="QRIS Dinamis Midtrans" 
                                             class="w-48 h-48 sm:w-52 sm:h-52 object-contain" />
                                        
                                        <!-- Center QRIS Badge -->
                                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <div class="w-8 h-8 bg-white rounded-lg p-1 border border-neutral-200 shadow-xs flex items-center justify-center">
                                                <span class="text-[9px] font-black text-red-600 font-mono">KAF</span>
                                            </div>
                                        </div>

                                        <!-- Hover click-to-copy tooltip -->
                                        <div class="absolute bottom-1 inset-x-0 mx-auto w-max px-2 py-0.5 rounded bg-neutral-900/80 text-white text-[9px] font-mono opacity-0 group-hover:opacity-100 transition-opacity">
                                            Klik untuk salin QR
                                        </div>
                                    </div>

                                    <!-- Locked Amount Banner -->
                                    <div class="mt-2.5 text-center">
                                        <div class="text-[10px] text-neutral-500 font-medium uppercase tracking-wider">Total Tagihan:</div>
                                        <div class="text-2xl font-black font-mono text-neutral-900 tracking-tight">
                                            Rp <span x-text="formatNumber(grandTotal)"></span>
                                        </div>
                                    </div>

                                    <!-- Direct Simulator Quick Action (Clean & Subtle) -->
                                    <div class="mt-1 text-center">
                                        <button type="button" 
                                                @click="openMidtransSimulator()" 
                                                class="text-[11px] text-neutral-500 hover:text-neutral-900 underline flex items-center justify-center gap-1.5 mx-auto cursor-pointer">
                                            <svg class="w-3 h-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            <span>Buka Midtrans Simulator (Otomatis Salin URL QR)</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Status Live & Countdown Bar -->
                                <div class="flex items-center justify-between px-3 py-2 bg-neutral-100/80 rounded-xl text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                        </span>
                                        <span class="text-neutral-700 font-medium text-[11px]">Menunggu pembayaran pelanggan...</span>
                                    </div>
                                    <div class="font-mono text-neutral-500 text-[11px]">
                                        Batas Waktu: <strong class="text-neutral-900 font-bold" x-text="formatTimer(qrisCountdown)">05:00</strong>
                                    </div>
                                </div>

                                <!-- Supported Apps Pill Bar -->
                                <div class="text-center pt-0.5">
                                    <p class="text-[9px] text-neutral-400 uppercase tracking-wider font-semibold mb-1">Dapat di-scan semua Bank & E-Wallet:</p>
                                    <div class="flex items-center justify-center flex-wrap gap-1 text-[10px] font-medium text-neutral-600">
                                        <span class="px-2 py-0.5 bg-neutral-100 rounded border border-neutral-200">BCA Mobile</span>
                                        <span class="px-2 py-0.5 bg-neutral-100 rounded border border-neutral-200">GoPay</span>
                                        <span class="px-2 py-0.5 bg-neutral-100 rounded border border-neutral-200">ShopeePay</span>
                                        <span class="px-2 py-0.5 bg-neutral-100 rounded border border-neutral-200">Livin' Mandiri</span>
                                        <span class="px-2 py-0.5 bg-neutral-100 rounded border border-neutral-200">Dana</span>
                                        <span class="px-2 py-0.5 bg-neutral-100 rounded border border-neutral-200">OVO</span>
                                        <span class="px-2 py-0.5 bg-neutral-100 rounded border border-neutral-200">BRImo</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Bank Transfer / Virtual Account Section (Midtrans Dynamic VA) -->
                <div x-show="paymentMethod === 'transfer'" class="space-y-3">
                    <!-- Bank Selection Tabs -->
                    <div>
                        <label class="block text-[11px] font-semibold text-neutral-500 uppercase tracking-wider mb-1.5">Pilih Bank Virtual Account:</label>
                        <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 6px;" class="p-1 bg-neutral-100 rounded-xl border border-neutral-200/80">
                            <template x-for="b in [
                                { code: 'bca', label: 'BCA' },
                                { code: 'mandiri', label: 'Mandiri' },
                                { code: 'bni', label: 'BNI' },
                                { code: 'bri', label: 'BRI' },
                                { code: 'permata', label: 'Permata' }
                            ]" :key="b.code">
                                <button type="button" @click="changeVaBank(b.code)"
                                        :class="vaBank === b.code ? 'bg-neutral-900 text-white font-bold shadow-xs' : 'text-neutral-600 hover:text-neutral-900 hover:bg-white/60'"
                                        class="py-2 px-1 text-xs rounded-lg text-center transition-all cursor-pointer flex flex-col items-center justify-center">
                                    <span x-text="b.label" class="text-xs font-semibold"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Virtual Account Box Container (Clean Minimalist) -->
                    <div class="border border-neutral-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white">
                        <!-- Header: Total Tagihan & Status -->
                        <div class="p-3.5 bg-neutral-50/80 border-b border-neutral-100 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] text-neutral-500 uppercase font-mono tracking-wider">Total Tagihan</div>
                                <div class="text-lg font-bold text-neutral-900 font-mono">
                                    Rp <span x-text="formatNumber(grandTotal)"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-full font-mono font-bold"
                                      :class="vaSettled ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="vaSettled ? 'bg-emerald-600' : 'bg-amber-600 animate-pulse'"></span>
                                    <span x-text="vaSettled ? 'Lunas Terverifikasi' : 'Menunggu Transfer'"></span>
                                </span>
                            </div>
                        </div>

                        <div class="p-4 space-y-3">
                            <!-- State 1: Loading State -->
                            <div x-show="vaLoading" class="py-8 flex flex-col items-center justify-center gap-2">
                                <div class="w-7 h-7 rounded-full border-2 border-neutral-200 border-t-neutral-900 animate-spin"></div>
                                <p class="text-xs font-medium text-neutral-600">Menghubungkan ke Midtrans VA...</p>
                            </div>

                            <!-- State 2: Active VA Details -->
                            <div x-show="!vaLoading" class="space-y-3">
                                <!-- Mandiri Company Code (if Mandiri) -->
                                <div x-show="vaBank === 'mandiri'" class="p-2.5 bg-amber-50/70 border border-amber-200/80 rounded-xl flex items-center justify-between font-mono text-xs">
                                    <span class="text-[11px] text-amber-900">Kode Perusahaan (Biller Code):</span>
                                    <strong class="text-neutral-900 text-sm">70012</strong>
                                </div>

                                <!-- Virtual Account Number Card -->
                                <div class="p-4 bg-neutral-900 text-white rounded-xl space-y-2">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <div class="flex items-center gap-1.5 text-neutral-400 font-mono">
                                            <span>NOMOR VIRTUAL ACCOUNT</span>
                                            <span class="px-1.5 py-0.5 rounded bg-white/10 text-amber-300 text-[10px] font-bold uppercase" x-text="vaBank"></span>
                                        </div>
                                        <button type="button" @click="copyVaNumber()"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-colors cursor-pointer"
                                                :class="vaCopied ? 'bg-emerald-600 text-white' : 'bg-white/10 hover:bg-white/20 text-neutral-200'">
                                            <svg x-show="!vaCopied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            <svg x-show="vaCopied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span x-text="vaCopied ? 'Tersalin' : 'Salin'"></span>
                                        </button>
                                    </div>
                                    <div class="font-mono text-lg sm:text-xl font-bold tracking-wider text-amber-300 break-all select-all leading-normal"
                                         x-text="vaNumber || 'Memuat nomor VA...'">
                                    </div>
                                </div>

                                <!-- Direct Link to Official Midtrans Payment Simulator -->
                                <div class="p-3 bg-neutral-50 rounded-xl border border-neutral-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                    <div class="text-[11px] text-neutral-500 font-mono">
                                        Order: <span class="font-semibold text-neutral-800" x-text="vaOrderNumber || '-'"></span>
                                    </div>
                                    <a :href="getMidtransVaSimulatorUrl()" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-900 hover:text-amber-700 underline underline-offset-2 transition-colors cursor-pointer">
                                        <span>Buka Midtrans Payment Simulator</span>
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cash Payment Section -->
                <div x-show="paymentMethod === 'cash'" class="space-y-3">
                    <div class="grid grid-cols-4 gap-1.5">
                        <button @click="paidAmount = grandTotal" class="py-2 bg-neutral-100 hover:bg-neutral-200/80 text-xs font-semibold text-neutral-800 rounded-lg border border-neutral-200/80 cursor-pointer transition-colors">
                            Uang Pas
                        </button>
                        <button @click="paidAmount = Math.ceil(grandTotal / 50000) * 50000" class="py-2 bg-neutral-100 hover:bg-neutral-200/80 text-xs font-semibold text-neutral-800 rounded-lg border border-neutral-200/80 cursor-pointer transition-colors">
                            50.000+
                        </button>
                        <button @click="paidAmount = 100000" class="py-2 bg-neutral-100 hover:bg-neutral-200/80 text-xs font-semibold text-neutral-800 rounded-lg border border-neutral-200/80 cursor-pointer transition-colors">
                            100.000
                        </button>
                        <button @click="paidAmount = 200000" class="py-2 bg-neutral-100 hover:bg-neutral-200/80 text-xs font-semibold text-neutral-800 rounded-lg border border-neutral-200/80 cursor-pointer transition-colors">
                            200.000
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 p-3.5 bg-neutral-50 rounded-xl border border-neutral-200/80">
                        <div>
                            <span class="text-[11px] text-neutral-500">Uang Diterima:</span>
                            <div class="text-xl font-bold text-neutral-900 font-mono">
                                Rp <span x-text="formatNumber(paidAmount)"></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] text-neutral-500">Kembalian:</span>
                            <div class="text-xl font-bold font-mono text-emerald-700">
                                Rp <span x-text="formatNumber(Math.max(0, paidAmount - grandTotal))"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Touch Keypad (0ms instant) -->
                    <div class="grid grid-cols-3 gap-1.5 font-mono">
                        <template x-for="num in ['1', '2', '3', '4', '5', '6', '7', '8', '9', '00', '0', 'C']" :key="num">
                            <button @click="handleKeypad(num)"
                                    class="h-12 min-h-[48px] bg-white hover:bg-neutral-100 text-neutral-900 font-bold text-base rounded-lg border border-neutral-200 active:bg-neutral-200 flex items-center justify-center cursor-pointer transition-colors shadow-2xs">
                                <span x-text="num"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="px-5 py-3.5 bg-neutral-50 border-t border-neutral-200 flex items-center justify-between gap-3 shrink-0">
                <button @click="closePaymentModal()" class="px-4 py-2 rounded-lg text-xs font-medium text-neutral-600 hover:text-neutral-900 cursor-pointer">
                    Batal
                </button>

                <button @click="processCheckout()" 
                        :disabled="(paymentMethod === 'cash' && paidAmount < grandTotal) || (paymentMethod === 'qris' && !qrisSettled) || (paymentMethod === 'transfer' && !vaSettled)"
                        class="flex-1 min-h-[48px] py-3 rounded-xl bg-neutral-900 hover:bg-neutral-800 disabled:opacity-40 disabled:pointer-events-none text-white font-semibold text-xs shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-2">
                    <span x-show="paymentMethod === 'cash' || qrisSettled || (paymentMethod === 'transfer' && vaSettled)">SELESAIKAN & CETAK STRUK</span>
                    <span x-show="paymentMethod === 'qris' && !qrisSettled" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin text-neutral-400" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        MENUNGGU PEMBAYARAN QRIS...
                    </span>
                    <span x-show="paymentMethod === 'transfer' && !vaSettled" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin text-neutral-400" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        MENUNGGU TRANSFER VA...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Split Bill -->
    <div x-show="openSplitBillModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         @keydown.escape.window="openSplitBillModal = false">
        <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-2xl shadow-2xl p-5" @click.away="openSplitBillModal = false">
            <h3 class="text-sm font-semibold text-neutral-900 mb-1">Split Bill (Pemisahan Tagihan)</h3>
            <p class="text-xs text-neutral-500 mb-4">Meja <span class="font-mono font-bold text-neutral-900" x-text="tableNumber"></span></p>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3.5 bg-neutral-50 rounded-xl border border-neutral-200/80">
                    <span class="text-xs text-neutral-700 font-medium">Bagi Rata (Jumlah Orang):</span>
                    <div class="flex items-center gap-2">
                        <button @click="splitPersonCount = Math.max(2, splitPersonCount - 1)" class="w-8 h-8 rounded-lg bg-white border border-neutral-200 font-bold shadow-2xs hover:bg-neutral-50 cursor-pointer">-</button>
                        <span class="w-8 text-center font-mono font-bold text-neutral-900 text-sm" x-text="splitPersonCount"></span>
                        <button @click="splitPersonCount = Math.min(10, splitPersonCount + 1)" class="w-8 h-8 rounded-lg bg-white border border-neutral-200 font-bold shadow-2xs hover:bg-neutral-50 cursor-pointer">+</button>
                    </div>
                </div>

                <div class="p-3.5 bg-neutral-100/70 rounded-xl border border-neutral-200/80 flex justify-between items-center text-xs font-mono">
                    <span class="text-neutral-600">Nominal Per Orang:</span>
                    <span class="font-bold text-neutral-900 text-base" x-text="'Rp ' + formatNumber(Math.round(grandTotal / splitPersonCount))"></span>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="openSplitBillModal = false" class="px-4 py-2 rounded-lg text-xs text-neutral-600 hover:text-neutral-900 cursor-pointer">Tutup</button>
                <button @click="openSplitBillModal = false; alert('Split Bill Disimpan.');" class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-medium hover:bg-neutral-800 cursor-pointer transition-colors shadow-2xs">Simpan</button>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Non-Revenue Modal -->
    <div x-show="openNonRevenueModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         @keydown.escape.window="openNonRevenueModal = false">
        <div class="w-full max-w-md bg-white border border-neutral-200 rounded-2xl shadow-2xl p-5" @click.away="openNonRevenueModal = false">
            <h3 class="text-sm font-semibold text-neutral-900 mb-1">Transaksi Non-Revenue (Rp 0)</h3>
            <p class="text-xs text-neutral-500 mb-3">Pemotongan stok bahan baku operasional tanpa mencatat omzet kasir</p>

            <div class="space-y-2 text-xs">
                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-neutral-200/80 cursor-pointer hover:bg-neutral-50 transition-colors">
                    <input type="radio" value="Tester Cupping / QC Roastery" x-model="nonRevenueReason" class="text-neutral-900">
                    <span class="font-medium text-neutral-800">1. Tester Cupping & QC Roastery</span>
                </label>
                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-neutral-200/80 cursor-pointer hover:bg-neutral-50 transition-colors">
                    <input type="radio" value="Kalibrasi Espresso Harian (Dialing-in)" x-model="nonRevenueReason" class="text-neutral-900">
                    <span class="font-medium text-neutral-800">2. Kalibrasi Espresso Harian (Dialing-in)</span>
                </label>
                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-neutral-200/80 cursor-pointer hover:bg-neutral-50 transition-colors">
                    <input type="radio" value="Jatah Minuman Karyawan" x-model="nonRevenueReason" class="text-neutral-900">
                    <span class="font-medium text-neutral-800">3. Jatah Minuman Karyawan (Staff Allowance)</span>
                </label>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="openNonRevenueModal = false" class="px-4 py-2 rounded-lg text-xs text-neutral-600 hover:text-neutral-900 cursor-pointer">Batal</button>
                <button @click="processNonRevenueCheckout()" class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-medium hover:bg-neutral-800 transition-colors cursor-pointer shadow-2xs">Proses Non-Revenue</button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: ESC/POS Thermal Receipt Preview & Drawer Kick -->
    <div x-show="openReceiptModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs overflow-y-auto font-sans"
         @keydown.escape.window="openReceiptModal = false">
        <div class="w-full max-w-md bg-white border border-neutral-200 rounded-2xl shadow-2xl flex flex-col my-auto" @click.away="openReceiptModal = false">
            <div class="px-4 py-3 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-neutral-700">Format:</span>
                    <button @click="receiptWidth = '58'" :class="receiptWidth === '58' ? 'bg-neutral-900 text-white' : 'bg-white text-neutral-800 border border-neutral-200'" class="px-2 py-0.5 rounded-md text-xs font-mono cursor-pointer">58mm</button>
                    <button @click="receiptWidth = '80'" :class="receiptWidth === '80' ? 'bg-neutral-900 text-white' : 'bg-white text-neutral-800 border border-neutral-200'" class="px-2 py-0.5 rounded-md text-xs font-mono cursor-pointer">80mm</button>
                </div>
                <button @click="triggerDrawerKick()" class="px-2.5 py-1 rounded-md bg-white hover:bg-neutral-100 border border-neutral-200 text-neutral-800 text-xs font-medium cursor-pointer shadow-2xs">
                    Kick Laci
                </button>
            </div>

            <div class="p-6 bg-neutral-100 flex justify-center items-center">
                <div id="printable-receipt" :class="receiptWidth === '58' ? 'thermal-paper-58' : 'thermal-paper-80'" class="bg-white text-black p-4 font-mono text-xs leading-relaxed select-text border border-neutral-300 shadow-sm">
                    <div class="text-center pb-2 border-b border-dashed border-neutral-400">
                        <div class="font-extrabold text-sm">KAFEINA ROASTERY</div>
                        <div class="text-[9px] text-neutral-600">Jl. Senopati No. 88, Jakarta Selatan</div>
                    </div>
                    <div class="py-2 border-b border-dashed border-neutral-400 text-[10px] space-y-0.5">
                        <div class="flex justify-between"><span>Order:</span><span class="font-bold" x-text="receiptData.order_number"></span></div>
                        <div class="flex justify-between"><span>Waktu:</span><span x-text="receiptData.created_at"></span></div>
                        <div class="flex justify-between"><span>Kasir:</span><span x-text="receiptData.cashier"></span></div>
                        <div class="flex justify-between">
                            <span>Tipe:</span>
                            <span class="font-bold" x-text="(receiptData.order_type || 'Dine In') + (receiptData.order_type === 'Dine In' && receiptData.table_number && receiptData.table_number !== '-' ? ' (Meja ' + receiptData.table_number + ')' : '')"></span>
                        </div>
                        <div class="flex justify-between" x-show="receiptData.customer_name">
                            <span>Pelanggan:</span>
                            <span class="font-bold" x-text="receiptData.customer_name"></span>
                        </div>
                    </div>
                    <div class="py-2 border-b border-dashed border-neutral-400 space-y-1 text-[10px]">
                        <template x-for="(item, rIdx) in receiptData.items" :key="(item.id || 'itm') + '-' + rIdx">
                            <div>
                                <div class="font-bold" x-text="item.name"></div>
                                <div x-show="item.temp || item.extraShots > 0 || (item.selectedExtras && item.selectedExtras.length > 0) || item.notes" class="text-[9px] text-neutral-600 pl-1">
                                    <span x-show="item.temp" x-text="'[' + item.temp + '] '"></span>
                                    <span x-show="item.extraShots > 0" x-text="'+' + item.extraShots + ' Shot '"></span>
                                    <template x-for="ex in (item.selectedExtras || [])" :key="ex.id">
                                        <span x-text="'+' + ex.name + ' '"></span>
                                    </template>
                                    <span x-show="item.sweetness && item.sweetness !== 'Normal (100%)'" x-text="item.sweetness + ' '"></span>
                                    <span x-show="item.notes" x-text="'(' + item.notes + ')'"></span>
                                </div>
                                <div class="flex justify-between pl-2 text-neutral-600">
                                    <span x-text="item.qty + 'x @ ' + formatNumber(item.price)"></span>
                                    <span class="font-bold text-black" x-text="formatNumber(item.price * item.qty)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="py-2 border-b border-dashed border-neutral-400 text-[10px] space-y-0.5">
                        <div class="flex justify-between"><span>Subtotal:</span><span x-text="'Rp ' + formatNumber(receiptData.subtotal)"></span></div>
                        <div class="flex justify-between" x-show="receiptData.discount > 0"><span>Diskon:</span><span x-text="'- Rp ' + formatNumber(receiptData.discount)"></span></div>
                        <div class="flex justify-between" x-show="(receiptData.service_charge || 0) > 0"><span>Service (<span x-text="receiptData.service_charge_rate ?? serviceChargeRate"></span>%):</span><span x-text="'Rp ' + formatNumber(receiptData.service_charge)"></span></div>
                        <div class="flex justify-between"><span>PB1 <span x-text="receiptData.tax_pb1_rate ?? taxPb1Rate"></span>%:</span><span x-text="'Rp ' + formatNumber(receiptData.tax_pb1)"></span></div>
                        <div class="flex justify-between font-bold text-xs pt-1 border-t border-dashed border-neutral-400"><span>TOTAL:</span><span x-text="'Rp ' + formatNumber(receiptData.total)"></span></div>
                        <div class="flex justify-between"><span>Bayar (<span x-text="receiptData.payment_method"></span>):</span><span x-text="'Rp ' + formatNumber(receiptData.paid_amount)"></span></div>
                        <div class="flex justify-between" x-show="receiptData.payment_reference"><span>Ref/EDC/VA:</span><span class="font-bold truncate max-w-[150px]" x-text="receiptData.payment_reference"></span></div>
                        <div class="flex justify-between" x-show="receiptData.payment_method === 'CASH'"><span>Kembalian:</span><span x-text="'Rp ' + formatNumber(receiptData.change_amount)"></span></div>
                    </div>
                    <div class="text-center pt-2 text-[9px] text-neutral-500">
                        Terima Kasih atas Kunjungan Anda!
                    </div>
                </div>
            </div>

            <div class="px-5 py-3.5 bg-neutral-50 border-t border-neutral-200 flex justify-end gap-2">
                <button @click="openReceiptModal = false" class="px-4 py-2 rounded-lg text-xs text-neutral-600 hover:text-neutral-900 cursor-pointer">Selesai</button>
                <button @click="printReceipt()" class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-semibold hover:bg-neutral-800 transition-colors cursor-pointer shadow-2xs">Cetak Struk (Ctrl+P)</button>
            </div>
        </div>
    </div>

    <!-- MODAL 5: Tutup Shift (Cash Counting) -->
    <div x-show="openCloseShiftModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         @keydown.escape.window="openCloseShiftModal = false">
        <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-2xl shadow-2xl p-5" @click.away="openCloseShiftModal = false">
            <h3 class="text-sm font-semibold text-neutral-900 mb-1">Tutup Sesi Shift Kasir</h3>
            <p class="text-xs text-neutral-500 mb-4">Penghitungan fisik uang tunai laci kasir (Blind Cash Drop)</p>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <template x-for="d in denominations" :key="d.denom">
                        <div class="flex items-center justify-between p-2 rounded-lg border border-neutral-200/80 bg-neutral-50">
                            <span class="font-mono font-medium text-neutral-700" x-text="'Rp ' + d.label"></span>
                            <div class="flex items-center gap-1.5">
                                <input type="number" min="0" x-model.number="d.count" class="w-14 px-2 py-0.5 rounded border border-neutral-300 bg-white font-mono text-center text-xs focus:outline-none focus:border-neutral-900" />
                                <span class="text-[10px] text-neutral-400">lbr</span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="p-3.5 bg-neutral-100/70 rounded-xl border border-neutral-200/80 space-y-1 text-xs font-mono tabular-nums">
                    <div class="flex justify-between text-neutral-600">
                        <span>Total Fisik Dihitung:</span>
                        <span class="font-bold text-neutral-900" x-text="'Rp ' + formatNumber(calculateTotalPhysicalCash())"></span>
                    </div>
                    <div class="flex justify-between text-neutral-600">
                        <span>Target Sistem:</span>
                        <span>Rp 4.350.000</span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-neutral-200 font-bold"
                         :class="getDiscrepancy() === 0 ? 'text-emerald-700' : 'text-rose-600'">
                        <span>Selisih (Discrepancy):</span>
                        <span x-text="(getDiscrepancy() >= 0 ? '+ Rp ' : '- Rp ') + formatNumber(Math.abs(getDiscrepancy()))"></span>
                    </div>
                </div>

                <div x-show="Math.abs(getDiscrepancy()) > 10000" class="space-y-1">
                    <label class="text-[11px] text-rose-600 font-medium">Otorisasi Supervisor (PIN Diperlukan jika selisih > Rp 10.000):</label>
                    <input type="password" x-model="supervisorPin" placeholder="Masukkan 6-digit PIN" class="w-full px-3 py-1.5 rounded-lg border border-rose-300 bg-rose-50/50 text-xs font-mono focus:outline-none focus:border-rose-600" />
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="openCloseShiftModal = false" class="px-4 py-2 rounded-lg text-xs text-neutral-600 hover:text-neutral-900 cursor-pointer">Batal</button>
                <button @click="submitShiftClosing()" class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-semibold hover:bg-neutral-800 transition-colors cursor-pointer shadow-2xs">Tutup & Rekonsiliasi</button>
            </div>
        </div>
    </div>

    <!-- MODAL 6: Shift Info Detail & Automated Schedule Reconciliation -->
    <div x-show="openShiftModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         @keydown.escape.window="openShiftModal = false">
        <div class="w-full max-w-md bg-white border border-neutral-200 rounded-2xl shadow-2xl p-5" @click.away="openShiftModal = false">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-semibold text-neutral-900">Jadwal Shift & Rekonsiliasi Kasir</h3>
                <button type="button" @click="openShiftModal = false" class="text-neutral-400 hover:text-neutral-900 p-1 font-bold text-xs cursor-pointer">✕</button>
            </div>
            <p class="text-xs text-neutral-500 mb-4">Sistem menutup shift dan membukukan kas otomatis sesuai jam operasional.</p>

            <div class="space-y-2 text-xs font-sans">
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Cabang / Outlet:</span>
                    <span class="font-semibold text-neutral-900" x-text="shiftData.outlet_name || 'Kafeina Flagship Senopati'">Kafeina Flagship Senopati</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Kasir Bertugas:</span>
                    <span class="font-semibold text-neutral-900" x-text="shiftData.cashier_name || 'Rizky Ramadhan'"></span>
                </div>
                <template x-if="shiftData.supervisor_name">
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Supervisi Akun:</span>
                    <span class="font-medium text-neutral-800 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <span x-text="shiftData.supervisor_name">Dimas Wicaksono (CEO)</span>
                    </span>
                </div>
                </template>
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Sesi Berjalan:</span>
                    <span class="font-medium text-neutral-900" x-text="currentShiftSchedule.name">Shift 1 (Pagi - Siang)</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Rentang Jam Kerja:</span>
                    <span class="font-mono text-neutral-800" x-text="currentShiftSchedule.hours + ' WIB'">07:00 – 15:00 WIB</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Jadwal Tutup Otomatis:</span>
                    <span class="font-mono font-bold text-amber-700" x-text="currentShiftSchedule.endHour + ':00 WIB'">15:00:00 WIB</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Sisa Waktu Shift:</span>
                    <span class="font-mono font-semibold text-neutral-900" x-text="shiftRemainingTime"></span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Modal Awal Laci:</span>
                    <span class="font-mono font-semibold text-neutral-900">Rp <span x-text="formatNumber(shiftData.opening_cash)">500.000</span></span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Penjualan Tunai Sistem:</span>
                    <span class="font-mono font-semibold text-neutral-900">Rp <span x-text="formatNumber(shiftData.system_cash_sales || 3850000)">3.850.000</span></span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-neutral-100">
                    <span class="text-neutral-500">Total Kas Terestimasi di Laci:</span>
                    <span class="font-mono font-bold text-neutral-900">Rp <span x-text="formatNumber(shiftData.expected_physical_cash || 4350000)">4.350.000</span></span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-neutral-500">Total Struk Diproses:</span>
                    <span class="font-mono font-semibold text-neutral-900" x-text="(shiftData.transaction_count || 64) + ' Struk'"></span>
                </div>
            </div>

            <div class="mt-4 p-2.5 rounded-xl bg-neutral-50 border border-neutral-200/80 text-[11px] text-neutral-600 leading-relaxed font-sans">
                <div class="flex items-start gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Penutupan shift tidak memerlukan input manual. Tepat pukul <strong x-text="currentShiftSchedule.endHour">15:00</strong> WIB, sistem otomatis mengunci sesi, membukukan rekonsiliasi, dan membuka sesi shift berikutnya.</span>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button @click="openShiftModal = false" class="px-4 py-2 rounded-lg bg-neutral-900 text-white text-xs font-medium hover:bg-neutral-800 transition-colors cursor-pointer shadow-2xs">Mengerti</button>
            </div>
        </div>
    </div>

    <!-- MODAL 7: Kelola & Kuota Kode Promo (Redeem Code) -->
    <div x-show="openPromoModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-neutral-900/50 backdrop-blur-xs font-sans"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="openPromoModal = false">
        
        <div x-show="openPromoModal"
             x-transition:enter="transition-all duration-200 ease-out"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition-all duration-150 ease-in"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-2xl bg-white border border-neutral-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
             @click.away="openPromoModal = false">
            
            <!-- Modal Header with Tab Selector -->
            <div class="px-5 py-4 bg-neutral-50 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-sm font-bold text-neutral-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        Kelola & Kuota Kode Promo
                    </h3>
                    <p class="text-xs text-neutral-500 mt-0.5">Sistem voucher diskon dengan batas kuota penukaran (*redeem limit*).</p>
                </div>
                
                <!-- Tab Pills -->
                <div class="flex items-center gap-1 p-0.5 bg-neutral-200/70 rounded-lg text-xs">
                    <button type="button" 
                            @click="promoTab = 'list'"
                            :class="promoTab === 'list' ? 'bg-white font-semibold text-neutral-900 shadow-2xs' : 'text-neutral-600 hover:text-neutral-900'"
                            class="px-3 py-1 rounded-md transition-all cursor-pointer">
                        Katalog Promo (<span x-text="redeemCodes.length"></span>)
                    </button>
                    @if(auth()->user()?->canAccessModule('vouchers'))
                    <button type="button" 
                            @click="promoTab = 'create'"
                            :class="promoTab === 'create' ? 'bg-white font-semibold text-neutral-900 shadow-2xs' : 'text-neutral-600 hover:text-neutral-900'"
                            class="px-3 py-1 rounded-md transition-all cursor-pointer">
                        + Tambah / Atur Kuota
                    </button>
                    @endif
                </div>
            </div>

            <!-- Tab 1: Promo List & Quota Tracker -->
            <div x-show="promoTab === 'list'" class="p-5 overflow-y-auto space-y-3 flex-1">
                <template x-for="code in redeemCodes" :key="code.id">
                    <div class="p-4 rounded-xl border transition-all"
                         :class="(code.max_redemptions !== null && code.times_redeemed >= code.max_redemptions) || !code.is_active ? 'bg-neutral-50/70 border-neutral-200 opacity-60' : 'bg-white border-neutral-200 hover:border-neutral-300 shadow-2xs'">
                        
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                            <!-- Left: Code info -->
                            <div class="space-y-1.5 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-lg bg-neutral-900 text-white font-mono text-xs font-bold tracking-wider" x-text="code.code"></span>
                                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-md"
                                          x-text="code.discount_type === 'PERCENTAGE' ? ('Diskon ' + Math.round(code.discount_value) + '%') : ('Potongan Rp ' + formatNumber(code.discount_value))"></span>
                                    <span x-show="!code.is_active" class="text-[10px] font-medium text-neutral-500 bg-neutral-200 px-1.5 py-0.5 rounded">Nonaktif</span>
                                    <span x-show="code.max_redemptions !== null && code.times_redeemed >= code.max_redemptions" class="text-[10px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">Kuota Habis</span>
                                </div>
                                <p class="text-xs text-neutral-700 font-sans" x-text="code.description || 'Promo Spesial Kafeina'"></p>
                                
                                <div class="flex flex-wrap items-center gap-3 text-[11px] text-neutral-500 font-sans pt-1">
                                    <span>Min. Belanja: <strong class="text-neutral-800">Rp <span x-text="formatNumber(code.min_order_amount)"></span></strong></span>
                                    <span x-show="code.max_discount_amount">Maks. Diskon: <strong class="text-neutral-800">Rp <span x-text="formatNumber(code.max_discount_amount)"></span></strong></span>
                                </div>
                            </div>

                            <!-- Right: Quota & Action Button -->
                            <div class="sm:w-48 shrink-0 flex flex-col justify-between sm:items-end gap-2 pt-2 sm:pt-0 border-t sm:border-t-0 border-neutral-100">
                                <!-- Quota Counter -->
                                <div class="w-full sm:text-right text-xs">
                                    <div class="flex items-center justify-between sm:justify-end gap-1 text-[11px] font-mono mb-1">
                                        <span class="text-neutral-500">Kuota Terpakai:</span>
                                        <span class="font-bold text-neutral-900">
                                            <span x-text="code.times_redeemed"></span> / <span x-text="code.max_redemptions !== null ? code.max_redemptions : '∞'"></span>
                                        </span>
                                    </div>
                                    <!-- Progress bar if limited -->
                                    <div x-show="code.max_redemptions !== null" class="w-full bg-neutral-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300"
                                             :class="code.times_redeemed >= code.max_redemptions ? 'bg-rose-500' : 'bg-emerald-600'"
                                             :style="'width: ' + Math.min(100, (code.times_redeemed / code.max_redemptions) * 100) + '%'"></div>
                                    </div>
                                    <div x-show="code.max_redemptions !== null" class="text-[10px] text-neutral-400 mt-0.5">
                                        Sisa: <strong class="text-neutral-700" x-text="Math.max(0, code.max_redemptions - code.times_redeemed)"></strong> kali klaim
                                    </div>
                                </div>

                                <!-- Apply button -->
                                <button type="button"
                                        @click="applyRedeemCode(code.code)"
                                        :disabled="(code.max_redemptions !== null && code.times_redeemed >= code.max_redemptions) || !code.is_active"
                                        class="w-full sm:w-auto px-3.5 py-1.5 rounded-lg bg-neutral-900 hover:bg-neutral-800 disabled:opacity-30 disabled:pointer-events-none text-white text-xs font-semibold cursor-pointer transition-colors shadow-2xs">
                                    Gunakan di Pesanan
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Tab 2: Create / Configure New Promo Code -->
            <div x-show="promoTab === 'create'" x-cloak class="p-5 overflow-y-auto space-y-4 flex-1">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-sans">
                    <!-- Code -->
                    <div>
                        <label class="block font-semibold text-neutral-700 mb-1">Kode Promo (Harus Unik):</label>
                        <input type="text" 
                               x-model="newPromo.code" 
                               placeholder="Contoh: KAFEINAPAGI, JUMATBERKAH..."
                               class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs font-mono uppercase tracking-wider focus:bg-white focus:border-neutral-900 outline-none" />
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block font-semibold text-neutral-700 mb-1">Tipe Diskon:</label>
                        <select x-model="newPromo.discount_type" class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs focus:bg-white focus:border-neutral-900 outline-none">
                            <option value="PERCENTAGE">Persentase (%)</option>
                            <option value="FIXED">Potongan Tetap (Rp)</option>
                        </select>
                    </div>

                    <!-- Value -->
                    <div>
                        <label class="block font-semibold text-neutral-700 mb-1" x-text="newPromo.discount_type === 'PERCENTAGE' ? 'Nilai Persentase (%):' : 'Nilai Potongan (Rp):'"></label>
                        <input type="number" 
                               x-model="newPromo.discount_value" 
                               placeholder="10 atau 20000"
                               class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs font-mono focus:bg-white focus:border-neutral-900 outline-none" />
                    </div>

                    <!-- Min Order -->
                    <div>
                        <label class="block font-semibold text-neutral-700 mb-1">Minimal Belanja (Rp):</label>
                        <input type="number" 
                               x-model="newPromo.min_order_amount" 
                               placeholder="0 (Tanpa minimal)"
                               class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs font-mono focus:bg-white focus:border-neutral-900 outline-none" />
                    </div>

                    <!-- Max Discount (if percentage) -->
                    <div x-show="newPromo.discount_type === 'PERCENTAGE'">
                        <label class="block font-semibold text-neutral-700 mb-1">Maksimal Diskon (Rp, Opsional):</label>
                        <input type="number" 
                               x-model="newPromo.max_discount_amount" 
                               placeholder="Kosongkan jika tanpa batas"
                               class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs font-mono focus:bg-white focus:border-neutral-900 outline-none" />
                    </div>

                    <!-- Max Redemptions (Batas seberapa banyak kode bisa di-redeem) -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="font-semibold text-neutral-700">Batas Kuota Penukaran:</label>
                            <label class="flex items-center gap-1.5 cursor-pointer text-neutral-500 text-[11px]">
                                <input type="checkbox" x-model="newPromo.is_unlimited" class="rounded text-neutral-900" />
                                <span>Tanpa Batas (∞)</span>
                            </label>
                        </div>
                        <input type="number" 
                               x-model="newPromo.max_redemptions" 
                               :disabled="newPromo.is_unlimited"
                               placeholder="Jumlah maksimal redeem (cth: 50 kali)"
                               class="w-full px-3 py-2 rounded-lg bg-neutral-50 disabled:opacity-40 border border-neutral-200 text-xs font-mono focus:bg-white focus:border-neutral-900 outline-none" />
                        <p class="text-[10px] text-neutral-400 mt-1">Mengatur seberapa banyak kode ini dapat digunakan oleh pelanggan sebelum hangus/habis.</p>
                    </div>

                    <!-- Description -->
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-neutral-700 mb-1">Deskripsi Singkat / Catatan Promo:</label>
                        <input type="text" 
                               x-model="newPromo.description" 
                               placeholder="Contoh: Voucher HUT Kopi Spesialti 10% All Menu"
                               class="w-full px-3 py-2 rounded-lg bg-neutral-50 border border-neutral-200 text-xs focus:bg-white focus:border-neutral-900 outline-none" />
                    </div>
                </div>

                <div class="pt-3 border-t border-neutral-200 flex justify-end gap-2">
                    <button type="button" @click="promoTab = 'list'" class="px-4 py-2 rounded-lg text-xs text-neutral-600 hover:text-neutral-900 cursor-pointer">Batal</button>
                    <button type="button" @click="saveNewPromoCode()" class="px-4 py-2 rounded-lg bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-semibold cursor-pointer transition-colors shadow-2xs">
                        Simpan & Aktifkan Kode
                    </button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-200 flex items-center justify-between shrink-0">
                @if(auth()->user()?->canAccessModule('vouchers'))
                <a href="{{ route('vouchers.index') }}" target="_blank" class="text-xs font-semibold text-neutral-900 hover:underline flex items-center gap-1">
                    <span>Buka Halaman Penuh Manajemen Voucher ↗</span>
                </a>
                @else
                <div></div>
                @endif
                <button type="button" @click="openPromoModal = false" class="px-4 py-2 rounded-lg bg-neutral-200 hover:bg-neutral-300 text-neutral-800 text-xs font-medium cursor-pointer transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function posApp() {
    return {
        categories: @json($categories),
        products: @json($products),
        shiftData: @json($shift),

        searchQuery: '',

        orderType: 'Dine In',
        tableNumber: '',
        customerName: '',
        validationError: '',
        cart: [],

        discountPercent: 0,
        
        redeemCodes: @json($redeemCodes ?? []),
        openPromoModal: false,
        promoTab: 'list',
        promoCodeInput: '',
        appliedPromo: null,
        promoError: '',
        newPromo: {
            code: '',
            description: '',
            discount_type: 'PERCENTAGE',
            discount_value: 10,
            min_order_amount: 0,
            max_discount_amount: null,
            max_redemptions: 50,
            is_unlimited: false
        },
        
        openPaymentModal: false,
        openSplitBillModal: false,
        openNonRevenueModal: false,
        openReceiptModal: false,
        openCloseShiftModal: false,
        openShiftModal: false,
        openProductDetailModal: false,
        selectedProduct: null,
        detailQty: 1,
        detailTemp: 'Hot',
        detailExtraShots: 0,
        extraShotPrice: 6000,
        detailSweetness: 'Normal (100%)',
        selectedExtras: [],
        detailNotes: '',
        selectedModifiers: [],

        paymentMethod: 'cash',
        paidAmount: 130000,

        // Midtrans Virtual Account Properties
        vaBank: 'bca',
        vaOrderNumber: null,
        vaNumber: '',
        vaBillerCode: null,
        vaLoading: false,
        vaSettled: false,
        vaSimulating: false,
        vaCopied: false,
        vaPollInterval: null,

        // Midtrans Dynamic QRIS Properties
        qrisData: null,
        qrisLoading: false,
        qrisError: '',
        qrisSettled: false,
        qrisCurrentOrderNumber: null,
        qrisCountdown: 300,
        qrisPollInterval: null,
        qrisTimerInterval: null,
        qrisSimulating: false,

        nonRevenueReason: 'Tester Cupping / QC Roastery',
        splitPersonCount: 2,

        receiptWidth: '58',
        receiptData: {
            order_number: 'KAF-001',
            created_at: '',
            cashier: 'Rizky Ramadhan',
            items: [],
            subtotal: 0,
            discount: 0,
            tax_pb1: 0,
            total: 0,
            paid_amount: 0,
            change_amount: 0
        },

        denominations: [
            { denom: 100000, label: '100.000', count: 35 },
            { denom: 50000, label: '50.000', count: 14 },
            { denom: 20000, label: '20.000', count: 5 },
            { denom: 10000, label: '10.000', count: 5 },
            { denom: 5000, label: '5.000', count: 0 },
            { denom: 2000, label: '2.000', count: 0 },
            { denom: 1000, label: '1.000', count: 0 },
        ],
        supervisorPin: '',

        // Draggable Order Pane Width
        cartWidth: parseInt(localStorage.getItem('kafeina_pos_cart_width') || '400', 10),
        isResizingCart: false,

        startCartResize(e) {
            this.isResizingCart = true;
            document.body.style.userSelect = 'none';
            document.body.style.cursor = 'col-resize';
            
            const cartEl = document.getElementById('pos-order-cart-pane');
            const cartRight = cartEl ? cartEl.getBoundingClientRect().right : window.innerWidth;
            
            const onPointerMove = (event) => {
                if (!this.isResizingCart) return;
                const newWidth = cartRight - event.clientX;
                const maxAllowed = Math.max(450, window.innerWidth - 380);
                this.cartWidth = Math.max(320, Math.min(Math.round(newWidth), maxAllowed));
            };

            const onPointerUp = () => {
                if (this.isResizingCart) {
                    this.isResizingCart = false;
                    document.body.style.userSelect = '';
                    document.body.style.cursor = '';
                    localStorage.setItem('kafeina_pos_cart_width', this.cartWidth.toString());
                    window.removeEventListener('pointermove', onPointerMove);
                    window.removeEventListener('pointerup', onPointerUp);
                }
            };

            window.addEventListener('pointermove', onPointerMove);
            window.addEventListener('pointerup', onPointerUp);
        },

        resetCartWidth() {
            this.cartWidth = 400;
            localStorage.setItem('kafeina_pos_cart_width', '400');
        },

        // Offline Resilience Queue
        isOnline: navigator.onLine,
        offlineQueue: JSON.parse(localStorage.getItem('kafeina_pos_offline_queue') || '[]'),

        // Automated Shift Scheduling & Live Timer
        currentShiftSchedule: {
            name: 'Shift 1 (Pagi - Siang)',
            hours: '07:00 – 15:00',
            startHour: '07:00',
            endHour: '15:00',
            nextShiftName: 'Shift 2 (Sore - Malam)'
        },
        shiftRemainingTime: '04j 28m 32d',
        autoShiftTimer: null,
        autoShiftClosedNotified: false,

        initShiftSchedule() {
            this.updateShiftScheduleAndTimer();
            this.autoShiftTimer = setInterval(() => {
                this.updateShiftScheduleAndTimer();
            }, 1000);
        },

        updateShiftScheduleAndTimer() {
            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            const currentSecond = now.getSeconds();

            // Determine active shift by hours:
            // Shift 1: 07:00 - 15:00
            // Shift 2: 15:00 - 23:00
            // Shift 3 (Night/Overnight): 23:00 - 07:00
            let targetHour = 15;
            if (currentHour >= 7 && currentHour < 15) {
                this.currentShiftSchedule = {
                    name: 'Shift 1 (Pagi - Siang)',
                    hours: '07:00 – 15:00',
                    startHour: '07:00',
                    endHour: '15:00',
                    nextShiftName: 'Shift 2 (Sore - Malam)'
                };
                targetHour = 15;
            } else if (currentHour >= 15 && currentHour < 23) {
                this.currentShiftSchedule = {
                    name: 'Shift 2 (Sore - Malam)',
                    hours: '15:00 – 23:00',
                    startHour: '15:00',
                    endHour: '23:00',
                    nextShiftName: 'Shift 1 (Pagi - Siang)'
                };
                targetHour = 23;
            } else {
                this.currentShiftSchedule = {
                    name: 'Shift Malam (Overnight)',
                    hours: '23:00 – 07:00',
                    startHour: '23:00',
                    endHour: '07:00',
                    nextShiftName: 'Shift 1 (Pagi - Siang)'
                };
                targetHour = currentHour >= 23 ? 31 : 7; // next day 7 AM
            }

            // Calculate remaining seconds
            const nowSec = (currentHour * 3600) + (currentMinute * 60) + currentSecond;
            const targetSec = targetHour * 3600;
            let diffSec = targetSec - nowSec;
            if (diffSec < 0) diffSec += 86400;

            const hoursLeft = Math.floor(diffSec / 3600);
            const minsLeft = Math.floor((diffSec % 3600) / 60);
            const secsLeft = diffSec % 60;

            this.shiftRemainingTime = String(hoursLeft).padStart(2, '0') + 'j ' + 
                                     String(minsLeft).padStart(2, '0') + 'm ' + 
                                     String(secsLeft).padStart(2, '0') + 'd';

            // Check if shift reached 0 (exact turnover)
            if (diffSec <= 1 && !this.autoShiftClosedNotified) {
                this.triggerAutoShiftClose();
            }
        },

        triggerAutoShiftClose() {
            this.autoShiftClosedNotified = true;
            console.log('Jadwal shift berakhir: Melakukan rekonsiliasi dan penutupan shift otomatis...');
            setTimeout(() => { this.autoShiftClosedNotified = false; }, 5000);
        },

        init() {
            this.initShiftSchedule();
            window.addEventListener('online', () => {
                this.isOnline = true;
                this.syncOfflineOrders();
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
            });

            window.addEventListener('keydown', (e) => {
                if (e.key === 'F4') {
                    e.preventDefault();
                    if (!this.openPaymentModal && this.cart.length > 0) {
                        this.openCheckoutModal();
                    }
                }
            });

            if (new URLSearchParams(window.location.search).has('test_qris')) {
                setTimeout(() => {
                    this.cart = [{
                        id: 'PRD-ESP-02',
                        name: 'Cafe Latte Velvet',
                        price: 38000,
                        qty: 2,
                        image: '/images/pos/prd-esp-02-latte.png'
                    }];
                    this.paymentMethod = 'qris';
                    this.openCheckoutModal();
                }, 400);
            }

            if (new URLSearchParams(window.location.search).has('test_modal')) {
                setTimeout(() => {
                    const prod = this.products.find(p => p.id === 'PRD-ESP-01') || this.products[0];
                    this.openProductDetail(prod);
                }, 400);
            }
        },

        getCategoryBadgeClass(category) {
            const map = {
                'espresso': 'bg-amber-50 text-amber-800 border-amber-200/70',
                'manual_brew': 'bg-emerald-50 text-emerald-800 border-emerald-200/70',
                'retail': 'bg-rose-50 text-rose-800 border-rose-200/70',
                'tea_bev': 'bg-cyan-50 text-cyan-800 border-cyan-200/70',
                'food': 'bg-orange-50 text-orange-800 border-orange-200/70',
                'merch': 'bg-indigo-50 text-indigo-800 border-indigo-200/70'
            };
            return map[category] || 'bg-neutral-100 text-neutral-800 border-neutral-200';
        },

        getCategoryDotClass(category) {
            const map = {
                'espresso': 'bg-amber-500',
                'manual_brew': 'bg-emerald-500',
                'retail': 'bg-rose-500',
                'tea_bev': 'bg-cyan-500',
                'food': 'bg-orange-500',
                'merch': 'bg-indigo-500'
            };
            return map[category] || 'bg-neutral-500';
        },

        formatCategoryName(category) {
            const map = {
                'espresso': 'Espresso',
                'manual_brew': 'Filter',
                'retail': 'Beans',
                'tea_bev': 'Artisan Tea',
                'food': 'Pastry',
                'merch': 'Merch'
            };
            return map[category] || category.replace('_', ' ');
        },

        getCategoryProducts(catId) {
            let list = this.products.filter(p => p.category === catId);
            if (this.searchQuery && this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(p => 
                    p.name.toLowerCase().includes(q) || 
                    (p.image_tag && p.image_tag.toLowerCase().includes(q))
                );
            }
            return list;
        },

        get totalFilteredCount() {
            if (!this.searchQuery || !this.searchQuery.trim()) {
                return this.products.length;
            }
            const q = this.searchQuery.toLowerCase();
            return this.products.filter(p => 
                p.name.toLowerCase().includes(q) || 
                (p.image_tag && p.image_tag.toLowerCase().includes(q))
            ).length;
        },

        get filteredProducts() {
            if (!this.searchQuery || !this.searchQuery.trim()) {
                return this.products;
            }
            const q = this.searchQuery.toLowerCase();
            return this.products.filter(p => 
                p.name.toLowerCase().includes(q) || 
                (p.image_tag && p.image_tag.toLowerCase().includes(q))
            );
        },

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        },

        get discountAmount() {
            if (!this.appliedPromo || this.subtotal <= 0) return 0;
            return this.calculatePromoDiscount(this.appliedPromo, this.subtotal);
        },

        calculatePromoDiscount(promo, subtotal) {
            let disc = 0;
            if (promo.discount_type === 'PERCENTAGE') {
                disc = subtotal * (parseFloat(promo.discount_value) / 100);
                if (promo.max_discount_amount && disc > parseFloat(promo.max_discount_amount)) {
                    disc = parseFloat(promo.max_discount_amount);
                }
            } else {
                disc = parseFloat(promo.discount_value);
            }
            return Math.min(disc, subtotal);
        },

        async applyRedeemCode(codeString = null) {
            const codeToApply = (codeString || this.promoCodeInput || '').trim().toUpperCase();
            this.promoError = '';
            
            if (!codeToApply) {
                this.promoError = 'Silakan ketik kode promo.';
                return;
            }

            if (this.subtotal <= 0) {
                this.promoError = 'Tambahkan menu terlebih dahulu sebelum menggunakan kode promo.';
                return;
            }

            try {
                const response = await fetch("{{ route('pos.redeem.validate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        code: codeToApply,
                        subtotal: this.subtotal
                    })
                });

                const data = await response.json();

                if (response.ok && data.valid) {
                    this.appliedPromo = data.redeem_code;
                    this.promoCodeInput = '';
                    this.promoError = '';
                    this.openPromoModal = false;
                } else {
                    this.promoError = data.message || 'Kode promo tidak valid.';
                }
            } catch (e) {
                const local = this.redeemCodes.find(c => c.code.toUpperCase() === codeToApply);
                if (!local) {
                    this.promoError = 'Kode promo [' + codeToApply + '] tidak ditemukan.';
                    return;
                }
                if (!local.is_active) {
                    this.promoError = 'Kode promo sedang tidak aktif.';
                    return;
                }
                if (local.max_redemptions !== null && local.times_redeemed >= local.max_redemptions) {
                    this.promoError = 'Kuota penukaran kode [' + local.code + '] sudah habis.';
                    return;
                }
                if (this.subtotal < parseFloat(local.min_order_amount || 0)) {
                    this.promoError = 'Subtotal pesanan belum mencapai minimal belanja Rp ' + this.formatNumber(local.min_order_amount);
                    return;
                }
                this.appliedPromo = local;
                this.promoCodeInput = '';
                this.promoError = '';
                this.openPromoModal = false;
            }
        },

        removeRedeemCode() {
            this.appliedPromo = null;
            this.promoCodeInput = '';
            this.promoError = '';
        },

        async saveNewPromoCode() {
            if (!this.newPromo.code || !this.newPromo.code.trim()) {
                alert('Kode promo wajib diisi.');
                return;
            }

            const payload = {
                code: this.newPromo.code.trim().toUpperCase(),
                description: this.newPromo.description,
                discount_type: this.newPromo.discount_type,
                discount_value: parseFloat(this.newPromo.discount_value) || 0,
                min_order_amount: parseFloat(this.newPromo.min_order_amount) || 0,
                max_discount_amount: this.newPromo.discount_type === 'PERCENTAGE' && this.newPromo.max_discount_amount ? parseFloat(this.newPromo.max_discount_amount) : null,
                max_redemptions: this.newPromo.is_unlimited ? null : (parseInt(this.newPromo.max_redemptions) || null),
                is_active: true
            };

            try {
                const res = await fetch("{{ route('pos.redeem.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success && data.redeem_code) {
                    const existingIdx = this.redeemCodes.findIndex(c => c.code === data.redeem_code.code);
                    if (existingIdx > -1) {
                        this.redeemCodes[existingIdx] = data.redeem_code;
                    } else {
                        this.redeemCodes.unshift(data.redeem_code);
                    }
                    alert(data.message || 'Kode promo berhasil disimpan!');
                    this.promoTab = 'list';
                    this.newPromo = {
                        code: '',
                        description: '',
                        discount_type: 'PERCENTAGE',
                        discount_value: 10,
                        min_order_amount: 0,
                        max_discount_amount: null,
                        max_redemptions: 50,
                        is_unlimited: false
                    };
                } else {
                    alert(data.message || 'Gagal menyimpan kode promo.');
                }
            } catch (e) {
                alert('Gagal menghubungi server untuk menyimpan kode promo.');
            }
        },

        taxPb1Rate: @json($taxPb1Rate ?? 11.0),
        serviceChargeRate: @json($serviceChargeRate ?? 0.0),

        get taxableSubtotal() {
            return Math.max(0, this.subtotal - this.discountAmount);
        },

        get serviceCharge() {
            return Math.round(this.taxableSubtotal * ((Number(this.serviceChargeRate) || 0) / 100));
        },

        get taxPb1() {
            return Math.round((this.taxableSubtotal + this.serviceCharge) * ((Number(this.taxPb1Rate) || 0) / 100));
        },

        get grandTotal() {
            return this.taxableSubtotal + this.serviceCharge + this.taxPb1;
        },

        formatNumber(val) {
            if (!val || isNaN(val)) return '0';
            return new Intl.NumberFormat('id-ID').format(Math.round(val));
        },

        getItemImage(item) {
            if (item && item.image) return item.image;
            const p = this.products.find(x => x.id === item.id);
            if (p && p.image) return p.image;
            return '/images/pos/' + (item.id ? item.id.toLowerCase() : 'prd-esp-01') + '.png';
        },

        isCoffeeProduct(product) {
            if (!product) return false;
            return Boolean(product.can_custom_shot || product.category === 'espresso' || product.id === 'PRD-TEA-05');
        },

        get detailExtrasTotal() {
            return this.selectedExtras.reduce((sum, item) => sum + (Number(item.price) || 0), 0);
        },

        get detailExtraShotsTotal() {
            return (Number(this.detailExtraShots) || 0) * this.extraShotPrice;
        },

        get detailUnitPrice() {
            if (!this.selectedProduct) return 0;
            return Number(this.selectedProduct.price || 0) + this.detailExtrasTotal + this.detailExtraShotsTotal;
        },

        get detailTotalPrice() {
            return this.detailUnitPrice * this.detailQty;
        },

        openProductDetail(product) {
            this.selectedProduct = product;
            this.detailQty = 1;
            this.detailTemp = (product.temperature && product.temperature.length > 0) ? product.temperature[0] : 'Hot';
            this.detailExtraShots = 0;
            this.detailSweetness = 'Normal (100%)';
            this.selectedExtras = [];
            this.detailNotes = '';
            this.selectedModifiers = [];
            this.openProductDetailModal = true;
        },

        toggleExtra(extra) {
            const idx = this.selectedExtras.findIndex(e => e.id === extra.id);
            const updated = [...this.selectedExtras];
            if (idx > -1) {
                updated.splice(idx, 1);
            } else {
                updated.push({
                    id: extra.id,
                    name: extra.name,
                    price: Number(extra.price) || 0,
                    category: extra.category || 'Ekstra'
                });
            }
            this.selectedExtras = updated;
        },

        isExtraSelected(extraId) {
            return this.selectedExtras.some(e => e.id === extraId);
        },

        setExtraShots(val) {
            this.detailExtraShots = Math.max(0, Math.min(5, val));
        },

        incrementExtraShots() {
            if (this.detailExtraShots < 5) {
                this.detailExtraShots++;
            }
        },

        decrementExtraShots() {
            if (this.detailExtraShots > 0) {
                this.detailExtraShots--;
            }
        },

        toggleModifier(modifier) {
            const idx = this.selectedModifiers.indexOf(modifier);
            if (idx > -1) {
                this.selectedModifiers.splice(idx, 1);
            } else {
                this.selectedModifiers.push(modifier);
            }
        },

        addDetailToCart() {
            if (!this.selectedProduct) return;

            const modifiersList = [];
            if (this.isCoffeeProduct(this.selectedProduct) && this.detailExtraShots > 0) {
                modifiersList.push(`+${this.detailExtraShots} Extra Shot (+Rp ${this.formatNumber(this.detailExtraShotsTotal)})`);
            }
            this.selectedExtras.forEach(e => {
                modifiersList.push(`${e.name} (+Rp ${this.formatNumber(e.price)})`);
            });
            if (this.selectedProduct.category === 'espresso' || this.selectedProduct.category === 'tea_bev') {
                if (this.detailSweetness && this.detailSweetness !== 'Normal (100%)') {
                    modifiersList.push(this.detailSweetness);
                }
            }
            if (this.detailNotes && this.detailNotes.trim()) {
                modifiersList.push(this.detailNotes.trim());
            }
            const combinedNotes = modifiersList.join(' • ');

            // Unique key to distinguish items with different customized options in cart
            const extrasIds = this.selectedExtras.map(e => e.id).sort().join(',');
            const itemKey = `${this.selectedProduct.id}|${this.detailTemp}|${this.detailExtraShots}|${extrasIds}|${this.detailSweetness}|${this.detailNotes.trim()}`;

            const existing = this.cart.find(i => i.itemKey === itemKey);
            if (existing) {
                existing.qty += this.detailQty;
            } else {
                this.cart.push({
                    itemKey: itemKey,
                    id: this.selectedProduct.id,
                    name: this.selectedProduct.name,
                    basePrice: this.selectedProduct.price,
                    price: this.detailUnitPrice,
                    qty: this.detailQty,
                    image: this.selectedProduct.image,
                    temp: this.detailTemp,
                    extraShots: this.detailExtraShots,
                    extraShotsTotal: this.detailExtraShotsTotal,
                    selectedExtras: JSON.parse(JSON.stringify(this.selectedExtras)),
                    sweetness: this.detailSweetness,
                    notes: combinedNotes,
                    modifiersList: modifiersList
                });
            }
            this.openProductDetailModal = false;
        },

        addToCart(product) {
            // Klik (+) juga memunculkan pop up detail / kustomisasi produk
            this.openProductDetail(product);
        },

        updateQty(index, delta) {
            if (!this.cart[index]) return;
            this.cart[index].qty += delta;
            if (this.cart[index].qty <= 0) {
                this.cart.splice(index, 1);
            }
        },

        removeItem(index) {
            this.cart.splice(index, 1);
        },

        setDiscount(pct) {
            this.discountPercent = pct;
        },

        handleKeypad(key) {
            let str = String(this.paidAmount || '0');
            if (key === 'C') {
                this.paidAmount = 0;
            } else if (key === '00') {
                this.paidAmount = Number(str + '00');
            } else {
                if (this.paidAmount === 0 || this.paidAmount === this.grandTotal) {
                    this.paidAmount = Number(key);
                } else {
                    this.paidAmount = Number(str + key);
                }
            }
        },

        validateOrderInputs() {
            const name = (this.customerName || '').trim();
            const table = (this.tableNumber || '').trim();

            if (this.orderType === 'Dine In') {
                if (!name && !table) {
                    this.validationError = 'Wajib mengisi Atas Nama Pelanggan dan Nomor Meja.';
                    this.$refs.customerNameDineIn?.focus();
                    return false;
                }
                if (!name) {
                    this.validationError = 'Wajib mengisi Atas Nama Pelanggan.';
                    this.$refs.customerNameDineIn?.focus();
                    return false;
                }
                if (!table) {
                    this.validationError = 'Wajib mengisi Nomor Meja untuk pesanan Dine In.';
                    this.$refs.tableNumberDineIn?.focus();
                    return false;
                }
            } else if (this.orderType === 'Take Away') {
                if (!name) {
                    this.validationError = 'Wajib mengisi Atas Nama Pelanggan untuk Take Away.';
                    this.$refs.customerNameTakeAway?.focus();
                    return false;
                }
            }
            this.validationError = '';
            return true;
        },

        openSplitBill() {
            if (this.cart.length === 0) return;
            if (!this.validateOrderInputs()) return;
            this.openSplitBillModal = true;
        },

        openCheckoutModal() {
            if (this.cart.length === 0) return;
            if (!this.validateOrderInputs()) return;

            this.openPaymentModal = true;
            if (this.paymentMethod === 'qris') {
                this.initQrisPayment();
            } else {
                this.paidAmount = this.grandTotal;
            }
        },

        closePaymentModal() {
            this.openPaymentModal = false;
            this.stopQrisPolling();
            this.stopVaPolling();
        },

        switchPaymentMethod(method) {
            this.paymentMethod = method;
            this.stopQrisPolling();
            this.stopVaPolling();

            if (method === 'qris') {
                this.initQrisPayment();
            } else if (method === 'transfer') {
                this.initVaPayment();
            } else {
                this.paidAmount = this.grandTotal;
            }
        },

        async initQrisPayment() {
            if (this.cart.length === 0 || this.grandTotal <= 0) return;

            if (!this.qrisCurrentOrderNumber || this.qrisSettled) {
                this.qrisCurrentOrderNumber = 'KAF-' + Date.now().toString().slice(-6);
            }

            this.qrisLoading = true;
            this.qrisError = '';
            this.qrisSettled = false;
            this.stopQrisPolling();

            try {
                const response = await fetch("{{ route('pos.qris.generate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        amount: this.grandTotal,
                        order_number: this.qrisCurrentOrderNumber,
                        items: this.cart.map(c => ({ id: c.id, name: c.name, price: c.price, quantity: c.qty })),
                        customer_name: this.tableNumber ? ('Meja ' + this.tableNumber) : 'Walk-in Customer'
                    })
                });

                const data = await response.json();
                if (data.status === 'success' || data.success) {
                    this.qrisData = data;
                    this.qrisCurrentOrderNumber = data.order_number || this.qrisCurrentOrderNumber;
                    this.startQrisCountdown();
                    this.startQrisPolling();
                } else {
                    this.qrisError = data.message || 'Gagal menghasilkan QRIS Midtrans.';
                }
            } catch (err) {
                console.error('QRIS Init Error:', err);
                this.qrisError = 'Gagal menghubungi server QRIS Midtrans. Periksa koneksi internet atau server lokal.';
            } finally {
                this.qrisLoading = false;
            }
        },

        startQrisCountdown() {
            clearInterval(this.qrisTimerInterval);
            this.qrisCountdown = 300; // 5 minutes
            this.qrisTimerInterval = setInterval(() => {
                if (this.qrisCountdown > 0) {
                    this.qrisCountdown--;
                } else {
                    clearInterval(this.qrisTimerInterval);
                    this.stopQrisPolling();
                    this.qrisError = 'Waktu pembayaran QRIS telah kedaluwarsa. Silakan buat ulang QRIS.';
                }
            }, 1000);
        },

        formatTimer(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },

        startQrisPolling() {
            clearInterval(this.qrisPollInterval);
            if (!this.qrisCurrentOrderNumber) return;

            this.qrisPollInterval = setInterval(async () => {
                await this.pollQrisStatusOnce();
            }, 2500);
        },

        async pollQrisStatusOnce() {
            if (!this.qrisCurrentOrderNumber || this.qrisSettled) return;

            try {
                const res = await fetch(`/pos/qris/status/${this.qrisCurrentOrderNumber}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await res.json();

                if (result.status === 'settlement' || result.status === 'paid' || result.transaction_status === 'settlement') {
                    this.handleQrisSuccess(result);
                }
            } catch (e) {
                console.warn('QRIS status poll error:', e);
            }
        },

        stopQrisPolling() {
            if (this.qrisPollInterval) {
                clearInterval(this.qrisPollInterval);
                this.qrisPollInterval = null;
            }
            if (this.qrisTimerInterval) {
                clearInterval(this.qrisTimerInterval);
                this.qrisTimerInterval = null;
            }
        },

        async simulateQrisSuccess() {
            if (!this.qrisCurrentOrderNumber) return;
            this.qrisSimulating = true;
            try {
                const res = await fetch("{{ route('pos.qris.simulate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({ order_number: this.qrisCurrentOrderNumber })
                });
                const data = await res.json();
                if (data.success || data.status === 'settlement') {
                    this.handleQrisSuccess(data);
                } else {
                    alert('Simulasi gagal: ' + (data.message || 'Status tidak valid'));
                }
            } catch (e) {
                alert('Gagal menghubungi endpoint simulasi.');
            } finally {
                this.qrisSimulating = false;
            }
        },

        handleQrisSuccess(statusData) {
            this.stopQrisPolling();
            this.qrisSettled = true;
            this.triggerSuccessChime();

            setTimeout(() => {
                this.processCheckout('QRIS');
            }, 1200);
        },

        copyQrString() {
            const urlToCopy = this.qrisData?.qr_url || this.qrisData?.qr_string;
            if (urlToCopy) {
                navigator.clipboard.writeText(urlToCopy);
                alert('URL Gambar QR berhasil disalin!\n\nSilakan paste ke kolom "QR Code Image Url" di Midtrans Simulator, lalu klik "Scan QR".');
            } else {
                alert('Data QR belum tersedia.');
            }
        },

        openMidtransSimulator() {
            const urlToCopy = this.qrisData?.qr_url || this.qrisData?.qr_string;
            if (urlToCopy) {
                navigator.clipboard.writeText(urlToCopy);
            }
            window.open('https://simulator.sandbox.midtrans.com/v2/qris/index', '_blank');
        },

        triggerSuccessChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;
                [523.25, 659.25, 783.99].forEach((freq, idx) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, now + (idx * 0.08));
                    gain.gain.setValueAtTime(0.15, now + (idx * 0.08));
                    gain.gain.exponentialRampToValueAtTime(0.001, now + (idx * 0.08) + 0.4);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + (idx * 0.08));
                    osc.stop(now + (idx * 0.08) + 0.45);
                });
            } catch (e) {}
        },

        stopVaPolling() {
            if (this.vaPollInterval) {
                clearInterval(this.vaPollInterval);
                this.vaPollInterval = null;
            }
        },

        async initVaPayment() {
            if (this.cart.length === 0 || this.grandTotal <= 0) return;

            if (!this.vaOrderNumber || this.vaSettled) {
                this.vaOrderNumber = 'KAF-' + Date.now().toString().slice(-6);
            }

            this.vaLoading = true;
            this.vaSettled = false;
            this.stopVaPolling();

            try {
                const response = await fetch("{{ route('pos.va.generate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        order_number: this.vaOrderNumber,
                        amount: this.grandTotal,
                        bank: this.vaBank,
                        customer_name: this.customerName || 'Tamu Kafeina'
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.vaNumber = data.va_number;
                    this.vaBillerCode = data.biller_code;
                    this.startVaPolling();
                } else {
                    alert('Gagal menerbitkan nomor VA: ' + (data.message || 'Error'));
                }
            } catch (err) {
                console.error('VA Init Error:', err);
            } finally {
                this.vaLoading = false;
            }
        },

        changeVaBank(bankCode) {
            this.vaBank = bankCode;
            this.initVaPayment();
        },

        startVaPolling() {
            this.stopVaPolling();
            this.vaPollInterval = setInterval(async () => {
                if (!this.vaOrderNumber || this.vaSettled) {
                    this.stopVaPolling();
                    return;
                }
                try {
                    const res = await fetch(`/pos/va/status/${this.vaOrderNumber}`);
                    if (res.ok) {
                        const statusData = await res.json();
                        if (statusData.paid || statusData.status === 'settlement') {
                            this.handleVaSettled();
                        }
                    }
                } catch (e) {}
            }, 3000);
        },

        async simulateVaPayment() {
            if (!this.vaOrderNumber || this.vaSettled || this.vaSimulating) return;
            this.vaSimulating = true;
            try {
                const res = await fetch("{{ route('pos.va.simulate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ order_number: this.vaOrderNumber })
                });
                const data = await res.json();
                if (data.success || data.paid) {
                    this.handleVaSettled();
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.vaSimulating = false;
            }
        },

        handleVaSettled() {
            this.vaSettled = true;
            this.stopVaPolling();
            this.triggerSuccessChime();
            setTimeout(() => {
                this.processCheckout('TRANSFER');
            }, 1200);
        },

        copyVaNumber() {
            if (this.vaNumber) {
                navigator.clipboard.writeText(this.vaNumber);
                this.vaCopied = true;
                setTimeout(() => {
                    this.vaCopied = false;
                }, 2000);
            }
        },

        getMidtransVaSimulatorUrl() {
            const bank = (this.vaBank || 'bca').toLowerCase();
            if (bank === 'mandiri') {
                return 'https://simulator.sandbox.midtrans.com/mandiri/bill/index';
            }
            return `https://simulator.sandbox.midtrans.com/${bank}/va/index`;
        },

        async processCheckout(forcedMethod = null) {
            if (!this.validateOrderInputs()) {
                this.openPaymentModal = false;
                return;
            }

            const method = (forcedMethod || this.paymentMethod).toUpperCase();
            let orderNum = 'KAF-' + Date.now().toString().slice(-6);
            if (method === 'QRIS' && this.qrisCurrentOrderNumber) {
                orderNum = this.qrisCurrentOrderNumber;
            } else if ((method === 'TRANSFER' || method === 'VA' || method === 'BANK_TRANSFER') && this.vaOrderNumber) {
                orderNum = this.vaOrderNumber;
            }

            let paymentRef = null;
            if (method === 'QRIS') {
                paymentRef = this.qrisCurrentOrderNumber;
            } else if (method === 'TRANSFER' || method === 'VA' || method === 'BANK_TRANSFER') {
                paymentRef = `${this.vaBank.toUpperCase()} VA • ${this.vaNumber || '-'}`;
            }

            const orderPayload = {
                order_number: orderNum,
                created_at: new Date().toLocaleString('id-ID') + ' WIB',
                branch_code: this.shiftData.outlet_code || 'OUT-SENO',
                branch: this.shiftData.branch_slug || 'senopati',
                cashier: this.shiftData.cashier_name || 'Rizky Ramadhan',
                order_type: this.orderType,
                table_number: this.orderType === 'Dine In' ? (this.tableNumber ? this.tableNumber.trim() : '-') : '-',
                customer_name: this.customerName ? this.customerName.trim() : 'Tamu Kafeina',
                items: JSON.parse(JSON.stringify(this.cart)),
                subtotal: this.subtotal,
                discount: this.discountAmount,
                discount_amount: this.discountAmount,
                redeem_code: this.appliedPromo ? this.appliedPromo.code : null,
                service_charge: this.serviceCharge,
                service_charge_rate: this.serviceChargeRate,
                tax_pb1: this.taxPb1,
                tax_pb1_rate: this.taxPb1Rate,
                total: this.grandTotal,
                payment_method: method,
                payment_reference: paymentRef,
                va_bank: this.vaBank,
                va_number: this.vaNumber,
                paid_amount: method === 'CASH' ? this.paidAmount : this.grandTotal,
                change_amount: method === 'CASH' ? Math.max(0, this.paidAmount - this.grandTotal) : 0
            };

            this.stopQrisPolling();
            this.stopVaPolling();
            this.receiptData = orderPayload;
            this.openPaymentModal = false;
            this.openReceiptModal = true;

            if (method === 'CASH') {
                this.triggerDrawerKick();
            }

            // Offline resilience check
            if (!this.isOnline) {
                this.offlineQueue.push(orderPayload);
                localStorage.setItem('kafeina_pos_offline_queue', JSON.stringify(this.offlineQueue));
                alert('MODE OFFLINE: Transaksi disimpan secara lokal. Akan otomatis disinkronkan ke server saat online.');
            } else {
                try {
                    await fetch("{{ route('pos.checkout') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify(orderPayload)
                    });
                } catch (e) {
                    // Fallback to offline queue if network fails
                    this.offlineQueue.push(orderPayload);
                    localStorage.setItem('kafeina_pos_offline_queue', JSON.stringify(this.offlineQueue));
                }
            }

            this.cart = [];
            this.customerName = '';
            this.tableNumber = '';
            this.validationError = '';
            this.appliedPromo = null;
            this.promoCodeInput = '';
            this.qrisData = null;
            this.qrisSettled = false;
            this.qrisCurrentOrderNumber = null;
            this.vaNumber = '';
            this.vaOrderNumber = null;
            this.vaSettled = false;
        },

        async processNonRevenueCheckout() {
            const payload = {
                items: this.cart,
                payment_method: 'non_revenue',
                paid_amount: 0,
                order_type: 'Non-Revenue',
                customer_name: this.customerName ? this.customerName.trim() : 'Internal Bar / QC',
                table_number: '-',
                non_revenue_reason: this.nonRevenueReason || 'CALIBRATION',
                branch_code: this.shiftData.outlet_code || ''
            };

            try {
                const res = await fetch("{{ route('pos.checkout') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success && data.order) {
                    this.receiptData = data.order;
                    this.openNonRevenueModal = false;
                    this.openReceiptModal = true;
                    this.cart = [];
                    return;
                }
                alert(data.message || 'Gagal mencatat transaksi Non-Revenue.');
            } catch (e) {
                alert('Terjadi kesalahan jaringan saat mencatat transaksi Non-Revenue.');
            }
        },

        async syncOfflineOrders() {
            if (this.offlineQueue.length === 0) return;
            const count = this.offlineQueue.length;
            try {
                for (const order of this.offlineQueue) {
                    await fetch("{{ route('pos.checkout') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify(order)
                    });
                }
                this.offlineQueue = [];
                localStorage.removeItem('kafeina_pos_offline_queue');
                alert('Sinkronisasi Berhasil: ' + count + ' transaksi offline telah tersimpan ke server.');
            } catch (e) {
                console.error('Sinkronisasi offline gagal:', e);
            }
        },

        triggerDrawerKick() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.21);
            } catch (e) {}
        },

        printReceipt() {
            window.print();
        },

        calculateTotalPhysicalCash() {
            return this.denominations.reduce((sum, item) => sum + (item.denom * (item.count || 0)), 0);
        },

        getDiscrepancy() {
            const expected = Number(this.shiftData.expected_physical_cash || 0);
            return this.calculateTotalPhysicalCash() - expected;
        },

        async submitShiftClosing() {
            const physicalCash = this.calculateTotalPhysicalCash();
            const expectedCash = Number(this.shiftData.expected_physical_cash || 0);

            try {
                const res = await fetch("{{ route('pos.shift.close') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        branch_code: this.shiftData.outlet_code || '',
                        total_physical_cash: physicalCash,
                        system_cash_expected: expectedCash,
                        supervisor_pin: this.supervisorPin || '',
                        notes: this.shiftNotes || ''
                    })
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message || 'Sesi shift kasir berhasil ditutup.');
                    this.shiftData.is_open = false;
                    this.supervisorPin = '';
                    this.openCloseShiftModal = false;
                } else {
                    alert(data.message || 'Gagal menutup sesi shift kasir.');
                }
            } catch (e) {
                alert('Terjadi kesalahan jaringan saat menutup shift kasir.');
            }
        }
    };
}
</script>
@endpush
