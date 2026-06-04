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
$openInputModal = $openInputModal ?? false;
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
        districtCards: <?= e(json_encode($selectedDistrictCards, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
        activeParticipantId: <?= e((string) ($selectedParticipant?->id ?? '')) ?>,
        openInputModal: <?= e($openInputModal ? 'true' : 'false') ?>,
        draftKeyPrefix: <?= e(json_encode('mfq.sheet.'.($selectedCategory?->id ?? 'draft').'.'.($selectionJudgingRound ?? 'Penyisihan').'.'.md5((string) $selectionSessionName))) ?>,
        totalsKey: <?= e(json_encode('mfq.sheet.totals.'.($selectedCategory?->id ?? 'draft').'.'.($selectionJudgingRound ?? 'Penyisihan').'.'.md5((string) $selectionSessionName))) ?>,
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

                <?php if ($selectedCategory): ?>
                <div class="mt-4 rounded-[1.4rem] border border-cyan-400/20 bg-cyan-400/10 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center rounded-full bg-cyan-400/20 p-2">
                            <?= mtq_icon('check-circle', 'h-4 w-4 text-cyan-300') ?>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-cyan-200/70">Golongan MFQ (Dipilih di Step 1)</p>
                            <p class="mt-1 text-lg font-bold text-cyan-100"><?= e(trim((string) $selectedCategory->branch.' - '.(string) $selectedCategory->name)) ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

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
                            <?php if ($selectedCategory): ?>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1.5 text-xs font-semibold text-cyan-200">
                                    <?= mtq_icon('check-circle', 'h-3 w-3') ?>
                                    Golongan: <?= e(trim((string) $selectedCategory->branch.' - '.(string) $selectedCategory->name)) ?>
                                </span>
                            </div>
                            <?php endif; ?>
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

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="section-kicker">Regu MFQ</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Kartu regu tetap di halaman utama</h3>
                            <p class="mt-2 text-sm text-slate-300">Klik tombol di bawah kartu untuk membuka modal input satu baris untuk regu tersebut.</p>
                        </div>
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?= e(count($selectedDistrictCards)) ?> regu
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(17rem,1fr))]">
                        <?php foreach ($selectedDistrictCards as $index => $districtCard): ?>
                            <?php $reguNumber = $index + 1; ?>
                            <?php $isActiveDistrictCard = (int) ($selectedDistrict?->id ?? 0) === (int) $districtCard['district_id']; ?>
                            <div class="group flex h-full flex-col rounded-[1.35rem] border px-4 py-4 transition <?= $isActiveDistrictCard ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_12px_35px_-20px_rgba(34,211,238,0.75)] ring-1 ring-cyan-300/40' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30' ?>">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[11px] uppercase tracking-[0.2em] text-cyan-200/80">Regu <?= e($reguNumber) ?></p>
                                        <p class="mt-1 text-lg font-bold text-white"><?= e($districtCard['district_name']) ?></p>
                                        <p class="mt-1 text-xs text-slate-400"><?= e($districtCard['participant_count']) ?> peserta regu</p>
                                        <p class="mt-2 text-xs uppercase tracking-[0.18em] text-fuchsia-200/80">Nomor Lot</p>
                                        <p class="mt-1 text-sm font-bold text-fuchsia-100"><?= e($districtCard['representative_lot_number'] ?? '-') ?></p>
                                    </div>
                                    <span class="status-pill <?= $isActiveDistrictCard ? 'border-cyan-300/30 bg-cyan-300/15 text-cyan-100' : 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100' ?>">
                                        <?= $isActiveDistrictCard ? 'Aktif' : 'Klik' ?>
                                    </span>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100">
                                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-cyan-300"></span>
                                        Total Saat Ini
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1 text-xs font-semibold text-white" <?= $isActiveDistrictCard ? 'x-text="totalScore().toFixed(2)"' : 'x-text="districtDraftTotal(' . (int) $districtCard['representative_id'] . ', ' . e(json_encode($districtCard['score_value'] ?? '0.00')) . ')"' ?>>
                                        <?= e($districtCard['score_value'] ?? '0.00') ?>
                                    </span>
                                </div>
                                <div class="mt-3 rounded-2xl border border-slate-800 bg-slate-900/60 px-3 py-3">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Perwakilan</p>
                                    <p class="mt-1 font-semibold text-white"><?= e($districtCard['representative_name']) ?></p>
                                    <p class="mt-1 text-xs text-slate-400"><?= e($districtCard['representative_registration_number']) ?></p>
                                </div>
                                <button type="button" class="mt-4 secondary-button w-full justify-center rounded-xl px-4 py-3" @click="openSheetModal('<?= e($districtCard['representative_id']) ?>')">
                                    <?= mtq_icon('pencil', 'h-4 w-4') ?>
                                    Tombol Input Nilai (Modal)
                                </button>
                                <div class="mt-4 rounded-[1.25rem] border border-slate-800 bg-slate-950/55 px-3 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Rincian Nilai</p>
                                        <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-200" x-text="districtDraftRows(<?= (int) $districtCard['representative_id'] ?>).length ? `${districtDraftRows(<?= (int) $districtCard['representative_id'] ?>).length} soal` : 'Belum ada'"></span>
                                    </div>
                                    <div class="mt-3 overflow-hidden rounded-xl border border-slate-800">
                                        <template x-if="districtDraftRows(<?= (int) $districtCard['representative_id'] ?>).length">
                                            <div class="overflow-x-auto">
                                                <table class="w-full min-w-[520px] border-collapse text-[11px]">
                                                    <colgroup>
                                                        <col style="width: 88px">
                                                        <col>
                                                        <col>
                                                        <col>
                                                        <col>
                                                    </colgroup>
                                                    <thead>
                                                        <tr class="bg-slate-900/90 text-slate-300">
                                                            <th class="border border-slate-800 px-2 py-2 text-left whitespace-nowrap">Soal</th>
                                                            <th class="border border-slate-800 px-2 py-2">Paket</th>
                                                            <th class="border border-slate-800 px-2 py-2">L1</th>
                                                            <th class="border border-slate-800 px-2 py-2">L2</th>
                                                            <th class="border border-slate-800 px-2 py-2">Reb</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="(row, rowIndex) in districtDraftRows(<?= (int) $districtCard['representative_id'] ?>)" :key="row.id">
                                                            <tr class="text-slate-200">
                                                                <td class="border border-slate-800 px-2 py-2 text-left align-top relative">
                                                                    <div class="pr-8">
                                                                        <span class="text-[11px] whitespace-nowrap" x-text="`Soal ${rowIndex + 1}`"></span>
                                                                    </div>
                                                                    <button type="button"
                                                                        class="absolute right-1 top-1 inline-flex h-6 w-6 items-center justify-center rounded-full border border-cyan-400/20 bg-cyan-400/10 text-cyan-100 transition hover:border-cyan-300 hover:bg-cyan-300/15"
                                                                        title="Edit soal"
                                                                        @click="editDistrictDraftRow(<?= (int) $districtCard['representative_id'] ?>, rowIndex)">
                                                                        <?= mtq_icon('pencil', 'h-3 w-3') ?>
                                                                    </button>
                                                                </td>
                                                                <td class="border border-slate-800 px-2 py-2" x-text="row.package_score || '-'"></td>
                                                                <td class="border border-slate-800 px-2 py-2" x-text="row.throw_scores?.[0] || '-'"></td>
                                                                <td class="border border-slate-800 px-2 py-2" x-text="row.throw_scores?.[1] || '-'"></td>
                                                                <td class="border border-slate-800 px-2 py-2" x-text="row.rebuttal_score || '-'"></td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>
                                        <div class="px-2 py-3 text-xs text-slate-500" x-show="districtDraftRows(<?= (int) $districtCard['representative_id'] ?>).length === 0">
                                            Belum ada nilai yang diinput.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-[1.5rem] border border-cyan-400/16 bg-cyan-400/10 px-5 py-4">
                        <div>
                            <p class="section-kicker">Finish Tahap 2</p>
                            <p class="mt-2 text-sm text-slate-200">Nilai modal disimpan dulu sebagai draft di kartu regu. Tombol ini mengirim seluruh draft aktif ke database.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="rounded-[1rem] border border-slate-800 bg-slate-950/60 px-3 py-2 text-right">
                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Regu Aktif</p>
                                <p class="mt-1 text-sm font-bold text-white" x-text="activeDistrictName()"></p>
                            </div>
                            <button type="button" class="primary-button justify-center px-5 py-3" @click="showRankingModal()">
                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                Finish & Kirim DB
                            </button>
                        </div>
                    </div>

                    <form x-ref="finishForm" method="POST" action="<?= e(route('scoring.mfq.store')) ?>" class="hidden">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="participant_id" :value="activeParticipantId">
                        <input type="hidden" name="judge_name" value="<?= e($selectionJudgeName) ?>">
                        <input type="hidden" name="judging_round" value="<?= e($selectionJudgingRound) ?>">
                        <input type="hidden" name="remarks" value="<?= e($selectionRemarks) ?>">
                        <template x-for="(question, index) in questions" :key="question.id">
                            <div>
                                <input type="hidden" :name="`questions[${index}][label]`" x-model="question.label">
                                <input type="hidden" :name="`questions[${index}][package_score]`" x-model="question.package_score">
                                <template x-for="(score, throwIndex) in question.throw_scores" :key="`${question.id}-finish-throw-${throwIndex}`">
                                    <input type="hidden" :name="`questions[${index}][throw_scores][${throwIndex}]`" x-model="question.throw_scores[throwIndex]">
                                </template>
                                <input type="hidden" :name="`questions[${index}][rebuttal_score]`" x-model="question.rebuttal_score">
                                <input type="hidden" :name="`questions[${index}][notes]`" x-model="question.notes">
                            </div>
                        </template>
                    </form>
                </section>

                <section class="space-y-6">
                    <div x-show="sheetModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-3 py-4 sm:px-4 sm:py-6">
                        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="closeSheetModal()"></div>
                        <div class="relative w-full max-w-[min(96vw,1100px)] overflow-hidden rounded-[2rem] border border-slate-700 bg-slate-950 shadow-[0_24px_80px_-20px_rgba(0,0,0,0.75)]">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 px-6 py-5">
                                <div>
                                    <p class="section-kicker">Modal Input Nilai</p>
                                    <h4 class="mt-2 text-2xl font-bold text-white" x-text="activeDistrictName()"><?= e($selectedDistrict?->name ?? 'Kecamatan Aktif') ?></h4>
                                    <p class="mt-2 text-sm text-slate-300">Isi satu baris nilai untuk regu aktif, lalu simpan draft. Modal akan tertutup dan tabel rincian di kartu langsung ter-update.</p>
                                </div>
                                <button type="button" class="secondary-button rounded-xl px-3 py-2" @click="closeSheetModal()">
                                    <?= mtq_icon('x', 'h-4 w-4') ?>
                                </button>
                            </div>

                            <form class="space-y-5 p-6" x-on:input.debounce.250ms="persistDraft()" x-on:submit.prevent="saveQuestionDraft()">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="participant_id" :value="activeParticipantId">
                                <input type="hidden" name="judge_name" value="<?= e($selectionJudgeName) ?>">
                                <input type="hidden" name="judging_round" value="<?= e($selectionJudgingRound) ?>">
                                <input type="hidden" name="remarks" value="<?= e($selectionRemarks) ?>">
                                <template x-for="(question, index) in questions" :key="question.id">
                                    <div class="hidden">
                                        <input type="hidden" :name="`questions[${index}][label]`" x-model="question.label">
                                        <input type="hidden" :name="`questions[${index}][package_score]`" x-model="question.package_score">
                                        <input type="hidden" :name="`questions[${index}][throw_scores][0]`" x-model="question.throw_scores[0]">
                                        <input type="hidden" :name="`questions[${index}][throw_scores][1]`" x-model="question.throw_scores[1]">
                                        <input type="hidden" :name="`questions[${index}][rebuttal_score]`" x-model="question.rebuttal_score">
                                        <input type="hidden" :name="`questions[${index}][notes]`" x-model="question.notes">
                                    </div>
                                </template>

                                <div class="rounded-[1.5rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
                                    Format ini mengikuti lembar Excel MFQ. Isi satu soal per submit, lalu lanjut ke soal berikutnya untuk regu yang sama.
                                </div>

                                <div class="overflow-x-auto rounded-[1.5rem] border border-slate-800 bg-slate-950/60">
                                    <table class="min-w-[760px] w-full border-collapse text-center">
                                        <thead>
                                            <tr class="text-sm font-semibold">
                                                <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-orange-200">Soal Paket</th>
                                                <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-emerald-200">Lontaran 1</th>
                                                <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-sky-200">Lontaran 2</th>
                                                <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-rose-200">Rebutan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="text-sm">
                                                <td class="border border-slate-700 bg-slate-950/80 px-3 py-3">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-orange-200/70">Soal <span x-text="activeQuestionIndex + 1"></span></div>
                                                    <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].package_score" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-center text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai paket">
                                                </td>
                                                <td class="border border-slate-700 bg-slate-950/80 px-3 py-3">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-emerald-200/70">Lontaran 1</div>
                                                    <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].throw_scores[0]" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-center text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai">
                                                </td>
                                                <td class="border border-slate-700 bg-slate-950/80 px-3 py-3">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-sky-200/70">Lontaran 2</div>
                                                    <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].throw_scores[1]" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-center text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai">
                                                </td>
                                                <td class="border border-slate-700 bg-slate-950/80 px-3 py-3">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-rose-200/70">Rebutan</div>
                                                    <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].rebuttal_score" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-center text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-4">
                                    <div class="text-sm text-slate-300">
                                        <p>Total akan tersimpan sebagai draft untuk regu yang sedang aktif.</p>
                                        <p class="mt-1 text-xs text-slate-500">Setelah simpan, modal ditutup dan kamu bisa lanjut soal berikutnya.</p>
                                    </div>
                                    <div class="rounded-[1rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-right">
                                        <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-100/70">Total Regu</p>
                                        <p class="mt-1 text-3xl font-black text-cyan-100" x-text="totalScore().toFixed(2)"></p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-800 pt-5">
                                    <button type="button" class="secondary-button px-5 py-3" @click="closeSheetModal()">
                                        <?= mtq_icon('x', 'h-4 w-4') ?>
                                        Tutup
                                    </button>
                                    <button type="button" class="primary-button justify-center px-5 py-3" @click="saveQuestionDraft()">
                                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                        Simpan Draft
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Modal Ranking -->
                <section x-show="rankingModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-3 py-4 sm:px-4 sm:py-6">
                    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="rankingModalOpen = false"></div>
                    <div class="relative w-full max-w-[min(96vw,600px)] overflow-hidden rounded-[2rem] border border-cyan-400/30 bg-slate-950 shadow-[0_24px_80px_-20px_rgba(0,0,0,0.75)]">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 px-6 py-5">
                            <div>
                                <p class="section-kicker">Peringkat Sesi</p>
                                <h4 class="mt-2 text-2xl font-bold text-white"><?= e($selectionSessionName ?: 'Sesi MFQ') ?></h4>
                                <p class="mt-2 text-sm text-slate-300"><?= e($selectionJudgingRound) ?></p>
                            </div>
                            <button type="button" class="secondary-button rounded-xl px-3 py-2" @click="rankingModalOpen = false">
                                <?= mtq_icon('x', 'h-4 w-4') ?>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <template x-for="(rank, index) in computedRankings" :key="rank.districtId">
                                <div class="relative overflow-hidden rounded-2xl border px-5 py-4 transition-all"
                                    :class="index === 0 ? 'border-amber-400/50 bg-gradient-to-r from-amber-500/20 via-amber-400/10 to-transparent shadow-[0_8px_30px_-10px_rgba(251,191,36,0.4)]' : 'border-slate-700/80 bg-slate-900/50'">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-4">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-full border-2 font-black"
                                                :class="index === 0 ? 'border-amber-400 bg-amber-400/20 text-2xl text-amber-300' : index === 1 ? 'border-slate-400 bg-slate-400/10 text-xl text-slate-300' : index === 2 ? 'border-orange-600 bg-orange-600/10 text-lg text-orange-400' : 'border-slate-600 bg-slate-600/10 text-base text-slate-400'">
                                                <span x-text="index + 1"></span>
                                            </div>
                                            <div>
                                                <p class="text-lg font-bold text-white" x-text="rank.districtName"></p>
                                                <p class="text-sm text-slate-400" x-text="rank.representativeName"></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-3xl font-black" :class="index === 0 ? 'text-amber-300' : 'text-white'" x-text="Number(rank.total).toFixed(0)"></p>
                                            <p class="text-xs uppercase tracking-[0.15em] text-slate-500">poin</p>
                                        </div>
                                    </div>
                                    <div x-show="index === 0" class="absolute right-0 top-0 -translate-y-1 translate-x-1">
                                        <div class="flex items-center gap-1 rounded-full bg-amber-400/20 px-3 py-1">
                                            <span class="text-lg">🏆</span>
                                            <span class="text-sm font-bold text-amber-300">PEMENANG</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-800 px-6 py-5">
                            <button type="button" class="secondary-button px-5 py-3" @click="rankingModalOpen = false">
                                <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                Kembali Edit
                            </button>
                            <button type="button" class="primary-button justify-center px-6 py-3" @click="submitRanking()">
                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                Konfirmasi & Simpan
                            </button>
                        </div>
                    </div>
                </section>

                <section class="hidden space-y-6">
                    <div x-show="sheetModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-3 py-4 sm:px-4 sm:py-6">
                        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="closeSheetModal()"></div>
                        <div class="relative w-full max-w-[min(96vw,1280px)] max-h-[92vh] overflow-y-auto rounded-[2rem] border border-slate-700 bg-slate-950 shadow-[0_24px_80px_-20px_rgba(0,0,0,0.75)]">
                            <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="section-kicker">Modal Lembar Skor MFQ</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Input nilai tanpa keluar dari modal</h3>
                                <p class="mt-2 text-sm text-slate-300">Pilih regu lain dari baris ini kapan saja. Draft tiap regu tetap tersimpan terpisah.</p>
                            </div>
                            <button type="button" class="secondary-button rounded-xl px-3 py-2" @click="closeSheetModal()">
                                <?= mtq_icon('x', 'h-4 w-4') ?>
                            </button>
                        </div>

                        <div class="mt-5 rounded-[1.5rem] border border-slate-800 bg-slate-950/60 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Pilih Regu di Dalam Modal</p>
                            <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                <template x-for="card in districtCards" :key="card.representative_id">
                                    <button type="button"
                                        class="rounded-[1.1rem] border px-4 py-3 text-left transition"
                                        :class="String(card.representative_id) === String(activeParticipantId) ? 'border-cyan-300 bg-cyan-400/10 ring-1 ring-cyan-300/30' : 'border-slate-800 bg-slate-950/70 hover:border-cyan-400/30'"
                                        @click="loadParticipant(card.representative_id)">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-200/80" x-text="card.district_name"></p>
                                                <p class="mt-1 text-sm font-semibold text-white" x-text="card.representative_name"></p>
                                                <p class="mt-1 text-xs text-slate-400" x-text="card.participant_count + ' peserta regu'"></p>
                                            </div>
                                            <span class="rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-[11px] font-semibold text-white" x-text="districtTotal(card.representative_id, '0.00')"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('fingerprint') ?></div>
                                <div>
                                    <p class="section-kicker">Kecamatan Aktif</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white" x-text="activeDistrictName()"><?= e($selectedDistrict?->name ?? 'Belum dipilih') ?></h3>
                                    <p class="mt-2 text-sm text-slate-300" x-text="activeDistrictRepresentative()"><?= e($selectedParticipantCategory) ?></p>
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
                                            'open_input_modal' => 1,
                                        ]));
                                    ?>
                                    <?php $isActiveDistrictCard = (int) ($selectedDistrict?->id ?? 0) === (int) $districtCard['district_id']; ?>
                                    <div class="group flex h-full flex-col rounded-[1.35rem] border px-4 py-4 transition <?= $isActiveDistrictCard ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_12px_35px_-20px_rgba(34,211,238,0.75)] ring-1 ring-cyan-300/40' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30' ?>">
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
                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100">
                                                <span class="inline-flex h-1.5 w-1.5 rounded-full bg-cyan-300"></span>
                                                Total Saat Ini
                                            </span>
                                            <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1 text-xs font-semibold text-white" <?= $isActiveDistrictCard ? 'x-text="totalScore().toFixed(2)"' : 'x-text="districtTotal(' . (int) $districtCard['representative_id'] . ', ' . e(json_encode($districtCard['score_value'] ?? '0.00')) . ')"' ?>>
                                                <?= e($districtCard['score_value'] ?? '0.00') ?>
                                            </span>
                                        </div>
                                        <div class="mt-3 rounded-2xl border border-slate-800 bg-slate-900/60 px-3 py-3">
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Perwakilan</p>
                                            <p class="mt-1 font-semibold text-white"><?= e($districtCard['representative_name']) ?></p>
                                            <p class="mt-1 text-xs text-slate-400"><?= e($districtCard['representative_registration_number']) ?></p>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between gap-3">
                                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Input Nilai Regu <?= e($reguNumber) ?></span>
                                        </div>
                                        <button type="button" class="mt-4 secondary-button w-full justify-center rounded-xl px-4 py-3" @click="openSheetModal('<?= e($districtCard['representative_id']) ?>')">
                                            <?= mtq_icon('pencil', 'h-4 w-4') ?>
                                            Tombol Input Nilai (Modal)
                                        </button>
                                    </div>
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
                                <button type="button" class="secondary-button rounded-xl px-4 py-3" @click="closeSheetModal()">
                                    <?= mtq_icon('x', 'h-4 w-4') ?>
                                    Tutup Modal
                                </button>
                                <div class="rounded-[1rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-right">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-100/70">Total Sementara</p>
                                    <p class="mt-1 text-2xl font-black text-cyan-100" x-text="totalScore()"></p>
                                </div>
                            </div>
                        </div>

                            <form method="POST" action="<?= e(route('scoring.mfq.store')) ?>" class="mt-6 space-y-5" x-on:input.debounce.250ms="persistDraft()">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="participant_id" :value="activeParticipantId">
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
                                                <th colspan="5" class="border border-slate-700 bg-slate-900/90 px-3 py-3 text-2xl font-bold tracking-wide text-white" x-text="activeDistrictName()"><?= e($selectedDistrict?->name ?? 'Kecamatan Aktif') ?></th>
                                            </tr>
                                            <tr>
                                                <th colspan="5" class="border border-slate-700 bg-slate-900/70 px-3 py-2 text-xl font-semibold tracking-[0.18em] text-cyan-100">Nomor Lot <span x-text="activeDistrictCard()?.representative_lot_number || '—'"><?= e($selectedDistrictLotNumber) ?></span></th>
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
                                            <div class="overflow-x-auto rounded-[1.5rem] border border-slate-800 bg-slate-950/60">
                                                <table class="min-w-[760px] w-full border-collapse text-center">
                                                    <thead>
                                                        <tr class="text-sm font-semibold">
                                                            <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-orange-200">Soal Paket</th>
                                                            <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-emerald-200">Lontaran 1</th>
                                                            <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-sky-200">Lontaran 2</th>
                                                            <th class="border border-slate-700 bg-slate-900/80 px-3 py-3 text-rose-200">Rebutan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="text-sm">
                                                            <td class="border border-slate-700 bg-slate-950/80 px-3 py-3">
                                                                <div class="text-xs uppercase tracking-[0.18em] text-orange-200/70">Soal <span x-text="activeQuestionIndex + 1"></span></div>
                                                                <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].package_score" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-center text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai paket">
                                                            </td>
                                                            <td class="border border-slate-700 bg-slate-950/80 px-3 py-3">
                                                                <div class="text-xs uppercase tracking-[0.18em] text-emerald-200/70" x-text="(opponentCards[0] && opponentCards[0].name) ? opponentCards[0].name : 'Lontaran 1'"></div>
                                                                <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].throw_scores[0]" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-center text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai">
                                                            </td>
                                                            <td class="border border-slate-700 bg-slate-950/80 px-3 py-3">
                                                                <div class="text-xs uppercase tracking-[0.18em] text-sky-200/70" x-text="(opponentCards[1] && opponentCards[1].name) ? opponentCards[1].name : 'Lontaran 2'"></div>
                                                                <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].throw_scores[1]" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-center text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai">
                                                            </td>
                                                            <td class="border border-slate-700 bg-slate-950/80 px-3 py-3">
                                                                <div class="text-xs uppercase tracking-[0.18em] text-rose-200/70">Rebutan</div>
                                                                <input type="number" step="1" min="1" max="100" x-model="questions[activeQuestionIndex].rebuttal_score" class="mt-3 w-full rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-3 text-center text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nilai">
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
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
                sheetModalOpen: Boolean(initialState.openInputModal),
                rankingModalOpen: false,
                activeQuestionIndex: 0,
                activeParticipantId: String(initialState.activeParticipantId || ''),
                modalAdvanceAfterSave: true,
                districtCards: Array.isArray(initialState.districtCards) ? initialState.districtCards : [],
                draftKeyPrefix: initialState.draftKeyPrefix || '',
                totalsKey: initialState.totalsKey || '',
                opponentCards: Array.isArray(initialState.opponentCards) ? initialState.opponentCards : [],
                districtTotals: {},
                districtDrafts: {},
                questions: [],
                init() {
                    this.districtTotals = this.loadTotalsIndex();
                    this.districtDrafts = this.loadDistrictDraftsIndex();
                    this.opponentCards = this.buildOpponentCards(this.activeParticipantId);
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
                    this.syncCurrentDistrictTotal();
                },
                buildOpponentCards(participantId) {
                    const activeId = String(participantId || '');

                    return this.districtCards
                        .filter((card) => String(card.representative_id || '') !== activeId)
                        .map((card, index) => ({
                            label: `Lontaran ${index + 1}`,
                            name: card.representative_name || card.district_name || '-',
                            registration_number: card.representative_registration_number || '-',
                        }));
                },
                blankQuestions() {
                    return Array.from({ length: 3 }, (_, index) => normalizeRow({
                        id: `q-${index + 1}-${Date.now()}`,
                        label: `Soal ${index + 1}`,
                        package_score: '',
                        throw_scores: Array.from({ length: Number(this.opponentCards.length || 0) }, () => ''),
                        rebuttal_score: '',
                        notes: '',
                    }, Number(this.opponentCards.length || 0)));
                },
                activeDistrictCard() {
                    return this.districtCards.find((card) => String(card.representative_id || '') === String(this.activeParticipantId || '')) || null;
                },
                activeDistrictName() {
                    return this.activeDistrictCard()?.district_name || 'Belum dipilih';
                },
                activeDistrictRepresentative() {
                    return this.activeDistrictCard()?.representative_name || '-';
                },
                loadParticipant(participantId) {
                    const nextParticipantId = String(participantId || '');
                    if (!nextParticipantId) {
                        return;
                    }

                    this.persistDraft();
                    this.activeParticipantId = nextParticipantId;
                    this.opponentCards = this.buildOpponentCards(this.activeParticipantId);
                    const draft = this.loadDraft();
                    const sourceQuestions = Array.isArray(draft?.questions) && draft.questions.length > 0
                        ? draft.questions
                        : (String(nextParticipantId) === String(initialState.activeParticipantId || '')
                            ? initialState.questions
                            : this.blankQuestions());

                    this.questions = sourceQuestions.map((row) => normalizeRow(row, Number(this.opponentCards.length || 0)));
                    this.activeQuestionIndex = Number.isFinite(Number(draft?.activeQuestionIndex))
                        ? Math.max(0, Math.min(Number(draft.activeQuestionIndex), this.questions.length - 1))
                        : 0;
                    this.sheetModalOpen = true;
                    this.syncCurrentDistrictTotal();
                },
                openSheetModal(participantId = null) {
                    if (participantId && String(participantId) !== String(this.activeParticipantId || '')) {
                        this.loadParticipant(participantId);
                        return;
                    }

                    this.sheetModalOpen = true;
                    this.modalAdvanceAfterSave = true;
                    this.opponentCards = this.buildOpponentCards(this.activeParticipantId);
                    this.persistDraft();
                },
                closeSheetModal() {
                    this.sheetModalOpen = false;
                    this.persistDraft();
                },
                saveQuestionDraft() {
                    this.persistDraft();

                    if (this.modalAdvanceAfterSave && this.activeQuestionIndex < this.questions.length - 1) {
                        this.activeQuestionIndex += 1;
                    }

                    this.sheetModalOpen = false;
                    this.persistDraft();
                },
                editDistrictDraftRow(participantId, rowIndex) {
                    const nextParticipantId = String(participantId || '');
                    if (!nextParticipantId) {
                        return;
                    }

                    this.loadParticipant(nextParticipantId);
                    this.activeQuestionIndex = Math.max(0, Math.min(Number(rowIndex) || 0, this.questions.length - 1));
                    this.modalAdvanceAfterSave = false;
                    this.sheetModalOpen = true;
                    this.persistDraft();
                },
                finishDraft() {
                    this.persistDraft();

                    if (this.$refs.finishForm) {
                        this.$refs.finishForm.requestSubmit();
                    }
                },
                get computedRankings() {
                    const rankings = this.districtCards.map((card) => {
                        const total = this.districtDraftTotal(card.representative_id, '0');
                        return {
                            districtId: card.district_id,
                            districtName: card.district_name,
                            representativeId: card.representative_id,
                            representativeName: card.representative_name,
                            total: parseFloat(total) || 0,
                        };
                    });
                    return rankings.sort((a, b) => b.total - a.total);
                },
                showRankingModal() {
                    this.persistDraft();
                    this.rankingModalOpen = true;
                },
                submitRanking() {
                    this.rankingModalOpen = false;
                    this.persistDraft();
                    if (this.$refs.finishForm) {
                        this.$refs.finishForm.requestSubmit();
                    }
                },
                participantDraftKey(participantId = null) {
                    const keyParticipantId = String(participantId || this.activeParticipantId || 'draft');
                    return `${this.draftKeyPrefix || 'mfq.sheet.draft'}.${keyParticipantId}`;
                },
                loadDraft() {
                    if (!this.draftKeyPrefix || typeof window === 'undefined' || !window.localStorage) {
                        return null;
                    }

                    try {
                        const raw = window.localStorage.getItem(this.participantDraftKey());
                        return raw ? JSON.parse(raw) : null;
                    } catch (error) {
                        return null;
                    }
                },
                loadTotalsIndex() {
                    if (!this.totalsKey || typeof window === 'undefined' || !window.localStorage) {
                        return {};
                    }

                    try {
                        const raw = window.localStorage.getItem(this.totalsKey);
                        const parsed = raw ? JSON.parse(raw) : {};

                        return parsed && typeof parsed === 'object' ? parsed : {};
                    } catch (error) {
                        return {};
                    }
                },
                loadDistrictDraftIndexItem(participantId) {
                    if (!this.draftKeyPrefix || typeof window === 'undefined' || !window.localStorage) {
                        return null;
                    }

                    try {
                        const raw = window.localStorage.getItem(this.participantDraftKey(participantId));
                        return raw ? JSON.parse(raw) : null;
                    } catch (error) {
                        return null;
                    }
                },
                loadDistrictDraftsIndex() {
                    const drafts = {};

                    (Array.isArray(this.districtCards) ? this.districtCards : []).forEach((card) => {
                        const participantId = String(card?.representative_id || '');
                        if (!participantId) {
                            return;
                        }

                        const draft = this.loadDistrictDraftIndexItem(participantId);
                        if (draft) {
                            drafts[participantId] = draft;
                        }
                    });

                    return drafts;
                },
                persistTotalsIndex() {
                    if (!this.totalsKey || typeof window === 'undefined' || !window.localStorage) {
                        return;
                    }

                    try {
                        window.localStorage.setItem(this.totalsKey, JSON.stringify(this.districtTotals));
                    } catch (error) {
                        // Ignore storage failures, index saving is best-effort.
                    }
                },
                syncCurrentDistrictTotal() {
                    if (!this.activeParticipantId) {
                        return;
                    }

                    this.districtTotals[String(this.activeParticipantId)] = this.totalScore().toFixed(2);
                    this.districtDrafts[String(this.activeParticipantId)] = {
                        questions: this.questions,
                        activeQuestionIndex: this.activeQuestionIndex,
                    };
                    this.persistTotalsIndex();
                },
                persistDraft() {
                    if (!this.draftKeyPrefix || typeof window === 'undefined' || !window.localStorage) {
                        return;
                    }

                    try {
                        const payload = {
                            questions: this.questions,
                            activeQuestionIndex: this.activeQuestionIndex,
                        };

                        window.localStorage.setItem(this.participantDraftKey(), JSON.stringify(payload));
                        this.districtDrafts = {
                            ...this.districtDrafts,
                            [String(this.activeParticipantId || '')]: payload,
                        };
                        this.syncCurrentDistrictTotal();
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
                districtDraftQuestions(participantId) {
                    const key = String(participantId || '');
                    const draft = this.districtDrafts[key];
                    const source = Array.isArray(draft?.questions) ? draft.questions : [];

                    return source.filter((row) => this.rowTotal(row) > 0 || [row.package_score, ...(row.throw_scores || []), row.rebuttal_score].some((value) => value !== '' && value !== null && value !== undefined));
                },
                districtDraftRows(participantId) {
                    return this.districtDraftQuestions(participantId);
                },
                districtDraftTotal(participantId, fallback = '0.00') {
                    const key = String(participantId || '');
                    const draftRows = this.districtDraftQuestions(participantId);

                    if (draftRows.length) {
                        return draftRows.reduce((sum, row) => sum + this.rowTotal(row), 0).toFixed(2);
                    }

                    return this.districtTotals[key] ?? fallback;
                },
            };
        }
    </script>
</body>
</html>
