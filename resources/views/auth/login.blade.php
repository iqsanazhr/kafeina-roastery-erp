<!DOCTYPE html>
<html lang="id" class="h-full bg-white text-neutral-900 antialiased selection:bg-black selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - KAFEINA ERP</title>

    <!-- Fonts (Geist & Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800&family=Geist+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full h-screen overflow-hidden bg-white font-sans">

    <div class="h-full flex flex-col lg:flex-row">
        
        <!-- ============================================================== -->
        <!-- SISI KIRI: FORM LOGIN BERSIH & RATA TENGAH (COMPACT CENTERED)  -->
        <!-- ============================================================== -->
        <div class="w-full lg:w-[380px] xl:w-[400px] flex flex-col justify-center h-full overflow-y-auto bg-white border-r border-neutral-200/70 px-6 py-8 sm:px-8 lg:px-9 z-10 shrink-0">
            <div class="max-w-[320px] w-full mx-auto my-auto py-6">
                
                <!-- 1. Logo Brand Kafeina (Opsi 1: Monogram K Artisanal Roaster - Dark Badge) & Judul Form -->
                <div class="mb-8 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-neutral-950 text-white mb-4 shadow-xs border border-neutral-900/80">
                        <svg class="w-6 h-6" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Pillar Stem (Fondasi Arsitektur ERP) -->
                            <rect x="8" y="7" width="5" height="26" rx="2.5" fill="#FFFFFF"/>
                            <!-- Top Wing (Lekukan Biji Kopi Spesialti) -->
                            <path d="M16 19.5C16 13 22 8 29 8C29 15 23 20 16 19.5Z" fill="#FFFFFF"/>
                            <!-- Bottom Wing (Aliran Ekstraksi Amber) -->
                            <path d="M16 20.5C21 21 31 23 31 32C24 32 18 28 16 20.5Z" fill="#F97316"/>
                            <!-- Center Accent Crevice -->
                            <path d="M21 11.5C22.5 13.5 24 16 23.5 18" stroke="#0A0A0A" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-neutral-950 font-sans">
                        Masuk ke Sistem
                    </h2>
                    <p class="mt-2 text-xs text-neutral-500 leading-relaxed max-w-xs mx-auto">
                        Pusat kendali roastery, inventaris biji kopi, POS kasir cabang, dan analitik multi-cabang.
                    </p>
                </div>

                <!-- 2. Alert Status Sesi / Error -->
                @if(session('info'))
                    <div class="mb-4 p-3 rounded-xl bg-neutral-50 border border-neutral-200 text-xs text-neutral-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-neutral-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <!-- 3. Form Login Utama -->
                <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1.5">Email Pengguna</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="nama@kafeina.id"
                               class="w-full px-3.5 py-2.5 bg-neutral-50 hover:bg-neutral-100/60 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-xs font-sans text-neutral-900 focus:outline-none transition-colors placeholder-neutral-400">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-700 mb-1.5">Password</label>
                        <input type="password" name="password" required
                               placeholder="Masukkan kata sandi"
                               class="w-full px-3.5 py-2.5 bg-neutral-50 hover:bg-neutral-100/60 focus:bg-white border border-neutral-200 focus:border-neutral-900 rounded-xl text-xs font-sans text-neutral-900 focus:outline-none transition-colors placeholder-neutral-400">
                    </div>

                    <div class="flex items-center justify-between text-xs pt-0.5">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} class="rounded border-neutral-300 text-neutral-950 focus:ring-neutral-950">
                            <span class="text-neutral-600 text-xs">Ingat sesi login saya</span>
                        </label>
                    </div>

                    <button type="submit" 
                            class="w-full py-2.5 px-4 rounded-xl bg-neutral-950 hover:bg-neutral-800 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer active:scale-[0.99] flex items-center justify-center gap-2">
                        <span>Masuk ke Sistem Kafeina</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>

            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SISI KANAN: MURNI GAMBAR KOPI 4K DENGAN SUDUT ROUNDED (24PX)   -->
        <!-- ============================================================== -->
        <div class="hidden lg:flex flex-1 p-3 lg:p-4 bg-white items-stretch">
            <div class="relative w-full h-full rounded-2xl lg:rounded-3xl overflow-hidden shadow-xs border border-neutral-200/60 bg-neutral-950">
                <img src="{{ asset('images/login-concepts/concept-5-beans-harvest.jpg') }}" 
                     alt="Kafeina Specialty Coffee"
                     class="absolute inset-0 w-full h-full object-cover object-center">
            </div>
        </div>

    </div>

</body>
</html>
