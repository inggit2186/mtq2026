<?php
require_once __DIR__.'/../partials/icon.php';

$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'participants.maqra.menu');
$district = $district ?? null;
$scopeLabel = $scopeLabel ?? ($district?->name ?? 'Semua Golongan');
$categories = $categories ?? collect();
$participants = $participants ?? collect();
$participantsByCategory = $participantsByCategory ?? $participants->groupBy(fn ($participant) => (int) $participant->competition_category_id);
$selectedParticipant = $selectedParticipant ?? null;
$selectedCategory = $selectedCategory ?? null;
$summaryStats = $summaryStats ?? ['category_total' => 0, 'participant_total' => 0, 'maqra_total' => 0];
$roundLabel = $roundLabel ?? 'Penyisihan';
$filters = $filters ?? ['round' => $roundLabel, 'participant_id' => ''];
$judgeNameDefault = $judgeNameDefault ?? (string) $user?->name;
$maqraOpenCategoriesSummary = $maqraOpenCategoriesSummary ?? collect();
$initialActiveCategoryId = (string) ($selectedCategory?->id ?? ($categories->first()?->id ?? ''));
$maqraRemainingPackagesByCategory = $maqraRemainingPackagesByCategory ?? collect();
$officialAccessSetting = $officialAccessSetting ?? new \App\Models\OfficialAccessSetting();
$maqraSelectedCategoryLotRange = $maqraSelectedCategoryLotRange ?? null;
$selectedMaqraLotRange = is_array($maqraSelectedCategoryLotRange) ? $maqraSelectedCategoryLotRange : null;
$selectedMaqraLotRangeLabel = is_array($selectedMaqraLotRange)
    ? sprintf('%02d - %02d', (int) ($selectedMaqraLotRange['min'] ?? 0), (int) ($selectedMaqraLotRange['max'] ?? 0))
    : 'Semua lot';
$maqraRoundHasActiveSchedule = $maqraRoundHasActiveSchedule ?? false;
$selectedMaqraStatusLabel = $maqraRoundHasActiveSchedule ? 'Dibuka' : 'Ditutup';
$maqraActiveSchedules = $maqraActiveSchedules ?? collect();
$categoryScheduleData = $categoryScheduleData ?? [];
$selectedMaqraRoundStatusLabel = 'Babak '.$roundLabel.' '.$selectedMaqraStatusLabel;
$selectedMaqraCategoryLabel = $selectedCategory
    ? trim((string) $selectedCategory->branch.' - '.(string) $selectedCategory->name)
    : 'Semua Golongan';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Pengambilan Maqra') ?></title>
    <?php foreach (($assets['css'] ?? []) as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false, activeCategoryId: '<?= e($initialActiveCategoryId) ?>' }">
        <div class="hero-orb hero-orb-fuchsia right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>
        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block" x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('sparkles') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-fuchsia-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Pengambilan Maqra</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-fuchsia-400/14 bg-gradient-to-br from-slate-900/90 via-violet-950/70 to-fuchsia-950/60 p-5">
                    <p class="section-kicker">Ringkasan</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($summaryStats['participant_total']) ?> peserta terverifikasi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Halaman ini menampilkan peserta yang memakai maqra pada wilayah atau seluruh kecamatan, tergantung peran pengguna.</p>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>
            </aside>

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Pengambilan Maqra</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Menu pemilihan peserta maqra</h2>
                            <p class="mt-2 text-sm text-slate-300">Gunakan halaman ini untuk menelusuri peserta yang memakai maqra dan memilih babak penilaian.</p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Akses aktif
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-3">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('book-open') ?></div><p class="mt-4 text-sm text-slate-400">Golongan</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['category_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Peserta</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['participant_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Maqra Tersedia</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($summaryStats['maqra_total']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Lingkup Akses</p>
                            <h3 class="mt-2 text-2xl font-bold text-white"><?= e($scopeLabel) ?></h3>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">
                                Admin melihat semua peserta terverifikasi yang menggunakan maqra. Official dibatasi pada kecamatan masing-masing, sedangkan panitia mengikuti hak akses golongan yang dimiliki akun.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 md:grid-cols-3">
                        <div class="rounded-[1.4rem] border border-emerald-400/14 bg-emerald-400/8 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-emerald-200/80">Status</p>
                            <p class="mt-2 text-lg font-bold text-white"><?= e($selectedMaqraStatusLabel) ?></p>
                            <p class="mt-1 text-xs text-slate-300">Pengambilan maqra <?= $selectedMaqraStatusLabel === 'Dibuka' ? 'sedang aktif' : 'sedang ditutup' ?> oleh admin.</p>
                        </div>
                        <div class="rounded-[1.4rem] border border-fuchsia-400/14 bg-fuchsia-400/8 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-fuchsia-200/80">Golongan</p>
                            <p class="mt-2 text-lg font-bold text-white"><?= e($selectedMaqraCategoryLabel) ?></p>
                            <p class="mt-1 text-xs text-slate-300">Golongan aktif yang sedang ditampilkan di daftar.</p>
                        </div>
                        <div class="rounded-[1.4rem] border border-cyan-400/14 bg-cyan-400/8 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-cyan-200/80">Nomor Lot</p>
                            <p class="mt-2 text-lg font-bold text-white"><?= e($selectedMaqraLotRangeLabel) ?></p>
                            <p class="mt-1 text-xs text-slate-300">Rentang lot yang saat ini dibuka untuk golongan tersebut.</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-[1.4rem] border <?= $selectedMaqraStatusLabel === 'Dibuka' ? 'border-emerald-400/14 bg-emerald-400/8' : 'border-rose-400/14 bg-rose-400/8' ?> p-4">
                        <p class="text-xs uppercase tracking-[0.18em] <?= $selectedMaqraStatusLabel === 'Dibuka' ? 'text-emerald-200/80' : 'text-rose-200/80' ?>">Babak Dibuka</p>
                        <p class="mt-2 text-lg font-bold text-white"><?= e($selectedMaqraRoundStatusLabel) ?></p>
                        <p class="mt-1 text-xs text-slate-300">Status ini mengikuti pengaturan admin untuk babak yang sedang dipilih.</p>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <?php
                        $maqraRounds = $maqraActiveSchedules ? $maqraActiveSchedules->groupBy(fn ($s) => $s->round?->name ?? 'Lainnya') : collect();
                        foreach (['Penyisihan', 'Final'] as $roundOption):
                            $roundHasSchedule = $maqraRounds->has($roundOption);
                            $roundEnabled = $roundHasSchedule;
                        ?>
                            <a href="<?= e(route('participants.maqra.menu', ['round' => $roundOption])) ?>" class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] <?= $roundLabel === $roundOption ? ($roundEnabled ? 'border-fuchsia-300/40 bg-fuchsia-400/10 text-fuchsia-100' : 'border-rose-300/40 bg-rose-400/10 text-rose-100') : ($roundEnabled ? 'border-slate-700 bg-slate-950/60 text-slate-300 hover:border-slate-600' : 'border-rose-400/20 bg-rose-400/10 text-rose-100 hover:border-rose-300/40') ?>">
                                <span class="block text-center"><?= e($roundOption) ?></span>
                                <span class="mt-1 block text-[10px] font-semibold normal-case tracking-[0.14em] <?= $roundEnabled ? 'text-emerald-200/90' : 'text-rose-200/90' ?>">
                                    <?= $roundEnabled ? 'Dibuka' : 'Ditutup' ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <?php
                    // Filter to only show active or scheduled categories for display
                    $visibleCategories = $categories->filter(function ($category) use ($categoryScheduleData) {
                        $data = $categoryScheduleData[$category->id] ?? null;
                        return $data && in_array($data['status'], ['active', 'scheduled']);
                    });
                    ?>
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Jadwal Maqra</p>
                            <h3 class="mt-2 text-2xl font-bold text-white"><?= e($visibleCategories->count()) ?> golongan <?= $visibleCategories->contains(fn ($c) => ($categoryScheduleData[$c->id]['status'] ?? '') === 'active') ? 'sedang buka' : 'terjadwal' ?></h3>
                            <p class="mt-2 text-sm text-slate-300">Klik kartu golongan untuk langsung lompat ke daftar peserta pada golongan tersebut.</p>
                        </div>
                    </div>

                    <?php if ($visibleCategories->isEmpty()): ?>
                        <div class="mt-6 rounded-[1.5rem] border border-dashed border-slate-700 bg-slate-950/50 p-6 text-sm text-slate-400">
                            Belum ada jadwal maqra yang sedang aktif atau terjadwal untuk babak ini.
                        </div>
                    <?php else: ?>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <?php foreach ($visibleCategories as $category): ?>
                                <?php
                                    $categoryParticipants = $participantsByCategory->get($category->id, collect());
                                    $scheduleData = $categoryScheduleData[$category->id] ?? null;
                                    $currentSchedule = $scheduleData['current'] ?? null;
                                    $upcomingSchedule = $scheduleData['upcoming'] ?? null;
                                    $status = $scheduleData['status'] ?? 'closed';

                                    $statusConfig = match($status) {
                                        'active' => ['label' => 'Sedang Buka', 'color' => 'emerald', 'bg' => 'bg-emerald-400/10', 'border' => 'border-emerald-400/30'],
                                        'scheduled' => ['label' => 'Terjadwal', 'color' => 'amber', 'bg' => 'bg-amber-400/10', 'border' => 'border-amber-400/30'],
                                        default => ['label' => 'Ditutup', 'color' => 'slate', 'bg' => 'bg-slate-700/30', 'border' => 'border-slate-700/50'],
                                    };

                                    $scheduleToShow = $currentSchedule ?? $upcomingSchedule;
                                ?>
                                <a
                                    href="#golongan-<?= e((string) $category->id) ?>"
                                    x-on:click.prevent="$nextTick(() => { activeCategoryId = '<?= e((string) $category->id) ?>'; const section = document.getElementById('golongan-<?= e((string) $category->id) ?>'); if(section){section.scrollIntoView({ behavior: 'smooth', block: 'start' }); if(window.flashMaqraSection) window.flashMaqraSection(section); } })"
                                    x-bind:class="activeCategoryId === '<?= e((string) $category->id) ?>' ? 'ring-2 ring-fuchsia-400 ring-offset-2 ring-offset-slate-950' : ''"
                                    class="block w-full rounded-[1.5rem] border px-4 py-4 transition <?= $status === 'active' ? 'border-emerald-400/30 bg-emerald-400/5 hover:border-emerald-400/50' : 'border-amber-400/30 bg-amber-400/5 hover:border-amber-400/50' ?>"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400"><?= e((string) $category->branch) ?></p>
                                            <p class="mt-2 text-base font-bold text-white"><?= e((string) $category->name) ?></p>
                                            <p class="mt-2 text-xs text-slate-400"><?= e($categoryParticipants->count()) ?> peserta</p>
                                        </div>
                                        <div class="flex flex-col items-end gap-2">
                                            <span class="inline-flex items-center gap-1.5 rounded-full border <?= e($statusConfig['border'].' '.$statusConfig['bg']) ?> px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-<?= e($statusConfig['color']) ?>-100">
                                                <span x-show="activeCategoryId === '<?= e((string) $category->id) ?>'" class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                                <?= e($statusConfig['label']) ?>
                                            </span>
                                            <?php if ($scheduleToShow): ?>
                                            <span class="text-[11px] font-semibold text-slate-300">
                                                Lot <?= e($scheduleToShow->lot_min) ?>-<?= e($scheduleToShow->lot_max) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($status === 'scheduled' && $upcomingSchedule): ?>
                                    <div class="mt-3 flex items-center gap-2 rounded-xl border border-amber-400/20 bg-amber-400/5 px-3 py-2">
                                        <span class="text-amber-400"><?= mtq_icon('clock', 'h-4 w-4') ?></span>
                                        <span class="text-[11px] font-semibold text-amber-200">
                                            Terjadwal
                                        </span>
                                    </div>
                                    <?php elseif ($status === 'active' && $currentSchedule): ?>
                                    <div class="mt-3 flex items-center gap-2 rounded-xl border border-emerald-400/20 bg-emerald-400/5 px-3 py-2">
                                        <span class="text-emerald-400"><?= mtq_icon('zap', 'h-4 w-4') ?></span>
                                        <span class="text-[11px] font-semibold text-emerald-200">
                                            Sedang Buka
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                </section>

                <?php foreach ($categories as $category): ?>
                    <?php
                        $categoryParticipants = $participantsByCategory->get($category->id, collect())->values();
                        $isDistrictMaqraCategory = app(\App\Http\Controllers\PageController::class)->categoryMaqraUsesDistrictSharing($category);
                        $isActiveCategory = (int) ($selectedCategory?->id ?? 0) === (int) $category->id;
                        $focusParticipantId = $categoryParticipants->first()?->id;
                        $remainingPackages = (int) ($maqraRemainingPackagesByCategory[$category->id] ?? 0);
                        $categoryStockEmpty = $remainingPackages <= 0 && $categoryParticipants->isNotEmpty();
                        $categoryAccent = $isActiveCategory
                            ? 'border-fuchsia-300/30 bg-fuchsia-400/8'
                            : 'border-slate-800 bg-slate-950/55';
                        $categoryHeaderAccent = $isActiveCategory
                            ? 'from-fuchsia-400/35 via-pink-400/20 to-transparent'
                            : 'from-fuchsia-400/20 via-violet-400/15 to-transparent';
                        $districtGroups = $isDistrictMaqraCategory
                            ? $categoryParticipants
                                ->groupBy(fn ($participant) => (int) ($participant->district_id ?? 0))
                                ->sortBy(fn ($group) => (string) ($group->first()?->district?->name ?? ''))
                            : collect();

                        // Schedule data for countdown
                        $catScheduleData = $categoryScheduleData[$category->id] ?? null;
                        $catCurrentSchedule = $catScheduleData['current'] ?? null;
                        $catUpcomingSchedule = $catScheduleData['upcoming'] ?? null;
                        $catStatus = $catScheduleData['status'] ?? 'closed';
                        $countdownTarget = $catStatus === 'scheduled' ? $catUpcomingSchedule : $catCurrentSchedule;
                        $countdownType = $catStatus === 'scheduled' ? 'scheduled' : 'active';
                    ?>
                    <section
                        id="golongan-<?= e($category->id) ?>"
                        data-category-section
                        data-category-id="<?= e((string) $category->id) ?>"
                        class="relative overflow-hidden glass-card rounded-[2rem] p-6 border"
                        x-data
                        x-cloak
                        x-show="activeCategoryId === '<?= e((string) $category->id) ?>'"
                        x-bind:class="activeCategoryId === '<?= e((string) $category->id) ?>'
                            ? 'ring-2 ring-fuchsia-300/20 border-fuchsia-300/30 bg-fuchsia-400/8'
                            : 'border-slate-800 bg-slate-950/55'"
                        x-transition.opacity.duration.200ms
                    >
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r <?= $categoryHeaderAccent ?>"></div>
                        <div
                            class="flex flex-wrap items-center justify-between gap-4 rounded-[1.35rem] border border-transparent p-1 transition hover:border-fuchsia-300/20 hover:bg-white/5"
                            role="button"
                            tabindex="0"
                            x-on:click="activeCategoryId = '<?= e((string) $category->id) ?>'; $nextTick(() => { const section = document.getElementById('golongan-<?= e($category->id) ?>'); section?.scrollIntoView({ behavior: 'smooth', block: 'start' }); window.flashMaqraSection?.(section); })"
                            x-on:keydown.enter.prevent="activeCategoryId = '<?= e((string) $category->id) ?>'; $nextTick(() => { const section = document.getElementById('golongan-<?= e($category->id) ?>'); section?.scrollIntoView({ behavior: 'smooth', block: 'start' }); window.flashMaqraSection?.(section); })"
                            x-on:keydown.space.prevent="activeCategoryId = '<?= e((string) $category->id) ?>'; $nextTick(() => { const section = document.getElementById('golongan-<?= e($category->id) ?>'); section?.scrollIntoView({ behavior: 'smooth', block: 'start' }); window.flashMaqraSection?.(section); })"
                        >
                            <div>
                                <p class="section-kicker">Golongan</p>
                                <h3 class="mt-2 text-2xl font-bold text-white"><?= e($category->branch.' - '.$category->name) ?></h3>
                                <?php if ($catStatus !== 'scheduled'): ?>
                                <p class="mt-2 text-sm text-slate-300"><?= e($categoryParticipants->count()) ?> peserta terverifikasi siap dipilih untuk maqra</p>
                                <?php endif; ?>
                                <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-fuchsia-100"><?= e(app(\App\Http\Controllers\PageController::class)->categoryMaqraRuleLabel($category)) ?></p>
                                <?php if ($categoryStockEmpty): ?>
                                    <div class="mt-3 inline-flex rounded-full border border-rose-400/20 bg-rose-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-rose-100">
                                        Stok maqra habis
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($catStatus !== 'scheduled'): ?>
                            <div class="flex flex-wrap gap-2">
                                <span class="status-pill border-fuchsia-400/20 bg-fuchsia-400/10 text-fuchsia-100">
                                    <?= mtq_icon('users', 'h-4 w-4') ?>
                                    <?= e($categoryParticipants->count()) ?> peserta
                                </span>
                                <span class="inline-flex rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300">
                                    <?= $categoryStockEmpty ? 'Stok habis' : ($isDistrictMaqraCategory ? '1 kecamatan = 1 maqra' : '1 peserta = 1 maqra') ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($countdownTarget): ?>
                        <div class="mt-6 rounded-[1.5rem] border border-amber-400/30 bg-amber-400/10 p-6 text-center"
                             x-data="maqraCountdown('<?= e($countdownTarget->{($countdownType === 'scheduled' ? 'open_at_iso' : 'close_at_iso')} ?? '') ?>', '<?= e($countdownType) ?>')"
                             x-init="init()">
                            <p class="text-xs uppercase tracking-[0.18em] text-amber-200/80 mb-4 flex items-center justify-center gap-2">
                                <?php if ($countdownType === 'scheduled'): ?>
                                    <?= mtq_icon('calendar', 'h-4 w-4') ?>
                                    <span>Pembukaan maqra dalam</span>
                                <?php else: ?>
                                    <?= mtq_icon('zap', 'h-4 w-4') ?>
                                    <span>Sisa waktu pengambilan</span>
                                <?php endif; ?>
                            </p>
                            <div class="flex items-center justify-center gap-1 sm:gap-2" x-show="!isExpired">
                                <template x-if="days">
                                    <div class="flex flex-col items-center">
                                        <div class="rounded-xl bg-amber-500/20 px-3 py-2 sm:px-4 sm:py-3">
                                            <span class="text-2xl sm:text-3xl font-black text-amber-50 tabular-nums" x-text="days.replace('h ', '')"></span>
                                        </div>
                                        <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-amber-300/70">hari</span>
                                    </div>
                                </template>
                                <template x-if="days">
                                    <span class="text-2xl font-bold text-amber-300/50">:</span>
                                </template>
                                <div class="flex flex-col items-center">
                                    <div class="rounded-xl bg-amber-500/20 px-3 py-2 sm:px-4 sm:py-3">
                                        <span class="text-3xl sm:text-4xl font-black text-amber-50 tabular-nums" x-text="hours"></span>
                                    </div>
                                    <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-amber-300/70">jam</span>
                                </div>
                                <span class="text-3xl font-bold text-amber-300/50">:</span>
                                <div class="flex flex-col items-center">
                                    <div class="rounded-xl bg-amber-500/20 px-3 py-2 sm:px-4 sm:py-3">
                                        <span class="text-3xl sm:text-4xl font-black text-amber-50 tabular-nums" x-text="minutes"></span>
                                    </div>
                                    <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-amber-300/70">menit</span>
                                </div>
                                <span class="text-3xl font-bold text-amber-300/50">:</span>
                                <div class="flex flex-col items-center">
                                    <div class="rounded-xl bg-amber-500/20 px-3 py-2 sm:px-4 sm:py-3">
                                        <span class="text-3xl sm:text-4xl font-black text-amber-50 tabular-nums" x-text="seconds"></span>
                                    </div>
                                    <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-amber-300/70">detik</span>
                                </div>
                            </div>
                            <div x-show="isExpired" class="flex items-center justify-center gap-2">
                                <span class="text-2xl font-bold text-emerald-300" x-text="label"></span>
                                <?= mtq_icon('check-circle', 'h-6 w-6 text-emerald-300') ?>
                            </div>
                            <p class="mt-4 text-m font-semibold text-amber-300/70">Waktu Indonesia Barat</p>
                        </div>
                        <?php endif; ?>

                        <?php if ($catStatus === 'scheduled'): ?>
                            <?php // Saat terjadwal: TIDAK tampilkan list peserta ?>
                        <?php elseif ($categoryParticipants->isEmpty()): ?>
                            <div class="mt-6 rounded-[1.5rem] border border-dashed border-slate-700 bg-slate-950/50 p-6 text-sm text-slate-400">
                                Belum ada peserta terverifikasi pada golongan ini.
                            </div>
                        <?php elseif ($isDistrictMaqraCategory): ?>
                            <div class="mt-6 grid gap-4 xl:grid-cols-2">
                                <?php foreach ($districtGroups as $districtIndex => $districtParticipants): ?>
                                    <?php
                                        $districtParticipants = $districtParticipants->values();
                                        $districtName = (string) ($districtParticipants->first()?->district?->name ?? 'Tanpa Kecamatan');
                                        $leaderParticipant = $districtParticipants->first();
                                        $districtMaqraParticipant = $districtParticipants->first(fn ($participant) => filled($participant->latestMaqraDraw?->maqraPackage?->maqra_code ?? null));
                                        $districtMaqraLabel = (string) ($districtMaqraParticipant?->latestMaqraDraw?->maqraPackage?->title ?? '');
                                        $districtMaqraLabel = $districtMaqraLabel !== '' ? (str_starts_with($districtMaqraLabel, 'QS') ? $districtMaqraLabel : 'QS '.$districtMaqraLabel) : '';
                                        $districtCompleted = filled($districtMaqraLabel);
                                        $districtRound = (string) ($districtMaqraParticipant?->latestMaqraDraw?->round_label ?? 'Penyisihan');
                                        $districtAccent = $districtIndex % 2 === 0
                                            ? 'border-fuchsia-400/14 bg-slate-950/50'
                                            : 'border-violet-400/14 bg-slate-950/55';
                                        $districtHeaderAccent = $districtIndex % 2 === 0
                                            ? 'from-fuchsia-400/30 via-pink-400/20 to-transparent'
                                            : 'from-violet-400/30 via-fuchsia-400/20 to-transparent';
                                        $lotLaunchUrl = $leaderParticipant
                                            ? route('participants.maqra.draw', $leaderParticipant).'?autofullscreen=1&round='.$roundLabel
                                            : null;
                                    ?>
                                    <div
                                        class="relative overflow-hidden rounded-[1.5rem] border <?= $districtCompleted ? 'border-emerald-400/30 bg-emerald-400/5' : $districtAccent ?> p-5"
                                        data-district-team-card
                                        data-district-id="<?= e((string) $districtParticipants->first()?->district_id) ?>"
                                        data-district-completed="<?= $districtCompleted ? '1' : '0' ?>"
                                    >
                                        <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r <?= $districtCompleted ? 'from-emerald-400/80 via-emerald-300/60 to-transparent' : $districtHeaderAccent ?>"></div>
                                        <div class="mb-4 flex items-center justify-between gap-3">
                                            <span class="inline-flex rounded-full border <?= $districtCompleted ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-fuchsia-300/20 bg-fuchsia-400/10 text-fuchsia-100' ?> px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">
                                                Kecamatan #<?= e(str_pad((string) ($districtIndex + 1), 2, '0', STR_PAD_LEFT)) ?>
                                            </span>
                                            <span class="inline-flex rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300">
                                                <?= e($districtParticipants->count()) ?> peserta tim
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="section-kicker">Kecamatan</p>
                                                <h4 class="mt-2 text-xl font-bold <?= $districtCompleted ? 'text-emerald-100' : 'text-white' ?>"><?= e($districtName) ?></h4>
                                                <p class="mt-2 text-sm text-slate-300"><?= e(app(\App\Http\Controllers\PageController::class)->categoryMaqraRuleLabel($category)) ?></p>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <?php if ($leaderParticipant && $leaderParticipant->verification_status === 'verified' && in_array($user?->role, ['admin', 'official', 'pendamping'], true)): ?>
                                                    <a href="<?= e($lotLaunchUrl) ?>" data-maqra-launcher class="secondary-button rounded-xl border-fuchsia-300/30 bg-fuchsia-400/10 px-3 py-2 text-[11px] text-fuchsia-100 hover:border-fuchsia-200/50">
                                                        <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                        Ambil Maqra
                                                    </a>
                                                <?php endif; ?>
                                                <span data-district-lot-status class="status-pill <?= $districtCompleted ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-fuchsia-400/20 bg-fuchsia-400/10 text-fuchsia-100' ?>">
                                                    <?= $districtCompleted ? '1 maqra aktif' : '1 maqra' ?>
                                                </span>
                                                <span data-district-lot-badge class="inline-flex rounded-full border <?= $districtCompleted ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-amber-400/20 bg-amber-400/10 text-amber-100' ?> px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">
                                                    <?= $districtCompleted ? 'Maqra sudah diambil' : 'Belum diambil' ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-4 space-y-2">
                                            <?php foreach ($districtParticipants->take(3) as $participant): ?>
                                                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3" data-district-member-card data-participant-row data-participant-id="<?= e((string) $participant->id) ?>" data-category-id="<?= e((string) $category->id) ?>" data-district-id="<?= e((string) ($participant->district_id ?? '')) ?>">
                                                    <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-white"><?= e($participant->name) ?></p>
                                                <p class="mt-1 text-xs text-slate-400"><?= e($participant->lot_number ?? '-') ?></p>
                                                <?php if ($districtCompleted): ?>
                                                    <p data-participant-maqra-label class="mt-2 text-xs font-semibold text-fuchsia-200"><?= e($districtMaqraLabel) ?></p>
                                                <?php endif; ?>
                                            </div>
                                                        <span data-participant-lot-status class="inline-flex rounded-full border <?= $districtCompleted ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : ($remainingPackages <= 0 ? 'border-rose-400/20 bg-rose-400/10 text-rose-100' : 'border-slate-700 bg-slate-950/70 text-slate-300') ?> px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em]">
                                                            <?= $districtCompleted ? 'Sudah' : ($remainingPackages <= 0 ? 'Habis' : 'Belum') ?>
                                                        </span>
                                                    </div>
                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                        <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button rounded-xl px-3 py-2 text-[11px]">
                                                            Detail Peserta
                                                        </a>
                                                        <?php if (! $districtCompleted && $remainingPackages > 0 && in_array($user?->role, ['admin', 'official', 'pendamping'], true)): ?>
                                                            <a href="<?= e($lotLaunchUrl) ?>" data-maqra-launcher class="secondary-button rounded-xl border-fuchsia-300/30 bg-fuchsia-400/10 px-3 py-2 text-[11px] text-fuchsia-100 hover:border-fuchsia-200/50">
                                                                Ambil Maqra
                                                            </a>
                                                        <?php elseif (! $districtCompleted && $remainingPackages <= 0): ?>
                                                            <span class="inline-flex rounded-xl border border-rose-400/20 bg-rose-400/10 px-3 py-2 text-[11px] font-semibold text-rose-100">
                                                                Stok Habis
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="mt-6 overflow-hidden rounded-[1.75rem] border border-fuchsia-400/14 bg-slate-950/50">
                                <table class="min-w-full">
                                    <thead class="table-head">
                                        <tr>
                                            <th class="px-5 py-4 text-left">Nama</th>
                                            <th class="px-5 py-4 text-left">Nomor Lot</th>
                                            <th class="px-5 py-4 text-left">Kecamatan</th>
                                            <th class="px-5 py-4 text-left">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categoryParticipants as $participant): ?>
                                            <tr class="border-t border-slate-800/80" data-participant-row data-participant-id="<?= e((string) $participant->id) ?>" data-category-id="<?= e((string) $category->id) ?>" data-district-id="<?= e((string) ($participant->district_id ?? '')) ?>">
                                                <td class="px-5 py-4">
                                                    <div class="break-normal font-semibold leading-snug text-white"><?= e($participant->name) ?></div>
                                                    <?php if (filled($participant->latestMaqraDraw?->maqraPackage?->maqra_code ?? null)): ?>
                                                        <?php
                                                            $maqraLabel = trim((string) preg_replace('/^(Tilawah|Tahfizh|Tafsir|Fahmil)\s*-\s*/u', '', (string) ($participant->latestMaqraDraw?->maqraPackage?->title ?? '')));
                                                            $maqraLabel = $maqraLabel !== '' ? (str_starts_with($maqraLabel, 'QS') ? $maqraLabel : 'QS '.$maqraLabel) : '-';
                                                            $maqraRound = (string) ($participant->latestMaqraDraw?->round_label ?? 'Penyisihan');
                                                        ?>
                                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                                            <div class="inline-flex rounded-full border border-fuchsia-300/20 bg-fuchsia-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-fuchsia-100" data-maqra-chip>
                                                                <?= e($maqraLabel) ?>
                                                            </div>
                                                            <span class="inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100" data-maqra-status-chip>
                                                                Sudah diambil
                                                            </span>
                                                        </div>
                                                        <div class="mt-1 text-[11px] text-fuchsia-100/90" data-maqra-round-label>
                                                            <?= e($maqraRound) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mt-2 inline-flex rounded-full border <?= $remainingPackages <= 0 ? 'border-rose-400/20 bg-rose-400/10 text-rose-100' : 'border-slate-700 bg-slate-950/70 text-slate-300' ?> px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]" data-maqra-status-chip>
                                                            <?= $remainingPackages <= 0 ? 'Stok Habis' : 'Belum diambil' ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-5 py-4 text-slate-300"><?= e($participant->lot_number ?? '-') ?></td>
                                                <td class="px-5 py-4 text-slate-300"><?= e($participant->district?->name ?? '-') ?></td>
                                                <td class="px-5 py-4">
                                                    <div class="flex flex-wrap gap-2">
                                                        <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button rounded-xl px-3 py-2 text-[11px]">Detail</a>
                                                        <?php if (! filled($participant->latestMaqraDraw?->maqraPackage?->maqra_code ?? null)): ?>
                                                            <?php if ($remainingPackages > 0): ?>
                                                                <a href="<?= e(route('participants.maqra.draw', $participant).'?autofullscreen=1&round='.$roundLabel) ?>" data-maqra-launcher class="secondary-button rounded-xl border-fuchsia-300/30 bg-fuchsia-400/10 px-3 py-2 text-[11px] text-fuchsia-100 hover:border-fuchsia-200/50">Ambil Maqra</a>
                                                            <?php else: ?>
                                                                <span class="inline-flex rounded-xl border border-rose-400/20 bg-rose-400/10 px-3 py-2 text-[11px] font-semibold text-rose-100">Stok Habis</span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <?php foreach (($assets['js'] ?? []) as $src): ?>
        <script src="<?= e($src) ?>" defer></script>
    <?php endforeach; ?>
    <script>
        (function () {
            window.flashMaqraSection = function (section) {
                if (!section) {
                    return;
                }

                const originalClassName = section.className;
                section.classList.add('ring-2', 'ring-fuchsia-300/70', 'bg-fuchsia-400/5');

                window.setTimeout(() => {
                    section.className = originalClassName;
                }, 1200);
            };

            const launchers = document.querySelectorAll('[data-maqra-launcher]');
            launchers.forEach((launcher) => {
                launcher.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = launcher.getAttribute('href');
                    if (!url) {
                        return;
                    }

                    const popup = window.open(
                        url,
                        'mtq-maqra-draw',
                        `popup=yes,width=${screen.availWidth},height=${screen.availHeight},left=0,top=0,noopener=no`
                    );

                    if (popup) {
                        try {
                            popup.moveTo(0, 0);
                            popup.resizeTo(screen.availWidth, screen.availHeight);
                            popup.focus();
                        } catch (error) {
                            popup.focus();
                        }
                    } else {
                        window.location.href = url;
                    }
                });
            });

            function flashElement(element) {
                if (!element) {
                    return;
                }

                const originalClassName = element.className;
                element.classList.add('ring-2', 'ring-fuchsia-300/80', 'bg-fuchsia-400/5');
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });

                window.setTimeout(() => {
                    element.className = originalClassName;
                }, 1400);
            }

            function formatMaqraLabel(label) {
                const cleaned = String(label || '')
                    .replace(/^(Tilawah|Tahfizh|Tafsir|Fahmil)\s*-\s*/i, '')
                    .trim();

                if (!cleaned) {
                    return 'Paket Maqra';
                }

                return /^QS\b/i.test(cleaned) ? cleaned : `QS ${cleaned}`;
            }

            function updateSingleRow(row, label, roundLabel) {
                const statusChip = row.querySelector('[data-maqra-status-chip]');
                if (statusChip) {
                    statusChip.textContent = 'Sudah diambil';
                    statusChip.className = 'inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100';
                }

                const maqraChip = row.querySelector('[data-maqra-chip]');
                if (maqraChip) {
                    maqraChip.textContent = label;
                } else {
                    const nameCell = row.children?.[0];
                    if (nameCell) {
                        const chip = document.createElement('div');
                        chip.className = 'mt-2 inline-flex rounded-full border border-fuchsia-300/20 bg-fuchsia-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-fuchsia-100';
                        chip.setAttribute('data-maqra-chip', '');
                        chip.textContent = label;
                        nameCell.appendChild(chip);
                    }
                }

                const roundLine = row.querySelector('[data-maqra-round-label]');
                if (roundLine) {
                    roundLine.textContent = roundLabel;
                } else {
                    const nameCell = row.children?.[0];
                    if (nameCell) {
                        const line = document.createElement('div');
                        line.className = 'mt-1 text-[11px] text-fuchsia-100/90';
                        line.setAttribute('data-maqra-round-label', '');
                        line.textContent = roundLabel;
                        const chip = row.querySelector('[data-maqra-chip]');
                        if (chip) {
                            chip.insertAdjacentElement('afterend', line);
                        } else {
                            nameCell.appendChild(line);
                        }
                    }
                }
            }

            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin) {
                    return;
                }

                const payload = event.data;
                if (!payload || payload.type !== 'participant.maqra.updated' || !payload.participant_id) {
                    return;
                }

                const label = payload.maqra_label || formatMaqraLabel(payload.maqra_title || payload.maqra_code || 'Paket Maqra');
                const roundLabel = payload.maqra_round_label || payload.maqra_round || 'Penyisihan';
                const rows = payload.district_shared && payload.district_id && payload.category_id
                    ? document.querySelectorAll(`[data-participant-row][data-category-id="${payload.category_id}"][data-district-id="${payload.district_id}"]`)
                    : document.querySelectorAll(`[data-participant-row][data-participant-id="${payload.participant_id}"]`);

                if (!rows.length) {
                    return;
                }

                rows.forEach((row) => {
                    updateSingleRow(row, label, roundLabel);
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    flashElement(row);
                });

                const teamCard = payload.district_shared && payload.district_id
                    ? document.querySelector(`[data-district-team-card][data-district-id="${payload.district_id}"]`)
                    : null;

                if (teamCard) {
                    const statusChip = teamCard.querySelector('[data-district-lot-status]');
                    if (statusChip) {
                        statusChip.textContent = '1 maqra aktif';
                        statusChip.className = 'status-pill border-emerald-400/20 bg-emerald-400/10 text-emerald-100';
                    }

                    const badge = teamCard.querySelector('[data-district-lot-badge]');
                    if (badge) {
                        badge.textContent = 'Maqra sudah diambil';
                        badge.className = 'inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100';
                    }

                    teamCard.querySelectorAll('[data-participant-lot-status]').forEach((chip) => {
                        chip.textContent = 'Sudah';
                        chip.className = 'inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-100';
                    });

                    teamCard.querySelectorAll('[data-participant-maqra-label]').forEach((labelNode) => {
                        labelNode.textContent = label;
                    });

                    teamCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    flashElement(teamCard);
                }
            });
        })();
    </script>
</body>
</html>
