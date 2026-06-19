<?php
require_once __DIR__.'/../../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$navigation = $navigation ?? [];
$category = $category ?? null;
$schedule = $schedule ?? null;
$dayData = $dayData ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Hasil Penampilan') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('sparkles') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Hasil Penampilan</h1>
                        </div>
                    </div>
                    <a href="<?= e(route('appearance.schedules')) ?>" class="secondary-button rounded-xl px-3 py-2">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        Kembali
                    </a>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <h2 class="text-xl font-bold text-white"><?= e($category->name) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($category->branch) ?></p>
                    <?php
                        $firstDay = $schedule->getDaySchedule(0);
                        if ($firstDay && !empty($firstDay['date'])):
                            $date = \Carbon\Carbon::parse($firstDay['date'])->translatedFormat('d F Y');
                            $time = $firstDay['time'] ?? '';
                    ?>
                    <p class="mt-3 inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-2 text-sm font-semibold text-amber-200">
                        <?= mtq_icon('calendar', 'h-4 w-4') ?>
                        <?= e($date) ?><?= $time ? ' - ' . e($time) . ' WIB' : '' ?>
                    </p>
                    <?php endif; ?>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../../partials/console-navigation.php'; ?>
                </nav>
            </aside>

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <a href="<?= e(route('appearance.schedules')) ?>" class="secondary-button rounded-xl px-3 py-2 lg:hidden">
                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        </a>
                        <div>
                            <p class="section-kicker">Hasil Penampilan</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Daftar Peserta Tampil Per Hari</h2>
                            <p class="mt-2 text-sm text-slate-300">Daftar nomor lot yang dijadwalkan tampil per hari.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="<?= e(route('appearance.results.pdf', $category->id)) ?>"
                            class="primary-button rounded-xl px-4 py-2.5 inline-flex items-center gap-2">
                            <?= mtq_icon('download', 'h-4 w-4') ?>
                            Rekap PDF
                        </a>
                    </div>
                </header>

                <?php
                    $totalScheduledLots = 0;
                    $totalConfiguredLots = $schedule->getTotalLots();
                    foreach ($dayData as $day) {
                        $totalScheduledLots += ($day['range']['count'] ?? 0);
                    }
                ?>

                <section class="grid gap-4 sm:grid-cols-3">
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Jumlah Hari</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($schedule->number_of_days) ?></p>
                    </div>
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('hash') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Total Lot</p>
                        <p class="mt-2 text-3xl font-extrabold text-cyan-200"><?= e($totalConfiguredLots) ?></p>
                    </div>
                    <div class="metric-card">
                        <div class="icon-chip"><?= mtq_icon('list') ?></div>
                        <p class="mt-4 text-sm text-slate-400">Lot Terjadwal</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($totalScheduledLots) ?></p>
                    </div>
                </section>

                <?php foreach ($dayData as $dayIndex => $data): ?>
                    <?php
                        $dayNumber = $dayIndex + 1;
                        $dayParticipants = $data['participants'];
                        $total = $data['total'];
                        $displayed = $data['displayed'];
                        $remaining = $data['remaining'];
                        $range = $data['range'] ?? [];
                        $daySchedule = $data['schedule'] ?? [];

                        $sessionName = $daySchedule['name'] ?? null;
                        $sessionDate = $daySchedule['date'] ?? null;
                        $sessionTime = $daySchedule['time'] ?? null;
                        $sessionEndTime = $daySchedule['end_time'] ?? null;

                        $formattedDate = null;
                        if ($sessionDate) {
                            $formattedDate = \Carbon\Carbon::parse($sessionDate)->translatedFormat('d F Y');
                        }
                    ?>
                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-cyan-400/18 bg-cyan-400/10 text-2xl font-black text-cyan-100">
                                    <?= e($dayNumber) ?>
                                </div>
                                <div>
                                    <p class="section-kicker">Hari <?= e($dayNumber) ?></p>
                                    <?php if ($sessionName): ?>
                                        <h3 class="mt-1 text-xl font-bold text-white"><?= e($sessionName) ?></h3>
                                    <?php else: ?>
                                        <h3 class="mt-1 text-xl font-bold text-white">Penampilan Hari <?= e($dayNumber) ?></h3>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <?php
                                    $dayLotNumbers = $range['lot_numbers'] ?? [];
                                    $lotCount = count($dayLotNumbers);
                                ?>
                                <?php if ($lotCount > 0): ?>
                                <span class="rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-2 text-sm font-semibold text-amber-200">
                                    <?= e(str_pad((string) reset($dayLotNumbers), 2, '0', STR_PAD_LEFT)) ?> - <?= e(str_pad((string) end($dayLotNumbers), 2, '0', STR_PAD_LEFT)) ?>
                                </span>
                                <?php endif; ?>
                                <span class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-100">
                                    <?= e($lotCount) ?> Lot
                                </span>
                                <?php if ($lotCount > 0): ?>
                                <a href="<?= e(route('appearance.results.day-recap.pdf', [$category->id, $dayIndex])) ?>"
                                    target="_blank"
                                    class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-2 text-sm font-semibold text-emerald-200 hover:bg-emerald-400/20 transition-colors inline-flex items-center gap-2">
                                    <?= mtq_icon('download', 'h-4 w-4') ?>
                                    Rekap PDF
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($sessionName || $sessionDate || $sessionTime || $sessionEndTime): ?>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <?php if ($sessionName): ?>
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-700 bg-slate-950/50 px-3 py-1.5 text-sm text-slate-300">
                                    <?= mtq_icon('tag', 'h-4 w-4') ?>
                                    <?= e($sessionName) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($formattedDate): ?>
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-700 bg-slate-950/50 px-3 py-1.5 text-sm text-slate-300">
                                    <?= mtq_icon('calendar', 'h-4 w-4') ?>
                                    <?= e($formattedDate) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($sessionTime): ?>
                                <span class="inline-flex items-center gap-2 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1.5 text-sm text-cyan-200">
                                    <?= mtq_icon('clock', 'h-4 w-4') ?>
                                    <?= e($sessionTime) ?> WIB<?= $sessionEndTime ? ' - ' . e($sessionEndTime) . ' WIB' : '' ?>
                                </span>
                            <?php elseif ($sessionEndTime): ?>
                                <span class="inline-flex items-center gap-2 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1.5 text-sm text-cyan-200">
                                    <?= mtq_icon('clock', 'h-4 w-4') ?>
                                    <?= e($sessionEndTime) ?> WIB
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php
                            $hasSchedule = ($range['count'] ?? 0) > 0;
                            $scheduledCount = $range['count'] ?? 0;
                            $dayLotNumbers = $range['lot_numbers'] ?? [];

                            // Create participant lookup by lot number suffix
                            $participantByLot = [];
                            foreach ($dayParticipants as $p) {
                                $parts = explode('-', $p->lot_number);
                                $lotSuffix = (int) end($parts);
                                $participantByLot[$lotSuffix] = $p;
                            }
                        ?>

                        <?php if ($hasSchedule): ?>
                            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-800 bg-slate-950/50">
                                <table class="min-w-full">
                                    <thead class="table-head">
                                        <tr>
                                            <th class="px-5 py-3 text-center w-16">No Lot</th>
                                            <th class="px-5 py-3 text-left">Nama</th>
                                            <th class="px-5 py-3 text-left">Kecamatan</th>
                                            <th class="px-5 py-3 text-center">Nomor Lot</th>
                                            <th class="px-5 py-3 text-center">JK</th>
                                            <th class="px-5 py-3 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dayLotNumbers as $lot): ?>
                                            <?php
                                                $participant = $participantByLot[$lot] ?? null;
                                                $lotStr = str_pad((string) $lot, 2, '0', STR_PAD_LEFT);
                                            ?>
                                            <tr class="border-t border-slate-800/60">
                                                <td class="px-5 py-3 text-center">
                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-400/18 bg-amber-400/10 text-sm font-bold text-amber-200">
                                                        <?= e($lotStr) ?>
                                                    </span>
                                                </td>
                                                <?php if ($participant): ?>
                                                    <td class="px-5 py-3">
                                                        <p class="text-sm font-semibold text-white"><?= e($participant->name) ?></p>
                                                        <p class="mt-1 text-xs text-slate-400"><?= e($participant->registration_number) ?></p>
                                                    </td>
                                                    <td class="px-5 py-3">
                                                        <p class="text-sm text-slate-300"><?= e($participant->district?->name ?? '-') ?></p>
                                                    </td>
                                                    <td class="px-5 py-3 text-center">
                                                        <span class="inline-flex rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-sm font-bold text-amber-200">
                                                            <?= e($participant->lot_number) ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3 text-center">
                                                        <span class="inline-flex rounded-full border border-slate-700 bg-slate-950/70 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] <?= $participant->gender === 'putra' ? 'text-blue-300' : 'text-pink-300' ?>">
                                                            <?= e($participant->gender === 'putra' ? 'L' : 'P') ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3 text-center">
                                                        <span class="inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-200">
                                                            Terjadwal
                                                        </span>
                                                    </td>
                                                <?php else: ?>
                                                    <td class="px-5 py-3">
                                                        <span class="text-sm text-slate-600">-</span>
                                                    </td>
                                                    <td class="px-5 py-3">
                                                        <span class="text-sm text-slate-600">-</span>
                                                    </td>
                                                    <td class="px-5 py-3 text-center">
                                                        <span class="inline-flex rounded-full border border-slate-700/50 bg-slate-800/30 px-3 py-1 text-sm font-bold text-slate-600">
                                                            <?= e($lotStr) ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3 text-center">
                                                        <span class="text-sm text-slate-600">-</span>
                                                    </td>
                                                    <td class="px-5 py-3 text-center">
                                                        <span class="inline-flex rounded-full border border-slate-700/50 bg-slate-800/30 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                                            Kosong
                                                        </span>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="mt-6 rounded-[1.5rem] border border-dashed border-slate-700 bg-slate-950/50 p-6 text-center">
                                <p class="text-slate-400">Belum ada jadwal untuk hari ini.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>

                <?php $unfilledLots = $totalConfiguredLots - $totalScheduledLots; ?>
                <?php if ($unfilledLots > 0): ?>
                    <div class="rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
                        <?= mtq_icon('alert', 'h-4 w-4 inline mr-2') ?>
                        Ada <?= e($unfilledLots) ?> nomor lot yang belum terjadwal. Pastikan total lot terjadwal sesuai dengan jumlah hari.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>