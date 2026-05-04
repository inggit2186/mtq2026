@php
    $isAuthenticated = auth()->check();
    $friendlyMessage = 'Terjadi gangguan di sisi server saat memproses permintaan Anda. Tim pengelola dapat memeriksa log untuk menelusuri penyebabnya.';
    $actions = $isAuthenticated
        ? [
            ['href' => route('dashboard'), 'label' => 'Dashboard', 'prefix' => '<-', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white'],
            ['type' => 'button', 'label' => 'Muat Ulang Halaman', 'onclick' => 'window.location.reload()', 'class' => 'inline-flex items-center gap-2 rounded-full bg-rose-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-rose-300'],
        ]
        : [
            ['href' => route('home'), 'label' => 'Kembali ke Beranda', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white'],
            ['type' => 'button', 'label' => 'Muat Ulang Halaman', 'onclick' => 'window.location.reload()', 'class' => 'inline-flex items-center gap-2 rounded-full bg-rose-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-rose-300'],
        ];
@endphp

@extends($isAuthenticated ? 'layouts.app' : 'layouts.guest')

@section('title', '- Gangguan Server')

@section('content')
    @include('errors.partials.error-page', [
        'panelClass' => 'border-rose-400/18 bg-gradient-to-br from-slate-900/95 via-rose-950/50 to-slate-950/95 shadow-[0_24px_70px_-32px_rgba(244,63,94,0.28)]',
        'iconShellClass' => 'border-rose-300/20 bg-rose-400/10 text-rose-200',
        'iconSvg' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14"/></svg>',
        'codeLabel' => 'Error 500',
        'title' => 'Terjadi Gangguan Server',
        'message' => $friendlyMessage,
        'hint' => 'Biasanya masalah ini bersifat sementara. Coba muat ulang halaman beberapa saat lagi.',
        'steps' => [
            'Muat ulang halaman untuk mencoba kembali permintaan yang tadi gagal.',
            'Jika error tetap muncul, catat langkah terakhir yang Anda lakukan lalu hubungi admin atau panitia teknis.',
        ],
        'actions' => $actions,
        'eyebrowClass' => 'text-rose-200/80',
    ])
@endsection
