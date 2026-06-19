<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? [];
$locations = $locations ?? collect();
$categories = $categories ?? collect();
$locationStats = $locationStats ?? [];
$categoryGroups = $categories->groupBy('branch');
$nextSortOrder = (int) ($locations->max('sort_order') ?? 0) + 1;

if (! function_exists('mtq_location_kind_label')) {
    function mtq_location_kind_label(string $venueName): string
    {
        $haystack = mb_strtolower($venueName);

        return match (true) {
            str_contains($haystack, 'masjid') => 'Masjid',
            str_contains($haystack, 'sma')
                || str_contains($haystack, 'smp')
                || str_contains($haystack, 'mts')
                || str_contains($haystack, 'sdn') => 'Sekolah',
            default => 'Lapangan / Komunitas',
        };
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Lokasi MTQ') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/sweet-alerts.php'; ?>
    <main
        class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8"
        x-data="{
            mobileNavOpen: false,
            editOpen: false,
            editing: {
                id: null,
                label: '',
                venue_name: '',
                map_url: '',
                sort_order: <?= e($nextSortOrder) ?>,
                category_ids: [],
                photo_url: '',
                photo_thumb_url: ''
            },
            openEdit(location) {
                this.editing = {
                    id: location.id,
                    label: location.label || '',
                    venue_name: location.venue_name || '',
                    map_url: location.map_url || '',
                    sort_order: location.sort_order || 1,
                    category_ids: location.category_ids || [],
                    photo_url: location.photo_url || '',
                    photo_thumb_url: location.photo_thumb_url || ''
                };
                this.editOpen = true;
            },
            closeEdit() {
                this.editOpen = false;
            }
        }"
    >
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('map-pin') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Lokasi MTQ</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden hamburger-btn" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Sinkron Golongan</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($locationStats['total_locations'] ?? 0) ?> venue aktif</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Lokasi dikelola dari database dan terhubung ke golongan lewat relasi pivot. Foto hero dan thumbnail memakai aset lokal ringan.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Database Ready
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Relasi Golongan</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($locationStats['total_links'] ?? 0) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Golongan Tersedia</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($locationStats['categories_total'] ?? 0) ?></p>
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
                            <p class="section-kicker">Pengaturan Lokasi</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Kelola lokasi lomba MTQ</h2>
                            <p class="mt-2 text-sm text-slate-300"><?= e($rolePanel['description'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Terkoneksi ke homepage
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('map-pin') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Total venue</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($locationStats['total_locations'] ?? 0) ?></p>
                    </div>
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('link-external') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Tersambung Maps</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($locationStats['locations_with_maps'] ?? 0) ?></p>
                    </div>
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('book-open') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Golongan tersentuh</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($locationStats['categories_total'] ?? 0) ?></p>
                    </div>
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('layers') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Relasi aktif</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($locationStats['total_links'] ?? 0) ?></p>
                    </div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Tambah Lokasi</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Form venue baru</h3>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">Urutan lokasi menentukan foto lokal yang dipakai homepage. Pilih minimal satu golongan agar relasinya langsung sinkron dengan halaman publik.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <form method="POST" action="<?= e(route('locations.sync')) ?>">
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit" class="secondary-button">
                                    <?= mtq_icon('refresh-cw', 'h-4 w-4') ?>
                                    Sinkron Ulang
                                </button>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="<?= e(route('locations.store')) ?>" enctype="multipart/form-data" class="mt-6 space-y-5 rounded-[1.75rem] border border-white/10 bg-slate-950/50 p-5">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Golongan / Label</label>
                                <input name="label" type="text" value="<?= e(old('label', '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Tilawah Remaja dan Dewasa">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Venue</label>
                                <input name="venue_name" type="text" value="<?= e(old('venue_name', '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Mimbar Utama / Lapangan Simabur">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Urutan</label>
                                <input name="sort_order" type="number" min="1" step="1" value="<?= e(old('sort_order', (string) $nextSortOrder)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Google Maps URL</label>
                            <input name="map_url" type="url" value="<?= e(old('map_url', '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="https://maps.app.goo.gl/...">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Foto Venue</label>
                            <input name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 file:mr-4 file:rounded-xl file:border-0 file:bg-cyan-400/15 file:px-4 file:py-2 file:text-cyan-100 hover:file:bg-cyan-400/25">
                            <p class="mt-2 text-xs text-slate-400">Opsional. Jika diisi, foto akan dikompres otomatis ke ukuran ringan.</p>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label class="block text-sm font-semibold text-slate-200">Golongan Terkait</label>
                                <p class="text-xs text-slate-400">Pilih minimal satu golongan</p>
                            </div>
                            <?php $createSelectedCategoryIds = collect(old('category_ids', []))->map(fn ($value): string => (string) $value)->all(); ?>
                            <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                <?php foreach ($categoryGroups as $branch => $branchCategories): ?>
                                    <div class="rounded-[1.4rem] border border-white/10 bg-white/5 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200"><?= e($branch) ?></p>
                                        <div class="mt-3 space-y-2">
                                            <?php foreach ($branchCategories as $category): ?>
                                                <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-slate-950/40 px-3 py-2 transition hover:border-cyan-300/30">
                                                    <input
                                                        type="checkbox"
                                                        name="category_ids[]"
                                                        value="<?= e($category->id) ?>"
                                                        <?= in_array((string) $category->id, $createSelectedCategoryIds, true) ? 'checked' : '' ?>
                                                        class="mt-1 rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-400/20"
                                                    >
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-white"><?= e($category->name) ?></span>
                                                        <span class="block text-xs text-slate-400"><?= e($category->notes ?: $category->round) ?></span>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                Simpan Lokasi
                            </button>
                        </div>
                    </form>
                </section>

                <section class="space-y-4">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="section-kicker">Daftar Venue</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Edit lokasi yang sudah tersimpan</h3>
                        </div>
                        <p class="text-sm text-slate-400">Setiap update langsung memengaruhi slideshow homepage.</p>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                        <?php foreach ($locations as $location): ?>
                            <?php
                                $locationCategoryIds = $location->categories->pluck('id')->map(fn ($value): string => (string) $value)->all();
                                $photoUrl = filled($location->photo_path) ? asset($location->photo_path) : '';
                                $thumbUrl = filled($location->photo_thumb_path) ? asset($location->photo_thumb_path) : $photoUrl;
                                $locationPayload = [
                                    'id' => (int) $location->id,
                                    'label' => (string) $location->label,
                                    'venue_name' => (string) $location->venue_name,
                                    'map_url' => (string) ($location->map_url ?? ''),
                                    'sort_order' => (int) $location->sort_order,
                                    'category_ids' => array_map('intval', $locationCategoryIds),
                                    'photo_url' => $photoUrl,
                                    'photo_thumb_url' => $thumbUrl,
                                ];
                            ?>
                            <article class="overflow-hidden rounded-[1.8rem] border border-white/10 bg-slate-950/65 shadow-[0_18px_45px_-28px_rgba(15,23,42,0.45)]">
                                <div class="relative min-h-[250px]">
                                    <?php if ($photoUrl !== ''): ?>
                                        <img src="<?= e($photoUrl) ?>" alt="<?= e($location->venue_name) ?>" class="h-full w-full object-cover object-center">
                                    <?php else: ?>
                                        <div class="flex h-full min-h-[250px] items-center justify-center bg-slate-900 text-slate-500">
                                            Foto lokal belum tersedia
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-transparent to-transparent"></div>
                                    <div class="absolute left-4 top-4">
                                        <span class="rounded-full border border-white/10 bg-slate-950/50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-white">No. <?= e(str_pad((string) $location->sort_order, 2, '0', STR_PAD_LEFT)) ?></span>
                                    </div>
                                    <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                                        <div class="max-w-lg rounded-[1.3rem] border border-white/10 bg-slate-950/45 p-4 backdrop-blur">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-100/90">Venue MTQ</p>
                                            <h4 class="mt-2 text-2xl font-black tracking-tight text-white"><?= e($location->venue_name) ?></h4>
                                            <p class="mt-2 text-sm text-slate-300"><?= e($location->label) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-5 sm:p-6">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="section-kicker">Venue</p>
                                        <span class="status-pill"><?= e(mtq_location_kind_label((string) $location->venue_name)) ?></span>
                                    </div>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <?php foreach ($location->categories->take(4) as $category): ?>
                                                <span class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100"><?= e($category->branch.' - '.$category->name) ?></span>
                                            <?php endforeach; ?>
                                            <?php if ($location->categories->count() > 4): ?>
                                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-200">+<?= e($location->categories->count() - 4) ?> lagi</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                            <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Urutan</p>
                                                <p class="mt-2 text-sm font-semibold text-white"><?= e($location->sort_order) ?></p>
                                            </div>
                                            <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Golongan</p>
                                                <p class="mt-2 text-sm font-semibold text-white"><?= e($location->categories->count()) ?></p>
                                            </div>
                                            <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Maps</p>
                                                <p class="mt-2 text-sm font-semibold text-white"><?= filled($location->map_url) ? 'Tersedia' : 'Belum ada' ?></p>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            class="mt-5 secondary-button w-full justify-center"
                                            x-on:click="openEdit(<?= e(json_encode($locationPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>)"
                                        >
                                            <?= mtq_icon('pencil', 'h-4 w-4') ?>
                                            Edit Lokasi
                                        </button>

                                        <form method="POST" action="<?= e(route('locations.destroy', $location)) ?>" class="mt-4" onsubmit="return confirm('Hapus lokasi ini? Relasi golongan juga ikut terhapus.')">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <button type="submit" class="secondary-button w-full justify-center border-rose-400/20 bg-rose-400/10 text-rose-100 hover:border-rose-300/40 hover:bg-rose-400/20">
                                                <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                Hapus Lokasi
                                            </button>
                                        </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>

        <div
            x-cloak
            x-show="editOpen"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/80 px-4 py-6 backdrop-blur-sm"
        >
            <div class="relative flex w-full max-w-6xl max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-slate-950 shadow-[0_30px_90px_-35px_rgba(14,165,233,0.55)]">
                <div class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-white/10 bg-slate-950/95 px-5 py-4 backdrop-blur sm:px-6">
                    <div>
                        <p class="section-kicker">Edit Lokasi</p>
                        <h3 class="mt-2 text-2xl font-black text-white" x-text="editing.venue_name || 'Lokasi MTQ'"></h3>
                        <p class="mt-1 text-sm text-slate-400" x-text="editing.label || 'Atur label, Maps, foto, dan relasi golongan di sini.'"></p>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2" x-on:click="closeEdit()">
                        <?= mtq_icon('x', 'h-4 w-4') ?>
                    </button>
                </div>

                <form method="POST" enctype="multipart/form-data" class="grid min-h-0 flex-1 gap-0 overflow-hidden lg:grid-cols-[1.05fr_0.95fr]" x-bind:action="editing.id ? ('<?= e(route('locations.update', ['competitionLocation' => '___ID___'])) ?>'.replace('___ID___', editing.id)) : '#'">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <div class="space-y-5 overflow-y-auto p-5 sm:p-6 lg:p-7">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Label</label>
                                <input x-model="editing.label" name="label" type="text" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Venue</label>
                                <input x-model="editing.venue_name" name="venue_name" type="text" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Urutan</label>
                                <input x-model="editing.sort_order" name="sort_order" type="number" min="1" step="1" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">URL Maps</label>
                                <input x-model="editing.map_url" name="map_url" type="url" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-[180px_minmax(0,1fr)] sm:items-start">
                            <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70">
                                <template x-if="editing.photo_thumb_url || editing.photo_url">
                                    <img x-bind:src="editing.photo_thumb_url || editing.photo_url" x-bind:alt="editing.venue_name || 'Foto venue'" class="h-40 w-full object-cover object-center">
                                </template>
                                <template x-if="!editing.photo_thumb_url && !editing.photo_url">
                                    <div class="flex h-40 items-center justify-center text-xs text-slate-500">Belum ada foto</div>
                                </template>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Ganti Foto Venue</label>
                                    <input name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 file:mr-4 file:rounded-xl file:border-0 file:bg-cyan-400/15 file:px-4 file:py-2 file:text-cyan-100 hover:file:bg-cyan-400/25">
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="submit"
                                        formaction="<?= e(route('locations.photo.destroy', ['competitionLocation' => '___ID___'])) ?>"
                                        x-bind:formaction="editing.id ? ('<?= e(route('locations.photo.destroy', ['competitionLocation' => '___ID___'])) ?>'.replace('___ID___', editing.id)) : '#'"
                                        formmethod="POST"
                                        formnovalidate
                                        onclick="return confirm('Hapus foto venue ini?')"
                                        class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-4 py-2 text-sm text-rose-100 hover:border-rose-300/40 hover:bg-rose-400/20"
                                    >
                                        <?= mtq_icon('trash', 'h-4 w-4') ?>
                                        Hapus Foto
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <?php foreach ($categoryGroups as $branch => $branchCategories): ?>
                                <div class="rounded-[1.3rem] border border-white/10 bg-white/5 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200"><?= e($branch) ?></p>
                                    <div class="mt-3 space-y-2">
                                        <?php foreach ($branchCategories as $category): ?>
                                            <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-slate-950/40 px-3 py-2 transition hover:border-cyan-300/30">
                                                <input
                                                    type="checkbox"
                                                    name="category_ids[]"
                                                    value="<?= e($category->id) ?>"
                                                    x-model="editing.category_ids"
                                                    class="mt-1 rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-400/20"
                                                >
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-white"><?= e($category->name) ?></span>
                                                    <span class="block text-xs text-slate-400"><?= e($category->notes ?: $category->round) ?></span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="border-t border-white/10 bg-slate-950/80 p-5 sm:p-6 lg:border-l lg:border-t-0 lg:p-7 lg:overflow-y-auto">
                        <div class="rounded-[1.5rem] border border-cyan-400/10 bg-gradient-to-br from-slate-950 via-slate-900/80 to-sky-950/40 p-5">
                            <p class="section-kicker">Preview Cepat</p>
                            <h4 class="mt-2 text-2xl font-bold text-white" x-text="editing.venue_name || '-'"></h4>
                            <p class="mt-2 text-sm text-slate-300" x-text="editing.label || '-'"></p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Urutan</p>
                                    <p class="mt-2 text-sm font-semibold text-white" x-text="editing.sort_order || '-'"></p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Golongan</p>
                                    <p class="mt-2 text-sm font-semibold text-white" x-text="editing.category_ids ? editing.category_ids.length : 0"></p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Maps</p>
                                    <p class="mt-2 text-sm font-semibold text-white" x-text="editing.map_url ? 'Tersedia' : 'Belum ada'"></p>
                                </div>
                            </div>
                        </div>

                        <div class="sticky bottom-0 mt-5 flex flex-wrap gap-3 border-t border-white/10 bg-slate-950/95 pt-4 backdrop-blur">
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                Simpan Perubahan
                            </button>
                            <a
                                target="_blank"
                                rel="noreferrer"
                                class="secondary-button"
                                x-bind:href="editing.sort_order ? ('<?= e(route('home', ['venue' => '___VENUE___'])) ?>'.replace('___VENUE___', editing.sort_order)) : '<?= e(route('home')) ?>'"
                            >
                                <?= mtq_icon('eye', 'h-4 w-4') ?>
                                Preview di Homepage
                            </a>
                            <button type="button" class="secondary-button" x-on:click="closeEdit()">
                                <?= mtq_icon('x', 'h-4 w-4') ?>
                                Tutup
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
