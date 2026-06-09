<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'admin.lot-auto-calculate');
$categories = $categories ?? collect();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Auto-Calculate Lot') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>

    <main class="relative mx-auto max-w-[1440px] px-4 py-5 sm:px-6 lg:px-8" x-data="lotAutoCalculate()">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside
                class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('calculator') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Auto-Calculate Lot</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = !mobileNavOpen">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-6 rounded-[1.6rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-cyan-950/70 to-slate-950/60 p-4">
                    <p class="section-kicker">Panduan Singkat</p>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Fitur ini menghitung pool nomor lot berdasarkan jumlah peserta terverifikasi per gender. Pool putra = nomor genap, pool putri = nomor ganjil.
                    </p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Hanya Admin
                    </div>
                </div>

                <nav class="mt-6 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-6 grid gap-3">
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
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Pengelolaan Nomor Lot</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Auto-Calculate Pool Lot</h2>
                            <p class="mt-2 text-sm text-slate-300">Klik tombol di bawah untuk menghitung pool nomor lot berdasarkan jumlah peserta terverifikasi.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="primary-button rounded-xl px-5 py-3"
                            x-on:click.prevent="loadCalculations()"
                            :disabled="loading"
                        >
                            <span x-show="!loading"><?= mtq_icon('calculator', 'h-4 w-4') ?></span>
                            <span x-show="loading" class="animate-spin"><?= mtq_icon('loader', 'h-4 w-4') ?></span>
                            <span x-text="loading ? 'Memuat...' : 'Hitung Pool Lot'"></span>
                        </button>
                    </div>
                </header>

                <section class="glass-card rounded-[2rem] p-6" x-show="calculations.length > 0" x-cloak>
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="section-kicker">Preview Pool Lot</p>
                            <h3 class="mt-2 text-xl font-bold text-white">Perhitungan Pool per Golongan</h3>
                        </div>
                        <div class="status-pill">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                            <span x-text="calculations.length + ' golongan'"></span>
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-4 py-3 text-left">Golongan</th>
                                    <th class="px-4 py-3 text-center">Group</th>
                                    <th class="px-4 py-3 text-center">Putra</th>
                                    <th class="px-4 py-3 text-left">Pool Genap</th>
                                    <th class="px-4 py-3 text-left">Tidak Dipakai</th>
                                    <th class="px-4 py-3 text-center">Putri</th>
                                    <th class="px-4 py-3 text-left">Pool Ganjil</th>
                                    <th class="px-4 py-3 text-left">Tidak Dipakai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="calc in calculations" :key="calc.category_id">
                                    <tr class="table-row">
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-white" x-text="calc.category_label"></div>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span
                                                    class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
                                                    :class="calc.is_shared ? 'bg-fuchsia-400/20 text-fuchsia-200' : 'bg-slate-400/20 text-slate-300'"
                                                    x-text="calc.is_shared ? 'Shared' : 'Per Peserta'"
                                                ></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-sm font-semibold" x-text="'x' + calc.group_size"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-lg font-bold text-cyan-200" x-text="calc.putra.participant_count"></span>
                                            <div class="mt-1 flex flex-col items-center text-[10px] text-slate-400">
                                                <span>Unique: <span x-text="calc.putra.unique_lots_needed"></span></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="mb-1 text-[10px] text-slate-400">
                                                <span x-text="calc.putra.pool_min + ' - ' + calc.putra.pool_max"></span>
                                                <span class="text-cyan-400/60">(<span x-text="calc.putra.pool_numbers?.length || 0"></span> nomor)</span>
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="num in calc.putra.pool_numbers" :key="'putra-' + num">
                                                    <span
                                                        class="inline-flex h-6 min-w-[1.75rem] items-center justify-center rounded bg-cyan-400/20 px-1 text-xs font-mono font-semibold text-cyan-200"
                                                        x-text="num"
                                                    ></span>
                                                </template>
                                                <span x-show="!calc.putra.pool_numbers?.length" class="text-xs text-slate-500">-</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="num in calc.putra.unused_numbers" :key="'putra-unused-' + num">
                                                    <span
                                                        class="inline-flex h-6 min-w-[1.75rem] items-center justify-center rounded bg-slate-700/60 px-1 text-xs font-mono text-slate-500 line-through"
                                                        x-text="num"
                                                    ></span>
                                                </template>
                                                <span x-show="!calc.putra.unused_numbers?.length" class="text-xs text-slate-500">-</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-lg font-bold text-pink-200" x-text="calc.putri.participant_count"></span>
                                            <div class="mt-1 flex flex-col items-center text-[10px] text-slate-400">
                                                <span>Unique: <span x-text="calc.putri.unique_lots_needed"></span></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="mb-1 text-[10px] text-slate-400">
                                                <span x-text="calc.putri.pool_min + ' - ' + calc.putri.pool_max"></span>
                                                <span class="text-pink-400/60">(<span x-text="calc.putri.pool_numbers?.length || 0"></span> nomor)</span>
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="num in calc.putri.pool_numbers" :key="'putri-' + num">
                                                    <span
                                                        class="inline-flex h-6 min-w-[1.75rem] items-center justify-center rounded bg-pink-400/20 px-1 text-xs font-mono font-semibold text-pink-200"
                                                        x-text="num"
                                                    ></span>
                                                </template>
                                                <span x-show="!calc.putri.pool_numbers?.length" class="text-xs text-slate-500">-</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                <template x-for="num in calc.putri.unused_numbers" :key="'putri-unused-' + num">
                                                    <span
                                                        class="inline-flex h-6 min-w-[1.75rem] items-center justify-center rounded bg-slate-700/60 px-1 text-xs font-mono text-slate-500 line-through"
                                                        x-text="num"
                                                    ></span>
                                                </template>
                                                <span x-show="!calc.putri.unused_numbers?.length" class="text-xs text-slate-500">-</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-700/60 bg-slate-950/50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-cyan-400/20 text-cyan-200">
                                <?= mtq_icon('info', 'h-3 w-3') ?>
                            </div>
                            <div class="text-sm text-slate-300">
                                <p class="font-semibold text-cyan-200">Keterangan:</p>
                                <ul class="mt-1 list-inside list-disc space-y-1">
                                    <li>Angka <span class="font-semibold text-cyan-200">cyan</span> = Pool putra (genap), <span class="font-semibold text-pink-200">pink</span> = Pool putri (ganjil)</li>
                                    <li>Angka <span class="text-slate-500 line-through"> Abu-abu dicoret</span> = Nomor yang tidak terpakai (di-skip saat pengambilan lot)</li>
                                    <li>Pool dibatasi sesuai jumlah peserta terverifikasi. Sistem tetap menggunakan random selection dari pool.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="glass-card rounded-[2rem] p-6" x-show="calculations.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-800/60">
                            <?= mtq_icon('calculator', 'h-8 w-8 text-slate-500') ?>
                        </div>
                        <h3 class="text-lg font-semibold text-white">Klik "Hitung Pool Lot"</h3>
                        <p class="mt-2 max-w-md text-sm text-slate-400">
                            Tekan tombol di header untuk menghitung pool nomor lot berdasarkan data peserta terverifikasi saat ini.
</p>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>

    <script>
        function lotAutoCalculate() {
            return {
                mobileNavOpen: false,
                calculations: [],
                loading: false,
                previewUrl: '<?= e(route('admin.lot-auto-calculate.preview')) ?>',
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

                loadCalculations() {
                    this.loading = true;
                    this.calculations = [];

                    fetch(this.previewUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        this.calculations = data.calculations || [];
                        this.loading = false;
                        console.log('Calculations loaded:', this.calculations);
                    })
                    .catch(error => {
                        console.error('Error loading calculations:', error);
                        this.loading = false;
                        alert('Gagal memuat perhitungan. Silakan coba lagi.');
                    });
                }
            };
        }
    </script>
</body>
</html>
