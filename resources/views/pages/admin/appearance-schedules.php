<?php
require_once __DIR__.'/../../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? [];
$categories = $categories ?? collect();
$schedules = $schedules ?? collect();
$categoryStats = $categoryStats ?? collect();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Jadwal Penampilan') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
   <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8"
        x-data="{
            mobileNavOpen: false,
            selectedCategory: null,
            numberOfDays: 1,
            dayCounts: [''],
            daySchedules: [{ name: '', date: '', time: '', end_time: '' }],
            showForm: false,
            totalParticipants: 0,
            totalDistricts: 0,
            isLotPerDistrict: false,
            totalDayCounts: 0,
            init() {
                this.$watch('selectedCategory', (val) => {
                    if (val) {
                        const stats = this.$refs.statsData?.value ? JSON.parse(this.$refs.statsData.value) : {};
                        this.totalParticipants = stats[val]?.total_participants || 0;
                        this.totalDistricts = stats[val]?.total_districts || 0;
                        this.isLotPerDistrict = stats[val]?.is_lot_per_district || false;
                    }
                });
                this.$watch('numberOfDays', (val) => {
                    val = parseInt(val) || 1;
                    const current = this.dayCounts.slice(0, val);
                    const currentSchedules = this.daySchedules.slice(0, val);
                    while (current.length < val) {
                        current.push('');
                        currentSchedules.push({ name: '', date: '', time: '', end_time: '' });
                    }
                    this.dayCounts = current;
                    this.daySchedules = currentSchedules;
                });
                this.$watch('dayCounts', (val) => {
                    this.totalDayCounts = val.reduce((sum, v) => sum + (parseInt(v) || 0), 0);
                }, { deep: true });
            },
            openForm(categoryId) {
                this.selectedCategory = categoryId;
                this.numberOfDays = 1;
                this.dayCounts = [''];
                this.daySchedules = [{ name: '', date: '', time: '', end_time: '' }];
                this.showForm = true;
                this.$nextTick(() => {
                    document.getElementById('form-modal')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },
            closeForm() {
                this.showForm = false;
                this.selectedCategory = null;
            },
            get displayTotal() {
                return this.isLotPerDistrict ? this.totalDistricts : this.totalParticipants;
            },
            get displayLabel() {
                return this.isLotPerDistrict ? 'Kecamatan' : 'Peserta';
            }
        }">
        <input type="hidden" x-ref="statsData" value="<?= e(json_encode($categoryStats->toArray())) ?>">

        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('sparkles') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Jadwal Penampilan</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Ringkasan</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($categories->count()) ?> Golongan</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Atur jadwal penampilan peserta per golongan dengan pembagian lot per hari.</p>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../../partials/console-navigation.php'; ?>
                </nav>
            </aside>

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Penampilan Peserta</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Jadwal Penampilan Per Golongan</h2>
                            <p class="mt-2 text-sm text-slate-300">Bagikan nomor lot per hari dengan jumlah yang bisa disesuaikan.</p>
                        </div>
                    </div>
                    <?php $hasAnySchedule = $schedules->isNotEmpty(); ?>
                    <?php if ($hasAnySchedule): ?>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="<?= e(route('appearance.export.all.pdf')) ?>"
                            target="_blank"
                            rel="noopener"
                            class="primary-button rounded-xl px-4 py-2.5 inline-flex items-center gap-2">
                            <?= mtq_icon('download', 'h-4 w-4') ?>
                            Export Semua PDF
                        </a>
                    </div>
                    <?php endif; ?>
                </header>

                <?php if (session('status')): ?>
                    <div class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
                        <?= e(session('status')) ?>
                    </div>
                <?php endif; ?>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="overflow-hidden rounded-[1.75rem] border border-cyan-400/14 bg-slate-950/50">
                        <table class="min-w-full">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-4 text-left">Golongan</th>
                                    <th class="px-5 py-4 text-center">Total Lot</th>
                                    <th class="px-5 py-4 text-center">Jumlah Peserta / Kecamatan</th>
                                    <th class="px-5 py-4 text-center">Range Lot</th>
                                    <th class="px-5 py-4 text-center">Jumlah Hari</th>
                                    <th class="px-5 py-4 text-center">Status</th>
                                    <th class="px-5 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <?php
                                        $stats = $categoryStats[$category->id] ?? [];
                                        $schedule = $schedules->get($category->id);
                                        $totalLots = $stats['total_lots'] ?? 0;
                                        $totalParticipants = $stats['total_participants'] ?? 0;
                                        $totalDistricts = $stats['total_districts'] ?? 0;
                                        $isLotPerDistrict = $stats['is_lot_per_district'] ?? false;
                                        $minLot = $stats['min_lot'] ?? 0;
                                        $maxLot = $stats['max_lot'] ?? 0;
                                        $hasSchedule = $stats['has_schedule'] ?? false;
                                        $isBalanced = $stats['is_balanced'] ?? false;

                                        // Display value based on category type
                                        $displayValue = $isLotPerDistrict ? $totalDistricts : $totalParticipants;
                                        $displayLabel = $isLotPerDistrict ? 'Kecamatan' : 'Peserta';
                                    ?>
                                    <tr class="table-row">
                                        <td class="px-5 py-4">
                                            <p class="text-sm font-semibold text-white"><?= e($category->name) ?></p>
                                            <p class="mt-1 text-xs text-slate-400"><?= e($category->branch) ?></p>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span class="text-lg font-bold text-white"><?= e($totalLots) ?></span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span class="text-lg font-bold text-emerald-300"><?= e($displayValue) ?></span>
                                            <?php if ($isLotPerDistrict): ?>
                                                <p class="text-xs text-slate-400 mt-0.5"><?= e($displayLabel) ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <?php if ($totalLots > 0): ?>
                                                <span class="rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-sm font-semibold text-amber-200">
                                                    <?= e(str_pad((string) $minLot, 2, '0', STR_PAD_LEFT)) ?> - <?= e(str_pad((string) $maxLot, 2, '0', STR_PAD_LEFT)) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-slate-500">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <?php if ($hasSchedule): ?>
                                                <span class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-sm font-semibold text-cyan-100">
                                                    <?= e($stats['schedule_days'] ?? 0) ?> hari
                                                </span>
                                            <?php else: ?>
                                                <span class="text-slate-500">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <?php if ($hasSchedule): ?>
                                                <?php if ($isBalanced): ?>
                                                    <span class="inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-100">
                                                        Seimbang
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-100">
                                                        Tidak Seimbang
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="inline-flex rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-xs font-semibold text-slate-400">
                                                    Belum Ada
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <div class="flex flex-wrap items-center justify-center gap-2">
                                                <?php if ($hasSchedule): ?>
                                                    <a href="<?= e(route('appearance.results', $category->id)) ?>"
                                                        class="secondary-button rounded-xl px-3 py-2 text-xs">
                                                        <?= mtq_icon('eye', 'h-4 w-4') ?>
                                                        Lihat Hasil
                                                    </a>
                                                    <form method="POST" action="<?= e(route('appearance.schedules.destroy', $schedule)) ?>"
                                                        onsubmit="return confirm('Hapus jadwal penampilan ini?')"
                                                        class="inline">
                                                        <?= csrf_field() ?>
                                                        <?= method_field('DELETE') ?>
                                                        <button type="submit" class="secondary-button rounded-xl px-3 py-2 text-xs text-rose-300 hover:border-rose-400/30">
                                                            <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                            Hapus
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <button type="button"
 x-on:click="openForm(<?= e($category->id) ?>)"
                                                    class="primary-button rounded-xl px-3 py-2 text-xs">
                                                    <?= mtq_icon('plus', 'h-4 w-4') ?>
                                                    <?= $hasSchedule ? 'Ubah' : 'Atur' ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
</tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <!-- Modal Form -->
        <div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6">
            <div class="absolute inset-0" x-on:click="closeForm()"></div>
            <div class="relative z-10 max-h-[88vh] w-full max-w-xl overflow-hidden rounded-[1.75rem] border border-cyan-400/16 bg-slate-950 shadow-[0_28px_90px_-40px_rgba(34,211,238,0.45)]"
                id="form-modal">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-800 px-6 py-5">
                    <div>
                        <p class="section-kicker">Jadwal Penampilan</p>
                        <h3 class="mt-2 text-2xl font-bold text-white">Atur Jadwal Per Hari</h3>
                    </div>
                    <button type="button" class="secondary-button px-4 py-2" x-on:click="closeForm()">
                        <?= mtq_icon('x', 'h-4 w-4') ?>
                        Tutup
                    </button>
                </div>

                <form method="POST" action="<?= e(route('appearance.schedules.store')) ?>" class="max-h-[70vh] overflow-y-auto px-6 py-5">
                    <?= csrf_field() ?>
                    <input type="hidden" name="competition_category_id" x-bind:value="selectedCategory">

                    <div class="space-y-4">
                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/45 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Jumlah Hari</p>
                                    <p class="mt-1 text-sm text-slate-300">Pilih berapa hari penampilan</p>
                                </div>
                                <input type="number" name="number_of_days" x-model.number="numberOfDays" min="1" max="10"
                                    class="w-24 rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-center text-lg font-bold text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/45 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Pengaturan Per Hari</p>
                            <p class="mt-1 text-sm text-slate-300">Atur jumlah <span x-text="displayLabel.toLowerCase()">peserta</span>, nama sesi, tanggal dan jam untuk setiap hari</p>

                            <div class="mt-4 space-y-4">
                                <template x-for="(day, index) in daySchedules" :key="index">
                                    <div class="rounded-xl border border-slate-700/50 bg-slate-900/30 p-4">
                                        <div class="mb-4 flex items-center gap-3">
                                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-cyan-400/18 bg-cyan-400/10 text-sm font-bold text-cyan-100"
                                                x-text="index + 1">1</span>
                                            <p class="text-sm font-semibold text-white">Hari <span x-text="index + 1"></span></p>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block text-xs text-slate-400">Nama Sesi</label>
                                                <input type="text" x-model="daySchedules[index].name" :name="`day_names[${index}]`"
                                                    class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                                    placeholder="Contoh: Penyisihan Hari 1">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs text-slate-400">Jumlah <span x-text="displayLabel.toLowerCase()">Peserta</span></label>
                                                <input type="number" x-model="dayCounts[index]" :name="`day_counts[${index}]`" min="1"
                                                    class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                                                    :placeholder="`Jumlah ${displayLabel.toLowerCase()}`">
                                            </div>
                                        </div>

                                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block text-xs text-slate-400">Tanggal</label>
                                                <input type="date" x-model="daySchedules[index].date" :name="`day_dates[${index}]`"
                                                    class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="mb-1 block text-xs text-slate-400">Jam Mulai</label>
                                                    <input type="time" x-model="daySchedules[index].time" :name="`day_times[${index}]`"
                                                        class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs text-slate-400">Jam Selesai</label>
                                                    <input type="time" x-model="daySchedules[index].end_time" :name="`day_end_times[${index}]`"
                                                        class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-4 py-2.5 text-white outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/45 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Total Ditampilkan</p>
                                    <p class="mt-1 text-sm text-slate-300">Total <span x-text="displayLabel.toLowerCase()">peserta</span> per hari</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-black" :class="totalDayCounts === displayTotal ? 'text-emerald-300' : 'text-amber-300'" x-text="totalDayCounts">0</span>
                                    <span class="text-slate-400"> / </span>
                                    <span class="text-lg font-bold text-white" x-text="displayTotal">0</span>
                                    <span class="text-slate-400 text-sm ml-1" x-text="displayLabel"></span>
                                </div>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
                                <div class="h-full rounded-full bg-gradient-to-r from-cyan-300 to-emerald-300 transition-all duration-300"
                                    :style="`width: ${displayTotal > 0 ? Math.min(100, (totalDayCounts / displayTotal) * 100) : 0}%`"></div>
                            </div>
                            <p class="mt-2 text-xs" :class="totalDayCounts === displayTotal ? 'text-emerald-300' : 'text-amber-300'"
                                x-text="totalDayCounts === displayTotal ? 'Jumlah sudah sesuai!' : 'Jumlah harus sama dengan total ' + displayLabel.toLowerCase()"></p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 pt-4">
                        <p class="text-sm text-slate-400">Pastikan total per hari sama dengan total <span x-text="displayLabel.toLowerCase()">peserta</span>.</p>
                        <button type="submit" class="primary-button px-5 py-3"
                            :disabled="totalDayCounts !== displayTotal || totalDayCounts === 0"
                            :class="(totalDayCounts !== displayTotal || totalDayCounts === 0) ? 'cursor-not-allowed opacity-60' : ''">
                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                            Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
