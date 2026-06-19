<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? [];
$districts = $districts ?? collect();
$categories = $categories ?? collect();

// Build simple flat navigation from grouped navigation
$flatNavItems = [];
foreach ($navigation as $item) {
    if (($item['type'] ?? '') === 'group' && !empty($item['children'])) {
        foreach ($item['children'] as $child) {
            $flatNavItems[] = $child;
        }
    } elseif (($item['type'] ?? '') !== 'group') {
        $flatNavItems[] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <!-- Sidebar -->
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition-all duration-300 ease-out lg:static lg:inset-auto lg:block -translate-x-full opacity-0 lg:translate-x-0 lg:opacity-100"
                :class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-full opacity-0'"
                x-show="mobileNavOpen || window.innerWidth >= 1024"
                @resize.window="if (window.innerWidth >= 1024) { mobileNavOpen = true }">
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
                    <p class="mt-2 text-sm leading-6 text-slate-300">Kelola judul maqra per kecamatan untuk MSQ (Syarhil Qur'an).</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                        District-Based
                    </div>
                </div>

                <!-- Simple Navigation -->
                <nav class="mt-6 space-y-1 overflow-y-auto max-h-[calc(100vh-400px)]">
                    <?php foreach ($flatNavItems as $navItem): ?>
                        <?php $isActive = !empty($navItem['active']); ?>
                        <a href="<?= e((string) ($navItem['href'] ?? '#')) ?>"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                                  <?= $isActive
                                      ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/10 border border-cyan-500/30 text-white shadow-lg shadow-cyan-500/10'
                                      : 'text-slate-400 hover:text-white hover:bg-slate-800/50 border border-transparent hover:border-slate-700/30' ?>">
                            <span class="flex-shrink-0 <?= $isActive ? 'text-cyan-400' : 'text-slate-500' ?>">
                                <?= mtq_icon((string) ($navItem['icon'] ?? 'spark'), 'h-5 w-5') ?>
                            </span>
                            <span class="flex-1 truncate"><?= e((string) ($navItem['label'] ?? 'Menu')) ?></span>
                            <?php if ($isActive): ?>
                                <span class="flex-shrink-0 w-2 h-2 rounded-full bg-cyan-400 shadow-lg shadow-cyan-400/50"></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <!-- Mobile Menu Toggle Button -->
            <button
                type="button"
                class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-cyan-500 shadow-lg shadow-cyan-500/30 transition-all duration-300 lg:hidden"
                :class="mobileNavOpen ? 'opacity-0 scale-75 pointer-events-none' : 'opacity-100 scale-100'"
                x-on:click="mobileNavOpen = true"
                aria-label="Buka menu">
                <?= mtq_icon('menu', 'h-6 w-6 text-white') ?>
            </button>

            <!-- Main Content -->
            <div class="min-w-0 space-y-6">
                <!-- Header with Mobile Menu Button -->
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Syarhil Qur'an</p>
                            <h2 class="mt-2 text-2xl font-bold text-white">Kelola Judul MSQ</h2>
                            <p class="mt-1 text-sm text-slate-300">Kelola judul maqra per kecamatan</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="<?= e(route('admin.content')) ?>" class="secondary-button">
                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                            Kembali ke Konten
                        </a>
                    </div>
                </header>

                <!-- Content -->
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
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                            <p class="text-sm text-slate-400 mt-1">Klik judul untuk mengedit, atau gunakan tombol aksi untuk menghapus/menonaktifkan</p>
                        </div>

                        <?php if ($districts->isEmpty()): ?>
                            <div class="p-8 text-center text-slate-400">
                                <p>Belum ada data kecamatan. Silakan sinkronisasi kecamatan terlebih dahulu.</p>
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-slate-700/50">
                                <?php foreach ($districts as $district): ?>
                                    <?php $titles = $district->activeMsqDistrictTitles ?? collect(); ?>
                                    <div class="p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="font-semibold text-white flex items-center gap-2">
                                                <?= mtq_icon('map-pin', 'h-5 w-5 text-cyan-400') ?>
                                                <?= e($district->name) ?>
                                                <span class="ml-2 text-sm font-normal text-slate-400">
                                                    (<?= $titles->count() ?> judul)
                                                </span>
                                            </h4>
                                        </div>

                                        <?php if ($titles->isEmpty()): ?>
                                            <p class="text-sm text-slate-500 italic py-2">Belum ada judul MSQ untuk kecamatan ini</p>
                                        <?php else: ?>
                                            <div class="flex flex-wrap gap-3">
                                                <?php foreach ($titles as $title): ?>
                                                    <div class="inline-flex items-center gap-3 rounded-xl border border-slate-700/80 bg-slate-800/60 px-4 py-3 group hover:border-cyan-500/30 transition-colors">
                                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10">
                                                            <?= mtq_icon('book-open', 'h-5 w-5 text-amber-400') ?>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="font-medium text-white truncate"><?= e($title->title) ?></p>
                                                            <?php if ($title->description): ?>
                                                                <p class="text-xs text-slate-400 truncate"><?= e($title->description) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="flex items-center gap-1 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <!-- Toggle Active -->
                                                            <form method="POST" action="<?= e(route('admin.msq-titles.toggle', $title)) ?>" class="inline">
                                                                <?= csrf_field() ?>
                                                                <button type="submit"
                                                                        class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-slate-700 <?= $title->is_active ? 'text-emerald-400' : 'text-slate-500' ?>"
                                                                        title="<?= $title->is_active ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                                    <?= $title->is_active ? mtq_icon('check-circle', 'h-5 w-5') : mtq_icon('x-circle', 'h-5 w-5') ?>
                                                                </button>
                                                            </form>
                                                            <!-- Delete -->
                                                            <form method="POST" action="<?= e(route('admin.msq-titles.destroy', $title)) ?>" class="inline" onsubmit="return confirm('Hapus judul &quot;<?= e($title->title) ?>&quot;?')">
                                                                <?= csrf_field() ?>
                                                                <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-red-500/20 text-red-400" title="Hapus">
                                                                    <?= mtq_icon('trash', 'h-5 w-5') ?>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Category Info -->
                    <?php if ($categories->isNotEmpty()): ?>
                    <div class="rounded-2xl border border-slate-700/50 bg-slate-900/60 p-6 backdrop-blur-xl">
                        <div class="mb-4">
                            <h3 class="font-semibold text-white">Kategori MSQ (Syarhil Qur'an)</h3>
                            <p class="text-sm text-slate-400 mt-1">Golongan yang menggunakan sistem maqra berbasis kecamatan</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($categories as $category): ?>
                                <div class="flex items-center gap-3 rounded-xl border border-slate-700/80 bg-slate-800/40 p-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10">
                                        <?= mtq_icon('user-group', 'h-6 w-6 text-amber-400') ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-white"><?= e($category->name) ?></p>
                                        <p class="text-xs text-slate-400"><?= e($category->branch) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
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
