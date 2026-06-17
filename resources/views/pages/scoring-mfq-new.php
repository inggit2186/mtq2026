<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = $user ?? auth()->user();
$mfqCategories = $mfqCategories ?? collect();
$selectedCategory = $selectedCategory ?? null;
$sessions = $sessions ?? collect();
$activeSession = $activeSession ?? null;
$currentStep = $currentStep ?? 1;
$participantsByDistrict = $participantsByDistrict ?? collect();
$districts = $districts ?? collect();
$summaryStats = $summaryStats ?? ['participant_total' => 0, 'category_total' => 0, 'session_active' => 0];
$availableJudges = $availableJudges ?? [];
$categoryJudgeIds = $categoryJudgeIds ?? [];
$completedSessions = $completedSessions ?? collect();
$rankingsData = $rankingsData ?? collect();
$displayedLotNumbers = $displayedLotNumbers ?? [];

// Default judges passed from controller (pre-populated with official judges from category)
$defaultJudges = $defaultJudges ?? [$user?->name ?? ''];

// Get selected category from URL or session
$selectedCategoryId = request()->query('competition_category_id', $activeSession?->competition_category_id);
$selectedDistrictIds = $activeSession?->district_ids ?? [];
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
            transform: translateX(4px);
        }

        .form-input {
            transition: all 0.2s ease;
        }

        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
        }

        .district-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .district-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .pulse-dot {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .active-session-glow {
            box-shadow: 0 0 30px rgba(34, 211, 238, 0.2), 0 0 60px rgba(34, 211, 238, 0.1);
        }
    </style>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>

    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8"
          x-data="mfqScoringApp()">

        <!-- Background Orbs -->
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
            <!-- Sidebar -->
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[320px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                        <h1 class="mt-1 text-lg font-bold text-white">Penilaian MFQ</h1>
                    </div>
                </div>

                <!-- Active Session Card -->
                <?php if ($activeSession): ?>
                <div class="mt-6 rounded-[1.5rem] border border-cyan-400/30 bg-gradient-to-br from-cyan-900/40 via-sky-900/30 to-blue-900/20 p-4 active-session-glow">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-3 w-3 animate-pulse rounded-full bg-emerald-400"></span>
                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-300">Sesi Aktif</p>
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-white"><?= e($activeSession->name) ?></h3>
                    <p class="mt-1 text-sm text-slate-300"><?= e($activeSession->category?->branch ?? '-') ?> - <?= e($activeSession->category?->name ?? '-') ?></p>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-200">
                            <?= mtq_icon('spark', 'h-3 w-3 inline mr-1') ?>
                            <?= e($activeSession->round) ?>
                        </span>
                        <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-300">
                            <?= mtq_icon('building', 'h-3 w-3 inline mr-1') ?>
                            <?= count($activeSession->district_ids ?? []) ?> Kecamatan
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Step Indicator -->
                <div class="mt-6 space-y-3">
                    <div class="step-card flex items-center gap-3 rounded-2xl border px-4 py-3 transition-all duration-300"
                         :class="currentStep >= 1 ? 'border-cyan-400/40 bg-cyan-400/10' : 'border-slate-800 bg-slate-900/50'">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl font-bold transition-all duration-300"
                             :class="currentStep >= 1 ? 'bg-gradient-to-br from-cyan-400 to-sky-400 text-slate-900 shadow-lg shadow-cyan-400/30' : 'bg-slate-700 text-slate-400'">
                            <span x-show="currentStep < 1">1</span>
                            <span x-show="currentStep >= 1"><?= mtq_icon('check-circle', 'h-5 w-5') ?></span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Buat Sesi</p>
                            <p class="text-xs text-slate-400">Nama sesi & Hakim</p>
                        </div>
                    </div>
                    <div class="step-card flex items-center gap-3 rounded-2xl border px-4 py-3 transition-all duration-300"
                         :class="currentStep >= 2 ? 'border-cyan-400/40 bg-cyan-400/10' : 'border-slate-800 bg-slate-900/50'">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl font-bold transition-all duration-300"
                             :class="currentStep >= 2 ? 'bg-gradient-to-br from-cyan-400 to-sky-400 text-slate-900 shadow-lg shadow-cyan-400/30' : 'bg-slate-700 text-slate-400'">
                            <span x-show="currentStep < 2">2</span>
                            <span x-show="currentStep >= 2"><?= mtq_icon('check-circle', 'h-5 w-5') ?></span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Pilih Kecamatan</p>
                            <p class="text-xs text-slate-400">2-5 Kecamatan</p>
                        </div>
                    </div>
                    <div class="step-card flex items-center gap-3 rounded-2xl border px-4 py-3 transition-all duration-300"
                         :class="currentStep >= 3 ? 'border-cyan-400/40 bg-cyan-400/10' : 'border-slate-800 bg-slate-900/50'">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl font-bold transition-all duration-300"
                             :class="currentStep >= 3 ? 'bg-gradient-to-br from-cyan-400 to-sky-400 text-slate-900 shadow-lg shadow-cyan-400/30' : 'bg-slate-700 text-slate-400'">
                            <span x-show="currentStep < 3">3</span>
                            <span x-show="currentStep >= 3"><?= mtq_icon('check-circle', 'h-5 w-5') ?></span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Input Nilai</p>
                            <p class="text-xs text-slate-400">Format Excel</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <!-- Quick Stats -->
                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('zap', 'h-4 w-4 text-cyan-300') ?>
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Sesi Aktif</p>
                        </div>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['session_active']) ?></p>
                    </div>
                    <div class="data-card">
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('layers', 'h-4 w-4 text-emerald-300') ?>
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Golongan MFQ</p>
                        </div>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['category_total']) ?></p>
                    </div>
                    <a href="<?= e(route('dashboard')) ?>" class="secondary-button w-full flex items-center justify-center gap-2">
                        <?= mtq_icon('home', 'h-4 w-4') ?>
                        Kembali ke Dashboard
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="min-w-0 space-y-6">

                <!-- Header -->
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('book-open', 'h-4 w-4 text-cyan-300') ?>
                            <p class="section-kicker">Ruang Penilaian MFQ</p>
                        </div>
                        <h2 class="mt-2 text-3xl font-black tracking-tight">
                            <?php if ($activeSession): ?>
                                <span class="gradient-text"><?= e($activeSession->name) ?></span>
                            <?php else: ?>
                                <span class="gradient-text">Sistem Penilaian MFQ Baru</span>
                            <?php endif; ?>
                        </h2>
                        <p class="mt-2 text-sm text-slate-300">
                            <?php if ($activeSession): ?>
                                Sesi <?= mtq_icon('spark', 'h-3 w-3 inline text-amber-300') ?> <?= e($activeSession->round) ?> - <?= e($activeSession->category?->branch ?? '-') ?> <?= e($activeSession->category?->name ?? '-') ?>
                            <?php else: ?>
                                Buat sesi baru dengan nama dan pilih hakim, kemudian pilih kecamatan yang akan bertanding.
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <?php if ($activeSession): ?>
                            <form method="POST" action="<?= e(route('scoring.mfq.session.destroy', $activeSession->id)) ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="secondary-button rounded-xl px-4 py-2.5 text-rose-300 hover:border-rose-400/40 hover:bg-rose-400/10 flex items-center gap-2"
                                        onclick="return confirm('Hapus sesi ini?')">
                                    <?= mtq_icon('trash', 'h-4 w-4') ?>
                                    Hapus Sesi
                                </button>
                            </form>
                        <?php endif; ?>
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full <?= $activeSession ? 'bg-emerald-300 animate-pulse' : 'bg-slate-500' ?>"></span>
                            <?= $activeSession ? 'Sesi Aktif' : 'Belum Ada Sesi' ?>
                        </div>
                    </div>
                </header>

                <!-- Alerts -->
                <?php if (session('status')): ?>
                    <div class="glass-card rounded-[1.5rem] border border-emerald-400/20 bg-emerald-400/10 px-5 py-4 text-sm text-emerald-100">
                        <?= e(session('status')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session('error')): ?>
                    <div class="glass-card rounded-[1.5rem] border border-rose-400/20 bg-rose-400/10 px-5 py-4 text-sm text-rose-100">
                        <?= e(session('error')) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errors->any()): ?>
                    <div class="glass-card rounded-[1.5rem] border border-rose-400/20 bg-rose-400/10 px-5 py-4 text-sm text-rose-100">
                        <p class="font-semibold">Periksa kembali data yang diisi:</p>
                        <ul class="mt-2 list-inside list-disc">
                            <?php foreach ($errors->all() as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Active Sessions List -->
                <?php if ($sessions->isNotEmpty() && !$activeSession): ?>
                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <?= mtq_icon('layers', 'h-5 w-5 text-cyan-300') ?>
                        <h3 class="text-xl font-bold text-white">Sesi MFQ Aktif</h3>
                    </div>
                    <p class="text-sm text-slate-400 mb-5">Pilih sesi yang ada atau buat sesi baru.</p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($sessions as $session): ?>
                        <a href="<?= e(route('scoring.mfq', ['session_id' => $session->id, 'competition_category_id' => $selectedCategoryId])) ?>"
                           class="group block rounded-2xl border border-slate-700 bg-slate-900/50 p-5 transition-all duration-300 hover:border-cyan-400/40 hover:bg-slate-900/80 hover:shadow-lg hover:shadow-cyan-400/10 hover:-translate-y-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <?= mtq_icon('file-text', 'h-4 w-4 text-cyan-300/70') ?>
                                        <p class="font-bold text-white group-hover:text-cyan-200"><?= e($session->name) ?></p>
                                    </div>
                                    <p class="text-sm text-slate-400"><?= e($session->category?->branch ?? '-') ?> <?= e($session->category?->name ?? '-') ?></p>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-200">
                                            <?= mtq_icon('spark', 'h-3 w-3 inline mr-1') ?>
                                            <?= e($session->round) ?>
                                        </span>
                                        <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1 text-xs text-slate-300">
                                            <?= mtq_icon('building', 'h-3 w-3 inline mr-1') ?>
                                            <?= count($session->district_ids ?? []) ?> Kec.
                                        </span>
                                        <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1 text-xs text-slate-300">
                                            <?= mtq_icon('users', 'h-3 w-3 inline mr-1') ?>
                                            <?= count($session->judges ?? []) ?> Hakim
                                        </span>
                                    </div>
                                </div>
                                <span class="inline-flex h-3 w-3 flex-shrink-0 rounded-full bg-emerald-400 animate-pulse"></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Step 1: Create Session -->
                <?php if (!$activeSession): ?>
                <section class="glass-card rounded-[2rem] p-6 glow-cyan" x-show="showStep >= 1">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl border border-cyan-400/30 bg-cyan-400/10">
                            <?= mtq_icon('plus', 'h-7 w-7 text-cyan-300') ?>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white">Buat Sesi Baru</h3>
                            <p class="mt-2 text-sm text-slate-400">Isi nama sesi, pilih golongan, dan tambahkan nama hakim (bisa lebih dari 1).</p>
                        </div>
                    </div>

                    <form method="POST" action="<?= e(route('scoring.mfq.session.store')) ?>" class="mt-6 space-y-5">
                        <?= csrf_field() ?>

                        <!-- Session Name -->
                        <div class="rounded-2xl border border-slate-700/50 bg-gradient-to-br from-slate-900/80 to-slate-800/40 p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <?= mtq_icon('file-text', 'h-5 w-5 text-cyan-300') ?>
                                <label class="block">
                                    <span class="text-sm font-semibold text-slate-200">Nama Sesi <span class="text-rose-400">*</span></span>
                                </label>
                            </div>
                            <input type="text" name="name" required
                                   placeholder="Contoh: Sesi Penyisihan MFQ Putra 1"
                                   class="form-input mt-1 w-full rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-3.5 text-white placeholder-slate-500 outline-none transition focus:border-cyan-400">
                        </div>

                        <!-- Category and Round -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-700/50 bg-gradient-to-br from-slate-900/80 to-slate-800/40 p-5">
                                <div class="flex items-center gap-2 mb-3">
                                    <?= mtq_icon('layers', 'h-5 w-5 text-emerald-300') ?>
                                    <label class="block">
                                        <span class="text-sm font-semibold text-slate-200">Golongan MFQ <span class="text-rose-400">*</span></span>
                                    </label>
                                </div>
                                <select name="competition_category_id" required
                                        x-model="selectedCategory"
                                        class="form-input mt-1 w-full rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-3.5 text-white outline-none transition focus:border-cyan-400">
                                    <option value="">Pilih Golongan</option>
                                    <?php foreach ($mfqCategories as $category): ?>
                                        <option value="<?= e($category->id) ?>" <?= $selectedCategoryId == $category->id ? 'selected' : '' ?>>
                                            <?= e($category->branch.' - '.$category->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="rounded-2xl border border-slate-700/50 bg-gradient-to-br from-slate-900/80 to-slate-800/40 p-5">
                                <div class="flex items-center gap-2 mb-3">
                                    <?= mtq_icon('spark', 'h-5 w-5 text-amber-300') ?>
                                    <label class="block">
                                        <span class="text-sm font-semibold text-slate-200">Babak <span class="text-rose-400">*</span></span>
                                    </label>
                                </div>
                                <select name="round" required
                                        class="form-input mt-1 w-full rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-3.5 text-white outline-none transition focus:border-cyan-400">
                                    <option value="Penyisihan">Penyisihan</option>
                                    <option value="Final">Final</option>
                                </select>
                            </div>
                        </div>

                        <!-- Judges -->
                        <div class="rounded-2xl border border-slate-700/50 bg-gradient-to-br from-slate-900/80 to-slate-800/40 p-5"
                             x-data="mfqJudgeSetup({
                                 judges: <?= e(json_encode($defaultJudges, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                 availableJudges: <?= e(json_encode($availableJudges, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                                 categoryJudgeIds: <?= e(json_encode($categoryJudgeIds, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
                             })">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <?= mtq_icon('users', 'h-5 w-5 text-violet-300') ?>
                                    <span class="text-sm font-semibold text-slate-200">Nama Hakim <span class="text-rose-400">*</span></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-3 py-1 text-xs font-semibold text-slate-200">
                                        <span x-text="judges.length"></span> hakim
                                    </span>
                                    <button type="button" class="secondary-button rounded-xl px-3 py-2 text-xs" x-on:click="judgeSearchQuery = ''; judgeModalOpen = true">
                                        <?= mtq_icon('plus', 'h-4 w-4') ?>
                                        Hakim
                                    </button>
                                </div>
                            </div>

                            <!-- Hidden inputs for form submission -->
                            <template x-for="(judgeName, index) in judges" :key="'input-' + index">
                                <input type="hidden" :name="`judges[${index}]`" :value="judgeName">
                            </template>

                            <!-- Judges List Display -->
                            <div class="space-y-3">
                                <template x-for="(judgeName, index) in judges" :key="'judge-' + index">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-cyan-400/30 bg-cyan-400/10 text-sm font-bold text-cyan-200"
                                              x-text="index + 1"></div>
                                        <div class="flex-1 rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-2.5 text-white">
                                            <span class="font-semibold" x-text="judgeName"></span>
                                        </div>
                                        <button type="button"
                                                @click="removeJudge(index)"
                                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-slate-600 bg-slate-800/80 text-slate-400 transition hover:border-rose-400/40 hover:bg-rose-400/10 hover:text-rose-300"
                                                :class="judges.length <= 1 ? 'opacity-30 cursor-not-allowed' : ''"
                                                :disabled="judges.length <= 1">
                                            <?= mtq_icon('trash', 'h-4 w-4') ?>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <p x-show="judgesError" class="mt-3 text-xs text-rose-200">Minimal harus ada 1 hakim dengan nama.</p>

                            <!-- Modal Tambah Hakim -->
                            <div x-show="judgeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm px-4 py-6"
                                x-on:keydown.escape.window="judgeModalOpen = false">
                                <div class="absolute inset-0" x-on:click="judgeModalOpen = false"></div>
                                <div class="relative z-10 w-full max-w-lg rounded-2xl border border-cyan-400/20 bg-slate-900 shadow-xl max-h-[85vh] flex flex-col">
                                    <div class="flex items-center justify-between border-b border-slate-700 px-6 py-4 shrink-0">
                                        <div>
                                            <h3 class="text-lg font-bold text-white">Tambah Hakim</h3>
                                            <p class="mt-1 text-xs text-slate-400" x-text="`${availableJudges.filter(j => !judges.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length} hakim tersedia`"></p>
                                        </div>
                                        <button type="button" class="secondary-button rounded-xl px-3 py-2" x-on:click="judgeModalOpen = false">
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
                                                class="w-full rounded-xl border border-slate-700 bg-slate-950/80 pl-10 pr-4 py-2.5 text-sm text-slate-100 outline-none focus:border-cyan-400/50 focus:ring-1 focus:ring-cyan-400/20">
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
                                                <template x-for="judge in availableJudges.filter(j => categoryJudgeIds.includes(j.id) && !judges.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase())))" :key="'sk-' + judge.id">
                                                    <button type="button"
                                                        class="flex items-center gap-3 rounded-xl border border-cyan-400/20 bg-cyan-400/5 p-3 text-left transition hover:border-cyan-400/40 hover:bg-cyan-400/10 hover:scale-[1.01]"
                                                        x-on:click="addJudge(judge.nama)">
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
                                                <p x-show="availableJudges.filter(j => categoryJudgeIds.includes(j.id) && !judges.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0" class="py-4 text-center text-xs text-slate-500">
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
                                                <template x-for="judge in availableJudges.filter(j => !categoryJudgeIds.includes(j.id) && !judges.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase())))" :key="'other-' + judge.id">
                                                    <button type="button"
                                                        class="flex items-center gap-3 rounded-xl border border-slate-700/60 bg-slate-800/30 p-3 text-left transition hover:border-amber-400/30 hover:bg-amber-400/5 hover:scale-[1.01]"
                                                        x-on:click="addJudge(judge.nama)">
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
                                                <p x-show="availableJudges.filter(j => !categoryJudgeIds.includes(j.id) && !judges.includes(j.nama) && (!judgeSearchQuery || j.nama.toLowerCase().includes(judgeSearchQuery.toLowerCase()))).length === 0" class="py-4 text-center text-xs text-slate-600">
                                                    <span x-text="judgeSearchQuery ? 'Tidak ada hasil pencarian' : 'Tidak ada hakim lain'"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end gap-3 border-t border-slate-700 px-6 py-4 shrink-0">
                                        <button type="button" class="secondary-button" x-on:click="judgeModalOpen = false">
                                            Tutup
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="rounded-2xl border border-slate-700/50 bg-gradient-to-br from-slate-900/80 to-slate-800/40 p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <?= mtq_icon('file-text', 'h-5 w-5 text-slate-400') ?>
                                <label class="block">
                                    <span class="text-sm font-semibold text-slate-200">Catatan (Opsional)</span>
                                </label>
                            </div>
                            <textarea name="remarks" rows="2"
                                      placeholder="Catatan tambahan untuk sesi ini..."
                                      class="form-input mt-1 w-full rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-3 text-white placeholder-slate-500 outline-none transition focus:border-cyan-400"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="submit" class="primary-button px-6 py-3 flex items-center gap-2 shadow-lg shadow-cyan-400/20">
                                <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                Buat Sesi & Lanjut
                            </button>
                        </div>
                    </form>
                </section>
                <?php endif; ?>

                <!-- Completed Sessions & Rankings -->
                <?php if (!$activeSession && $selectedCategory): ?>
                <section class="glass-card rounded-[2rem] p-6 mt-6" x-show="showStep >= 1">
                    <div class="flex items-start gap-4 mb-6">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl border border-amber-400/30 bg-amber-400/10">
                            <?= mtq_icon('trophy', 'h-7 w-7 text-amber-300') ?>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white">Hasil & Ranking</h3>
                            <p class="mt-2 text-sm text-slate-400">Rekap hasil sesi MFQ dan ranking berdasarkan poin serta total nilai.</p>
                        </div>
                    </div>

                    <?php if ($completedSessions->isEmpty()): ?>
                    <div class="rounded-2xl border border-slate-700/50 bg-slate-800/30 p-8 text-center">
                        <?= mtq_icon('inbox', 'h-12 w-12 text-slate-600 mx-auto mb-3') ?>
                        <p class="text-slate-500">Belum ada sesi yang diselesaikan.</p>
                    </div>
                    <?php else: ?>

                    <!-- Completed Sessions List -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-slate-300 mb-3 flex items-center gap-2">
                            <?= mtq_icon('check-circle', 'h-4 w-4 text-emerald-400') ?>
                            Sesi yang Sudah Selesai
                        </h4>
                        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                            <?php foreach ($completedSessions as $session): ?>
                            <div class="rounded-xl border border-slate-700/50 bg-slate-800/30 p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-white"><?= e($session->name) ?></p>
                                        <p class="text-xs text-slate-400 mt-1">
                                            <?= e($session->round) ?> |
                                            <?= e($session->created_at->format('d M Y, H:i')) ?>
                                        </p>
                                    </div>
                                    <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-2 py-0.5 text-xs font-semibold text-emerald-300">
                                        <?= e($session->district_ids ? count($session->district_ids) : 0) ?> Kec.
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Rankings Table -->
                    <?php if ($rankingsData->isNotEmpty()): ?>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-300 mb-3 flex items-center gap-2">
                            <?= mtq_icon('hash', 'h-4 w-4 text-amber-400') ?>
                            Ranking per Kecamatan
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-slate-700">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Nomor Lot</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Kecamatan</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-400">Poin Ranking</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Total Nilai</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/50">
                                    <?php foreach ($rankingsData as $index => $rank): ?>
                                    <tr class="hover:bg-slate-800/50 transition-colors <?= $index < 3 ? 'bg-amber-500/5' : '' ?>">
                                        <td class="px-4 py-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg font-bold <?= $index === 0 ? 'bg-amber-500 text-white' : ($index === 1 ? 'bg-slate-400 text-slate-900' : ($index === 2 ? 'bg-orange-500 text-white' : 'bg-slate-700 text-slate-300')) ?>">
                                            <?= $index + 1 ?>
                                        </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                <?php foreach ($rank['lot_numbers'] as $lot): ?>
                                                <span class="inline-flex rounded-full border border-amber-400/30 bg-amber-400/10 px-2 py-0.5 text-xs font-bold text-amber-300">
                                                    <?= e($lot) ?>
                                                </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm font-medium text-white"><?= e($rank['district_name']) ?></span>
                                            <span class="text-xs text-slate-500 ml-2">(<?= $rank['participant_count'] ?> peserta)</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-xl font-black text-amber-300"><?= $rank['total_points'] ?></span>
                                            <div class="flex justify-center gap-1 mt-1">
                                                <?php foreach ($rank['session_points'] as $points): ?>
                                                <span class="inline-flex rounded-full bg-slate-700/50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-400">
                                                    <?= $points ?> pts
                                                </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-xl font-black text-emerald-400"><?= number_format($rank['total_score'], 0) ?></span>
                                            <div class="flex justify-end gap-1 mt-1">
                                                <?php foreach ($rank['session_scores'] as $score): ?>
                                                <span class="inline-flex rounded-full bg-slate-700/50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-400">
                                                    <?= number_format($score, 0) ?>
                                                </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <!-- Step 2: Select Districts -->
                <?php if ($activeSession && empty($activeSession->district_ids) && $currentStep >= 2): ?>
                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl border border-emerald-400/30 bg-emerald-400/10">
                            <?= mtq_icon('map-pin', 'h-7 w-7 text-emerald-300') ?>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white">Pilih Kecamatan</h3>
                            <p class="mt-2 text-sm text-slate-400">Pilih 2 sampai 5 kecamatan yang akan bertanding dalam sesi ini.</p>
                        </div>
                    </div>

                    <?php if (!$selectedCategory): ?>
                    <div class="mt-6 rounded-2xl border border-amber-400/20 bg-gradient-to-r from-amber-500/15 to-orange-500/10 p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <?= mtq_icon('bell', 'h-5 w-5 text-amber-300') ?>
                            <span class="text-sm font-semibold text-amber-100">
                                Pilih golongan MFQ terlebih dahulu untuk melihat daftar kecamatan.
                            </span>
                        </div>
                        <form method="GET" action="<?= e(route('scoring.mfq', ['session_id' => $activeSession->id, 'competition_category_id' => $selectedCategoryId])) ?>" class="mt-3">
                            <div class="flex flex-wrap items-end gap-3">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="mb-2 block text-xs font-semibold text-slate-300">Golongan MFQ</label>
                                    <select name="competition_category_id" class="form-input w-full rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-white">
                                        <option value="">Pilih Golongan</option>
                                        <?php foreach ($mfqCategories as $category): ?>
                                            <option value="<?= e($category->id) ?>">
                                                <?= e($category->branch.' - '.$category->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="primary-button rounded-xl px-4 py-2.5 flex items-center gap-2">
                                    <?= mtq_icon('eye', 'h-4 w-4') ?>
                                    Lihat Kecamatan
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <form method="POST" action="<?= e(route('scoring.mfq.districts.store', $activeSession->id)) ?>" class="mt-6">
                        <?= csrf_field() ?>

                        <!-- Category Info -->
                        <div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-400/20 bg-gradient-to-r from-emerald-500/15 to-teal-500/10 px-4 py-3">
                            <?= mtq_icon('check-circle', 'h-5 w-5 text-emerald-300') ?>
                            <span class="text-sm font-semibold text-emerald-100">
                                <?= e($selectedCategory->branch.' - '.$selectedCategory->name) ?>
                            </span>
                        </div>

                        <!-- Districts Selection -->
                        <div class="space-y-4" x-data="{ selected: [] }">
                            <div class="flex items-center gap-3">
                                <?= mtq_icon('layers', 'h-5 w-5 text-cyan-300') ?>
                                <p class="text-sm font-semibold text-slate-300">
                                    Kecamatan Terpilih: <span class="text-cyan-300 font-bold" x-text="selected.length">0</span> / 2-5
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <?php foreach ($participantsByDistrict as $districtId => $participants): ?>
                                    <?php
                                    $district = $districts->get($districtId);
                                    $districtName = $district?->name ?? 'Tanpa Kecamatan';
                                    $participantCount = $participants->count();
                                    // Find participant with lot_number first, fallback to first participant
                                    $representative = $participants->firstWhere('lot_number', '!=', null)
                                        ?? $participants->firstWhere('lot_number', '!=', '')
                                        ?? $participants->first();
                                    $lotNumber = $representative?->lot_number ?? '-';
                                    ?>
                                    <label class="group block cursor-pointer">
                                        <input type="checkbox"
                                               name="district_ids[]"
                                               value="<?= e($districtId) ?>"
                                               x-model="selected"
                                               class="peer sr-only">
                                        <div class="district-card rounded-2xl border border-slate-700 bg-gradient-to-br from-slate-900/80 to-slate-800/40 p-5 transition-all duration-300 hover:border-slate-600 peer-checked:border-cyan-400 peer-checked:bg-gradient-to-br peer-checked:from-cyan-500/15 peer-checked:to-emerald-500/10 peer-checked:shadow-lg peer-checked:shadow-cyan-400/20">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <?= mtq_icon('hash', 'h-4 w-4 text-amber-400') ?>
                                                        <p class="font-bold text-amber-300 text-lg" x-text="'<?= e($lotNumber) ?>'"><?= e($lotNumber) ?></p>
                                                        <?php if (in_array($lotNumber, $displayedLotNumbers)): ?>
                                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-2 py-0.5 text-xs font-semibold text-emerald-300" title="Sudah tampil di sesi sebelumnya">
                                                            <?= mtq_icon('check-circle', 'h-3 w-3') ?>
                                                            Sudah Tampil
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex items-center gap-3 text-sm text-slate-400">
                                                        <span class="flex items-center gap-1">
                                                            <?= mtq_icon('building', 'h-3 w-3') ?>
                                                            <?= e($districtName) ?>
                                                        </span>
                                                        <span class="flex items-center gap-1">
                                                            <?= mtq_icon('users', 'h-3 w-3') ?>
                                                            <?= e($participantCount) ?> peserta
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl border-2 border-slate-600 bg-slate-800 transition-all peer-checked:border-cyan-400 peer-checked:bg-gradient-to-br peer-checked:from-cyan-400 peer-checked:to-sky-400">
                                                    <?= mtq_icon('check', 'h-4 w-4 text-slate-900 opacity-0 peer-checked:opacity-100') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($participantsByDistrict->isEmpty()): ?>
                                <div class="rounded-2xl border border-slate-700 bg-gradient-to-br from-slate-900/80 to-slate-800/40 p-8 text-center">
                                    <div class="flex items-center justify-center mb-3">
                                        <?= mtq_icon('file-text', 'h-10 w-10 text-slate-500') ?>
                                    </div>
                                    <p class="text-slate-400">Belum ada kecamatan dengan peserta terverifikasi untuk golongan ini.</p>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between pt-4 border-t border-slate-700/30">
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <?= mtq_icon('info', 'h-4 w-4') ?>
                                    <span>Minimal pilih 2 kecamatan, maksimal 5 kecamatan.</span>
                                </div>
                                <button type="submit"
                                        class="primary-button px-6 py-3 flex items-center gap-2 shadow-lg shadow-cyan-400/20"
                                        :disabled="selected.length < 2 || selected.length > 5"
                                        :class="(selected.length < 2 || selected.length > 5) ? 'opacity-50 cursor-not-allowed' : ''">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    Lanjut ke Input Nilai
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <!-- Step 3: Scoring -->
                <?php if ($activeSession && !empty($activeSession->district_ids) && $currentStep >= 3): ?>
                <section class="glass-card rounded-[2rem] p-6 glow-emerald">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl border border-emerald-400/30 bg-emerald-400/10">
                                <?= mtq_icon('pencil', 'h-7 w-7 text-emerald-300') ?>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Input Nilai</h3>
                                <p class="mt-2 text-sm text-slate-400">
                                    Sesi: <strong class="text-cyan-200"><?= e($activeSession->name) ?></strong> |
                                    <?= mtq_icon('spark', 'h-3 w-3 inline text-amber-300') ?>
                                    <?= e($activeSession->round) ?> |
                                    <?= mtq_icon('building', 'h-3 w-3 inline text-slate-400') ?>
                                    <?= e(count($activeSession->district_ids)) ?> Kecamatan
                                </p>
                            </div>
                        </div>
                        <a href="<?= e(route('scoring.mfq.scoring', $activeSession->id)) ?>"
                           class="primary-button px-5 py-3 flex items-center gap-2 shadow-lg shadow-emerald-400/20">
                            <?= mtq_icon('external-link', 'h-4 w-4') ?>
                            Buka Halaman Scoring
                        </a>
                    </div>

                    <!-- Judges Info -->
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-400">
                            <?= mtq_icon('users', 'h-4 w-4 text-violet-300') ?>
                            <span>Hakim:</span>
                        </div>
                        <?php foreach ($activeSession->judges ?? [] as $index => $judge): ?>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-1.5 text-sm font-semibold text-cyan-200">
                                <?= mtq_icon('user', 'h-3.5 w-3.5') ?>
                                <?= e($judge) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <!-- Quick Score Summary -->
                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <?php
                        $districtIds = $activeSession->district_ids ?? [];
                        $districts = \App\Models\District::whereIn('id', $districtIds)->get()->keyBy('id');

                        // Build district cards data for display (similar to scoring page)
                        $districtCardsData = collect($districtIds)->map(function ($districtId) use ($districts, $activeSession) {
                            $district = $districts->get($districtId);
                            $districtParticipants = \App\Models\Participant::with('district')
                                ->where('competition_category_id', $activeSession->competition_category_id)
                                ->where('district_id', $districtId)
                                ->where('verification_status', 'verified')
                                ->get();

                            if ($districtParticipants->isEmpty()) {
                                return null;
                            }

                            // Find participant with lot_number first, fallback to first participant
                            $representative = $districtParticipants->firstWhere('lot_number', '!=', null)
                                ?? $districtParticipants->firstWhere('lot_number', '!=', '')
                                ?? $districtParticipants->first();
                            $scores = \App\Models\ScoreEntry::where('participant_id', $representative->id)->get();

                            return [
                                'district_id' => $districtId,
                                'district_name' => $district?->name ?? '-',
                                'participant_count' => $districtParticipants->count(),
                                'lot_number' => $representative->lot_number,
                                'representative_name' => $representative->name,
                                'total_score' => $scores->sum('score'),
                            ];
                        })->filter()->values();

                        foreach ($districtCardsData as $index => $card):
                        ?>
                        <div class="district-card rounded-2xl border border-slate-700/50 bg-gradient-to-br from-slate-900/80 to-slate-800/40 p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <?= mtq_icon('hash', 'h-3 w-3 text-amber-400') ?>
                                        <p class="text-[10px] uppercase tracking-wider text-amber-300">Lot</p>
                                    </div>
                                    <p class="mt-1 text-3xl font-black text-amber-300"><?= e($card['lot_number'] ?? '-') ?></p>
                                </div>
                                <span class="flex h-12 w-12 items-center justify-center rounded-xl border-2 border-slate-600 bg-slate-800 text-lg font-bold text-slate-300">
                                    <?= e($index + 1) ?>
                                </span>
                            </div>
                            <div class="mt-4 flex items-end justify-between">
                                <div class="flex items-center gap-2">
                                    <?= mtq_icon('user', 'h-4 w-4 text-slate-400') ?>
                                    <div>
                                        <p class="text-sm text-white"><?= e($card['representative_name'] ?? '-') ?></p>
                                        <p class="text-xs text-slate-500"><?= e($card['participant_count'] ?? 0) ?> peserta</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center gap-1 text-xs text-slate-500">
                                        <?= mtq_icon('zap', 'h-3 w-3') ?>
                                        <span>Total Nilai</span>
                                    </div>
                                    <p class="text-3xl font-black text-emerald-300"><?= number_format($card['total_score'] ?? 0, 0) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>

    <script>
        function mfqScoringApp() {
            return {
                currentStep: <?= e($currentStep) ?>,
                selectedCategory: '<?= e($selectedCategoryId ?? '') ?>',
                showStep: 1,

                init() {
                    // Sync current step with PHP
                    this.currentStep = <?= e($currentStep) ?>;
                }
            }
        }

        function mfqJudgeSetup(initialState) {
            return {
                judges: Array.isArray(initialState.judges) && initialState.judges.length > 0
                    ? initialState.judges.filter(j => j.trim() !== '')
                    : [initialState.judges?.[0] || ''],
                availableJudges: initialState.availableJudges ?? [],
                categoryJudgeIds: initialState.categoryJudgeIds ?? [],
                judgeModalOpen: false,
                judgeSearchQuery: '',
                judgesError: false,

                init() {
                    // Ensure at least one empty judge entry
                    if (this.judges.length === 0) {
                        this.judges = [''];
                    }
                },

                addJudge(name) {
                    if (name && !this.judges.includes(name)) {
                        this.judges.push(name);
                    }
                    // Close modal if no more available judges
                    this.$nextTick(() => {
                        const available = this.availableJudges.filter(j =>
                            !this.judges.includes(j.nama) &&
                            (!this.judgeSearchQuery || j.nama.toLowerCase().includes(this.judgeSearchQuery.toLowerCase()))
                        );
                        if (available.length === 0) {
                            this.judgeModalOpen = false;
                        }
                    });
                },

                removeJudge(index) {
                    if (this.judges.length > 1) {
                        this.judges.splice(index, 1);
                    }
                },

                validateJudges() {
                    const filledJudges = this.judges.filter(j => j.trim() !== '');
                    this.judgesError = filledJudges.length < 1;
                    return !this.judgesError;
                }
            };
        }
    </script>
</body>
</html>
