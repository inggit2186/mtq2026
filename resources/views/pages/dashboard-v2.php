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
    'verificationSummary' => $verificationSummary ?? ['title' => 'Perbaikan berkas peserta oleh official', 'label' => '-', 'message' => '-', 'open_at' => null, 'close_at' => null, 'open_at_label' => null, 'close_at_label' => null, 'is_open' => false, 'total_registered' => 0, 'total_verified' => 0, 'tone' => 'warning'],
    'verificationDistrictCounts' => $verificationDistrictCounts ?? [],
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

        <div class="grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)]" x-bind:class="forcePasswordChange ? 'pointer-events-none select-none blur-[1px]' : ''">
            <!-- Sidebar -->
            <aside
                class="console-sidebar fixed inset-y-4 left-4 z-50 w-[300px] rounded-[1.75rem] p-5 transition-all duration-300 lg:static lg:inset-auto lg:block"
                :class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-x-full lg:translate-x-0"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 -translate-x-full lg:translate-x-0"
            >
                <!-- Mobile Backdrop -->
                <div
                    class="fixed inset-0 z-[-1] bg-slate-950/80 backdrop-blur-sm lg:hidden"
                    x-show="mobileNavOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="mobileNavOpen = false"
                ></div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="brand-logo">
                            <img src="<?= e(asset('images/emtq-resmi.webp')) ?>" alt="Logo resmi e-MTQ" class="h-full w-full object-contain">
                        </div>
                        <div>
                            <p class="brand-subtitle">e-MTQ Console</p>
                            <h1 class="brand-title"><?= e($user?->roleLabel()) ?> Workspace</h1>
                        </div>
                    </div>
                    <button type="button" class="sidebar-close-btn" @click="mobileNavOpen = false">
                        <?= mtq_icon('x', 'h-5 w-5') ?>
                    </button>
                </div>

                <div class="sidebar-profile-card lg:block hidden">
                    <div class="flex flex-col items-center gap-4 text-center">
                        <?php if ($userProfilePhotoUrl): ?>
                            <img src="<?= e($userProfilePhotoUrl) ?>" alt="Foto profil <?= e($user?->name) ?>" class="profile-avatar">
                        <?php else: ?>
                            <div class="profile-avatar profile-avatar--initials">
                                <?= e($userInitials) ?>
                            </div>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <p class="profile-status-label">Status Pengguna</p>
                            <h2 class="profile-name"><?= e($user?->name) ?></h2>
                            <p class="profile-role">Akses sebagai <?= e($user?->roleLabel()) ?></p>
                        </div>
                    </div>
                    <div class="profile-actions">
                        <div class="status-indicator">
                            <span class="status-dot"></span>
                            <span class="status-text">Online</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="<?= e(route('dashboard.user-sync')) ?>">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <button
                                    type="submit"
                                    class="icon-action-btn"
                                    <?= $canSyncSilatarUser ? '' : 'disabled' ?>
                                    title="<?= e($canSyncSilatarUser ? 'Sinkronkan data user dari API SILATAR.' : 'Akun ini belum memiliki NIP atau nomor induk untuk sinkronisasi SILATAR.') ?>"
                                    aria-label="Sinkronkan data user dari SILATAR"
                                >
                                    <?= mtq_icon('refresh-cw', 'h-4 w-4') ?>
                                </button>
                            </form>
                            <button
                                type="button"
                                class="icon-action-btn"
                                @click="showPasswordModal = true"
                                title="Ganti password akun"
                                aria-label="Ganti password akun"
                            >
                                <?= mtq_icon('key', 'h-4 w-4') ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile: Simple user info -->
                <div class="sidebar-profile-mobile lg:hidden">
                    <div class="flex items-center gap-3">
                        <?php if ($userProfilePhotoUrl): ?>
                            <img src="<?= e($userProfilePhotoUrl) ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border-2 border-cyan-400/30">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-cyan-500/20 flex items-center justify-center text-cyan-300 font-bold text-sm">
                                <?= e($userInitials) ?>
                            </div>
                        <?php endif; ?>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-white truncate"><?= e($user?->name) ?></p>
                            <p class="text-xs text-slate-400"><?= e($user?->roleLabel()) ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="<?= e(route('dashboard.user-sync')) ?>" class="inline">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="p-2 rounded-lg bg-slate-800/50 hover:bg-slate-700 text-slate-400 hover:text-cyan-400 transition" <?= $canSyncSilatarUser ? '' : 'disabled' ?>>
                                    <?= mtq_icon('refresh-cw', 'h-4 w-4') ?>
                                </button>
                            </form>
                            <button type="button" class="p-2 rounded-lg bg-slate-800/50 hover:bg-slate-700 text-slate-400 hover:text-cyan-400 transition" @click="showPasswordModal = true">
                                <?= mtq_icon('key', 'h-4 w-4') ?>
                            </button>
                        </div>
                    </div>
                </div>

                <nav class="sidebar-nav" aria-label="Navigasi utama">
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="sidebar-footer">
                    <div class="sidebar-mode-card">
                        <div class="mode-icon">
                            <?= mtq_icon('spark') ?>
                        </div>
                        <div>
                            <p class="mode-label">Mode Kerja</p>
                            <p class="mode-title">Tampilan Operasional</p>
                        </div>
                    </div>
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

                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    class="fixed top-4 left-4 z-[100] flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-500 shadow-lg shadow-cyan-500/40 transition-all duration-300 lg:hidden"
                    :class="mobileNavOpen ? 'opacity-0 scale-75 pointer-events-none' : 'opacity-100 scale-100'"
                    @click="mobileNavOpen = true"
                    aria-label="Buka menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>
                    </svg>
                </button>

                <!-- Session Alerts -->
                <?php if (session('status')): ?>
                    <div class="dash-alert dash-alert--success">
                        <?= mtq_icon('check-circle', 'h-5 w-5') ?>
                        <span><?= e(session('status')) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (session('warning')): ?>
                    <div class="dash-alert dash-alert--warning">
                        <?= mtq_icon('info', 'h-5 w-5') ?>
                        <span><?= e(session('warning')) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Hero Welcome Section -->
                <section class="dash-hero">
                    <div class="dash-hero-bg"></div>
                    <div class="dash-hero-content">
                        <div class="flex items-center gap-4">
                            <div class="dash-hero-icon">
                                <?php if ($userProfilePhotoUrl): ?>
                                    <img src="<?= e($userProfilePhotoUrl) ?>" alt="Avatar" class="h-full w-full rounded-full object-cover">
                                <?php else: ?>
                                    <span class="text-2xl font-black text-cyan-300"><?= e($userInitials) ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="dash-hero-greeting">Selamat datang kembali</p>
                                <h1 class="dash-hero-title"><?= e($user?->name) ?></h1>
                                <p class="dash-hero-subtitle"><?= e($rolePanel['headline'] ?? 'Workspace '.ucfirst($user?->role ?? 'user')) ?></p>
                            </div>
                        </div>
                        <div class="dash-hero-badge">
                            <?= mtq_icon('zap', 'h-4 w-4') ?>
                            <span><?= e($user?->roleLabel()) ?></span>
                        </div>
                    </div>
                </section>

                <!-- Quick Stats -->
                <section class="dash-stats-grid">
                    <div class="dash-stat-card dash-stat-card--primary">
                        <div class="dash-stat-icon">
                            <?= mtq_icon('users', 'h-6 w-6') ?>
                        </div>
                        <div class="dash-stat-content">
                            <p class="dash-stat-value"><?= e($stats['participants']) ?></p>
                            <p class="dash-stat-label">Total Peserta</p>
                        </div>
                        <div class="dash-stat-trend dash-stat-trend--up">
                            <?= mtq_icon('arrow-up', 'h-3 w-3') ?>
                        </div>
                    </div>

                    <div class="dash-stat-card">
                        <div class="dash-stat-icon">
                            <?= mtq_icon('layers', 'h-6 w-6') ?>
                        </div>
                        <div class="dash-stat-content">
                            <p class="dash-stat-value"><?= e($stats['categories']) ?></p>
                            <p class="dash-stat-label">Golongan</p>
                        </div>
                    </div>

                    <div class="dash-stat-card">
                        <div class="dash-stat-icon">
                            <?= mtq_icon('calendar', 'h-6 w-6') ?>
                        </div>
                        <div class="dash-stat-content">
                            <p class="dash-stat-value"><?= e($stats['today_sessions']) ?></p>
                            <p class="dash-stat-label">Sesi Hari Ini</p>
                        </div>
                    </div>

                    <div class="dash-stat-card dash-stat-card--accent">
                        <div class="dash-stat-icon">
                            <?= mtq_icon('chart', 'h-6 w-6') ?>
                        </div>
                        <div class="dash-stat-content">
                            <p class="dash-stat-value"><?= e($stats['average_score']) ?></p>
                            <p class="dash-stat-label">Rata-rata Nilai</p>
                        </div>
                    </div>
                </section>

                <!-- Dashboard Notices -->
                <?php if ($dashboardNotices->isNotEmpty()): ?>
                    <?php
                    $dashboardNotice = $dashboardNotices->first();
                    $dashboardPriority = (string) ($dashboardNotice->priority ?? 'normal');
                    $noticeIcon = match ($dashboardPriority) {
                        'high' => 'alert-triangle',
                        'low' => 'info',
                        default => 'bell',
                    };
                    ?>
                    <div class="dash-notice dash-notice--<?= e($dashboardPriority) ?>">
                        <div class="dash-notice-icon">
                            <?= mtq_icon($noticeIcon, 'h-5 w-5') ?>
                        </div>
                        <div class="dash-notice-content">
                            <h3 class="dash-notice-title"><?= e($dashboardNotice->title) ?></h3>
                            <p class="dash-notice-text"><?= e($dashboardNotice->body) ?></p>
                        </div>
                        <span class="dash-notice-badge"><?= e($dashboardNotice->audienceLabel()) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Two Column Layout -->
                <div class="grid gap-6 lg:grid-cols-2">

                    <!-- Quick Actions -->
                    <section class="dash-section">
                        <div class="dash-section-header">
                            <?= mtq_icon('zap', 'h-5 w-5') ?>
                            <h2 class="dash-section-title">Aksi Cepat</h2>
                        </div>
                        <div class="dash-actions-grid">
                            <?php if ($user?->role === 'admin' || $user?->role === 'panitia'): ?>
                                <a href="<?= e(route('scoring')) ?>" class="dash-action-card dash-action-card--primary">
                                    <div class="dash-action-icon">
                                        <?= mtq_icon('chart', 'h-6 w-6') ?>
                                    </div>
                                    <div class="dash-action-content">
                                        <p class="dash-action-title">Penilaian</p>
                                        <p class="dash-action-desc">Input &elola nilai</p>
                                    </div>
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                </a>
                            <?php endif; ?>

                            <?php if ($user?->role === 'admin' || $user?->role === 'panitia' || $user?->role === 'official' || $user?->role === 'pendamping'): ?>
                                <a href="<?= e(route('participants.index')) ?>" class="dash-action-card">
                                    <div class="dash-action-icon">
                                        <?= mtq_icon('id-card', 'h-6 w-6') ?>
                                    </div>
                                    <div class="dash-action-content">
                                        <p class="dash-action-title">Pendaftaran</p>
                                        <p class="dash-action-desc">Daftarkan peserta</p>
                                    </div>
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                </a>
                            <?php endif; ?>

                            <a href="<?= e(route('participants.list')) ?>" class="dash-action-card">
                                <div class="dash-action-icon">
                                    <?= mtq_icon('users', 'h-6 w-6') ?>
                                </div>
                                <div class="dash-action-content">
                                    <p class="dash-action-title">Data Peserta</p>
                                    <p class="dash-action-desc">Lihat & cari data</p>
                                </div>
                                <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                            </a>

                            <?php if ($user?->role === 'admin' || $user?->role === 'panitia'): ?>
                                <a href="<?= e(route('results.recap')) ?>" class="dash-action-card">
                                    <div class="dash-action-icon">
                                        <?= mtq_icon('file-text', 'h-6 w-6') ?>
                                    </div>
                                    <div class="dash-action-content">
                                        <p class="dash-action-title">Rekap Nilai</p>
                                        <p class="dash-action-text">Lihat hasil penilaian</p>
                                    </div>
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Upcoming Schedules -->
                    <section class="dash-section">
                        <div class="dash-section-header">
                            <?= mtq_icon('calendar', 'h-5 w-5') ?>
                            <h2 class="dash-section-title">Jadwal Mendatang</h2>
                            <a href="<?= e(route('dashboard').'#jadwal') ?>" class="dash-section-link">Lihat semua</a>
                        </div>
                        <div class="dash-schedule-list">
                            <?php if (empty($schedules) || count($schedules) === 0): ?>
                                <div class="dash-empty">
                                    <?= mtq_icon('calendar', 'h-10 w-10') ?>
                                    <p>Belum ada jadwal yang dijadwalkan</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (collect($schedules)->take(4) as $schedule): ?>
                                    <div class="dash-schedule-item">
                                        <div class="dash-schedule-time">
                                            <span class="dash-schedule-date"><?= e(optional($schedule->starts_at)->format('d M')) ?></span>
                                            <span class="dash-schedule-hour"><?= e(optional($schedule->starts_at)->format('H:i')) ?></span>
                                        </div>
                                        <div class="dash-schedule-content">
                                            <p class="dash-schedule-title"><?= e($schedule->title) ?></p>
                                            <p class="dash-schedule-venue"><?= e($schedule->venue) ?></p>
                                        </div>
                                        <?php if (in_array($user?->role, ['admin', 'panitia'], true)): ?>
                                            <form method="POST" action="<?= e(route('broadcast.schedule', $schedule)) ?>">
                                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                <button type="submit" class="dash-schedule-broadcast" title="Siarkan jadwal">
                                                    <?= mtq_icon('bell', 'h-4 w-4') ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <!-- Announcements -->
                <?php if (!empty($announcements)): ?>
                    <section class="dash-section" id="pengumuman">
                        <div class="dash-section-header">
                            <?= mtq_icon('bell', 'h-5 w-5') ?>
                            <h2 class="dash-section-title">Pengumuman</h2>
                        </div>
                        <div class="dash-announcement-list">
                            <?php foreach (collect($announcements)->take(3) as $announcement): ?>
                                <div class="dash-announcement-item">
                                    <div class="dash-announcement-icon">
                                        <?= mtq_icon('info', 'h-4 w-4') ?>
                                    </div>
                                    <div class="dash-announcement-content">
                                        <h3 class="dash-announcement-title"><?= e($announcement->title) ?></h3>
                                        <p class="dash-announcement-text"><?= e($announcement->body) ?></p>
                                        <p class="dash-announcement-time"><?= e(optional($announcement->published_at)->translatedFormat('d M Y, H:i')) ?></p>
                                    </div>
                                    <?php if (in_array($user?->role, ['admin', 'panitia'], true)): ?>
                                        <form method="POST" action="<?= e(route('broadcast.announcement', $announcement)) ?>">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <button type="submit" class="dash-announcement-broadcast" title="Siarkan">
                                                <?= mtq_icon('send', 'h-4 w-4') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Role Focus Cards -->
                <?php if (!empty($rolePanel['actions']) || !empty($rolePanel['focus'])): ?>
                    <section class="dash-section">
                        <div class="dash-section-header">
                            <?= mtq_icon('target', 'h-5 w-5') ?>
                            <h2 class="dash-section-title">Fokus <?= e(ucfirst($user?->role ?? 'Peran')) ?></h2>
                        </div>
                        <?php if (!empty($rolePanel['actions'])): ?>
                            <div class="dash-focus-actions">
                                <?php foreach ($rolePanel['actions'] as $action): ?>
                                    <a href="<?= e($action['href']) ?>" class="dash-focus-btn">
                                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                        <?= e($action['label']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($rolePanel['focus'])): ?>
                            <div class="dash-focus-list">
                                <?php foreach ($rolePanel['focus'] as $item): ?>
                                    <div class="dash-focus-item">
                                        <?= mtq_icon('check', 'h-4 w-4') ?>
                                        <span><?= e($item) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </div>

        <!-- Password Modal -->
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
                            <p class="mt-2 text-sm leading-6 text-slate-300">Gunakan formulir ini untuk memperbarui password akun Anda.</p>
                        </div>
                        <?php if (!$mustChangePassword): ?>
                            <button
                                type="button"
                                class="secondary-button inline-flex h-10 w-10 items-center justify-center rounded-full p-0"
                                x-on:click="showPasswordModal = false"
                                aria-label="Tutup dialog"
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
                            <input name="password" type="password" minlength="8" required class="dash-input" placeholder="Masukkan password baru">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Ulangi password baru</label>
                            <input name="password_confirmation" type="password" minlength="8" required class="dash-input" placeholder="Ulangi password baru">
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
                if (!value) return null;
                const date = new Date(value);
                return Number.isNaN(date.getTime()) ? null : date;
            };

            return {
                mobileNavOpen: false,
                leaders: initialState.leaders ?? [],
                participantProfileId: initialState.participantProfileId ?? null,
                participantLatestScore: initialState.participantLatestScore ?? '0.00',
                participantAverageScore: initialState.participantAverageScore ?? '0.00',
                verificationSummary: initialState.verificationSummary ?? {},
                verificationDistrictCounts: Array.isArray(initialState.verificationDistrictCounts) ? initialState.verificationDistrictCounts : [],
                forcePasswordChange: Boolean(initialState.forcePasswordChange ?? false),
                showPasswordModal: false,
                verificationTicker: null,
                init() {
                    window.addEventListener('mtq-score-updated', () => this.refreshSummary());
                    window.addEventListener('beforeunload', () => {
                        if (this.verificationTicker) window.clearInterval(this.verificationTicker);
                    });
                },
                async refreshSummary() {
                    try {
                        const url = new URL(initialState.summaryEndpoint, window.location.origin);
                        if (this.participantProfileId) url.searchParams.set('participant_id', this.participantProfileId);
                        const response = await fetch(url.toString(), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (!response.ok) return;
                        const summary = await response.json();
                        if (Array.isArray(summary.leaders)) this.leaders = summary.leaders;
                        if (summary.participant_summary) {
                            this.participantLatestScore = summary.participant_summary.latest_score ?? this.participantLatestScore;
                            this.participantAverageScore = summary.participant_summary.average_score ?? this.participantAverageScore;
                        }
                    } catch (error) {
                        console.warn('Realtime refresh failed.', error);
                    }
                },
            };
        }
    </script>
</body>
</html>

