<?php
require_once __DIR__.'/../partials/icon.php';
require_once __DIR__.'/../partials/category-visual.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$selectedParticipant = $selectedParticipant ?? null;
$selectedCategory = $selectedCategory ?? null;
$scoringSetting = $scoringSetting ?? null;
$setupReady = $setupReady ?? false;
$judgingRounds = $judgingRounds ?? ['Penyisihan', 'Final'];
$selectedJudgingRound = $selectedJudgingRound ?? ($judgingRounds[0] ?? 'Penyisihan');
$judgeNames = $judgeNames ?? [$user?->name];
$criteria = $criteria ?? [];
$roundSetupConfigs = $roundSetupConfigs ?? [];
$roundLocked = [
    'Penyisihan' => $scoringSetting && $scoringSetting->isFinalized('Penyisihan'),
    'Final' => $scoringSetting && $scoringSetting->isFinalized('Final'),
];
$filters = $filters ?? [];
$recentScores = $recentScores ?? collect();
$bigScreenUrl = $bigScreenUrl ?? route('big-screen');
$scoreStats = $scoreStats ?? ['participant_total' => 0, 'verified_total' => 0, 'selected_average' => '0.00', 'selected_latest' => '0.00', 'judge_total' => 0, 'criteria_total' => 0];
$restrictedCategories = $restrictedCategories ?? [];
$regularCategories = $regularCategories ?? $categories ?? [];
$mfqCategories = $mfqCategories ?? collect();
$categoryUsage = $categoryUsage ?? collect();
$selectedCategoryIsMfq = $selectedCategoryIsMfq ?? false;
$selectedCategoryIsMsq = $selectedCategoryIsMsq ?? false;
$setupCreated = $setupCreated ?? $setupReady;
$setupEditable = $setupEditable ?? false;
$setupRequested = $setupRequested ?? false;
$participantHasScores = $participantHasScores ?? false;
$participantScoreRound = $participantScoreRound ?? $selectedJudgingRound;
$participantScoreDraft = $participantScoreDraft ?? [];
$initialStep = (int) ($initialStep ?? 1);
$initialJudgeIndex = (int) ($initialJudgeIndex ?? 0);
$districtOptions = $districtOptions ?? [];
$defaultCriteria = $selectedCategory
    ? (config('scoring.criteria.'.($selectedCategory->branch ?? '')) ?? config('scoring.criteria.default', []))
    : config('scoring.criteria.default', []);
$resolveParticipantPhoto = static function ($participant): ?string {
    $path = (string) ($participant?->document_photo ?? '');

    if ($path === '') {
        return null;
    }

    return asset('storage/'.ltrim(str_replace('\\', '/', $path), '/'));
};
$selectedParticipantPhoto = $resolveParticipantPhoto($selectedParticipant);
$participantOptions = collect($participants ?? [])->map(function ($participant) use ($resolveParticipantPhoto, $filters, $selectedJudgingRound, $judgeNames, $criteria): array {
    $participantScores = collect($participant->scores ?? []);
    // Filter scores for the specific round being scored
    $roundScores = $participantScores->where('judging_round', $selectedJudgingRound);
    $latestScore = $roundScores->sortByDesc('submitted_at')->first();
    $latestRound = (string) ($latestScore?->judging_round ?? '');
    $latestRoundScores = $latestRound !== ''
        ? $roundScores->keyBy(fn ($entry) => $entry->getAllJudgeScores() ? array_key_first($entry->getAllJudgeScores()) : '')
        : collect();
    $correctionRequestDraft = [];

    foreach ($judgeNames as $judgeName) {
        $allJudgeScores = $latestScore?->getAllJudgeScores() ?? [];
        $judgeData = $allJudgeScores[$judgeName] ?? null;
        $correctionRequestDraft[$judgeName] = [
            'scores' => [],
            'remarks' => (string) ($judgeData['remarks'] ?? ''),
        ];

        foreach (array_keys($criteria) as $key) {
            $correctionRequestDraft[$judgeName]['scores'][$key] = $judgeData ? ($judgeData['scores'][$key] ?? '') : '';
        }
    }

    return [
        'id' => $participant->id,
        'district_id' => (int) $participant->district_id,
        'name' => $participant->name,
        'branch' => $participant->category?->branch ?? '-',
        'category' => $participant->category?->name ?? '-',
        'district' => $participant->district?->name ?? '-',
        'lot_number' => $participant->lot_number ?: '-',
        'registration_number' => $participant->registration_number,
        'institution' => $participant->institution,
        'score_count' => $roundScores->count(), // Only count scores for this round
        'average_score' => number_format((float) ($roundScores->avg('average_score') ?? 0), 2),
        'latest_score' => number_format((float) ($latestScore?->average_score ?? 0), 2),
        'latest_round' => (string) ($latestScore?->judging_round ?? '-'),
        // Check if scored for THIS round only
        'scoring_status' => $roundScores->count() > 0 ? 'Sudah dinilai' : 'Belum dinilai',
        'correction_request_round' => $latestRound !== '' ? $latestRound : $selectedJudgingRound,
        'correction_request_draft' => $correctionRequestDraft,
        'photo' => $resolveParticipantPhoto($participant),
        'url' => route('scoring', array_filter([
            'participant_id' => $participant->id,
            'competition_category_id' => $filters['competition_category_id'] ?? null,
            'branch' => $filters['branch'] ?? null,
            'keyword' => $filters['keyword'] ?? null,
            'judging_round' => $filters['judging_round'] ?? $selectedJudgingRound,
            'step' => 3,
        ])),
    ];
})->values();
$roundFormKeys = ['Penyisihan' => 'penyisihan', 'Final' => 'final'];
$defaultRoundForms = [];
foreach ($roundFormKeys as $roundLabel => $roundKey) {
    $roundConfig = $roundSetupConfigs[$roundLabel] ?? [
        'judge_count' => 1,
        'judge_names' => $availableJudgeNames ?: [$user?->name],
        'scoring_points' => $defaultCriteria,
    ];
    $judgeNamesValue = preg_split('/\r\n|\r|\n/', (string) old('rounds.'.$roundKey.'.judge_names_text', implode("\n", $roundConfig['judge_names'] ?? ($availableJudgeNames ?: [$user?->name])))) ?: [];
    $judgeNamesValue = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $judgeNamesValue)));
    if ($judgeNamesValue === []) {
        $judgeNamesValue = $availableJudgeNames ?: [(string) ($user?->name ?? 'Hakim 1')];
    }

    $pointLabelsValue = preg_split('/\r\n|\r|\n/', (string) old('rounds.'.$roundKey.'.scoring_points_text', implode("\n", array_values($roundConfig['scoring_points'] ?? [])))) ?: [];
    $pointLabelsValue = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $pointLabelsValue)));
    if ($pointLabelsValue === []) {
        // Fallback: use default criteria values if round config has no scoring points
        $pointLabelsValue = array_values($defaultCriteria);
    }

    $defaultRoundForms[$roundKey] = [
        'label' => $roundLabel,
        'judge_count' => (int) old('rounds.'.$roundKey.'.judge_count', $roundConfig['judge_count'] ?? max(1, count($judgeNamesValue))),
        'judge_names' => $judgeNamesValue,
        'judge_ids' => $roundConfig['judge_ids'] ?? [],
        'scoring_points' => $pointLabelsValue,
    ];
}
$step1Keyword = trim((string) ($filters['keyword'] ?? ''));
$selectedRoundCategories = collect($categories ?? [])->filter(function ($category) use ($selectedJudgingRound, $filters, $step1Keyword): bool {
    $categoryRound = trim((string) ($category->round ?? ''));
    $categoryBranch = trim((string) ($category->branch ?? ''));
    $categoryText = mb_strtolower(trim((string) $category->branch.' '.(string) $category->name.' '.(string) $category->notes.' '.(string) $category->description));

    if ($categoryRound !== '' && strcasecmp($categoryRound, (string) $selectedJudgingRound) !== 0) {
        return false;
    }

    if (filled($filters['branch'] ?? null) && (string) $filters['branch'] !== $categoryBranch) {
        return false;
    }

    if ($step1Keyword !== '' && ! str_contains($categoryText, mb_strtolower($step1Keyword))) {
        return false;
    }

    return true;
});
$selectedRoundBranchGroups = $selectedRoundCategories
    ->groupBy(fn ($category) => filled($category->branch) ? $category->branch : 'Tanpa Cabang');
$isMfqCategoryCard = static function ($category): bool {
    $haystack = mb_strtolower(trim((string) ($category->branch ?? '').' '.(string) ($category->name ?? '').' '.(string) ($category->slug ?? '')));

    return str_contains($haystack, 'fahmil');
};
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'scoring');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Penilaian') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
    <style>
        .glass-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.7) 100%);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        #step-3 {
            grid-template-columns: 1fr;
        }

        @media (max-width: 1279px) {
            #step-3 {
                grid-template-columns: 1fr;
            }
        }

        .glow-cyan {
            box-shadow: 0 0 20px rgba(34, 211, 238, 0.15), 0 0 40px rgba(34, 211, 238, 0.05);
        }

        .glow-emerald {
            box-shadow: 0 0 20px rgba(52, 211, 153, 0.15), 0 0 40px rgba(52, 211, 153, 0.05);
        }

        .glow-amber {
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.15), 0 0 40px rgba(251, 191, 36, 0.05);
        }

        .gradient-text {
            background: linear-gradient(135deg, #22d3ee 0%, #34d399 50%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .step-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .step-card:hover {
            transform: translateY(-2px);
        }

        .category-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .metric-card {
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
        }

        .form-input {
            transition: all 0.2s ease;
        }

        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
        }

        .participant-card {
            transition: all 0.2s ease;
        }

        .participant-card:hover {
            background: rgba(34, 211, 238, 0.08);
        }

        .modal-overlay {
            background: rgba(2, 6, 23, 0.85);
            backdrop-filter: blur(8px);
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .pulse-dot {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }
    </style>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <script>
        window.currentParticipantId = <?= e($selectedParticipant?->id ?? 'null') ?>;
    </script>
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8"
        x-data="scoringWorkflow({
            initialStep: <?= e($initialStep) ?>,
            setupReady: <?= e($setupReady ? 'true' : 'false') ?>,
            setupEditable: <?= e($setupEditable ? 'true' : 'false') ?>,
            categoryReady: <?= e($selectedCategory ? 'true' : 'false') ?>,
            participantReady: <?= e($selectedParticipant ? 'true' : 'false') ?>,
            mfqMode: <?= e($selectedCategoryIsMfq ? 'true' : 'false') ?>,
        })">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>

        <div class="grid gap-6 xl:grid-cols-[290px_minmax(0,1fr)]">

        <!-- Mobile Overlay for Sidebar -->
        <div x-show="mobileNavOpen" x-cloak x-on:click="mobileNavOpen = false" class="fixed inset-0 z-20 bg-black/60 backdrop-blur-sm lg:hidden"></div>

            <!-- Sidebar -->
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:relative lg:inset-auto lg:z-auto lg:ml-0 -translate-x-full lg:translate-x-0 glass-card"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-full lg:translate-x-0 lg:opacity-100'">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                        <h1 class="mt-1 text-lg font-bold text-white">Penilaian</h1>
                    </div>
                </div>

                <!-- User Info -->
                <div class="rounded-2xl border border-cyan-400/20 bg-gradient-to-br from-cyan-500/10 to-sky-500/10 p-4 mb-6">
                    <div class="flex items-center gap-2">
                        <?= mtq_icon('user', 'h-4 w-4 text-cyan-300') ?>
                        <p class="text-sm font-semibold text-white"><?= e($user?->name) ?></p>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-flex h-2 w-2 rounded-full <?= $setupReady ? 'bg-emerald-400' : 'bg-amber-400' ?>"></span>
                        <span class="text-xs text-slate-300"><?= $setupReady ? 'Setting Siap' : 'Butuh Setup' ?></span>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid gap-3 mb-6">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 border border-slate-700/30">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-400/20">
                            <?= mtq_icon('users', 'h-5 w-5 text-cyan-300') ?>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Peserta</p>
                            <p class="text-lg font-bold text-white"><?= e($scoreStats['participant_total']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 border border-slate-700/30">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-400/20">
                            <?= mtq_icon('check-circle', 'h-5 w-5 text-emerald-300') ?>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Terverifikasi</p>
                            <p class="text-lg font-bold text-white"><?= e($scoreStats['verified_total']) ?></p>
                        </div>
                    </div>
                </div>

                <?php if ($user?->role === 'admin'): ?>
                <!-- Global Finalize Controls -->
                <div class="mb-6">
                    <p class="px-3 mb-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Kontrol Global</p>
                    <div class="space-y-2">
                        <form method="POST" action="<?= e(route('scoring.finalize-all')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="round_to_finalize" value="Penyisihan">
                            <button type="submit" class="w-full flex items-center justify-between gap-2 rounded-xl border border-amber-400/20 bg-amber-400/5 px-3 py-2 text-xs font-semibold text-amber-200 transition hover:bg-amber-400/10"
                                onclick="return confirm('Yakin menutup babak Penyisihan untuk SEMUA golongan?')">
                                <span class="flex items-center gap-2">
                                    <?= mtq_icon('layers', 'h-4 w-4') ?>
                                    Tutup Penyisihan
                                </span>
                                <?= mtq_icon('lock', 'h-4 w-4') ?>
                            </button>
                        </form>
                        <form method="POST" action="<?= e(route('scoring.finalize-all')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="round_to_finalize" value="Final">
                            <button type="submit" class="w-full flex items-center justify-between gap-2 rounded-xl border border-amber-400/20 bg-amber-400/5 px-3 py-2 text-xs font-semibold text-amber-200 transition hover:bg-amber-400/10"
                                onclick="return confirm('Yakin menutup babak Final untuk SEMUA golongan?')">
                                <span class="flex items-center gap-2">
                                    <?= mtq_icon('crown', 'h-4 w-4') ?>
                                    Tutup Final
                                </span>
                                <?= mtq_icon('lock', 'h-4 w-4') ?>
                            </button>
                        </form>
                        <form method="POST" action="<?= e(route('scoring.unfinalize-all')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="round_to_unfinalize" value="Penyisihan">
                            <button type="submit" class="w-full flex items-center justify-between gap-2 rounded-xl border border-emerald-400/20 bg-emerald-400/5 px-3 py-2 text-xs font-semibold text-emerald-200 transition hover:bg-emerald-400/10"
                                onclick="return confirm('Yakin membuka babak Penyisihan untuk SEMUA golongan?')">
                                <span class="flex items-center gap-2">
                                    <?= mtq_icon('layers', 'h-4 w-4') ?>
                                    Buka Penyisihan
                                </span>
                                <?= mtq_icon('lock-open', 'h-4 w-4') ?>
                            </button>
                        </form>
                        <form method="POST" action="<?= e(route('scoring.unfinalize-all')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="round_to_unfinalize" value="Final">
                            <button type="submit" class="w-full flex items-center justify-between gap-2 rounded-xl border border-emerald-400/20 bg-emerald-400/5 px-3 py-2 text-xs font-semibold text-emerald-200 transition hover:bg-emerald-400/10"
                                onclick="return confirm('Yakin membuka babak Final untuk SEMUA golongan?')">
                                <span class="flex items-center gap-2">
                                    <?= mtq_icon('crown', 'h-4 w-4') ?>
                                    Buka Final
                                </span>
                                <?= mtq_icon('lock-open', 'h-4 w-4') ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <nav class="space-y-2 mb-6">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <a href="<?= e(route('dashboard')) ?>" class="secondary-button w-full flex items-center justify-center gap-2">
                    <?= mtq_icon('home', 'h-4 w-4') ?>
                    Dashboard
                </a>
            </aside>

            <!-- Main Content -->
            <div class="min-w-0 space-y-6 pb-8">
                <!-- Header -->
                <header class="glass-card rounded-[2rem] p-4 sm:p-6 glow-cyan">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = true">
                                <?= mtq_icon('menu', 'h-4 w-4') ?>
                            </button>
                            <div>
                                <div class="flex items-center gap-2">
                                    <?= mtq_icon('book-open', 'h-4 w-4 text-cyan-300') ?>
                                    <p class="section-kicker">Ruang Penilaian</p>
                                </div>
                                <h2 class="mt-2 text-3xl font-black tracking-tight">
                                    <span class="gradient-text"><?= e($user?->roleLabel()) ?></span>
                                </h2>
                                <?php if ($selectedCategory): ?>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-200">
                                            <?= mtq_icon('layers', 'h-3 w-3') ?>
                                            <?= e(trim(($selectedCategory->branch ?? '-').' | '.($selectedCategory->name ?? '-'))) ?>
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
                            <div class="<?= $setupReady ? 'status-pill' : 'inline-flex items-center gap-2 rounded-full border border-amber-400/18 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-100' ?>">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full <?= $setupReady ? 'bg-emerald-300' : 'bg-amber-300' ?>"></span>
                                <?= $setupReady ? 'Penilaian Siap' : 'Setup Belum Lengkap' ?>
                            </div>
                            <?php if ($selectedCategory): ?>
                                <a href="<?= e($bigScreenUrl) ?>" target="_blank" rel="noreferrer" class="secondary-button flex items-center gap-2">
                                    <?= mtq_icon('eye', 'h-4 w-4') ?>
                                    Big Screen
                                </a>
                                <a href="<?= e(route('scoring.ranking', array_filter([
                                    'competition_category_id' => $selectedCategory?->id,
                                    'judging_round' => $selectedJudgingRound,
                                ]))) ?>" target="_blank" rel="noreferrer" class="secondary-button flex items-center gap-2">
                                    <?= mtq_icon('trophy', 'h-4 w-4') ?>
                                    Ranking
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </header>

                <?php if (session('status')): ?>
                    <div class="glass-card rounded-[1.5rem] border border-emerald-400/20 bg-emerald-400/10 px-5 py-4 text-sm text-emerald-100">
                        <?= e(session('status')) ?>
                    </div>
                <?php endif; ?>

                <!-- Metric Cards -->
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card glass-card rounded-2xl p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-400/20">
                                <?= mtq_icon('users', 'h-6 w-6 text-cyan-300') ?>
                            </div>
                            <div>
                                <p class="text-sm text-slate-400">Peserta</p>
                                <p class="mt-1 text-2xl font-extrabold text-white"><?= e($scoreStats['participant_total']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="metric-card glass-card rounded-2xl p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400/20">
                                <?= mtq_icon('check-circle', 'h-6 w-6 text-emerald-300') ?>
                            </div>
                            <div>
                                <p class="text-sm text-slate-400">Terverifikasi</p>
                                <p class="mt-1 text-2xl font-extrabold text-white"><?= e($scoreStats['verified_total']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="metric-card glass-card rounded-2xl p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-400/20">
                                <?= mtq_icon('shield', 'h-6 w-6 text-violet-300') ?>
                            </div>
                            <div>
                                <p class="text-sm text-slate-400">Hakim</p>
                                <p class="mt-1 text-2xl font-extrabold text-cyan-200"><?= e($scoreStats['judge_total']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="metric-card glass-card rounded-2xl p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-400/20">
                                <?= mtq_icon('spark', 'h-6 w-6 text-amber-300') ?>
                            </div>
                            <div>
                                <p class="text-sm text-slate-400">Poin</p>
                                <p class="mt-1 text-2xl font-extrabold text-emerald-300"><?= e($scoreStats['criteria_total']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Workflow Steps -->
                <section class="glass-card rounded-[2rem] p-6 glow-emerald">
                    <div class="flex items-center gap-3 mb-4">
                        <?= mtq_icon('layers', 'h-5 w-5 text-cyan-300') ?>
                        <h2 class="text-xl font-bold text-white">Alur Penilaian</h2>
                    </div>
                    <div class="grid gap-3 lg:grid-cols-3">
                        <button type="button" class="step-card rounded-2xl border px-5 py-4 text-left transition"
                            :class="currentStep === 1 ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_14px_40px_-28px_rgba(34,211,238,0.7)]' : 'border-slate-700/50 bg-slate-800/30 hover:border-cyan-400/30'"
                            x-on:click="goToStep(1)">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl font-bold"
                                      :class="currentStep === 1 ? 'bg-gradient-to-br from-cyan-400 to-sky-400 text-slate-900' : 'bg-slate-700 text-slate-300'">1</span>
                                <div>
                                    <p class="font-semibold text-white">Pilih Golongan</p>
                                    <p class="text-xs text-slate-400">Step 1</p>
                                </div>
                            </div>
                        </button>
                        <button type="button" class="step-card rounded-2xl border px-5 py-4 text-left transition"
                            :class="currentStep === 2 ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_14px_40px_-28px_rgba(34,211,238,0.7)]' : 'border-slate-700/50 bg-slate-800/30 hover:border-cyan-400/30'"
                            x-on:click="goToStep(<?= $setupCreated ? ($setupEditable ? 2 : 3) : 2 ?>)">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl font-bold"
                                      :class="currentStep === 2 ? 'bg-gradient-to-br from-cyan-400 to-sky-400 text-slate-900' : 'bg-slate-700 text-slate-300'">2</span>
                                <div>
                                    <p class="font-semibold text-white">Atur Setting</p>
                                    <p class="text-xs text-slate-400">Step 2</p>
                                </div>
                            </div>
                        </button>
                        <button type="button" class="step-card rounded-2xl border px-5 py-4 text-left transition"
                            :class="currentStep === 3 ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_14px_40px_-28px_rgba(34,211,238,0.7)]' : 'border-slate-700/50 bg-slate-800/30 hover:border-cyan-400/30'"
                            x-on:click="goToStep(3)">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl font-bold"
                                      :class="currentStep === 3 ? 'bg-gradient-to-br from-cyan-400 to-sky-400 text-slate-900' : 'bg-slate-700 text-slate-300'">3</span>
                                <div>
                                    <p class="font-semibold text-white">Input Nilai</p>
                                    <p class="text-xs text-slate-400">Step 3</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </section>

                <section id="step-1" class="glass-card rounded-[2rem] p-6" x-show="currentStep === 1" x-cloak>
                    <!-- Search Form -->
                    <form method="GET" action="<?= e(route('scoring')) ?>" class="mb-6">
                        <input type="hidden" name="judging_round" value="<?= e($selectedJudgingRound) ?>">
                        <input type="hidden" name="step" value="1">
                        <div class="flex flex-wrap gap-3">
                            <div class="flex-1 min-w-[200px]">
                                <input name="keyword" value="<?= e($filters['keyword'] ?? '') ?>" type="text" placeholder="Cari golongan..."
                                    class="form-input w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                            </div>
                            <button type="submit" class="primary-button px-5 py-3 flex items-center gap-2">
                                <?= mtq_icon('search', 'h-4 w-4') ?>
                                Cari
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-4">
                            <a href="<?= e(route('scoring', array_filter([
                                'keyword' => $filters['keyword'] ?? null,
                                'competition_category_id' => $filters['competition_category_id'] ?? null,
                                'judging_round' => $selectedJudgingRound,
                                'step' => 1,
                            ]))) ?>"
                                class="rounded-full border px-4 py-2 text-sm font-semibold transition <?= filled($filters['branch'] ?? null) ? 'border-slate-700 bg-slate-900/70 text-slate-300 hover:border-cyan-400/30' : 'border-cyan-300/30 bg-cyan-400/10 text-cyan-100' ?>">
                                Semua
                            </a>
                            <?php foreach ($branches as $branch): ?>
                                <a href="<?= e(route('scoring', array_filter([
                                    'branch' => $branch,
                                    'keyword' => $filters['keyword'] ?? null,
                                    'competition_category_id' => $filters['competition_category_id'] ?? null,
                                    'judging_round' => $selectedJudgingRound,
                                    'step' => 1,
                                ]))) ?>"
                                    class="rounded-full border px-4 py-2 text-sm font-semibold transition <?= (string) ($filters['branch'] ?? '') === (string) $branch ? 'border-cyan-300/30 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-900/70 text-slate-300 hover:border-cyan-400/30' ?>">
                                    <?= e($branch) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </form>

                    <!-- Category Cards -->
                    <div class="space-y-6">
                        <?php if ($selectedRoundCategories->isEmpty()): ?>
                            <div class="rounded-2xl border border-slate-700 bg-slate-900/50 p-8 text-center">
                                <p class="text-slate-400">Tidak ada golongan untuk babak ini.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($selectedRoundBranchGroups as $branchName => $categoryGroup): ?>
                                <?php if (filled($filters['branch'] ?? null) && (string) $filters['branch'] !== (string) $branchName) continue; ?>
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3">
                                        <?= mtq_icon('layers', 'h-5 w-5 text-cyan-300') ?>
                                        <h3 class="text-lg font-bold text-white"><?= e($branchName) ?></h3>
                                        <span class="rounded-full border border-slate-700 bg-slate-800 px-3 py-1 text-xs text-slate-300"><?= e($categoryGroup->count()) ?></span>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                        <?php foreach ($categoryGroup as $categoryCard): ?>
                                            <?php
                                                $isMfqCard = $isMfqCategoryCard($categoryCard);
                                                $cardSetting = $categorySettings[$categoryCard->id] ?? null;
                                                $cardSetupCreated = (bool) $cardSetting;
                                                $cardSetupEditable = (bool) ($cardSetting?->isEditable($selectedJudgingRound) ?? false);
                                                $cardSetupRequested = (bool) ($cardSetting?->isEditRequested($selectedJudgingRound) ?? false);
                                                $cardSetupReady = (bool) ($cardSetting?->isReady($selectedJudgingRound) ?? false);
                                                $categoryCardUsage = $categoryUsage[$categoryCard->id] ?? [];
                                                $availableSlots = (int) ($categoryCardUsage['available_slots'] ?? $categoryCard->quota ?? 0);
                                                $registered = (int) ($categoryCardUsage['registered'] ?? 0);
                                                $categoryLink = $isMfqCard
                                                    ? route('scoring.mfq', ['competition_category_id' => $categoryCard->id])
                                                    : route('scoring', array_filter([
                                                        'competition_category_id' => $categoryCard->id,
                                                        'branch' => $categoryCard->branch,
                                                        'keyword' => $filters['keyword'] ?? null,
                                                        'judging_round' => $selectedJudgingRound,
                                                        'step' => 2,
                                                    ]));
                                                $isSelectedCard = (int) ($selectedCategory?->id ?? 0) === (int) $categoryCard->id;
                                            ?>
                                            <?php $categoryVisual = mtq_category_visual((string) $categoryCard->branch, (string) $categoryCard->name); ?>
                                            <?php if ($isMfqCard): ?>
                                            <a href="<?= e($categoryLink) ?>"
                                                class="category-card group overflow-hidden rounded-2xl border bg-slate-900/80 text-left transition hover:-translate-y-1 hover:shadow-lg <?= $isSelectedCard ? 'ring-2 ring-cyan-300/30 border-cyan-300/60' : 'border-slate-700/80' ?>">
                                            <?php else: ?>
                                            <button type="button"
                                                x-on:click="openRoundModal(<?= e($categoryCard->id) ?>, '<?= e(addslashes($categoryCard->branch)) ?>', '<?= e(addslashes($categoryCard->name)) ?>')"
                                                class="category-card group overflow-hidden rounded-2xl border bg-slate-900/80 text-left transition hover:-translate-y-1 hover:shadow-lg w-full <?= $isSelectedCard ? 'ring-2 ring-cyan-300/30 border-cyan-300/60' : 'border-slate-700/80' ?>">
                                            <?php endif; ?>
                                                <div class="aspect-[16/9] overflow-hidden bg-slate-950/70 p-3">
                                                    <img src="<?= e($categoryVisual) ?>" alt="<?= e($categoryCard->name) ?>" loading="lazy" class="h-full w-full object-contain">
                                                </div>
                                                <div class="p-4">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div>
                                                            <p class="text-[10px] uppercase tracking-[0.2em] text-cyan-200/70"><?= e($categoryCard->branch) ?></p>
                                                            <h4 class="mt-1 text-base font-bold text-white group-hover:text-cyan-200"><?= e($categoryCard->name) ?></h4>
                                                        </div>
                                                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase <?= $isMfqCard ? 'border-amber-400/20 bg-amber-400/10 text-amber-200' : 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200' ?>">
                                                            <?= $isMfqCard ? 'MFQ' : e($availableSlots).' slot' ?>
                                                        </span>
                                                    </div>
                                                    <div class="mt-3 flex items-center gap-2">
                                                        <span class="rounded-full border px-2.5 py-1 text-[10px] font-semibold <?= $cardSetupReady ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200' : 'border-slate-700 bg-slate-800 text-slate-400' ?>">
                                                            <?= $cardSetupReady ? 'Siap' : 'Belum' ?>
                                                        </span>
                                                        <?php if ($isSelectedCard): ?>
                                                            <span class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-2.5 py-1 text-[10px] font-semibold text-cyan-100">Aktif</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?= $isMfqCard ? '</a>' : '</button>' ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Round Selection Modal -->
                <div x-show="roundModalOpen" x-cloak
                    x-transition:enter="transition duration-200 ease-out"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition duration-150 ease-in"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    x-on:keydown.escape.window="roundModalOpen = false; selectedCategoryForRound = null">
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-md" x-on:click="roundModalOpen = false; selectedCategoryForRound = null"></div>
                    <div class="relative z-10 w-full max-w-sm"
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                        <!-- Header -->
                        <div class="mb-4 text-center">
                            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-400/20 bg-gradient-to-br from-cyan-400/10 to-sky-500/10 shadow-lg shadow-cyan-400/10">
                                <?= mtq_icon('target', 'h-7 w-7 text-cyan-300') ?>
                            </div>
                            <p class="text-xs font-bold uppercase tracking-[0.25em] text-cyan-300/70">Pilih Babak</p>
                            <h3 class="mt-1 text-xl font-bold text-white" x-text="selectedCategoryForRound?.name ?? ''"></h3>
                            <p class="mt-0.5 text-sm text-slate-400" x-text="selectedCategoryForRound?.branch ?? ''"></p>
                        </div>

                        <!-- Round Options -->
                        <div class="space-y-3 rounded-2xl border border-slate-700/80 bg-slate-900/90 p-3 shadow-2xl backdrop-blur-md">
                            <!-- Penyisihan -->
                            <?php if ($roundLocked['Penyisihan']): ?>
                            <div class="group flex w-full items-center gap-4 rounded-xl border border-slate-600/40 bg-slate-800/30 p-4 text-left opacity-60 cursor-not-allowed">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-600/40 bg-slate-700/40 text-slate-500">
                                    <?= mtq_icon('layers', 'h-6 w-6') ?>
                                </span>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-400">Penyisihan</p>
                                    <p class="text-xs text-slate-500">Babak sudah ditutup</p>
                                </div>
                                <?= mtq_icon('lock', 'h-5 w-5 text-slate-500 shrink-0') ?>
                            </div>
                            <?php else: ?>
                            <button type="button" x-on:click="confirmRoundAndProceed('Penyisihan')"
                                class="group flex w-full items-center gap-4 rounded-xl border border-slate-700/60 bg-slate-800/50 p-4 text-left transition-all duration-200 hover:border-cyan-400/40 hover:bg-gradient-to-r hover:from-cyan-400/8 hover:to-transparent hover:shadow-[0_0_20px_-6px_rgba(34,211,238,0.2)]">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-cyan-400/20 bg-cyan-400/10 text-cyan-300 shadow-md shadow-cyan-400/10 transition-transform duration-200 group-hover:scale-110">
                                    <?= mtq_icon('layers', 'h-6 w-6') ?>
                                </span>
                                <div class="flex-1">
                                    <p class="font-bold text-white">Penyisihan</p>
                                    <p class="text-xs text-slate-400">Babak awal / kualifikasi</p>
                                </div>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-700/60 text-slate-400 transition-all duration-200 group-hover:translate-x-0.5 group-hover:bg-cyan-400/10 group-hover:text-cyan-300">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                </span>
                            </button>
                            <?php endif; ?>

                            <!-- Final -->
                            <?php if ($roundLocked['Final']): ?>
                            <div class="group flex w-full items-center gap-4 rounded-xl border border-slate-600/40 bg-slate-800/30 p-4 text-left opacity-60 cursor-not-allowed">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-600/40 bg-slate-700/40 text-slate-500">
                                    <?= mtq_icon('crown', 'h-6 w-6') ?>
                                </span>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-400">Final</p>
                                    <p class="text-xs text-slate-500">Babak sudah ditutup</p>
                                </div>
                                <?= mtq_icon('lock', 'h-5 w-5 text-slate-500 shrink-0') ?>
                            </div>
                            <?php else: ?>
                            <button type="button" x-on:click="confirmRoundAndProceed('Final')"
                                class="group flex w-full items-center gap-4 rounded-xl border border-slate-700/60 bg-slate-800/50 p-4 text-left transition-all duration-200 hover:border-amber-400/40 hover:bg-gradient-to-r hover:from-amber-400/8 hover:to-transparent hover:shadow-[0_0_20px_-6px_rgba(251,191,36,0.2)]">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-amber-400/20 bg-amber-400/10 text-amber-300 shadow-md shadow-amber-400/10 transition-transform duration-200 group-hover:scale-110">
                                    <?= mtq_icon('crown', 'h-6 w-6') ?>
                                </span>
                                <div class="flex-1">
                                    <p class="font-bold text-white">Final</p>
                                    <p class="text-xs text-slate-400">Babak penentuan juara</p>
                                </div>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-700/60 text-slate-400 transition-all duration-200 group-hover:translate-x-0.5 group-hover:bg-amber-400/10 group-hover:text-amber-300">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                </span>
                            </button>
                            <?php endif; ?>
                        </div>

                        <!-- Close hint -->
                        <p class="mt-3 text-center text-xs text-slate-500">Tekan <kbd class="rounded border border-slate-700 bg-slate-800 px-1.5 py-0.5 font-mono text-slate-400">Esc</kbd> untuk menutup</p>
                    </div>
                </div>

                <section id="step-2" class="glass-card rounded-[2rem] p-6" x-show="currentStep === 2" x-cloak>
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('fingerprint') ?></div>
                            <div>
                                <p class="section-kicker">Step 2</p>
                                <h2 class="text-2xl font-bold text-white">Atur hakim dan poin nilai</h2>
                                <p class="mt-2 text-sm text-slate-300">Babak dan golongan sudah dipilih di Step 1, jadi di sini fokusnya hanya ke hakim dan poin.</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="<?= $setupEditable ? 'status-pill border-cyan-300/20 bg-cyan-400/10 text-cyan-100' : ($setupReady ? 'status-pill' : 'inline-flex items-center gap-2 rounded-full border border-amber-400/18 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-100') ?>">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full <?= $setupEditable ? 'bg-cyan-300' : ($setupReady ? 'bg-emerald-300' : 'bg-amber-300') ?>"></span>
                                <?= $setupEditable ? 'Setting Dibuka' : ($setupReady ? 'Setting Tersimpan' : 'Belum Disiapkan') ?>
                            </div>
                            <?php if ($setupReady && $user?->role === 'admin'): ?>
                                <?php $isFinalized = $scoringSetting && $scoringSetting->isFinalized($selectedJudgingRound); ?>
                                <?php if ($isFinalized): ?>
                                <!-- Babak sudah ditutup - tombol buka -->
                                <form method="POST" action="<?= e(route('scoring.unfinalize-round')) ?>" x-data>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="competition_category_id" value="<?= e($selectedCategory?->id ?? '') ?>">
                                    <input type="hidden" name="judging_round" value="<?= e($selectedJudgingRound) ?>">
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-amber-400/40 bg-amber-400/10 px-4 py-2 text-xs font-semibold text-amber-100 transition hover:bg-amber-400/20">
                                        <?= mtq_icon('lock-open', 'h-4 w-4') ?>
                                        Buka Babak
                                    </button>
                                </form>
                                <?php else: ?>
                                <!-- Babak belum ditutup - tombol tutup -->
                                <form method="POST" action="<?= e(route('scoring.finalize-round')) ?>" x-data>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="competition_category_id" value="<?= e($selectedCategory?->id ?? '') ?>">
                                    <input type="hidden" name="judging_round" value="<?= e($selectedJudgingRound) ?>">
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-600/60 bg-slate-800/50 px-4 py-2 text-xs font-semibold text-slate-300 transition hover:bg-slate-700/60"
                                        onclick="return confirm('Yakin menutup babak <?= e($selectedJudgingRound) ?>? Peserta tidak akan bisa memilih babak ini lagi.')">
                                        <?= mtq_icon('lock', 'h-4 w-4') ?>
                                        Tutup Babak
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (! $setupCreated || $setupEditable): ?>
                    <form method="POST" action="<?= e(route('scoring.settings.store')) ?>" class="mt-6 grid gap-4" data-loading-text="Menyimpan konfigurasi penilaian..."
                        x-on:submit.prevent="syncAllRoundsBeforeSubmit(); $el.submit()"
                        x-data="scoringRoundSetupForm({
                            rounds: <?= e(json_encode($defaultRoundForms, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            activeRound: <?= e(json_encode(strtolower($selectedJudgingRound) === 'final' ? 'final' : 'penyisihan', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            availableJudges: <?= e(json_encode($availableJudges, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            categoryJudgeIds: <?= e(json_encode($categoryJudgeIds ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            selectedJudgingRound: <?= e(json_encode($selectedJudgingRound, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                        })">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="competition_category_id" value="<?= e($selectedCategory?->id ?? '') ?>">
                        <input type="hidden" name="judging_rounds_text" value="Penyisihan&#10;Final">
                        <input type="hidden" name="selected_judging_round" x-model="activeRound">
                        <!-- Always send data for both rounds to ensure correctness -->
                        <input type="hidden" name="rounds[penyisihan][judge_names_text]" x-model="rounds.penyisihan._judgeNamesText">
                        <input type="hidden" name="rounds[penyisihan][scoring_points_text]" x-model="rounds.penyisihan._scoringPointsText">
                        <input type="hidden" name="rounds[penyisihan][judge_count]" x-model.number="rounds.penyisihan.judgeCount">
                        <input type="hidden" name="rounds[penyisihan][judge_ids]" x-model="rounds.penyisihan._judgeIdsJson">
                        <input type="hidden" name="rounds[final][judge_names_text]" x-model="rounds.final._judgeNamesText">
                        <input type="hidden" name="rounds[final][scoring_points_text]" x-model="rounds.final._scoringPointsText">
                        <input type="hidden" name="rounds[final][judge_count]" x-model.number="rounds.final.judgeCount">
                        <input type="hidden" name="rounds[final][judge_ids]" x-model="rounds.final._judgeIdsJson">
                        <?php if ($selectedCategoryIsMfq): ?>
                            <div class="rounded-[1.5rem] border border-amber-400/20 bg-amber-400/10 px-4 py-4 text-sm text-amber-100">
                                Golongan yang sedang dipilih terdeteksi sebagai MFQ. Gunakan jalur MFQ dari Step 1, karena format penilaiannya berbeda dari setting babak umum.
                            </div>
                        <?php endif; ?>

                    <div class="rounded-[1.7rem] border border-slate-800 bg-slate-950/60 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="section-kicker">Setting Babak</p>
                                <h3 class="mt-2 text-xl font-bold text-white">Atur hakim dan poin penilaian</h3>
                                <p class="mt-2 text-sm text-slate-300">Konfigurasi disimpan per babak. Babak dipilih sebelum masuk ke halaman ini.</p>
                            </div>
                            <!-- Round Info Badge -->
                            <div class="flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2">
                                <?= mtq_icon('target', 'h-4 w-4 text-cyan-300') ?>
                                <span class="text-sm font-semibold text-cyan-100"><?= e($selectedJudgingRound) ?></span>
                            </div>
                        </div>

                            <?php
                            // Determine initial active round from server
                            $initialActiveRoundKey = strtolower($selectedJudgingRound) === 'final' ? 'final' : 'penyisihan';
                            $initialActiveRoundLabel = ucfirst($initialActiveRoundKey);
                            ?>
                            <section class="mt-5 space-y-5">
                                <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-200" x-text="activeRoundLabel"></p>
                                    <p class="mt-1 text-sm text-slate-300">Rapikan hakim dan poin untuk babak ini.</p>
                                </div>
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]"
                                    :class="roundReady(activeRound) ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border-amber-300/20 bg-amber-400/10 text-amber-100'"
                                    x-text="roundReady(activeRound) ? 'Setup siap' : 'Masih perlu dilengkapi'"></span>
                            </div>
                                </div>

                                    <div class="grid gap-5 xl:grid-cols-[1.08fr_0.92fr]">
                                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/50 p-4">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Nama hakim</label>
                                                    <p class="text-xs text-slate-400">Pilih hakim dari database untuk babak ini.</p>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1 text-xs font-semibold text-slate-200">
                                                        <span x-text="roundJudgeCount(activeRound)"></span> hakim
                                                    </span>
                                                    <button type="button" class="secondary-button rounded-xl px-3 py-2 text-xs" x-on:click="judgeSearchQuery = ''; judgeModalOpen = activeRound">
                                                        <?= mtq_icon('plus', 'h-4 w-4') ?>
                                                        Tambah Hakim
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mt-4 space-y-3">
                                                <template x-for="(judgeName, index) in rounds[activeRound].judgeNames" :key="activeRound + '-judge-' + index">
                                                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-4 py-3 flex items-center justify-between">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-400/18 bg-cyan-400/10 text-sm font-bold text-cyan-200" x-text="index + 1"></div>
                                                            <span class="font-semibold text-white" x-text="judgeName"></span>
                                                        </div>
                                                        <button type="button"
                                                            class="secondary-button rounded-xl px-3 py-2 text-xs"
                                                            x-on:click="removeJudge(activeRound, index)"
                                                            x-show="rounds[activeRound].judgeNames.length > 1">
                                                            <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                            Hapus
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <p x-show="hasJudgeNameIssues(activeRound)" class="mt-3 text-xs text-rose-200">Semua nama hakim babak <span x-text="activeRoundLabel"></span> wajib terisi dan tidak boleh ada yang sama.</p>
                                        </div>

                                        <!-- Modal Tambah Hakim -->
                                        <div x-show="judgeModalOpen === activeRound" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm px-4 py-6"
                                            x-on:keydown.escape.window="judgeModalOpen = null">
                                            <div class="absolute inset-0" x-on:click="judgeModalOpen = null"></div>
                                            <div class="relative z-10 w-full max-w-lg rounded-2xl border border-cyan-400/20 bg-slate-900 shadow-xl max-h-[85vh] flex flex-col">
                                                <div class="flex items-center justify-between border-b border-slate-700 px-6 py-4 shrink-0">
                                                    <div>
                                                        <h3 class="text-lg font-bold text-white">Tambah Hakim - <span x-text="activeRoundLabel"></span></h3>
                                                        <p class="mt-1 text-xs text-slate-400" x-text="`${availableJudges.filter(j => !rounds[activeRound].judgeNames.includes(j.nama)).length} hakim tersedia`"></p>
                                                    </div>
                                                    <button type="button" class="secondary-button rounded-xl px-3 py-2" x-on:click="judgeModalOpen = null">
                                                        <?= mtq_icon('x', 'h-4 w-4') ?>
                                                    </button>
                                                </div>

                                                <!-- Notice SK -->
                                                <div class="px-6 py-3 border-b border-slate-700/50 shrink-0">
                                                    <div class="flex items-start gap-2 rounded-lg border border-amber-400/20 bg-amber-400/10 px-3 py-2.5">
                                                        <?= mtq_icon('info', 'h-4 w-4 text-amber-300 shrink-0 mt-0.5') ?>
                                                        <p class="text-xs text-amber-100 leading-relaxed">
                                                            Daftar Dewan Hakim berdasarkan <strong>SK Bupati Tanah Datar Nomor 100.3.3.2/165/KESRA-2026</strong>. Jika ada/ingin menambahkan Hakim di luar SK, Hubungi Admin.
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Search -->
                                                <div class="px-6 py-3 border-b border-slate-700/50 shrink-0">
                                                    <div class="relative">
                                                        <?= mtq_icon('search', 'h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none') ?>
                                                        <input type="text"
                                                            x-model="judgeSearchQuery"
                                                            placeholder="Cari nama hakim..."
                                                            class="w-full rounded-xl border border-slate-700 bg-slate-950/80 pl-10 pr-4 py-2.5 text-sm text-slate-100 outline-none focus:border-cyan-400/50 focus:ring-1 focus:ring-cyan-400/20"
                                                            x-on:focus="judgeSearchQuery = judgeSearchQuery">
                                                    </div>
                                                </div>

                                                <div class="p-6 overflow-y-auto flex-1 min-h-0">
                                                    <!-- Hakim Berdasarkan SK -->
                                                    <div class="mb-4">
                                                        <div class="flex items-center gap-2 mb-3">
                                                            <span class="h-px flex-1 bg-gradient-to-r from-cyan-400/30 to-transparent"></span>
                                                            <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-300 bg-slate-900/80 px-2 py-1 rounded-full border border-cyan-400/20">
                                                                Hakim Berdasarkan SK
                                                            </span>
                                                            <span class="h-px flex-1 bg-gradient-to-l from-cyan-400/30 to-transparent"></span>
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <template x-for="judge in availableJudges.filter(j => categoryJudgeIds.includes(j.id) && !rounds[activeRound].judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase())))" :key="'sk-' + judge.id">
                                                                <button type="button"
                                                                    class="flex items-center gap-3 rounded-xl border border-cyan-400/20 bg-cyan-400/5 p-3 text-left transition hover:border-cyan-400/40 hover:bg-cyan-400/10 hover:scale-[1.01]"
                                                                    x-on:click="rounds[activeRound].judgeNames.push(judge.nama); rounds[activeRound].judgeCount = Math.min(15, rounds[activeRound].judgeNames.length); _syncHiddenInputs(activeRound); $nextTick(() => { if (availableJudges.filter(j => !rounds[activeRound].judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0) judgeModalOpen = null; })">
                                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-cyan-400/30 bg-cyan-400/15 text-xs font-bold text-cyan-200">
                                                                        <span x-text="availableJudges.findIndex(j => j.id === judge.id) + 1"></span>
                                                                    </span>
                                                                    <div class="min-w-0 flex-1">
                                                                        <p class="text-sm font-semibold text-white truncate" x-text="judge.nama"></p>
                                                                        <p class="text-xs text-slate-400 truncate" x-text="judge.asal || '-'"></p>
                                                                    </div>
                                                                    <?= mtq_icon('plus-circle', 'h-5 w-5 text-cyan-400 shrink-0') ?>
                                                                </button>
                                                            </template>
                                                            <p x-show="availableJudges.filter(j => categoryJudgeIds.includes(j.id) && !rounds[activeRound].judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0" class="py-4 text-center text-xs text-slate-500">
                                                                <span x-text="judgeSearchQuery ? 'Tidak ada hasil pencarian' : 'Semua hakim SK sudah ditambahkan'"></span>
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Hakim Lainnya (di luar SK) -->
                                                    <div>
                                                        <div class="flex items-center gap-2 mb-3">
                                                            <span class="h-px flex-1 bg-gradient-to-r from-slate-600/40 to-transparent"></span>
                                                            <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400 bg-slate-900/80 px-2 py-1 rounded-full border border-slate-600/30">
                                                                Hakim Lainnya (di luar SK)
                                                            </span>
                                                            <span class="h-px flex-1 bg-gradient-to-l from-slate-600/40 to-transparent"></span>
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <template x-for="judge in availableJudges.filter(j => !categoryJudgeIds.includes(j.id) && !rounds[activeRound].judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase())))" :key="'other-' + judge.id">
                                                                <button type="button"
                                                                    class="flex items-center gap-3 rounded-xl border border-slate-700/60 bg-slate-800/30 p-3 text-left transition hover:border-amber-400/30 hover:bg-amber-400/5 hover:scale-[1.01]"
                                                                    x-on:click="rounds[activeRound].judgeNames.push(judge.nama); rounds[activeRound].judgeCount = Math.min(15, rounds[activeRound].judgeNames.length); _syncHiddenInputs(activeRound); $nextTick(() => { if (availableJudges.filter(j => !rounds[activeRound].judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0) judgeModalOpen = null; })">
                                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-600/40 bg-slate-800/50 text-xs font-bold text-slate-400">
                                                                        <span x-text="availableJudges.findIndex(j => j.id === judge.id) + 1"></span>
                                                                    </span>
                                                                    <div class="min-w-0 flex-1">
                                                                        <p class="text-sm font-semibold text-slate-200 truncate" x-text="judge.nama"></p>
                                                                        <p class="text-xs text-slate-500 truncate" x-text="judge.asal || '-'"></p>
                                                                    </div>
                                                                    <?= mtq_icon('plus-circle', 'h-5 w-5 text-amber-400/70 shrink-0') ?>
                                                                </button>
                                                            </template>
                                                            <p x-show="availableJudges.filter(j => !categoryJudgeIds.includes(j.id) && !rounds[activeRound].judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0" class="py-4 text-center text-xs text-slate-600">
                                                                <span x-text="judgeSearchQuery ? 'Tidak ada hasil pencarian' : 'Tidak ada hakim lain'"></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center justify-end gap-3 border-t border-slate-700 px-6 py-4 shrink-0">
                                                    <button type="button" class="secondary-button" x-on:click="judgeModalOpen = null">
                                                        Tutup
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/50 p-4">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Poin yang dinilai</label>
                                            <div class="space-y-3">
                                                <template x-for="(pointLabel, index) in rounds[activeRound].scoringPoints" :key="activeRound + '-point-' + index">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-emerald-400/18 bg-emerald-400/10 text-sm font-bold text-emerald-100" x-text="index + 1"></div>
                                                        <input type="text" x-model="rounds[activeRound].scoringPoints[index]" x-on:input="_syncHiddenInputs(activeRound)" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" :placeholder="'Poin penilaian ' + (index + 1)">
                                                        <button type="button" class="secondary-button rounded-xl px-3 py-2" x-on:click="movePointUp(activeRound, index)" x-bind:disabled="index === 0" title="Naik">
                                                            <?= mtq_icon('arrow-up', 'h-4 w-4') ?>
                                                        </button>
                                                        <button type="button" class="secondary-button rounded-xl px-3 py-2 text-xs" x-on:click="removePoint(activeRound, index)" x-bind:disabled="rounds[activeRound].scoringPoints.length <= 1">
                                                            Hapus
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-3">
                                                <button type="button" class="secondary-button" x-on:click="addPoint(activeRound)">
                                                    <?= mtq_icon('plus', 'h-4 w-4') ?>
                                                    Tambah Poin
                                                </button>
                                            </div>
                                            <div class="mt-3 rounded-xl border border-amber-400/30 bg-amber-400/10 px-4 py-3">
                                                <p class="text-xs font-semibold text-amber-200">
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <?= mtq_icon('info', 'h-4 w-4') ?>
                                                        Urutan Poin = Prioritas Tie-Break
                                                    </span>
                                                </p>
                                                <p class="mt-1 text-xs text-amber-300/80">Urutan poin di babak <span x-text="activeRoundLabel"></span> akan menentukan prioritas tie-break. Pastikan urutan sudah benar sebelum menyimpan.</p>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="primary-button" :disabled="hasAnyRoundIssues" x-bind:class="hasAnyRoundIssues ? 'cursor-not-allowed opacity-60' : ''">
                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                Simpan Setting Penilaian
                            </button>
                            <?php if ($selectedCategory): ?>
                                <div class="secondary-button cursor-default">
                                    <?= mtq_icon('layers', 'h-4 w-4') ?>
                                    <?= e($selectedCategory->branch.' - '.$selectedCategory->name) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="mt-6 rounded-[1.7rem] border border-emerald-400/20 bg-emerald-400/10 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="section-kicker">Step 2 Tersimpan</p>
                                    <h3 class="mt-2 text-xl font-bold text-white">Setting babak untuk golongan ini sudah pernah dibuat</h3>
                                    <p class="mt-2 text-sm text-slate-300">Step 2 tidak perlu diulang lagi. Jika mau mengubah setting babak, kirim request ke admin dan tunggu pembukaan akses.</p>
                                </div>
                                <div class="status-pill border-emerald-300/20 bg-emerald-400/10 text-emerald-100">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                    Siap lanjut
                                </div>
                            </div>
                            <div class="mt-5 flex flex-wrap gap-3">
                                <button type="button" class="primary-button" x-on:click="goToStep(3)">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    Lanjut ke Step 3
                                </button>
                                <?php if (($user?->role ?? '') === 'admin'): ?>
                                    <form method="POST" action="<?= e(route('scoring.settings.open')) ?>">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="competition_category_id" value="<?= e($selectedCategory?->id ?? '') ?>">
                                        <input type="hidden" name="judging_round" value="<?= e($selectedJudgingRound) ?>">
                                        <button type="submit" class="secondary-button">
                                            <?= mtq_icon('key', 'h-4 w-4') ?>
                                            Buka Step 2
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="<?= e(route('scoring.settings.request')) ?>">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="competition_category_id" value="<?= e($selectedCategory?->id ?? '') ?>">
                                        <input type="hidden" name="judging_round" value="<?= e($selectedJudgingRound) ?>">
                                        <button type="submit" class="secondary-button">
                                            <?= mtq_icon('mail', 'h-4 w-4') ?>
                                            Request ke Admin
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <section id="step-3" class="grid gap-6" x-show="currentStep === 3" x-cloak>
                    <!-- PDF Recap Buttons -->
                    <?php if ($selectedCategory): ?>
                    <div class="rounded-[1.5rem] border border-amber-400/30 bg-gradient-to-br from-slate-900/80 to-slate-950/90 p-6 shadow-lg">
                        <!-- Header Title -->
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-amber-400 via-orange-500 to-amber-500 shadow-xl shadow-amber-500/30 mb-4">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-black text-white tracking-tight">📄 Rekap Rincian Nilai Peserta</h3>
                            <p class="mt-2 text-sm text-slate-300">Cetak PDF dengan rincian nilai per hakim untuk <?= e($selectedJudgingRound) ?></p>
                            <div class="mt-3 inline-flex items-center gap-2">
                                <span class="h-px w-12 bg-gradient-to-r from-transparent to-amber-400/50"></span>
                                <span class="text-xs font-semibold uppercase tracking-widest text-amber-400/70">Pilih Tipe Laporan</span>
                                <span class="h-px w-12 bg-gradient-to-l from-transparent to-amber-400/50"></span>
                            </div>
                        </div>
                        <!-- Buttons Grid -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Ranking PDF -->
                            <a href="<?= e(route('scoring.ranking.pdf', ['competition_category_id' => $selectedCategory->id, 'judging_round' => $selectedJudgingRound])) ?>"
                               target="_blank"
                               class="group relative flex items-center justify-between gap-4 overflow-hidden rounded-2xl border-2 border-amber-400/50 bg-gradient-to-br from-amber-500/10 via-orange-500/10 to-amber-500/10 p-5 transition-all hover:border-amber-400 hover:scale-[1.02] hover:shadow-[0_0_50px_-10px_rgba(245,158,11,0.5)] hover:from-amber-500/20 hover:via-orange-500/20 hover:to-amber-500/20">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg shadow-amber-500/40">
                                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-amber-100">📊 Berdasarkan Ranking</h4>
                                        <p class="mt-1 text-sm text-amber-200/60">Putra & Putri terpisah</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span class="rounded-full bg-amber-400/20 px-4 py-2 text-sm font-bold text-amber-200 border border-amber-400/30">
                                        🏆 Ranking
                                    </span>
                                    <svg class="h-5 w-5 text-amber-400 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </div>
                            </a>
                            <!-- Lot PDF -->
                            <a href="<?= e(route('scoring.ranking-lot.pdf', ['competition_category_id' => $selectedCategory->id, 'judging_round' => $selectedJudgingRound])) ?>"
                               target="_blank"
                               class="group relative flex items-center justify-between gap-4 overflow-hidden rounded-2xl border-2 border-purple-400/50 bg-gradient-to-br from-purple-500/10 via-violet-500/10 to-purple-500/10 p-5 transition-all hover:border-purple-400 hover:scale-[1.02] hover:shadow-[0_0_50px_-10px_rgba(168,85,247,0.5)] hover:from-purple-500/20 hover:via-violet-500/20 hover:to-purple-500/20">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-400 to-violet-500 shadow-lg shadow-purple-500/40">
                                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-purple-100">📋 Berdasarkan Nomor Lot</h4>
                                        <p class="mt-1 text-sm text-purple-200/60">Putra & Putri digabung</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span class="rounded-full bg-purple-400/20 px-4 py-2 text-sm font-bold text-purple-200 border border-purple-400/30">
                                        🎯 Lot
                                    </span>
                                    <svg class="h-5 w-5 text-purple-400 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </div>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="glass-card rounded-[2rem] p-6"
                        x-data="participantPicker({
                            participants: <?= e(json_encode($participantOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            districts: <?= e(json_encode($districtOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            selectedId: <?= e(json_encode((string) ($selectedParticipant?->id ?? ''))) ?>,
                            selectedDistrictId: null,
                        })">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('users') ?></div>
                            <div>
                                <h2 class="text-2xl font-bold text-white">
                                    <?= $selectedCategoryIsMsq ? 'Kecamatan / Nomor Lot' : 'Peserta Siap Dinilai' ?>
                                </h2>
                                <p class="mt-1 text-sm text-slate-300">
                                    <?= $selectedCategoryIsMsq
                                        ? 'Pilih kecamatan/lot yang akan dinilai. 1 kecamatan = 1 nomor lot.'
                                        : 'Cari peserta lalu pilih dari daftar agar tetap ringan walau data peserta banyak.' ?>
                                </p>
                            </div>
                        </div>
                        <div class="status-pill mt-4">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?php if ($selectedCategoryIsMsq): ?>
                                <?= count($districtOptions) ?> Kecamatan / Lot
                            <?php else: ?>
                                <?= e(count($participants)) ?> Peserta
                            <?php endif; ?>
                        </div>

                        <div class="mt-6 space-y-4">
                            <?php if (count($participants) === 0): ?>
                                <div class="data-card text-sm text-slate-300">Belum ada peserta terverifikasi yang sesuai dengan filter.</div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <label class="block text-sm font-semibold text-slate-200">Cari peserta siap dinilai</label>
                                    <div class="relative">
                                        <input type="text"
                                            x-model="search"
                                            x-on:focus="dropdownOpen = true"
                                            x-on:keydown.arrow-down.prevent="highlightNext()"
                                            x-on:keydown.arrow-up.prevent="highlightPrevious()"
                                            x-on:keydown.enter.prevent="selectHighlighted()"
                                            x-on:keydown.escape.prevent="dropdownOpen = false"
                                            placeholder="Ketik nama, lot, kecamatan, atau golongan"
                                            class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button"
                                            class="rounded-full border px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition"
                                            :class="scoreFilterMode === 'all' ? 'border-cyan-300/30 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-950/70 text-slate-400 hover:border-cyan-400/30 hover:text-white'"
                                            x-on:click="scoreFilterMode = 'all'; highlightedIndex = 0;">
                                            Semua
                                        </button>
                                        <button type="button"
                                            class="rounded-full border px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition"
                                            :class="scoreFilterMode === 'scored' ? 'border-emerald-300/30 bg-emerald-400/10 text-emerald-100' : 'border-slate-700 bg-slate-950/70 text-slate-400 hover:border-cyan-400/30 hover:text-white'"
                                            x-on:click="scoreFilterMode = 'scored'; highlightedIndex = 0;">
                                            Sudah Dinilai
                                        </button>
                                        <button type="button"
                                            class="rounded-full border px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition"
                                            :class="scoreFilterMode === 'unscored' ? 'border-amber-300/30 bg-amber-400/10 text-amber-100' : 'border-slate-700 bg-slate-950/70 text-slate-400 hover:border-cyan-400/30 hover:text-white'"
                                            x-on:click="scoreFilterMode = 'unscored'; highlightedIndex = 0;">
                                            Belum Dinilai
                                        </button>
                                    </div>
                                    <?php if ($selectedCategoryIsMsq): ?>
                                    <!-- MSQ: District/Lot List -->
                                    <div class="max-h-80 overflow-y-auto rounded-[1.4rem] border border-slate-800 bg-slate-950/95 p-2 shadow-[0_18px_50px_-30px_rgba(15,23,42,0.9)]">
                                        <div class="px-3 py-2 text-[11px] uppercase tracking-[0.2em] text-slate-500">Daftar Kecamatan / Lot</div>
                                        <template x-if="filteredDistricts.length === 0">
                                            <div class="rounded-[1rem] px-4 py-3 text-sm text-slate-400">Tidak ada kecamatan yang cocok dengan pencarian.</div>
                                        </template>
                                        <template x-for="(district, index) in filteredDistricts" :key="district.id">
                                            <button type="button"
                                                class="flex w-full items-start gap-3 rounded-[1rem] px-3 py-3 text-left transition"
                                                :class="highlightedIndex === index ? 'bg-cyan-400/12 text-white' : 'text-slate-300 hover:bg-slate-900/80'"
                                                x-on:mouseenter="highlightedIndex = index"
                                                x-on:click="selectDistrict(district)">
                                                <template x-if="district.photo">
                                                    <img :src="district.photo" :alt="`Foto ${district.name}`" class="h-14 w-11 shrink-0 rounded-[0.9rem] border border-cyan-400/16 object-cover">
                                                </template>
                                                <template x-if="!district.photo">
                                                    <div class="flex h-14 w-11 shrink-0 items-center justify-center rounded-[0.9rem] border border-slate-700 bg-slate-900/80 text-[10px] uppercase tracking-[0.2em] text-slate-500">
                                                        Lot
                                                    </div>
                                                </template>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-semibold text-white" x-text="district.name"></p>
                                                    <p class="mt-1 truncate text-xs text-amber-300 font-bold" x-text="`Lot ${district.lot_number}`"></p>
                                                    <p class="mt-1 truncate text-xs text-slate-400" x-text="`${district.participant_count} peserta`"></p>
                                                    <div class="mt-2 flex flex-wrap gap-2 text-[11px]">
                                                        <span class="inline-flex rounded-full border px-2.5 py-1" :class="district.score_count > 0 ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-slate-700 bg-slate-900/80 text-slate-400'" x-text="district.scoring_status"></span>
                                                        <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-2.5 py-1 text-cyan-200" x-show="district.score_count > 0">
                                                            <span x-text="`Avg ${district.average_score}`"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                    <?php else: ?>
                                    <!-- Regular: Participant List -->
                                    <div class="max-h-80 overflow-y-auto rounded-[1.4rem] border border-slate-800 bg-slate-950/95 p-2 shadow-[0_18px_50px_-30px_rgba(15,23,42,0.9)]">
                                        <div class="px-3 py-2 text-[11px] uppercase tracking-[0.2em] text-slate-500">Daftar Peserta</div>
                                        <template x-if="filteredParticipants.length === 0">
                                            <div class="rounded-[1rem] px-4 py-3 text-sm text-slate-400">Tidak ada peserta yang cocok dengan pencarian.</div>
                                        </template>
                                        <template x-for="(participant, index) in filteredParticipants" :key="participant.id">
                                                    <button type="button"
                                                        class="flex w-full items-start gap-3 rounded-[1rem] px-3 py-3 text-left transition"
                                                        :class="highlightedIndex === index ? 'bg-cyan-400/12 text-white' : 'text-slate-300 hover:bg-slate-900/80'"
                                                        x-on:mouseenter="highlightedIndex = index"
                                                        x-on:click="selectParticipant(participant)">
                                                <template x-if="participant.photo">
                                                    <img :src="participant.photo" :alt="`Foto ${participant.name}`" class="h-14 w-11 shrink-0 rounded-[0.9rem] border border-cyan-400/16 object-cover">
                                                </template>
                                                <template x-if="!participant.photo">
                                                    <div class="flex h-14 w-11 shrink-0 items-center justify-center rounded-[0.9rem] border border-slate-700 bg-slate-900/80 text-[10px] uppercase tracking-[0.2em] text-slate-500">
                                                        Foto
                                                    </div>
                                                </template>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-semibold text-white" x-text="participant.name"></p>
                                                    <p class="mt-1 truncate text-xs text-slate-400" x-text="`Lot ${participant.lot_number} | ${participant.district}`"></p>
                                                    <p class="mt-1 truncate text-xs text-cyan-200" x-text="`${participant.branch} | ${participant.category}`"></p>
                                                    <div class="mt-2 flex flex-wrap gap-2 text-[11px]">
                                                        <span class="inline-flex rounded-full border px-2.5 py-1" :class="Number(participant.score_count || 0) > 0 ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-slate-700 bg-slate-900/80 text-slate-400'" x-text="participant.scoring_status"></span>
                                                        <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-2.5 py-1 text-cyan-200" x-show="Number(participant.score_count || 0) > 0">
                                                            <span x-text="`Avg ${participant.average_score}`"></span>
                                                        </span>
                                                        <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-slate-300" x-show="Number(participant.score_count || 0) > 0">
                                                            <span x-text="`Terakhir ${participant.latest_score} (${participant.latest_round})`"></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                    <?php endif; ?>
                                            </div>
                                </div>
                                <div class="mt-5 rounded-[1.5rem] border border-slate-800 bg-slate-950/45 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200" x-text="isMsq ? 'Kecamatan Terpilih' : 'Peserta Terpilih'"></p>
                                            <h3 class="mt-1 text-lg font-bold text-white" x-text="isMsq ? (selectedDistrict ? selectedDistrict.name : 'Belum ada kecamatan dipilih') : (selectedParticipant ? selectedParticipant.name : 'Belum ada peserta dipilih')"></h3>
                                        </div>
                                        <template x-if="selectedParticipant || selectedDistrict">
                                            <div class="status-pill">
                                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                                <span x-text="selectedDistrict ? selectedDistrict.lot_number : selectedParticipant?.lot_number"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <template x-if="selectedParticipant">
                                        <div class="mt-4 grid gap-4 lg:grid-cols-[156px_minmax(0,1fr)] items-start">
                                            <template x-if="selectedParticipant.photo">
                                                <img :src="selectedParticipant.photo" :alt="`Foto ${selectedParticipant.name}`" class="h-40 w-full max-w-[156px] rounded-[1.2rem] border border-cyan-400/16 object-cover">
                                            </template>
                                            <template x-if="!selectedParticipant.photo">
                                                <div class="flex h-40 w-full max-w-[156px] items-center justify-center rounded-[1.2rem] border border-slate-700 bg-slate-950/70 text-center text-xs uppercase tracking-[0.22em] text-slate-500">
                                                    Tanpa foto
                                                </div>
                                            </template>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-xs text-slate-300"><span x-text="selectedParticipant.branch"></span> | <span x-text="selectedParticipant.category"></span></p>
                                                <p class="mt-2 truncate text-xs text-slate-400"><span x-text="selectedParticipant.district"></span> | <span x-text="selectedParticipant.lot_number"></span></p>
                                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                    <div class="rounded-[1rem] border border-slate-800 bg-slate-950/45 px-3 py-3">
                                                        <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Kecamatan</p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-100" x-text="selectedParticipant.district"></p>
                                                    </div>
                                                    <div class="rounded-[1rem] border border-slate-800 bg-slate-950/45 px-3 py-3">
                                                        <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Institusi</p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-100" x-text="selectedParticipant.institution"></p>
                                                    </div>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                                    <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1 text-slate-300" x-text="selectedParticipant.branch"></span>
                                                    <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-cyan-100" x-text="selectedParticipant.category"></span>
                                                    <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-cyan-200">Avg: <span class="ml-1" x-text="selectedParticipant.average_score"></span></span>
                                                    <span class="inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-emerald-100" x-text="`Lot ${selectedParticipant.lot_number}`"></span>
                                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs" :class="Number(selectedParticipant.score_count || 0) > 0 ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-slate-700 bg-slate-900/80 text-slate-400'" x-text="selectedParticipant.scoring_status"></span>
                                                    <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1 text-slate-300" x-show="Number(selectedParticipant.score_count || 0) > 0" x-text="`Nilai terakhir ${selectedParticipant.latest_score} (${selectedParticipant.latest_round})`"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!selectedParticipant">
                                        <div class="mt-4 text-sm text-slate-300">Pilih peserta dari daftar untuk melihat ringkasan singkatnya di panel ini.</div>
                                    </template>

                                    <?php if ($selectedParticipant && $selectedCategory): ?>
                                        <div class="mt-5 flex flex-wrap gap-3">
                                            <a href="<?= e($bigScreenUrl) ?>" target="_blank" rel="noreferrer" class="secondary-button">
                                                <?= mtq_icon('eye', 'h-4 w-4') ?>
                                                Buka Big Screen Peserta Aktif
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    <div id="form-penilaian" class="glass-card rounded-[2rem] p-4 sm:p-6">
                            <div class="rounded-[1.65rem] border border-cyan-400/14 bg-gradient-to-br from-cyan-400/10 via-slate-950/70 to-slate-950/95 p-4 sm:p-5 shadow-[0_20px_60px_-36px_rgba(34,211,238,0.45)]">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                    <div class="max-w-full sm:max-w-2xl">
                                        <div class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-100">
                                            <?= mtq_icon('check-circle', 'h-3.5 w-3.5') ?>
                                            Form Penilaian
                                        </div>
                                        <h2 class="mt-2 sm:mt-3 text-xl sm:text-2xl font-black text-white">Input nilai hakim per peserta</h2>
                                        <p class="mt-2 sm:mt-3 max-w-xl text-sm leading-6 text-slate-300">Babak aktif mengikuti setting yang sudah dipilih operator. Gunakan tab hakim di bawah untuk berpindah panel, lalu simpan seluruh batch sekaligus.</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 shrink-0">
                                        <div class="rounded-[1.2rem] border border-slate-800 bg-slate-950/70 px-3 sm:px-4 py-2 sm:py-3">
                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Babak aktif</p>
                                            <p class="mt-1 text-sm sm:text-base font-bold text-white"><?= e($selectedJudgingRound) ?></p>
                                        </div>
                                        <div class="rounded-[1.2rem] border border-slate-800 bg-slate-950/70 px-3 sm:px-4 py-2 sm:py-3">
                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Jumlah hakim</p>
                                            <p class="mt-1 text-sm sm:text-base font-bold text-cyan-200"><?= e(count($judgeNames)) ?> orang</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (! $selectedCategory): ?>
                                <div class="mt-6 data-card text-sm text-slate-300">Pilih golongan terlebih dahulu pada filter atau pengaturan penilaian.</div>
                            <?php elseif (! $setupReady): ?>
                                <div class="mt-6 data-card text-sm text-amber-100">Setting penilaian untuk golongan ini belum lengkap. Simpan setting lebih dahulu sebelum input nilai peserta.</div>
                            <?php elseif (! $selectedParticipant): ?>
                                <div class="mt-6 data-card text-sm text-slate-300">Belum ada peserta dipilih untuk dinilai.</div>
                            <?php else: ?>
                                <div x-data="{ correctionRequestOpen: false, correctionRequestName: '', correctionRequestLot: '', correctionRequestRound: '', correctionRequestDraft: {}, init() { window.addEventListener('open-scoring-correction-request', (event) => { const detail = event?.detail ?? {}; this.correctionRequestOpen = true; this.correctionRequestName = detail.name || this.correctionRequestName; this.correctionRequestLot = detail.lot || this.correctionRequestLot; this.correctionRequestRound = detail.round || this.correctionRequestRound; this.correctionRequestDraft = detail.draft || this.correctionRequestDraft; }); window.addEventListener('enable-edit-mode', () => { Alpine.store('scoringEdit').enabled = true; }); } }">

                                <!-- Form Penilaian Container - MORE PROMINENT -->
                                <div id="form-penilaian" class="relative mt-6 rounded-[2rem] border-2 border-cyan-400/40 bg-gradient-to-br from-slate-900 via-slate-900/95 to-slate-950 p-6 shadow-[0_0_60px_-20px_rgba(34,211,238,0.3)] overflow-hidden">

                                    <!-- Glow Effect Background -->
                                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-cyan-500/5 via-transparent to-sky-500/5"></div>
                                    <div class="pointer-events-none absolute -top-24 -right-24 h-48 w-48 rounded-full bg-cyan-500/10 blur-3xl"></div>
                                    <div class="pointer-events-none absolute -bottom-24 -left-24 h-48 w-48 rounded-full bg-emerald-500/10 blur-3xl"></div>

                                    <!-- Header - Prominent -->
                                    <div class="relative mb-6">
                                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="inline-flex items-center gap-2 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-4 py-1.5 text-xs font-bold uppercase tracking-[0.22em] text-cyan-100">
                                                    <?= mtq_icon('spark', 'h-4 w-4') ?>
                                                    Form Penilaian Aktif
                                                </div>
                                                <h2 class="mt-3 text-2xl sm:text-3xl font-black text-white">
                                                    Input Nilai <?= e($selectedJudgingRound) ?>
                                                </h2>
                                                <p class="mt-2 text-sm text-slate-300">Isi nilai hakim per panel, pindah dengan tombol navigasi, lalu simpan semua sekaligus.</p>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-3 shrink-0">
                                                <div class="rounded-2xl border border-violet-400/30 bg-violet-400/10 px-4 py-3 text-center">
                                                    <p class="text-[10px] uppercase tracking-[0.18em] text-violet-300">Babak</p>
                                                    <p class="mt-1 text-lg font-bold text-white"><?= e($selectedJudgingRound) ?></p>
                                                </div>
                                                <div class="rounded-2xl border border-cyan-400/30 bg-cyan-400/10 px-4 py-3 text-center">
                                                    <p class="text-[10px] uppercase tracking-[0.18em] text-cyan-300">Jumlah Hakim</p>
                                                    <p class="mt-1 text-lg font-bold text-cyan-100"><?= e(count($judgeNames)) ?> Orang</p>
                                                </div>
                                                <div class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-center">
                                                    <p class="text-[10px] uppercase tracking-[0.18em] text-emerald-300">Jumlah Poin</p>
                                                    <p class="mt-1 text-lg font-bold text-emerald-100"><?= e(count($criteria)) ?> Poin</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($participantHasScores): ?>
                                    <!-- Edit Mode Toggle - Prominent but Clean -->
                                    <div class="relative mb-6 rounded-2xl border-2 border-dashed border-amber-400/40 bg-gradient-to-r from-amber-500/10 to-orange-500/10 p-5"
                                         x-show="!$store.scoringEdit.enabled"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                                         x-transition:enter-end="opacity-100 transform translate-y-0">
                                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                            <div class="flex items-center gap-4">
                                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/20 border border-amber-400/30">
                                                    <?= mtq_icon('pencil', 'h-7 w-7 text-amber-300') ?>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-bold text-amber-100">Peserta Sudah Dinilai</h3>
                                                    <p class="mt-0.5 text-sm text-amber-200/70">Aktifkan mode edit untuk mengubah nilai</p>
                                                </div>
                                            </div>
                                            <button type="button"
                                                    @click="confirmEditModeGlobal(<?= e(count($judgeNames)) ?>)"
                                                    class="group relative overflow-hidden rounded-full bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:from-amber-400 hover:to-orange-400 hover:shadow-amber-500/30 hover:scale-105 active:scale-95">
                                                <span class="relative z-10 flex items-center gap-2">
                                                    <?= mtq_icon('pencil', 'h-5 w-5') ?>
                                                    Aktifkan Edit
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Edit Mode Active Banner -->
                                    <div class="relative mb-6 rounded-2xl border-2 border-dashed border-red-400/50 bg-gradient-to-r from-red-500/20 to-orange-500/20 p-5"
                                         x-show="$store.scoringEdit.enabled"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                                         x-transition:enter-end="opacity-100 transform translate-y-0">
                                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                            <div class="flex items-center gap-4">
                                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-500/30 border border-red-400/40">
                                                    <svg class="h-7 w-7 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-bold text-red-100">⚠️ Mode Edit Aktif - Isi Ulang Semua Nilai!</h3>
                                                    <p class="mt-1 text-sm text-red-200/80">
                                                        <strong>Wajib!</strong> Masukkan nilai untuk semua <?= e(count($judgeNames)) ?> Dewan Hakim. Nilai lama sudah dihapus.
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button"
                                                    @click="$store.scoringEdit.enabled = false"
                                                    class="group relative overflow-hidden rounded-full bg-gradient-to-r from-slate-600 to-slate-700 px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:from-slate-500 hover:to-slate-600 hover:scale-105 active:scale-95">
                                                <span class="relative z-10 flex items-center gap-2">
                                                    <?= mtq_icon('x', 'h-5 w-5') ?>
                                                    Batal Edit
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                <form method="POST" action="<?= e(route('scoring.store')) ?>" x-ref="scoreForm" class="relative mt-4 grid gap-5"
                                    x-data="judgeBatchForm({
                                        judgeNames: <?= e(json_encode(array_values($judgeNames), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                        judgeIds: <?= e(json_encode(array_values($judgeIds ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                        initialJudgeIndex: <?= e($initialJudgeIndex) ?>,
                                        selectedParticipantName: <?= e(json_encode($selectedParticipant->name ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                        selectedParticipantLot: <?= e(json_encode($selectedParticipant->lot_number ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                        selectedJudgingRound: <?= e(json_encode($selectedJudgingRound, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                    })">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="participant_id" x-bind:value="window.currentParticipantId || '<?= e($selectedParticipant?->id ?? '') ?>'">
                                    <input type="hidden" name="judging_round" value="<?= e($selectedJudgingRound) ?>">
                                    <input type="hidden" name="active_judge_index" :value="activeJudgeIndex">

                                    <fieldset x-bind:disabled="!$store.scoringEdit.enabled && <?= $participantHasScores ? 'true' : 'false' ?>">

                                    <!-- Info Bar - Minimal -->
                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-cyan-400/20 bg-cyan-400/10 px-4 py-2.5">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
                                            <span class="text-sm text-cyan-100">Panel aktif: <span class="font-bold" x-text="progressLabel()"></span></span>
                                        </div>
                                        <div class="flex items-center gap-4 text-xs text-slate-400">
                                            <span><?= e(count($judgeNames)) ?> Hakim</span>
                                            <span class="text-cyan-400/50">|</span>
                                            <span><?= e(count($criteria)) ?> Poin</span>
                                            <span class="text-cyan-400/50">|</span>
                                            <span>Babak: <?= e($selectedJudgingRound) ?></span>
                                        </div>
                                    </div>

                                    <!-- Progress & Judge Tabs -->
                                    <div class="rounded-2xl border border-slate-700/80 bg-slate-900/50 p-4">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Navigasi Panel Hakim</p>
                                                <p class="mt-1 text-sm text-slate-300">Klik tab di bawah untuk berpindah hakim</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-slate-500">Progress:</span>
                                                <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-800">
                                                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-emerald-400 transition-all" :style="`width: ${progressPercent()}%`"></div>
                                                </div>
                                                <span class="text-xs font-semibold text-cyan-300" x-text="completionSummary()"></span>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($judgeNames as $idx => $jn): ?>
                                                <button type="button"
                                                    class="group relative rounded-xl border px-4 py-3 transition-all"
                                                    :class="activeJudgeIndex === <?= e($idx) ?> ? 'border-cyan-400 bg-cyan-500/20 shadow-[0_0_20px_-5px_rgba(34,211,238,0.4)] scale-105' : 'border-slate-700 bg-slate-800/50 hover:border-cyan-400/50 hover:bg-slate-800'"
                                                    x-on:click="goToJudge(<?= e($idx) ?>)">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg border transition-colors"
                                                             :class="activeJudgeIndex === <?= e($idx) ?> ? 'border-cyan-400/50 bg-cyan-400/20' : 'border-slate-600 bg-slate-700'">
                                                            <span class="text-sm font-bold" :class="activeJudgeIndex === <?= e($idx) ?> ? 'text-cyan-200' : 'text-slate-400'"><?= e($idx + 1) ?></span>
                                                        </div>
                                                        <div class="text-left">
                                                            <p class="text-xs uppercase tracking-[0.1em]" :class="activeJudgeIndex === <?= e($idx) ?> ? 'text-cyan-300' : 'text-slate-500'">Hakim <?= e($idx + 1) ?></p>
                                                            <p class="text-sm font-semibold text-white truncate max-w-[120px]" x-text="'<?= e($jn) ?>'"></p>
                                                        </div>
                                                        <span class="h-3 w-3 rounded-full transition-all"
                                                              :class="judgeStatusDotClass(<?= e($idx) ?>)"
                                                              :title="judgeStatusLabel(<?= e($idx) ?>)"></span>
                                                    </div>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <?php foreach ($judgeNames as $index => $judgeName): ?>
                                        <section x-show="activeJudgeIndex === <?= e($index) ?>" x-cloak data-judge-panel="<?= e($index) ?>" class="space-y-5">

                                            <!-- Panel Header -->
                                            <div class="flex items-center gap-4 rounded-2xl border border-cyan-400/20 bg-gradient-to-r from-cyan-500/10 to-sky-500/10 px-5 py-4">
                                                <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-cyan-400/30 bg-cyan-500/20">
                                                    <span class="text-xl font-black text-cyan-200"><?= e($index + 1) ?></span>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-300">Panel Hakim</p>
                                                    <h3 class="text-xl font-bold text-white"><?= e($judgeName) ?></h3>
                                                </div>
                                                <div class="hidden sm:flex items-center gap-2">
                                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
                                                    <span class="text-sm text-cyan-200">Aktif</span>
                                                </div>
                                            </div>

                                            <!-- Info Pemberitahuan - Updated -->
                                            <div class="flex items-start gap-4 rounded-2xl border border-amber-400/30 bg-gradient-to-r from-amber-500/15 to-orange-500/10 px-5 py-4">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/20 border border-amber-400/30">
                                                    <?= mtq_icon('info', 'h-5 w-5 text-amber-300') ?>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm font-bold text-amber-100">Petunjuk Input Nilai</p>
                                                    <div class="mt-2 grid gap-2 text-xs text-amber-50">
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                                                            <span>Bilangan desimal menggunakan <span class="font-bold text-amber-100">titik (.)</span> - contoh: <span class="font-mono bg-slate-800 px-1.5 py-0.5 rounded text-amber-200">87.50</span></span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                                            <span>Nilai minimum adalah <span class="font-bold text-emerald-200">1</span> - kolom kosong atau 0 tidak dihitung</span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-cyan-400"></span>
                                                            <span>Range nilai: <span class="font-bold text-cyan-200">1 - 100</span></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Poin Penilaian - MORE PROMINENT -->
                                            <div class="rounded-2xl border-2 border-cyan-400/40 bg-gradient-to-br from-slate-900 via-slate-900/95 to-slate-950 p-6 shadow-[0_0_40px_-15px_rgba(34,211,238,0.25)] overflow-hidden relative">
                                                <!-- Glow effect -->
                                                <div class="pointer-events-none absolute -top-20 -right-20 h-40 w-40 rounded-full bg-cyan-500/10 blur-2xl"></div>
                                                <div class="pointer-events-none absolute -bottom-20 -left-20 h-40 w-40 rounded-full bg-emerald-500/10 blur-2xl"></div>

                                                <div class="relative">
                                                    <div class="flex items-center gap-3 mb-5">
                                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-400/30 bg-cyan-500/20">
                                                            <?= mtq_icon('spark', 'h-5 w-5 text-cyan-300') ?>
                                                        </div>
                                                        <div>
                                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-300">Input Nilai</p>
                                                            <h4 class="text-lg font-bold text-white">Poin Penilaian <?= e($judgeName) ?></h4>
                                                        </div>
                                                        <div class="ml-auto rounded-full border border-cyan-400/30 bg-cyan-400/10 px-4 py-1.5 text-xs font-bold text-cyan-200">
                                                            <?= e(count($criteria)) ?> Poin
                                                        </div>
                                                    </div>

                                                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                                        <?php
                                                        // Build lookup from judge ID to judge name for consistent access
                                                        // Note: Using judge ID as the primary lookup key since it's numeric and reliable
                                                        $judgeIdToNameMap = [];
                                                        foreach ($judgeNames as $idx => $name) {
                                                            $id = $judgeIds[$idx] ?? null;
                                                            if ($id !== null) {
                                                                $judgeIdToNameMap[$id] = $name;
                                                            }
                                                        }
                                                        $judgeId = $judgeIds[$index] ?? '';
                                                        $getScoreValue = function(string $key) use ($judgeId, $judgeName, $participantScoreDraft, $judgeIdToNameMap): string {
                                                            // First check old() values (from failed validation) - keyed by judge ID
                                                            $oldScores = old('scores', []);
                                                            $oldValue = data_get($oldScores, $judgeId.'.'.str_replace('.', '\\.', $key));
                                                            if ($oldValue !== null && $oldValue !== '') {
                                                                return e($oldValue);
                                                            }
                                                            // Then check draft - use judge name from mapping, then direct array access
                                                            // to avoid issues with data_get and special characters in names
                                                            $draftJudgeName = $judgeIdToNameMap[$judgeId] ?? $judgeName;
                                                            $draftScores = $participantScoreDraft[$draftJudgeName]['scores'] ?? [];
                                                            return e($draftScores[$key] ?? '');
                                                        };
                                                        $getRemarksValue = function() use ($judgeId, $judgeName, $participantScoreDraft, $judgeIdToNameMap): string {
                                                            // First check old() values - keyed by judge ID
                                                            $oldRemarks = old('remarks', []);
                                                            $oldValue = data_get($oldRemarks, $judgeId);
                                                            if ($oldValue !== null) {
                                                                return e($oldValue);
                                                            }
                                                            // Then check draft - use direct array access
                                                            $draftJudgeName = $judgeIdToNameMap[$judgeId] ?? $judgeName;
                                                            return e($participantScoreDraft[$draftJudgeName]['remarks'] ?? '');
                                                        };
                                                        ?>
                                                        <?php foreach ($criteria as $key => $label): ?>
                                                            <div class="group relative rounded-2xl border border-cyan-400/25 bg-gradient-to-br from-slate-800/80 to-slate-900/80 p-4 shadow-lg transition-all hover:border-cyan-400/50 hover:shadow-cyan-400/10 hover:scale-[1.02]">
                                                                <label class="mb-3 block text-center text-xs font-bold uppercase tracking-[0.18em] text-cyan-200">
                                                                    <?= e($label) ?>
                                                                </label>
                                                                <input
                                                                    name="scores[<?= e($judgeIds[$index] ?? '') ?>][<?= e($key) ?>]"
                                                                    data-score-label="<?= e($label) ?>"
                                                                    type="number"
                                                                    min="1"
                                                                    max="100"
                                                                    step="0.01"
                                                                    value="<?= $getScoreValue($key) ?>"
                                                                    class="w-full rounded-xl border-2 border-cyan-400/40 bg-gradient-to-b from-slate-900 to-slate-800 px-3 py-4 text-center text-2xl font-black text-cyan-100 outline-none transition-all focus:border-cyan-300 focus:ring-4 focus:ring-cyan-400/30 focus:bg-slate-800 placeholder:text-slate-600"
                                                                    placeholder="0">
                                                                <div class="mt-2 flex items-center justify-center gap-1 text-[10px] text-slate-500">
                                                                    <span>Min: 1</span>
                                                                    <span class="text-cyan-400/50">|</span>
                                                                    <span>Max: 100</span>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Catatan -->
                                            <div class="rounded-2xl border border-slate-700/80 bg-slate-900/50 p-4">
                                                <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-300">
                                                    <?= mtq_icon('message', 'h-4 w-4') ?>
                                                    Catatan <?= e($judgeName) ?>
                                                    <span class="ml-auto text-xs font-normal text-slate-500">(Opsional)</span>
                                                </label>
                                                <textarea
                                                    name="remarks[<?= e($judgeIds[$index] ?? '') ?>]"
                                                    rows="2"
                                                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20"
                                                    placeholder="Catatan performa atau keputusan teknis..."><?= $getRemarksValue() ?></textarea>
                                            </div>

                                            <!-- Navigation Buttons -->
                                            <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-700/80 bg-slate-900/50 px-5 py-4">
                                                <button type="button"
                                                    class="flex items-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-5 py-3 text-sm font-semibold text-slate-300 transition hover:border-cyan-400/50 hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                    x-on:click="previousJudge()"
                                                    x-bind:disabled="activeJudgeIndex === 0">
                                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                                    Sebelumnya
                                                </button>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-slate-500">Hakim</span>
                                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-cyan-500/20 border border-cyan-400/30 text-xs font-bold text-cyan-200" x-text="activeJudgeIndex + 1"></span>
                                                    <span class="text-xs text-slate-500">dari</span>
                                                    <span class="text-xs font-bold text-slate-300" x-text="maxJudgeIndex() + 1"></span>
                                                </div>
                                                <button type="button"
                                                    class="flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition"
                                                    x-on:click="activeJudgeIndex === maxJudgeIndex() ? openPreview() : nextJudge()"
                                                    :class="activeJudgeIndex === maxJudgeIndex() ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/30 hover:from-emerald-400 hover:to-emerald-500' : 'bg-gradient-to-r from-cyan-500 to-sky-500 text-white shadow-lg shadow-cyan-500/30 hover:from-cyan-400 hover:to-sky-400'">
                                                    <span x-text="activeJudgeIndex === maxJudgeIndex() ? 'Pratinjau & Simpan' : 'Hakim Berikutnya'"></span>
                                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                                </button>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>

                                    <!-- Footer Submit Bar -->
                                    <div class="sticky bottom-4 rounded-2xl border-2 border-emerald-400/40 bg-gradient-to-r from-slate-900 via-slate-900/95 to-slate-900 px-6 py-4 shadow-[0_0_40px_-15px_rgba(52,211,153,0.25)]">
                                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                            <div class="text-center sm:text-left">
                                                <p class="text-sm font-semibold text-white">Simpan Nilai</p>
                                                <p class="mt-0.5 text-xs text-slate-400">Pastikan seluruh panel sudah terisi sebelum menyimpan</p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <div class="rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-2 text-center">
                                                    <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500">Status</p>
                                                    <p class="mt-0.5 text-sm font-bold text-cyan-300" x-text="completionSummary()"></p>
                                                </div>
                                                <button type="button" class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/30 transition hover:from-emerald-400 hover:to-emerald-500 hover:shadow-emerald-500/50 hover:scale-105 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
                                                    x-on:click="openPreview()"
                                                    :disabled="isSubmitting">
                                                    <?= mtq_icon('check-circle', 'h-5 w-5') ?>
                                                    <span x-show="!isSubmitting">Pratinjau & Simpan</span>
                                                    <span x-show="isSubmitting">Menyimpan...</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Preview -->
                                    <div x-show="previewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 backdrop-blur-sm px-2 sm:px-4 py-4 sm:py-6"
                                        x-on:keydown.escape.window="closePreview()">
                                        <div class="absolute inset-0" x-on:click="closePreview()"></div>
                                        <div class="relative z-10 max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-[1.75rem] border border-cyan-400/16 bg-slate-950 shadow-[0_28px_90px_-40px_rgba(34,211,238,0.45)]">
                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b border-slate-800 px-4 sm:px-6 py-4 sm:py-5">
                                                <div>
                                                    <p class="section-kicker">Pratinjau Nilai</p>
                                                    <h3 class="mt-2 text-xl sm:text-2xl font-bold text-white">Cek kembali sebelum disimpan</h3>
                                                    <p class="mt-2 text-sm text-slate-300" x-text="selectedParticipantLabel()"></p>
                                                </div>
                                                <button type="button" class="secondary-button px-4 py-2 shrink-0" x-on:click="closePreview()">
                                                    <?= mtq_icon('x', 'h-4 w-4') ?>
                                                    Tutup
                                                </button>
                                            </div>

                                            <div class="max-h-[60vh] overflow-y-auto px-4 sm:px-6 py-4 sm:py-5">

                                                <!-- Warning: Score Discrepancies -->
                                                <template x-if="hasDiscrepancies()">
                                                    <div class="mb-6 rounded-2xl border-2 border-amber-400/40 bg-gradient-to-br from-amber-500/20 to-orange-500/15 p-5 shadow-[0_8px_30px_-12px_rgba(251,191,36,0.25)]">
                                                        <div class="flex items-start gap-4">
                                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-amber-400/30 bg-amber-500/20">
                                                                <?= mtq_icon('alert-triangle', 'h-6 w-6 text-amber-300') ?>
                                                            </div>
                                                            <div class="flex-1">
                                                                <h4 class="text-lg font-bold text-amber-100">Peringatan: Ada Selisih Nilai yang Besar</h4>
                                                                <p class="mt-1 text-sm text-amber-200/80">Disarankan untuk mengecek ulang sebelum menyimpan nilai.</p>

                                                                <div class="mt-4 space-y-3">
                                                                    <template x-for="(disc, idx) in detectScoreDiscrepancies()" :key="disc.pointLabel">
                                                                        <div class="rounded-xl border border-amber-400/25 bg-slate-900/60 p-3">
                                                                            <div class="flex items-center justify-between gap-3">
                                                                                <div>
                                                                                    <p class="text-sm font-semibold text-amber-100" x-text="disc.pointLabel"></p>
                                                                                    <p class="mt-1 text-xs text-amber-200/70">
                                                                                        Selisih: <span class="font-bold text-amber-100" x-text="disc.interval"></span>
                                                                                    </p>
                                                                                </div>
                                                                                <div class="flex items-center gap-2">
                                                                                    <!-- Min Value -->
                                                                                    <div class="text-center rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-1.5">
                                                                                        <p class="text-[10px] uppercase tracking-[0.1em] text-slate-400">Terendah</p>
                                                                                        <p class="text-sm font-bold text-slate-300" x-text="disc.min.displayValue"></p>
                                                                                        <p class="text-[10px] text-slate-500 truncate max-w-[80px]" x-text="disc.min.judgeName"></p>
                                                                                    </div>
                                                                                    <div class="text-amber-400">→</div>
                                                                                    <!-- Max Value -->
                                                                                    <div class="text-center rounded-lg border border-amber-400/30 bg-amber-500/10 px-3 py-1.5">
                                                                                        <p class="text-[10px] uppercase tracking-[0.1em] text-amber-400">Tertinggi</p>
                                                                                        <p class="text-sm font-bold text-amber-100" x-text="disc.max.displayValue"></p>
                                                                                        <p class="text-[10px] text-amber-200/60 truncate max-w-[80px]" x-text="disc.max.judgeName"></p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- Summary: Total Nilai -->
                                                <div class="mb-6 rounded-2xl border-2 border-cyan-400/30 bg-gradient-to-br from-cyan-500/15 to-sky-500/10 p-4 sm:p-5 shadow-[0_12px_40px_-20px_rgba(34,211,238,0.3)]">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Total Nilai</p>
                                                            <p class="mt-1 text-xs text-slate-400">Jumlah rata-rata poin per hakim</p>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="text-3xl sm:text-4xl font-black text-white" x-text="calculateTotalScore()"></p>
                                                            <p class="mt-1 text-sm text-cyan-200" x-text="`Total semua poin`"></p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Rincian Poin per Hakim -->
                                                <h4 class="mb-3 text-sm font-semibold text-slate-300">Rincian Nilai per Hakim</h4>
                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <template x-for="(judgeData, judgeIdx) in previewData" :key="judgeData.name">
                                                        <section class="rounded-[1.35rem] border border-slate-700/50 bg-slate-900/40 p-3 sm:p-4">
                                                            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                                                <div>
                                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Hakim</p>
                                                                    <h4 class="mt-1 text-sm sm:text-base font-bold text-white truncate" x-text="judgeData.name"></h4>
                                                                </div>
                                                            </div>
                                                            <div class="grid gap-2">
                                                                <template x-for="(item, scoreIdx) in judgeData.scores" :key="item.label">
                                                                    <div class="flex items-center justify-between gap-3 rounded-lg border px-3 py-2 transition-all"
                                                                         :class="hasDiscrepancyForPoint(item.label) ? 'border-amber-400/40 bg-amber-500/10' : 'border-slate-800 bg-slate-950/60'">
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="text-xs sm:text-sm text-slate-300 truncate" x-text="item.label"></span>
                                                                            <template x-if="hasDiscrepancyForPoint(item.label)">
                                                                                <span class="inline-flex items-center gap-1 rounded-full border border-amber-400/30 bg-amber-500/20 px-1.5 py-0.5 text-[10px] font-semibold text-amber-200">
                                                                                    <?= mtq_icon('alert-triangle', 'h-3 w-3') ?>
                                                                                    Selisih
                                                                                </span>
                                                                            </template>
                                                                        </div>
                                                                        <span class="text-sm font-bold shrink-0"
                                                                              :class="hasDiscrepancyForPoint(item.label) ? 'text-amber-100' : 'text-white'"
                                                                              x-text="item.value"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </section>
                                                    </template>
                                                </div>

                                                <!-- Total per Poin (Rata-rata per Poin) -->
                                                <h4 class="mb-3 text-sm font-semibold text-slate-300">Total per Poin (Rata-rata Hakim)</h4>
                                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                                    <template x-for="item in previewPointTotals" :key="item.label">
                                                        <div class="rounded-xl border px-3 sm:px-4 py-2 sm:py-3 transition-all"
                                                             :class="hasDiscrepancyForPoint(item.label) ? 'border-amber-400/40 bg-amber-500/10' : 'border-emerald-400/20 bg-emerald-500/10'">
                                                            <div class="flex items-center justify-between gap-2">
                                                                <div class="flex items-center gap-2">
                                                                    <template x-if="hasDiscrepancyForPoint(item.label)">
                                                                        <span class="inline-flex items-center rounded-full border border-amber-400/30 bg-amber-500/20 p-0.5">
                                                                            <?= mtq_icon('alert-triangle', 'h-3 w-3 text-amber-300') ?>
                                                                        </span>
                                                                    </template>
                                                                    <span class="text-xs truncate" :class="hasDiscrepancyForPoint(item.label) ? 'text-amber-200' : 'text-emerald-200'" x-text="item.label"></span>
                                                                </div>
                                                                <span class="text-base sm:text-lg font-bold shrink-0" :class="hasDiscrepancyForPoint(item.label) ? 'text-amber-100' : 'text-emerald-300'" x-text="item.average"></span>
                                                            </div>
                                                            <p class="mt-1 text-[10px]" :class="hasDiscrepancyForPoint(item.label) ? 'text-amber-200/60' : 'text-emerald-200/60'" x-text="`(Jumlah: ${item.sum} / ${item.count} hakim)`"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-t border-slate-800 px-4 sm:px-6 py-4 sm:py-5">
                                                <p class="text-sm text-slate-300 order-2 sm:order-1">Jika semua sudah benar, lanjutkan simpan ke sistem.</p>
                                                <div class="flex flex-col sm:flex-row gap-3 order-1 sm:order-2">
                                                    <button type="button" class="secondary-button px-4 sm:px-5 py-3 w-full sm:w-auto" x-on:click="closePreview()">
                                                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                                        Edit Lagi
                                                    </button>
                                                    <button type="button" class="primary-button px-4 sm:px-5 py-3 w-full sm:w-auto" x-on:click="submitConfirmed()">
                                                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                        Ya, Simpan Nilai
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </fieldset>

                                    <!-- Cancel Edit Mode Button -->
                                    <?php if ($participantHasScores): ?>
                                    <div class="mt-4 flex justify-center" x-show="$store.scoringEdit.enabled">
                                        <button type="button"
                                                @click="$store.scoringEdit.enabled = false"
                                                class="rounded-full border border-red-400/30 bg-red-500/10 px-6 py-3 text-sm font-semibold text-red-200 transition hover:bg-red-500/20 hover:border-red-400/50">
                                            <?= mtq_icon('x', 'h-4 w-4 inline') ?>
                                            Batal Edit
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="glass-card rounded-[2rem] p-5">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                                <div>
                                    <p class="section-kicker">Riwayat Nilai</p>
                                    <p class="mt-1 text-sm text-slate-300">
                                        <?php if ($selectedParticipant): ?>
                                            Riwayat untuk <?= e($selectedParticipant->name) ?> · Lot <?= e($selectedParticipant->lot_number ?: '-') ?>
                                        <?php else: ?>
                                            Skor terbaru untuk peserta yang sedang dipilih.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <?php if ($recentScores->isEmpty()): ?>
                                    <div class="data-card text-sm text-slate-300">Belum ada nilai yang tercatat untuk peserta ini.</div>
                                <?php endif; ?>
                                <?php foreach ($recentScores->groupBy(fn ($score) => $score->judging_round ?: 'Tanpa Babak') as $roundLabel => $roundScores): ?>
                                    <?php
                                        // Get the first score entry for this round (new format: 1 row with all judges)
                                        $firstScore = $roundScores->first();
                                        $isNewFormat = $firstScore && $firstScore->scores && is_array($firstScore->scores);
                                        $allJudgeScores = $firstScore?->getAllJudgeScores() ?? [];
                                        $judgeCount = count($allJudgeScores);

                                        // Calculate point totals for new format
                                        $pointTotals = $firstScore?->getPointTotals() ?? [];
                                        $criteriaKeys = $isNewFormat && !empty($allJudgeScores) ? array_keys(reset($allJudgeScores)['scores'] ?? []) : [];
                                    ?>
                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/45 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                            <div>
                                                <p class="font-semibold text-white"><?= e($roundLabel) ?></p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    <?= e($selectedParticipant?->name ?? '-') ?> · Lot <?= e($selectedParticipant?->lot_number ?: '-') ?>
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <div class="rounded-full border border-emerald-400/18 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-100">
                                                    Total: <?= e(number_format((float) ($firstScore?->average_score ?? 0), 2)) ?>
                                                </div>
                                                <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                                    <?= e($judgeCount) ?> hakim
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($isNewFormat && !empty($pointTotals)): ?>
                                            <!-- Total Per Poin (Jumlah Semua Hakim) -->
                                            <div class="mb-4 rounded-xl border border-emerald-400/20 bg-emerald-500/5 p-3">
                                                <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-300">Total per Poin (Jumlah Semua Hakim)</p>
                                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                                    <?php foreach ($pointTotals as $pointKey => $pointTotal): ?>
                                                        <?php if ((float) $pointTotal > 0): ?>
                                                            <div class="flex items-center justify-between rounded-lg border border-emerald-400/15 bg-emerald-500/5 px-3 py-2">
                                                                <span class="text-xs text-slate-300">
                                                                    <?= e(str_replace('_', ' ', ucfirst((string) $pointKey))) ?>
                                                                </span>
                                                                <span class="text-sm font-bold text-emerald-200">
                                                                    <?= e(number_format((float) $pointTotal, 2)) ?>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Rincian Per Hakim -->
                                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200">Rincian Nilai per Hakim</p>
                                        <div class="space-y-3">
                                            <?php foreach ($allJudgeScores as $judgeName => $judgeData): ?>
                                                <div class="rounded-[1rem] border border-slate-700/50 bg-slate-900/30 p-3">
                                                    <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                                                        <div>
                                                            <p class="text-sm font-semibold text-white"><?= e($judgeName) ?></p>
                                                            <p class="mt-0.5 text-[10px] text-slate-500"><?= e(optional($firstScore->submitted_at)->format('d M Y H:i')) ?></p>
                                                        </div>
                                                        <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                                            Nilai: <?= e(number_format((float) ($judgeData['score'] ?? 0), 2)) ?>
                                                        </div>
                                                    </div>
                                                    <?php if (! empty($judgeData['scores'] ?? [])): ?>
                                                        <div class="grid gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
                                                            <?php foreach ($judgeData['scores'] as $pointKey => $pointValue): ?>
                                                                <?php if ((float) $pointValue > 0): ?>
                                                                    <div class="flex items-center justify-between rounded-md border border-slate-800/50 bg-slate-950/50 px-2.5 py-1.5">
                                                                        <span class="text-[10px] text-slate-400 truncate mr-2">
                                                                            <?= e(str_replace('_', ' ', ucfirst((string) $pointKey))) ?>
                                                                        </span>
                                                                        <span class="text-xs font-semibold text-slate-200">
                                                                            <?= e(number_format((float) $pointValue, 2)) ?>
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (! empty($judgeData['remarks'] ?? '')): ?>
                                                        <div class="mt-2 rounded-md border border-slate-700/30 bg-slate-900/30 px-3 py-2 text-xs text-slate-400 italic">
                                                            <?= e($judgeData['remarks']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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
    <script>
        // Alpine store for scoring edit mode state
        document.addEventListener('alpine:init', () => {
            Alpine.store('scoringEdit', {
                enabled: false
            });
        });

        function scoringWorkflow(initialState) {
            const step = Number(initialState.initialStep ?? 1);

            return {
                mobileNavOpen: false,
                currentStep: Math.max(1, Math.min(3, step || 1)),
                hasCategoryReady: Boolean(initialState.categoryReady),
                hasSetupReady: Boolean(initialState.setupReady),
                hasSetupEditable: Boolean(initialState.setupEditable),
                hasParticipantReady: Boolean(initialState.participantReady),
                isMfqMode: Boolean(initialState.mfqMode),
                roundModalOpen: false,
                selectedCategoryForRound: null,
                stepLabel(stepNumber) {
                    return {
                        1: 'Step 1',
                        2: 'Step 2',
                        3: 'Step 3',
                    }[Number(stepNumber)] ?? 'Step 1';
                },
                goToStep(stepNumber) {
                    const nextStep = Math.max(1, Math.min(3, Number(stepNumber) || 1));

                    if (this.isMfqMode && nextStep > 1) {
                        return;
                    }

                    if (nextStep === 2 && !this.hasSetupEditable && this.hasSetupReady) {
                        this.currentStep = 3;

                        const target = document.getElementById('step-3');
                        if (target instanceof HTMLElement) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }

                        return;
                    }

                    if (nextStep === 2 && !this.hasCategoryReady) {
                        return;
                    }

                    if (nextStep === 3 && (!this.hasCategoryReady || !this.hasSetupReady)) {
                        return;
                    }

                    this.currentStep = nextStep;

                    const target = document.getElementById(`step-${nextStep}`);
                    if (target instanceof HTMLElement) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                },
                openRoundModal(categoryId, branch, name) {
                    this.selectedCategoryForRound = { id: categoryId, branch, name };
                    this.roundModalOpen = true;
                },
                confirmRoundAndProceed(roundLabel) {
                    const cat = this.selectedCategoryForRound;
                    if (!cat) return;

                    const params = new URLSearchParams({
                        competition_category_id: cat.id,
                        branch: cat.branch,
                        step: '2',
                        judging_round: roundLabel,
                    });

                    this.roundModalOpen = false;
                    this.selectedCategoryForRound = null;
                    window.location.href = `<?= e(route('scoring')) ?>?${params.toString()}`;
                },
            };
        }

        // Loading overlay helper functions (inline version)
        function ensureScoringOverlay() {
            let overlay = document.getElementById('mtq-submit-loading-overlay');
            if (overlay) return overlay;

            overlay = document.createElement('div');
            overlay.id = 'mtq-submit-loading-overlay';
            overlay.className = 'mtq-submit-overlay';
            overlay.setAttribute('aria-hidden', 'true');
            overlay.innerHTML = `
                <div class="mtq-submit-overlay__panel" role="status" aria-live="polite">
                    <div class="mtq-submit-overlay__spinner" aria-hidden="true"></div>
                    <div class="mtq-submit-overlay__copy">
                        <p class="mtq-submit-overlay__title" data-loading-title>Menyimpan data</p>
                        <p class="mtq-submit-overlay__text" data-loading-text>Mohon tunggu, data sedang diproses.</p>
                        <div class="mtq-submit-overlay__progress">
                            <div class="mtq-submit-overlay__progress-row">
                                <span class="mtq-submit-overlay__progress-label">Progres</span>
                                <span class="mtq-submit-overlay__progress-percent" data-loading-percent>0%</span>
                            </div>
                            <div class="mtq-submit-overlay__progress-track" aria-hidden="true">
                                <div class="mtq-submit-overlay__progress-fill" data-loading-fill></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            return overlay;
        }

        function showLoadingOverlay(message) {
            const overlay = ensureScoringOverlay();
            const isUpload = Boolean(message && String(message).toLowerCase().includes('unggah'));
            const titleNode = overlay.querySelector('[data-loading-title]');
            const textNode = overlay.querySelector('[data-loading-text]');
            if (titleNode) titleNode.textContent = isUpload ? 'Mengunggah berkas' : 'Menyimpan data';
            if (textNode) textNode.textContent = message || (isUpload ? 'Mohon tunggu, berkas sedang diunggah.' : 'Mohon tunggu, data sedang diproses.');
            overlay.classList.add('is-visible');
            document.body.classList.add('mtq-loading-active');
            const fillNode = overlay.querySelector('[data-loading-fill]');
            const percentNode = overlay.querySelector('[data-loading-percent]');
            if (fillNode) fillNode.style.width = '0%';
            if (percentNode) percentNode.textContent = '0%';
        }

        function hideLoadingOverlay() {
            const overlay = document.getElementById('mtq-submit-loading-overlay');
            if (overlay) overlay.classList.remove('is-visible');
            document.body.classList.remove('mtq-loading-active');
        }

        function participantPicker(initialState) {
            return {
                participants: initialState.participants ?? [],
                districts: initialState.districts ?? [],
                selectedId: String(initialState.selectedId ?? ''),
                selectedDistrictId: initialState.selectedDistrictId ?? null,
                search: '',
                scoreFilterMode: 'all',
                dropdownOpen: false,
                highlightedIndex: 0,
                init() {
                    // Keep search empty on page load - user must search explicitly
                    // Only sync selectedId from URL if needed
                    const urlParams = new URLSearchParams(window.location.search);
                    const urlParticipantId = urlParams.get('participant_id');
                    if (urlParticipantId) {
                        this.selectedId = String(urlParticipantId);
                    }
                    // Search stays empty until user types
                },
                get selectedParticipant() {
                    return this.participants.find((participant) => String(participant.id) === String(this.selectedId)) ?? null;
                },
                get selectedDistrict() {
                    return this.districts.find((d) => d.id === this.selectedDistrictId) ?? null;
                },
                get isMsq() {
                    return this.districts.length > 0;
                },
                get filteredParticipants() {
                    const keyword = this.search.trim().toLowerCase();

                    return this.participants.filter((participant) => {
                        const hasScores = Number(participant.score_count || 0) > 0;
                        if (this.scoreFilterMode === 'scored' && !hasScores) {
                            return false;
                        }

                        if (this.scoreFilterMode === 'unscored' && hasScores) {
                            return false;
                        }

                        const haystack = [
                            participant.name,
                            participant.lot_number,
                            participant.registration_number,
                            participant.district,
                            participant.branch,
                            participant.category,
                            participant.institution,
                        ].join(' ').toLowerCase();

                        return keyword === '' || haystack.includes(keyword);
                    }).sort((a, b) => {
                        const aScored = Number(a.score_count || 0) > 0 ? 1 : 0;
                        const bScored = Number(b.score_count || 0) > 0 ? 1 : 0;

                        if (aScored !== bScored) {
                            return aScored - bScored;
                        }

                        const aLot = String(a.lot_number || '').localeCompare(String(b.lot_number || ''), 'id', { numeric: true, sensitivity: 'base' });
                        if (aLot !== 0) {
                            return aLot;
                        }

                        return String(a.name || '').localeCompare(String(b.name || ''), 'id', { sensitivity: 'base' });
                    }).slice(0, 12);
                },
                get filteredDistricts() {
                    const keyword = this.search.trim().toLowerCase();

                    return this.districts.filter((district) => {
                        const hasScores = Number(district.score_count || 0) > 0;
                        if (this.scoreFilterMode === 'scored' && !hasScores) {
                            return false;
                        }

                        if (this.scoreFilterMode === 'unscored' && hasScores) {
                            return false;
                        }

                        const haystack = [
                            district.name,
                            district.lot_number,
                        ].join(' ').toLowerCase();

                        return keyword === '' || haystack.includes(keyword);
                    }).sort((a, b) => {
                        const aScored = Number(a.score_count || 0) > 0 ? 1 : 0;
                        const bScored = Number(b.score_count || 0) > 0 ? 1 : 0;

                        if (aScored !== bScored) {
                            return aScored - bScored;
                        }

                        const aLot = String(a.lot_number || '').localeCompare(String(b.lot_number || ''), 'id', { numeric: true, sensitivity: 'base' });
                        return aLot;
                    }).slice(0, 12);
                },
                selectParticipant(participant) {
                    // Show confirmation modal first
                    this.showBigScreenConfirmation(participant);
                },
                selectDistrict(district) {
                    // For MSQ: select district and show first participant of that district
                    this.selectedDistrictId = district.id;
                    // Match using district_id (numeric)
                    const districtNumericId = district.district_id;
                    const firstParticipant = this.participants.find((p) => Number(p.district_id) === Number(districtNumericId));
                    if (firstParticipant) {
                        this.selectedId = String(firstParticipant.id);
                        this.showBigScreenConfirmation(firstParticipant);
                    } else {
                        // No participant found for this district
                        Swal.fire({
                            title: 'Tidak Ada Peserta',
                            text: `Tidak ada peserta terverifikasi untuk ${district.name}`,
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                async showBigScreenConfirmation(participant) {
                    // Show SweetAlert confirmation
                    const result = await Swal.fire({
                        title: 'Peserta yang akan Tampil?',
                        html: `<div class="text-left">
                            <p class="mb-2">Peserta ini akan ditampilkan di Big Screen:</p>
                            <div class="rounded-xl border border-slate-600 bg-slate-800 p-4">
                                <p class="text-lg font-bold text-white">${participant.name}</p>
                                <p class="text-sm text-cyan-300">Lot: ${participant.lot_number || '-'}</p>
                                <p class="text-sm text-slate-400">${participant.district || participant.district_name || '-'}</p>
                            </div>
                        </div>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Tampilkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#22c55e',
                        cancelButtonColor: '#64748b',
                        background: '#1e293b',
                        color: '#f1f5f9',
                    });

                    if (!result.isConfirmed) {
                        return; // User cancelled
                    }

                    // Show loading using existing app loading overlay
                    showLoadingOverlay(`Mempersiapkan form penilaian untuk ${participant.name}...`);

                    // Process participant selection
                    this.processParticipantSelection(participant);
                },
                async processParticipantSelection(participant) {
                    this.selectedId = String(participant.id);
                    this.search = participant.name;
                    this.dropdownOpen = false;
                    this.highlightedIndex = 0;
                    window.dispatchEvent(new CustomEvent('scoring-participant-selected', {
                        detail: {
                            id: String(participant.id),
                            name: participant.name ?? '',
                            lot_number: participant.lot_number ?? '',
                            judging_round: participant.judging_round ?? '',
                            district: participant.district ?? '',
                        },
                    }));

                    // Update global state for form submission
                    window.currentParticipantId = participant.id;
                    window.dispatchEvent(new CustomEvent('current-participant-changed', { detail: { id: participant.id } }));

                    // Update Big Screen
                    await this.updateBigScreen(participant);

                    // Hide loading overlay and navigate
                    hideLoadingOverlay();
                    this.goToSelected();
                },
                async updateBigScreen(participant) {
                    try {
                        const categoryId = <?= e($selectedCategory?->id ?? 0) ?>;
                        if (!categoryId || !participant) return;

                        await fetch('/api/big-screen/set-participant', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                            body: JSON.stringify({
                                participant_id: participant.id,
                                category_id: categoryId,
                            })
                        });
                    } catch (error) {
                        console.error('Failed to update Big Screen:', error);
                    }
                },
                selectHighlighted() {
                    if (!this.dropdownOpen) {
                        this.dropdownOpen = true;
                        return;
                    }

                    const participant = this.filteredParticipants[this.highlightedIndex] ?? null;
                    if (participant) {
                        this.selectParticipant(participant);
                    }
                },
                highlightNext() {
                    this.dropdownOpen = true;
                    if (this.filteredParticipants.length === 0) {
                        this.highlightedIndex = 0;
                        return;
                    }

                    this.highlightedIndex = (this.highlightedIndex + 1) % this.filteredParticipants.length;
                },
                highlightPrevious() {
                    this.dropdownOpen = true;
                    if (this.filteredParticipants.length === 0) {
                        this.highlightedIndex = 0;
                        return;
                    }

                    this.highlightedIndex = (this.highlightedIndex - 1 + this.filteredParticipants.length) % this.filteredParticipants.length;
                },
                resetSearch() {
                    this.search = '';
                    this.selectedId = '';
                    this.scoreFilterMode = 'all';
                    this.dropdownOpen = false;
                    this.highlightedIndex = 0;
                },
                goToSelected() {
                    if (!this.selectedParticipant) {
                        return;
                    }

                    const targetUrl = new URL(this.selectedParticipant.url, window.location.origin);
                    targetUrl.hash = 'form-penilaian';
                    targetUrl.searchParams.set('step', '3');
                    // Navigate to the new URL to get fresh participant data from server
                    window.location.href = targetUrl.toString();
                },
                scrollToFormPanel() {
                    const formSection = document.getElementById('form-penilaian');
                    if (!(formSection instanceof HTMLElement)) {
                        return;
                    }

                    smoothScrollToElement(formSection, 20, 700);
                },
            };
        }

        function smoothScrollToElement(element, offset = 24, duration = 650) {
            const targetTop = Math.max(0, window.scrollY + element.getBoundingClientRect().top - offset);
            const startTop = window.scrollY;
            const distance = targetTop - startTop;

            if (Math.abs(distance) < 2) {
                return;
            }

            const startTime = performance.now();

            const easeInOutCubic = (t) => (t < 0.5
                ? 4 * t * t * t
                : 1 - Math.pow(-2 * t + 2, 3) / 2);

            const step = (currentTime) => {
                const elapsed = Math.min(1, (currentTime - startTime) / duration);
                const eased = easeInOutCubic(elapsed);
                window.scrollTo(0, startTop + distance * eased);

                if (elapsed < 1) {
                    window.requestAnimationFrame(step);
                }
            };

            window.requestAnimationFrame(step);
        }

        function scoringRoundSetupForm(initialState) {
            return {
                rounds: initialState.rounds ?? {},
                activeRound: initialState.activeRound ?? 'penyisihan',
                availableJudges: initialState.availableJudges ?? [],
                categoryJudgeIds: initialState.categoryJudgeIds ?? [],
                selectedJudgingRound: initialState.selectedJudgingRound ?? 'Penyisihan',
                judgeModalOpen: null,
                judgeSearchQuery: '',
                init() {
                    // Ensure activeRound matches the server-side selected judging round
                    const serverRound = this.selectedJudgingRound.toLowerCase();
                    if (serverRound === 'final' || serverRound === 'penyisihan') {
                        this.activeRound = serverRound;
                    }

                    Object.keys(this.rounds).forEach((roundKey) => {
                        const round = this.rounds[roundKey] ?? {};

                        // Initialize judgeNames from PHP data (judge_names contains actual names)
                        round.judgeNames = Array.isArray(round.judge_names) && round.judge_names.length
                            ? round.judge_names
                            : [''];

                        round.scoringPoints = Array.isArray(round.scoring_points ?? round.scoringPoints) && (round.scoring_points ?? round.scoringPoints).length
                            ? (round.scoring_points ?? round.scoringPoints)
                            : [''];
                        round.judgeCount = Math.max(1, Math.min(15, round.judgeNames.length || 1));

                        // Initialize hidden input sync properties
                        round._judgeNamesText = this._getJudgeNamesText(roundKey);
                        round._scoringPointsText = this._getScoringPointsText(roundKey);
                        round._judgeIdsJson = JSON.stringify(this._getJudgeIds(roundKey));

                        this.rounds[roundKey] = round;
                        this.rounds[roundKey].scoringPoints = this.rounds[roundKey].scoringPoints
                            .map((value) => String(value ?? '').trim())
                            .filter(Boolean);
                        if (!this.rounds[roundKey].scoringPoints.length) {
                            this.rounds[roundKey].scoringPoints = [''];
                        }
                        // Update hidden inputs after cleaning
                        this.rounds[roundKey]._judgeNamesText = this._getJudgeNamesText(roundKey);
                        this.rounds[roundKey]._scoringPointsText = this._getScoringPointsText(roundKey);
                    });
                    if (!Object.prototype.hasOwnProperty.call(this.rounds, this.activeRound)) {
                        this.activeRound = Object.keys(this.rounds)[0] ?? 'penyisihan';
                    }

                    // Initial sync of all hidden inputs
                    Object.keys(this.rounds).forEach(roundKey => {
                        this._syncHiddenInputs(roundKey);
                    });
                },
                // Computed property for active round label
                get activeRoundLabel() {
                    return this.activeRound === 'final' ? 'Final' : 'Penyisihan';
                },
                // Watch for changes and sync hidden inputs
                watch: {
                    'rounds': {
                        handler(newRounds) {
                            Object.keys(newRounds).forEach(roundKey => {
                                this._syncHiddenInputs(roundKey);
                            });
                        },
                        deep: true,
                    },
                },
                // Helper methods for hidden input sync
                _getJudgeNamesText(roundKey) {
                    return (this.rounds[roundKey]?.judgeNames ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean)
                        .join('\n');
                },
                _getScoringPointsText(roundKey) {
                    return (this.rounds[roundKey]?.scoringPoints ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean)
                        .join('\n');
                },
                _getJudgeIds(roundKey) {
                    const judgeNames = (this.rounds[roundKey]?.judgeNames ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean);
                    const savedIds = this.rounds[roundKey]?.judge_ids ?? [];
                    if (savedIds.length === judgeNames.length) {
                        return savedIds;
                    }
                    return judgeNames.map((name) => {
                        const judge = this.availableJudges.find((j) => j.nama.toLowerCase() === name.toLowerCase());
                        return judge ? judge.id : name;
                    });
                },
                // Sync hidden inputs when data changes
                _syncHiddenInputs(roundKey) {
                    if (this.rounds[roundKey]) {
                        this.rounds[roundKey]._judgeNamesText = this._getJudgeNamesText(roundKey);
                        this.rounds[roundKey]._scoringPointsText = this._getScoringPointsText(roundKey);
                        this.rounds[roundKey]._judgeIdsJson = JSON.stringify(this._getJudgeIds(roundKey));
                    }
                },
                roundJudgeCount(roundKey) {
                    return Math.max(1, Math.min(15, Number(this.rounds[roundKey]?.judgeCount ?? 1)));
                },
                roundJudgeNamesText(roundKey) {
                    return (this.rounds[roundKey]?.judgeNames ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean)
                        .join('\n');
                },
                roundJudgeIds(roundKey) {
                    const judgeNames = (this.rounds[roundKey]?.judgeNames ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean);
                    // Use saved judge_ids from config if available and counts match
                    const savedIds = this.rounds[roundKey]?.judge_ids ?? [];
                    if (savedIds.length === judgeNames.length) {
                        return savedIds;
                    }
                    // Fallback: map from availableJudges (case-insensitive)
                    return judgeNames.map((name) => {
                        const judge = this.availableJudges.find((j) => j.nama.toLowerCase() === name.toLowerCase());
                        return judge ? judge.id : name;
                    });
                },
                roundScoringPointsText(roundKey) {
                    return (this.rounds[roundKey]?.scoringPoints ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean)
                        .join('\n');
                },
                roundSummary(roundKey) {
                    const judgeTotal = (this.rounds[roundKey]?.judgeNames ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean)
                        .length;
                    const pointTotal = (this.rounds[roundKey]?.scoringPoints ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean)
                        .length;

                    return `${judgeTotal} hakim, ${pointTotal} poin`;
                },
                roundScoringPointsCount(roundKey) {
                    return (this.rounds[roundKey]?.scoringPoints ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean)
                        .length;
                },
                roundReady(roundKey) {
                    const pointTotal = this.roundScoringPointsCount(roundKey);

                    return !this.hasJudgeNameIssues(roundKey) && pointTotal > 0;
                },
                normalizedJudgeNames(roundKey) {
                    return (this.rounds[roundKey]?.judgeNames ?? []).map((value) => String(value ?? '').trim());
                },
                hasJudgeNameIssues(roundKey) {
                    const names = this.normalizedJudgeNames(roundKey);

                    return names.some((value) => value === '')
                        || new Set(names.map((value) => value.toLowerCase())).size !== names.length;
                },
                get hasAnyRoundIssues() {
                    return Object.keys(this.rounds).some((roundKey) => this.hasJudgeNameIssues(roundKey));
                },
                judgeNameState(roundKey, index) {
                    const names = this.normalizedJudgeNames(roundKey);
                    const value = names[index] ?? '';

                    if (value === '') {
                        return {
                            valid: false,
                            message: 'Nama hakim wajib diisi.',
                        };
                    }

                    const normalized = value.toLowerCase();
                    const duplicateIndex = names.findIndex((item, itemIndex) => itemIndex !== index && item.toLowerCase() === normalized);

                    if (duplicateIndex !== -1) {
                        return {
                            valid: false,
                            message: 'Nama hakim tidak boleh sama dengan hakim lainnya.',
                        };
                    }

                    return {
                        valid: true,
                        message: '',
                    };
                },
                syncJudgeCount(roundKey) {
                    const round = this.rounds[roundKey];
                    const total = Math.max(1, Math.min(15, Number(round?.judgeCount || 1)));
                    round.judgeCount = total;

                    if (round.judgeNames.length < total) {
                        for (let index = round.judgeNames.length; index < total; index += 1) {
                            round.judgeNames.push('');
                        }
                    }

                    if (round.judgeNames.length > total) {
                        round.judgeNames = round.judgeNames.slice(0, total);
                    }
                    this._syncHiddenInputs(roundKey);
                },
                addJudge(roundKey) {
                    const round = this.rounds[roundKey];
                    if (!round) {
                        return;
                    }

                    if (this.roundJudgeCount(roundKey) >= 15) {
                        return;
                    }

                    round.judgeCount = this.roundJudgeCount(roundKey) + 1;
                    round.judgeNames.push('');
                    this._syncHiddenInputs(roundKey);
                },
                removeJudge(roundKey, index) {
                    const round = this.rounds[roundKey];
                    if (!round || round.judgeNames.length <= 1) {
                        return;
                    }

                    round.judgeNames.splice(index, 1);
                    round.judgeCount = Math.max(1, Math.min(15, round.judgeNames.length));
                    if (this.activeRound === roundKey && this.rounds[roundKey].judgeNames.length === 0) {
                        this.rounds[roundKey].judgeNames = [''];
                        round.judgeCount = 1;
                    }
                    this._syncHiddenInputs(roundKey);
                },
                addPoint(roundKey) {
                    this.rounds[roundKey].scoringPoints.push('');
                    this._syncHiddenInputs(roundKey);
                },
                removePoint(roundKey, index) {
                    if (this.rounds[roundKey].scoringPoints.length <= 1) {
                        return;
                    }

                    this.rounds[roundKey].scoringPoints.splice(index, 1);
                    this._syncHiddenInputs(roundKey);
                },
                movePointUp(roundKey, index) {
                    if (index <= 0) {
                        return;
                    }

                    const points = this.rounds[roundKey].scoringPoints;
                    const current = points[index];
                    points[index] = points[index - 1];
                    points[index - 1] = current;
                    this._syncHiddenInputs(roundKey);
                },
                // Sync all rounds before form submission
                syncAllRoundsBeforeSubmit() {
                    Object.keys(this.rounds).forEach((roundKey) => {
                        this._syncHiddenInputs(roundKey);
                    });
                },
            };
        }

        function judgeBatchForm(initialState) {
            return {
                judgeNames: initialState.judgeNames ?? [],
                judgeIds: initialState.judgeIds ?? [],
                activeJudgeIndex: Math.max(0, Number(initialState.initialJudgeIndex ?? 0)),
                selectedParticipantName: String(initialState.selectedParticipantName ?? ''),
                selectedParticipantLot: String(initialState.selectedParticipantLot ?? ''),
                selectedJudgingRound: String(initialState.selectedJudgingRound ?? ''),
                previewOpen: false,
                previewData: [],
                isSubmitting: false,
                init() {
                    window.addEventListener('scoring-participant-selected', (event) => {
                        const detail = event?.detail ?? {};
                        this.selectedParticipantName = String(detail.name ?? this.selectedParticipantName ?? '');
                        this.selectedParticipantLot = String(detail.lot_number ?? this.selectedParticipantLot ?? '');
                        this.selectedJudgingRound = String(detail.judging_round ?? this.selectedJudgingRound ?? '');
                    });

                    // Listen for score updated events to refresh page when score is submitted
                    window.addEventListener('mtq-score-updated', (event) => {
                        const detail = event?.detail ?? {};
                        // Check if this score is for the current participant
                        const currentParticipantId = window.currentParticipantId;
                        if (detail.participant_id == currentParticipantId) {
                            // Refresh the page to show updated score data
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        }
                    });
                },
                selectedParticipantLabel() {
                    const participantName = this.selectedParticipantName || 'Peserta aktif';
                    const roundLabel = this.selectedJudgingRound || 'Babak aktif';

                    return `${participantName} | ${roundLabel}`;
                },
                maxJudgeIndex() {
                    return Math.max(0, this.judgeNames.length - 1);
                },
                progressLabel() {
                    return `${this.activeJudgeIndex + 1} / ${Math.max(1, this.judgeNames.length)}`;
                },
                progressPercent() {
                    return this.judgeNames.length > 0
                        ? Math.round(((this.activeJudgeIndex + 1) / this.judgeNames.length) * 100)
                        : 0;
                },
                judgePanel(index) {
                    const panel = this.$root.querySelector(`[data-judge-panel="${index}"]`);
                    return panel instanceof HTMLElement ? panel : null;
                },
                judgeCompleted(index) {
                    const panel = this.judgePanel(index);
                    if (!panel) {
                        return false;
                    }

                    const scoreInputs = [...panel.querySelectorAll('input[type="number"]')];
                    if (scoreInputs.length === 0) {
                        return false;
                    }

                    // Check if all score inputs have value >= 1 (0 or empty = not filled)
                    return scoreInputs.every((input) => {
                        const value = Number(input.value);
                        return input.value !== '' && !isNaN(value) && value >= 1;
                    });
                },
                judgeStatusLabel(index) {
                    return this.judgeCompleted(index) ? 'Lengkap' : 'Belum lengkap';
                },
                judgeStatusDotClass(index) {
                    return this.judgeCompleted(index)
                        ? 'bg-emerald-300 shadow-[0_0_12px_rgba(110,231,183,0.55)]'
                        : 'bg-amber-300 shadow-[0_0_10px_rgba(252,211,77,0.45)]';
                },
                judgeStatusClass(index) {
                    return this.judgeCompleted(index)
                        ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100'
                        : 'border-amber-300/20 bg-amber-400/10 text-amber-100';
                },
                completionSummary() {
                    const completed = this.judgeNames.filter((_, index) => this.judgeCompleted(index)).length;
                    return `${completed} / ${Math.max(1, this.judgeNames.length)} hakim lengkap`;
                },
                collectPreviewData() {
                    const judgeData = this.judgeNames.map((judgeName, index) => {
                        const panel = this.judgePanel(index);
                        const scoreInputs = panel ? [...panel.querySelectorAll('input[type="number"]')] : [];
                        // Treat value < 1 (including 0, empty) as not filled
                        const scores = scoreInputs.map((input) => ({
                            label: input.dataset.scoreLabel || 'Poin',
                            value: input.value || '',
                            // Only count values >= 1 as valid
                            numericValue: (input.value !== '' && !isNaN(Number(input.value)) && Number(input.value) >= 1) ? Number(input.value) : null,
                        }));
                        const remarksField = panel ? panel.querySelector('textarea') : null;

                        return {
                            name: judgeName,
                            scores,
                            remarks: remarksField && String(remarksField.value || '').trim() !== '' ? remarksField.value.trim() : '',
                        };
                    });

                    // Calculate per-point averages: sum / count of judges who gave valid values (>= 1)
                    if (judgeData.length > 0 && judgeData[0].scores.length > 0) {
                        const pointAverages = judgeData[0].scores.map((score, idx) => {
                            let sum = 0;
                            let count = 0;
                            judgeData.forEach(judge => {
                                const val = judge.scores[idx]?.numericValue;
                                // Only count values >= 1
                                if (val !== null && val !== undefined && val >= 1) {
                                    sum += val;
                                    count++;
                                }
                            });
                            const avg = count > 0 ? sum / count : 0;
                            return {
                                label: score.label,
                                sum: sum.toFixed(2),
                                count: count,
                                average: avg.toFixed(2),
                                numericAverage: avg,
                            };
                        });
                        this.previewPointTotals = pointAverages;
                    } else {
                        this.previewPointTotals = [];
                    }

                    return judgeData;
                },
                previewPointTotals: [],
                calculateTotalScore() {
                    if (!this.previewPointTotals || this.previewPointTotals.length === 0) {
                        return '0.00';
                    }
                    // Total Score = sum of all per-point averages
                    const sum = this.previewPointTotals.reduce((acc, pt) => acc + pt.numericAverage, 0);
                    return sum.toFixed(2);
                },
                // Detect score discrepancies between judges (interval > 2)
                detectScoreDiscrepancies() {
                    const discrepancies = [];
                    if (!this.previewData || this.previewData.length < 2) {
                        return discrepancies;
                    }

                    const pointCount = this.previewData[0]?.scores?.length || 0;
                    if (pointCount === 0) return discrepancies;

                    for (let i = 0; i < pointCount; i++) {
                        const pointLabel = this.previewData[0].scores[i]?.label || `Poin ${i + 1}`;
                        const values = [];

                        this.previewData.forEach((judge, judgeIdx) => {
                            const score = judge.scores[i];
                            if (score && score.numericValue !== null) {
                                values.push({
                                    judgeName: judge.name,
                                    value: score.numericValue,
                                    displayValue: score.value,
                                });
                            }
                        });

                        if (values.length < 2) continue;

                        // Find min and max
                        const sorted = [...values].sort((a, b) => a.value - b.value);
                        const min = sorted[0];
                        const max = sorted[sorted.length - 1];
                        const interval = max.value - min.value;

                        if (interval > 2) {
                            discrepancies.push({
                                pointLabel,
                                min,
                                max,
                                interval,
                                values,
                            });
                        }
                    }

                    return discrepancies;
                },
                hasDiscrepancies() {
                    return this.detectScoreDiscrepancies().length > 0;
                },
                // Check if a specific point label has discrepancy
                hasDiscrepancyForPoint(pointLabel) {
                    const discrepancies = this.detectScoreDiscrepancies();
                    return discrepancies.some(d => d.pointLabel === pointLabel);
                },
                // Get discrepancy info for a specific point
                getDiscrepancyForPoint(pointLabel) {
                    const discrepancies = this.detectScoreDiscrepancies();
                    return discrepancies.find(d => d.pointLabel === pointLabel);
                },
                openPreview() {
                    this.previewData = this.collectPreviewData();
                    this.previewOpen = true;
                },
                closePreview() {
                    this.previewOpen = false;
                },
                scorePreviewClass(value) {
                    if (value <= 0) {
                        return 'border-rose-400/20 bg-rose-400/10';
                    }

                    if (value >= 90) {
                        return 'border-emerald-300/20 bg-emerald-400/10';
                    }

                    if (value <= 60) {
                        return 'border-amber-300/20 bg-amber-400/10';
                    }

                    return 'border-slate-800 bg-slate-950/55';
                },
                submitConfirmed() {
                    if (this.isSubmitting) return;
                    this.previewOpen = false;
                    this.isSubmitting = true;

                    const form = this.$refs.scoreForm;
                    if (!form) {
                        this.isSubmitting = false;
                        this.$root.submit();
                        return;
                    }

                    // Show loading using existing app loading overlay
                    showLoadingOverlay('Mohon tunggu, data penilaian sedang disimpan.');

                    // Submit form
                    fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(response => {
                        if (response.ok || response.redirected) {
                            // Hide loading overlay
                            hideLoadingOverlay();

                            // Show beautiful success notification
                            Swal.fire({
                                icon: 'success',
                                title: '<span class="text-2xl">Berhasil!</span>',
                                html: `
                                    <div class="flex flex-col items-center py-3">
                                        <div class="w-20 h-20 mb-4 rounded-full bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <p class="text-slate-300 text-center mb-2">Nilai berhasil disimpan!</p>
                                        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-sm">
                                            <p class="text-emerald-300 font-semibold">${this.selectedParticipantName || 'Peserta'}</p>
                                            <p class="text-emerald-200/70">Lot ${this.selectedParticipantLot || '-'} · ${this.selectedJudgingRound || 'Babak aktif'}</p>
                                        </div>
                                    </div>
                                `,
                                confirmButtonText: 'OK, Mengerti',
                                confirmButtonColor: '#22c55e',
                                background: '#1e293b',
                                color: '#f1f5f9',
                                allowOutsideClick: false,
                                customClass: {
                                    popup: 'rounded-3xl !max-w-md !w-full',
                                    confirmButton: '!rounded-xl !px-8 !py-3 !font-bold !shadow-lg',
                                },
                                timer: 5000,
                                timerProgressBar: true,
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            hideLoadingOverlay();
                            return response.json().then(err => {
                                throw new Error(err.message || 'Terjadi kesalahan');
                            });
                        }
                    }).catch((error) => {
                        // Hide loading and show error
                        hideLoadingOverlay();
                        Swal.fire({
                            icon: 'error',
                            title: '<span class="text-2xl">Gagal!</span>',
                            html: `
                                <div class="flex flex-col items-center py-3">
                                    <div class="w-20 h-20 mb-4 rounded-full bg-gradient-to-br from-rose-400 to-red-500 flex items-center justify-center shadow-lg shadow-rose-500/30">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                    <p class="text-slate-300 text-center">${error.message || 'Terjadi kesalahan saat menyimpan nilai.'}</p>
                                </div>
                            `,
                            confirmButtonText: 'Coba Lagi',
                            confirmButtonColor: '#ef4444',
                            background: '#1e293b',
                            color: '#f1f5f9',
                            customClass: {
                                popup: 'rounded-3xl !max-w-md !w-full',
                                confirmButton: '!rounded-xl !px-8 !py-3 !font-bold',
                            },
                        });
                    }).finally(() => {
                        this.isSubmitting = false;
                    });
                },
                goToJudge(index) {
                    this.activeJudgeIndex = Math.max(0, Math.min(index, this.maxJudgeIndex()));
                    this.focusActiveJudgePanel();
                },
                nextJudge() {
                    if (this.activeJudgeIndex >= this.maxJudgeIndex()) {
                        return;
                    }

                    this.activeJudgeIndex += 1;
                    this.focusActiveJudgePanel();
                },
                previousJudge() {
                    if (this.activeJudgeIndex <= 0) {
                        return;
                    }

                    this.activeJudgeIndex -= 1;
                    this.focusActiveJudgePanel();
                },
                focusActiveJudgePanel() {
                    this.$nextTick(() => {
                        const panel = this.$root.querySelector(`[data-judge-panel="${this.activeJudgeIndex}"]`);
                        if (!(panel instanceof HTMLElement)) {
                            return;
                        }

                        const target = panel.querySelector('input[type="number"], textarea');
                        if (target instanceof HTMLElement) {
                            target.focus({ preventScroll: true });
                        }
                    });
                },
            };
        }

        // Global function for edit mode confirmation
        window.confirmEditModeGlobal = function(judgeCount) {
            Swal.fire({
                icon: 'warning',
                title: '<span class="text-xl">⚠️ Perhatian!</span>',
                html: '<div class="text-left mt-3"><p class="mb-3 text-slate-200">Saat mode edit aktif, <strong class="text-amber-300">semua nilai lama akan dihapus</strong>.</p><p class="mb-2 text-slate-300">Anda <strong class="text-red-400">HARUS</strong> mengisi ulang:</p><ul class="text-left text-slate-300 ml-4 list-disc"><li>✅ Semua nilai ' + judgeCount + ' Dewan Hakim</li><li>✅ Semua poin penilaian</li><li>✅ Catatan (jika ada)</li></ul></div>',
                confirmButtonText: '👍 Saya Paham, Lanjutkan',
                confirmButtonColor: '#f59e0b',
                cancelButtonText: 'Batal',
                cancelButtonColor: '#64748b',
                showCancelButton: true,
                background: '#1e293b',
                color: '#f1f5f9',
                customClass: {
                    popup: 'rounded-3xl !max-w-lg !w-full',
                    confirmButton: '!rounded-xl !px-6 !py-3 !font-bold !text-slate-900',
                    cancelButton: '!rounded-xl !px-6 !py-3 !font-bold',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    window.dispatchEvent(new CustomEvent('enable-edit-mode'));
                    // Clear all score inputs after enabling edit mode
                    setTimeout(() => {
                        document.querySelectorAll('input[name^="scores"]').forEach(input => { input.value = ''; });
                        document.querySelectorAll('textarea[name^="remarks"]').forEach(input => { input.value = ''; });
                    }, 100);
                }
            });
        };

        document.addEventListener('DOMContentLoaded', () => {
            if (window.location.hash !== '#form-penilaian') {
                return;
            }

            const formSection = document.getElementById('form-penilaian');
            if (!formSection) {
                return;
            }

            smoothScrollToElement(formSection, 20, 700);

            window.setTimeout(() => {
                const focusTarget = formSection.querySelector('input[type="number"], textarea, input[type="radio"]:checked, input:not([type="hidden"]), button');
                if (focusTarget instanceof HTMLElement) {
                    focusTarget.focus({ preventScroll: true });
                }
            }, 180);
        });
    </script>
</body>
</html>
