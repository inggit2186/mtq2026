<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$participant = $participant ?? null;
$maqraRound = (string) ($maqraRound ?? 'Penyisihan');
$maqraRoundLabel = (string) ($maqraRoundLabel ?? 'Penyisihan');
$maqraSystemLabel = (string) ($maqraSystemLabel ?? 'Maqra');
$maqraCodePrefix = (string) ($maqraCodePrefix ?? 'MQR');
$districtSharedMaqra = $districtSharedMaqra ?? false;
$districtParticipants = $districtParticipants ?? collect();
$maqraCandidates = collect($maqraCandidates ?? []);
$maqraAssignedPackage = $maqraPackage ?? null;
$maqraAssignedCode = (string) ($maqraAssignedPackage?->maqra_code ?? '');
$maqraAssignedTitle = (string) ($maqraAssignedPackage?->title ?? '');
$maqraAssignedContent = (string) ($maqraAssignedPackage?->content ?? '');

// MSQ specific
$usesDistrictMaqraTitles = $usesDistrictMaqraTitles ?? false;
$districtMaqraTitles = $districtMaqraTitles ?? collect();
$currentMsqDistrictTitle = $currentMsqDistrictTitle ?? null;
$maqraAssignedTitle = $usesDistrictMaqraTitles && $currentMsqDistrictTitle
    ? (string) $currentMsqDistrictTitle->title
    : $maqraAssignedTitle;
$maqraAssignedContent = $usesDistrictMaqraTitles && $currentMsqDistrictTitle
    ? (string) ($currentMsqDistrictTitle->description ?? '')
    : $maqraAssignedContent;

$formatMaqraLabel = static function (string $label): string {
    $cleanLabel = trim((string) preg_replace('/^(Tilawah|Tahfizh|Tafsir|Fahmil)\s*-\s*/u', '', $label));

    if ($cleanLabel === '') {
        return '---';
    }

    return str_starts_with($cleanLabel, 'QS') ? $cleanLabel : 'QS '.$cleanLabel;
};

$maqraAssignedLabel = $formatMaqraLabel($maqraAssignedTitle);
$maqraAssigned = filled($maqraAssignedTitle);
$maqraDrawnAt = $maqraDrawnAt ?? null;
$photoDataUri = (string) ($maqraPhotoDataUri ?? '');
$initials = (string) ($initials ?? 'P');
$assignUrl = route('participants.maqra.assign', $participant);
$autoFullscreen = request()->boolean('autofullscreen');
$maqraPackageCount = (int) ($maqraPackageCount ?? 0);
$roundOptions = ['Penyisihan', 'Final'];
$currentRoundLabel = $maqraRound === 'Final' ? 'Final' : 'Penyisihan';

// MSQ candidates for the spinning animation
$msqCandidateLabels = $usesDistrictMaqraTitles
    ? $districtMaqraTitles->map(fn($t) => ['id' => $t->id, 'title' => $t->title, 'content' => $t->description ?? ''])->values()->all()
    : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Layar Pengambilan Maqra') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main id="maqra-stage" class="relative flex h-screen flex-col overflow-hidden" x-data="{ rolling: false, assigned: <?= $maqraAssigned ? 'true' : 'false' ?>, currentCode: '<?= e($maqraAssignedCode ?: '---') ?>', currentTitle: <?= json_encode($maqraAssignedTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, currentContent: <?= json_encode($maqraAssignedContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, status: '<?= e($maqraAssigned ? $maqraAssignedLabel : 'Tekan tombol untuk memulai pengambilan') ?>', history: [] }">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.28),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.18),_transparent_26%),linear-gradient(135deg,rgba(2,6,23,0.98),rgba(8,15,43,0.96))]"></div>
        <div class="hero-orb hero-orb-cyan right-[-8rem] top-10 h-80 w-80 opacity-70"></div>
        <div class="hero-orb hero-orb-blue left-[-9rem] bottom-[-6rem] h-[28rem] w-[28rem] opacity-50"></div>

        <header class="relative z-10 mx-auto flex max-w-[1500px] flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <div class="icon-chip h-12 w-12 rounded-2xl"><?= mtq_icon('sparkles', 'h-5 w-5') ?></div>
                <div>
                    <p class="section-kicker">e-MTQ Console</p>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-white lg:text-[2.05rem]">Layar Pengambilan Maqra</h1>
                    <p class="mt-1 max-w-3xl text-xs text-slate-300 sm:text-sm">Tampilkan layar ini ke pengunjung. Peserta menekan tombol untuk mengacak paket maqra lalu sistem menguncinya otomatis.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button id="fullscreen-button" type="button" class="secondary-button px-4 py-2 text-sm">
                    <?= mtq_icon('spark', 'h-4 w-4') ?>
                    Layar Penuh
                </button>
                <a href="<?= e(route('participants.show', $participant)) ?>" id="back-button" class="secondary-button px-4 py-2 text-sm">
                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    Kembali
                </a>
                <?php if ($maqraAssigned): ?>
                    <span class="status-pill border-emerald-400/20 bg-emerald-400/10 text-emerald-100 text-xs">
                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                        Sudah dikunci
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <section
            id="maqra-layout"
            class="relative z-10 mx-auto grid w-full max-w-[1500px] flex-1 min-h-0 gap-4 px-4 pb-2 sm:px-6 lg:px-8"
            style="grid-template-columns: 250px minmax(0, 1fr);"
        >
            <aside class="glass-card flex min-h-0 flex-col overflow-y-auto rounded-[2rem] p-4 lg:p-5">
                <?php if ($districtSharedMaqra): ?>
                <?php /* District-shared maqra: show district participants list */ ?>
                <p class="section-kicker">Asal Kecamatan</p>
                <div class="mt-3">
                    <div class="rounded-2xl border border-cyan-400/20 bg-cyan-400/5 px-4 py-3">
                        <p class="text-sm font-semibold text-white"><?= e($participant?->district?->name ?? '-') ?></p>
                    </div>
                </div>
                <?php if ($districtParticipants->isNotEmpty()): ?>
                <div class="mt-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-cyan-300 font-semibold">Peserta Satu Maqra</p>
                    <p class="mt-1 text-[11px] text-slate-500"><?= e($districtParticipants->count()) ?> peserta</p>
                    <div class="mt-3 space-y-3 max-h-[400px] overflow-y-auto pr-1">
                        <?php foreach ($districtParticipants as $dp): ?>
                        <?php
                            $dpPhoto = null;
                            if (filled($dp->document_photo)) {
                                $dpPhoto = asset('storage/'.ltrim(str_replace('\\', '/', $dp->document_photo), '/'));
                            }
                            $dpInitials = '';
                            if ($dp->name) {
                                $names = explode(' ', trim($dp->name));
                                $dpInitials = strtoupper(substr($names[0] ?? '', 0, 1));
                                if (count($names) > 1) {
                                    $dpInitials .= strtoupper(substr($names[count($names) - 1] ?? '', 0, 1));
                                }
                            }
                        ?>
                        <div class="flex flex-col items-center rounded-2xl border p-4 <?= $dp->id === $participant->id ? 'border-cyan-400/50 bg-cyan-400/10' : 'border-slate-700/50 bg-slate-950/40' ?>">
                            <div class="h-20 w-20 rounded-2xl border-2 border-white/20 bg-white/5 overflow-hidden flex items-center justify-center mb-3">
                                <?php if (filled($dpPhoto)): ?>
                                <img src="<?= e($dpPhoto) ?>" alt="" class="h-full w-full object-cover">
                                <?php else: ?>
                                <span class="text-2xl font-bold text-cyan-100"><?= e($dpInitials) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-m font-semibold text-center <?= $dp->id === $participant->id ? 'text-cyan-100' : 'text-white' ?> mb-2"><?= e($dp->name) ?></p>
                            <p class="text-sm <?= $dp->id === $participant->id ? 'text-cyan-300/70' : 'text-slate-400' ?> font-mono text-center">Lot: <?= e($dp->lot_number ?? '-') ?></p>
                            <?php if (filled($dp->nik)): ?>
                            <p class="text-sm <?= $dp->id === $participant->id ? 'text-cyan-300/70' : 'text-slate-400' ?> font-mono text-center">NIK: <?= e($dp->nik) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <?php /* Regular: show single participant */ ?>
                <p class="section-kicker">Peserta</p>
                <div class="mt-2 overflow-hidden rounded-[1.5rem] border border-white/10 bg-slate-950/60">
                    <div class="aspect-[4/5] bg-slate-950/80">
                        <?php if (filled($photoDataUri)): ?>
                            <img src="<?= e($photoDataUri) ?>" alt="<?= e($participant?->name) ?>" class="h-full w-full object-contain object-center">
                        <?php else: ?>
                            <div class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.18),_rgba(15,23,42,0.95))]">
                                <div class="flex h-24 w-24 items-center justify-center rounded-[1.35rem] border border-white/10 bg-white/5 text-3xl font-black tracking-[0.2em] text-cyan-100 shadow-[0_0_80px_rgba(34,211,238,0.18)]">
                                    <?= e($initials) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="border-t border-white/10 bg-slate-950/75 p-2.5">
                        <p class="text-[11px] uppercase tracking-[0.22em] text-cyan-200">Peserta</p>
                        <h2 class="mt-1 text-lg font-black leading-tight text-white"><?= e($participant?->name) ?></h2>
                    </div>
                </div>
                <div class="mt-3 inline-flex w-fit rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1.5 text-[11px] font-semibold text-cyan-100">
                    <?= e($participant?->lot_number ?? '-') ?>
                </div>
                <div class="mt-3 space-y-2.5">
                    <div class="rounded-2xl border border-slate-700/80 bg-slate-950/60 px-3 py-2.5">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Kecamatan</p>
                        <p class="mt-1.5 text-sm font-semibold text-white"><?= e($participant?->district?->name ?? '-') ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </aside>

            <section class="relative flex min-h-0 flex-col overflow-hidden rounded-[2rem] border border-cyan-400/20 bg-slate-950/60 p-4 shadow-[0_30px_120px_-50px_rgba(15,23,42,0.95)] backdrop-blur-xl sm:p-5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-400 via-blue-500 to-fuchsia-500"></div>

                <div class="w-full rounded-2xl border border-white/10 bg-white/5 p-3">
                    <p class="text-center text-xs uppercase tracking-[0.2em] text-slate-400 mb-6">Info Golongan</p>
                    <div class="flex flex-wrap items-start justify-center gap-6">
                        <div class="flex-1 min-w-[120px] text-center px-4 py-3 border-r border-white/10 last:border-r-0">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 mb-2">Golongan</p>
                            <p class="text-base font-semibold text-cyan-200"><?= e(trim((string) ($participant?->category?->branch ?? '-').' - '.(string) ($participant?->category?->name ?? '-'))) ?></p>
                        </div>
                        <div class="flex-1 min-w-[100px] text-center px-4 py-3 border-r border-white/10 last:border-r-0">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 mb-2">Sistem</p>
                            <p class="text-base font-semibold text-white"><?= e($maqraSystemLabel) ?></p>
                        </div>
                        <?php if (! $usesDistrictMaqraTitles): ?>
                        <div class="flex-1 min-w-[80px] text-center px-4 py-3 border-r border-white/10 last:border-r-0">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 mb-2">Kode</p>
                            <p class="text-2xl font-black tracking-[0.24em] text-cyan-200"><?= e($maqraCodePrefix) ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-[80px] text-center px-4 py-3 border-r border-white/10 last:border-r-0">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 mb-2">Babak</p>
                            <p class="text-base font-semibold text-white"><?= e($maqraRoundLabel) ?></p>
                        </div>
                        <div class="flex-1 min-w-[100px] text-center px-4 py-3">
                            <?php if ($usesDistrictMaqraTitles): ?>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 mb-2">Judul Tersedia</p>
                            <p class="text-base font-semibold text-white"><?= e(number_format($districtMaqraTitles->count())) ?></p>
                            <?php else: ?>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400 mb-2">Jumlah Paket</p>
                            <p class="text-base font-semibold text-white"><?= e(number_format($maqraPackageCount)) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($usesDistrictMaqraTitles): ?>
                        <?php if (! $maqraAssigned && $districtMaqraTitles->isEmpty()): ?>
                        <div class="mt-4 flex justify-center">
                            <div class="inline-flex rounded-full border border-rose-400/20 bg-rose-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-rose-100">
                                Belum ada judul MSQ untuk kecamatan ini
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="mt-3 flex justify-center">
                            <div class="inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-2 text-xs font-semibold text-amber-100">
                                <?= mtq_icon('information-circle', 'h-4 w-4') ?>
                                MSQ Berbasis Kecamatan
                            </div>
                        </div>
                    <?php elseif (! $maqraAssigned && $maqraPackageCount <= 0): ?>
                        <div class="mt-4 flex justify-center">
                            <div class="inline-flex rounded-full border border-rose-400/20 bg-rose-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-rose-100">
                                Stok maqra habis
                            </div>
                        </div>
                    <?php elseif (! $maqraAssigned && $maqraCandidates->isEmpty()): ?>
                        <div class="mt-4 flex justify-center">
                            <div class="inline-flex rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-100">
                                Belum ada kandidat maqra
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-center gap-4">
                    <p class="text-sm text-slate-300">Tekan tombol di bawah atau tekan <strong class="text-cyan-200">Enter</strong> untuk mengambil maqra.</p>
                </div>

                <div class="mt-4 grid flex-1 min-h-0 gap-4">
                    <div class="rounded-[2rem] border border-slate-700/80 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.12),_transparent_42%),linear-gradient(180deg,rgba(15,23,42,0.92),rgba(2,6,23,0.98))] p-4 sm:p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.24em] text-slate-400"><?= $usesDistrictMaqraTitles ? 'Judul MSQ' : 'Nomor Maqra' ?></p>
                                <p class="mt-1.5 text-[11px] text-slate-300 sm:text-xs">
                                    <?php if ($usesDistrictMaqraTitles): ?>
                                        Klik tombol untuk memilih judul secara acak.
                                    <?php else: ?>
                                        Klik tombol untuk memulai pengacakan paket.
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] border-cyan-300/20 bg-cyan-400/10 text-cyan-100">
                                <?= e($maqraRoundLabel) ?>
                            </span>
                        </div>

                        <div class="mt-4 flex flex-col items-center justify-center gap-3 text-center">
                            <div class="relative mx-auto flex w-full max-w-4xl items-center justify-center rounded-[2rem] border border-white/10 bg-slate-950/60 px-4 py-4 shadow-[0_28px_100px_-50px_rgba(8,15,43,1)]">
                                <div class="absolute inset-0 rounded-[2.5rem] bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.08),_transparent_35%)]"></div>
                                <div class="relative flex w-full flex-col items-center gap-3">
                                    <div class="inline-flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.28em] text-slate-300">
                                        <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                        <?= $usesDistrictMaqraTitles ? 'Judul MSQ' : 'Maqra' ?>
                                    </div>
                                    <div class="flex w-full flex-col items-center justify-center gap-2 overflow-hidden px-2">
                                        <span
                                            class="inline-flex shrink-0 items-center justify-center rounded-[1.1rem] border border-cyan-300/20 bg-cyan-400/10 px-3.5 py-2 text-[0.72rem] font-black tracking-[0.18em] text-cyan-100 shadow-[0_0_40px_rgba(34,211,238,0.16)]"
                                            id="maqra-code-display"
                                        ><?= e($maqraAssignedLabel ?: '---') ?></span>
                                        <span
                                            class="block w-full max-w-3xl text-center font-black leading-[0.95] tracking-[0.01em] text-white drop-shadow-[0_0_24px_rgba(255,255,255,0.15)]"
                                            style="font-size: clamp(1.15rem, 2.4vw, 3.1rem);"
                                            id="maqra-title-display"
                                        ><?= e($maqraAssignedTitle ?: ($usesDistrictMaqraTitles ? 'Judul belum dipilih' : 'Paket belum dipilih')) ?></span>
                                    </div>
                                    <div class="max-w-3xl rounded-[1.5rem] border border-white/8 bg-slate-950/70 px-4 py-2.5 text-left shadow-[0_16px_80px_-60px_rgba(34,211,238,0.3)]">
                                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400"><?= $usesDistrictMaqraTitles ? 'Deskripsi' : 'Isi Maqra' ?></p>
                                        <p class="mt-1.5 whitespace-pre-line text-[12px] leading-5 text-slate-200" id="maqra-content-display"><?= e($maqraAssignedContent ?: ($usesDistrictMaqraTitles ? 'Deskripsi judul akan muncul setelah terpilih.' : 'Maqra akan muncul setelah paket terkunci.')) ?></p>
                                    </div>
                                    <p class="text-[11px] uppercase tracking-[0.24em] text-slate-400" id="maqra-status"><?= e($maqraAssigned ? $maqraAssignedLabel : 'Tekan tombol untuk memulai pengacakan') ?></p>
                                </div>
                            </div>

                            <div class="grid w-full gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 px-3 py-2.5 text-left">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Peserta</p>
                                    <p class="mt-1.5 text-sm font-semibold text-white"><?= e($participant?->name) ?></p>
                                </div>
                                <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 px-3 py-2.5 text-left">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500"><?= $usesDistrictMaqraTitles ? 'Judul MSQ' : 'Maqra' ?></p>
                                    <p class="mt-1.5 text-sm font-semibold text-white" id="maqra-final-display"><?= e($maqraAssignedLabel ?: ($usesDistrictMaqraTitles ? '---' : $maqraCodePrefix.'-___')) ?></p>
                                </div>
                                <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 px-3 py-2.5 text-left">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Status</p>
                                    <p class="mt-1.5 text-sm font-semibold text-white" id="maqra-rolling-label"><?= e($maqraAssigned ? $maqraAssignedLabel : 'Menunggu putaran') ?></p>
                                </div>
                            </div>

                            <?php
                            $noCandidates = $usesDistrictMaqraTitles
                                ? $districtMaqraTitles->isEmpty()
                                : $maqraCandidates->isEmpty();
                            ?>
                            <div class="flex flex-wrap justify-center gap-3 pt-1">
                                <button
                                    id="start-draw-button"
                                    type="button"
                                    class="primary-button px-5 py-3.5 text-[11px] sm:px-6 sm:py-4 sm:text-sm <?= ($maqraAssigned || (! $maqraAssigned && $noCandidates)) ? 'cursor-not-allowed opacity-60' : '' ?>"
                                    <?= ($maqraAssigned || (! $maqraAssigned && $noCandidates)) ? 'disabled' : '' ?>
                                >
                                    <?= mtq_icon('sparkles', 'h-5 w-5') ?>
                                    <?php if ($maqraAssigned): ?>
                                        <?= e($maqraAssignedLabel) ?>
                                    <?php elseif ($noCandidates): ?>
                                        <?= $usesDistrictMaqraTitles ? 'Belum Ada Judul' : 'Stok Habis' ?>
                                    <?php else: ?>
                                        <?= $usesDistrictMaqraTitles ? 'Ambil Judul MSQ' : 'Ambil Maqra' ?>
                                    <?php endif; ?>
                                </button>
                                <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button px-5 py-3.5 text-[11px] sm:px-6 sm:py-4 sm:text-sm">
                                    <?= mtq_icon('id-card', 'h-5 w-5') ?>
                                    Detail Peserta
                                </a>
                            </div>
                            <?php if (! $maqraAssigned && $noCandidates): ?>
                                <p class="text-center text-xs text-rose-200">
                                    <?php if ($usesDistrictMaqraTitles): ?>
                                        Belum ada judul MSQ untuk kecamatan ini. Hubungi admin untuk menambahkan judul MSQ.
                                    <?php else: ?>
                                        Tidak ada paket maqra tersisa untuk babak ini. Silakan cek pengaturan paket atau gunakan babak lain.
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <div id="maqra-history" class="hidden"></div>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <style>
        @media (max-width: 1023px) {
            #maqra-layout {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @keyframes confetti-fall {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) translate(0, 0) rotate(0deg) scale(0.5);
            }
            12% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -50%) translate(var(--confetti-x), var(--confetti-y)) rotate(var(--confetti-rotate)) scale(0.95);
            }
        }

        @keyframes maqra-bounce {
            0% { transform: scale(1); text-shadow: 0 0 0 rgba(34, 211, 238, 0); }
            40% { transform: scale(1.05); text-shadow: 0 0 30px rgba(34, 211, 238, 0.35); }
            70% { transform: scale(0.99); text-shadow: 0 0 42px rgba(34, 211, 238, 0.48); }
            100% { transform: scale(1.01); text-shadow: 0 0 24px rgba(34, 211, 238, 0.28); }
        }

        .maqra-bounce {
            animation: maqra-bounce 520ms cubic-bezier(.2,.8,.2,1);
        }
    </style>
    <script>
        (function () {
            const button = document.getElementById('start-draw-button');
            const codeDisplay = document.getElementById('maqra-code-display');
            const titleDisplay = document.getElementById('maqra-title-display');
            const contentDisplay = document.getElementById('maqra-content-display');
            const statusDisplay = document.getElementById('maqra-status');
            const rollingLabel = document.getElementById('maqra-rolling-label');
            const finalDisplay = document.getElementById('maqra-final-display');
            const history = document.getElementById('maqra-history');
            const fullscreenButton = document.getElementById('fullscreen-button');
            const backButton = document.getElementById('back-button');
            const stage = document.getElementById('maqra-stage');
            const confettiHost = document.createElement('div');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const assignUrl = <?= json_encode($assignUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const roundLabel = <?= json_encode($maqraRoundLabel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const usesDistrictMaqraTitles = <?= $usesDistrictMaqraTitles ? 'true' : 'false' ?>;
            // Use MSQ candidates if MSQ, otherwise use regular maqra candidates
            const candidates = <?= json_encode($msqCandidateLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const regularCandidates = <?= json_encode($maqraCandidates->values()->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const alreadyAssigned = <?= $maqraAssigned ? 'true' : 'false' ?>;
            const assignedCode = <?= json_encode($maqraAssignedCode, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const assignedTitle = <?= json_encode($maqraAssignedTitle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const assignedContent = <?= json_encode($maqraAssignedContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const assignedLabel = <?= json_encode($maqraAssignedLabel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const noCandidates = candidates.length === 0;

            confettiHost.className = 'pointer-events-none absolute inset-0 z-20 overflow-hidden';
            stage.appendChild(confettiHost);

            let rollingTimer = null;
            let finalLockTimer = null;
            let drawStarted = false;
            let audioContext = null;

            function pushHistory(label, value) {
                const item = document.createElement('div');
                item.className = 'rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-50';
                item.textContent = `${label}: ${value}`;
                if (history.firstElementChild && history.firstElementChild.textContent === 'Belum ada putaran.') {
                    history.innerHTML = '';
                }
                history.prepend(item);
                while (history.children.length > 5) {
                    history.removeChild(history.lastElementChild);
                }
            }

            function spawnConfetti() {
                const colors = ['#67e8f9', '#a78bfa', '#f472b6', '#facc15', '#86efac'];
                for (let index = 0; index < 18; index += 1) {
                    const piece = document.createElement('span');
                    const size = 6 + Math.floor(Math.random() * 6);
                    piece.className = 'absolute top-1/2 left-1/2 rounded-sm opacity-90';
                    piece.style.width = `${size}px`;
                    piece.style.height = `${Math.max(3, Math.round(size * 0.55))}px`;
                    piece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    piece.style.transform = `translate(-50%, -50%) rotate(${Math.floor(Math.random() * 180)}deg)`;
                    piece.style.boxShadow = '0 0 12px rgba(255,255,255,0.18)';
                    piece.style.left = `${50 + (Math.random() * 18 - 9)}%`;
                    piece.style.top = `${38 + (Math.random() * 14 - 7)}%`;
                    piece.style.animation = `confetti-fall ${900 + Math.floor(Math.random() * 500)}ms ease-out forwards`;
                    piece.style.setProperty('--confetti-x', `${Math.floor(Math.random() * 420 - 210)}px`);
                    piece.style.setProperty('--confetti-y', `${Math.floor(220 + Math.random() * 260)}px`);
                    piece.style.setProperty('--confetti-rotate', `${Math.floor(Math.random() * 720 - 360)}deg`);
                    confettiHost.appendChild(piece);
                    setTimeout(() => piece.remove(), 1800);
                }
            }

            function bounceFinalNumber() {
                finalDisplay.classList.remove('maqra-bounce');
                void finalDisplay.offsetWidth;
                finalDisplay.classList.add('maqra-bounce');
                setTimeout(() => finalDisplay.classList.remove('maqra-bounce'), 600);
            }

            function getAudioContext() {
                const Ctor = window.AudioContext || window.webkitAudioContext;
                if (!Ctor) {
                    return null;
                }

                if (!audioContext) {
                    audioContext = new Ctor();
                }

                return audioContext;
            }

            async function ensureAudioReady() {
                const context = getAudioContext();
                if (!context) {
                    return null;
                }

                if (context.state === 'suspended') {
                    try {
                        await context.resume();
                    } catch (error) {
                        console.warn('Audio context tidak bisa di-resume:', error);
                    }
                }

                return context;
            }

            function playTone(frequency, duration, type = 'sine', gainValue = 0.05) {
                const context = getAudioContext();
                if (!context) {
                    return;
                }

                const oscillator = context.createOscillator();
                const gainNode = context.createGain();
                oscillator.type = type;
                oscillator.frequency.value = frequency;
                gainNode.gain.value = gainValue;

                oscillator.connect(gainNode);
                gainNode.connect(context.destination);

                const now = context.currentTime;
                gainNode.gain.setValueAtTime(0.0001, now);
                gainNode.gain.exponentialRampToValueAtTime(gainValue, now + 0.015);
                gainNode.gain.exponentialRampToValueAtTime(0.0001, now + duration);

                oscillator.start(now);
                oscillator.stop(now + duration + 0.03);
            }

            function playStartSound() {
                playTone(520, 0.12, 'sine', 0.04);
                setTimeout(() => playTone(680, 0.1, 'triangle', 0.03), 110);
            }

            function playTickSound() {
                playTone(880, 0.035, 'square', 0.018);
            }

            function playLockSound() {
                playTone(660, 0.14, 'triangle', 0.05);
                setTimeout(() => playTone(880, 0.12, 'triangle', 0.045), 110);
                setTimeout(() => playTone(990, 0.18, 'sine', 0.05), 220);
            }

            function clearSpinTimers() {
                if (rollingTimer) {
                    clearTimeout(rollingTimer);
                    rollingTimer = null;
                }
                if (finalLockTimer) {
                    clearTimeout(finalLockTimer);
                    finalLockTimer = null;
                }
            }

            function randomCandidate() {
                if (!candidates.length) {
                    return null;
                }

                return candidates[Math.floor(Math.random() * candidates.length)];
            }

            function renderCandidate(candidate) {
                if (!candidate) {
                    codeDisplay.textContent = '---';
                    titleDisplay.textContent = usesDistrictMaqraTitles ? 'Belum ada judul' : 'Belum ada paket';
                    contentDisplay.textContent = usesDistrictMaqraTitles
                        ? 'Belum tersedia judul MSQ untuk kecamatan ini.'
                        : 'Belum tersedia paket maqra untuk kategori dan babak ini.';
                    finalDisplay.textContent = '---';
                    return;
                }

                const label = formatMaqraLabel(candidate.title || candidate.code || (usesDistrictMaqraTitles ? 'Judul MSQ' : 'Paket Maqra'));
                codeDisplay.textContent = label;
                titleDisplay.textContent = label;
                contentDisplay.textContent = candidate.content || (usesDistrictMaqraTitles ? 'Deskripsi judul.' : 'Isi maqra akan tampil setelah terkunci.');
                finalDisplay.textContent = label;
            }

            function finalizeDisplay(payload, withEffects = true) {
                const code = payload?.maqra_code || assignedCode || '---';
                const title = payload?.maqra_title || assignedTitle || (usesDistrictMaqraTitles ? 'Judul MSQ' : 'Paket Maqra');
                const content = payload?.maqra_content || assignedContent || (usesDistrictMaqraTitles ? 'Judul MSQ telah dikunci.' : 'Isi maqra telah dikunci.');
                const label = formatMaqraLabel(title || assignedLabel || code || (usesDistrictMaqraTitles ? 'Judul MSQ' : 'Paket Maqra'));

                codeDisplay.textContent = label;
                titleDisplay.textContent = label;
                contentDisplay.textContent = content;
                finalDisplay.textContent = label;
                statusDisplay.textContent = label;
                rollingLabel.textContent = label;

                if (withEffects) {
                    playLockSound();
                    spawnConfetti();
                    bounceFinalNumber();
                }

                pushHistory('Hasil final', label);
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');
                button.textContent = label;

                if (window.opener && !window.opener.closed) {
                    try {
                        window.opener.postMessage({
                            type: 'participant.maqra.updated',
                            participant_id: <?= json_encode((int) ($participant?->id ?? 0)) ?>,
                            participant_name: <?= json_encode((string) ($participant?->name ?? '')) ?>,
                            registration_number: <?= json_encode((string) ($participant?->registration_number ?? '')) ?>,
                            maqra_round: <?= json_encode((string) $maqraRoundLabel) ?>,
                            maqra_round_label: <?= json_encode((string) $maqraRoundLabel) ?>,
                            maqra_code: code,
                            maqra_title: title,
                            maqra_content: content,
                            maqra_label: label,
                            drawn_at: payload?.drawn_at || <?= json_encode((string) optional($participant?->latestMaqraDraw?->drawn_at)->format('d M Y H:i') ?: '') ?>,
                            maqra_prefix: <?= json_encode((string) $maqraCodePrefix) ?>,
                            system_label: <?= json_encode((string) $maqraSystemLabel) ?>,
                            category_id: <?= json_encode((int) ($participant?->competition_category_id ?? 0)) ?>,
                            district_id: <?= json_encode((int) ($participant?->district_id ?? 0)) ?>,
                            district_shared: <?= json_encode($districtSharedMaqra) ?>,
                            verification_status: 'verified',
                        }, window.location.origin);
                    } catch (error) {
                        console.warn('Gagal mengirim update maqra ke opener:', error);
                    }
                }
            }

            function runSpinSequence() {
                const phases = [
                    { rounds: 14, delay: 60, status: 'Mengacak paket...' },
                    { rounds: 8, delay: 90, status: 'Sedikit melambat...' },
                    { rounds: 6, delay: 150, status: 'Makin mendekati hasil...' },
                    { rounds: 4, delay: 240, status: 'Menahan paket terakhir...' },
                    { rounds: 2, delay: 360, status: 'Mengunci hasil...' },
                ];

                let phaseIndex = 0;
                let phaseRound = 0;
                let tick = 0;

                const advance = () => {
                    if (phaseIndex >= phases.length) {
                        return;
                    }

                    const phase = phases[phaseIndex];
                    const candidate = randomCandidate();
                    if (candidate) {
                        renderCandidate(candidate);
                    }
                    statusDisplay.textContent = phase.status;
                    rollingLabel.textContent = phaseIndex < phases.length - 1 ? 'Sedang berputar' : 'Melambat';

                    if (tick % 3 === 0) {
                        playTickSound();
                    }

                    if (candidate && tick % 4 === 0) {
                        pushHistory('Putaran', formatMaqraLabel(candidate.title || candidate.code || 'Paket Maqra'));
                    }

                    tick += 1;
                    phaseRound += 1;

                    if (phaseIndex >= 3 && phaseRound <= 1) {
                        bounceFinalNumber();
                    }

                    if (phaseRound >= phase.rounds) {
                        phaseIndex += 1;
                        phaseRound = 0;
                    }

                    if (phaseIndex < phases.length) {
                        rollingTimer = setTimeout(advance, phase.delay);
                        return;
                    }

                    finalLockTimer = setTimeout(async () => {
                        clearSpinTimers();
                        statusDisplay.textContent = 'Mengunci hasil...';

                        try {
                            const response = await fetch(assignUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify({ maqra_round: roundLabel }),
                            });

                            const payload = await response.json();

                            if (!response.ok) {
                                const errorMessage = payload?.message || payload?.error || 'Terjadi kesalahan saat mengambil maqra. Silakan coba lagi.';
                                throw new Error(errorMessage);
                            }

                            finalizeDisplay(payload, true);
                        } catch (error) {
                            // Clear displays to prevent confusion
                            codeDisplay.textContent = '---';
                            titleDisplay.textContent = 'GAGAL';
                            contentDisplay.textContent = error.message || 'Terjadi kesalahan yang tidak diketahui.';
                            finalDisplay.textContent = '---';
                            statusDisplay.textContent = 'Pengambilan Gagal: ' + (error.message || 'Error tidak diketahui');
                            rollingLabel.textContent = 'Gagal';
                            button.disabled = true;
                            button.className = 'inline-flex items-center gap-2 rounded-xl border border-rose-400/30 bg-rose-500/10 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-rose-200 opacity-50 cursor-not-allowed';
                            button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg> Gagal';
                            drawStarted = false;
                        }
                    }, 420);
                };

                advance();
            }

            async function startDraw() {
                if (drawStarted || alreadyAssigned) {
                    return;
                }

                enterFullscreen();
                ensureAudioReady().then(playStartSound);
                drawStarted = true;
                button.disabled = true;
                statusDisplay.textContent = 'Putaran dimulai...';
                rollingLabel.textContent = 'Sedang berputar';
                button.innerHTML = 'Menjalankan Maqra...';
                clearSpinTimers();
                runSpinSequence();
            }

            if (!alreadyAssigned) {
                if (noCandidates) {
                    statusDisplay.textContent = 'Stok maqra habis';
                    rollingLabel.textContent = 'Tidak tersedia';
                } else {
                    button.addEventListener('click', startDraw);
                    // Enter key support
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' && !button.disabled && !drawStarted) {
                            e.preventDefault();
                            startDraw();
                        }
                    });
                }
            } else {
                finalizeDisplay({
                    maqra_code: assignedCode,
                    maqra_title: assignedTitle,
                    maqra_content: assignedContent,
                }, false);
            }

            function formatMaqraLabel(label) {
                const cleaned = (label || '')
                    .replace(/^(Tilawah|Tahfizh|Tafsir|Fahmil)\s*-\s*/i, '')
                    .trim();

                if (!cleaned) {
                    return usesDistrictMaqraTitles ? 'Judul MSQ' : 'Paket Maqra';
                }

                // MSQ titles don't need "QS" prefix - they're already proper titles
                if (usesDistrictMaqraTitles) {
                    return cleaned;
                }

                return /^QS\b/i.test(cleaned) ? cleaned : `QS ${cleaned}`;
            }

            async function enterFullscreen() {
                try {
                    if (document.fullscreenElement) {
                        return;
                    }

                    if (stage?.requestFullscreen) {
                        await stage.requestFullscreen();
                    } else if (document.documentElement.requestFullscreen) {
                        await document.documentElement.requestFullscreen();
                    }
                } catch (error) {
                    console.warn('Fullscreen tidak tersedia:', error);
                }
            }

            if (fullscreenButton) {
                fullscreenButton.addEventListener('click', enterFullscreen);
            }

            if (backButton) {
                backButton.addEventListener('click', (event) => {
                    if (window.opener && !window.opener.closed) {
                        event.preventDefault();
                        window.close();
                    }
                });
            }

            if (<?= $autoFullscreen ? 'true' : 'false' ?>) {
                const tryFullscreen = () => {
                    enterFullscreen();
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', tryFullscreen, { once: true });
                } else {
                    tryFullscreen();
                }

                window.addEventListener('load', tryFullscreen, { once: true });
                setTimeout(tryFullscreen, 250);
                setTimeout(tryFullscreen, 900);
            }

            // ========================================
            // Realtime sync: Reverb broadcast + polling fallback
            // ========================================

            const participantId = <?= json_encode((int) ($participant?->id ?? 0)) ?>;
            const maqraStatusUrl = <?= json_encode(route('participants.maqra.status', $participant), JSON_UNESCAPED_SLASHES) ?>;
            let isLockedByOther = false;
            let currentAssignedBy = null;

            // Function to handle when maqra is assigned by another user
            function handleMaqraLockedByOther(data) {
                if (isLockedByOther) return; // Already handled

                isLockedByOther = true;
                currentAssignedBy = data.assigned_by || 'Official lain';

                statusDisplay.textContent = 'Sudah dikunci';
                rollingLabel.textContent = 'Dikunci oleh ' + currentAssignedBy;

                // Disable button
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');
                button.innerHTML = '&#10003; Sudah Dikunci';

                // Show alert
                const alertDiv = document.createElement('div');
                alertDiv.className = 'fixed bottom-4 right-4 z-50 rounded-2xl border border-amber-400/30 bg-amber-500/20 px-4 py-3 text-sm text-amber-100 shadow-lg';
                alertDiv.innerHTML = '<strong>Perhatian:</strong> Maqra sudah dikunci oleh ' + currentAssignedBy + '. Halaman akan di-refresh.';
                document.body.appendChild(alertDiv);

                // Refresh page after 3 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            }

            // Polling fallback - check status every 5 seconds
            let pollingInterval = null;
            let echoConnected = false;

            async function pollMaqraStatus() {
                if (isLockedByOther) {
                    stopPolling();
                    return;
                }

                try {
                    const url = new URL(maqraStatusUrl);
                    url.searchParams.set('round', roundLabel);

                    const response = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.assigned && !alreadyAssigned) {
                            // Maqra was assigned by someone else while we were watching
                            handleMaqraLockedByOther({
                                assigned_by: 'Official lain',
                                maqra_code: data.maqra_code,
                                maqra_title: data.maqra_title
                            });
                        }
                    }
                } catch (error) {
                    // Silently ignore polling errors - it's a fallback
                }
            }

            function startPolling() {
                if (pollingInterval) return;
                pollingInterval = setInterval(pollMaqraStatus, 5000);
                pollMaqraStatus(); // Immediate first check
            }

            function stopPolling() {
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            }

            // Listen for Reverb broadcast (if Echo is available)
            if (window.Echo) {
                window.Echo.channel('mtq-live')
                    .listen('.maqra.assigned', (data) => {
                        if (data.participant_id === participantId && data.round_label === roundLabel && !alreadyAssigned) {
                            echoConnected = true;
                            handleMaqraLockedByOther(data);
                        }
                    })
                    .error((error) => {
                        console.warn('Echo connection failed, using polling fallback:', error);
                        startPolling();
                    });
            } else {
                // Echo not available, use polling only
                console.info('Echo not available, using polling fallback');
                startPolling();
            }

            // Also start polling as fallback in case Echo doesn't fire
            setTimeout(() => {
                if (!echoConnected && !isLockedByOther) {
                    startPolling();
                }
            }, 3000);

            // Stop polling when user starts draw
            button?.addEventListener('click', () => {
                stopPolling();
            });

            // Cleanup on page unload
            window.addEventListener('beforeunload', stopPolling);
        })();
    </script>
</body>
</html>
