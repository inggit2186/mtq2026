<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lampiran SK Dewan Hakim - Rekap Juara Umum MTQ Tanah Datar ke-43</title>
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
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1a1a1a;
        }

        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
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

        .sk-title {
            font-family: 'Merriweather', serif;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 20px;
            padding: 15px 20px;
            background: #f8fafc;
            border: 2px solid #1a1a1a;
            text-align: center;
        }

        .info-row {
            margin-bottom: 8px;
            font-size: 14px;
            line-height: 1.6;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 700;
        }

        .info-value {
            font-weight: 400;
        }

        /* Table */
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 12px;
        }

        .result-table thead {
            background: #f8fafc;
        }

        .result-table th {
            padding: 10px 8px;
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            border: 1px solid #1a1a1a;
            background: #1a365d;
            color: white;
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

        .rank-badge.juara1 {
            background: transparent;
            border-color: #f59e0b;
            color: #92400e;
        }

        .rank-badge.juara2 {
            background: transparent;
            border-color: #94a3b8;
            color: #475569;
        }

        .rank-badge.juara3 {
            background: transparent;
            border-color: #ea580c;
            color: #c2410c;
        }

        .rank-badge.rank-other {
            background: transparent;
            border-color: #64748b;
            color: #475569;
        }

        .district-cell {
            font-weight: 600;
        }

        .points-cell {
            text-align: center;
            font-family: 'Merriweather', serif;
            font-weight: 700;
            font-size: 16px;
            color: #1a365d;
        }

        .detail-cell {
            font-size: 11px;
            line-height: 1.6;
        }

        .detail-item {
            margin-bottom: 3px;
            display: flex;
            gap: 5px;
        }

        .detail-rank {
            font-weight: 700;
            min-width: 60px;
        }

        .detail-rank.juara1 { color: #92400e; }
        .detail-rank.juara2 { color: #475569; }
        .detail-rank.juara3 { color: #c2410c; }
        .detail-rank.harapan { color: #1d4ed8; }

        .detail-info {
            color: #475569;
        }

        .detail-points {
            font-weight: 700;
            color: #1a365d;
        }

        /* Rank Row Colors */
        .result-table tbody tr.rank-1 {
            background: rgba(251, 191, 36, 0.15);
        }

        .result-table tbody tr.rank-2 {
            background: rgba(148, 163, 184, 0.15);
        }

        .result-table tbody tr.rank-3 {
            background: rgba(253, 186, 116, 0.15);
        }

        .result-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .result-table tbody tr.rank-1:nth-child(even) {
            background: rgba(251, 191, 36, 0.2);
        }

        .result-table tbody tr.rank-2:nth-child(even) {
            background: rgba(148, 163, 184, 0.2);
        }

        .result-table tbody tr.rank-3:nth-child(even) {
            background: rgba(253, 186, 116, 0.2);
        }

        /* Signature Section */
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-right {
            width: 250px;
            text-align: center;
        }

        .signature-date {
            margin-bottom: 40px;
        }

        .signature-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .signature-name {
            font-weight: 700;
            border-top: 1px solid #1a1a1a;
            padding-top: 5px;
            margin-bottom: 5px;
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

        .footer-logo {
            height: 25px;
            opacity: 0.6;
        }

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

        .print-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }

        .print-btn i { pointer-events: none; }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header-logos { flex-wrap: wrap; }
            .header-logo { height: 35px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <header class="header-section">
            <div class="header-logos">
                <img src="<?= asset('images/logo-kabupaten.webp') ?>" alt="Logo Kabupaten" class="header-logo" onerror="this.style.display='none'">
                <img src="<?= asset('images/favicon.webp') ?>" alt="Logo MTQ" class="header-logo" onerror="this.style.display='none'">
                <img src="<?= asset('images/logo-lptq.webp') ?>" alt="Logo LPTQ" class="header-logo" onerror="this.style.display='none'">
                <img src="<?= asset('images/emtq-resmi.webp') ?>" alt="e-MTQ" class="header-logo emtq" onerror="this.style.display='none'">
            </div>

            <div class="sk-header">
                <div class="sk-title">LAMPIRAN SURAT KEPUTUSAN</div>
                <div class="sk-title">KOORDINATOR DEWAN HAKIM MTQ NASIONAL KE-43</div>
                <div class="sk-title">TINGKAT KABUPATEN TANAH DATAR TAHUN 2026</div>
            </div>
        </header>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Nomor</span>
                <span class="info-value">: 02/KPTS/DH/MTQ/XLIII/2026</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tentang</span>
                <span class="info-value">: Penetapan Perolehan Poin Juara Umum MTQ Tingkat Kabupaten Tanah Datar Ke-43 Tahun 2026 di Kecamatan Rambatan</span>
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
                    $rankBadgeClass = $rank <= 3 ? 'juara' . $rank : 'rank-other';
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
                                $rankLabelClass = $detailRank <= 3 ? 'juara' . $detailRank : 'harapan';
                                $rankLabel = $detailRank <= 3 ? 'J' . $detailRank : 'H' . ($detailRank - 3);
                                ?>
                                <div class="detail-item">
                                    <span class="detail-rank <?= e($rankLabelClass) ?>">
                                        [<?= e($rankLabel) ?>]
                                    </span>
                                    <span class="detail-info">
                                        <?= e($detail['category']) ?> -
                                        <?= e($detail['participant_name']) ?>
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

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-right">
                <div class="signature-date">
                    Pariangan, <?= date('d F Y') ?>
                </div>
                <div class="signature-title">
                    KETUA DEWAN HAKIM MTQ
                </div>
                <div class="signature-title">
                    KABUPATEN TANAH DATAR
                </div>
                <div class="signature-name">&nbsp;</div>
                <div class="signature-role">( .......................... )</div>
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
    <button onclick="window.print()" class="print-btn no-print" title="Cetak Lampiran Resmi">
        <i class="fas fa-print"></i>
    </button>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</body>
</html>
