<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$filters = $filters ?? [];
$rows = $rows ?? collect();
$categories = $categories ?? collect();
$branches = $branches ?? collect();
$selectedCategory = $selectedCategory ?? null;
$rankingPriorityContext = $rankingPriorityContext ?? ['text' => '', 'specific' => false];
$recapStats = $recapStats ?? ['participants' => 0, 'categories' => 0, 'highest_score' => '0.00', 'average_score' => '0.00'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Rekap Penilaian') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="space-y-6">
            <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="section-kicker">Berita Acara Ringkas</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-white">Rekap penilaian per cabang dan golongan</h1>
                    <p class="mt-2 text-sm text-slate-300">Ringkasan peringkat peserta untuk rapat hasil, pemantauan dewan hakim, dan arsip panitia.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="<?= e(route('dashboard')) ?>" class="secondary-button">
                        <?= mtq_icon('home', 'h-4 w-4') ?>
                        Dashboard
                    </a>
                    <a href="<?= e(route('results.recap.export.excel', array_filter([
                        'branch' => $filters['branch'] ?? null,
                        'competition_category_id' => $filters['competition_category_id'] ?? null,
                        'keyword' => $filters['keyword'] ?? null,
                    ]))) ?>" class="secondary-button">
                        <?= mtq_icon('download', 'h-4 w-4') ?>
                        Excel Detail
                    </a>
                    <a href="<?= e(route('results.recap.winners.penyisihan.pdf', array_filter([
                        'keyword' => $filters['keyword'] ?? null,
                    ]))) ?>" target="_blank" rel="noreferrer" class="secondary-button">
                        <?= mtq_icon('book-open', 'h-4 w-4') ?>
                        PDF Penyisihan
                    </a>
                    <a href="<?= e(route('results.recap.winners.final.pdf', array_filter([
                        'keyword' => $filters['keyword'] ?? null,
                    ]))) ?>" target="_blank" rel="noreferrer" class="secondary-button">
                        <?= mtq_icon('book-open', 'h-4 w-4') ?>
                        PDF Final
                    </a>
                    <?php if ($selectedCategory): ?>
                        <a href="<?= e(route('results.recap.category.pdf', ['category' => $selectedCategory->id])) ?>" target="_blank" rel="noreferrer" class="primary-button">
                            <?= mtq_icon('trophy', 'h-4 w-4') ?>
                            PDF Detail Golongan
                        </a>
                    <?php else: ?>
                        <span class="secondary-button cursor-not-allowed opacity-60" title="Pilih golongan dulu untuk mencetak PDF detail golongan">
                            <?= mtq_icon('trophy', 'h-4 w-4') ?>
                            PDF Detail Golongan
                        </span>
                    <?php endif; ?>
                </div>
            </header>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Peserta</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($recapStats['participants']) ?></p></div>
                <div class="metric-card"><div class="icon-chip"><?= mtq_icon('layers') ?></div><p class="mt-4 text-sm text-slate-400">Golongan</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($recapStats['categories']) ?></p></div>
                <div class="metric-card"><div class="icon-chip"><?= mtq_icon('trophy') ?></div><p class="mt-4 text-sm text-slate-400">Nilai Tertinggi</p><p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($recapStats['highest_score']) ?></p></div>
                <div class="metric-card"><div class="icon-chip"><?= mtq_icon('chart') ?></div><p class="mt-4 text-sm text-slate-400">Rata-rata Rekap</p><p class="mt-2 text-3xl font-extrabold text-emerald-300"><?= e($recapStats['average_score']) ?></p></div>
            </section>

            <section class="glass-card rounded-[2rem] p-6">
                <div class="flex items-center gap-3">
                    <div class="icon-chip"><?= mtq_icon('layers') ?></div>
                    <div>
                        <p class="section-kicker">Filter Rekap</p>
                        <h2 class="mt-2 text-2xl font-bold text-white">Saring cabang, golongan, dan peserta</h2>
                    </div>
                </div>

                <form method="GET" action="<?= e(route('results.recap')) ?>" class="mt-6 grid gap-4 lg:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Kata kunci</label>
                        <input name="keyword" type="text" value="<?= e($filters['keyword'] ?? '') ?>" placeholder="Nama / registrasi / kafilah" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Cabang</label>
                        <select name="branch" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            <option value="">Semua cabang</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= e($branch) ?>" <?= ($filters['branch'] ?? '') === $branch ? 'selected' : '' ?>><?= e($branch) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Golongan</label>
                        <select name="competition_category_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            <option value="">Semua golongan</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e($category->id) ?>" <?= (string) ($filters['competition_category_id'] ?? '') === (string) $category->id ? 'selected' : '' ?>><?= e($category->branch.' - '.$category->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="primary-button">
                            <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                            Terapkan
                        </button>
                        <a href="<?= e(route('results.recap')) ?>" class="secondary-button">
                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="glass-card rounded-[2rem] p-6">
                <div class="flex items-center gap-3">
                    <div class="icon-chip"><?= mtq_icon('trophy') ?></div>
                    <div>
                        <p class="section-kicker">Tabel Rekap</p>
                        <h2 class="mt-2 text-2xl font-bold text-white">Peringkat hasil penilaian</h2>
                        <?php if (($rankingPriorityContext['text'] ?? '') !== ''): ?>
                            <p class="mt-2 text-sm text-slate-400"><?= e($rankingPriorityContext['text']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($selectedCategory): ?>
                    <div class="mt-5 rounded-[1.5rem] border border-cyan-400/16 bg-cyan-400/8 px-5 py-4 text-sm text-cyan-100">
                        PDF detail golongan akan mengikuti pilihan Anda saat ini: <strong><?= e($selectedCategory->branch.' - '.$selectedCategory->name) ?></strong>.
                    </div>
                <?php else: ?>
                    <div class="mt-5 rounded-[1.5rem] border border-slate-700 bg-slate-950/60 px-5 py-4 text-sm text-slate-300">
                        Pilih satu golongan pada filter di atas jika ingin mengunduh PDF detail golongan dengan data nilai lengkap per poin.
                    </div>
                <?php endif; ?>

                <div class="table-shell mt-6">
                    <table class="min-w-full">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-4">Peringkat</th>
                                <th class="px-5 py-4">Peserta</th>
                                <th class="px-5 py-4">Cabang / Golongan</th>
                                <th class="px-5 py-4">Kecamatan</th>
                                <th class="px-5 py-4">Nilai Terakhir</th>
                                <th class="px-5 py-4">Nilai Rata-rata</th>
                                <th class="px-5 py-4">Nilai Terbaik</th>
                                <th class="px-5 py-4">Entri</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($rows->isEmpty()): ?>
                                <tr class="table-row">
                                    <td colspan="8" class="px-5 py-6 text-sm text-slate-300">Belum ada data penilaian yang sesuai dengan filter.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $index => $row): ?>
                                <tr class="table-row">
                                    <td class="px-5 py-4 text-sm font-semibold text-cyan-200"><?= e($index + 1) ?></td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-white"><?= e($row['participant_name']) ?></div>
                                        <div class="text-xs text-slate-400"><?= e($row['registration_number']) ?> | <?= e($row['institution']) ?></div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-300"><?= e($row['branch']) ?><div class="text-xs text-slate-500"><?= e($row['category_name']) ?></div></td>
                                    <td class="px-5 py-4 text-sm text-slate-300"><?= e($row['district']) ?></td>
                                    <td class="px-5 py-4 text-sm font-semibold text-cyan-200"><?= e($row['latest_score']) ?></td>
                                    <td class="px-5 py-4 text-sm font-semibold text-emerald-300"><?= e($row['average_score']) ?></td>
                                    <td class="px-5 py-4 text-sm font-semibold text-white"><?= e($row['best_score']) ?></td>
                                    <td class="px-5 py-4 text-sm text-slate-300"><?= e($row['entry_count']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
