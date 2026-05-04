<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$categoryGroups = $categoryGroups ?? collect();
$branchGroups = $branchGroups ?? collect();
$selectedCategoryId = $selectedCategoryId ?? 0;
$selectedCategoryData = $selectedCategoryData ?? null;
$selectedBranch = $selectedBranch ?? null;
$leaderboardStats = $leaderboardStats ?? ['branches' => 0, 'verified_participants' => 0, 'categories' => 0, 'score_entries' => 0];
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'leaderboard');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Leaderboard Golongan') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg scroll-smooth min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main
        class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8"
        x-data='{
            mobileNavOpen: false,
            branches: <?= e(json_encode($branchGroups->values()->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>,
            activeBranch: <?= e(json_encode($selectedBranch ?? ($branchGroups->first()["branch"] ?? ""))) ?>,
            activeCategoryId: <?= (int) $selectedCategoryId ?>,
            scrollToSection(sectionId) {
                this.$nextTick(() => {
                    const target = document.getElementById(sectionId);
                    if (target) {
                        target.scrollIntoView({ behavior: "smooth", block: "start" });
                    }
                });
            },
            pickBranch(branchName) {
                this.activeBranch = branchName;
                const branch = this.branches.find((item) => item.branch === branchName);
                if (branch && branch.categories && branch.categories.length) {
                    this.activeCategoryId = branch.categories[0].category_id;
                }
                this.scrollToSection("golongan-section");
            },
            openCategory(categoryId) {
                const url = "<?= e(route('leaderboard.index')) ?>?competition_category_id=" + categoryId + "#ranking-section";
                window.location.href = url;
            }
        }'
    >
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('trophy') ?></div>
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
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($leaderboardStats['verified_participants']) ?> peserta terverifikasi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Ranking per golongan dipisah menjadi babak Penyisihan dan Final agar perkembangan skor lebih mudah dibaca.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                        Live leaderboard
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
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Cabang</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['branches']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Golongan</p>
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

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Ranking Golongan</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Leaderboard Penyisihan dan Final</h2>
                            <p class="mt-2 text-sm text-slate-300">Mulai dari cabang, lanjut ke golongan, lalu buka ranking yang ingin kamu lihat.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="<?= e(route('dashboard')) ?>" class="secondary-button">
                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                            Kembali ke Dashboard
                        </a>
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('trophy') ?></div><p class="mt-4 text-sm text-slate-400">Cabang Aktif</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['branches']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Peserta Terverifikasi</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['verified_participants']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('layers') ?></div><p class="mt-4 text-sm text-slate-400">Golongan</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['categories']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('chart') ?></div><p class="mt-4 text-sm text-slate-400">Entri Nilai</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($leaderboardStats['score_entries']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl">
                            <div class="icon-chip"><?= mtq_icon('spark') ?></div>
                            <p class="mt-5 section-kicker">Langkah 1</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Pilih cabang dulu, baru pilih golongannya</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-300">
                                Alurnya dibuat bertahap supaya lebih mudah dipindai. Begitu golongan dipilih, halaman langsung membawa kamu ke ranking golongan itu.
                            </p>
                        </div>
                        <div class="status-pill self-start lg:self-auto">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?= e($leaderboardStats['branches']) ?> cabang aktif
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cabang MTQ</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <?php foreach ($branchGroups as $branchGroup): ?>
                                    <button
                                        type="button"
                                        @click="pickBranch(<?= e(json_encode($branchGroup['branch'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>)"
                                        class="group rounded-2xl border px-4 py-4 text-left transition duration-200 hover:-translate-y-0.5 <?= ($selectedBranch === $branchGroup['branch']) ? 'border-cyan-300/40 bg-cyan-400/12 text-white shadow-[0_18px_55px_-28px_rgba(34,211,238,0.5)]' : 'border-slate-700 bg-slate-900/70 text-slate-300 hover:border-slate-500 hover:bg-slate-900' ?>"
                                        :class="activeBranch === <?= e(json_encode($branchGroup['branch'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?> ? 'border-cyan-300/40 bg-cyan-400/12 text-white shadow-[0_18px_55px_-28px_rgba(34,211,238,0.5)]' : ''"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Cabang</p>
                                                <p class="mt-2 text-lg font-bold leading-snug"><?= e($branchGroup['branch']) ?></p>
                                            </div>
                                            <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-slate-200"><?= e($branchGroup['category_total']) ?> golongan</span>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-2 text-[11px]">
                                            <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-slate-200"><?= e($branchGroup['participant_total']) ?> peserta</span>
                                            <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-slate-200"><?= e($branchGroup['score_entries']) ?> entri nilai</span>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="golongan-section" class="rounded-[1.75rem] border border-slate-700 bg-slate-950/50 p-5 scroll-mt-6">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="section-kicker">Langkah 2</p>
                                    <h4 class="mt-2 text-xl font-bold text-white">Pilih golongan</h4>
                                </div>
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                    Klik untuk lihat ranking
                                </div>
                            </div>

                            <template x-for="branch in branches" :key="branch.branch">
                                <div x-show="activeBranch === branch.branch" x-transition.opacity.duration.250ms x-transition.scale.duration.250ms class="mt-5 space-y-3">
                                    <p class="text-sm text-slate-300" x-text="branch.branch"></p>
                                    <template x-for="category in branch.categories" :key="category.category_id">
                                        <button
                                            type="button"
                                            @click="openCategory(category.category_id)"
                                            class="group w-full rounded-2xl border px-4 py-4 text-left transition duration-200 hover:-translate-y-0.5"
                                            :class="activeCategoryId === category.category_id ? 'border-cyan-300/40 bg-cyan-400/12 text-white shadow-[0_18px_55px_-28px_rgba(34,211,238,0.5)]' : 'border-slate-700 bg-slate-900/70 text-slate-300 hover:border-slate-500 hover:bg-slate-900'"
                                        >
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400" x-text="branch.branch"></p>
                                                    <p class="mt-2 text-lg font-bold leading-snug" x-text="category.category_name"></p>
                                                </div>
                                                <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-slate-200" x-text="category.participant_total + ' peserta'"></span>
                                            </div>
                                            <div class="mt-4 flex flex-wrap gap-2 text-[11px]">
                                                <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-slate-200" x-text="category.score_entries + ' entri nilai'"></span>
                                                <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-slate-200">Lihat ranking</span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>

                <?php if ($selectedCategoryData): ?>
                    <section id="ranking-section" class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-6 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)] scroll-mt-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="icon-chip"><?= mtq_icon('trophy') ?></div>
                                <p class="mt-5 section-kicker">Golongan Terpilih</p>
                                <h2 class="mt-2 text-2xl font-bold text-white"><?= e($selectedCategoryData['category_name']) ?></h2>
                                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                                    Ranking golongan ini menampilkan peserta terbaik pada babak Penyisihan dan Final.
                                </p>
                            </div>
                            <div class="status-pill">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                <?= e($selectedCategoryData['participant_total']) ?> peserta
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-3">
                            <div class="data-card">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Cabang</p>
                                <p class="mt-2 text-2xl font-bold text-white"><?= e($selectedCategoryData['branch']) ?></p>
                            </div>
                            <div class="data-card">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Peserta Dinilai</p>
                                <p class="mt-2 text-2xl font-bold text-emerald-300"><?= e($selectedCategoryData['participant_total']) ?></p>
                            </div>
                            <div class="data-card">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Entri Nilai</p>
                                <p class="mt-2 text-2xl font-bold text-cyan-200"><?= e($selectedCategoryData['score_entries']) ?></p>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-2">
                        <?php foreach (['Penyisihan', 'Final'] as $roundLabel): ?>
                            <?php $roundData = $selectedCategoryData['rounds'][$roundLabel] ?? ['leaders' => []]; ?>
                            <div class="glass-card rounded-[2rem] p-6">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                                        <p class="mt-5 section-kicker"><?= e($roundLabel) ?></p>
                                        <h3 class="mt-2 text-2xl font-bold text-white">Ranking babak <?= e($roundLabel) ?></h3>
                                    </div>
                                    <div class="status-pill">
                                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                        <?= e(count($roundData['leaders'] ?? [])) ?> peserta
                                    </div>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <?php if (empty($roundData['leaders'])): ?>
                                        <div class="data-card text-sm text-slate-300">Belum ada skor untuk babak <?= e($roundLabel) ?> pada cabang ini.</div>
                                    <?php else: ?>
                                        <?php foreach ($roundData['leaders'] as $index => $leader): ?>
                                            <?php
                                            $rank = $index + 1;
                                            $rankClass = match ($rank) {
                                                1 => 'border-amber-300/20 bg-amber-400/10 text-amber-100',
                                                2 => 'border-slate-300/20 bg-slate-300/10 text-slate-100',
                                                3 => 'border-orange-300/20 bg-orange-400/10 text-orange-100',
                                                default => 'border-white/10 bg-slate-900/60 text-white',
                                            };
                                            ?>
                                            <div class="rounded-3xl border border-white/10 bg-slate-950/50 p-4">
                                                <div class="flex items-start gap-4">
                                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border <?= e($rankClass) ?> text-xl font-black">
                                                        <?= e($rank) ?>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                                            <div>
                                                                <p class="font-semibold text-white"><?= e($leader['name']) ?></p>
                                                                <p class="mt-1 text-xs text-slate-400"><?= e($leader['district']) ?> | <?= e($leader['category']) ?></p>
                                                                <p class="mt-1 text-xs text-slate-500"><?= e($leader['institution'] ?: '-') ?></p>
                                                            </div>
                                                            <div class="text-right">
                                                                <p class="text-2xl font-black text-cyan-200"><?= e($leader['average_score']) ?></p>
                                                                <p class="text-xs text-slate-400">Rata-rata babak</p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                                                            <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-slate-200"><?= e($leader['latest_score']) ?> nilai terakhir</span>
                                                            <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-slate-200"><?= e($leader['entry_count']) ?> entri</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('users') ?></div>
                            <div>
                                <p class="section-kicker">Peringkat Tiga Besar</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Performa terbaik golongan ini</h3>
                            </div>
                        </div>
                        <div class="mt-6 grid gap-4 md:grid-cols-3">
                            <?php foreach (($selectedCategoryData['overall_leaders'] ?? []) as $index => $leader): ?>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Peringkat <?= e($index + 1) ?></p>
                                    <p class="mt-3 text-lg font-bold text-white"><?= e($leader['name']) ?></p>
                                    <p class="mt-2 text-sm text-slate-400"><?= e($leader['category']) ?></p>
                                    <p class="mt-4 text-3xl font-black text-cyan-200"><?= e($leader['average_score'] ?? '0.00') ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="glass-card rounded-[2rem] p-6 text-sm text-slate-300">
                        Belum ada data leaderboard yang siap ditampilkan.
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
