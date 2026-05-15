<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$adminDashboard = $adminDashboard ?? ['enabled' => false, 'branch_recap' => collect(), 'quick_exports' => [], 'ops_stats' => []];
$officialDashboard = $officialDashboard ?? ['enabled' => false, 'district' => null, 'mandate_alert' => null, 'participant_alerts' => collect(), 'status_breakdown' => [], 'needs_attention' => collect()];
$participantDashboard = $participantDashboard ?? ['enabled' => false, 'profile' => null, 'latest_score' => '0.00', 'average_score' => '0.00', 'next_schedule' => null];
$dashboardNotices = collect($dashboardNotices ?? [])->values();
$leaders = $leaders ?? [];
$userProfilePhotoUrl = $user?->profilePhotoUrl();
$userInitials = $user?->profileInitials() ?? 'U';
$realtimeState = [
    'leaders' => $leaders,
    'participantProfileId' => $participantDashboard['profile']?->id,
    'participantLatestScore' => $participantDashboard['latest_score'] ?? '0.00',
    'participantAverageScore' => $participantDashboard['average_score'] ?? '0.00',
    'registrationSummary' => $registrationSummary ?? ['title' => 'Masa pendaftaran MTQ', 'label' => '-', 'message' => '-', 'open_at' => null, 'close_at' => null, 'is_open' => false, 'total_registered' => 0, 'tone' => 'warning'],
    'registrationDistrictCounts' => $registrationDistrictCounts ?? [],
    'summaryEndpoint' => route('dashboard.realtime-summary'),
    'forcePasswordChange' => (bool) ($mustChangePassword ?? false),
];
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'dashboard');
$canSyncSilatarUser = filled($user?->nomor_induk);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Dashboard') ?></title>
    <style>[x-cloak]{display:none!important;}</style>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="dashboardRealtime(<?= e(json_encode($realtimeState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)">
        <div class="hero-orb hero-orb-cyan right-[-8rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]" x-bind:class="forcePasswordChange ? 'pointer-events-none select-none blur-[1px]' : ''">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-[1.15rem] border border-cyan-200/30 bg-transparent p-2 shadow-[0_14px_30px_-18px_rgba(125,211,252,0.45)]">
                            <img src="<?= e(asset('images/emtq-resmi.webp')) ?>" alt="Logo resmi e-MTQ" class="h-full w-full object-contain">
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white"><?= e($user?->roleLabel()) ?> Workspace</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <div class="flex flex-col items-center gap-4 text-center">
                        <?php if ($userProfilePhotoUrl): ?>
                            <img src="<?= e($userProfilePhotoUrl) ?>" alt="Foto profil <?= e($user?->name) ?>" class="h-16 w-16 rounded-[1.25rem] border border-cyan-300/20 object-cover shadow-[0_18px_40px_-24px_rgba(34,211,238,0.45)]">
                        <?php else: ?>
                            <div class="flex h-16 w-16 items-center justify-center rounded-[1.25rem] border border-cyan-300/20 bg-cyan-400/10 text-lg font-black tracking-[0.08em] text-cyan-100 shadow-[0_18px_40px_-24px_rgba(34,211,238,0.45)]">
                                <?= e($userInitials) ?>
                            </div>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <p class="section-kicker">Status Pengguna</p>
                            <h2 class="mt-2 text-xl font-bold text-white"><?= e($user?->name) ?></h2>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Akses aktif sebagai <?= e($user?->roleLabel()) ?> dengan tampilan kerja yang disesuaikan.</p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                            Online
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="<?= e(route('dashboard.user-sync')) ?>">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <button
                                    type="submit"
                                    class="secondary-button inline-flex h-9 w-9 items-center justify-center rounded-full p-0"
                                    <?= $canSyncSilatarUser ? '' : 'disabled' ?>
                                    title="<?= e($canSyncSilatarUser ? 'Sinkronkan data user dari API SILATAR.' : 'Akun ini belum memiliki NIP atau nomor induk untuk sinkronisasi SILATAR.') ?>"
                                    aria-label="Sinkronkan data user dari SILATAR"
                                >
                                    <?= mtq_icon('refresh-cw', 'h-4 w-4') ?>
                                </button>
                            </form>
                            <button
                                type="button"
                                class="secondary-button inline-flex h-9 w-9 items-center justify-center rounded-full p-0"
                                x-on:click="showPasswordModal = true"
                                title="Ganti password akun"
                                aria-label="Ganti password akun"
                            >
                                <?= mtq_icon('key', 'h-4 w-4') ?>
                            </button>
                        </div>
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                    <div class="mt-8 grid gap-3">
                        <div class="data-card">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Mode Kerja</p>
                            <p class="mt-2 text-sm font-semibold text-white">Tampilan Operasional</p>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Tampilan dikondisikan agar nyaman untuk akses panjang dan pemantauan cepat.</p>
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
                <?php if (session('status')): ?>
                    <section class="space-y-3">
                        <div class="rounded-[1.5rem] border border-emerald-400/18 bg-emerald-400/10 px-5 py-4 text-sm leading-6 text-emerald-100">
                            <?= e(session('status')) ?>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if (session('warning')): ?>
                    <section class="space-y-3">
                        <div class="rounded-[1.5rem] border border-amber-400/18 bg-amber-400/10 px-5 py-4 text-sm leading-6 text-amber-100">
                            <?= e(session('warning')) ?>
                        </div>
                    </section>
                <?php endif; ?>

                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Area Pengguna</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Selamat datang, <?= e($user?->name) ?></h2>
                            <p class="mt-2 text-sm text-slate-300"><?= e($rolePanel['headline'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="inline-flex items-center gap-3 rounded-full border border-cyan-400/16 bg-slate-950/50 px-3 py-2">
                            <?php if ($userProfilePhotoUrl): ?>
                                <img src="<?= e($userProfilePhotoUrl) ?>" alt="Avatar <?= e($user?->name) ?>" class="h-10 w-10 rounded-full border border-cyan-300/20 object-cover">
                            <?php else: ?>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full border border-cyan-300/20 bg-cyan-400/10 text-sm font-black text-cyan-100">
                                    <?= e($userInitials) ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-left">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500"><?= e($user?->roleLabel()) ?></p>
                            <p class="text-sm font-semibold text-white"><?= e($user?->name) ?></p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Aktif
                    </div>
                </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Peserta</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($stats['participants']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('layers') ?></div><p class="mt-4 text-sm text-slate-400">Golongan</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($stats['categories']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('clock') ?></div><p class="mt-4 text-sm text-slate-400">Sesi Hari Ini</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($stats['today_sessions']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('chart') ?></div><p class="mt-4 text-sm text-slate-400">Rata-rata Nilai</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($stats['average_score']) ?></p></div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <div class="relative overflow-hidden rounded-[2rem] border border-amber-300/20 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-6 shadow-[0_26px_80px_-34px_rgba(251,191,36,0.45)]">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-amber-300/80 to-transparent"></div>
                        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-amber-400/10 blur-3xl"></div>
                        <div class="absolute -left-10 bottom-0 h-28 w-28 rounded-full bg-cyan-400/10 blur-3xl"></div>

                        <div class="relative flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="icon-chip ring-1 ring-amber-300/30"><?= mtq_icon('calendar') ?></div>
                                <p class="mt-5 section-kicker text-amber-100/80">Notifikasi Pendaftaran</p>
                                <h2 class="mt-2 text-2xl font-bold text-white" x-text="registrationNoticeTitle"><?= e($registrationSummary['title'] ?? 'Masa pendaftaran MTQ') ?></h2>
                                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300" x-text="registrationNoticeBody"><?= e($registrationSummary['message'] ?? '-') ?></p>
                            </div>
                            <div class="status-pill border-amber-300/30 bg-amber-400/10 text-amber-100" x-text="registrationStatusLabel"><?= e($registrationSummary['label'] ?? '-') ?></div>
                        </div>

                        <div class="relative mt-6 space-y-4">
                            <div class="data-card border border-amber-300/25 bg-gradient-to-r from-amber-400/12 via-orange-400/10 to-slate-900 px-4 py-4 shadow-[0_18px_50px_-30px_rgba(251,191,36,0.65)] transition-all duration-300" x-bind:class="registrationCountdownFlash ? 'scale-[1.01] shadow-[0_22px_60px_-28px_rgba(251,191,36,0.8)]' : 'scale-100'">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip border border-amber-300/25 bg-amber-300/10 text-amber-100"><?= mtq_icon('clock') ?></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] uppercase tracking-[0.24em] text-amber-100/70">Countdown</p>
                                        <div class="mt-3 flex w-full flex-nowrap gap-2 overflow-hidden">
                                            <span class="inline-flex min-w-[110px] flex-1 items-center justify-center rounded-2xl border border-amber-200/20 bg-amber-300/12 px-3 py-3 text-center text-lg font-black text-amber-50 tabular-nums shadow-inner shadow-amber-950/20" x-text="`${registrationCountdownParts.days} hari`">0 hari</span>
                                            <span class="inline-flex min-w-[110px] flex-1 items-center justify-center rounded-2xl border border-slate-200/10 bg-slate-950/40 px-3 py-3 text-center text-lg font-black text-slate-100 tabular-nums" x-text="`${registrationCountdownParts.hours} jam`">0 jam</span>
                                            <span class="inline-flex min-w-[110px] flex-1 items-center justify-center rounded-2xl border border-slate-200/10 bg-slate-950/40 px-3 py-3 text-center text-lg font-black text-slate-100 tabular-nums" x-text="`${registrationCountdownParts.minutes} menit`">0 menit</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="data-card border border-cyan-300/15 bg-gradient-to-br from-cyan-400/10 via-sky-400/10 to-slate-900 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Sudah Mendaftar</p>
                                    <p class="mt-1 text-2xl font-black text-white sm:text-3xl" x-text="registrationTotalRegistered"><?= e($registrationSummary['total_registered'] ?? 0) ?></p>
                                </div>
                                <div class="data-card border border-emerald-300/15 bg-gradient-to-br from-emerald-400/10 via-cyan-400/10 to-slate-900 px-4 py-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Status</p>
                                    <p class="mt-1 text-lg font-black" x-bind:class="registrationIsOpen ? 'text-emerald-300' : 'text-amber-200'" x-text="registrationIsOpen ? 'Sedang Dibuka' : 'Sudah Ditutup'">
                                        <?= e(($registrationSummary['is_open'] ?? false) ? 'Sedang Dibuka' : 'Sudah Ditutup') ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="section-kicker">Peserta per Kecamatan</p>
                                <p class="mt-1 text-sm text-slate-300">Jumlah peserta yang sudah mendaftar di masing-masing kecamatan.</p>
                            </div>
                            <div class="status-pill" x-text="`${registrationDistrictCounts.length} kecamatan`"><?= e(count($registrationDistrictCounts ?? [])) ?> kecamatan</div>
                        </div>
                        <div class="mt-4 max-h-[22rem] space-y-3 overflow-auto pr-1">
                            <template x-for="district in registrationDistrictCounts" :key="district.district_id">
                                <div class="data-card flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-white" x-text="district.district_name"><?= e($registrationDistrictCounts[0]['district_name'] ?? '') ?></p>
                                        <p class="mt-1 text-xs text-slate-400">Peserta yang sudah mendaftar</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-cyan-200 tabular-nums" x-text="district.total"><?= e($registrationDistrictCounts[0]['total'] ?? 0) ?></p>
                                    </div>
                                </div>
                            </template>
                            <div class="data-card text-sm text-slate-300" x-show="registrationDistrictCounts.length === 0">
                                Belum ada peserta yang sudah mendaftar.
                            </div>
                        </div>
                    </div>
                </section>

                <?php if ($dashboardNotices->isNotEmpty()): ?>
                    <?php
                    $dashboardNotice = $dashboardNotices->first();
                    $dashboardPriority = (string) ($dashboardNotice->priority ?? 'normal');
                    $dashboardNoticeClass = match ($dashboardPriority) {
                        'high' => 'border-amber-300/50 bg-gradient-to-r from-amber-400/20 via-orange-500/18 to-slate-950/70 text-amber-50 shadow-[0_22px_55px_-30px_rgba(251,191,36,0.55)]',
                        'low' => 'border-slate-300/25 bg-gradient-to-r from-slate-700/35 via-slate-800/45 to-slate-950/70 text-slate-100 shadow-[0_22px_55px_-30px_rgba(148,163,184,0.25)]',
                        default => 'border-cyan-300/50 bg-gradient-to-r from-cyan-400/20 via-sky-500/18 to-slate-950/70 text-cyan-50 shadow-[0_22px_55px_-30px_rgba(34,211,238,0.5)]',
                    };
                    ?>
                    <section class="rounded-[2rem] border px-5 py-5 sm:px-6 sm:py-6 <?= e($dashboardNoticeClass) ?>">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-white/70">Area Pengguna</p>
                                <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/70">Notifikasi dashboard</p>
                                <h2 class="mt-2 text-xl font-bold text-white sm:text-2xl"><?= e($dashboardNotice->title) ?></h2>
                                <p class="mt-3 max-w-4xl text-sm leading-6 text-white/90 sm:text-base"><?= e($dashboardNotice->body) ?></p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-white">
                                    <?= e($dashboardNotice->audienceLabel()) ?>
                                </span>
                                <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-white/90">
                                    <?= e(optional($dashboardNotice->published_at)->translatedFormat('d M Y H:i') ?? '-') ?>
                                </span>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($adminDashboard['enabled']): ?>
                    <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                        <div class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-6 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)]">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                                    <p class="mt-5 section-kicker">Cockpit Operasional</p>
                                    <h2 class="mt-2 text-2xl font-bold text-white">Ringkasan cepat untuk admin dan panitia</h2>
                                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">Akses cepat ke rekap, modul penilaian, dan statistik operasional utama tanpa pindah-pindah halaman.</p>
                                </div>
                                <a href="<?= e(route('results.recap')) ?>" class="primary-button rounded-full px-4 py-2">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    Buka Rekap
                                </a>
                            </div>

                            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Pengumuman</p><p class="mt-2 text-2xl font-bold text-white"><?= e($adminDashboard['ops_stats']['announcements'] ?? 0) ?></p></div>
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Jadwal</p><p class="mt-2 text-2xl font-bold text-white"><?= e($adminDashboard['ops_stats']['schedules'] ?? 0) ?></p></div>
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Peserta Verified</p><p class="mt-2 text-2xl font-bold text-emerald-300"><?= e($adminDashboard['ops_stats']['verified_participants'] ?? 0) ?></p></div>
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Entri Nilai</p><p class="mt-2 text-2xl font-bold text-cyan-200"><?= e($adminDashboard['ops_stats']['score_entries'] ?? 0) ?></p></div>
                            </div>

                            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <?php foreach ($adminDashboard['quick_exports'] as $action): ?>
                                    <a href="<?= e($action['href']) ?>" class="secondary-button justify-start rounded-2xl px-4 py-3">
                                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                        <?= e($action['label']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('trophy') ?></div>
                                <div>
                                    <p class="section-kicker">Rekap Cabang</p>
                                    <p class="mt-1 text-sm text-slate-300">Cabang dengan aktivitas penilaian paling kuat saat ini.</p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <?php if (collect($adminDashboard['branch_recap'])->isEmpty()): ?>
                                    <div class="data-card text-sm text-slate-300">Belum ada rekap cabang yang bisa ditampilkan.</div>
                                <?php else: ?>
                                    <?php foreach ($adminDashboard['branch_recap'] as $branch): ?>
                                        <div class="data-card">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p class="font-semibold text-white"><?= e($branch['branch']) ?></p>
                                                    <p class="mt-1 text-xs text-slate-400"><?= e($branch['category_total']) ?> golongan | <?= e($branch['participant_total']) ?> peserta | <?= e($branch['score_entries']) ?> entri nilai</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-lg font-bold text-cyan-200"><?= e($branch['average_score']) ?></p>
                                                    <p class="text-xs text-slate-400">Rata-rata cabang</p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($officialDashboard['enabled']): ?>
                    <?php if ($officialDashboard['mandate_alert'] || collect($officialDashboard['participant_alerts'])->isNotEmpty()): ?>
                        <section class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
                            <?php if ($officialDashboard['mandate_alert']): ?>
                                <?php
                                $mandateAlert = $officialDashboard['mandate_alert'];
                                $mandateAlertClass = match ($mandateAlert['level'] ?? 'info') {
                                    'danger' => 'border-rose-400/24 bg-rose-400/10',
                                    'warning' => 'border-amber-400/24 bg-amber-400/10',
                                    default => 'border-cyan-400/24 bg-cyan-400/10',
                                };
                                $mandateIconClass = match ($mandateAlert['level'] ?? 'info') {
                                    'danger' => 'text-rose-200',
                                    'warning' => 'text-amber-200',
                                    default => 'text-cyan-200',
                                };
                                ?>
                                <div class="rounded-[2rem] border p-6 <?= e($mandateAlertClass) ?>">
                                    <div class="flex items-start gap-3">
                                        <div class="icon-chip <?= e($mandateIconClass) ?>"><?= mtq_icon('book-open') ?></div>
                                        <div class="min-w-0">
                                            <p class="section-kicker">Notifikasi Mandat</p>
                                            <h2 class="mt-2 text-xl font-bold text-white"><?= e($mandateAlert['title']) ?></h2>
                                            <p class="mt-3 text-sm leading-7 text-slate-200"><?= e($mandateAlert['message']) ?></p>
                                            <a href="<?= e($mandateAlert['href']) ?>" class="secondary-button mt-4 rounded-full px-4 py-2">
                                                <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                                Buka Pendaftaran
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="glass-card rounded-[2rem] p-6">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('bell') ?></div>
                                    <div>
                                        <p class="section-kicker">Notifikasi Perbaikan</p>
                                        <p class="mt-1 text-sm text-slate-300">Peserta yang ditolak atau perlu Anda cek ulang segera.</p>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-3">
                                    <?php if (collect($officialDashboard['participant_alerts'])->isEmpty()): ?>
                                        <div class="data-card text-sm text-slate-300">Belum ada peserta yang ditolak. Fokus utama saat ini ada pada peserta yang masih menunggu verifikasi.</div>
                                    <?php else: ?>
                                        <?php foreach ($officialDashboard['participant_alerts'] as $alert): ?>
                                            <div class="data-card flex flex-wrap items-center justify-between gap-4 border border-rose-400/14 bg-rose-400/6">
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-white"><?= e($alert['name']) ?></p>
                                                    <p class="mt-1 text-xs text-slate-400"><?= e($alert['category']) ?></p>
                                                    <p class="mt-2 text-sm leading-6 text-rose-100"><?= e($alert['message']) ?></p>
                                                </div>
                                                <a href="<?= e($alert['href']) ?>" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                                    Perbaiki
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                        <div class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-6 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)]">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="icon-chip"><?= mtq_icon('id-card') ?></div>
                                    <p class="mt-5 section-kicker">Ringkasan Kecamatan</p>
                                    <h2 class="mt-2 text-2xl font-bold text-white"><?= e($officialDashboard['district']?->name ?? 'Kecamatan Belum Dipilih') ?></h2>
                                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">Panel ini memusatkan data peserta kecamatan Anda, terutama berkas yang perlu perbaikan dan pengiriman ulang verifikasi.</p>
                                </div>
                                <a href="<?= e(route('participants.index')) ?>" class="primary-button rounded-full px-4 py-2">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    Buka Pendaftaran
                                </a>
                            </div>

                            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Draf</p><p class="mt-2 text-2xl font-bold text-white"><?= e($officialDashboard['status_breakdown']['draft'] ?? 0) ?></p></div>
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Menunggu</p><p class="mt-2 text-2xl font-bold text-cyan-200"><?= e($officialDashboard['status_breakdown']['submitted'] ?? 0) ?></p></div>
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Terverifikasi</p><p class="mt-2 text-2xl font-bold text-emerald-300"><?= e($officialDashboard['status_breakdown']['verified'] ?? 0) ?></p></div>
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Ditolak</p><p class="mt-2 text-2xl font-bold text-rose-200"><?= e($officialDashboard['status_breakdown']['rejected'] ?? 0) ?></p></div>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('check-circle') ?></div>
                                <div>
                                    <p class="section-kicker">Prioritas Official</p>
                                    <p class="mt-1 text-sm text-slate-300">Titik tindak lanjut yang perlu Anda cek hari ini.</p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <div class="data-card">
                                    <p class="text-sm leading-6 text-slate-200">Peserta dengan status <strong class="text-rose-200">ditolak</strong> perlu perbaikan data atau unggah ulang dokumen.</p>
                                </div>
                                <div class="data-card">
                                    <p class="text-sm leading-6 text-slate-200">Peserta dengan status <strong class="text-cyan-200">menunggu</strong> sedang menanti verifikasi panitia.</p>
                                </div>
                                <div class="data-card">
                                    <p class="text-sm leading-6 text-slate-200">Gunakan halaman pendaftaran untuk membuka detail peserta dan kirim ulang berkas bila perlu.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('bell') ?></div>
                            <div>
                                <p class="section-kicker">Perlu Tindak Lanjut</p>
                                <h2 class="mt-1 text-2xl font-bold text-white">Peserta kecamatan yang perlu dicek</h2>
                            </div>
                        </div>
                        <div class="mt-4 space-y-3">
                            <?php if (collect($officialDashboard['needs_attention'])->isEmpty()): ?>
                                <div class="data-card text-sm text-slate-300">Belum ada peserta yang memerlukan tindak lanjut. Semua berkas kecamatan ini sedang aman.</div>
                            <?php else: ?>
                                <?php foreach ($officialDashboard['needs_attention'] as $participant): ?>
                                    <?php
                                    $statusLabel = match ($participant->verification_status) {
                                        'rejected' => 'Ditolak',
                                        'submitted' => 'Menunggu',
                                        default => ucfirst((string) $participant->verification_status),
                                    };
                                    $statusClass = $participant->verification_status === 'rejected'
                                        ? 'border-rose-400/20 bg-rose-400/10 text-rose-100'
                                        : 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200';
                                    ?>
                                    <div class="data-card flex flex-wrap items-center justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-white"><?= e($participant->name) ?></p>
                                            <p class="mt-1 text-xs text-slate-400"><?= e($participant->category?->name ?? '-') ?><?php if ($participant->verification_notes): ?> | <?= e($participant->verification_notes) ?><?php endif; ?></p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
                                            <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                                <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                                Buka
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($participantDashboard['enabled']): ?>
                    <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                        <div class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-6 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)]">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="icon-chip"><?= mtq_icon('users') ?></div>
                                    <p class="mt-5 section-kicker">Profil Peserta</p>
                                    <h2 class="mt-2 text-2xl font-bold text-white"><?= e($participantDashboard['profile']?->name ?? $user?->name) ?></h2>
                                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                                        <?php if ($participantDashboard['profile']): ?>
                                            <?= e(($participantDashboard['profile']?->category?->branch ?? '-').' | '.($participantDashboard['profile']?->category?->name ?? '-')) ?>
                                        <?php else: ?>
                                            Data peserta belum terhubung ke akun ini. Hubungkan `nomor_induk` akun peserta dengan `NIK` pada data peserta untuk menampilkan dashboard personal.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                    <?= e($participantDashboard['profile']?->verification_status ? ucfirst((string) $participantDashboard['profile']?->verification_status) : 'Profil belum terhubung') ?>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Kecamatan</p><p class="mt-2 text-lg font-bold text-white"><?= e($participantDashboard['profile']?->district?->name ?? '-') ?></p></div>
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">No. Registrasi</p><p class="mt-2 text-lg font-bold text-white"><?= e($participantDashboard['profile']?->registration_number ?? '-') ?></p></div>
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nilai Terakhir</p><p class="mt-2 text-lg font-bold text-cyan-200" x-text="participantLatestScore"><?= e($participantDashboard['latest_score']) ?></p></div>
                                <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Rata-rata Nilai</p><p class="mt-2 text-lg font-bold text-emerald-300" x-text="participantAverageScore"><?= e($participantDashboard['average_score']) ?></p></div>
                                <?php if (! empty($participantDashboard['cv_url'])): ?>
                                    <div class="data-card md:col-span-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">CV Peserta</p>
                                                <p class="mt-2 text-sm leading-6 text-slate-300">Unduh CV PDF yang stylist dan siap dibagikan.</p>
                                            </div>
                                            <a href="<?= e($participantDashboard['cv_url']) ?>" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                                <?= mtq_icon('download', 'h-4 w-4') ?>
                                                Download CV
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="data-card md:col-span-2">
                                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">CV Peserta</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">CV PDF akan tersedia setelah profil peserta Anda dinyatakan terverifikasi.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                                <div>
                                    <p class="section-kicker">Jadwal Berikutnya</p>
                                    <p class="mt-1 text-sm text-slate-300">Agenda lomba terdekat yang perlu Anda antisipasi.</p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <?php if ($participantDashboard['next_schedule']): ?>
                                    <div class="data-card">
                                        <p class="font-semibold text-white"><?= e($participantDashboard['next_schedule']->title) ?></p>
                                        <p class="mt-1 text-xs text-slate-400"><?= e($participantDashboard['next_schedule']->stage) ?> | <?= e($participantDashboard['next_schedule']->venue) ?></p>
                                        <p class="mt-2 text-sm text-slate-300"><?= e(optional($participantDashboard['next_schedule']->starts_at)->format('d M Y H:i')) ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="data-card text-sm text-slate-300">Belum ada jadwal tampil yang tersedia.</div>
                                <?php endif; ?>

                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Status berkas</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-200"><?= e($participantDashboard['profile']?->verification_notes ?? 'Belum ada catatan verifikasi untuk akun ini.') ?></p>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <div class="rounded-[2rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/90 to-blue-950/80 p-6 shadow-[0_22px_65px_-32px_rgba(14,165,233,0.45)]">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-[1.15rem] border border-cyan-200/30 bg-transparent p-2 shadow-[0_14px_30px_-18px_rgba(125,211,252,0.45)]">
                                    <img src="<?= e(asset('images/emtq-resmi.webp')) ?>" alt="Logo resmi e-MTQ" class="h-full w-full object-contain">
                                </div>
                                <p class="mt-5 section-kicker">Fokus Peran</p>
                                <h2 class="mt-2 text-2xl font-bold text-white"><?= e($rolePanel['headline'] ?? '') ?></h2>
                                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300"><?= e($rolePanel['description'] ?? '') ?></p>
                            </div>
                            <a href="<?= e(route('home')) ?>" class="secondary-button rounded-full px-4 py-2">
                                <?= mtq_icon('home', 'h-4 w-4') ?>
                                Beranda
                            </a>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <?php foreach (($rolePanel['actions'] ?? []) as $action): ?>
                                <a href="<?= e($action['href']) ?>" class="primary-button rounded-full px-4 py-2">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    <?= e($action['label']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('check-circle') ?></div>
                            <div>
                                <p class="section-kicker">Checklist Singkat</p>
                                <p class="mt-1 text-sm text-slate-300">Panduan prioritas untuk sesi ini.</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-3">
                            <?php foreach (($rolePanel['focus'] ?? []) as $item): ?>
                                <div class="data-card">
                                    <p class="text-sm leading-6 text-slate-200"><?= e($item) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <section id="jadwal" class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                                <h2 class="text-2xl font-bold text-white">Jadwal</h2>
                            </div>
                            <div class="mt-4 space-y-3">
                                <?php foreach ($schedules as $schedule): ?>
                                    <div class="data-card">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-white"><?= e($schedule->title) ?></p>
                                                <p class="mt-1 text-xs text-slate-400"><?= e($schedule->stage) ?> | <?= e($schedule->venue) ?></p>
                                                <p class="mt-2 text-sm text-slate-300"><?= e(optional($schedule->starts_at)->format('d M Y H:i')) ?></p>
                                            </div>
                                            <?php if (in_array($user?->role, ['admin', 'panitia'], true)): ?>
                                                <form method="POST" action="<?= e(route('broadcast.schedule', $schedule)) ?>">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <button type="submit" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                                        <?= mtq_icon('bell', 'h-4 w-4') ?>
                                                        Siarkan
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                </section>

                <section id="pengumuman" class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('bell') ?></div>
                                <h2 class="text-2xl font-bold text-white">Pengumuman</h2>
                            </div>
                            <div class="mt-4 space-y-3">
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="data-card">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-white"><?= e($announcement->title) ?></p>
                                                <p class="mt-2 text-sm text-slate-300"><?= e($announcement->body) ?></p>
                                            </div>
                                            <?php if (in_array($user?->role, ['admin', 'panitia'], true)): ?>
                                                <form method="POST" action="<?= e(route('broadcast.announcement', $announcement)) ?>">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <button type="submit" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                                        <?= mtq_icon('bell', 'h-4 w-4') ?>
                                                        Siarkan
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                </section>
            </div>
        </div>
        <div
            x-cloak
            x-show="forcePasswordChange || showPasswordModal"
            x-transition.opacity
            x-on:keydown.escape.window="!forcePasswordChange && (showPasswordModal = false)"
            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
        >
            <div class="absolute inset-0 bg-slate-950/85 backdrop-blur-md" x-on:click="!forcePasswordChange && (showPasswordModal = false)"></div>
            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-[1.8rem] border border-cyan-400/18 bg-slate-950 shadow-[0_40px_120px_-40px_rgba(34,211,238,0.5)]" x-on:click.stop>
                <div class="border-b border-white/8 bg-gradient-to-r from-cyan-400/10 to-blue-400/10 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="section-kicker">Keamanan Akun</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Ganti password akun</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Gunakan formulir ini untuk memperbarui password akun Anda. Jika akun wajib ganti password, modal ini akan muncul otomatis saat login.</p>
                        </div>
                        <?php if (! $mustChangePassword): ?>
                            <button
                                type="button"
                                class="secondary-button inline-flex h-10 w-10 items-center justify-center rounded-full p-0"
                                x-on:click="showPasswordModal = false"
                                aria-label="Tutup dialog"
                                title="Tutup dialog"
                            >
                                <?= mtq_icon('x', 'h-4 w-4') ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="p-6">
                    <?php if ($errors->has('password') || $errors->has('password_confirmation')): ?>
                        <div class="mb-4 rounded-2xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm leading-6 text-rose-100">
                            <?= e($errors->first('password') ?: $errors->first('password_confirmation')) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= e(route('dashboard.password.update')) ?>" class="space-y-4">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Password baru</label>
                            <input name="password" type="password" minlength="8" required class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Masukkan password baru">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Ulangi password baru</label>
                            <input name="password_confirmation" type="password" minlength="8" required class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Ulangi password baru">
                        </div>
                        <button type="submit" class="primary-button w-full">
                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                            Simpan Password Baru
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <script>
        function dashboardRealtime(initialState) {
            const parseDate = (value) => {
                if (!value) {
                    return null;
                }

                const date = new Date(value);
                return Number.isNaN(date.getTime()) ? null : date;
            };

            const buildCountdownParts = (ms) => {
                const totalMinutes = Math.max(0, Math.floor(ms / 60000));
                const days = Math.floor(totalMinutes / 1440);
                const hours = Math.floor((totalMinutes % 1440) / 60);
                const minutes = totalMinutes % 60;

                return { days, hours, minutes };
            };

            return {
                mobileNavOpen: false,
                leaders: initialState.leaders ?? [],
                participantProfileId: initialState.participantProfileId ?? null,
                participantLatestScore: initialState.participantLatestScore ?? '0.00',
                participantAverageScore: initialState.participantAverageScore ?? '0.00',
                registrationSummary: initialState.registrationSummary ?? {},
                registrationDistrictCounts: Array.isArray(initialState.registrationDistrictCounts) ? initialState.registrationDistrictCounts : [],
                registrationNoticeTitle: initialState.registrationSummary?.title ?? 'Masa pendaftaran MTQ',
                registrationNoticeBody: initialState.registrationSummary?.message ?? '-',
                registrationStatusLabel: initialState.registrationSummary?.label ?? '-',
                registrationCountdownText: '-',
                registrationCountdownParts: { days: 0, hours: 0, minutes: 0 },
                registrationCountdownFlash: false,
                registrationCountdownFlashTimer: null,
                registrationTotalRegistered: Number(initialState.registrationSummary?.total_registered ?? 0),
                registrationIsOpen: Boolean(initialState.registrationSummary?.is_open ?? false),
                registrationTicker: null,
                summaryTicker: null,
                forcePasswordChange: Boolean(initialState.forcePasswordChange ?? false),
                showPasswordModal: false,
                init() {
                    this.syncRegistrationNotice();
                    window.addEventListener('mtq-score-updated', (event) => {
                        this.applyScoreUpdate(event.detail ?? {});
                    });
                    window.addEventListener('mtq-participant-verification-updated', () => {
                        this.refreshSummary();
                    });

                    this.registrationTicker = window.setInterval(() => {
                        this.syncRegistrationNotice();
                    }, 1000);

                    this.summaryTicker = window.setInterval(() => {
                        this.refreshSummary();
                    }, 60000);

                    window.addEventListener('beforeunload', () => {
                        if (this.registrationTicker) {
                            window.clearInterval(this.registrationTicker);
                        }

                        if (this.summaryTicker) {
                            window.clearInterval(this.summaryTicker);
                        }
                    });
                },
                async applyScoreUpdate(payload) {
                    if (!payload.participant_id) {
                        return;
                    }
                    await this.refreshSummary();
                },
                async refreshSummary() {
                    try {
                        const url = new URL(initialState.summaryEndpoint, window.location.origin);

                        if (this.participantProfileId) {
                            url.searchParams.set('participant_id', this.participantProfileId);
                        }

                        const response = await fetch(url.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            return;
                        }

                        const summary = await response.json();

                        if (Array.isArray(summary.leaders)) {
                            this.leaders = summary.leaders;
                        }

                        if (summary.participant_summary) {
                            this.participantLatestScore = summary.participant_summary.latest_score ?? this.participantLatestScore;
                            this.participantAverageScore = summary.participant_summary.average_score ?? this.participantAverageScore;
                        }

                        if (summary.registration_summary) {
                            this.registrationSummary = summary.registration_summary;
                            this.registrationTotalRegistered = Number(summary.registration_summary.total_registered ?? this.registrationTotalRegistered);
                            this.registrationNoticeTitle = summary.registration_summary.title ?? this.registrationNoticeTitle;
                            this.registrationNoticeBody = summary.registration_summary.message ?? this.registrationNoticeBody;
                            this.registrationStatusLabel = summary.registration_summary.label ?? this.registrationStatusLabel;
                            this.registrationIsOpen = Boolean(summary.registration_summary.is_open ?? this.registrationIsOpen);
                        }

                        if (Array.isArray(summary.registration_district_counts)) {
                            this.registrationDistrictCounts = summary.registration_district_counts;
                        }

                        this.syncRegistrationNotice();
                    } catch (error) {
                        console.warn('Realtime summary refresh failed.', error);
                    }
                },
                syncRegistrationNotice() {
                    const openAt = parseDate(this.registrationSummary?.open_at ?? null);
                    const closeAt = parseDate(this.registrationSummary?.close_at ?? null);
                    const now = new Date();

                    if (openAt && now < openAt) {
                        const diff = openAt.getTime() - now.getTime();
                        this.registrationIsOpen = false;
                        this.registrationNoticeTitle = 'Pendaftaran segera dibuka';
                        this.registrationStatusLabel = 'Menunggu dibuka';
                        this.registrationNoticeBody = `Pendaftaran dibuka pada ${openAt.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}.`;
                        this.updateCountdownState('Mulai dalam', diff);
                        return;
                    }

                    if (openAt && closeAt && now >= openAt && now <= closeAt) {
                        const diff = closeAt.getTime() - now.getTime();
                        this.registrationIsOpen = true;
                        this.registrationNoticeTitle = 'Pendaftaran masih berlangsung';
                        this.registrationStatusLabel = 'Sedang dibuka';
                        this.registrationNoticeBody = `Pendaftaran ditutup pada ${closeAt.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}.`;
                        this.updateCountdownState('Sisa', diff);
                        return;
                    }

                    if (closeAt && now > closeAt) {
                        this.registrationIsOpen = false;
                        this.registrationNoticeTitle = 'Masa pendaftaran selesai';
                        this.registrationStatusLabel = 'Sudah ditutup';
                        this.registrationNoticeBody = `Pendaftaran ditutup pada ${closeAt.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}.`;
                        this.registrationCountdownText = 'Waktu pendaftaran telah habis';
                        this.registrationCountdownParts = { days: 0, hours: 0, minutes: 0 };
                        this.registrationCountdownFlash = false;
                        return;
                    }

                    this.registrationIsOpen = Boolean(this.registrationSummary?.is_open ?? false);
                    this.registrationNoticeTitle = this.registrationSummary?.title ?? 'Masa pendaftaran MTQ';
                    this.registrationStatusLabel = this.registrationSummary?.label ?? '-';
                    this.registrationNoticeBody = this.registrationSummary?.message ?? '-';
                    this.registrationCountdownText = '-';
                    this.registrationCountdownParts = { days: 0, hours: 0, minutes: 0 };
                    this.registrationCountdownFlash = false;
                },
                updateCountdownState(prefix, diff) {
                    this.registrationCountdownParts = buildCountdownParts(diff);
                    this.registrationCountdownText = prefix;

                    if (this.registrationCountdownFlashTimer) {
                        window.clearTimeout(this.registrationCountdownFlashTimer);
                    }

                    this.registrationCountdownFlash = true;
                    this.registrationCountdownFlashTimer = window.setTimeout(() => {
                        this.registrationCountdownFlash = false;
                    }, 260);
                },
            };
        }
    </script>
</body>
</html>
