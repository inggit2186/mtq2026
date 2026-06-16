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
    <style>
        .glass-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.7) 100%);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.1);
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

        .form-input {
            transition: all 0.2s ease;
        }

        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
        }

        .question-card {
            transition: all 0.3s ease;
        }

        .question-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .metric-card {
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
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
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>

    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <!-- Sidebar -->
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block glass-card"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                        <h1 class="mt-1 text-lg font-bold text-white">Penilaian MFQ</h1>
                    </div>
                </div>

                <!-- User Info -->
                <div class="rounded-2xl border border-cyan-400/20 bg-gradient-to-br from-cyan-500/10 to-sky-500/10 p-4 mb-6">
                    <div class="flex items-center gap-2">
                        <?= mtq_icon('user', 'h-4 w-4 text-cyan-300') ?>
                        <p class="text-sm font-semibold text-white"><?= e($user?->name) ?></p>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                        <span class="text-xs text-slate-300">Siap Input</span>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid gap-3 mb-6">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 border border-slate-700/30">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-400/20">
                            <?= mtq_icon('layers', 'h-5 w-5 text-cyan-300') ?>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Golongan</p>
                            <p class="text-lg font-bold text-white"><?= e($summaryStats['category_total']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 border border-slate-700/30">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-400/20">
                            <?= mtq_icon('check-circle', 'h-5 w-5 text-emerald-300') ?>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Terverifikasi</p>
                            <p class="text-lg font-bold text-white"><?= e($summaryStats['verified_total']) ?></p>
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
                <header class="glass-card rounded-[2rem] p-6 glow-emerald">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                                <?= mtq_icon('menu', 'h-4 w-4') ?>
                            </button>
                            <div>
                                <div class="flex items-center gap-2">
                                    <?= mtq_icon('book-open', 'h-4 w-4 text-cyan-300') ?>
                                    <p class="section-kicker">Ruang Penilaian MFQ</p>
                                </div>
                                <h2 class="mt-2 text-3xl font-black tracking-tight">
                                    <span class="gradient-text">Fahmil Qur'an</span>
                                </h2>
                            </div>
                        </div>
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                            Mode MFQ Aktif
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
                                <p class="text-sm text-slate-400">Peserta MFQ</p>
                                <p class="mt-1 text-2xl font-extrabold text-white"><?= e($summaryStats['participant_total']) ?></p>
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
                                <p class="mt-1 text-2xl font-extrabold text-white"><?= e($summaryStats['verified_total']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="metric-card glass-card rounded-2xl p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-400/20">
                                <?= mtq_icon('book-open', 'h-6 w-6 text-violet-300') ?>
                            </div>
                            <div>
                                <p class="text-sm text-slate-400">Golongan</p>
                                <p class="mt-1 text-2xl font-extrabold text-white"><?= e($summaryStats['category_total']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="metric-card glass-card rounded-2xl p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-400/20">
                                <?= mtq_icon('spark', 'h-6 w-6 text-amber-300') ?>
                            </div>
                            <div>
                                <p class="text-sm text-slate-400">Skor Terakhir</p>
                                <p class="mt-1 text-2xl font-extrabold text-emerald-300"><?= e($summaryStats['selected_latest']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div class="space-y-6">
                        <!-- Scoring Form -->
                        <div class="glass-card rounded-[2rem] p-6 glow-cyan">
                            <form method="POST" action="<?= e(route('scoring.mfq.store')) ?>" class="space-y-5"
                                x-data="mfqScoringForm({
                                    questions: <?= e(json_encode($initialQuestions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                    modeOptions: <?= e(json_encode($modeOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                    statusOptions: <?= e(json_encode($statusOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                })">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                                <!-- Identity Fields -->
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Peserta / Regu</label>
                                        <select name="participant_id" class="form-input w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                                            <option value="">Pilih peserta</option>
                                            <?php foreach ($participants as $participant): ?>
                                                <option value="<?= e($participant->id) ?>" <?= (string) old('participant_id', $filters['participant_id'] ?? '') === (string) $participant->id ? 'selected' : '' ?>>
                                                    <?= e($participant->name.' - '.trim((string) ($participant->category?->branch ?? '-').' | '.(string) ($participant->category?->name ?? '-'))) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Hakim</label>
                                        <input name="judge_name" type="text" value="<?= e(old('judge_name', $judgeNameDefault)) ?>" class="form-input w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400" placeholder="Nama hakim">
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Babak</label>
                                        <select name="judging_round" class="form-input w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                                            <?php foreach (['Penyisihan', 'Final'] as $roundLabel): ?>
                                                <option value="<?= e($roundLabel) ?>" <?= (string) old('judging_round', $selectedJudgingRound) === $roundLabel ? 'selected' : '' ?>>
                                                    <?= e($roundLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan</label>
                                        <textarea name="remarks" rows="2" class="form-input w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400" placeholder="Opsional"><?= e(old('remarks')) ?></textarea>
                                    </div>
                                </div>

                                <!-- Questions -->
                                <div class="rounded-2xl border border-slate-700/50 bg-slate-900/50 p-5">
                                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                        <div class="flex items-center gap-2">
                                            <?= mtq_icon('layers', 'h-5 w-5 text-cyan-300') ?>
                                            <h3 class="font-bold text-white">Builder Soal</h3>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="button" class="secondary-button px-4 py-2 text-sm flex items-center gap-2" x-on:click="addQuestion()">
                                                <?= mtq_icon('plus', 'h-4 w-4') ?>
                                                Tambah
                                            </button>
                                            <div class="flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1">
                                                <span class="h-2 w-2 rounded-full bg-cyan-400 pulse-dot"></span>
                                                <span class="text-xs text-cyan-200" x-text="summaryLabel()"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <template x-for="(question, index) in questions" :key="question.id">
                                            <div class="question-card rounded-xl border border-slate-700/50 bg-slate-900/50 p-4">
                                                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                                                    <div class="flex items-center gap-2">
                                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-400/20 text-sm font-bold text-cyan-200" x-text="index + 1"></span>
                                                        <input :name="`questions[${index}][label]`" x-model="question.label" type="text" class="form-input w-full rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" placeholder="Judul soal">
                                                    </div>
                                                    <button type="button" class="secondary-button px-3 py-1.5 text-xs" x-on:click="removeQuestion(index)" :disabled="questions.length === 1" :class="questions.length === 1 ? 'opacity-50 cursor-not-allowed' : ''">
                                                        <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                    </button>
                                                </div>

                                                <div class="grid gap-4 lg:grid-cols-3">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold text-slate-300">Jenis</label>
                                                        <select :name="`questions[${index}][mode]`" x-model="question.mode" x-on:change="normalizeRow(question)" class="form-input w-full rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400">
                                                            <template x-for="(label, value) in modeOptions" :key="value">
                                                                <option :value="value" x-text="label"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold text-slate-300">Status</label>
                                                        <select :name="`questions[${index}][status]`" x-model="question.status" class="form-input w-full rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400">
                                                            <template x-for="(item, value) in statusOptionsFor(question.mode)" :key="value">
                                                                <option :value="value" x-text="item.label"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold text-slate-300">Skor</label>
                                                        <div class="flex h-[42px] items-center justify-center rounded-lg border border-slate-700 bg-slate-900/80 px-3 text-lg font-bold text-cyan-200" x-text="questionScore(question).toFixed(0)"></div>
                                                    </div>
                                                </div>

                                                <div class="mt-3 grid gap-3 lg:grid-cols-2" x-show="question.status === 'partial'" x-cloak>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold text-slate-300">Nilai Proporsional</label>
                                                        <input :name="`questions[${index}][partial_score]`" x-model="question.partial_score" type="number" min="0" max="79.99" step="0.01" class="form-input w-full rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" placeholder="0-79.99">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold text-slate-300">Catatan</label>
                                                        <textarea :name="`questions[${index}][notes]`" x-model="question.notes" rows="2" class="form-input w-full rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" placeholder="Opsional"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-700/50 bg-slate-900/50 px-5 py-4">
                                    <div class="text-sm text-slate-300">
                                        <p>Total dihitung otomatis</p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-right">
                                            <p class="text-xs text-slate-400">Total</p>
                                            <p class="text-2xl font-black text-cyan-200" x-text="totalScore().toFixed(0)"></p>
                                        </div>
                                        <button type="submit" class="primary-button px-6 py-3 flex items-center gap-2 shadow-lg shadow-cyan-400/20">
                                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Score History -->
                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <?= mtq_icon('clock', 'h-5 w-5 text-cyan-300') ?>
                                <h3 class="font-bold text-white">Riwayat Nilai</h3>
                            </div>

                            <?php if ($recentScores->isEmpty()): ?>
                                <div class="rounded-xl border border-slate-700/50 bg-slate-900/50 p-6 text-center text-slate-400">
                                    Belum ada riwayat penilaian
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($recentScores as $score): ?>
                                        <div class="rounded-xl border border-slate-700/50 bg-slate-900/50 p-4">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="font-semibold text-white"><?= e($score->judge_name) ?></p>
                                                    <p class="text-xs text-slate-400"><?= e(optional($score->submitted_at)->format('d M Y H:i')) ?></p>
                                                </div>
                                                <div class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 text-lg font-bold text-cyan-200">
                                                    <?= e(number_format((float) $score->score, 0)) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Guide -->
                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <?= mtq_icon('info', 'h-5 w-5 text-amber-300') ?>
                                <h3 class="font-bold text-white">Panduan MFQ</h3>
                            </div>
                            <div class="space-y-3">
                                <div class="rounded-xl border border-slate-700/50 bg-slate-900/50 p-4">
                                    <p class="font-semibold text-white">Paket Regu</p>
                                    <p class="mt-1 text-sm text-slate-400">100=Sempurna, 50=Lempar Benar, -25=Lempar Salah, proporsional=parsial</p>
                                </div>
                                <div class="rounded-xl border border-slate-700/50 bg-slate-900/50 p-4">
                                    <p class="font-semibold text-white">Rebutan</p>
                                    <p class="mt-1 text-sm text-slate-400">100=Benar, -100=Salah</p>
                                </div>
                            </div>
                        </div>

                        <!-- Session Summary -->
                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <?= mtq_icon('layers', 'h-5 w-5 text-cyan-300') ?>
                                <h3 class="font-bold text-white">Ringkasan</h3>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between rounded-xl border border-slate-700/50 bg-slate-900/50 px-4 py-3">
                                    <span class="text-sm text-slate-400">Peserta</span>
                                    <span class="font-semibold text-white"><?= e($selectedParticipant?->name ?? 'Belum') ?></span>
                                </div>
                                <div class="flex items-center justify-between rounded-xl border border-slate-700/50 bg-slate-900/50 px-4 py-3">
                                    <span class="text-sm text-slate-400">Babak</span>
                                    <span class="font-semibold text-white"><?= e($selectedJudgingRound) ?></span>
                                </div>
                                <div class="flex items-center justify-between rounded-xl border border-slate-700/50 bg-slate-900/50 px-4 py-3">
                                    <span class="text-sm text-slate-400">Rata-rata</span>
                                    <span class="font-semibold text-cyan-200"><?= e($summaryStats['selected_average']) ?></span>
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
