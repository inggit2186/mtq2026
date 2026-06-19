<?php
require_once __DIR__.'/../partials/icon.php';

$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'participants.lot.menu');
$categories = $categories ?? collect();
$participants = $participants ?? collect();
$participantsByCategory = $participantsByCategory ?? $participants->groupBy(fn ($participant) => (int) $participant->competition_category_id);
$selectedCategory = $selectedCategory ?? null;
$selectedParticipant = $selectedParticipant ?? null;
$summaryStats = $summaryStats ?? ['category_total' => 0, 'participant_total' => 0, 'verified_total' => 0];
$filters = $filters ?? ['competition_category_id' => '', 'participant_id' => ''];
$judgeNameDefault = $judgeNameDefault ?? (string) $user?->name;
$pageController = app(\App\Http\Controllers\PageController::class);
$initialActiveCategoryId = (string) ($selectedCategory?->id ?? ($categories->first()?->id ?? ''));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Pengambilan Lot') ?></title>
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
                        <div class="brand-logo brand-logo--lot">
                            <?= mtq_icon('hash', 'h-6 w-6') ?>
                        </div>
                        <div>
                            <p class="brand-subtitle">e-MTQ Console</p>
                            <h1 class="brand-title">Pengambilan Lot</h1>
                        </div>
                    </div>
                    <button type="button" class="sidebar-close-btn lg:hidden" @click="mobileNavOpen = false">
                        <?= mtq_icon('x', 'h-5 w-5') ?>
                    </button>
                </div>

                <div class="lot-summary-card">
                    <div class="lot-summary-icon">
                        <?= mtq_icon('users', 'h-6 w-6') ?>
                    </div>
                    <div class="lot-summary-content">
                        <p class="lot-summary-value"><?= e($summaryStats['participant_total']) ?></p>
                        <p class="lot-summary-label">Peserta Terverifikasi</p>
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
                <section class="lot-hero">
                    <div class="lot-hero-bg"></div>
                    <div class="lot-hero-content">
                        <div class="flex items-center gap-4">
                            <div class="lot-hero-icon">
                                <?= mtq_icon('hash', 'h-7 w-7') ?>
                            </div>
                            <div>
                                <p class="lot-hero-greeting">Pengambilan Lot</p>
                                <h1 class="lot-hero-title">Menu Pemilihan Peserta Lot</h1>
                                <p class="lot-hero-subtitle"><?= e($rolePanel['headline'] ?? 'Telusuri dan pilih peserta untuk nomor lot') ?></p>
                            </div>
                        </div>
                        <div class="lot-hero-badge">
                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                            <span>Akses Aktif</span>
                        </div>
                    </div>
                </section>

                <!-- Quick Stats -->
                <section class="lot-stats-grid">
                    <div class="lot-stat-card">
                        <div class="lot-stat-icon">
                            <?= mtq_icon('book-open', 'h-5 w-5') ?>
                        </div>
                        <div class="lot-stat-content">
                            <p class="lot-stat-value"><?= e($summaryStats['category_total']) ?></p>
                            <p class="lot-stat-label">Golongan</p>
                        </div>
                    </div>
                    <div class="lot-stat-card">
                        <div class="lot-stat-icon">
                            <?= mtq_icon('users', 'h-5 w-5') ?>
                        </div>
                        <div class="lot-stat-content">
                            <p class="lot-stat-value"><?= e($summaryStats['participant_total']) ?></p>
                            <p class="lot-stat-label">Peserta</p>
                        </div>
                    </div>
                    <div class="lot-stat-card lot-stat-card--accent">
                        <div class="lot-stat-icon">
                            <?= mtq_icon('check-circle', 'h-5 w-5') ?>
                        </div>
                        <div class="lot-stat-content">
                            <p class="lot-stat-value"><?= e($summaryStats['verified_total']) ?></p>
                            <p class="lot-stat-label">Terverifikasi</p>
                        </div>
                    </div>
                </section>

                <!-- Category Tabs Section -->
                <section class="lot-section">
                    <div class="lot-section-header">
                        <?= mtq_icon('grid', 'h-5 w-5') ?>
                        <h2 class="lot-section-title">Pilih Golongan</h2>
                    </div>
                    <div class="lot-category-tabs">
                        <?php foreach ($categories as $category): ?>
                            <?php
                                $categoryParticipants = $participantsByCategory->get($category->id, collect());
                                $lotTakenCount = $categoryParticipants->whereNotNull('lot_number')->count();
                            ?>
                            <button
                                type="button"
                                @click="activeCategoryId = '<?= e((string) $category->id) ?>'; $nextTick(() => { const section = document.getElementById('golongan-<?= e($category->id) ?>'); if(section){section.scrollIntoView({ behavior: 'smooth', block: 'start' }); } })"
                                class="lot-category-tab"
                                :class="{ 'lot-category-tab--active': activeCategoryId === '<?= e((string) $category->id) ?>' }"
                            >
                                <div class="lot-category-tab-icon">
                                    <?= mtq_icon('layers', 'h-5 w-5') ?>
                                </div>
                                <div class="lot-category-tab-content">
                                    <p class="lot-category-tab-branch"><?= e($category->branch) ?></p>
                                    <p class="lot-category-tab-name"><?= e($category->name) ?></p>
                                    <p class="lot-category-tab-meta"><?= e($categoryParticipants->count()) ?> peserta</p>
                                </div>
                                <?php if ($lotTakenCount > 0): ?>
                                    <span class="lot-category-tab-badge"><?= e($lotTakenCount) ?> lot</span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Category Sections with Participants -->
                <?php foreach ($categories as $category): ?>
                    <?php
                        $categoryParticipants = $participantsByCategory->get($category->id, collect())->values();
                        $isDistrictTeamCategory = str_contains(mb_strtolower((string) $category->branch), 'fahmil')
                            || str_contains(mb_strtolower((string) $category->branch), 'syarhil');
                        $lotTakenParticipantsCount = $categoryParticipants->whereNotNull('lot_number')->count();
                        $districtGroups = $isDistrictTeamCategory
                            ? $categoryParticipants->groupBy(fn ($participant) => (int) ($participant->district_id ?? 0))->sortBy(fn ($group) => (string) ($group->first()?->district?->name ?? ''))
                            : collect();
                    ?>
                    <section
                        id="golongan-<?= e($category->id) ?>"
                        data-category-section
                        data-category-id="<?= e((string) $category->id) ?>"
                        data-district-team-category="<?= $isDistrictTeamCategory ? '1' : '0' ?>"
                        class="lot-category-section"
                        x-data
                        x-cloak
                        x-show="activeCategoryId === '<?= e((string) $category->id) ?>'"
                    >
                        <div class="lot-category-section-header">
                            <div class="lot-category-section-info">
                                <p class="lot-category-section-branch"><?= e($category->branch) ?></p>
                                <h2 class="lot-category-section-name"><?= e($category->name) ?></h2>
                                <p class="lot-category-section-rule"><?= e($pageController->categoryLotRuleLabel($category)) ?></p>
                            </div>
                            <div class="lot-category-section-stats">
                                <span class="lot-category-section-badge">
                                    <?= mtq_icon('users', 'h-4 w-4') ?>
                                    <?= e($categoryParticipants->count()) ?> Peserta
                                </span>
                                <?php if ($lotTakenParticipantsCount > 0): ?>
                                    <span class="lot-category-section-badge lot-category-section-badge--success">
                                        <?= mtq_icon('check', 'h-4 w-4') ?>
                                        <?= e($lotTakenParticipantsCount) ?> Lot Diambil
                                    </span>
                                <?php else: ?>
                                    <span class="lot-category-section-badge lot-category-section-badge--warning">
                                        <?= mtq_icon('clock', 'h-4 w-4') ?>
                                        Belum Ada Lot
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($categoryParticipants->isEmpty()): ?>
                            <div class="lot-empty">
                                <?= mtq_icon('users', 'h-10 w-10') ?>
                                <p>Belum ada peserta terverifikasi pada golongan ini</p>
                            </div>
                        <?php elseif ($isDistrictTeamCategory): ?>
                            <!-- District Team View -->
                            <div class="lot-district-grid">
                                <?php foreach ($districtGroups as $districtIndex => $districtParticipants): ?>
                                    <?php
                                        $districtParticipants = $districtParticipants->values();
                                        $districtName = (string) ($districtParticipants->first()?->district?->name ?? 'Tanpa Kecamatan');
                                        $leaderParticipant = $districtParticipants->first();
                                        $districtLotNumber = (string) ($districtParticipants->firstWhere('lot_number')?->lot_number ?? '');
                                        $districtCompleted = filled($districtLotNumber);
                                        $lotLaunchUrl = $leaderParticipant ? route('participants.lot.draw', $leaderParticipant).'?autofullscreen=1' : null;
                                    ?>
                                    <div class="lot-district-card <?= $districtCompleted ? 'lot-district-card--completed' : '' ?>">
                                        <div class="lot-district-header">
                                            <span class="lot-district-badge">Kecamatan #<?= e(str_pad((string) ($districtIndex + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                                            <span class="lot-district-count"><?= e($districtParticipants->count()) ?> peserta</span>
                                        </div>
                                        <div class="lot-district-content">
                                            <h3 class="lot-district-name"><?= e($districtName) ?></h3>
                                            <?php if ($districtCompleted): ?>
                                                <p class="lot-district-lot">
                                                    <?= mtq_icon('hash', 'h-4 w-4') ?>
                                                    Lot: <?= e($districtLotNumber) ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="lot-district-status">
                                                    <?= mtq_icon('clock', 'h-4 w-4') ?>
                                                    Belum diambil
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="lot-district-members">
                                            <?php foreach ($districtParticipants->take(3) as $participant): ?>
                                                <div class="lot-district-member">
                                                    <span class="lot-district-member-name"><?= e($participant->name) ?></span>
                                                    <span class="lot-district-member-status <?= $districtCompleted ? 'lot-district-member-status--done' : '' ?>">
                                                        <?= $districtCompleted ? 'Sudah' : 'Belum' ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="lot-district-actions">
                                            <?php if ($leaderParticipant && $leaderParticipant->verification_status === 'verified' && in_array($user?->role, ['admin', 'panitia'], true)): ?>
                                                <a href="<?= e($lotLaunchUrl) ?>" data-lot-launcher class="lot-btn lot-btn--primary">
                                                    <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                    Ambil Nomor Lot
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= e(route('participants.show', $districtParticipants->first())) ?>" class="lot-btn lot-btn--secondary">
                                                Detail
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Mobile-friendly Table View -->
                            <div class="lot-table-wrapper">
                                <table class="lot-table">
                                    <thead>
                                        <tr>
                                            <th class="lot-th">Peserta</th>
                                            <th class="lot-th lot-th--center">Lot</th>
                                            <th class="lot-th">Kecamatan</th>
                                            <th class="lot-th lot-th--actions">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categoryParticipants as $participant): ?>
                                            <?php $hasLot = filled($participant->lot_number); ?>
                                            <tr class="lot-tr" data-participant-row data-participant-id="<?= e((string) $participant->id) ?>" data-category-id="<?= e((string) $category->id) ?>">
                                                <td class="lot-td lot-td--name">
                                                    <p class="lot-participant-name"><?= e($participant->name) ?></p>
                                                    <span class="lot-status-chip <?= $hasLot ? 'lot-status-chip--success' : 'lot-status-chip--neutral' ?>">
                                                        <?= $hasLot ? mtq_icon('check', 'h-3 w-3').' '.'Sudah diambil' : mtq_icon('clock', 'h-3 w-3').' '.'Belum diambil' ?>
                                                    </span>
                                                </td>
                                                <td class="lot-td lot-td--center">
                                                    <?php if ($hasLot): ?>
                                                        <span class="lot-number-badge"><?= e($participant->lot_number) ?></span>
                                                    <?php else: ?>
                                                        <span class="lot-empty-badge">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="lot-td">
                                                    <span class="lot-district-text"><?= e($participant->district?->name ?? '-') ?></span>
                                                </td>
                                                <td class="lot-td lot-td--actions">
                                                    <div class="lot-action-buttons">
                                                        <a href="<?= e(route('participants.show', $participant)) ?>" class="lot-action-btn lot-action-btn--secondary">
                                                            <?= mtq_icon('eye', 'h-4 w-4') ?>
                                                            <span>Detail</span>
                                                        </a>
                                                        <?php if ($participant->verification_status === 'verified' && in_array($user?->role, ['admin', 'panitia'], true)): ?>
                                                            <a href="<?= e(route('participants.lot.draw', $participant).'?autofullscreen=1') ?>" data-lot-launcher class="lot-action-btn lot-action-btn--primary">
                                                                <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                                <span>Ambil Lot</span>
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
            function flashElement(element) {
                if (!element) return;
                const originalClassName = element.className;
                element.classList.add('ring-2', 'ring-emerald-300/80');
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(() => { element.className = originalClassName; }, 1400);
            }

            function setText(el, text) {
                if (!el) return;
                el.textContent = text;
            }

            function setStatusChip(el, taken) {
                if (!el) return;
                el.className = taken
                    ? 'lot-status-chip lot-status-chip--success'
                    : 'lot-status-chip lot-status-chip--neutral';
                el.innerHTML = taken
                    ? '<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Sudah diambil'
                    : '<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Belum diambil';
            }

            function updateCategorySummary(section) {
                if (!section) return;
                const takenCount = section.querySelectorAll('[data-lot-number-badge]').length;
                const badge = section.querySelector('.lot-category-section-badge--warning, .lot-category-section-badge--success');
                if (badge) {
                    if (takenCount > 0) {
                        badge.className = 'lot-category-section-badge lot-category-section-badge--success';
                        badge.innerHTML = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> ' + takenCount + ' Lot Diambil';
                    } else {
                        badge.className = 'lot-category-section-badge lot-category-section-badge--warning';
                        badge.innerHTML = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Belum Ada Lot';
                    }
                }
            }

            function updateRegularRow(row, payload) {
                const taken = Boolean(payload.lot_number);
                const statusChip = row.querySelector('.lot-status-chip');
                setStatusChip(statusChip, taken);

                const lotCell = row.querySelector('.lot-number-badge, .lot-empty-badge');
                if (lotCell) {
                    lotCell.className = taken ? 'lot-number-badge' : 'lot-empty-badge';
                    lotCell.textContent = taken ? payload.lot_number : '-';
                }

                flashElement(row);
            }

            function applyLotUpdate(payload) {
                if (!payload || !payload.category_id || !payload.lot_number) return;
                const section = document.querySelector(`[data-category-section][data-category-id="${payload.category_id}"]`);
                if (!section) return;

                const row = section.querySelector(`[data-participant-row][data-participant-id="${payload.participant_id}"]`);
                if (row) updateRegularRow(row, payload);
                updateCategorySummary(section);
            }

            const launchers = document.querySelectorAll('[data-lot-launcher]');
            launchers.forEach((launcher) => {
                launcher.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = launcher.getAttribute('href');
                    if (!url) return;
                    const popup = window.open(url, 'mtq-lot-draw', `popup=yes,width=${screen.availWidth},height=${screen.availHeight},left=0,top=0,noopener=no`);
                    if (popup) {
                        try { popup.moveTo(0, 0); popup.resizeTo(screen.availWidth, screen.availHeight); popup.focus(); }
                        catch (error) { popup.focus(); }
                    } else { window.location.href = url; }
                });
            });

            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin) return;
                const payload = event.data;
                if (!payload || payload.type !== 'participant.lot.updated') return;
                applyLotUpdate(payload);
            });
        })();
    </script>
</body>
</html>
