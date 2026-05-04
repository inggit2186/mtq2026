@php
    $isAuthenticated = auth()->check();
    $friendlyMessage = 'Layanan sedang dalam pemeliharaan atau sementara belum tersedia. Sistem akan kembali normal setelah proses pembaruan selesai.';
    $actions = $isAuthenticated
        ? [
            ['href' => route('dashboard'), 'label' => 'Dashboard', 'prefix' => '<-', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white'],
            ['type' => 'button', 'label' => 'Coba Lagi Nanti', 'onclick' => 'window.location.reload()', 'class' => 'inline-flex items-center gap-2 rounded-full bg-violet-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-violet-300'],
        ]
        : [
            ['href' => route('home'), 'label' => 'Kembali ke Beranda', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white'],
            ['type' => 'button', 'label' => 'Coba Lagi Nanti', 'onclick' => 'window.location.reload()', 'class' => 'inline-flex items-center gap-2 rounded-full bg-violet-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-violet-300'],
        ];
@endphp

@extends($isAuthenticated ? 'layouts.app' : 'layouts.guest')

@section('title', '- Layanan Sementara Tidak Tersedia')

@section('content')
    @include('errors.partials.error-page', [
        'panelClass' => 'border-violet-400/18 bg-gradient-to-br from-slate-900/95 via-violet-950/50 to-slate-950/95 shadow-[0_24px_70px_-32px_rgba(167,139,250,0.28)]',
        'iconShellClass' => 'border-violet-300/20 bg-violet-400/10 text-violet-200',
        'iconSvg' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 17h10"/></svg>',
        'codeLabel' => 'Error 503',
        'title' => 'Layanan Sementara Tidak Tersedia',
        'message' => $friendlyMessage,
        'hint' => 'Silakan tunggu beberapa saat. Setelah pemeliharaan selesai, halaman ini akan bisa diakses kembali seperti biasa.',
        'steps' => [
            'Tunggu beberapa saat lalu muat ulang halaman.',
            'Jika pemeliharaan berlangsung lebih lama dari yang diperkirakan, hubungi admin atau panitia teknis untuk memastikan status layanan.',
        ],
        'actions' => $actions,
        'eyebrowClass' => 'text-violet-200/80',
    ])
@endsection
