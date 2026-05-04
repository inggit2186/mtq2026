<?php
require_once __DIR__.'/../partials/icon.php';
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
$participantOptions = collect($participants ?? [])->map(function ($participant) use ($resolveParticipantPhoto, $filters, $selectedJudgingRound): array {
    return [
        'id' => $participant->id,
        'name' => $participant->name,
        'branch' => $participant->category?->branch ?? '-',
        'category' => $participant->category?->name ?? '-',
        'district' => $participant->district?->name ?? '-',
        'registration_number' => $participant->registration_number,
        'institution' => $participant->institution,
        'average_score' => number_format((float) ($participant->scores->avg('score') ?? 0), 2),
        'photo' => $resolveParticipantPhoto($participant),
        'url' => route('scoring', array_filter([
            'participant_id' => $participant->id,
            'competition_category_id' => $filters['competition_category_id'] ?? null,
            'branch' => $filters['branch'] ?? null,
            'keyword' => $filters['keyword'] ?? null,
            'judging_round' => $filters['judging_round'] ?? $selectedJudgingRound,
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
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
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
                    <?php foreach ($navigation as $item): ?>
                        <a href="<?= e($item['href']) ?>" class="sidebar-link <?= $item['active'] ? 'sidebar-link-active' : '' ?>">
                            <span class="icon-chip h-10 w-10 rounded-xl"><?= mtq_icon($item['icon'], 'h-4 w-4') ?></span>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
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
                        <?php if ($selectedCategory): ?>
                            <a href="<?= e($bigScreenUrl) ?>" target="_blank" rel="noreferrer" class="secondary-button">
                                <?= mtq_icon('eye', 'h-4 w-4') ?>
                                Big Screen Golongan
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="grid w-full gap-3 md:grid-cols-3">
                        <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Golongan Aktif</p>
                            <p class="mt-1 text-sm font-semibold text-white"><?= e($selectedCategory ? trim(($selectedCategory->branch ?? '-').' | '.($selectedCategory->name ?? '-')) : 'Belum dipilih') ?></p>
                        </div>
                        <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Babak Aktif</p>
                            <p class="mt-1 text-sm font-semibold text-white"><?= e($selectedJudgingRound) ?></p>
                        </div>
                        <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Peserta Dipilih</p>
                            <p class="mt-1 text-sm font-semibold text-white"><?= e($selectedParticipant?->name ?? 'Belum dipilih') ?></p>
                        </div>
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Siap Dinilai</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($scoreStats['participant_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Peserta Terverifikasi</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($scoreStats['verified_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('shield') ?></div><p class="mt-4 text-sm text-slate-400">Hakim Tersetting</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($scoreStats['judge_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Poin Penilaian</p><p class="mt-2 text-3xl font-extrabold text-emerald-300"><?= e($scoreStats['criteria_total']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('fingerprint') ?></div>
                            <div>
                                <p class="section-kicker">Setting Penilaian</p>
                                <h2 class="text-2xl font-bold text-white">Atur jumlah hakim, nama hakim, babak, dan poin nilai</h2>
                                <p class="mt-2 text-sm text-slate-300">Setting ini berlaku khusus untuk golongan yang dipilih operator. Form nilai baru aktif setelah setting tersimpan.</p>
                            </div>
                        </div>
                        <div class="<?= $setupReady ? 'status-pill' : 'inline-flex items-center gap-2 rounded-full border border-amber-400/18 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-100' ?>">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full <?= $setupReady ? 'bg-emerald-300' : 'bg-amber-300' ?>"></span>
                            <?= $setupReady ? 'Setting Tersimpan' : 'Belum Disiapkan' ?>
                        </div>
                    </div>

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

                        <div class="grid gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Golongan aktif</label>
                                <select name="competition_category_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                    <option value="">Pilih golongan</option>
                                    <?php foreach ($categories as $category): ?>
                                        <?php $settingSelected = (string) old('competition_category_id', $selectedCategory?->id) === (string) $category->id; ?>
                                        <option value="<?= e($category->id) ?>" <?= $settingSelected ? 'selected' : '' ?>><?= e($category->branch.' - '.$category->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/60 p-4">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Babak penilaian</label>
                            <input type="hidden" name="judging_rounds_text" value="Penyisihan&#10;Final">
                            <div class="rounded-[1.5rem] border border-slate-700 bg-slate-950/80 px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-sm font-semibold text-cyan-100">Penyisihan</span>
                                    <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-sm font-semibold text-cyan-100">Final</span>
                                </div>
                                <p class="mt-3 text-xs text-slate-400">Masing-masing babak punya setting hakim dan poin penilaian sendiri.</p>
                            </div>
                        </div>

                        <div class="rounded-[1.7rem] border border-slate-800 bg-slate-950/60 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="section-kicker">Tab Setting Babak</p>
                                    <h3 class="mt-2 text-xl font-bold text-white">Fokus satu babak dalam satu waktu</h3>
                                    <p class="mt-2 text-sm text-slate-300">Pilih tab `Penyisihan` atau `Final` untuk mengatur hakim dan poin penilaian tanpa memenuhi layar dengan terlalu banyak form.</p>
                                </div>
                                <div class="rounded-[1.4rem] border border-slate-800 bg-slate-950/80 p-1.5">
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($roundFormKeys as $roundLabel => $roundKey): ?>
                                            <button type="button"
                                                class="rounded-[1rem] px-4 py-3 text-left text-sm font-semibold transition"
                                                :class="activeRound === '<?= e($roundKey) ?>' ? 'bg-cyan-400/15 text-cyan-100 shadow-[0_12px_30px_-18px_rgba(34,211,238,0.7)]' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'"
                                                x-on:click="activeRound = '<?= e($roundKey) ?>'">
                                                <span class="flex items-center gap-2">
                                                    <span><?= e($roundLabel) ?></span>
                                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em]"
                                                        :class="roundReady('<?= e($roundKey) ?>') ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border-amber-300/20 bg-amber-400/10 text-amber-100'"
                                                        x-text="roundReady('<?= e($roundKey) ?>') ? 'Siap' : 'Belum siap'"></span>
                                                </span>
                                                <span class="mt-1 block text-xs font-medium"
                                                    :class="activeRound === '<?= e($roundKey) ?>' ? 'text-cyan-100/80' : 'text-slate-500'"
                                                    x-text="roundSummary('<?= e($roundKey) ?>')"></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <?php foreach ($roundFormKeys as $roundLabel => $roundKey): ?>
                                <section x-show="activeRound === '<?= e($roundKey) ?>'" x-cloak class="mt-5 space-y-5">
                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.4rem] border border-slate-800 bg-slate-950/50 px-4 py-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-200"><?= e($roundLabel) ?></p>
                                            <p class="mt-1 text-sm text-slate-300">Konfigurasi khusus babak <?= e($roundLabel) ?>.</p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]"
                                                :class="roundReady('<?= e($roundKey) ?>') ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border-amber-300/20 bg-amber-400/10 text-amber-100'"
                                                x-text="roundReady('<?= e($roundKey) ?>') ? 'Setup siap' : 'Masih perlu dilengkapi'"></span>
                                            <div class="status-pill">
                                                <span class="inline-flex h-2.5 w-2.5 rounded-full <?= $selectedJudgingRound === $roundLabel ? 'bg-emerald-300' : 'bg-cyan-300' ?>"></span>
                                                <?= $selectedJudgingRound === $roundLabel ? 'Babak Aktif di Form Nilai' : 'Setting Tersedia' ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Jumlah hakim</label>
                                        <input name="rounds[<?= e($roundKey) ?>][judge_count]" type="number" min="1" max="15"
                                            x-model.number="rounds.<?= e($roundKey) ?>.judgeCount"
                                            x-on:input="syncJudgeCount('<?= e($roundKey) ?>')"
                                            class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                    </div>

                                    <div class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
                                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/50 p-4">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nama hakim</label>
                                            <div class="space-y-3">
                                                <template x-for="(judgeName, index) in rounds.<?= e($roundKey) ?>.judgeNames" :key="'<?= e($roundKey) ?>-judge-' + index">
                                                    <div class="space-y-2">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-cyan-400/18 bg-cyan-400/10 text-sm font-bold text-cyan-100" x-text="index + 1"></div>
                                                            <div class="min-w-0 flex-1">
                                                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500" x-text="'Hakim ' + (index + 1)"></label>
                                                                <input type="text"
                                                                    x-model="rounds.<?= e($roundKey) ?>.judgeNames[index]"
                                                                    class="w-full rounded-2xl border bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                                                    :class="judgeNameState('<?= e($roundKey) ?>', index).valid ? 'border-slate-700' : 'border-rose-400/50 focus:border-rose-300 focus:ring-rose-400/20'"
                                                                    :placeholder="'Nama hakim ' + (index + 1)">
                                                            </div>
                                                        </div>
                                                        <p x-show="!judgeNameState('<?= e($roundKey) ?>', index).valid" x-text="judgeNameState('<?= e($roundKey) ?>', index).message" class="pl-14 text-xs text-rose-200"></p>
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
                </section>

                <section class="grid gap-6 xl:grid-cols-[0.78fr_1.22fr]">
                    <div class="glass-card rounded-[2rem] p-6"
                        x-data="participantPicker({
                            participants: <?= e(json_encode($participantOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                            selectedId: <?= e(json_encode((string) ($selectedParticipant?->id ?? ''))) ?>,
                        })">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('users') ?></div>
                            <div>
                                <h2 class="text-2xl font-bold text-white">Peserta Siap Dinilai</h2>
                                <p class="mt-1 text-sm text-slate-300">Pilih peserta dari dropdown agar tetap ringan walau data peserta banyak.</p>
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
                                            placeholder="Ketik nama, nomor registrasi, kecamatan, atau golongan"
                                            class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                        <div x-show="dropdownOpen" x-cloak x-on:click.outside="dropdownOpen = false" class="absolute z-20 mt-2 max-h-80 w-full overflow-y-auto rounded-[1.4rem] border border-slate-800 bg-slate-950/95 p-2 shadow-[0_18px_50px_-30px_rgba(15,23,42,0.9)]">
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
                                                        <p class="mt-1 truncate text-xs text-slate-400" x-text="`${participant.registration_number} | ${participant.district}`"></p>
                                                        <p class="mt-1 truncate text-xs text-cyan-200" x-text="`${participant.branch} | ${participant.category}`"></p>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <button type="button" class="primary-button px-4 py-3" x-on:click="goToSelected()" :disabled="!selectedParticipant" x-bind:class="!selectedParticipant ? 'cursor-not-allowed opacity-60' : ''">
                                            <?= mtq_icon('chart', 'h-4 w-4') ?>
                                            Pilih Peserta
                                        </button>
                                        <button type="button" class="secondary-button px-4 py-3" x-on:click="resetSearch()" :disabled="!search && !selectedParticipant" x-bind:class="!search && !selectedParticipant ? 'cursor-not-allowed opacity-60' : ''">
                                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                            Bersihkan
                                        </button>
                                    </div>
                                </div>

                                <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/45 p-4">
                                    <template x-if="selectedParticipant">
                                        <div class="flex items-start gap-3">
                                            <template x-if="selectedParticipant.photo">
                                                <img :src="selectedParticipant.photo" :alt="`Foto ${selectedParticipant.name}`" class="h-24 w-18 shrink-0 rounded-[1rem] border border-cyan-400/16 object-cover">
                                            </template>
                                            <template x-if="!selectedParticipant.photo">
                                                <div class="flex h-24 w-18 shrink-0 items-center justify-center rounded-[1rem] border border-slate-700 bg-slate-950/80 text-center text-[10px] uppercase tracking-[0.2em] text-slate-500">
                                                    Tanpa foto
                                                </div>
                                            </template>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-base font-bold text-white" x-text="selectedParticipant.name"></p>
                                                <p class="mt-1 truncate text-xs text-slate-300"><span x-text="selectedParticipant.branch"></span> | <span x-text="selectedParticipant.category"></span></p>
                                                <p class="mt-2 truncate text-xs text-slate-400"><span x-text="selectedParticipant.district"></span> | <span x-text="selectedParticipant.registration_number"></span></p>
                                                <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-slate-400">
                                                    <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1" x-text="selectedParticipant.institution"></span>
                                                    <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-2.5 py-1 text-cyan-200">Avg: <span class="ml-1" x-text="selectedParticipant.average_score"></span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!selectedParticipant">
                                        <div class="text-sm text-slate-300">Pilih peserta dari dropdown untuk melihat foto dan ringkasan singkatnya.</div>
                                    </template>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-5 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)]">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                                    <p class="mt-4 section-kicker">Peserta Terpilih</p>
                                    <h2 class="mt-2 text-xl font-bold text-white"><?= e($selectedParticipant?->name ?? 'Belum ada peserta dipilih') ?></h2>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">
                                        <?php if ($selectedParticipant): ?>
                                            <?= e(($selectedParticipant->category?->branch ?? '-').' | '.($selectedParticipant->category?->name ?? '-')) ?><br>
                                            <?= e(($selectedParticipant->district?->name ?? '-').' | '.$selectedParticipant->institution) ?>
                                        <?php else: ?>
                                            Pilih salah satu peserta dari daftar sebelah kiri untuk memulai input nilai.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php if ($selectedParticipant): ?>
                                    <div class="status-pill">
                                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                        <?= e($selectedParticipant->registration_number) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($selectedParticipant): ?>
                                <div class="mt-4 grid gap-4 lg:grid-cols-[128px_minmax(0,1fr)] rounded-[1.5rem] border border-cyan-400/14 bg-slate-950/35 p-4">
                                    <?php if ($selectedParticipantPhoto): ?>
                                        <img src="<?= e($selectedParticipantPhoto) ?>" alt="<?= e('Foto '.$selectedParticipant->name) ?>" class="h-36 w-28 rounded-[1.2rem] border border-cyan-400/16 object-cover">
                                    <?php else: ?>
                                        <div class="flex h-36 w-28 items-center justify-center rounded-[1.2rem] border border-slate-700 bg-slate-950/70 text-center text-xs uppercase tracking-[0.22em] text-slate-500">
                                            Tanpa foto
                                        </div>
                                    <?php endif; ?>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">Identitas singkat</p>
                                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-[1rem] border border-slate-800 bg-slate-950/45 px-3 py-3">
                                                <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Kecamatan</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-100"><?= e($selectedParticipant->district?->name ?? '-') ?></p>
                                            </div>
                                            <div class="rounded-[1rem] border border-slate-800 bg-slate-950/45 px-3 py-3">
                                                <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Institusi</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-100"><?= e($selectedParticipant->institution) ?></p>
                                            </div>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                            <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1 text-slate-300"><?= e($selectedParticipant->category?->branch ?? '-') ?></span>
                                            <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-cyan-100"><?= e($selectedParticipant->category?->name ?? '-') ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($selectedParticipant && $selectedCategory): ?>
                                <div class="mt-5 flex flex-wrap gap-3">
                                    <a href="<?= e($bigScreenUrl) ?>" target="_blank" rel="noreferrer" class="secondary-button">
                                        <?= mtq_icon('eye', 'h-4 w-4') ?>
                                        Buka Big Screen Peserta Aktif
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="form-penilaian" class="glass-card rounded-[2rem] p-5">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('check-circle') ?></div>
                                <div>
                                    <p class="section-kicker">Form Penilaian</p>
                                    <p class="mt-1 text-sm text-slate-300">Nama hakim dan poin mengikuti setting babak aktif yang dipilih operator.</p>
                                </div>
                            </div>

                            <?php if (! $selectedCategory): ?>
                                <div class="mt-6 data-card text-sm text-slate-300">Pilih golongan terlebih dahulu pada filter atau pengaturan penilaian.</div>
                            <?php elseif (! $setupReady): ?>
                                <div class="mt-6 data-card text-sm text-amber-100">Setting penilaian untuk golongan ini belum lengkap. Simpan setting lebih dahulu sebelum input nilai peserta.</div>
                            <?php elseif (! $selectedParticipant): ?>
                                <div class="mt-6 data-card text-sm text-slate-300">Belum ada peserta dipilih untuk dinilai.</div>
                            <?php else: ?>
                                <form method="POST" action="<?= e(route('scoring.store')) ?>" class="mt-6 grid gap-4"
                                    x-data="judgeBatchForm({
                                        judgeNames: <?= e(json_encode(array_values($judgeNames), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                    })">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="participant_id" value="<?= e($selectedParticipant->id) ?>">
                                    <input type="hidden" name="judging_round" value="<?= e($selectedJudgingRound) ?>">

                                    <div class="rounded-[1.25rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
                                        Babak aktif saat ini: <span class="font-bold"><?= e($selectedJudgingRound) ?></span>. Isi nilai hakim per panel, pindah dengan tombol `Lanjut`, lalu simpan semua sekaligus di akhir.
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-[1.15fr_0.85fr]">
                                        <div class="rounded-[1.2rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Hakim Aktif Dalam Batch</p>
                                                    <p class="mt-1 text-lg font-bold text-white"><?= e(count($judgeNames)) ?> hakim</p>
                                                </div>
                                                <div class="rounded-[1rem] border border-cyan-400/18 bg-cyan-400/10 px-3 py-2 text-right">
                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Sedang Dibuka</p>
                                                    <p class="mt-1 text-sm font-bold text-white" x-text="progressLabel()"></p>
                                                </div>
                                            </div>
                                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
                                                <div class="h-full rounded-full bg-gradient-to-r from-cyan-300 via-sky-400 to-emerald-300 transition-all duration-200" :style="`width: ${progressPercent()}%`"></div>
                                            </div>
                                            <p class="mt-3 text-xs text-slate-400">Klik kartu hakim atau tombol `Lanjut` untuk berpindah tanpa menyimpan dahulu.</p>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Babak penilaian</label>
                                            <div class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"><?= e($selectedJudgingRound) ?></div>
                                        </div>
                                    </div>

                                    <div id="judge_name_field" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                        <?php foreach ($judgeNames as $index => $judgeName): ?>
                                            <button type="button"
                                                class="rounded-[1.2rem] border px-4 py-3 text-left transition"
                                                :class="activeJudgeIndex === <?= e($index) ?> ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_18px_35px_-24px_rgba(34,211,238,0.7)]' : 'border-slate-800 bg-slate-950/45 hover:border-cyan-400/25'"
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
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/45 p-4">
                                        <?php foreach ($judgeNames as $index => $judgeName): ?>
                                            <section x-show="activeJudgeIndex === <?= e($index) ?>" x-cloak data-judge-panel="<?= e($index) ?>" class="space-y-4">
                                                <div class="flex flex-wrap items-center justify-between gap-3">
                                                    <div>
                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200">Panel Nilai</p>
                                                        <h3 class="mt-1 text-xl font-bold text-white"><?= e($judgeName) ?></h3>
                                                    </div>
                                                    <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                                        Hakim <?= e($index + 1) ?> dari <?= e(count($judgeNames)) ?>
                                                    </div>
                                                </div>

                                                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                    <?php foreach ($criteria as $key => $label): ?>
                                                        <div class="rounded-[1.1rem] border border-slate-800 bg-slate-950/55 p-3">
                                                            <label class="mb-2 block text-sm font-semibold text-slate-200"><?= e($label) ?></label>
                                                            <input
                                                                name="scores[<?= e($judgeName) ?>][<?= e($key) ?>]"
                                                                data-score-label="<?= e($label) ?>"
                                                                type="number"
                                                                min="0"
                                                                max="100"
                                                                step="0.01"
                                                                value="<?= e(data_get(old('scores', []), $judgeName.'.'.$key)) ?>"
                                                                class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3.5 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                                                placeholder="0 - 100">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan <?= e($judgeName) ?></label>
                                                    <textarea
                                                        name="remarks[<?= e($judgeName) ?>]"
                                                        rows="3"
                                                        class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                                        placeholder="Opsional, misalnya catatan performa atau keputusan teknis."><?= e(data_get(old('remarks', []), $judgeName)) ?></textarea>
                                                </div>

                                                <div class="flex flex-wrap justify-between gap-3 border-t border-slate-800 pt-2">
                                                    <button type="button"
                                                        class="secondary-button px-4 py-3"
                                                        x-on:click="previousJudge()"
                                                        x-bind:disabled="activeJudgeIndex === 0"
                                                        :class="activeJudgeIndex === 0 ? 'cursor-not-allowed opacity-50' : ''">
                                                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                                        Kembali
                                                    </button>
                                                    <button type="button"
                                                        class="primary-button px-4 py-3"
                                                        x-on:click="nextJudge()"
                                                        x-bind:disabled="activeJudgeIndex === maxJudgeIndex()"
                                                        :class="activeJudgeIndex === maxJudgeIndex() ? 'cursor-not-allowed opacity-50' : ''">
                                                        Lanjut
                                                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                                    </button>
                                                </div>
                                            </section>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
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
                                                    <p class="mt-2 text-sm text-slate-300"><?= e($selectedParticipant->name) ?> | <?= e($selectedJudgingRound) ?></p>
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
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="glass-card rounded-[2rem] p-5">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                                <div>
                                    <p class="section-kicker">Riwayat Nilai</p>
                                    <p class="mt-1 text-sm text-slate-300">Skor terbaru untuk peserta yang sedang dipilih.</p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <?php if ($recentScores->isEmpty()): ?>
                                    <div class="data-card text-sm text-slate-300">Belum ada nilai yang tercatat untuk peserta ini.</div>
                                <?php endif; ?>
                                <?php foreach ($recentScores->groupBy(fn ($score) => $score->judging_round ?: 'Tanpa Babak') as $roundLabel => $roundScores): ?>
                                    <?php $latestRoundScores = $roundScores->take(count($judgeNames) ?: 1); ?>
                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/45 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-white"><?= e($roundLabel) ?></p>
                                                <p class="mt-1 text-xs text-slate-400">Batch terbaru <?= e(optional($latestRoundScores->first()?->submitted_at)->format('d M Y H:i')) ?></p>
                                            </div>
                                            <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                                <?= e($latestRoundScores->count()) ?> entri
                                            </div>
                                        </div>

                                        <div class="mt-4 grid gap-3">
                                            <?php foreach ($latestRoundScores as $score): ?>
                                                <div class="rounded-[1rem] border border-slate-800 bg-slate-950/55 px-3 py-3">
                                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                                        <div>
                                                            <p class="text-sm font-semibold text-white"><?= e($score->judge_name) ?></p>
                                                            <p class="mt-1 text-xs text-slate-400"><?= e(optional($score->submitted_at)->format('d M Y H:i')) ?></p>
                                                        </div>
                                                        <p class="text-base font-bold text-cyan-200"><?= e(number_format((float) $score->score, 2)) ?></p>
                                                    </div>
                                                    <?php if (! empty($score->score_breakdown)): ?>
                                                        <div class="mt-3 flex flex-wrap gap-2">
                                                            <?php foreach ($score->score_breakdown as $label => $value): ?>
                                                                <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-[11px] text-slate-300"><?= e(str_replace('_', ' ', ucfirst((string) $label))) ?>: <?= e(number_format((float) $value, 2)) ?></span>
                                                            <?php endforeach; ?>
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
        function participantPicker(initialState) {
            return {
                participants: initialState.participants ?? [],
                selectedId: String(initialState.selectedId ?? ''),
                search: '',
                dropdownOpen: false,
                highlightedIndex: 0,
                init() {
                    if (this.selectedParticipant) {
                        this.search = this.selectedParticipant.name;
                    }
                },
                get selectedParticipant() {
                    return this.participants.find((participant) => String(participant.id) === String(this.selectedId)) ?? null;
                },
                get filteredParticipants() {
                    const keyword = this.search.trim().toLowerCase();

                    if (keyword === '') {
                        return this.participants.slice(0, 12);
                    }

                    return this.participants.filter((participant) => {
                        const haystack = [
                            participant.name,
                            participant.registration_number,
                            participant.district,
                            participant.branch,
                            participant.category,
                            participant.institution,
                        ].join(' ').toLowerCase();

                        return haystack.includes(keyword);
                    }).slice(0, 12);
                },
                selectParticipant(participant) {
                    this.selectedId = String(participant.id);
                    this.search = participant.name;
                    this.dropdownOpen = false;
                    this.highlightedIndex = 0;
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
                    this.dropdownOpen = false;
                    this.highlightedIndex = 0;
                },
                goToSelected() {
                    if (!this.selectedParticipant) {
                        return;
                    }

                    const targetUrl = new URL(this.selectedParticipant.url, window.location.origin);
                    targetUrl.hash = 'form-penilaian';
                    window.location.href = targetUrl.toString();
                },
            };
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
                roundReady(roundKey) {
                    const pointTotal = (this.rounds[roundKey]?.scoringPoints ?? [])
                        .map((value) => String(value ?? '').trim())
                        .filter(Boolean)
                        .length;

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
                activeJudgeIndex: 0,
                previewOpen: false,
                previewData: [],
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

            formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

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
