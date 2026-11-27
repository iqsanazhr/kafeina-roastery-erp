<!DOCTYPE html>
<html lang="id" class="h-full bg-[#fafafa] text-neutral-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | KAFEINA ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 bg-[#fafafa] font-sans">
    <div class="max-w-md w-full bg-white border border-[#eaeaea] rounded-2xl p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] text-center">
        <!-- Shield Lock Icon -->
        <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center mx-auto mb-5 text-rose-600 shadow-2xs">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono font-semibold bg-rose-50 text-rose-700 border border-rose-200/80 mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
            HTTP 403 • FORBIDDEN
        </div>

        <h1 class="text-xl font-bold tracking-tight text-neutral-900 mb-2">
            Akses Ditolak
        </h1>
        <p class="text-xs text-neutral-500 leading-relaxed mb-6">
            {{ $message ?? 'Akun Anda tidak memiliki izin operasional yang memadai untuk mengakses modul atau rute ini.' }}
        </p>

        <!-- Current Role vs Required Roles Card -->
        <div class="bg-neutral-50 border border-neutral-200/80 rounded-xl p-3.5 mb-6 text-left space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span class="text-neutral-500">Peran Akun Aktif:</span>
                <span class="font-medium text-neutral-900 font-mono bg-white px-2 py-0.5 rounded border border-neutral-200">
                    {{ $currentUser->role?->display_name ?? $userRole ?? 'Pengguna' }}
                </span>
            </div>
            @if(!empty($allowedRoles))
                <div class="flex items-center justify-between text-xs pt-1.5 border-t border-neutral-200/60">
                    <span class="text-neutral-500">Otoritas yang Dibutuhkan:</span>
                    <span class="font-medium text-neutral-700 font-mono text-[11px]">
                        {{ implode(', ', (array) $allowedRoles) }}
                    </span>
                </div>
            @endif
        </div>

        @php
            $resolvedUser = $currentUser ?? auth()->user();
            $authorizedHomeUrl = $resolvedUser ? $resolvedUser->getDefaultHomeRoute() : route('login');
        @endphp

        <!-- Action CTAs -->
        <div class="flex flex-col sm:flex-row gap-2.5">
            <a href="{{ $authorizedHomeUrl }}" 
               class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-neutral-900 hover:bg-black text-white text-xs font-medium transition-colors shadow-2xs">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Kembali ke Modul Utama
            </a>
            <a href="{{ route('login') }}" 
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 text-xs font-medium transition-colors">
                Beralih Akun
            </a>
        </div>
    </div>
</body>
</html>