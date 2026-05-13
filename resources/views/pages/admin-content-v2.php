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
$maqraCategories = $maqraCategories ?? collect();
$selectedMaqraCategoryIds = collect($officialAccessSetting->maqraOpenCategoryIds())
    ->map(fn (int $id): string => (string) $id)
    ->all();
$maqraLotMin = old('participant_maqra_lot_min', $officialAccessSetting->participant_maqra_lot_min ?? '');
$maqraLotMax = old('participant_maqra_lot_max', $officialAccessSetting->participant_maqra_lot_max ?? '');
$maqraLotRangesByCategory = old('participant_maqra_lot_ranges', $officialAccessSetting->maqraOpenLotRanges());
if (! is_array($maqraLotRangesByCategory)) {
    $maqraLotRangesByCategory = [];
}
$maqraPenyisihanOpen = old('participant_maqra_penyisihan_open', $officialAccessSetting->participant_maqra_penyisihan_open ?? true);
$maqraFinalOpen = old('participant_maqra_final_open', $officialAccessSetting->participant_maqra_final_open ?? true);
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
                                        'description' => 'Mengizinkan official mengubah data peserta yang sudah didaftarkan.',
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
                                <?php $checked = (bool) ($officialAccessSetting->{$card['key']} ?? true); ?>
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

                            <div class="lg:col-span-2 rounded-[1.5rem] border border-fuchsia-400/14 bg-slate-950/60 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-white">Akses Maqra per Babak</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Pisahkan buka/tutup pengambilan maqra untuk Penyisihan dan Final. Pengaturan global lama tetap dipertahankan sebagai lapisan fallback, namun akses operasional mengikuti masing-masing babak.</p>
                                    </div>
                                    <div class="status-pill border-fuchsia-400/20 bg-fuchsia-400/10 text-fuchsia-100">
                                        <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                        Per Babak
                                    </div>
                                </div>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <label class="rounded-[1.25rem] border border-fuchsia-400/14 bg-slate-950/50 p-4 transition hover:border-fuchsia-300/40">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-white">Penyisihan</p>
                                                <p class="mt-2 text-sm leading-6 text-slate-300">Mengaktifkan pengambilan maqra untuk babak penyisihan.</p>
                                            </div>
                                            <input type="checkbox" name="participant_maqra_penyisihan_open" value="1" <?= $maqraPenyisihanOpen ? 'checked' : '' ?> class="mt-1 h-5 w-5 rounded border-slate-600 bg-slate-950 text-fuchsia-400 focus:ring-fuchsia-300/30">
                                        </div>
                                    </label>
                                    <label class="rounded-[1.25rem] border border-violet-400/14 bg-slate-950/50 p-4 transition hover:border-violet-300/40">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-white">Final</p>
                                                <p class="mt-2 text-sm leading-6 text-slate-300">Mengaktifkan pengambilan maqra untuk babak final.</p>
                                            </div>
                                            <input type="checkbox" name="participant_maqra_final_open" value="1" <?= $maqraFinalOpen ? 'checked' : '' ?> class="mt-1 h-5 w-5 rounded border-slate-600 bg-slate-950 text-violet-400 focus:ring-violet-300/30">
                                        </div>
                                    </label>
                                </div>
                                <div class="mt-4 rounded-2xl border border-slate-700/70 bg-slate-950/70 px-4 py-3 text-xs leading-6 text-slate-300">
                                    Status global maqra akan mengikuti gabungan kedua babak ini. Jika salah satu babak dibuka, akses maqra global tetap dianggap aktif.
                                </div>
                            </div>

                            <div class="lg:col-span-2 rounded-[1.5rem] border border-fuchsia-400/14 bg-slate-950/60 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-white">Golongan Maqra untuk Official</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Centang golongan yang boleh tampil di menu Pengambilan Maqra untuk official. Panitia tetap mengikuti hak akses golongan, lalu hasilnya disaring lagi dengan daftar ini.</p>
                                    </div>
                                    <div class="status-pill border-fuchsia-400/20 bg-fuchsia-400/10 text-fuchsia-100">
                                        <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                        <?= e($maqraCategories->count()) ?> golongan
                                    </div>
                                </div>

                                <?php if ($maqraCategories->isEmpty()): ?>
                                    <div class="mt-4 rounded-[1.25rem] border border-dashed border-slate-700 bg-slate-950/50 p-4 text-sm text-slate-400">
                                        Belum ada golongan yang bisa diatur untuk maqra.
                                    </div>
                                <?php else: ?>
                                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                        <?php foreach ($maqraCategories as $category): ?>
                                            <?php
                                                $isChecked = in_array((string) $category->id, $selectedMaqraCategoryIds, true);
                                                $categoryRange = $maqraLotRangesByCategory[(string) $category->id] ?? $maqraLotRangesByCategory[$category->id] ?? ['min' => '', 'max' => ''];
                                            ?>
                                            <label class="rounded-[1.25rem] border border-slate-700/80 bg-slate-950/50 p-4 transition hover:border-fuchsia-300/40">
                                                <div class="flex items-start gap-3">
                                                    <input type="checkbox" name="participant_maqra_category_ids[]" value="<?= e($category->id) ?>" <?= $isChecked ? 'checked' : '' ?> class="mt-1 h-5 w-5 rounded border-slate-600 bg-slate-950 text-fuchsia-400 focus:ring-fuchsia-300/30">
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-sm font-semibold text-white"><?= e(trim((string) $category->branch.' - '.(string) $category->name)) ?></p>
                                                        <p class="mt-1 text-xs text-slate-400"><?= e((string) $category->quota) ?> peserta | <?= e((string) $category->slug) ?></p>
                                                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                            <div>
                                                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Nomor Lot Awal</label>
                                                                <input
                                                                    type="number"
                                                                    min="1"
                                                                    step="1"
                                                                    name="participant_maqra_lot_ranges[<?= e($category->id) ?>][min]"
                                                                    value="<?= e((string) ($categoryRange['min'] ?? '')) ?>"
                                                                    placeholder="001"
                                                                    class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-3 py-2.5 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20"
                                                                >
                                                            </div>
                                                            <div>
                                                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Nomor Lot Akhir</label>
                                                                <input
                                                                    type="number"
                                                                    min="1"
                                                                    step="1"
                                                                    name="participant_maqra_lot_ranges[<?= e($category->id) ?>][max]"
                                                                    value="<?= e((string) ($categoryRange['max'] ?? '')) ?>"
                                                                    placeholder="100"
                                                                    class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-3 py-2.5 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20"
                                                                >
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 rounded-[1.5rem] border border-cyan-400/14 bg-cyan-400/8 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-white">Fallback Rentang Nomor Lot Maqra</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Ini dipakai hanya jika sebuah golongan belum diisi rentang khusus. Jika rentang sudah diisi per golongan di atas, nilai ini akan diabaikan untuk golongan tersebut.</p>
                                    </div>
                                    <div class="status-pill border-cyan-400/20 bg-cyan-400/10 text-cyan-100">
                                        <?= mtq_icon('hash', 'h-4 w-4') ?>
                                        Fallback
                                    </div>
                                </div>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor Lot Awal</label>
                                        <input name="participant_maqra_lot_min" type="number" min="1" step="1" value="<?= e((string) $maqraLotMin) ?>" placeholder="001" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor Lot Akhir</label>
                                        <input name="participant_maqra_lot_max" type="number" min="1" step="1" value="<?= e((string) $maqraLotMax) ?>" placeholder="100" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-slate-400">Jika diisi, peserta tanpa nomor lot atau di luar rentang ini tidak bisa membuka pengambilan maqra untuk official/pendamping.</p>
                            </div>

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
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
