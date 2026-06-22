<?php
use Carbon\Carbon;
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
$rankings = $rankings ?? [];
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'results');
$latestEntry = $scoreTimeline->first();
$officialCanViewScoreDetail = $officialCanViewScoreDetail ?? false;
$isOfficial = in_array($user?->role, ['official', 'pendamping'], true);
$canViewScoreDetail = !$isOfficial || $officialCanViewScoreDetail;

// Get breakdown - new format uses point_averages from aggregated scores
$latestBreakdown = null;
$latestJudges = [];
if ($latestEntry) {
    if ($latestEntry->scores && is_array($latestEntry->scores)) {
        // New aggregated format - check for point_averages first
        $firstJudge = array_key_first($latestEntry->scores);
        $firstJudgeData = $latestEntry->scores[$firstJudge] ?? [];

        // Use point_averages (aggregated from all judges) if available
        if (isset($firstJudgeData['point_averages']) && is_array($firstJudgeData['point_averages'])) {
            $latestBreakdown = $firstJudgeData['point_averages'];
        } elseif (isset($firstJudgeData['breakdown']) && is_array($firstJudgeData['breakdown'])) {
            $latestBreakdown = $firstJudgeData['breakdown'];
        } elseif (isset($firstJudgeData['scores']) && is_array($firstJudgeData['scores'])) {
            $latestBreakdown = $firstJudgeData['scores'];
        }

        $latestJudges = array_keys($latestEntry->scores);
    } else {
        $latestBreakdown = $latestEntry->score_breakdown;
        $latestJudges = [$latestEntry->judge_name ?? 'Hakim'];
    }
}

// Check if rankings exist
$hasRankings = !empty($rankings);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Hasil Nilai') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-8px); } }
        @keyframes pulse-glow { 0%, 100% { box-shadow: 0 0 20px rgba(34, 211, 238, 0.3); } 50% { box-shadow: 0 0 40px rgba(34, 211, 238, 0.6); } }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        @keyframes slide-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes scale-in { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .animate-float { animation: float 4s ease-in-out infinite; }
        .animate-float-delay { animation: float 4s ease-in-out 0.5s infinite; }
        .animate-pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
        .animate-slide-up { animation: slide-up 0.5s ease-out forwards; }
        .animate-scale-in { animation: scale-in 0.4s ease-out forwards; }
        .shimmer-bg { background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent); background-size: 200% 100%; animation: shimmer 2s infinite; }
        .gradient-text { background: linear-gradient(135deg, #67e8f9 0%, #22d3ee 50%, #06b6d4 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .gradient-border { border-image: linear-gradient(135deg, #67e8f9, #22d3ee) 1; }
        .trophy-card { background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%); border: 1px solid rgba(251, 191, 36, 0.3); }
        .gold-gradient { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%); }
        .silver-gradient { background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 50%, #64748b 100%); }
        .bronze-gradient { background: linear-gradient(135deg, #d97706 0%, #b45309 50%, #92400e 100%); }
        .rank-badge { display: inline-flex; align-items: center; justify-content: center; font-weight: 800; }
        .rank-gold { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1e293b; box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4); }
        .rank-silver { background: linear-gradient(135deg, #e2e8f0, #94a3b8); color: #1e293b; box-shadow: 0 4px 12px rgba(148, 163, 184, 0.4); }
        .rank-bronze { background: linear-gradient(135deg, #d97706, #b45309); color: white; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.4); }
        .progress-bar { height: 8px; border-radius: 9999px; background: rgba(255,255,255,0.1); overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 9999px; transition: width 1s ease-out; }
        .stat-card { position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, currentColor, transparent); opacity: 0.5; }
        .hover-lift { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .hover-lift:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
    </style>
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
                <!-- Hero Header -->
                <header class="relative overflow-hidden rounded-[2rem] border border-cyan-400/20 bg-gradient-to-br from-slate-900 via-slate-900/95 to-sky-950/80 p-6 sm:p-8 shadow-[0_0_80px_-20px_rgba(34,211,238,0.25)]">
                    <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-cyan-400/10 blur-3xl"></div>
                    <div class="absolute -left-4 -bottom-4 h-24 w-24 rounded-full bg-blue-400/10 blur-3xl"></div>
                    <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                                <?= mtq_icon('menu', 'h-4 w-4') ?>
                            </button>
                            <div>
                                <div class="flex items-center gap-2 text-cyan-300">
                                    <?= mtq_icon('trophy', 'h-5 w-5') ?>
                                    <p class="text-sm font-semibold uppercase tracking-widest">Rekap Penilaian</p>
                                </div>
                                <h1 class="mt-2 text-3xl sm:text-4xl font-black tracking-tight">
                                    <span class="gradient-text">Detail Hasil Nilai</span>
                                </h1>
                                <p class="mt-2 max-w-xl text-sm text-slate-400">
                                    <?php if ($isParticipant): ?>
                                        Ringkasan nilai personal Anda, komponen penilaian, dan histori performa lengkap dalam satu tampilan.
                                    <?php else: ?>
                                        Lihat skor total, komponen penilaian, dan histori performa peserta dalam satu halaman yang komprehensif.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-200">
                                <?= mtq_icon('file-text', 'h-4 w-4') ?>
                                <?= e($resultStats['entries']) ?> Entri Nilai
                            </div>
                        </div>
                    </div>
                </header>

                <?php if (!$isParticipant && $canViewScoreDetail): ?>
                    <!-- Filter Section -->
                    <section class="glass-card rounded-[2rem] p-5 sm:p-6 animate-slide-up" style="animation-delay: 0.1s;">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-400/20 bg-cyan-400/10">
                                <?= mtq_icon('search', 'h-5 w-5 text-cyan-300') ?>
                            </div>
                            <div>
                                <p class="section-kicker">Filter</p>
                                <h3 class="text-lg font-bold text-white">Cari Peserta</h3>
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
                    <!-- Latest Score -->
                    <div class="stat-card rounded-2xl border border-cyan-400/20 bg-gradient-to-br from-cyan-500/10 to-sky-500/5 p-5 text-cyan-300 hover-lift animate-slide-up" style="animation-delay: 0.2s;">
                        <div class="flex items-center justify-between">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-400/30 bg-cyan-400/20">
                                <?= mtq_icon('clock', 'h-5 w-5') ?>
                            </span>
                            <span class="text-xs font-medium uppercase tracking-wider opacity-70">Terakhir</span>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-black text-white"><?= e($resultStats['latest']) ?></p>
                            <p class="mt-1 text-sm text-cyan-200/70">Nilai Akhir</p>
                        </div>
                    </div>

                    <!-- Best Score -->
                    <div class="stat-card rounded-2xl border border-amber-400/20 bg-gradient-to-br from-amber-500/10 to-orange-500/5 p-5 text-amber-300 hover-lift animate-slide-up" style="animation-delay: 0.3s;">
                        <div class="flex items-center justify-between">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-400/30 bg-amber-400/20">
                                <?= mtq_icon('trophy', 'h-5 w-5') ?>
                            </span>
                            <span class="text-xs font-medium uppercase tracking-wider opacity-70">Terbaik</span>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-black text-white"><?= e($resultStats['best']) ?></p>
                            <p class="mt-1 text-sm text-amber-200/70">Skor Tertinggi</p>
                        </div>
                    </div>

                    <!-- Average Score -->
                    <div class="stat-card rounded-2xl border border-violet-400/20 bg-gradient-to-br from-violet-500/10 to-purple-500/5 p-5 text-violet-300 hover-lift animate-slide-up" style="animation-delay: 0.4s;">
                        <div class="flex items-center justify-between">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-violet-400/30 bg-violet-400/20">
                                <?= mtq_icon('chart', 'h-5 w-5') ?>
                            </span>
                            <span class="text-xs font-medium uppercase tracking-wider opacity-70">Rata-rata</span>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-black text-white"><?= e($resultStats['average']) ?></p>
                            <p class="mt-1 text-sm text-violet-200/70">Semua Penilaian</p>
                        </div>
                    </div>

                    <!-- Entries Count -->
                    <div class="stat-card rounded-2xl border border-emerald-400/20 bg-gradient-to-br from-emerald-500/10 to-teal-500/5 p-5 text-emerald-300 hover-lift animate-slide-up" style="animation-delay: 0.5s;">
                        <div class="flex items-center justify-between">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-400/30 bg-emerald-400/20">
                                <?= mtq_icon('layers', 'h-5 w-5') ?>
                            </span>
                            <span class="text-xs font-medium uppercase tracking-wider opacity-70">Total</span>
                        </div>
                        <div class="mt-4">
                            <p class="text-3xl font-black text-white"><?= e($resultStats['entries']) ?></p>
                            <p class="mt-1 text-sm text-emerald-200/70">Entri Penilaian</p>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1fr_1.2fr]">
                    <!-- Participant Profile Card -->
                    <div class="relative overflow-hidden rounded-[2rem] border border-cyan-400/20 bg-gradient-to-br from-slate-900 via-sky-950/80 to-blue-950/70 p-6 shadow-[0_0_60px_-20px_rgba(34,211,238,0.3)] animate-slide-up" style="animation-delay: 0.3s;">
                        <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-cyan-400/10 blur-3xl"></div>
                        <div class="absolute -left-3 -bottom-3 h-20 w-20 rounded-full bg-blue-400/10 blur-3xl"></div>
                        <div class="relative z-10">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-cyan-400/30 bg-gradient-to-br from-cyan-400/20 to-sky-500/20 shadow-lg shadow-cyan-400/10">
                                            <?php if ($selectedParticipant): ?>
                                                <span class="text-2xl font-black text-cyan-200"><?= e(substr($selectedParticipant->name, 0, 1)) ?></span>
                                            <?php else: ?>
                                                <?= mtq_icon('user', 'h-8 w-8 text-cyan-300') ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($selectedParticipant): ?>
                                            <div class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-900 bg-emerald-400">
                                                <?= mtq_icon('check', 'h-3 w-3 text-slate-900') ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="section-kicker">Profil Peserta</p>
                                        <h2 class="mt-1 text-xl font-bold text-white"><?= e($selectedParticipant?->name ?? 'Belum ada peserta dipilih') ?></h2>
                                        <?php if ($selectedParticipant): ?>
                                            <p class="mt-1 text-sm text-cyan-200/70"><?= e(($selectedParticipant->category?->branch ?? '-').' - '.($selectedParticipant->category?->name ?? '-')) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($selectedParticipant): ?>
                                    <div class="flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1.5 text-xs font-semibold text-emerald-200">
                                        <?= mtq_icon('check-circle', 'h-3.5 w-3.5') ?>
                                        <?= e($selectedParticipant->registration_number) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($selectedParticipant): ?>
                                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                                        <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-slate-500">
                                            <?= mtq_icon('map-pin', 'h-3.5 w-3.5') ?>
                                            Kecamatan
                                        </p>
                                        <p class="mt-2 text-base font-semibold text-white"><?= e($selectedParticipant->district?->name ?? '-') ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                                        <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-slate-500">
                                            <?= mtq_icon('building', 'h-3.5 w-3.5') ?>
                                            Kafilah
                                        </p>
                                        <p class="mt-2 text-base font-semibold text-white"><?= e($selectedParticipant->institution ?? '-') ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                                        <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-slate-500">
                                            <?= mtq_icon('id-card', 'h-3.5 w-3.5') ?>
                                            No. Lot
                                        </p>
                                        <p class="mt-2 text-base font-semibold text-white"><?= e($selectedParticipant->lot_number ?? '-') ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-4">
                                        <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-slate-500">
                                            <?= mtq_icon('shield', 'h-3.5 w-3.5') ?>
                                            Status
                                        </p>
                                        <p class="mt-2 text-base font-semibold <?= $selectedParticipant->verification_status === 'verified' ? 'text-emerald-300' : 'text-amber-300' ?>">
                                            <?= e(ucfirst((string) ($selectedParticipant->verification_status ?? 'belum tersedia'))) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mt-6 flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-700 bg-slate-800/30 p-8 text-center">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-full border border-slate-600 bg-slate-800 text-slate-500">
                                        <?= mtq_icon('search', 'h-6 w-6') ?>
                                    </div>
                                    <p class="mt-4 text-sm text-slate-400">Pilih peserta untuk melihat hasil nilai.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Score Breakdown Card -->
                    <div class="glass-card rounded-[2rem] p-6 animate-slide-up" style="animation-delay: 0.4s;">
                        <?php if (!$canViewScoreDetail): ?>
                        <!-- Hidden for officials -->
                        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-700 bg-slate-800/30 p-8 text-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-600 bg-slate-800 text-slate-500">
                                <?= mtq_icon('lock', 'h-5 w-5') ?>
                            </div>
                            <h3 class="mt-4 text-lg font-bold text-slate-300">Detail Nilai Ditutup</h3>
                            <p class="mt-2 text-sm text-slate-400">Detail komponen nilai (breakdown) tidak dapat dilihat oleh official. Hubungi panitia untuk informasi lebih lanjut.</p>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-400/20 bg-cyan-400/10">
                                <?= mtq_icon('check-circle', 'h-5 w-5 text-cyan-300') ?>
                            </div>
                            <div>
                                <p class="section-kicker">Komponen Nilai</p>
                                <h3 class="text-lg font-bold text-white">Breakdown Penilaian</h3>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <?php
                            // Filter out null values and get valid breakdown items
                            $validBreakdown = null;
                            if ($latestEntry && !empty($latestBreakdown)) {
                                $validBreakdown = array_filter($latestBreakdown, fn($v) => $v !== null && $v !== '');
                            }
                            ?>
                            <?php if (!$latestEntry || empty($validBreakdown)): ?>
                                <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-700 bg-slate-800/30 p-8 text-center">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-600 bg-slate-800 text-slate-500">
                                        <?= mtq_icon('file-text', 'h-5 w-5') ?>
                                    </div>
                                    <p class="mt-4 text-sm text-slate-400">Belum ada komponen nilai untuk peserta ini.</p>
                                </div>
                            <?php else: ?>
                                <?php
                                // Recalculate max value based on valid breakdown
                                $maxBreakdownValue = max(array_map('floatval', $validBreakdown)) ?: 100;
                                foreach ($validBreakdown as $key => $value):
                                    $percentage = $maxBreakdownValue > 0 ? ((float)$value / $maxBreakdownValue) * 100 : 0;
                                    $colorClass = $percentage >= 80 ? 'from-emerald-400 to-emerald-500' : ($percentage >= 60 ? 'from-cyan-400 to-cyan-500' : ($percentage >= 40 ? 'from-amber-400 to-amber-500' : 'from-rose-400 to-rose-500'));
                                ?>
                                    <div class="group rounded-xl border border-slate-700/50 bg-slate-800/50 p-4 transition-all hover:border-cyan-400/30 hover:bg-slate-800/70">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-600 bg-slate-700/50 text-xs font-bold text-slate-400">
                                                    <?= e(strtoupper(substr($branchCriteria[$key] ?? ucwords(str_replace('_', ' ', (string) $key)), 0, 1))) ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-white"><?= e($branchCriteria[$key] ?? ucwords(str_replace('_', ' ', (string) $key))) ?></p>
                                                    <p class="text-xs text-slate-500"><?= e($latestEntry->judging_round ?: 'Babak belum ditentukan') ?> • <?= e(count($latestJudges)) ?> Hakim</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xl font-black text-cyan-200"><?= e(number_format((float) $value, 2)) ?></p>
                                                <p class="text-xs text-slate-500">/ 100</p>
                                            </div>
                                        </div>
                                        <div class="mt-3 progress-bar">
                                            <div class="progress-fill bg-gradient-to-r <?= e($colorClass) ?>" style="width: <?= e($percentage) ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Total Score Highlight -->
                                <div class="relative mt-6 overflow-hidden rounded-2xl border border-cyan-400/30 bg-gradient-to-r from-cyan-500/10 to-sky-500/10 p-5">
                                    <div class="absolute -right-4 -top-4 h-20 w-20 rounded-full bg-cyan-400/10 blur-2xl"></div>
                                    <div class="relative flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-cyan-200/70">Total Nilai Akhir</p>
                                            <p class="mt-1 text-xs text-slate-500">Rata-rata dari <?= e(count($latestJudges)) ?> hakim</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-3xl font-black text-white"><?= e(number_format((float) ($latestEntry->average_score ?? $latestEntry->score ?? 0), 2)) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </section>

                <?php if ($canViewScoreDetail): ?>
                <section class="glass-card rounded-[2rem] p-6 animate-slide-up" style="animation-delay: 0.5s;">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-violet-400/20 bg-violet-400/10">
                            <?= mtq_icon('clock', 'h-5 w-5 text-violet-300') ?>
                        </div>
                        <div>
                            <p class="section-kicker">Timeline</p>
                            <h3 class="text-lg font-bold text-white">Riwayat Penilaian</h3>
                        </div>
                    </div>

                    <div class="relative">
                        <?php if ($scoreTimeline->isEmpty()): ?>
                            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-700 bg-slate-800/30 p-10 text-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full border border-slate-600 bg-slate-800 text-slate-500">
                                    <?= mtq_icon('clock', 'h-6 w-6') ?>
                                </div>
                                <p class="mt-4 text-sm text-slate-400">Belum ada nilai yang tercatat untuk peserta ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="relative space-y-4">
                                <!-- Timeline Line -->
                                <div class="absolute left-[19px] top-0 h-full w-0.5 bg-gradient-to-b from-cyan-400/50 via-slate-700 to-transparent"></div>

                                <?php foreach ($scoreTimeline as $index => $entry): ?>
                                    <?php
                                    // Check if new aggregated format
                                    $isNewFormat = $entry->scores && is_array($entry->scores);
                                    $judgeCount = $isNewFormat ? count($entry->scores) : 1;
                                    $displayScore = $isNewFormat
                                        ? number_format((float) ($entry->average_score ?? 0), 2)
                                        : number_format((float) ($entry->score ?? 0), 2);

                                    // Get breakdown
                                    $breakdown = null;
                                    if ($isNewFormat) {
                                        $firstJudge = array_key_first($entry->scores);
                                        $firstJudgeData = $entry->scores[$firstJudge] ?? [];

                                        // Use point_averages (aggregated from all judges) if available
                                        if (isset($firstJudgeData['point_averages']) && is_array($firstJudgeData['point_averages'])) {
                                            $breakdown = $firstJudgeData['point_averages'];
                                        } elseif (isset($firstJudgeData['breakdown']) && is_array($firstJudgeData['breakdown'])) {
                                            $breakdown = $firstJudgeData['breakdown'];
                                        } elseif (isset($firstJudgeData['scores']) && is_array($firstJudgeData['scores'])) {
                                            $breakdown = $firstJudgeData['scores'];
                                        }
                                    } else {
                                        $breakdown = $entry->score_breakdown;
                                    }

                                    // Filter out null values
                                    $validBreakdown = is_array($breakdown) ? array_filter($breakdown, fn($v) => $v !== null && $v !== '') : [];

                                    // Timeline dot color based on index
                                    $dotColor = $index === 0 ? 'bg-cyan-400 border-cyan-400 shadow-lg shadow-cyan-400/50' : 'bg-slate-600 border-slate-600';
                                    ?>
                                    <div class="relative pl-12 animate-slide-up" style="animation-delay: <?= e(0.6 + $index * 0.1) ?>s;">
                                        <!-- Timeline Dot -->
                                        <div class="absolute left-0 flex h-10 w-10 items-center justify-center rounded-full border-2 <?= e($dotColor) ?> bg-slate-900">
                                            <?php if ($index === 0): ?>
                                                <?= mtq_icon('zap', 'h-4 w-4 text-slate-900') ?>
                                            <?php else: ?>
                                                <span class="text-xs font-bold text-slate-400"><?= e($index + 1) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="rounded-2xl border border-slate-700/50 bg-slate-800/50 p-4 transition-all hover:border-cyan-400/30 hover:bg-slate-800/70">
                                            <div class="flex flex-wrap items-center justify-between gap-4">
                                                <div>
                                                    <?php if ($isNewFormat): ?>
                                                        <div class="flex items-center gap-2">
                                                            <span class="rounded-lg border border-cyan-400/30 bg-cyan-400/10 px-2 py-0.5 text-xs font-semibold text-cyan-200">
                                                                <?= e($judgeCount) ?> Hakim
                                                            </span>
                                                            <?php if ($entry->judging_round): ?>
                                                                <span class="rounded-lg border border-slate-600/50 bg-slate-700/50 px-2 py-0.5 text-xs font-medium text-slate-300">
                                                                    <?= e($entry->judging_round) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="mt-2 text-xs text-slate-500">
                                                            <?php
                                                            $judgeNames = array_keys($entry->scores);
                                                            echo implode(', ', array_slice($judgeNames, 0, 3));
                                                            if (count($judgeNames) > 3) echo '...';
                                                            ?>
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="font-semibold text-white"><?= e($entry->judge_name) ?></p>
                                                        <div class="mt-1 flex items-center gap-2">
                                                            <?php if ($entry->judging_round): ?>
                                                                <span class="rounded-lg border border-slate-600/50 bg-slate-700/50 px-2 py-0.5 text-xs font-medium text-slate-300">
                                                                    <?= e($entry->judging_round) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <span class="text-xs text-slate-500"><?= e(optional($entry->submitted_at)->format('d M Y, H:i')) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-2xl font-black text-cyan-200"><?= e($displayScore) ?></p>
                                                    <p class="text-xs text-slate-500">Total Nilai</p>
                                                </div>
                                            </div>

                                            <?php if (! empty($validBreakdown)): ?>
                                                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                                    <?php foreach ($validBreakdown as $key => $value): ?>
                                                        <div class="flex items-center justify-between rounded-lg border border-slate-700/50 bg-slate-900/50 px-3 py-2">
                                                            <span class="text-xs text-slate-400"><?= e($branchCriteria[$key] ?? ucwords(str_replace('_', ' ', (string) $key))) ?></span>
                                                            <span class="text-sm font-semibold text-white"><?= e(number_format((float) $value, 2)) ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($entry->remarks): ?>
                                                <div class="mt-4 rounded-lg border border-slate-700/50 bg-slate-900/30 px-4 py-3">
                                                    <p class="text-xs text-slate-500">Catatan:</p>
                                                    <p class="mt-1 text-sm leading-relaxed text-slate-300"><?= e($entry->remarks) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

<?php if ($hasRankings): ?>
                <!-- Rankings Section - Trophy Podium Design -->
                <section class="animate-slide-up" style="animation-delay: 0.6s;">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-amber-400/30 bg-gradient-to-br from-amber-400/20 to-orange-500/10 shadow-lg shadow-amber-400/10 animate-float">
                                <?= mtq_icon('trophy', 'h-6 w-6 text-amber-300') ?>
                            </div>
                            <div>
                                <p class="section-kicker">Peringkat</p>
                                <h2 class="text-xl font-black text-white">Klasemen Hasil Nilai</h2>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-amber-200/70">
                            <?= mtq_icon('star', 'h-4 w-4') ?>
                            <?= count($rankings) ?> Konfigurasi Aktif
                        </div>
                    </div>

                    <div class="space-y-8">
                        <?php foreach ($rankings as $rankingConfig): ?>
                            <?php
                            $rankingData = $rankingConfig['data'] ?? [];
                            $putraRankings = $rankingData['putra'] ?? [];
                            $putriRankings = $rankingData['putri'] ?? [];
                            $showPutra = in_array($rankingConfig['gender'], ['putra', 'all']);
                            $showPutri = in_array($rankingConfig['gender'], ['putri', 'all']);
                            $hasAnyData = !empty($putraRankings) || !empty($putriRankings);
                            $isFinalist = ($rankingConfig['is_finalist_announcement'] ?? false);
                            $finalistDisplayName = $rankingConfig['finalist_display_name'] ?? 'Pengumuman Finalis';
                            ?>
                            <div class="relative overflow-hidden rounded-[2rem] border <?= $isFinalist ? 'border-amber-400/40 bg-gradient-to-br from-amber-900/40 via-amber-950/30 to-slate-900' : 'border-amber-400/20 bg-gradient-to-br from-slate-900 via-amber-950/30 to-slate-900' ?> p-6 shadow-[0_0_60px_-20px_rgba(251,191,36,0.15)]">
                                <!-- Decorative Background -->
                                <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-amber-400/10 blur-3xl"></div>
                                <div class="absolute -left-4 -bottom-4 h-24 w-24 rounded-full bg-orange-400/10 blur-3xl"></div>

                                <!-- Header -->
                                <div class="relative z-10 mb-6">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <?php if ($isFinalist): ?>
                                                    <span class="flex items-center gap-2 rounded-full border border-amber-400/50 bg-amber-400/20 px-3 py-1.5 text-sm font-bold text-amber-200">
                                                        <?= mtq_icon('star', 'h-4 w-4') ?>
                                                        FINALIS
                                                    </span>
                                                <?php endif; ?>
                                                <h3 class="text-xl font-bold text-white <?= empty($rankingConfig['is_active']) ? 'opacity-50' : '' ?>"><?= e($rankingConfig['name']) ?></h3>
                                                <?php if (empty($rankingConfig['is_active'])): ?>
                                                    <span class="flex items-center gap-1 rounded-full border border-slate-500/50 bg-slate-500/20 px-2 py-0.5 text-xs font-medium text-slate-400">
                                                        <?= mtq_icon('eye-off', 'h-3 w-3') ?> Nonaktif
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($isFinalist): ?>
                                                    <span class="rounded-lg border border-amber-400/30 bg-amber-400/10 px-2.5 py-1 text-sm font-semibold text-amber-200">
                                                        <?= e($finalistDisplayName) ?>
                                                    </span>
                                                <?php elseif ($rankingConfig['appearance_day'] !== null): ?>
                                                    <span class="flex items-center gap-1 rounded-lg border border-cyan-400/30 bg-cyan-400/10 px-2.5 py-1 text-xs font-semibold text-cyan-200">
                                                        <?= mtq_icon('calendar', 'h-3.5 w-3.5') ?>
                                                        Sesi <?= e(($rankingConfig['appearance_day'] ?? 0) + 1) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($rankingData['schedule_date']) && !$isFinalist): ?>
                                                    <span class="flex items-center gap-1 rounded-lg border border-violet-400/30 bg-violet-400/10 px-2.5 py-1 text-xs font-semibold text-violet-200">
                                                        <?= mtq_icon('clock', 'h-3.5 w-3.5') ?>
                                                        <?= e(Carbon::parse($rankingData['schedule_date'])->locale('id')->isoFormat('ddd, D MMM YYYY')) ?>
                                                        <?php if (!empty($rankingData['schedule_time'])): ?>
                                                            Pukul <?= e(Carbon::parse($rankingData['schedule_time'])->format('H:i')) ?>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mt-1 text-sm <?= $isFinalist ? 'text-amber-300/70' : 'text-amber-200/70' ?>">
                                                <?php if ($isFinalist): ?>
                                                    Juara Babak Penyisihan untuk晋级 Final
                                                <?php else: ?>
                                                    <?= e($rankingConfig['display_label']) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <?php if ($isFinalist): ?>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-400/20 px-3 py-1 text-xs font-bold text-amber-200">
                                                <?= mtq_icon('star', 'h-3 w-3') ?> 3 Besar Teratas
                                            </span>
                                        </div>
                                        <?php else: ?>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <?php if ($showPutra && ($rankingData['putra_count'] ?? 0) > 0): ?>
                                                <span class="flex items-center gap-1.5 rounded-full border border-blue-400/30 bg-blue-400/10 px-3 py-1 text-xs font-semibold text-blue-200">
                                                    <span class="text-sm">&#9794;</span> <?= e($rankingData['putra_count']) ?> Putra
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($showPutri && ($rankingData['putri_count'] ?? 0) > 0): ?>
                                                <span class="flex items-center gap-1.5 rounded-full border border-pink-400/30 bg-pink-400/10 px-3 py-1 text-xs font-semibold text-pink-200">
                                                    <span class="text-sm">&#9793;</span> <?= e($rankingData['putri_count']) ?> Putri
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!$hasAnyData): ?>
                                    <!-- Empty State -->
                                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-amber-400/20 bg-amber-400/5 p-10 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-full border border-amber-400/30 bg-amber-400/10 text-amber-300">
                                            <?= mtq_icon('trophy', 'h-6 w-6') ?>
                                        </div>
                                        <p class="mt-4 text-sm text-slate-400">Belum ada data <?= $isFinalist ? 'finalis' : 'ranking' ?> untuk konfigurasi ini.</p>
                                    </div>
                                <?php elseif ($isFinalist): ?>
                                    <!-- Finalist Display - More prominent winner cards (Top 3 only) -->
                                    <div class="relative z-10">
                                        <?php
                                        $finalistPutra = array_slice(array_filter($putraRankings, fn($p) => ($p['rank'] ?? 0) <= 3), 0, 3);
                                        $finalistPutri = array_slice(array_filter($putriRankings, fn($p) => ($p['rank'] ?? 0) <= 3), 0, 3);
                                        ?>
                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-<?= (count($finalistPutra) > 0 && count($finalistPutri) > 0) ? '2' : '1' ?>">
                                            <?php if (count($finalistPutra) > 0): ?>
                                                <!-- Finalis Putra -->
                                                <div class="space-y-3">
                                                    <div class="flex items-center gap-3 rounded-xl border border-amber-400/30 bg-gradient-to-r from-amber-900/30 to-transparent px-4 py-3">
                                                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-xl font-black text-white shadow-lg shadow-amber-500/40">&#9794;</span>
                                                        <div>
                                                            <h4 class="font-bold text-amber-200">Finalis Putra</h4>
                                                            <p class="text-xs text-amber-300/70"><?= e($finalistDisplayName) ?></p>
                                                        </div>
                                                    </div>
                                                    <?php foreach ($finalistPutra as $idx => $p): ?>
                                                        <?php
                                                        $isSelected = $p['id'] == $selectedParticipant?->id;
                                                        $isTop = $idx < 3;
                                                        $rankLabel = 'Finalis ' . ($idx + 1);
                                                        ?>
                                                        <div class="group relative flex items-center gap-4 rounded-xl border px-4 py-3 transition-all <?= $isSelected ? 'border-cyan-400/50 bg-cyan-400/10' : ($isTop ? 'border-amber-400/40 bg-gradient-to-r from-amber-900/20 to-transparent' : 'border-slate-700/50 bg-slate-800/30 hover:border-amber-400/30 hover:bg-slate-800/60') ?>">
                                                            <?php if ($isTop): ?>
                                                                <div class="absolute -left-1 top-1/2 -translate-y-1/2 -translate-x-1/2">
                                                                    <?= mtq_icon('star', 'h-5 w-5 text-amber-400') ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="ml-<?= $isTop ? '3' : '0' ?> flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-black <?= $isTop ? 'bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-lg' : 'border border-slate-600 bg-slate-800 text-slate-300' ?>">
                                                                <?= $idx + 1 ?>
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <p class="truncate font-semibold <?= $isSelected ? 'text-cyan-200' : 'text-white' ?>">
                                                                    <?= e($p['name']) ?>
                                                                </p>
                                                                <span class="inline-flex items-center gap-1 rounded-full border <?= $isTop ? 'border-amber-400/40 bg-amber-400/20 text-amber-200' : 'border-slate-600 bg-slate-800 text-slate-400' ?> px-2 py-0.5 text-xs font-bold">
                                                                    LOT <?= e($p['lot_number']) ?>
                                                                </span>
                                                            </div>
                                                            <div class="text-right shrink-0">
                                                                <?php if ($p['has_score']): ?>
                                                                    <p class="text-lg font-bold <?= $isTop ? 'text-amber-200' : 'text-emerald-300' ?>">
                                                                        <?= e(number_format($p['average_score'], 2)) ?>
                                                                    </p>
                                                                <?php else: ?>
                                                                    <span class="text-xs text-slate-500">Belum</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (count($finalistPutri) > 0): ?>
                                                <!-- Finalis Putri -->
                                                <div class="space-y-3">
                                                    <div class="flex items-center gap-3 rounded-xl border border-pink-400/30 bg-gradient-to-r from-pink-900/30 to-transparent px-4 py-3">
                                                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-pink-400 to-rose-500 text-xl font-black text-white shadow-lg shadow-pink-500/40">&#9793;</span>
                                                        <div>
                                                            <h4 class="font-bold text-pink-200">Finalis Putri</h4>
                                                            <p class="text-xs text-pink-300/70"><?= e($finalistDisplayName) ?></p>
                                                        </div>
                                                    </div>
                                                    <?php foreach ($finalistPutri as $idx => $p): ?>
                                                        <?php
                                                        $isSelected = $p['id'] == $selectedParticipant?->id;
                                                        $isTop = $idx < 3;
                                                        ?>
                                                        <div class="group relative flex items-center gap-4 rounded-xl border px-4 py-3 transition-all <?= $isSelected ? 'border-cyan-400/50 bg-cyan-400/10' : ($isTop ? 'border-pink-400/40 bg-gradient-to-r from-pink-900/20 to-transparent' : 'border-slate-700/50 bg-slate-800/30 hover:border-pink-400/30 hover:bg-slate-800/60') ?>">
                                                            <?php if ($isTop): ?>
                                                                <div class="absolute -left-1 top-1/2 -translate-y-1/2 -translate-x-1/2">
                                                                    <?= mtq_icon('star', 'h-5 w-5 text-pink-400') ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="ml-<?= $isTop ? '3' : '0' ?> flex h-10 w-10 shrink-0 items-center justify-center rounded-xl font-black <?= $isTop ? 'bg-gradient-to-br from-pink-400 to-rose-500 text-white shadow-lg' : 'border border-slate-600 bg-slate-800 text-slate-300' ?>">
                                                                <?= $idx + 1 ?>
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <p class="truncate font-semibold <?= $isSelected ? 'text-cyan-200' : 'text-white' ?>">
                                                                    <?= e($p['name']) ?>
                                                                </p>
                                                                <span class="inline-flex items-center gap-1 rounded-full border <?= $isTop ? 'border-pink-400/40 bg-pink-400/20 text-pink-200' : 'border-slate-600 bg-slate-800 text-slate-400' ?> px-2 py-0.5 text-xs font-bold">
                                                                    LOT <?= e($p['lot_number']) ?>
                                                                </span>
                                                            </div>
                                                            <div class="text-right shrink-0">
                                                                <?php if ($p['has_score']): ?>
                                                                    <p class="text-lg font-bold <?= $isTop ? 'text-pink-200' : 'text-emerald-300' ?>">
                                                                        <?= e(number_format($p['average_score'], 2)) ?>
                                                                    </p>
                                                                <?php else: ?>
                                                                    <span class="text-xs text-slate-500">Belum</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Normal Ranking Display -->
                                    <div class="relative z-10 grid gap-6 lg:grid-cols-2">
                                        <?php if ($showPutra && !empty($putraRankings)): ?>
                                            <!-- Putra Rankings -->
                                            <div class="overflow-hidden rounded-2xl border border-blue-500/20 bg-gradient-to-b from-blue-950/40 to-slate-900/80">
                                                <div class="flex items-center gap-3 border-b border-blue-500/20 bg-blue-950/30 px-5 py-4">
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500 text-lg font-black text-white shadow-lg shadow-blue-500/30">&#9794;</span>
                                                    <h4 class="text-base font-bold text-blue-200"><?= $isFinalist ? 'Finalis Putra' : 'Klasemen Putra' ?></h4>
                                                </div>
                                                <div class="max-h-[400px] overflow-y-auto p-4">
                                                    <div class="space-y-2">
                                                        <?php foreach ($putraRankings as $idx => $p): ?>
                                                            <?php
                                                            $isSelected = $p['id'] == $selectedParticipant?->id;
                                                            $isTop3 = $p['rank'] <= 3;
                                                            $rankClass = match($p['rank']) {
                                                                1 => 'rank-gold',
                                                                2 => 'rank-silver',
                                                                3 => 'rank-bronze',
                                                                default => 'bg-slate-700 text-slate-300'
                                                            };
                                                            ?>
                                                            <div class="group relative flex items-center gap-3 rounded-xl border px-3 py-2.5 transition-all <?= $isSelected ? 'border-cyan-400/50 bg-cyan-400/10' : 'border-slate-700/50 bg-slate-800/30 hover:border-blue-400/30 hover:bg-slate-800/60' ?>">
                                                                <?php if ($isTop3): ?>
                                                                    <div class="absolute -left-1 top-1/2 -translate-y-1/2">
                                                                        <?= mtq_icon('star', 'h-4 w-4 text-amber-400') ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="rank-badge ml-<?= $isTop3 ? '2' : '0' ?> h-8 w-8 rounded-lg text-sm <?= e($rankClass) ?>">
                                                                    <?= e($p['rank']) ?>
                                                                </div>
                                                                <div class="min-w-0 flex-1">
                                                                    <p class="truncate text-sm font-semibold <?= $isSelected ? 'text-cyan-200' : 'text-white' ?>">
                                                                        <?= e($p['name']) ?>
                                                                    </p>
                                                                    <p class="truncate text-xs text-slate-500">
                                                                        <?= e($p['district_name']) ?> • Lot <?= e($p['lot_number']) ?>
                                                                    </p>
                                                                </div>
                                                                <div class="text-right shrink-0">
                                                                    <?php if ($p['has_score']): ?>
                                                                        <p class="text-base font-bold <?= $isTop3 ? 'text-amber-200' : 'text-emerald-300' ?>">
                                                                            <?= e(number_format($p['average_score'], 2)) ?>
                                                                        </p>
                                                                    <?php else: ?>
                                                                        <span class="text-xs text-slate-500">Belum</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($showPutri && !empty($putriRankings)): ?>
                                            <!-- Putri Rankings -->
                                            <div class="overflow-hidden rounded-2xl border border-pink-500/20 bg-gradient-to-b from-pink-950/40 to-slate-900/80">
                                                <div class="flex items-center gap-3 border-b border-pink-500/20 bg-pink-950/30 px-5 py-4">
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-pink-500 text-lg font-black text-white shadow-lg shadow-pink-500/30">&#9793;</span>
                                                    <h4 class="text-base font-bold text-pink-200"><?= $isFinalist ? 'Finalis Putri' : 'Klasemen Putri' ?></h4>
                                                </div>
                                                <div class="max-h-[400px] overflow-y-auto p-4">
                                                    <div class="space-y-2">
                                                        <?php foreach ($putriRankings as $idx => $p): ?>
                                                            <?php
                                                            $isSelected = $p['id'] == $selectedParticipant?->id;
                                                            $isTop3 = $p['rank'] <= 3;
                                                            $rankClass = match($p['rank']) {
                                                                1 => 'rank-gold',
                                                                2 => 'rank-silver',
                                                                3 => 'rank-bronze',
                                                                default => 'bg-slate-700 text-slate-300'
                                                            };
                                                            ?>
                                                            <div class="group relative flex items-center gap-3 rounded-xl border px-3 py-2.5 transition-all <?= $isSelected ? 'border-cyan-400/50 bg-cyan-400/10' : 'border-slate-700/50 bg-slate-800/30 hover:border-pink-400/30 hover:bg-slate-800/60' ?>">
                                                                <?php if ($isTop3): ?>
                                                                    <div class="absolute -left-1 top-1/2 -translate-y-1/2">
                                                                        <?= mtq_icon('star', 'h-4 w-4 text-amber-400') ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="rank-badge ml-<?= $isTop3 ? '2' : '0' ?> h-8 w-8 rounded-lg text-sm <?= e($rankClass) ?>">
                                                                    <?= e($p['rank']) ?>
                                                                </div>
                                                                <div class="min-w-0 flex-1">
                                                                    <p class="truncate text-sm font-semibold <?= $isSelected ? 'text-cyan-200' : 'text-white' ?>">
                                                                        <?= e($p['name']) ?>
                                                                    </p>
                                                                    <p class="truncate text-xs text-slate-500">
                                                                        <?= e($p['district_name']) ?> • Lot <?= e($p['lot_number']) ?>
                                                                    </p>
                                                                </div>
                                                                <div class="text-right shrink-0">
                                                                    <?php if ($p['has_score']): ?>
                                                                        <p class="text-base font-bold <?= $isTop3 ? 'text-amber-200' : 'text-emerald-300' ?>">
                                                                            <?= e(number_format($p['average_score'], 2)) ?>
                                                                        </p>
                                                                    <?php else: ?>
                                                                        <span class="text-xs text-slate-500">Belum</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php else: ?>
                <!-- No Rankings - Placeholder -->
                <section class="glass-card rounded-[2rem] p-8 text-center animate-slide-up" style="animation-delay: 0.6s;">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border border-slate-700 bg-slate-800/50 text-slate-500">
                        <?= mtq_icon('trophy', 'h-8 w-8') ?>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-white">Ranking Belum Tersedia</h3>
                    <p class="mt-2 max-w-md mx-auto text-sm text-slate-400">
                        <?php if (empty($selectedParticipant)): ?>
                            Pilih peserta terlebih dahulu untuk melihat ranking atau hubungi admin untuk mengkonfigurasi sistem ranking.
                        <?php else: ?>
                            Ranking untuk kategori ini belum dikonfigurasi. Hubungi admin untuk mengatur sistem ranking.
                        <?php endif; ?>
                    </p>
                    <?php if ($user?->role === 'admin'): ?>
                        <a href="<?= e(route('ranking.settings.index')) ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl border border-amber-400/30 bg-amber-400/10 px-5 py-2.5 text-sm font-semibold text-amber-200 transition-all hover:bg-amber-400/20">
                            <?= mtq_icon('settings', 'h-4 w-4') ?>
                            Kelola Pengaturan Ranking
                        </a>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
