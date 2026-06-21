<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($filterTitle ?? 'Rekap Ranking') ?> - e-MTQ</title>
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

        /* Header - Matching finalists-print style */
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
            font-size: 20px;
            font-weight: 900;
            color: var(--gold-dark);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }

        .header-event {
            font-size: 12px;
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
            font-size: 14px;
            font-weight: 700;
        }

        .category-subtitle {
            font-size: 10px;
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
            font-size: 10px;
            font-weight: 600;
        }

        .msq-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
        }

        .msq-badge-small {
            background: #fef3c7;
            color: #92400e;
        }

        /* Table */
        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .ranking-table thead {
            background: var(--bg-light);
        }

        .ranking-table th {
            padding: 8px 10px;
            text-align: left;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-medium);
            border-bottom: 2px solid var(--border-dark);
        }

        .ranking-table td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .ranking-table tbody tr:hover {
            background: var(--bg-light);
        }

        .ranking-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Column Widths - Adjusted */
        .rank-cell {
            width: 70px;
            text-align: center;
        }

        .name-cell {
            min-width: 0;
        }

        .name-text {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .district-text {
            font-size: 9px;
            color: var(--text-light);
            font-weight: 400;
            margin-top: 1px;
        }

        .lot-cell {
            width: 120px;
            text-align: center;
        }

        .lot-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 6px;
            min-width: 80px;
        }

        .no-lot {
            color: var(--text-light);
            font-size: 9px;
        }

        /* Score Cell */
        .score-cell {
            width: 90px;
            text-align: right;
        }

        .score-value {
            font-size: 14px;
            font-weight: 800;
            color: var(--green);
        }

        .score-round {
            font-size: 8px;
            color: var(--text-light);
            text-transform: uppercase;
            margin-top: 1px;
        }

        .score-round.final {
            color: var(--gold-dark);
            font-weight: 600;
        }

        /* Rank Badge */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 55px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 10px;
        }

        .rank-1, .juara-1 {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #78350f;
        }

        .rank-2, .juara-2 {
            background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%);
            color: #334155;
        }

        .rank-3, .juara-3 {
            background: linear-gradient(135deg, #fdba74 0%, #ea580c 100%);
            color: white;
        }

        .harapan-1, .harapan-2, .harapan-3 {
            background: linear-gradient(135deg, #dbeafe 0%, #3b82f6 100%);
            color: white;
        }

        .rank-other, .rank-11, .rank-12, .rank-13 {
            background: var(--bg-light);
            color: var(--text-medium);
            border: 1px solid var(--border-dark);
        }

        /* Fallback indicator */
        .fallback-badge {
            display: inline-block;
            background: rgba(245, 158, 11, 0.2);
            color: #b45309;
            font-size: 7px;
            font-weight: 600;
            padding: 1px 4px;
            border-radius: 3px;
            margin-left: 4px;
            vertical-align: middle;
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

        /* Gender Subheader - Bigger */
        .gender-subheader {
            background: var(--bg-light);
            border-bottom: 1px solid var(--border);
        }

        .putra-subheader td {
            background: rgba(8, 145, 178, 0.15);
            color: var(--cyan);
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
            padding: 10px 12px;
            letter-spacing: 0.5px;
        }

        .putri-subheader td {
            background: rgba(190, 24, 93, 0.15);
            color: var(--pink);
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
            padding: 10px 12px;
            letter-spacing: 0.5px;
        }

        /* MFQ Section */
        .mfq-section {
            margin-bottom: 25px;
            border: 2px solid #7c3aed;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.15);
        }

        .mfq-header {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            color: white;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mfq-name {
            font-size: 14px;
            font-weight: 700;
        }

        .mfq-subtitle {
            font-size: 10px;
            opacity: 0.9;
            margin-top: 2px;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .header-content { flex-direction: column; }
            .category-header { flex-direction: column; gap: 10px; }
            .ranking-table { font-size: 9px; }
            .ranking-table th, .ranking-table td { padding: 6px 4px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header - Matching finalists-print style -->
        <header class="header">
            <div class="header-content">
                <div class="header-logos">
                    <img src="/images/logo-kabupaten.webp" alt="Logo Kabupaten" class="header-logo">
                    <img src="/images/favicon.webp" alt="Logo MTQ" class="header-logo">
                </div>

                <div class="header-text">
                    <h1 class="header-title"><?= e($filterTitle ?? 'Rekap Ranking Leaderboard') ?></h1>
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
                    <span><?= $totalParticipants ?> Peserta</span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📋</span>
                    <span><?= count($categoryRankings) ?> Golongan</span>
                </div>
                <?php if (($filterType ?? 'semua') === 'juara'): ?>
                <div class="meta-item">
                    <span class="meta-icon">🎖️</span>
                    <span>Juara 1-3: Final (Harapan 1-3: Penyisihan)</span>
                </div>
                <?php endif; ?>
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
        <?php foreach ($categoryRankings as $categoryId => $data): ?>
            <?php
            $category = $data['category'];
            $isMfq = !empty($data['is_mfq']);
            $isMsq = !empty($data['is_msq']);
            $totalParticipants = $data['total_participants'] ?? 0;

            // Format header: "Fahmil Qur'an - Golongan Putra" for all categories
            $headerName = e($category->name) . ' - ' . e($category->branch);
            ?>
            <?php if ($isMfq): ?>
                <?php
                $mfqRankings = $data['mfq_rankings'] ?? [];
                $districtCount = count($mfqRankings);
                ?>
                <div class="category-section mfq-category">
                    <div class="category-header" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);">
                        <div>
                            <div class="category-name" style="color: white;">
                                <?= $headerName ?>
                            </div>
                        </div>
                        <div class="category-meta">
                            <span class="meta-badge" style="background: rgba(255,255,255,0.2); color: white;"><?= $districtCount ?> Kecamatan</span>
                            <span class="meta-badge" style="background: rgba(255,255,255,0.2); color: white;"><?= $totalParticipants ?> Peserta</span>
                        </div>
                    </div>

                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th class="rank-cell">Rank</th>
                                <th>Nama Representative</th>
                                <th class="lot-cell">No. Lot</th>
                                <th class="score-cell">Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($mfqRankings)): ?>
                                <?php foreach ($mfqRankings as $index => $rank): ?>
                                    <tr>
                                        <td class="rank-cell">
                                            <span class="rank-badge rank-<?= ($index < 3) ? ($index + 1) : 'other' ?>">
                                                <?= ($index < 3) ? (($index === 0) ? '🥇' : (($index === 1) ? '🥈' : '🥉')) : ($index + 1) ?>
                                            </span>
                                        </td>
                                        <td class="name-cell">
                                            <div class="name-text"><?= e($rank['representative_name']) ?></div>
                                            <div class="district-text">📍 <?= e($rank['district_name']) ?> • <?= $rank['participant_count'] ?> peserta</div>
                                        </td>
                                        <td class="lot-cell">
                                            <?php if (!empty($rank['lot_numbers'])): ?>
                                                <?php foreach (array_slice($rank['lot_numbers'], 0, 5) as $lot): ?>
                                                    <span class="lot-badge"><?= e($lot) ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($rank['lot_numbers']) > 5): ?>
                                                    <span class="lot-badge" style="background: #64748b;">+<?= count($rank['lot_numbers']) - 5 ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="no-lot">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="score-cell">
                                            <div class="score-value"><?= number_format((float) $rank['total_score'], 2) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="empty-row">
                                    <td colspan="4">Belum ada data ranking untuk MFQ ini</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <?php
                $putraLeaders = $data['putra'] ?? [];
                $putriLeaders = $data['putri'] ?? [];
                $hasAny = !empty($putraLeaders) || !empty($putriLeaders);
                $isJuara = ($filterType ?? 'semua') === 'juara';
                $districtCount = $data['district_count'] ?? 0;
                ?>
                <div class="category-section">
                    <div class="category-header">
                        <div>
                            <div class="category-name">
                                <?= $headerName ?>
                                <?php if ($isMsq): ?>
                                    <span class="msq-badge">📌 Nilai per Kecamatan</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="category-meta">
                            <?php if ($isMsq): ?>
                                <span class="meta-badge msq-badge-small"><?= $districtCount ?> Kecamatan</span>
                            <?php else: ?>
                                <span class="meta-badge"><?= $totalParticipants ?> Peserta</span>
                            <?php endif; ?>
                            <span class="meta-badge">Putra: <?= count($putraLeaders) ?></span>
                            <span class="meta-badge">Putri: <?= count($putriLeaders) ?></span>
                        </div>
                    </div>

                    <table class="ranking-table">
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
                            <?php if (!empty($putraLeaders)): ?>
                                <tr class="gender-subheader putra-subheader">
                                    <td colspan="4">♂ Kategori Putra</td>
                                </tr>
                                <?php foreach ($putraLeaders as $index => $leader): ?>
                                    <?php
                                    $rankLabel = $leader['rank_label'] ?? ($index + 1);
                                    $isHarapan = str_starts_with((string) $rankLabel, 'Harapan');
                                    // Extract the numeric rank
                                    $numericRank = (int) preg_replace('/\D/', '', (string) $rankLabel);
                                    // Special colors for rank 1, 2, 3 only (single digit)
                                    if ($isHarapan) {
                                        $rankClass = 'harapan-' . $numericRank;
                                    } elseif ($numericRank >= 1 && $numericRank <= 3) {
                                        $rankClass = 'rank-' . $numericRank;
                                    } else {
                                        $rankClass = 'rank-other';
                                    }
                                    $isFallback = !empty($leader['is_fallback']);
                                    $scoreValue = (float) ($leader['average_score'] ?? 0);
                                    $scoreDisplay = $scoreValue > 0 ? number_format($scoreValue, 2) : '-';
                                    $showLot = !empty($leader['lot_number']) || $scoreValue > 0;
                                    ?>
                                    <tr>
                                        <td class="rank-cell">
                                            <span class="rank-badge <?= e($rankClass) ?>">
                                                <?= e($rankLabel) ?>
                                                <?php if ($isFallback): ?>
                                                    <span class="fallback-badge">dari P</span>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="name-cell">
                                            <div class="name-text"><?= e($leader['name'] ?? '-') ?></div>
                                            <div class="district-text"><?= e($leader['district'] ?? '-') ?></div>
                                        </td>
                                        <td class="lot-cell">
                                            <?php if ($showLot && !empty($leader['lot_number'])): ?>
                                                <span class="lot-badge"><?= e($leader['lot_number']) ?></span>
                                            <?php else: ?>
                                                <span class="no-lot">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="score-cell">
                                            <div class="score-value"><?= e($scoreDisplay) ?></div>
                                            <?php if (!empty($leader['current_round'])): ?>
                                                <div class="score-round <?= ($leader['current_round'] ?? '') === 'Final' ? 'final' : '' ?>">
                                                    <?= e($leader['current_round']) ?>
                                                </div>
                                            <?php elseif (!empty($leader['round'])): ?>
                                                <div class="score-round <?= $leader['round'] === 'Final' ? 'final' : '' ?>">
                                                    <?= e($leader['round']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($putriLeaders)): ?>
                                <tr class="gender-subheader putri-subheader">
                                    <td colspan="4">♀ Kategori Putri</td>
                                </tr>
                                <?php foreach ($putriLeaders as $index => $leader): ?>
                                    <?php
                                    $rankLabel = $leader['rank_label'] ?? ($index + 1);
                                    $isHarapan = str_starts_with((string) $rankLabel, 'Harapan');
                                    // Extract the numeric rank
                                    $numericRank = (int) preg_replace('/\D/', '', (string) $rankLabel);
                                    // Special colors for rank 1, 2, 3 only (single digit)
                                    if ($isHarapan) {
                                        $rankClass = 'harapan-' . $numericRank;
                                    } elseif ($numericRank >= 1 && $numericRank <= 3) {
                                        $rankClass = 'rank-' . $numericRank;
                                    } else {
                                        $rankClass = 'rank-other';
                                    }
                                    $isFallback = !empty($leader['is_fallback']);
                                    $scoreValue = (float) ($leader['average_score'] ?? 0);
                                    $scoreDisplay = $scoreValue > 0 ? number_format($scoreValue, 2) : '-';
                                    $showLot = !empty($leader['lot_number']) || $scoreValue > 0;
                                    ?>
                                    <tr>
                                        <td class="rank-cell">
                                            <span class="rank-badge <?= e($rankClass) ?>">
                                                <?= e($rankLabel) ?>
                                                <?php if ($isFallback): ?>
                                                    <span class="fallback-badge">dari P</span>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="name-cell">
                                            <div class="name-text"><?= e($leader['name'] ?? '-') ?></div>
                                            <div class="district-text"><?= e($leader['district'] ?? '-') ?></div>
                                        </td>
                                        <td class="lot-cell">
                                            <?php if ($showLot && !empty($leader['lot_number'])): ?>
                                                <span class="lot-badge"><?= e($leader['lot_number']) ?></span>
                                            <?php else: ?>
                                                <span class="no-lot">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="score-cell">
                                            <div class="score-value"><?= e($scoreDisplay) ?></div>
                                            <?php if (!empty($leader['current_round'])): ?>
                                                <div class="score-round <?= ($leader['current_round'] ?? '') === 'Final' ? 'final' : '' ?>">
                                                    <?= e($leader['current_round']) ?>
                                                </div>
                                            <?php elseif (!empty($leader['round'])): ?>
                                                <div class="score-round <?= $leader['round'] === 'Final' ? 'final' : '' ?>">
                                                    <?= e($leader['round']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="4">Belum ada data ranking untuk golongan ini</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Footer with favicon -->
        <footer class="footer">
            <div class="no-print" style="display: flex; justify-content: center; gap: 20px; margin-bottom: 15px; align-items: center;">
                <img src="/images/logo-kabupaten.webp" alt="Logo" style="height: 35px; opacity: 0.7;">
                <img src="/images/favicon.webp" alt="Logo" style="height: 35px; opacity: 0.7;">
                <img src="/images/logo-lptq.webp" alt="Logo" style="height: 35px; opacity: 0.7;">
                <img src="/images/emtq-resmi.webp" alt="Logo" style="height: 35px; opacity: 0.7;">
            </div>
            <p>Dokumen ini di-generate oleh <span class="footer-brand">e-MTQ System</span></p>
        </footer>
    </div>
</body>
</html>
