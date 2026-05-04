<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'e-MTQ') }} @yield('title', '')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="grid-bg min-h-screen overflow-x-hidden">
    @php
        $sweetAlertPayload = [
            'status' => session('status'),
            'errors' => isset($errors) && $errors->any() ? $errors->all() : [],
        ];
    @endphp
    <script type="application/json" id="mtq-swal-payload">{!! json_encode($sweetAlertPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-20 top-0 h-72 w-72 rounded-full bg-cyan-400/10 blur-3xl"></div>
        <div class="absolute right-0 top-24 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/75 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-blue-600 text-lg font-black text-slate-950 shadow-lg shadow-cyan-500/30">
                    e
                </div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-200/80">e-MTQ</p>
                    <p class="text-xs text-slate-400">Realtime Qur'an Competition Suite</p>
                </div>
            </a>

            <nav class="hidden items-center gap-2 md:flex">
                <a href="{{ route('home') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 transition hover:bg-white/6 hover:text-white">Beranda</a>
                <a href="{{ route('dashboard') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 transition hover:bg-white/6 hover:text-white">Dashboard</a>
                @if (auth()->user()?->role === 'admin' || auth()->user()?->role === 'panitia')
                    <a href="{{ route('scoring') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 transition hover:bg-white/6 hover:text-white">Penilaian</a>
                @endif
            </nav>

            <div class="flex items-center gap-3">
                <div class="hidden rounded-full border border-white/10 bg-white/6 px-4 py-2 text-xs text-slate-300 lg:block">
                    <span class="font-semibold text-white">{{ auth()->user()?->name }}</span>
                    <span class="mx-1 text-slate-500">/</span>
                    <span>{{ auth()->user()?->roleLabel() }}</span>
                </div>
                <div class="hidden items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-2 text-xs font-semibold text-emerald-200 md:flex">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-300"></span>
                    Live sync aktif
                </div>
                <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                    @csrf
                    <button type="submit" class="rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white">
                        Keluar
                    </button>
                </form>
                <button class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/6 text-white transition hover:bg-white/10 md:hidden"
                        @click="$store.ui.mobileMenuOpen = !$store.ui.mobileMenuOpen" aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 5.5A1.5 1.5 0 014.5 4h11a1.5 1.5 0 010 3h-11A1.5 1.5 0 013 5.5zm0 4.5A1.5 1.5 0 014.5 8h11a1.5 1.5 0 010 3h-11A1.5 1.5 0 013 10zm0 4.5A1.5 1.5 0 014.5 13h11a1.5 1.5 0 010 3h-11A1.5 1.5 0 013 14.5z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>

        <div x-show="$store.ui.mobileMenuOpen" x-transition class="border-t border-white/10 bg-slate-950/95 px-4 py-4 md:hidden">
            <div class="mx-auto flex max-w-7xl flex-col gap-2">
                <a href="{{ route('home') }}" class="rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/6 hover:text-white">Beranda</a>
                <a href="{{ route('dashboard') }}" class="rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/6 hover:text-white">Dashboard</a>
                @if (auth()->user()?->role === 'admin' || auth()->user()?->role === 'panitia')
                    <a href="{{ route('scoring') }}" class="rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/6 hover:text-white">Penilaian</a>
                @endif
                <div class="rounded-2xl border border-white/10 bg-white/6 px-4 py-3 text-sm text-slate-300">
                    {{ auth()->user()?->name }} / {{ auth()->user()?->roleLabel() }}
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-2xl border border-white/10 bg-white/6 px-4 py-3 text-left text-sm text-slate-300 transition hover:bg-white/10 hover:text-white">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>

