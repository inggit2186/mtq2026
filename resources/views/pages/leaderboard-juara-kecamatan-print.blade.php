<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lampiran SK Dewan Hakim - Rekap Juara Umum Kecamatan MTQ Tanah Datar ke-43</title>
    <link rel="icon" type="image/webp" href="<?= asset('images/favicon.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700;900&family=Inter:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { margin: 2cm; size: A4; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1a1a1a;
            background: white;
        }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Section */
        .header-section {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1a1a1a;
        }

        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .header-logo {
            height: 50px;
            width: auto;
        }

        .header-logo.emtq { height: 60px; }

        .sk-header {
            text-align: center;
            margin-top: 10px;
        }

        .sk-number {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .sk-title {
            font-family: 'Merriweather', serif;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .sk-about {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 8px 0;
        }

        .sk-location {
            font-size: 11px;
            margin-top: 5px;
        }

        /* Page 1 Simple Table */
        .simple-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 12px;
        }

        .simple-table thead th {
            padding: 8px 6px;
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #1a1a1a;
            background: #1a365d;
            color: white;
        }

        .simple-table td {
            padding: 6px 6px;
            border: 1px solid #1a1a1a;
            vertical-align: middle;
        }

        .simple-table tbody tr.rank-1 { background: rgba(251, 191, 36, 0.15); }
        .simple-table tbody tr.rank-2 { background: rgba(148, 163, 184, 0.15); }
        .simple-table tbody tr.rank-3 { background: rgba(253, 186, 116, 0.15); }
        .simple-table tbody tr:nth-child(even) { background: #fafafa; }
        .simple-table tbody tr.rank-1:nth-child(even) { background: rgba(251, 191, 36, 0.2); }
        .simple-table tbody tr.rank-2:nth-child(even) { background: rgba(148, 163, 184, 0.2); }
        .simple-table tbody tr.rank-3:nth-child(even) { background: rgba(253, 186, 116, 0.2); }

        .rank-cell {
            text-align: center;
            font-weight: 700;
        }

        .rank-badge {
            display: inline-block;
            min-width: 35px;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            border: 2px solid;
        }

        .rank-badge.juara1 { background: transparent; border-color: #f59e0b; color: #92400e; }
        .rank-badge.juara2 { background: transparent; border-color: #94a3b8; color: #475569; }
        .rank-badge.juara3 { background: transparent; border-color: #ea580c; color: #c2410c; }
        .rank-badge.harapan1, .rank-badge.harapan2, .rank-badge.harapan3 {
            background: transparent; border-color: #3b82f6; color: #1d4ed8;
        }
        .rank-badge.rank-other { background: transparent; border-color: #64748b; color: #475569; }

        .district-cell { font-weight: 600; text-align: center; }
        .points-cell { text-align: center; font-family: 'Merriweather', serif; font-weight: 700; font-size: 14px; color: #1a365d; }
        .prev-rank-cell { text-align: center; }

        .editable-rank {
            min-width: 40px;
            padding: 3px 6px;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            text-align: center;
            font-weight: 700;
            outline: none;
            cursor: text;
        }

        .editable-rank:focus {
            border-color: #3b82f6;
            background: #f0f9ff;
        }

        .editable-rank::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        /* Page 2 - Detail Table */
        .detail-header {
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
            color: white;
            padding: 15px 20px;
            margin: 20px 0 0 0;
            text-align: center;
        }

        .detail-header h2 {
            font-family: 'Merriweather', serif;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0;
        }

        .detail-header p {
            font-size: 10px;
            opacity: 0.9;
            margin-top: 5px;
        }

        /* Point Rules Section */
        .point-rules-section {
            margin-bottom: 20px;
            padding: 15px 20px;
            background: #fff;
            border: 2px solid #1a1a1a;
        }

        .point-rules-title {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 10px;
            text-align: center;
        }

        .point-rules-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .point-rule-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 11px;
        }

        .point-rule-label { font-weight: 600; }
        .point-rule-value { font-family: 'Merriweather', serif; font-weight: 700; color: #1a365d; }

        /* Ranking Table */
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 12px;
        }

        .result-table thead th {
            padding: 10px 8px;
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #1a1a1a;
            background: #f1f5f9;
        }

        .result-table th.rank-col { width: 50px; }
        .result-table th.district-col { min-width: 150px; text-align: left; }
        .result-table th.points-col { width: 80px; }
        .result-table th.detail-col { min-width: 300px; text-align: left; }

        .result-table td {
            padding: 10px 8px;
            border: 1px solid #1a1a1a;
            vertical-align: top;
        }

        .detail-cell { font-size: 11px; line-height: 1.6; }

        .detail-item {
            margin-bottom: 3px;
            display: flex;
            gap: 5px;
            align-items: flex-start;
        }

        .detail-rank { font-weight: 700; min-width: 70px; }
        .detail-rank.juara1 { color: #92400e; }
        .detail-rank.juara2 { color: #475569; }
        .detail-rank.juara3 { color: #c2410c; }
        .detail-rank.harapan1 { color: #1d4ed8; }
        .detail-rank.harapan2 { color: #1d4ed8; }
        .detail-rank.harapan3 { color: #1d4ed8; }

        .detail-info { color: #475569; flex: 1; }

        .detail-gender {
            font-size: 9px;
            padding: 1px 4px;
            border-radius: 2px;
            margin-left: 3px;
        }

        .detail-gender.putra { background: #e0f2fe; color: #0369a1; }
        .detail-gender.putri { background: #fce7f3; color: #9d174d; }

        .detail-points { font-weight: 700; color: #1a365d; white-space: nowrap; }

        .result-table tbody tr.rank-1 { background: rgba(251, 191, 36, 0.15); }
        .result-table tbody tr.rank-2 { background: rgba(148, 163, 184, 0.15); }
        .result-table tbody tr.rank-3 { background: rgba(253, 186, 116, 0.15); }
        .result-table tbody tr:nth-child(even) { background: #fafafa; }
        .result-table tbody tr.rank-1:nth-child(even) { background: rgba(251, 191, 36, 0.2); }
        .result-table tbody tr.rank-2:nth-child(even) { background: rgba(148, 163, 184, 0.2); }
        .result-table tbody tr.rank-3:nth-child(even) { background: rgba(253, 186, 116, 0.2); }

        /* Summary Section */
        .summary-section {
            margin-top: 20px;
            padding: 15px 20px;
            background: #f8fafc;
            border: 2px solid #1a1a1a;
        }

        .summary-title {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 10px;
            text-align: center;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
        }

        .summary-item {
            text-align: center;
            padding: 10px;
            background: white;
            border: 1px solid #e2e8f0;
        }

        .summary-rank { font-weight: 700; font-size: 11px; margin-bottom: 5px; }
        .summary-rank.juara1 { color: #92400e; }
        .summary-rank.juara2 { color: #475569; }
        .summary-rank.juara3 { color: #c2410c; }
        .summary-rank.harapan1 { color: #1d4ed8; }
        .summary-rank.harapan2 { color: #1d4ed8; }
        .summary-rank.harapan3 { color: #1d4ed8; }

        .summary-count {
            font-family: 'Merriweather', serif;
            font-size: 20px;
            font-weight: 700;
            color: #1a365d;
        }

        .summary-label { font-size: 9px; color: #64748b; }

        /* Signature Section */
        .signature-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0 20px;
        }

        .signature-left, .signature-right {
            width: 250px;
            text-align: center;
        }

        .signature-date-place {
            min-height: 45px;
            margin-bottom: 10px;
            font-size: 11px;
            line-height: 1.5;
        }

        .signature-date-place-right {
            min-height: 45px;
            margin-bottom: 10px;
            font-size: 11px;
            line-height: 1.5;
        }

        .signature-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .signature-name-editable {
            font-weight: 700;
            border-bottom: 1px dashed #64748b;
            padding-bottom: 5px;
            margin: 70px auto 5px auto;
            min-height: 20px;
            min-width: 180px;
            outline: none;
            cursor: text;
            text-align: center;
        }

        .signature-role {
            font-size: 11px;
            color: #64748b;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #1a1a1a;
            text-align: center;
            font-size: 10px;
            color: #64748b;
        }

        .footer-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .footer-logo { height: 25px; opacity: 0.6; }

        /* Print Button */
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: all 0.3s;
            z-index: 100;
        }

        .print-btn:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(0,0,0,0.4); }
        .print-btn i { pointer-events: none; }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header-logos { flex-wrap: wrap; }
            .header-logo { height: 35px; }
            .point-rules-grid { grid-template-columns: repeat(2, 1fr); }
            .summary-grid { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- PAGE 1: Header with SK info -->
        <header class="header-section">
            <div class="header-logos">
                <img src="<?= asset('images/logo-kabupaten.webp') ?>" alt="Logo Kabupaten" class="header-logo" onerror="this.style.display='none'">
                <img src="<?= asset('images/favicon.webp') ?>" alt="Logo MTQ" class="header-logo" onerror="this.style.display='none'">
                <img src="<?= asset('images/logo-lptq.webp') ?>" alt="Logo LPTQ" class="header-logo" onerror="this.style.display='none'">
                <img src="<?= asset('images/emtq-resmi.webp') ?>" alt="e-MTQ" class="header-logo emtq" onerror="this.style.display='none'">
            </div>

            <div class="sk-header">
                <div class="sk-number">Nomor : 03/KPTS/DH/MTQ/XLIII/2026</div>
                <div class="sk-title">TENTANG</div>
                <div class="sk-about">PENETAPAN JUARA UMUM DAN RANKING MTQ NASIONAL KE-43 <br/>TINGKAT KABUPATEN TANAH DATAR <br/>DI KECAMATAN PARIANGAN TAHUN 2026</div>
            </div>
        </header>

        <!-- PAGE 1: Simple Table -->
        <table class="simple-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Peringkat</th>
                    <th>Kecamatan</th>
                    <th style="width: 80px;">Total Poin</th>
                    <th style="width: 100px;">Peringkat 2024</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($districtRankings as $district): ?>
                    <?php
                    $rank = $district['rank'];
                    $rankBadgeClass = match (true) {
                        $rank === 1 => 'juara1',
                        $rank === 2 => 'juara2',
                        $rank === 3 => 'juara3',
                        $rank === 4 => 'harapan1',
                        $rank === 5 => 'harapan2',
                        $rank === 6 => 'harapan3',
                        default => 'rank-other',
                    };
                    ?>
                    <tr class="rank-<?= $rank <= 3 ? $rank : 'other' ?>">
                        <td class="rank-cell">
                            <span class="rank-badge <?= e($rankBadgeClass) ?>">
                                <?= $rank ?>
                            </span>
                        </td>
                        <td class="district-cell">
                            <?= e($district['district_name']) ?>
                        </td>
                        <td class="points-cell">
                            <?= $district['points'] ?>
                        </td>
                        <td class="prev-rank-cell">
                            <input type="text" class="editable-rank" placeholder="-" data-district="<?= e($district['district_name']) ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- PAGE 1: Signature -->
        <div class="signature-section">
            <!-- Left Bottom - Ketua -->
            <div class="signature-left">
                <div class="signature-date-place"></div>
                <div class="signature-title">
                    KETUA
                </div>
                <div class="signature-name-editable" contenteditable="true" spellcheck="false"></div>
                <div class="signature-role">NIP.</div>
            </div>

            <!-- Right Bottom - Sekretaris -->
            <div class="signature-right">
                <div class="signature-date-place-right">
                    Ditetapkan di : Pariangan<br>
                    Pada Tanggal : 23 Juni 2026
                </div>
                <div class="signature-title">
                    SEKRETARIS
                </div>
                <div class="signature-name-editable" contenteditable="true" spellcheck="false"></div>
                <div class="signature-role">NIP.</div>
            </div>
        </div>

        <!-- PAGE BREAK -->
        <div class="page-break"></div>

        <!-- PAGE 2: Detail Header -->
        <div class="detail-header">
            <h2>RINCIAN PEROLEHAN PER KECAMATAN</h2>
            <p>MTQ Nasional ke-43 Tingkat Kabupaten Tanah Datar - Kecamatan Pariangan</p>
        </div>

        <!-- Point Rules Section -->
        <div class="point-rules-section">
            <div class="point-rules-title">ATURAN PEMBERIAN POIN</div>
            <div class="point-rules-grid">
                <?php foreach ($pointRules as $rank => $points): ?>
                    <?php $label = match ($rank) {
                        1 => 'Juara 1',
                        2 => 'Juara 2',
                        3 => 'Juara 3',
                        4 => 'Harapan 1',
                        5 => 'Harapan 2',
                        6 => 'Harapan 3',
                        default => 'Peringkat ' . $rank,
                    }; ?>
                    <div class="point-rule-item">
                        <span class="point-rule-label"><?= e($label) ?></span>
                        <span class="point-rule-value"><?= $points ?> Poin</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Ranking Table -->
        <table class="result-table">
            <thead>
                <tr>
                    <th class="rank-col">Peringkat</th>
                    <th class="district-col">Kecamatan</th>
                    <th class="points-col">Total Poin</th>
                    <th class="detail-col">Perolehan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($districtRankings as $district): ?>
                    <?php
                    $rank = $district['rank'];
                    $rankClass = $rank <= 3 ? 'rank-' . $rank : 'rank-other';
                    $rankBadgeClass = match (true) {
                        $rank === 1 => 'juara1',
                        $rank === 2 => 'juara2',
                        $rank === 3 => 'juara3',
                        $rank === 4 => 'harapan1',
                        $rank === 5 => 'harapan2',
                        $rank === 6 => 'harapan3',
                        default => 'rank-other',
                    };
                    ?>
                    <tr class="<?= e($rankClass) ?>">
                        <td class="rank-cell">
                            <span class="rank-badge <?= e($rankBadgeClass) ?>">
                                <?= $rank ?>
                            </span>
                        </td>
                        <td class="district-cell">
                            <?= e($district['district_name']) ?>
                            <div style="font-size: 10px; color: #64748b; font-weight: 400;">
                                <?= e($district['participant_count']) ?> Peserta
                            </div>
                        </td>
                        <td class="points-cell">
                            <?= $district['points'] ?>
                        </td>
                        <td class="detail-cell">
                            <?php foreach ($district['details'] as $detail): ?>
                                <?php
                                $detailRank = $detail['rank'];
                                $rankLabel = match (true) {
                                    $detailRank === 1 => 'J1',
                                    $detailRank === 2 => 'J2',
                                    $detailRank === 3 => 'J3',
                                    $detailRank === 4 => 'H1',
                                    $detailRank === 5 => 'H2',
                                    $detailRank === 6 => 'H3',
                                    default => 'R' . $detailRank,
                                };
                                $rankLabelClass = match (true) {
                                    $detailRank === 1 => 'juara1',
                                    $detailRank === 2 => 'juara2',
                                    $detailRank === 3 => 'juara3',
                                    $detailRank === 4 => 'harapan1',
                                    $detailRank === 5 => 'harapan2',
                                    $detailRank === 6 => 'harapan3',
                                    default => 'rank-other',
                                };
                                $gender = $detail['gender'] ?? '';
                                ?>
                                <div class="detail-item">
                                    <span class="detail-rank <?= e($rankLabelClass) ?>">
                                        [<?= e($rankLabel) ?>]
                                    </span>
                                    <span class="detail-info">
                                        <?= e($detail['category']) ?> -
                                        <?= e($detail['participant_name']) ?>
                                        <?php if ($gender): ?>
                                            <span class="detail-gender <?= e($gender) ?>"><?= e(ucfirst($gender)) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="detail-points">
                                        (<?= $detail['points'] ?> poin)
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary Section -->
        <?php
        $totalRankCounts = ['juara1' => 0, 'juara2' => 0, 'juara3' => 0, 'harapan1' => 0, 'harapan2' => 0, 'harapan3' => 0];
        foreach ($districtRankings as $district) {
            foreach ($district['rank_counts'] as $rank => $count) {
                $key = match ($rank) {
                    1 => 'juara1',
                    2 => 'juara2',
                    3 => 'juara3',
                    4 => 'harapan1',
                    5 => 'harapan2',
                    6 => 'harapan3',
                    default => null,
                };
                if ($key) {
                    $totalRankCounts[$key] += $count;
                }
            }
        }
        ?>
        <div class="summary-section">
            <div class="summary-title">REKAPITULASI TOTAL PEROLEHAN</div>
            <div class="summary-grid">
                <?php foreach ($totalRankCounts as $key => $count): ?>
                    <?php $label = match ($key) {
                        'juara1' => 'Juara 1',
                        'juara2' => 'Juara 2',
                        'juara3' => 'Juara 3',
                        'harapan1' => 'Harapan 1',
                        'harapan2' => 'Harapan 2',
                        'harapan3' => 'Harapan 3',
                        default => $key,
                    }; ?>
                    <?php $pointsLabel = match ($key) {
                        'juara1' => 'x 9 Poin',
                        'juara2' => 'x 7 Poin',
                        'juara3' => 'x 5 Poin',
                        'harapan1' => 'x 3 Poin',
                        'harapan2' => 'x 2 Poin',
                        'harapan3' => 'x 1 Poin',
                        default => '',
                    }; ?>
                    <div class="summary-item">
                        <div class="summary-rank <?= e($key) ?>"><?= e($label) ?></div>
                        <div class="summary-count"><?= $count ?></div>
                        <div class="summary-label"><?= e($pointsLabel) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-logos">
                <img src="<?= asset('images/logo-kabupaten.webp') ?>" alt="Logo" class="footer-logo" onerror="this.style.display='none'">
                <img src="<?= asset('images/favicon.webp') ?>" alt="Logo" class="footer-logo" onerror="this.style.display='none'">
                <img src="<?= asset('images/logo-lptq.webp') ?>" alt="Logo" class="footer-logo" onerror="this.style.display='none'">
                <img src="<?= asset('images/emtq-resmi.webp') ?>" alt="Logo" class="footer-logo" onerror="this.style.display='none'">
            </div>
            <p>Dokumen ini di-generate oleh <strong>e-MTQ System</strong></p>
            <p>&copy; <?= date('Y') ?> MTQ Nasional ke-XLIII Tingkat Kabupaten Tanah Datar</p>
        </footer>
    </div>

    <!-- Print Button -->
    <button onclick="window.print()" class="print-btn no-print" title="Cetak Rekap">
        <i class="fas fa-print"></i>
    </button>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</body>
</html>
