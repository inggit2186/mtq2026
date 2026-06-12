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
$setupCreated = $setupCreated ?? $setupReady;
$setupEditable = $setupEditable ?? false;
$setupRequested = $setupRequested ?? false;
$participantHasScores = $participantHasScores ?? false;
$participantScoreRound = $participantScoreRound ?? $selectedJudgingRound;
$participantScoreDraft = $participantScoreDraft ?? [];
$initialStep = (int) ($initialStep ?? 1);
$initialJudgeIndex = (int) ($initialJudgeIndex ?? 0);
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
        'judge_names' => [$user?->name],
        'scoring_points' => $defaultCriteria,
    ];
    $judgeNamesValue = preg_split('/\r\n|\r|\n/', (string) old('rounds.'.$roundKey.'.judge_names_text', implode("\n", $roundConfig['judge_names'] ?? [$user?->name]))) ?: [];
    $judgeNamesValue = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $judgeNamesValue)));
    if ($judgeNamesValue === []) {
        $judgeNamesValue = [(string) ($user?->name ?? 'Hakim 1')];
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
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Ruang Penilaian</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Status Operator</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($user?->name) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Operator wajib menyiapkan konfigurasi penilaian sebelum sesi dimulai.</p>
                    <div class="mt-4 <?= $setupReady ? 'status-pill' : 'inline-flex items-center gap-2 rounded-full border border-amber-400/18 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-100' ?>">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full <?= $setupReady ? 'bg-emerald-300' : 'bg-amber-300' ?>"></span>
                        <?= $setupReady ? 'Setting Siap' : 'Butuh Setup' ?>
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Prioritas</p>
                        <p class="mt-2 text-sm font-semibold text-white">Setting lebih dulu, nilai kemudian</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Pastikan jumlah hakim, nama hakim, babak, dan poin penilaian sudah final sebelum peserta dipilih.</p>
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
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Ruang Penilaian</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Panel operator untuk <?= e($user?->roleLabel()) ?></h2>
                            <p class="mt-2 text-sm text-slate-300">Operator menyiapkan setting penilaian per golongan, lalu baru menginput nilai peserta.</p>
                            <?php if ($user?->role === 'panitia'): ?>
                                <p class="mt-2 text-xs leading-6 text-cyan-200">Golongan yang bisa diakses akun ini: <?= e($restrictedCategories !== [] ? implode(', ', $restrictedCategories) : 'belum diatur admin') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="<?= $setupReady ? 'status-pill' : 'inline-flex items-center gap-2 rounded-full border border-amber-400/18 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-100' ?>">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full <?= $setupReady ? 'bg-emerald-300' : 'bg-amber-300' ?>"></span>
                            <?= $setupReady ? 'Penilaian Siap' : 'Setup Belum Lengkap' ?>
                        </div>
                        <div x-data="{ liveConnected: window.Alpine?.store('ui')?.liveConnected ?? false, pollingActive: window.Alpine?.store('ui')?.pollingActive ?? false }"
                             x-init="$watch('$store.ui.liveConnected', v => liveConnected = v); $watch('$store.ui.pollingActive', v => pollingActive = v);"
                             class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em]"
                             :class="liveConnected ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : (pollingActive ? 'border-amber-400/20 bg-amber-400/10 text-amber-100' : 'border-slate-700 bg-slate-900/70 text-slate-400')">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full"
                                  :class="liveConnected ? 'bg-emerald-300' : (pollingActive ? 'bg-amber-300 animate-pulse' : 'bg-slate-500')"></span>
                            <span x-text="liveConnected ? 'Live' : (pollingActive ? 'Polling' : 'Offline')"></span>
                        </div>
                        <?php if ($selectedCategory): ?>
                            <a href="<?= e($bigScreenUrl) ?>" target="_blank" rel="noreferrer" class="secondary-button">
                                <?= mtq_icon('eye', 'h-4 w-4') ?>
                                Big Screen Golongan
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="grid w-full gap-3 md:grid-cols-2">
                        <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Golongan Aktif</p>
                            <p class="mt-1 text-sm font-semibold text-white"><?= e($selectedCategory ? trim(($selectedCategory->branch ?? '-').' | '.($selectedCategory->name ?? '-')) : 'Belum dipilih') ?></p>
                        </div>
                        <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Babak Aktif</p>
                            <p class="mt-1 text-sm font-semibold text-white"><?= e($selectedJudgingRound) ?></p>
                        </div>
                    </div>
                </header>

                <section class="glass-card rounded-[2rem] p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="section-kicker">Alur Penilaian</p>
                            <h2 class="mt-2 text-2xl font-bold text-white">Tiga langkah, satu layar kerja</h2>
                            <p class="mt-2 text-sm text-slate-300">Pilih golongan dulu, atur babak, lalu buka form penilaian. MFQ tersedia dari langkah pertama supaya operator tetap masuk dari satu pintu.</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-800 bg-slate-950/60 px-4 py-3 text-right">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Langkah aktif</p>
                            <p class="mt-1 text-xl font-bold text-white" x-text="stepLabel(currentStep)"></p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 lg:grid-cols-3">
                        <button type="button" class="rounded-[1.35rem] border px-4 py-4 text-left transition"
                            :class="currentStep === 1 ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_14px_40px_-28px_rgba(34,211,238,0.7)]' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30'"
                            x-on:click="goToStep(1)">
                            <p class="text-xs uppercase tracking-[0.24em]" :class="currentStep === 1 ? 'text-cyan-100' : 'text-slate-500'">Step 1</p>
                            <p class="mt-2 text-lg font-bold text-white">Pilih cabang / golongan</p>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Masuk dari filter golongan utama, dan lihat juga jalur MFQ di kartu khusus.</p>
                        </button>
                        <button type="button" class="rounded-[1.35rem] border px-4 py-4 text-left transition"
                            :class="currentStep === 2 ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_14px_40px_-28px_rgba(34,211,238,0.7)]' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30'"
                            x-on:click="goToStep(<?= $setupCreated ? ($setupEditable ? 2 : 3) : 2 ?>)">
                            <p class="text-xs uppercase tracking-[0.24em]" :class="currentStep === 2 ? 'text-cyan-100' : 'text-slate-500'">Step 2</p>
                            <p class="mt-2 text-lg font-bold text-white">Tab setting babak</p>
                            <p class="mt-2 text-sm leading-6 text-slate-300"><?= $setupCreated ? ($setupEditable ? 'Setting sedang terbuka lagi untuk diperbarui.' : 'Setting sudah tersimpan, klik untuk lanjut ke peserta.') : 'Rapikan hakim dan poin per babak sebelum masuk form penilaian.' ?></p>
                        </button>
                        <button type="button" class="rounded-[1.35rem] border px-4 py-4 text-left transition"
                            :class="currentStep === 3 ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_14px_40px_-28px_rgba(34,211,238,0.7)]' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30'"
                            x-on:click="goToStep(3)">
                            <p class="text-xs uppercase tracking-[0.24em]" :class="currentStep === 3 ? 'text-cyan-100' : 'text-slate-500'">Step 3</p>
                            <p class="mt-2 text-lg font-bold text-white">Form penilaian</p>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Isi nilai hakim per peserta, lalu simpan tanpa pindah halaman.</p>
                        </button>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Siap Dinilai</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($scoreStats['participant_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Peserta Terverifikasi</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($scoreStats['verified_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('shield') ?></div><p class="mt-4 text-sm text-slate-400">Hakim Tersetting</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($scoreStats['judge_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Poin Penilaian</p><p class="mt-2 text-3xl font-extrabold text-emerald-300"><?= e($scoreStats['criteria_total']) ?></p></div>
                </section>

                <section id="step-1" class="glass-card rounded-[2rem] p-6" x-show="currentStep === 1" x-cloak>
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="section-kicker">Step 1</p>
                            <h2 class="text-2xl font-bold text-white">Pilih cabang dan golongan MTQ</h2>
                            <p class="mt-2 text-sm text-slate-300">Pilih cabang besar terlebih dahulu. Setelah itu baru pilih golongan di dalam cabang tersebut. Jika ada golongan tanpa cabang, pilihannya tampil langsung di awal.</p>
                        </div>
                        <div class="<?= $setupEditable ? 'status-pill border-cyan-300/20 bg-cyan-400/10 text-cyan-100' : ($setupRequested ? 'status-pill border-amber-300/20 bg-amber-400/10 text-amber-100' : 'status-pill') ?>">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full <?= $selectedCategory ? 'bg-emerald-300' : 'bg-amber-300' ?>"></span>
                            <?= $setupEditable ? 'Setting dibuka' : ($setupRequested ? 'Menunggu admin' : ($selectedCategory ? 'Golongan dipilih' : 'Pilih golongan')) ?>
                        </div>
                    </div>

                    <div class="mt-6 space-y-6">
                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/55 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="section-kicker">Babak Golongan</p>
                                    <h3 class="mt-2 text-xl font-bold text-white">Pilih tab Penyisihan atau Final dulu</h3>
                                    <p class="mt-2 text-sm text-slate-300">Kartu golongan di bawah akan menyesuaikan tab babak yang sedang aktif.</p>
                                </div>
                                <div class="rounded-[1.4rem] border border-slate-800 bg-slate-950/80 p-1.5">
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($judgingRounds as $roundLabel): ?>
                                            <a href="<?= e(route('scoring', array_filter([
                                                'branch' => $filters['branch'] ?? null,
                                                'keyword' => $filters['keyword'] ?? null,
                                                'competition_category_id' => $filters['competition_category_id'] ?? null,
                                                'judging_round' => $roundLabel,
                                                'step' => 1,
                                            ]))) ?>"
                                                class="rounded-[1rem] px-4 py-3 text-left text-sm font-semibold transition <?= $selectedJudgingRound === $roundLabel ? 'bg-cyan-400/15 text-cyan-100 shadow-[0_12px_30px_-18px_rgba(34,211,238,0.7)]' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' ?>">
                                                <span class="flex items-center gap-2">
                                                    <span><?= e($roundLabel) ?></span>
                                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] <?= $selectedJudgingRound === $roundLabel ? 'border-cyan-300/20 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-900/70 text-slate-400' ?>">
                                                        Pilih
                                                    </span>
                                                </span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-6">
                            <div class="rounded-[1.75rem] border border-slate-800 bg-slate-950/55 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p class="section-kicker">Cari Golongan</p>
                                        <h3 class="mt-2 text-xl font-bold text-white">Pilih cabang seperti saat pendaftaran</h3>
                                        <p class="mt-2 text-sm text-slate-300">Gunakan tab cabang di bawah untuk mempercepat pencarian golongan pada babak <?= e($selectedJudgingRound) ?>.</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border border-slate-800 bg-slate-950/70 px-4 py-3 text-right">
                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Golongan babak ini</p>
                                        <p class="mt-1 text-2xl font-black text-white"><?= e($selectedRoundCategories->count()) ?></p>
                                    </div>
                                </div>

                                <form method="GET" action="<?= e(route('scoring')) ?>" class="mt-5 grid gap-4">
                                    <input type="hidden" name="judging_round" value="<?= e($selectedJudgingRound) ?>">
                                    <input type="hidden" name="step" value="1">
                                    <div class="grid gap-4 md:grid-cols-[1fr_auto]">
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Cari cabang atau golongan</label>
                                            <input name="keyword" value="<?= e($filters['keyword'] ?? '') ?>" type="text" placeholder="Ketik nama cabang, golongan, atau keterangan" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                        </div>
                                        <div class="flex items-end">
                                            <button type="submit" class="secondary-button px-5 py-3">
                                                <?= mtq_icon('search', 'h-4 w-4') ?>
                                                Cari
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <a href="<?= e(route('scoring', array_filter([
                                            'keyword' => $filters['keyword'] ?? null,
                                            'competition_category_id' => $filters['competition_category_id'] ?? null,
                                            'judging_round' => $selectedJudgingRound,
                                            'step' => 1,
                                        ]))) ?>"
                                            class="rounded-full border px-4 py-2 text-sm font-semibold transition <?= filled($filters['branch'] ?? null) ? 'border-slate-700 bg-slate-950/70 text-slate-300 hover:border-cyan-400/30 hover:text-white' : 'border-cyan-300/30 bg-cyan-400/10 text-cyan-100' ?>">
                                            Semua cabang
                                        </a>
                                        <?php foreach ($branches as $branch): ?>
                                            <?php $branchCount = $selectedRoundCategories->where('branch', $branch)->count(); ?>
                                            <a href="<?= e(route('scoring', array_filter([
                                                'branch' => $branch,
                                                'keyword' => $filters['keyword'] ?? null,
                                                'competition_category_id' => $filters['competition_category_id'] ?? null,
                                                'judging_round' => $selectedJudgingRound,
                                                'step' => 1,
                                            ]))) ?>"
                                                class="rounded-full border px-4 py-2 text-sm font-semibold transition <?= (string) ($filters['branch'] ?? '') === (string) $branch ? 'border-cyan-300/30 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-950/70 text-slate-300 hover:border-cyan-400/30 hover:text-white' ?>">
                                                <?= e($branch) ?> <span class="ml-1 text-xs opacity-70"><?= e($branchCount) ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </form>

                                <div class="mt-6 space-y-6">
                                    <?php if ($selectedRoundCategories->isEmpty()): ?>
                                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/55 px-4 py-5 text-sm text-slate-300">Tidak ada golongan yang cocok untuk babak <?= e($selectedJudgingRound) ?>.</div>
                                    <?php else: ?>
                                        <?php foreach ($selectedRoundBranchGroups as $branchName => $categoryGroup): ?>
                                            <?php
                                                if (filled($filters['branch'] ?? null) && (string) $filters['branch'] !== (string) $branchName) {
                                                    continue;
                                                }
                                            ?>
                                            <div class="rounded-[1.75rem] border border-slate-800 bg-slate-950/45 p-5">
                                                <div class="flex flex-wrap items-center justify-between gap-3">
                                                    <div>
                                                        <p class="text-xs uppercase tracking-[0.18em] text-cyan-200/80">Cabang</p>
                                                        <h4 class="mt-1 text-xl font-bold text-white"><?= e($branchName) ?></h4>
                                                        <p class="mt-1 text-sm text-slate-400"><?= e($categoryGroup->count()) ?> golongan tersedia pada babak ini.</p>
                                                    </div>
                                                    <span class="status-pill border-cyan-400/20 bg-cyan-400/10 text-cyan-100">
                                                        <?= e($categoryGroup->count()) ?> kartu
                                                    </span>
                                                </div>

                                                <div class="mt-5 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
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
                                                            $remainingSlots = (int) ($categoryCardUsage['remaining_slots'] ?? max($availableSlots - $registered, 0));
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
                                                            class="group overflow-hidden rounded-[1.75rem] border border-slate-700/80 bg-slate-900/80 text-left transition duration-200 hover:-translate-y-1 hover:border-cyan-300/40 hover:shadow-[0_18px_55px_-28px_rgba(34,211,238,0.55)] <?= $isSelectedCard ? 'ring-2 ring-cyan-300/30 border-cyan-300/60' : '' ?>">
                                                            <div class="aspect-[16/9] overflow-hidden bg-slate-950/70">
                                                                <img src="<?= e($categoryVisual) ?>" alt="<?= e($categoryCard->name) ?>" loading="lazy" decoding="async" class="h-full w-full object-contain p-2">
                                                            </div>
                                                            <div class="space-y-3 p-5">
                                                                <div class="flex items-start justify-between gap-3">
                                                                    <div>
                                                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Golongan MTQ</p>
                                                                        <h4 class="mt-2 text-lg font-bold text-white"><?= e($categoryCard->name) ?></h4>
                                                                    </div>
                                                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?= $isMfqCard ? 'border-amber-400/20 bg-amber-400/10 text-amber-200' : 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200' ?>">
                                                                        <?= $isMfqCard ? 'MFQ' : e($availableSlots).' slot' ?>
                                                                    </span>
                                                                </div>

                                                                <div class="flex flex-wrap gap-2">
                                                                    <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] <?= $cardSetupEditable ? 'border-cyan-300/20 bg-cyan-400/10 text-cyan-100' : ($cardSetupRequested ? 'border-amber-300/20 bg-amber-400/10 text-amber-100' : ($cardSetupCreated ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border-slate-700 bg-slate-950/70 text-slate-400')) ?>">
                                                                        <?= $cardSetupEditable ? 'Setting dibuka' : ($cardSetupRequested ? 'Request terkirim' : ($cardSetupCreated ? 'Setting tersimpan' : 'Belum disiapkan')) ?>
                                                                    </span>
                                                                    <?php if ($isSelectedCard): ?>
                                                                        <span class="inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100">
                                                                            Aktif
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <div class="grid grid-cols-3 gap-3 rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                                                                    <div>
                                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Slot</p>
                                                                        <p class="mt-1 text-lg font-bold text-white"><?= e($availableSlots) ?></p>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Terdaftar</p>
                                                                        <p class="mt-1 text-lg font-bold text-cyan-200"><?= e($registered) ?></p>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Tersisa</p>
                                                                        <p class="mt-1 text-lg font-bold <?= $remainingSlots > 0 ? 'text-emerald-300' : 'text-rose-200' ?>"><?= e($remainingSlots) ?></p>
                                                                    </div>
                                                                </div>

                                                                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-3">
                                                                    <p class="text-xs text-slate-400">
                                                                        <?= $cardSetupEditable
                                                                            ? 'Step 2 terbuka kembali.'
                                                                            : ($cardSetupRequested
                                                                                ? 'Menunggu admin membuka Step 2.'
                                                                                : ($cardSetupReady
                                                                                    ? 'Siap lanjut ke Step 3.'
                                                                                    : ($cardSetupCreated
                                                                                        ? 'Setting tersimpan, siap dipakai.'
                                                                                        : 'Belum ada setting babak.'))) ?>
                                                                    </p>
                                                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300">
                                                                        <?= $isMfqCard ? 'Buka MFQ' : 'Klik kartu' ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
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
                                                    <p class="text-xs text-slate-400">Tambah atau hapus hakim sesuai kebutuhan babak ini.</p>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1 text-xs font-semibold text-slate-200">
                                                        <span x-text="roundJudgeCount('<?= e($roundKey) ?>')"></span> hakim
                                                    </span>
                                                    <button type="button" class="secondary-button rounded-xl px-3 py-2 text-xs" x-on:click="addJudge('<?= e($roundKey) ?>')">
                                                        <?= mtq_icon('plus', 'h-4 w-4') ?>
                                                        Tambah Hakim
                                                    </button>
                                                </div>
                                            </div>
                                            <input type="hidden" name="rounds[<?= e($roundKey) ?>][judge_count]" :value="rounds.<?= e($roundKey) ?>.judgeCount">
                                            <div class="mt-4 space-y-3">
                                                <template x-for="(judgeName, index) in rounds.<?= e($roundKey) ?>.judgeNames" :key="'<?= e($roundKey) ?>-judge-' + index">
                                                    <div class="rounded-[1.4rem] border border-slate-800 bg-slate-950/60 p-4">
                                                        <div class="flex items-start gap-3">
                                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-cyan-400/18 bg-cyan-400/10 text-sm font-bold text-cyan-100" x-text="index + 1"></div>
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex flex-wrap items-center justify-between gap-3">
                                                                    <div>
                                                                        <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500" x-text="'Hakim ' + (index + 1)"></label>
                                                                        <p class="mt-1 text-xs text-slate-400">Nama harus unik dalam satu babak.</p>
                                                                    </div>
                                                                    <button type="button"
                                                                        class="secondary-button rounded-xl px-3 py-2 text-xs"
                                                                        x-on:click="removeJudge('<?= e($roundKey) ?>', index)"
                                                                        x-bind:disabled="rounds.<?= e($roundKey) ?>.judgeNames.length <= 1"
                                                                        :class="rounds.<?= e($roundKey) ?>.judgeNames.length <= 1 ? 'cursor-not-allowed opacity-50' : ''">
                                                                        <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                                        Hapus
                                                                    </button>
                                                                </div>
                                                                <input type="text"
                                                                    x-model="rounds.<?= e($roundKey) ?>.judgeNames[index]"
                                                                    class="mt-3 w-full rounded-2xl border bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                                                    :class="judgeNameState('<?= e($roundKey) ?>', index).valid ? 'border-slate-700' : 'border-rose-400/50 focus:border-rose-300 focus:ring-rose-400/20'"
                                                                    :placeholder="'Nama hakim ' + (index + 1)">
                                                                <p x-show="!judgeNameState('<?= e($roundKey) ?>', index).valid" x-text="judgeNameState('<?= e($roundKey) ?>', index).message" class="mt-2 text-xs text-rose-200"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <p x-show="hasJudgeNameIssues('<?= e($roundKey) ?>')" class="mt-3 text-xs text-rose-200">Semua nama hakim babak <?= e($roundLabel) ?> wajib terisi dan tidak boleh ada yang sama.</p>
                                        </div>

                                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/50 p-4">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Poin yang dinilai</label>
                                            <div class="space-y-3">
                                                <template x-for="(pointLabel, index) in rounds.<?= e($roundKey) ?>.scoringPoints" :key="'<?= e($roundKey) ?>-point-' + index">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-emerald-400/18 bg-emerald-400/10 text-sm font-bold text-emerald-100" x-text="index + 1"></div>
                                                        <input type="text" x-model="rounds.<?= e($roundKey) ?>.scoringPoints[index]" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" :placeholder="'Poin penilaian ' + (index + 1)">
                                                        <button type="button" class="secondary-button rounded-xl px-3 py-2 text-xs" x-on:click="movePointUp('<?= e($roundKey) ?>', index)" x-bind:disabled="index === 0">
                                                            Naik
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

                <section id="step-3" class="grid gap-6 xl:grid-cols-[0.48fr_1.52fr]" x-show="currentStep === 3" x-cloak>
                    <div class="glass-card rounded-[2rem] p-6"
                        x-data="participantPicker({
                            participants: <?= e(json_encode($participantOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            selectedId: <?= e(json_encode((string) ($selectedParticipant?->id ?? ''))) ?>,
                        })">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('users') ?></div>
                            <div>
                                <h2 class="text-2xl font-bold text-white">Peserta Siap Dinilai</h2>
                                <p class="mt-1 text-sm text-slate-300">Cari peserta lalu pilih dari daftar agar tetap ringan walau data peserta banyak.</p>
                            </div>
                        </div>
                        <div class="status-pill mt-4">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?= e(count($participants)) ?> Peserta
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
                                            <div class="flex flex-wrap gap-3">
                                                <button type="button" class="secondary-button px-4 py-3" x-on:click="resetSearch()" :disabled="!search && !selectedParticipant" x-bind:class="!search && !selectedParticipant ? 'cursor-not-allowed opacity-60' : ''">
                                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                                    Bersihkan
                                                </button>
                                                <button type="button" class="primary-button px-4 py-3" x-on:click="goToSelected()" :disabled="!selectedParticipant" x-bind:class="!selectedParticipant ? 'cursor-not-allowed opacity-60' : ''">
                                                    <?= mtq_icon('chart', 'h-4 w-4') ?>
                                                    Aktifkan Peserta
                                                </button>
                                            </div>
                                </div>
                                <div class="mt-5 rounded-[1.5rem] border border-slate-800 bg-slate-950/45 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">Peserta Terpilih</p>
                                            <h3 class="mt-1 text-lg font-bold text-white" x-text="selectedParticipant ? selectedParticipant.name : 'Belum ada peserta dipilih'"></h3>
                                        </div>
                                        <template x-if="selectedParticipant">
                                            <div class="status-pill">
                                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                                <span x-text="selectedParticipant.lot_number"></span>
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
                                <form method="POST" action="<?= e(route('scoring.store')) ?>" class="mt-6 grid gap-4 <?= $participantHasScores ? 'pointer-events-none opacity-45' : '' ?>"
                                    x-data="judgeBatchForm({
                                        judgeNames: <?= e(json_encode(array_values($judgeNames), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
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

                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/40 p-3">
                                        <div id="judge_name_field" class="flex gap-3 overflow-x-auto pb-1 pr-1 scroll-smooth [scrollbar-width:thin] [scrollbar-color:rgba(34,211,238,0.55)_transparent]">
                                            <?php foreach ($judgeNames as $index => $judgeName): ?>
                                                <button type="button"
                                                    class="min-w-[210px] flex-1 rounded-[1.2rem] border px-4 py-3 text-left transition sm:min-w-[230px] lg:min-w-[250px]"
                                                    :class="activeJudgeIndex === <?= e($index) ?> ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_18px_35px_-24px_rgba(34,211,238,0.7)]' : 'border-slate-800 bg-slate-950/45 hover:border-cyan-400/25 hover:bg-slate-950/70'"
                                                    x-on:click="goToJudge(<?= e($index) ?>)">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <p class="text-[11px] uppercase tracking-[0.18em]" :class="activeJudgeIndex === <?= e($index) ?> ? 'text-cyan-200' : 'text-slate-500'">Hakim <?= e($index + 1) ?></p>
                                                            <p class="mt-1 truncate text-sm font-semibold text-white"><?= e($judgeName) ?></p>
                                                        </div>
                                                        <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full"
                                                            :class="judgeStatusDotClass(<?= e($index) ?>)"
                                                            :title="judgeStatusLabel(<?= e($index) ?>)"></span>
                                                    </div>
                                                    <div class="mt-3 flex items-center justify-between gap-3">
                                                        <p class="text-xs text-slate-400" :class="activeJudgeIndex === <?= e($index) ?> ? 'text-cyan-100/80' : 'text-slate-500'">
                                                            Klik untuk buka panel nilai
                                                        </p>
                                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]"
                                                            :class="activeJudgeIndex === <?= e($index) ?> ? 'border-cyan-300/20 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-900/70 text-slate-400'">
                                                            Panel
                                                        </span>
                                                    </div>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/45 p-3">
                                        <?php foreach ($judgeNames as $index => $judgeName): ?>
                                            <section x-show="activeJudgeIndex === <?= e($index) ?>" x-cloak data-judge-panel="<?= e($index) ?>" class="space-y-4">
                                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.1rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
                                                    <div>
                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Panel Nilai</p>
                                                        <h3 class="mt-1 text-xl font-bold text-white"><?= e($judgeName) ?></h3>
                                                    </div>
                                                    <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                                        Hakim <?= e($index + 1) ?> dari <?= e(count($judgeNames)) ?>
                                                    </div>
                                                </div>

                                                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                                    <?php foreach ($criteria as $key => $label): ?>
                                                        <div class="rounded-[1.1rem] border border-slate-800 bg-slate-950/55 p-3">
                                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400"><?= e($label) ?></label>
                                                            <input
                                                                name="scores[<?= e($judgeName) ?>][<?= e($key) ?>]"
                                                                data-score-label="<?= e($label) ?>"
                                                                type="number"
                                                                min="0"
                                                                max="100"
                                                                step="0.01"
                                                                value="<?= e(data_get(old('scores', []), $judgeName.'.'.$key)) ?>"
                                                                class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3.5 py-2.5 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                                                placeholder="0 - 100">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Catatan <?= e($judgeName) ?></label>
                                                    <textarea
                                                        name="remarks[<?= e($judgeName) ?>]"
                                                        rows="3"
                                                        class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                                        placeholder="Opsional, misalnya catatan performa atau keputusan teknis."><?= e(data_get(old('remarks', []), $judgeName)) ?></textarea>
                                                </div>

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
                                                        <span x-text="activeJudgeIndex === maxJudgeIndex() ? 'Pratinjau Sebelum Simpan' : 'Lanjut'"></span>
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
                                        <button type="button" class="primary-button justify-center px-5 py-3" x-on:click="openPreview()">
                                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                            Pratinjau Sebelum Simpan
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
                                                <div class="grid gap-4 xl:grid-cols-2">
                                                    <template x-for="judge in previewData" :key="judge.name">
                                                        <section class="rounded-[1.35rem] border border-slate-800 bg-slate-900/55 p-4">
                                                            <div class="flex items-center justify-between gap-3">
                                                                <div>
                                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Hakim</p>
                                                                    <h4 class="mt-1 text-lg font-bold text-white" x-text="judge.name"></h4>
                                                                </div>
                                                                <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100" x-text="judge.total"></div>
                                                            </div>
                                                            <div class="mt-4 grid gap-2">
                                                                <template x-for="item in judge.scores" :key="item.label">
                                                                    <div class="flex items-center justify-between gap-3 rounded-[1rem] border px-3 py-2"
                                                                        :class="scorePreviewClass(item.numericValue)">
                                                                        <span class="text-sm text-slate-300" x-text="item.label"></span>
                                                                        <span class="text-sm font-bold text-white" x-text="item.value"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                            <template x-if="judge.remarks">
                                                                <div class="mt-4 rounded-[1rem] border border-slate-800 bg-slate-950/55 px-3 py-3 text-sm text-slate-300" x-text="judge.remarks"></div>
                                                            </template>
                                                        </section>
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

                                                            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                                                <?php foreach ($criteria as $key => $label): ?>
                                                                    <div class="rounded-[1.1rem] border border-slate-800 bg-slate-950/55 p-3">
                                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400"><?= e($label) ?></label>
                                                                        <input
                                                                            name="scores[<?= e($judgeName) ?>][<?= e($key) ?>]"
                                                                            type="number"
                                                                            min="0"
                                                                            max="100"
                                                                            step="0.01"
                                                                            x-bind:value="correctionRequestDraft?.[<?= e(json_encode($judgeName)) ?>]?.scores?.[<?= e(json_encode($key)) ?>] ?? ''"
                                                                            class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3.5 py-2.5 text-slate-100 outline-none focus:border-amber-300 focus:ring-2 focus:ring-amber-400/20"
                                                                            placeholder="0 - 100">
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>

                                                            <div class="mt-4">
                                                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Catatan <?= e($judgeName) ?></label>
                                                                <textarea
                                                                    name="remarks[<?= e($judgeName) ?>]"
                                                                    rows="3"
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
                                        // Old format: first row is sufficient, accessor will handle it
                                        $firstScore = $roundScores->first();
                                        $isNewFormat = $firstScore && $firstScore->scores && is_array($firstScore->scores);
                                        $judgeCount = $isNewFormat ? count($firstScore->scores) : ($roundScores->count() ?: 1);
                                    ?>
                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/45 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-white"><?= e($roundLabel) ?></p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    <?= e($selectedParticipant?->name ?? '-') ?> · Lot <?= e($selectedParticipant?->lot_number ?: '-') ?>
                                                </p>
                                            </div>
                                            <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                                <?= e($judgeCount) ?> hakim
                                            </div>
                                        </div>

                                        <div class="mt-4 grid gap-3">
                                            <?php if ($isNewFormat): ?>
                                                <?php // New format: iterate over judges in the JSON scores ?>
                                                <?php foreach ($firstScore->scores as $judgeName => $judgeData): ?>
                                                    <div class="rounded-[1rem] border border-slate-800 bg-slate-950/55 px-3 py-2.5">
                                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                                            <div>
                                                                <p class="text-sm font-semibold text-white"><?= e($judgeName) ?></p>
                                                                <p class="mt-1 text-xs text-slate-400"><?= e(optional($firstScore->submitted_at)->format('d M Y H:i')) ?></p>
                                                            </div>
                                                            <p class="text-base font-bold text-cyan-200"><?= e(number_format((float) ($judgeData['score'] ?? 0), 2)) ?></p>
                                                        </div>
                                                        <?php if (! empty($judgeData['breakdown'] ?? [])): ?>
                                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                                <?php foreach ($judgeData['breakdown'] as $label => $value): ?>
                                                                    <span class="inline-flex items-center gap-1 rounded-full border border-slate-700 bg-slate-900/80 px-2 py-0.5 text-[10px] font-medium text-slate-300">
                                                                        <span class="uppercase tracking-[0.12em] text-slate-500"><?= e(str_replace('_', ' ', ucfirst((string) $label))) ?></span>
                                                                        <span class="text-slate-100"><?= e(number_format((float) $value, 2)) ?></span>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <?php // Old format: iterate over each score entry row ?>
                                                <?php foreach ($roundScores as $score): ?>
                                                    <div class="rounded-[1rem] border border-slate-800 bg-slate-950/55 px-3 py-2.5">
                                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                                            <div>
                                                                <p class="text-sm font-semibold text-white"><?= e($score->judge_name) ?></p>
                                                                <p class="mt-1 text-xs text-slate-400"><?= e(optional($score->submitted_at)->format('d M Y H:i')) ?></p>
                                                            </div>
                                                            <p class="text-base font-bold text-cyan-200"><?= e(number_format((float) $score->score, 2)) ?></p>
                                                        </div>
                                                        <?php if (! empty($score->score_breakdown)): ?>
                                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                                <?php foreach ($score->score_breakdown as $label => $value): ?>
                                                                    <span class="inline-flex items-center gap-1 rounded-full border border-slate-700 bg-slate-900/80 px-2 py-0.5 text-[10px] font-medium text-slate-300">
                                                                        <span class="uppercase tracking-[0.12em] text-slate-500"><?= e(str_replace('_', ' ', ucfirst((string) $label))) ?></span>
                                                                        <span class="text-slate-100"><?= e(number_format((float) $value, 2)) ?></span>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
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

        function participantPicker(initialState) {
            return {
                participants: initialState.participants ?? [],
                selectedId: String(initialState.selectedId ?? ''),
                search: '',
                scoreFilterMode: 'all',
                dropdownOpen: false,
                highlightedIndex: 0,
                init() {
                    // Sync with URL parameter on page load
                    const urlParams = new URLSearchParams(window.location.search);
                    const urlParticipantId = urlParams.get('participant_id');
                    if (urlParticipantId) {
                        this.selectedId = String(urlParticipantId);
                        // Find the participant in the list to set the search
                        const found = this.participants.find(p => String(p.id) === this.selectedId);
                        if (found) {
                            this.search = found.name;
                        }
                    } else if (this.selectedParticipant) {
                        this.search = this.selectedParticipant.name;
                    }
                },
                get selectedParticipant() {
                    return this.participants.find((participant) => String(participant.id) === String(this.selectedId)) ?? null;
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
                selectParticipant(participant) {
                    // Show confirmation modal first
                    this.showBigScreenConfirmation(participant);
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

                    // Update Big Screen - await to ensure broadcast completes before page reload
                    await this.updateBigScreen(participant);

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
                init() {
                    Object.keys(this.rounds).forEach((roundKey) => {
                        const round = this.rounds[roundKey] ?? {};
                        round.judgeCount = Math.max(1, Math.min(15, Number(round.judge_count ?? round.judgeCount ?? 1)));
                        round.judgeNames = Array.isArray(round.judge_names ?? round.judgeNames) && (round.judge_names ?? round.judgeNames).length
                            ? (round.judge_names ?? round.judgeNames)
                            : [''];
                        round.scoringPoints = Array.isArray(round.scoring_points ?? round.scoringPoints) && (round.scoring_points ?? round.scoringPoints).length
                            ? (round.scoring_points ?? round.scoringPoints)
                            : [''];
                        this.rounds[roundKey] = round;
                        this.syncJudgeCount(roundKey);
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
                activeJudgeIndex: Math.max(0, Number(initialState.initialJudgeIndex ?? 0)),
                selectedParticipantName: String(initialState.selectedParticipantName ?? ''),
                selectedParticipantLot: String(initialState.selectedParticipantLot ?? ''),
                selectedJudgingRound: String(initialState.selectedJudgingRound ?? ''),
                previewOpen: false,
                previewData: [],
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
                    return this.judgeNames.map((judgeName, index) => {
                        const panel = this.judgePanel(index);
                        const scoreInputs = panel ? [...panel.querySelectorAll('input[type="number"]')] : [];
                        const scores = scoreInputs.map((input) => ({
                            label: input.dataset.scoreLabel || 'Poin',
                            value: input.value || '0',
                            numericValue: Number(input.value || 0),
                        }));
                        const numericValues = scoreInputs.map((input) => Number(input.value || 0));
                        const total = numericValues.length
                            ? (numericValues.reduce((sum, value) => sum + value, 0) / numericValues.length).toFixed(2)
                            : '0.00';
                        const remarksField = panel ? panel.querySelector('textarea') : null;

                        return {
                            name: judgeName,
                            total: `Rata-rata ${total}`,
                            scores,
                            remarks: remarksField && String(remarksField.value || '').trim() !== '' ? remarksField.value.trim() : '',
                        };
                    });
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
                    this.previewOpen = false;
                    if (this.$root.requestSubmit) {
                        this.$root.requestSubmit();
                        return;
                    }

                    this.$root.submit();
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
