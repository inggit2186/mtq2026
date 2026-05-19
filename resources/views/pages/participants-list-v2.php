<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$participants = $participants ?? collect();
$participantsPage = $participantsPage ?? collect();
$participantsPaginator = $participantsPaginator ?? null;
$participantsPerPage = (int) ($participantsPerPage ?? 12);
$categories = $categories ?? collect();
$districts = $districts ?? collect();
$filters = $filters ?? [];
$registrationStats = $registrationStats ?? ['total' => 0, 'verified' => 0, 'pending' => 0, 'draft' => 0];
$districtLocked = $districtLocked ?? false;
$canVerify = $canVerify ?? false;
$canManageMaqra = $canManageMaqra ?? false;
$canDrawParticipant = $canDrawParticipant ?? false;
$canDrawMaqra = $canDrawMaqra ?? false;
$officialAccessSetting = $officialAccessSetting ?? new \App\Models\OfficialAccessSetting();
$isOfficialUser = in_array($user?->role, ['official', 'pendamping'], true);
$isPanitiaUser = $user?->role === 'panitia';
$registrationWindowOpen = $registrationWindowOpen ?? true;
$verificationOpenForPanitia = (bool) ($officialAccessSetting->participant_verification_open ?? true);
$lotOpenForPanitia = (bool) ($officialAccessSetting->participant_lot_open ?? true);
$maqraOpenForPanitia = (bool) ($officialAccessSetting->maqraAnyRoundEnabled())
    && collect($officialAccessSetting->maqraOpenCategoryIds())->isNotEmpty();
$officialEditOpen = (bool) ($officialAccessSetting->participant_edit_open ?? true);
$participantDeleteOpen = (bool) ($officialAccessSetting->participant_delete_open ?? true);
$officialEditOpen = $officialEditOpen && (! $isOfficialUser || $registrationWindowOpen);
$participantDeleteOpen = $participantDeleteOpen && (! $isOfficialUser || $registrationWindowOpen);
$maqraSwapCandidatesMap = $maqraSwapCandidatesMap ?? collect();
$mainParticipants = $participants
    ->filter(fn ($participant) => ($participant->participant_role ?? 'main') !== 'reserve')
    ->values();
$reserveParticipants = $participants
    ->filter(fn ($participant) => ($participant->participant_role ?? 'main') === 'reserve')
    ->values();
$buildParticipantGroup = function (\Illuminate\Support\Collection $items, array $config): array {
    $verifiedCount = $items->where('verification_status', 'verified')->count();
    $pendingCount = $items->where('verification_status', 'submitted')->count();
    $needsReviewCount = $items->filter(fn ($participant) => in_array($participant->verification_status, ['draft', 'rejected'], true))->count();
    $dominantState = 'verified';

    if ($needsReviewCount > 0 && $needsReviewCount >= max($verifiedCount, $pendingCount)) {
        $dominantState = 'review';
    } elseif ($pendingCount > 0 && $pendingCount >= max($verifiedCount, $needsReviewCount)) {
        $dominantState = 'pending';
    }

    $dominantBadgeClass = match ($dominantState) {
        'review' => 'border-amber-300/30 bg-amber-300/14 text-amber-100',
        'pending' => 'border-cyan-300/30 bg-cyan-300/14 text-cyan-100',
        default => 'border-emerald-300/30 bg-emerald-300/14 text-emerald-100',
    };
    $dominantBadgeLabel = match ($dominantState) {
        'review' => 'Prioritas Review',
        'pending' => 'Menunggu Verifikasi',
        default => 'Siap & Terverifikasi',
    };

    return array_merge($config, [
        'items' => $items,
        'verified_count' => $verifiedCount,
        'pending_count' => $pendingCount,
        'needs_review_count' => $needsReviewCount,
        'dominant_state' => $dominantState,
        'dominant_badge_class' => $dominantBadgeClass,
        'dominant_badge_label' => $dominantBadgeLabel,
    ]);
};
$participantGroups = [
    $buildParticipantGroup($mainParticipants, [
        'key' => 'main',
        'label' => 'Peserta Inti',
        'status' => 'Slot utama aktif',
        'dot' => 'bg-cyan-300',
        'accent' => 'cyan',
        'panel_class' => 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100',
        'active_button_class' => 'border-cyan-300/40 bg-cyan-400/12 text-white shadow-[0_18px_55px_-28px_rgba(34,211,238,0.55)]',
        'active_status_class' => 'text-cyan-200',
        'summary' => 'Daftar utama untuk peserta yang mengisi slot inti pada golongan terpilih.',
    ]),
    $buildParticipantGroup($reserveParticipants, [
        'key' => 'reserve',
        'label' => 'Peserta Cadangan',
        'status' => 'Slot cadangan aktif',
        'dot' => 'bg-emerald-300',
        'accent' => 'emerald',
        'panel_class' => 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100',
        'active_button_class' => 'border-emerald-300/40 bg-emerald-400/12 text-white shadow-[0_18px_55px_-28px_rgba(52,211,153,0.45)]',
        'active_status_class' => 'text-emerald-200',
        'summary' => 'Daftar cadangan untuk peserta pengganti bila slot inti pada golongan yang sama berubah.',
    ]),
];
$defaultParticipantTab = 'main';
$exportQuery = array_filter([
    'district_id' => $filters['district_id'] ?? '',
    'competition_category_id' => $filters['competition_category_id'] ?? '',
    'verification_status' => $filters['verification_status'] ?? '',
    'keyword' => $filters['keyword'] ?? '',
], fn ($value) => filled($value));
$activeSort = (string) ($filters['sort'] ?? 'created_at');
$activeDirection = (string) ($filters['direction'] ?? 'desc');
$sortDefaultDirection = [
    'created_at' => 'desc',
    'verification_status' => 'asc',
    'lot_number' => 'asc',
];
$buildSortedListUrl = function (string $sortKey) use ($filters, $activeSort, $activeDirection, $sortDefaultDirection): string {
    $preferredDirection = $sortDefaultDirection[$sortKey] ?? 'asc';
    $nextDirection = $activeSort === $sortKey
        ? ($activeDirection === 'asc' ? 'desc' : 'asc')
        : $preferredDirection;

    $query = array_filter([
        'district_id' => $filters['district_id'] ?? '',
        'competition_category_id' => $filters['competition_category_id'] ?? '',
        'verification_status' => $filters['verification_status'] ?? '',
        'keyword' => $filters['keyword'] ?? '',
        'sort' => $sortKey,
        'direction' => $nextDirection,
    ], fn ($value) => filled($value));

    return route('participants.list', $query);
};
$verificationScopeLabel = 'Semua kecamatan';

if ($districtLocked && filled($user?->district?->name)) {
    $verificationScopeLabel = (string) $user?->district?->name;
} elseif (filled($filters['district_id'] ?? null)) {
    $selectedDistrict = collect($districts)->firstWhere('id', (string) ($filters['district_id'] ?? ''));
    $verificationScopeLabel = (string) ($selectedDistrict->name ?? 'Semua kecamatan');
} elseif ($restrictPanitiaDistricts && count($verificationDistrictIds) === 1) {
    $selectedDistrict = collect($districts)->firstWhere('id', (string) $verificationDistrictIds[0]);
    $verificationScopeLabel = (string) ($selectedDistrict->name ?? 'Semua kecamatan');
} elseif ($restrictPanitiaDistricts && count($verificationDistrictIds) > 1) {
    $verificationScopeLabel = 'Terbatas pada kecamatan verifikasi yang ditugaskan';
}

if ($mainParticipants->isEmpty() && $reserveParticipants->isNotEmpty()) {
    $defaultParticipantTab = 'reserve';
}

$activeParticipantTabLabel = $defaultParticipantTab === 'reserve' ? 'Peserta Cadangan' : 'Peserta Inti';
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'participants.list');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Data Peserta') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false, participantTab: '<?= e($defaultParticipantTab) ?>' }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('users') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Data Peserta</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Daftar Terdaftar</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($registrationStats['total']) ?> Peserta</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Kelola pencarian, verifikasi, dan perbaikan data peserta dari satu tempat.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                        Data Aktif
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Menunggu</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['pending']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Terverifikasi</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['verified']) ?></p>
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
                            <p class="section-kicker">Data Masuk</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Daftar peserta terdaftar</h2>
                            <p class="mt-2 text-sm text-slate-300">Gunakan filter untuk mencari peserta berdasarkan kecamatan, golongan, status, nama, NIK, atau nomor registrasi.</p>
                            <div class="mt-3 inline-flex max-w-full items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1.5 text-xs font-semibold text-cyan-100">
                                <?= mtq_icon('shield-check', 'h-3.5 w-3.5') ?>
                                <span class="truncate">Anda sedang melihat kecamatan verifikasi: <?= e($verificationScopeLabel) ?></span>
                            </div>
                            <?php if ($isPanitiaUser && ! $verificationOpenForPanitia): ?>
                                <div class="mt-3 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm leading-6 text-amber-100">
                                    Masa verifikasi peserta untuk panitia sedang ditutup oleh admin.
                                </div>
                            <?php endif; ?>
                            <?php if ($isPanitiaUser && ! $lotOpenForPanitia): ?>
                                <div class="mt-3 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm leading-6 text-amber-100">
                                    Masa ambil nomor lot untuk panitia sedang ditutup oleh admin.
                                </div>
                            <?php endif; ?>
                            <?php if ($isPanitiaUser && ! $maqraOpenForPanitia): ?>
                                <div class="mt-3 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm leading-6 text-amber-100">
                                    Masa ambil maqra untuk panitia sedang ditutup oleh admin.
                                </div>
                            <?php endif; ?>
                            <?php if (($isOfficialUser || $isPanitiaUser) && ! $participantDeleteOpen): ?>
                                <div class="mt-3 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm leading-6 text-amber-100">
                                    Akses hapus peserta untuk official dan panitia sedang ditutup.
                                </div>
                            <?php endif; ?>
                            <?php if ($isOfficialUser && ! $officialEditOpen): ?>
                                <div class="mt-3 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm leading-6 text-amber-100">
                                    Masa edit peserta official sudah berakhir mengikuti juknis. Akses otomatis ditutup mulai 19 Mei 2026 pukul 00:00.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <?php if ($user?->role === 'admin'): ?>
                            <a href="<?= e(route('participants.trash')) ?>" class="secondary-button">
                                <?= mtq_icon('trash', 'h-4 w-4') ?>
                                Arsip Peserta
                            </a>
                        <?php endif; ?>
                            <a href="<?= e(route('participants.export.excel', $exportQuery)) ?>" class="secondary-button">
                                <?= mtq_icon('book-open', 'h-4 w-4') ?>
                                Export Data Peserta
                            </a>
                            <a href="<?= e(route('participants.export.verification.excel', $exportQuery)) ?>" class="secondary-button">
                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                Export Data Verifikasi
                            </a>
                            <a href="<?= e(route('participants.export.pdf', $exportQuery)) ?>" target="_blank" rel="noreferrer" class="secondary-button">
                                <?= mtq_icon('book-open', 'h-4 w-4') ?>
                                Export PDF
                            </a>
                        <a href="<?= e(route('participants.index')) ?>" class="primary-button">
                            <?= mtq_icon('id-card', 'h-4 w-4') ?>
                            Daftar Peserta Baru
                        </a>
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Total</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Terverifikasi</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['verified']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('clock') ?></div><p class="mt-4 text-sm text-slate-400">Menunggu</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['pending']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('upload') ?></div><p class="mt-4 text-sm text-slate-400">Draf</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['draft']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('layers') ?></div>
                        <div>
                            <p class="section-kicker">Filter Data</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Saring peserta berdasarkan kebutuhan verifikasi</h3>
                        </div>
                    </div>

                    <form method="GET" action="<?= e(route('participants.list')) ?>" class="mt-6 grid gap-4 lg:grid-cols-4">
                        <input type="hidden" name="sort" value="<?= e($activeSort) ?>">
                        <input type="hidden" name="direction" value="<?= e($activeDirection) ?>">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kata kunci</label>
                            <input name="keyword" type="text" value="<?= e($filters['keyword'] ?? '') ?>" placeholder="Nama / NIK / registrasi" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Status verifikasi</label>
                            <select name="verification_status" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Semua status</option>
                                <option value="draft" <?= ($filters['verification_status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draf</option>
                                <option value="submitted" <?= ($filters['verification_status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Menunggu</option>
                                <option value="verified" <?= ($filters['verification_status'] ?? '') === 'verified' ? 'selected' : '' ?>>Terverifikasi</option>
                                <option value="rejected" <?= ($filters['verification_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kecamatan</label>
                            <select name="district_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" <?= $districtLocked ? 'disabled' : '' ?>>
                                <option value="">Semua kecamatan</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= e($district->id) ?>" <?= (string) ($filters['district_id'] ?? '') === (string) $district->id ? 'selected' : '' ?>><?= e($district->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($districtLocked): ?>
                                <input type="hidden" name="district_id" value="<?= e($filters['district_id'] ?? $user?->district_id) ?>">
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kategori</label>
                            <select name="competition_category_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Semua golongan</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e($category->id) ?>" <?= (string) ($filters['competition_category_id'] ?? '') === (string) $category->id ? 'selected' : '' ?>>
                                        <?= e($category->branch.' - '.$category->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="lg:col-span-4 flex flex-wrap gap-3">
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                Terapkan Filter
                            </button>
                            <a href="<?= e(route('participants.list')) ?>" class="secondary-button">
                                <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                Reset
                            </a>
                        </div>
                        <div class="lg:col-span-4 rounded-2xl border border-cyan-400/16 bg-cyan-400/8 px-4 py-3 text-sm text-slate-300">
                            Export rekap mengikuti filter aktif pada halaman ini. Official hanya dapat mengexport peserta kecamatan sendiri, sedangkan admin dan panitia dapat mengexport per kecamatan atau seluruh kecamatan.
                        </div>
                    </form>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('users') ?></div>
                            <div>
                                <p class="section-kicker">Data Masuk</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Daftar peserta terdaftar</h3>
                            </div>
                        </div>
                        <span class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?= e($participants->count()) ?> peserta
                        </span>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex w-full gap-3 overflow-x-auto pb-1 lg:w-auto">
                            <?php foreach ($participantGroups as $group): ?>
                                <button
                                    type="button"
                                    class="min-w-[220px] flex-1 rounded-2xl border px-4 py-3 text-left transition lg:flex-none"
                                    x-on:click="participantTab = '<?= e($group['key']) ?>'"
                                    x-bind:class="participantTab === '<?= e($group['key']) ?>' ? '<?= e($group['active_button_class']) ?>' : 'border-slate-700 bg-slate-900/70 text-slate-300 hover:border-slate-600'"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400"><?= e($group['label']) ?></p>
                                            <p class="mt-2 text-lg font-bold"><?= e($group['items']->count()) ?> peserta</p>
                                        </div>
                                        <span class="inline-flex h-2.5 w-2.5 rounded-full <?= e($group['dot']) ?>"></span>
                                    </div>
                                    <p class="mt-1 text-xs" x-bind:class="participantTab === '<?= e($group['key']) ?>' ? '<?= e($group['active_status_class']) ?>' : 'text-slate-500'"><?= e($group['status']) ?></p>
                                    <div class="mt-3 grid grid-cols-3 gap-2 text-[11px]">
                                        <div class="rounded-xl border border-white/10 bg-slate-950/20 px-2 py-2">
                                            <p class="uppercase tracking-[0.18em] text-slate-500">Verif</p>
                                            <p class="mt-1 font-bold text-emerald-200"><?= e($group['verified_count']) ?></p>
                                        </div>
                                        <div class="rounded-xl border border-white/10 bg-slate-950/20 px-2 py-2">
                                            <p class="uppercase tracking-[0.18em] text-slate-500">Tunggu</p>
                                            <p class="mt-1 font-bold text-cyan-200"><?= e($group['pending_count']) ?></p>
                                        </div>
                                        <div class="rounded-xl border border-white/10 bg-slate-950/20 px-2 py-2">
                                            <p class="uppercase tracking-[0.18em] text-slate-500">Review</p>
                                            <p class="mt-1 font-bold text-amber-100"><?= e($group['needs_review_count']) ?></p>
                                        </div>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="status-pill hidden sm:inline-flex">
                            <?php foreach ($participantGroups as $group): ?>
                                <span x-show="participantTab === '<?= e($group['key']) ?>'" class="inline-flex items-center gap-2">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full <?= e($group['dot']) ?>"></span>
                                    <?= e($group['label']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($participants->isNotEmpty() && $defaultParticipantTab === 'reserve'): ?>
                        <div class="mt-4 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
                            Tab awal otomatis dibuka ke <strong><?= e($activeParticipantTabLabel) ?></strong> karena hasil filter saat ini hanya berisi peserta cadangan.
                        </div>
                    <?php endif; ?>

                    <?php foreach ($participantGroups as $group): ?>
                        <?php $pageGroupItems = $group['items']->filter(fn ($participant) => $participantsPage->contains('id', $participant->id))->values(); ?>
                        <div x-show="participantTab === '<?= e($group['key']) ?>'" x-cloak>
                            <div class="mt-6 rounded-[1.5rem] border px-4 py-4 sm:px-5 <?= e($group['panel_class']) ?>">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em]">Tab Aktif</p>
                                        <p class="mt-2 text-lg font-bold"><?= e($group['label']) ?></p>
                                        <p class="mt-1 text-sm opacity-90"><?= e($group['summary']) ?></p>
                                        <div class="mt-3">
                                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?= e($group['dominant_badge_class']) ?>">
                                                <?= e($group['dominant_badge_label']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        <div class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3 text-sm text-white/90">
                                            <p class="text-xs uppercase tracking-[0.18em] text-white/60">Total</p>
                                            <p class="mt-2 text-2xl font-black"><?= e($group['items']->count()) ?></p>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3 text-sm text-white/90">
                                            <p class="text-xs uppercase tracking-[0.18em] text-white/60">Terverifikasi</p>
                                            <p class="mt-2 text-2xl font-black text-emerald-200"><?= e($group['verified_count']) ?></p>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3 text-sm text-white/90">
                                            <p class="text-xs uppercase tracking-[0.18em] text-white/60">Menunggu</p>
                                            <p class="mt-2 text-2xl font-black text-cyan-200"><?= e($group['pending_count']) ?></p>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-slate-950/20 px-4 py-3 text-sm text-white/90">
                                            <p class="text-xs uppercase tracking-[0.18em] text-white/60">Perlu Review</p>
                                            <p class="mt-2 text-2xl font-black text-amber-100"><?= e($group['needs_review_count']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-700/70 bg-slate-950/50 px-4 py-3 text-xs text-slate-300">
                                <span class="mr-1 font-semibold uppercase tracking-[0.18em] text-slate-500">Urutkan cepat</span>
                                <?php foreach ([
                                    'created_at' => 'Terbaru',
                                    'name' => 'Nama',
                                    'registration_number' => 'No. Reg',
                                    'district' => 'Kecamatan',
                                    'category' => 'Kategori',
                                    'verification_status' => 'Status',
                                    'nik' => 'NIK',
                                    'lot_number' => 'Lot',
                                ] as $sortKey => $label): ?>
                                    <a
                                        href="<?= e($buildSortedListUrl($sortKey)) ?>"
                                        class="inline-flex items-center gap-1 rounded-full border px-3 py-1.5 font-semibold transition <?= $activeSort === $sortKey ? 'border-cyan-300/30 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-900/70 text-slate-300 hover:border-cyan-400/20 hover:text-cyan-100' ?>"
                                    >
                                        <?= e($label) ?>
                                        <?php if ($activeSort === $sortKey): ?>
                                            <?= mtq_icon($activeDirection === 'asc' ? 'arrow-up' : 'arrow-down', 'h-3.5 w-3.5') ?>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>

                            <div class="table-shell mt-6">
                            <table class="min-w-full">
                                <colgroup>
                                    <?php if ($canVerify): ?>
                                        <col style="width: 14%;">
                                        <col style="width: 23%;">
                                        <col style="width: 12%;">
                                        <col style="width: 12%;">
                                        <col style="width: 13%;">
                                        <col style="width: 16%;">
                                        <col style="width: 10%;">
                                    <?php else: ?>
                                        <col style="width: 14%;">
                                        <col style="width: 29%;">
                                        <col style="width: 13%;">
                                        <col style="width: 14%;">
                                        <col style="width: 12%;">
                                        <col style="width: 18%;">
                                    <?php endif; ?>
                                </colgroup>
                                <thead class="table-head">
                                    <tr>
                                        <th class="px-3 py-3 text-center" aria-sort="<?= e($activeSort === 'registration_number' ? ($activeDirection === 'asc' ? 'ascending' : 'descending') : 'none') ?>">
                                            <a href="<?= e($buildSortedListUrl('registration_number')) ?>" class="inline-flex items-center justify-center gap-1 rounded-md transition hover:text-cyan-200">
                                                No. Registrasi
                                                <?php if ($activeSort === 'registration_number'): ?>
                                                    <?= mtq_icon($activeDirection === 'asc' ? 'arrow-up' : 'arrow-down', 'h-3.5 w-3.5') ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th class="px-3 py-3 text-center" aria-sort="<?= e($activeSort === 'name' ? ($activeDirection === 'asc' ? 'ascending' : 'descending') : 'none') ?>">
                                            <a href="<?= e($buildSortedListUrl('name')) ?>" class="inline-flex items-center justify-center gap-1 rounded-md transition hover:text-cyan-200">
                                                Peserta
                                                <?php if ($activeSort === 'name'): ?>
                                                    <?= mtq_icon($activeDirection === 'asc' ? 'arrow-up' : 'arrow-down', 'h-3.5 w-3.5') ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th class="px-3 py-3 text-center" aria-sort="<?= e($activeSort === 'district' ? ($activeDirection === 'asc' ? 'ascending' : 'descending') : 'none') ?>">
                                            <a href="<?= e($buildSortedListUrl('district')) ?>" class="inline-flex items-center justify-center gap-1 rounded-md transition hover:text-cyan-200">
                                                Kecamatan
                                                <?php if ($activeSort === 'district'): ?>
                                                    <?= mtq_icon($activeDirection === 'asc' ? 'arrow-up' : 'arrow-down', 'h-3.5 w-3.5') ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th class="px-3 py-3 text-center" aria-sort="<?= e($activeSort === 'category' ? ($activeDirection === 'asc' ? 'ascending' : 'descending') : 'none') ?>">
                                            <a href="<?= e($buildSortedListUrl('category')) ?>" class="inline-flex items-center justify-center gap-1 rounded-md transition hover:text-cyan-200">
                                                Kategori
                                                <?php if ($activeSort === 'category'): ?>
                                                    <?= mtq_icon($activeDirection === 'asc' ? 'arrow-up' : 'arrow-down', 'h-3.5 w-3.5') ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th class="px-3 py-3 text-center" aria-sort="<?= e($activeSort === 'verification_status' ? ($activeDirection === 'asc' ? 'ascending' : 'descending') : 'none') ?>">
                                            <a href="<?= e($buildSortedListUrl('verification_status')) ?>" class="inline-flex items-center justify-center gap-1 rounded-md transition hover:text-cyan-200">
                                                Status
                                                <?php if ($activeSort === 'verification_status'): ?>
                                                    <?= mtq_icon($activeDirection === 'asc' ? 'arrow-up' : 'arrow-down', 'h-3.5 w-3.5') ?>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th class="px-3 py-3 text-center">Aksi</th>
                                        <?php if ($canVerify): ?>
                                            <th class="px-3 py-3 text-center">Verifikasi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($group['items']->isEmpty()): ?>
                                        <tr class="table-row" data-participant-row data-participant-id="" data-category-id="" data-district-id="">
                                            <td colspan="<?= e($canVerify ? 7 : 6) ?>" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada <?= e(mb_strtolower($group['label'])) ?> yang sesuai dengan filter.</td>
                                        </tr>
                                    <?php elseif ($pageGroupItems->isEmpty()): ?>
                                        <tr class="table-row" data-participant-row data-participant-id="" data-category-id="" data-district-id="">
                                            <td colspan="<?= e($canVerify ? 7 : 6) ?>" class="px-5 py-8 text-center text-sm text-slate-400">Tidak ada data <?= e(mb_strtolower($group['label'])) ?> pada halaman ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($pageGroupItems as $participant): ?>
                                        <tr class="table-row">
                                            <td class="px-3 py-3 text-sm leading-tight text-cyan-200">
                                                <span class="break-all"><?= e($participant->registration_number) ?></span>
                                            </td>
                                            <td class="px-3 py-3 align-top">
                                                <div class="break-normal font-semibold leading-snug text-white"><?= e($participant->name) ?></div>
                                                <?php if (filled($participant->lot_number)): ?>
                                                    <?php
                                                        $lotGroupSize = $participant->category
                                                            ? app(\App\Http\Controllers\PageController::class)->categoryLotGroupSize($participant->category, (string) $participant->gender)
                                                            : 1;
                                                        $lotRuleLabel = $participant->category
                                                            ? app(\App\Http\Controllers\PageController::class)->categoryLotRuleLabel($participant->category, (string) $participant->gender)
                                                            : '1 peserta = 1 nomor lot';
                                                    ?>
                                                    <div class="mt-1 inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100" data-lot-chip>
                                                        Lot <?= e($participant->lot_number) ?>
                                                    </div>
                                                    <div class="mt-1 text-[11px] text-cyan-100/90" data-lot-rule-label>
                                                        <?= e($lotGroupSize > 1 ? 'Lot kelompok · '.$lotRuleLabel : $lotRuleLabel) ?>
                                                    </div>
                                                <?php elseif ($participant->verification_status === 'verified'): ?>
                                                    <div class="mt-1 text-xs text-slate-400" data-lot-placeholder>Nomor lot belum diambil</div>
                                                <?php endif; ?>
                                                <div class="mt-1 text-xs text-slate-400"><?= e($participant->nik ?: '-') ?></div>
                                            </td>
                                            <td class="px-3 py-3 text-sm text-slate-300">
                                                <span class="break-normal"><?= e($participant->district?->name ?? '-') ?></span>
                                            </td>
                                            <td class="px-3 py-3 text-sm text-slate-300">
                                                <span class="break-normal"><?= e($participant->category?->name ?? '-') ?></span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <?php
                                                $statusLabel = match ($participant->verification_status) {
                                                    'verified' => 'Terverifikasi',
                                                    'submitted' => 'Menunggu',
                                                    'rejected' => 'Ditolak',
                                                    default => 'Draf',
                                                };
                                                $statusClass = match ($participant->verification_status) {
                                                    'verified' => 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200',
                                                    'submitted' => 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200',
                                                    'rejected' => 'border-rose-400/20 bg-rose-400/10 text-rose-100',
                                                    default => 'border-slate-600 bg-slate-800/80 text-slate-300',
                                                };
                                                $officialMandateRejected = in_array($user?->role, ['official', 'pendamping'], true)
                                                    && $user?->district?->mandate_status === 'rejected';
                                                $canEditParticipant = ! (
                                                    (in_array($user?->role, ['official', 'pendamping'], true)
                                                    && $participant->verification_status === 'verified')
                                                    || $officialMandateRejected
                                                );
                                                $canEditParticipant = $canEditParticipant && (! $isOfficialUser || $officialEditOpen);
                                                $canDeleteParticipant = $user?->role === 'admin'
                                                    || (
                                                        $participantDeleteOpen
                                                        && (
                                                            $user?->role === 'panitia'
                                                            || (
                                                                in_array($user?->role, ['official', 'pendamping'], true)
                                                                && in_array($participant->verification_status, ['submitted', 'rejected'], true)
                                                            )
                                                        )
                                                    );
                                                $usesOfficialDeleteCopy = in_array($user?->role, ['official', 'pendamping'], true);
                                                ?>
                                                <span class="inline-flex w-fit max-w-full items-center justify-center rounded-full border px-2.5 py-1.5 text-[11px] font-semibold uppercase leading-none tracking-[0.12em] whitespace-nowrap <?= e($statusClass) ?>">
                                                    <?= e($statusLabel) ?>
                                                </span>
                                                <?php if ($participant->verification_notes): ?>
                                                    <div class="mt-2 text-xs text-slate-400"><?= e($participant->verification_notes) ?></div>
                                                <?php endif; ?>
                                                <?php if (filled($participant->lot_number)): ?>
                                                    <?php
                                                        $lotGroupSize = $participant->category
                                                            ? app(\App\Http\Controllers\PageController::class)->categoryLotGroupSize($participant->category, (string) $participant->gender)
                                                            : 1;
                                                        $lotRuleLabel = $participant->category
                                                            ? app(\App\Http\Controllers\PageController::class)->categoryLotRuleLabel($participant->category, (string) $participant->gender)
                                                            : '1 peserta = 1 nomor lot';
                                                    ?>
                                                    <div class="mt-2 inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100" data-lot-chip>
                                                        Lot <?= e($participant->lot_number) ?>
                                                    </div>
                                                    <div class="mt-2 text-[11px] text-cyan-100/90" data-lot-rule-label>
                                                        <?= e($lotGroupSize > 1 ? 'Lot kelompok · '.$lotRuleLabel : $lotRuleLabel) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php $usesMaqra = $participant->category ? app(\App\Http\Controllers\PageController::class)->categoryUsesMaqra($participant->category) : false; ?>
                                                <?php if ($usesMaqra && filled($participant->latestMaqraDraw?->maqraPackage?->maqra_code ?? null)): ?>
                                                    <?php
                                                        $latestMaqraRound = (string) ($participant->latestMaqraDraw?->round_label ?? 'Penyisihan');
                                                        $maqraLabel = trim((string) preg_replace('/^(Tilawah|Tahfizh|Tafsir|Fahmil)\s*-\s*/u', '', (string) ($participant->latestMaqraDraw?->maqraPackage?->title ?? '')));
                                                        $maqraLabel = $maqraLabel !== '' ? (str_starts_with($maqraLabel, 'QS') ? $maqraLabel : 'QS '.$maqraLabel) : '-';
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
                                                        <?= e($latestMaqraRound) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="grid gap-1.5">
                                                    <a href="<?= e(route('participants.show', array_merge(['participant' => $participant], request()->query()))) ?>" class="secondary-button rounded-xl px-2.5 py-2 text-[11px] leading-tight text-center">
                                                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                                        Lihat Detail
                                                    </a>
                                                    <?php if ($canDrawMaqra && $participant->verification_status === 'verified' && $participant->category && app(\App\Http\Controllers\PageController::class)->categoryUsesMaqra($participant->category)): ?>
                                                        <?php
                                                            $participantMaqraRound = (string) ($participant->latestMaqraDraw?->round_label ?? 'Penyisihan');
                                                            $participantMaqraUsesDistrictSharing = app(\App\Http\Controllers\PageController::class)->categoryMaqraUsesDistrictSharing($participant->category);
                                                        ?>
                                                        <a href="<?= e(route('participants.maqra.draw', $participant).'?autofullscreen=1&round='.urlencode($participantMaqraRound)) ?>" data-maqra-launcher class="secondary-button rounded-xl border-fuchsia-300/30 bg-fuchsia-400/10 px-2.5 py-2 text-[11px] leading-tight text-center text-fuchsia-100 hover:border-fuchsia-200/50">
                                                            <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                            <?= e($participantMaqraUsesDistrictSharing ? 'Ambil Maqra Regu' : 'Ambil Maqra') ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($canEditParticipant): ?>
                                                        <a href="<?= e(route('participants.edit', $participant)) ?>" class="secondary-button rounded-xl px-2.5 py-2 text-[11px] leading-tight text-center">
                                                            <?= mtq_icon('id-card', 'h-4 w-4') ?>
                                                            Edit
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="inline-flex rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-2 text-[11px] font-semibold text-emerald-100">
                                                            <?= e($officialMandateRejected ? 'Mandat Ditolak' : 'Terkunci') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($canDeleteParticipant): ?>
                                                        <form method="POST" action="<?= e(route('participants.archive', $participant)) ?>" <?= $usesOfficialDeleteCopy ? 'data-swal-confirm data-swal-title="Hapus data peserta?" data-swal-text="Data akan dipindahkan ke arsip admin dan dapat dipanggil kembali jika diperlukan." data-swal-confirm="Ya, hapus" data-swal-cancel="Batal"' : '' ?>>
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-2.5 py-2 text-[11px] leading-tight text-center text-rose-100 hover:border-rose-300/40">
                                                                <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                                <?= e($usesOfficialDeleteCopy ? 'Hapus' : 'Arsipkan') ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <?php if ($canVerify): ?>
                                                <td class="px-3 py-3">
                                                    <div class="grid gap-1.5">
                                                        <a href="<?= e(route('participants.show', array_merge(['participant' => $participant], request()->query()))) ?>" class="secondary-button w-full justify-center rounded-xl px-2.5 py-2 text-[11px] leading-tight text-center">
                                                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                            Cek Berkas
                                                        </a>
                                                        <?php if ($participant->verification_status === 'verified'): ?>
                                                            <a href="<?= e(route('participants.cv', $participant)) ?>" class="secondary-button w-full justify-center rounded-xl px-2.5 py-2 text-[11px] leading-tight text-center">
                                                                <?= mtq_icon('download', 'h-4 w-4') ?>
                                                                Download CV
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($participantsPaginator instanceof \Illuminate\Pagination\LengthAwarePaginator && $participantsPaginator->hasPages()): ?>
                        <div class="mt-6 rounded-[1.5rem] border border-slate-700/80 bg-slate-950/60 px-4 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm text-slate-300">
                                    Menampilkan <span class="font-semibold text-white"><?= e($participantsPaginator->firstItem() ?? 0) ?></span>
                                    sampai <span class="font-semibold text-white"><?= e($participantsPaginator->lastItem() ?? 0) ?></span>
                                    dari <span class="font-semibold text-white"><?= e($participantsPaginator->total()) ?></span> peserta
                                </div>
                                <div class="text-sm text-slate-400">
                                    Halaman <?= e($participantsPaginator->currentPage()) ?> dari <?= e($participantsPaginator->lastPage()) ?> · <?= e($participantsPerPage) ?> data per halaman
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <a
                                    href="<?= e($participantsPaginator->previousPageUrl() ?: '#') ?>"
                                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition <?= $participantsPaginator->onFirstPage() ? 'cursor-not-allowed border-slate-700 bg-slate-900/40 text-slate-500' : 'border-slate-600 bg-slate-900/80 text-slate-100 hover:border-cyan-300/30 hover:bg-cyan-400/10 hover:text-white' ?>"
                                    aria-disabled="<?= $participantsPaginator->onFirstPage() ? 'true' : 'false' ?>"
                                    <?= $participantsPaginator->onFirstPage() ? 'tabindex="-1"' : '' ?>
                                >
                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                    Previous
                                </a>

                                <div class="inline-flex items-center rounded-full border border-slate-700 bg-slate-950/80 px-4 py-2 text-sm font-semibold text-slate-200">
                                    <?= e($participantsPaginator->currentPage()) ?> / <?= e($participantsPaginator->lastPage()) ?>
                                </div>

                                <a
                                    href="<?= e($participantsPaginator->nextPageUrl() ?: '#') ?>"
                                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition <?= $participantsPaginator->hasMorePages() ? 'border-slate-600 bg-slate-900/80 text-slate-100 hover:border-cyan-300/30 hover:bg-cyan-400/10 hover:text-white' : 'cursor-not-allowed border-slate-700 bg-slate-900/40 text-slate-500' ?>"
                                    aria-disabled="<?= $participantsPaginator->hasMorePages() ? 'false' : 'true' ?>"
                                    <?= $participantsPaginator->hasMorePages() ? '' : 'tabindex="-1"' ?>
                                >
                                    Next
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <script>
        (function () {
            function flashElement(element) {
                if (!element) {
                    return;
                }

                const originalClassName = element.className;
                element.classList.add('ring-2', 'ring-emerald-300/80', 'bg-emerald-400/5');
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

            function updateLotBadges(payload) {
                if (!payload || !payload.participant_id || !payload.lot_number) {
                    return;
                }

                const rows = payload.district_shared && payload.district_id && payload.category_id
                    ? document.querySelectorAll(`[data-participant-row][data-category-id="${payload.category_id}"][data-district-id="${payload.district_id}"]`)
                    : document.querySelectorAll(`[data-participant-row][data-participant-id="${payload.participant_id}"]`);

                rows.forEach((row) => {
                    const verificationBadge = row.querySelector('[data-verification-badge]');
                    if (verificationBadge) {
                        verificationBadge.textContent = 'Terverifikasi';
                        verificationBadge.className = 'inline-flex w-fit max-w-full items-center justify-center rounded-full border px-2.5 py-1.5 text-[11px] font-semibold uppercase leading-none tracking-[0.12em] whitespace-nowrap border-emerald-400/20 bg-emerald-400/10 text-emerald-200';
                    }

                    const nameCell = row.children?.[1];
                    const chipSelectors = '[data-lot-chip]';
                    const ruleSelectors = '[data-lot-rule-label]';
                    const placeholderSelectors = '[data-lot-placeholder]';
                    const lotRuleLabel = payload.lot_rule_label || '1 peserta = 1 nomor lot';

                    const chips = row.querySelectorAll(chipSelectors);
                    if (chips.length > 0) {
                        chips.forEach((chip) => {
                            chip.textContent = `Lot ${payload.lot_number}`;
                        });
                    } else if (nameCell) {
                        const nameBlock = nameCell.querySelector('.break-normal.font-semibold.leading-snug.text-white');
                        if (nameBlock) {
                            const chip = document.createElement('div');
                            chip.className = 'mt-1 inline-flex rounded-full border border-cyan-300/20 bg-cyan-400/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100';
                            chip.setAttribute('data-lot-chip', '');
                            chip.textContent = `Lot ${payload.lot_number}`;
                            nameBlock.insertAdjacentElement('afterend', chip);
                        }
                    }

                    row.querySelectorAll(ruleSelectors).forEach((ruleLabel) => {
                        ruleLabel.textContent = lotRuleLabel;
                    });

                    row.querySelectorAll(placeholderSelectors).forEach((placeholder) => {
                        placeholder.remove();
                    });

                    flashElement(row);
                });
            }

            function updateMaqraBadges(payload) {
                if (!payload || !payload.participant_id || !payload.maqra_code) {
                    return;
                }

                const rows = payload.district_shared && payload.district_id && payload.category_id
                    ? document.querySelectorAll(`[data-participant-row][data-category-id="${payload.category_id}"][data-district-id="${payload.district_id}"]`)
                    : document.querySelectorAll(`[data-participant-row][data-participant-id="${payload.participant_id}"]`);
                const maqraLabel = payload.maqra_label || formatMaqraLabel(payload.maqra_title || payload.maqra_code || 'Paket Maqra');
                const roundLabel = payload.maqra_round_label || payload.maqra_round || 'Penyisihan';

                rows.forEach((row) => {
                    const nameCell = row.children?.[1];
                    if (!nameCell) {
                        return;
                    }

                    const maqraChip = row.querySelector('[data-maqra-chip]');
                    if (maqraChip) {
                        maqraChip.textContent = maqraLabel;
                    } else {
                        const titleBlock = nameCell.querySelector('.break-normal.font-semibold.leading-snug.text-white');
                        const nikBlock = nameCell.querySelector('.mt-1.text-xs.text-slate-400:last-of-type') || nameCell.querySelector('.mt-1.text-xs.text-slate-400');
                        if (titleBlock) {
                            const chip = document.createElement('div');
                            chip.className = 'mt-2 inline-flex rounded-full border border-fuchsia-300/20 bg-fuchsia-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-fuchsia-100';
                            chip.setAttribute('data-maqra-chip', '');
                            chip.textContent = maqraLabel;
                            if (nikBlock && nikBlock.parentNode === nameCell) {
                                nikBlock.insertAdjacentElement('beforebegin', chip);
                            } else {
                                titleBlock.insertAdjacentElement('afterend', chip);
                            }
                        }
                    }

                    const roundLine = row.querySelector('[data-maqra-round-label]');
                    if (roundLine) {
                        roundLine.textContent = roundLabel;
                    } else if (nameCell) {
                        const chip = row.querySelector('[data-maqra-chip]');
                        if (chip) {
                            const line = document.createElement('div');
                            line.className = 'mt-1 text-[11px] text-fuchsia-100/90';
                            line.setAttribute('data-maqra-round-label', '');
                            line.textContent = roundLabel;
                            chip.insertAdjacentElement('afterend', line);
                        }
                    }

                    const statusChip = row.querySelector('[data-maqra-status-chip]');
                    if (statusChip) {
                        statusChip.textContent = 'Sudah diambil';
                        statusChip.className = 'inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100';
                    }

                    flashElement(row);
                });
            }

            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin) {
                    return;
                }

                const payload = event.data;
                if (!payload) {
                    return;
                }

                if (payload.type === 'participant.lot.updated') {
                    updateLotBadges(payload);
                }

                if (payload.type === 'participant.maqra.updated') {
                    updateMaqraBadges(payload);
                }
            });

            document.querySelectorAll('[data-maqra-launcher]').forEach((launcher) => {
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
        })();
    </script>
</body>
</html>
