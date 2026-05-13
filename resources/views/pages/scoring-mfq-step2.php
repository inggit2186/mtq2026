<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'scoring.mfq');
$selectedParticipants = $selectedParticipants ?? collect();
$selectedParticipant = $selectedParticipant ?? null;
$selectedCategory = $selectedCategory ?? null;
$recentScores = $recentScores ?? collect();
$summaryStats = $summaryStats ?? ['participant_total' => 0, 'category_total' => 0, 'verified_total' => 0, 'selected_average' => '0.00', 'selected_latest' => '0.00'];
$filters = $filters ?? [];
$judgeNameDefault = $judgeNameDefault ?? (string) $user?->name;
$selectionState = $selectionState ?? ['competition_category_id' => null, 'district_ids' => []];
$selectionSessionName = $selectionSessionName ?? '';
$selectionJudgeName = $selectionJudgeName ?? (string) $user?->name;
$selectionJudgingRound = $selectionJudgingRound ?? 'Penyisihan';
$selectionRemarks = $selectionRemarks ?? '';
$selectedDistrict = $selectedDistrict ?? null;
$selectedDistrictCards = $selectedDistrictCards ?? [];
$selectedDistrictLotNumber = $selectedDistrictCards[0]['representative_lot_number'] ?? ($selectedParticipant?->lot_number ?? '-');
$scoringColumns = $scoringColumns ?? ['active' => ['id' => null, 'name' => '-', 'registration_number' => '-'], 'opponents' => collect()];
$opponents = collect($scoringColumns['opponents'] ?? []);
$selectedParticipantCategory = $selectedParticipant?->category ? trim((string) $selectedParticipant->category->branch.' - '.(string) $selectedParticipant->category->name) : '-';
$questionSource = old('questions');
$questionSource = is_array($questionSource) && $questionSource !== [] ? $questionSource : ($defaultQuestions ?? []);
$initialQuestions = [];

foreach (array_values(is_array($questionSource) ? $questionSource : []) as $index => $question) {
    $throwScoresSource = data_get($question, 'throw_scores', []);
    $throwScores = [];

    foreach (range(0, max(0, $opponents->count() - 1)) as $throwIndex) {
        $throwScores[] = (string) data_get($throwScoresSource, $throwIndex, '');
    }

    $initialQuestions[] = [
        'id' => (string) data_get($question, 'id', uniqid('mfq_', true)),
        'label' => (string) data_get($question, 'label', 'Soal '.($index + 1)),
        'package_score' => (string) data_get($question, 'package_score', ''),
        'throw_scores' => $throwScores,
        'rebuttal_score' => (string) data_get($question, 'rebuttal_score', ''),
        'notes' => (string) data_get($question, 'notes', ''),
    ];
}

if ($initialQuestions === []) {
    $initialQuestions = $defaultQuestions ?? [];
}

$opponentCards = $opponents->map(function (array $opponent, int $index): array {
    return [
        'label' => 'Lontaran '.($index + 1),
        'name' => $opponent['name'] ?? '-',
        'registration_number' => $opponent['registration_number'] ?? '-',
    ];
})->values()->all();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Penilaian MFQ') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>

    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="mfqSheetForm({
        questions: <?= e(json_encode($initialQuestions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
        opponentCount: <?= e($opponents->count()) ?>,
        opponentCards: <?= e(json_encode($opponentCards, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
        storageKey: <?= e(json_encode('mfq.sheet.'.($selectedParticipant?->id ?? 'draft').'.'.($selectedJudgingRound ?? 'Penyisihan').'.'.($selectionSessionName ?? 'session')) ) ?>,
    })" x-init="init()">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Penilaian MFQ</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Tahap 2</p>
                    <h2 class="mt-3 text-xl font-bold text-white">Grid skor ala Excel</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Isi nilai per soal dalam bentuk angka bulat pada kolom paket, lontaran, dan rebutan. Total akan dijumlahkan otomatis per baris dan per regu aktif.</p>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Nama Sesi</p>
                        <p class="mt-2 text-lg font-bold text-white"><?= e($selectionSessionName !== '' ? $selectionSessionName : '-') ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Kecamatan Dipilih</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($selectedParticipants->count()) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Kecamatan Aktif</p>
                        <p class="mt-2 text-sm font-bold text-white"><?= e($selectedDistrict?->name ?? '-') ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Perwakilan Aktif</p>
                        <p class="mt-2 text-sm font-bold text-white"><?= e($selectedParticipant?->name ?? '-') ?></p>
                        <p class="mt-1 text-xs text-slate-400"><?= e($selectedParticipant?->registration_number ?? '-') ?></p>
                    </div>
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
                            <p class="section-kicker">Ruang Penilaian MFQ</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Tahap 2: nilai regu yang sudah dipilih</h2>
                            <p class="mt-2 text-sm text-slate-300"><?= e($mfqSheetSummary ?? 'Format penilaian mengikuti lembar Excel MFQ dengan kolom paket, lontaran, rebutan, dan jumlah.') ?></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                            Tahap Penilaian
                        </div>
                        <form method="POST" action="<?= e(route('scoring.mfq.selection.clear')) ?>">
                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                            <button type="submit" class="secondary-button rounded-xl px-4 py-3">
                                <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                Kembali Memilih Regu
                            </button>
                        </form>
                    </div>
                </header>

                <?php if (session('status')): ?>
                    <div class="glass-card rounded-[1.5rem] border border-emerald-400/20 bg-emerald-400/10 px-5 py-4 text-sm text-emerald-100">
                        <?= e(session('status')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session('errors')?->any()): ?>
                    <div class="glass-card rounded-[1.5rem] border border-rose-400/20 bg-rose-400/10 px-5 py-4 text-sm text-rose-100">
                        Periksa kembali angka yang diisi. Lembar MFQ ini memakai nilai numerik per sel seperti pada Excel.
                    </div>
                <?php endif; ?>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Regu Dipilih</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['participant_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('book-open') ?></div><p class="mt-4 text-sm text-slate-400">Regu Lawan</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($opponents->count()) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Kecamatan Aktif</p><p class="mt-2 text-xl font-extrabold text-cyan-200"><?= e($selectedDistrict?->name ?? '-') ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Skor Terakhir</p><p class="mt-2 text-3xl font-extrabold text-emerald-300"><?= e($summaryStats['selected_latest']) ?></p></div>
                </section>

                <section class="space-y-6">
                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('fingerprint') ?></div>
                                <div>
                                    <p class="section-kicker">Kecamatan Aktif</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white"><?= e($selectedDistrict?->name ?? 'Belum dipilih') ?></h3>
                                    <p class="mt-2 text-sm text-slate-300"><?= e($selectedParticipantCategory) ?></p>
                                    <?php if (filled($selectionSessionName)): ?>
                                        <p class="mt-2 text-xs uppercase tracking-[0.18em] text-cyan-200/80">Sesi: <?= e($selectionSessionName) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="status-pill">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                Babak <?= e($selectionJudgingRound) ?>
                            </div>
                        </div>

                        <?php if (empty($selectedDistrictCards)): ?>
                            <div class="mt-5 rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-4 text-sm text-slate-300">
                                Belum ada regu yang dipilih.
                            </div>
                        <?php else: ?>
                            <div class="mt-5 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(17rem,1fr))]">
                                <?php foreach ($selectedDistrictCards as $index => $districtCard): ?>
                                    <?php
                                        $reguNumber = $index + 1;
                                        $activeLink = route('scoring.mfq', array_filter([
                                            'competition_category_id' => $selectedCategory?->id,
                                            'participant_id' => $districtCard['representative_id'],
                                            'judging_round' => $selectionJudgingRound,
                                        ]));
                                    ?>
                                    <a href="<?= e($activeLink) ?>" class="group flex h-full flex-col rounded-[1.35rem] border px-4 py-4 transition <?= (int) ($selectedDistrict?->id ?? 0) === (int) $districtCard['district_id'] ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_12px_35px_-20px_rgba(34,211,238,0.75)] ring-1 ring-cyan-300/40' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30' ?>" x-on:click="persistDraft()">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[11px] uppercase tracking-[0.2em] text-cyan-200/80">Regu <?= e($reguNumber) ?></p>
                                                <p class="mt-1 text-lg font-bold text-white"><?= e($districtCard['district_name']) ?></p>
                                                <p class="mt-1 text-xs text-slate-400"><?= e($districtCard['participant_count']) ?> peserta regu</p>
                                                <p class="mt-2 text-xs uppercase tracking-[0.18em] text-fuchsia-200/80">Nomor Lot</p>
                                                <p class="mt-1 text-sm font-bold text-fuchsia-100"><?= e($districtCard['representative_lot_number'] ?? '-') ?></p>
                                            </div>
                                            <?php if ((int) ($selectedDistrict?->id ?? 0) === (int) $districtCard['district_id']): ?>
                                                <span class="status-pill border-cyan-300/30 bg-cyan-300/15 text-cyan-100">Aktif</span>
                                            <?php else: ?>
                                                <span class="status-pill border-cyan-400/20 bg-cyan-400/10 text-cyan-100">Klik</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-3 rounded-2xl border border-slate-800 bg-slate-900/60 px-3 py-3">
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Perwakilan</p>
                                            <p class="mt-1 font-semibold text-white"><?= e($districtCard['representative_name']) ?></p>
                                            <p class="mt-1 text-xs text-slate-400"><?= e($districtCard['representative_registration_number']) ?></p>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between gap-3">
                                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Input Nilai Regu <?= e($reguNumber) ?></span>
                                            <span class="inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100 transition group-hover:border-cyan-300/40 group-hover:bg-cyan-300/15">
                                                <?= mtq_icon('pencil', 'h-3.5 w-3.5') ?>
                                                Buka
                                            </span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="section-kicker">Lembar Skor MFQ</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Input paket soal untuk regu aktif</h3>
                                <p class="mt-2 text-sm text-slate-300">Setiap soal yang diisi akan dijumlahkan ke total regu aktif. Tombol Next menambah soal baru bila masih ingin lanjut.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" class="secondary-button rounded-xl px-4 py-3" @click="addQuestion()">
                                    <?= mtq_icon('plus', 'h-4 w-4') ?>
                                    Tambah Soal
                                </button>
                                <div class="rounded-[1rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-right">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-100/70">Total Sementara</p>
                                    <p class="mt-1 text-2xl font-black text-cyan-100" x-text="totalScore()"></p>
                                </div>
                            </div>
                        </div>

                            <form method="POST" action="<?= e(route('scoring.mfq.store')) ?>" class="mt-6 space-y-5" x-on:input.debounce.250ms="persistDraft()">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="participant_id" value="<?= e($selectedParticipant?->id ?? '') ?>">
                                <input type="hidden" name="judge_name" value="<?= e($selectionJudgeName) ?>">
                                <input type="hidden" name="judging_round" value="<?= e($selectionJudgingRound) ?>">
                                <input type="hidden" name="remarks" value="<?= e($selectionRemarks) ?>">

                                <div class="rounded-[1.5rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
                                    Format ini mengikuti lembar Excel MFQ. Isi nilai per sel dengan angka bulat, lalu sistem menjumlahkan total otomatis per baris dan per kolom.
                                </div>

                                <div class="overflow-x-auto rounded-[1.7rem] border border-slate-800 bg-slate-950/55">
                                    <table class="min-w-[980px] w-full border-collapse text-center">
                                        <thead>
                                            <tr>
                                                <th colspan="5" class="border border-slate-700 bg-slate-900/90 px-3 py-3 text-2xl font-bold tracking-wide text-white"><?= e($selectedDistrict?->name ?? 'Kecamatan Aktif') ?></th>
                                            </tr>
                                            <tr>
                                                <th colspan="5" class="border border-slate-700 bg-slate-900/70 px-3 py-2 text-xl font-semibold tracking-[0.18em] text-cyan-100">Nomor Lot <?= e($selectedDistrictLotNumber) ?></th>
                                            </tr>
                                            <tr class="text-sm font-semibold">
                                                <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-yellow-300">No</th>
                                                <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-orange-200">Soal Paket</th>
                                                <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-emerald-200">Lontaran 1</th>
                                                <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-sky-200">Lontaran 2</th>
                                                <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-rose-200">Rebutan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(question, index) in questions" :key="question.id">
                                                <tr class="text-sm">
                                                    <td class="border border-slate-700 bg-slate-950/85 px-3 py-2 font-semibold text-yellow-300" x-text="index + 1"></td>
                                                    <td class="border border-slate-700 bg-slate-950/80 px-2 py-2">
                                                        <input type="number" step="1" min="0" max="100" :name="`questions[${index}][package_score]`" x-model="question.package_score" class="w-full border-0 bg-transparent px-2 py-2 text-center font-semibold text-orange-200 outline-none" placeholder="-">
                                                        <input type="hidden" :name="`questions[${index}][label]`" x-model="question.label">
                                                    </td>
                                                    <td class="border border-slate-700 bg-slate-950/80 px-2 py-2">
                                                        <input type="number" step="1" min="0" max="100" :name="`questions[${index}][throw_scores][0]`" x-model="question.throw_scores[0]" class="w-full border-0 bg-transparent px-2 py-2 text-center font-semibold text-emerald-200 outline-none" placeholder="-">
                                                    </td>
                                                    <td class="border border-slate-700 bg-slate-950/80 px-2 py-2">
                                                        <input type="number" step="1" min="0" max="100" :name="`questions[${index}][throw_scores][1]`" x-model="question.throw_scores[1]" class="w-full border-0 bg-transparent px-2 py-2 text-center font-semibold text-sky-200 outline-none" placeholder="-">
                                                    </td>
                                                    <td class="border border-slate-700 bg-slate-950/80 px-2 py-2">
                                                        <input type="number" step="1" min="0" max="100" :name="`questions[${index}][rebuttal_score]`" x-model="question.rebuttal_score" class="w-full border-0 bg-transparent px-2 py-2 text-center font-semibold text-rose-200 outline-none" placeholder="-">
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr class="text-sm font-bold">
                                                <td class="border border-slate-700 bg-slate-900/85 px-3 py-3 text-yellow-300">Jml</td>
                                                <td class="border border-slate-700 bg-slate-900/85 px-3 py-3 text-orange-200" x-text="questions.reduce((sum, question) => sum + Number(question.package_score || 0), 0)"></td>
                                                <td class="border border-slate-700 bg-slate-900/85 px-3 py-3 text-emerald-200" x-text="questions.reduce((sum, question) => sum + Number(question.throw_scores?.[0] || 0), 0)"></td>
                                                <td class="border border-slate-700 bg-slate-900/85 px-3 py-3 text-sky-200" x-text="questions.reduce((sum, question) => sum + Number(question.throw_scores?.[1] || 0), 0)"></td>
                                                <td class="border border-slate-700 bg-slate-900/85 px-3 py-3 text-rose-200" x-text="questions.reduce((sum, question) => sum + Number(question.rebuttal_score || 0), 0)"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-4">
                                    <div class="text-sm text-slate-300">
                                        Total dihitung otomatis dari semua soal. Contoh: 100 + 50 + 0 + 30 = 180.
                                    </div>
                                    <div class="rounded-[1rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-right">
                                        <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-100/70">Total Regu</p>
                                        <p class="mt-1 text-3xl font-black text-cyan-100" x-text="totalScore()"></p>
                                    </div>
                                </div>

                                <div class="hidden rounded-[1.7rem] border border-slate-800 bg-slate-950/55 p-4">
                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="section-kicker">Pilih Soal</p>
                                            <h4 class="mt-2 text-xl font-bold text-white">Input nilai dari satu tombol utama</h4>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            <span class="rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-slate-300">Range: 1 s/d 100</span>
                                            <span class="rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-slate-300" x-text="`${questions.length} soal`"></span>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]">
                                        <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/60 px-4 py-4">
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Soal Aktif</p>
                                            <p class="mt-1 text-3xl font-black text-white" x-text="activeQuestionIndex + 1"></p>
                                            <p class="mt-2 text-sm text-slate-300" x-text="`Total saat ini: ${questions.length} soal`"></p>
                                            <p class="mt-1 text-xs text-slate-500" x-text="questions.length > activeQuestionIndex + 1 ? `Masih ada ${questions.length - activeQuestionIndex - 1} soal berikutnya.` : 'Next akan menambah soal baru jika belum finish.'"></p>
                                        </div>

                                        <div class="flex items-end">
                                            <button type="button" class="primary-button w-full justify-center px-6 py-4 md:w-auto" @click="openQuestion(activeQuestionIndex)">
                                                <?= mtq_icon('pencil', 'h-4 w-4') ?>
                                                Input Nilai Regu
                                            </button>
                                        </div>
                                    </div>

                                    <div class="hidden">
                                        <template x-for="(question, index) in questions" :key="`${question.id}-hidden`">
                                            <div>
                                                <input type="hidden" :name="`questions[${index}][label]`" x-model="question.label">
                                                <input type="hidden" :name="`questions[${index}][package_score]`" x-model="question.package_score">
                                                <template x-for="(opponent, throwIndex) in question.throw_scores" :key="`${question.id}-hidden-throw-${throwIndex}`">
                                                    <input type="hidden" :name="`questions[${index}][throw_scores][${throwIndex}]`" x-model="question.throw_scores[throwIndex]">
                                                </template>
                                                <input type="hidden" :name="`questions[${index}][rebuttal_score]`" x-model="question.rebuttal_score">
                                                <input type="hidden" :name="`questions[${index}][notes]`" x-model="question.notes">
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div x-show="modalOpen" x-cloak class="hidden fixed inset-0 z-50 flex items-center justify-center px-3 py-4 sm:px-4 sm:py-6">
                                    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="closeQuestion()"></div>
                                    <div class="relative w-full max-w-[min(96vw,1100px)] overflow-hidden rounded-[2rem] border border-slate-700 bg-slate-950 shadow-[0_24px_80px_-20px_rgba(0,0,0,0.75)]">
                                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 px-6 py-5">
                                            <div>
                                                <p class="section-kicker">Input Nilai Regu Aktif</p>
                                                <h4 class="mt-2 text-2xl font-bold text-white">
                                                    Soal <span x-text="activeQuestionIndex + 1"></span> untuk regu aktif
                                                </h4>
                                                <p class="mt-2 text-sm text-slate-300">Isi nilai paket soal, lalu lanjut ke lontaran dan rebutan. Contoh: 100 + 0 + 0 + 0 = 100.</p>
                                            </div>
                                            <button type="button" class="secondary-button rounded-xl px-3 py-2" @click="closeQuestion()">
                                                <?= mtq_icon('x', 'h-4 w-4') ?>
                                            </button>
                                        </div>

                                        <div class="p-6">
                                            <div class="grid gap-4 lg:grid-cols-4">
                                                <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/60 px-4 py-4">
                                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Soal</p>
                                                    <p class="mt-2 text-3xl font-black text-white" x-text="activeQuestionIndex + 1"></p>
                                                    <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].package_score" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai soal">
                                                    <p class="mt-2 text-xs text-slate-500">Nilai paket soal untuk regu aktif.</p>
                                                </div>

                                                <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/60 px-4 py-4">
                                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Lontaran 1</p>
                                                    <p class="mt-2 text-sm text-slate-300" x-text="(opponentCards[0] && opponentCards[0].name) ? opponentCards[0].name : '-'"></p>
                                                    <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].throw_scores[0]" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai">
                                                </div>

                                                <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/60 px-4 py-4">
                                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Lontaran 2</p>
                                                    <p class="mt-2 text-sm text-slate-300" x-text="(opponentCards[1] && opponentCards[1].name) ? opponentCards[1].name : '-'"></p>
                                                    <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].throw_scores[1]" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai">
                                                </div>

                                                <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/60 px-4 py-4">
                                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Rebutan</p>
                                                    <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].rebuttal_score" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Masukkan nilai rebutan">
                                                </div>
                                            </div>

                                            <div class="mt-4 rounded-[1.35rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-4">
                                                <p class="text-xs uppercase tracking-[0.18em] text-cyan-100/70">Total Soal Aktif</p>
                                                <p class="mt-1 text-4xl font-black text-cyan-100" x-text="rowTotal(questions[activeQuestionIndex] || {})"></p>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 px-6 py-5">
                                            <div class="text-sm text-slate-300">
                                                <p x-text="activeQuestionIndex + 1 < questions.length ? `Lanjut ke soal ${activeQuestionIndex + 2}` : 'Finish akan menutup sesi input dan siap disimpan'"></p>
                                            </div>
                                            <div class="flex flex-wrap gap-3">
                                                <button type="button" class="secondary-button px-5 py-3" @click="previousQuestion()" :disabled="activeQuestionIndex === 0" :class="activeQuestionIndex === 0 ? 'cursor-not-allowed opacity-50' : ''">
                                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                                    Prev
                                                </button>
                                                <button type="button" class="primary-button px-5 py-3" @click="nextQuestion()">
                                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                                    Next
                                                </button>
                                                <button type="button" class="secondary-button px-5 py-3" @click="closeQuestion()">
                                                    <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                    Finish
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
                                    <div>
                                    <p class="text-sm text-slate-300">Total dihitung otomatis dari seluruh nilai yang diisi pada semua soal.</p>
                                        <p class="mt-1 text-xs text-slate-500">Tekan simpan setelah data lembar MFQ selesai diinput.</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="rounded-[1rem] border border-slate-800 bg-slate-950/60 px-3 py-2 text-right">
                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Total Akhir</p>
                                            <p class="mt-1 text-sm font-bold text-white" x-text="totalScore()"></p>
                                        </div>
                                        <button type="submit" class="primary-button justify-center px-5 py-3">
                                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                            Finish & Simpan
                                        </button>
                                    </div>
                                </div>
                            </form>
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
        function mfqSheetForm(initialState) {
            const sanitizeNumber = (value) => {
                if (value === null || value === undefined || value === '') {
                    return 0;
                }

                const parsed = Number(value);
                return Number.isFinite(parsed) ? Math.trunc(parsed) : 0;
            };

            const normalizeRow = (row, opponentCount) => {
                const throwScores = Array.isArray(row.throw_scores) ? row.throw_scores.slice(0, opponentCount) : [];

                while (throwScores.length < opponentCount) {
                    throwScores.push('');
                }

                return {
                    id: row.id || `q-${Date.now()}-${Math.random().toString(36).slice(2)}`,
                    label: row.label || '',
                    package_score: row.package_score ?? '',
                    throw_scores: throwScores,
                    rebuttal_score: row.rebuttal_score ?? '',
                    notes: row.notes || '',
                };
            };

            const createEmptyQuestion = (index, opponentCount) => normalizeRow({
                id: `q-${index + 1}-${Date.now()}-${Math.random().toString(36).slice(2)}`,
                label: `Soal ${index + 1}`,
                package_score: '',
                throw_scores: Array.from({ length: opponentCount }, () => ''),
                rebuttal_score: '',
                notes: '',
            }, opponentCount);

            return {
                mobileNavOpen: false,
                modalOpen: false,
                activeQuestionIndex: 0,
                storageKey: initialState.storageKey || '',
                opponentCards: Array.isArray(initialState.opponentCards) ? initialState.opponentCards : [],
                questions: [],
                init() {
                    const draft = this.loadDraft();
                    const sourceQuestions = Array.isArray(draft?.questions) && draft.questions.length > 0
                        ? draft.questions
                        : (Array.isArray(initialState.questions) && initialState.questions.length > 0
                            ? initialState.questions
                            : Array.from({ length: 3 }, (_, index) => ({
                                id: `q-${index + 1}-${Date.now()}`,
                                label: `Soal ${index + 1}`,
                                package_score: '',
                                throw_scores: Array.from({ length: Number(initialState.opponentCount || 0) }, () => ''),
                                rebuttal_score: '',
                                notes: '',
                            })));

                    this.questions = sourceQuestions.map((row) => normalizeRow(row, Number(initialState.opponentCount || 0)));
                    this.activeQuestionIndex = Number.isFinite(Number(draft?.activeQuestionIndex))
                        ? Math.max(0, Math.min(Number(draft.activeQuestionIndex), this.questions.length - 1))
                        : 0;
                },
                loadDraft() {
                    if (!this.storageKey || typeof window === 'undefined' || !window.localStorage) {
                        return null;
                    }

                    try {
                        const raw = window.localStorage.getItem(this.storageKey);
                        return raw ? JSON.parse(raw) : null;
                    } catch (error) {
                        return null;
                    }
                },
                persistDraft() {
                    if (!this.storageKey || typeof window === 'undefined' || !window.localStorage) {
                        return;
                    }

                    try {
                        window.localStorage.setItem(this.storageKey, JSON.stringify({
                            questions: this.questions,
                            activeQuestionIndex: this.activeQuestionIndex,
                        }));
                    } catch (error) {
                        // Ignore storage failures, draft saving is best-effort.
                    }
                },
                openQuestion(index) {
                    this.activeQuestionIndex = Math.max(0, Math.min(index, this.questions.length - 1));
                    this.modalOpen = true;
                    this.persistDraft();
                },
                closeQuestion() {
                    this.modalOpen = false;
                    this.persistDraft();
                },
                previousQuestion() {
                    if (this.activeQuestionIndex > 0) {
                        this.activeQuestionIndex -= 1;
                        this.persistDraft();
                    }
                },
                nextQuestion() {
                    if (this.activeQuestionIndex < this.questions.length - 1) {
                        this.activeQuestionIndex += 1;
                        this.persistDraft();
                        return;
                    }

                    this.questions.push(createEmptyQuestion(this.questions.length, this.opponentCards.length));
                    this.activeQuestionIndex = this.questions.length - 1;
                    this.modalOpen = true;
                    this.persistDraft();
                },
                addQuestion() {
                    this.questions.push(createEmptyQuestion(this.questions.length, this.opponentCards.length));
                    this.activeQuestionIndex = this.questions.length - 1;
                    this.modalOpen = true;
                    this.persistDraft();
                },
                rowTotal(question) {
                    return [
                        sanitizeNumber(question.package_score),
                        ...((question.throw_scores || []).map((score) => sanitizeNumber(score))),
                        sanitizeNumber(question.rebuttal_score),
                    ].reduce((sum, value) => sum + value, 0);
                },
                activeQuestion() {
                    return this.questions[this.activeQuestionIndex] || null;
                },
                totalScore() {
                    return this.questions.reduce((sum, question) => sum + this.rowTotal(question), 0);
                },
            };
        }
    </script>
</body>
</html>
