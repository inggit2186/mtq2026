<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$participant = $participant ?? null;
$documentMap = $documentMap ?? [];
$canVerify = $canVerify ?? false;
$districtMandate = $districtMandate ?? null;
$officialMandateRejected = in_array($user?->role, ['official', 'pendamping'], true)
    && $user?->district?->mandate_status === 'rejected';
$canEditParticipant = ! (
    (in_array($user?->role, ['official', 'pendamping'], true)
    && $participant?->verification_status === 'verified')
    || $officialMandateRejected
);
$canDeleteParticipant = in_array($user?->role, ['admin', 'panitia'], true)
    || in_array($participant?->verification_status, ['submitted', 'rejected'], true);
$usesOfficialDeleteCopy = in_array($user?->role, ['official', 'pendamping'], true);
$cvDownloadUrl = $cvDownloadUrl ?? null;
$canManageMaqra = $canManageMaqra ?? false;
$canDrawParticipant = $canDrawParticipant ?? in_array($user?->role, ['admin', 'panitia'], true);
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'participants.list');
$usesMaqra = $participant?->category ? app(\App\Http\Controllers\PageController::class)->categoryUsesMaqra($participant->category) : false;
$latestMaqraDraw = $participant?->latestMaqraDraw;
$latestMaqraRound = (string) ($latestMaqraDraw?->round_label ?? 'Penyisihan');
$maqraSwapCandidates = $maqraSwapCandidates ?? collect();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Detail Peserta') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('id-card') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Detail Peserta</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
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

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">No. Registrasi</p>
                        <p class="mt-2 text-lg font-bold text-white"><?= e($participant?->registration_number) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Status Verifikasi</p>
                        <p class="mt-2 text-lg font-bold text-white"><?= e(ucfirst((string) $participant?->verification_status)) ?></p>
                    </div>
                    <div class="data-card">
                        <div class="flex items-center gap-2 text-slate-500">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('id-card', 'h-4 w-4') ?></span>
                            <p class="text-xs uppercase tracking-[0.24em]">Nomor Lot</p>
                        </div>
                        <?php
                            $lotSequenceValue = null;
                            if (filled($participant?->lot_number) && preg_match('/-(\d+)$/', (string) $participant?->lot_number, $matches)) {
                                $lotSequenceValue = (int) $matches[1];
                            }
                        ?>
                        <?php if (filled($participant?->lot_number)): ?>
                            <p class="mt-2 text-lg font-bold text-white"><?= e($participant?->lot_number) ?></p>
                            <p class="mt-1 text-xs text-emerald-300">Diambil pada <?= e(optional($participant?->lot_assigned_at)->format('d M Y H:i') ?: '-') ?></p>
                            <?php if ($canDrawParticipant): ?>
                                <a href="<?= e(route('participants.lot.draw', $participant).'?autofullscreen=1') ?>" data-lot-launcher class="secondary-button mt-3 w-full justify-center border-cyan-300/30 bg-cyan-400/10 text-[11px] text-cyan-100 hover:border-cyan-200/50">
                                    <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                    Ambil Nomor Lot
                                </a>
                            <?php endif; ?>
                            <?php if ($canManageLot): ?>
                                <details class="mt-4 rounded-2xl border border-slate-700/80 bg-slate-950/70 p-4">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Aksi Admin Lot</p>
                                            <p class="mt-1 text-sm font-semibold text-cyan-200">Reset, ubah, atau tukar nomor lot</p>
                                        </div>
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-200">
                                            <?= mtq_icon('pencil', 'h-3.5 w-3.5') ?>
                                        </span>
                                    </summary>

                                    <div class="mt-4 space-y-4">
                                        <form method="POST" action="<?= e(route('participants.lot.reset', $participant)) ?>" data-swal-confirm data-swal-title="Reset nomor lot?" data-swal-text="Nomor lot peserta ini akan dikosongkan dan bisa diambil ulang." data-swal-confirm="Ya, reset" data-swal-cancel="Batal" class="flex flex-wrap items-center gap-3" data-loading-text="Mereset nomor lot peserta...">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-4 py-2 text-xs text-rose-100 hover:border-rose-300/40">
                                                <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                Reset Lot
                                            </button>
                                        </form>

                                        <form method="POST" action="<?= e(route('participants.lot.update', $participant)) ?>" class="grid gap-3 sm:grid-cols-[1fr_160px]" data-loading-text="Menyimpan nomor lot peserta...">
                                            <?= csrf_field() ?>
                                            <div>
                                                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Ubah nomor sequence</label>
                                                <input name="lot_sequence" type="number" min="1" step="1" value="<?= e(old('lot_sequence', (string) ($lotSequenceValue ?? ''))) ?>" placeholder="001" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                            </div>
                                            <div class="flex items-end">
                                                <button type="submit" class="primary-button w-full rounded-xl px-4 py-3 text-sm">
                                                    <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                                    Ubah
                                                </button>
                                            </div>
                                        </form>

                                        <?php if (! $lotSwapCandidates->isEmpty()): ?>
                                            <form method="POST" action="<?= e(route('participants.lot.swap', $participant)) ?>" class="grid gap-3 sm:grid-cols-[1fr_160px]" data-loading-text="Menukar nomor lot peserta...">
                                                <?= csrf_field() ?>
                                                <div>
                                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Tukar dengan peserta</label>
                                                    <select name="swap_participant_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                                        <option value="">Pilih peserta lain</option>
                                                        <?php foreach ($lotSwapCandidates as $candidate): ?>
                                                            <option value="<?= e($candidate->id) ?>"><?= e($candidate->name.' | '.$candidate->registration_number.' | '.$candidate->lot_number) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="flex items-end">
                                                    <button type="submit" class="secondary-button w-full rounded-xl px-4 py-3 text-sm">
                                                        <?= mtq_icon('refresh-cw', 'h-4 w-4') ?>
                                                        Tukar
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            <?php endif; ?>
                        <?php elseif ($participant?->verification_status === 'verified'): ?>
                            <p class="mt-2 text-sm text-slate-300">Peserta sudah terverifikasi dan siap diambil nomor lot-nya.</p>
                            <?php if ($canDrawParticipant): ?>
                                <a href="<?= e(route('participants.lot.draw', $participant).'?autofullscreen=1') ?>" data-lot-launcher class="secondary-button mt-3 w-full justify-center border-cyan-300/30 bg-cyan-400/10 text-[11px] text-cyan-100 hover:border-cyan-200/50">
                                    <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                    Ambil Nomor Lot
                                </a>
                            <?php endif; ?>
                            <p class="mt-2 text-xs text-slate-400">Format: kode golongan - nomor. Putra genap, putri ganjil.</p>
                            <?php if ($canManageLot): ?>
                                <details class="mt-4 rounded-2xl border border-slate-700/80 bg-slate-950/70 p-4">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Aksi Admin Lot</p>
                                            <p class="mt-1 text-sm font-semibold text-cyan-200">Siapkan nomor lot untuk peserta ini</p>
                                        </div>
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-200">
                                            <?= mtq_icon('pencil', 'h-3.5 w-3.5') ?>
                                        </span>
                                    </summary>
                                    <div class="mt-4">
                                        <form method="POST" action="<?= e(route('participants.lot.assign', $participant)) ?>" class="flex flex-wrap items-center gap-3" data-loading-text="Mengambil nomor lot peserta...">
                                            <?= csrf_field() ?>
                                                <button type="submit" class="secondary-button rounded-xl border-cyan-300/30 bg-cyan-400/10 px-4 py-2 text-[11px] text-cyan-100 hover:border-cyan-200/50">
                                                    <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                                    Ambil Nomor Lot
                                                </button>
                                        </form>
                                    </div>
                                </details>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="mt-2 text-sm text-slate-300">Nomor lot tersedia setelah peserta berstatus terverifikasi.</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($usesMaqra): ?>
                        <div class="data-card">
                            <div class="flex items-center gap-2 text-slate-500">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-fuchsia-300/14 bg-fuchsia-400/10 text-fuchsia-200"><?= mtq_icon('sparkles', 'h-4 w-4') ?></span>
                                <p class="text-xs uppercase tracking-[0.24em]">Maqra</p>
                            </div>
                            <?php if ($latestMaqraDraw?->maqraPackage): ?>
                                <?php
                                    $maqraLabel = trim((string) preg_replace('/^(Tilawah|Tahfizh|Tafsir|Fahmil)\s*-\s*/u', '', (string) $latestMaqraDraw->maqraPackage->title));
                                    $maqraLabel = $maqraLabel !== '' ? (str_starts_with($maqraLabel, 'QS') ? $maqraLabel : 'QS '.$maqraLabel) : '-';
                                ?>
                                <p class="mt-2 text-lg font-bold text-white"><?= e($maqraLabel) ?></p>
                                <p class="mt-1 text-xs text-emerald-300">Diambil pada <?= e(optional($latestMaqraDraw?->drawn_at)->format('d M Y H:i') ?: '-') ?> • <?= e($latestMaqraDraw->round_label ?? 'Penyisihan') ?></p>
                                <p class="mt-2 text-sm text-slate-300">QS resmi untuk pengambilan maqra ini.</p>
                                <?php if ($canDrawParticipant): ?>
                                    <a href="<?= e(route('participants.maqra.draw', $participant).'?autofullscreen=1&round='.urlencode($latestMaqraRound)) ?>" data-maqra-launcher class="secondary-button mt-3 w-full justify-center border-fuchsia-300/30 bg-fuchsia-400/10 text-[11px] text-fuchsia-100 hover:border-fuchsia-200/50">
                                        <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                        Ambil Maqra
                                    </a>
                                <?php endif; ?>
                                <?php if ($canManageMaqra): ?>
                                    <details class="mt-4 rounded-2xl border border-slate-700/80 bg-slate-950/70 p-4">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Aksi Admin Maqra</p>
                                                <p class="mt-1 text-sm font-semibold text-fuchsia-200">Reset atau tukar QS peserta ini</p>
                                            </div>
                                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl border border-slate-700 bg-slate-900/80 text-slate-200">
                                                <?= mtq_icon('pencil', 'h-3.5 w-3.5') ?>
                                            </span>
                                        </summary>

                                        <div class="mt-4 space-y-4">
                                            <form method="POST" action="<?= e(route('participants.maqra.reset', $participant)) ?>" data-swal-confirm data-swal-title="Reset maqra?" data-swal-text="Pengambilan maqra pada babak ini akan dihapus dan bisa diambil ulang." data-swal-confirm="Ya, reset" data-swal-cancel="Batal" class="flex flex-wrap items-center gap-3" data-loading-text="Mereset maqra peserta...">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="maqra_round" value="<?= e($latestMaqraRound) ?>">
                                                <button type="submit" class="secondary-button rounded-xl border-rose-400/20 bg-rose-400/10 px-4 py-2 text-[11px] text-rose-100 hover:border-rose-300/40">
                                                    <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                    Reset Maqra
                                                </button>
                                            </form>

                                            <?php if ($maqraSwapCandidates->isNotEmpty()): ?>
                                                <form method="POST" action="<?= e(route('participants.maqra.swap', $participant)) ?>" class="grid gap-3 sm:grid-cols-[1fr_160px]" data-loading-text="Menukar maqra peserta...">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="maqra_round" value="<?= e($latestMaqraRound) ?>">
                                                    <div>
                                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Tukar dengan peserta</label>
                                                        <select name="swap_participant_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-fuchsia-300 focus:ring-2 focus:ring-fuchsia-400/20">
                                                            <option value="">Pilih peserta lain</option>
                                                            <?php foreach ($maqraSwapCandidates as $candidate): ?>
                                                                <?php
                                                                    $candidateDraw = $candidate->maqraDraws->firstWhere('round_label', $latestMaqraRound);
                                                                    $candidateLabel = trim((string) preg_replace('/^(Tilawah|Tahfizh|Tafsir|Fahmil)\s*-\s*/u', '', (string) ($candidateDraw?->maqraPackage?->title ?? '')));
                                                                    $candidateLabel = $candidateLabel !== '' ? (str_starts_with($candidateLabel, 'QS') ? $candidateLabel : 'QS '.$candidateLabel) : '-';
                                                                ?>
                                                                <option value="<?= e($candidate->id) ?>"><?= e($candidate->name.' | '.$candidate->registration_number.' | '.$candidateLabel) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="flex items-end">
                                                        <button type="submit" class="secondary-button w-full rounded-xl px-4 py-3 text-[11px]">
                                                            <?= mtq_icon('refresh-cw', 'h-4 w-4') ?>
                                                            Tukar
                                                        </button>
                                                    </div>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            <?php elseif ($participant?->verification_status === 'verified'): ?>
                                <p class="mt-2 text-sm text-slate-300">Peserta sudah terverifikasi dan siap diambil maqra-nya.</p>
                                <?php if ($canDrawParticipant): ?>
                                    <a href="<?= e(route('participants.maqra.draw', $participant).'?autofullscreen=1&round=Penyisihan') ?>" data-maqra-launcher class="secondary-button mt-3 w-full justify-center border-fuchsia-300/30 bg-fuchsia-400/10 text-[11px] text-fuchsia-100 hover:border-fuchsia-200/50">
                                        <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                        Ambil Maqra
                                    </a>
                                <?php endif; ?>
                                <p class="mt-2 text-xs text-slate-400">Babak default dimulai dari Penyisihan.</p>
                                <?php if ($canManageMaqra): ?>
                                    <div class="mt-4 rounded-2xl border border-slate-700/80 bg-slate-950/70 p-4">
                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Info Admin</p>
                                        <p class="mt-1 text-sm text-slate-300">Maqra diacak dari seed test sesuai sistem MTQ nasional pada cabang yang memakainya.</p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="mt-2 text-sm text-slate-300">Maqra tersedia setelah peserta berstatus terverifikasi.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
                            <p class="section-kicker">Profil Peserta</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white"><?= e($participant?->name) ?></h2>
                            <p class="mt-2 text-sm text-slate-300"><?= e($participant?->district?->name ?? '-') ?> | <?= e($participant?->category?->branch ?? '-') ?></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <?php if ($canEditParticipant): ?>
                            <a href="<?= e(route('participants.edit', $participant)) ?>" class="secondary-button">
                                <?= mtq_icon('id-card', 'h-4 w-4') ?>
                                Perbaiki Data
                            </a>
                        <?php else: ?>
                            <span class="status-pill">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                Data terkunci
                            </span>
                        <?php endif; ?>
                        <?php if ($canDeleteParticipant): ?>
                            <form method="POST" action="<?= e(route('participants.archive', $participant)) ?>" <?= $usesOfficialDeleteCopy ? 'data-swal-confirm data-swal-title="Hapus data peserta?" data-swal-text="Data akan dipindahkan ke arsip admin dan dapat dipanggil kembali jika diperlukan." data-swal-confirm="Ya, hapus" data-swal-cancel="Batal"' : '' ?> data-loading-text="Memindahkan peserta ke arsip...">
                                <?= csrf_field() ?>
                                <button type="submit" class="secondary-button border-rose-400/20 bg-rose-400/10 text-rose-100 hover:border-rose-300/40">
                                    <?= mtq_icon('trash', 'h-4 w-4') ?>
                                    <?= e($usesOfficialDeleteCopy ? 'Hapus' : 'Arsipkan') ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        <a href="<?= e(route('participants.list')) ?>" class="secondary-button">
                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                            Kembali ke daftar
                        </a>
                    </div>
                </header>

                <?php if ($canVerify && $districtMandate): ?>
                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center justify-center gap-3 text-center">
                            <div class="icon-chip"><?= mtq_icon('book-open') ?></div>
                            <div>
                                <p class="section-kicker">Mandat Kecamatan</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Surat mandat kecamatan peserta</h3>
                            </div>
                        </div>
                        <div class="mt-6 flex flex-wrap justify-center gap-4">
                            <?php
                            $mandateStatus = $districtMandate->mandate_status ?: 'submitted';
                            $mandateStatusLabel = match ($mandateStatus) {
                                'verified' => 'Terverifikasi',
                                'rejected' => 'Ditolak',
                                default => 'Sudah Upload',
                            };
                            $mandateStatusClass = match ($mandateStatus) {
                                'verified' => 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100',
                                'rejected' => 'border-rose-400/20 bg-rose-400/10 text-rose-100',
                                default => 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100',
                            };
                            ?>
                            <div class="data-card w-full max-w-lg px-5 py-6 text-center sm:px-6">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500"><?= e($districtMandate->name ?? 'Tanpa kecamatan') ?></p>
                                        <h4 class="mt-2 text-lg font-bold text-white">Surat Mandat Kecamatan</h4>
                                        <p class="mt-1 text-sm text-slate-400">Berlaku untuk seluruh official pada kecamatan ini</p>
                                    </div>
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?= e($mandateStatusClass) ?>">
                                        <?= e($mandateStatusLabel) ?>
                                    </span>
                                </div>
                                <div class="mt-5 space-y-2 text-sm text-slate-300">
                                    <p>Upload: <?= e($districtMandate->mandate_uploaded_at?->translatedFormat('d F Y H:i') ?? '-') ?></p>
                                    <?php if ($districtMandate->mandate_verification_notes): ?>
                                        <p class="text-slate-400"><?= e($districtMandate->mandate_verification_notes) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-5 flex justify-center">
                                    <a href="<?= e(route('participants.mandate.district-preview', ['district' => $districtMandate])) ?>" target="_blank" rel="noreferrer" class="secondary-button rounded-xl px-3 py-2 text-xs">
                                        <?= mtq_icon('book-open', 'h-4 w-4') ?>
                                        Pratinjau PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('upload') ?></div>
                        <div>
                            <p class="section-kicker">Dokumen</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Berkas administrasi peserta</h3>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <?php foreach ($documentMap as $key => $document): ?>
                            <?php
                            $documentIcon = match ($key) {
                                'photo' => 'users',
                                'kk', 'ktp', 'student_card' => 'id-card',
                                default => 'book-open',
                            };
                            ?>
                            <div class="data-card">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200">
                                        <?= mtq_icon($documentIcon, 'h-4 w-4') ?>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-white"><?= e($document['label']) ?></p>
                                        <p class="mt-2 text-sm text-slate-300">
                                            <?php if ($document['multiple'] ?? false): ?>
                                                <?= ! empty($document['files']) ? e(count($document['files']).' file tersedia') : 'Dokumen belum diunggah' ?>
                                            <?php else: ?>
                                                <?= $document['path'] ? 'Dokumen tersedia' : 'Dokumen belum diunggah' ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <?php if (! empty($document['revision_note'])): ?>
                                    <div class="mt-3 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-3 py-3 text-xs leading-6 text-amber-100">
                                        <p class="font-semibold uppercase tracking-[0.18em] text-amber-200">Catatan Revisi</p>
                                        <p class="mt-2"><?= e($document['revision_note']) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (($document['multiple'] ?? false) && ! empty($document['files'])): ?>
                                    <div class="mt-4 space-y-2">
                                        <?php foreach (($document['files'] ?? []) as $index => $path): ?>
                                            <div class="rounded-2xl border border-slate-700/80 bg-slate-950/60 px-3 py-3">
                                                <p class="text-xs uppercase tracking-[0.18em] text-slate-500"><?= e($document['label'].' #'.($index + 1)) ?></p>
                                                <div class="mt-3 grid gap-2">
                                                    <a href="<?= e(route('participants.documents.preview', ['participant' => $participant, 'document' => $key, 'index' => $index])) ?>" target="_blank" rel="noreferrer" class="secondary-button w-full rounded-xl px-3 py-2 text-xs">
                                                        <?= mtq_icon('eye', 'h-4 w-4') ?>
                                                        Pratinjau
                                                    </a>
                                                    <a href="<?= e(route('participants.documents.download', ['participant' => $participant, 'document' => $key, 'index' => $index])) ?>" class="secondary-button w-full rounded-xl px-3 py-2 text-xs">
                                                        <?= mtq_icon('upload', 'h-4 w-4') ?>
                                                        Unduh Dokumen
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($document['path']): ?>
                                    <div class="mt-4 grid gap-2">
                                        <a href="<?= e(route('participants.documents.preview', ['participant' => $participant, 'document' => $key])) ?>" target="_blank" rel="noreferrer" class="secondary-button w-full rounded-xl px-3 py-2 text-xs">
                                            <?= mtq_icon('eye', 'h-4 w-4') ?>
                                            Pratinjau
                                        </a>
                                        <a href="<?= e(route('participants.documents.download', ['participant' => $participant, 'document' => $key])) ?>" class="secondary-button w-full rounded-xl px-3 py-2 text-xs">
                                            <?= mtq_icon('upload', 'h-4 w-4') ?>
                                            Unduh Dokumen
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('users') ?></div>
                            <div>
                                <p class="section-kicker">Identitas Lengkap</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Data pribadi dan administrasi</h3>
                            </div>
                        </div>
                        <?php $photoDocument = $documentMap['photo'] ?? null; ?>
                        <div class="mt-6 space-y-5">
                            <div class="data-card">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Pas Foto</p>
                                        <p class="mt-1 text-sm text-slate-300"><?= e($participant?->name ?: '-') ?></p>
                                    </div>
                                    <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-100">
                                        <?= e(($participant?->participant_role ?? 'main') === 'reserve' ? 'Cadangan' : 'Peserta Inti') ?>
                                    </span>
                                </div>
                                <div class="mt-4 flex justify-center">
                                    <?php if (! empty($photoDocument['path'])): ?>
                                        <img
                                            src="<?= e(route('participants.documents.preview', ['participant' => $participant, 'document' => 'photo'])) ?>"
                                            alt="<?= e('Pas foto '.$participant?->name) ?>"
                                            class="h-72 w-56 rounded-[1.4rem] border border-cyan-300/12 object-cover shadow-[0_20px_50px_-28px_rgba(34,211,238,0.5)]"
                                        >
                                    <?php else: ?>
                                        <div class="flex h-72 w-56 items-center justify-center rounded-[1.4rem] border border-dashed border-slate-700 bg-slate-950/65 text-center text-sm leading-6 text-slate-400">
                                            Pas foto belum tersedia.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="data-card md:col-span-2">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('users', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Nama</p>
                                    </div>
                                    <p class="mt-2 text-base font-semibold text-white"><?= e($participant?->name) ?></p>
                                    <p class="mt-2 text-xs text-slate-400"><?= e(($participant?->participant_role ?? 'main') === 'reserve' ? 'Peserta Cadangan' : 'Peserta Inti') ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('fingerprint', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">NIK</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e($participant?->nik ?: '-') ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('calendar', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Tanggal KTP</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e(optional($participant?->ktp_date)->format('d M Y') ?: '-') ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('bell', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">No. HP</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e($participant?->phone ?: '-') ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('shield', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Gender</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e(ucfirst((string) $participant?->gender)) ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('clock', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Umur</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?php if ($participant?->date_of_birth): ?><?php $age = $participant->date_of_birth->diff(now()); ?><?= e($age->y.' tahun '.$age->m.' bulan '.$age->d.' hari') ?><?php else: ?>-<?php endif; ?></p>
                                </div>
                                <div class="data-card md:col-span-2">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('calendar', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Tempat, Tanggal Lahir</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e(trim(($participant?->place_of_birth ?? '-').', '.optional($participant?->date_of_birth)->format('d M Y'))) ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('home', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Kecamatan</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e($participant?->district?->name ?? '-') ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('building', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Lembaga</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e($participant?->institution ?: '-') ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('book-open', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Pendidikan Terakhir</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e($participant?->last_education ?: '-') ?></p>
                                </div>
                                <div class="data-card md:col-span-2">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('layers', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Cabang & Golongan</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e($participant?->category?->branch.' - '.$participant?->category?->name) ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('home', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Nomor KK</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e($participant?->kk_number ?: '-') ?></p>
                                </div>
                                <div class="data-card">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('calendar', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Tanggal KK</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e(optional($participant?->kk_date)->format('d M Y') ?: '-') ?></p>
                                </div>
                                <div class="data-card md:col-span-2">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('home', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Alamat saat ini</p>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-white"><?= e($participant?->current_address ?: '-') ?></p>
                                </div>
                                <div class="data-card md:col-span-2">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('id-card', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Alamat sesuai KTP</p>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-white"><?= e($participant?->ktp_address ?: '-') ?></p>
                                    <p class="mt-2 text-xs text-slate-400">Kecamatan <?= e($participant?->ktp_district ?: '-') ?>, Kabupaten <?= e($participant?->ktp_regency ?: '-') ?></p>
                                </div>
                                <div class="data-card md:col-span-2">
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-cyan-300/14 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('check-circle', 'h-4 w-4') ?></span>
                                        <p class="text-xs uppercase tracking-[0.2em]">Data Rekening</p>
                                    </div>
                                    <p class="mt-2 text-sm text-white"><?= e(($participant?->bank_name ?: '-') . ' | ' . ($participant?->bank_account_number ?: '-') . ' | ' . ($participant?->bank_account_name ?: '-')) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('check-circle') ?></div>
                            <div>
                                <p class="section-kicker">Status Verifikasi</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Catatan panitia</h3>
                            </div>
                        </div>
                        <div class="mt-6 space-y-3">
                            <div class="data-card">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Status saat ini</p>
                                <p class="mt-2 text-sm text-white"><?= e(ucfirst((string) $participant?->verification_status)) ?></p>
                            </div>
                            <div class="data-card">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Catatan</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($participant?->verification_notes ?: 'Belum ada catatan verifikasi.') ?></p>
                            </div>
                            <?php if ($canVerify): ?>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Form verifikasi</p>
                                    <form method="POST" action="<?= e(route('participants.verify', $participant)) ?>" class="mt-4 space-y-3" data-verification-form>
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Status verifikasi</label>
                                            <select name="verification_status" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" data-verification-status>
                                                <option value="verified">Setujui</option>
                                                <option value="rejected" <?= $participant?->verification_status === 'rejected' ? 'selected' : '' ?>>Tolak</option>
                                                <option value="submitted" <?= $participant?->verification_status === 'submitted' ? 'selected' : '' ?>>Kembalikan ke menunggu</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan umum</label>
                                            <textarea name="verification_notes" rows="3" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Catatan verifikasi umum untuk official" data-verification-notes><?= e(old('verification_notes', $participant?->verification_notes)) ?></textarea>
                                        </div>
                                        <div class="grid gap-3">
                                            <?php foreach ($documentMap as $key => $document): ?>
                                                <div>
                                                    <label class="mb-2 block text-sm font-semibold text-slate-200"><?= e('Catatan '.$document['label']) ?></label>
                                                    <textarea name="document_revision_notes[<?= e($key) ?>]" rows="2" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Opsional, isi bila dokumen ini perlu diperbaiki"><?= e(old('document_revision_notes.'.$key, $document['revision_note'] ?? '')) ?></textarea>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if ($districtMandate): ?>
                                            <div class="rounded-2xl border border-cyan-400/14 bg-slate-950/50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Surat Mandat Kecamatan</p>
                                                <p class="mt-2 text-sm leading-6 text-slate-300">Pilih status surat mandat kecamatan saat menyimpan verifikasi peserta ini.</p>
                                                <div class="mt-4 grid gap-3">
                                                    <select name="mandate_status" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                                        <option value="">Tidak diubah</option>
                                                        <option value="verified">Setujui surat mandat kecamatan</option>
                                                        <option value="rejected">Tolak surat mandat kecamatan</option>
                                                        <option value="submitted">Kembalikan ke menunggu</option>
                                                    </select>
                                                    <textarea name="mandate_verification_notes" rows="2" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20" placeholder="Catatan surat mandat kecamatan, bila diperlukan"></textarea>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <button type="submit" class="primary-button w-full justify-center">
                                            <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                            Simpan Verifikasi
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('clock') ?></div>
                        <div>
                            <p class="section-kicker">Riwayat Verifikasi</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Jejak perubahan status peserta</h3>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <?php if ($participant?->verificationLogs?->isEmpty()): ?>
                            <div class="data-card text-sm text-slate-300">Belum ada histori verifikasi untuk peserta ini.</div>
                        <?php else: ?>
                            <?php foreach ($participant->verificationLogs as $log): ?>
                                <div class="data-card">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-white"><?= e(ucfirst((string) $log->status)) ?></p>
                                            <p class="mt-1 text-xs text-slate-400"><?= e(optional($log->created_at)->format('d M Y H:i')) ?><?php if ($log->verifier): ?> | <?= e($log->verifier->name) ?><?php endif; ?></p>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-sm leading-6 text-slate-300"><?= e($log->notes ?: 'Tanpa catatan tambahan.') ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
        (function () {
            const launchers = document.querySelectorAll('[data-lot-launcher], [data-maqra-launcher]');
            launchers.forEach((launcher) => {
                launcher.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = launcher.getAttribute('href');
                    if (!url) {
                        return;
                    }

                    const windowName = launcher.hasAttribute('data-maqra-launcher') ? 'mtq-maqra-draw' : 'mtq-lot-draw';

                    const popup = window.open(
                        url,
                        windowName,
                        `popup=yes,width=${screen.availWidth},height=${screen.availHeight},left=0,top=0,noopener=no`
                    );

                    if (popup) {
                        try {
                            popup.moveTo(0, 0);
                            popup.resizeTo(screen.availWidth, screen.availHeight);
                            popup.focus();
                        } catch (error) {
                            popup.focus();
                        }
                    } else {
                        window.location.href = url;
                    }
                });
            });
        })();
    </script>
    <script>
        document.querySelectorAll('[data-verification-form]').forEach((form) => {
            const statusField = form.querySelector('[data-verification-status]');
            const notesField = form.querySelector('[data-verification-notes]');

            if (!statusField || !notesField) {
                return;
            }

            const defaultNotes = {
                verified: 'Berkas peserta telah diverifikasi dan dinyatakan lengkap.',
                rejected: 'Berkas peserta perlu diperbaiki sesuai catatan verifikasi.',
                submitted: 'Peserta dikembalikan ke status menunggu verifikasi.',
            };

            const syncDefaultNote = () => {
                const status = statusField.value;
                const nextNote = defaultNotes[status] ?? '';
                notesField.value = nextNote;
            };

            statusField.addEventListener('change', syncDefaultNote);
            syncDefaultNote();
        });
    </script>
</body>
</html>
