<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Peserta Final - e-MTQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #b8860b;
            --gold-dark: #8b6914;
            --gold-light: #daa520;
            --cyan: #0891b2;
            --cyan-light: #0e7490;
            --pink: #be185d;
            --pink-light: #db2777;
            --green: #059669;
            --bg-white: #ffffff;
            --bg-light: #f8fafc;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-light: #64748b;
            --border: #e2e8f0;
            --border-dark: #cbd5e1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-white);
            color: var(--text-dark);
            line-height: 1.5;
            padding: 0;
            font-size: 11px;
        }

        @media print {
            body { padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #ffffff 0%, #fefce8 50%, #fef9c3 100%);
            border-bottom: 4px solid var(--gold);
            padding: 20px;
            margin-bottom: 20px;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .header-logos {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo {
            height: 50px;
            width: auto;
            object-fit: contain;
        }

        .header-logo-main {
            height: 65px;
        }

        .header-text {
            text-align: center;
            flex: 1;
        }

        .header-title {
            font-family: 'Merriweather', serif;
            font-size: 22px;
            font-weight: 900;
            color: var(--gold-dark);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }

        .header-event {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-medium);
            margin-bottom: 2px;
        }

        .header-year {
            font-size: 11px;
            color: var(--text-light);
        }

        .header-meta {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--text-medium);
        }

        .meta-icon {
            width: 18px;
            height: 18px;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            font-weight: bold;
        }

        /* Print Button */
        .print-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 30px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            font-size: 14px;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin: 0 auto 20px;
            width: fit-content;
            box-shadow: 0 4px 15px rgba(184, 134, 11, 0.3);
        }

        /* Category Section */
        .category-section {
            margin-bottom: 25px;
            border: 2px solid var(--border-dark);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .category-header {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .category-name {
            font-size: 15px;
            font-weight: 700;
        }

        .category-subtitle {
            font-size: 11px;
            opacity: 0.9;
            margin-top: 2px;
        }

        .category-meta {
            display: flex;
            gap: 15px;
        }

        .meta-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Table */
        .finalist-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .finalist-table thead {
            background: var(--bg-light);
        }

        .finalist-table th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-medium);
            border-bottom: 2px solid var(--border-dark);
        }

        .finalist-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .finalist-table tbody tr:hover {
            background: var(--bg-light);
        }

        .finalist-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Rank Cell */
        .rank-cell {
            width: 50px;
            text-align: center;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 14px;
        }

        .rank-1 {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #78350f;
        }

        .rank-2 {
            background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%);
            color: #334155;
        }

        .rank-3 {
            background: linear-gradient(135deg, #fdba74 0%, #ea580c 100%);
            color: white;
        }

        .rank-other {
            background: var(--bg-light);
            color: var(--text-medium);
            border: 1px solid var(--border-dark);
        }

        /* Name Cell */
        .name-cell {
            font-weight: 600;
            color: var(--text-dark);
        }

        .district-info {
            font-size: 10px;
            color: var(--text-light);
            font-weight: 400;
            margin-top: 2px;
        }

        /* Lot Cell */
        .lot-cell {
            width: 80px;
        }

        .lot-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 6px;
        }

        .no-lot {
            color: var(--text-light);
            font-size: 10px;
        }

        /* Score Cell */
        .score-cell {
            width: 100px;
            text-align: right;
        }

        .score-value {
            font-size: 16px;
            font-weight: 800;
            color: var(--green);
        }

        .score-round {
            font-size: 9px;
            color: var(--text-light);
            text-transform: uppercase;
            margin-top: 2px;
        }

        .score-round.final {
            color: var(--gold-dark);
            font-weight: 600;
        }

        /* Empty State */
        .empty-row td {
            padding: 20px;
            text-align: center;
            color: var(--text-light);
            font-style: italic;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: var(--text-light);
            font-size: 11px;
            border-top: 2px solid var(--border);
        }

        .footer-brand {
            font-weight: 700;
            color: var(--gold-dark);
        }

        /* Gender Subheader */
        .gender-subheader {
            background: var(--bg-light);
            border-bottom: 1px solid var(--border);
        }

        .putra-subheader td {
            background: rgba(8, 145, 178, 0.1);
            color: var(--cyan);
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            padding: 8px 12px;
        }

        .putri-subheader td {
            background: rgba(190, 24, 93, 0.1);
            color: var(--pink);
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            padding: 8px 12px;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .header-content { flex-direction: column; }
            .category-header { flex-direction: column; gap: 10px; }
            .finalist-table { font-size: 10px; }
            .finalist-table th, .finalist-table td { padding: 8px 6px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-logos">
                    <img src="/images/logo-kabupaten.webp" alt="Logo Kabupaten" class="header-logo">
                    <img src="/images/favicon.webp" alt="Logo MTQ" class="header-logo">
                </div>

                <div class="header-text">
                    <h1 class="header-title">Daftar Peserta Final</h1>
                    <div class="header-event">MTQ Nasional ke-XLIII Tingkat Kabupaten Tanah Datar</div>
                    <div class="header-year">Pariangan, <?= date('d F Y') ?></div>
                </div>

                <div class="header-logos">
                    <img src="/images/logo-lptq.webp" alt="Logo LPTQ" class="header-logo">
                    <img src="/images/emtq-resmi.webp" alt="Logo EMTQ" class="header-logo header-logo-main">
                </div>
            </div>

            <div class="header-meta">
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <span>Generated: <?= $generatedAt ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">🏆</span>
                    <span><?= $groupedFinalists->flatten(1)->flatten(1)->count() ?> Finalis</span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📋</span>
                    <span><?= $categories->count() ?> Golongan</span>
                </div>
            </div>
        </header>

        <!-- Print Button -->
        <button onclick="window.print()" class="print-btn no-print">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak / Save as PDF
        </button>

        <!-- Categories -->
        <?php foreach ($categories as $category): ?>
            <?php
            $catFinalists = $groupedFinalists->get($category->id, collect());
            $putraFinalists = $catFinalists->get('putra', collect());
            $putriFinalists = $catFinalists->get('putri', collect());
            $hasAny = $putraFinalists->isNotEmpty() || $putriFinalists->isNotEmpty();
            ?>
            <div class="category-section">
                <div class="category-header">
                    <div>
                        <div class="category-name"><?= e($category->name) ?></div>
                        <div class="category-subtitle"><?= e($category->branch) ?> • Golongan #<?= $category->id ?></div>
                    </div>
                    <div class="category-meta">
                        <span class="meta-badge">Putra: <?= $putraFinalists->count() ?></span>
                        <span class="meta-badge">Putri: <?= $putriFinalists->count() ?></span>
                    </div>
                </div>

                <table class="finalist-table">
                    <thead>
                        <tr>
                            <th class="rank-cell">Rank</th>
                            <th>Nama Peserta</th>
                            <th class="lot-cell">No. Lot</th>
                            <th class="score-cell">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($hasAny): ?>
                            <?php if ($putraFinalists->isNotEmpty()): ?>
                                <tr class="gender-subheader putra-subheader">
                                    <td colspan="4">♂ Kategori Putra</td>
                                </tr>
                                <?php foreach ($putraFinalists as $finalist): ?>
                                    <tr>
                                        <td class="rank-cell">
                                            <span class="rank-badge rank-<?= $finalist->finalist_rank ?>">
                                                <?php if ($finalist->finalist_rank <= 3): ?>
                                                    <?= $finalist->finalist_rank == 1 ? '🥇' : ($finalist->finalist_rank == 2 ? '🥈' : '🥉') ?>
                                                <?php else: ?>
                                                    <?= $finalist->finalist_rank ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="name-cell">
                                            <?= e($finalist->participant?->name ?? '-') ?>
                                            <div class="district-info"><?= e($finalist->participant?->district?->name ?? '-') ?></div>
                                        </td>
                                        <td class="lot-cell">
                                            <?php if ($finalist->participant?->lot_number): ?>
                                                <span class="lot-badge"><?= $finalist->participant->lot_number ?></span>
                                            <?php else: ?>
                                                <span class="no-lot">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="score-cell">
                                            <div class="score-value"><?= number_format($finalist->score, 2) ?></div>
                                            <div class="score-round <?= $finalist->round === 'Final' ? 'final' : '' ?>">
                                                <?= $finalist->round ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($putriFinalists->isNotEmpty()): ?>
                                <tr class="gender-subheader putri-subheader">
                                    <td colspan="4">♀ Kategori Putri</td>
                                </tr>
                                <?php foreach ($putriFinalists as $finalist): ?>
                                    <tr>
                                        <td class="rank-cell">
                                            <span class="rank-badge rank-<?= $finalist->finalist_rank ?>">
                                                <?php if ($finalist->finalist_rank <= 3): ?>
                                                    <?= $finalist->finalist_rank == 1 ? '🥇' : ($finalist->finalist_rank == 2 ? '🥈' : '🥉') ?>
                                                <?php else: ?>
                                                    <?= $finalist->finalist_rank ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="name-cell">
                                            <?= e($finalist->participant?->name ?? '-') ?>
                                            <div class="district-info"><?= e($finalist->participant?->district?->name ?? '-') ?></div>
                                        </td>
                                        <td class="lot-cell">
                                            <?php if ($finalist->participant?->lot_number): ?>
                                                <span class="lot-badge"><?= $finalist->participant->lot_number ?></span>
                                            <?php else: ?>
                                                <span class="no-lot">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="score-cell">
                                            <div class="score-value"><?= number_format($finalist->score, 2) ?></div>
                                            <div class="score-round <?= $finalist->round === 'Final' ? 'final' : '' ?>">
                                                <?= $finalist->round ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="4">Belum ada finalis untuk golongan ini</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <!-- Footer -->
        <footer class="footer">
            <div class="no-print" style="display: flex; justify-content: center; gap: 20px; margin-bottom: 15px;">
                <img src="/images/logo-kabupaten.webp" alt="Logo" style="height: 35px; opacity: 0.7;">
                <img src="/images/logo-lptq.webp" alt="Logo" style="height: 35px; opacity: 0.7;">
                <img src="/images/emtq-resmi.webp" alt="Logo" style="height: 35px; opacity: 0.7;">
            </div>
            <p>Dokumen ini di-generate oleh <span class="footer-brand">e-MTQ System</span></p>
        </footer>
    </div>
</body>
</html>
