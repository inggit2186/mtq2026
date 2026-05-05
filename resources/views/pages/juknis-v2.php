<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$juknis = $juknis ?? [];
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'juknis');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Juknis MTQ') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('book-open') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Juknis MTQ</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Referensi Utama</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($juknis['title'] ?? 'Juknis MTQ') ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Disusun sebagai acuan fitur aplikasi untuk alur pendaftaran, verifikasi, jadwal, penilaian, dan hak official.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                        Host <?= e($juknis['host'] ?? '-') ?>
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php foreach ($navigation as $item): ?>
                        <a href="<?= e($item['href']) ?>" class="sidebar-link <?= $item['active'] ? 'sidebar-link-active' : '' ?>">
                            <span class="icon-chip h-10 w-10 rounded-xl"><?= mtq_icon($item['icon'], 'h-4 w-4') ?></span>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Acuan Umur</p>
                        <p class="mt-2 text-lg font-bold text-white"><?= e($juknis['age_reference_date'] ?? '-') ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Peran Official</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Official kecamatan berhak memantau, menyiapkan dokumen, dan mengajukan protes resmi sesuai juknis.</p>
                    </div>
                    <form method="POST" action="<?= e(route('logout')) ?>">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="secondary-button w-full">
                            <?= mtq_icon('logout', 'h-4 w-4') ?>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Sinkronisasi Fitur</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white"><?= e($juknis['title'] ?? 'Juknis MTQ') ?></h2>
                            <p class="mt-2 text-sm text-slate-300"><?= e($rolePanel['description'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Fitur Diselaraskan
                    </div>
                    <a href="<?= e(route('participants.guide.pdf')) ?>" class="primary-button rounded-full px-4 py-2">
                        <?= mtq_icon('download', 'h-4 w-4') ?>
                        Download Panduan
                    </a>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('calendar') ?></div><p class="mt-4 text-sm text-slate-400">Pendaftaran</p><p class="mt-2 text-lg font-bold text-white"><?= e(($juknis['registration']['open'] ?? '-').' - '.($juknis['registration']['close'] ?? '-')) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Verifikasi</p><p class="mt-2 text-lg font-bold text-white"><?= e(($juknis['registration']['verification_start'] ?? '-').' - '.($juknis['registration']['verification_end'] ?? '-')) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('bell') ?></div><p class="mt-4 text-sm text-slate-400">Pengumuman</p><p class="mt-2 text-lg font-bold text-white"><?= e($juknis['registration']['announcement'] ?? '-') ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('clock') ?></div><p class="mt-4 text-sm text-slate-400">Sanggah</p><p class="mt-2 text-lg font-bold text-white"><?= e(($juknis['registration']['objection_start'] ?? '-').' - '.($juknis['registration']['objection_end'] ?? '-')) ?></p></div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('mail') ?></div>
                            <div>
                                <p class="section-kicker">Administrasi</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Alur pendaftaran dan verifikasi</h3>
                            </div>
                        </div>
                        <div class="mt-5 space-y-3">
                            <div class="data-card text-sm text-slate-200">Pendaftaran dilakukan online melalui aplikasi e-MTQ oleh administrator kecamatan.</div>
                            <div class="data-card text-sm text-slate-200">Administrator kecamatan wajib melengkapi seluruh isian form termasuk nomor handphone.</div>
                            <div class="data-card text-sm text-slate-200">Verifikasi tahap I dilakukan setelah masa pendaftaran, lalu hasilnya diumumkan dan dibuka masa sanggah/penggantian peserta.</div>
                        </div>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('fingerprint') ?></div>
                            <div>
                                <p class="section-kicker">Berkas Wajib</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Dokumen administrasi peserta</h3>
                            </div>
                        </div>
                        <div class="mt-5 space-y-3">
                            <?php foreach (($juknis['administration_requirements'] ?? []) as $item): ?>
                                <div class="data-card text-sm text-slate-200"><?= e($item) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section id="jadwal-juknis" class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                        <div>
                            <p class="section-kicker">Jadwal Resmi</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Rangkaian kegiatan MTQ 19 - 23 Juni 2026</h3>
                        </div>
                    </div>
                    <div class="table-shell mt-6">
                        <table class="min-w-full">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-4">Tanggal</th>
                                    <th class="px-5 py-4">Waktu</th>
                                    <th class="px-5 py-4">Kegiatan</th>
                                    <th class="px-5 py-4">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($juknis['event_schedule'] ?? []) as $row): ?>
                                    <tr class="table-row">
                                        <td class="px-5 py-4 text-sm text-slate-300"><?= e($row['date'] ?? '-') ?></td>
                                        <td class="px-5 py-4 text-sm text-cyan-200"><?= e($row['time'] ?? '-') ?></td>
                                        <td class="px-5 py-4 font-semibold text-white"><?= e($row['activity'] ?? '-') ?></td>
                                        <td class="px-5 py-4 text-sm text-slate-300"><?= e($row['notes'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-3">
                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('users') ?></div>
                            <h3 class="text-2xl font-bold text-white">Ketentuan Peserta</h3>
                        </div>
                        <div class="mt-5 space-y-3">
                            <?php foreach (($juknis['participant_rules'] ?? []) as $item): ?>
                                <div class="data-card text-sm text-slate-200"><?= e($item) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                            <h3 class="text-2xl font-bold text-white">Sistem Musabaqah</h3>
                        </div>
                        <div class="mt-5 space-y-3">
                            <?php foreach (($juknis['competition_system'] ?? []) as $item): ?>
                                <div class="data-card text-sm text-slate-200"><?= e($item) ?></div>
                            <?php endforeach; ?>
                            <?php foreach (($juknis['performance_rules'] ?? []) as $item): ?>
                                <div class="data-card text-sm text-slate-200"><?= e($item) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('shield') ?></div>
                            <h3 class="text-2xl font-bold text-white">Hak Official dan Protes</h3>
                        </div>
                        <div class="mt-5 space-y-3">
                            <?php foreach (($juknis['objection_rules'] ?? []) as $item): ?>
                                <div class="data-card text-sm text-slate-200"><?= e($item) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
