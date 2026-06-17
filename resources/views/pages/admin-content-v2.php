<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$announcements = $announcements ?? collect();
$schedules = $schedules ?? collect();
$bigScreenUrl = $bigScreenUrl ?? route('big-screen');
$projectorProtocolUrl = $projectorProtocolUrl ?? ('emtq-launch://bigscreen?url='.rawurlencode($bigScreenUrl));
$districtCount = $districtCount ?? 0;
$officialAccessReady = $officialAccessReady ?? false;
$officialAccessSetting = $officialAccessSetting ?? \App\Models\OfficialAccessSetting::currentOrDefault();
$officialAccessDefaults = \App\Models\OfficialAccessSetting::defaults();
$rawOfficialAccess = static function (string $feature) use ($officialAccessSetting, $officialAccessDefaults): bool {
    return (bool) ($officialAccessSetting->getRawOriginal($feature) ?? ($officialAccessDefaults[$feature] ?? true));
};
$maqraCategories = $maqraCategories ?? collect();
$maqraRounds = $maqraRounds ?? collect();
$maqraSchedules = $maqraSchedules ?? collect();
$maqraCategorySchedules = $officialAccessSetting->getAttribute('participant_maqra_category_schedules') ?? [];
if (! is_array($maqraCategorySchedules)) {
    $maqraCategorySchedules = [];
}
$maqraPenyisihanOpen = old('participant_maqra_penyisihan_open', $rawOfficialAccess('participant_maqra_penyisihan_open'));
$maqraFinalOpen = old('participant_maqra_final_open', $rawOfficialAccess('participant_maqra_final_open'));
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'admin.content');
$priorityLabels = [
    'low' => 'Rendah',
    'normal' => 'Normal',
    'high' => 'Tinggi',
];
$announcementAudienceLabels = $announcementAudienceLabels ?? [
    'all' => 'Semua Dashboard',
    'official' => 'Official',
    'panitia' => 'Panitia',
    'official_panitia' => 'Official + Panitia',
];
$priorityClasses = [
    'low' => 'border-slate-500/30 bg-slate-500/10 text-slate-200',
    'normal' => 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200',
    'high' => 'border-amber-400/20 bg-amber-400/10 text-amber-100',
];
$scheduleStatusLabels = [
    'scheduled' => 'Terjadwal',
    'ongoing' => 'Berlangsung',
    'completed' => 'Selesai',
    'postponed' => 'Ditunda',
];
$scheduleFormStatusLabels = [
    'scheduled' => 'Otomatis sesuai jam',
    'postponed' => 'Ditunda',
];
$scheduleStatusClasses = [
    'scheduled' => 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200',
    'ongoing' => 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200',
    'completed' => 'border-slate-500/30 bg-slate-500/10 text-slate-200',
    'postponed' => 'border-rose-400/20 bg-rose-400/10 text-rose-100',
];
$impersonation = session('impersonation', []);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Kelola Konten') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-8rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('bell') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Kelola Konten</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Pusat Siaran</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($user?->name) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Kelola pengumuman, notif dashboard kecil, dan jadwal dari satu halaman, lalu siarkan pembaruan penting ke target yang sesuai.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Admin Broadcast Ready
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Fokus Saat Ini</p>
                        <p class="mt-2 text-sm font-semibold text-white"><?= e($rolePanel['headline'] ?? 'Operasional Konten') ?></p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Gunakan form di kanan untuk menambah konten baru, lalu siarkan hanya ketika informasi sudah final.</p>
                    </div>
                    <a href="<?= e(route('dashboard')) ?>" class="secondary-button w-full">
                        <?= mtq_icon('home', 'h-4 w-4') ?>
                        Kembali ke Dashboard
                    </a>
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
                            <p class="section-kicker">Panel Admin Konten</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Manajemen pengumuman dan jadwal</h2>
                            <p class="mt-2 text-sm text-slate-300">Semua update penting event bisa dibuat, ditinjau, dan disiarkan dari halaman ini.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?= e($announcements->count()) ?> Pengumuman | <?= e($schedules->count()) ?> Jadwal
                        </div>
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('bell') ?></div><p class="mt-4 text-sm text-slate-400">Total Pengumuman</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($announcements->count()) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('calendar') ?></div><p class="mt-4 text-sm text-slate-400">Total Jadwal</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($schedules->count()) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Jadwal Aktif</p><p class="mt-2 text-3xl font-extrabold text-emerald-300"><?= e($schedules->where('status', 'ongoing')->count()) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('clock') ?></div><p class="mt-4 text-sm text-slate-400">Jadwal Mendatang</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($schedules->filter(fn ($schedule) => optional($schedule->starts_at)->isFuture())->count()) ?></p></div>
                </section>

                <?php if ($user?->role === 'admin'): ?>
                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                            <div class="max-w-3xl">
                                <p class="section-kicker">Login Sebagai User</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Masuk ke akun lain berdasarkan ID</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Gunakan fitur ini untuk melihat langsung tampilan dan akses user lain. Admin tetap menyimpan sesi asal supaya bisa kembali kapan saja.</p>
                            </div>
                            <div class="status-pill">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                                Hanya Admin
                            </div>
                        </div>

                        <?php if (filled($impersonation['original_user_id'] ?? null)): ?>
                            <div class="mt-5 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-4 text-sm leading-6 text-amber-100">
                                Saat ini sedang masuk sebagai <span class="font-semibold text-white"><?= e((string) ($impersonation['target_user_name'] ?? auth()->user()?->name ?? '-')) ?></span>.
                                Gunakan tombol kembali di banner atas untuk pulang ke akun admin.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= e(route('admin.impersonate.store')) ?>" class="mt-6 flex flex-wrap items-end gap-3">
                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                            <div class="min-w-0 flex-1">
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">ID User / NIP</label>
                                <input type="text" name="identifier" value="<?= e(old('identifier')) ?>" placeholder="Masukkan ID user atau NIP" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <?php if ($errors->has('identifier')): ?>
                                    <p class="mt-2 text-sm text-rose-300"><?= e($errors->first('identifier')) ?></p>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('key', 'h-4 w-4') ?>
                                Login Sebagai User
                            </button>
                        </form>
                    </section>

                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                            <div class="max-w-3xl">
                                <p class="section-kicker">Sinkronisasi Master Data</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Sinkronkan kecamatan dari SILATAR</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Gunakan tombol ini untuk memperbarui daftar kecamatan e-MTQ agar mengikuti data KUA terbaru dari API SILATAR tanpa harus membuka terminal.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                    <?= e($districtCount) ?> Kecamatan Tersimpan
                                </div>
                                <form method="POST" action="<?= e(route('admin.content.districts.sync')) ?>">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="primary-button">
                                        <?= mtq_icon('refresh', 'h-4 w-4') ?>
                                        Sinkronisasi Kecamatan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                            <div class="max-w-3xl">
                                <p class="section-kicker">Akses Official</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Buka atau tutup fitur official</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Gunakan pengaturan ini untuk mengatur kapan official dapat mendaftarkan peserta, mengubah data peserta, mengupload surat mandat, membuka dokumen peserta, dan membuka masa verifikasi untuk panitia. Admin tetap bisa mengakses semua fitur untuk kebutuhan operasional.</p>
                            </div>
                            <div class="status-pill">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                Konfigurasi Akses
                            </div>
                        </div>

                        <?php if (! $officialAccessReady): ?>
                            <div class="mt-5 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-4 text-sm text-amber-100">
                                Tabel `official_access_settings` belum tersedia. Jalankan `php artisan migrate` agar pengaturan akses official bisa disimpan.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= e(route('admin.content.official-access.update')) ?>" class="mt-6 grid gap-4 lg:grid-cols-2">
                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                            <?php
                                $accessCards = [
                                    [
                                        'key' => 'participant_registration_open',
                                        'title' => 'Pendaftaran Peserta',
                                        'description' => 'Membuka atau menutup form pendaftaran peserta untuk official kecamatan.',
                                    ],
                                    [
                                        'key' => 'participant_edit_open',
                                        'title' => 'Edit Peserta',
                                        'description' => 'Mengizinkan official mengubah data peserta yang sudah didaftarkan lewat pengaturan akses admin.',
                                    ],
                                    [
                                        'key' => 'participant_delete_open',
                                        'title' => 'Hapus Peserta',
                                        'description' => 'Mengizinkan official dan panitia menghapus peserta yang sudah didaftarkan ke arsip admin. Admin tetap dapat menghapus kapan saja.',
                                    ],
                                    [
                                        'key' => 'mandate_upload_open',
                                        'title' => 'Upload Surat Mandat',
                                        'description' => 'Mengizinkan official mengupload atau mengganti surat mandat kecamatan.',
                                    ],
                                    [
                                        'key' => 'participant_documents_open',
                                        'title' => 'Dokumen Peserta',
                                        'description' => 'Mengizinkan official membuka pratinjau dan unduh dokumen peserta.',
                                    ],
                                    [
                                        'key' => 'participant_verification_open',
                                        'title' => 'Verifikasi Peserta',
                                        'description' => 'Membuka atau menutup form verifikasi peserta bagi panitia.',
                                    ],
                                    [
                                        'key' => 'participant_lot_open',
                                        'title' => 'Ambil Nomor Lot',
                                        'description' => 'Membuka atau menutup pengambilan nomor lot peserta bagi panitia.',
                                    ],
                                ];
                            ?>

                            <?php foreach ($accessCards as $card): ?>
                                <?php $checked = $rawOfficialAccess($card['key']); ?>
                                <label class="rounded-[1.5rem] border border-slate-700/80 bg-slate-950/60 p-5 transition hover:border-cyan-400/30">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-white"><?= e($card['title']) ?></p>
                                            <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($card['description']) ?></p>
                                        </div>
                                        <input type="checkbox" name="<?= e($card['key']) ?>" value="1" <?= $checked ? 'checked' : '' ?> class="mt-1 h-5 w-5 rounded border-slate-600 bg-slate-950 text-cyan-400 focus:ring-cyan-300/30">
                                    </div>
                                </label>
                            <?php endforeach; ?>

                            <div class="lg:col-span-2 flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-cyan-400/14 bg-cyan-400/8 px-5 py-4">
                                <p class="text-sm leading-6 text-slate-200">Kalau pendaftaran atau verifikasi ditutup, user yang terdampak masih bisa login ke dashboard tetapi tombol aksi yang terkait akan diblok oleh sistem.</p>
                                <button type="submit" class="primary-button">
                                    <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                    Simpan Akses Official
                                </button>
                            </div>
                        </form>
                    </section>
                    <?php endif; ?>

                    <?php if ($officialAccessReady): ?>
                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                            <div class="max-w-3xl">
                                <p class="section-kicker">Akses Maqra</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Pengaturan jadwal pengambilan maqra</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-300">Atur jadwal pengambilan maqra per babak dan golongan. 1 golongan bisa memiliki lebih dari 1 sesi jadwal.</p>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <?php if ($user?->role === 'admin'): ?>
                                <button type="button" @click="$dispatch('open-modal', 'maqra-rounds-modal')" class="secondary-button">
                                    <?= mtq_icon('flag', 'h-4 w-4') ?>
                                    Kelola Babak
                                </button>
                                <button type="button" @click="$dispatch('open-modal', 'maqra-add-modal')" class="primary-button">
                                    <?= mtq_icon('plus-circle', 'h-4 w-4') ?>
                                    Tambah Jadwal
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($user?->role === 'admin'): ?>
                        <!-- Filter & List Jadwal Maqra -->
                        <div class="mt-6 space-y-4">
                            <?php
                            $filterRound = request('filter_round');
                            $filterCategory = request('filter_category');
                            $filterStatus = request('filter_status');
                            $filteredSchedules = $maqraSchedules->filter(function ($schedule) use ($filterRound, $filterCategory, $filterStatus) {
                                if ($filterRound && $schedule->round_id != $filterRound) return false;
                                if ($filterCategory && $schedule->category_id != $filterCategory) return false;
                                if ($filterStatus) {
                                    if ($filterStatus === 'active' && !$schedule->isCurrentlyOpen()) return false;
                                    if ($filterStatus === 'scheduled' && $schedule->status !== 'scheduled') return false;
                                    if ($filterStatus === 'closed' && $schedule->status !== 'closed') return false;
                                }
                                return true;
                            });
                            ?>
                            <form method="GET" action="<?= e(route('admin.content')) ?>" class="flex flex-wrap gap-3 items-end">
                                <input type="hidden" name="section" value="maqra">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">Babak</label>
                                    <select name="filter_round" class="rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-slate-100 outline-none focus:border-fuchsia-400">
                                        <option value="">Semua Babak</option>
                                        <?php foreach ($maqraRounds as $round): ?>
                                            <option value="<?= $round->id ?>" <?= $filterRound == $round->id ? 'selected' : '' ?>><?= e($round->name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">Golongan</label>
                                    <select name="filter_category" class="rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-slate-100 outline-none focus:border-fuchsia-400">
                                        <option value="">Semua Golongan</option>
                                        <?php foreach ($maqraCategories as $cat): ?>
                                            <option value="<?= $cat->id ?>" <?= $filterCategory == $cat->id ? 'selected' : '' ?>><?= e(trim((string) $cat->branch.' - '.(string) $cat->name)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">Status</label>
                                    <select name="filter_status" class="rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-slate-100 outline-none focus:border-fuchsia-400">
                                        <option value="">Semua Status</option>
                                        <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Sedang Buka</option>
                                        <option value="scheduled" <?= $filterStatus === 'scheduled' ? 'selected' : '' ?>>Terjadwal</option>
                                        <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Selesai/Ditutup</option>
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="secondary-button py-2">Filter</button>
                                    <?php if ($filterRound || $filterCategory || $filterStatus): ?>
                                    <a href="<?= e(route('admin.content')) ?>" class="secondary-button py-2">Reset</a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <?php if ($filteredSchedules->isEmpty()): ?>
                                <div class="rounded-[1.5rem] border border-dashed border-slate-700 bg-slate-950/50 p-8 text-center">
                                    <p class="text-slate-400">Belum ada jadwal maqra. Klik "Tambah Jadwal" untuk membuat jadwal baru.</p>
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-700/80">
                                                <th class="text-left py-3 px-4 font-semibold text-slate-400">Babak</th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-400">Golongan</th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-400">Tanggal</th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-400">Waktu</th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-400">Lot</th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-400">Akses</th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-400">Status</th>
                                                <?php if ($user?->role === 'admin'): ?>
                                                <th class="text-right py-3 px-4 font-semibold text-slate-400">Aksi</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($filteredSchedules as $schedule): ?>
                                            <tr class="border-b border-slate-800/60 hover:bg-slate-800/20">
                                                <td class="py-3 px-4">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-fuchsia-500/20 text-fuchsia-300">
                                                        <?= e($schedule->round?->name ?? '-') ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-white font-medium">
                                                    <?= e($schedule->category?->name ?? '-') ?>
                                                    <?php if ($schedule->category?->branch): ?>
                                                    <span class="text-slate-500 text-xs ml-1">(<?= e($schedule->category->branch) ?>)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 px-4 text-slate-300">
                                                    <?= $schedule->open_at ? $schedule->open_at->format('d M Y') : '-' ?>
                                                </td>
                                                <td class="py-3 px-4 text-slate-300">
                                                    <?= $schedule->open_at ? $schedule->open_at->format('H:i') : '' ?>
                                                    -
                                                    <?= $schedule->close_at ? $schedule->close_at->format('H:i') : '' ?>
                                                </td>
                                                <td class="py-3 px-4 text-slate-300">
                                                    <?= $schedule->lot_min ?> - <?= $schedule->lot_max ?>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <?php
                                                    $accessColor = match($schedule->draw_access_by ?? 'official_only') {
                                                        'panitia_only' => 'sky',
                                                        'official_only' => 'violet',
                                                        'both' => 'amber',
                                                        default => 'slate',
                                                    };
                                                    $accessLabel = match($schedule->draw_access_by ?? 'official_only') {
                                                        'panitia_only' => 'Panitia',
                                                        'official_only' => 'Official',
                                                        'both' => 'Panitia & Official',
                                                        default => 'Official',
                                                    };
                                                    ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $accessColor ?>-500/20 text-<?= $accessColor ?>-300">
                                                        <?= e($accessLabel) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <?php
                                                    $statusColor = match($schedule->status) {
                                                        'open' => 'emerald',
                                                        'scheduled' => 'amber',
                                                        'closed' => 'slate',
                                                        default => 'slate',
                                                    };
                                                    ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $statusColor ?>-500/20 text-<?= $statusColor ?>-300">
                                                        <span class="h-1.5 w-1.5 rounded-full <?= $schedule->status === 'open' ? 'bg-emerald-400 animate-pulse' : 'bg-'.$statusColor.'-400' ?>"></span>
                                                        <?= e($schedule->status_label) ?>
                                                    </span>
                                                </td>
                                                <?php if ($user?->role === 'admin'): ?>
                                                <td class="py-3 px-4 text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <form method="POST" action="<?= e(route('admin.content.maqra-schedules.toggle', $schedule)) ?>" class="inline">
                                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                            <button type="submit" class="p-1.5 rounded-lg <?= $schedule->is_active ? 'text-emerald-400 hover:bg-emerald-500/20' : 'text-slate-500 hover:bg-slate-500/20' ?>" title="<?= $schedule->is_active ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                                <?= mtq_icon($schedule->is_active ? 'toggle-right' : 'toggle-left', 'h-5 w-5') ?>
                                                            </button>
                                                        </form>
                                                        <button type="button" @click="$dispatch('open-modal', 'maqra-edit-modal-<?= $schedule->id ?>')" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-500/20 hover:text-white" title="Edit">
                                                            <?= mtq_icon('edit', 'h-5 w-5') ?>
                                                        </button>
                                                        <form method="POST" action="<?= e(route('admin.content.maqra-schedules.destroy', $schedule)) ?>" class="inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:bg-rose-500/20 hover:text-rose-400" title="Hapus">
                                                                <?= mtq_icon('trash', 'h-5 w-5') ?>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-xs text-slate-500"><?= $filteredSchedules->count() ?> jadwal ditampilkan</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                        <div class="max-w-3xl">
                            <p class="section-kicker">Projector Mode</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Satu paket untuk layar besar arena</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-300">Tombol ini memakai launcher lokal di komputer panitia. Setelah dipasang sekali, klik "Aktifkan Projector" akan memanggil mode <span class="font-semibold text-cyan-200">extend</span> lalu membuka halaman tampilan besar penuh layar.</p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="<?= e($projectorProtocolUrl) ?>" class="primary-button">
                                <?= mtq_icon('zap', 'h-4 w-4') ?>
                                Aktifkan Projector
                            </a>
                            <a href="<?= e($bigScreenUrl) ?>" target="_blank" rel="noreferrer" class="secondary-button">
                                <?= mtq_icon('eye', 'h-4 w-4') ?>
                                Pratinjau Layar Besar
                            </a>
                            <a href="<?= e(route('admin.content.projector-installer')) ?>" class="secondary-button">
                                <?= mtq_icon('upload', 'h-4 w-4') ?>
                                Unduh Launcher
                            </a>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div class="data-card">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Langkah 1</p>
                            <p class="mt-2 font-semibold text-white">Pasang launcher lokal</p>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Unduh `install-emtq-projector.ps1`, lalu jalankan sekali di komputer operator atau laptop panitia.</p>
                        </div>
                        <div class="data-card">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Langkah 2</p>
                            <p class="mt-2 font-semibold text-white">Sambungkan layar</p>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Pastikan layar kedua sudah terdeteksi Windows sebelum tombol aktivasi ditekan.</p>
                        </div>
                        <div class="data-card">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Langkah 3</p>
                            <p class="mt-2 font-semibold text-white">Klik tombol aktivasi</p>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Browser akan meminta izin membuka aplikasi eksternal, lalu launcher menjalankan mode extend dan membuka halaman layar besar.</p>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-2">
                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('bell') ?></div>
                            <div>
                                <p class="section-kicker">Pengumuman Baru</p>
                                <h3 class="mt-1 text-2xl font-bold text-white">Tulis pengumuman atau notif dashboard</h3>
                            </div>
                        </div>

                        <form method="POST" action="<?= e(route('admin.content.announcements.store')) ?>" class="mt-6 space-y-4">
                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Judul</label>
                                <input name="title" type="text" value="<?= e(old('title')) ?>" placeholder="Contoh: Verifikasi Tahap I Dibuka" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Isi pengumuman</label>
                                <textarea name="body" rows="5" placeholder="Tuliskan informasi resmi yang ingin diumumkan..." class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"><?= e(old('body')) ?></textarea>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Prioritas</label>
                                    <select name="priority" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                        <?php foreach ($priorityLabels as $priority => $label): ?>
                                            <option value="<?= e($priority) ?>" <?= old('priority', 'normal') === $priority ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Target dashboard</label>
                                    <select name="audience" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                        <?php foreach ($announcementAudienceLabels as $audience => $label): ?>
                                            <option value="<?= e($audience) ?>" <?= old('audience', 'all') === $audience ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Waktu publikasi</label>
                                    <input name="published_at" type="datetime-local" value="<?= e(old('published_at')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="primary-button">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    Simpan Pengumuman
                                </button>
                                <a href="#pengumuman-list" class="secondary-button">
                                    <?= mtq_icon('bell', 'h-4 w-4') ?>
                                    Lihat Daftar
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                            <div>
                                <p class="section-kicker">Jadwal Baru</p>
                                <h3 class="mt-1 text-2xl font-bold text-white">Tambahkan sesi dan agenda</h3>
                            </div>
                        </div>

                        <form method="POST" action="<?= e(route('admin.content.schedules.store')) ?>" class="mt-6 space-y-4">
                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Judul sesi</label>
                                    <input name="title" type="text" value="<?= e(old('title')) ?>" placeholder="Contoh: Babak Penyisihan Tilawah Dewasa" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Tahap / babak</label>
                                    <input name="stage" type="text" value="<?= e(old('stage')) ?>" placeholder="Penyisihan / Final / Verifikasi" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Lokasi</label>
                                    <input name="venue" type="text" value="<?= e(old('venue')) ?>" placeholder="Panggung utama / Aula / Masjid" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Mulai</label>
                                    <input name="starts_at" type="datetime-local" value="<?= e(old('starts_at')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Selesai</label>
                                    <input name="ends_at" type="datetime-local" value="<?= e(old('ends_at')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Status</label>
                                    <select name="status" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                        <?php foreach ($scheduleFormStatusLabels as $status => $label): ?>
                                            <option value="<?= e($status) ?>" <?= old('status', 'scheduled') === $status ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan</label>
                                    <textarea name="notes" rows="4" placeholder="Catatan tambahan untuk panitia atau peserta..." class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"><?= e(old('notes')) ?></textarea>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="primary-button">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    Simpan Jadwal
                                </button>
                                <a href="#jadwal-list" class="secondary-button">
                                    <?= mtq_icon('calendar', 'h-4 w-4') ?>
                                    Lihat Daftar
                                </a>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-2">
                    <div id="pengumuman-list" class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('bell') ?></div>
                            <div>
                                <p class="section-kicker">Daftar Pengumuman</p>
                                <h3 class="mt-1 text-2xl font-bold text-white">Publikasi terbaru</h3>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <?php if ($announcements->isEmpty()): ?>
                                <div class="data-card text-sm text-slate-300">Belum ada pengumuman yang tersimpan.</div>
                            <?php else: ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <?php
                                    $priority = (string) ($announcement->priority ?? 'normal');
                                    $priorityClass = $priorityClasses[$priority] ?? $priorityClasses['normal'];
                                    $priorityLabel = $priorityLabels[$priority] ?? ucfirst($priority);
                                    $audienceLabel = $announcement->audienceLabel();
                                    ?>
                                    <div class="data-card">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="font-semibold text-white"><?= e($announcement->title) ?></p>
                                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?= e($priorityClass) ?>"><?= e($priorityLabel) ?></span>
                                                    <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-slate-200"><?= e($audienceLabel) ?></span>
                                                </div>
                                                <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($announcement->body) ?></p>
                                                <p class="mt-3 text-xs text-slate-500">
                                                    Oleh <?= e($announcement->author?->name ?? 'Sistem') ?> | <?= e(optional($announcement->published_at)->format('d M Y H:i') ?? '-') ?>
                                                </p>
                                            </div>
                                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                                <form method="POST" action="<?= e(route('broadcast.announcement', $announcement)) ?>">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <button type="submit" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                                        <?= mtq_icon('bell', 'h-4 w-4') ?>
                                                        Siarkan
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= e(route('admin.content.announcements.destroy', $announcement)) ?>" data-swal-confirm data-swal-title="Hapus pengumuman?" data-swal-text="Pengumuman <?= e($announcement->title) ?> akan dihapus dari daftar." data-swal-confirm="Ya, hapus" data-swal-cancel="Batal">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <button type="submit" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-3 py-2 text-xs text-rose-100 hover:border-rose-300/40" title="Hapus pengumuman" aria-label="Hapus pengumuman <?= e($announcement->title) ?>">
                                                        <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="jadwal-list" class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                            <div>
                                <p class="section-kicker">Daftar Jadwal</p>
                                <h3 class="mt-1 text-2xl font-bold text-white">Agenda dan sesi terbaru</h3>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <?php if ($schedules->isEmpty()): ?>
                                <div class="data-card text-sm text-slate-300">Belum ada jadwal yang tersimpan.</div>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                    <?php
                                    $status = (string) ($schedule->status ?? 'scheduled');
                                    $statusClass = $scheduleStatusClasses[$status] ?? $scheduleStatusClasses['scheduled'];
                                    $statusLabel = $scheduleStatusLabels[$status] ?? ucfirst($status);
                                    ?>
                                    <div class="data-card">
                                        <div class="space-y-4">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-start justify-between gap-3">
                                                    <p class="min-w-0 flex-1 text-base font-semibold leading-6 text-white"><?= e($schedule->title) ?></p>
                                                    <span class="inline-flex shrink-0 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
                                                </div>
                                                <div class="mt-3 space-y-2 text-sm leading-6">
                                                    <p class="text-slate-400"><?= e($schedule->stage) ?> | <?= e($schedule->venue) ?></p>
                                                    <p class="text-slate-300">
                                                        <?= e(optional($schedule->starts_at)->format('d M Y H:i') ?? '-') ?>
                                                        <?php if ($schedule->ends_at): ?>
                                                            - <?= e(optional($schedule->ends_at)->format('d M Y H:i')) ?>
                                                        <?php endif; ?>
                                                    </p>
                                                    <?php if ($schedule->notes): ?>
                                                        <p class="text-slate-300"><?= e($schedule->notes) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2 border-t border-slate-800/80 pt-4">
                                                <form method="POST" action="<?= e(route('admin.content.schedules.status', $schedule)) ?>" class="flex flex-wrap items-center gap-2" data-loading-text="Mengubah status jadwal...">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <select name="status" class="min-w-[7rem] rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-xs font-semibold text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" aria-label="Mode status jadwal <?= e($schedule->title) ?>">
                                                        <option value="scheduled" <?= $status !== 'postponed' ? 'selected' : '' ?>>Otomatis</option>
                                                        <option value="postponed" <?= $status === 'postponed' ? 'selected' : '' ?>>Ditunda</option>
                                                    </select>
                                                    <button type="submit" class="secondary-button rounded-xl px-3 py-2 text-xs" title="Update status jadwal" aria-label="Update status jadwal <?= e($schedule->title) ?>">
                                                        <?= mtq_icon('refresh-cw', 'h-4 w-4') ?>
                                                        Update
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= e(route('broadcast.schedule', $schedule)) ?>">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <button type="submit" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                                        <?= mtq_icon('bell', 'h-4 w-4') ?>
                                                        Siarkan
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= e(route('admin.content.schedules.destroy', $schedule)) ?>" data-swal-confirm data-swal-title="Hapus jadwal?" data-swal-text="Jadwal <?= e($schedule->title) ?> akan dihapus dari daftar." data-swal-confirm="Ya, hapus" data-swal-cancel="Batal">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <button type="submit" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-3 py-2 text-xs text-rose-100 hover:border-rose-300/40" title="Hapus jadwal" aria-label="Hapus jadwal <?= e($schedule->title) ?>">
                                                        <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>

    <!-- Modals Maqra -->
    <?php if ($user?->role === 'admin'): ?>
    <!-- Modal Tambah Jadwal Maqra -->
    <div x-data="{ show: false }"
         @open-modal.window="if ($event.detail === 'maqra-add-modal') show = true"
         @keydown.escape.window="show = false"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.outside="show = false" class="glass-card mx-4 w-full max-w-lg rounded-[2rem] p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">Tambah Jadwal Maqra</h3>
                <button @click="show = false" class="p-2 rounded-xl hover:bg-slate-700/50 text-slate-400 hover:text-white transition">
                    <?= mtq_icon('x', 'h-5 w-5') ?>
                </button>
            </div>
            <form method="POST" action="<?= e(route('admin.content.maqra-schedules.store')) ?>">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Babak</label>
                        <select name="round_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                            <option value="">Pilih Babak</option>
                            <?php foreach ($maqraRounds as $round): ?>
                                <option value="<?= $round->id ?>" <?= old('round_id') == $round->id ? 'selected' : '' ?>><?= e($round->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Golongan</label>
                        <select name="category_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                            <option value="">Pilih Golongan</option>
                            <?php foreach ($maqraCategories as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= old('category_id') == $cat->id ? 'selected' : '' ?>><?= e(trim((string) $cat->branch.' - '.(string) $cat->name)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Tanggal & Jam Mulai</label>
                            <input name="open_at" type="datetime-local" required value="<?= e(old('open_at')) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Tanggal & Jam Selesai</label>
                            <input name="close_at" type="datetime-local" required value="<?= e(old('close_at')) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor Lot Awal</label>
                            <input name="lot_min" type="number" min="1" required value="<?= e(old('lot_min', '1')) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor Lot Akhir</label>
                            <input name="lot_max" type="number" min="1" required value="<?= e(old('lot_max')) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" id="maqra_schedule_active" value="1" checked class="h-5 w-5 rounded border-slate-600 bg-slate-900 text-fuchsia-400 focus:ring-fuchsia-300/30">
                        <label for="maqra_schedule_active" class="text-sm text-slate-300">Aktifkan jadwal ini</label>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Pengambilan Maqra oleh</label>
                        <select name="draw_access_by" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                            <option value="official_only">Official</option>
                            <option value="panitia_only">Panitia</option>
                            <option value="both">Panitia & Official</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="show = false" class="secondary-button">Batal</button>
                    <button type="submit" class="primary-button">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Kelola Babak -->
    <div x-data="{ show: false }"
         @open-modal.window="if ($event.detail === 'maqra-rounds-modal') show = true"
         @keydown.escape.window="show = false"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.outside="show = false" class="glass-card mx-4 w-full max-w-lg rounded-[2rem] p-6 max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">Kelola Babak Maqra</h3>
                <button @click="show = false" class="p-2 rounded-xl hover:bg-slate-700/50 text-slate-400 hover:text-white transition">
                    <?= mtq_icon('x', 'h-5 w-5') ?>
                </button>
            </div>

            <!-- Form Tambah Babak -->
            <form method="POST" action="<?= e(route('admin.content.maqra-rounds.store')) ?>" class="mb-6">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <div class="flex gap-3">
                    <input name="name" type="text" required placeholder="Nama babak baru..." class="flex-1 rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                    <input name="sort_order" type="number" min="0" placeholder="Urutan" value="<?= e(old('sort_order', $maqraRounds->count() + 1)) ?>" class="w-24 rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                    <button type="submit" class="primary-button">Tambah</button>
                </div>
            </form>

            <!-- List Babak -->
            <div class="space-y-2">
                <?php foreach ($maqraRounds as $round): ?>
                <div class="flex items-center justify-between rounded-xl border border-slate-700/80 bg-slate-900/50 p-4">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-white"><?= e($round->name) ?></span>
                        <span class="text-xs text-slate-500">#<?= e($round->slug) ?></span>
                        <?php if (!$round->is_active): ?>
                        <span class="rounded-full bg-slate-700 px-2 py-0.5 text-xs text-slate-400">Nonaktif</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="<?= e(route('admin.content.maqra-rounds.destroy', $round)) ?>" class="inline" onsubmit="return confirm('Hapus babak <?= e($round->name) ?>?')">
                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                            <button type="submit" class="p-2 rounded-lg text-slate-400 hover:bg-rose-500/20 hover:text-rose-400" title="Hapus">
                                <?= mtq_icon('trash', 'h-4 w-4') ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($maqraRounds->isEmpty()): ?>
                <p class="text-center text-slate-500 py-4">Belum ada babak. Tambahkan babak baru di atas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Edit Jadwal Maqra (Generated per schedule) -->
    <?php foreach ($maqraSchedules as $schedule): ?>
    <div x-data="{ show: false }"
         @open-modal.window="if ($event.detail === 'maqra-edit-modal-<?= $schedule->id ?>') show = true"
         @keydown.escape.window="show = false"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.outside="show = false" class="glass-card mx-4 w-full max-w-lg rounded-[2rem] p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">Edit Jadwal Maqra</h3>
                <button @click="show = false" class="p-2 rounded-xl hover:bg-slate-700/50 text-slate-400 hover:text-white transition">
                    <?= mtq_icon('x', 'h-5 w-5') ?>
                </button>
            </div>
            <form method="POST" action="<?= e(route('admin.content.maqra-schedules.update', $schedule)) ?>">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Babak</label>
                        <select name="round_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                            <?php foreach ($maqraRounds as $round): ?>
                                <option value="<?= $round->id ?>" <?= $schedule->round_id == $round->id ? 'selected' : '' ?>><?= e($round->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Golongan</label>
                        <select name="category_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                            <?php foreach ($maqraCategories as $cat): ?>
                                <option value="<?= $cat->id ?>" <?= $schedule->category_id == $cat->id ? 'selected' : '' ?>><?= e(trim((string) $cat->branch.' - '.(string) $cat->name)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Tanggal & Jam Mulai</label>
                            <input name="open_at" type="datetime-local" required value="<?= e($schedule->open_at?->format('Y-m-d\TH:i') ?? '') ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Tanggal & Jam Selesai</label>
                            <input name="close_at" type="datetime-local" required value="<?= e($schedule->close_at?->format('Y-m-d\TH:i') ?? '') ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor Lot Awal</label>
                            <input name="lot_min" type="number" min="1" required value="<?= e((string) $schedule->lot_min) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor Lot Akhir</label>
                            <input name="lot_max" type="number" min="1" required value="<?= e((string) $schedule->lot_max) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" id="maqra_edit_active_<?= $schedule->id ?>" value="1" <?= $schedule->is_active ? 'checked' : '' ?> class="h-5 w-5 rounded border-slate-600 bg-slate-900 text-fuchsia-400 focus:ring-fuchsia-300/30">
                        <label for="maqra_edit_active_<?= $schedule->id ?>" class="text-sm text-slate-300">Aktifkan jadwal ini</label>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Pengambilan Maqra oleh</label>
                        <select name="draw_access_by" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-400 focus:ring-2 focus:ring-fuchsia-400/20">
                            <option value="official_only" <?= ($schedule->draw_access_by ?? 'official_only') === 'official_only' ? 'selected' : '' ?>>Official</option>
                            <option value="panitia_only" <?= $schedule->draw_access_by === 'panitia_only' ? 'selected' : '' ?>>Panitia</option>
                            <option value="both" <?= $schedule->draw_access_by === 'both' ? 'selected' : '' ?>>Panitia & Official</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="show = false" class="secondary-button">Batal</button>
                    <button type="submit" class="primary-button">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
