<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$categories = $categories ?? collect();
$branches = $branches ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Export Data') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
    <style>
        .glass-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.7) 100%);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .glow-cyan { box-shadow: 0 0 20px rgba(34, 211, 238, 0.15), 0 0 40px rgba(34, 211, 238, 0.05); }
        .glow-emerald { box-shadow: 0 0 20px rgba(52, 211, 153, 0.15), 0 0 40px rgba(52, 211, 153, 0.05); }
        .glow-amber { box-shadow: 0 0 20px rgba(251, 191, 36, 0.15), 0 0 40px rgba(251, 191, 36, 0.05); }
        .glow-rose { box-shadow: 0 0 20px rgba(251, 113, 133, 0.15), 0 0 40px rgba(251, 113, 133, 0.05); }
        .glow-violet { box-shadow: 0 0 20px rgba(167, 139, 250, 0.15), 0 0 40px rgba(167, 139, 250, 0.05); }
        .gradient-text {
            background: linear-gradient(135deg, #22d3ee 0%, #34d399 50%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .form-input { transition: all 0.2s ease; }
        .form-input:focus { box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2); }
        .export-card { transition: all 0.3s ease; }
        .export-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="grid-bg min-h-screen bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>

    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8"
          x-data="{ selectedBranch: '', selectedCategory: '' }">

        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72 opacity-30 pointer-events-none fixed"></div>

        <!-- Header -->
        <header class="mb-8 rounded-3xl glass-card px-6 py-5 glow-cyan">
            <div class="flex items-center gap-4">
                <a href="<?= e(route('dashboard')) ?>"
                   class="group flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-600 bg-slate-800/80 text-slate-400 transition-all duration-300 hover:border-cyan-400/50 hover:bg-cyan-400/10 hover:text-cyan-300 hover:scale-110">
                    <?= mtq_icon('arrow-left', 'h-5 w-5') ?>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <?= mtq_icon('download', 'h-4 w-4 text-cyan-300') ?>
                        <p class="section-kicker text-cyan-300">Admin Panel</p>
                    </div>
                    <h1 class="mt-1 text-2xl font-black tracking-tight">
                        <span class="gradient-text">Export Data MTQ</span>
                    </h1>
                </div>
            </div>
        </header>

        <!-- Alert Info -->
        <div class="mb-6 rounded-2xl border border-cyan-400/20 bg-gradient-to-r from-cyan-500/15 to-sky-500/10 px-5 py-4">
            <div class="flex items-start gap-3">
                <?= mtq_icon('info', 'h-5 w-5 text-cyan-300 shrink-0 mt-0.5') ?>
                <div class="text-sm text-cyan-100">
                    <p class="font-semibold text-cyan-200">Halaman Export Data</p>
                    <p class="mt-1 text-slate-300">Export data peserta dalam format Excel dan PDF, serta download kokarde.</p>
                </div>
            </div>
        </div>

        <!-- Export Options Grid -->
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

            <!-- Export Excel by Golongan -->
            <div class="export-card glass-card rounded-3xl p-6 glow-emerald">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400/20">
                        <?= mtq_icon('file-text', 'h-6 w-6 text-emerald-300') ?>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Export Excel</h3>
                        <p class="text-xs text-slate-400">Per Golongan</p>
                    </div>
                </div>
                <p class="mb-4 text-sm text-slate-300">
                    Download data peserta berupa file Excel berdasarkan golongan yang dipilih.
                </p>
                <form action="<?= e(route('admin.export.excel.category')) ?>" method="GET" class="space-y-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-300">Pilih Golongan</label>
                        <select name="category_id" required
                                class="form-input w-full rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-2.5 text-white outline-none transition focus:border-cyan-400">
                            <option value="">-- Pilih Golongan --</option>
                            <?php foreach ($branches as $branch): ?>
                                <optgroup label="<?= e($branch) ?>">
                                    <?php foreach ($categories[$branch] ?? [] as $category): ?>
                                        <option value="<?= e($category->id) ?>">
                                            <?= e($category->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rounded-xl border border-emerald-400/20 bg-emerald-400/5 px-4 py-3">
                        <p class="text-xs text-emerald-200 font-semibold mb-1">Kolom Excel:</p>
                        <p class="text-xs text-emerald-100/70">No, Nama, Gender, NIK, Nomor KK, Tempat Lahir, Tanggal Lahir, Kecamatan, Lot, Keterangan</p>
                    </div>
                    <button type="submit" class="primary-button w-full justify-center px-4 py-2.5 flex items-center gap-2">
                        <?= mtq_icon('download', 'h-4 w-4') ?>
                        Download Excel
                    </button>
                </form>
            </div>

            <!-- Download Kokarde Peserta -->
            <div class="export-card glass-card rounded-3xl p-6 glow-amber">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-400/20">
                        <?= mtq_icon('award', 'h-6 w-6 text-amber-300') ?>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Download Kokarde Peserta</h3>
                        <p class="text-xs text-slate-400">Semua Peserta</p>
                    </div>
                </div>
                <p class="mb-4 text-sm text-slate-300">
                    Download kokarde (surat undangan/sertifikat partisipasi) untuk semua peserta terverifikasi.
                </p>
                <form action="<?= e(route('admin.export.kokarde.page')) ?>" method="GET" class="space-y-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-300">Filter Golongan (Opsional)</label>
                        <select name="category_id"
                                class="form-input w-full rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-2.5 text-white outline-none transition focus:border-cyan-400">
                            <option value="">Semua Golongan</option>
                            <?php foreach ($branches as $branch): ?>
                                <optgroup label="<?= e($branch) ?>">
                                    <?php foreach ($categories[$branch] ?? [] as $category): ?>
                                        <option value="<?= e($category->id) ?>">
                                            <?= e($category->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="primary-button w-full justify-center px-4 py-2.5 flex items-center gap-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 shadow-lg shadow-amber-400/20">
                        <?= mtq_icon('award', 'h-4 w-4') ?>
                        Download Kokarde Peserta
                    </button>
                </form>
            </div>

            <!-- Download Kokarde Panitia -->
            <div class="export-card glass-card rounded-3xl p-6 glow-violet">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-400/20">
                        <?= mtq_icon('users', 'h-6 w-6 text-violet-300') ?>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Download Kokarde Panitia</h3>
                        <p class="text-xs text-slate-400">Admin &Panitia</p>
                    </div>
                </div>
                <p class="mb-4 text-sm text-slate-300">
                    Download kokarde untuk seluruh admin dan panitia MTQ yang sudah terdaftar.
                </p>
                <a href="<?= e(route('admin.export.kokarde.committee.page')) ?>"
                   class="primary-button w-full justify-center px-4 py-2.5 flex items-center gap-2 bg-gradient-to-r from-violet-500 to-purple-500 hover:from-violet-400 hover:to-purple-400 shadow-lg shadow-violet-400/20">
                    <?= mtq_icon('users', 'h-4 w-4') ?>
                    Download Kokarde Panitia
                </a>
            </div>

            <!-- Export PDF Peserta -->
            <div class="export-card glass-card rounded-3xl p-6 glow-rose">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-400/20">
                        <?= mtq_icon('file', 'h-6 w-6 text-rose-300') ?>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Export PDF Peserta</h3>
                        <p class="text-xs text-slate-400">Per Golongan</p>
                    </div>
                </div>
                <p class="mb-4 text-sm text-slate-300">
                    Download data peserta dalam format PDF berdasarkan golongan.
                </p>
                <form action="<?= e(route('participants.export.by-category.pdf')) ?>" method="GET" target="_blank" class="space-y-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-300">Pilih Golongan</label>
                        <select name="id" required
                                class="form-input w-full rounded-xl border border-slate-600 bg-slate-800/80 px-4 py-2.5 text-white outline-none transition focus:border-cyan-400">
                            <option value="">-- Pilih Golongan --</option>
                            <?php foreach ($branches as $branch): ?>
                                <optgroup label="<?= e($branch) ?>">
                                    <?php foreach ($categories[$branch] ?? [] as $category): ?>
                                        <option value="<?= e($category->id) ?>">
                                            <?= e($category->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="primary-button w-full justify-center px-4 py-2.5 flex items-center gap-2 bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-400 hover:to-pink-400 shadow-lg shadow-rose-400/20">
                        <?= mtq_icon('file', 'h-4 w-4') ?>
                        Export PDF
                    </button>
                </form>
            </div>

        </div>

        <!-- Quick Links -->
        <div class="mt-8">
            <h2 class="mb-4 text-xl font-bold text-white">Link Export Lainnya</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <a href="<?= e(route('full.schedule.pdf')) ?>" target="_blank"
                   class="glass-card flex items-center justify-between rounded-2xl border border-slate-700/50 p-5 transition-all hover:border-cyan-400/40 hover:bg-slate-800/50">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-400/20">
                            <?= mtq_icon('calendar', 'h-5 w-5 text-cyan-300') ?>
                        </div>
                        <div>
                            <p class="font-semibold text-white">Rangkaian Kegiatan PDF</p>
                            <p class="text-xs text-slate-400">Download jadwal rangkaian kegiatan MTQ</p>
                        </div>
                    </div>
                    <?= mtq_icon('external-link', 'h-5 w-5 text-slate-400') ?>
                </a>
                <a href="<?= e(route('participants.export.pdf')) ?>" target="_blank"
                   class="glass-card flex items-center justify-between rounded-2xl border border-slate-700/50 p-5 transition-all hover:border-cyan-400/40 hover:bg-slate-800/50">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-400/20">
                            <?= mtq_icon('users', 'h-5 w-5 text-violet-300') ?>
                        </div>
                        <div>
                            <p class="font-semibold text-white">Semua Peserta PDF</p>
                            <p class="text-xs text-slate-400">Download data semua peserta terverifikasi</p>
                        </div>
                    </div>
                    <?= mtq_icon('external-link', 'h-5 w-5 text-slate-400') ?>
                </a>
            </div>
        </div>

    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
