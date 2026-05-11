<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$logs = $logs ?? collect();
$filters = $filters ?? [];
$actions = $actions ?? collect();
$logStats = $logStats ?? ['total' => 0, 'today' => 0, 'participant' => 0, 'lot_maqra' => 0];
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'application.logs');
$roleLabels = [
    'admin' => 'Admin',
    'panitia' => 'Panitia',
    'official' => 'Official',
    'pendamping' => 'Pendamping',
    'peserta' => 'Peserta',
];
$actionLabels = [
    'auth.login' => 'Login',
    'auth.logout' => 'Logout',
    'participant.created' => 'Daftar Peserta',
    'participant.updated' => 'Update Peserta',
    'participant.archived' => 'Arsip Peserta',
    'participant.restored' => 'Pulihkan Peserta',
    'participant.permanently_deleted' => 'Hapus Permanen Arsip',
    'participant.verified' => 'Verifikasi Peserta',
    'participant.rejected' => 'Tolak Peserta',
    'participant.submitted' => 'Kembalikan Menunggu',
    'participant.lot.assigned' => 'Ambil Nomor Lot',
    'participant.lot.reused' => 'Buka Nomor Lot',
    'participant.lot.updated' => 'Ubah Nomor Lot',
    'participant.lot.reset' => 'Hapus Nomor Lot',
    'participant.lot.swapped' => 'Tukar Nomor Lot',
    'participant.maqra.assigned' => 'Ambil Maqra',
    'participant.maqra.reused' => 'Buka Maqra',
    'participant.maqra.reset' => 'Hapus Maqra',
    'participant.maqra.swapped' => 'Tukar Maqra',
    'mandate.uploaded' => 'Upload Surat Mandat',
    'mandate.submitted' => 'Mandat Menunggu',
    'mandate.verified' => 'Verifikasi Mandat',
    'mandate.rejected' => 'Tolak Mandat',
    'announcement.created' => 'Buat Pengumuman',
    'announcement.deleted' => 'Hapus Pengumuman',
    'announcement.broadcasted' => 'Siarkan Pengumuman',
    'schedule.created' => 'Buat Jadwal',
    'schedule.status_updated' => 'Ubah Status Jadwal',
    'schedule.deleted' => 'Hapus Jadwal',
    'schedule.broadcasted' => 'Siarkan Jadwal',
    'category.lot_settings.updated' => 'Setting Nomor Lot',
    'maqra.package.created' => 'Buat Paket Maqra',
    'maqra.package.updated' => 'Update Paket Maqra',
    'maqra.package.deleted' => 'Hapus Paket Maqra',
    'maqra.package.imported' => 'Import Paket Maqra',
    'gallery.photo.created' => 'Upload Galeri',
    'gallery.photo.deleted' => 'Hapus Galeri',
    'scoring.settings.updated' => 'Update Setting Nilai',
    'scoring.score.created' => 'Input Nilai',
    'document.settings.updated' => 'Update Dokumen',
    'user.official.created' => 'Buat Official',
    'user.official.updated' => 'Update Official',
    'user.official.deleted' => 'Hapus Official',
    'user.committee.created' => 'Buat Panitia',
    'user.committee.updated' => 'Update Panitia',
    'user.committee.access_updated' => 'Ubah Akses Panitia',
    'user.committee.deleted' => 'Hapus Panitia',
];
$formatAction = fn (string $action): string => $actionLabels[$action] ?? str($action)->replace('.', ' ')->title()->toString();
$actionTone = function (string $action): string {
    if (str_contains($action, 'deleted') || str_contains($action, 'reset') || str_contains($action, 'archived') || str_contains($action, 'rejected')) {
        return 'border-rose-300/20 bg-rose-400/10 text-rose-100';
    }

    if (str_contains($action, 'verified') || str_contains($action, 'created') || str_contains($action, 'assigned')) {
        return 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100';
    }

    if (str_contains($action, 'lot') || str_contains($action, 'maqra') || str_contains($action, 'score')) {
        return 'border-cyan-300/20 bg-cyan-400/10 text-cyan-100';
    }

    return 'border-slate-600 bg-slate-900/70 text-slate-200';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Log Aplikasi') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Log Aplikasi</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Audit Trail</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($logStats['total']) ?> Aktivitas</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Semua aktivitas penting tersimpan di database dan bisa difilter untuk pemeriksaan panitia.</p>
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

                <div class="mt-8">
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
                            <p class="section-kicker">Riwayat Aktivitas</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Log aplikasi</h2>
                            <p class="mt-2 text-sm text-slate-300">Pantau siapa melakukan apa, kapan, dan terhadap data apa.</p>
                        </div>
                    </div>
                    <a href="<?= e(route('dashboard')) ?>" class="secondary-button">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        Dashboard
                    </a>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('layers') ?></div><p class="mt-4 text-sm text-slate-400">Total Log</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($logStats['total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('clock') ?></div><p class="mt-4 text-sm text-slate-400">Hari Ini</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($logStats['today']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Peserta</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($logStats['participant']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('sparkles') ?></div><p class="mt-4 text-sm text-slate-400">Lot & Maqra</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($logStats['lot_maqra']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('eye') ?></div>
                        <div>
                            <p class="section-kicker">Filter</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Cari aktivitas</h3>
                        </div>
                    </div>

                    <form method="GET" action="<?= e(route('application.logs')) ?>" class="mt-6 grid gap-4 lg:grid-cols-5">
                        <div class="lg:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kata kunci</label>
                            <input name="keyword" type="text" value="<?= e($filters['keyword'] ?? '') ?>" placeholder="User / aktivitas / target / IP" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Aksi</label>
                            <select name="action" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Semua aksi</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?= e($action) ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>><?= e($formatAction((string) $action)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Role</label>
                            <select name="user_role" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Semua role</option>
                                <?php foreach ($roleLabels as $role => $label): ?>
                                    <option value="<?= e($role) ?>" <?= ($filters['user_role'] ?? '') === $role ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Mulai</label>
                            <input name="date_from" type="date" value="<?= e($filters['date_from'] ?? '') ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Sampai</label>
                            <input name="date_to" type="date" value="<?= e($filters['date_to'] ?? '') ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div class="flex items-end gap-3 lg:col-span-4">
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('eye', 'h-4 w-4') ?>
                                Terapkan
                            </button>
                            <a href="<?= e(route('application.logs')) ?>" class="secondary-button">
                                <?= mtq_icon('refresh-cw', 'h-4 w-4') ?>
                                Reset
                            </a>
                        </div>
                    </form>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="section-kicker">Aktivitas Tersimpan</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Riwayat terbaru</h3>
                        </div>
                        <span class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?= e(method_exists($logs, 'total') ? $logs->total() : $logs->count()) ?> log
                        </span>
                    </div>

                    <div class="table-shell mt-6">
                        <table class="min-w-full">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-4">Waktu</th>
                                    <th class="px-5 py-4">User</th>
                                    <th class="px-5 py-4">Aktivitas</th>
                                    <th class="px-5 py-4">Target</th>
                                    <th class="px-5 py-4">IP</th>
                                    <th class="px-5 py-4">Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($logs->isEmpty()): ?>
                                    <tr class="table-row">
                                        <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada log yang sesuai filter.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php
                                        $properties = $log->properties ?? [];
                                        $propertiesJson = $properties
                                            ? json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                            : '';
                                    ?>
                                    <tr class="table-row align-top">
                                        <td class="px-5 py-4 text-sm text-slate-300">
                                            <div class="font-semibold text-white"><?= e(optional($log->created_at)->format('d M Y')) ?></div>
                                            <div class="mt-1 text-xs text-slate-500"><?= e(optional($log->created_at)->format('H:i:s')) ?></div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-white"><?= e($log->user_name ?? $log->actor?->name ?? 'Sistem') ?></div>
                                            <div class="mt-1 inline-flex rounded-full border border-slate-700 bg-slate-900/80 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-300">
                                                <?= e($roleLabels[$log->user_role] ?? ($log->user_role ?: 'system')) ?>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] <?= e($actionTone((string) $log->action)) ?>">
                                                <?= e($formatAction((string) $log->action)) ?>
                                            </span>
                                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-200"><?= e($log->description) ?></p>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-slate-300">
                                            <div class="font-semibold text-white"><?= e($log->subject_name ?: '-') ?></div>
                                            <?php if ($log->subject_type || $log->subject_id): ?>
                                                <div class="mt-1 text-xs text-slate-500"><?= e($log->subject_type ? class_basename($log->subject_type) : 'Target') ?> #<?= e($log->subject_id ?? '-') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-slate-300"><?= e($log->ip_address ?: '-') ?></td>
                                        <td class="px-5 py-4 text-sm text-slate-300">
                                            <?php if ($propertiesJson !== ''): ?>
                                                <details class="max-w-sm rounded-2xl border border-slate-700 bg-slate-950/70 px-3 py-2">
                                                    <summary class="cursor-pointer text-xs font-semibold uppercase tracking-[0.16em] text-cyan-200">Metadata</summary>
                                                    <pre class="mt-3 max-h-64 overflow-auto whitespace-pre-wrap text-xs leading-5 text-slate-300"><?= e($propertiesJson) ?></pre>
                                                </details>
                                            <?php else: ?>
                                                <span class="text-slate-500">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages()): ?>
                        <div class="mt-6 rounded-[1.5rem] border border-slate-700/80 bg-slate-950/60 px-4 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm text-slate-300">
                                    Menampilkan <span class="font-semibold text-white"><?= e($logs->firstItem() ?? 0) ?></span>
                                    sampai <span class="font-semibold text-white"><?= e($logs->lastItem() ?? 0) ?></span>
                                    dari <span class="font-semibold text-white"><?= e($logs->total()) ?></span> log
                                </div>
                                <div class="text-sm text-slate-400">
                                    Halaman <?= e($logs->currentPage()) ?> dari <?= e($logs->lastPage()) ?>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <a href="<?= e($logs->previousPageUrl() ?: '#') ?>" class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition <?= $logs->onFirstPage() ? 'cursor-not-allowed border-slate-700 bg-slate-900/40 text-slate-500' : 'border-slate-600 bg-slate-900/80 text-slate-100 hover:border-cyan-300/30 hover:bg-cyan-400/10 hover:text-white' ?>" aria-disabled="<?= $logs->onFirstPage() ? 'true' : 'false' ?>" <?= $logs->onFirstPage() ? 'tabindex="-1"' : '' ?>>
                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                    Previous
                                </a>
                                <div class="inline-flex items-center rounded-full border border-slate-700 bg-slate-950/80 px-4 py-2 text-sm font-semibold text-slate-200">
                                    <?= e($logs->currentPage()) ?> / <?= e($logs->lastPage()) ?>
                                </div>
                                <a href="<?= e($logs->nextPageUrl() ?: '#') ?>" class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-semibold transition <?= $logs->hasMorePages() ? 'border-slate-600 bg-slate-900/80 text-slate-100 hover:border-cyan-300/30 hover:bg-cyan-400/10 hover:text-white' : 'cursor-not-allowed border-slate-700 bg-slate-900/40 text-slate-500' ?>" aria-disabled="<?= $logs->hasMorePages() ? 'false' : 'true' ?>" <?= $logs->hasMorePages() ? '' : 'tabindex="-1"' ?>>
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
</body>
</html>
