<?php
require_once __DIR__.'/../partials/icon.php';

$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'participants.maqra.menu');
$district = $district ?? null;
$participants = $participants ?? collect();
$selectedParticipant = $selectedParticipant ?? null;
$summaryStats = $summaryStats ?? ['participant_total' => 0, 'maqra_total' => 0];
$roundLabel = $roundLabel ?? 'Penyisihan';
$filters = $filters ?? ['round' => $roundLabel, 'participant_id' => ''];
$judgeNameDefault = $judgeNameDefault ?? (string) $user?->name;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Pengambilan Maqra') ?></title>
    <?php foreach (($assets['css'] ?? []) as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-fuchsia right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>
        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block" x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('sparkles') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-fuchsia-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Pengambilan Maqra</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-fuchsia-400/14 bg-gradient-to-br from-slate-900/90 via-violet-950/70 to-fuchsia-950/60 p-5">
                    <p class="section-kicker">Ringkasan</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($summaryStats['participant_total']) ?> peserta terverifikasi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Halaman ini menampilkan peserta yang memakai maqra pada wilayah atau seluruh kecamatan, tergantung peran pengguna.</p>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>
            </aside>

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Pengambilan Maqra</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Menu pemilihan peserta maqra</h2>
                            <p class="mt-2 text-sm text-slate-300">Gunakan halaman ini untuk menelusuri peserta yang memakai maqra dan memilih babak penilaian.</p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Akses aktif
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-3">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Peserta</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['participant_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('book-open') ?></div><p class="mt-4 text-sm text-slate-400">Maqra Tersedia</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['maqra_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Babak Aktif</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($roundLabel) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Wilayah Aktif</p>
                            <h3 class="mt-2 text-2xl font-bold text-white"><?= e($district?->name ?? 'Semua Kecamatan') ?></h3>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">Jika yang masuk admin, halaman ini menampilkan semua peserta terverifikasi yang menggunakan maqra. Official tetap dibatasi pada kecamatan masing-masing.</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <?php foreach (['Penyisihan', 'Final'] as $roundOption): ?>
                            <a href="<?= e(route('participants.maqra.menu', ['round' => $roundOption])) ?>" class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] <?= $roundLabel === $roundOption ? 'border-fuchsia-300/40 bg-fuchsia-400/10 text-fuchsia-100' : 'border-slate-700 bg-slate-950/60 text-slate-300 hover:border-slate-600' ?>">
                                <?= e($roundOption) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Peserta Aktif</p>
                            <h3 class="mt-2 text-2xl font-bold text-white"><?= e($selectedParticipant?->name ?? 'Belum dipilih') ?></h3>
                            <p class="mt-2 text-sm text-slate-300"><?= e($selectedParticipant?->registration_number ?? '-') ?></p>
                        </div>
                        <?php if ($selectedParticipant): ?>
                            <a href="<?= e(route('participants.show', $selectedParticipant)) ?>" class="secondary-button rounded-xl px-4 py-2 text-sm">Lihat Detail</a>
                        <?php endif; ?>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-[1.75rem] border border-fuchsia-400/14 bg-slate-950/50">
                        <table class="min-w-full">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-4 text-left">Nama</th>
                                    <th class="px-5 py-4 text-left">Registrasi</th>
                                    <th class="px-5 py-4 text-left">Kecamatan</th>
                                    <th class="px-5 py-4 text-left">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $participant): ?>
                                    <tr class="border-t border-slate-800/80">
                                        <td class="px-5 py-4"><?= e($participant->name) ?></td>
                                        <td class="px-5 py-4 text-slate-300"><?= e($participant->registration_number) ?></td>
                                        <td class="px-5 py-4 text-slate-300"><?= e($participant->district?->name ?? '-') ?></td>
                                        <td class="px-5 py-4">
                                            <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button rounded-xl px-3 py-2 text-[11px]">Detail</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php foreach (($assets['js'] ?? []) as $src): ?>
        <script src="<?= e($src) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
