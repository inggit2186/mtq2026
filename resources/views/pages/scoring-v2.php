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
    $latestScore = $participantScores->sortByDesc('submitted_at')->first();
    $latestRound = (string) ($latestScore?->judging_round ?? '');
    $latestRoundScores = $latestRound !== ''
        ? $participantScores->where('judging_round', $latestRound)->keyBy('judge_name')
        : collect();
    $correctionRequestDraft = [];

    foreach ($judgeNames as $judgeName) {
        $entry = $latestRoundScores->get($judgeName);
        $correctionRequestDraft[$judgeName] = [
            'scores' => [],
            'remarks' => (string) ($entry?->remarks ?? ''),
        ];

        foreach (array_keys($criteria) as $key) {
            $correctionRequestDraft[$judgeName]['scores'][$key] = $entry ? data_get($entry->score_breakdown, $key, '') : '';
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
        'score_count' => $participantScores->count(),
        'average_score' => number_format((float) ($participant->scores->avg('score') ?? 0), 2),
        'latest_score' => number_format((float) ($latestScore?->score ?? 0), 2),
        'latest_round' => (string) ($latestScore?->judging_round ?? '-'),
        'scoring_status' => $participantScores->count() > 0 ? 'Sudah dinilai' : 'Belum dinilai',
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

    $pointLabelsValue = preg_split('/\r\n|\r|\n/', (string) old('rounds.'.$roundKey.'.scoring_points_text', implode("\n", array_values($roundConfig['scoring_points'] ?? $defaultCriteria)))) ?: [];
    $pointLabelsValue = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $pointLabelsValue)));
    if ($pointLabelsValue === []) {
        $pointLabelsValue = array_values($defaultCriteria);
    }

    $defaultRoundForms[$roundKey] = [
        'label' => $roundLabel,
        'judge_count' => (int) old('rounds.'.$roundKey.'.judge_count', $roundConfig['judge_count'] ?? max(1, count($judgeNamesValue))),
        'judge_names' => $judgeNamesValue,
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

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <!-- Sidebar -->
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block glass-card"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
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

                <nav class="space-y-2 mb-6">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <a href="<?= e(route('dashboard')) ?>" class="secondary-button w-full flex items-center justify-center gap-2">
                    <?= mtq_icon('home', 'h-4 w-4') ?>
                    Dashboard
                </a>
            </aside>

            <div class="min-w-0 space-y-6">
                <!-- Header -->
                <header class="glass-card rounded-[2rem] p-6 glow-cyan">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
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
                    <!-- Round Tabs -->
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div class="flex items-center gap-3">
                            <?= mtq_icon('layers', 'h-5 w-5 text-cyan-300') ?>
                            <h2 class="text-xl font-bold text-white">Pilih Golongan</h2>
                        </div>
                        <div class="flex gap-2">
                            <?php foreach ($judgingRounds as $roundLabel): ?>
                                <a href="<?= e(route('scoring', array_filter([
                                    'branch' => $filters['branch'] ?? null,
                                    'keyword' => $filters['keyword'] ?? null,
                                    'competition_category_id' => $filters['competition_category_id'] ?? null,
                                    'judging_round' => $roundLabel,
                                    'step' => 1,
                                ]))) ?>"
                                    class="rounded-xl px-4 py-2 text-sm font-semibold transition <?= $selectedJudgingRound === $roundLabel ? 'bg-cyan-400/15 text-cyan-100 border border-cyan-400/30' : 'text-slate-400 hover:text-white border border-slate-700' ?>">
                                    <?= e($roundLabel) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

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
                                                $cardSetupEditable = (bool) ($cardSetting?->isEditable() ?? false);
                                                $cardSetupRequested = (bool) ($cardSetting?->isEditRequested() ?? false);
                                                $cardSetupReady = (bool) ($cardSetting?->isReady() ?? false);
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
                                            <a href="<?= e($categoryLink) ?>"
                                                class="category-card group overflow-hidden rounded-2xl border bg-slate-900/80 text-left transition hover:-translate-y-1 hover:shadow-lg <?= $isSelectedCard ? 'ring-2 ring-cyan-300/30 border-cyan-300/60' : 'border-slate-700/80' ?>">
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
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

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
                        <div class="<?= $setupEditable ? 'status-pill border-cyan-300/20 bg-cyan-400/10 text-cyan-100' : ($setupReady ? 'status-pill' : 'inline-flex items-center gap-2 rounded-full border border-amber-400/18 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-100') ?>">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full <?= $setupEditable ? 'bg-cyan-300' : ($setupReady ? 'bg-emerald-300' : 'bg-amber-300') ?>"></span>
                            <?= $setupEditable ? 'Setting Dibuka' : ($setupReady ? 'Setting Tersimpan' : 'Belum Disiapkan') ?>
                        </div>
                    </div>

                    <?php if (! $setupCreated || $setupEditable): ?>
                    <form method="POST" action="<?= e(route('scoring.settings.store')) ?>" class="mt-6 grid gap-4" data-loading-text="Menyimpan konfigurasi penilaian..."
                        x-data="scoringRoundSetupForm({
                            rounds: <?= e(json_encode($defaultRoundForms, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            activeRound: <?= e(json_encode(array_key_exists(strtolower($selectedJudgingRound), $roundFormKeys) ? strtolower($selectedJudgingRound) : 'penyisihan', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            availableJudges: <?= e(json_encode($availableJudges, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            categoryJudgeIds: <?= e(json_encode($categoryJudgeIds ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                        })">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <?php foreach ($roundFormKeys as $roundLabel => $roundKey): ?>
                            <input type="hidden" name="rounds[<?= e($roundKey) ?>][judge_names_text]" :value="roundJudgeNamesText('<?= e($roundKey) ?>')">
                            <input type="hidden" name="rounds[<?= e($roundKey) ?>][scoring_points_text]" :value="roundScoringPointsText('<?= e($roundKey) ?>')">
                        <?php endforeach; ?>

                        <input type="hidden" name="competition_category_id" value="<?= e($selectedCategory?->id ?? '') ?>">
                        <input type="hidden" name="judging_rounds_text" value="Penyisihan&#10;Final">
                        <input type="hidden" name="selected_judging_round" :value="activeRound === 'final' ? 'Final' : 'Penyisihan'">
                        <?php if ($selectedCategoryIsMfq): ?>
                            <div class="rounded-[1.5rem] border border-amber-400/20 bg-amber-400/10 px-4 py-4 text-sm text-amber-100">
                                Golongan yang sedang dipilih terdeteksi sebagai MFQ. Gunakan jalur MFQ dari Step 1, karena format penilaiannya berbeda dari setting babak umum.
                            </div>
                        <?php endif; ?>

                    <div class="rounded-[1.7rem] border border-slate-800 bg-slate-950/60 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="section-kicker">Setting Babak</p>
                                <h3 class="mt-2 text-xl font-bold text-white">Pilih babak yang mau dirapikan</h3>
                                <p class="mt-2 text-sm text-slate-300">Tab di bawah menampilkan ringkasan hakim dan poin tiap babak, lalu form detailnya muncul di area utama.</p>
                            </div>
                        </div>

                            <div class="mt-5 flex flex-wrap gap-3 rounded-[1.4rem] border border-slate-800 bg-slate-950/45 p-2">
                                <?php foreach ($roundFormKeys as $roundLabel => $roundKey): ?>
                                    <button type="button"
                                        class="min-w-[220px] flex-1 rounded-[1.2rem] border px-4 py-3 text-left transition"
                                        x-on:click="activeRound = '<?= e($roundKey) ?>'"
                                        :class="activeRound === '<?= e($roundKey) ?>'
                                            ? 'border-cyan-300/30 bg-cyan-400/10 text-cyan-50 shadow-[0_18px_40px_-28px_rgba(34,211,238,0.8)]'
                                            : 'border-slate-800 bg-slate-950/70 text-slate-300 hover:border-slate-700 hover:bg-slate-900/70 hover:text-white'">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm font-semibold"><?= e($roundLabel) ?></span>
                                            <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]"
                                                :class="activeRound === '<?= e($roundKey) ?>'
                                                    ? 'border-cyan-300/20 bg-cyan-400/10 text-cyan-100'
                                                    : 'border-slate-700 bg-slate-900/80 text-slate-400'">
                                                <?= $selectedJudgingRound === $roundLabel ? 'aktif' : 'siap' ?>
                                            </span>
                                        </div>
                                        <p class="mt-2 text-xs text-slate-400" x-text="roundSummary('<?= e($roundKey) ?>')"></p>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <?php foreach ($roundFormKeys as $roundLabel => $roundKey): ?>
                                <section x-show="activeRound === '<?= e($roundKey) ?>'" x-cloak class="mt-5 space-y-5">
                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-200"><?= e($roundLabel) ?></p>
                                    <p class="mt-1 text-sm text-slate-300">Rapikan hakim dan poin untuk babak ini tanpa mengulang pilihan dari Step 1.</p>
                                </div>
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]"
                                    :class="roundReady('<?= e($roundKey) ?>') ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border-amber-300/20 bg-amber-400/10 text-amber-100'"
                                    x-text="roundReady('<?= e($roundKey) ?>') ? 'Setup siap' : 'Masih perlu dilengkapi'"></span>
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
                                                        <span x-text="roundJudgeCount('<?= e($roundKey) ?>"></span> hakim
                                                    </span>
                                                    <button type="button" class="secondary-button rounded-xl px-3 py-2 text-xs" x-on:click="judgeSearchQuery = ''; judgeModalOpen = '<?= e($roundKey) ?>'">
                                                        <?= mtq_icon('plus', 'h-4 w-4') ?>
                                                        Tambah Hakim
                                                    </button>
                                                </div>
                                            </div>
                                            <input type="hidden" name="rounds[<?= e($roundKey) ?>][judge_count]" :value="rounds.<?= e($roundKey) ?>.judgeCount">
                                            <div class="mt-4 space-y-3">
                                                <template x-for="(judgeName, index) in rounds.<?= e($roundKey) ?>.judgeNames" :key="'<?= e($roundKey) ?>-judge-' + index">
                                                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-4 py-3 flex items-center justify-between">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-400/18 bg-cyan-400/10 text-sm font-bold text-cyan-200" x-text="index + 1"></div>
                                                            <span class="font-semibold text-white" x-text="judgeName"></span>
                                                        </div>
                                                        <button type="button"
                                                            class="secondary-button rounded-xl px-3 py-2 text-xs"
                                                            x-on:click="rounds.<?= e($roundKey) ?>.judgeNames.splice(index, 1)"
                                                            x-show="rounds.<?= e($roundKey) ?>.judgeNames.length > 1">
                                                            <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                            Hapus
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <p x-show="hasJudgeNameIssues('<?= e($roundKey) ?>')" class="mt-3 text-xs text-rose-200">Semua nama hakim babak <?= e($roundLabel) ?> wajib terisi dan tidak boleh ada yang sama.</p>
                                        </div>

                                        <!-- Modal Tambah Hakim -->
                                        <div x-show="judgeModalOpen === '<?= e($roundKey) ?>'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm px-4 py-6"
                                            x-on:keydown.escape.window="judgeModalOpen = null">
                                            <div class="absolute inset-0" x-on:click="judgeModalOpen = null"></div>
                                            <div class="relative z-10 w-full max-w-lg rounded-2xl border border-cyan-400/20 bg-slate-900 shadow-xl max-h-[85vh] flex flex-col">
                                                <div class="flex items-center justify-between border-b border-slate-700 px-6 py-4 shrink-0">
                                                    <div>
                                                        <h3 class="text-lg font-bold text-white">Tambah Hakim - <?= e($roundLabel) ?></h3>
                                                        <p class="mt-1 text-xs text-slate-400" x-text="`${availableJudges.filter(j => !rounds.<?= e($roundKey) ?>.judgeNames.includes(j.nama)).length} hakim tersedia`"></p>
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
                                                            <template x-for="judge in availableJudges.filter(j => categoryJudgeIds.includes(j.id) && !rounds.<?= e($roundKey) ?>.judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase())))" :key="'sk-' + judge.id">
                                                                <button type="button"
                                                                    class="flex items-center gap-3 rounded-xl border border-cyan-400/20 bg-cyan-400/5 p-3 text-left transition hover:border-cyan-400/40 hover:bg-cyan-400/10 hover:scale-[1.01]"
                                                                    x-on:click="rounds.<?= e($roundKey) ?>.judgeNames.push(judge.nama); $nextTick(() => { if (availableJudges.filter(j => !rounds.<?= e($roundKey) ?>.judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0) judgeModalOpen = null; })">
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
                                                            <p x-show="availableJudges.filter(j => categoryJudgeIds.includes(j.id) && !rounds.<?= e($roundKey) ?>.judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0" class="py-4 text-center text-xs text-slate-500">
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
                                                            <template x-for="judge in availableJudges.filter(j => !categoryJudgeIds.includes(j.id) && !rounds.<?= e($roundKey) ?>.judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase())))" :key="'other-' + judge.id">
                                                                <button type="button"
                                                                    class="flex items-center gap-3 rounded-xl border border-slate-700/60 bg-slate-800/30 p-3 text-left transition hover:border-amber-400/30 hover:bg-amber-400/5 hover:scale-[1.01]"
                                                                    x-on:click="rounds.<?= e($roundKey) ?>.judgeNames.push(judge.nama); $nextTick(() => { if (availableJudges.filter(j => !rounds.<?= e($roundKey) ?>.judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0) judgeModalOpen = null; })">
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
                                                            <p x-show="availableJudges.filter(j => !categoryJudgeIds.includes(j.id) && !rounds.<?= e($roundKey) ?>.judgeNames.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0" class="py-4 text-center text-xs text-slate-600">
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
                                                <template x-for="(pointLabel, index) in rounds.<?= e($roundKey) ?>.scoringPoints" :key="'<?= e($roundKey) ?>-point-' + index">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-emerald-400/18 bg-emerald-400/10 text-sm font-bold text-emerald-100" x-text="index + 1"></div>
                                                        <input type="text" x-model="rounds.<?= e($roundKey) ?>.scoringPoints[index]" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" :placeholder="'Poin penilaian ' + (index + 1)">
                                                        <button type="button" class="secondary-button rounded-xl px-3 py-2" x-on:click="movePointUp('<?= e($roundKey) ?>', index)" x-bind:disabled="index === 0" title="Naik">
                                                            <?= mtq_icon('arrow-up', 'h-4 w-4') ?>
                                                        </button>
                                                        <button type="button" class="secondary-button rounded-xl px-3 py-2 text-xs" x-on:click="removePoint('<?= e($roundKey) ?>', index)" x-bind:disabled="rounds.<?= e($roundKey) ?>.scoringPoints.length <= 1">
                                                            Hapus
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-3">
                                                <button type="button" class="secondary-button" x-on:click="addPoint('<?= e($roundKey) ?>')">
                                                    <?= mtq_icon('plus', 'h-4 w-4') ?>
                                                    Tambah Poin
                                                </button>
                                            </div>
                                            <p class="mt-3 text-xs text-slate-400">Urutan poin babak <?= e($roundLabel) ?> otomatis menjadi urutan prioritas tie-break untuk babak ini.</p>
                                        </div>
                                    </div>
                                </section>
                            <?php endforeach; ?>
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
                                            <div class="flex flex-wrap gap-3">
                                                <button type="button" class="secondary-button px-4 py-3" x-on:click="resetSearch()" :disabled="!search && !selectedParticipant && !selectedDistrict" x-bind:class="!search && !selectedParticipant && !selectedDistrict ? 'cursor-not-allowed opacity-60' : ''">
                                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                                    Bersihkan
                                                </button>
                                                <button type="button" class="primary-button px-4 py-3" x-on:click="goToSelected()" :disabled="!selectedParticipant && !selectedDistrict" x-bind:class="!selectedParticipant && !selectedDistrict ? 'cursor-not-allowed opacity-60' : ''">
                                                    <?= mtq_icon('chart', 'h-4 w-4') ?>
                                                    <span x-text="isMsq ? 'Aktifkan Kecamatan' : 'Aktifkan Peserta'"></span>
                                                </button>
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
                                                <button type="button" class="mt-4 inline-flex items-center gap-2 rounded-full border border-amber-300/20 bg-amber-400/10 px-3 py-2 text-xs font-semibold text-amber-100 transition hover:border-amber-300/30 hover:bg-amber-400/15"
                                                    x-show="Number(selectedParticipant.score_count || 0) > 0"
                                                    x-on:click="window.dispatchEvent(new CustomEvent('open-scoring-correction-request', {
                                                        detail: {
                                                            name: selectedParticipant.name,
                                                            lot: selectedParticipant.lot_number,
                                                            round: selectedParticipant.correction_request_round,
                                                            draft: selectedParticipant.correction_request_draft,
                                                        }
                                                    }))">
                                                        <?= mtq_icon('pencil', 'h-4 w-4') ?>
                                                        Request Perbaikan
                                                    </button>
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

                    <div id="form-penilaian" class="glass-card rounded-[2rem] p-6">
                            <div class="rounded-[1.65rem] border border-cyan-400/14 bg-gradient-to-br from-cyan-400/10 via-slate-950/70 to-slate-950/95 p-5 shadow-[0_20px_60px_-36px_rgba(34,211,238,0.45)]">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="max-w-2xl">
                                        <div class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-100">
                                            <?= mtq_icon('check-circle', 'h-3.5 w-3.5') ?>
                                            Form Penilaian
                                        </div>
                                        <h2 class="mt-3 text-2xl font-black text-white sm:text-[2rem]">Input nilai hakim per peserta</h2>
                                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">Babak aktif mengikuti setting yang sudah dipilih operator. Gunakan tab hakim di bawah untuk berpindah panel, lalu simpan seluruh batch sekaligus.</p>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-[1.2rem] border border-slate-800 bg-slate-950/70 px-4 py-3">
                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Babak aktif</p>
                                            <p class="mt-1 text-base font-bold text-white"><?= e($selectedJudgingRound) ?></p>
                                        </div>
                                        <div class="rounded-[1.2rem] border border-slate-800 bg-slate-950/70 px-4 py-3">
                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Jumlah hakim</p>
                                            <p class="mt-1 text-base font-bold text-cyan-200"><?= e(count($judgeNames)) ?> orang</p>
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
                                <div x-data="{ correctionRequestOpen: false, correctionRequestName: '', correctionRequestLot: '', correctionRequestRound: '', correctionRequestDraft: {}, init() { window.addEventListener('open-scoring-correction-request', (event) => { const detail = event?.detail ?? {}; this.correctionRequestOpen = true; this.correctionRequestName = detail.name || this.correctionRequestName; this.correctionRequestLot = detail.lot || this.correctionRequestLot; this.correctionRequestRound = detail.round || this.correctionRequestRound; this.correctionRequestDraft = detail.draft || this.correctionRequestDraft; }); } }">
                                <form method="POST" action="<?= e(route('scoring.store')) ?>" x-ref="scoreForm" class="mt-6 grid gap-4 <?= $participantHasScores ? 'pointer-events-none opacity-45' : '' ?>"
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

                                    <fieldset <?= $participantHasScores ? 'disabled' : '' ?>>

                                    <div class="rounded-[1.25rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
                                        Babak aktif saat ini: <span class="font-bold"><?= e($selectedJudgingRound) ?></span>. Isi nilai hakim per panel, pindah dengan tombol `Lanjut`, lalu simpan semua sekaligus di akhir.
                                    </div>

                                    <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                                        <div class="rounded-[1.4rem] border border-slate-800 bg-slate-950/45 px-4 py-4">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Progress Batch</p>
                                                    <p class="mt-1 text-lg font-bold text-white"><?= e(count($judgeNames)) ?> hakim siap dinilai</p>
                                                </div>
                                                <div class="rounded-[1rem] border border-cyan-400/18 bg-cyan-400/10 px-3 py-2 text-right">
                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Panel Aktif</p>
                                                    <p class="mt-1 text-sm font-bold text-white" x-text="progressLabel()"></p>
                                                </div>
                                            </div>
                                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
                                                <div class="h-full rounded-full bg-gradient-to-r from-cyan-300 via-sky-400 to-emerald-300 transition-all duration-200" :style="`width: ${progressPercent()}%`"></div>
                                            </div>
                                            <p class="mt-3 text-xs text-slate-400">Klik kartu hakim di bawah untuk berpindah panel tanpa keluar dari form.</p>
                                        </div>
                                        <div class="rounded-[1.4rem] border border-slate-800 bg-slate-950/45 px-4 py-4">
                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Babak penilaian</p>
                                            <div class="mt-2 inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-100">
                                                <?= e($selectedJudgingRound) ?>
                                            </div>
                                            <p class="mt-3 text-sm text-slate-300">Seluruh input pada panel ini mengikuti babak yang sedang aktif.</p>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/45 p-3">
                                        <?php foreach ($judgeNames as $index => $judgeName): ?>
                                            <section x-show="activeJudgeIndex === <?= e($index) ?>" x-cloak data-judge-panel="<?= e($index) ?>" class="space-y-4">
                                                <!-- Tab Hakim (max 3 per row) -->
                                                <div class="rounded-[1.1rem] border border-slate-800 bg-slate-950/40 p-3">
                                                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                                        <div>
                                                            <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Pilih Hakim</p>
                                                            <p class="mt-1 text-sm font-semibold text-white"><?= e(count($judgeNames)) ?> Hakim</p>
                                                        </div>
                                                        <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                                            Panel: <?= e($judgeName) ?>
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                        <?php foreach ($judgeNames as $idx => $jn): ?>
                                                            <button type="button"
                                                                class="rounded-[0.9rem] border px-3 py-2.5 text-left transition"
                                                                :class="activeJudgeIndex === <?= e($idx) ?> ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_8px_24px_-10px_rgba(34,211,238,0.5)]' : 'border-slate-800 bg-slate-950/45 hover:border-cyan-400/25'"
                                                                x-on:click="goToJudge(<?= e($idx) ?>)">
                                                                <div class="flex items-center justify-between gap-2">
                                                                    <div class="min-w-0 flex-1">
                                                                        <p class="text-[10px] uppercase tracking-[0.18em]" :class="activeJudgeIndex === <?= e($idx) ?> ? 'text-cyan-200' : 'text-slate-500'">Hakim <?= e($idx + 1) ?></p>
                                                                        <p class="mt-0.5 truncate text-sm font-semibold text-white"><?= e($jn) ?></p>
                                                                    </div>
                                                                    <span class="inline-flex h-2.5 w-2.5 shrink-0 rounded-full"
                                                                        :class="judgeStatusDotClass(<?= e($idx) ?>)"
                                                                        :title="judgeStatusLabel(<?= e($idx) ?>)"></span>
                                                                </div>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Info Pemberitahuan (single line, bigger) -->
                                                <div class="flex items-center gap-3 rounded-[1rem] border border-amber-400/30 bg-gradient-to-r from-amber-500/15 to-orange-500/10 px-5 py-4">
                                                    <div class="shrink-0">
                                                        <?= mtq_icon('info', 'h-5 w-5 text-amber-300') ?>
                                                    </div>
                                                    <div class="flex-1 text-sm text-amber-50">
                                                        <span class="font-semibold text-amber-100">Penting:</span>
                                                        Bilangan berkoma pakai <span class="font-bold text-amber-100">titik (.)</span>
                                                    </div>
                                                </div>

                                                <!-- Poin Penilaian -->
                                                <div class="rounded-[1.1rem] border-2 border-cyan-400/30 bg-gradient-to-br from-cyan-500/8 to-sky-500/5 p-4 shadow-[0_8px_30px_-12px_rgba(34,211,238,0.25)]">
                                                    <div class="flex items-center gap-2 mb-3">
                                                        <?= mtq_icon('spark', 'h-4 w-4 text-cyan-300') ?>
                                                        <h4 class="text-sm font-bold text-cyan-100 uppercase tracking-[0.18em]">Poin Penilaian</h4>
                                                    </div>
                                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                        <?php foreach ($criteria as $key => $label): ?>
                                                            <div class="rounded-[1rem] border border-cyan-400/20 bg-slate-950/80 p-3 shadow-[0_4px_16px_-8px_rgba(34,211,238,0.15)]">
                                                                <label class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">
                                                                    <?= e($label) ?>
                                                                </label>
                                                                <input
                                                                    name="scores[<?= e($judgeIds[$index] ?? '') ?>][<?= e($key) ?>]"
                                                                    data-score-label="<?= e($label) ?>"
                                                                    type="number"
                                                                    min="0"
                                                                    max="100"
                                                                    step="0.01"
                                                                    value="<?= e(data_get(old('scores', []), ($judgeIds[$index] ?? '').'.'.str_replace('.', '\\.', $key))) ?>"
                                                                    class="w-full rounded-xl border-2 border-cyan-400/30 bg-slate-900/90 px-3.5 py-3 text-center text-lg font-bold text-white outline-none transition focus:border-cyan-300 focus:ring-4 focus:ring-cyan-400/20 focus:bg-slate-900"
                                                                    placeholder="0">
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Catatan -->
                                                <div>
                                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan <?= e($judgeName) ?></label>
                                                    <textarea
                                                        name="remarks[<?= e($judgeIds[$index] ?? '') ?>]"
                                                        rows="2"
                                                        class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                                        placeholder="Opsional, misalnya catatan performa atau keputusan teknis."><?= e(data_get(old('remarks', []), $judgeIds[$index] ?? '')) ?></textarea>
                                                </div>

                                                <!-- Navigation Buttons -->
                                                <div class="flex flex-wrap justify-between gap-3 border-t border-slate-800 pt-3">
                                                    <button type="button"
                                                        class="secondary-button px-4 py-3"
                                                        x-on:click="previousJudge()"
                                                        x-bind:disabled="activeJudgeIndex === 0"
                                                        :class="activeJudgeIndex === 0 ? 'cursor-not-allowed opacity-50' : ''">
                                                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                                        Kembali
                                                    </button>
                                                    <button type="button"
                                                        class="primary-button px-4 py-3 transition"
                                                        x-on:click="activeJudgeIndex === maxJudgeIndex() ? openPreview() : nextJudge()">
                                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]"
                                                            :class="activeJudgeIndex === maxJudgeIndex() ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border-cyan-300/20 bg-cyan-400/10 text-cyan-100'">
                                                            <span x-text="activeJudgeIndex === maxJudgeIndex() ? 'Akhir' : 'Lanjut'"></span>
                                                        </span>
                                                        <span x-text="activeJudgeIndex === maxJudgeIndex() ? 'Pratinjau' : 'Lanjut'"></span>
                                                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                                    </button>
                                                </div>
                                            </section>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-slate-800 bg-slate-950/45 px-4 py-4">
                                        <div>
                                            <p class="text-sm text-slate-300">Pastikan nilai seluruh hakim sudah lengkap sebelum disimpan.</p>
                                            <p class="mt-1 text-xs text-slate-500">Satu kali simpan akan membuat entri nilai untuk semua hakim pada babak ini.</p>
                                        </div>
                                        <div class="rounded-[1rem] border border-slate-800 bg-slate-950/60 px-3 py-2 text-right">
                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Kelengkapan Batch</p>
                                            <p class="mt-1 text-sm font-bold text-white" x-text="completionSummary()"></p>
                                        </div>
                                        <button type="button" class="primary-button justify-center px-5 py-3" x-on:click="openPreview()" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-60 cursor-not-allowed' : ''">
                                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                            <span x-show="!isSubmitting">Pratinjau Sebelum Simpan</span>
                                            <span x-show="isSubmitting">Menyimpan...</span>
                                        </button>
                                    </div>

                                    <div x-show="previewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6">
                                        <div class="absolute inset-0" x-on:click="closePreview()"></div>
                                        <div class="relative z-10 max-h-[88vh] w-full max-w-5xl overflow-hidden rounded-[1.75rem] border border-cyan-400/16 bg-slate-950 shadow-[0_28px_90px_-40px_rgba(34,211,238,0.45)]">
                                            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-800 px-6 py-5">
                                                <div>
                                                    <p class="section-kicker">Pratinjau Nilai</p>
                                                    <h3 class="mt-2 text-2xl font-bold text-white">Cek kembali sebelum disimpan</h3>
                                                    <p class="mt-2 text-sm text-slate-300" x-text="selectedParticipantLabel()"></p>
                                                </div>
                                                <button type="button" class="secondary-button px-4 py-2" x-on:click="closePreview()">
                                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                                    Kembali Edit
                                                </button>
                                            </div>

                                            <div class="max-h-[60vh] overflow-y-auto px-6 py-5">
                                                <!-- Summary: Total Nilai -->
                                                <div class="mb-6 rounded-2xl border-2 border-cyan-400/30 bg-gradient-to-br from-cyan-500/15 to-sky-500/10 p-5 shadow-[0_12px_40px_-20px_rgba(34,211,238,0.3)]">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Total Nilai</p>
                                                            <p class="mt-1 text-xs text-slate-400">Jumlah semua poin / jumlah poin</p>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="text-4xl font-black text-white" x-text="calculateTotalScore()"></p>
                                                            <p class="mt-1 text-sm text-cyan-200" x-text="`dari ${judgeNames.length} hakim`"></p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Rincian Poin per Hakim -->
                                                <h4 class="mb-3 text-sm font-semibold text-slate-300">Rincian Nilai per Hakim</h4>
                                                <div class="grid gap-4 xl:grid-cols-2 mb-6">
                                                    <template x-for="(judgeData, judgeIdx) in previewData" :key="judgeData.name">
                                                        <section class="rounded-[1.35rem] border border-slate-700/50 bg-slate-900/40 p-4">
                                                            <div class="flex items-center justify-between gap-3 mb-3">
                                                                <div>
                                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Hakim</p>
                                                                    <h4 class="mt-1 text-base font-bold text-white" x-text="judgeData.name"></h4>
                                                                </div>
                                                            </div>
                                                            <div class="grid gap-2">
                                                                <template x-for="item in judgeData.scores" :key="item.label">
                                                                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2">
                                                                        <span class="text-sm text-slate-300" x-text="item.label"></span>
                                                                        <span class="text-sm font-bold text-white" x-text="item.value"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </section>
                                                    </template>
                                                </div>

                                                <!-- Total per Poin -->
                                                <h4 class="mb-3 text-sm font-semibold text-slate-300">Total per Poin (Jumlah Semua Hakim)</h4>
                                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                    <template x-for="item in previewPointTotals" :key="item.label">
                                                        <div class="rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3">
                                                            <div class="flex items-center justify-between gap-2">
                                                                <span class="text-xs text-emerald-200" x-text="item.label"></span>
                                                                <span class="text-lg font-bold text-emerald-300" x-text="item.total"></span>
                                                            </div>
                                                            <p class="mt-1 text-[10px] text-emerald-200/60" x-text="`(${judgeNames.length} hakim)`"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 px-6 py-5">
                                                <p class="text-sm text-slate-300">Jika semua sudah benar, lanjutkan simpan ke sistem.</p>
                                                <div class="flex flex-wrap gap-3">
                                                    <button type="button" class="secondary-button px-5 py-3" x-on:click="closePreview()">
                                                        Edit Lagi
                                                    </button>
                                                    <button type="button" class="primary-button px-5 py-3" x-on:click="submitConfirmed()">
                                                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                        Ya, Simpan Nilai
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </fieldset>
                                </form>

                                        <div x-show="correctionRequestOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6">
                                        <div class="absolute inset-0" x-on:click="correctionRequestOpen = false"></div>
                                        <div class="relative z-10 max-h-[88vh] w-full max-w-6xl overflow-hidden rounded-[1.75rem] border border-amber-400/18 bg-slate-950 shadow-[0_28px_90px_-40px_rgba(251,191,36,0.45)]">
                                            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-800 px-6 py-5">
                                                <div>
                                                    <p class="section-kicker">Request Perbaikan</p>
                                                    <h3 class="mt-2 text-2xl font-bold text-white">Masukkan nilai baru untuk dikirim ke admin</h3>
                                                    <p class="mt-2 text-sm text-slate-300" x-text="`${correctionRequestName || '-'} | Lot ${correctionRequestLot || '-'} | ${correctionRequestRound || '-'}`"></p>
                                                </div>
                                                <button type="button" class="secondary-button px-4 py-2" x-on:click="correctionRequestOpen = false">
                                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                                    Tutup
                                                </button>
                                            </div>

                                            <form method="POST" action="<?= e(route('scoring.corrections.store')) ?>" class="max-h-[70vh] overflow-y-auto px-6 py-5">
                                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="participant_id" value="<?= e($selectedParticipant->id) ?>">
                                                <input type="hidden" name="judging_round" value="<?= e($participantScoreRound) ?>">

                                                <div class="rounded-[1.25rem] border border-amber-400/16 bg-amber-400/10 px-4 py-3 text-sm text-amber-50">
                                                    Nilai utama sudah terkunci. Isi usulan nilai baru di bawah ini agar admin bisa meninjau perbaikannya.
                                                </div>

                                                <div class="mt-4 space-y-4">
                                                    <?php foreach ($judgeNames as $index => $judgeName): ?>
                                                        <section class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 p-4">
                                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                                <div>
                                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Hakim <?= e($index + 1) ?></p>
                                                                    <h4 class="mt-1 text-lg font-bold text-white"><?= e($judgeName) ?></h4>
                                                                </div>
                                                                <div class="rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1 text-xs font-semibold text-slate-200">Usulan nilai baru</div>
                                                            </div>

                                                            <!-- Info Pemberitahuan (single line, bigger) -->
                                                            <div class="flex items-center gap-3 rounded-[1rem] border border-amber-400/30 bg-gradient-to-r from-amber-500/15 to-orange-500/10 px-5 py-4">
                                                                <div class="shrink-0">
                                                                    <?= mtq_icon('info', 'h-5 w-5 text-amber-300') ?>
                                                                </div>
                                                                <div class="flex-1 text-sm text-amber-50">
                                                                    <span class="font-semibold text-amber-100">Penting:</span>
                                                                    Bilangan berkoma pakai <span class="font-bold text-amber-100">titik (.)</span> ·
                                                                    Nilai kosong diisi <span class="font-bold text-amber-100">nol (0)</span>
                                                                </div>
                                                            </div>

                                                            <!-- Poin Penilaian -->
                                                            <div class="rounded-[1.1rem] border-2 border-amber-400/30 bg-gradient-to-br from-amber-500/8 to-orange-500/5 p-4 shadow-[0_8px_30px_-12px_rgba(251,191,36,0.2)]">
                                                                <div class="flex items-center gap-2 mb-3">
                                                                    <?= mtq_icon('spark', 'h-4 w-4 text-amber-300') ?>
                                                                    <h4 class="text-sm font-bold text-amber-100 uppercase tracking-[0.18em]">Poin Penilaian</h4>
                                                                </div>
                                                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                                    <?php foreach ($criteria as $key => $label): ?>
                                                                        <div class="rounded-[1rem] border border-amber-400/20 bg-slate-950/80 p-3 shadow-[0_4px_16px_-8px_rgba(251,191,36,0.12)]">
                                                                            <label class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">
                                                                                <?= e($label) ?>
                                                                            </label>
                                                                            <input
                                                                                name="scores[<?= e($judgeIds[$index] ?? '') ?>][<?= e($key) ?>]"
                                                                                type="number"
                                                                                min="0"
                                                                                max="100"
                                                                                step="0.01"
                                                                                x-bind:value="correctionRequestDraft?.[<?= e(json_encode($judgeName)) ?>]?.scores?.[<?= e(json_encode($key)) ?>] ?? ''"
                                                                                class="w-full rounded-xl border-2 border-amber-400/30 bg-slate-900/90 px-3.5 py-3 text-center text-lg font-bold text-white outline-none transition focus:border-amber-300 focus:ring-4 focus:ring-amber-400/20 focus:bg-slate-900"
                                                                                placeholder="0">
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>

                                                            <!-- Catatan -->
                                                            <div>
                                                                <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan <?= e($judgeName) ?></label>
                                                                <textarea
                                                                    name="remarks[<?= e($judgeIds[$index] ?? '') ?>]"
                                                                    rows="2"
                                                                    class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-amber-300 focus:ring-2 focus:ring-amber-400/20"
                                                                    placeholder="Opsional, jelaskan alasan perbaikan nilai."
                                                                    x-bind:value="correctionRequestDraft?.[<?= e(json_encode($judgeName)) ?>]?.remarks ?? ''"></textarea>
                                                            </div>
                                                        </section>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div class="mt-4">
                                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Catatan Request</label>
                                                    <textarea
                                                        name="note"
                                                        rows="3"
                                                        class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-amber-300 focus:ring-2 focus:ring-amber-400/20"
                                                        placeholder="Opsional, misalnya alasan koreksi atau instruksi untuk admin."></textarea>
                                                </div>

                                                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 pt-4">
                                                    <p class="text-sm text-slate-300">Request akan disimpan sebagai permintaan perbaikan dan menunggu tindak lanjut admin.</p>
                                                    <div class="flex flex-wrap gap-3">
                                                        <button type="button" class="secondary-button px-5 py-3" x-on:click="correctionRequestOpen = false">Batal</button>
                                                        <button type="submit" class="primary-button px-5 py-3">
                                                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                            Kirim Request
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
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
                judgeModalOpen: null,
                judgeSearchQuery: '',
                init() {
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
                        this.rounds[roundKey] = round;
                        this.rounds[roundKey].scoringPoints = this.rounds[roundKey].scoringPoints
                            .map((value) => String(value ?? '').trim())
                            .filter(Boolean);
                        if (!this.rounds[roundKey].scoringPoints.length) {
                            this.rounds[roundKey].scoringPoints = [''];
                        }
                    });
                    if (!Object.prototype.hasOwnProperty.call(this.rounds, this.activeRound)) {
                        this.activeRound = Object.keys(this.rounds)[0] ?? 'penyisihan';
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
                },
                addPoint(roundKey) {
                    this.rounds[roundKey].scoringPoints.push('');
                },
                removePoint(roundKey, index) {
                    if (this.rounds[roundKey].scoringPoints.length <= 1) {
                        return;
                    }

                    this.rounds[roundKey].scoringPoints.splice(index, 1);
                },
                movePointUp(roundKey, index) {
                    if (index <= 0) {
                        return;
                    }

                    const points = this.rounds[roundKey].scoringPoints;
                    const current = points[index];
                    points[index] = points[index - 1];
                    points[index - 1] = current;
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

                    return scoreInputs.every((input) => String(input.value ?? '').trim() !== '');
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
                        const scores = scoreInputs.map((input) => ({
                            label: input.dataset.scoreLabel || 'Poin',
                            value: input.value || '0',
                            numericValue: Number(input.value || 0),
                        }));
                        const remarksField = panel ? panel.querySelector('textarea') : null;

                        return {
                            name: judgeName,
                            scores,
                            remarks: remarksField && String(remarksField.value || '').trim() !== '' ? remarksField.value.trim() : '',
                        };
                    });

                    // Calculate point totals (sum of all judges per point)
                    if (judgeData.length > 0 && judgeData[0].scores.length > 0) {
                        const pointTotals = judgeData[0].scores.map((score, idx) => {
                            let sum = 0;
                            judgeData.forEach(judge => {
                                sum += judge.scores[idx]?.numericValue || 0;
                            });
                            return {
                                label: score.label,
                                total: sum.toFixed(2),
                                numericTotal: sum,
                            };
                        });
                        this.previewPointTotals = pointTotals;
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
                    const sum = this.previewPointTotals.reduce((acc, pt) => acc + pt.numericTotal, 0);
                    const avg = sum / this.previewPointTotals.length;
                    return avg.toFixed(2);
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
