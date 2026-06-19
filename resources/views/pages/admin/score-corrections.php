<?php
require_once __DIR__.'/../../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$requests = $requests ?? collect();
$categories = $categories ?? [];
$filters = $filters ?? [];
$stats = $stats ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Verifikasi Perbaikan Nilai') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased"
      x-data="{ selectedRequest: null }">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">

        <div class="hero-orb hero-orb-amber right-[-7rem] top-10 h-72 w-72"></div>

        <!-- Header -->
        <header class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="icon-chip"><?= mtq_icon('pencil') ?></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('settings', 'h-4 w-4 text-amber-300') ?>
                            <p class="section-kicker">Admin Console</p>
                        </div>
                        <h2 class="mt-1 sm:mt-2 text-xl sm:text-3xl font-black tracking-tight">
                            <span class="gradient-text">Verifikasi Perbaikan Nilai</span>
                        </h2>
                        <p class="mt-1 text-sm text-slate-400">
                            Tinjaulah dan setujui/tolak request perbaikan nilai dari panitia/penilaian.
                        </p>
                    </div>
                </div>
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

        <!-- Stats -->
        <div class="grid gap-4 sm:grid-cols-3 mb-6">
            <div class="glass-card rounded-2xl p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-400/20">
                        <?= mtq_icon('clock', 'h-5 w-5 text-amber-300') ?>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Menunggu</p>
                        <p class="mt-1 text-2xl font-extrabold text-amber-300"><?= e($stats['pending']) ?></p>
                    </div>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-400/20">
                        <?= mtq_icon('check-circle', 'h-5 w-5 text-emerald-300') ?>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Disetujui</p>
                        <p class="mt-1 text-2xl font-extrabold text-emerald-300"><?= e($stats['approved']) ?></p>
                    </div>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-400/20">
                        <?= mtq_icon('x-circle', 'h-5 w-5 text-red-300') ?>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Ditolak</p>
                        <p class="mt-1 text-2xl font-extrabold text-red-300"><?= e($stats['rejected']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <section class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
            <form method="GET" action="<?= e(route('admin.score-corrections.index')) ?>" class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                <div class="flex-1 w-full">
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
                <div class="flex-1 w-full">
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Golongan</label>
                    <select name="competition_category_id" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                        <option value="">Semua Golongan</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= e($cat->id) ?>" <?= ($filters['competition_category_id'] ?? '') == $cat->id ? 'selected' : '' ?>>
                                <?= e(trim(($cat->branch ?? '-').' | '.$cat->name)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1 w-full">
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Babak</label>
                    <select name="judging_round" class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-white outline-none focus:border-cyan-400">
                        <option value="">Semua Babak</option>
                        <option value="Penyisihan" <?= ($filters['judging_round'] ?? '') === 'Penyisihan' ? 'selected' : '' ?>>Penyisihan</option>
                        <option value="Final" <?= ($filters['judging_round'] ?? '') === 'Final' ? 'selected' : '' ?>>Final</option>
                    </select>
                </div>
                <button type="submit" class="primary-button px-5 py-3 flex items-center gap-2">
                    <?= mtq_icon('filter', 'h-4 w-4') ?>
                    Filter
                </button>
            </form>
        </section>

        <!-- Requests List -->
        <section class="glass-card rounded-[2rem] p-4 sm:p-6">
            <h3 class="text-lg font-bold text-white mb-4">Daftar Request Perbaikan</h3>

            <?php if($requests->isEmpty()): ?>
                <div class="rounded-xl border border-dashed border-slate-700 bg-slate-900/50 p-8 text-center">
                    <div class="text-slate-500 mb-3">
                        <?= mtq_icon('inbox', 'h-12 w-12 mx-auto opacity-50') ?>
                    </div>
                    <p class="text-slate-400">Belum ada request perbaikan nilai.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach($requests as $req): ?>
                    <div class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/50 p-4 transition-colors hover:bg-slate-900/80">
                        <div class="flex items-center gap-4">
                            <?php
                            $statusColor = match($req->status) {
                                'pending' => 'bg-amber-500/20 text-amber-300',
                                'approved' => 'bg-emerald-500/20 text-emerald-300',
                                'rejected' => 'bg-red-500/20 text-red-300',
                                default => 'bg-slate-500/20 text-slate-300',
                            };
                            $statusIcon = match($req->status) {
                                'pending' => mtq_icon('clock', 'h-4 w-4'),
                                'approved' => mtq_icon('check-circle', 'h-4 w-4'),
                                'rejected' => mtq_icon('x-circle', 'h-4 w-4'),
                                default => mtq_icon('help-circle', 'h-4 w-4'),
                            };
                            ?>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl <?= e($statusColor) ?>">
                                <?= $statusIcon ?>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-white"><?= e($req->participant?->name ?? '-') ?></h4>
                                    <span class="rounded-full border border-slate-600/50 bg-slate-800/50 px-2 py-0.5 text-xs text-slate-400">
                                        Lot <?= e($req->participant?->lot_number ?? '-') ?>
                                    </span>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                    <span><?= e($req->category?->name ?? '-') ?></span>
                                    <span>·</span>
                                    <span><?= e($req->judging_round) ?></span>
                                    <span>·</span>
                                    <span><?= e($req->requestedBy?->name ?? '-') ?></span>
                                    <span>·</span>
                                    <span><?= e(optional($req->created_at)->format('d/m/Y H:i')) ?></span>
                                </div>
                                <?php if($req->note): ?>
                                    <p class="mt-1 text-xs text-slate-500 line-clamp-1"><?= e($req->note) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if($req->status === 'pending'): ?>
                                <a href="<?= e(route('admin.score-corrections.show', $req)) ?>"
                                   class="rounded-lg border border-cyan-400/30 bg-cyan-400/10 px-3 py-2 text-xs font-semibold text-cyan-200 transition-colors hover:bg-cyan-400/20">
                                    <?= mtq_icon('eye', 'h-4 w-4 inline') ?>
                                    Lihat Detail
                                </a>
                                <form action="<?= e(route('admin.score-corrections.approve', $req)) ?>" method="POST" class="inline"
                                      onsubmit="return confirm('Setujui request perbaikan nilai ini?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="rounded-lg border border-emerald-400/30 bg-emerald-400/10 p-2 text-emerald-300 transition-colors hover:bg-emerald-400/20" title="Setujui">
                                        <?= mtq_icon('check', 'h-4 w-4') ?>
                                    </button>
                                </form>
                                <form action="<?= e(route('admin.score-corrections.reject', $req)) ?>" method="POST" class="inline"
                                      onsubmit="return confirm('Tolak request perbaikan nilai ini?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="rounded-lg border border-red-400/30 bg-red-400/10 p-2 text-red-300 transition-colors hover:bg-red-400/20" title="Tolak">
                                        <?= mtq_icon('x', 'h-4 w-4') ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="rounded-full border <?= e($statusColor) ?> bg-opacity-20 px-3 py-1 text-xs font-semibold capitalize">
                                    <?= e($req->status) ?>
                                </span>
                                <?php if($req->status !== 'pending'): ?>
                                    <form action="<?= e(route('admin.score-corrections.reset', $req)) ?>" method="POST" class="inline"
                                          onsubmit="return confirm('Kembalikan ke status pending?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="rounded-lg border border-slate-600/30 bg-slate-700/30 p-2 text-slate-400 transition-colors hover:bg-slate-600/40" title="Reset ke Pending">
                                            <?= mtq_icon('rotate-ccw', 'h-4 w-4') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
