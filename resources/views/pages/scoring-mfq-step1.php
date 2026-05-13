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
$participantsByDistrict = $participantsByDistrict ?? $participants->groupBy(fn ($participant) => (int) ($participant->district_id ?? 0));
$districtCards = $districtCards ?? $participantsByDistrict->map(function ($districtParticipants, $districtId): array {
    $districtParticipants = collect($districtParticipants)->values();

    return [
        'district_id' => (int) $districtId,
        'district_name' => (string) ($districtParticipants->first()?->district?->name ?? 'Tanpa Kecamatan'),
        'participant_count' => $districtParticipants->count(),
    ];
})->values()->all();
$summaryStats = $summaryStats ?? ['participant_total' => 0, 'category_total' => 0, 'verified_total' => 0, 'selected_average' => '0.00', 'selected_latest' => '0.00'];
$filters = $filters ?? [];
$selectionState = $selectionState ?? ['competition_category_id' => null, 'district_ids' => []];
$selectionSessionName = $selectionSessionName ?? '';
$selectionJudgeName = $selectionJudgeName ?? (string) $user?->name;
$selectionJudgingRound = $selectionJudgingRound ?? 'Penyisihan';
$selectionRemarks = $selectionRemarks ?? '';
$categoryId = (string) old('competition_category_id', $selectionState['competition_category_id'] ?? ($filters['competition_category_id'] ?? ''));
$sessionName = (string) old('session_name', $selectionSessionName);
$selectedIds = collect(old('district_ids', $selectionState['district_ids'] ?? []))
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
        initialSessionName: <?= e(json_encode($sessionName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
        districtCards: <?= e(json_encode($districtCards, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>,
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
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Kecamatan Terpilih</p><p class="mt-2 text-3xl font-extrabold text-cyan-200" x-text="selectedIds.length"></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('spark') ?></div><p class="mt-4 text-sm text-slate-400">Batas Pemilihan</p><p class="mt-2 text-3xl font-extrabold text-emerald-300">2-5</p></div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <div class="space-y-6">
                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="icon-chip"><?= mtq_icon('fingerprint') ?></div>
                                    <div>
                                        <p class="section-kicker">Nama Sesi</p>
                                        <h3 class="mt-2 text-2xl font-bold text-white">Beri nama sesi penilaian</h3>
                                        <p class="mt-2 text-sm text-slate-300">Contoh: Sesi Penyisihan MFQ Putra 1. Nama ini akan terbawa sampai tahap penilaian.</p>
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
                                        Lihat Peserta
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="section-kicker">Pilih Regu</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Pilih 2 sampai 5 kecamatan yang akan bertanding</h3>
                                    <p class="mt-2 text-sm text-slate-300">MFQ di sini berjalan kecamatan vs kecamatan. Satu kecamatan = satu regu, jadi yang dipilih adalah wilayahnya, bukan orang per orang.</p>
                                </div>
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                    <span x-text="`${selectedIds.length} kecamatan dipilih`"></span>
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

                                    <div class="rounded-[1.4rem] border <?= filled($sessionName) ? 'border-cyan-400/16 bg-cyan-400/10' : 'border-amber-400/20 bg-amber-400/10' ?> px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.18em] <?= filled($sessionName) ? 'text-cyan-100/70' : 'text-amber-100/80' ?>">Nama Sesi</p>
                                        <div class="mt-3">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Isi nama sesi setelah peserta tampil</label>
                                            <input
                                                type="text"
                                                name="session_name"
                                                value="<?= e($sessionName) ?>"
                                                x-model="sessionName"
                                                placeholder="Contoh: Sesi Penyisihan MFQ Putra 1"
                                                class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20"
                                            >
                                        </div>
                                        <p class="mt-2 text-xs text-slate-300">Nama sesi diisi setelah golongan dan peserta tampil, lalu wajib ada sebelum lanjut ke tahap 2.</p>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-3">
                                        <div class="rounded-[1.4rem] border border-slate-800 bg-slate-950/55 px-4 py-4">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Hakim</label>
                                            <input
                                                type="text"
                                                name="judge_name"
                                                value="<?= e($selectionJudgeName) ?>"
                                                placeholder="Contoh: Ust. Ahmad"
                                                class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20"
                                            >
                                        </div>

                                        <div class="rounded-[1.4rem] border border-slate-800 bg-slate-950/55 px-4 py-4">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Babak Penilaian</label>
                                            <select name="judging_round" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20">
                                                <?php foreach (['Penyisihan', 'Final'] as $roundLabel): ?>
                                                    <option value="<?= e($roundLabel) ?>" <?= $selectionJudgingRound === $roundLabel ? 'selected' : '' ?>>
                                                        <?= e($roundLabel) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="rounded-[1.4rem] border border-slate-800 bg-slate-950/55 px-4 py-4">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan Umum</label>
                                            <textarea name="remarks" rows="3" placeholder="Catatan umum penilaian..." class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20"><?= e($selectionRemarks) ?></textarea>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <?php foreach ($participantsByDistrict as $districtId => $districtParticipants): ?>
                                            <?php
                                                $districtParticipants = $districtParticipants->values();
                                                $districtName = (string) ($districtParticipants->first()?->district?->name ?? 'Tanpa Kecamatan');
                                                $memberCount = $districtParticipants->count();
                                            ?>
                                            <label class="block cursor-pointer rounded-[1.5rem] border px-4 py-4 transition"
                                                :class="selectedIds.includes('<?= e($districtId) ?>') ? 'border-cyan-300 bg-cyan-400/10 shadow-[0_12px_35px_-20px_rgba(34,211,238,0.75)]' : 'border-slate-800 bg-slate-950/55 hover:border-cyan-400/30'">
                                                <div class="flex flex-wrap items-center justify-between gap-3">
                                                    <div>
                                                        <p class="text-xs uppercase tracking-[0.18em] text-cyan-200/80">Kecamatan</p>
                                                        <h4 class="mt-1 text-xl font-bold text-white"><?= e($districtName) ?></h4>
                                                        <p class="mt-1 text-sm text-slate-400">1 kecamatan = 1 regu. Pilih kecamatan ini sebagai satu tim.</p>
                                                    </div>
                                                    <span class="status-pill border-cyan-400/20 bg-cyan-400/10 text-cyan-100">
                                                        <?= e($memberCount) ?> peserta regu
                                                    </span>
                                                </div>

                                                <div class="mt-4 flex items-start gap-3">
                                                    <input
                                                        type="checkbox"
                                                        name="district_ids[]"
                                                        value="<?= e($districtId) ?>"
                                                        x-model="selectedIds"
                                                        class="mt-1 h-4 w-4 rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-300">
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Anggota Regu</p>
                                                        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                                            <?php foreach ($districtParticipants as $participant): ?>
                                                                <div class="rounded-[1.1rem] border border-slate-800 bg-slate-900/60 px-3 py-3">
                                                                    <p class="font-semibold text-white"><?= e($participant->name) ?></p>
                                                                    <p class="mt-1 text-xs text-slate-400"><?= e($participant->registration_number) ?></p>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($errors->has('competition_category_id')): ?>
                                        <p class="text-sm text-rose-300"><?= e($errors->first('competition_category_id')) ?></p>
                                    <?php endif; ?>
                                    <?php if ($errors->has('session_name')): ?>
                                        <p class="text-sm text-rose-300"><?= e($errors->first('session_name')) ?></p>
                                    <?php endif; ?>
                                    <?php if ($errors->has('district_ids')): ?>
                                        <p class="text-sm text-rose-300"><?= e($errors->first('district_ids')) ?></p>
                                    <?php endif; ?>
                                    <?php if ($errors->has('session_name')): ?>
                                        <p class="text-sm text-rose-300"><?= e($errors->first('session_name')) ?></p>
                                    <?php endif; ?>

                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-slate-800 bg-slate-950/45 px-4 py-3">
                                        <div>
                                            <p class="text-sm text-slate-300">Jumlah pilihan harus di antara 2 dan 5 kecamatan. Setiap kecamatan otomatis mewakili 1 regu.</p>
                                            <p class="mt-1 text-xs text-slate-500">Setelah disimpan, nama sesi dan daftar kecamatan ini menjadi dasar untuk tahap penilaian MFQ berikutnya.</p>
                                        </div>
                                        <div class="flex flex-wrap gap-3">
                                            <button type="submit" class="primary-button px-5 py-3" :disabled="!canContinue" :class="!canContinue ? 'cursor-not-allowed opacity-50' : ''">
                                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                Lanjut ke Tahap 2
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

                    <div class="space-y-6 xl:sticky xl:top-6">
                        <div class="glass-card rounded-[2rem] p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Kecamatan Terpilih</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Pilihan regu yang sudah masuk</h3>
                                    <p class="mt-2 text-sm text-slate-300">Panel ini merangkum semua kecamatan yang dipilih untuk sesi MFQ.</p>
                                </div>
                                <div class="rounded-[1rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3 text-right">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-cyan-100/70">Regu Dipilih</p>
                                    <p class="mt-1 text-3xl font-black text-cyan-100" x-text="selectedIds.length"></p>
                                </div>
                            </div>
                            <?php if (filled($selectionSessionName)): ?>
                                <div class="mt-4 rounded-[1.25rem] border border-cyan-400/16 bg-cyan-400/10 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.18em] text-cyan-100/70">Nama Sesi</p>
                                    <p class="mt-1 font-semibold text-white"><?= e($selectionSessionName) ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="mt-4 rounded-[1.25rem] border border-slate-800 bg-slate-950/55 px-4 py-3" x-show="selectedIds.length > 0" x-cloak>
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Status Pilihan</p>
                                <p class="mt-1 text-sm font-semibold text-white" x-text="selectionStatusLabel()"></p>
                                <p class="mt-1 text-xs text-slate-500" x-text="selectionDistrictLabel()"></p>
                            </div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <?php if ($selectedParticipants->isEmpty()): ?>
                                    <div class="data-card text-sm text-slate-300 sm:col-span-2">Belum ada kecamatan yang dipilih.</div>
                                <?php else: ?>
                                    <?php foreach ($selectedParticipants as $participant): ?>
                                        <?php $districtParticipantCount = $participantsByDistrict->get((int) $participant->district_id, collect())->count(); ?>
                                        <div class="rounded-[1.35rem] border border-slate-800 bg-slate-950/55 px-4 py-3">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-xs uppercase tracking-[0.18em] text-cyan-200/80">Kecamatan</p>
                                                    <p class="mt-1 text-lg font-bold text-white"><?= e($participant->district?->name ?? '-') ?></p>
                                                    <p class="mt-1 text-xs text-slate-400"><?= e($districtParticipantCount) ?> peserta regu</p>
                                                </div>
                                                <span class="status-pill border-cyan-400/20 bg-cyan-400/10 text-cyan-100">
                                                    <?= e($districtParticipantCount) ?> orang
                                                </span>
                                            </div>
                                            <div class="mt-3 rounded-2xl border border-slate-800 bg-slate-900/60 px-3 py-3">
                                                <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Perwakilan Regu</p>
                                                <p class="mt-1 font-semibold text-white"><?= e($participant->name) ?></p>
                                                <p class="mt-1 text-xs text-slate-400"><?= e($participant->registration_number) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="glass-card rounded-[2rem] p-6">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Tahap Berikutnya</p>
                            <div class="mt-5 grid gap-4 sm:grid-cols-2">
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
            const districtCards = Array.isArray(initialState.districtCards) ? initialState.districtCards : [];
            const districtCardMap = Object.fromEntries(districtCards.map((card) => [String(card.district_id), card]));

            return {
                selectedIds: Array.isArray(initialState.initialSelectedIds) ? initialState.initialSelectedIds.map((value) => String(value)) : [],
                sessionName: String(initialState.initialSessionName || ''),
                districtCardMap,
                get canContinue() {
                    return this.sessionName.trim().length > 0
                        && this.selectedIds.length >= 2
                        && this.selectedIds.length <= 5;
                },
                selectionStatusLabel() {
                    const count = this.selectedIds.length;
                    if (count === 0) {
                        return 'Belum ada kecamatan dipilih';
                    }

                    if (count < 2) {
                        return 'Minimal pilih 2 kecamatan';
                    }

                    if (count > 5) {
                        return 'Maksimal 5 kecamatan';
                    }

                    return this.sessionName.trim().length > 0 ? `${count} kecamatan siap lanjut` : 'Isi nama sesi terlebih dahulu';
                },
                selectionDistrictLabel() {
                    const selectedDistricts = this.selectedIds
                        .map((id) => districtCardMap[String(id)])
                        .filter((card) => Boolean(card));

                    if (selectedDistricts.length === 0) {
                        return 'Pilih 2 sampai 5 kecamatan untuk membentuk regu.';
                    }

                    const districtNames = selectedDistricts
                        .map((card) => card.district_name)
                        .filter((value) => Boolean(value));

                    return districtNames.length > 0
                        ? `Kecamatan terpilih: ${districtNames.join(', ')}`
                        : 'Kecamatan sudah dipilih.';
                },
            };
        }
    </script>
</body>
</html>
