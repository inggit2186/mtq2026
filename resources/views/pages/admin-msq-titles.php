<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? [];
$districts = $districts ?? collect();
$categories = $categories ?? collect();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Judul MSQ - e-MTQ</title>
    <style>[x-cloak]{display:none!important;}</style>
    <?php foreach ($cssAssets as $asset): ?>
        <link rel="stylesheet" href="<?= e($asset) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-8rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition-all duration-300 lg:static lg:inset-auto lg:block"
                :class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('list') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Judul MSQ</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Syarhil Qur'an</p>
                    <h2 class="mt-3 text-xl font-bold text-white">Kelola Judul MSQ</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Kelola judul maqra per kecamatan untuk MSQ.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                        District-Based
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Total Kecamatan</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($districts->count()) ?></p>
                    </div>
                    <a href="<?= e(route('admin.content')) ?>" class="secondary-button w-full">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        Kembali ke Konten
                    </a>
                </div>
            </aside>

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Panel Admin MSQ</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Kelola Judul MSQ</h2>
                            <p class="mt-2 text-sm text-slate-300">Judul maqra per kecamatan untuk Syarhil Qur'an.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?= e($districts->count()) ?> Kecamatan
                        </div>
                    </div>
                </header>

                <div class="space-y-6">
                    <!-- Info Box -->
                    <div class="rounded-2xl border border-cyan-400/20 bg-gradient-to-br from-cyan-950/40 to-blue-950/40 p-5">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-cyan-400/20 bg-cyan-400/10">
                                    <?= mtq_icon('information-circle', 'h-6 w-6 text-cyan-400') ?>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">Pengambilan maqra MSQ berbasis kecamatan</h3>
                                <p class="mt-1 text-sm text-slate-300">Setiap kecamatan dapat memiliki 1-3 judul MSQ. Peserta MSQ dari kecamatan yang sama akan mendapatkan judul yang sama (district sharing).</p>
                            </div>
                        </div>
                    </div>

                    <!-- Add New Title Form -->
                    <div class="rounded-2xl border border-slate-700/50 bg-slate-900/60 p-6 backdrop-blur-xl">
                        <div class="mb-5">
                            <h3 class="text-lg font-semibold text-white">Tambah Judul MSQ Baru</h3>
                            <p class="text-sm text-slate-400 mt-1">Tambahkan judul maqra untuk kecamatan tertentu</p>
                        </div>
                        <form method="POST" action="<?= e(route('admin.msq-titles.store')) ?>" class="space-y-4">
                            <?= csrf_field() ?>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Kecamatan</label>
                                    <select name="district_id" required
                                            class="w-full rounded-xl border border-slate-700/80 bg-slate-800/60 px-4 py-2.5 text-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                        <option value="">-- Pilih Kecamatan --</option>
                                        <?php foreach ($districts as $district): ?>
                                            <option value="<?= $district->id ?>" <?= old('district_id') == $district->id ? 'selected' : '' ?>>
                                                <?= e($district->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Gender</label>
                                    <select name="gender" required
                                            class="w-full rounded-xl border border-slate-700/80 bg-slate-800/60 px-4 py-2.5 text-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                        <option value="">-- Pilih --</option>
                                        <option value="putra" <?= old('gender') == 'putra' ? 'selected' : '' ?>>Putra</option>
                                        <option value="putri" <?= old('gender') == 'putri' ? 'selected' : '' ?>>Putri</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Judul MSQ</label>
                                    <input type="text" name="title" required
                                           value="<?= e(old('title', '')) ?>"
                                           placeholder="Contoh: Al-Baqarah ayat 1-10"
                                           class="w-full rounded-xl border border-slate-700/80 bg-slate-800/60 px-4 py-2.5 text-white placeholder-slate-500 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Urutan</label>
                                    <input type="number" name="sort_order" min="0"
                                           value="<?= e(old('sort_order', '0')) ?>"
                                           class="w-full rounded-xl border border-slate-700/80 bg-slate-800/60 px-4 py-2.5 text-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="primary-button w-full">
                                        <?= mtq_icon('plus', 'h-4 w-4') ?>
                                        Tambah
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1.5">Deskripsi (opsional)</label>
                                <textarea name="description" rows="2"
                                          placeholder="Keterangan tambahan..."
                                          class="w-full rounded-xl border border-slate-700/80 bg-slate-800/60 px-4 py-2.5 text-white placeholder-slate-500 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"><?= e(old('description', '')) ?></textarea>
                            </div>
                        </form>
                    </div>

                    <!-- Districts with Titles -->
                    <div class="rounded-2xl border border-slate-700/50 bg-slate-900/60 backdrop-blur-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-700/50">
                            <h3 class="font-semibold text-white">Daftar Judul MSQ per Kecamatan</h3>
                            <p class="text-sm text-slate-400 mt-1">Kelola judul untuk setiap kecamatan</p>
                        </div>

                        <?php if ($districts->isEmpty()): ?>
                            <div class="p-8 text-center text-slate-400">
                                <p>Belum ada data kecamatan.</p>
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-slate-700/50">
                                <?php foreach ($districts as $district): ?>
                                    <?php
                                        $putraTitles = ($district->activeMsqDistrictTitles ?? collect())->filter(fn($t) => $t->gender === 'putra');
                                        $putriTitles = ($district->activeMsqDistrictTitles ?? collect())->filter(fn($t) => $t->gender === 'putri');
                                    ?>
                                    <div class="p-6">
                                        <h4 class="font-semibold text-white flex items-center gap-2 mb-4">
                                            <?= mtq_icon('map-pin', 'h-5 w-5 text-cyan-400') ?>
                                            <?= e($district->name) ?>
                                            <span class="ml-2 text-sm font-normal text-slate-400">(<?= $putraTitles->count() ?> Putra, <?= $putriTitles->count() ?> Putri)</span>
                                        </h4>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <!-- Putra Titles -->
                                            <div>
                                                <p class="text-sm font-medium text-blue-400 mb-2 flex items-center gap-2">
                                                    <span class="inline-flex h-2 w-2 rounded-full bg-blue-400"></span>
                                                    Putra (<?= $putraTitles->count() ?>)
                                                </p>
                                                <?php if ($putraTitles->isEmpty()): ?>
                                                    <p class="text-sm text-slate-600 italic py-2">Belum ada judul Putra</p>
                                                <?php else: ?>
                                                    <div class="flex flex-wrap gap-2">
                                                        <?php foreach ($putraTitles as $title): ?>
                                                            <div class="inline-flex items-center gap-2 rounded-lg border border-blue-500/20 bg-blue-500/10 px-3 py-2 group hover:border-blue-500/40 transition-colors">
                                                                <div class="flex h-6 w-6 items-center justify-center rounded bg-blue-500/20">
                                                                    <?= mtq_icon('book-open', 'h-3 w-3 text-blue-400') ?>
                                                                </div>
                                                                <span class="text-sm text-white"><?= e($title->title) ?></span>
                                                                <div class="flex items-center gap-1 ml-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                    <form method="POST" action="<?= e(route('admin.msq-titles.toggle', $title)) ?>" class="inline">
                                                                        <?= csrf_field() ?>
                                                                        <button type="submit" class="flex h-5 w-5 items-center justify-center rounded hover:bg-slate-700 <?= $title->is_active ? 'text-emerald-400' : 'text-slate-500' ?>" title="<?= $title->is_active ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                                            <?= $title->is_active ? mtq_icon('check-circle', 'h-4 w-4') : mtq_icon('x-circle', 'h-4 w-4') ?>
                                                                        </button>
                                                                    </form>
                                                                    <form method="POST" action="<?= e(route('admin.msq-titles.destroy', $title)) ?>" class="inline" onsubmit="return confirm('Hapus judul ini?')">
                                                                        <?= csrf_field() ?>
                                                                        <button type="submit" class="flex h-5 w-5 items-center justify-center rounded hover:bg-red-500/20 text-red-400" title="Hapus">
                                                                            <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Putri Titles -->
                                            <div>
                                                <p class="text-sm font-medium text-pink-400 mb-2 flex items-center gap-2">
                                                    <span class="inline-flex h-2 w-2 rounded-full bg-pink-400"></span>
                                                    Putri (<?= $putriTitles->count() ?>)
                                                </p>
                                                <?php if ($putriTitles->isEmpty()): ?>
                                                    <p class="text-sm text-slate-600 italic py-2">Belum ada judul Putri</p>
                                                <?php else: ?>
                                                    <div class="flex flex-wrap gap-2">
                                                        <?php foreach ($putriTitles as $title): ?>
                                                            <div class="inline-flex items-center gap-2 rounded-lg border border-pink-500/20 bg-pink-500/10 px-3 py-2 group hover:border-pink-500/40 transition-colors">
                                                                <div class="flex h-6 w-6 items-center justify-center rounded bg-pink-500/20">
                                                                    <?= mtq_icon('book-open', 'h-3 w-3 text-pink-400') ?>
                                                                </div>
                                                                <span class="text-sm text-white"><?= e($title->title) ?></span>
                                                                <div class="flex items-center gap-1 ml-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                    <form method="POST" action="<?= e(route('admin.msq-titles.toggle', $title)) ?>" class="inline">
                                                                        <?= csrf_field() ?>
                                                                        <button type="submit" class="flex h-5 w-5 items-center justify-center rounded hover:bg-slate-700 <?= $title->is_active ? 'text-emerald-400' : 'text-slate-500' ?>" title="<?= $title->is_active ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                                            <?= $title->is_active ? mtq_icon('check-circle', 'h-4 w-4') : mtq_icon('x-circle', 'h-4 w-4') ?>
                                                                        </button>
                                                                    </form>
                                                                    <form method="POST" action="<?= e(route('admin.msq-titles.destroy', $title)) ?>" class="inline" onsubmit="return confirm('Hapus judul ini?')">
                                                                        <?= csrf_field() ?>
                                                                        <button type="submit" class="flex h-5 w-5 items-center justify-center rounded hover:bg-red-500/20 text-red-400" title="Hapus">
                                                                            <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
