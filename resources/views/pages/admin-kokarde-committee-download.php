<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$committees = $committees ?? collect();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Download KokardePanitia') ?></title>
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
                   class="group flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-600 bg-slate-800/80 text-slate-400 transition-all duration-300 hover:border-violet-400/50 hover:bg-violet-400/10 hover:text-violet-300 hover:scale-110">
                    <?= mtq_icon('arrow-left', 'h-5 w-5') ?>
                </a>
                <div class="flex-1">
                    <h1 class="text-2xl font-black text-white">Download KokardePanitia</h1>
                    <p class="text-sm text-slate-400">
                        Admin &amp;Panitia (<?= $committees->count() ?> orang)
                    </p>
                </div>
                <a href="<?= e(route('admin.export.kokarde.committee.print')) ?>"
                   target="_blank"
                   class="flex items-center gap-2 rounded-xl border border-emerald-400/50 bg-emerald-400/10 px-4 py-2.5 text-sm font-semibold text-emerald-300 transition-all duration-300 hover:border-emerald-400 hover:bg-emerald-400/20">
                    <?= mtq_icon('printer', 'h-5 w-5') ?>
                    Print / Save PDF
                </a>
            </div>
        </header>

        <?php if ($committees->isEmpty()): ?>
            <div class="rounded-2xl border border-slate-700 bg-slate-900/50 p-8 text-center">
                <p class="text-slate-400">Tidak ada admin/panitia untuk diunduh kokardenya.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($committees as $committee): ?>
                    <?php $photoUrl = $committee->profilePhotoUrl(); ?>
                    <div class="glass-card rounded-2xl p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <?php if ($photoUrl): ?>
                                    <img src="<?= e($photoUrl) ?>" alt="Foto" class="h-12 w-12 rounded-full object-cover border border-violet-400/30">
                                <?php else: ?>
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-violet-400/30 bg-violet-400/10 text-lg font-black text-violet-300">
                                        <?= e($committee->profileInitials()) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-white"><?= e($committee->name) ?></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold <?= $committee->role === 'admin' ? 'border border-amber-400/30 bg-amber-400/10 text-amber-200' : 'border border-cyan-400/30 bg-cyan-400/10 text-cyan-200' ?>">
                                            <?= e(ucfirst($committee->role)) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <a href="<?= e(route('admin.export.kokarde.committee', $committee->id)) ?>"
                               target="_blank"
                               class="primary-button shrink-0 px-4 py-2 flex items-center gap-2 bg-gradient-to-r from-violet-500 to-purple-500 hover:from-violet-400 hover:to-purple-400">
                                <?= mtq_icon('download', 'h-4 w-4') ?>
                                Kokarde
                            </a>
                        </div>
                        <?php if ($committee->categoryAccesses->isNotEmpty()): ?>
                            <div class="mt-3 flex flex-wrap gap-1">
                                <?php foreach ($committee->categoryAccesses as $access): ?>
                                    <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-2 py-0.5 text-xs text-cyan-200">
                                        <?= e(trim(($access->category?->branch ?? '-').' - '.($access->category?->name ?? '-'))) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</body>
</html>
