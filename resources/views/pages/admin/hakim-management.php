<?php
require_once __DIR__.'/../../partials/icon.php';
require_once __DIR__.'/../../partials/live-notifications.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? [];
$hakims = $hakims ?? collect();
$categories = $categories ?? [];
$filters = $filters ?? ['search' => '', 'category' => ''];

$storeUrl = route('admin.hakim.store');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Manajemen Hakim') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased"
      x-data="HakimApp()">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">

        <div class="hero-orb hero-orb-violet right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-cyan left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <!-- Sidebar Navigation -->
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('users') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Manajemen Hakim</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Akses Aktif</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($user?->name) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">
                        Kelola data hakim dan penugasan golongan.
                    </p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        <?= e($hakims->count()) ?> Hakim
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
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
                <!-- Header -->
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <div class="flex items-center gap-2 text-violet-300">
                                <?= mtq_icon('settings', 'h-5 w-5') ?>
                                <p class="text-sm font-semibold uppercase tracking-widest">Admin Console</p>
                            </div>
                            <h2 class="mt-2 text-2xl sm:text-3xl font-black tracking-tight">
                                <span class="gradient-text">Manajemen Hakim</span>
                            </h2>
                            <p class="mt-2 max-w-xl text-sm text-slate-400">
                                Kelola data hakim beserta penugasan golongan untuk penilaian.
                            </p>
                        </div>
                    </div>
                    <button type="button" x-on:click="openCreate()"
                            class="primary-button flex items-center gap-2">
                        <?= mtq_icon('plus', 'h-4 w-4') ?>
                        Tambah Hakim
                    </button>
                </header>

                <?php if(session('success')): ?>
                <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-emerald-200">
                    <div class="flex items-center gap-2">
                        <?= mtq_icon('check-circle', 'h-5 w-5') ?>
                        <?= e(session('success')) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                <div class="rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-red-200">
                    <div class="flex items-center gap-2">
                        <?= mtq_icon('alert-circle', 'h-5 w-5') ?>
                        <?= e(session('error')) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filter -->
                <section class="glass-card rounded-[2rem] p-4 sm:p-6">
                    <form method="GET" action="<?= e(route('admin.hakim.index')) ?>" class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Cari Hakim</label>
                            <input type="text" name="search" value="<?= e($filters['search']) ?>"
                                   placeholder="Nama atau asal..."
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Filter Golongan</label>
                            <select name="category" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                                <option value="">Semua Golongan</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat['id']) ?>" <?= (string) ($filters['category'] ?? '') === (string) $cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('search', 'h-4 w-4') ?>
                                Filter
                            </button>
                            <a href="<?= e(route('admin.hakim.index')) ?>" class="secondary-button">
                                Reset
                            </a>
                        </div>
                    </form>
                </section>

                <!-- Hakim List -->
                <section class="glass-card rounded-[2rem] p-4 sm:p-6">
                    <?php if($hakims->isEmpty()): ?>
                        <div class="rounded-xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                            <div class="text-slate-500 mb-4">
                                <?= mtq_icon('users', 'h-12 w-12 mx-auto opacity-50') ?>
                            </div>
                            <p class="text-slate-400 mb-4">Belum ada hakim yang terdaftar.</p>
                            <button type="button" x-on:click="openCreate()" class="secondary-button">
                                Tambah Hakim Pertama
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach($hakims as $hakim): ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/50 p-4 transition-colors hover:bg-slate-900/80">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-violet-400/30 bg-violet-500/10 text-violet-300 font-bold text-lg">
                                        <?= e(substr($hakim->nama, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-white"><?= e($hakim->nama) ?></h4>
                                        <p class="text-sm text-slate-400"><?= e($hakim->asal ?? '-') ?></p>
                                        <?php if($hakim->golongans->isNotEmpty()): ?>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <?php foreach($hakim->golongans as $golongan): ?>
                                                <span class="rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2 py-0.5 text-xs font-medium text-cyan-200">
                                                    <?= e($golongan->name) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <p class="mt-1 text-xs text-amber-400">Belum ditugaskan ke golongan manapun</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            x-on:click="openEdit(<?= e(json_encode([
                                                'id' => $hakim->id,
                                                'nama' => $hakim->nama,
                                                'asal' => $hakim->asal,
                                                'golongan_ids' => $hakim->golongans->pluck('id')->toArray(),
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"
                                            class="rounded-lg border border-slate-700 bg-slate-800/50 p-2 text-slate-400 transition-colors hover:border-amber-400/50 hover:text-amber-300"
                                            title="Edit">
                                        <?= mtq_icon('edit', 'h-4 w-4') ?>
                                    </button>
                                    <form action="<?= e(route('admin.hakim.destroy', $hakim)) ?>" method="POST" class="inline"
                                          onsubmit="return confirm('Yakin ingin menghapus hakim <?= e(addslashes($hakim->nama)) ?>?');">
                                        <?= csrf_field() ?>
                                        <?= method_field('DELETE') ?>
                                        <button type="submit"
                                                class="rounded-lg border border-slate-700 bg-slate-800/50 p-2 text-slate-400 transition-colors hover:border-red-400/50 hover:text-red-300"
                                                title="Hapus">
                                            <?= mtq_icon('trash', 'h-4 w-4') ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <!-- Form Modal -->
        <div x-show="showForm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-8"
             style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);">
            <div x-show="showForm"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.away="showForm = false"
                 class="glass-card w-full max-w-lg rounded-[2rem] p-6">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white" x-text="editItem ? 'Edit Hakim' : 'Tambah Hakim'"></h3>
                    <button type="button" x-on:click="showForm = false" class="text-slate-400 hover:text-white">
                        <?= mtq_icon('x', 'h-6 w-6') ?>
                    </button>
                </div>

                <form method="POST" x-ref="formElement">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" x-model="formMethod">

                    <div class="space-y-5">
                        <!-- Nama -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Nama Hakim <span class="text-red-400">*</span></label>
                            <input type="text" name="nama" x-model="form.nama"
                                   placeholder="Masukkan nama hakim"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400"
                                   required>
                        </div>

                        <!-- Asal -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Asal / Institusi</label>
                            <input type="text" name="asal" x-model="form.asal"
                                   placeholder="Contoh: Jakarta,Kemenag, dll"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                        </div>

                        <!-- Golongan -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Penugasan Golongan</label>
                            <p class="mb-2 text-xs text-slate-500">Pilih golongan yang akan dinilai oleh hakim ini</p>
                            <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-700 bg-slate-900/50 p-3 space-y-2">
                                <?php foreach ($categoriesGrouped as $branch => $cats): ?>
                                    <div>
                                        <p class="px-2 py-1 text-xs font-semibold text-slate-400 uppercase tracking-wider"><?= e($branch) ?></p>
                                        <div class="space-y-1">
                                            <?php foreach ($cats as $cat): ?>
                                            <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-800/50 cursor-pointer">
                                                <input type="checkbox" name="golongan_ids[]" x-model="form.golongan_ids"
                                                       value="<?= e($cat['id']) ?>"
                                                       class="h-4 w-4 rounded border-slate-600 bg-slate-800 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0">
                                                <span class="text-sm text-white"><?= e($cat['name']) ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" x-on:click="showForm = false" class="secondary-button px-5 py-3">
                            Batal
                        </button>
                        <button type="button" x-on:click="submitForm()" class="primary-button px-5 py-3">
                            <span x-text="editItem ? 'Simpan Perubahan' : 'Tambah Hakim'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>

    <script>
        function HakimApp() {
            return {
                showForm: false,
                editItem: null,
                formMethod: '',
                form: {
                    nama: '',
                    asal: '',
                    golongan_ids: [],
                },

                openEdit(data) {
                    this.editItem = data;
                    this.formMethod = 'PUT';
                    this.form = {
                        nama: data.nama || '',
                        asal: data.asal || '',
                        golongan_ids: data.golongan_ids || [],
                    };
                    this.showForm = true;
                },

                openCreate() {
                    this.editItem = null;
                    this.formMethod = '';
                    this.form = {
                        nama: '',
                        asal: '',
                        golongan_ids: [],
                    };
                    this.showForm = true;
                },

                submitForm() {
                    const form = this.$refs.formElement;
                    if (this.editItem && this.editItem.id) {
                        form.action = '/admin/hakim/' + this.editItem.id;
                    } else {
                        form.action = '<?= e($storeUrl) ?>';
                    }
                    form.submit();
                }
            };
        }
    </script>
</body>
</html>
