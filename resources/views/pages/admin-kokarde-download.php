<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$participants = $participants ?? collect();
$category = $category ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Download Kokarde') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
    <style>
        .glass-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.7) 100%);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
    </style>
</head>
<body class="grid-bg min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">

        <!-- Header -->
        <header class="mb-8 rounded-3xl glass-card px-6 py-5">
            <div class="flex items-center gap-4">
                <a href="<?= e(route('admin.export')) ?>"
                   class="group flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-600 bg-slate-800/80 text-slate-400 transition-all duration-300 hover:border-cyan-400/50 hover:bg-cyan-400/10 hover:text-cyan-300 hover:scale-110">
                    <?= mtq_icon('arrow-left', 'h-5 w-5') ?>
                </a>
                <div>
                    <h1 class="text-2xl font-black text-white">Download Kokarde</h1>
                    <p class="text-sm text-slate-400">
                        <?php if ($category): ?>
                            <?= e($category->branch) ?> - <?= e($category->name) ?>
                        <?php else: ?>
                            Semua Golongan
                        <?php endif; ?>
                        (<?= $participants->count() ?> peserta)
                    </p>
                </div>
            </div>
        </header>

        <?php if ($participants->isEmpty()): ?>
            <div class="rounded-2xl border border-slate-700 bg-slate-900/50 p-8 text-center">
                <p class="text-slate-400">Tidak ada peserta untuk diunduh kokardenya.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($participants as $participant): ?>
                    <div class="glass-card rounded-2xl p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-white"><?= e($participant->name) ?></p>
                                <p class="text-xs text-slate-400">
                                    <?= e($participant->category?->name ?? '-') ?> |
                                    Lot: <?= e($participant->lot_number ?? '-') ?>
                                </p>
                            </div>
                            <a href="<?= e(route('participants.kokarde', $participant->id)) ?>"
                               target="_blank"
                               class="primary-button shrink-0 px-4 py-2 flex items-center gap-2">
                                <?= mtq_icon('download', 'h-4 w-4') ?>
                                Kokarde
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</body>
</html>
