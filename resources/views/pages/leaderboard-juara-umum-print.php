<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($filterTitle ?? 'Rekap Juara Umum') ?> - e-MTQ</title>
    <link rel="icon" type="image/webp" href="<?= asset('images/favicon.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Merriweather:wght@700;900&family=Amiri:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #b8860b;
            --gold-dark: #8b6914;
            --gold-light: #fcd34d;
            --cyan: #0891b2;
            --pink: #be185d;
            --green: #059669;
            --purple: #7c3aed;
            --bg-white: #ffffff;
            --bg-light: #f8fafc;
            --bg-gold: #fffbeb;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-light: #64748b;
            --border: #e2e8f0;
            --border-dark: #cbd5e1;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #fefefe 0%, #f0f9ff 100%);
            color: var(--text-dark);
            line-height: 1.5;
            padding: 0;
            font-size: 12px;
        }

        @media print {
            body { padding: 0; background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            .header { box-shadow: none; border: 2px solid var(--gold); }
            .header-logo-row { margin-bottom: 15px; }
        }

        .container {
            max-width: 950px;
            margin: 0 auto;
            padding: 25px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #ffffff 0%, #fef3c7 50%, #fef9c3 100%);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 25px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 3px solid var(--gold);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(184,134,11,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(10%, 10%); }
        }

        /* Logo Row */
        .header-logo-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 25px;
            margin-bottom: 15px;
            position: relative;
        }

        .header-logo {
            height: 70px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.1));
        }

        .header-logo.emtq-logo {
            height: 85px;
        }

        .logo-divider {
            width: 2px;
            height: 50px;
            background: linear-gradient(180deg, transparent, var(--gold), transparent);
        }

        @media (max-width: 600px) {
            .header-logo-row {
                gap: 10px;
                flex-wrap: wrap;
            }
            .header-logo {
                height: 50px;
            }
            .header-logo.emtq-logo {
                height: 60px;
            }
            .logo-divider {
                display: none;
            }
        }

        .header-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            box-shadow: 0 2px 4px rgba(184,134,11,0.3);
        }

        .header-title {
            font-family: 'Amiri', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--gold-dark);
            text-transform: uppercase;
            letter-spacing: 3px;
            position: relative;
        }

        .header-icon {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .header-icon i {
            font-size: 24px;
            color: var(--gold);
            animation: float 3s ease-in-out infinite;
        }

        .header-icon i:nth-child(2) { animation-delay: 0.5s; }
        .header-icon i:nth-child(3) { animation-delay: 1s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .header-subtitle {
            font-size: 13px;
            color: var(--text-medium);
            margin-top: 8px;
            font-weight: 500;
        }

        .header-meta {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px dashed var(--gold);
            display: flex;
            justify-content: center;
            gap: 25px;
            font-size: 11px;
            color: var(--text-light);
        }

        .header-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .header-meta i {
            color: var(--gold);
        }

        /* Page Title */
        .page-title {
            text-align: center;
            font-family: 'Merriweather', serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--gold-dark);
            margin-bottom: 20px;
            padding: 15px 0;
            position: relative;
        }

        .page-title::after {
            content: '';
            display: block;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            margin: 10px auto 0;
        }

        /* Print Button */
        .print-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 30px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            font-size: 14px;
            font-weight: 700;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            margin: 0 auto 25px;
            width: fit-content;
            box-shadow: 0 4px 15px rgba(184,134,11,0.4);
            transition: all 0.3s ease;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(184,134,11,0.5);
        }

        .print-btn i {
            font-size: 16px;
        }

        /* Trophy Icon Styles */
        .trophy-icon {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .trophy-gold { color: #ffd700; text-shadow: 0 2px 4px rgba(255,215,0,0.5); }
        .trophy-silver { color: #c0c0c0; text-shadow: 0 2px 4px rgba(192,192,192,0.5); }
        .trophy-bronze { color: #cd7f32; text-shadow: 0 2px 4px rgba(205,127,50,0.5); }
        .trophy-hope { color: var(--cyan); text-shadow: 0 2px 4px rgba(8,145,178,0.3); }

        /* Table */
        .ranking-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .ranking-table th,
        .ranking-table td {
            padding: 14px 16px;
            border: none;
        }

        .ranking-table thead th {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ranking-table thead th:first-child {
            border-radius: 12px 0 0 0;
        }

        .ranking-table thead th:last-child {
            border-radius: 0 12px 0 0;
        }

        .ranking-table tbody tr {
            transition: all 0.2s ease;
        }

        .ranking-table tbody tr:hover {
            background: var(--bg-gold) !important;
            transform: scale(1.005);
        }

        .ranking-table tbody tr:nth-child(even) td {
            background: var(--bg-light);
        }

        .ranking-table tbody td {
            border-bottom: 1px solid var(--border);
            background: white;
        }

        .ranking-table tbody tr:last-child td:first-child {
            border-radius: 0 0 0 12px;
        }

        .ranking-table tbody tr:last-child td:last-child {
            border-radius: 0 0 12px 0;
        }

        /* Champion Table Specific */
        .champion-table tbody td:nth-child(1) {
            text-align: center;
            width: 120px;
        }

        .champion-table tbody td:nth-child(2) {
            font-weight: 700;
            font-size: 14px;
        }

        .champion-table tbody td:nth-child(3) {
            text-align: center;
            font-weight: 900;
            font-size: 22px;
            color: var(--gold-dark);
        }

        /* Rank Badges */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .rank-badge i {
            font-size: 16px;
        }

        .rank-1 {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 2px solid #fbbf24;
        }

        .rank-2 {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #4b5563;
            border: 2px solid #9ca3af;
        }

        .rank-3 {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 2px solid #d97706;
        }

        .rank-harapan {
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
            color: #0e7490;
            border: 2px solid var(--cyan);
        }

        /* District Cell */
        .district-cell {
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .district-icon {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        /* Points Cell */
        .points-cell {
            text-align: center;
            font-weight: 900;
            font-size: 20px;
            color: var(--gold-dark);
            font-family: 'Merriweather', serif;
        }

        /* Detail Table */
        .detail-table tbody td:nth-child(1) { text-align: center; width: 60px; }
        .detail-table tbody td:nth-child(3) { text-align: center; font-weight: 900; font-size: 18px; color: var(--gold-dark); }

        /* Rank Count Column */
        .rank-count-cell {
            font-size: 10px;
            min-width: 120px;
        }

        .rank-count-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 8px;
            margin: 2px 0;
            background: var(--bg-light);
            border-radius: 4px;
            border-left: 3px solid var(--gold);
        }

        .rank-count-label {
            color: var(--text-medium);
            font-size: 9px;
        }

        .rank-count-value {
            font-weight: 700;
            color: var(--gold-dark);
            font-size: 11px;
        }

        /* Detail Cell */
        .detail-cell {
            font-size: 9px;
            color: var(--text-light);
            max-width: 300px;
        }

        .detail-item {
            margin: 3px 0;
            padding: 4px 6px;
            background: var(--bg-light);
            border-radius: 5px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px;
            border-left: 3px solid var(--border);
        }

        .detail-final {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 50%);
            color: #92400e;
            border-left-color: var(--gold);
        }

        .detail-penyisihan {
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 50%);
            color: #0e7490;
            border-left-color: var(--cyan);
        }

        .detail-points {
            font-weight: 800;
            color: var(--gold-dark);
            margin-left: auto;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 40px;
            padding: 25px;
            background: linear-gradient(135deg, var(--bg-light) 0%, var(--bg-gold) 100%);
            border-radius: 12px;
            font-size: 11px;
            color: var(--text-light);
        }

        .footer-brand {
            font-weight: 800;
            color: var(--gold-dark);
            font-size: 14px;
        }

        .footer i {
            color: var(--gold);
        }

        /* Rule Box */
        .rule-box {
            background: white;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .rule-title {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rule-title i {
            color: var(--gold);
        }

        .rule-list {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 11px;
            color: var(--text-medium);
        }

        .rule-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--bg-light);
            border-radius: 20px;
        }

        .rule-points {
            font-weight: 700;
            color: var(--gold-dark);
            background: var(--bg-gold);
            padding: 2px 8px;
            border-radius: 10px;
        }

        /* Small Rank Badge in Detail */
        .small-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 700;
        }

        .small-badge.final {
            background: var(--gold);
            color: white;
        }

        .small-badge.penyisihan {
            background: var(--cyan);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <!-- Official Logos Row -->
            <div class="header-logo-row">
                <img src="<?= asset('images/logo-kabupaten.webp') ?>" alt="Logo Kabupaten" class="header-logo" title="Logo Kabupaten Tanah Datar">
                <div class="logo-divider"></div>
                <img src="<?= asset('images/favicon.webp') ?>" alt="Logo MTQ" class="header-logo" title="Logo MTQ">
                <div class="logo-divider"></div>
                <img src="<?= asset('images/logo-lptq.webp') ?>" alt="Logo LPTQ" class="header-logo" title="Logo LPTQ">
                <div class="logo-divider"></div>
                <img src="<?= asset('images/emtq-resmi.webp') ?>" alt="e-MTQ" class="header-logo emtq-logo" title="e-MTQ System">
            </div>

            <div class="header-badge"><i class="fas fa-star"></i> MTQ Nasional XLIII</div>
            <div class="header-icon">
                <i class="fas fa-book-quran"></i>
                <i class="fas fa-crown"></i>
                <i class="fas fa-mosque"></i>
            </div>
            <div class="header-title"><?= e($filterTitle ?? 'Rekap Juara Umum') ?></div>
            <div class="header-subtitle">MTQ Nasional ke-XLIII Tingkat Kabupaten Tanah Datar</div>
            <div class="header-subtitle"><i class="fas fa-map-marker-alt"></i> Pariangan, <?= date('d F Y') ?></div>
            <div class="header-meta">
                <span><i class="fas fa-clock"></i> <?= e($generatedAt) ?></span>
                <span><i class="fas fa-building"></i> <?= count($districtRankings) ?> Kecamatan</span>
                <span><i class="fas fa-trophy"></i> Based on Final & Penyisihan</span>
            </div>
        </header>

        <button onclick="window.print()" class="print-btn no-print">
            <i class="fas fa-print"></i>
            Cetak / Save as PDF
        </button>

        <!-- ==================== PAGE 1: TABEL JUARA KECAMATAN ==================== -->
        <div class="page-title">
            <i class="fas fa-trophy" style="color: var(--gold);"></i>
            Tabel Juara Kecamatan
        </div>

        <table class="ranking-table champion-table">
            <thead>
                <tr>
                    <th><i class="fas fa-medal"></i> Peringkat</th>
                    <th><i class="fas fa-map-marked-alt"></i> Kecamatan</th>
                    <th><i class="fas fa-star"></i> Total Poin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($districtRankings as $district): ?>
                    <?php
                    $rank = $district['rank'];
                    $rankClass = match(true) {
                        $rank === 1 => 'rank-1',
                        $rank === 2 => 'rank-2',
                        $rank === 3 => 'rank-3',
                        default => 'rank-harapan',
                    };
                    $rankLabel = match(true) {
                        $rank === 1 => 'Juara 1',
                        $rank === 2 => 'Juara 2',
                        $rank === 3 => 'Juara 3',
                        $rank === 4 => 'Harapan 1',
                        $rank === 5 => 'Harapan 2',
                        $rank === 6 => 'Harapan 3',
                        default => $rank,
                    };
                    $trophyIcon = match(true) {
                        $rank === 1 => '<i class="fas fa-trophy"></i>',
                        $rank === 2 => '<i class="fas fa-medal"></i>',
                        $rank === 3 => '<i class="fas fa-award"></i>',
                        default => '<i class="fas fa-star"></i>',
                    };
                    $trophyClass = match(true) {
                        $rank === 1 => 'trophy-gold',
                        $rank === 2 => 'trophy-silver',
                        $rank === 3 => 'trophy-bronze',
                        default => 'trophy-hope',
                    };
                    ?>
                    <tr>
                        <td>
                            <span class="rank-badge <?= e($rankClass) ?>">
                                <span class="<?= e($trophyClass) ?>"><?= $trophyIcon ?></span>
                                <?= e($rankLabel) ?>
                            </span>
                        </td>
                        <td class="district-cell">
                            <span class="district-icon">
                                <i class="fas fa-landmark"></i>
                            </span>
                            <?= e($district['district_name']) ?>
                        </td>
                        <td class="points-cell"><?= e($district['points']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Page Break for Print -->
        <div class="page-break"></div>

        <!-- ==================== PAGE 2: TABEL DETAIL ==================== -->
        <div class="page-title">
            <i class="fas fa-list-alt" style="color: var(--gold);"></i>
            Tabel Detail Perolehan Poin
        </div>

        <div class="rule-box">
            <div class="rule-title">
                <i class="fas fa-info-circle"></i>
                Sistem Poin Juara Umum
            </div>
            <div class="rule-list">
                <span class="rule-item">
                    <i class="fas fa-trophy" style="color: var(--gold);"></i>
                    Final Rank 1, 2, 3
                    <span class="rule-points">9, 7, 5 Poin</span>
                </span>
                <span class="rule-item">
                    <i class="fas fa-star" style="color: var(--cyan);"></i>
                    Penyisihan Rank 4, 5, 6
                    <span class="rule-points">3, 2, 1 Poin</span>
                </span>
            </div>
        </div>

        <table class="ranking-table detail-table">
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag"></i> Rank</th>
                    <th><i class="fas fa-map-marked-alt"></i> Kecamatan</th>
                    <th><i class="fas fa-star"></i> Poin</th>
                    <th><i class="fas fa-trophy"></i> Jumlah Juara</th>
                    <th><i class="fas fa-list"></i> Rincian Perolehan Poin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($districtRankings as $district): ?>
                    <?php $rankCounts = $district['rank_counts'] ?? []; ?>
                    <tr>
                        <td class="rank-cell">
                            <?php
                            $rankClass = match($district['rank']) {
                                1 => 'rank-1',
                                2 => 'rank-2',
                                3 => 'rank-3',
                                default => 'rank-harapan',
                            };
                            ?>
                            <span class="rank-badge <?= e($rankClass) ?>" style="padding: 6px 12px;">
                                #<?= e($district['rank']) ?>
                            </span>
                        </td>
                        <td class="district-cell">
                            <span class="district-icon" style="width: 30px; height: 30px; font-size: 12px;">
                                <i class="fas fa-landmark"></i>
                            </span>
                            <?= e($district['district_name']) ?>
                        </td>
                        <td class="points-cell"><?= e($district['points']) ?></td>
                        <td class="rank-count-cell">
                            <?php
                            $rankLabels = [1 => 'Juara 1', 2 => 'Juara 2', 3 => 'Juara 3', 4 => 'Rank 4', 5 => 'Rank 5', 6 => 'Rank 6'];
                            foreach ($rankLabels as $rank => $label):
                                $count = $rankCounts[$rank] ?? 0;
                                if ($count > 0):
                            ?>
                                <div class="rank-count-item">
                                    <span class="rank-count-label"><?= e($label) ?></span>
                                    <span class="rank-count-value">× <?= e($count) ?></span>
                                </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </td>
                        <td class="detail-cell">
                            <?php foreach ($district['details'] as $detail): ?>
                                <div class="detail-item <?= $detail['type'] === 'final' ? 'detail-final' : 'detail-penyisihan' ?>">
                                    <span class="small-badge <?= $detail['type'] === 'final' ? 'final' : 'penyisihan' ?>">
                                        <?= $detail['type'] === 'final' ? '<i class="fas fa-trophy"></i>' : '<i class="fas fa-star"></i>' ?>
                                        <?= e(ucfirst($detail['type'])) ?>
                                    </span>
                                    <strong>#<?= e($detail['rank']) ?></strong>
                                    <span><?= e($detail['category']) ?></span>
                                    <span>(<?= e($detail['participant_name']) ?>)</span>
                                    <span class="detail-points"><i class="fas fa-plus"></i><?= e($detail['points']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <footer class="footer">
            <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 10px;">
                <img src="<?= asset('images/logo-kabupaten.webp') ?>" alt="Logo Kabupaten" style="height: 35px; width: auto;">
                <img src="<?= asset('images/favicon.webp') ?>" alt="Logo MTQ" style="height: 35px; width: auto;">
                <img src="<?= asset('images/logo-lptq.webp') ?>" alt="Logo LPTQ" style="height: 35px; width: auto;">
                <img src="<?= asset('images/emtq-resmi.webp') ?>" alt="e-MTQ" style="height: 40px; width: auto;">
            </div>
            <p>
                <i class="fas fa-bolt"></i>
                Dokumen ini di-generate oleh <span class="footer-brand">e-MTQ System</span>
            </p>
            <p style="margin-top: 5px;">
                <i class="fas fa-book"></i>
                Rekap Juara Umum Kecamatan - MTQ Nasional ke-XLIII Kabupaten Tanah Datar
            </p>
        </footer>
    </div>
</body>
</html>
