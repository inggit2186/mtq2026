<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$documentConfig = $documentConfig ?? [];
$stats = $stats ?? [];
$leaders = collect($leaders ?? [])->values();
$announcements = ($announcements ?? collect())->values();
$schedules = ($schedules ?? collect())->values();
$todaySchedules = ($todaySchedules ?? collect())->values();
$recentScores = ($recentScores ?? collect())->values();
$queueParticipants = ($queueParticipants ?? collect())->values();
$selectedCategory = $selectedCategory ?? null;
$currentParticipant = $currentParticipant ?? null;
$latestScoredEntry = $latestScoredEntry ?? null;
$generatedAt = $generatedAt ?? now();
$rankingPriorityContext = $rankingPriorityContext ?? ['text' => '', 'specific' => false];
$eventTitle = $documentConfig['event_title'] ?? config('app.name', 'e-MTQ');
$organizationName = $documentConfig['organization_name'] ?? 'e-MTQ';
$location = $documentConfig['event_location'] ?? config('juknis.host', 'Tanah Datar');
$headlineAnnouncement = $announcements->first();
$categoryLabel = $selectedCategory ? trim(($selectedCategory->branch ? $selectedCategory->branch.' | ' : '').$selectedCategory->name) : 'Semua Golongan';
$spotlightLeader = $leaders->first();
$otherLeaders = $leaders->slice(1, 5)->values();
$tickerItems = collect();

if ($currentParticipant) {
    $tickerItems->push('Sedang tampil: '.$currentParticipant->name.' - '.($currentParticipant->district?->name ?? $currentParticipant->institution));
}

if ($latestScoredEntry?->participant) {
    $tickerItems->push('Baru dinilai: '.$latestScoredEntry->participant->name.' - skor '.number_format((float) $latestScoredEntry->score, 2));
}

if ($headlineAnnouncement) {
    $tickerItems->push('Info panitia: '.$headlineAnnouncement->title);
}

if ($tickerItems->isEmpty()) {
    $tickerItems->push('Big screen operator siap menampilkan fokus golongan yang sedang dinilai.');
}

$resolveParticipantPhoto = static function ($participant): ?string {
    $path = (string) ($participant?->document_photo ?? '');

    if ($path === '') {
        return null;
    }

    return asset('storage/'.ltrim(str_replace('\\', '/', $path), '/'));
};

$currentParticipantPhoto = $resolveParticipantPhoto($currentParticipant);
$latestParticipantPhoto = $resolveParticipantPhoto($latestScoredEntry?->participant);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app.name', 'e-MTQ')) ?> - Big Screen Operator</title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-hidden bg-slate-950 text-slate-100 antialiased">
    <main class="relative h-screen overflow-hidden px-4 py-4 xl:px-6 xl:py-5">
        <div class="hero-orb hero-orb-cyan left-[-8rem] top-0 h-64 w-64"></div>
        <div class="hero-orb hero-orb-blue right-[-10rem] top-14 h-80 w-80"></div>
        <div class="hero-orb hero-orb-cyan bottom-10 left-[34%] h-48 w-48 opacity-40"></div>

        <div class="grid h-full gap-5 xl:grid-cols-[1.12fr_0.88fr]">
            <section class="grid min-h-0 gap-5">
                <header class="glass-card rounded-[2.15rem] border-white/8 bg-gradient-to-r from-slate-950/88 via-slate-900/80 to-cyan-950/35 px-5 py-4">
                    <div class="flex items-start justify-between gap-6">
                        <div class="min-w-0">
                            <div class="badge-live w-fit">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-cyan-200/30 bg-slate-50 p-0.5">
                                    <img src="<?= e(asset('images/emtq-resmi.webp')) ?>" alt="Logo resmi e-MTQ" class="h-full w-full object-contain">
                                </span>
                                Big Screen Operator
                            </div>
                            <h1 class="mt-3 max-w-5xl text-3xl font-black tracking-tight text-white 2xl:text-4xl"><?= e($eventTitle) ?></h1>
                            <p class="mt-2 text-base text-slate-300"><?= e($organizationName) ?> | <?= e($location) ?></p>
                            <p class="mt-3 text-sm font-semibold text-cyan-100/90"><?= e($categoryLabel) ?></p>
                        </div>

                        <div class="shrink-0 rounded-[1.8rem] border border-cyan-400/12 bg-slate-950/60 px-4 py-3 text-right shadow-[0_20px_60px_-35px_rgba(14,165,233,0.5)]" x-data="{ now: new Date(), tick() { this.now = new Date(); } }" x-init="setInterval(() => tick(), 1000)">
                            <p class="text-sm uppercase tracking-[0.3em] text-cyan-200">Live Clock</p>
                            <p class="mt-2 text-3xl font-black text-white" x-text="now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })"><?= e($generatedAt->format('H:i:s')) ?></p>
                            <p class="mt-2 text-sm text-slate-400" x-text="now.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })"><?= e($generatedAt->translatedFormat('l, d F Y')) ?></p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-4">
                        <div class="metric-card bg-gradient-to-br from-slate-900/92 to-slate-950/80 p-4"><p class="text-xs text-slate-400">Peserta golongan</p><p class="mt-2 text-2xl font-extrabold text-white"><?= e($stats['verified_participants'] ?? 0) ?></p></div>
                        <div class="metric-card bg-gradient-to-br from-slate-900/92 to-slate-950/80 p-4"><p class="text-xs text-slate-400">Skor tercatat</p><p class="mt-2 text-2xl font-extrabold text-white"><?= e($stats['score_entries'] ?? 0) ?></p></div>
                        <div class="metric-card bg-gradient-to-br from-slate-900/92 to-slate-950/80 p-4"><p class="text-xs text-slate-400">Papan peringkat</p><p class="mt-2 text-2xl font-extrabold text-white"><?= e($stats['leaders'] ?? 0) ?></p></div>
                        <div class="metric-card bg-gradient-to-br from-cyan-950/40 to-blue-950/35 p-4"><p class="text-xs text-cyan-100/75">Pengumuman</p><p class="mt-2 text-2xl font-extrabold text-white"><?= e($stats['announcements'] ?? 0) ?></p></div>
                    </div>
                </header>

                <section class="grid min-h-0 gap-5 xl:grid-cols-[1.04fr_0.96fr]">
                    <div class="grid min-h-0 gap-5">
                        <div class="glass-card rounded-[2.15rem] border-emerald-300/10 bg-gradient-to-br from-slate-950/88 via-slate-900/84 to-emerald-950/20 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="section-kicker">Fokus Utama</p>
                                    <h2 class="mt-2 text-3xl font-black text-white">Peserta sedang tampil</h2>
                                </div>
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                    On Stage
                                </div>
                            </div>

                            <?php if ($currentParticipant): ?>
                                <div class="mt-4 rounded-[1.9rem] border border-emerald-300/14 bg-gradient-to-r from-emerald-400/10 via-slate-900/84 to-cyan-400/10 p-5 shadow-[0_20px_70px_-35px_rgba(74,222,128,0.3)]">
                                    <div class="flex items-start gap-6">
                                        <div class="shrink-0">
                                            <?php if ($currentParticipantPhoto): ?>
                                                <img src="<?= e($currentParticipantPhoto) ?>" alt="<?= e('Foto '.$currentParticipant->name) ?>" class="h-48 w-36 rounded-[1.6rem] border border-emerald-300/18 object-cover shadow-[0_18px_50px_-28px_rgba(74,222,128,0.35)]">
                                            <?php else: ?>
                                                <div class="flex h-48 w-36 items-center justify-center rounded-[1.6rem] border border-emerald-300/18 bg-slate-950/75 text-center text-slate-400">
                                                    <div>
                                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-400/16 bg-cyan-400/10 text-cyan-200"><?= mtq_icon('users', 'h-6 w-6') ?></div>
                                                        <p class="mt-3 text-xs uppercase tracking-[0.22em]">Foto belum ada</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-emerald-200">Sedang tampil sekarang</p>
                                            <h3 class="mt-3 text-4xl font-black leading-tight text-white"><?= e($currentParticipant->name) ?></h3>
                                            <p class="mt-3 text-lg text-slate-200"><?= e($currentParticipant->district?->name ?? '-') ?></p>
                                            <p class="mt-1 text-base text-slate-400"><?= e($currentParticipant->institution) ?></p>
                                            <div class="mt-4 flex flex-wrap gap-3 text-sm">
                                                <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 font-semibold text-cyan-100"><?= e($currentParticipant->registration_number) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mt-5 data-card flex min-h-[15rem] items-center justify-center text-center text-slate-300">
                                    Operator belum memilih peserta aktif. Buka modul penilaian dan pilih peserta untuk menandai siapa yang sedang tampil.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="glass-card rounded-[2.15rem] border-amber-300/10 bg-gradient-to-br from-slate-950/88 via-slate-900/84 to-amber-950/18 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="section-kicker">Fokus Utama</p>
                                    <h2 class="mt-2 text-3xl font-black text-white">Peserta baru dinilai</h2>
                                </div>
                                <div class="badge-live"><?= mtq_icon('check-circle', 'h-4 w-4') ?> Live Score</div>
                            </div>

                            <?php if ($latestScoredEntry?->participant): ?>
                                <div class="mt-4 rounded-[1.9rem] border border-amber-300/14 bg-gradient-to-r from-amber-400/10 via-slate-900/84 to-orange-400/10 p-5 shadow-[0_20px_70px_-35px_rgba(251,191,36,0.3)]">
                                    <div class="flex items-start gap-6">
                                        <div class="shrink-0">
                                            <?php if ($latestParticipantPhoto): ?>
                                                <img src="<?= e($latestParticipantPhoto) ?>" alt="<?= e('Foto '.$latestScoredEntry->participant->name) ?>" class="h-44 w-32 rounded-[1.5rem] border border-amber-300/18 object-cover shadow-[0_18px_50px_-28px_rgba(251,191,36,0.35)]">
                                            <?php else: ?>
                                                <div class="flex h-44 w-32 items-center justify-center rounded-[1.5rem] border border-amber-300/18 bg-slate-950/75 text-center text-slate-400">
                                                    <div>
                                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-amber-300/16 bg-amber-400/10 text-amber-200"><?= mtq_icon('users', 'h-5 w-5') ?></div>
                                                        <p class="mt-3 text-[11px] uppercase tracking-[0.22em]">Tanpa foto</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex min-w-0 flex-1 items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-amber-200">Skor terbaru masuk</p>
                                                <h3 class="mt-3 text-3xl font-black leading-tight text-white"><?= e($latestScoredEntry->participant->name) ?></h3>
                                                <p class="mt-3 text-lg text-slate-200"><?= e($latestScoredEntry->participant->district?->name ?? '-') ?></p>
                                                <p class="mt-1 text-base text-slate-400"><?= e($latestScoredEntry->participant->institution) ?></p>
                                                <p class="mt-3 text-sm text-slate-400"><?= e($latestScoredEntry->judging_round ?: 'Babak belum ditentukan') ?> | <?= e(optional($latestScoredEntry->submitted_at)->format('d M Y H:i')) ?></p>
                                            </div>
                                            <div class="shrink-0 text-right">
                                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Total Nilai</p>
                                                <p class="mt-2 text-4xl font-black text-amber-200"><?= e(number_format((float) $latestScoredEntry->score, 2)) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mt-5 data-card flex min-h-[15rem] items-center justify-center text-center text-slate-300">
                                    Belum ada peserta yang selesai dinilai pada golongan ini.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="glass-card min-h-0 rounded-[2.15rem] p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="section-kicker">Pendukung</p>
                                <h2 class="mt-2 text-2xl font-bold text-white">Peringkat sementara</h2>
                                <?php if (($rankingPriorityContext['text'] ?? '') !== ''): ?>
                                    <p class="mt-2 text-xs leading-6 text-slate-400"><?= e($rankingPriorityContext['text']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="status-pill"><span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span> Leaderboard</div>
                        </div>

                        <?php if ($spotlightLeader): ?>
                            <div class="mt-4 rounded-[1.9rem] border border-cyan-400/14 bg-gradient-to-r from-cyan-400/10 via-slate-900/80 to-blue-500/10 p-4 shadow-[0_20px_70px_-35px_rgba(34,211,238,0.3)]">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-200">Peringkat 1</p>
                                        <h3 class="mt-2 text-2xl font-black text-white"><?= e($spotlightLeader['name']) ?></h3>
                                        <p class="mt-1 text-sm text-slate-300"><?= e($spotlightLeader['institution']) ?></p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Rata-rata</p>
                                        <p class="mt-1 text-3xl font-black text-emerald-300"><?= e($spotlightLeader['average_score']) ?></p>
                                        <p class="mt-1 text-xs text-slate-400">Nilai terakhir <?= e($spotlightLeader['latest_score']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-5 grid gap-3">
                            <?php if ($leaders->isEmpty()): ?>
                                <div class="data-card flex min-h-[20rem] items-center justify-center text-center text-slate-300">
                                    Leaderboard golongan akan tampil setelah nilai pertama masuk.
                                </div>
                            <?php else: ?>
                                <?php foreach ($otherLeaders as $index => $leader): ?>
                                    <div class="data-card flex items-center justify-between gap-4 p-4">
                                        <div class="flex min-w-0 items-center gap-4">
                                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[1.2rem] border border-cyan-400/18 bg-cyan-400/10 text-xl font-black text-cyan-200">
                                                <?= e($index + 2) ?>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-lg font-bold text-white"><?= e($leader['name']) ?></p>
                                                <p class="mt-1 truncate text-xs text-slate-400"><?= e($leader['institution']) ?></p>
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Avg</p>
                                            <p class="mt-1 text-xl font-black text-emerald-300"><?= e($leader['average_score']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </section>

            <aside class="grid min-h-0 gap-5">
                <div class="glass-card rounded-[2.15rem] p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="section-kicker">Pendukung</p>
                            <h2 class="mt-2 text-2xl font-bold text-white">Antrian peserta</h2>
                        </div>
                        <div class="badge-live"><?= mtq_icon('users', 'h-4 w-4') ?> Next Up</div>
                    </div>

                    <div class="mt-5">
                        <?php if ($queueParticipants->isEmpty()): ?>
                            <div class="data-card text-center text-slate-300">Belum ada antrian tambahan pada tampilan ini.</div>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($queueParticipants as $participant): ?>
                                    <div class="flex items-center justify-between gap-3 rounded-[1rem] border border-slate-800 bg-slate-950/65 px-3 py-2.5">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-white"><?= e($participant->name) ?></p>
                                            <p class="mt-0.5 truncate text-xs text-slate-400"><?= e($participant->district?->name ?? '-') ?></p>
                                        </div>
                                        <span class="text-xs font-semibold text-cyan-200"><?= e($participant->registration_number) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="glass-card min-h-0 rounded-[2.15rem] p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="section-kicker">Pendukung</p>
                            <h2 class="mt-2 text-2xl font-bold text-white">Ticker arena</h2>
                        </div>
                        <div class="text-right">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Update terakhir</p>
                            <p class="mt-1 text-lg font-bold text-cyan-200"><?= e($generatedAt->format('d M Y H:i:s')) ?></p>
                        </div>
                    </div>
                    <div class="mt-5 overflow-hidden rounded-[1.75rem] border border-cyan-400/12 bg-slate-950/85 p-4">
                        <div class="flex w-max min-w-full animate-[marquee_28s_linear_infinite] gap-12">
                            <?php foreach ($tickerItems as $item): ?>
                                <div class="flex items-center gap-3 whitespace-nowrap text-lg font-semibold text-slate-200">
                                    <span class="inline-flex h-3 w-3 rounded-full bg-cyan-300 shadow-[0_0_18px_rgba(103,232,249,0.6)]"></span>
                                    <span><?= e($item) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($tickerItems as $item): ?>
                                <div class="flex items-center gap-3 whitespace-nowrap text-lg font-semibold text-slate-200">
                                    <span class="inline-flex h-3 w-3 rounded-full bg-cyan-300 shadow-[0_0_18px_rgba(103,232,249,0.6)]"></span>
                                    <span><?= e($item) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($headlineAnnouncement): ?>
                        <div class="mt-5 rounded-[1.5rem] border border-amber-400/14 bg-gradient-to-r from-amber-400/10 via-slate-900/84 to-orange-400/10 p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-200">Info Panitia</p>
                            <h3 class="mt-3 text-xl font-bold text-white"><?= e($headlineAnnouncement->title) ?></h3>
                            <p class="mt-3 text-sm leading-7 text-slate-300"><?= e($headlineAnnouncement->body) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </main>

    <script>
        setTimeout(() => window.location.reload(), 30000);
    </script>
    <?php require __DIR__.'/../partials/ongoing-schedules.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
