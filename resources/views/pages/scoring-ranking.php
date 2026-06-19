<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rankedParticipants = $rankedParticipants ?? collect();
$putraRankings = $putraRankings ?? collect();
$putriRankings = $putriRankings ?? collect();
$participantsByDay = $participantsByDay ?? [];
$selectedCategory = $selectedCategory ?? null;
$selectedJudgingRound = $selectedJudgingRound ?? 'Penyisihan';
$selectedAppearanceDay = $selectedAppearanceDay;
$categoryLabel = $categoryLabel ?? 'Semua Golongan';
$stats = $stats ?? [];
$filters = $filters ?? [];
$scoringSetting = $scoringSetting ?? null;
$appearanceSchedule = $appearanceSchedule ?? null;
$dayRanges = $dayRanges ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Ranking Sesi Penilaian') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
    <style>
        .glass-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.7) 100%);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .rank-badge {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 800;
            font-size: 1rem;
        }
        .rank-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #1e1b4b; }
        .rank-2 { background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); color: #1e1b4b; }
        .rank-3 { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: #fff; }
        .rank-other { background: rgba(51, 65, 85, 0.8); color: #e2e8f0; border: 1px solid rgba(148, 163, 184, 0.2); }
        .putra-badge { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #fff; }
        .putri-badge { background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); color: #fff; }
        .participant-row { transition: all 0.2s ease; }
        .participant-row:hover { background: rgba(34, 211, 238, 0.08); }
        .glow-amber { box-shadow: 0 0 20px rgba(251, 191, 36, 0.15), 0 0 40px rgba(251, 191, 36, 0.05); }
        .glow-blue { box-shadow: 0 0 20px rgba(59, 130, 246, 0.15); }
        .glow-pink { box-shadow: 0 0 20px rgba(236, 72, 153, 0.15); }
    </style>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="hero-orb hero-orb-amber right-[-7rem] top-10 h-72 w-72"></div>

        <!-- Header -->
        <header class="glass-card rounded-[2rem] p-4 sm:p-6 glow-amber mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="icon-chip"><?= mtq_icon('trophy') ?></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('chart', 'h-4 w-4 text-amber-300') ?>
                            <p class="section-kicker">Ranking Penilaian</p>
                        </div>
                        <h2 class="mt-1 sm:mt-2 text-xl sm:text-3xl font-black tracking-tight">
                            <span class="gradient-text">Ranking per Putra & Putri</span>
                        </h2>
                        <?php if ($selectedCategory): ?>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2 sm:px-3 py-1 text-xs font-semibold text-cyan-200">
                                    <?= mtq_icon('layers', 'h-3 w-3') ?>
                                    <?= e($categoryLabel) ?>
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full border border-amber-400/30 bg-amber-400/10 px-2 sm:px-3 py-1 text-xs font-semibold text-amber-200">
                                    <?= mtq_icon('spark', 'h-3 w-3') ?>
                                    <?= e($selectedJudgingRound) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?= e(route('scoring', array_filter([
                    'competition_category_id' => $selectedCategory?->id,
                    'judging_round' => $selectedJudgingRound,
                    'step' => 3,
                ]))) ?>" class="secondary-button flex items-center gap-2 justify-center">
                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    Kembali ke Penilaian
                </a>
            </div>
        </header>

        <!-- Filter -->
        <div class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
            <form method="GET" action="<?= e(route('scoring.ranking')) ?>" class="flex flex-col lg:flex-row items-start lg:items-end gap-4">
                <input type="hidden" name="judging_round" value="<?= e($selectedJudgingRound) ?>">
                <div class="flex-1 w-full">
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Golongan</label>
                    <select name="competition_category_id" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                        <option value="">Semua Golongan</option>
                        <?php
                        $categories = \App\Models\CompetitionCategory::query()
                            ->orderBy('branch')
                            ->orderBy('sort_order')
                            ->get();
                        foreach ($categories as $cat):
                        ?>
                            <option value="<?= e($cat->id) ?>" <?= ($selectedCategory?->id ?? '') == $cat->id ? 'selected' : '' ?>>
                                <?= e(trim(($cat->branch ?? '-').' | '.$cat->name)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($dayRanges)): ?>
                <div class="flex-1 w-full">
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Jadwal Tampil</label>
                    <select name="appearance_day" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                        <option value="">Semua Hari</option>
                        <?php foreach ($dayRanges as $day):
                            $dayFormattedDate = isset($day['date']) && $day['date'] ? \Carbon\Carbon::parse($day['date'])->translatedFormat('d M Y') : '';
                        ?>
                            <option value="<?= e($day['day_index']) ?>" <?= $selectedAppearanceDay == $day['day_index'] ? 'selected' : '' ?>>
                                <?= e($day['name']) ?><?= $dayFormattedDate ? ' - ' . e($dayFormattedDate) : '' ?><?= $day['lot_range'] !== '-' ? ' (Lot ' . e($day['lot_range']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <button type="submit" class="primary-button px-5 py-3 flex items-center gap-2">
                    <?= mtq_icon('filter', 'h-4 w-4') ?>
                    Tampilkan
                </button>
            </form>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="glass-card rounded-2xl p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-xl bg-cyan-400/20">
                        <?= mtq_icon('users', 'h-5 w-5 sm:h-6 sm:w-6 text-cyan-300') ?>
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm text-slate-400">Total Peserta</p>
                        <p class="mt-1 text-xl sm:text-2xl font-extrabold text-white"><?= e($stats['total_participants'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-xl bg-emerald-400/20">
                        <?= mtq_icon('check-circle', 'h-5 w-5 sm:h-6 sm:w-6 text-emerald-300') ?>
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm text-slate-400">Sudah Dinilai</p>
                        <p class="mt-1 text-xl sm:text-2xl font-extrabold text-emerald-300"><?= e($stats['scored_participants'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-xl bg-blue-400/20">
                        <?= mtq_icon('user', 'h-5 w-5 sm:h-6 sm:w-6 text-blue-300') ?>
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm text-slate-400">Putra</p>
                        <p class="mt-1 text-xl sm:text-2xl font-extrabold text-blue-300"><?= e($stats['putra_count'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-xl bg-pink-400/20">
                        <?= mtq_icon('user', 'h-5 w-5 sm:h-6 sm:w-6 text-pink-300') ?>
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm text-slate-400">Putri</p>
                        <p class="mt-1 text-xl sm:text-2xl font-extrabold text-pink-300"><?= e($stats['putri_count'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selectedAppearanceDay !== null && isset($participantsByDay[$selectedAppearanceDay])): ?>
            <?php $dayData = $participantsByDay[$selectedAppearanceDay]; ?>
            <!-- Per Day View -->
            <section class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-2xl border border-cyan-400/18 bg-cyan-400/10 text-xl sm:text-2xl font-black text-cyan-100">
                            <?= e($selectedAppearanceDay + 1) ?>
                        </div>
                        <div>
                            <p class="section-kicker">Jadwal Hari <?= e($selectedAppearanceDay + 1) ?></p>
                            <h3 class="text-lg sm:text-xl font-bold text-white"><?= e($dayData['name']) ?></h3>
                            <?php if ($dayData['formatted_date']): ?>
                                <p class="text-xs sm:text-sm text-slate-400"><?= e($dayData['formatted_date']) ?><?= $dayData['time'] ? ' - ' . e($dayData['time']) . ' WIB' : '' ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs sm:text-sm font-semibold text-amber-200">
                            Lot <?= e($dayData['lot_range']) ?>
                        </span>
                        <span class="rounded-full border border-slate-700 bg-slate-800 px-3 py-1 text-xs sm:text-sm font-semibold text-slate-300">
                            <?= e($dayData['total_participants']) ?> Peserta
                        </span>
                        <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs sm:text-sm font-semibold text-emerald-200">
                            <?= e($dayData['scored_count']) ?> Dinilai
                        </span>
                    </div>
                </div>

                <!-- Putra & Putri Side by Side -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Putra -->
                    <div class="rounded-2xl border border-blue-500/20 bg-blue-950/30 p-4 sm:p-5 glow-blue">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500 text-lg">&#9794;</span>
                                <h4 class="text-lg font-bold text-blue-200">Putra</h4>
                            </div>
                            <span class="rounded-full border border-blue-400/30 bg-blue-400/10 px-3 py-1 text-xs font-semibold text-blue-200">
                                <?= e($dayData['putra_count']) ?> Peserta
                            </span>
                        </div>

                        <?php if ($dayData['putra']->isEmpty()): ?>
                            <div class="rounded-xl border border-dashed border-slate-700 bg-slate-900/50 p-6 text-center">
                                <p class="text-slate-500">Belum ada peserta putra</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-2 max-h-[500px] overflow-y-auto">
                                <?php foreach ($dayData['putra'] as $idx => $p): ?>
                                    <div class="participant-row flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/50 p-3">
                                        <div class="rank-badge <?= $p['day_gender_rank'] == 1 ? 'rank-1' : ($p['day_gender_rank'] == 2 ? 'rank-2' : ($p['day_gender_rank'] == 3 ? 'rank-3' : 'rank-other')) ?>">
                                            <?= e($p['day_gender_rank']) ?>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-white truncate text-sm"><?= e($p['name']) ?></p>
                                            <p class="text-xs text-slate-400"><?= e($p['district_name']) ?> · Lot <?= e($p['lot_number']) ?></p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <?php if ($p['has_score']): ?>
                                                <span class="text-lg font-bold text-emerald-300"><?= e(number_format($p['average_score'], 2)) ?></span>
                                            <?php else: ?>
                                                <span class="text-slate-500 text-sm">Belum dinilai</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Putri -->
                    <div class="rounded-2xl border border-pink-500/20 bg-pink-950/30 p-4 sm:p-5 glow-pink">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-pink-500 text-lg">&#9793;</span>
                                <h4 class="text-lg font-bold text-pink-200">Putri</h4>
                            </div>
                            <span class="rounded-full border border-pink-400/30 bg-pink-400/10 px-3 py-1 text-xs font-semibold text-pink-200">
                                <?= e($dayData['putri_count']) ?> Peserta
                            </span>
                        </div>

                        <?php if ($dayData['putri']->isEmpty()): ?>
                            <div class="rounded-xl border border-dashed border-slate-700 bg-slate-900/50 p-6 text-center">
                                <p class="text-slate-500">Belum ada peserta putri</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-2 max-h-[500px] overflow-y-auto">
                                <?php foreach ($dayData['putri'] as $idx => $p): ?>
                                    <div class="participant-row flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/50 p-3">
                                        <div class="rank-badge <?= $p['day_gender_rank'] == 1 ? 'rank-1' : ($p['day_gender_rank'] == 2 ? 'rank-2' : ($p['day_gender_rank'] == 3 ? 'rank-3' : 'rank-other')) ?>">
                                            <?= e($p['day_gender_rank']) ?>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-white truncate text-sm"><?= e($p['name']) ?></p>
                                            <p class="text-xs text-slate-400"><?= e($p['district_name']) ?> · Lot <?= e($p['lot_number']) ?></p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <?php if ($p['has_score']): ?>
                                                <span class="text-lg font-bold text-emerald-300"><?= e(number_format($p['average_score'], 2)) ?></span>
                                            <?php else: ?>
                                                <span class="text-slate-500 text-sm">Belum dinilai</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <!-- Overall Rankings by Gender -->
            <div class="grid gap-6 lg:grid-cols-2 mb-6">
                <!-- Putra Overall -->
                <section class="glass-card rounded-[2rem] p-4 sm:p-6 glow-blue">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500 text-2xl">&#9794;</span>
                            <div>
                                <h3 class="text-xl font-bold text-blue-200">Ranking Putra</h3>
                                <p class="text-xs text-slate-400"><?= e($putraRankings->count()) ?> Peserta</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($putraRankings->isEmpty()): ?>
                        <div class="rounded-xl border border-dashed border-slate-700 bg-slate-900/50 p-6 text-center">
                            <p class="text-slate-500">Belum ada peserta putra</p>
                        </div>
                    <?php else: ?>
                        <!-- Top 3 -->
                        <?php $top3Putra = $putraRankings->take(3); ?>
                        <?php if ($top3Putra->count() > 0): ?>
                        <div class="flex items-end justify-center gap-2 sm:gap-4 mb-6">
                            <?php if ($top3Putra->count() >= 2): ?>
                                <?php $second = $top3Putra[1]; ?>
                                <div class="text-center w-24 sm:w-32 order-2">
                                    <div class="rank-badge rank-2 mx-auto mb-2"><?= e($second['gender_rank']) ?></div>
                                    <span class="text-xl sm:text-2xl">&#129352;</span>
                                    <p class="text-xs sm:text-sm font-bold text-white truncate mt-1"><?= e($second['name']) ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 truncate"><?= e($second['district_name']) ?></p>
                                    <span class="text-lg sm:text-xl font-black text-slate-300"><?= e(number_format($second['average_score'], 2)) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($top3Putra->count() >= 1): ?>
                                <?php $first = $top3Putra[0]; ?>
                                <div class="text-center w-28 sm:w-40 order-1 border-2 border-amber-400/30 rounded-2xl p-2 sm:p-3 bg-amber-400/5">
                                    <div class="rank-badge rank-1 mx-auto mb-2"><?= e($first['gender_rank']) ?></div>
                                    <span class="text-2xl sm:text-3xl">&#128081;</span>
                                    <p class="text-sm sm:text-base font-bold text-white truncate mt-1"><?= e($first['name']) ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 truncate"><?= e($first['district_name']) ?></p>
                                    <span class="text-xl sm:text-2xl font-black text-amber-200"><?= e(number_format($first['average_score'], 2)) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($top3Putra->count() >= 3): ?>
                                <?php $third = $top3Putra[2]; ?>
                                <div class="text-center w-24 sm:w-32 order-3">
                                    <div class="rank-badge rank-3 mx-auto mb-2"><?= e($third['gender_rank']) ?></div>
                                    <span class="text-xl sm:text-2xl">&#129353;</span>
                                    <p class="text-xs sm:text-sm font-bold text-white truncate mt-1"><?= e($third['name']) ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 truncate"><?= e($third['district_name']) ?></p>
                                    <span class="text-lg sm:text-xl font-black text-orange-300"><?= e(number_format($third['average_score'], 2)) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Full List -->
                        <div class="space-y-2 max-h-[400px] overflow-y-auto">
                            <?php foreach ($putraRankings as $p): ?>
                                <div class="participant-row flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/50 p-3">
                                    <div class="rank-badge <?= $p['gender_rank'] <= 3 ? 'rank-'.$p['gender_rank'] : 'rank-other' ?>">
                                        <?= e($p['gender_rank']) ?>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-white truncate text-sm"><?= e($p['name']) ?></p>
                                        <p class="text-xs text-slate-400"><?= e($p['district_name']) ?> · Lot <?= e($p['lot_number']) ?></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <?php if ($p['has_score']): ?>
                                            <span class="text-lg font-bold text-emerald-300"><?= e(number_format($p['average_score'], 2)) ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-500 text-sm">Belum</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Putri Overall -->
                <section class="glass-card rounded-[2rem] p-4 sm:p-6 glow-pink">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-pink-500 text-2xl">&#9793;</span>
                            <div>
                                <h3 class="text-xl font-bold text-pink-200">Ranking Putri</h3>
                                <p class="text-xs text-slate-400"><?= e($putriRankings->count()) ?> Peserta</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($putriRankings->isEmpty()): ?>
                        <div class="rounded-xl border border-dashed border-slate-700 bg-slate-900/50 p-6 text-center">
                            <p class="text-slate-500">Belum ada peserta putri</p>
                        </div>
                    <?php else: ?>
                        <!-- Top 3 -->
                        <?php $top3Putri = $putriRankings->take(3); ?>
                        <?php if ($top3Putri->count() > 0): ?>
                        <div class="flex items-end justify-center gap-2 sm:gap-4 mb-6">
                            <?php if ($top3Putri->count() >= 2): ?>
                                <?php $second = $top3Putri[1]; ?>
                                <div class="text-center w-24 sm:w-32 order-2">
                                    <div class="rank-badge rank-2 mx-auto mb-2"><?= e($second['gender_rank']) ?></div>
                                    <span class="text-xl sm:text-2xl">&#129352;</span>
                                    <p class="text-xs sm:text-sm font-bold text-white truncate mt-1"><?= e($second['name']) ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 truncate"><?= e($second['district_name']) ?></p>
                                    <span class="text-lg sm:text-xl font-black text-slate-300"><?= e(number_format($second['average_score'], 2)) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($top3Putri->count() >= 1): ?>
                                <?php $first = $top3Putri[0]; ?>
                                <div class="text-center w-28 sm:w-40 order-1 border-2 border-amber-400/30 rounded-2xl p-2 sm:p-3 bg-amber-400/5">
                                    <div class="rank-badge rank-1 mx-auto mb-2"><?= e($first['gender_rank']) ?></div>
                                    <span class="text-2xl sm:text-3xl">&#128081;</span>
                                    <p class="text-sm sm:text-base font-bold text-white truncate mt-1"><?= e($first['name']) ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 truncate"><?= e($first['district_name']) ?></p>
                                    <span class="text-xl sm:text-2xl font-black text-amber-200"><?= e(number_format($first['average_score'], 2)) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($top3Putri->count() >= 3): ?>
                                <?php $third = $top3Putri[2]; ?>
                                <div class="text-center w-24 sm:w-32 order-3">
                                    <div class="rank-badge rank-3 mx-auto mb-2"><?= e($third['gender_rank']) ?></div>
                                    <span class="text-xl sm:text-2xl">&#129353;</span>
                                    <p class="text-xs sm:text-sm font-bold text-white truncate mt-1"><?= e($third['name']) ?></p>
                                    <p class="text-[10px] sm:text-xs text-slate-400 truncate"><?= e($third['district_name']) ?></p>
                                    <span class="text-lg sm:text-xl font-black text-orange-300"><?= e(number_format($third['average_score'], 2)) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Full List -->
                        <div class="space-y-2 max-h-[400px] overflow-y-auto">
                            <?php foreach ($putriRankings as $p): ?>
                                <div class="participant-row flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/50 p-3">
                                    <div class="rank-badge <?= $p['gender_rank'] <= 3 ? 'rank-'.$p['gender_rank'] : 'rank-other' ?>">
                                        <?= e($p['gender_rank']) ?>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-white truncate text-sm"><?= e($p['name']) ?></p>
                                        <p class="text-xs text-slate-400"><?= e($p['district_name']) ?> · Lot <?= e($p['lot_number']) ?></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <?php if ($p['has_score']): ?>
                                            <span class="text-lg font-bold text-emerald-300"><?= e(number_format($p['average_score'], 2)) ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-500 text-sm">Belum</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>

        <?php if (!empty($participantsByDay) && $selectedAppearanceDay === null): ?>
            <!-- Day by Day Summary Cards -->
            <section class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <?= mtq_icon('calendar', 'h-5 w-5 text-cyan-300') ?>
                    <h3 class="text-lg sm:text-xl font-bold text-white">Ranking per Jadwal Tampil</h3>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($participantsByDay as $dayIndex => $day): ?>
                        <a href="<?= e(route('scoring.ranking', array_filter([
                            'competition_category_id' => $selectedCategory?->id,
                            'judging_round' => $selectedJudgingRound,
                            'appearance_day' => $dayIndex,
                        ]))) ?>"
                            class="rounded-2xl border border-slate-700/50 bg-slate-900/50 p-4 hover:border-cyan-400/30 hover:bg-slate-900/80 transition-colors">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-cyan-400/20 bg-cyan-400/10 text-sm font-bold text-cyan-200">
                                        <?= e($dayIndex + 1) ?>
                                    </div>
                                    <span class="font-semibold text-white"><?= e($day['name']) ?></span>
                                </div>
                                <?php if ($day['scored_count'] > 0): ?>
                                    <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-200">
                                        <?= e($day['scored_count']) ?>/<?= e($day['total_participants']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($day['formatted_date']): ?>
                                <p class="text-xs text-slate-400 mb-3"><?= e($day['formatted_date']) ?><?= $day['time'] ? ' - ' . e($day['time']) . ' WIB' : '' ?></p>
                            <?php endif; ?>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full border border-amber-400/20 bg-amber-400/10 px-2 py-0.5 text-[10px] font-semibold text-amber-200">
                                    Lot <?= e($day['lot_range']) ?>
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-semibold text-blue-200">
                                    &#9794; <?= e($day['putra_count']) ?>
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-pink-500/10 px-2 py-0.5 text-[10px] font-semibold text-pink-200">
                                    &#9793; <?= e($day['putri_count']) ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
