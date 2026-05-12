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
$selectionState = $selectionState ?? ['competition_category_id' => null, 'participant_ids' => []];
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
    })">
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
                    <?php foreach ($navigation as $item): ?>
                        <a href="<?= e($item['href']) ?>" class="sidebar-link <?= $item['active'] ? 'sidebar-link-active' : '' ?>">
                            <span class="icon-chip h-10 w-10 rounded-xl"><?= mtq_icon($item['icon'], 'h-4 w-4') ?></span>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Regu Dipilih</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($selectedParticipants->count()) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Regu Aktif</p>
                        <p class="mt-2 text-sm font-bold text-white"><?= e($selectedParticipant?->name ?? '-') ?></p>
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
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Regu Sesi</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['participant_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('book-open') ?></div><p class="mt-4 text-sm text-slate-400">Kolom Lontaran</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($opponents->count()) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Regu Aktif</p><p class="mt-2 text-xl font-extrabold text-cyan-200"><?= e($selectedParticipant?->name ?? '-') ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Skor Terakhir</p><p class="mt-2 text-3xl font-extrabold text-emerald-300"><?= e($summaryStats['selected_latest']) ?></p></div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div class="space-y-6">
                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('fingerprint') ?></div>
                                    <div>
                                        <p class="section-kicker">Regu Aktif</p>
                                        <h3 class="mt-2 text-2xl font-bold text-white"><?= e($selectedParticipant?->name ?? 'Belum dipilih') ?></h3>
                                        <p class="mt-2 text-sm text-slate-300"><?= e($selectedParticipantCategory) ?></p>
                                    </div>
                                </div>
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                    Babak <?= e($filters['judging_round'] ?? 'Penyisihan') ?>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <?php foreach ($selectedParticipants as $participant): ?>
                                    <?php
                                        $activeLink = route('scoring.mfq', array_filter([
                                            'competition_category_id' => $selectedCategory?->id,
                                            'participant_id' => $participant->id,
                                            'judging_round' => $filters['judging_round'] ?? 'Penyisihan',
                                        ]));
                                    ?>
                                    <a href="<?= e($activeLink) ?>" class="rounded-[1.35rem] border px-4 py-4 transition <?= (int) ($selectedParticipant?->id ?? 0) === (int) $participant->id ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_12px_35px_-20px_rgba(34,211,238,0.75)]' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30' ?>">
                                        <p class="font-semibold text-white"><?= e($participant->name) ?></p>
                                        <p class="mt-1 text-xs text-slate-400"><?= e($participant->registration_number) ?></p>
                                        <p class="mt-1 text-xs text-cyan-200"><?= e($participant->district?->name ?? '-') ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="section-kicker">Lembar Skor MFQ</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">15 soal, 1 baris = 1 soal</h3>
                                    <p class="mt-2 text-sm text-slate-300">Kolom paket dan rebutan untuk regu aktif. Kolom lontaran disesuaikan dengan jumlah regu lawan yang ada di sesi.</p>
                                </div>
                                <div class="rounded-[1rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-right">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-100/70">Total Sementara</p>
                                    <p class="mt-1 text-2xl font-black text-cyan-100" x-text="totalScore()"></p>
                                </div>
                            </div>

                            <form method="POST" action="<?= e(route('scoring.mfq.store')) ?>" class="mt-6 space-y-5">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="participant_id" value="<?= e($selectedParticipant?->id ?? '') ?>">

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Dewan Hakim / Operator</label>
                                        <input name="judge_name" type="text" value="<?= e(old('judge_name', $judgeNameDefault)) ?>" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20">
                                        <?php if ($errors->has('judge_name')): ?>
                                            <p class="mt-2 text-sm text-rose-300"><?= e($errors->first('judge_name')) ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Babak penilaian</label>
                                        <select name="judging_round" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20">
                                            <?php foreach (['Penyisihan', 'Final'] as $roundLabel): ?>
                                                <option value="<?= e($roundLabel) ?>" <?= (string) old('judging_round', $filters['judging_round'] ?? 'Penyisihan') === $roundLabel ? 'selected' : '' ?>>
                                                    <?= e($roundLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($errors->has('judging_round')): ?>
                                            <p class="mt-2 text-sm text-rose-300"><?= e($errors->first('judging_round')) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan umum</label>
                                    <textarea name="remarks" rows="3" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20" placeholder="Contoh: soal 4 perlu koreksi kecil pada nilai lontaran."><?= e(old('remarks')) ?></textarea>
                                    <?php if ($errors->has('remarks')): ?>
                                        <p class="mt-2 text-sm text-rose-300"><?= e($errors->first('remarks')) ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="rounded-[1.5rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
                                    Format ini mengikuti lembar Excel MFQ. Isi nilai per sel dengan angka bulat, lalu sistem menjumlahkan total otomatis per baris dan per kolom.
                                </div>

                                <div class="overflow-x-auto rounded-[1.7rem] border border-slate-800 bg-slate-950/55 p-4">
                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="section-kicker">Grid Soal</p>
                                            <h4 class="mt-2 text-xl font-bold text-white">Nilai paket, lontaran, rebutan</h4>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            <span class="rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-slate-300">Range: 1 s/d 100</span>
                                            <span class="rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-slate-300">15 soal</span>
                                        </div>
                                    </div>

                                    <table class="min-w-full border-separate border-spacing-y-3 text-left">
                                        <thead>
                                            <tr class="text-xs uppercase tracking-[0.18em] text-slate-500">
                                                <th class="px-3 py-2">No</th>
                                                <th class="px-3 py-2">Soal Paket</th>
                                                <?php foreach ($opponentCards as $index => $opponentCard): ?>
                                                    <th class="px-3 py-2"><?= e($opponentCard['label']) ?></th>
                                                <?php endforeach; ?>
                                                <th class="px-3 py-2">Rebutan</th>
                                                <th class="px-3 py-2">Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(question, index) in questions" :key="question.id">
                                                <tr class="align-top">
                                                    <td class="px-3 py-2 text-sm font-semibold text-slate-300">
                                                        <input type="hidden" :name="`questions[${index}][label]`" x-model="question.label">
                                                        <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-3 py-3 text-center text-white" x-text="index + 1"></div>
                                                    </td>

                                                    <td class="px-3 py-2">
                                                        <input :name="`questions[${index}][package_score]`" x-model="question.package_score" type="number" step="1" min="1" max="100" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="1">
                                                    </td>

                                                    <template x-for="(opponent, throwIndex) in question.throw_scores" :key="`${question.id}-throw-${throwIndex}`">
                                                        <td class="px-3 py-2">
                                                            <input :name="`questions[${index}][throw_scores][${throwIndex}]`" x-model="question.throw_scores[throwIndex]" type="number" step="1" min="1" max="100" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="1">
                                                        </td>
                                                    </template>

                                                    <td class="px-3 py-2">
                                                        <input :name="`questions[${index}][rebuttal_score]`" x-model="question.rebuttal_score" type="number" step="1" min="1" max="100" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="1">
                                                    </td>

                                                    <td class="px-3 py-2">
                                                        <div class="rounded-2xl border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-right">
                                                            <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-100/70">Total</p>
                                                            <p class="mt-1 text-lg font-bold text-cyan-100" x-text="rowTotal(question)"></p>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td colspan="<?= 4 + max(0, $opponents->count()) ?>" class="px-3 pb-4">
                                                        <div class="rounded-2xl border border-slate-800 bg-slate-950/55 px-4 py-3">
                                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan Soal <span x-text="index + 1"></span></label>
                                                            <textarea :name="`questions[${index}][notes]`" x-model="question.notes" rows="2" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20" placeholder="Opsional, misalnya alasan pemberian nilai atau koreksi hakim."></textarea>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
                                    <div>
                                        <p class="text-sm text-slate-300">Total dihitung otomatis dari seluruh nilai yang diisi pada 15 baris soal.</p>
                                        <p class="mt-1 text-xs text-slate-500">Tekan simpan setelah data lembar MFQ selesai diinput.</p>
                                    </div>
                                    <div class="rounded-[1rem] border border-slate-800 bg-slate-950/60 px-3 py-2 text-right">
                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Total Akhir</p>
                                        <p class="mt-1 text-sm font-bold text-white" x-text="totalScore()"></p>
                                    </div>
                                    <button type="submit" class="primary-button justify-center px-5 py-3">
                                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                        Simpan Penilaian MFQ
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="glass-card rounded-[2rem] p-6">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Regu Sesi</p>
                            <div class="mt-5 space-y-3">
                                <?php foreach ($selectedParticipants as $participant): ?>
                                    <a href="<?= e(route('scoring.mfq', [
                                        'competition_category_id' => $selectedCategory?->id,
                                        'participant_id' => $participant->id,
                                        'judging_round' => $filters['judging_round'] ?? 'Penyisihan',
                                    ])) ?>" class="block rounded-[1.35rem] border px-4 py-3 transition <?= (int) ($selectedParticipant?->id ?? 0) === (int) $participant->id ? 'border-cyan-300 bg-cyan-400/10' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30' ?>">
                                        <p class="font-semibold text-white"><?= e($participant->name) ?></p>
                                        <p class="mt-1 text-xs text-slate-400"><?= e($participant->registration_number) ?></p>
                                        <p class="mt-1 text-xs text-cyan-200"><?= e($participant->district?->name ?? '-') ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Kolom Lontaran</p>
                            <div class="mt-5 space-y-3">
                                <?php if ($opponentCards === []): ?>
                                    <div class="data-card text-sm text-slate-300">Belum ada regu lawan untuk sesi ini.</div>
                                <?php endif; ?>

                                <?php foreach ($opponentCards as $opponentCard): ?>
                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                                        <p class="font-semibold text-white"><?= e($opponentCard['label']) ?></p>
                                        <p class="mt-1 text-sm text-slate-300"><?= e($opponentCard['name']) ?></p>
                                        <p class="mt-1 text-xs text-cyan-200"><?= e($opponentCard['registration_number']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                                <div>
                                    <p class="section-kicker">Riwayat Nilai</p>
                                    <p class="mt-1 text-sm text-slate-300">Entri terbaru untuk regu aktif.</p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <?php if ($recentScores->isEmpty()): ?>
                                    <div class="data-card text-sm text-slate-300">Belum ada riwayat penilaian untuk regu ini.</div>
                                <?php endif; ?>

                                <?php foreach ($recentScores as $score): ?>
                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/45 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-white"><?= e($score->judge_name) ?></p>
                                                <p class="mt-1 text-xs text-slate-400"><?= e(optional($score->submitted_at)->format('d M Y H:i')) ?></p>
                                            </div>
                                            <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                                <?= e(number_format((int) $score->score, 0)) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Panduan Teknis</p>
                            <div class="mt-5 space-y-4">
                                <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                    <p class="font-semibold text-white">Struktur Excel</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">Format ini meniru lembar `Tampil` pada workbook: satu baris untuk satu soal, lalu nilai dikumpulkan ke kolom jumlah per regu.</p>
                                </div>
                                <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                    <p class="font-semibold text-white">Input Angka</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">Gunakan angka positif atau negatif sesuai keputusan hakim. Sel kosong dianggap nol saat total dihitung.</p>
                                </div>
                                <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                    <p class="font-semibold text-white">Pindah Regu</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">Klik kartu regu di panel kanan untuk mengganti regu aktif tanpa kehilangan pilihan sesi MFQ.</p>
                                </div>
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

            return {
                mobileNavOpen: false,
                questions: Array.isArray(initialState.questions) && initialState.questions.length > 0
                    ? initialState.questions.map((row) => normalizeRow(row, Number(initialState.opponentCount || 0)))
                    : Array.from({ length: 15 }, (_, index) => normalizeRow({
                        id: `q-${index + 1}-${Date.now()}`,
                        label: `Soal ${index + 1}`,
                        package_score: '',
                        throw_scores: Array.from({ length: Number(initialState.opponentCount || 0) }, () => ''),
                        rebuttal_score: '',
                        notes: '',
                    }, Number(initialState.opponentCount || 0))),
                rowTotal(question) {
                    return [
                        sanitizeNumber(question.package_score),
                        ...((question.throw_scores || []).map((score) => sanitizeNumber(score))),
                        sanitizeNumber(question.rebuttal_score),
                    ].reduce((sum, value) => sum + value, 0);
                },
                totalScore() {
                    return this.questions.reduce((sum, question) => sum + this.rowTotal(question), 0);
                },
            };
        }
    </script>
</body>
</html>
