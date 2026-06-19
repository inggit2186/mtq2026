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
$msqRemainingTitlesByDistrict = $msqRemainingTitlesByDistrict ?? collect();
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

        <div class="grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)]">
            <!-- Sidebar -->
            <aside
                class="console-sidebar fixed inset-y-4 left-4 z-50 w-[300px] rounded-[1.75rem] p-5 transition-all duration-300 lg:static lg:inset-auto lg:block"
                :class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="brand-logo brand-logo--maqra">
                            <?= mtq_icon('sparkles', 'h-6 w-6') ?>
                        </div>
                        <div>
                            <p class="brand-subtitle">e-MTQ Console</p>
                            <h1 class="brand-title">Pengambilan Maqra</h1>
                        </div>
                    </div>
                    <button type="button" class="sidebar-close-btn lg:hidden" @click="mobileNavOpen = false">
                        <?= mtq_icon('x', 'h-5 w-5') ?>
                    </button>
                </div>

                <div class="maqra-summary-card">
                    <div class="maqra-summary-icon">
                        <?= mtq_icon('users', 'h-6 w-6') ?>
                    </div>
                    <div class="maqra-summary-content">
                        <p class="maqra-summary-value"><?= e($summaryStats['participant_total']) ?></p>
                        <p class="maqra-summary-label">Peserta Terverifikasi</p>
                    </div>
                </div>

                <nav class="sidebar-nav" aria-label="Navigasi utama">
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="sidebar-footer">
                    <form method="POST" action="<?= e(route('logout')) ?>">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="logout-btn">
                            <?= mtq_icon('logout', 'h-4 w-4') ?>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="min-w-0 space-y-6">

                <!-- Hero Header -->
                <section class="maqra-hero">
                    <div class="maqra-hero-bg"></div>
                    <div class="maqra-hero-content">
                        <div class="flex items-center gap-4">
                            <div class="maqra-hero-icon">
                                <?= mtq_icon('sparkles', 'h-7 w-7') ?>
                            </div>
                            <div>
                                <p class="maqra-hero-greeting">Pengambilan Maqra</p>
                                <h1 class="maqra-hero-title"><?= e($scopeLabel) ?></h1>
                                <p class="maqra-hero-subtitle"><?= e($rolePanel['headline'] ?? 'Menu pemilihan peserta maqra') ?></p>
                            </div>
                        </div>
                        <div class="maqra-hero-badge <?= $selectedMaqraStatusLabel === 'Dibuka' ? 'maqra-hero-badge--active' : 'maqra-hero-badge--closed' ?>">
                            <?= $selectedMaqraStatusLabel === 'Dibuka' ? mtq_icon('zap', 'h-4 w-4') : mtq_icon('lock', 'h-4 w-4') ?>
                            <span><?= e($selectedMaqraStatusLabel) ?></span>
                        </div>
                    </div>
                </section>

                <!-- PROMINENT ROUND TABS -->
                <section class="maqra-round-tabs">
                    <?php
                    $maqraRounds = $maqraActiveSchedules ? $maqraActiveSchedules->groupBy(fn ($s) => $s->round?->name ?? 'Lainnya') : collect();
                    foreach (['Penyisihan', 'Final'] as $roundOption):
                        $roundHasSchedule = $maqraRounds->has($roundOption);
                        $isActive = $roundLabel === $roundOption;
                    ?>
                        <a href="<?= e(route('participants.maqra.menu', ['round' => $roundOption])) ?>"
                           class="maqra-round-tab <?= $isActive ? 'maqra-round-tab--active' : '' ?> <?= !$roundHasSchedule ? 'maqra-round-tab--disabled' : '' ?>">
                            <div class="maqra-round-tab-icon">
                                <?php if ($roundOption === 'Penyisihan'): ?>
                                    <?= mtq_icon('layers', 'h-6 w-6') ?>
                                <?php else: ?>
                                    <?= mtq_icon('crown', 'h-6 w-6') ?>
                                <?php endif; ?>
                            </div>
                            <div class="maqra-round-tab-content">
                                <p class="maqra-round-tab-title"><?= e($roundOption) ?></p>
                                <p class="maqra-round-tab-status">
                                    <?php if ($roundHasSchedule): ?>
                                        <?= mtq_icon('check-circle', 'h-3 w-3') ?>
                                        <span>Buka</span>
                                    <?php else: ?>
                                        <?= mtq_icon('x', 'h-3 w-3') ?>
                                        <span>Tidak aktif</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($isActive): ?>
                                <div class="maqra-round-tab-indicator"></div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </section>

                <!-- Quick Stats -->
                <section class="maqra-stats-grid">
                    <div class="maqra-stat-card">
                        <div class="maqra-stat-icon">
                            <?= mtq_icon('book-open', 'h-5 w-5') ?>
                        </div>
                        <div class="maqra-stat-content">
                            <p class="maqra-stat-value"><?= e($summaryStats['category_total']) ?></p>
                            <p class="maqra-stat-label">Golongan</p>
                        </div>
                    </div>
                    <div class="maqra-stat-card">
                        <div class="maqra-stat-icon">
                            <?= mtq_icon('users', 'h-5 w-5') ?>
                        </div>
                        <div class="maqra-stat-content">
                            <p class="maqra-stat-value"><?= e($summaryStats['participant_total']) ?></p>
                            <p class="maqra-stat-label">Peserta</p>
                        </div>
                    </div>
                    <div class="maqra-stat-card maqra-stat-card--accent">
                        <div class="maqra-stat-icon">
                            <?= mtq_icon('spark', 'h-5 w-5') ?>
                        </div>
                        <div class="maqra-stat-content">
                            <p class="maqra-stat-value"><?= e($summaryStats['maqra_total']) ?></p>
                            <p class="maqra-stat-label">Maqra Tersedia</p>
                        </div>
                    </div>
                </section>

                <!-- Status Cards -->
                <section class="maqra-status-grid">
                    <div class="maqra-status-card <?= $selectedMaqraStatusLabel === 'Dibuka' ? 'maqra-status-card--active' : 'maqra-status-card--inactive' ?>">
                        <div class="maqra-status-icon">
                            <?= $selectedMaqraStatusLabel === 'Dibuka' ? mtq_icon('zap', 'h-5 w-5') : mtq_icon('lock', 'h-5 w-5') ?>
                        </div>
                        <div>
                            <p class="maqra-status-label">Status Pembukaan</p>
                            <p class="maqra-status-value"><?= e($selectedMaqraStatusLabel) ?></p>
                        </div>
                    </div>
                    <div class="maqra-status-card maqra-status-card--category">
                        <div class="maqra-status-icon">
                            <?= mtq_icon('layers', 'h-5 w-5') ?>
                        </div>
                        <div>
                            <p class="maqra-status-label">Golongan Aktif</p>
                            <p class="maqra-status-value"><?= e($selectedMaqraCategoryLabel) ?></p>
                        </div>
                    </div>
                    <div class="maqra-status-card maqra-status-card--lot">
                        <div class="maqra-status-icon">
                            <?= mtq_icon('hash', 'h-5 w-5') ?>
                        </div>
                        <div>
                            <p class="maqra-status-label">Nomor Lot</p>
                            <p class="maqra-status-value"><?= e($selectedMaqraLotRangeLabel) ?></p>
                        </div>
                    </div>
                </section>

                <!-- Category Cards -->
                <?php
                $visibleCategories = $categories->filter(function ($category) use ($categoryScheduleData) {
                    $data = $categoryScheduleData[$category->id] ?? null;
                    return $data && in_array($data['status'], ['active', 'scheduled']);
                });
                ?>

                <?php if ($visibleCategories->isNotEmpty()): ?>
                    <section class="maqra-section">
                        <div class="maqra-section-header">
                            <?= mtq_icon('grid', 'h-5 w-5') ?>
                            <?php $hasActiveCategory = $visibleCategories->contains(fn ($c) => ($categoryScheduleData[$c->id]['status'] ?? '') === 'active'); ?>
                            <h2 class="maqra-section-title">Golongan <?= $hasActiveCategory ? 'Sedang Buka' : 'Terjadwal' ?></h2>
                            <span class="maqra-section-badge"><?= e($visibleCategories->count()) ?></span>
                        </div>
                        <div class="maqra-category-grid">
                            <?php foreach ($visibleCategories as $category): ?>
                                <?php
                                    $categoryParticipants = $participantsByCategory->get($category->id, collect());
                                    $scheduleData = $categoryScheduleData[$category->id] ?? null;
                                    $currentSchedule = $scheduleData['current'] ?? null;
                                    $upcomingSchedule = $scheduleData['upcoming'] ?? null;
                                    $status = $scheduleData['status'] ?? 'closed';
                                    $scheduleToShow = $currentSchedule ?? $upcomingSchedule;
                                ?>
                                <a
                                    href="#golongan-<?= e((string) $category->id) ?>"
                                    @click.prevent="$nextTick(() => { activeCategoryId = '<?= e((string) $category->id) ?>'; const section = document.getElementById('golongan-<?= e((string) $category->id) ?>'); if(section){section.scrollIntoView({ behavior: 'smooth', block: 'start' }); if(window.flashMaqraSection) window.flashMaqraSection(section); } })"
                                    class="maqra-category-card <?= $status === 'active' ? 'maqra-category-card--active' : 'maqra-category-card--scheduled' ?>"
                                    :class="{ 'ring-2 ring-fuchsia-400 ring-offset-2 ring-offset-slate-950': activeCategoryId === '<?= e((string) $category->id) ?>' }"
                                >
                                    <div class="maqra-category-header">
                                        <div class="maqra-category-info">
                                            <p class="maqra-category-branch"><?= e((string) $category->branch) ?></p>
                                            <h3 class="maqra-category-name"><?= e((string) $category->name) ?></h3>
                                        </div>
                                        <span class="maqra-category-status <?= $status === 'active' ? 'maqra-category-status--active' : 'maqra-category-status--scheduled' ?>">
                                            <?php if ($status === 'active'): ?>
                                                <?= mtq_icon('zap', 'h-3 w-3') ?>
                                            <?php else: ?>
                                                <?= mtq_icon('clock', 'h-3 w-3') ?>
                                            <?php endif; ?>
                                            <span><?= $status === 'active' ? 'Buka' : 'Terjadwal' ?></span>
                                        </span>
                                    </div>
                                    <div class="maqra-category-footer">
                                        <div class="maqra-category-stat">
                                            <?= mtq_icon('users', 'h-4 w-4') ?>
                                            <span><?= e($categoryParticipants->count()) ?> peserta</span>
                                        </div>
                                        <?php if ($scheduleToShow): ?>
                                            <div class="maqra-category-lot">
                                                Lot <?= e($scheduleToShow->lot_min) ?>-<?= e($scheduleToShow->lot_max) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <div class="maqra-empty">
                        <?= mtq_icon('calendar', 'h-12 w-12') ?>
                        <p>Belum ada jadwal maqra yang aktif atau terjadwal</p>
                    </div>
                <?php endif; ?>

                <!-- Category Sections with Participants -->
                <?php foreach ($categories as $category): ?>
                    <?php
                        $categoryParticipants = $participantsByCategory->get($category->id, collect())->values();
                        $isDistrictMaqraCategory = app(\App\Http\Controllers\PageController::class)->categoryMaqraUsesDistrictSharing($category);
                        $isActiveCategory = (int) ($selectedCategory?->id ?? 0) === (int) $category->id;
                        $remainingPackages = (int) ($maqraRemainingPackagesByCategory[$category->id] ?? 0);
                        $categoryStockEmpty = $remainingPackages <= 0 && $categoryParticipants->isNotEmpty();
                        $catScheduleData = $categoryScheduleData[$category->id] ?? null;
                        $catCurrentSchedule = $catScheduleData['current'] ?? null;
                        $catUpcomingSchedule = $catScheduleData['upcoming'] ?? null;
                        $catStatus = $catScheduleData['status'] ?? 'closed';
                        $countdownTarget = $catStatus === 'scheduled' ? $catUpcomingSchedule : $catCurrentSchedule;
                        $countdownType = $catStatus === 'scheduled' ? 'scheduled' : 'active';
                        $districtGroups = $isDistrictMaqraCategory
                            ? $categoryParticipants->groupBy(fn ($participant) => (int) ($participant->district_id ?? 0))->sortBy(fn ($group) => (string) ($group->first()?->district?->name ?? ''))
                            : collect();
                    ?>
                    <section
                        id="golongan-<?= e($category->id) ?>"
                        data-category-section
                        data-category-id="<?= e((string) $category->id) ?>"
                        class="maqra-category-section"
                        x-data
                        x-cloak
                        x-show="activeCategoryId === '<?= e((string) $category->id) ?>'"
                    >
                        <div class="maqra-category-section-header">
                            <div class="maqra-category-section-info">
                                <p class="maqra-category-section-branch"><?= e($category->branch) ?></p>
                                <h2 class="maqra-category-section-name"><?= e($category->name) ?></h2>
                                <p class="maqra-category-section-rule"><?= e(app(\App\Http\Controllers\PageController::class)->categoryMaqraRuleLabel($category)) ?></p>
                            </div>
                            <div class="maqra-category-section-stats">
                                <span class="maqra-category-section-badge">
                                    <?= mtq_icon('users', 'h-4 w-4') ?>
                                    <?= e($categoryParticipants->count()) ?> Peserta
                                </span>
                                <?php if ($categoryStockEmpty): ?>
                                    <span class="maqra-category-section-badge maqra-category-section-badge--warning">
                                        <?= mtq_icon('alert-triangle', 'h-4 w-4') ?>
                                        Stok Habis
                                    </span>
                                <?php elseif ($isDistrictMaqraCategory): ?>
                                    <span class="maqra-category-section-badge">
                                        <?= mtq_icon('check', 'h-4 w-4') ?>
                                        1 Kecamatan = 1 Maqra
                                    </span>
                                <?php else: ?>
                                    <span class="maqra-category-section-badge">
                                        <?= mtq_icon('check', 'h-4 w-4') ?>
                                        1 Peserta = 1 Maqra
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($countdownTarget): ?>
                        <div class="maqra-countdown"
                             x-data="maqraCountdown('<?= e($countdownTarget->{($countdownType === 'scheduled' ? 'open_at_iso' : 'close_at_iso')} ?? '') ?>', '<?= e($countdownType) ?>')"
                             x-init="init()">
                            <div class="maqra-countdown-header">
                                <?= $countdownType === 'scheduled' ? mtq_icon('calendar', 'h-4 w-4') : mtq_icon('zap', 'h-4 w-4') ?>
                                <span><?= $countdownType === 'scheduled' ? 'Pembukaan dalam' : 'Sisa waktu' ?></span>
                            </div>
                            <div class="maqra-countdown-display" x-show="!isExpired">
                                <template x-if="days">
                                    <div class="maqra-countdown-unit">
                                        <span class="maqra-countdown-value" x-text="days.replace('h ', '')"></span>
                                        <span class="maqra-countdown-label">Hari</span>
                                    </div>
                                </template>
                                <span class="maqra-countdown-sep">:</span>
                                <div class="maqra-countdown-unit">
                                    <span class="maqra-countdown-value" x-text="hours"></span>
                                    <span class="maqra-countdown-label">Jam</span>
                                </div>
                                <span class="maqra-countdown-sep">:</span>
                                <div class="maqra-countdown-unit">
                                    <span class="maqra-countdown-value" x-text="minutes"></span>
                                    <span class="maqra-countdown-label">Menit</span>
                                </div>
                                <span class="maqra-countdown-sep">:</span>
                                <div class="maqra-countdown-unit">
                                    <span class="maqra-countdown-value" x-text="seconds"></span>
                                    <span class="maqra-countdown-label">Detik</span>
                                </div>
                            </div>
                            <div class="maqra-countdown-expired" x-show="isExpired" x-cloak>
                                <span x-text="label"></span>
                                <?= mtq_icon('check-circle', 'h-5 w-5') ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($catStatus === 'scheduled'): ?>
                            <!-- Scheduled - no participant list -->
                        <?php elseif ($categoryParticipants->isEmpty()): ?>
                            <div class="maqra-empty">
                                <?= mtq_icon('users', 'h-10 w-10') ?>
                                <p>Belum ada peserta terverifikasi pada golongan ini</p>
                            </div>
                        <?php elseif ($isDistrictMaqraCategory): ?>
                            <!-- District-based view -->
                            <div class="maqra-district-grid">
                                <?php foreach ($districtGroups as $districtIndex => $districtParticipants): ?>
                                    <?php
                                        $districtParticipants = $districtParticipants->values();
                                        $districtName = (string) ($districtParticipants->first()?->district?->name ?? 'Tanpa Kecamatan');
                                        $districtId = (int) ($districtParticipants->first()?->district_id ?? 0);
                                        $firstParticipant = $districtParticipants->first();
                                        $leaderParticipant = $firstParticipant;
                                        $gender = $firstParticipant?->gender ?? 'putra';
                                        $isMsqCategory = app(\App\Http\Controllers\PageController::class)->categoryUsesDistrictMaqraTitles($category);
                                        $msqKey = "{$category->id}_{$districtId}_{$gender}";
                                        $msqRemainingForDistrict = $isMsqCategory ? (int) ($msqRemainingTitlesByDistrict[$msqKey] ?? 0) : $remainingPackages;

                                        // Get maqra label - use msqDistrictTitle for MSQ categories, maqraPackage for others
                                        $districtMaqraLabel = '';
                                        $districtCompleted = false;
                                        if ($isMsqCategory) {
                                            // MSQ: check from msqDistrictTitle
                                            $districtMaqraParticipant = $districtParticipants->first(fn ($p) => filled($p->latestMaqraDraw?->msqDistrictTitle?->title ?? null));
                                            $districtMaqraLabel = (string) ($districtMaqraParticipant?->latestMaqraDraw?->msqDistrictTitle?->title ?? '');
                                            $districtCompleted = filled($districtMaqraLabel);
                                        } else {
                                            // Non-MSQ: check from maqraPackage
                                            $districtMaqraParticipant = $districtParticipants->first(fn ($participant) => filled($participant->latestMaqraDraw?->maqraPackage?->maqra_code ?? null));
                                            $rawLabel = (string) ($districtMaqraParticipant?->latestMaqraDraw?->maqraPackage?->title ?? '');
                                            $districtMaqraLabel = $rawLabel !== '' ? (str_starts_with($rawLabel, 'QS') ? $rawLabel : 'QS '.$rawLabel) : '';
                                            $districtCompleted = filled($districtMaqraLabel);
                                        }
                                        $lotLaunchUrl = $leaderParticipant ? route('participants.maqra.draw', $leaderParticipant).'?autofullscreen=1&round='.$roundLabel : null;
                                    ?>
                                    <div class="maqra-district-card <?= $districtCompleted ? 'maqra-district-card--completed' : '' ?>">
                                        <div class="maqra-district-header">
                                            <span class="maqra-district-badge">Kecamatan #<?= e(str_pad((string) ($districtIndex + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                                            <span class="maqra-district-count"><?= e($districtParticipants->count()) ?> peserta</span>
                                        </div>
                                        <div class="maqra-district-content">
                                            <h3 class="maqra-district-name"><?= e($districtName) ?></h3>
                                            <?php if ($districtCompleted): ?>
                                                <p class="maqra-district-maqra">
                                                    <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                    <?= e($districtMaqraLabel) ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="maqra-district-status">
                                                    <?= mtq_icon('clock', 'h-4 w-4') ?>
                                                    <?= $msqRemainingForDistrict <= 0 ? 'Stok habis' : 'Belum diambil' ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="maqra-district-actions">
                                            <?php if ($leaderParticipant && $leaderParticipant->verification_status === 'verified' && in_array($user?->role, ['admin', 'official', 'pendamping'], true)): ?>
                                                <a href="<?= e($lotLaunchUrl) ?>" data-maqra-launcher class="maqra-btn maqra-btn--primary">
                                                    <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                    Ambil Maqra
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= e(route('participants.show', $districtParticipants->first())) ?>" class="maqra-btn maqra-btn--secondary">
                                                Detail
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Mobile-friendly Table View -->
                            <div class="maqra-table-wrapper">
                                <table class="maqra-table">
                                    <thead>
                                        <tr>
                                            <th class="maqra-th">Peserta</th>
                                            <th class="maqra-th maqra-th--center">Lot</th>
                                            <th class="maqra-th">Kecamatan</th>
                                            <th class="maqra-th maqra-th--center">Maqra</th>
                                            <th class="maqra-th maqra-th--actions">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categoryParticipants as $participant): ?>
                                            <?php
                                                $isMsqCategory = app(\App\Http\Controllers\PageController::class)->categoryUsesDistrictMaqraTitles($category);
                                                $districtId = (int) ($participant->district_id ?? 0);
                                                $gender = $participant->gender ?? 'putra';
                                                $msqKey = "{$category->id}_{$districtId}_{$gender}";
                                                $msqRemainingForDistrict = $isMsqCategory ? (int) ($msqRemainingTitlesByDistrict[$msqKey] ?? 0) : $remainingPackages;

                                                // Get maqra info - use msqDistrictTitle for MSQ categories
                                                if ($isMsqCategory) {
                                                    $hasMaqra = filled($participant->latestMaqraDraw?->msqDistrictTitle?->title ?? null);
                                                    $maqraTitle = (string) ($participant->latestMaqraDraw?->msqDistrictTitle?->title ?? '');
                                                } else {
                                                    $hasMaqra = filled($participant->latestMaqraDraw?->maqraPackage?->maqra_code ?? null);
                                                    $rawTitle = trim((string) preg_replace('/^(Tilawah|Tahfizh|Tafsir|Fahmil)\s*-\s*/u', '', (string) ($participant->latestMaqraDraw?->maqraPackage?->title ?? '')));
                                                    $maqraTitle = $rawTitle !== '' ? (str_starts_with($rawTitle, 'QS') ? $rawTitle : 'QS '.$rawTitle) : '';
                                                }
                                                $maqraRound = (string) ($participant->latestMaqraDraw?->round_label ?? 'Penyisihan');
                                                $currentRemaining = $isMsqCategory ? $msqRemainingForDistrict : $remainingPackages;
                                            ?>
                                            <tr class="maqra-tr" data-participant-row data-participant-id="<?= e((string) $participant->id) ?>" data-category-id="<?= e((string) $category->id) ?>">
                                                <td class="maqra-td maqra-td--name">
                                                    <p class="maqra-participant-name"><?= e($participant->name) ?></p>
                                                    <?php if ($hasMaqra): ?>
                                                        <div class="maqra-maqra-chips">
                                                            <span class="maqra-chip maqra-chip--success">
                                                                <?= mtq_icon('check', 'h-3 w-3') ?>
                                                                <?= e($maqraTitle) ?>
                                                            </span>
                                                            <span class="maqra-chip maqra-chip--round"><?= e($maqraRound) ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="maqra-chip <?= $currentRemaining <= 0 ? 'maqra-chip--warning' : 'maqra-chip--neutral' ?>">
                                                            <?= $currentRemaining <= 0 ? 'Stok Habis' : 'Belum Diambil' ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="maqra-td maqra-td--center">
                                                    <span class="maqra-lot-number"><?= e($participant->lot_number ?? '-') ?></span>
                                                </td>
                                                <td class="maqra-td">
                                                    <span class="maqra-district-name-text"><?= e($participant->district?->name ?? '-') ?></span>
                                                </td>
                                                <td class="maqra-td maqra-td--center">
                                                    <?php if ($hasMaqra): ?>
                                                        <span class="maqra-status-badge maqra-status-badge--success">
                                                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="maqra-status-badge <?= $currentRemaining <= 0 ? 'maqra-status-badge--warning' : 'maqra-status-badge--neutral' ?>">
                                                            <?= mtq_icon('x', 'h-4 w-4') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="maqra-td maqra-td--actions">
                                                    <div class="maqra-action-buttons">
                                                        <a href="<?= e(route('participants.show', $participant)) ?>" class="maqra-action-btn maqra-action-btn--secondary">
                                                            <?= mtq_icon('eye', 'h-4 w-4') ?>
                                                            <span>Detail</span>
                                                        </a>
                                                        <?php if (! $hasMaqra && $currentRemaining > 0 && in_array($user?->role, ['admin', 'official', 'pendamping'], true)): ?>
                                                            <a href="<?= e(route('participants.maqra.draw', $participant).'?autofullscreen=1&round='.$roundLabel) ?>" data-maqra-launcher class="maqra-action-btn maqra-action-btn--primary">
                                                                <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                                <span>Ambil Maqra</span>
                                                            </a>
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
                if (!section) return;
                const originalClassName = section.className;
                section.classList.add('ring-2', 'ring-fuchsia-300/70', 'bg-fuchsia-400/5');
                window.setTimeout(() => { section.className = originalClassName; }, 1200);
            };

            const launchers = document.querySelectorAll('[data-maqra-launcher]');
            launchers.forEach((launcher) => {
                launcher.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = launcher.getAttribute('href');
                    if (!url) return;
                    const popup = window.open(url, 'mtq-maqra-draw', `popup=yes,width=${screen.availWidth},height=${screen.availHeight},left=0,top=0,noopener=no`);
                    if (popup) {
                        try { popup.moveTo(0, 0); popup.resizeTo(screen.availWidth, screen.availHeight); popup.focus(); }
                        catch (error) { popup.focus(); }
                    } else { window.location.href = url; }
                });
            });

            function flashElement(element) {
                if (!element) return;
                const originalClassName = element.className;
                element.classList.add('ring-2', 'ring-fuchsia-300/80', 'bg-fuchsia-400/5');
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(() => { element.className = originalClassName; }, 1400);
            }

            function formatMaqraLabel(label) {
                const cleaned = String(label || '').replace(/^(Tilawah|Tahfizh|Tafsir|Fahmil)\s*-\s*/i, '').trim();
                if (!cleaned) return 'Paket Maqra';
                return /^QS\b/i.test(cleaned) ? cleaned : `QS ${cleaned}`;
            }

            function updateSingleRow(row, label, roundLabel) {
                const statusChip = row.querySelector('[data-maqra-status-chip]');
                if (statusChip) {
                    statusChip.textContent = 'Sudah diambil';
                    statusChip.className = 'inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100';
                }
                const maqraChip = row.querySelector('[data-maqra-chip]');
                if (maqraChip) { maqraChip.textContent = label; }
            }

            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin) return;
                const payload = event.data;
                if (!payload || payload.type !== 'participant.maqra.updated' || !payload.participant_id) return;

                const label = payload.maqra_label || formatMaqraLabel(payload.maqra_title || payload.maqra_code || 'Paket Maqra');
                const roundLabel = payload.maqra_round_label || payload.maqra_round || 'Penyisihan';
                const rows = payload.district_shared && payload.district_id && payload.category_id
                    ? document.querySelectorAll(`[data-participant-row][data-category-id="${payload.category_id}"][data-district-id="${payload.district_id}"]`)
                    : document.querySelectorAll(`[data-participant-row][data-participant-id="${payload.participant_id}"]`);

                rows.forEach((row) => {
                    updateSingleRow(row, label, roundLabel);
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    flashElement(row);
                });
            });
        })();
    </script>
</body>
</html>
