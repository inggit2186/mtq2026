<?php
require_once __DIR__.'/../partials/icon.php';

$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'participants.lot.menu');
$categories = $categories ?? collect();
$participants = $participants ?? collect();
$participantsByCategory = $participantsByCategory ?? $participants->groupBy(fn ($participant) => (int) $participant->competition_category_id);
$selectedCategory = $selectedCategory ?? null;
$selectedParticipant = $selectedParticipant ?? null;
$summaryStats = $summaryStats ?? ['category_total' => 0, 'participant_total' => 0, 'verified_total' => 0];
$filters = $filters ?? ['competition_category_id' => '', 'participant_id' => ''];
$judgeNameDefault = $judgeNameDefault ?? (string) $user?->name;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Pengambilan Lot') ?></title>
    <?php foreach (($assets['css'] ?? []) as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>
        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block" x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('sparkles') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Pengambilan Lot</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Ringkasan</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($summaryStats['participant_total']) ?> peserta siap ditinjau</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Halaman ini menampilkan data peserta terverifikasi untuk kebutuhan pengambilan nomor lot.</p>
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
                            <p class="section-kicker">Pengambilan Lot</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Menu pemilihan peserta lot</h2>
                            <p class="mt-2 text-sm text-slate-300">Gunakan halaman ini untuk menelusuri peserta terverifikasi dan melihat kandidat yang akan mendapat nomor lot.</p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Akses aktif
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-3">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('book-open') ?></div><p class="mt-4 text-sm text-slate-400">Golongan</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['category_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Peserta</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['participant_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Terverifikasi</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($summaryStats['verified_total']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Golongan Aktif</p>
                            <h3 class="mt-2 text-2xl font-bold text-white"><?= e($selectedCategory ? trim((string) $selectedCategory->branch.' - '.(string) $selectedCategory->name) : 'Semua Golongan') ?></h3>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">Kategori yang tampil di sini mengikuti golongan yang tersedia untuk pengambilan lot pada data peserta terverifikasi.</p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($categories as $category): ?>
                            <a href="#golongan-<?= e($category->id) ?>" class="rounded-[1.5rem] border px-4 py-4 transition <?= (int) ($selectedCategory?->id ?? 0) === (int) $category->id ? 'border-cyan-300 bg-cyan-400/10' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30' ?>">
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-400"><?= e($category->branch) ?></p>
                                <p class="mt-2 text-base font-bold text-white"><?= e($category->name) ?></p>
                                <p class="mt-2 text-xs text-slate-400"><?= e((string) ($participantsByCategory->get($category->id, collect())->count())) ?> peserta terverifikasi</p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php foreach ($categories as $category): ?>
                    <?php
                        $categoryParticipants = $participantsByCategory->get($category->id, collect())->values();
                        $isActiveCategory = (int) ($selectedCategory?->id ?? 0) === (int) $category->id;
                    ?>
                    <section id="golongan-<?= e($category->id) ?>" class="glass-card rounded-[2rem] p-6 <?= $isActiveCategory ? 'ring-2 ring-cyan-300/20' : '' ?>">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="section-kicker">Golongan</p>
                                <h3 class="mt-2 text-2xl font-bold text-white"><?= e($category->branch.' - '.$category->name) ?></h3>
                                <p class="mt-2 text-sm text-slate-300"><?= e($categoryParticipants->count()) ?> peserta terverifikasi siap diambil nomor lot</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="status-pill border-cyan-400/20 bg-cyan-400/10 text-cyan-100">
                                    <?= mtq_icon('users', 'h-4 w-4') ?>
                                    <?= e($categoryParticipants->count()) ?> peserta
                                </span>
                                <a href="<?= e(route('participants.lot.menu', ['competition_category_id' => $category->id])) ?>" class="secondary-button rounded-xl px-4 py-2 text-sm">
                                    <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                    Fokus Golongan
                                </a>
                            </div>
                        </div>

                        <?php if ($categoryParticipants->isEmpty()): ?>
                            <div class="mt-6 rounded-[1.5rem] border border-dashed border-slate-700 bg-slate-950/50 p-6 text-sm text-slate-400">
                                Belum ada peserta terverifikasi pada golongan ini.
                            </div>
                        <?php else: ?>
                            <div class="mt-6 overflow-hidden rounded-[1.75rem] border border-cyan-400/14 bg-slate-950/50">
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
                                        <?php foreach ($categoryParticipants as $participant): ?>
                                            <tr class="border-t border-slate-800/80">
                                                <td class="px-5 py-4"><?= e($participant->name) ?></td>
                                                <td class="px-5 py-4 text-slate-300"><?= e($participant->registration_number) ?></td>
                                                <td class="px-5 py-4 text-slate-300"><?= e($participant->district?->name ?? '-') ?></td>
                                                <td class="px-5 py-4">
                                                    <div class="flex flex-wrap gap-2">
                                                        <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button rounded-xl px-3 py-2 text-[11px]">Detail</a>
                                                        <?php if ($participant->verification_status === 'verified' && $user?->role === 'panitia'): ?>
                                                            <a href="<?= e(route('participants.lot.draw', $participant).'?autofullscreen=1') ?>" data-lot-launcher class="secondary-button rounded-xl border-cyan-300/30 bg-cyan-400/10 px-3 py-2 text-[11px] text-cyan-100 hover:border-cyan-200/50">
                                                                <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                                Ambil Nomor Lot
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <?php foreach (($assets['js'] ?? []) as $src): ?>
        <script src="<?= e($src) ?>" defer></script>
    <?php endforeach; ?>
    <script>
        (function () {
            const launchers = document.querySelectorAll('[data-lot-launcher]');
            launchers.forEach((launcher) => {
                launcher.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = launcher.getAttribute('href');
                    if (!url) {
                        return;
                    }

                    const popup = window.open(
                        url,
                        'mtq-lot-draw',
                        `popup=yes,width=${screen.availWidth},height=${screen.availHeight},left=0,top=0,noopener=no`
                    );

                    if (popup) {
                        try {
                            popup.moveTo(0, 0);
                            popup.resizeTo(screen.availWidth, screen.availHeight);
                            popup.focus();
                        } catch (error) {
                            popup.focus();
                        }
                    } else {
                        window.location.href = url;
                    }
                });
            });
        })();
    </script>
</body>
</html>
