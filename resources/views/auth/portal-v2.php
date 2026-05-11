<?php
require_once __DIR__.'/../partials/icon.php';
$title = 'Login';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - '.$title) ?></title>
    <style>[x-cloak]{display:none!important;}</style>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/sweet-alerts.php'; ?>
    <main class="relative mx-auto flex min-h-screen max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-8">
        <div class="hero-orb hero-orb-cyan left-[-8rem] top-8 h-64 w-64"></div>
        <div class="hero-orb hero-orb-blue right-[-6rem] bottom-10 h-72 w-72"></div>
        <div class="grid w-full gap-8 lg:grid-cols-[0.95fr_1.05fr] lg:gap-10">
            <section class="glass-card rounded-[2rem] p-6 sm:p-8">
                <div class="badge-live w-fit">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-cyan-200/30 bg-slate-50 p-0.5">
                        <img src="<?= e(asset('images/emtq-resmi.webp')) ?>" alt="Logo resmi e-MTQ" class="h-full w-full object-contain">
                    </span>
                    Portal Masuk e-MTQ
                </div>
                <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-5xl">
                    Satu pintu masuk untuk admin, panitia, official, dan peserta.
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                    Masuk dengan email atau nomor induk NIP/NIK, lalu sistem akan mengenali peran akun Anda dan mengarahkan ke area yang sesuai.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <?php foreach ([
                        ['role' => 'Admin', 'desc' => 'Kelola sistem, data utama, dan pemantauan penuh.', 'icon' => 'shield'],
                        ['role' => 'Panitia', 'desc' => 'Kelola operasional kegiatan dan penilaian.', 'icon' => 'chart'],
                        ['role' => 'Official', 'desc' => 'Pantau jadwal, progres peserta, dan pengumuman.', 'icon' => 'calendar'],
                        ['role' => 'Peserta', 'desc' => 'Lihat ringkasan kompetisi dan informasi terbaru.', 'icon' => 'trophy'],
                    ] as $item): ?>
                        <div class="data-card">
                            <div class="icon-chip"><?= mtq_icon($item['icon']) ?></div>
                            <p class="mt-4 font-semibold text-white"><?= e($item['role']) ?></p>
                            <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($item['desc']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="glass-card rounded-[2rem] p-6 sm:p-8" x-data="{ showPassword: false, showForgotPasswordModal: <?= $errors->has('nip') ? 'true' : 'false' ?> }" x-on:keydown.escape.window="showForgotPasswordModal = false">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="section-kicker">Masuk Sekarang</p>
                        <h2 class="mt-1 text-2xl font-bold text-white">Masuk ke akun e-MTQ</h2>
                    </div>
                    <a href="<?= e(route('home')) ?>" class="secondary-button rounded-full px-4 py-2">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        Kembali
                    </a>
                </div>

                <form method="POST" action="<?= e(route('login.store')) ?>" class="mt-6 space-y-5">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                    <div>
                        <label for="login" class="mb-2 block text-sm font-semibold text-slate-200">Email / NIP / NIK</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-cyan-200"><?= mtq_icon('mail', 'h-5 w-5') ?></span>
                            <input id="login" name="login" type="text" value="<?= e(old('login')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 py-3 pl-12 pr-4 text-slate-100 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="nama@emtq.test atau 3201123456789001">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-slate-200">Password</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-cyan-200"><?= mtq_icon('fingerprint', 'h-5 w-5') ?></span>
                            <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 py-3 pl-12 pr-14 text-slate-100 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Password">
                            <button type="button" x-on:click="showPassword = !showPassword" class="absolute right-3 top-1/2 inline-flex -translate-y-1/2 items-center justify-center rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-400 transition hover:text-cyan-200">
                                <?= mtq_icon('eye', 'h-4 w-4') ?>
                            </button>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 text-sm text-slate-300">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-700 bg-slate-900 text-cyan-400 focus:ring-cyan-400/20">
                        Ingat saya di perangkat ini
                    </label>

                    <div class="flex items-center justify-between gap-3">
                        <button type="button" class="text-sm font-semibold text-cyan-200 transition hover:text-cyan-100" x-on:click="showForgotPasswordModal = true">
                            Lupa Password
                        </button>
                    </div>

                    <button type="submit" class="primary-button w-full">
                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                        Masuk
                    </button>
                </form>

                <div
                    x-cloak
                    x-show="showForgotPasswordModal"
                    x-transition.opacity.duration.150ms
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 px-4 py-6"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="forgot-password-title"
                >
                    <div class="absolute inset-0" x-on:click="showForgotPasswordModal = false"></div>
                    <div class="relative z-10 w-full max-w-md rounded-[1.75rem] border border-cyan-400/20 bg-slate-950 p-6 shadow-[0_24px_70px_-28px_rgba(15,23,42,0.95)]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="section-kicker">Reset Password</p>
                                <h3 id="forgot-password-title" class="mt-1 text-2xl font-bold text-white">Lupa Password</h3>
                            </div>
                            <button type="button" class="secondary-button rounded-xl px-3 py-2" x-on:click="showForgotPasswordModal = false" aria-label="Tutup modal">
                                <?= mtq_icon('x', 'h-4 w-4') ?>
                            </button>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-300">
                            Masukkan NIP pegawai yang terdaftar. Jika cocok, password baru akan dibuat dan dikirim ke nomor WhatsApp yang tersimpan di akun.
                        </p>

                        <form method="POST" action="<?= e(route('password.reset.request')) ?>" class="mt-5 space-y-4">
                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                            <div>
                                <label for="reset-nip" class="mb-2 block text-sm font-semibold text-slate-200">NIP Pegawai</label>
                                <input
                                    id="reset-nip"
                                    name="nip"
                                    type="text"
                                    value="<?= e(old('nip')) ?>"
                                    autocomplete="off"
                                    inputmode="numeric"
                                    placeholder="Masukkan NIP pegawai"
                                    class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                >
                                <?php if ($errors->has('nip')): ?>
                                    <p class="mt-2 text-sm leading-6 text-rose-200"><?= e($errors->first('nip')) ?></p>
                                <?php else: ?>
                                    <p class="mt-2 text-xs leading-6 text-slate-500">Pastikan nomor WhatsApp di akun masih aktif agar password baru bisa diterima.</p>
                                <?php endif; ?>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button type="button" class="secondary-button" x-on:click="showForgotPasswordModal = false">
                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                    Batal
                                </button>
                                <button type="submit" class="primary-button">
                                    <?= mtq_icon('key', 'h-4 w-4') ?>
                                    Kirim Password Baru
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php require __DIR__.'/../partials/ongoing-schedules.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
