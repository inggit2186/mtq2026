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
                <div class="badge-live w-fit"><?= mtq_icon('spark', 'h-4 w-4') ?> Portal Masuk e-MTQ</div>
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

            <section class="glass-card rounded-[2rem] p-6 sm:p-8" x-data="{ showPassword: false }">
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

                    <button type="submit" class="primary-button w-full">
                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                        Masuk
                    </button>
                </form>
            </section>
        </div>
    </main>

    <?php require __DIR__.'/../partials/ongoing-schedules.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
