<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'gallery.index');
$galleryItems = $galleryItems ?? collect();
$galleryStats = $galleryStats ?? ['total' => 0, 'active' => 0, 'cover' => 0, 'contributors' => 0, 'this_week' => 0];
$galleryTotal = $galleryItems && method_exists($galleryItems, 'total') ? (int) $galleryItems->total() : 0;
$canManageAll = in_array((string) $user?->role, ['admin', 'panitia'], true);
$canDeleteAny = (string) $user?->role === 'admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Galeri MTQ') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <?php require __DIR__.'/../partials/sweet-alerts.php'; ?>
    <main
        class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8"
        x-data="{
            mobileNavOpen: false,
            focusGalleryId: <?= e(json_encode((string) request('focus_gallery_id', ''))) ?>,
            init() {
                if (! this.focusGalleryId) {
                    return;
                }

                this.$nextTick(() => {
                    const target = document.getElementById('gallery-item-' + this.focusGalleryId);

                    if (! target) {
                        return;
                    }

                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    target.classList.add('ring-2', 'ring-fuchsia-300/70', 'ring-offset-2', 'ring-offset-slate-950');

                    window.setTimeout(() => {
                        target.classList.remove('ring-2', 'ring-fuchsia-300/70', 'ring-offset-2', 'ring-offset-slate-950');
                    }, 2600);
                });
            }
        }"
        x-init="init()"
    >
        <div class="hero-orb hero-orb-cyan right-[-8rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('image') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Galeri MTQ</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Pusat Dokumentasi</p>
                    <h2 class="mt-3 text-xl font-bold text-white">Foto kegiatan siap tayang di homepage</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Upload foto dokumentasi dengan deskripsi singkat, lalu biarkan homepage publik menampilkannya sebagai galeri MTQ yang hidup.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Siap untuk audience
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Total Foto</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($galleryStats['total']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Aktif</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($galleryStats['active']) ?></p>
                    </div>
                    <a href="<?= e(route('home').'#galeri-mtq') ?>" class="secondary-button w-full">
                        <?= mtq_icon('eye', 'h-4 w-4') ?>
                        Lihat Homepage
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
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Galeri Publik MTQ</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Upload dokumentasi kegiatan untuk homepage</h2>
                            <p class="mt-2 text-sm text-slate-300">Setiap foto yang diupload di sini bisa langsung tampil sebagai cuplikan suasana pada halaman publik e-MTQ.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <?= e($galleryStats['this_week']) ?> unggahan minggu ini
                        </div>
                        <?php if ($canManageAll): ?>
                            <form method="POST" action="<?= e(route('gallery.backfill')) ?>" onsubmit="return confirm('Jalankan backfill thumbnail untuk foto lama?')" data-loading-text="Menjalankan backfill thumbnail...">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="secondary-button">
                                    <?= mtq_icon('refresh-cw', 'h-4 w-4') ?>
                                    Backfill Thumbnail
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('image') ?></div><p class="mt-4 text-sm text-slate-400">Total Galeri</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($galleryStats['total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Aktif</p><p class="mt-2 text-3xl font-extrabold text-emerald-300"><?= e($galleryStats['active']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Slideshow Homepage</p><p class="mt-2 text-3xl font-extrabold text-fuchsia-200"><?= e($galleryStats['cover']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Kontributor</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($galleryStats['contributors']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('clock') ?></div><p class="mt-4 text-sm text-slate-400">Minggu Ini</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($galleryStats['this_week']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="section-kicker">Upload Foto Dokumentasi</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Tambahkan foto kegiatan dengan deskripsi singkat</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-300">Gunakan caption pendek agar tampilannya tetap elegan di homepage. Semua foto yang diupload akan masuk ke galeri publik MTQ.</p>
                        </div>
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                            Upload ringan
                        </div>
                    </div>

                    <form method="POST" action="<?= e(route('gallery.store')) ?>" enctype="multipart/form-data" class="mt-6 space-y-4" data-loading-text="Mengunggah foto dokumentasi...">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="return_page" value="<?= e((string) request()->integer('page', 1)) ?>">
                        <div class="grid gap-4 xl:grid-cols-2">
                            <div class="xl:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Deskripsi singkat</label>
                                <input name="caption" type="text" value="<?= e(old('caption')) ?>" maxlength="255" placeholder="Contoh: Suasana pembukaan lomba tilawah anak" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <p class="mt-2 text-xs text-slate-400">Caption ini akan dipakai untuk semua foto yang dipilih pada unggahan ini.</p>
                            </div>
                            <div class="xl:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Pilih foto</label>
                                <input name="photos[]" type="file" accept="image/*" multiple data-max-files="8" data-max-files-message="Maksimal 8 foto per unggahan galeri." class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-cyan-400/10 file:px-4 file:py-2 file:text-cyan-100">
                                <p class="mt-2 text-xs text-slate-400">Format JPG, JPEG, PNG, atau WEBP. Bisa pilih lebih dari satu file sekaligus, maksimal 8 foto per unggahan dan 5 MB per foto.</p>
                            </div>
                            <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-slate-200">
                                <input type="checkbox" name="is_active" value="1" <?= old('is_active', true) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-cyan-500 focus:ring-cyan-400">
                                Tampilkan langsung di homepage
                            </label>
                            <?php if ($user?->role === 'admin'): ?>
                                <label class="inline-flex items-center gap-3 rounded-2xl border border-fuchsia-400/20 bg-fuchsia-400/10 px-4 py-3 text-sm text-fuchsia-100 xl:col-span-2">
                                    <input type="checkbox" name="is_cover_homepage" value="1" <?= old('is_cover_homepage') ? 'checked' : '' ?> class="h-4 w-4 rounded border-fuchsia-300 bg-slate-900 text-fuchsia-500 focus:ring-fuchsia-400">
                                    Pilih untuk slideshow homepage
                                </label>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('upload', 'h-4 w-4') ?>
                                Upload Dokumentasi
                            </button>
                            <a href="<?= e(route('home').'#galeri-mtq') ?>" class="secondary-button">
                                <?= mtq_icon('eye', 'h-4 w-4') ?>
                                Cek Tampilan Publik
                            </a>
                        </div>
                    </form>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Galeri Terbaru</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Foto yang sudah masuk ke sistem</h3>
                        </div>
                        <div class="badge-live"><?= mtq_icon('image', 'h-4 w-4') ?> <?= e($galleryItems->count()) ?> item</div>
                    </div>

                    <?php if (! $galleryItems || $galleryItems->count() === 0): ?>
                        <div class="mt-6 rounded-[1.5rem] border border-white/8 bg-slate-950/35 p-6 text-sm leading-7 text-slate-300">
                            Belum ada foto dokumentasi yang diupload. Setelah foto pertama masuk, galeri publik homepage akan langsung punya isi yang menarik.
                        </div>
                    <?php else: ?>
                        <div class="mt-6 flex flex-col gap-3 rounded-[1.35rem] border border-white/10 bg-slate-950/30 px-4 py-4 text-sm text-slate-300 lg:flex-row lg:items-center lg:justify-between">
                            <div class="space-y-1">
                                <p>Menampilkan <?= e(($galleryItems->firstItem() ?? 0)) ?>-<?= e(($galleryItems->lastItem() ?? 0)) ?> dari <?= e($galleryTotal) ?> foto.</p>
                                <p>Slideshow aktif: <?= e($galleryStats['cover']) ?>, batas pilihan admin: 5 foto.</p>
                            </div>
                            <?php if ($user?->role === 'admin'): ?>
                                <form id="gallery-cover-bulk-form" method="POST" action="<?= e(route('gallery.cover-bulk')) ?>" class="flex flex-col gap-3 lg:items-end">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="return_page" value="<?= e((string) request()->integer('page', 1)) ?>">
                                    <p class="text-xs leading-6 text-slate-400">Centang beberapa foto di bawah lalu simpan sebagai slideshow utama. Maksimal 5 foto.</p>
                                    <button type="submit" class="primary-button">
                                        <?= mtq_icon('spark', 'h-4 w-4') ?>
                                        Simpan pilihan slideshow
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <?php foreach ($galleryItems as $item): ?>
                                <?php
                                $imageUrl = $item->thumbnailUrl();
                                $canDelete = $canDeleteAny || ((int) ($item->uploaded_by ?? 0) === (int) ($user?->id ?? 0));
                                ?>
                                <article id="gallery-item-<?= e($item->id) ?>" tabindex="-1" class="group overflow-hidden rounded-[1.8rem] border border-white/10 bg-slate-950/45 shadow-[0_18px_45px_-28px_rgba(15,23,42,0.35)] transition">
                                    <div class="relative min-h-[240px]">
                                        <?php if ($user?->role === 'admin'): ?>
                                            <label class="absolute right-4 top-4 z-10 inline-flex items-center gap-2 rounded-full border border-white/10 bg-slate-950/70 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/90 backdrop-blur">
                                                <input
                                                    type="checkbox"
                                                    form="gallery-cover-bulk-form"
                                                    name="gallery_cover_ids[]"
                                                    value="<?= e($item->id) ?>"
                                                    <?= $item->is_cover_homepage ? 'checked' : '' ?>
                                                    class="h-4 w-4 rounded border-fuchsia-300 bg-slate-900 text-fuchsia-500 focus:ring-fuchsia-400"
                                                >
                                                Pilih
                                            </label>
                                        <?php endif; ?>
                                        <?php if ($imageUrl): ?>
                                            <img src="<?= e($imageUrl) ?>" alt="<?= e($item->caption) ?>" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                                        <?php else: ?>
                                            <div class="flex min-h-[240px] items-center justify-center bg-slate-900/80 text-sm text-slate-400">
                                                Gambar tidak tersedia
                                            </div>
                                        <?php endif; ?>
                                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/10 to-transparent"></div>
                                        <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                                            <?php if ($item->is_cover_homepage): ?>
                                                <span class="inline-flex rounded-full border border-fuchsia-300/20 bg-fuchsia-400/20 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-fuchsia-100">
                                                    Slideshow #<?= e(max(1, (int) $item->sort_order)) ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="inline-flex rounded-full border border-white/10 bg-slate-950/50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/90">
                                                <?= e($item->is_active ? 'Aktif' : 'Nonaktif') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="space-y-3 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <p class="text-xs text-slate-500"><?= e(optional($item->created_at)->format('d M Y H:i') ?? '-') ?></p>
                                        </div>
                                        <h4 class="text-lg font-bold text-white"><?= e($item->caption) ?></h4>
                                        <p class="text-sm leading-6 text-slate-300">Diunggah oleh <?= e($item->uploader?->name ?? 'Sistem') ?>.</p>
                                        <div class="flex flex-wrap gap-3">
                                            <?php if ($user?->role === 'admin'): ?>
                                                <?php if ($item->is_cover_homepage): ?>
                                                    <form method="POST" action="<?= e(route('gallery.cover-release', $item)) ?>">
                                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="return_page" value="<?= e((string) request()->integer('page', 1)) ?>">
                                                        <button type="submit" class="secondary-button text-sm border-fuchsia-300/40 bg-fuchsia-400/12 text-fuchsia-100">
                                                            <?= mtq_icon('spark', 'h-4 w-4') ?>
                                                            Keluarkan dari slideshow
                                                        </button>
                                                    </form>
                                                    <div class="flex items-center gap-2">
                                                        <form method="POST" action="<?= e(route('gallery.cover-move', ['activityDocumentation' => $item, 'direction' => 'up'])) ?>">
                                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                            <input type="hidden" name="return_page" value="<?= e((string) request()->integer('page', 1)) ?>">
                                                            <button type="submit" class="secondary-button px-3 py-2 text-sm" title="Pindah ke atas" aria-label="Pindah ke atas">
                                                                <?= mtq_icon('arrow-up', 'h-4 w-4') ?>
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="<?= e(route('gallery.cover-move', ['activityDocumentation' => $item, 'direction' => 'down'])) ?>">
                                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                            <input type="hidden" name="return_page" value="<?= e((string) request()->integer('page', 1)) ?>">
                                                            <button type="submit" class="secondary-button px-3 py-2 text-sm" title="Pindah ke bawah" aria-label="Pindah ke bawah">
                                                                <?= mtq_icon('arrow-down', 'h-4 w-4') ?>
                                                            </button>
                                                        </form>
                                                    </div>
                                            <?php else: ?>
                                                    <form method="POST" action="<?= e(route('gallery.cover-main', $item)) ?>">
                                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="return_page" value="<?= e((string) request()->integer('page', 1)) ?>">
                                                        <button type="submit" class="secondary-button text-sm">
                                                            <?= mtq_icon('spark', 'h-4 w-4') ?>
                                                            Masukkan ke slideshow
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($canDelete): ?>
                                                <form method="POST" action="<?= e(route('gallery.destroy', $item)) ?>" onsubmit="return confirm('Hapus foto ini dari galeri?')">
                                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="return_page" value="<?= e((string) request()->integer('page', 1)) ?>">
                                                    <button type="submit" class="secondary-button text-sm">
                                                        <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                        Hapus
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($galleryItems && method_exists($galleryItems, 'hasPages') && $galleryItems->hasPages()): ?>
                            <?php
                                $currentPage = $galleryItems->currentPage();
                                $lastPage = $galleryItems->lastPage();
                                $windowStart = max(1, $currentPage - 2);
                                $windowEnd = min($lastPage, $currentPage + 2);
                            ?>
                            <nav class="mt-8 flex flex-col gap-3 rounded-[1.5rem] border border-slate-800 bg-slate-950/45 px-4 py-4 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination galeri">
                                <div class="text-sm text-slate-400">
                                    Halaman <?= e($currentPage) ?> dari <?= e($lastPage) ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        href="<?= e($galleryItems->previousPageUrl() ?: '#') ?>"
                                        class="inline-flex min-h-11 items-center justify-center rounded-2xl border px-4 py-2 text-sm font-semibold transition
                                            <?= $galleryItems->onFirstPage() ? 'pointer-events-none border-slate-800 bg-slate-900/60 text-slate-500' : 'border-cyan-400/18 bg-slate-900/80 text-cyan-100 hover:border-cyan-300/40 hover:bg-slate-900' ?>"
                                        aria-disabled="<?= $galleryItems->onFirstPage() ? 'true' : 'false' ?>"
                                        aria-label="Halaman sebelumnya"
                                        rel="prev"
                                    >
                                        Previous
                                    </a>

                                    <?php if ($windowStart > 1): ?>
                                        <a href="<?= e($galleryItems->url(1)) ?>" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900/60 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-300/40 hover:bg-slate-900">1</a>
                                        <?php if ($windowStart > 2): ?>
                                            <span class="px-1 text-sm text-slate-500">...</span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($page = $windowStart; $page <= $windowEnd; $page++): ?>
                                        <a
                                            href="<?= e($galleryItems->url($page)) ?>"
                                            class="inline-flex min-h-11 items-center justify-center rounded-2xl border px-4 py-2 text-sm font-semibold transition
                                                <?= $page === $currentPage ? 'border-cyan-300/50 bg-cyan-400/15 text-cyan-50 shadow-[0_12px_28px_-18px_rgba(34,211,238,0.5)]' : 'border-slate-800 bg-slate-900/60 text-slate-200 hover:border-cyan-300/40 hover:bg-slate-900' ?>"
                                            aria-current="<?= $page === $currentPage ? 'page' : 'false' ?>"
                                        >
                                            <?= e($page) ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($windowEnd < $lastPage): ?>
                                        <?php if ($windowEnd < $lastPage - 1): ?>
                                            <span class="px-1 text-sm text-slate-500">...</span>
                                        <?php endif; ?>
                                        <a href="<?= e($galleryItems->url($lastPage)) ?>" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900/60 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-300/40 hover:bg-slate-900"><?= e($lastPage) ?></a>
                                    <?php endif; ?>

                                    <a
                                        href="<?= e($galleryItems->nextPageUrl() ?: '#') ?>"
                                        class="inline-flex min-h-11 items-center justify-center rounded-2xl border px-4 py-2 text-sm font-semibold transition
                                            <?= $galleryItems->hasMorePages() ? 'border-cyan-400/18 bg-slate-900/80 text-cyan-100 hover:border-cyan-300/40 hover:bg-slate-900' : 'pointer-events-none border-slate-800 bg-slate-900/60 text-slate-500' ?>"
                                        aria-disabled="<?= $galleryItems->hasMorePages() ? 'false' : 'true' ?>"
                                        aria-label="Halaman berikutnya"
                                        rel="next"
                                    >
                                        Next
                                    </a>
                                </div>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
