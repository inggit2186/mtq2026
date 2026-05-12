<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'scoring.mfq');
$participants = $participants ?? collect();
$mfqCategories = $mfqCategories ?? collect();
$selectedParticipant = $selectedParticipant ?? null;
$selectedCategory = $selectedCategory ?? null;
$selectedJudgingRound = $selectedJudgingRound ?? 'Penyisihan';
$recentScores = $recentScores ?? collect();
$modeOptions = $modeOptions ?? [];
$statusOptions = $statusOptions ?? [];
$defaultQuestions = $defaultQuestions ?? [];
$summaryStats = $summaryStats ?? ['participant_total' => 0, 'category_total' => 0, 'verified_total' => 0, 'selected_average' => '0.00', 'selected_latest' => '0.00'];
$filters = $filters ?? [];
$judgeNameDefault = $judgeNameDefault ?? (string) $user?->name;
$mfqModeSummary = $mfqModeSummary ?? [];

$oldQuestions = old('questions');
$initialQuestionsSource = is_array($oldQuestions) && $oldQuestions !== [] ? $oldQuestions : $defaultQuestions;
$initialQuestions = [];

foreach (array_values(is_array($initialQuestionsSource) ? $initialQuestionsSource : []) as $index => $question) {
    $initialQuestions[] = [
        'id' => (string) data_get($question, 'id', uniqid('mfq_', true)),
        'label' => (string) data_get($question, 'label', 'Soal '.($index + 1)),
        'mode' => (string) data_get($question, 'mode', 'paket_regu'),
        'status' => (string) data_get($question, 'status', 'blank'),
        'partial_score' => (string) data_get($question, 'partial_score', ''),
        'notes' => (string) data_get($question, 'notes', ''),
    ];
}

if ($initialQuestions === []) {
    $initialQuestions = $defaultQuestions;
}

$selectedParticipantCategory = $selectedParticipant?->category ? trim((string) $selectedParticipant->category->branch.' - '.(string) $selectedParticipant->category->name) : '-';
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

    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
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
                    <p class="section-kicker">Status Operator</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($user?->name) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Form ini dirancang untuk skor MFQ beregu, dengan perhitungan yang mengikuti paket regu dan rebutan.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Siap Input
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Golongan MFQ</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['category_total']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Peserta Terverifikasi</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['verified_total']) ?></p>
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
                            <p class="section-kicker">Ruang Penilaian MFQ</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Form khusus cabang Fahmil Qur'an</h2>
                            <p class="mt-2 text-sm text-slate-300">Isi tiap soal sesuai hasil keputusan hakim, lalu total dihitung otomatis sesuai aturan paket regu dan rebutan.</p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Mode MFQ Aktif
                    </div>
                </header>

                <?php if (session('status')): ?>
                    <div class="glass-card rounded-[1.5rem] border border-emerald-400/20 bg-emerald-400/10 px-5 py-4 text-sm text-emerald-100">
                        <?= e(session('status')) ?>
                    </div>
                <?php endif; ?>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Peserta MFQ</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['participant_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('book-open') ?></div><p class="mt-4 text-sm text-slate-400">Golongan Aktif</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['category_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Siap Dinilai</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($summaryStats['verified_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Skor Terakhir</p><p class="mt-2 text-3xl font-extrabold text-emerald-300"><?= e($summaryStats['selected_latest']) ?></p></div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div class="space-y-6">
                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('fingerprint') ?></div>
                                    <div>
                                        <p class="section-kicker">Identitas Sesi</p>
                                        <h3 class="mt-2 text-2xl font-bold text-white">Pilih peserta, hakim, dan babak</h3>
                                        <p class="mt-2 text-sm text-slate-300">MFQ biasanya berjalan dengan ritme cepat, jadi bagian identitas sesi dibuat sederhana dan mudah dibaca.</p>
                                    </div>
                                </div>
                                <?php if ($selectedCategory): ?>
                                    <div class="status-pill">
                                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                        <?= e(trim((string) $selectedCategory->branch.' - '.(string) $selectedCategory->name)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (session('errors')?->any()): ?>
                                <div class="mt-6 rounded-[1.25rem] border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                                    Periksa kembali isian form, lalu kirim ulang setelah semua status dan nilai per soal sudah benar.
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= e(route('scoring.mfq.store')) ?>" class="mt-6 space-y-5"
                                x-data="mfqScoringForm({
                                    questions: <?= e(json_encode($initialQuestions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                    modeOptions: <?= e(json_encode($modeOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                    statusOptions: <?= e(json_encode($statusOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                })">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Peserta / Regu</label>
                                        <select name="participant_id" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20">
                                            <option value="">Pilih peserta MFQ</option>
                                            <?php foreach ($participants as $participant): ?>
                                                <option value="<?= e($participant->id) ?>" <?= (string) old('participant_id', $filters['participant_id'] ?? '') === (string) $participant->id ? 'selected' : '' ?>>
                                                    <?= e($participant->name.' - '.trim((string) ($participant->category?->branch ?? '-').' | '.(string) ($participant->category?->name ?? '-'))) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($errors->has('participant_id')): ?>
                                            <p class="mt-2 text-sm text-rose-300"><?= e($errors->first('participant_id')) ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Dewan Hakim / Operator</label>
                                        <input name="judge_name" type="text" value="<?= e(old('judge_name', $judgeNameDefault)) ?>" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20" placeholder="Nama dewan hakim">
                                        <?php if ($errors->has('judge_name')): ?>
                                            <p class="mt-2 text-sm text-rose-300"><?= e($errors->first('judge_name')) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Babak penilaian</label>
                                        <select name="judging_round" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20">
                                            <?php foreach (['Penyisihan', 'Final'] as $roundLabel): ?>
                                                <option value="<?= e($roundLabel) ?>" <?= (string) old('judging_round', $selectedJudgingRound) === $roundLabel ? 'selected' : '' ?>>
                                                    <?= e($roundLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($errors->has('judging_round')): ?>
                                            <p class="mt-2 text-sm text-rose-300"><?= e($errors->first('judging_round')) ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan umum</label>
                                        <textarea name="remarks" rows="3" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20" placeholder="Contoh: sesi berlangsung lancar, ada revisi kecil pada soal ke-5."><?= e(old('remarks')) ?></textarea>
                                        <?php if ($errors->has('remarks')): ?>
                                            <p class="mt-2 text-sm text-rose-300"><?= e($errors->first('remarks')) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="rounded-[1.5rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
                                    Nilai per soal mengikuti keputusan akhir hakim. Untuk paket regu, pilih status yang sesuai dengan hasil akhir soal. Untuk rebutan, nilai mutlaknya 100 untuk benar dan -100 untuk salah.
                                </div>

                                <div class="rounded-[1.7rem] border border-slate-800 bg-slate-950/55 p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="section-kicker">Builder Soal</p>
                                            <h4 class="mt-2 text-xl font-bold text-white">Satu kartu per soal</h4>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <button type="button" class="secondary-button px-4 py-2" x-on:click="addQuestion()">
                                                <?= mtq_icon('plus', 'h-4 w-4') ?>
                                                Tambah Soal
                                            </button>
                                            <div class="status-pill">
                                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                                <span x-text="summaryLabel()"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-5 space-y-4">
                                        <template x-for="(question, index) in questions" :key="question.id">
                                            <section class="rounded-[1.5rem] border border-slate-800 bg-slate-950/60 p-4">
                                                <div class="flex flex-wrap items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Soal <span x-text="index + 1"></span></p>
                                                        <input :name="`questions[${index}][label]`" x-model="question.label" type="text" class="mt-2 w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Judul soal">
                                                    </div>
                                                    <button type="button" class="secondary-button px-3 py-2" x-on:click="removeQuestion(index)" x-bind:disabled="questions.length === 1" :class="questions.length === 1 ? 'cursor-not-allowed opacity-50' : ''">
                                                        <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                        Hapus
                                                    </button>
                                                </div>

                                                <div class="mt-4 grid gap-4 lg:grid-cols-[180px_1fr_1fr]">
                                                    <div>
                                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Jenis Soal</label>
                                                        <select :name="`questions[${index}][mode]`" x-model="question.mode" x-on:change="normalizeRow(question)" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20">
                                                            <template x-for="(label, value) in modeOptions" :key="value">
                                                                <option :value="value" x-text="label"></option>
                                                            </template>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Status Jawaban</label>
                                                        <select :name="`questions[${index}][status]`" x-model="question.status" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20">
                                                            <template x-for="(item, value) in statusOptionsFor(question.mode)" :key="value">
                                                                <option :value="value" x-text="item.label"></option>
                                                            </template>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Skor Soal</label>
                                                        <div class="rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-lg font-bold text-cyan-200" x-text="questionScore(question).toFixed(2)"></div>
                                                    </div>
                                                </div>

                                                <div class="mt-4 grid gap-4 lg:grid-cols-[220px_1fr]">
                                                    <div x-show="question.status === 'partial'" x-cloak>
                                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Nilai Proporsional</label>
                                                        <input :name="`questions[${index}][partial_score]`" x-model="question.partial_score" type="number" min="0" max="79.99" step="0.01" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20" placeholder="0 - 79.99">
                                                    </div>

                                                    <div>
                                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan Soal</label>
                                                        <textarea :name="`questions[${index}][notes]`" x-model="question.notes" rows="3" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20" placeholder="Opsional, misalnya alasan lemparan atau catatan jawaban."></textarea>
                                                    </div>
                                                </div>
                                            </section>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
                                    <div>
                                        <p class="text-sm text-slate-300">Total dihitung otomatis dari seluruh kartu soal.</p>
                                        <p class="mt-1 text-xs text-slate-500">Simpan setelah semua soal, status, dan nilai proporsional sudah lengkap.</p>
                                    </div>
                                    <div class="rounded-[1rem] border border-slate-800 bg-slate-950/60 px-3 py-2 text-right">
                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Total Akhir</p>
                                        <p class="mt-1 text-sm font-bold text-white" x-text="totalScore().toFixed(2)"></p>
                                    </div>
                                    <button type="submit" class="primary-button justify-center px-5 py-3">
                                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                        Simpan Penilaian MFQ
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                                <div>
                                    <p class="section-kicker">Riwayat Nilai</p>
                                    <p class="mt-1 text-sm text-slate-300">Entri terbaru untuk peserta yang dipilih.</p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                <?php if ($recentScores->isEmpty()): ?>
                                    <div class="data-card text-sm text-slate-300">Belum ada riwayat penilaian untuk peserta ini.</div>
                                <?php endif; ?>

                                <?php foreach ($recentScores as $score): ?>
                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/45 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-white"><?= e($score->judge_name) ?></p>
                                                <p class="mt-1 text-xs text-slate-400"><?= e(optional($score->submitted_at)->format('d M Y H:i')) ?></p>
                                            </div>
                                            <div class="rounded-full border border-cyan-400/18 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100">
                                                <?= e(number_format((float) $score->score, 2)) ?>
                                            </div>
                                        </div>

                                        <?php if (! empty($score->score_breakdown['summary'] ?? null)): ?>
                                            <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                                                <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-slate-300">Soal: <?= e((string) ($score->score_breakdown['summary']['total_questions'] ?? 0)) ?></span>
                                                <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-slate-300">Mode Regu: <?= e((string) ($score->score_breakdown['summary']['mode_counts']['paket_regu'] ?? 0)) ?></span>
                                                <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-slate-300">Mode Rebutan: <?= e((string) ($score->score_breakdown['summary']['mode_counts']['rebutan'] ?? 0)) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="glass-card rounded-[2rem] p-6">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Panduan Teknis MFQ</p>
                            <div class="mt-5 space-y-4">
                                <?php foreach ([
                                    ['title' => 'Paket Soal Regu', 'body' => $mfqModeSummary['paket_regu'] ?? 'Setiap soal regu dapat bernilai 100 untuk sempurna, 50 jika dilempar dan dijawab benar, -25 jika dilempar dan dijawab salah, atau nilai proporsional jika jawaban masih kurang sempurna.'],
                                    ['title' => 'Paket Soal Rebutan', 'body' => $mfqModeSummary['rebutan'] ?? 'Jawaban benar bernilai 100 dan jawaban salah dikurangi 100. Yang dihitung adalah jawaban pertama setelah bel.' ],
                                    ['title' => 'Isi final, bukan proses', 'body' => 'Di form ini, panitia mencatat nilai akhir per soal sesuai keputusan hakim. Itu membuat rekap lebih cepat dan lebih mudah diaudit.'],
                                ] as $step): ?>
                                    <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                        <p class="font-semibold text-white"><?= e($step['title']) ?></p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($step['body']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Ringkasan Sesi</p>
                            <div class="mt-5 grid gap-3">
                                <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Peserta Dipilih</p>
                                    <p class="mt-1 text-sm font-semibold text-white"><?= e($selectedParticipant?->name ?? 'Belum dipilih') ?></p>
                                    <p class="mt-1 text-xs text-slate-400"><?= e($selectedParticipantCategory) ?></p>
                                </div>
                                <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Babak Aktif</p>
                                    <p class="mt-1 text-sm font-semibold text-white"><?= e($selectedJudgingRound) ?></p>
                                </div>
                                <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Skor Rata-rata Riwayat</p>
                                    <p class="mt-1 text-sm font-semibold text-white"><?= e($summaryStats['selected_average']) ?></p>
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
        function mfqScoringForm(initialState) {
            return {
                questions: Array.isArray(initialState.questions) && initialState.questions.length > 0
                    ? initialState.questions.map((question, index) => ({
                        id: question.id || `q-${index + 1}-${Date.now()}`,
                        label: question.label || `Soal ${index + 1}`,
                        mode: question.mode || 'paket_regu',
                        status: question.status || 'blank',
                        partial_score: question.partial_score || '',
                        notes: question.notes || '',
                    }))
                    : [{
                        id: `q-1-${Date.now()}`,
                        label: 'Soal 1',
                        mode: 'paket_regu',
                        status: 'blank',
                        partial_score: '',
                        notes: '',
                    }],
                modeOptions: initialState.modeOptions ?? {},
                statusOptions: initialState.statusOptions ?? {},
                defaultStatusForMode(mode) {
                    const options = this.statusOptionsFor(mode);
                    const firstKey = Object.keys(options)[0] ?? 'blank';

                    return firstKey;
                },
                statusOptionsFor(mode) {
                    return this.statusOptions?.[mode] ?? {};
                },
                normalizeRow(question) {
                    const availableStatuses = Object.keys(this.statusOptionsFor(question.mode));
                    if (!availableStatuses.includes(question.status)) {
                        question.status = this.defaultStatusForMode(question.mode);
                    }
                    if (question.status !== 'partial') {
                        question.partial_score = '';
                    }
                },
                addQuestion() {
                    const index = this.questions.length + 1;
                    this.questions.push({
                        id: `q-${index}-${Date.now()}`,
                        label: `Soal ${index}`,
                        mode: 'paket_regu',
                        status: 'blank',
                        partial_score: '',
                        notes: '',
                    });
                },
                removeQuestion(index) {
                    if (this.questions.length <= 1) {
                        return;
                    }

                    this.questions.splice(index, 1);
                },
                questionScore(question) {
                    if (question.mode === 'paket_regu') {
                        switch (question.status) {
                            case 'perfect':
                                return 100;
                            case 'partial':
                                return Math.max(0, Math.min(79.99, Number(question.partial_score || 0)));
                            case 'tossed_correct':
                                return 50;
                            case 'tossed_wrong':
                                return -25;
                            default:
                                return 0;
                        }
                    }

                    switch (question.status) {
                        case 'rebut_correct':
                            return 100;
                        case 'rebut_wrong':
                            return -100;
                        default:
                            return 0;
                    }
                },
                totalScore() {
                    return this.questions.reduce((sum, question) => sum + this.questionScore(question), 0);
                },
                summaryLabel() {
                    return `${this.questions.length} soal, total ${this.totalScore().toFixed(2)}`;
                },
            };
        }
    </script>
</body>
</html>
