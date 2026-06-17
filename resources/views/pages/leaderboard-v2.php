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
            animation: bounce 2s ease-in-out infinite;
            display: inline-block;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
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
            'mfqCompletedSessions' => $mfqBranch['completed_sessions'] ?? [],
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
                    // If MFQ branch, also initialize MFQ data
                    if (branch.is_mfq) {
                        activeRound = 'Semua';
                        activeGender = 'all';
                    }
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
                        const gender = leader.gender || '';
                        return (activeGender === 'putra' && gender === 'putra') || (activeGender === 'putri' && gender === 'putri');
                    });
                }
                return leaders;
            };
	            getCategoryName = () => {
	                if (isMfqBranch()) {
	                    const cat = getCurrentMfqCategory();
	                    return cat ? cat.category_name : 'Fahmil Qur\'an';
	                }
	                const category = categoryGroups.find((item) => item.category_id === activeCategoryId);
	                return category ? category.category_name : '-';
	            };
	            getBranchName = () => {
	                if (isMfqBranch()) return 'Fahmil Qur\'an';
	                const category = categoryGroups.find((item) => item.category_id === activeCategoryId);
	                return category ? category.branch : '-';
	            };
	            getCurrentMfqCategory = () => {
	                const mfqBranchData = branches.find((b) => b.is_mfq);
	                if (!mfqBranchData || !mfqBranchData.categories) return null;
	                const cat = mfqBranchData.categories.find((c) => c.category_id === activeCategoryId);
	                if (!cat) return null;
	                // Use rankings from the category data directly
	                const rankings = cat.rankings || { rounds: { 'Semua': { by_rank: [], by_score: [] }, 'Penyisihan': { by_rank: [], by_score: [] }, 'Final': { by_rank: [], by_score: [] } } };
	                return { category_id: cat.category_id, category_name: cat.category_name, branch: cat.branch, is_mfq: true, rankings: rankings, completed_sessions: cat.completed_sessions || [] };
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
            getMfqRoundSessions = (round) => {
                const cat = getCurrentMfqCategory();
                if (!cat || !cat.completed_sessions) return [];
                return cat.completed_sessions.filter((s) => s.round === round);
            };
            hasMfqRoundData = (round) => {
                return getMfqRoundSessions(round).length > 0;
            };
            getMfqActiveTabs = () => {
                return ['Penyisihan', 'Final'].filter((round) => hasMfqRoundData(round));
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
                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card py-3 px-4">
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('layers', 'h-4 w-4 text-cyan-300') ?>
                            <p class="text-xs text-slate-400">Cabang Aktif</p>
                        </div>
                        <p class="mt-1 text-2xl font-bold text-white"><?= e($leaderboardStats['branches']) ?></p>
                    </div>
                    <div class="metric-card py-3 px-4">
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('users', 'h-4 w-4 text-emerald-300') ?>
                            <p class="text-xs text-slate-400">Peserta</p>
                        </div>
                        <p class="mt-1 text-2xl font-bold text-white"><?= e($leaderboardStats['verified_participants']) ?></p>
                    </div>
                    <div class="metric-card py-3 px-4">
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('trophy', 'h-4 w-4 text-amber-300') ?>
                            <p class="text-xs text-slate-400">Golongan</p>
                        </div>
                        <p class="mt-1 text-2xl font-bold text-white"><?= e($leaderboardStats['categories']) ?></p>
                    </div>
                    <div class="metric-card py-3 px-4">
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('chart-bar', 'h-4 w-4 text-pink-300') ?>
                            <p class="text-xs text-slate-400">Entri Nilai</p>
                        </div>
                        <p class="mt-1 text-2xl font-bold text-white"><?= e($leaderboardStats['score_entries']) ?></p>
                    </div>
                </section>

                <!-- Branch Selection -->
                <section class="glass-card rounded-[1.5rem] p-4">
                    <div class="flex items-center gap-3 mb-4">
                        <?= mtq_icon('target', 'h-4 w-4 text-cyan-300') ?>
                        <h3 class="text-lg font-bold text-white">Pilih Cabang</h3>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <?php foreach ($branchGroups as $branchGroup): ?>
                            <button
                                type="button"
                                @click="pickBranch(<?= e(json_encode($branchGroup['branch'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>)"
                                class="leaderboard-card rounded-xl border p-3 text-left transition-all duration-300 hover:-translate-y-1"
                                :class="activeBranch === <?= e(json_encode($branchGroup['branch'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?> ? 'border-cyan-400/50 bg-cyan-400/10 shadow-lg shadow-cyan-400/20' : 'border-slate-700 bg-slate-900/50 hover:border-slate-600'"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="rounded-full border border-amber-400/30 bg-amber-400/10 px-2 py-0.5 text-xs font-semibold text-amber-300">
                                        <?= e($branchGroup['category_total']) ?> Golongan
                                    </span>
                                </div>
                                <p class="text-sm font-bold text-white"><?= e($branchGroup['branch']) ?></p>
                                <div class="mt-1 flex items-center justify-between text-xs text-slate-400">
                                    <span><?= e($branchGroup['participant_total']) ?> Peserta</span>
                                </div>
                                <div class="mt-2 flex items-center gap-1">
                                    <span class="inline-flex items-center gap-1 rounded-full border <?= ($branchGroup['score_entry_status']['penyisihan_count'] ?? 0) > 0 ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300' : 'border-slate-600/50 bg-slate-700/30 text-slate-500' ?> px-1.5 py-0.5 text-[10px] font-medium">
                                        P:<?= $branchGroup['score_entry_status']['penyisihan_count'] ?? 0 ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full border <?= ($branchGroup['score_entry_status']['final_count'] ?? 0) > 0 ? 'border-amber-400/40 bg-amber-400/10 text-amber-300' : 'border-slate-600/50 bg-slate-700/30 text-slate-500' ?> px-1.5 py-0.5 text-[10px] font-medium">
                                        F:<?= $branchGroup['score_entry_status']['final_count'] ?? 0 ?>
                                    </span>
                                </div>
                            </button>
                        <?php endforeach; ?>

                        <!-- MFQ Category Cards (each category gets its own card) -->
                        <?php if ($mfqBranch && isset($mfqBranch['categories'])): ?>
                            <?php foreach ($mfqBranch['categories'] as $mfqCat): ?>
                                <button
                                    type="button"
                                    @click="activeBranch = '<?= e(addslashes($mfqBranch['branch'])) ?>'; activeCategoryId = <?= $mfqCat['category_id'] ?>; scrollToSection('ranking-section');"
                                    class="leaderboard-card rounded-xl border p-3 text-left transition-all duration-300 hover:-translate-y-1"
                                    :class="activeBranch === '<?= e(addslashes($mfqBranch['branch'])) ?>' && activeCategoryId === <?= $mfqCat['category_id'] ?> ? 'border-cyan-400/50 bg-cyan-400/10 shadow-lg shadow-cyan-400/20' : 'border-slate-700 bg-slate-900/50 hover:border-slate-600'"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="rounded-full border border-violet-400/30 bg-violet-400/10 px-2 py-0.5 text-xs font-semibold text-violet-300">
                                            MFQ
                                        </span>
                                    </div>
                                    <p class="text-sm font-bold text-white"><?= e($mfqCat['category_name']) ?></p>
                                    <div class="mt-1 flex items-center justify-between text-xs text-slate-400">
                                        <span><?= e($mfqCat['rankings']['participant_count'] ?? 0) ?> Peserta</span>
                                    </div>
                                    <div class="mt-2 flex items-center gap-1">
                                        <span class="inline-flex items-center gap-1 rounded-full border <?= ($mfqCat['score_entry_status']['penyisihan_count'] ?? 0) > 0 ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300' : 'border-slate-600/50 bg-slate-700/30 text-slate-500' ?> px-1.5 py-0.5 text-[10px] font-medium">
                                            P:<?= $mfqCat['score_entry_status']['penyisihan_count'] ?? 0 ?>
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full border <?= ($mfqCat['score_entry_status']['final_count'] ?? 0) > 0 ? 'border-amber-400/40 bg-amber-400/10 text-amber-300' : 'border-slate-600/50 bg-slate-700/30 text-slate-500' ?> px-1.5 py-0.5 text-[10px] font-medium">
                                            F:<?= $mfqCat['score_entry_status']['final_count'] ?? 0 ?>
                                        </span>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Category Selection -->
                <section id="golongan-section" class="glass-card rounded-[1.5rem] p-4 scroll-mt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <?= mtq_icon('award', 'h-4 w-4 text-amber-300') ?>
                            <h3 class="text-lg font-bold text-white">Pilih Golongan</h3>
                        </div>
                        <div class="status-pill text-xs">
                            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-300"></span>
                            <span x-text="activeBranch"></span>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <template x-for="branch in branches" :key="branch.branch">
                            <div x-show="activeBranch === branch.branch" class="contents">
                                <template x-for="category in (branch.categories || [])" :key="category.category_id">
                                    <button
                                        type="button"
                                        @click="selectCategory(category.category_id)"
                                        class="leaderboard-card rounded-xl border p-3 text-left transition-all duration-300"
                                        :class="activeCategoryId === category.category_id ? 'border-amber-400/50 bg-amber-400/10 shadow-lg shadow-amber-400/20' : 'border-slate-700 bg-slate-900/50 hover:border-slate-600'"
                                    >
                                        <p class="text-sm font-bold text-white" x-text="category.category_name"></p>
                                        <div class="mt-1 flex items-center justify-between">
                                            <p class="text-xs text-slate-400" x-text="(category.participant_total || 0) + ' Peserta'"></p>
                                            <div class="flex items-center gap-1">
                                                <span class="rounded-full border border-cyan-400/30 bg-cyan-400/10 px-1.5 py-0.5 text-xs font-semibold text-cyan-300" x-text="(category.putra_leaders || []).length + ' P'"></span>
                                                <span class="rounded-full border border-pink-400/30 bg-pink-400/10 px-1.5 py-0.5 text-xs font-semibold text-pink-300" x-text="(category.putri_leaders || []).length + ' W'"></span>
                                            </div>
                                        </div>
                                        <!-- Score Entry Count -->
                                        <div class="mt-2 flex items-center gap-1">
                                            <span class="inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-[10px] font-medium"
                                                :class="(category.score_entry_status?.penyisihan_count || 0) > 0 ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300' : 'border-slate-600/50 bg-slate-700/30 text-slate-500'">
                                                P:<span x-text="category.score_entry_status?.penyisihan_count || 0"></span>
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-[10px] font-medium"
                                                :class="(category.score_entry_status?.final_count || 0) > 0 ? 'border-amber-400/40 bg-amber-400/10 text-amber-300' : 'border-slate-600/50 bg-slate-700/30 text-slate-500'">
                                                F:<span x-text="category.score_entry_status?.final_count || 0"></span>
                                            </span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
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

                    <!-- Debug: Show leaders data for gender filter testing -->
                    <div x-data="{ showDebug: false }" class="mb-4">
                        <button type="button" @click="showDebug = !showDebug" class="text-xs text-slate-500 hover:text-slate-300 mb-2">
                            [Debug Leaders]
                        </button>
                        <div x-show="showDebug" class="p-4 rounded-xl border border-amber-400/30 bg-amber-400/10 text-xs max-h-48 overflow-auto">
                            <p class="text-amber-300 font-bold">Leaders Debug:</p>
                            <p class="text-slate-300">Active Gender: <span x-text="activeGender"></span></p>
                            <p class="text-slate-300">Total Leaders: <span x-text="getFilteredLeaders().length"></span></p>
                            <template x-for="(leader, idx) in getFilteredLeaders().slice(0, 5)" :key="'debug-' + idx">
                                <div class="mt-2 p-2 bg-slate-800/50 rounded">
                                    <p class="text-slate-300">Name: <span x-text="leader.name"></span></p>
                                    <p class="text-slate-300">Gender: <span x-text="leader.gender || 'null'"></span></p>
                                    <p class="text-slate-300">Round: <span x-text="leader.current_round || '-'"></span></p>
                                </div>
                            </template>
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

                    <!-- Podium (Top 3) - Show when there are leaders -->
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
                            <div class="podium-card text-center w-44 glow-gold podium-1st" x-show="getFilteredLeaders()[0]">
                                <div class="crown-icon">
                                    <?= mtq_icon('crown', 'h-10 w-10 text-amber-400') ?>
                                </div>
                                <div class="relative mx-auto mt-2 mb-2">
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

                    <!-- Ranking Table - Show for all filters -->
                    <div>
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
                    </div>
                </section>
                <?php else: ?>
                <section class="glass-card rounded-[2rem] p-8 text-center">
                    <?= mtq_icon('trophy', 'h-16 w-16 mx-auto mb-4 text-slate-600') ?>
                    <p class="text-lg text-slate-400">Pilih golongan terlebih dahulu untuk melihat ranking.</p>
                </section>
                <?php endif; ?>

                <!-- Global Debug -->
                <div x-data="{ showGlobalDebug: false }" class="fixed bottom-4 right-4 z-50">
                    <button type="button" @click="showGlobalDebug = !showGlobalDebug" class="bg-red-500 text-white px-3 py-1 rounded text-xs">
                        [Global Debug]
                    </button>
                    <div x-show="showGlobalDebug" class="bg-slate-900 border border-red-500 p-4 rounded-lg mt-2 text-xs max-w-md">
                        <p class="text-red-400 font-bold">Global Debug:</p>
                        <p class="text-white">Active Branch: <span x-text="activeBranch"></span></p>
                        <p class="text-white">Is MFQ Branch: <span x-text="isMfqBranch()"></span></p>
                        <p class="text-white">Total Branches: <span x-text="branches.length"></span></p>
                        <p class="text-white">Branch names: <span x-text="branches.map(b => b.branch + (b.is_mfq ? ' (MFQ)' : '')).join(', ')"></span></p>
                    </div>
                </div>

                <!-- MFQ Ranking Section - Same format as /penilaian/mfq -->
                <section x-show="isMfqBranch()" class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-6 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)]">
                    <!-- Header -->
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <?= mtq_icon('trophy', 'h-5 w-5 text-amber-300') ?>
                                <p class="section-kicker"><?= e($mfqBranch['branch'] ?? 'Fahmil Qur\'an') ?></p>
                            </div>
                            <h2 class="mt-2 text-2xl font-bold text-white" x-text="getCategoryName()"></h2>
                            <p class="mt-1 text-sm text-slate-400">Rekap hasil sesi MFQ dan ranking berdasarkan total nilai.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="status-pill">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                <span x-text="getCurrentMfqCategory()?.rankings?.participant_count || 0"></span> Peserta
                            </span>
                        </div>
                    </div>

                    <!-- Debug: Show raw data for debugging -->
                    <div x-data="{ showDebug: false }" class="mb-4">
                        <button type="button" @click="showDebug = !showDebug" class="text-xs text-slate-500 hover:text-slate-300 mb-2">
                            [Toggle Debug]
                        </button>
                        <div x-show="showDebug" class="p-4 rounded-xl border border-amber-400/30 bg-amber-400/10 text-xs">
                            <p class="text-amber-300 font-bold">Debug MFQ Data:</p>
                            <p class="text-slate-300">Total Sessions: <span x-text="getCurrentMfqCategory()?.completed_sessions?.length || 0"></span></p>
                            <p class="text-slate-300">Rankings Keys: <span x-text="Object.keys(getCurrentMfqCategory()?.rankings || {}).join(', ')"></span></p>
                            <p class="text-slate-300">Rankings Rounds: <span x-text="Object.keys(getCurrentMfqCategory()?.rankings?.rounds || {}).join(', ')"></span></p>
                            <p class="text-slate-300">Penyisihan by_score length: <span x-text="(getCurrentMfqCategory()?.rankings?.rounds?.Penyisihan?.by_score || []).length"></span></p>
                            <p class="text-slate-300">Final by_score length: <span x-text="(getCurrentMfqCategory()?.rankings?.rounds?.Final?.by_score || []).length"></span></p>
                        </div>
                    </div>

                    <!-- Tabs and Ranking Mode -->
                    <div x-data="{
                        mfqActiveTab: 'Penyisihan',
                        getMfqRankings(round) {
                            const catData = getCurrentMfqCategory();
                            if (!catData || !catData.rankings?.rounds) return [];
                            const roundData = catData.rankings.rounds[round];
                            if (!roundData) return [];
                            const leaders = activeRankingMode === 'points' ? (roundData.by_rank || []) : (roundData.by_score || []);
                            return leaders.slice(0, 20);
                        },
                        getMfqRoundSessions(round) {
                            const cat = getCurrentMfqCategory();
                            if (!cat || !cat.completed_sessions) return [];
                            return cat.completed_sessions.filter((s) => s.round === round);
                        }
                    }">
                        <!-- Tab Navigation -->
                        <div class="flex items-center justify-between mb-4 border-b border-slate-700">
                            <div class="flex items-center gap-2">
                                <template x-for="round in (getMfqActiveTabs().length ? getMfqActiveTabs() : ['Penyisihan', 'Final'])" :key="'mfq-tab-' + round">
                                    <button @click="mfqActiveTab = round"
                                            :class="mfqActiveTab === round ? 'border-b-2 border-amber-400 text-amber-400' : 'text-slate-400 hover:text-slate-200'"
                                            class="px-4 py-2 text-sm font-semibold transition-colors">
                                        <span x-text="round"></span>
                                        <span class="ml-1.5 rounded-full bg-slate-700/50 px-2 py-0.5 text-xs" x-text="getMfqRoundSessions(round).length"></span>
                                    </button>
                                </template>
                            </div>

                            <!-- Ranking Mode Selector -->
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400">Tampilan Ranking:</span>
                                <div class="flex rounded-xl border border-slate-700 bg-slate-800/50 p-0.5">
                                    <button @click="activeRankingMode = 'points'"
                                            :class="activeRankingMode === 'points' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-400/40' : 'text-slate-400 hover:text-slate-200 border border-transparent'"
                                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5">
                                        <?= mtq_icon('hash', 'h-3.5 w-3.5') ?>
                                        Poin Ranking
                                    </button>
                                    <button @click="activeRankingMode = 'score'"
                                            :class="activeRankingMode === 'score' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/40' : 'text-slate-400 hover:text-slate-200 border border-transparent'"
                                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1.5">
                                        <?= mtq_icon('zap', 'h-3.5 w-3.5') ?>
                                        Total Skor
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Content -->
                        <?php foreach (['Penyisihan', 'Final'] as $round): ?>
                            <div x-show="'<?= $round ?>' === mfqActiveTab" class="space-y-6">
                                <!-- Completed Sessions List -->
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-300 mb-3 flex items-center gap-2">
                                        <?= mtq_icon('check-circle', 'h-4 w-4 text-emerald-400') ?>
                                        Sesi <?= $round ?>
                                        <span class="ml-2 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2 py-0.5 text-xs text-cyan-300">
                                            <span x-text="'<?= $round ?>' === mfqActiveTab ? (activeRankingMode === 'points' ? 'Berdasarkan Poin' : 'Berdasarkan Total Skor') : ''"></span>
                                        </span>
                                    </h4>
                                    <div x-show="'<?= $round ?>' === mfqActiveTab && getMfqRoundSessions('<?= $round ?>').length > 0" class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                        <template x-for="(session, idx) in getMfqRoundSessions('<?= $round ?>')" :key="'mfq-session-' + idx">
                                            <div class="rounded-xl border border-slate-700/50 bg-slate-800/30 p-4">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <p class="font-semibold text-white" x-text="session.name || 'Sesi'"></p>
                                                        <p class="text-xs text-slate-400 mt-1" x-text="session.created_at || '-'"></p>
                                                    </div>
                                                    <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-2 py-0.5 text-xs font-semibold text-emerald-300"
                                                          x-text="(session.district_ids || []).length + ' Kec.'"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="'<?= $round ?>' === mfqActiveTab && getMfqRoundSessions('<?= $round ?>').length === 0" class="rounded-xl border border-slate-700/50 bg-slate-800/30 p-6 text-center">
                                        <p class="text-slate-500">Belum ada sesi <?= $round ?> yang diselesaikan.</p>
                                    </div>
                                </div>

                                <!-- Rankings Table -->
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-300 mb-3 flex items-center gap-2">
                                        <?= mtq_icon('hash', 'h-4 w-4 text-amber-400') ?>
                                        Ranking <?= $round ?>
                                    </h4>
                                    <div class="overflow-x-auto rounded-2xl border border-slate-700">
                                        <table class="w-full">
                                            <thead>
                                                <tr class="border-b border-slate-700">
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">No</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Nomor Lot</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Kecamatan</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Sesi</th>
                                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-400">Poin Ranking</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Total Nilai</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-700/50">
                                                <template x-for="(rank, index) in getMfqRankings('<?= $round ?>')" :key="'mfq-rank-<?= $round ?>-' + index">
                                                    <tr class="hover:bg-slate-800/50 transition-colors" :class="index < 3 ? 'bg-amber-500/5' : ''">
                                                        <td class="px-4 py-3">
                                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg font-bold"
                                                                 :class="index === 0 ? 'bg-amber-500 text-white' : (index === 1 ? 'bg-slate-400 text-slate-900' : (index === 2 ? 'bg-orange-500 text-white' : 'bg-slate-700 text-slate-300'))">
                                                                <span x-text="index + 1"></span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex flex-wrap gap-1">
                                                                <template x-for="lot in (rank.lot_numbers || [])" :key="'lot-' + index + '-' + lot">
                                                                    <span class="inline-flex rounded-full border border-amber-400/30 bg-amber-400/10 px-2 py-0.5 text-xs font-bold text-amber-300"
                                                                          x-text="lot"></span>
                                                                </template>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <span class="text-sm font-medium text-white" x-text="rank.district_name || '-'"></span>
                                                            <span class="text-xs text-slate-500 ml-2" x-text="'(' + (rank.participant_count || 0) + ' peserta)'"></span>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex flex-wrap gap-1">
                                                                <template x-for="(sessionName, sessionId) in (rank.session_names || {})" :key="'session-' + index + '-' + sessionId">
                                                                    <span class="inline-flex rounded-full border border-violet-400/30 bg-violet-400/10 px-2 py-0.5 text-[10px] font-semibold text-violet-300"
                                                                          x-text="sessionName"></span>
                                                                </template>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <span class="text-xl font-black text-amber-300" x-text="rank.total_points || 0"></span>
                                                            <div class="flex justify-center gap-1 mt-1">
                                                                <template x-for="(points, sessionId) in (rank.session_points || {})" :key="'pts-' + index + '-' + sessionId">
                                                                    <span class="inline-flex rounded-full bg-slate-700/50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-400"
                                                                          x-text="points + ' pts'"></span>
                                                                </template>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-right">
                                                            <span class="text-xl font-black text-emerald-400" x-text="Number(rank.total_score || 0).toLocaleString()"></span>
                                                            <div class="flex justify-end gap-1 mt-1">
                                                                <template x-for="(score, sessionId) in (rank.session_scores || {})" :key="'score-' + index + '-' + sessionId">
                                                                    <span class="inline-flex rounded-full bg-slate-700/50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-400"
                                                                          x-text="Number(score).toLocaleString()"></span>
                                                                </template>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <tr x-show="'<?= $round ?>' === mfqActiveTab && getMfqRankings('<?= $round ?>').length === 0">
                                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                                        <?= mtq_icon('inbox', 'h-10 w-10 mx-auto mb-2 text-slate-600') ?>
                                                        <p>Belum ada data ranking untuk <?= $round ?>.</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Normal Ranking Table (like other branches) -->
                                <div x-show="activeRound === 'Semua' && activeGender === 'all'">
                                    <h4 class="text-sm font-semibold text-slate-300 mb-3 flex items-center gap-2">
                                        <?= mtq_icon('list', 'h-4 w-4 text-cyan-400') ?>
                                        Ranking Final
                                    </h4>
                                    <div class="overflow-x-auto rounded-2xl border border-slate-700">
                                        <table class="w-full">
                                            <thead>
                                                <tr class="border-b border-slate-700">
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">#</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Kecamatan</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">No. Lot</th>
                                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-400">Babak</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Total Nilai</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-700/50">
                                                <template x-for="(rank, index) in getMfqRankings('Semua')" :key="'mfq-normal-rank-' + index">
                                                    <tr class="hover:bg-slate-800/50 transition-colors" :class="index < 3 ? 'bg-amber-500/5' : ''">
                                                        <td class="px-4 py-3">
                                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg font-bold"
                                                                 :class="index === 0 ? 'bg-amber-500 text-white' : (index === 1 ? 'bg-slate-400 text-slate-900' : (index === 2 ? 'bg-orange-500 text-white' : 'bg-slate-700 text-slate-300'))">
                                                                <span x-text="index + 1"></span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <p class="font-semibold text-white" x-text="rank.district_name || '-'"></p>
                                                            <p class="text-xs text-slate-500" x-text="(rank.participant_count || 0) + ' peserta'"></p>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex flex-wrap gap-1">
                                                                <template x-for="lot in (rank.lot_numbers || [])" :key="'lot-normal-' + index + '-' + lot">
                                                                    <span class="inline-flex rounded-full border border-amber-400/30 bg-amber-400/10 px-2 py-0.5 text-xs font-bold text-amber-300"
                                                                          x-text="lot"></span>
                                                                </template>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                                                  :class="rank.current_round === 'Final' ? 'bg-amber-400/20 text-amber-300 border border-amber-400/30' : 'bg-slate-700/50 text-slate-400 border border-slate-600/30'">
                                                                <span x-text="rank.current_round || '-'"></span>
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 text-right">
                                                            <p class="text-lg font-bold text-cyan-200" x-text="Number(rank.total_score || 0).toLocaleString()"></p>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <tr x-show="getMfqRankings('Semua').length === 0">
                                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                                        <?= mtq_icon('inbox', 'h-10 w-10 mx-auto mb-2 text-slate-600') ?>
                                                        <p>Belum ada data ranking.</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                            <div x-data="{ showInfo: false }" class="relative">
                                <button type="button" @click="showInfo = !showInfo" @click.outside="showInfo = false" class="rounded-full border border-slate-700 bg-slate-800/50 p-1.5 text-slate-400 hover:text-cyan-300 hover:border-cyan-400/50 transition-colors">
                                    <?= mtq_icon('info', 'h-4 w-4') ?>
                                </button>
                                <div x-show="showInfo" x-transition class="absolute right-0 top-full mt-2 z-50 w-72 rounded-xl border border-slate-600 bg-slate-900/95 p-4 shadow-xl">
                                    <h4 class="mb-2 font-bold text-amber-300">Sistem Poin Juara Umum</h4>
                                    <ul class="space-y-1 text-xs text-slate-300">
                                        <li><span class="font-bold text-amber-400">Juara 1:</span> 9 Poin</li>
                                        <li><span class="font-bold text-slate-400">Juara 2:</span> 7 Poin</li>
                                        <li><span class="font-bold text-orange-400">Juara 3:</span> 5 Poin</li>
                                        <li><span class="font-bold text-slate-300">Juara 4:</span> 3 Poin</li>
                                        <li><span class="font-bold text-slate-300">Juara 5:</span> 2 Poin</li>
                                        <li><span class="font-bold text-slate-300">Juara 6:</span> 1 Poin</li>
                                    </ul>
                                    <p class="mt-2 border-t border-slate-700 pt-2 text-xs text-slate-400">Poin diakumulasi dari semua cabang dan golongan.</p>
                                </div>
                            </div>
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
                            <div class="podium-card text-center w-44 glow-gold podium-1st" x-show="juaraUmumData?.top_three[0]">
                                <div class="crown-icon">
                                    <?= mtq_icon('crown', 'h-10 w-10 text-amber-400') ?>
                                </div>
                                <div class="relative mx-auto mt-2 mb-2">
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
