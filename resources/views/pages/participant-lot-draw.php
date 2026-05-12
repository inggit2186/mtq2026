<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$participant = $participant ?? null;
$lotPrefix = (string) ($lotPrefix ?? 'MTQ');
$lotParity = (string) ($lotParity ?? 'even');
$lotNumber = (string) ($participant?->lot_number ?? '');
$lotAssigned = filled($lotNumber);
$lotSuffix = $lotAssigned && str_contains($lotNumber, '-')
    ? (int) substr($lotNumber, (int) strpos($lotNumber, '-') + 1)
    : null;
$lotLabel = $lotAssigned ? $lotNumber : $lotPrefix.'-___';
$parityLabel = $lotParity === 'even' ? 'Putra / Genap' : 'Putri / Ganjil';
$parityColor = $lotParity === 'even' ? 'from-cyan-400 to-sky-500' : 'from-fuchsia-400 to-pink-500';
$lotRuleLabel = (string) ($lotRuleLabel ?? '1 peserta = 1 nomor lot');
$lotGroupSize = (int) ($lotGroupSize ?? 1);
$photoDataUri = (string) ($photoDataUri ?? '');
$initials = (string) ($initials ?? 'P');
$assignUrl = route('participants.lot.assign', $participant);
$autoFullscreen = request()->boolean('autofullscreen');
$lotRangeLabel = (string) ($lotRangeLabel ?? '001 - 999');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Layar Undian Nomor Lot') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main id="lot-stage" class="relative flex h-screen flex-col overflow-hidden" x-data="{ rolling: false, assigned: <?= $lotAssigned ? 'true' : 'false' ?>, currentSuffix: '<?= e($lotAssigned && $lotSuffix !== null ? str_pad((string) $lotSuffix, 3, '0', STR_PAD_LEFT) : '---') ?>', status: '<?= e($lotAssigned ? 'Nomor lot sudah dikunci' : 'Tekan tombol untuk memulai undian') ?>', history: [] }">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.28),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.18),_transparent_26%),linear-gradient(135deg,rgba(2,6,23,0.98),rgba(8,15,43,0.96))]"></div>
        <div class="hero-orb hero-orb-cyan right-[-8rem] top-10 h-80 w-80 opacity-70"></div>
        <div class="hero-orb hero-orb-blue left-[-9rem] bottom-[-6rem] h-[28rem] w-[28rem] opacity-50"></div>

        <header class="relative z-10 mx-auto flex max-w-[1500px] flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <div class="icon-chip h-14 w-14 rounded-2xl"><?= mtq_icon('sparkles', 'h-6 w-6') ?></div>
                <div>
                    <p class="section-kicker">e-MTQ Console</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-white">Layar Undian Nomor Lot</h1>
                    <p class="mt-2 text-sm text-slate-300">Tampilkan layar ini ke pengunjung. Peserta menekan tombol untuk memulai putaran dan sistem akan mengunci hasilnya.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <button id="fullscreen-button" type="button" class="secondary-button">
                    <?= mtq_icon('spark', 'h-4 w-4') ?>
                    Layar Penuh
                </button>
                <a href="<?= e(route('participants.show', $participant)) ?>" id="back-button" class="secondary-button">
                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    Kembali
                </a>
                <?php if ($lotAssigned): ?>
                    <span class="status-pill border-emerald-400/20 bg-emerald-400/10 text-emerald-100">
                        <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                        Sudah dikunci
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <section class="relative z-10 mx-auto grid w-full max-w-[1500px] flex-1 min-h-0 gap-6 px-4 pb-4 sm:px-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:px-8">
            <aside class="glass-card flex min-h-0 flex-col overflow-y-auto rounded-[2rem] p-5">
                <p class="section-kicker">Peserta</p>
                <div class="mt-3 overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-950/60">
                    <div class="aspect-[4/5] bg-slate-950/80">
                        <?php if (filled($photoDataUri)): ?>
                            <img src="<?= e($photoDataUri) ?>" alt="<?= e($participant?->name) ?>" class="h-full w-full object-contain object-center">
                        <?php else: ?>
                            <div class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.18),_rgba(15,23,42,0.95))]">
                                <div class="flex h-28 w-28 items-center justify-center rounded-[1.5rem] border border-white/10 bg-white/5 text-4xl font-black tracking-[0.2em] text-cyan-100 shadow-[0_0_80px_rgba(34,211,238,0.18)]">
                                    <?= e($initials) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="border-t border-white/10 bg-slate-950/75 p-3">
                        <p class="text-xs uppercase tracking-[0.22em] text-cyan-200">Peserta</p>
                        <h2 class="mt-1 text-xl font-black leading-tight text-white"><?= e($participant?->name) ?></h2>
                    </div>
                </div>
                <div class="mt-3 inline-flex w-fit rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1.5 text-xs font-semibold text-cyan-100">
                    <?= e($participant?->registration_number) ?>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="rounded-2xl border border-slate-700/80 bg-slate-950/60 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Kecamatan</p>
                        <p class="mt-2 text-sm font-semibold text-white"><?= e($participant?->district?->name ?? '-') ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-700/80 bg-slate-950/60 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Golongan</p>
                        <p class="mt-2 text-sm font-semibold text-white"><?= e(trim((string) ($participant?->category?->branch ?? '-'). ' - '. (string) ($participant?->category?->name ?? '-'))) ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-700/80 bg-slate-950/60 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Aturan</p>
                        <div class="mt-2 inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-200">
                            <?= e($parityLabel) ?>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="relative flex min-h-0 flex-col overflow-hidden rounded-[2rem] border border-cyan-400/20 bg-slate-950/60 p-4 shadow-[0_30px_120px_-50px_rgba(15,23,42,0.95)] backdrop-blur-xl sm:p-6">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-400 via-blue-500 to-fuchsia-500"></div>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="section-kicker">Nomor Lot</p>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-white xl:text-3xl">Angka akan berputar seperti acara TV</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Tekan tombol di bawah untuk memulai. Setelah putaran selesai, sistem otomatis mengunci nomor lot yang sesuai dengan aturan genap atau ganjil.</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/5 px-4 py-3 text-right">
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Kode Golongan</p>
                        <p class="mt-1 text-xl font-black tracking-[0.24em] text-cyan-200"><?= e($lotPrefix) ?></p>
                        <p class="mt-2 text-[11px] uppercase tracking-[0.18em] text-slate-400">Range Nomor</p>
                        <p class="mt-1 text-sm font-semibold text-white"><?= e($lotRangeLabel) ?></p>
                        <p class="mt-3 text-[11px] uppercase tracking-[0.18em] text-slate-400">Aturan Khusus</p>
                        <p class="mt-1 text-sm font-semibold text-white"><?= e($lotRuleLabel) ?></p>
                    </div>
                </div>

                <div class="mt-5 grid flex-1 min-h-0 gap-5">
                    <div class="rounded-[2rem] border border-slate-700/80 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.12),_transparent_42%),linear-gradient(180deg,rgba(15,23,42,0.92),rgba(2,6,23,0.98))] p-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Nomor Lot</p>
                                <p class="mt-2 text-sm text-slate-300">Klik tombol untuk memulai pengacakan.</p>
                            </div>
                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?= e($lotParity === 'even' ? 'border-cyan-300/20 bg-cyan-400/10 text-cyan-100' : 'border-fuchsia-300/20 bg-fuchsia-400/10 text-fuchsia-100') ?>">
                                <?= e($parityLabel) ?>
                            </span>
                        </div>

                        <div class="mt-5 flex flex-col items-center justify-center gap-4 text-center">
                            <div class="relative mx-auto flex w-full max-w-3xl items-center justify-center rounded-[2.5rem] border border-white/10 bg-slate-950/60 px-4 py-8 shadow-[0_28px_100px_-50px_rgba(8,15,43,1)]">
                                <div class="absolute inset-0 rounded-[2.5rem] bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.08),_transparent_35%)]"></div>
                                <div class="relative flex flex-col items-center gap-4">
                                        <div class="inline-flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-300">
                                            <?= mtq_icon('sparkles', 'h-4 w-4') ?>
                                        Nomor Lot
                                        </div>
                                    <div class="flex flex-nowrap items-center justify-center gap-3 overflow-hidden px-2">
                                        <span class="inline-flex shrink-0 items-center justify-center rounded-[1.1rem] border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-[0.8rem] font-black tracking-[0.16em] text-cyan-100 shadow-[0_0_40px_rgba(34,211,238,0.16)]"><?= e($lotPrefix) ?></span>
                                        <span
                                            class="inline-flex min-w-[7rem] shrink-0 items-center justify-center whitespace-nowrap text-center font-black leading-[0.9] tracking-[0.04em] text-white drop-shadow-[0_0_24px_rgba(255,255,255,0.15)]"
                                            style="font-size: clamp(4rem, 10vw, 8rem);"
                                            id="lot-suffix-display"
                                        ><?= e($lotAssigned && $lotSuffix !== null ? str_pad((string) $lotSuffix, 3, '0', STR_PAD_LEFT) : '---') ?></span>
                                    </div>
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-400" id="lot-status"><?= e($lotAssigned ? 'Nomor lot sudah dikunci' : 'Tekan tombol untuk memulai putaran') ?></p>
                                </div>
                            </div>

                            <div class="grid w-full gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 px-4 py-3 text-left">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Peserta</p>
                                    <p class="mt-2 text-sm font-semibold text-white"><?= e($participant?->name) ?></p>
                                </div>
                                <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 px-4 py-3 text-left">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Nomor Lot</p>
                                    <p class="mt-2 text-sm font-semibold text-white" id="lot-final-display"><?= e($lotLabel) ?></p>
                                </div>
                                <div class="rounded-2xl border border-slate-700/80 bg-slate-950/70 px-4 py-3 text-left">
                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">Status</p>
                                    <p class="mt-2 text-sm font-semibold text-white" id="lot-rolling-label"><?= e($lotAssigned ? 'Terkunci' : 'Menunggu putaran') ?></p>
                                </div>
                            </div>

                            <div class="flex flex-wrap justify-center gap-3 pt-1">
                                <button
                                    id="start-draw-button"
                                    type="button"
                                    class="primary-button px-6 py-4 text-base <?= $lotAssigned ? 'cursor-not-allowed opacity-60' : '' ?>"
                                    <?= $lotAssigned ? 'disabled' : '' ?>
                                >
                                    <?= mtq_icon('sparkles', 'h-5 w-5') ?>
                                    <?= e($lotAssigned ? 'Sudah Dikunci' : 'Ambil Nomor') ?>
                                </button>
                                <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button px-6 py-4 text-base">
                                    <?= mtq_icon('id-card', 'h-5 w-5') ?>
                                    Detail Peserta
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-[1.8rem] border border-slate-700/80 bg-slate-950/60 px-4 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="section-kicker">Ketentuan</p>
                                <p class="mt-2 text-sm text-slate-300">Format kode-golongan-nomor, putra genap, putri ganjil.</p>
                                <?php if ($lotGroupSize > 1): ?>
                                    <p class="mt-2 text-sm text-cyan-100">Setiap grup dibagi per <?= e($lotGroupSize) ?> peserta sesuai aturan khusus golongan ini.</p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 font-semibold uppercase tracking-[0.18em] text-slate-200">Kode Golongan</span>
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 font-semibold uppercase tracking-[0.18em] text-slate-200">Nomor 3 Digit</span>
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 font-semibold uppercase tracking-[0.18em] text-slate-200">Auto Lock</span>
                            </div>
                        </div>
                        <div class="mt-3 space-y-2" id="lot-history">
                            <div class="rounded-2xl border border-white/5 bg-white/5 px-4 py-3 text-sm text-slate-300">Belum ada putaran.</div>
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

        @keyframes lot-bounce {
            0% {
                transform: scale(1);
                text-shadow: 0 0 0 rgba(34, 211, 238, 0);
            }
            40% {
                transform: scale(1.05);
                text-shadow: 0 0 30px rgba(34, 211, 238, 0.35);
            }
            70% {
                transform: scale(0.99);
                text-shadow: 0 0 42px rgba(34, 211, 238, 0.48);
            }
            100% {
                transform: scale(1.01);
                text-shadow: 0 0 24px rgba(34, 211, 238, 0.28);
            }
        }

        .lot-bounce {
            animation: lot-bounce 520ms cubic-bezier(.2,.8,.2,1);
        }
    </style>
    <script>
        (function () {
            const button = document.getElementById('start-draw-button');
            const suffixDisplay = document.getElementById('lot-suffix-display');
            const finalDisplay = document.getElementById('lot-final-display');
            const statusDisplay = document.getElementById('lot-status');
            const rollingLabel = document.getElementById('lot-rolling-label');
            const history = document.getElementById('lot-history');
            const fullscreenButton = document.getElementById('fullscreen-button');
            const backButton = document.getElementById('back-button');
            const stage = document.getElementById('lot-stage');
            const confettiHost = document.createElement('div');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const assignUrl = <?= json_encode($assignUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const prefix = <?= json_encode($lotPrefix, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const parity = <?= json_encode($lotParity, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const alreadyAssigned = <?= $lotAssigned ? 'true' : 'false' ?>;
            const assignedValue = <?= json_encode($lotNumber, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            confettiHost.className = 'pointer-events-none absolute inset-0 z-20 overflow-hidden';
            stage.appendChild(confettiHost);
            let rollingTimer = null;
            let finalLockTimer = null;
            let drawStarted = false;
            let audioContext = null;

            function padNumber(value) {
                return String(value).padStart(3, '0');
            }

            function nextCandidate() {
                const start = parity === 'even' ? 2 : 1;
                const maxIndex = 498;
                const index = Math.floor(Math.random() * maxIndex);
                return padNumber(start + (index * 2));
            }

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

                    setTimeout(() => {
                        piece.remove();
                    }, 1800);
                }
            }

            function bounceFinalNumber() {
                finalDisplay.classList.remove('lot-bounce');
                void finalDisplay.offsetWidth;
                finalDisplay.classList.add('lot-bounce');
                setTimeout(() => {
                    finalDisplay.classList.remove('lot-bounce');
                }, 600);
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

            function finalizeDisplay(lotNumber, withEffects = true) {
                const pieces = lotNumber.split('-');
                suffixDisplay.textContent = pieces[1] || '---';
                finalDisplay.textContent = lotNumber;
                statusDisplay.textContent = 'Nomor lot resmi sudah dikunci';
                rollingLabel.textContent = 'Selesai';
                if (withEffects) {
                    playLockSound();
                    spawnConfetti();
                    bounceFinalNumber();
                }
                pushHistory('Hasil final', lotNumber);
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');
                button.innerHTML = 'Nomor Sudah Terkunci';
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

            function runSpinSequence() {
                const phases = [
                    { rounds: 16, delay: 58, status: 'Mengacak nomor...' },
                    { rounds: 8, delay: 92, status: 'Sedikit melambat...' },
                    { rounds: 6, delay: 150, status: 'Makin mendekati hasil...' },
                    { rounds: 4, delay: 240, status: 'Menahan angka terakhir...' },
                    { rounds: 2, delay: 360, status: 'Mengunci hasil...' },
                ];

                let phaseIndex = 0;
                let phaseRound = 0;
                let tick = 0;
                let currentDelay = phases[0].delay;

                const advance = () => {
                    if (phaseIndex >= phases.length) {
                        return;
                    }

                    const phase = phases[phaseIndex];
                    const candidate = nextCandidate();
                    suffixDisplay.textContent = candidate;
                    finalDisplay.textContent = `${prefix}-${candidate}`;
                    statusDisplay.textContent = phase.status;
                    rollingLabel.textContent = phaseIndex < phases.length - 1 ? 'Sedang berputar' : 'Melambat';

                    if (tick % 3 === 0) {
                        playTickSound();
                    }

                    if (tick % 5 === 0) {
                        pushHistory('Putaran', `${prefix}-${candidate}`);
                    }

                    tick += 1;
                    phaseRound += 1;
                    currentDelay = phase.delay;

                    if (phaseIndex === 0) {
                        finalDisplay.classList.add('scale-[1.01]', 'transition-transform', 'duration-150');
                    } else if (phaseIndex >= 3) {
                        finalDisplay.classList.remove('scale-[1.01]');
                        finalDisplay.classList.add('scale-[1.03]');
                        if (phaseIndex === 3 && phaseRound <= 1) {
                            bounceFinalNumber();
                        }
                    } else {
                        finalDisplay.classList.remove('scale-[1.03]');
                    }

                    if (phaseRound >= phase.rounds) {
                        phaseIndex += 1;
                        phaseRound = 0;
                    }

                    if (phaseIndex < phases.length) {
                        rollingTimer = setTimeout(advance, currentDelay);
                    } else {
                        finalLockTimer = setTimeout(async () => {
                            clearSpinTimers();
                            suffixDisplay.textContent = '...';
                            statusDisplay.textContent = 'Mengunci hasil...';

                            try {
                                const response = await fetch(assignUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                    body: JSON.stringify({}),
                                });

                                const payload = await response.json();

                                if (!response.ok) {
                                    throw new Error(payload?.message || 'Gagal mengambil nomor lot.');
                                }

                                finalizeDisplay(payload.lot_number || assignedValue);
                            } catch (error) {
                                statusDisplay.textContent = error.message || 'Terjadi kesalahan saat mengunci nomor lot.';
                                rollingLabel.textContent = 'Gagal';
                                button.disabled = false;
                                button.innerHTML = 'Coba Lagi';
                                drawStarted = false;
                            }
                        }, 420);
                    }
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
                button.innerHTML = 'Menjalankan Undian...';
                clearSpinTimers();
                runSpinSequence();
            }

            if (!alreadyAssigned) {
                button.addEventListener('click', startDraw);
            } else {
                finalizeDisplay(assignedValue, false);
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
        })();
    </script>
</body>
</html>
