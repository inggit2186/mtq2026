<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rankedParticipants = $rankedParticipants ?? collect();
$selectedCategory = $selectedCategory ?? null;
$selectedJudgingRound = $selectedJudgingRound ?? 'Penyisihan';
$categoryLabel = $categoryLabel ?? 'Semua Golongan';
$stats = $stats ?? [];
$filters = $filters ?? [];
$scoringSetting = $scoringSetting ?? null;

$resolvePhoto = function($participant) {
    if (!$participant || empty($participant['photo_url'])) return null;
    return $participant['photo_url'];
};

$topThree = $rankedParticipants->take(3);
$restParticipants = $rankedParticipants->skip(3);
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
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 800;
            font-size: 1.25rem;
        }
        .rank-1 {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #1e1b4b;
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.4);
        }
        .rank-2 {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
            color: #1e1b4b;
            box-shadow: 0 0 30px rgba(148, 163, 184, 0.4);
        }
        .rank-3 {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: #fff;
            box-shadow: 0 0 30px rgba(217, 119, 6, 0.4);
        }
        .rank-other {
            background: linear-gradient(135deg, rgba(51, 65, 85, 0.8) 0%, rgba(30, 41, 59, 0.9) 100%);
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        .podium-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .podium-card:hover {
            transform: translateY(-4px);
        }
        .participant-row {
            transition: all 0.2s ease;
        }
        .participant-row:hover {
            background: rgba(34, 211, 238, 0.08);
        }
        .glow-amber {
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.15), 0 0 40px rgba(251, 191, 36, 0.05);
        }
        .crown-icon {
            animation: bounce 2s ease-in-out infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
    </style>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="hero-orb hero-orb-amber right-[-7rem] top-10 h-72 w-72"></div>

        <!-- Header -->
        <header class="glass-card rounded-[2rem] p-6 glow-amber mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="icon-chip"><?= mtq_icon('trophy') ?></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('chart', 'h-4 w-4 text-amber-300') ?>
                            <p class="section-kicker">Ranking Penilaian</p>
                        </div>
                        <h2 class="mt-2 text-3xl font-black tracking-tight">
                            <span class="gradient-text">Ranking Peserta</span>
                        </h2>
                        <?php if ($selectedCategory): ?>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-200">
                                    <?= mtq_icon('layers', 'h-3 w-3') ?>
                                    <?= e($categoryLabel) ?>
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-200">
                                    <?= mtq_icon('spark', 'h-3 w-3') ?>
                                    <?= e($selectedJudgingRound) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="<?= e(route('scoring', array_filter([
                        'competition_category_id' => $selectedCategory?->id,
                        'judging_round' => $selectedJudgingRound,
                        'step' => 3,
                    ]))) ?>" class="secondary-button flex items-center gap-2">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        Kembali ke Penilaian
                    </a>
                </div>
            </div>
        </header>

        <!-- Filter & Stats -->
        <div class="glass-card rounded-[2rem] p-6 mb-6">
            <form method="GET" action="<?= e(route('scoring.ranking')) ?>" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
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
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Babak</label>
                    <select name="judging_round" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                        <option value="Penyisihan" <?= $selectedJudgingRound === 'Penyisihan' ? 'selected' : '' ?>>Penyisihan</option>
                        <option value="Final" <?= $selectedJudgingRound === 'Final' ? 'selected' : '' ?>>Final</option>
                    </select>
                </div>
                <button type="submit" class="primary-button px-5 py-3 flex items-center gap-2">
                    <?= mtq_icon('filter', 'h-4 w-4') ?>
                    Tampilkan
                </button>
            </form>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 sm:grid-cols-3 mb-6">
            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-400/20">
                        <?= mtq_icon('users', 'h-6 w-6 text-cyan-300') ?>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400">Total Peserta</p>
                        <p class="mt-1 text-2xl font-extrabold text-white"><?= e($stats['verified_participants'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400/20">
                        <?= mtq_icon('check-circle', 'h-6 w-6 text-emerald-300') ?>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400">Sudah Dinilai</p>
                        <p class="mt-1 text-2xl font-extrabold text-emerald-300"><?= e($stats['scored_participants'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-400/20">
                        <?= mtq_icon('trophy', 'h-6 w-6 text-amber-300') ?>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400">Ranking Ditampilkan</p>
                        <p class="mt-1 text-2xl font-extrabold text-amber-300"><?= e($rankedParticipants->count()) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 3 Podium -->
        <?php if ($topThree->isNotEmpty()): ?>
        <section class="mb-8 overflow-x-auto">
            <div class="flex flex-wrap items-end justify-center gap-4 min-w-[600px]">
                <!-- Rank 2 -->
                <?php if ($topThree->count() >= 2): ?>
                <?php $second = $topThree[1]; ?>
                <div class="podium-card glass-card rounded-2xl p-4 sm:p-6 w-36 sm:w-48 text-center order-2 lg:order-2">
                    <div class="flex justify-center mb-2 sm:mb-3">
                        <div class="rank-badge rank-2">
                            <?= e($second['rank']) ?>
                        </div>
                    </div>
                    <span class="text-xl sm:text-2xl mb-2 inline-block">&#129352;</span>
                    <p class="text-base sm:text-lg font-bold text-white truncate"><?= e($second['name']) ?></p>
                    <p class="text-xs sm:text-sm text-slate-400 truncate"><?= e($second['district_name']) ?></p>
                    <div class="mt-2 sm:mt-3 inline-flex items-center gap-1 rounded-full border border-slate-700 bg-slate-900/60 px-2 sm:px-3 py-1 text-xs text-slate-300">
                        Lot <?= e($second['lot_number'] ?? '-') ?>
                    </div>
                    <div class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-black text-slate-200">
                        <?= e(number_format($second['average_score'], 2)) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Rank 1 -->
                <?php if ($topThree->count() >= 1): ?>
                <?php $first = $topThree[0]; ?>
                <div class="podium-card glass-card rounded-2xl p-4 sm:p-6 w-40 sm:w-56 text-center order-1 lg:order-1 border-2 border-amber-400/30">
                    <div class="flex justify-center mb-2 sm:mb-3">
                        <div class="rank-badge rank-1">
                            <?= e($first['rank']) ?>
                        </div>
                    </div>
                    <span class="crown-icon text-2xl sm:text-3xl mb-2 inline-block">&#128081;</span>
                    <p class="text-lg sm:text-xl font-bold text-white truncate"><?= e($first['name']) ?></p>
                    <p class="text-xs sm:text-sm text-slate-400 truncate"><?= e($first['district_name']) ?></p>
                    <div class="mt-2 sm:mt-3 inline-flex items-center gap-1 rounded-full border border-amber-400/30 bg-amber-400/10 px-2 sm:px-3 py-1 text-xs text-amber-200">
                        Lot <?= e($first['lot_number'] ?? '-') ?>
                    </div>
                    <div class="mt-2 sm:mt-3 text-3xl sm:text-4xl font-black text-amber-200">
                        <?= e(number_format($first['average_score'], 2)) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Rank 3 -->
                <?php if ($topThree->count() >= 3): ?>
                <?php $third = $topThree[2]; ?>
                <div class="podium-card glass-card rounded-2xl p-4 sm:p-6 w-36 sm:w-48 text-center order-3 lg:order-3">
                    <div class="flex justify-center mb-2 sm:mb-3">
                        <div class="rank-badge rank-3">
                            <?= e($third['rank']) ?>
                        </div>
                    </div>
                    <span class="text-xl sm:text-2xl mb-2 inline-block">&#129353;</span>
                    <p class="text-base sm:text-lg font-bold text-white truncate"><?= e($third['name']) ?></p>
                    <p class="text-xs sm:text-sm text-slate-400 truncate"><?= e($third['district_name']) ?></p>
                    <div class="mt-2 sm:mt-3 inline-flex items-center gap-1 rounded-full border border-slate-700 bg-slate-900/60 px-2 sm:px-3 py-1 text-xs text-slate-300">
                        Lot <?= e($third['lot_number'] ?? '-') ?>
                    </div>
                    <div class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-black text-orange-300">
                        <?= e(number_format($third['average_score'], 2)) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Full Ranking Table -->
        <section class="glass-card rounded-[2rem] p-4 sm:p-6">
            <div class="flex flex-wrap items-center gap-3 mb-4 sm:mb-6">
                <?= mtq_icon('list', 'h-5 w-5 text-cyan-300') ?>
                <h3 class="text-lg sm:text-xl font-bold text-white">Daftar Ranking Lengkap</h3>
                <span class="rounded-full border border-slate-700 bg-slate-800 px-2 sm:px-3 py-1 text-xs text-slate-300">
                    <?= e($rankedParticipants->count()) ?> Peserta
                </span>
            </div>

            <?php if ($rankedParticipants->isEmpty()): ?>
                <div class="rounded-2xl border border-slate-700 bg-slate-900/50 p-6 sm:p-8 text-center">
                    <span class="text-5xl sm:text-6xl text-slate-600">&#128202;</span>
                    <p class="mt-4 text-base sm:text-lg text-slate-400">Belum ada data ranking.</p>
                    <p class="mt-1 text-sm text-slate-500">Pastikan sudah ada nilai yang dimasukkan untuk golongan ini.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                    <table class="w-full min-w-[600px]">
                        <thead>
                            <tr class="border-b border-slate-700/50">
                                <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">#</th>
                                <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Peserta</th>
                                <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 hidden sm:table-cell">Lot</th>
                                <th class="px-2 sm:px-4 py-2 sm:py-3 text-left text-[10px] sm:text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 hidden md:table-cell">Kecamatan</th>
                                <th class="px-2 sm:px-4 py-2 sm:py-3 text-right text-[10px] sm:text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Nilai</th>
                                <th class="px-2 sm:px-4 py-2 sm:py-3 text-center text-[10px] sm:text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rankedParticipants as $participant): ?>
                            <tr class="participant-row border-b border-slate-800/50">
                                <td class="px-2 sm:px-4 py-3 sm:py-4">
                                    <div class="flex items-center gap-2">
                                        <?php
                                        $rankClass = match($participant['rank']) {
                                            1 => 'rank-1',
                                            2 => 'rank-2',
                                            3 => 'rank-3',
                                            default => 'rank-other',
                                        };
                                        ?>
                                        <div class="rank-badge rank-other <?= e($rankClass) ?>" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                            <?= e($participant['rank']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 sm:px-4 py-3 sm:py-4">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <?php if ($participant['photo_url']): ?>
                                            <img src="<?= e($participant['photo_url']) ?>" alt="<?= e($participant['name']) ?>" class="h-8 w-6 sm:h-10 sm:w-8 rounded-lg object-cover border border-slate-700">
                                        <?php else: ?>
                                            <div class="flex h-8 w-6 sm:h-10 sm:w-8 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 text-xs uppercase text-slate-500">
                                                <?= e(substr($participant['name'] ?? '?', 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-white truncate max-w-[100px] sm:max-w-none"><?= e($participant['name']) ?></p>
                                            <div class="flex flex-wrap items-center gap-2 sm:hidden">
                                                <span class="inline-flex items-center rounded-full border border-amber-400/20 bg-amber-400/10 px-2 py-0.5 text-[10px] font-semibold text-amber-200">
                                                    Lot <?= e($participant['lot_number'] ?? '-') ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($participant['institution'])): ?>
                                                <p class="text-[10px] sm:text-xs text-slate-400 truncate max-w-[100px] sm:max-w-[150px]"><?= e($participant['institution']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 sm:px-4 py-3 sm:py-4 hidden sm:table-cell">
                                    <span class="inline-flex items-center rounded-full border border-amber-400/20 bg-amber-400/10 px-2 sm:px-3 py-1 text-xs font-semibold text-amber-200">
                                        <?= e($participant['lot_number'] ?? '-') ?>
                                    </span>
                                </td>
                                <td class="px-2 sm:px-4 py-3 sm:py-4 text-right">
                                    <?php if ($participant['score_count'] > 0): ?>
                                        <span class="text-lg sm:text-xl font-bold text-emerald-300">
                                            <?= e(number_format($participant['average_score'], 2)) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-lg font-bold text-slate-500">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-2 sm:px-4 py-3 sm:py-4 text-center">
                                    <?php if ($participant['score_count'] > 0): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2 sm:px-3 py-1 text-[10px] sm:text-xs font-semibold text-emerald-200">
                                            <?= mtq_icon('check', 'h-3 w-3') ?>
                                            <span class="hidden sm:inline">Sudah Dinilai</span>
                                            <span class="sm:hidden">&#10003;</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 rounded-full border border-slate-700 bg-slate-800 px-2 sm:px-3 py-1 text-[10px] sm:text-xs font-semibold text-slate-400">
                                            <?= mtq_icon('clock', 'h-3 w-3') ?>
                                            <span class="hidden sm:inline">Belum Dinilai</span>
                                            <span class="sm:hidden">&#10005;</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
