<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$stats = $stats ?? [];
$recentScores = $recentScores ?? collect();
$queueParticipants = $queueParticipants ?? collect();
$selectedCategory = $selectedCategory ?? null;
$currentParticipant = $currentParticipant ?? null;
$latestScoredEntry = $latestScoredEntry ?? null;
$generatedAt = $generatedAt ?? now();
$eventTitle = config('app.name', 'e-MTQ');
$categoryLabel = $selectedCategory ? $selectedCategory->branch.' - '.$selectedCategory->name : 'Semua Golongan';
$currentBabak = $latestScoredEntry?->judging_round ?? 'Penyisihan';

$resolvePhoto = function($participant) {
    if (!$participant || empty($participant->document_photo)) return null;
    return asset('storage/'.ltrim(str_replace('\\', '/', $participant->document_photo), '/'));
};

$currentPhoto = $currentParticipant ? $resolvePhoto($currentParticipant) : null;
$scoredPhoto = $latestScoredEntry && $latestScoredEntry->participant ? $resolvePhoto($latestScoredEntry->participant) : null;
$breakdown = $latestScoredEntry ? ($latestScoredEntry->score_breakdown ?? []) : [];
$isMfq = isset($breakdown['type']) && $breakdown['type'] === 'MFQ';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($eventTitle) ?> - Big Screen</title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
    <style>
        @keyframes pulse-green { 0%, 100% { box-shadow: inset 0 0 60px rgba(16, 185, 129, 0.15), 0 0 40px rgba(16, 185, 129, 0.1); } 50% { box-shadow: inset 0 0 80px rgba(16, 185, 129, 0.25), 0 0 60px rgba(16, 185, 129, 0.15); } }
        @keyframes pulse-gold { 0%, 100% { box-shadow: inset 0 0 60px rgba(245, 158, 11, 0.2), 0 0 40px rgba(245, 158, 11, 0.1); } 50% { box-shadow: inset 0 0 80px rgba(245, 158, 11, 0.3), 0 0 60px rgba(245, 158, 11, 0.2); } }
        .glow-green { animation: pulse-green 2s ease-in-out infinite; }
        .glow-gold { animation: pulse-gold 2s ease-in-out infinite; }
        @keyframes live-dot { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.6; transform: scale(0.9); } }
        .live-dot { animation: live-dot 1.5s ease-in-out infinite; }
    </style>
</head>
<body class="bg-slate-950 text-white" x-data="{ waktu: new Date() }" x-init="setInterval(() => waktu = new Date(), 1000)">
    <div class="flex h-screen w-full flex-col gap-3 p-4">
        <!-- Header Bar - BESAR & TEBAL -->
        <div class="flex shrink-0 items-center justify-between rounded-2xl border border-white/5 bg-gradient-to-r from-slate-900 via-slate-900/90 to-slate-900 px-6 py-4">
            <div class="flex items-center gap-4">
                <img src="<?= e(asset('images/emtq-resmi.webp')) ?>" alt="Logo" class="h-14 w-14 rounded-xl border-2 border-cyan-400/30 bg-cyan-400/10 object-contain">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-white"><?= e($eventTitle) ?></h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-xl border-2 border-cyan-400/40 bg-cyan-400/10 px-4 py-2 text-lg font-bold text-cyan-200">
                            <span class="text-2xl">&#127981;</span>
                            <span><?= e($categoryLabel) ?></span>
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-xl border-2 border-purple-400/40 bg-purple-400/10 px-4 py-2 text-lg font-bold text-purple-200">
                            <span class="text-2xl">&#127917;</span>
                            <span><?= e($currentBabak) ?></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="rounded-2xl border-2 border-cyan-400/30 bg-cyan-400/10 px-5 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-cyan-300/70">Waktu</p>
                    <p class="text-3xl font-black text-white" x-text="waktu.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'})"><?= e($generatedAt->format('H:i:s')) ?></p>
                </div>
                <div class="rounded-2xl border border-slate-700/50 bg-slate-800/50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tanggal</p>
                    <p class="text-lg font-bold text-white" x-text="waktu.toLocaleDateString('id-ID', {weekday:'long', day:'2-digit', month:'long', year:'numeric'})"><?= e($generatedAt->translatedFormat('l, d F Y')) ?></p>
                </div>
            </div>
        </div>

        <!-- Main Content - SAMA BESAR -->
        <div class="flex flex-1 gap-4">
            <!-- KIRI: Sekarang Tampil -->
            <div class="flex flex-1 flex-col rounded-3xl border-2 border-emerald-400/40 bg-gradient-to-br from-emerald-950/70 via-slate-950 to-emerald-900/40 p-5 glow-green" id="current-card">
                <div class="mb-3 text-center" id="current-badge">
                    <div class="inline-flex items-center gap-3 rounded-2xl border-2 border-emerald-400/40 bg-emerald-400/10 px-6 py-2" id="current-status">
                        <span class="text-3xl" id="current-icon">&#127908;</span>
                        <span class="text-lg font-bold uppercase tracking-wider text-emerald-200" id="current-label">Sedang Tampil</span>
                        <span class="relative flex h-4 w-4">
                            <span class="live-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                            <span class="relative inline-flex h-4 w-4 rounded-full bg-emerald-500"></span>
                        </span>
                    </div>
                </div>

                <!-- Photo -->
                <div class="flex flex-1 items-center justify-center">
                    <div class="relative" id="current-photo-container">
                        <?php if ($currentPhoto): ?>
                            <img src="<?= e($currentPhoto) ?>" alt="<?= e($currentParticipant?->name ?? '') ?>" class="h-full max-h-[280px] w-auto rounded-3xl border-4 border-emerald-400/50 object-cover shadow-2xl" id="current-photo">
                        <?php else: ?>
                            <div class="flex h-[280px] w-[200px] items-center justify-center rounded-3xl border-4 border-emerald-400/50 bg-slate-900" id="current-photo-placeholder">
                                <span class="text-8xl font-black text-emerald-400/40" id="current-initial"><?= e($currentParticipant ? substr($currentParticipant->name, 0, 1) : '?') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info -->
                <div class="mt-4 text-center" id="current-info">
                    <div class="mb-3 inline-flex items-center gap-3 rounded-xl border-2 border-amber-400/30 bg-amber-400/10 px-5 py-2">
                        <span class="text-2xl">&#128278;</span>
                        <span class="text-sm font-bold uppercase tracking-wider text-amber-300">Nomor Lot</span>
                        <span class="text-2xl font-black text-amber-200" id="current-lot"><?= e($currentParticipant?->lot_number ?? '-') ?></span>
                    </div>
                    <h2 class="text-4xl font-black leading-tight text-white" id="current-name"><?= e($currentParticipant?->name ?? 'Menunggu Peserta') ?></h2>
                    <div class="mt-3 flex items-center justify-center gap-2 text-xl font-bold text-emerald-200" id="current-district">
                        <span class="text-2xl">&#128205;</span>
                        <!-- <span id="current-district-text"><?= e($currentParticipant?->district?->name ?? '-') ?></span> -->
                    </div>
                    <?php if (!empty($currentParticipant?->institution)): ?>
                    <div class="mt-2 flex items-center justify-center gap-2 text-base text-slate-400" id="current-institution">
                        <span>&#127979;</span>
                        <span id="current-institution-text"><?= e($currentParticipant->institution) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KANAN: Baru Dinilai -->
            <div class="flex flex-1 flex-col rounded-3xl border-2 border-amber-400/50 bg-gradient-to-br from-amber-950/60 via-slate-950 to-orange-900/50 p-5 glow-gold" id="scored-card">
                <div class="mb-3 text-center">
                    <div class="inline-flex items-center gap-3 rounded-2xl border-2 border-amber-400/40 bg-amber-400/10 px-6 py-2">
                        <span class="text-3xl">&#10004;</span>
                        <span class="text-lg font-bold uppercase tracking-wider text-amber-200">Nilai Baru Masuk</span>
                        <span class="rounded-full bg-amber-400 px-3 py-1 text-base font-bold text-amber-950">&#9733; SCORED</span>
                    </div>
                </div>

                <?php if ($latestScoredEntry && $latestScoredEntry->participant): ?>
                <!-- Photo & Info Row -->
                <div class="flex flex-1 gap-4" id="scored-content">
                    <!-- Photo -->
                    <div class="flex items-center" id="scored-photo-container">
                        <?php if ($scoredPhoto): ?>
                            <img src="<?= e($scoredPhoto) ?>" alt="<?= e($latestScoredEntry->participant->name) ?>" class="h-full max-h-[280px] w-auto rounded-3xl border-4 border-amber-400/50 object-cover shadow-2xl" id="scored-photo">
                        <?php else: ?>
                            <div class="flex h-[280px] w-[200px] items-center justify-center rounded-3xl border-4 border-amber-400/50 bg-slate-900" id="scored-photo-placeholder">
                                <span class="text-8xl font-black text-amber-400/40" id="scored-initial"><?= e(substr($latestScoredEntry->participant->name ?? '?', 0, 1)) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="flex flex-1 flex-col justify-center">
                        <div class="mb-3 inline-flex items-center gap-3 rounded-xl border-2 border-amber-400/30 bg-amber-400/10 px-5 py-2">
                            <span class="text-2xl">&#128278;</span>
                            <span class="text-sm font-bold uppercase tracking-wider text-amber-300">Nomor Lot</span>
                            <span class="text-2xl font-black text-amber-200" id="scored-lot"><?= e($latestScoredEntry->participant->lot_number ?? '-') ?></span>
                        </div>

                        <h2 class="text-4xl font-black leading-tight text-white" id="scored-name"><?= e($latestScoredEntry->participant->name) ?></h2>

                        <div class="mt-2 flex items-center gap-2 text-xl font-bold text-amber-200" id="scored-district">
                            <span class="text-2xl">&#128205;</span>
                            <!-- <span id="scored-district-text"><?= e($latestScoredEntry->participant->district?->name ?? '-') ?></span> -->
                        </div>

                        <?php if (!empty($latestScoredEntry->participant->institution)): ?>
                        <div class="mt-1 flex items-center gap-2 text-base text-slate-400" id="scored-institution">
                            <span>&#127979;</span>
                            <span id="scored-institution-text"><?= e($latestScoredEntry->participant->institution) ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="mt-2 flex items-center gap-3 text-sm text-slate-500" id="scored-meta">
                            <span>&#127917;</span>
                            <span class="font-semibold text-slate-400" id="scored-round"><?= e($latestScoredEntry->judging_round ?? '-') ?></span>
                            <span class="mx-1">|</span>
                            <span id="scored-time"><?= e($latestScoredEntry->submitted_at ? $latestScoredEntry->submitted_at->format('H:i:s') : '-') ?></span>
                        </div>

                        <!-- Rincian Nilai -->
                        <div class="mt-3 flex flex-wrap items-center gap-2" id="scored-breakdown">
                            <?php
                            // Check if new format (scores JSON) or old format (score_breakdown)
                            $scoresJson = $latestScoredEntry->scores ?? null;
                            $isNewFormat = $scoresJson && is_array($scoresJson);
                            ?>
                            <?php if ($isMfq && isset($breakdown['summary'])): ?>
                                <span class="rounded-lg border border-orange-400/30 bg-orange-400/10 px-3 py-1 text-sm font-semibold text-orange-200">Pkt: <?= e(number_format($breakdown['summary']['column_totals']['package_score'] ?? 0, 2)) ?></span>
                                <span class="rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-sm font-semibold text-emerald-200">Lng: <?= e(number_format(collect($breakdown['summary']['column_totals']['throw_scores'] ?? [])->sum(), 2)) ?></span>
                                <span class="rounded-lg border border-rose-400/30 bg-rose-400/10 px-3 py-1 text-sm font-semibold text-rose-200">Rbt: <?= e(number_format($breakdown['summary']['column_totals']['rebuttal_score'] ?? 0, 2)) ?></span>
                            <?php elseif ($isNewFormat): ?>
                                <?php
                                // New format: display first judge's breakdown
                                $firstJudge = array_key_first($scoresJson);
                                $firstJudgeData = $scoresJson[$firstJudge] ?? [];
                                $judgeBreakdown = $firstJudgeData['breakdown'] ?? [];
                                foreach (array_slice($judgeBreakdown, 0, 4, true) as $key => $value):
                                ?>
                                    <span class="rounded-lg border border-slate-600 bg-slate-800/50 px-3 py-1 text-sm text-slate-300"><?php echo e(ucfirst(substr((string) $key, 0, 6).': '.number_format((float) $value, 2))); ?></span>
                                <?php endforeach; ?>
                            <?php elseif (is_array($breakdown) && !isset($breakdown['type']) && !empty($breakdown)): ?>
                                <?php foreach (array_slice($breakdown, 0, 4, true) as $key => $value): ?>
                                    <span class="rounded-lg border border-slate-600 bg-slate-800/50 px-3 py-1 text-sm text-slate-300"><?php echo e(ucfirst(substr((string) $key, 0, 6).': '.number_format((float) $value, 2))); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Total Score - FOKUS UTAMA -->
                        <div class="mt-4 rounded-2xl border-2 border-amber-400/50 bg-gradient-to-br from-amber-500/30 to-orange-500/30 px-8 py-5 shadow-xl" id="scored-total-container">
                            <div class="flex items-center gap-3 text-sm font-bold uppercase tracking-widest text-amber-300/70">
                                <span class="text-2xl">&#127941;</span>
                                <span>Total Nilai</span>
                            </div>
                            <?php
                            // Use average_score for new format, score for old format
                            $totalDisplay = $latestScoredEntry->average_score !== null
                                ? number_format((float) $latestScoredEntry->average_score, 2)
                                : number_format((float) $latestScoredEntry->score, 2);
                            ?>
                            <div class="mt-1 text-7xl font-black leading-none text-amber-100" id="scored-total"><?= e($totalDisplay) ?></div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="flex flex-1 flex-col items-center justify-center text-slate-500" id="scored-empty">
                    <span class="text-8xl">&#128221;</span>
                    <p class="mt-4 text-xl font-semibold">Belum Ada Nilai</p>
                    <p class="mt-1 text-base">Nilai akan muncul di sini</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer - Queue & Stats -->
        <div class="flex shrink-0 gap-4">
            <!-- Queue -->
            <div class="flex-1 overflow-hidden rounded-2xl border border-cyan-400/15 bg-slate-900/90 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">&#128202;</span>
                        <span class="text-lg font-bold text-cyan-200">Daftar Peserta</span>
                    </div>
                    <span class="rounded-full bg-cyan-400/10 border border-cyan-400/25 px-3 py-1 text-sm font-bold text-cyan-200"><?= e($queueParticipants->count()) ?> Peserta</span>
                </div>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <?php foreach ($queueParticipants as $i => $p): ?>
                        <div class="shrink-0 flex items-center gap-3 rounded-xl border border-slate-700/40 bg-slate-900/40 px-4 py-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-cyan-400/20 bg-cyan-400/10 text-base font-bold text-cyan-300"><?= e($i + 1) ?></span>
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold text-white"><?= e($p->name) ?></p>
                                <p class="truncate text-sm text-slate-500">Lot <?= e($p->lot_number ?? '-') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($queueParticipants->isEmpty()): ?>
                        <p class="py-4 text-center text-base text-slate-600">Belum ada antrian</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="w-64 shrink-0 rounded-2xl border border-slate-700/40 bg-slate-800/60 p-4">
                <p class="text-sm font-semibold uppercase tracking-wider text-slate-500">Statistik</p>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-700/50 bg-slate-900/60 p-3 text-center">
                        <p class="text-3xl font-black text-white"><?= e($stats['verified_participants'] ?? 0) ?></p>
                        <p class="mt-1 text-sm text-slate-500">Total Peserta</p>
                    </div>
                    <div class="rounded-xl border border-slate-700/50 bg-slate-900/60 p-3 text-center">
                        <p class="text-3xl font-black text-white"><?= e($stats['score_entries'] ?? 0) ?></p>
                        <p class="mt-1 text-sm text-slate-500">Sudah Dinilai</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize as number or null for consistent comparison
        let currentId = <?= e($currentParticipant?->id ?? 'null') ?> !== null ? Number(<?= e($currentParticipant?->id ?? 'null') ?>) : null;
        let scoredId = <?= e($latestScoredEntry?->participant_id ?? 'null') ?>;
        const catId = <?= e($selectedCategory?->id ?? 'null') ?>;
        let pollTimer = null;

        function updateScored(data) {
            // Update scored card
            const card = document.getElementById('scored-card');
            if (!card || !data) return;

            // Clear empty state if visible
            const emptyState = document.getElementById('scored-empty');
            if (emptyState) emptyState.remove();

            // Update photo
            const photoContainer = document.getElementById('scored-photo-container');
            if (photoContainer) {
                const photo = data.photo_url;
                const name = data.participant || '-';
                if (photo) {
                    photoContainer.innerHTML = '<img src="'+photo+'" alt="'+name+'" class="h-full max-h-[280px] w-auto rounded-3xl border-4 border-amber-400/50 object-cover shadow-2xl" id="scored-photo">';
                } else {
                    photoContainer.innerHTML = '<div class="flex h-[280px] w-[200px] items-center justify-center rounded-3xl border-4 border-amber-400/50 bg-slate-900" id="scored-photo-placeholder"><span class="text-8xl font-black text-amber-400/40">'+(name.charAt(0) || '?')+'</span></div>';
                }
            }

            // Update info
            document.getElementById('scored-lot').textContent = data.lot_number || '-';
            document.getElementById('scored-name').textContent = data.participant || '-';
            document.getElementById('scored-district-text').textContent = data.district_name || '-';
            document.getElementById('scored-round').textContent = data.judging_round || '-';

            // Update total score - use average_score for new format, score for legacy
            const totalScore = data.average_score ?? data.score ?? 0;
            const formattedTotal = typeof totalScore === 'number' ? totalScore.toFixed(2) : String(totalScore);
            document.getElementById('scored-total').textContent = formattedTotal;

            const timeEl = document.getElementById('scored-time');
            if (timeEl && data.submitted_at) {
                const d = new Date(data.submitted_at);
                timeEl.textContent = d.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
            }

            const instEl = document.getElementById('scored-institution-text');
            if (instEl) instEl.textContent = data.institution || '-';

            // Update breakdown
            const breakdownEl = document.getElementById('scored-breakdown');
            if (breakdownEl) {
                let html = '';

                // Check for MFQ format
                if (data.scores && data.scores.type === 'MFQ' && data.scores.summary) {
                    const s = data.scores.summary.column_totals || {};
                    html += '<span class="rounded-lg border border-orange-400/30 bg-orange-400/10 px-3 py-1 text-sm font-semibold text-orange-200">Pkt: '+(typeof s.package_score === 'number' ? s.package_score.toFixed(2) : (s.package_score || 0))+'</span>';
                    html += '<span class="rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-sm font-semibold text-emerald-200">Lng: '+(s.throw_scores ? s.throw_scores.reduce((a,b) => a+b, 0).toFixed(2) : '0.00')+'</span>';
                    html += '<span class="rounded-lg border border-rose-400/30 bg-rose-400/10 px-3 py-1 text-sm font-semibold text-rose-200">Rbt: '+(typeof s.rebuttal_score === 'number' ? s.rebuttal_score.toFixed(2) : (s.rebuttal_score || 0))+'</span>';
                }
                // Check for new aggregated format (scores JSON with judge data)
                else if (data.scores && typeof data.scores === 'object' && !data.scores.type) {
                    // Get first judge's breakdown
                    const firstJudge = Object.keys(data.scores)[0];
                    if (firstJudge && data.scores[firstJudge]) {
                        const judgeData = data.scores[firstJudge];
                        const breakdown = judgeData.breakdown || {};
                        for (const [key, val] of Object.entries(breakdown)) {
                            if (key !== 'type') {
                                const numVal = typeof val === 'number' ? val.toFixed(2) : val;
                                html += '<span class="rounded-lg border border-slate-600 bg-slate-800/50 px-3 py-1 text-sm text-slate-300">'+key.substring(0,6)+': '+numVal+'</span>';
                            }
                        }
                    }
                }
                // Legacy score_breakdown format
                else if (data.score_breakdown && typeof data.score_breakdown === 'object') {
                    for (const [key, val] of Object.entries(data.score_breakdown)) {
                        if (key !== 'type') {
                            const numVal = typeof val === 'number' ? val.toFixed(2) : val;
                            html += '<span class="rounded-lg border border-slate-600 bg-slate-800/50 px-3 py-1 text-sm text-slate-300">'+key.substring(0,6)+': '+numVal+'</span>';
                        }
                    }
                }

                breakdownEl.innerHTML = html;
            }

            scoredId = data.participant_id;
            card.classList.add('glow-gold');
        }

        function clearScored() {
            const card = document.getElementById('scored-card');
            if (!card) return;
            card.innerHTML = `
                <div class="mb-3 text-center">
                    <div class="inline-flex items-center gap-3 rounded-2xl border-2 border-amber-400/40 bg-amber-400/10 px-6 py-2">
                        <span class="text-3xl">&#10004;</span>
                        <span class="text-lg font-bold uppercase tracking-wider text-amber-200">Nilai Baru Masuk</span>
                        <span class="rounded-full bg-amber-400 px-3 py-1 text-base font-bold text-amber-950">&#9733; SCORED</span>
                    </div>
                </div>
                <div class="flex flex-1 flex-col items-center justify-center text-slate-500">
                    <span class="text-8xl">&#128221;</span>
                    <p class="mt-4 text-xl font-semibold">Belum Ada Nilai</p>
                    <p class="mt-1 text-base">Nilai akan muncul di sini</p>
                </div>
            `;
            card.classList.add('glow-gold');
        }

        function updateCurrentParticipant(data) {
            const card = document.getElementById('current-card');
            if (!card) return;

            // Update badge to "Sedang Tampil"
            const badge = document.getElementById('current-badge');
            if (badge) {
                badge.innerHTML = `
                    <div class="inline-flex items-center gap-3 rounded-2xl border-2 border-emerald-400/40 bg-emerald-400/10 px-6 py-2" id="current-status">
                        <span class="text-3xl" id="current-icon">&#127908;</span>
                        <span class="text-lg font-bold uppercase tracking-wider text-emerald-200" id="current-label">Sedang Tampil</span>
                        <span class="relative flex h-4 w-4">
                            <span class="live-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                            <span class="relative inline-flex h-4 w-4 rounded-full bg-emerald-500"></span>
                        </span>
                    </div>
                `;
            }

            // Update photo
            const photoContainer = document.getElementById('current-photo-container');
            if (photoContainer) {
                if (data.photo_url) {
                    photoContainer.innerHTML = '<img src="'+data.photo_url+'" alt="'+data.participant_name+'" class="h-full max-h-[280px] w-auto rounded-3xl border-4 border-emerald-400/50 object-cover shadow-2xl" id="current-photo">';
                } else {
                    const initial = data.participant_name ? data.participant_name.charAt(0) : '?';
                    photoContainer.innerHTML = '<div class="flex h-[280px] w-[200px] items-center justify-center rounded-3xl border-4 border-emerald-400/50 bg-slate-900" id="current-photo-placeholder"><span class="text-8xl font-black text-emerald-400/40" id="current-initial">'+initial+'</span></div>';
                }
            }

            // Update info
            const lotEl = document.getElementById('current-lot');
            if (lotEl) lotEl.textContent = data.lot_number || '-';
            const nameEl = document.getElementById('current-name');
            if (nameEl) nameEl.textContent = data.participant_name || '-';
            const districtEl = document.getElementById('current-district-text');
            if (districtEl) districtEl.textContent = data.district_name || '-';

            // Update institution if element exists
            const instEl = document.getElementById('current-institution-text');
            if (instEl) instEl.textContent = data.institution || '-';
            // If institution element doesn't exist but we have institution data, we might need to add it

            // Restore card style
            card.classList.add('glow-green');
            card.classList.remove('border-slate-600/40');

            // Update currentId - ensure it's a number for consistent comparison
            currentId = data.participant_id != null ? Number(data.participant_id) : null;
        }

        function showWaiting() {
            // Update current card to waiting state
            const card = document.getElementById('current-card');
            if (!card) return;

            const badge = document.getElementById('current-badge');
            if (badge) {
                badge.innerHTML = '<div class="inline-flex items-center gap-3 rounded-2xl border-2 border-slate-600/40 bg-slate-800/50 px-6 py-2"><span class="text-3xl">&#128336;</span><span class="text-lg font-bold uppercase tracking-wider text-slate-400">Menunggu Penilaian</span></div>';
            }

            const photoContainer = document.getElementById('current-photo-container');
            if (photoContainer) {
                photoContainer.innerHTML = '<div class="flex h-[280px] w-[200px] items-center justify-center rounded-3xl border-4 border-slate-600/40 bg-slate-900"><span class="text-8xl font-black text-slate-600">?</span></div>';
            }

            const lotEl = document.getElementById('current-lot');
            if (lotEl) lotEl.textContent = '-';
            const nameEl = document.getElementById('current-name');
            if (nameEl) nameEl.textContent = 'Menunggu Peserta';
            const districtEl = document.getElementById('current-district-text');
            if (districtEl) districtEl.textContent = '-';
            const instEl = document.getElementById('current-institution-text');
            if (instEl) instEl.textContent = '-';

            card.classList.remove('glow-green');
            card.classList.add('border-slate-600/40');
            currentId = null;
        }

        async function poll() {
            if (!catId) return;
            try {
                const r = await fetch('/api/big-screen/current-participant?category_id=' + catId);
                const d = await r.json();
                if (d.participant) {
                    // Use loose equality to handle type mismatches
                    if (d.participant.id != currentId) {
                        currentId = Number(d.participant.id);
                        // Update current participant display
                        updateCurrentParticipant({
                            participant_id: d.participant.id,
                            participant_name: d.participant.name,
                            district_name: d.participant.district_name,
                            lot_number: d.participant.lot_number,
                            photo_url: d.participant.photo_url,
                            institution: d.participant.institution
                        });
                    }
                }

                // Poll for latest scored entry
                if (d.latest_scored && d.latest_scored.participant_id != scoredId) {
                    scoredId = d.latest_scored.participant_id;
                    updateScored({
                        participant: d.latest_scored.participant,
                        participant_id: d.latest_scored.participant_id,
                        lot_number: d.latest_scored.lot_number,
                        district_name: d.latest_scored.district_name,
                        institution: d.latest_scored.institution,
                        judging_round: d.latest_scored.judging_round,
                        average_score: d.latest_scored.average_score,
                        scores: d.latest_scored.scores,
                        photo_url: d.latest_scored.photo_url,
                        submitted_at: d.latest_scored.submitted_at
                    });
                }
            } catch(e) {
                console.error('Poll error:', e);
            }
        }

        // Listen for participant selected event (form scoring selects a participant)
        window.addEventListener('mtq-participant-selected', (e) => {
            const d = e.detail;
            console.log('Participant selected event received:', d);

            if (d.category_id === catId) {
                updateCurrentParticipant({
                    participant_id: d.participant_id,
                    participant_name: d.participant_name,
                    district_name: d.district_name,
                    lot_number: d.lot_number,
                    photo_url: d.photo_url
                });
            }
        });

        // Listen for score updated event
        window.addEventListener('mtq-score-updated', (e) => {
            const d = e.detail;
            console.log('Score event received:', d);

            // Check if scored participant is the current on stage participant
            // Use loose equality (==) to handle type mismatches between string/number
            if (d.participant_id == currentId) {
                // Move current participant to scored section and show waiting
                showWaiting();
            }

            // Update scored section with new score data
            // Use average_score for new format, falls back to score for legacy data
            updateScored({
                participant: d.participant,
                participant_id: d.participant_id,
                lot_number: d.lot_number,
                district_name: d.district_name,
                institution: d.institution,
                judging_round: d.judging_round,
                average_score: d.average_score,
                score: d.score,
                scores: d.scores,
                score_breakdown: d.score_breakdown,
                photo_url: d.photo_url,
                submitted_at: d.submitted_at
            });
        });

        // Poll every 10s as backup
        pollTimer = setInterval(poll, 10000);
        poll();
    </script>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
