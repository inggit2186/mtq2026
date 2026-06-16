<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = $user ?? auth()->user();
$session = $session ?? null;
$districtCards = $districtCards ?? collect();
$judges = $judges ?? [];
$districts = $districts ?? collect();
$participants = $participants ?? collect();
$summaryStats = $summaryStats ?? ['total_districts' => 0, 'total_participants' => 0, 'total_score_entries' => 0];

$defaultQuestionCount = 12;
$districtCardsJson = json_encode($districtCards->toArray());
$judgesJson = json_encode($judges);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e(config('app.name', 'e-MTQ').' - Input Nilai MFQ'); ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?php echo e($href); ?>">
    <?php endforeach; ?>
    <style>
        .score-table-scroll::-webkit-scrollbar { height: 6px; }
        .score-table-scroll::-webkit-scrollbar-track { background: rgba(30, 41, 59, 0.5); border-radius: 3px; }
        .score-table-scroll::-webkit-scrollbar-thumb { background: rgba(71, 85, 105, 0.8); border-radius: 3px; }
        .rank-badge-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
        .rank-badge-2 { background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); }
        .rank-badge-3 { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }

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

        .district-card {
            background: linear-gradient(180deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .district-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3), 0 0 20px rgba(52, 211, 153, 0.1);
        }

        .score-input {
            transition: all 0.2s ease;
        }

        .score-input:focus {
            transform: scale(1.05);
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.3);
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.6) 0%, rgba(51, 65, 85, 0.4) 100%);
            border: 1px solid rgba(148, 163, 184, 0.08);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: rgba(52, 211, 153, 0.3);
            transform: scale(1.02);
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

        .gradient-text {
            background: linear-gradient(135deg, #22d3ee 0%, #34d399 50%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .input-glow-orange:focus { box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.3); }
        .input-glow-sky:focus { box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.3); }
        .input-glow-violet:focus { box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.3); }
        .input-glow-pink:focus { box-shadow: 0 0 0 3px rgba(244, 114, 182, 0.3); }
        .input-glow-rose:focus { box-shadow: 0 0 0 3px rgba(251, 113, 133, 0.3); }
        .input-glow-cyan:focus { box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.3); }
    </style>
</head>
<body class="grid-bg min-h-screen bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>

    <div class="mx-auto max-w-[1920px] px-4 py-6 sm:px-6 lg:px-8"
         x-data="mfqScoringSheet()" x-init="init()">

        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72 opacity-30 pointer-events-none fixed"></div>
        <div class="hero-orb hero-orb-emerald left-[-5rem] top-1/2 h-96 w-96 -translate-y-1/2 opacity-20 pointer-events-none fixed"></div>

        <!-- Header Section -->
        <header class="mb-8 rounded-3xl glass-card px-6 py-5 glow-cyan">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <a href="<?php echo e(route('scoring.mfq', ['session_id' => $session?->id])); ?>"
                       class="group flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-600 bg-slate-800/80 text-slate-400 transition-all duration-300 hover:border-cyan-400/50 hover:bg-cyan-400/10 hover:text-cyan-300 hover:scale-110">
                        <?php echo mtq_icon('arrow-left', 'h-5 w-5'); ?>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <?php echo mtq_icon('book-open', 'h-4 w-4 text-cyan-300'); ?>
                                <p class="section-kicker text-cyan-300"><?php echo e($session?->name ?? 'Sesi MFQ'); ?></p>
                            </div>
                            <span class="rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-200">
                                <?php echo e($session?->round ?? 'Penyisihan'); ?>
                            </span>
                        </div>
                        <h1 class="mt-1 text-2xl font-black tracking-tight">
                            <span class="gradient-text">Input Nilai MFQ</span>
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="stat-card rounded-2xl px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-400/20">
                                <?php echo mtq_icon('zap', 'h-5 w-5 text-emerald-300'); ?>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-400">Total Poin</p>
                                <p class="text-2xl font-black text-emerald-300" x-text="grandTotal().toLocaleString()">0</p>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="<?php echo e(route('scoring.mfq.session.complete', $session?->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="primary-button px-5 py-2.5 flex items-center gap-2" onclick="return confirm('Selesaikan sesi ini?')">
                            <?php echo mtq_icon('check-circle', 'h-4 w-4'); ?>
                            Selesaikan
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Toolbar Section -->
        <div class="mb-6 rounded-2xl glass-card px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <?php echo mtq_icon('users', 'h-5 w-5 text-cyan-300'); ?>
                        <span class="text-sm font-semibold text-slate-300">Hakim Bertugas:</span>
                    </div>
                    <template x-for="(judge, index) in judges" :key="index">
                        <button @click="activeJudge = judge"
                                class="group inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition-all duration-300"
                                :class="activeJudge === judge ? 'border-cyan-400/50 bg-gradient-to-r from-cyan-400/20 to-sky-400/20 text-cyan-200 shadow-lg shadow-cyan-400/10' : 'border-slate-600/50 bg-slate-800/50 text-slate-400 hover:border-slate-500 hover:bg-slate-700/50'">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-bold transition-all duration-300"
                                  :class="activeJudge === judge ? 'bg-gradient-to-br from-cyan-400 to-sky-400 text-slate-900' : 'bg-slate-700 group-hover:bg-slate-600'">
                                <span x-text="index + 1"></span>
                            </span>
                            <span x-text="judge"></span>
                            <span x-show="activeJudge === judge" class="ml-1">
                                <?php echo mtq_icon('check-circle', 'h-4 w-4 text-cyan-300'); ?>
                            </span>
                        </button>
                    </template>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="addQuestionAll()" class="secondary-button px-4 py-2.5 text-sm flex items-center gap-2">
                        <?php echo mtq_icon('layers', 'h-4 w-4'); ?>
                        Tambah Baris
                    </button>
                    <button @click="clearAllScores()" class="group secondary-button px-4 py-2.5 text-sm text-rose-300 hover:border-rose-400/40 flex items-center gap-2">
                        <?php echo mtq_icon('refresh-cw', 'h-4 w-4 group-hover:rotate-180 transition-transform duration-500'); ?>
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <template x-for="(district, dIndex) in districts" :key="district.district_id">
                <div class="district-card rounded-2xl border overflow-hidden"
                     :class="getDistrictTotal(district.district_id) > 0 ? 'border-emerald-400/30 glow-emerald' : 'border-slate-700/50'">

                    <!-- District Header -->
                    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-5 py-4 border-b border-slate-700/30">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <!-- Rank Badge -->
                                <div class="relative">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl font-black text-xl text-white rank-badge-1 shadow-lg shadow-amber-400/20"
                                         x-show="getRank(district.district_id) === 1" x-text="getRank(district.district_id)"></div>
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl font-black text-xl text-white rank-badge-2 shadow-lg shadow-slate-400/20"
                                         x-show="getRank(district.district_id) === 2" x-text="getRank(district.district_id)"></div>
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl font-black text-xl text-white rank-badge-3 shadow-lg shadow-orange-400/20"
                                         x-show="getRank(district.district_id) === 3" x-text="getRank(district.district_id)"></div>
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-700 font-black text-xl text-white shadow-lg"
                                         x-show="getRank(district.district_id) > 3" x-text="getRank(district.district_id)"></div>
                                    <div x-show="getRank(district.district_id) === 1" class="absolute -top-1 -right-1">
                                        <?php echo mtq_icon('trophy', 'h-4 w-4 text-amber-300'); ?>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <?php echo mtq_icon('building', 'h-3 w-3 text-cyan-300/70'); ?>
                                        <p class="text-[10px] uppercase tracking-widest text-cyan-300/70">Regu <span x-text="dIndex + 1"></span></p>
                                    </div>
                                    <p class="text-xl font-bold text-white" x-text="district.district_name"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center gap-1 text-xs text-slate-400">
                                    <?php echo mtq_icon('zap', 'h-3 w-3'); ?>
                                    <span>Total Poin</span>
                                </div>
                                <p class="text-3xl font-black text-emerald-300" x-text="getDistrictTotal(district.district_id).toLocaleString()">0</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between rounded-xl bg-slate-800/50 px-4 py-2 border border-slate-700/30">
                            <div class="flex items-center gap-2">
                                <?php echo mtq_icon('user', 'h-4 w-4 text-slate-400'); ?>
                                <p class="text-sm text-slate-300">
                                    <span x-text="district.representative ? district.representative.name : '-'"></span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg border border-slate-600 bg-slate-900 px-3 py-1.5">
                                <?php echo mtq_icon('hash', 'h-4 w-4 text-amber-300'); ?>
                                <p class="text-sm font-bold text-white" x-text="district.representative ? district.representative.lot_number : '-'">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="score-table-scroll overflow-x-auto bg-slate-900/50">
                        <table class="w-full border-collapse text-center text-sm">
                            <thead>
                                <tr class="bg-slate-800 text-xs">
                                    <th class="border border-slate-700/50 px-2 py-3 font-semibold text-yellow-200">
                                        <?php echo mtq_icon('hash', 'h-3 w-3 inline'); ?>
                                    </th>
                                    <th class="border border-slate-700/50 bg-gradient-to-b from-orange-500/25 to-orange-500/10 px-4 py-3 font-semibold text-orange-200">
                                        <?php echo mtq_icon('star', 'h-4 w-4 inline mr-1'); ?> Paket
                                    </th>
                                    <template x-for="(opponent, oIndex) in getOpponents(district.district_id)" :key="'th-' + opponent.district_id">
                                        <th class="border border-slate-700/50 px-3 py-3 font-semibold"
                                            :class="oIndex === 0 ? 'bg-gradient-to-b from-sky-500/25 to-sky-500/10 text-sky-200' : oIndex === 1 ? 'bg-gradient-to-b from-violet-500/25 to-violet-500/10 text-violet-200' : 'bg-gradient-to-b from-pink-500/25 to-pink-500/10 text-pink-200'">
                                            <div class="flex flex-col items-center gap-1">
                                                <span x-text="'Lontaran ' + (oIndex + 1)"></span>
                                                <span class="text-[10px] font-normal opacity-70" x-text="opponent.district_name"></span>
                                            </div>
                                        </th>
                                    </template>
                                    <th class="border border-slate-700/50 bg-gradient-to-b from-rose-500/25 to-rose-500/10 px-3 py-3 font-semibold text-rose-200">
                                        <?php echo mtq_icon('zap', 'h-4 w-4 inline mr-1'); ?> Rebut
                                    </th>
                                    <th class="border border-slate-700/50 bg-gradient-to-b from-cyan-500/25 to-cyan-500/10 px-3 py-3 font-semibold text-cyan-200">
                                        <?php echo mtq_icon('chart', 'h-4 w-4 inline mr-1'); ?> Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(question, qIndex) in getQuestionsByDistrict(district.district_id)" :key="question.id">
                                    <tr class="border-b border-slate-700/30 hover:bg-slate-800/30 transition-colors">
                                        <td class="px-2 py-2 font-bold text-yellow-200" x-text="qIndex + 1"></td>
                                        <td class="border-l border-slate-700/30 bg-gradient-to-b from-orange-500/15 to-orange-500/5 px-2 py-1">
                                            <input type="number" min="0" max="100" x-model.number="question.paket"
                                                   @input="updateQuestionTotal(question.id)"
                                                   class="score-input w-full rounded-xl bg-slate-800/90 py-3 text-center text-xl font-bold text-orange-200 outline-none input-glow-orange" placeholder="0">
                                        </td>
                                        <template x-for="(opponent, oIndex) in getOpponents(district.district_id)" :key="'td-' + question.id + '-' + opponent.district_id">
                                            <td class="border-l border-slate-700/30 px-1 py-1"
                                                :class="oIndex === 0 ? 'bg-gradient-to-b from-sky-500/15 to-sky-500/5' : oIndex === 1 ? 'bg-gradient-to-b from-violet-500/15 to-violet-500/5' : 'bg-gradient-to-b from-pink-500/15 to-pink-500/5'">
                                                <input type="number" min="0" max="100"
                                                       x-model.number="question['lontaran' + (oIndex + 1)]"
                                                       @input="updateQuestionTotal(question.id)"
                                                       class="score-input w-full rounded-xl bg-slate-800/90 py-3 text-center text-xl font-bold outline-none"
                                                       :class="oIndex === 0 ? 'text-sky-200 input-glow-sky' : oIndex === 1 ? 'text-violet-200 input-glow-violet' : 'text-pink-200 input-glow-pink'"
                                                       placeholder="0">
                                            </td>
                                        </template>
                                        <td class="border-l border-slate-700/30 bg-gradient-to-b from-rose-500/15 to-rose-500/5 px-2 py-1">
                                            <input type="number" min="-100" max="100" x-model.number="question.rebutan"
                                                   @input="updateQuestionTotal(question.id)"
                                                   class="score-input w-full rounded-xl bg-slate-800/90 py-3 text-center text-xl font-bold text-rose-200 outline-none input-glow-rose" placeholder="0">
                                        </td>
                                        <td class="border-l border-slate-700/30 bg-gradient-to-b from-cyan-500/15 to-cyan-500/5 px-2 py-2">
                                            <div class="flex items-center justify-center gap-1">
                                                <?php echo mtq_icon('zap', 'h-4 w-4 text-cyan-300/50'); ?>
                                                <span class="text-2xl font-black text-cyan-200" x-text="question.rowTotal"></span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-800 font-bold">
                                    <td colspan="2" class="border border-slate-700/50 px-3 py-4 text-left text-base text-slate-300">
                                        <div class="flex items-center gap-2">
                                            <?php echo mtq_icon('calculator', 'h-4 w-4'); ?>
                                            <span>JUMLAH</span>
                                        </div>
                                    </td>
                                    <template x-for="(opponent, oIndex) in getOpponents(district.district_id)" :key="'footer-' + opponent.district_id">
                                        <td class="border border-slate-700/50 px-2 py-4 text-xl"
                                            :class="oIndex === 0 ? 'bg-sky-500/10 text-sky-200' : oIndex === 1 ? 'bg-violet-500/10 text-violet-200' : 'bg-pink-500/10 text-pink-200'"
                                            x-text="getColumnTotal(district.district_id, 'lontaran' + (oIndex + 1))">0</td>
                                    </template>
                                    <td class="border border-slate-700/50 bg-rose-500/10 px-2 py-4 text-xl text-rose-200"
                                        x-text="getColumnTotal(district.district_id, 'rebutan')">0</td>
                                    <td class="border border-slate-700/50 bg-cyan-500/10 px-2 py-4 text-2xl text-cyan-200"
                                        x-text="getDistrictTotal(district.district_id).toLocaleString()">0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="border-t border-slate-700/30 bg-gradient-to-r from-slate-900/80 to-slate-800/50 px-4 py-3">
                        <button @click="addQuestion(district.district_id)"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-600/50 bg-slate-800/30 px-4 py-3 text-base font-semibold text-slate-400 transition-all duration-300 hover:border-cyan-400/40 hover:bg-cyan-400/5 hover:text-cyan-300 hover:scale-[1.02]">
                            <?php echo mtq_icon('plus', 'h-5 w-5'); ?>
                            Tambah Baris
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Summary Section -->
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <!-- Grand Total Card -->
            <div class="rounded-3xl glass-card p-8 glow-cyan relative overflow-hidden">
                <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-cyan-400/20 to-transparent rounded-full blur-3xl"></div>
                <div class="relative">
                    <div class="flex items-center gap-3 mb-2">
                        <?php echo mtq_icon('trophy', 'h-6 w-6 text-cyan-300'); ?>
                        <h3 class="text-xl font-bold text-white">Grand Total Semua Regu</h3>
                    </div>
                    <p class="text-sm text-slate-400 mb-6">Total poin dari semua kecamatan</p>
                    <div class="flex items-center justify-center py-4">
                        <div class="text-center relative">
                            <div class="float-animation">
                                <p class="text-8xl font-black bg-gradient-to-r from-cyan-300 via-emerald-300 to-cyan-300 bg-clip-text text-transparent" x-text="grandTotal().toLocaleString()">0</p>
                            </div>
                            <div class="flex items-center justify-center gap-2 mt-4">
                                <div class="h-1.5 w-24 rounded-full bg-gradient-to-r from-cyan-400 to-emerald-400"></div>
                                <p class="text-lg font-bold text-slate-300">POIN</p>
                                <div class="h-1.5 w-24 rounded-full bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rankings Card -->
            <div class="rounded-3xl glass-card p-6">
                <div class="flex items-center gap-3 mb-2">
                    <?php echo mtq_icon('chart', 'h-6 w-6 text-cyan-300'); ?>
                    <h3 class="text-xl font-bold text-white">Peringkat Akhir</h3>
                </div>
                <p class="text-sm text-slate-400 mb-4 flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                      <span class="pulse-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Diperbarui otomatis
                </p>
                <div class="mt-4 space-y-3">
                    <template x-for="(rank, index) in rankings()" :key="rank.districtId">
                        <div class="group relative flex items-center gap-4 rounded-2xl border px-5 py-4 transition-all duration-300"
                             :class="index === 0 ? 'border-amber-400/40 bg-gradient-to-r from-amber-500/15 via-amber-500/5 to-transparent shadow-lg shadow-amber-400/10' : 'border-slate-700/50 bg-slate-800/30 hover:border-slate-600 hover:bg-slate-800/50'">
                            <!-- Rank Number -->
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl font-black text-xl text-white shadow-lg transition-transform group-hover:scale-110"
                                 :class="index === 0 ? 'rank-badge-1 shadow-amber-400/30' : index === 1 ? 'rank-badge-2' : index === 2 ? 'rank-badge-3' : 'bg-slate-700'">
                                <span x-text="index + 1"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-lg font-bold text-white truncate" x-text="rank.districtName"></p>
                                    <span x-show="index === 0" class="shrink-0">
                                        <?php echo mtq_icon('trophy', 'h-4 w-4 text-amber-300'); ?>
                                    </span>
                                </div>
                                <p class="text-sm text-slate-400 flex items-center gap-1">
                                    <?php echo mtq_icon('user', 'h-3 w-3'); ?>
                                    <span x-text="rank.representativeName"></span>
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-3xl font-black" :class="index === 0 ? 'text-amber-300' : index === 1 ? 'text-slate-300' : 'text-white'" x-text="rank.total.toLocaleString()"></p>
                                <p class="text-[10px] uppercase tracking-wider text-slate-500">poin</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 flex flex-wrap items-center justify-end gap-4">
            <button @click="saveDraft()" class="group secondary-button px-6 py-3 flex items-center gap-2">
                <?php echo mtq_icon('save', 'h-4 w-4 text-slate-400 group-hover:text-slate-200'); ?>
                Simpan Draft
            </button>
            <button @click="submitScores()" class="primary-button px-6 py-3 flex items-center gap-2 shadow-lg shadow-emerald-400/20">
                <?php echo mtq_icon('send', 'h-4 w-4'); ?>
                Kirim Semua Nilai
            </button>
        </div>
    </div>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?php echo e($src); ?>"></script>
    <?php endforeach; ?>

    <script>
    function mfqScoringSheet() {
        return {
            sessionId: <?php echo e($session?->id ?? 'null'); ?>,
            activeJudge: '',
            questions: [],
            districts: <?php echo $districtCardsJson; ?>,

            init: function() {
                this.questions = [];
                this.generateInitialQuestions();
                var stored = this.getFromStorage();
                if (stored && stored.questions && stored.questions.length > 0) {
                    this.questions = stored.questions;
                }
                if (this.judges && this.judges.length > 0) {
                    this.activeJudge = this.judges[0];
                }
                this.recalculateAll();
            },

            judges: <?php echo $judgesJson; ?>,

            getOpponents: function(districtId) {
                return this.districts.filter(function(d) { return d.district_id !== districtId; });
            },

            getOpponentCount: function(districtId) {
                return this.getOpponents(districtId).length;
            },

            generateInitialQuestions: function() {
                var self = this;
                this.districts.forEach(function(district) {
                    var opponentCount = self.getOpponentCount(district.district_id);
                    for (var i = 0; i < 12; i++) {
                        var question = { id: 'q-' + district.district_id + '-' + Date.now() + '-' + i, districtId: district.district_id, paket: 0, rowTotal: 0 };
                        for (var j = 1; j <= opponentCount; j++) { question['lontaran' + j] = 0; }
                        question.rebutan = 0;
                        self.questions.push(question);
                    }
                });
            },

            getQuestionsByDistrict: function(districtId) {
                return this.questions.filter(function(q) { return q.districtId === districtId; });
            },

            addQuestion: function(districtId) {
                var opponentCount = this.getOpponentCount(districtId);
                var question = { id: 'q-' + districtId + '-' + Date.now(), districtId: districtId, paket: 0, rowTotal: 0 };
                for (var j = 1; j <= opponentCount; j++) { question['lontaran' + j] = 0; }
                question.rebutan = 0;
                this.questions.push(question);
                this.saveToStorage();
            },

            addQuestionAll: function() {
                var self = this;
                this.districts.forEach(function(d) { self.addQuestion(d.district_id); });
            },

            updateQuestionTotal: function(questionId) { this.recalculateAll(true); },

            recalculateAll: function(skipSave) {
                skipSave = skipSave || false;
                var self = this;
                this.questions.forEach(function(q) {
                    var total = parseFloat(q.paket) || 0;
                    total += parseFloat(q.rebutan) || 0;
                    Object.keys(q).forEach(function(k) { if (k.indexOf('lontaran') === 0) total += parseFloat(q[k]) || 0; });
                    q.rowTotal = total;
                });
                if (!skipSave) this.saveToStorage();
            },

            getDistrictTotal: function(districtId) {
                var qs = this.getQuestionsByDistrict(districtId), total = 0;
                for (var i = 0; i < qs.length; i++) total += qs[i].rowTotal || 0;
                return total;
            },

            getColumnTotal: function(districtId, column) {
                var qs = this.getQuestionsByDistrict(districtId), total = 0;
                for (var i = 0; i < qs.length; i++) total += parseFloat(qs[i][column]) || 0;
                return total;
            },

            grandTotal: function() {
                var total = 0;
                for (var i = 0; i < this.questions.length; i++) total += this.questions[i].rowTotal || 0;
                return total;
            },

            getRank: function(districtId) {
                var self = this;
                var totals = this.districts.map(function(d) { return { districtId: d.district_id, total: self.getDistrictTotal(d.district_id) }; });
                totals.sort(function(a, b) { return b.total - a.total; });
                for (var i = 0; i < totals.length; i++) if (totals[i].districtId === districtId) return i + 1;
                return this.districts.length;
            },

            rankings: function() {
                var self = this;
                return this.districts.map(function(d) {
                    return { districtId: d.district_id, districtName: d.district_name, representativeName: d.representative ? d.representative.name : '-', total: self.getDistrictTotal(d.district_id) };
                }).sort(function(a, b) { return b.total - a.total; });
            },

            clearAllScores: function() {
                if (!confirm('Hapus semua nilai?')) return;
                this.questions.forEach(function(q) {
                    q.paket = 0; q.rebutan = 0; q.rowTotal = 0;
                    Object.keys(q).forEach(function(k) { if (k.indexOf('lontaran') === 0) q[k] = 0; });
                });
                this.recalculateAll();
            },

            saveDraft: function() { this.saveToStorage(); alert('Draft tersimpan!'); },

            getStorageKey: function() { return 'mfq_score_v5_' + this.sessionId; },

            getFromStorage: function() {
                if (!this.sessionId) return null;
                try { var raw = localStorage.getItem(this.getStorageKey()); if (raw) return JSON.parse(raw); } catch (e) {}
                return null;
            },

            saveToStorage: function() {
                if (!this.sessionId) return;
                localStorage.setItem(this.getStorageKey(), JSON.stringify({ questions: this.questions, activeJudge: this.activeJudge, savedAt: Date.now() }));
            },

            submitScores: function() {
                var self = this;
                var filled = this.questions.filter(function(q) {
                    if (!q.districtId || !q.paket && !q.rebutan) return false;
                    return true;
                });
                if (filled.length === 0) { alert('Belum ada nilai yang diisi!'); return; }

                var byDistrict = {};
                filled.forEach(function(q) { if (!byDistrict[q.districtId]) byDistrict[q.districtId] = []; byDistrict[q.districtId].push(q); });
                var ids = Object.keys(byDistrict), submitted = 0;

                ids.forEach(function(districtId) {
                    var qs = byDistrict[districtId], district = null;
                    for (var i = 0; i < self.districts.length; i++) { if (self.districts[i].district_id == districtId) { district = self.districts[i]; break; } }
                    if (!district || !district.representative || !district.representative.id) return;

                    var oppCount = self.getOpponentCount(districtId);
                    var fd = new FormData();
                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    fd.append('participant_id', district.representative.id);
                    fd.append('judge_name', self.activeJudge);
                    fd.append('questions', JSON.stringify(qs.map(function(q, idx) {
                        var throws = [];
                        for (var i = 1; i <= oppCount; i++) throws.push(q['lontaran' + i] || 0);
                        return { label: 'Soal ' + (idx + 1), package_score: q.paket || 0, throw_scores: throws, rebuttal_score: q.rebutan || 0 };
                    })));

                    fetch('/penilaian/mfq/sesi/' + self.sessionId + '/nilai', { method: 'POST', body: fd }).then(function() {
                        submitted++;
                        if (submitted === ids.length) { localStorage.removeItem(self.getStorageKey()); alert('Semua nilai berhasil disimpan!'); window.location.reload(); }
                    });
                });
            }
        };
    }
    </script>
</body>
</html>
