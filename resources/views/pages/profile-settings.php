<?php
require_once __DIR__.'/../partials/icon.php';

$assets = $assets ?? [];
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = $user ?? auth()->user();
$photoUrl = $user->profilePhotoUrl();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Pengaturan Profil') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen bg-slate-950 text-slate-100 antialiased">
    <main class="relative mx-auto max-w-[800px] px-4 py-6 sm:px-6 lg:px-8">

        <!-- Header -->
        <header class="mb-8 rounded-3xl glass-card px-6 py-5">
            <div class="flex items-center gap-4">
                <a href="<?= e(route('dashboard')) ?>"
                   class="group flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-600 bg-slate-800/80 text-slate-400 transition-all duration-300 hover:border-cyan-400/50 hover:bg-cyan-400/10 hover:text-cyan-300 hover:scale-110">
                    <?= mtq_icon('arrow-left', 'h-5 w-5') ?>
                </a>
                <div>
                    <h1 class="text-2xl font-black text-white">Pengaturan Profil</h1>
                    <p class="text-sm text-slate-400">Kelola foto profil Anda</p>
                </div>
            </div>
        </header>

        <?php if (session('success')): ?>
            <div class="mb-6 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-emerald-300">
                <?= e(session('success')) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Card -->
        <div class="glass-card rounded-3xl p-6">
            <div class="flex flex-col items-center">
                <!-- Current Photo -->
                <div class="mb-6">
                    <?php if ($photoUrl): ?>
                        <img src="<?= e($photoUrl) ?>"
                             alt="Foto Profil"
                             class="h-40 w-40 rounded-full object-cover border-4 border-slate-600 shadow-lg">
                    <?php else: ?>
                        <div class="flex h-40 w-40 items-center justify-center rounded-full border-4 border-slate-600 bg-slate-800 text-5xl font-black text-slate-400 shadow-lg">
                            <?= e($user->profileInitials()) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- User Info -->
                <div class="mb-8 text-center">
                    <h2 class="text-xl font-bold text-white"><?= e($user->name) ?></h2>
                    <p class="text-sm text-slate-400">
                        <?= e(ucfirst($user->role)) ?>
                        <?php if ($user->nomor_induk): ?>
                            | NIP: <?= e($user->nomor_induk) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Upload Form -->
                <form id="uploadForm" action="/profile/photo"
                      method="POST"
                      enctype="multipart/form-data"
                      class="w-full max-w-md">
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-slate-300">
                            Upload Foto Baru
                        </label>
                        <input type="file"
                               name="photo"
                               accept="image/jpeg,image/png,image/jpg"
                               required
                               class="block w-full cursor-pointer rounded-xl border border-slate-600 bg-slate-800/50 p-3 text-sm text-slate-300 file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-cyan-500/20 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-cyan-300 hover:file:bg-cyan-500/30">
                        <p class="mt-2 text-xs text-slate-500">
                            Format: JPG, PNG. Maksimal 2MB. Rasio 3:4 disarankan.
                        </p>
                    </div>

                    <?php if ($errors->has('photo')): ?>
                        <div class="mb-4 rounded-lg border border-red-400/30 bg-red-400/10 px-4 py-2 text-sm text-red-300">
                            <?= e($errors->first('photo')) ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 px-6 py-3 font-semibold text-white transition hover:from-cyan-400 hover:to-blue-400">
                        <?= mtq_icon('camera', 'h-5 w-5') ?>
                        Update Foto
                    </button>
                </form>
            </div>
        </div>

        <!-- Info Card -->
        <div class="mt-6 rounded-2xl border border-slate-700/50 bg-slate-800/30 p-4">
            <div class="flex items-start gap-3">
                <div class="rounded-lg bg-blue-500/20 p-2 text-blue-400">
                    <?= mtq_icon('info', 'h-5 w-5') ?>
                </div>
                <div>
                    <h3 class="font-semibold text-white">Tips Foto</h3>
                    <p class="mt-1 text-sm text-slate-400">
                        Gunakan foto formal dengan latar belakang polos. Pastikan wajah terlihat jelas dan menghadap kamera.
                        Foto ini akan digunakan untuk kokarde dan keperluan administrasi lainnya.
                    </p>
                </div>
            </div>
        </div>

    </main>

    <?php foreach ($jsAssets as $src): ?>
        <script src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
