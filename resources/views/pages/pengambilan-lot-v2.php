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
<?php
$initialActiveCategoryId = (string) ($selectedCategory?->id ?? ($categories->first()?->id ?? ''));
?>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false, activeCategoryId: '<?= e($initialActiveCategoryId) ?>' }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>
        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block" x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('sparkles') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Pengambilan Lot</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Ringkasan</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($summaryStats['participant_total']) ?> peserta siap ditinjau</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Halaman ini menampilkan data peserta terverifikasi untuk kebutuhan pengambilan nomor lot.</p>
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
                            <p class="section-kicker">Pengambilan Lot</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Menu pemilihan peserta lot</h2>
                            <p class="mt-2 text-sm text-slate-300">Gunakan halaman ini untuk menelusuri peserta terverifikasi dan melihat kandidat yang akan mendapat nomor lot.</p>
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
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Terverifikasi</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($summaryStats['verified_total']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Golongan Aktif</p>
                            <h3 class="mt-2 text-2xl font-bold text-white"><?= e($selectedCategory ? trim((string) $selectedCategory->branch.' - '.(string) $selectedCategory->name) : 'Semua Golongan') ?></h3>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">Kategori yang tampil di sini mengikuti golongan yang tersedia untuk pengambilan lot pada data peserta terverifikasi.</p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($categories as $category): ?>
                            <button
                                type="button"
                                x-on:click="activeCategoryId = '<?= e((string) $category->id) ?>'; $nextTick(() => document.getElementById('golongan-<?= e($category->id) ?>')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                                x-bind:class="activeCategoryId === '<?= e((string) $category->id) ?>' ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_0_0_1px_rgba(34,211,238,0.20)]' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30'"
                                class="rounded-[1.5rem] border px-4 py-4 text-left transition"
                            >
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-400"><?= e($category->branch) ?></p>
                                <p class="mt-2 text-base font-bold text-white"><?= e($category->name) ?></p>
                                <p class="mt-2 text-xs text-slate-400"><?= e((string) ($participantsByCategory->get($category->id, collect())->count())) ?> peserta terverifikasi</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-100"><?= e($pageController->categoryLotRuleLabel($category)) ?></p>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php foreach ($categories as $category): ?>
                    <?php
                        $categoryParticipants = $participantsByCategory->get($category->id, collect())->values();
                        $isDistrictTeamCategory = str_contains(mb_strtolower((string) $category->branch), 'fahmil')
                            || str_contains(mb_strtolower((string) $category->branch), 'syarhil');
                        $lotTakenParticipantsCount = $categoryParticipants->whereNotNull('lot_number')->count();
                        $districtGroups = $isDistrictTeamCategory
                            ? $categoryParticipants
                                ->groupBy(fn ($participant) => (int) ($participant->district_id ?? 0))
                                ->sortBy(fn ($group) => (string) ($group->first()?->district?->name ?? ''))
                            : collect();
                        $isActiveCategory = (int) ($selectedCategory?->id ?? 0) === (int) $category->id;
                    ?>
                    <section
                        id="golongan-<?= e($category->id) ?>"
                        data-category-section
                        data-category-id="<?= e((string) $category->id) ?>"
                        data-district-team-category="<?= $isDistrictTeamCategory ? '1' : '0' ?>"
                        class="glass-card rounded-[2rem] p-6 border"
                        x-data
                        x-cloak
                        x-show="activeCategoryId === '<?= e((string) $category->id) ?>'"
                        x-bind:class="activeCategoryId === '<?= e((string) $category->id) ?>' ? 'ring-2 ring-cyan-300/20 border-cyan-300/30 bg-cyan-400/8' : 'border-slate-800 bg-slate-950/55'"
                        x-transition.opacity.duration.200ms
                    >
                        <button
                            type="button"
                            class="flex w-full flex-wrap items-center justify-between gap-4 text-left"
                            x-on:click="activeCategoryId = activeCategoryId === '<?= e((string) $category->id) ?>' ? '' : '<?= e((string) $category->id) ?>'"
                        >
                            <div>
                                <p class="section-kicker">Golongan</p>
                                <h3 class="mt-2 text-2xl font-bold text-white"><?= e($category->branch.' - '.$category->name) ?></h3>
                                <p class="mt-2 text-sm text-slate-300"><?= e($categoryParticipants->count()) ?> peserta terverifikasi siap diambil nomor lot</p>
                                <p class="mt-2 text-sm font-semibold text-cyan-100"><?= e($pageController->categoryLotRuleLabel($category)) ?></p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="status-pill border-cyan-400/20 bg-cyan-400/10 text-cyan-100">
                                    <?= mtq_icon('users', 'h-4 w-4') ?>
                                    <?= e($categoryParticipants->count()) ?> peserta
                                </span>
                                <span data-category-lot-summary class="inline-flex rounded-full border <?= $lotTakenParticipantsCount > 0 ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-slate-700 bg-slate-950/70 text-slate-300' ?> px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">
                                    <?= $lotTakenParticipantsCount > 0 ? e($lotTakenParticipantsCount.' lot diambil') : 'Belum ada lot' ?>
                                </span>
                                <span class="inline-flex rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300" x-show="activeCategoryId !== '<?= e((string) $category->id) ?>'" x-cloak>
                                    Klik untuk buka
                                </span>
                                <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100" x-show="activeCategoryId === '<?= e((string) $category->id) ?>'" x-cloak>
                                    Sedang dibuka
                                </span>
                            </div>
                        </button>

                        <div class="pt-6">
                            <?php if ($categoryParticipants->isEmpty()): ?>
                                <div class="rounded-[1.5rem] border border-dashed border-slate-700 bg-slate-950/50 p-6 text-sm text-slate-400">
                                    Belum ada peserta terverifikasi pada golongan ini.
                                </div>
                            <?php elseif ($isDistrictTeamCategory): ?>
                                <div class="grid gap-4 xl:grid-cols-2">
                                    <?php foreach ($districtGroups as $districtIndex => $districtParticipants): ?>
                                        <?php
                                            $districtParticipants = $districtParticipants->values();
                                            $districtName = (string) ($districtParticipants->first()?->district?->name ?? 'Tanpa Kecamatan');
                                            $leaderParticipant = $districtParticipants->first();
                                            $districtLotNumber = (string) ($districtParticipants->firstWhere('lot_number')?->lot_number ?? '');
                                            $districtCompleted = filled($districtLotNumber);
                                            $districtAccent = $districtIndex % 2 === 0
                                                ? 'border-cyan-400/14 bg-slate-950/50'
                                                : 'border-indigo-400/14 bg-slate-950/55';
                                            $districtBadgeAccent = $districtIndex % 2 === 0
                                                ? 'border-cyan-300/20 bg-cyan-400/10 text-cyan-100'
                                                : 'border-indigo-300/20 bg-indigo-400/10 text-indigo-100';
                                            $districtHeaderAccent = $districtIndex % 2 === 0
                                                ? 'from-cyan-400/30 via-sky-400/20 to-transparent'
                                                : 'from-indigo-400/30 via-fuchsia-400/20 to-transparent';
                                            $lotLaunchUrl = $leaderParticipant
                                                ? route('participants.lot.draw', $leaderParticipant).'?autofullscreen=1'
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
                                                <span class="inline-flex rounded-full border <?= $districtCompleted ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : $districtBadgeAccent ?> px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">
                                                    Kecamatan #<?= e(str_pad((string) ($districtIndex + 1), 2, '0', STR_PAD_LEFT)) ?>
                                                </span>
                                                <span class="inline-flex rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300">
                                                    <?= e($districtParticipants->count()) ?> peserta tim
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <p class="section-kicker">Kecamatan</p>
                                                    <h4 data-district-title class="mt-2 text-xl font-bold <?= $districtCompleted ? 'text-emerald-100' : 'text-white' ?>"><?= e($districtName) ?></h4>
                                                    <p class="mt-2 text-sm text-slate-300">1 regu = 1 nomor lot</p>
                                                </div>
                                                <div class="flex flex-col items-end gap-2">
                                                    <?php if ($leaderParticipant && $leaderParticipant->verification_status === 'verified' && in_array($user?->role, ['admin', 'panitia'], true)): ?>
                                                        <a href="<?= e($lotLaunchUrl) ?>" data-lot-launcher class="secondary-button rounded-xl border-cyan-300/30 bg-cyan-400/10 px-3 py-2 text-[11px] text-cyan-100 hover:border-cyan-200/50">
                                                            <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                            Ambil Nomor Lot
                                                        </a>
                                                    <?php endif; ?>
                                                    <span data-district-lot-status class="status-pill <?= $districtCompleted ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100' ?>">
                                                        <?= $districtCompleted ? '1 nomor lot aktif' : '1 nomor lot' ?>
                                                    </span>
                                                    <span data-district-lot-badge class="inline-flex rounded-full border <?= $districtCompleted ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-amber-400/20 bg-amber-400/10 text-amber-100' ?> px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">
                                                        <?= $districtCompleted ? 'Lot sudah diambil' : 'Belum diambil' ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="mt-4 space-y-2">
                                                <?php foreach ($districtParticipants->take(3) as $participant): ?>
                                                    <div
                                                        class="rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3"
                                                        data-district-member-card
                                                        data-participant-id="<?= e((string) $participant->id) ?>"
                                                    >
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p class="text-sm font-semibold text-white"><?= e($participant->name) ?></p>
                                                                <p class="mt-1 text-xs text-slate-400"><?= e($participant->lot_number ?? '-') ?></p>
                                                                <?php if ($districtCompleted): ?>
                                                                    <p data-participant-lot-number class="mt-2 text-xs font-semibold text-emerald-200">Lot: <?= e($districtLotNumber) ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                            <span data-participant-lot-status class="inline-flex rounded-full border <?= $districtCompleted ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-slate-700 bg-slate-950/70 text-slate-300' ?> px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em]">
                                                                <?= $districtCompleted ? 'Sudah' : 'Belum' ?>
                                                            </span>
                                                        </div>
                                                        <div class="mt-3 flex flex-wrap gap-2">
                                                            <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button rounded-xl px-3 py-2 text-[11px]">
                                                                Detail Peserta
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="overflow-hidden rounded-[1.75rem] border border-cyan-400/14 bg-slate-950/50">
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
                                                <tr
                                                    class="border-t border-slate-800/80"
                                                    data-participant-row
                                                    data-participant-id="<?= e((string) $participant->id) ?>"
                                                    data-category-id="<?= e((string) $category->id) ?>"
                                                >
                                                    <td class="px-5 py-4"><?= e($participant->name) ?></td>
                                                <td class="px-5 py-4 text-slate-300" data-lot-number-cell><?= e($participant->lot_number ?? '-') ?></td>
                                                    <td class="px-5 py-4 text-slate-300"><?= e($participant->district?->name ?? '-') ?></td>
                                                    <td class="px-5 py-4">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span data-lot-status-chip class="inline-flex rounded-full border <?= filled($participant->lot_number) ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-slate-700 bg-slate-950/70 text-slate-300' ?> px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]">
                                                                <?= filled($participant->lot_number) ? 'Sudah diambil' : 'Belum diambil' ?>
                                                            </span>
                                                            <?php if (filled($participant->lot_number)): ?>
                                                                <span data-lot-number-chip class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100">
                                                                    <?= e($participant->lot_number) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button rounded-xl px-3 py-2 text-[11px]">Detail</a>
                                                            <?php if ($participant->verification_status === 'verified' && in_array($user?->role, ['admin', 'panitia'], true)): ?>
                                                                <a href="<?= e(route('participants.lot.draw', $participant).'?autofullscreen=1') ?>" data-lot-launcher class="secondary-button rounded-xl border-cyan-300/30 bg-cyan-400/10 px-3 py-2 text-[11px] text-cyan-100 hover:border-cyan-200/50">
                                                                    <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                                    Ambil Nomor Lot
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
                        </div>
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
                if (!element) {
                    return;
                }

                const originalClassName = element.className;
                element.classList.add('ring-2', 'ring-emerald-300/80');
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });

                window.setTimeout(() => {
                    element.className = originalClassName;
                }, 1400);
            }

            function setText(el, text) {
                if (!el) {
                    return;
                }

                el.textContent = text;
            }

            function setStatusChip(el, taken) {
                if (!el) {
                    return;
                }

                el.className = taken
                    ? 'inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100'
                    : 'inline-flex rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300';
                el.textContent = taken ? 'Sudah diambil' : 'Belum diambil';
            }

            function updateCategorySummary(section) {
                if (!section) {
                    return;
                }

                const summary = section.querySelector('[data-category-lot-summary]');
                if (!summary) {
                    return;
                }

                const isDistrictTeamCategory = section.getAttribute('data-district-team-category') === '1';
                const takenCount = isDistrictTeamCategory
                    ? section.querySelectorAll('[data-district-team-card][data-district-completed="1"]').length
                    : section.querySelectorAll('[data-lot-number-chip]').length;

                summary.className = takenCount > 0
                    ? 'inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100'
                    : 'inline-flex rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300';
                summary.textContent = takenCount > 0 ? `${takenCount} lot diambil` : 'Belum ada lot';
            }

            function ensureLotNumberChip(container, lotNumber, beforeEl, className) {
                if (!container || !lotNumber) {
                    return null;
                }

                let chip = container.querySelector('[data-lot-number-chip]');
                if (!chip) {
                    chip = document.createElement('span');
                    chip.setAttribute('data-lot-number-chip', '');
                    chip.className = className;

                    if (beforeEl && beforeEl.parentNode === container) {
                        beforeEl.insertAdjacentElement('beforebegin', chip);
                    } else {
                        container.appendChild(chip);
                    }
                }

                chip.textContent = lotNumber;
                return chip;
            }

            function updateRegularRow(row, payload) {
                const taken = Boolean(payload.lot_number);
                const lotNumber = payload.lot_number ? String(payload.lot_number) : '';
                const statusChip = row.querySelector('[data-lot-status-chip]');
                setStatusChip(statusChip, taken);

                const lotCell = row.querySelector('[data-lot-number-cell]');
                if (lotCell) {
                    lotCell.textContent = taken ? lotNumber : '-';
                }

                const actionCell = row.querySelector('td:last-child');
                if (actionCell) {
                    const flex = actionCell.querySelector('.flex');
                    const detailButton = actionCell.querySelector('a.secondary-button');
                    if (taken) {
                        ensureLotNumberChip(
                            flex,
                            lotNumber,
                            detailButton,
                            'inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100'
                        );
                    } else {
                        const chip = row.querySelector('[data-lot-number-chip]');
                        if (chip) {
                            chip.remove();
                        }
                    }
                }

                flashElement(row);
            }

            function updateDistrictTeamCard(card, payload) {
                if (!card) {
                    return;
                }

                const taken = Boolean(payload.lot_number);
                const lotNumber = payload.lot_number ? String(payload.lot_number) : '';

                card.dataset.districtCompleted = taken ? '1' : '0';
                card.classList.toggle('border-emerald-400/30', taken);
                card.classList.toggle('bg-emerald-400/5', taken);
                card.classList.toggle('border-cyan-400/14', !taken);
                card.classList.toggle('bg-slate-950/50', !taken);

                const title = card.querySelector('[data-district-title]');
                if (title) {
                    title.classList.toggle('text-emerald-100', taken);
                    title.classList.toggle('text-white', !taken);
                }

                setText(card.querySelector('[data-district-lot-status]'), taken ? '1 nomor lot aktif' : '1 nomor lot');
                setStatusChip(card.querySelector('[data-district-lot-badge]'), taken);

                card.querySelectorAll('[data-district-member-card]').forEach((memberCard) => {
                    const status = memberCard.querySelector('[data-participant-lot-status]');
                    setStatusChip(status, taken);

                    const textBox = memberCard.querySelector('.flex.items-start.justify-between.gap-3 > div');
                    const regNumber = memberCard.querySelector('.mt-1.text-xs.text-slate-400');
                    const lotLine = memberCard.querySelector('[data-participant-lot-number]');

                    if (taken) {
                        const lotText = `Lot: ${lotNumber}`;
                        if (lotLine) {
                            setText(lotLine, lotText);
                        } else if (textBox) {
                            const line = document.createElement('p');
                            line.setAttribute('data-participant-lot-number', '');
                            line.className = 'mt-2 text-xs font-semibold text-emerald-200';
                            line.textContent = lotText;
                            if (regNumber && regNumber.parentNode === textBox) {
                                regNumber.insertAdjacentElement('afterend', line);
                            } else {
                                textBox.appendChild(line);
                            }
                        }
                    } else if (lotLine) {
                        lotLine.remove();
                    }
                });

                flashElement(card);
            }

            function applyLotUpdate(payload) {
                if (!payload || !payload.category_id || !payload.lot_number) {
                    return;
                }

                const section = document.querySelector(`[data-category-section][data-category-id="${payload.category_id}"]`);
                if (!section) {
                    return;
                }

                const isDistrictTeamCategory = section.getAttribute('data-district-team-category') === '1';

                if (isDistrictTeamCategory && payload.district_id) {
                    const card = section.querySelector(`[data-district-team-card][data-district-id="${payload.district_id}"]`);
                    updateDistrictTeamCard(card, payload);
                } else {
                    const row = section.querySelector(`[data-participant-row][data-participant-id="${payload.participant_id}"]`);
                    if (row) {
                        updateRegularRow(row, payload);
                    }
                }

                updateCategorySummary(section);
            }

            const launchers = document.querySelectorAll('[data-lot-launcher]');
            launchers.forEach((launcher) => {
                launcher.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = launcher.getAttribute('href');
                    if (!url) {
                        return;
                    }

                    const popup = window.open(
                        url,
                        'mtq-lot-draw',
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

            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin) {
                    return;
                }

                const payload = event.data;
                if (!payload || payload.type !== 'participant.lot.updated') {
                    return;
                }

                applyLotUpdate(payload);
            });
        })();
    </script>
</body>
</html>
