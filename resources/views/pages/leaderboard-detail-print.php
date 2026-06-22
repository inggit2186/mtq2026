<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($filterTitle ?? 'Rekap Detail Nilai') ?> - e-MTQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #b8860b;
            --gold-dark: #8b6914;
            --cyan: #0891b2;
            --pink: #be185d;
            --green: #059669;
            --bg-white: #ffffff;
            --bg-light: #f8fafc;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-light: #64748b;
            --border: #e2e8f0;
            --border-dark: #cbd5e1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-white);
            color: var(--text-dark);
            line-height: 1.5;
            padding: 0;
            font-size: 10px;
        }

        @media print {
            body { padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .category { page-break-after: always; }
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 15px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #ffffff 0%, #fefce8 50%, #fef9c3 100%);
            border-bottom: 4px solid var(--gold);
            padding: 15px 20px;
            margin-bottom: 15px;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .header-logos {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-logo {
            height: 40px;
            width: auto;
        }

        .header-logo-main { height: 50px; }

        .header-text { text-align: center; flex: 1; }

        .header-title {
            font-family: 'Merriweather', serif;
            font-size: 16px;
            font-weight: 900;
            color: var(--gold-dark);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-event {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-medium);
        }

        .header-year {
            font-size: 9px;
            color: var(--text-light);
        }

        .header-meta {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--border);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 9px;
            color: var(--text-medium);
        }

        .meta-icon {
            width: 16px;
            height: 16px;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 8px;
            font-weight: bold;
        }

        /* Print Button */
        .print-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 24px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            font-size: 12px;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 0 auto 15px;
            width: fit-content;
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3);
        }

        /* Category Section */
        .category {
            margin-bottom: 20px;
            border: 2px solid var(--border-dark);
            border-radius: 10px;
            overflow: hidden;
        }

        .category-head {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .category-name {
            font-size: 12px;
            font-weight: 700;
        }

        .category-meta {
            display: flex;
            gap: 10px;
        }

        .meta-badge {
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 5px;
            font-size: 9px;
            font-weight: 600;
        }

        /* Table */
        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .ranking-table thead {
            background: var(--bg-light);
        }

        .ranking-table th {
            padding: 5px 6px;
            text-align: center;
            font-weight: 700;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: var(--text-medium);
            border-bottom: 2px solid var(--border-dark);
        }

        .ranking-table td {
            padding: 4px 6px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .ranking-table tbody tr:nth-child(even) td { background: var(--bg-light); }
        .ranking-table tbody tr:last-child td { border-bottom: none; }

        .rank-cell { width: 25px; text-align: center; font-weight: 700; }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 9px;
        }

        .rank-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f; }
        .rank-2 { background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%); color: #334155; }
        .rank-3 { background: linear-gradient(135deg, #fdba74 0%, #ea580c 100%); color: white; }
        .rank-other { background: var(--bg-light); color: var(--text-medium); border: 1px solid var(--border-dark); }

        .name-cell { width: 100px; }
        .name-text { font-weight: 600; color: var(--text-dark); font-size: 9px; }
        .muted { color: var(--text-light); font-size: 7.5px; }

        .lot-cell { width: 50px; text-align: center; flex-shrink: 0; }
        .lot-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .score-cell { text-align: center; width: 45px; }
        .score-value { font-size: 10px; font-weight: 700; color: var(--green); }

        /* Gender Subheader */
        .gender-subheader td {
            font-weight: 800;
            font-size: 9px;
            text-transform: uppercase;
            padding: 5px 8px;
            letter-spacing: 0.3px;
        }

        .putra-subheader td {
            background: rgba(8, 145, 178, 0.15);
            color: var(--cyan);
        }

        .putri-subheader td {
            background: rgba(190, 24, 93, 0.15);
            color: var(--pink);
        }

        /* Judge Detail Row */
        .judge-row td {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%) !important;
            font-size: 10px;
            color: #92400e;
            border-bottom: 2px dashed #fbbf24;
            padding: 8px 6px;
        }
        .judge-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: #78350f;
            padding-left: 6px;
            font-size: 11px;
        }
        .judge-label::before {
            content: '📋';
        }
        .judge-item {
            display: inline-flex;
            gap: 4px;
            margin: 2px 4px;
            background: white;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #fcd34d;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .judge-name { font-weight: 700; color: #92400e; font-size: 10px; }
        .judge-score { font-weight: 800; color: #78350f; }

        /* Spacer Row */
        .spacer-row td {
            height: 6px;
            background: #fff !important;
            border: none;
        }

        /* Signature Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 20px;
            padding: 20px 15px;
            border-top: 2px solid var(--gold);
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-radius: 0 0 8px 8px;
        }

        .signature-left {
            width: 45%;
            padding-right: 15px;
        }

        .signature-right {
            width: 35%;
            text-align: center;
            padding-left: 15px;
            border-left: 1px dashed var(--border-dark);
        }

        .signature-title {
            font-weight: 700;
            color: var(--gold-dark);
            font-size: 11px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .signature-title::before {
            content: '📋';
            font-size: 14px;
        }

        /* Judge List Styling */
        .hakim-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 10px;
        }

        .hakim-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .hakim-number {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            flex-shrink: 0;
        }

        .hakim-name {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 12px;
        }

        .hakim-empty {
            font-style: italic;
            color: var(--text-light);
            font-size: 11px;
            padding: 15px;
            text-align: center;
            background: white;
            border-radius: 8px;
            border: 1px dashed var(--border-dark);
        }

        /* Signature Box */
        .signature-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .signature-date {
            font-size: 11px;
            color: var(--text-medium);
            margin-bottom: 10px;
        }

        .signature-position {
            font-weight: 700;
            color: var(--gold-dark);
            font-size: 11px;
            margin-bottom: 25px;
        }

        .signature-stamp-area {
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 9px;
            border: 2px dashed var(--border);
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .signature-line {
            border-top: 1px solid var(--text-dark);
            width: 100%;
            margin-top: 5px;
        }

        .signature-name {
            margin-top: 5px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 11px;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            color: var(--text-light);
            font-size: 9px;
            border-top: 2px solid var(--border);
        }

        .footer-brand { font-weight: 700; color: var(--gold-dark); }

        @media (max-width: 600px) {
            .header-content { flex-direction: column; }
            .category-header { flex-direction: column; gap: 8px; }
            .ranking-table { font-size: 8px; }
            .ranking-table th, .ranking-table td { padding: 3px 2px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-logos">
                    <img src="/images/logo-kabupaten.webp" alt="Logo Kabupaten" class="header-logo" onerror="this.style.display='none'">
                    <img src="/images/favicon.webp" alt="Logo MTQ" class="header-logo" onerror="this.style.display='none'">
                </div>

                <div class="header-text">
                    <h1 class="header-title"><?= e($filterTitle ?? 'Rekap Detail Nilai') ?></h1>
                    <div class="header-event">MTQ Nasional ke-XLIII Tingkat Kabupaten Tanah Datar</div>
                    <div class="header-year">Pariangan, <?= date('d F Y') ?></div>
                </div>

                <div class="header-logos">
                    <img src="/images/logo-lptq.webp" alt="Logo LPTQ" class="header-logo" onerror="this.style.display='none'">
                    <img src="/images/emtq-resmi.webp" alt="Logo EMTQ" class="header-logo header-logo-main" onerror="this.style.display='none'">
                </div>
            </div>

            <div class="header-meta">
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <span>Generated: <?= e($generatedAt) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">🏆</span>
                    <span><?= count($categoryBlocks) ?> Golongan</span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📋</span>
                    <span>Ranking Penyisihan</span>
                </div>
            </div>
        </header>

        <!-- Print Button -->
        <button onclick="window.print()" class="print-btn no-print">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak / Save as PDF
        </button>

        <!-- Categories -->
        <?php foreach ($categoryBlocks as $block): ?>
            <?php
            $category = $block['category'];
            $priorityLabels = $block['priority_labels'] ?? [];
            $priorityKeys = $block['priority_keys'] ?? [];
            $rankingRows = $block['ranking_rows'] ?? [];
            $hakimList = $block['hakim_list'] ?? collect();
            $participantTotal = $block['participant_total'] ?? 0;

            $putraRows = array_values(array_filter($rankingRows, fn ($row) => ($row['gender'] ?? '') === 'putra'));
            $putriRows = array_values(array_filter($rankingRows, fn ($row) => ($row['gender'] ?? '') === 'putri'));

            $totalColumns = 3 + count($priorityLabels);
            ?>
            <div class="category">
                <div class="category-head">
                    <div class="category-name">
                        <?= e($category->branch) ?> - <?= e($category->name) ?>
                    </div>
                    <div class="category-meta">
                        <span class="meta-badge"><?= e($participantTotal) ?> Peserta</span>
                        <span class="meta-badge">Putra: <?= count($putraRows) ?></span>
                        <span class="meta-badge">Putri: <?= count($putriRows) ?></span>
                    </div>
                </div>

                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th class="rank-cell">#</th>
                            <th>Nama</th>
                            <th class="lot-cell">Lot</th>
                            <?php foreach ($priorityLabels as $idx => $label): ?>
                                <th class="score-cell" title="<?= e($label) ?>"><?= e($label) ?></th>
                            <?php endforeach; ?>
                            <th class="score-cell">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($putraRows)): ?>
                            <tr class="gender-subheader putra-subheader">
                                <td colspan="<?= $totalColumns ?>">Laki-Laki</td>
                            </tr>
                            <?php foreach ($putraRows as $genderIdx => $row): ?>
                                <!-- Main Row -->
                                <tr>
                                    <td class="rank-cell">
                                        <span class="rank-badge rank-<?= ($genderIdx + 1) <= 3 ? ($genderIdx + 1) : 'other' ?>">
                                            <?= $genderIdx + 1 ?>
                                        </span>
                                    </td>
                                    <td class="name-cell">
                                        <div class="name-text"><?= e($row['name']) ?></div>
                                    </td>
                                    <td class="lot-cell">
                                        <span class="lot-badge"><?= e($row['lot_number']) ?></span>
                                    </td>
                                    <?php foreach ($priorityLabels as $label): ?>
                                        <td class="score-cell">
                                            <?= e($row['priority_label_values'][$label] ?? '0.00') ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="score-cell">
                                        <span class="score-value"><?= e($row['average_score']) ?></span>
                                    </td>
                                </tr>
                                <!-- Judge Detail Row -->
                                <?php
                                $scoreEntries = $row['score_entries'] ?? [];
                                $judgeDetails = [];
                                foreach ($scoreEntries as $entry) {
                                    foreach ($entry['judge_breakdown'] ?? [] as $jIdx => $judge) {
                                        $parts = [];
                                        foreach ($priorityKeys as $pKey) {
                                            $val = $judge['breakdown'][$pKey] ?? 0;
                                            $parts[] = $val > 0 ? number_format($val, 1) : '-';
                                        }
                                        $shortName = substr($judge['judge_name'], 0, 12);
                                        $judgeDetails[] = '<span class="judge-item"><span class="judge-name">H' . ($jIdx + 1) . ':</span>' . implode('/', $parts) . ' = <span class="judge-score">' . number_format($judge['score'], 2) . '</span></span>';
                                    }
                                }
                                ?>
                                <?php if (!empty($judgeDetails)): ?>
                                <tr class="judge-row">
                                    <td class="rank-cell"></td>
                                    <td colspan="2">
                                        <div class="judge-label">Detail Nilai Hakim ▼</div>
                                    </td>
                                    <?php foreach ($priorityLabels as $pIdx => $label): ?>
                                        <td class="score-cell">
                                            <?php
                                            // Show judge's score for this point
                                            $pointScores = [];
                                            foreach ($scoreEntries as $entry) {
                                                foreach ($entry['judge_breakdown'] ?? [] as $jIdx => $judge) {
                                                    $val = $judge['breakdown'][$priorityKeys[$pIdx]] ?? 0;
                                                    if ($val > 0) {
                                                        $pointScores[] = '<span class="judge-item"><span class="judge-name">H' . ($jIdx + 1) . ':</span><span class="judge-score">' . number_format($val, 1) . '</span></span>';
                                                    }
                                                }
                                            }
                                            echo implode('', $pointScores);
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="score-cell"></td>
                                </tr>
                                <?php endif; ?>
                                <!-- Spacer -->
                                <tr class="spacer-row">
                                    <td colspan="<?= $totalColumns ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($putriRows)): ?>
                            <tr class="gender-subheader putri-subheader">
                                <td colspan="<?= $totalColumns ?>">Perempuan</td>
                            </tr>
                            <?php foreach ($putriRows as $genderIdx => $row): ?>
                                <!-- Main Row -->
                                <tr>
                                    <td class="rank-cell">
                                        <span class="rank-badge rank-<?= ($genderIdx + 1) <= 3 ? ($genderIdx + 1) : 'other' ?>">
                                            <?= $genderIdx + 1 ?>
                                        </span>
                                    </td>
                                    <td class="name-cell">
                                        <div class="name-text"><?= e($row['name']) ?></div>
                                    </td>
                                    <td class="lot-cell">
                                        <span class="lot-badge"><?= e($row['lot_number']) ?></span>
                                    </td>
                                    <?php foreach ($priorityLabels as $label): ?>
                                        <td class="score-cell">
                                            <?= e($row['priority_label_values'][$label] ?? '0.00') ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="score-cell">
                                        <span class="score-value"><?= e($row['average_score']) ?></span>
                                    </td>
                                </tr>
                                <!-- Judge Detail Row -->
                                <?php
                                $scoreEntries = $row['score_entries'] ?? [];
                                ?>
                                <?php if (!empty($scoreEntries)): ?>
                                <tr class="judge-row">
                                    <td class="rank-cell"></td>
                                    <td colspan="2">
                                        <div class="judge-label">Detail Nilai Hakim ▼</div>
                                    </td>
                                    <?php foreach ($priorityLabels as $pIdx => $label): ?>
                                        <td class="score-cell">
                                            <?php
                                            $pointScores = [];
                                            foreach ($scoreEntries as $entry) {
                                                foreach ($entry['judge_breakdown'] ?? [] as $jIdx => $judge) {
                                                    $val = $judge['breakdown'][$priorityKeys[$pIdx]] ?? 0;
                                                    if ($val > 0) {
                                                        echo '<span class="judge-item"><span class="judge-name">H' . ($jIdx + 1) . ':</span><span class="judge-score">' . number_format($val, 1) . '</span></span> ';
                                                    }
                                                }
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="score-cell"></td>
                                </tr>
                                <?php endif; ?>
                                <!-- Spacer -->
                                <tr class="spacer-row">
                                    <td colspan="<?= $totalColumns ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (empty($putraRows) && empty($putriRows)): ?>
                            <tr>
                                <td colspan="<?= $totalColumns ?>" style="text-align: center; color: var(--text-light); padding: 20px;">
                                    Belum ada data ranking untuk golongan ini
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="signature-left">
                        <div class="signature-title">Dewan Hakim <?= e($category->branch) ?></div>
                        <?php if (!$hakimList->isEmpty()): ?>
                            <div class="hakim-list">
                                <?php foreach ($hakimList as $index => $hakim): ?>
                                    <div class="hakim-item">
                                        <span class="hakim-number"><?= $index + 1 ?></span>
                                        <span class="hakim-name"><?= e($hakim->nama) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="hakim-empty">(Belum ada data hakim)</div>
                        <?php endif; ?>
                    </div>
                    <div class="signature-right">
                        <div class="signature-box">
                            <div class="signature-date"><?= e(($documentConfig['signature_city'] ?? 'Pariangan')) ?>, <?= e(date('d F Y')) ?></div>
                            <div class="signature-position"><?= e(($documentConfig['officials']['chief_judge']['title'] ?? 'Ketua Dewan Hakim')) ?></div>
                            <div class="signature-stamp-area">📌 Area Tanda Tangan / Stempel</div>
                            <div class="signature-line"></div>
                            <div class="signature-name">(<?= e(($documentConfig['officials']['chief_judge']['name'] ?? '..........................')) ?>)</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Footer -->
        <footer class="footer">
            <div class="no-print" style="display: flex; justify-content: center; gap: 15px; margin-bottom: 10px; align-items: center;">
                <img src="/images/logo-kabupaten.webp" alt="Logo" style="height: 30px; opacity: 0.7;" onerror="this.style.display='none'">
                <img src="/images/favicon.webp" alt="Logo" style="height: 30px; opacity: 0.7;" onerror="this.style.display='none'">
                <img src="/images/logo-lptq.webp" alt="Logo" style="height: 30px; opacity: 0.7;" onerror="this.style.display='none'">
                <img src="/images/emtq-resmi.webp" alt="Logo" style="height: 30px; opacity: 0.7;" onerror="this.style.display='none'">
            </div>
            <p>Dokumen ini di-generate oleh <span class="footer-brand">e-MTQ System</span></p>
        </footer>
    </div>
</body>
</html>
