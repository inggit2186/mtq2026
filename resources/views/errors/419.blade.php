@php
    $isAuthenticated = auth()->check();
    $friendlyMessage = 'Sesi atau token formulir Anda sudah kedaluwarsa. Biasanya ini terjadi karena halaman terlalu lama terbuka sebelum tombol simpan diklik.';
@endphp

@extends($isAuthenticated ? 'layouts.app' : 'layouts.guest')

@section('title', '- Sesi Kedaluwarsa')

@section('content')
    @php
        $actions = [
            ['type' => 'button', 'label' => 'Muat Ulang Halaman', 'onclick' => 'window.location.reload()', 'class' => 'inline-flex items-center gap-2 rounded-full bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-300'],
            $isAuthenticated
                ? ['href' => route('dashboard'), 'label' => 'Dashboard', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white']
                : ['href' => route('login'), 'label' => 'Login Ulang', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white'],
        ];
    @endphp

    @include('errors.partials.error-page', [
        'panelClass' => 'border-amber-400/18 bg-gradient-to-br from-slate-900/95 via-amber-950/50 to-slate-950/95',
        'iconShellClass' => 'border-amber-300/20 bg-amber-400/10 text-amber-200',
        'iconSvg' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/></svg>',
        'codeLabel' => 'Error 419',
        'title' => 'Sesi Kedaluwarsa',
        'message' => $friendlyMessage,
        'hint' => 'Muat ulang halaman, lalu kirim ulang form Anda agar sistem membuat token baru.',
        'steps' => [
            'Segarkan halaman terlebih dahulu sebelum mencoba simpan lagi.',
            'Jika Anda sedang mengisi data panjang, pastikan form tidak dibiarkan terbuka terlalu lama.',
        ],
        'actions' => $actions,
        'eyebrowClass' => 'text-amber-200/80',
    ])
@endsection
