<?php
require_once __DIR__.'/../../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$rankingSettings = $rankingSettings ?? collect();
$categories = $categories ?? [];

// Pre-generate URLs for JavaScript
$storeUrl = route('ranking.settings.store');
$updateUrlBase = route('ranking.settings.update', ['rankingSetting' => '__ID__']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Pengaturan Ranking') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased"
      x-data="rankingForm()">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">

        <div class="hero-orb hero-orb-amber right-[-7rem] top-10 h-72 w-72"></div>

        <!-- Header -->
        <header class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="icon-chip"><?= mtq_icon('trophy') ?></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('settings', 'h-4 w-4 text-amber-300') ?>
                            <p class="section-kicker">Admin Console</p>
                        </div>
                        <h2 class="mt-1 sm:mt-2 text-xl sm:text-3xl font-black tracking-tight">
                            <span class="gradient-text">Pengaturan Ranking</span>
                        </h2>
                        <p class="mt-1 text-sm text-slate-400">
                            Konfigurasikan ranking yang ditampilkan di halaman hasil nilai.
                            Admin dapat mengatur ranking berdasarkan golongan, jadwal, dan babak penilaian.
                        </p>
                    </div>
                </div>
                <button type="button"
                        @click="openCreate()"
                        class="primary-button flex items-center gap-2 justify-center">
                    <?= mtq_icon('plus', 'h-4 w-4') ?>
                    Tambah Ranking
                </button>
            </div>
        </header>

        <?php if(session('success')): ?>
        <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-emerald-200">
            <div class="flex items-center gap-2">
                <?= mtq_icon('check-circle', 'h-5 w-5') ?>
                <?= e(session('success')) ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
        <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-red-200">
            <div class="flex items-center gap-2">
                <?= mtq_icon('alert-circle', 'h-5 w-5') ?>
                <?= e(session('error')) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Settings List -->
        <section class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white">Daftar Ranking</h3>
                <span class="text-sm text-slate-400">
                    <?= e($rankingSettings->where('is_active')->count()) ?> aktif /
                    <?= e($rankingSettings->count()) ?> total
                </span>
            </div>

            <?php if($rankingSettings->isEmpty()): ?>
                <div class="rounded-xl border border-dashed border-slate-700 bg-slate-900/50 p-8 text-center">
                    <div class="text-slate-500 mb-3">
                        <?= mtq_icon('trophy', 'h-12 w-12 mx-auto opacity-50') ?>
                    </div>
                    <p class="text-slate-400 mb-4">Belum ada pengaturan ranking.</p>
                    <button type="button"
                            @click="openCreate()"
                            class="secondary-button">
                        Tambah Ranking Pertama
                    </button>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach($rankingSettings as $setting): ?>
                    <div class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/50 p-4 transition-colors hover:bg-slate-900/80">
                        <div class="flex items-center gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl <?= $setting->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-700/50 text-slate-400' ?>">
                                <?= $setting->is_active ? mtq_icon('check-circle', 'h-5 w-5') : mtq_icon('x-circle', 'h-5 w-5') ?>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-white <?= !$setting->is_active ? 'opacity-50' : '' ?>">
                                        <?= e($setting->name) ?>
                                    </h4>
                                    <?php if($setting->category): ?>
                                        <span class="rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2 py-0.5 text-xs font-medium text-cyan-200">
                                            <?= e($setting->category->name) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="rounded-full border border-purple-400/30 bg-purple-400/10 px-2 py-0.5 text-xs font-medium text-purple-200">
                                            Semua Golongan
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                    <span><?= e($setting->display_label) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="<?= e(route('ranking.settings.toggle', $setting)) ?>"
                               class="rounded-lg border border-slate-700 bg-slate-800/50 p-2 text-slate-400 transition-colors hover:border-cyan-400/50 hover:text-cyan-300"
                               title="<?= $setting->is_active ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                <?= $setting->is_active ? mtq_icon('eye-off', 'h-4 w-4') : mtq_icon('eye', 'h-4 w-4') ?>
                            </a>
                            <button type="button"
                                    @click="openEdit(<?= e(json_encode([
                                        'id' => $setting->id,
                                        'name' => $setting->name,
                                        'competition_category_id' => $setting->competition_category_id,
                                        'gender' => $setting->gender,
                                        'appearance_day' => $setting->appearance_day,
                                        'judging_round' => $setting->judging_round,
                                        'sort_order' => $setting->sort_order,
                                        'is_active' => (bool)$setting->is_active,
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)"
                                    class="rounded-lg border border-slate-700 bg-slate-800/50 p-2 text-slate-400 transition-colors hover:border-amber-400/50 hover:text-amber-300"
                                    title="Edit">
                                <?= mtq_icon('edit', 'h-4 w-4') ?>
                            </button>
                            <form action="<?= e(route('ranking.settings.destroy', $setting)) ?>" method="POST" class="inline"
                                  onsubmit="return confirm('Yakin ingin menghapus pengaturan ranking ini?');">
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
                    <h3 class="text-xl font-bold text-white" x-text="editItem ? 'Edit Ranking' : 'Tambah Ranking'"></h3>
                    <button type="button" @click="showForm = false" class="text-slate-400 hover:text-white">
                        <?= mtq_icon('x', 'h-6 w-6') ?>
                    </button>
                </div>

                <form method="POST" x-ref="formElement">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" x-model="formMethod">

                    <div class="space-y-5">
                        <!-- Name -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Nama Ranking</label>
                            <input type="text"
                                   name="name"
                                   x-model="form.name"
                                   placeholder="Contoh: Ranking Putra Hari 1"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400"
                                   required>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Golongan</label>
                            <select name="competition_category_id"
                                    x-model="form.competition_category_id"
                                    @change="loadScheduleDays()"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                                <option value="">Semua Golongan</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= e($cat['id']) ?>"><?= e($cat['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Gender -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Jenis Kelamin</label>
                            <select name="gender"
                                    x-model="form.gender"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                                <option value="all">Putra & Putri</option>
                                <option value="putra">Putra</option>
                                <option value="putri">Putri</option>
                            </select>
                        </div>

                        <!-- Schedule Day -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Jadwal / Sesi</label>
                            <select name="appearance_day"
                                    x-model="form.appearance_day"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                                <option value="">Keseluruhan (Semua Hari)</option>
                                <template x-for="day in scheduleDays" :key="day.index">
                                    <option :value="day.index" x-text="day.display || day.name"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Pilih jadwal penampilan atau kosongkan untuk ranking keseluruhan</p>
                        </div>

                        <!-- Judging Round -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Babak Penilaian</label>
                            <select name="judging_round"
                                    x-model="form.judging_round"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                                <option value="Penyisihan">Penyisihan</option>
                                <option value="Final">Final</option>
                            </select>
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Urutan Tampilan</label>
                            <input type="number"
                                   name="sort_order"
                                   x-model="form.sort_order"
                                   min="0"
                                   class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                            <p class="mt-1 text-xs text-slate-500">Angka lebih kecil ditampilkan lebih dulu</p>
                        </div>

                        <!-- Active -->
                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   name="is_active"
                                   id="is_active"
                                   x-model="form.is_active"
                                   value="1"
                                   class="h-5 w-5 rounded border-slate-600 bg-slate-800 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0">
                            <label for="is_active" class="text-sm font-semibold text-slate-300">Aktifkan ranking ini</label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showForm = false" class="secondary-button px-5 py-3">
                            Batal
                        </button>
                        <button type="button" @click="submitForm()" class="primary-button px-5 py-3">
                            <span x-text="editItem ? 'Simpan Perubahan' : 'Tambah Ranking'"></span>
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
        function rankingForm() {
            return {
                showForm: false,
                editItem: null,
                formMethod: '',
                form: {
                    name: '',
                    competition_category_id: '',
                    gender: 'all',
                    appearance_day: '',
                    judging_round: 'Penyisihan',
                    sort_order: 0,
                    is_active: false,
                },
                scheduleDays: [],

                async loadScheduleDays() {
                    if (!this.form.competition_category_id) {
                        this.scheduleDays = [];
                        return;
                    }
                    try {
                        const formData = new FormData();
                        formData.append('category_id', this.form.competition_category_id);
                        formData.append('_token', document.querySelector('meta[name=csrf-token]')?.content || '');
                        const res = await fetch('/admin/ranking-settings/schedule-days', {
                            method: 'POST',
                            body: formData,
                        });
                        const data = await res.json();
                        this.scheduleDays = data.days || [];
                    } catch (e) {
                        console.error('Error loading schedule days:', e);
                        this.scheduleDays = [];
                    }
                },

                openEdit(setting) {
                    this.editItem = setting;
                    this.formMethod = 'PUT';
                    this.form = {
                        name: setting.name || '',
                        competition_category_id: setting.competition_category_id || '',
                        gender: setting.gender || 'all',
                        appearance_day: setting.appearance_day ?? '',
                        judging_round: setting.judging_round || 'Penyisihan',
                        sort_order: setting.sort_order || 0,
                        is_active: setting.is_active || false,
                    };
                    this.showForm = true;
                    this.$nextTick(() => {
                        this.loadScheduleDays();
                    });
                },

                openCreate() {
                    this.editItem = null;
                    this.formMethod = '';
                    this.form = {
                        name: '',
                        competition_category_id: '',
                        gender: 'all',
                        appearance_day: '',
                        judging_round: 'Penyisihan',
                        sort_order: 0,
                        is_active: false,
                    };
                    this.scheduleDays = [];
                    this.showForm = true;
                },

                submitForm() {
                    const form = this.$refs.formElement;
                    if (this.editItem && this.editItem.id) {
                        form.action = '/admin/ranking-settings/' + this.editItem.id;
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
