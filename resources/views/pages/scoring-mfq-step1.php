<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'scoring.mfq');
$participants = $participants ?? collect();
$mfqCategories = $mfqCategories ?? collect();
$selectedCategory = $selectedCategory ?? null;
$selectedParticipants = $selectedParticipants ?? collect();
$summaryStats = $summaryStats ?? ['participant_total' => 0, 'category_total' => 0, 'verified_total' => 0, 'selected_average' => '0.00', 'selected_latest' => '0.00'];
$filters = $filters ?? [];
$selectionState = $selectionState ?? ['competition_category_id' => null, 'participant_ids' => []];
$categoryId = (string) old('competition_category_id', $selectionState['competition_category_id'] ?? ($filters['competition_category_id'] ?? ''));
$selectedIds = collect(old('participant_ids', $selectionState['participant_ids'] ?? []))
    ->map(fn ($id) => (string) $id)
    ->filter()
    ->values()
    ->all();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Penilaian MFQ') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>

    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="mfqSelectionPage({
        initialSelectedIds: <?= e(json_encode($selectedIds, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
    })">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block">
                <div class="flex items-center gap-3">
                    <div class="icon-chip"><?= mtq_icon('chart') ?></div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                        <h1 class="mt-1 text-lg font-bold text-white">Penilaian MFQ</h1>
                    </div>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Tahap 1</p>
                    <h2 class="mt-3 text-xl font-bold text-white">Pilih regu bertanding dulu</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">MFQ dimulai dari pemilihan 2 sampai 5 regu yang akan tampil. Setelah itu baru kita bangun alur penilaian per soal.</p>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Golongan MFQ</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['category_total']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Regu Terverifikasi</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['verified_total']) ?></p>
                    </div>
                    <a href="<?= e(route('dashboard')) ?>" class="secondary-button w-full">
                        <?= mtq_icon('home', 'h-4 w-4') ?>
                        Kembali ke Dashboard
                    </a>
                </div>
            </aside>

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div>
                            <p class="section-kicker">Ruang Penilaian MFQ</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Langkah awal: pilih regu yang akan bertanding</h2>
                            <p class="mt-2 text-sm text-slate-300">Kita mulai dari pemilihan regu dulu. Form penilaian detail akan dibangun setelah daftar peserta tandingnya sudah final.</p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Tahap Pemilihan
                    </div>
                </header>

                <?php if (session('status')): ?>
                    <div class="glass-card rounded-[1.5rem] border border-emerald-400/20 bg-emerald-400/10 px-5 py-4 text-sm text-emerald-100">
                        <?= e(session('status')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session('errors')?->any()): ?>
                    <div class="glass-card rounded-[1.5rem] border border-rose-400/20 bg-rose-400/10 px-5 py-4 text-sm text-rose-100">
                        Periksa lagi golongan dan jumlah regu yang dipilih. MFQ wajib memilih minimal 2 regu dan maksimal 5 regu.
                    </div>
                <?php endif; ?>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Peserta MFQ</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['participant_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('book-open') ?></div><p class="mt-4 text-sm text-slate-400">Golongan Aktif</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($summaryStats['category_total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Regu Terpilih</p><p class="mt-2 text-3xl font-extrabold text-cyan-200" x-text="selectedIds.length"></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Batas Pemilihan</p><p class="mt-2 text-3xl font-extrabold text-emerald-300">2-5</p></div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <div class="space-y-6">
                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('fingerprint') ?></div>
                                    <div>
                                        <p class="section-kicker">Filter Golongan</p>
                                        <h3 class="mt-2 text-2xl font-bold text-white">Tentukan golongan MFQ dulu</h3>
                                        <p class="mt-2 text-sm text-slate-300">Setelah golongan dipilih, daftar regu yang tampil akan disesuaikan agar panitia lebih mudah memilih peserta tanding.</p>
                                    </div>
                                </div>
                                <?php if ($selectedCategory): ?>
                                    <div class="status-pill">
                                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                        <?= e(trim((string) $selectedCategory->branch.' - '.(string) $selectedCategory->name)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form method="GET" action="<?= e(route('scoring.mfq')) ?>" class="mt-6 grid gap-4 md:grid-cols-[1fr_auto]">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-200">Golongan MFQ</label>
                                    <select name="competition_category_id" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20">
                                        <option value="">Pilih golongan MFQ</option>
                                        <?php foreach ($mfqCategories as $category): ?>
                                            <option value="<?= e($category->id) ?>" <?= $categoryId === (string) $category->id ? 'selected' : '' ?>>
                                                <?= e($category->branch.' - '.$category->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="primary-button rounded-2xl px-5 py-3">
                                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                        Tampilkan Regu
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="section-kicker">Pilih Regu</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Centang 2 sampai 5 regu yang akan bertanding</h3>
                                    <p class="mt-2 text-sm text-slate-300">Kalau belum memilih golongan, kita belum bisa membatasi daftar regunya. Setelah golongan dipilih, pilih regu yang akan masuk ke arena.</p>
                                </div>
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                    <span x-text="`${selectedIds.length} regu dipilih`"></span>
                                </div>
                            </div>

                            <?php if (! $selectedCategory): ?>
                                <div class="mt-6 rounded-[1.5rem] border border-slate-800 bg-slate-950/60 px-4 py-5 text-sm text-slate-300">
                                    Pilih golongan MFQ terlebih dahulu di atas. Setelah itu daftar regu akan muncul di sini.
                                </div>
                            <?php elseif ($participants->isEmpty()): ?>
                                <div class="mt-6 rounded-[1.5rem] border border-slate-800 bg-slate-950/60 px-4 py-5 text-sm text-slate-300">
                                    Belum ada regu terverifikasi pada golongan ini.
                                </div>
                            <?php else: ?>
                                <form id="mfq-selection-form" method="POST" action="<?= e(route('scoring.mfq.selection.store')) ?>" class="mt-6 space-y-5">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="competition_category_id" value="<?= e($selectedCategory->id) ?>">

                                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                        <?php foreach ($participants as $participant): ?>
                                            <label class="relative block cursor-pointer rounded-[1.4rem] border px-4 py-4 transition"
                                                :class="selectedIds.includes('<?= e($participant->id) ?>') ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_12px_35px_-20px_rgba(34,211,238,0.75)]' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30'">
                                                <div class="flex items-start gap-3">
                                                    <input
                                                        type="checkbox"
                                                        name="participant_ids[]"
                                                        value="<?= e($participant->id) ?>"
                                                        x-model="selectedIds"
                                                        class="mt-1 h-4 w-4 rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-300">
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-white"><?= e($participant->name) ?></p>
                                                        <p class="mt-1 text-xs text-slate-400"><?= e($participant->registration_number) ?></p>
                                                        <p class="mt-1 text-xs text-cyan-200"><?= e($participant->category?->branch.' - '.$participant->category?->name) ?></p>
                                                        <p class="mt-1 text-xs text-slate-500"><?= e($participant->district?->name ?? '-') ?></p>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($errors->has('competition_category_id')): ?>
                                        <p class="text-sm text-rose-300"><?= e($errors->first('competition_category_id')) ?></p>
                                    <?php endif; ?>
                                    <?php if ($errors->has('participant_ids')): ?>
                                        <p class="text-sm text-rose-300"><?= e($errors->first('participant_ids')) ?></p>
                                    <?php endif; ?>

                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
                                        <div>
                                            <p class="text-sm text-slate-300">Jumlah pilihan harus di antara 2 dan 5 regu.</p>
                                            <p class="mt-1 text-xs text-slate-500">Setelah disimpan, daftar ini menjadi dasar untuk tahap penilaian MFQ berikutnya.</p>
                                        </div>
                                        <div class="flex flex-wrap gap-3">
                                            <button type="submit" class="primary-button px-5 py-3" :disabled="selectedIds.length < 2 || selectedIds.length > 5" :class="selectedIds.length < 2 || selectedIds.length > 5 ? 'cursor-not-allowed opacity-50' : ''">
                                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                Simpan Pilihan Regu
                                            </button>
                                            <button type="submit" form="mfq-reset-form" class="secondary-button px-5 py-3">
                                                <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                Reset Pilihan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <form id="mfq-reset-form" method="POST" action="<?= e(route('scoring.mfq.selection.clear')) ?>" class="hidden">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="glass-card rounded-[2rem] p-6">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Regu Terpilih</p>
                            <div class="mt-5 space-y-3">
                                <?php if ($selectedParticipants->isEmpty()): ?>
                                    <div class="data-card text-sm text-slate-300">Belum ada regu yang dipilih.</div>
                                <?php else: ?>
                                    <?php foreach ($selectedParticipants as $participant): ?>
                                        <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                                            <p class="font-semibold text-white"><?= e($participant->name) ?></p>
                                            <p class="mt-1 text-xs text-slate-400"><?= e($participant->registration_number) ?></p>
                                            <p class="mt-1 text-xs text-cyan-200"><?= e($participant->category?->branch.' - '.$participant->category?->name) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Tahap Berikutnya</p>
                            <div class="mt-5 space-y-4">
                                <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                    <p class="font-semibold text-white">Tahap 2: susun form penilaian</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">Setelah regu final, kita lanjut bikin alur soal regu, soal rebutan, dan pembagian nilai per babak.</p>
                                </div>
                                <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                    <p class="font-semibold text-white">Tahap 3: rekap hasil</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">Nanti hasil setiap sesi akan kita simpan per regu supaya rekap juara dan urutan nilai lebih mudah.</p>
                                </div>
                            </div>
                        </div>
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
        function mfqSelectionPage(initialState) {
            return {
                selectedIds: Array.isArray(initialState.initialSelectedIds) ? initialState.initialSelectedIds.map((value) => String(value)) : [],
            };
        }
    </script>
</body>
</html>
