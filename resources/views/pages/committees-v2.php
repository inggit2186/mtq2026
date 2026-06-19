<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$committees = $committees ?? collect();
$categoryOptions = collect($categoryOptions ?? []);
$districtOptions = collect($districtOptions ?? []);
$committeeStats = $committeeStats ?? ['total' => 0, 'with_category_access' => 0, 'category_scope_total' => 0, 'with_district_access' => 0, 'district_scope_total' => 0];
$generatedCredentials = $generatedCredentials ?? session('generated_credentials');
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'committees.index');
$categoryPayload = $categoryOptions->map(fn ($category): array => [
    'id' => $category->id,
    'label' => trim(($category->branch ?? '-').' - '.($category->name ?? '-')),
])->values()->all();
$districtPayload = $districtOptions->map(fn ($district): array => [
    'id' => $district->id,
    'label' => (string) ($district->name ?? '-'),
])->values()->all();
$committeePayload = $committees->map(fn ($committee): array => [
    'id' => $committee->id,
    'name' => $committee->name,
    'role' => $committee->role,
    'category_ids' => $committee->categoryAccesses->pluck('competition_category_id')->map(fn ($id): string => (string) $id)->values()->all(),
    'district_ids' => $committee->districtAccesses->pluck('district_id')->map(fn ($id): string => (string) $id)->values()->all(),
    'updateUrl' => route('committees.branches.update', $committee),
])->values()->all();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Panitia Golongan') ?></title>
    <style>[x-cloak]{display:none!important;}</style>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="committeeRegistrationPage(<?= e(json_encode([
        'previewUrl' => route('committees.preview'),
        'storeUrl' => route('committees.store'),
        'csrfToken' => csrf_token(),
        'categories' => $categoryPayload,
        'districts' => $districtPayload,
        'committees' => $committeePayload,
        'initialNip' => old('nip', ''),
        'initialCategoryIds' => array_map('strval', array_values((array) old('category_ids', []))),
        'initialDistrictIds' => array_map('strval', array_values((array) old('district_ids', []))),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)">
        <div class="hero-orb hero-orb-cyan right-[-8rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('users') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Panitia Golongan</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Hak Akses Golongan</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($user?->name) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Daftarkan panitia dari NIP SILATAR, lalu tentukan golongan yang menjadi tanggung jawab tiap akun.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Hanya Admin
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Total Panitia</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($committeeStats['total']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Cakupan Golongan</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($committeeStats['category_scope_total']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Verifikator Kecamatan</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($committeeStats['district_scope_total']) ?></p>
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
                            <p class="section-kicker">Manajemen Akun Panitia</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Daftarkan panitia dari SILATAR dan atur hak akses golongannya</h2>
                            <p class="mt-2 text-sm text-slate-300">Setiap panitia bisa memegang lebih dari satu golongan, dan akses modul penilaian hanya akan dibuka untuk golongan yang Anda pilih di sini.</p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                        <?= e($categoryOptions->count()) ?> Golongan MTQ
                    </div>
                </header>

                <?php if ($generatedCredentials): ?>
                    <section class="rounded-[2rem] border border-emerald-400/20 bg-emerald-400/10 p-6">
                        <p class="section-kicker text-emerald-200">Akun Berhasil Dibuat</p>
                        <h3 class="mt-2 text-2xl font-bold text-white"><?= e($generatedCredentials['name'] ?? '-') ?></h3>
                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                            <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Email</p><p class="mt-2 text-sm font-semibold text-white"><?= e($generatedCredentials['email'] ?? '-') ?></p></div>
                            <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">No. HP</p><p class="mt-2 text-sm font-semibold text-white"><?= e($generatedCredentials['phone'] ?? '-') ?></p></div>
                            <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nomor Induk</p><p class="mt-2 text-sm font-semibold text-white"><?= e($generatedCredentials['nomor_induk'] ?? '-') ?></p></div>
                            <div class="data-card md:col-span-2 xl:col-span-2"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Golongan</p><p class="mt-2 text-sm font-semibold text-white"><?= e(implode(', ', (array) ($generatedCredentials['categories'] ?? [])) ?: '-') ?></p></div>
                            <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Password Awal</p><p class="mt-2 text-lg font-black tracking-[0.08em] text-emerald-300"><?= e($generatedCredentials['password'] ?? '-') ?></p></div>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('id-card') ?></div>
                            <div>
                                <p class="section-kicker">Form Registrasi</p>
                                <h3 class="mt-1 text-2xl font-bold text-white">Daftarkan panitia dari NIP</h3>
                            </div>
                        </div>

                        <form class="mt-6 space-y-4" x-on:submit.prevent="openPreview()">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">NIP Pegawai SILATAR</label>
                                <input name="nip" type="text" inputmode="numeric" x-model="nip" placeholder="Contoh: 199201152022031001" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            <p class="mt-2 text-xs leading-6 text-slate-400">Sistem akan mengambil `nama`, `email`, `no hp`, dan `foto profil` dari SILATAR. Hak akses golongan dipilih manual oleh admin sebelum akun panitia disimpan.</p>
                                <template x-if="previewError">
                                    <p class="mt-3 text-sm text-rose-200" x-text="previewError"></p>
                                </template>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="primary-button" x-bind:disabled="loadingPreview" x-bind:class="loadingPreview ? 'pointer-events-none opacity-60' : ''">
                                    <?= mtq_icon('eye', 'h-4 w-4') ?>
                                    <span x-text="loadingPreview ? 'Memuat Pratinjau...' : 'Pratinjau User'"></span>
                                </button>
                                <a href="<?= e(route('admin.content')) ?>" class="secondary-button">
                                    <?= mtq_icon('bell', 'h-4 w-4') ?>
                                    Kembali ke Admin
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('check-circle') ?></div>
                            <div>
                                <p class="section-kicker">Aturan Akses</p>
                                <h3 class="mt-1 text-2xl font-bold text-white">Yang akan terjadi setelah akun dibuat</h3>
                            </div>
                        </div>
                        <div class="mt-6 grid gap-3">
                            <div class="data-card"><p class="font-semibold text-white">Login memakai akun SILATAR hasil salin</p><p class="mt-2 text-sm leading-6 text-slate-300">Nama, email, no. HP, dan foto profil ikut disalin ke e-MTQ.</p></div>
                            <div class="data-card"><p class="font-semibold text-white">Hak akses berdasarkan golongan</p><p class="mt-2 text-sm leading-6 text-slate-300">Panitia hanya bisa melihat setting penilaian, peserta, dan input nilai pada golongan yang Anda pilih.</p></div>
                            <div class="data-card"><p class="font-semibold text-white">Multi-golongan</p><p class="mt-2 text-sm leading-6 text-slate-300">Satu akun panitia bisa diberi tanggung jawab untuk beberapa golongan sekaligus.</p></div>
                            <div class="data-card"><p class="font-semibold text-white">Verifikator pendaftaran per kecamatan</p><p class="mt-2 text-sm leading-6 text-slate-300">Hak akses verifikasi pendaftaran panitia diatur terpisah per kecamatan, dan satu panitia bisa menangani beberapa kecamatan.</p></div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Total Panitia</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($committeeStats['total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('layers') ?></div><p class="mt-4 text-sm text-slate-400">Panitia Siap Bertugas</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($committeeStats['with_category_access']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('building') ?></div><p class="mt-4 text-sm text-slate-400">Golongan Tercover</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($committeeStats['category_scope_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('building') ?></div><p class="mt-4 text-sm text-slate-400">Kecamatan Verifikator</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($committeeStats['district_scope_total']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('users') ?></div>
                        <div>
                            <p class="section-kicker">Daftar Panitia</p>
                            <h3 class="mt-1 text-2xl font-bold text-white">Panitia dan golongan tanggung jawab</h3>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 xl:grid-cols-2">
                        <?php if ($committees->isEmpty()): ?>
                            <div class="data-card text-sm text-slate-300 xl:col-span-2">Belum ada panitia yang terdaftar.</div>
                        <?php else: ?>
                            <?php foreach ($committees as $committee): ?>
                                <?php $photoUrl = $committee->profilePhotoUrl(); ?>
                                <div class="data-card relative">
                                    <div class="absolute right-4 top-4 flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="secondary-button rounded-full p-2"
                                            x-on:click="openCategoryEditor(<?= e(json_encode($committee->id)) ?>)"
                                            title="Edit Golongan"
                                            aria-label="Edit Golongan"
                                        >
                                            <?= mtq_icon('pencil', 'h-4 w-4') ?>
                                        </button>
                                        <form method="POST" action="<?= e(route('committees.destroy', $committee)) ?>" data-swal-confirm data-swal-title="Hapus akun panitia?" data-swal-text="Akun panitia <?= e($committee->name) ?> akan dihapus beserta hak akses golongan dan kecamatan verifikatornya." data-swal-confirm="Ya, hapus" data-swal-cancel="Batal">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="secondary-button rounded-full border-rose-400/20 bg-rose-400/10 p-2 text-rose-100 hover:border-rose-300/40"
                                                title="Hapus akun panitia"
                                                aria-label="Hapus akun panitia"
                                            >
                                                <?= mtq_icon('trash', 'h-4 w-4') ?>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="flex items-center gap-3 pr-24">
                                        <?php if ($photoUrl): ?>
                                            <img src="<?= e($photoUrl) ?>" alt="Foto <?= e($committee->name) ?>" class="h-12 w-12 rounded-full border border-cyan-300/16 object-cover">
                                        <?php else: ?>
                                            <div class="flex h-12 w-12 items-center justify-center rounded-full border border-cyan-300/16 bg-cyan-400/10 text-sm font-black text-cyan-100"><?= e($committee->profileInitials()) ?></div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-white"><?= e($committee->name) ?></p>
                                            <p class="mt-1 truncate text-xs text-slate-400"><?= e($committee->email ?: '-') ?></p>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold <?= $committee->role === 'admin' ? 'border border-amber-400/30 bg-amber-400/10 text-amber-200' : 'border border-cyan-400/30 bg-cyan-400/10 text-cyan-200' ?>"><?= e(ucfirst($committee->role)) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 space-y-1 text-sm text-slate-300">
                                        <p><span class="text-slate-500">No. HP:</span> <?= e($committee->phone ?: '-') ?></p>
                                        <p><span class="text-slate-500">NIP:</span> <?= e($committee->nomor_induk ?: '-') ?></p>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <?php foreach ($committee->categoryAccesses as $access): ?>
                                            <span class="inline-flex rounded-full border border-cyan-400/16 bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-100"><?= e(trim(($access->category?->branch ?? '-').' - '.($access->category?->name ?? '-'))) ?></span>
                                        <?php endforeach; ?>
                                        <?php if ($committee->categoryAccesses->isEmpty()): ?>
                                            <span class="inline-flex rounded-full border border-amber-400/16 bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-100">Belum ada golongan</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <?php foreach ($committee->districtAccesses as $access): ?>
                                            <span class="inline-flex rounded-full border border-emerald-400/16 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-100"><?= e($access->district?->name ?? '-') ?></span>
                                        <?php endforeach; ?>
                                        <?php if ($committee->districtAccesses->isEmpty()): ?>
                                            <span class="inline-flex rounded-full border border-amber-400/16 bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-100">Belum ada kecamatan verifikator</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div x-cloak x-show="previewOpen" x-on:keydown.escape.window="closePreview()" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-3">
                <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" x-on:click="closePreview()"></div>
                <div class="relative z-10 flex max-h-[78vh] w-full max-w-2xl flex-col overflow-hidden rounded-[1.75rem] border border-cyan-400/14 bg-slate-950 shadow-[0_40px_100px_-35px_rgba(14,165,233,0.45)]">
                    <div class="flex items-center justify-between gap-4 border-b border-white/8 px-4 py-3">
                        <div>
                            <p class="section-kicker">Pratinjau User SILATAR</p>
                            <h3 class="mt-1.5 text-lg font-bold text-white">Tinjau data panitia dan pilih golongan akses</h3>
                        </div>
                        <button type="button" class="secondary-button rounded-xl px-3 py-2" x-on:click="closePreview()">
                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        </button>
                    </div>

                    <div class="overflow-y-auto px-4 py-4">
                        <template x-if="preview">
                            <form method="POST" x-bind:action="storeUrl" class="space-y-4" data-loading-text="Menyimpan akun panitia dan akses golongan...">
                                <input type="hidden" name="_token" x-bind:value="csrfToken">
                                <input type="hidden" name="nip" x-bind:value="nip">
                                <div class="grid gap-4 md:grid-cols-[120px_minmax(0,1fr)]">
                                    <div class="rounded-[1.25rem] border border-white/8 bg-slate-900/70 p-3">
                                        <template x-if="preview.avatar_url">
                                            <img x-bind:src="preview.avatar_url" x-bind:alt="`Foto ${preview.name}`" class="mx-auto h-20 w-20 rounded-[1rem] border border-cyan-300/16 object-cover">
                                        </template>
                                        <template x-if="!preview.avatar_url">
                                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[1rem] border border-cyan-300/16 bg-cyan-400/10 text-2xl font-black text-cyan-100" x-text="initials(preview.name)"></div>
                                        </template>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="data-card md:col-span-2">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nama</p>
                                            <p class="mt-1.5 text-base font-bold text-white" x-text="preview.name"></p>
                                        </div>
                                        <div class="data-card">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Email</p>
                                            <p class="mt-1.5 text-sm font-semibold text-white break-all" x-text="preview.email || '-'"></p>
                                        </div>
                                        <div class="data-card">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">No. HP</p>
                                            <p class="mt-1.5 text-sm font-semibold text-white" x-text="preview.phone || '-'"></p>
                                        </div>
                                        <div class="data-card">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nomor Induk</p>
                                            <p class="mt-1.5 text-sm font-semibold text-white" x-text="preview.nomor_induk"></p>
                                        </div>
                                        <div class="data-card md:col-span-2">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Unit Kerja SILATAR</p>
                                            <p class="mt-1.5 text-sm leading-6 text-slate-200" x-text="preview.unit_label || '-'"></p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Role Akun</label>
                                            <select name="role" x-model="selectedRole" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                                <option value="panitia">Panitia</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Hak Akses Golongan</label>
                                            <input type="text" x-model="categorySearch" placeholder="Cari golongan..." class="mb-3 w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                            <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/70 p-2">
                                                <div class="max-h-[220px] overflow-y-auto pr-1">
                                                    <div class="grid gap-2 sm:grid-cols-2">
                                                        <template x-for="category in filteredCategories(categorySearch)" :key="category.id">
                                                            <label class="flex items-start gap-3 rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3 text-sm text-slate-200">
                                                                <input type="checkbox" name="category_ids[]" x-bind:value="String(category.id)" x-model="selectedCategoryIds" class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-950 text-cyan-400 focus:ring-cyan-300/30">
                                                                <span class="leading-6" x-text="category.label"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                    <template x-if="filteredCategories(categorySearch).length === 0">
                                                        <p class="px-3 py-4 text-sm text-slate-400">Golongan yang dicari tidak ditemukan.</p>
                                                    </template>
                                                </div>
                                            </div>
                                            <p class="mt-2 text-xs leading-5 text-slate-400">Golongan boleh dikosongkan jika panitia belum diberi tugas pada cabang tertentu. Jika diisi, akun ini hanya akan melihat dan mengelola penilaian pada golongan yang dicentang.</p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Hak Verifikator Pendaftaran per Kecamatan</label>
                                            <input type="text" x-model="districtSearch" placeholder="Cari kecamatan..." class="mb-3 w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-emerald-300 focus:ring-2 focus:ring-emerald-400/20">
                                            <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/70 p-2">
                                                <div class="max-h-[180px] overflow-y-auto pr-1">
                                                    <div class="grid gap-2 sm:grid-cols-2">
                                                        <template x-for="district in filteredDistricts(districtSearch)" :key="district.id">
                                                            <label class="flex items-start gap-3 rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3 text-sm text-slate-200">
                                                                <input type="checkbox" name="district_ids[]" x-bind:value="String(district.id)" x-model="selectedDistrictIds" class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-950 text-emerald-400 focus:ring-emerald-300/30">
                                                                <span class="leading-6" x-text="district.label"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                    <template x-if="filteredDistricts(districtSearch).length === 0">
                                                        <p class="px-3 py-4 text-sm text-slate-400">Kecamatan yang dicari tidak ditemukan.</p>
                                                    </template>
                                                </div>
                                            </div>
                                            <p class="mt-2 text-xs leading-5 text-slate-400">Kecamatan verifikator boleh dikosongkan jika belum ada penugasan. Jika diisi, hak ini dipakai untuk memantau dan memverifikasi pendaftaran peserta per kecamatan.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="sticky bottom-0 flex flex-wrap gap-3 border-t border-white/8 bg-slate-950 pt-3">
                                    <button type="submit" class="primary-button">
                                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                        Daftarkan Panitia
                                    </button>
                                    <button type="button" class="secondary-button" x-on:click="closePreview()">
                                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                        Tutup Pratinjau
                                    </button>
                                    <p class="w-full text-sm font-medium text-slate-300">Golongan dan kecamatan verifikator bersifat opsional. Kosongkan jika akun panitia belum perlu akses tertentu.</p>
                                </div>
                            </form>
                        </template>
                    </div>
                </div>
            </div>

            <div x-cloak x-show="categoryEditorOpen" x-on:keydown.escape.window="closeCategoryEditor()" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-3">
                <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" x-on:click="closeCategoryEditor()"></div>
                <div class="relative z-10 flex max-h-[78vh] w-full max-w-xl flex-col overflow-hidden rounded-[1.75rem] border border-cyan-400/14 bg-slate-950 shadow-[0_40px_100px_-35px_rgba(14,165,233,0.45)]">
                    <div class="flex items-center justify-between gap-4 border-b border-white/8 px-4 py-3">
                        <div>
                            <p class="section-kicker">Edit Cepat</p>
                            <h3 class="mt-1.5 text-lg font-bold text-white">Ubah hak akses golongan panitia</h3>
                        </div>
                        <button type="button" class="secondary-button rounded-xl px-3 py-2" x-on:click="closeCategoryEditor()">
                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        </button>
                    </div>

                    <div class="overflow-y-auto px-4 py-4">
                        <template x-if="editingCommittee">
                            <form method="POST" x-bind:action="editingCommittee.updateUrl" class="space-y-4">
                                <input type="hidden" name="_token" x-bind:value="csrfToken">
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Panitia</p>
                                    <p class="mt-2 text-base font-bold text-white" x-text="editingCommittee.name"></p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Hak Akses Golongan</label>
                                    <input type="text" x-model="editingCategorySearch" placeholder="Cari golongan..." class="mb-3 w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/70 p-2">
                                        <div class="max-h-[240px] overflow-y-auto pr-1">
                                            <div class="grid gap-2 sm:grid-cols-2">
                                                <template x-for="category in filteredCategories(editingCategorySearch)" :key="`edit-${category.id}`">
                                                    <label class="flex items-start gap-3 rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3 text-sm text-slate-200">
                                                        <input type="checkbox" name="category_ids[]" x-bind:value="String(category.id)" x-model="editingCategoryIds" class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-950 text-cyan-400 focus:ring-cyan-300/30">
                                                        <span class="leading-6" x-text="category.label"></span>
                                                    </label>
                                                </template>
                                            </div>
                                            <template x-if="filteredCategories(editingCategorySearch).length === 0">
                                                <p class="px-3 py-4 text-sm text-slate-400">Golongan yang dicari tidak ditemukan.</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Hak Verifikator Pendaftaran per Kecamatan</label>
                                    <input type="text" x-model="editingDistrictSearch" placeholder="Cari kecamatan..." class="mb-3 w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-emerald-300 focus:ring-2 focus:ring-emerald-400/20">
                                    <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/70 p-2">
                                        <div class="max-h-[200px] overflow-y-auto pr-1">
                                            <div class="grid gap-2 sm:grid-cols-2">
                                                <template x-for="district in filteredDistricts(editingDistrictSearch)" :key="`edit-district-${district.id}`">
                                                    <label class="flex items-start gap-3 rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3 text-sm text-slate-200">
                                                        <input type="checkbox" name="district_ids[]" x-bind:value="String(district.id)" x-model="editingDistrictIds" class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-950 text-emerald-400 focus:ring-emerald-300/30">
                                                        <span class="leading-6" x-text="district.label"></span>
                                                    </label>
                                                </template>
                                            </div>
                                            <template x-if="filteredDistricts(editingDistrictSearch).length === 0">
                                                <p class="px-3 py-4 text-sm text-slate-400">Kecamatan yang dicari tidak ditemukan.</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="sticky bottom-0 flex flex-wrap gap-3 border-t border-white/8 bg-slate-950 pt-3">
                                    <button type="submit" class="primary-button">
                                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                        Simpan Golongan
                                    </button>
                                    <button type="button" class="secondary-button" x-on:click="closeCategoryEditor()">
                                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                        Tutup
                                    </button>
                                </div>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <script>
        function committeeRegistrationPage(initialState) {
            return {
                mobileNavOpen: false,
                previewUrl: initialState.previewUrl || '',
                storeUrl: initialState.storeUrl || '',
                csrfToken: initialState.csrfToken || '',
                categories: initialState.categories || [],
                districts: initialState.districts || [],
                committees: initialState.committees || [],
                nip: initialState.initialNip || '',
                selectedCategoryIds: Array.isArray(initialState.initialCategoryIds) ? initialState.initialCategoryIds : [],
                selectedDistrictIds: Array.isArray(initialState.initialDistrictIds) ? initialState.initialDistrictIds : [],
                selectedRole: 'panitia',
                categorySearch: '',
                districtSearch: '',
                previewOpen: false,
                categoryEditorOpen: false,
                previewError: '',
                preview: null,
                editingCommittee: null,
                editingCategoryIds: [],
                editingDistrictIds: [],
                editingCategorySearch: '',
                editingDistrictSearch: '',
                loadingPreview: false,
                async openPreview() {
                    this.previewError = '';
                    this.preview = null;

                    const normalizedNip = String(this.nip || '').replace(/\D+/g, '');
                    if (!normalizedNip) {
                        this.previewError = 'NIP pegawai wajib diisi dengan angka yang valid.';
                        return;
                    }

                    this.loadingPreview = true;

                    try {
                        const response = await fetch(this.previewUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ nip: normalizedNip }),
                            credentials: 'same-origin',
                        });

                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            this.previewError = payload.message || 'Pratinjau user SILATAR gagal dimuat.';
                            return;
                        }

                        this.nip = normalizedNip;
                        this.preview = payload.preview || null;
                        this.categorySearch = '';
                        this.districtSearch = '';
                        this.previewOpen = true;
                    } catch (error) {
                        this.previewError = 'Koneksi ke SILATAR gagal. Silakan coba beberapa saat lagi.';
                    } finally {
                        this.loadingPreview = false;
                    }
                },
                closePreview() {
                    this.previewOpen = false;
                    this.categorySearch = '';
                    this.districtSearch = '';
                },
                openCategoryEditor(committeeId) {
                    const committee = this.committees.find((item) => String(item.id) === String(committeeId)) || null;
                    if (!committee) {
                        return;
                    }

                    this.editingCommittee = committee;
                    this.editingCategoryIds = Array.isArray(committee.category_ids) ? [...committee.category_ids] : [];
                    this.editingDistrictIds = Array.isArray(committee.district_ids) ? [...committee.district_ids] : [];
                    this.editingCategorySearch = '';
                    this.editingDistrictSearch = '';
                    this.categoryEditorOpen = true;
                },
                closeCategoryEditor() {
                    this.categoryEditorOpen = false;
                    this.editingCommittee = null;
                    this.editingCategoryIds = [];
                    this.editingDistrictIds = [];
                    this.editingCategorySearch = '';
                    this.editingDistrictSearch = '';
                },
                filteredCategories(keyword) {
                    const normalizedKeyword = String(keyword || '').trim().toLowerCase();

                    if (normalizedKeyword === '') {
                        return this.categories;
                    }

                    return this.categories.filter((category) => String(category.label || '').toLowerCase().includes(normalizedKeyword));
                },
                filteredDistricts(keyword) {
                    const normalizedKeyword = String(keyword || '').trim().toLowerCase();

                    if (normalizedKeyword === '') {
                        return this.districts;
                    }

                    return this.districts.filter((district) => String(district.label || '').toLowerCase().includes(normalizedKeyword));
                },
                initials(name) {
                    return String(name || '')
                        .trim()
                        .split(/\s+/)
                        .filter(Boolean)
                        .slice(0, 2)
                        .map((part) => part.charAt(0).toUpperCase())
                        .join('') || 'U';
                },
            };
        }
    </script>
</body>
</html>
