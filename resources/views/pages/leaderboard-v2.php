<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$categoryGroups = $categoryGroups ?? collect();
$branchGroups = $branchGroups ?? collect();
$mfqBranch = $mfqBranch ?? null;
$mfqRankings = $mfqRankings ?? [];
$juaraUmumData = $juaraUmumData ?? [];
$selectedCategoryId = $selectedCategoryId ?? 0;
$selectedCategoryData = $selectedCategoryData ?? null;
$selectedBranch = $selectedBranch ?? null;
$showJuaraUmum = $showJuaraUmum ?? false;
$leaderboardStats = $leaderboardStats ?? ['branches' => 0, 'verified_participants' => 0, 'categories' => 0, 'score_entries' => 0];

// Prepare branches array with MFQ
$allBranches = $branchGroups->values()->all();
if ($mfqBranch) {
    $allBranches[] = $mfqBranch;
}
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'leaderboard');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Leaderboard MTQ') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
    <style>
        .leaderboard-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .leaderboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .podium-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .podium-card:hover {
            transform: scale(1.05);
        }
        .rank-badge {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .rank-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
        .rank-2 { background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); }
        .rank-3 { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
        .crown-icon {
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .gender-tab {
            transition: all 0.3s ease;
        }
        .gender-tab.active {
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.2) 0%, rgba(6, 182, 212, 0.2) 100%);
            border-color: rgba(34, 211, 238, 0.5);
        }
        .round-pill {
            transition: all 0.2s ease;
        }
        .round-pill.active {
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.3) 0%, rgba(6, 182, 212, 0.3) 100%);
            border-color: rgba(34, 211, 238, 0.6);
            color: #22d3ee;
        }
        .glow-gold { box-shadow: 0 0 30px rgba(251, 191, 36, 0.4); }
        .glow-silver { box-shadow: 0 0 30px rgba(148, 163, 184, 0.4); }
        .glow-bronze { box-shadow: 0 0 30px rgba(217, 119, 6, 0.4); }
    </style>
</head>
<body class="grid-bg scroll-smooth min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main
        class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8"
        x-data="<?= htmlspecialchars(json_encode([
            'mobileNavOpen' => false,
            'branches' => $allBranches,
            'mfqRankings' => $mfqRankings,
            'juaraUmumData' => $juaraUmumData,
            'activeBranch' => $selectedBranch ?? '',
            'activeCategoryId' => (int) $selectedCategoryId,
            'activeGender' => 'all',
            'activeRound' => 'Semua',
            'activeRankingMode' => 'score',
            'showJuaraUmum' => $showJuaraUmum,
            'categoryGroups' => $categoryGroups->values()->all(),
            'scrollToSection' => function($sectionId) { return ['sectionId' => $sectionId]; },
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
        x-init="
            scrollToSection = (sectionId) => {
                $nextTick(() => {
                    const target = document.getElementById(sectionId);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            };
            pickBranch = (branchName) => {
                activeBranch = branchName;
                const branch = branches.find((item) => item.branch === branchName);
                if (branch && branch.categories && branch.categories.length) {
                    activeCategoryId = branch.categories[0].category_id;
                    scrollToSection('golongan-section');
                }
            };
            selectCategory = (categoryId) => {
                activeCategoryId = categoryId;
                const category = categoryGroups.find((item) => item.category_id === categoryId);
                if (category) {
                    activeBranch = category.branch;
                }
                scrollToSection('ranking-section');
            };
            getFilteredLeaders = () => {
                const category = categoryGroups.find((item) => item.category_id === activeCategoryId);
                if (!category || !category.rounds) return [];
                const roundData = category.rounds[activeRound];
                if (!roundData || !roundData.leaders) return [];
                let leaders = roundData.leaders;
                if (activeGender !== 'all') {
                    leaders = leaders.filter((leader) => {
                        const gender = leader.gender || (leader.name ? 'male' : 'female');
                        return (activeGender === 'putra' && gender === 'male') || (activeGender === 'putri' && gender === 'female');
                    });
                }
                return leaders;
            };
            getCategoryName = () => {
                const category = categoryGroups.find((item) => item.category_id === activeCategoryId);
                return category ? category.category_name : '-';
            };
            getBranchName = () => {
                const category = categoryGroups.find((item) => item.category_id === activeCategoryId);
                return category ? category.branch : '-';
            };
            getCurrentMfqCategory = () => {
                const mfqBranch = branches.find((b) => b.is_mfq);
                if (!mfqBranch) return null;
                const cat = mfqBranch.categories?.find((c) => c.category_id === activeCategoryId);
                if (!cat) return null;
                return { ...cat, rankings: mfqRankings[cat.category_id] || { rounds: {} } };
            };
            isMfqBranch = () => {
                const branch = branches.find((b) => b.branch === activeBranch);
                return branch?.is_mfq === true;
            };
            getMfqFilteredLeaders = () => {
                if (!isMfqBranch()) return [];
                const catData = getCurrentMfqCategory();
                if (!catData || !catData.rankings?.rounds) return [];
                const roundData = catData.rankings.rounds[activeRound];
                if (!roundData) return [];
                const leaders = activeRankingMode === 'points' ? (roundData.by_rank || []) : (roundData.by_score || []);
                return leaders.slice(0, 20);
            };
            toggleJuaraUmum = () => {
                showJuaraUmum = !showJuaraUmum;
                if (showJuaraUmum) {
                    $nextTick(() => {
                        const target = document.getElementById('juara-umum-section');
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                }
            };
        "
    >
        <!-- Background Orbs -->
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)]">
            <!-- Sidebar -->
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[300px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('crown') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Leaderboard</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Papan Peringkat</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($leaderboardStats['verified_participants']) ?> Peserta</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Ranking babak Penyisihan dan Final dengan pemisahan Putra dan Putri.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300 animate-pulse"></span>
                        Live Update
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('layers', 'h-4 w-4 text-amber-300') ?>
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Cabang</p>
                        </div>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['branches']) ?></p>
                    </div>
                    <div class="data-card">
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('users', 'h-4 w-4 text-emerald-300') ?>
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Golongan</p>
                        </div>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['categories']) ?></p>
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

            <!-- Main Content -->
            <div class="min-w-0 space-y-6">
                <!-- Header -->
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <div class="flex items-center gap-2">
                                <?= mtq_icon('crown', 'h-5 w-5 text-amber-300') ?>
                                <p class="section-kicker">Peringkat MTQ</p>
                            </div>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Leaderboard Kompetisi</h2>
                            <p class="mt-2 text-sm text-slate-300">Pilih cabang, golongan, dan babak untuk melihat ranking.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            @click="toggleJuaraUmum()"
                            class="rounded-xl border px-4 py-2 font-semibold transition-all"
                            :class="showJuaraUmum ? 'border-amber-400/50 bg-amber-400/10 text-amber-300 shadow-lg shadow-amber-400/20' : 'border-slate-700 bg-slate-800 text-slate-300 hover:border-amber-400/30 hover:text-amber-300'"
                        >
                            <?= mtq_icon('crown', 'h-4 w-4 inline mr-1') ?>
                            Juara Umum
                        </button>
                        <a href="<?= e(route('dashboard')) ?>" class="secondary-button">
                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                            Dashboard
                        </a>
                    </div>
                </header>

                <!-- Stats Overview -->
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('layers') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Cabang Aktif</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['branches']) ?></p>
                    </div>
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('users') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Peserta</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['verified_participants']) ?></p>
                    </div>
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('trophy') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Golongan</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['categories']) ?></p>
                    </div>
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('chart-bar') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Entri Nilai</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['score_entries']) ?></p>
                    </div>
                </section>

                <!-- Branch Selection -->
                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <?= mtq_icon('target', 'h-5 w-5 text-cyan-300') ?>
                        <h3 class="text-xl font-bold text-white">Pilih Cabang</h3>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <?php foreach ($branchGroups as $branchGroup): ?>
                            <button
                                type="button"
                                @click="pickBranch(<?= e(json_encode($branchGroup['branch'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>)"
                                class="leaderboard-card rounded-2xl border p-4 text-left transition-all duration-300 hover:-translate-y-1"
                                :class="activeBranch === <?= e(json_encode($branchGroup['branch'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?> ? 'border-cyan-400/50 bg-cyan-400/10 shadow-lg shadow-cyan-400/20' : 'border-slate-700 bg-slate-900/50 hover:border-slate-600'"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="rounded-full border border-amber-400/30 bg-amber-400/10 px-2 py-0.5 text-xs font-semibold text-amber-300">
                                        <?= e($branchGroup['category_total']) ?> Golongan
                                    </span>
                                </div>
                                <p class="text-lg font-bold text-white"><?= e($branchGroup['branch']) ?></p>
                                <div class="mt-2 flex items-center gap-2 text-xs text-slate-400">
                                    <span><?= e($branchGroup['participant_total']) ?> Peserta</span>
                                    <span class="text-slate-600">|</span>
                                    <span><?= e($branchGroup['score_entries']) ?> Entri</span>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Category Selection -->
                <section id="golongan-section" class="glass-card rounded-[2rem] p-6 scroll-mt-6">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <?= mtq_icon('award', 'h-5 w-5 text-amber-300') ?>
                            <h3 class="text-xl font-bold text-white">Pilih Golongan</h3>
                        </div>
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                            <span x-text="activeBranch"></span>
                        </div>
                    </div>

                    <template x-for="branch in branches" :key="branch.branch">
                        <div x-show="activeBranch === branch.branch" class="space-y-4">
                            <template x-for="category in branch.categories" :key="category.category_id">
                                <button
                                    type="button"
                                    @click="selectCategory(category.category_id)"
                                    class="leaderboard-card w-full rounded-2xl border p-4 text-left transition-all duration-300"
                                    :class="activeCategoryId === category.category_id ? 'border-amber-400/50 bg-amber-400/10 shadow-lg shadow-amber-400/20' : 'border-slate-700 bg-slate-900/50 hover:border-slate-600'"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-white" x-text="category.category_name"></p>
                                            <p class="mt-1 text-xs text-slate-400">
                                                <span x-text="category.participant_total"></span> Peserta |
                                                <span x-text="category.score_entries"></span> Entri
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <div class="flex items-center gap-1">
                                                <span class="rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2 py-0.5 text-xs font-semibold text-cyan-300">
                                                    <span x-text="category.putra_leaders ? category.putra_leaders.length : 0"></span> Putra
                                                </span>
                                                <span class="rounded-full border border-pink-400/30 bg-pink-400/10 px-2 py-0.5 text-xs font-semibold text-pink-300">
                                                    <span x-text="category.putri_leaders ? category.putri_leaders.length : 0"></span> Putri
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </template>
                </section>

                <!-- Ranking Section -->
                <?php if ($selectedCategoryData): ?>
                <section id="ranking-section" class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-6 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)] scroll-mt-6">
                    <!-- Header -->
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <?= mtq_icon('trophy', 'h-5 w-5 text-amber-300') ?>
                                <p class="section-kicker">Ranking</p>
                            </div>
                            <h2 class="mt-2 text-2xl font-bold text-white" x-text="getCategoryName()"></h2>
                            <p class="mt-1 text-sm text-slate-300" x-text="getBranchName()"></p>
                        </div>
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <span x-text="activeCategoryId"></span> Peserta
                        </div>
                    </div>

                    <!-- Gender Tabs -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <button
                            @click="activeGender = 'all'"
                            class="gender-tab rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold transition-all"
                            :class="activeGender === 'all' ? 'active text-white border-cyan-400/50 bg-cyan-400/10' : 'text-slate-400 hover:text-white'"
                        >
                            <?= mtq_icon('users', 'h-4 w-4 inline mr-1') ?>
                            Semua
                        </button>
                        <button
                            @click="activeGender = 'putra'"
                            class="gender-tab rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold transition-all"
                            :class="activeGender === 'putra' ? 'active text-white border-cyan-400/50 bg-cyan-400/10' : 'text-slate-400 hover:text-white'"
                        >
                            <?= mtq_icon('gender-male', 'h-4 w-4 inline mr-1') ?>
                            Putra
                        </button>
                        <button
                            @click="activeGender = 'putri'"
                            class="gender-tab rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold transition-all"
                            :class="activeGender === 'putri' ? 'active text-white border-cyan-400/50 bg-cyan-400/10' : 'text-slate-400 hover:text-white'"
                        >
                            <?= mtq_icon('gender-female', 'h-4 w-4 inline mr-1') ?>
                            Putri
                        </button>
                    </div>

                    <!-- Round Pills -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <?php foreach (['Semua', 'Penyisihan', 'Final'] as $round): ?>
                            <button
                                @click="activeRound = '<?= $round ?>'"
                                class="round-pill rounded-full border border-slate-700 px-4 py-2 text-sm font-semibold transition-all"
                                :class="activeRound === '<?= $round ?>' ? 'active border-cyan-400/50 text-cyan-300' : 'text-slate-400 hover:text-white'"
                            >
                                <?= e($round) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Podium (Top 3) -->
                    <div class="mb-8" x-show="getFilteredLeaders().length > 0">
                        <div class="flex items-end justify-center gap-4">
                            <!-- 2nd Place -->
                            <div class="podium-card text-center w-36" x-show="getFilteredLeaders()[1]">
                                <div class="relative mx-auto mb-2">
                                    <div class="h-24 w-24 mx-auto rounded-full border-4 border-slate-400 bg-gradient-to-br from-slate-600 to-slate-700 flex items-center justify-center overflow-hidden">
                                        <span class="text-3xl">🥈</span>
                                    </div>
                                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-slate-400 to-slate-500 px-3 py-1 text-sm font-bold text-white shadow-lg">2</span>
                                </div>
                                <p class="font-bold text-white text-sm truncate" x-text="getFilteredLeaders()[1]?.name || '-'"></p>
                                <p class="text-xs text-slate-400 truncate" x-text="getFilteredLeaders()[1]?.district || '-'"></p>
                                <p class="mt-2 text-xl font-black text-slate-300" x-text="getFilteredLeaders()[1]?.average_score || '0.00'"></p>
                            </div>

                            <!-- 1st Place -->
                            <div class="podium-card text-center w-44 glow-gold" x-show="getFilteredLeaders()[0]">
                                <div class="crown-icon absolute -translate-x-1/2 left-1/2 -mt-6">
                                    <?= mtq_icon('crown', 'h-10 w-10 text-amber-400') ?>
                                </div>
                                <div class="relative mx-auto mt-6 mb-2">
                                    <div class="h-28 w-28 mx-auto rounded-full border-4 border-amber-400 bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center overflow-hidden shadow-lg shadow-amber-400/30">
                                        <span class="text-4xl">🥇</span>
                                    </div>
                                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-amber-400 to-amber-500 px-3 py-1 text-sm font-bold text-white shadow-lg">1</span>
                                </div>
                                <p class="font-bold text-white text-base truncate" x-text="getFilteredLeaders()[0]?.name || '-'"></p>
                                <p class="text-xs text-slate-400 truncate" x-text="getFilteredLeaders()[0]?.district || '-'"></p>
                                <p class="mt-2 text-2xl font-black text-amber-300" x-text="getFilteredLeaders()[0]?.average_score || '0.00'"></p>
                            </div>

                            <!-- 3rd Place -->
                            <div class="podium-card text-center w-36" x-show="getFilteredLeaders()[2]">
                                <div class="relative mx-auto mb-2">
                                    <div class="h-24 w-24 mx-auto rounded-full border-4 border-orange-600 bg-gradient-to-br from-orange-600 to-orange-700 flex items-center justify-center overflow-hidden">
                                        <span class="text-3xl">🥉</span>
                                    </div>
                                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 px-3 py-1 text-sm font-bold text-white shadow-lg">3</span>
                                </div>
                                <p class="font-bold text-white text-sm truncate" x-text="getFilteredLeaders()[2]?.name || '-'"></p>
                                <p class="text-xs text-slate-400 truncate" x-text="getFilteredLeaders()[2]?.district || '-'"></p>
                                <p class="mt-2 text-xl font-black text-orange-400" x-text="getFilteredLeaders()[2]?.average_score || '0.00'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Ranking Table -->
                    <div class="overflow-hidden rounded-2xl border border-slate-700">
                        <table class="w-full">
                            <thead class="bg-slate-800/80">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Peserta</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Kecamatan</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-400">Babak</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Skor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50 bg-slate-900/50">
                                <template x-for="(leader, index) in getFilteredLeaders()" :key="'rank-' + index">
                                    <tr class="hover:bg-slate-800/50 transition-colors" :class="index < 3 ? 'bg-amber-500/5' : ''">
                                        <td class="px-4 py-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg font-bold"
                                                :class="index === 0 ? 'bg-amber-500 text-white' : (index === 1 ? 'bg-slate-400 text-slate-900' : (index === 2 ? 'bg-orange-500 text-white' : 'bg-slate-700 text-slate-300'))">
                                                <span x-text="index + 1"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-white" x-text="leader.name"></p>
                                            <p class="text-xs text-slate-500" x-text="leader.institution || '-'"></p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <?= mtq_icon('building', 'h-3 w-3 text-slate-500') ?>
                                                <span class="text-sm text-slate-300" x-text="leader.district || '-'"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                                :class="leader.current_round === 'Final' ? 'bg-amber-400/20 text-amber-300 border border-amber-400/30' : 'bg-slate-700/50 text-slate-400 border border-slate-600/30'">
                                                <span x-text="leader.current_round || '-'"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <p class="text-lg font-bold text-cyan-200" x-text="leader.average_score || '0.00'"></p>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="getFilteredLeaders().length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                        <?= mtq_icon('inbox', 'h-10 w-10 mx-auto mb-2 text-slate-600') ?>
                                        <p>Belum ada data ranking untuk golongan ini.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php else: ?>
                <section class="glass-card rounded-[2rem] p-8 text-center">
                    <?= mtq_icon('trophy', 'h-16 w-16 mx-auto mb-4 text-slate-600') ?>
                    <p class="text-lg text-slate-400">Pilih golongan terlebih dahulu untuk melihat ranking.</p>
                </section>
                <?php endif; ?>

                <!-- MFQ Ranking Section -->
                <section x-show="isMfqBranch()" class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-6 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)]">
                    <!-- Header -->
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <?= mtq_icon('trophy', 'h-5 w-5 text-amber-300') ?>
                                <p class="section-kicker">Fahmil Qur'an</p>
                            </div>
                            <h2 class="mt-2 text-2xl font-bold text-white" x-text="getCategoryName()"></h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="status-pill">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                <span x-text="mfqRankings?.participant_count || 0"></span> Peserta
                            </span>
                        </div>
                    </div>

                    <!-- Ranking Mode Toggle -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <button
                            @click="activeRankingMode = 'score'"
                            class="round-pill rounded-full border border-slate-700 px-4 py-2 text-sm font-semibold transition-all"
                            :class="activeRankingMode === 'score' ? 'active border-cyan-400/50 text-cyan-300' : 'text-slate-400 hover:text-white'"
                        >
                            Total Skor
                        </button>
                        <button
                            @click="activeRankingMode = 'points'"
                            class="round-pill rounded-full border border-slate-700 px-4 py-2 text-sm font-semibold transition-all"
                            :class="activeRankingMode === 'points' ? 'active border-cyan-400/50 text-cyan-300' : 'text-slate-400 hover:text-white'"
                        >
                            Poin Ranking
                        </button>
                    </div>

                    <!-- Round Pills for MFQ -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <template x-for="round in ['Semua', 'Penyisihan', 'Final']" :key="round">
                            <button
                                @click="activeRound = round"
                                class="round-pill rounded-full border border-slate-700 px-4 py-2 text-sm font-semibold transition-all"
                                :class="activeRound === round ? 'active border-cyan-400/50 text-cyan-300' : 'text-slate-400 hover:text-white'"
                                x-text="round"
                            ></button>
                        </template>
                    </div>

                    <!-- MFQ Ranking Table -->
                    <div class="overflow-hidden rounded-2xl border border-slate-700">
                        <table class="w-full">
                            <thead class="bg-slate-800/80">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Peserta</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Kecamatan</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-400">Babak</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Skor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50 bg-slate-900/50">
                                <template x-for="(leader, index) in getMfqFilteredLeaders()" :key="'mfq-rank-' + index">
                                    <tr class="hover:bg-slate-800/50 transition-colors" :class="index < 3 ? 'bg-amber-500/5' : ''">
                                        <td class="px-4 py-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg font-bold"
                                                :class="index === 0 ? 'bg-amber-500 text-white' : (index === 1 ? 'bg-slate-400 text-slate-900' : (index === 2 ? 'bg-orange-500 text-white' : 'bg-slate-700 text-slate-300'))">
                                                <span x-text="index + 1"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-white" x-text="leader.name || '-'"></p>
                                            <p class="text-xs text-slate-500" x-text="leader.institution || '-'"></p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <?= mtq_icon('building', 'h-3 w-3 text-slate-500') ?>
                                                <span class="text-sm text-slate-300" x-text="leader.district || '-'"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                                :class="leader.current_round === 'Final' ? 'bg-amber-400/20 text-amber-300 border border-amber-400/30' : 'bg-slate-700/50 text-slate-400 border border-slate-600/30'">
                                                <span x-text="leader.current_round || '-'"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <p class="text-lg font-bold text-cyan-200" x-text="(leader.total_score || 0).toFixed(2)"></p>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="getMfqFilteredLeaders().length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                        <?= mtq_icon('inbox', 'h-10 w-10 mx-auto mb-2 text-slate-600') ?>
                                        <p>Belum ada data ranking untuk Fahmil Qur'an.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Juara Umum Section -->
                <section id="juara-umum-section" x-show="showJuaraUmum" class="scroll-mt-6 rounded-[2rem] border border-amber-400/20 bg-gradient-to-br from-slate-900/95 via-amber-950/50 to-slate-900/95 p-6 shadow-[0_22px_65px_-32px_rgba(251,191,36,0.25)]">
                    <!-- Header -->
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <?= mtq_icon('crown', 'h-6 w-6 text-amber-300') ?>
                                <p class="section-kicker">Peringkat District</p>
                            </div>
                            <h2 class="mt-2 text-2xl font-bold text-white">Juara Umum</h2>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="rounded-full border border-slate-700 bg-slate-800/50 px-3 py-1 text-slate-400">
                                <span x-text="juaraUmumData?.participating_districts || 0"></span> / <span x-text="juaraUmumData?.total_districts || 0"></span> Kecamatan
                            </span>
                        </div>
                    </div>

                    <!-- Podium -->
                    <div class="mb-8" x-show="juaraUmumData?.top_three?.length > 0">
                        <div class="flex items-end justify-center gap-4">
                            <!-- 2nd Place -->
                            <div class="podium-card text-center w-36" x-show="juaraUmumData?.top_three[1]">
                                <div class="relative mx-auto mb-2">
                                    <div class="h-20 w-20 mx-auto rounded-full border-4 border-slate-400 bg-gradient-to-br from-slate-500 to-slate-600 flex items-center justify-center overflow-hidden shadow-lg">
                                        <span class="text-2xl">🥈</span>
                                    </div>
                                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-slate-400 to-slate-500 px-3 py-1 text-sm font-bold text-white shadow-lg">2</span>
                                </div>
                                <p class="font-bold text-white text-sm truncate" x-text="juaraUmumData?.top_three[1]?.district_name || '-'"></p>
                                <p class="mt-2 text-xl font-black text-slate-300" x-text="juaraUmumData?.top_three[1]?.total_points || 0"></p>
                            </div>

                            <!-- 1st Place -->
                            <div class="podium-card text-center w-44 glow-gold" x-show="juaraUmumData?.top_three[0]">
                                <div class="crown-icon absolute -translate-x-1/2 left-1/2 -mt-6">
                                    <?= mtq_icon('crown', 'h-10 w-10 text-amber-400') ?>
                                </div>
                                <div class="relative mx-auto mt-6 mb-2">
                                    <div class="h-24 w-24 mx-auto rounded-full border-4 border-amber-400 bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center overflow-hidden shadow-lg shadow-amber-400/30">
                                        <span class="text-3xl">🥇</span>
                                    </div>
                                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-amber-400 to-amber-500 px-3 py-1 text-sm font-bold text-white shadow-lg">1</span>
                                </div>
                                <p class="font-bold text-white text-base truncate" x-text="juaraUmumData?.top_three[0]?.district_name || '-'"></p>
                                <p class="mt-2 text-2xl font-black text-amber-300" x-text="juaraUmumData?.top_three[0]?.total_points || 0"></p>
                            </div>

                            <!-- 3rd Place -->
                            <div class="podium-card text-center w-36" x-show="juaraUmumData?.top_three[2]">
                                <div class="relative mx-auto mb-2">
                                    <div class="h-20 w-20 mx-auto rounded-full border-4 border-orange-600 bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center overflow-hidden shadow-lg">
                                        <span class="text-2xl">🥉</span>
                                    </div>
                                    <span class="absolute -top-2 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 px-3 py-1 text-sm font-bold text-white shadow-lg">3</span>
                                </div>
                                <p class="font-bold text-white text-sm truncate" x-text="juaraUmumData?.top_three[2]?.district_name || '-'"></p>
                                <p class="mt-2 text-xl font-black text-orange-400" x-text="juaraUmumData?.top_three[2]?.total_points || 0"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Rankings Table -->
                    <div class="overflow-hidden rounded-2xl border border-slate-700">
                        <table class="w-full">
                            <thead class="bg-slate-800/80">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Kecamatan</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Total Poin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50 bg-slate-900/50">
                                <template x-for="(district, index) in juaraUmumData?.rankings || []" :key="'district-' + index">
                                    <tr class="hover:bg-slate-800/50 transition-colors" :class="index < 3 ? 'bg-amber-500/5' : ''">
                                        <td class="px-4 py-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg font-bold"
                                                :class="index === 0 ? 'bg-amber-500 text-white' : (index === 1 ? 'bg-slate-400 text-slate-900' : (index === 2 ? 'bg-orange-500 text-white' : 'bg-slate-700 text-slate-300'))">
                                                <span x-text="index + 1"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-white" x-text="district.district_name || '-'"></p>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <p class="text-lg font-bold text-amber-300" x-text="district.total_points || 0"></p>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!juaraUmumData?.rankings?.length">
                                    <td colspan="3" class="px-4 py-8 text-center text-slate-500">
                                        <?= mtq_icon('inbox', 'h-10 w-10 mx-auto mb-2 text-slate-600') ?>
                                        <p>Belum ada data juara umum.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
