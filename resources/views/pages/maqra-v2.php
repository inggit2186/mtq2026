<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'maqra');
$categories = $categories ?? collect();
$maqraGroups = $maqraGroups ?? collect();
$maqraStats = $maqraStats ?? ['categories' => 0, 'packages' => 0, 'active' => 0, 'inactive' => 0];
$roundOptions = $roundOptions ?? ['Penyisihan', 'Final'];
$hasCreateDraft = $errors->any() || collect(['competition_category_id', 'round_label', 'maqra_code', 'title', 'content', 'notes'])->contains(fn (string $field): bool => filled(old($field)));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Kelola Maqra') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>

    <main class="relative mx-auto max-w-[1440px] px-4 py-5 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside
                class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('book-open') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Kelola Maqra</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-6 rounded-[1.6rem] border border-fuchsia-400/14 bg-gradient-to-br from-slate-900/90 via-fuchsia-950/70 to-slate-950/60 p-4">
                    <p class="section-kicker">Ringkasan Cepat</p>
                    <div class="mt-3 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-300">Paket aktif di panel</p>
                            <p class="mt-1 text-3xl font-black text-white"><?= e($maqraStats['packages']) ?></p>
                        </div>
                        <div class="status-pill whitespace-nowrap">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-fuchsia-300"></span>
                            Hanya Admin
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Gunakan accordion di bawah untuk membuka golongan, babak, dan paket yang ingin diedit.
                    </p>
                </div>

                <nav class="mt-6 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-6 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Golongan</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($maqraStats['categories']) ?></p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div class="data-card">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Aktif</p>
                            <p class="mt-2 text-3xl font-extrabold text-white"><?= e($maqraStats['active']) ?></p>
                        </div>
                        <div class="data-card">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Nonaktif</p>
                            <p class="mt-2 text-3xl font-extrabold text-white"><?= e($maqraStats['inactive']) ?></p>
                        </div>
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

            <div class="min-w-0 space-y-5">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div class="max-w-3xl">
                            <p class="section-kicker">Kelola Data Maqra</p>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <h2 class="text-2xl font-black tracking-tight text-white sm:text-3xl">Tampilan yang lebih pendek, padat, dan enak dipakai</h2>
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-fuchsia-300"></span>
                                    <?= e($maqraStats['categories']) ?> golongan
                                </div>
                            </div>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                                Buka golongan atau babak yang diperlukan saja. Form tambah dan edit dibuat tersembunyi secara default agar halaman terasa lebih ringkas.
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[380px]">
                            <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Golongan</p>
                                <p class="mt-2 text-2xl font-black text-white"><?= e($maqraStats['categories']) ?></p>
                            </div>
                            <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Paket</p>
                                <p class="mt-2 text-2xl font-black text-white"><?= e($maqraStats['packages']) ?></p>
                            </div>
                            <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Aktif</p>
                                <p class="mt-2 text-2xl font-black text-white"><?= e($maqraStats['active']) ?></p>
                            </div>
                            <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Nonaktif</p>
                                <p class="mt-2 text-2xl font-black text-white"><?= e($maqraStats['inactive']) ?></p>
                            </div>
                    </div>
                </header>

                <section class="glass-card rounded-[2rem] p-5 sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="section-kicker">Import CSV</p>
                            <h3 class="mt-2 text-xl font-bold text-white">Tambah banyak maqra sekaligus</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-300">
                                Upload file CSV dengan kolom: <span class="font-semibold text-white">competition_category_id</span>, <span class="font-semibold text-white">round_label</span>, <span class="font-semibold text-white">maqra_code</span>, <span class="font-semibold text-white">title</span>, <span class="font-semibold text-white">content</span>, <span class="font-semibold text-white">notes</span>, <span class="font-semibold text-white">sort_order</span>, dan <span class="font-semibold text-white">is_active</span>.
                            </p>
                        </div>
                        <form method="POST" action="<?= e(route('maqra.import')) ?>" enctype="multipart/form-data" class="grid gap-3 lg:min-w-[380px]" data-maqra-import-form data-preview-url="<?= e(route('maqra.preview')) ?>">
                            <?= csrf_field() ?>
                            <input type="file" name="maqra_csv" accept=".csv,text/csv" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-cyan-400/15 file:px-4 file:py-2 file:text-cyan-100 hover:border-cyan-300/50">
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-200">
                                <input type="checkbox" name="update_existing" value="1" class="rounded border-slate-600 bg-slate-900 text-fuchsia-500 focus:ring-fuchsia-400">
                                <span>Perbarui data yang sudah ada jika kode sama</span>
                            </label>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <button type="button" class="secondary-button" data-maqra-preview-button>
                                    <?= mtq_icon('eye', 'h-4 w-4') ?>
                                    Preview
                                </button>
                                <button type="submit" class="primary-button">
                                    <?= mtq_icon('upload', 'h-4 w-4') ?>
                                    Import CSV
                                </button>
                            </div>
                            <a href="<?= e(route('maqra.template-csv')) ?>" class="secondary-button">
                                <?= mtq_icon('download', 'h-4 w-4') ?>
                                Unduh Template
                            </a>
                            <button type="submit" formaction="<?= e(route('maqra.report')) ?>" class="secondary-button">
                                <?= mtq_icon('download', 'h-4 w-4') ?>
                                Unduh Laporan
                            </button>
                        </form>
                    </div>
                    <div class="mt-5 hidden rounded-[1.5rem] border border-cyan-400/14 bg-slate-950/50 p-4" data-maqra-preview-panel>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="section-kicker">Preview CSV</p>
                                <p class="mt-2 text-sm text-slate-300" data-maqra-preview-status>Belum ada file yang dipilih.</p>
                            </div>
                            <div class="status-pill" data-maqra-preview-summary>Menunggu preview</div>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-5" data-maqra-preview-stats>
                            <div class="rounded-2xl border border-slate-700/70 bg-slate-950/70 px-4 py-3"><p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Total</p><p class="mt-2 text-xl font-black text-white">-</p></div>
                            <div class="rounded-2xl border border-slate-700/70 bg-slate-950/70 px-4 py-3"><p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Baru</p><p class="mt-2 text-xl font-black text-white">-</p></div>
                            <div class="rounded-2xl border border-slate-700/70 bg-slate-950/70 px-4 py-3"><p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Update</p><p class="mt-2 text-xl font-black text-white">-</p></div>
                            <div class="rounded-2xl border border-slate-700/70 bg-slate-950/70 px-4 py-3"><p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Duplikat</p><p class="mt-2 text-xl font-black text-white">-</p></div>
                            <div class="rounded-2xl border border-slate-700/70 bg-slate-950/70 px-4 py-3"><p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Invalid</p><p class="mt-2 text-xl font-black text-white">-</p></div>
                        </div>
                        <div class="mt-4 space-y-2" data-maqra-preview-errors></div>
                        <div class="mt-4 overflow-hidden rounded-[1.4rem] border border-slate-700/70">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-900/80 text-slate-300">
                                    <tr>
                                        <th class="px-4 py-3">Baris</th>
                                        <th class="px-4 py-3">ID Golongan</th>
                                        <th class="px-4 py-3">Golongan</th>
                                        <th class="px-4 py-3">Babak</th>
                                        <th class="px-4 py-3">Kode</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800 bg-slate-950/60" data-maqra-preview-rows>
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-sm text-slate-400">Preview akan muncul di sini.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="glass-card overflow-hidden rounded-[2rem] border border-fuchsia-400/10">
                    <details <?= $hasCreateDraft ? 'open' : '' ?>>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-6">
                            <div class="flex items-center gap-3">
                                <div class="icon-chip"><?= mtq_icon('plus-circle') ?></div>
                                <div>
                                    <p class="section-kicker">Tambah Paket</p>
                                    <h3 class="mt-1 text-xl font-bold text-white">Buat paket maqra baru</h3>
                                    <p class="mt-1 text-sm text-slate-300">Klik untuk membuka form. Form ini otomatis terbuka jika validasi gagal.</p>
                                </div>
                            </div>
                            <span class="status-pill">
                                <?= $hasCreateDraft ? 'Form terbuka' : 'Klik untuk buka' ?>
                            </span>
                        </summary>

                        <div class="border-t border-slate-800/80 p-5 sm:p-6">
                            <form method="POST" action="<?= e(route('maqra.store')) ?>" class="grid gap-4 lg:grid-cols-2">
                                <?= csrf_field() ?>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Golongan</label>
                                    <select name="competition_category_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                        <option value="">Pilih golongan</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= e($category->id) ?>" <?= (string) old('competition_category_id') === (string) $category->id ? 'selected' : '' ?>><?= e($category->branch.' - '.$category->name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Babak</label>
                                    <select name="round_label" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                        <?php foreach ($roundOptions as $roundOption): ?>
                                            <option value="<?= e($roundOption) ?>" <?= old('round_label') === $roundOption ? 'selected' : '' ?>><?= e($roundOption) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Kode Maqra</label>
                                    <input name="maqra_code" type="text" value="<?= e(old('maqra_code')) ?>" placeholder="TLW-PS-01" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Nama QS</label>
                                    <input name="title" type="text" value="<?= e(old('title')) ?>" placeholder="Al-Ma'idah 1-5" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Isi Maqra</label>
                                    <textarea name="content" rows="3" placeholder="QS. Al-Ma'idah ayat 1 sampai 5" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20"><?= e(old('content')) ?></textarea>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan</label>
                                    <input name="notes" type="text" value="<?= e(old('notes')) ?>" placeholder="Seed test / sumber nasional" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-200">Urutan</label>
                                        <input name="sort_order" type="number" min="0" step="1" value="<?= e(old('sort_order', '0')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                    </div>
                                    <?php $createIsActive = old('is_active', '1'); ?>
                                    <label class="flex items-end gap-3 rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-200">
                                        <input type="checkbox" name="is_active" value="1" <?= $createIsActive ? 'checked' : '' ?> class="mt-1 rounded border-slate-600 bg-slate-900 text-fuchsia-500 focus:ring-fuchsia-400">
                                        <span>Aktifkan paket</span>
                                    </label>
                                </div>
                                <div class="lg:col-span-2 flex justify-end">
                                    <button type="submit" class="primary-button">
                                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                        Simpan Paket
                                    </button>
                                </div>
                            </form>
                        </div>
                    </details>
                </section>

                <section class="space-y-4">
                    <?php $isFirstGroup = true; ?>
                    <?php foreach ($maqraGroups as $group): ?>
                        <details class="glass-card overflow-hidden rounded-[2rem] border border-white/10" <?= $isFirstGroup ? 'open' : '' ?>>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-6">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('book-open') ?></div>
                                    <div>
                                        <p class="section-kicker">Golongan</p>
                                        <h3 class="mt-1 text-xl font-bold text-white"><?= e($group['category']->branch.' - '.$group['category']->name) ?></h3>
                                        <p class="mt-1 text-sm text-slate-300"><?= e($group['packages']->count()) ?> paket · <?= e($group['rounds']->keys()->count()) ?> babak</p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <span class="status-pill">
                                        <?= e($group['packages']->where('is_active', true)->count()) ?> aktif
                                    </span>
                                    <span class="rounded-full border border-slate-700/80 bg-slate-950/70 px-3 py-1.5 text-xs font-semibold text-slate-300">
                                        Buka detail
                                    </span>
                                </div>
                            </summary>

                            <div class="border-t border-slate-800/80 p-4 sm:p-5">
                                <div class="grid gap-3">
                                    <?php foreach ($roundOptions as $roundOption): ?>
                                        <?php $roundPackages = $group['rounds']->get($roundOption, collect()); ?>
                                        <details class="rounded-[1.5rem] border border-slate-700/80 bg-slate-950/60">
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 sm:px-5">
                                                <div>
                                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Babak</p>
                                                    <h4 class="mt-1 text-base font-bold text-white"><?= e($roundOption) ?></h4>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="status-pill">
                                                        <?= e($roundPackages->count()) ?> paket
                                                    </span>
                                                    <span class="text-xs text-slate-400">Buka paket</span>
                                                </div>
                                            </summary>

                                            <div class="border-t border-slate-800/80 p-3 sm:p-4">
                                                <?php if ($roundPackages->isEmpty()): ?>
                                                    <div class="rounded-2xl border border-dashed border-slate-700/70 bg-slate-950/50 px-4 py-5 text-sm text-slate-400">
                                                        Belum ada paket pada babak ini.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="grid gap-3">
                                                        <?php foreach ($roundPackages as $package): ?>
                                                            <details class="rounded-[1.35rem] border border-slate-700/80 bg-slate-950/85">
                                                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 sm:px-5">
                                                                    <div class="min-w-0">
                                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500"><?= e($package->maqra_code) ?></p>
                                                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                                                            <h5 class="text-base font-bold text-white"><?= e($package->title) ?></h5>
                                                                            <span class="rounded-full border border-slate-700/80 bg-slate-900/80 px-2.5 py-1 text-[11px] font-semibold <?= $package->is_active ? 'text-emerald-200' : 'text-slate-300' ?>">
                                                                                <?= e($package->is_active ? 'Aktif' : 'Nonaktif') ?>
                                                                            </span>
                                                                            <span class="rounded-full border border-slate-700/80 bg-slate-900/80 px-2.5 py-1 text-[11px] font-semibold text-slate-300">
                                                                                Urutan <?= e($package->sort_order) ?>
                                                                            </span>
                                                                        </div>
                                                                        <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-400">
                                                                            <?= e(\Illuminate\Support\Str::limit(trim((string) $package->content), 120)) ?>
                                                                        </p>
                                                                    </div>
                                                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-200">
                                                                        <?= mtq_icon('pencil', 'h-4 w-4') ?>
                                                                    </span>
                                                                </summary>

                                                                <div class="border-t border-slate-800/80 p-4 sm:p-5">
                                                                    <form method="POST" action="<?= e(route('maqra.update', $package)) ?>" class="grid gap-4 lg:grid-cols-2">
                                                                        <?= csrf_field() ?>
                                                                        <div>
                                                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Golongan</label>
                                                                            <select name="competition_category_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                                                                <?php foreach ($categories as $category): ?>
                                                                                    <option value="<?= e($category->id) ?>" <?= (int) $package->competition_category_id === (int) $category->id ? 'selected' : '' ?>><?= e($category->branch.' - '.$category->name) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div>
                                                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Babak</label>
                                                                            <select name="round_label" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                                                                <?php foreach ($roundOptions as $roundOption): ?>
                                                                                    <option value="<?= e($roundOption) ?>" <?= $package->round_label === $roundOption ? 'selected' : '' ?>><?= e($roundOption) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div>
                                                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Kode Maqra</label>
                                                                            <input name="maqra_code" type="text" value="<?= e(old('maqra_code', $package->maqra_code)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                                                        </div>
                                                                        <div>
                                                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Nama QS</label>
                                                                            <input name="title" type="text" value="<?= e(old('title', $package->title)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                                                        </div>
                                                                        <div class="lg:col-span-2">
                                                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Isi Maqra</label>
                                                                            <textarea name="content" rows="3" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20"><?= e(old('content', $package->content)) ?></textarea>
                                                                        </div>
                                                                        <div>
                                                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Catatan</label>
                                                                            <input name="notes" type="text" value="<?= e(old('notes', $package->notes ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                                                        </div>
                                                                        <div class="grid gap-4 sm:grid-cols-2">
                                                                            <div>
                                                                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Urutan</label>
                                                                                <input name="sort_order" type="number" min="0" step="1" value="<?= e(old('sort_order', (string) $package->sort_order)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                                                            </div>
                                                                            <label class="flex items-end gap-3 rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-200">
                                                                                <input type="checkbox" name="is_active" value="1" <?= $package->is_active ? 'checked' : '' ?> class="mt-1 rounded border-slate-600 bg-slate-900 text-fuchsia-500 focus:ring-fuchsia-400">
                                                                                <span>Aktifkan paket</span>
                                                                            </label>
                                                                        </div>
                                                                        <div class="lg:col-span-2 flex flex-wrap items-center justify-between gap-3">
                                                                            <div class="text-xs text-slate-400">
                                                                                Kode: <span class="font-semibold text-white"><?= e($package->maqra_code) ?></span>
                                                                            </div>
                                                                            <button type="submit" class="primary-button rounded-xl px-4 py-2 text-sm">
                                                                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                                                Simpan Perubahan
                                                                            </button>
                                                                        </div>
                                                                    </form>

                                                                    <div class="mt-3 flex justify-end">
                                                                        <form method="POST" action="<?= e(route('maqra.destroy', $package)) ?>" data-swal-confirm data-swal-title="Hapus paket maqra?" data-swal-text="Paket yang belum pernah dipakai bisa dihapus. Yang sudah dipakai sebaiknya dinonaktifkan." data-swal-confirm="Ya, hapus" data-swal-cancel="Batal">
                                                                            <?= csrf_field() ?>
                                                                            <button type="submit" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-4 py-2 text-sm text-rose-100 hover:border-rose-300/40">
                                                                                <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                                                Hapus
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </details>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </details>
                        <?php $isFirstGroup = false; ?>
                    <?php endforeach; ?>
                </section>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <script>
        (() => {
            const form = document.querySelector('[data-maqra-import-form]');
            if (!form) return;

            const previewUrl = form.dataset.previewUrl;
            const previewButton = form.querySelector('[data-maqra-preview-button]');
            const previewPanel = document.querySelector('[data-maqra-preview-panel]');
            const previewStatus = document.querySelector('[data-maqra-preview-status]');
            const previewSummary = document.querySelector('[data-maqra-preview-summary]');
            const previewStats = document.querySelector('[data-maqra-preview-stats]');
            const previewErrors = document.querySelector('[data-maqra-preview-errors]');
            const previewRows = document.querySelector('[data-maqra-preview-rows]');
            const fileInput = form.querySelector('input[type="file"][name="maqra_csv"]');
            const updateExisting = form.querySelector('input[type="checkbox"][name="update_existing"]');

            if (!previewUrl || !previewButton || !previewPanel || !previewStatus || !previewSummary || !previewStats || !previewErrors || !previewRows || !fileInput) {
                return;
            }

            const statusClasses = {
                created: 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200',
                updated: 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200',
                duplicate: 'border-amber-400/20 bg-amber-400/10 text-amber-200',
                invalid: 'border-rose-400/20 bg-rose-400/10 text-rose-200',
                valid: 'border-slate-400/20 bg-slate-400/10 text-slate-200',
            };

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const renderBadge = (status) => {
                const label = {
                    created: 'Baru',
                    updated: 'Update',
                    duplicate: 'Duplikat',
                    invalid: 'Invalid',
                    valid: 'Valid',
                }[status] || 'Info';

                const cls = statusClasses[status] || statusClasses.valid;
                return `<span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold ${cls}">${label}</span>`;
            };

            const setSummaryCards = (summary) => {
                const cards = previewStats.querySelectorAll('div');
                const values = [
                    summary.total ?? 0,
                    summary.created ?? 0,
                    summary.updated ?? 0,
                    summary.duplicate_count ?? 0,
                    summary.invalid_count ?? 0,
                ];

                cards.forEach((card, index) => {
                    const valueEl = card.querySelector('p:last-child');
                    if (valueEl) valueEl.textContent = String(values[index] ?? 0);
                });
            };

            const setErrorList = (errors) => {
                previewErrors.innerHTML = '';

                if (!errors || !errors.length) {
                    previewErrors.innerHTML = '<div class="rounded-2xl border border-dashed border-slate-700/70 bg-slate-950/40 px-4 py-3 text-sm text-slate-400">Tidak ada error format.</div>';
                    return;
                }

                errors.slice(0, 5).forEach((error) => {
                    const item = document.createElement('div');
                    item.className = 'rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3 text-sm text-slate-300';
                    item.textContent = error;
                    previewErrors.appendChild(item);
                });
            };

            const setRows = (rows) => {
                if (!rows || !rows.length) {
                    previewRows.innerHTML = '<tr><td colspan="7" class="px-4 py-6 text-sm text-slate-400">Tidak ada baris untuk ditampilkan.</td></tr>';
                    return;
                }

                previewRows.innerHTML = rows.map((row) => `
                    <tr class="align-top">
                        <td class="px-4 py-3 text-slate-400">${escapeHtml(row.row_number ?? '-')}</td>
                        <td class="px-4 py-3 font-semibold text-cyan-200">${escapeHtml(row.competition_category_id ?? '-')}</td>
                        <td class="px-4 py-3 text-slate-200">${escapeHtml(row.category_label ?? '-')}</td>
                        <td class="px-4 py-3 text-slate-300">${escapeHtml(row.round_label ?? '-')}</td>
                        <td class="px-4 py-3 font-semibold text-white">${escapeHtml(row.maqra_code ?? '-')}</td>
                        <td class="px-4 py-3">${renderBadge(row.status || 'valid')}</td>
                        <td class="px-4 py-3 text-slate-400">${escapeHtml(row.note || '-')}</td>
                    </tr>
                `).join('');
            };

            const preview = async () => {
                const file = fileInput.files && fileInput.files[0];
                if (!file) {
                    previewPanel.classList.remove('hidden');
                    previewStatus.textContent = 'Silakan pilih file CSV terlebih dahulu.';
                    previewSummary.textContent = 'Belum ada file';
                    return;
                }

                const data = new FormData(form);
                previewStatus.textContent = 'Membaca CSV...';
                previewSummary.textContent = 'Sedang diproses';
                previewPanel.classList.remove('hidden');

                try {
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: data,
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.ok) {
                        throw new Error(payload.message || 'Preview gagal diproses.');
                    }

                    const summary = payload.summary || {};
                    previewStatus.textContent = updateExisting && updateExisting.checked
                        ? 'Mode update aktif. Data yang sama akan diperbarui.'
                        : 'Mode update tidak aktif. Data yang sama akan dianggap duplikat.';
                    previewSummary.textContent = `Total ${summary.total ?? 0} baris`;
                    setSummaryCards(summary);
                    setErrorList(payload.errors || []);
                    setRows(payload.rows || []);
                } catch (error) {
                    previewStatus.textContent = error.message || 'Preview gagal.';
                    previewSummary.textContent = 'Preview error';
                    previewErrors.innerHTML = `<div class="rounded-2xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">${error.message || 'Preview gagal diproses.'}</div>`;
                    previewRows.innerHTML = '<tr><td colspan="7" class="px-4 py-6 text-sm text-slate-400">Preview tidak tersedia.</td></tr>';
                }
            };

            previewButton.addEventListener('click', preview);

            fileInput.addEventListener('change', () => {
                previewPanel.classList.remove('hidden');
                previewStatus.textContent = 'File siap dipreview.';
                previewSummary.textContent = 'Klik Preview untuk memeriksa';
            });
        })();
    </script>
</body>
</html>
