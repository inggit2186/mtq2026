<?php
    $isAuthenticated = auth()->check();
    $friendlyMessage = 'Halaman atau data yang Anda cari tidak ditemukan. Mungkin sudah dipindahkan, dihapus, atau alamatnya tidak lagi aktif.';
?>



<?php $__env->startSection('title', '- Halaman Tidak Ditemukan'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $actions = $isAuthenticated
            ? [
                ['href' => route('dashboard'), 'label' => 'Dashboard', 'prefix' => '<-', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white'],
                ['href' => route('participants.index'), 'label' => 'Buka Pendaftaran', 'class' => 'inline-flex items-center gap-2 rounded-full bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300'],
            ]
            : [
                ['href' => route('home'), 'label' => 'Kembali ke Beranda', 'class' => 'inline-flex items-center gap-2 rounded-full bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300'],
                ['href' => route('login'), 'label' => 'Login', 'class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white'],
            ];
    ?>

    <?php echo $__env->make('errors.partials.error-page', [
        'panelClass' => 'border-cyan-400/18 bg-gradient-to-br from-slate-900/95 via-sky-950/55 to-slate-950/95',
        'iconShellClass' => 'border-cyan-300/20 bg-cyan-400/10 text-cyan-200',
        'iconSvg' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35"/></svg>',
        'codeLabel' => 'Error 404',
        'title' => 'Halaman Tidak Ditemukan',
        'message' => $friendlyMessage,
        'hint' => 'Coba kembali ke dashboard, buka menu yang tersedia, atau ulangi akses dari halaman sebelumnya.',
        'steps' => [
            'Periksa kembali tautan atau halaman yang tadi Anda buka.',
            'Jika Anda sedang membuka detail peserta atau dokumen, pastikan datanya masih tersedia dan hak akses Anda sesuai.',
        ],
        'actions' => $actions,
        'eyebrowClass' => 'text-cyan-200/80',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make($isAuthenticated ? 'layouts.app' : 'layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /www/wwwroot/mtq.kemenagtanahdatar.id/resources/views/errors/404.blade.php ENDPATH**/ ?>