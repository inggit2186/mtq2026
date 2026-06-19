<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$selectedParticipant = $selectedParticipant ?? null;
$filters = $filters ?? [];
$resultStats = $resultStats ?? ['entries' => 0, 'latest' => '0.00', 'best' => '0.00', 'average' => '0.00'];
$scoreTimeline = $scoreTimeline ?? collect();
$branchCriteria = $branchCriteria ?? [];
$isParticipant = $isParticipant ?? false;
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'results');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Hasil Nilai') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('trophy') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Hasil Nilai</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Akses Aktif</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($user?->name) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">
                        <?php if ($isParticipant): ?>
                            Ringkasan nilai personal Anda ditampilkan di sini, termasuk histori dan komponen penilaian.
                        <?php else: ?>
                            Gunakan halaman ini untuk meninjau hasil penilaian peserta secara lebih detail daripada leaderboard.
                        <?php endif; ?>
                    </p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Siap Ditinjau
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Peserta Dipilih</p>
                        <p class="mt-2 text-lg font-bold text-white"><?= e($selectedParticipant?->name ?? 'Belum ada') ?></p>
                        <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($selectedParticipant?->category?->name ?? 'Pilih peserta untuk melihat hasil nilai.') ?></p>
                    </div>
                    <a href="<?= e(route('dashboard')) ?>" class="secondary-button w-full">
                        <?= mtq_icon('home', 'h-4 w-4') ?>
                        Kembali ke Dashboard
                    </a>
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
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Rekap Penilaian</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Detail hasil nilai peserta</h2>
                            <p class="mt-2 text-sm text-slate-300">Lihat skor total, komponen penilaian, dan histori performa dalam satu halaman.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <?php if ($selectedParticipant && ! $isParticipant): ?>
                            <a href="<?= e(route('results.export', array_filter([
                                'participant_id' => $selectedParticipant?->id,
                                'competition_category_id' => $filters['competition_category_id'] ?? null,
                                'keyword' => $filters['keyword'] ?? null,
                            ]))) ?>" class="secondary-button">
                                <?= mtq_icon('upload', 'h-4 w-4') ?>
                                Ekspor CSV
                            </a>
                        <?php endif; ?>
                        <?php if ($selectedParticipant): ?>
                            <a href="<?= e(route('results.print', array_filter([
                                'participant_id' => $selectedParticipant?->id,
                                'competition_category_id' => $filters['competition_category_id'] ?? null,
                                'keyword' => $filters['keyword'] ?? null,
                            ]))) ?>" target="_blank" rel="noreferrer" class="secondary-button">
                                <?= mtq_icon('book-open', 'h-4 w-4') ?>
                                Cetak Rekap
                            </a>
                        <?php endif; ?>
                        <?php if (! $isParticipant): ?>
                            <a href="<?= e(route('results.recap', array_filter([
                                'competition_category_id' => $filters['competition_category_id'] ?? null,
                                'keyword' => $filters['keyword'] ?? null,
                            ]))) ?>" class="secondary-button">
                                <?= mtq_icon('layers', 'h-4 w-4') ?>
                                Rekap Cabang
                            </a>
                        <?php endif; ?>
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?= e($resultStats['entries']) ?> Entri Nilai
                        </div>
                    </div>
                </header>

                <?php if (! $isParticipant): ?>
                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('layers') ?></div>
                            <div>
                                <p class="section-kicker">Filter Peserta</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Cari peserta dan golongan</h3>
                            </div>
                        </div>

                        <form method="GET" action="<?= e(route('results.index')) ?>" class="mt-6 grid gap-4 lg:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Kata kunci</label>
                                <input name="keyword" type="text" value="<?= e($filters['keyword'] ?? '') ?>" placeholder="Nama / registrasi / kafilah" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Golongan</label>
                                <select name="competition_category_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                    <option value="">Semua golongan</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= e($category->id) ?>" <?= (string) ($filters['competition_category_id'] ?? '') === (string) $category->id ? 'selected' : '' ?>><?= e($category->branch.' - '.$category->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Peserta</label>
                                <select name="participant_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                    <option value="">Pilih peserta</option>
                                    <?php foreach ($participants as $participant): ?>
                                        <option value="<?= e($participant->id) ?>" <?= (string) ($filters['participant_id'] ?? '') === (string) $participant->id ? 'selected' : '' ?>><?= e($participant->name.' - '.$participant->category?->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex items-end gap-3">
                                <button type="submit" class="primary-button">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    Tampilkan
                                </button>
                                <a href="<?= e(route('results.index')) ?>" class="secondary-button">
                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                    Reset
                                </a>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('chart') ?></div><p class="mt-4 text-sm text-slate-400">Nilai Terakhir</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($resultStats['latest']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('trophy') ?></div><p class="mt-4 text-sm text-slate-400">Nilai Terbaik</p><p class="mt-2 text-3xl font-extrabold text-emerald-300"><?= e($resultStats['best']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Rata-rata</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($resultStats['average']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('clock') ?></div><p class="mt-4 text-sm text-slate-400">Jumlah Entri</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($resultStats['entries']) ?></p></div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <div class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-6 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)]">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="icon-chip"><?= mtq_icon('users') ?></div>
                                <p class="mt-5 section-kicker">Profil Nilai</p>
                                <h2 class="mt-2 text-2xl font-bold text-white"><?= e($selectedParticipant?->name ?? 'Belum ada peserta dipilih') ?></h2>
                                <p class="mt-3 text-sm leading-7 text-slate-300">
                                    <?php if ($selectedParticipant): ?>
                                        <?= e(($selectedParticipant->category?->branch ?? '-').' | '.($selectedParticipant->category?->name ?? '-')) ?><br>
                                        <?= e(($selectedParticipant->district?->name ?? '-').' | '.$selectedParticipant->institution) ?>
                                    <?php else: ?>
                                        Belum ada data peserta yang siap ditampilkan.
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($selectedParticipant): ?>
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                    <?= e($selectedParticipant->registration_number) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-6 space-y-3">
                            <div class="data-card">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Status Verifikasi</p>
                                <p class="mt-2 text-sm text-white"><?= e(ucfirst((string) ($selectedParticipant?->verification_status ?? 'belum tersedia'))) ?></p>
                            </div>
                            <div class="data-card">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Catatan Berkas</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($selectedParticipant?->verification_notes ?? 'Belum ada catatan verifikasi.') ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('check-circle') ?></div>
                            <div>
                                <p class="section-kicker">Breakdown Komponen</p>
                                <p class="mt-1 text-sm text-slate-300">Komponen dari entri nilai terbaru peserta.</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-3">
                            <?php $latestEntry = $scoreTimeline->first(); ?>
                            <?php
                            // Get breakdown - new format uses first judge's breakdown
                            $latestBreakdown = null;
                            if ($latestEntry) {
                                if ($latestEntry->scores && is_array($latestEntry->scores)) {
                                    $firstJudge = array_key_first($latestEntry->scores);
                                    $latestBreakdown = $latestEntry->scores[$firstJudge]['breakdown'] ?? null;
                                } else {
                                    $latestBreakdown = $latestEntry->score_breakdown;
                                }
                            }
                            ?>
                            <?php if (! $latestEntry || empty($latestBreakdown)): ?>
                                <div class="data-card text-sm text-slate-300">Belum ada breakdown komponen nilai untuk peserta ini.</div>
                            <?php else: ?>
                                <?php foreach ($latestBreakdown as $key => $value): ?>
                                    <div class="data-card flex items-center justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-white"><?= e($branchCriteria[$key] ?? ucwords(str_replace('_', ' ', (string) $key))) ?></p>
                                            <p class="mt-1 text-xs text-slate-400"><?= e($latestEntry->judging_round ?: 'Babak belum ditentukan') ?></p>
                                        </div>
                                        <p class="text-lg font-bold text-cyan-200"><?= e(number_format((float) $value, 2)) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                        <div>
                            <p class="section-kicker">Histori Penilaian</p>
                            <h3 class="mt-1 text-2xl font-bold text-white">Riwayat nilai dari waktu ke waktu</h3>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <?php if ($scoreTimeline->isEmpty()): ?>
                            <div class="data-card text-sm text-slate-300">Belum ada nilai yang tercatat untuk peserta ini.</div>
                        <?php else: ?>
                            <?php foreach ($scoreTimeline as $entry): ?>
                                <?php
                                // Check if new aggregated format
                                $isNewFormat = $entry->scores && is_array($entry->scores);
                                $judgeCount = $isNewFormat ? count($entry->scores) : 1;
                                $displayScore = $isNewFormat
                                    ? number_format((float) ($entry->average_score ?? 0), 2)
                                    : number_format((float) ($entry->score ?? 0), 2);
                                ?>
                                <div class="data-card">
                                    <div class="flex flex-wrap items-center justify-between gap-4">
                                        <div>
                                            <?php if ($isNewFormat): ?>
                                                <p class="font-semibold text-white"><?= e($judgeCount) ?> Hakim<?php if ($entry->judging_round): ?> | <?= e($entry->judging_round) ?><?php endif; ?></p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    <?php
                                                    $judgeNames = array_keys($entry->scores);
                                                    echo implode(', ', array_slice($judgeNames, 0, 3));
                                                    if (count($judgeNames) > 3) echo '...';
                                                    ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="font-semibold text-white"><?= e($entry->judge_name) ?><?php if ($entry->judging_round): ?> | <?= e($entry->judging_round) ?><?php endif; ?></p>
                                                <p class="mt-1 text-xs text-slate-400"><?= e(optional($entry->submitted_at)->format('d M Y H:i')) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-cyan-200"><?= e($displayScore) ?></p>
                                            <p class="text-xs text-slate-400">Total Nilai</p>
                                        </div>
                                    </div>

                                    <?php
                                    // Get breakdown - new format uses first judge's breakdown, old uses score_breakdown
                                    $breakdown = null;
                                    if ($isNewFormat) {
                                        $firstJudge = array_key_first($entry->scores);
                                        $breakdown = $entry->scores[$firstJudge]['breakdown'] ?? null;
                                    } else {
                                        $breakdown = $entry->score_breakdown;
                                    }
                                    ?>
                                    <?php if (! empty($breakdown)): ?>
                                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                            <?php foreach ($breakdown as $key => $value): ?>
                                                <div class="rounded-2xl border border-slate-700 bg-slate-900/80 px-4 py-3">
                                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500"><?= e($branchCriteria[$key] ?? ucwords(str_replace('_', ' ', (string) $key))) ?></p>
                                                    <p class="mt-2 text-base font-bold text-white"><?= e(number_format((float) $value, 2)) ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($entry->remarks): ?>
                                        <p class="mt-4 text-sm leading-6 text-slate-300"><?= e($entry->remarks) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
