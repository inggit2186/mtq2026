<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$juknisConfig = $juknisConfig ?? [];
$juknisSettingsReady = $juknisSettingsReady ?? false;
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'admin.juknis');
$appConfig = old('app', $juknisConfig['app'] ?? []);
if (! is_array($appConfig)) {
    $appConfig = [];
}
$footerConfig = old('footer', $juknisConfig['footer'] ?? []);
if (! is_array($footerConfig)) {
    $footerConfig = [];
}

$registration = old('registration', $juknisConfig['registration'] ?? []);
if (! is_array($registration)) {
    $registration = [];
}

$registrationWindows = old('registration_windows', $juknisConfig['registration_windows'] ?? []);
if (! is_array($registrationWindows)) {
    $registrationWindows = [];
}
$registrationWindows = array_values($registrationWindows);
$registrationWindows = array_pad($registrationWindows, max(count($registrationWindows) + 1, 4), []);

$eventScheduleRows = old('event_schedule', $juknisConfig['event_schedule'] ?? []);
if (! is_array($eventScheduleRows)) {
    $eventScheduleRows = [];
}
$eventScheduleRows = array_values($eventScheduleRows);
$eventScheduleRows = array_pad($eventScheduleRows, max(count($eventScheduleRows) + 1, 8), []);

$administrationRequirementsText = old('administration_requirements_text', implode(PHP_EOL, $juknisConfig['administration_requirements'] ?? []));
$participantRulesText = old('participant_rules_text', implode(PHP_EOL, $juknisConfig['participant_rules'] ?? []));
$performanceRulesText = old('performance_rules_text', implode(PHP_EOL, $juknisConfig['performance_rules'] ?? []));
$competitionSystemText = old('competition_system_text', implode(PHP_EOL, $juknisConfig['competition_system'] ?? []));
$objectionRulesText = old('objection_rules_text', implode(PHP_EOL, $juknisConfig['objection_rules'] ?? []));

$summaryCounts = [
    'schedule' => count($juknisConfig['event_schedule'] ?? []),
    'windows' => count($juknisConfig['registration_windows'] ?? []),
    'administration' => count($juknisConfig['administration_requirements'] ?? []),
    'participant' => count($juknisConfig['participant_rules'] ?? []),
    'performance' => count($juknisConfig['performance_rules'] ?? []),
    'system' => count($juknisConfig['competition_system'] ?? []),
    'objection' => count($juknisConfig['objection_rules'] ?? []),
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Edit Juknis') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/sweet-alerts.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('book-open') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Edit Juknis</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Acuan Utama</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($juknisConfig['title'] ?? 'Juknis MTQ') ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Perubahan di halaman ini akan dipakai oleh halaman juknis publik dan bagian aplikasi lain yang membaca konfigurasi juknis.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                        Host <?= e($juknisConfig['host'] ?? '-') ?>
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Total Jadwal</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryCounts['schedule']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Acuan Usia</p>
                        <p class="mt-2 text-lg font-bold text-white"><?= e($juknisConfig['age_reference_date'] ?? '-') ?></p>
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
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Pengaturan Juknis</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Edit juknis aplikasi dari satu menu admin</h2>
                            <p class="mt-2 text-sm text-slate-300"><?= e($rolePanel['description'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Siap Disimpan
                    </div>
                </header>

                <?php if (! $juknisSettingsReady): ?>
                    <div class="rounded-3xl border border-amber-400/20 bg-amber-400/10 px-4 py-4 text-sm text-amber-100">
                        Tabel `juknis_settings` belum tersedia. Halaman ini masih memakai konfigurasi bawaan dari file `config/juknis.php`.
                        Jalankan `php artisan migrate` agar perubahan juknis bisa disimpan ke database.
                    </div>
                <?php endif; ?>

                <div class="glass-card rounded-[1.75rem] p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="section-kicker">Lompatan Cepat</p>
                            <h3 class="mt-2 text-xl font-bold text-white">Loncat ke bagian yang ingin diedit</h3>
                        </div>
                        <span class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            Ramah Mobile
                        </span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="#juknis-branding" class="secondary-button rounded-full px-4 py-2 text-sm">Branding</a>
                        <a href="#juknis-footer" class="secondary-button rounded-full px-4 py-2 text-sm">Footer</a>
                        <a href="#juknis-masa-akses" class="secondary-button rounded-full px-4 py-2 text-sm">Masa Akses</a>
                        <a href="#juknis-jadwal" class="secondary-button rounded-full px-4 py-2 text-sm">Jadwal</a>
                        <a href="#juknis-aturan" class="secondary-button rounded-full px-4 py-2 text-sm">Aturan</a>
                        <a href="#juknis-ringkasan" class="secondary-button rounded-full px-4 py-2 text-sm">Ringkasan</a>
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <form method="POST" action="<?= e(route('admin.juknis.update')) ?>" class="glass-card rounded-[2rem] p-6 space-y-6" data-loading-text="Menyimpan juknis aplikasi...">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

                        <section id="juknis-identitas" class="space-y-4 scroll-mt-24">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('book-open') ?></div>
                                <div>
                                    <p class="section-kicker">Identitas Juknis</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Informasi dasar dokumen</h3>
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Judul Juknis</label>
                                    <input name="title" type="text" value="<?= e(old('title', $juknisConfig['title'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Host / Tuan Rumah</label>
                                    <input name="host" type="text" value="<?= e(old('host', $juknisConfig['host'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Acuan Umur</label>
                                    <input name="age_reference_date" type="text" value="<?= e(old('age_reference_date', $juknisConfig['age_reference_date'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                </div>
                            </div>
                        </section>

                        <details id="juknis-branding" class="group rounded-[1.75rem] border border-slate-700/80 bg-slate-950/50 p-4 scroll-mt-24" open>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('spark') ?></div>
                                    <div>
                                        <p class="section-kicker">Branding Aplikasi</p>
                                        <h3 class="mt-2 text-2xl font-bold text-white">Nama, judul, dan tampilan utama</h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Bagian ini mengatur identitas aplikasi di homepage.</p>
                                    </div>
                                </div>
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-200 transition group-open:rotate-180">
                                    <?= mtq_icon('arrow-down', 'h-4 w-4') ?>
                                </span>
                            </summary>
                            <div class="mt-5 space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Aplikasi</label>
                                        <input name="app[name]" type="text" value="<?= e(old('app.name', $appConfig['name'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Judul Homepage</label>
                                        <input name="app[homepage_title]" type="text" value="<?= e(old('app.homepage_title', $appConfig['homepage_title'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Tagline Homepage</label>
                                        <input name="app[homepage_tagline]" type="text" value="<?= e(old('app.homepage_tagline', $appConfig['homepage_tagline'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Deskripsi Homepage</label>
                                        <textarea name="app[homepage_description]" rows="4" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?= e(old('app.homepage_description', $appConfig['homepage_description'] ?? '')) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <details id="juknis-footer" class="group rounded-[1.75rem] border border-slate-700/80 bg-slate-950/50 p-4 scroll-mt-24">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('shield') ?></div>
                                    <div>
                                        <p class="section-kicker">Footer Aplikasi</p>
                                        <h3 class="mt-2 text-2xl font-bold text-white">Teks bawah halaman dan kontak</h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Bagian ini dipakai untuk footer publik dan info kontak.</p>
                                    </div>
                                </div>
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-200 transition group-open:rotate-180">
                                    <?= mtq_icon('arrow-down', 'h-4 w-4') ?>
                                </span>
                            </summary>
                            <div class="mt-5 space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Headline Footer</label>
                                        <input name="footer[headline]" type="text" value="<?= e(old('footer.headline', $footerConfig['headline'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Deskripsi Footer</label>
                                        <textarea name="footer[description]" rows="4" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?= e(old('footer.description', $footerConfig['description'] ?? '')) ?></textarea>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Label Kontak</label>
                                        <input name="footer[contact_label]" type="text" value="<?= e(old('footer.contact_label', $footerConfig['contact_label'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor WhatsApp</label>
                                        <input name="footer[contact_number]" type="text" value="<?= e(old('footer.contact_number', $footerConfig['contact_number'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Penulis</label>
                                        <input name="footer[attribution_name]" type="text" value="<?= e(old('footer.attribution_name', $footerConfig['attribution_name'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Keterangan Penulis</label>
                                        <input name="footer[attribution_role]" type="text" value="<?= e(old('footer.attribution_role', $footerConfig['attribution_role'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan Footer</label>
                                        <textarea name="footer[note]" rows="3" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?= e(old('footer.note', $footerConfig['note'] ?? '')) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <details id="juknis-masa-akses" class="group rounded-[1.75rem] border border-slate-700/80 bg-slate-950/50 p-4 scroll-mt-24" open>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                                    <div>
                                        <p class="section-kicker">Masa / Sesi Akses</p>
                                        <h3 class="mt-2 text-2xl font-bold text-white">Tambah atau hapus periode akses</h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Bagian ini bisa dilipat agar halaman lebih ringan di layar kecil.</p>
                                    </div>
                                </div>
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-200 transition group-open:rotate-180">
                                    <?= mtq_icon('arrow-down', 'h-4 w-4') ?>
                                </span>
                            </summary>
                            <div class="mt-5 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                                <div>
                                    <p class="section-kicker">Jadwal Registrasi</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Batas waktu dan masa layanan</h3>
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <div><label class="mb-2 block text-sm font-semibold text-slate-200">Pendaftaran Buka</label><input name="registration[open]" type="text" value="<?= e(old('registration.open', $registration['open'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                <div><label class="mb-2 block text-sm font-semibold text-slate-200">Pendaftaran Tutup</label><input name="registration[close]" type="text" value="<?= e(old('registration.close', $registration['close'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                <div><label class="mb-2 block text-sm font-semibold text-slate-200">Edit Official Buka</label><input name="registration[official_edit_start]" type="text" value="<?= e(old('registration.official_edit_start', $registration['official_edit_start'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                <div><label class="mb-2 block text-sm font-semibold text-slate-200">Edit Official Tutup</label><input name="registration[official_edit_end]" type="text" value="<?= e(old('registration.official_edit_end', $registration['official_edit_end'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                <div><label class="mb-2 block text-sm font-semibold text-slate-200">Verifikasi Buka</label><input name="registration[verification_start]" type="text" value="<?= e(old('registration.verification_start', $registration['verification_start'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                <div><label class="mb-2 block text-sm font-semibold text-slate-200">Verifikasi Tutup</label><input name="registration[verification_end]" type="text" value="<?= e(old('registration.verification_end', $registration['verification_end'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                <div><label class="mb-2 block text-sm font-semibold text-slate-200">Pengumuman</label><input name="registration[announcement]" type="text" value="<?= e(old('registration.announcement', $registration['announcement'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                <div><label class="mb-2 block text-sm font-semibold text-slate-200">Sanggah Buka</label><input name="registration[objection_start]" type="text" value="<?= e(old('registration.objection_start', $registration['objection_start'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                <div><label class="mb-2 block text-sm font-semibold text-slate-200">Sanggah Tutup</label><input name="registration[objection_end]" type="text" value="<?= e(old('registration.objection_end', $registration['objection_end'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                            </div>
                        </section>

                        <section id="juknis-jadwal" class="space-y-4 scroll-mt-24">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                                    <div>
                                        <p class="section-kicker">Masa / Sesi Akses</p>
                                        <h3 class="mt-2 text-2xl font-bold text-white">Tambah atau hapus periode akses</h3>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <button type="button" class="secondary-button" onclick="mtqJuknisAddPresetWindow('registration_open')">
                                        <?= mtq_icon('plus', 'h-4 w-4') ?>
                                        Preset Pendaftaran
                                    </button>
                                    <button type="button" class="secondary-button" onclick="mtqJuknisAddPresetWindow('verification_open')">
                                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                        Preset Verifikasi
                                    </button>
                                    <button type="button" class="secondary-button" onclick="mtqJuknisAddPresetWindow('lot_open')">
                                        <?= mtq_icon('spark', 'h-4 w-4') ?>
                                        Preset Lot
                                    </button>
                                    <button type="button" class="secondary-button" onclick="mtqJuknisAddPresetWindow('all_closed')">
                                        <?= mtq_icon('x', 'h-4 w-4') ?>
                                        Preset Tutup
                                    </button>
                                    <button type="button" class="secondary-button" onclick="mtqJuknisAddRow('registration_windows')">
                                        <?= mtq_icon('plus', 'h-4 w-4') ?>
                                        Tambah Masa
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-3" data-dynamic-body="registration_windows">
                                <?php foreach ($registrationWindows as $index => $window): ?>
                                    <?php $windowKey = is_int($index) ? (string) $index : (string) $index; ?>
                                    <div class="rounded-[1.5rem] border border-slate-700/80 bg-slate-950/60 p-4" data-dynamic-row>
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="grid flex-1 gap-3 md:grid-cols-3">
                                                <div class="md:col-span-3">
                                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Label Masa / Sesi</label>
                                                    <input name="registration_windows[<?= e($windowKey) ?>][label]" type="text" value="<?= e(old("registration_windows.$index.label", $window['label'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
                                                </div>
                                                <div>
                                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Mulai</label>
                                                    <input name="registration_windows[<?= e($windowKey) ?>][start_at]" type="text" value="<?= e(old("registration_windows.$index.start_at", $window['start_at'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
                                                </div>
                                                <div>
                                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Selesai</label>
                                                    <input name="registration_windows[<?= e($windowKey) ?>][end_at]" type="text" value="<?= e(old("registration_windows.$index.end_at", $window['end_at'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
                                                </div>
                                                <div class="md:col-span-3 grid gap-4 xl:grid-cols-2">
                                                    <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 p-4">
                                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Official</p>
                                                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                                            <?php foreach ([
                                                                'participant_registration_open' => 'Pendaftaran',
                                                                'participant_edit_open' => 'Edit',
                                                                'participant_verification_open' => 'Verifikasi',
                                                                'participant_delete_open' => 'Hapus',
                                                                'mandate_upload_open' => 'Upload Mandat',
                                                                'participant_documents_open' => 'Dokumen',
                                                                'participant_lot_open' => 'Lot',
                                                                'participant_maqra_open' => 'Maqra',
                                                                'participant_maqra_penyisihan_open' => 'Maqra Penyisihan',
                                                                'participant_maqra_final_open' => 'Maqra Final',
                                                            ] as $featureKey => $featureLabel): ?>
                                                                <label class="flex items-center gap-3 rounded-xl border border-slate-700/80 bg-slate-950/60 px-3 py-2 text-sm text-slate-200">
                                                                    <input type="checkbox" name="registration_windows[<?= e($windowKey) ?>][official][<?= e($featureKey) ?>]" value="1" <?= ! empty(data_get($window, 'official.'.$featureKey)) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-600 bg-slate-950 text-cyan-400">
                                                                    <span><?= e($featureLabel) ?></span>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 p-4">
                                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">Panitia</p>
                                                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                                            <?php foreach ([
                                                                'participant_registration_open' => 'Pendaftaran',
                                                                'participant_edit_open' => 'Edit',
                                                                'participant_verification_open' => 'Verifikasi',
                                                                'participant_delete_open' => 'Hapus',
                                                                'mandate_upload_open' => 'Upload Mandat',
                                                                'participant_documents_open' => 'Dokumen',
                                                                'participant_lot_open' => 'Lot',
                                                                'participant_maqra_open' => 'Maqra',
                                                                'participant_maqra_penyisihan_open' => 'Maqra Penyisihan',
                                                                'participant_maqra_final_open' => 'Maqra Final',
                                                            ] as $featureKey => $featureLabel): ?>
                                                                <label class="flex items-center gap-3 rounded-xl border border-slate-700/80 bg-slate-950/60 px-3 py-2 text-sm text-slate-200">
                                                                    <input type="checkbox" name="registration_windows[<?= e($windowKey) ?>][panitia][<?= e($featureKey) ?>]" value="1" <?= ! empty(data_get($window, 'panitia.'.$featureKey)) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-600 bg-slate-950 text-emerald-400">
                                                                    <span><?= e($featureLabel) ?></span>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-3 py-2 text-xs text-rose-100" onclick="this.closest('[data-dynamic-row]').remove()">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <template id="registration_windows-template">
                                <div class="rounded-[1.5rem] border border-slate-700/80 bg-slate-950/60 p-4" data-dynamic-row>
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="grid flex-1 gap-3 md:grid-cols-3">
                                            <div class="md:col-span-3">
                                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Label Masa / Sesi</label>
                                                <input name="registration_windows[__INDEX__][label]" type="text" value="" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Mulai</label>
                                                <input name="registration_windows[__INDEX__][start_at]" type="text" value="" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Selesai</label>
                                                <input name="registration_windows[__INDEX__][end_at]" type="text" value="" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
                                            </div>
                                            <div class="md:col-span-3 grid gap-4 xl:grid-cols-2">
                                                <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 p-4">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Official</p>
                                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                                        <?php foreach ([
                                                            'participant_registration_open' => 'Pendaftaran',
                                                            'participant_edit_open' => 'Edit',
                                                            'participant_verification_open' => 'Verifikasi',
                                                            'participant_delete_open' => 'Hapus',
                                                            'mandate_upload_open' => 'Upload Mandat',
                                                            'participant_documents_open' => 'Dokumen',
                                                            'participant_lot_open' => 'Lot',
                                                            'participant_maqra_open' => 'Maqra',
                                                            'participant_maqra_penyisihan_open' => 'Maqra Penyisihan',
                                                            'participant_maqra_final_open' => 'Maqra Final',
                                                        ] as $featureKey => $featureLabel): ?>
                                                            <label class="flex items-center gap-3 rounded-xl border border-slate-700/80 bg-slate-950/60 px-3 py-2 text-sm text-slate-200">
                                                                <input type="checkbox" name="registration_windows[__INDEX__][official][<?= e($featureKey) ?>]" value="1" class="h-4 w-4 rounded border-slate-600 bg-slate-950 text-cyan-400">
                                                                <span><?= e($featureLabel) ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 p-4">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">Panitia</p>
                                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                                        <?php foreach ([
                                                            'participant_registration_open' => 'Pendaftaran',
                                                            'participant_edit_open' => 'Edit',
                                                            'participant_verification_open' => 'Verifikasi',
                                                            'participant_delete_open' => 'Hapus',
                                                            'mandate_upload_open' => 'Upload Mandat',
                                                            'participant_documents_open' => 'Dokumen',
                                                            'participant_lot_open' => 'Lot',
                                                            'participant_maqra_open' => 'Maqra',
                                                            'participant_maqra_penyisihan_open' => 'Maqra Penyisihan',
                                                            'participant_maqra_final_open' => 'Maqra Final',
                                                        ] as $featureKey => $featureLabel): ?>
                                                            <label class="flex items-center gap-3 rounded-xl border border-slate-700/80 bg-slate-950/60 px-3 py-2 text-sm text-slate-200">
                                                                <input type="checkbox" name="registration_windows[__INDEX__][panitia][<?= e($featureKey) ?>]" value="1" class="h-4 w-4 rounded border-slate-600 bg-slate-950 text-emerald-400">
                                                                <span><?= e($featureLabel) ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-3 py-2 text-xs text-rose-100" onclick="this.closest('[data-dynamic-row]').remove()">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </template>
                            </div>
                        </details>

                        <details id="juknis-aturan" class="group rounded-[1.75rem] border border-slate-700/80 bg-slate-950/50 p-4 scroll-mt-24">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                                    <div>
                                        <p class="section-kicker">Jadwal & Aturan</p>
                                        <h3 class="mt-2 text-2xl font-bold text-white">Rangkaian event dan daftar ketentuan</h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-300">Bagian ini memuat jadwal kegiatan dan aturan resmi lomba.</p>
                                    </div>
                                </div>
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-200 transition group-open:rotate-180">
                                    <?= mtq_icon('arrow-down', 'h-4 w-4') ?>
                                </span>
                            </summary>
                            <div class="mt-5 space-y-4">
                                <section id="juknis-kegiatan" class="space-y-4 scroll-mt-24">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                                            <div>
                                                <p class="section-kicker">Jadwal Kegiatan</p>
                                                <h3 class="mt-2 text-2xl font-bold text-white">Rangkaian event MTQ</h3>
                                            </div>
                                        </div>
                                        <button type="button" class="secondary-button ml-auto" onclick="mtqJuknisAddRow('event_schedule')">
                                            <?= mtq_icon('plus', 'h-4 w-4') ?>
                                            Tambah Kegiatan
                                        </button>
                                    </div>
                                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-700/80">
                                        <table class="min-w-full">
                                            <thead class="table-head">
                                                <tr>
                                                    <th class="px-4 py-3 text-left">Tanggal</th>
                                                    <th class="px-4 py-3 text-left">Waktu</th>
                                                    <th class="px-4 py-3 text-left">Kegiatan</th>
                                                    <th class="px-4 py-3 text-left">Catatan</th>
                                                    <th class="px-4 py-3 text-right">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody data-dynamic-body="event_schedule">
                                                <?php foreach ($eventScheduleRows as $index => $row): ?>
                                                    <tr class="table-row align-top" data-dynamic-row>
                                                        <td class="px-4 py-3">
                                                            <input name="event_schedule[<?= e((string) $index) ?>][date]" type="text" value="<?= e(old("event_schedule.$index.date", $row['date'] ?? '')) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100">
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <input name="event_schedule[<?= e((string) $index) ?>][time]" type="text" value="<?= e(old("event_schedule.$index.time", $row['time'] ?? '')) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100">
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <input name="event_schedule[<?= e((string) $index) ?>][activity]" type="text" value="<?= e(old("event_schedule.$index.activity", $row['activity'] ?? '')) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100">
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <input name="event_schedule[<?= e((string) $index) ?>][notes]" type="text" value="<?= e(old("event_schedule.$index.notes", $row['notes'] ?? '')) ?>" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100">
                                                        </td>
                                                        <td class="px-4 py-3 text-right">
                                                            <button type="button" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-3 py-2 text-xs text-rose-100" onclick="this.closest('[data-dynamic-row]').remove()">Hapus</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-xs text-slate-400">Tambahkan baris baru di bagian bawah jika diperlukan. Baris kosong otomatis diabaikan saat disimpan.</p>
                                    <template id="event_schedule-template">
                                        <tr class="table-row align-top" data-dynamic-row>
                                            <td class="px-4 py-3"><input name="event_schedule[__INDEX__][date]" type="text" value="" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100"></td>
                                            <td class="px-4 py-3"><input name="event_schedule[__INDEX__][time]" type="text" value="" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100"></td>
                                            <td class="px-4 py-3"><input name="event_schedule[__INDEX__][activity]" type="text" value="" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100"></td>
                                            <td class="px-4 py-3"><input name="event_schedule[__INDEX__][notes]" type="text" value="" class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100"></td>
                                            <td class="px-4 py-3 text-right"><button type="button" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-3 py-2 text-xs text-rose-100" onclick="this.closest('[data-dynamic-row]').remove()">Hapus</button></td>
                                        </tr>
                                    </template>
                                </section>

                                <section class="space-y-4">
                                    <div class="flex items-center gap-3">
                                        <div class="icon-chip"><?= mtq_icon('users') ?></div>
                                        <div>
                                            <p class="section-kicker">Ketentuan Peserta</p>
                                            <h3 class="mt-2 text-2xl font-bold text-white">Aturan berbentuk daftar</h3>
                                        </div>
                                    </div>
                                    <div class="grid gap-4 xl:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Administrasi Peserta</label>
                                            <textarea name="administration_requirements_text" rows="8" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?= e($administrationRequirementsText) ?></textarea>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Ketentuan Peserta</label>
                                            <textarea name="participant_rules_text" rows="8" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?= e($participantRulesText) ?></textarea>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Ketentuan Penampilan</label>
                                            <textarea name="performance_rules_text" rows="8" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?= e($performanceRulesText) ?></textarea>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Sistem Musabaqah</label>
                                            <textarea name="competition_system_text" rows="8" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?= e($competitionSystemText) ?></textarea>
                                        </div>
                                        <div class="xl:col-span-2">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Hak Official dan Protes</label>
                                            <textarea name="objection_rules_text" rows="8" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?= e($objectionRulesText) ?></textarea>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </details>

                        <div class="flex flex-wrap gap-3 pt-2">
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                Simpan Juknis
                            </button>
                            <a href="<?= e(route('juknis.index')) ?>" class="secondary-button">
                                <?= mtq_icon('book-open', 'h-4 w-4') ?>
                                Lihat Halaman Juknis
                            </a>
                            <a href="<?= e(route('admin.content')) ?>" class="secondary-button">
                                <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                Kembali ke Konten
                            </a>
                        </div>
                    </form>

                    <aside class="space-y-6">
                        <section id="juknis-ringkasan" class="glass-card rounded-[2rem] p-6 scroll-mt-24">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('spark') ?></div>
                                <div>
                                    <p class="section-kicker">Ringkasan</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Apa yang sedang aktif</h3>
                                </div>
                            </div>
                            <div class="mt-6 grid gap-3">
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Judul</p>
                                    <p class="mt-2 text-sm font-semibold text-white"><?= e($juknisConfig['title'] ?? '-') ?></p>
                                </div>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Host</p>
                                    <p class="mt-2 text-sm font-semibold text-white"><?= e($juknisConfig['host'] ?? '-') ?></p>
                                </div>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Acuan Umur</p>
                                    <p class="mt-2 text-sm font-semibold text-white"><?= e($juknisConfig['age_reference_date'] ?? '-') ?></p>
                                </div>
                            </div>
                        </section>

                        <section class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                                <div>
                                    <p class="section-kicker">Timeline</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Jadwal registrasi</h3>
                                </div>
                            </div>
                            <div class="mt-6 space-y-3 text-sm">
                                <div class="data-card"><span class="font-semibold text-white">Pendaftaran:</span> <?= e(($juknisConfig['registration']['open'] ?? '-') . ' - ' . ($juknisConfig['registration']['close'] ?? '-')) ?></div>
                                <div class="data-card"><span class="font-semibold text-white">Edit Official:</span> <?= e(($juknisConfig['registration']['official_edit_start'] ?? '-') . ' - ' . ($juknisConfig['registration']['official_edit_end'] ?? '-')) ?></div>
                                <div class="data-card"><span class="font-semibold text-white">Verifikasi:</span> <?= e(($juknisConfig['registration']['verification_start'] ?? '-') . ' - ' . ($juknisConfig['registration']['verification_end'] ?? '-')) ?></div>
                                <div class="data-card"><span class="font-semibold text-white">Pengumuman:</span> <?= e($juknisConfig['registration']['announcement'] ?? '-') ?></div>
                                <div class="data-card"><span class="font-semibold text-white">Sanggah:</span> <?= e(($juknisConfig['registration']['objection_start'] ?? '-') . ' - ' . ($juknisConfig['registration']['objection_end'] ?? '-')) ?></div>
                            </div>
                        </section>

                        <section class="glass-card rounded-[2rem] p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('shield') ?></div>
                                <div>
                                    <p class="section-kicker">Daftar Aturan</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Jumlah item per bagian</h3>
                                </div>
                            </div>
                            <div class="mt-6 grid gap-3">
                                <div class="data-card flex items-center justify-between gap-3"><span class="text-sm text-slate-300">Masa Akses</span><span class="text-lg font-bold text-white"><?= e($summaryCounts['windows']) ?></span></div>
                                <div class="data-card flex items-center justify-between gap-3"><span class="text-sm text-slate-300">Administrasi</span><span class="text-lg font-bold text-white"><?= e($summaryCounts['administration']) ?></span></div>
                                <div class="data-card flex items-center justify-between gap-3"><span class="text-sm text-slate-300">Peserta</span><span class="text-lg font-bold text-white"><?= e($summaryCounts['participant']) ?></span></div>
                                <div class="data-card flex items-center justify-between gap-3"><span class="text-sm text-slate-300">Penampilan</span><span class="text-lg font-bold text-white"><?= e($summaryCounts['performance']) ?></span></div>
                                <div class="data-card flex items-center justify-between gap-3"><span class="text-sm text-slate-300">Sistem</span><span class="text-lg font-bold text-white"><?= e($summaryCounts['system']) ?></span></div>
                                <div class="data-card flex items-center justify-between gap-3"><span class="text-sm text-slate-300">Protes</span><span class="text-lg font-bold text-white"><?= e($summaryCounts['objection']) ?></span></div>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <script>
        const MTQ_JUKNIS_WINDOW_PRESETS = {
            registration_open: {
                label: 'Masa Pendaftaran',
                official: {
                    participant_registration_open: true,
                    participant_edit_open: true,
                    participant_verification_open: false,
                    participant_delete_open: false,
                    mandate_upload_open: true,
                    participant_documents_open: true,
                    participant_lot_open: false,
                    participant_maqra_open: false,
                    participant_maqra_penyisihan_open: false,
                    participant_maqra_final_open: false,
                },
                panitia: {
                    participant_registration_open: true,
                    participant_edit_open: true,
                    participant_verification_open: false,
                    participant_delete_open: false,
                    mandate_upload_open: true,
                    participant_documents_open: true,
                    participant_lot_open: false,
                    participant_maqra_open: false,
                    participant_maqra_penyisihan_open: false,
                    participant_maqra_final_open: false,
                },
            },
            verification_open: {
                label: 'Masa Verifikasi',
                official: {
                    participant_registration_open: false,
                    participant_edit_open: true,
                    participant_verification_open: true,
                    participant_delete_open: true,
                    mandate_upload_open: true,
                    participant_documents_open: true,
                    participant_lot_open: false,
                    participant_maqra_open: false,
                    participant_maqra_penyisihan_open: false,
                    participant_maqra_final_open: false,
                },
                panitia: {
                    participant_registration_open: false,
                    participant_edit_open: false,
                    participant_verification_open: true,
                    participant_delete_open: false,
                    mandate_upload_open: false,
                    participant_documents_open: false,
                    participant_lot_open: false,
                    participant_maqra_open: false,
                    participant_maqra_penyisihan_open: false,
                    participant_maqra_final_open: false,
                },
            },
            lot_open: {
                label: 'Masa Lot',
                official: {
                    participant_registration_open: false,
                    participant_edit_open: false,
                    participant_verification_open: false,
                    participant_delete_open: false,
                    mandate_upload_open: false,
                    participant_documents_open: false,
                    participant_lot_open: true,
                    participant_maqra_open: false,
                    participant_maqra_penyisihan_open: false,
                    participant_maqra_final_open: false,
                },
                panitia: {
                    participant_registration_open: false,
                    participant_edit_open: false,
                    participant_verification_open: false,
                    participant_delete_open: false,
                    mandate_upload_open: false,
                    participant_documents_open: false,
                    participant_lot_open: true,
                    participant_maqra_open: false,
                    participant_maqra_penyisihan_open: false,
                    participant_maqra_final_open: false,
                },
            },
            all_closed: {
                label: 'Semua Ditutup',
                official: {},
                panitia: {},
            },
        };

        window.mtqJuknisAddRow = function (section) {
            const body = document.querySelector(`[data-dynamic-body="${section}"]`);
            const template = document.getElementById(`${section}-template`);

            if (!body || !template) {
                return;
            }

            const index = `${Date.now()}-${Math.floor(Math.random() * 10000)}`;
            const html = template.innerHTML.replaceAll('__INDEX__', index);
            body.insertAdjacentHTML('beforeend', html);
        };

        window.mtqJuknisAddPresetWindow = function (presetName) {
            const preset = MTQ_JUKNIS_WINDOW_PRESETS[presetName];

            if (! preset) {
                return;
            }

            window.mtqJuknisAddRow('registration_windows');

            const body = document.querySelector('[data-dynamic-body="registration_windows"]');
            const row = body ? body.lastElementChild : null;
            if (! row) {
                return;
            }

            const labelInput = row.querySelector('input[name$="[label]"]');
            const startInput = row.querySelector('input[name$="[start_at]"]');
            const endInput = row.querySelector('input[name$="[end_at]"]');
            const officialInputs = row.querySelectorAll('input[type="checkbox"][name*="[official]"]');
            const panitiaInputs = row.querySelectorAll('input[type="checkbox"][name*="[panitia]"]');

            if (labelInput) {
                labelInput.value = preset.label || '';
            }

            if (startInput && ! startInput.value) {
                startInput.value = '';
            }

            if (endInput && ! endInput.value) {
                endInput.value = '';
            }

            officialInputs.forEach((input) => {
                const match = input.name.match(/\[official\]\[([^\]]+)\]$/);
                const key = match ? match[1] : null;
                input.checked = !! (key && preset.official && preset.official[key]);
            });

            panitiaInputs.forEach((input) => {
                const match = input.name.match(/\[panitia\]\[([^\]]+)\]$/);
                const key = match ? match[1] : null;
                input.checked = !! (key && preset.panitia && preset.panitia[key]);
            });
        };
    </script>
</body>
</html>
