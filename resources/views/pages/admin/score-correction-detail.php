<?php
require_once __DIR__.'/../../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$correction = $correction ?? null;
$currentScores = $currentScores ?? null;
$criteria = $criteria ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Detail Perbaikan Nilai') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">

        <div class="hero-orb hero-orb-amber right-[-7rem] top-10 h-72 w-72"></div>

        <!-- Header -->
        <header class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="icon-chip"><?= mtq_icon('pencil') ?></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('arrow-left', 'h-4 w-4 text-amber-300') ?>
                            <p class="section-kicker">Admin Console</p>
                        </div>
                        <h2 class="mt-1 sm:mt-2 text-xl sm:text-3xl font-black tracking-tight">
                            <span class="gradient-text">Detail Perbaikan Nilai</span>
                        </h2>
                        <p class="mt-1 text-sm text-slate-400">
                            <?= e($correction?->participant?->name ?? '-') ?> - <?= e($correction?->judging_round) ?>
                        </p>
                    </div>
                </div>
                <a href="<?= e(route('admin.score-corrections.index')) ?>" class="secondary-button flex items-center gap-2">
                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    Kembali
                </a>
            </div>
        </header>

        <?php if($correction): ?>
        <!-- Participant Info -->
        <section class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
            <h3 class="text-lg font-bold text-white mb-4">Informasi Peserta</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="data-card">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nama</p>
                    <p class="mt-2 text-lg font-bold text-white"><?= e($correction->participant?->name ?? '-') ?></p>
                </div>
                <div class="data-card">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">No. Lot</p>
                    <p class="mt-2 text-lg font-bold text-white"><?= e($correction->participant?->lot_number ?? '-') ?></p>
                </div>
                <div class="data-card">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Golongan</p>
                    <p class="mt-2 text-lg font-bold text-white"><?= e($correction->category?->name ?? '-') ?></p>
                </div>
                <div class="data-card">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Kecamatan</p>
                    <p class="mt-2 text-lg font-bold text-white"><?= e($correction->participant?->district?->name ?? '-') ?></p>
                </div>
                <div class="data-card">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Babak</p>
                    <p class="mt-2 text-lg font-bold text-white"><?= e($correction->judging_round) ?></p>
                </div>
                <div class="data-card">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</p>
                    <?php
                    $statusColor = match($correction->status) {
                        'pending' => 'text-amber-300',
                        'approved' => 'text-emerald-300',
                        'rejected' => 'text-red-300',
                        default => 'text-slate-300',
                    };
                    ?>
                    <p class="mt-2 text-lg font-bold <?= e($statusColor) ?> capitalize"><?= e($correction->status) ?></p>
                </div>
                <div class="data-card">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Requested By</p>
                    <p class="mt-2 text-lg font-bold text-white"><?= e($correction->requestedBy?->name ?? '-') ?></p>
                </div>
                <div class="data-card">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Requested At</p>
                    <p class="mt-2 text-lg font-bold text-white"><?= e(optional($correction->created_at)->format('d/m/Y H:i')) ?></p>
                </div>
            </div>
            <?php if($correction->note): ?>
            <div class="mt-4 rounded-xl border border-amber-400/20 bg-amber-400/10 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-amber-300 mb-2">Catatan Request</p>
                <p class="text-slate-200"><?= e($correction->note) ?></p>
            </div>
            <?php endif; ?>
        </section>

        <!-- Score Comparison -->
        <section class="glass-card rounded-[2rem] p-4 sm:p-6 mb-6">
            <h3 class="text-lg font-bold text-white mb-4">Perbandingan Nilai</h3>
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Current Score -->
                <div class="rounded-xl border border-slate-700 bg-slate-900/50 p-4">
                    <h4 class="text-md font-bold text-slate-300 mb-4">Nilai Saat Ini</h4>
                    <?php
                    $currentBreakdown = $currentScores?->score_breakdown ?? [];
                    $currentTotal = $currentScores?->score ?? 0;
                    ?>
                    <?php if(empty($currentBreakdown)): ?>
                        <p class="text-slate-500">Tidak ada nilai tercatat</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach($criteria as $key => $label): ?>
                                <?php $value = $currentBreakdown[$key] ?? 0; ?>
                                <div class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-800/30 px-4 py-2">
                                    <span class="text-sm text-slate-300"><?= e($label) ?></span>
                                    <span class="text-lg font-bold text-white"><?= e(number_format((float)$value, 2)) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 flex items-center justify-between rounded-lg border border-slate-600 bg-slate-800 px-4 py-3">
                            <span class="font-bold text-white">Total</span>
                            <span class="text-xl font-bold text-cyan-300"><?= e(number_format((float)$currentTotal, 2)) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Requested Score -->
                <div class="rounded-xl border border-amber-400/30 bg-amber-400/5 p-4">
                    <h4 class="text-md font-bold text-amber-200 mb-4">Nilai Usulan</h4>
                    <?php
                    $requestedScores = $correction->requested_scores ?? [];
                    $requestedTotal = array_sum($requestedScores);
                    ?>
                    <?php if(empty($requestedScores)): ?>
                        <p class="text-slate-500">Tidak ada nilai diusulkan</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach($criteria as $key => $label): ?>
                                <?php $value = $requestedScores[$key] ?? 0; ?>
                                <?php $oldValue = $currentBreakdown[$key] ?? 0; ?>
                                <?php $diff = (float)$value - (float)$oldValue; ?>
                                <div class="flex items-center justify-between rounded-lg border border-amber-400/20 bg-amber-400/5 px-4 py-2">
                                    <span class="text-sm text-slate-300"><?= e($label) ?></span>
                                    <div class="flex items-center gap-2">
                                        <?php if($diff != 0): ?>
                                            <span class="text-xs <?= $diff > 0 ? 'text-emerald-300' : 'text-red-300' ?>">
                                                (<?= $diff > 0 ? '+' : '' ?><?= e(number_format($diff, 2)) ?>)
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-lg font-bold <?= $diff != 0 ? 'text-amber-200' : 'text-white' ?>"><?= e(number_format((float)$value, 2)) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php $totalDiff = $requestedTotal - $currentTotal; ?>
                        <div class="mt-4 flex items-center justify-between rounded-lg border border-amber-400/30 bg-amber-400/10 px-4 py-3">
                            <span class="font-bold text-white">Total</span>
                            <div class="flex items-center gap-2">
                                <?php if($totalDiff != 0): ?>
                                    <span class="text-xs <?= $totalDiff > 0 ? 'text-emerald-300' : 'text-red-300' ?>">
                                        (<?= $totalDiff > 0 ? '+' : '' ?><?= e(number_format($totalDiff, 2)) ?>)
                                    </span>
                                <?php endif; ?>
                                <span class="text-xl font-bold text-amber-200"><?= e(number_format((float)$requestedTotal, 2)) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Actions -->
        <?php if($correction->status === 'pending'): ?>
        <section class="glass-card rounded-[2rem] p-4 sm:p-6">
            <h3 class="text-lg font-bold text-white mb-4">Tindakan</h3>
            <div class="flex flex-wrap gap-4">
                <form action="<?= e(route('admin.score-corrections.approve', $correction)) ?>" method="POST" class="inline"
                      onsubmit="return confirm('Setujui request ini? Nilai akan diperbarui.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="primary-button px-6 py-3 flex items-center gap-2">
                        <?= mtq_icon('check-circle', 'h-5 w-5') ?>
                        Setujui & Terapkan
                    </button>
                </form>
                <form action="<?= e(route('admin.score-corrections.reject', $correction)) ?>" method="POST" class="inline"
                      onsubmit="return confirm('Tolak request ini?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="danger-button px-6 py-3 flex items-center gap-2">
                        <?= mtq_icon('x-circle', 'h-5 w-5') ?>
                        Tolak
                    </button>
                </form>
            </div>
        </section>
        <?php else: ?>
        <section class="glass-card rounded-[2rem] p-4 sm:p-6">
            <div class="flex items-center justify-between">
                <p class="text-slate-400">Request ini sudah diproses.</p>
                <?php if($correction->status !== 'pending'): ?>
                <form action="<?= e(route('admin.score-corrections.reset', $correction)) ?>" method="POST" class="inline"
                      onsubmit="return confirm('Kembalikan ke status pending?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="secondary-button px-4 py-2 flex items-center gap-2">
                        <?= mtq_icon('rotate-ccw', 'h-4 w-4') ?>
                        Reset ke Pending
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php endif; ?>

    </main>

    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
