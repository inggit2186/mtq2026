<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$participants = $participants ?? collect();
$categories = $categories ?? collect();
$districts = $districts ?? collect();
$filters = $filters ?? [];
$trashStats = $trashStats ?? ['total' => 0, 'verified' => 0, 'pending' => 0, 'rejected' => 0];
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'participants.trash');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Arsip Peserta') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('trash') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Arsip Peserta</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Khusus Admin</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($trashStats['total']) ?> Data Diarsipkan</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Data yang diarsipkan official, panitia, atau admin disimpan di sini bersama dokumen yang pernah diupload.</p>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php foreach ($navigation as $item): ?>
                        <a href="<?= e($item['href']) ?>" class="sidebar-link <?= $item['active'] ? 'sidebar-link-active' : '' ?>">
                            <span class="icon-chip h-10 w-10 rounded-xl"><?= mtq_icon($item['icon'], 'h-4 w-4') ?></span>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="mt-8">
                    <form method="POST" action="<?= e(route('logout')) ?>">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="secondary-button w-full">
                            <?= mtq_icon('logout', 'h-4 w-4') ?>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Data Arsip</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Arsip peserta khusus admin</h2>
                            <p class="mt-2 text-sm text-slate-300">Pulihkan data peserta jika masih diperlukan, atau buka kembali dokumen yang sebelumnya diupload.</p>
                        </div>
                    </div>
                    <a href="<?= e(route('participants.list')) ?>" class="secondary-button">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        Kembali ke Data Peserta
                    </a>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('trash') ?></div><p class="mt-4 text-sm text-slate-400">Arsip</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($trashStats['total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Terverifikasi</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($trashStats['verified']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('clock') ?></div><p class="mt-4 text-sm text-slate-400">Menunggu</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($trashStats['pending']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('upload') ?></div><p class="mt-4 text-sm text-slate-400">Ditolak</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($trashStats['rejected']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('layers') ?></div>
                        <div>
                            <p class="section-kicker">Filter Arsip</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Cari data peserta yang sudah diarsipkan</h3>
                        </div>
                    </div>

                    <form method="GET" action="<?= e(route('participants.trash')) ?>" class="mt-6 grid gap-4 lg:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kata kunci</label>
                            <input name="keyword" type="text" value="<?= e($filters['keyword'] ?? '') ?>" placeholder="Nama / NIK / registrasi" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Status verifikasi</label>
                            <select name="verification_status" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Semua status</option>
                                <option value="draft" <?= ($filters['verification_status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draf</option>
                                <option value="submitted" <?= ($filters['verification_status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Menunggu</option>
                                <option value="verified" <?= ($filters['verification_status'] ?? '') === 'verified' ? 'selected' : '' ?>>Terverifikasi</option>
                                <option value="rejected" <?= ($filters['verification_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kecamatan</label>
                            <select name="district_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Semua kecamatan</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= e($district->id) ?>" <?= (string) ($filters['district_id'] ?? '') === (string) $district->id ? 'selected' : '' ?>><?= e($district->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kategori</label>
                            <select name="competition_category_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Semua golongan</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e($category->id) ?>" <?= (string) ($filters['competition_category_id'] ?? '') === (string) $category->id ? 'selected' : '' ?>>
                                        <?= e($category->branch.' - '.$category->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="lg:col-span-4 flex flex-wrap gap-3">
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                Terapkan Filter
                            </button>
                            <a href="<?= e(route('participants.trash')) ?>" class="secondary-button">
                                <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                Reset
                            </a>
                        </div>
                    </form>
                </section>

                <?php if ($errors->any()): ?>
                    <section class="space-y-3">
                        <div class="rounded-[1.5rem] border border-rose-400/20 bg-rose-400/10 px-5 py-4 text-sm leading-6 text-rose-100">
                            <?= e($errors->first()) ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('trash') ?></div>
                            <div>
                                <p class="section-kicker">Arsip</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Data dan berkas peserta diarsipkan</h3>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="status-pill">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                <?= e($participants->count()) ?> data
                            </span>
                            <?php if (($legacyTrashCount ?? 0) > 0): ?>
                                <form method="POST" action="<?= e(route('participants.trash.import-legacy')) ?>" data-swal-confirm data-swal-title="Tarik arsip lama?" data-swal-text="Data soft delete lama dari tabel peserta akan dipindahkan ke arsip baru. Data yang sudah pernah dipindah akan dilewati." data-swal-confirm="Ya, tarik" data-swal-cancel="Batal">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                        <?= mtq_icon('upload', 'h-4 w-4') ?>
                                        Tarik Arsip Lama
                                        <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-cyan-100">
                                            <?= e($legacyTrashCount) ?>
                                        </span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-shell mt-6">
                        <table class="min-w-full">
                            <thead class="table-head">
                                <tr>
                                    <th class="px-5 py-4">Peserta</th>
                                    <th class="px-5 py-4">Kecamatan</th>
                                    <th class="px-5 py-4">Kategori</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th class="px-5 py-4">Berkas</th>
                                    <th class="px-5 py-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($participants->isEmpty()): ?>
                                    <tr class="table-row">
                                        <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada data peserta di arsip.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($participants as $participant): ?>
                                    <?php
                                    $statusLabel = match ($participant->verification_status) {
                                        'verified' => 'Terverifikasi',
                                        'submitted' => 'Menunggu',
                                        'rejected' => 'Ditolak',
                                        default => 'Draf',
                                    };
                                    $documentMap = [
                                        'kk' => ['label' => 'KK', 'path' => $participant->document_kk],
                                        'ktp' => ['label' => 'KTP', 'path' => $participant->document_ktp],
                                        'birth_certificate' => ['label' => 'Akta', 'path' => $participant->document_birth_certificate],
                                        'photo' => ['label' => 'Foto', 'path' => $participant->document_photo],
                                    ];
                                    ?>
                                    <tr class="table-row">
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-white"><?= e($participant->name) ?></div>
                                            <div class="mt-1">
                                                <span class="inline-flex rounded-full border px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] <?= ($participant->participant_role ?? 'main') === 'reserve' ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200' : 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200' ?>">
                                                    <?= e(($participant->participant_role ?? 'main') === 'reserve' ? 'Cadangan' : 'Inti') ?>
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-400"><?= e($participant->registration_number) ?> | <?= e($participant->nik ?: '-') ?></div>
                                            <div class="mt-1 text-xs text-rose-100">Diarsipkan: <?= e(optional($participant->archived_at)->format('d M Y H:i')) ?></div>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-slate-300"><?= e($participant->district?->name ?? '-') ?></td>
                                        <td class="px-5 py-4 text-sm text-slate-300"><?= e(($participant->category?->branch ?? '-').' - '.($participant->category?->name ?? '-')) ?></td>
                                        <td class="px-5 py-4 text-sm text-slate-300"><?= e($statusLabel) ?></td>
                                        <td class="px-5 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach ($documentMap as $key => $document): ?>
                                                    <?php if ($document['path']): ?>
                                                        <a href="<?= e(route('participants.trash.documents.preview', ['participant' => $participant->id, 'document' => $key])) ?>" target="_blank" rel="noreferrer" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                                            <?= e($document['label']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex flex-col gap-2">
                                                <form method="POST" action="<?= e(route('participants.restore', ['participant' => $participant->id])) ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="primary-button rounded-xl px-3 py-2 text-xs">
                                                        <?= mtq_icon('upload', 'h-4 w-4') ?>
                                                        Pulihkan
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= e(route('participants.trash.destroy', ['participant' => $participant->id])) ?>" data-swal-confirm data-swal-title="Hapus permanen data peserta?" data-swal-text="Data peserta, berkas arsip, dan riwayat arsip akan dihapus permanen. Aksi ini tidak bisa dibatalkan." data-swal-confirm="Ya, hapus permanen" data-swal-cancel="Batal">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-3 py-2 text-xs text-rose-100 hover:border-rose-300/40">
                                                        <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                        Hapus Permanen
                                                    </button>
                                                </form>
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
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
