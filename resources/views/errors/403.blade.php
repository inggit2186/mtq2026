@php
    $isAuthenticated = auth()->check();
    $message = trim($exception->getMessage() ?? '');
    $defaultMessage = 'Aksi ini belum bisa dilakukan dari akun Anda saat ini.';
    $friendlyMessage = $message !== '' ? $message : $defaultMessage;
    $title = 'Akses Belum Diizinkan';
    $hint = 'Silakan kembali ke halaman sebelumnya atau gunakan menu yang masih tersedia.';

    if (str_contains(mb_strtolower($friendlyMessage), 'surat mandat kecamatan sedang ditolak')) {
        $title = 'Perlu Upload Ulang Surat Mandat';
        $hint = 'Official belum dapat mengubah data peserta sampai surat mandat kecamatan diperbaiki dan diupload ulang.';
    } elseif (str_contains(mb_strtolower($friendlyMessage), 'sudah terverifikasi tidak dapat diedit')) {
        $title = 'Peserta Sudah Terkunci';
        $hint = 'Data peserta yang sudah lolos verifikasi dikunci agar perubahan tidak mengganggu validitas administrasi.';
    }
@endphp

@extends($isAuthenticated ? 'layouts.app' : 'layouts.guest')

@section('title', '- Akses Ditolak')

@section('content')
    @php
        $steps = str_contains(mb_strtolower($friendlyMessage), 'surat mandat kecamatan sedang ditolak')
            ? [
                'Upload ulang surat mandat kecamatan yang benar dari halaman pendaftaran peserta.',
                'Setelah mandat diperiksa kembali panitia, akses edit peserta akan terbuka lagi.',
            ]
            : (str_contains(mb_strtolower($friendlyMessage), 'sudah terverifikasi tidak dapat diedit')
                ? [
                    'Periksa detail peserta untuk memastikan data terakhir yang sudah diverifikasi.',
                    'Jika memang perlu perubahan, minta tindak lanjut ke admin atau panitia.',
                ]
                : ['Periksa kembali hak akses akun Anda atau buka halaman lain yang masih tersedia.']);

        $actions = $isAuthenticated
            ? [
                ['href' => route('dashboard'), 'label' => 'Dashboard', 'prefix' => '<-', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white'],
                ['href' => route('participants.index'), 'label' => 'Buka Pendaftaran', 'class' => 'inline-flex items-center gap-2 rounded-full bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300'],
                ['href' => route('participants.list'), 'label' => 'Data Peserta', 'class' => 'inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 text-sm text-cyan-100 transition hover:border-cyan-300/30 hover:bg-cyan-400/16'],
            ]
            : [
                ['href' => route('home'), 'label' => 'Kembali ke Beranda', 'class' => 'inline-flex items-center gap-2 rounded-full bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300'],
                ['href' => route('login'), 'label' => 'Login', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white'],
            ];
    @endphp

    @include('errors.partials.error-page', [
        'panelClass' => 'border-rose-400/18 bg-gradient-to-br from-slate-900/95 via-rose-950/55 to-slate-950/95',
        'iconShellClass' => 'border-rose-300/20 bg-rose-400/10 text-rose-200',
        'iconSvg' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>',
        'codeLabel' => 'Error 403',
        'title' => $title,
        'message' => $friendlyMessage,
        'hint' => $hint,
        'steps' => $steps,
        'actions' => $actions,
        'eyebrowClass' => 'text-rose-200/80',
    ])
@endsection
