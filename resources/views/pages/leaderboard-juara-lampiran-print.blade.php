<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lampiran SK Dewan Hakim - Penetapan Juara MTQ Tanah Datar ke-43</title>
    <link rel="icon" type="image/webp" href="<?= asset('images/favicon.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700;900&family=Inter:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { margin: 2.5cm; size: A4; }

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

        .sk-number {
            font-size: 11px;
            margin-bottom: 3px;
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

        /* Category Section */
        .category-section {
            margin-bottom: 20px;
        }

        .category-section.page-break-before::before {
            content: '';
            display: block;
            height: 25px;
        }

        .category-header {
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
            color: white;
            padding: 8px 12px;
            margin-bottom: 0;
        }

        .category-title {
            font-family: 'Merriweather', serif;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .category-meta {
            font-size: 9px;
            opacity: 0.9;
            margin-top: 2px;
        }

        /* Gender Subsection */
        .gender-subsection {
            margin-bottom: 15px;
        }

        .gender-header {
            background: #e2e8f0;
            padding: 6px 12px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            border-left: 4px solid #2c5282;
        }

        .gender-header.putra {
            background: #e0f2fe;
            border-left-color: #0284c7;
        }

        .gender-header.putri {
            background: #fce7f3;
            border-left-color: #db2777;
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
            padding: 8px 6px;
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            border: 1px solid #1a1a1a;
            background: #f1f5f9;
        }

        .result-table th.rank-col { width: 45px; }
        .result-table th.lot-col { width: 60px; }
        .result-table th.name-col { min-width: 120px; }
        .result-table th.district-col { width: 100px; }
        .result-table th.score-col { width: 70px; }

        .result-table td {
            padding: 6px 6px;
            border: 1px solid #1a1a1a;
            vertical-align: middle;
        }

        .result-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .result-table tbody tr.juara-row {
            background: rgba(251, 191, 36, 0.15);
        }

        .result-table tbody tr.juara-row:nth-child(even) {
            background: rgba(251, 191, 36, 0.2);
        }

        .result-table tbody tr.harapan-row {
            background: rgba(59, 130, 246, 0.1);
        }

        .result-table tbody tr.harapan-row:nth-child(even) {
            background: rgba(59, 130, 246, 0.15);
        }

        .rank-cell {
            text-align: center;
            font-weight: 700;
        }

        .rank-badge {
            display: inline-block;
            min-width: 60px;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 10px;
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

        .rank-badge.harapan1,
        .rank-badge.harapan2,
        .rank-badge.harapan3 {
            background: transparent;
            border-color: #3b82f6;
            color: #1d4ed8;
        }

        .lot-cell {
            text-align: center;
            font-family: 'Merriweather', serif;
            font-weight: 700;
            font-size: 11px;
        }

        .name-cell {
            font-weight: 600;
        }

        .district-cell {
            color: #475569;
        }

        .score-cell {
            text-align: center;
            font-weight: 700;
            font-family: 'Merriweather', serif;
        }

        .fallback-note {
            font-size: 9px;
            color: #92400e;
            font-style: italic;
            margin-top: 2px;
        }

        .no-data {
            text-align: center;
            color: #64748b;
            padding: 15px;
            font-style: italic;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .signature-left, .signature-right {
            width: 45%;
            text-align: center;
        }

        .signature-right {
            text-align: center;
        }

        .signature-date-place {
            margin-bottom: 30px;
            font-size: 11px;
            line-height: 1.6;
        }

        .signature-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .signature-name {
            font-weight: 700;
            border-bottom: 1px solid #1a1a1a;
            padding-bottom: 40px;
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

        .signature-name-editable:focus {
            border-bottom-color: #1a1a1a;
            border-bottom-style: solid;
        }

        .signature-name-editable:empty::before {
            content: 'Ketik nama di sini';
            color: #94a3b8;
            font-style: italic;
        }

        .signature-role {
            font-size: 10px;
            color: #64748b;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #1a1a1a;
            text-align: center;
            font-size: 9px;
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

        /* Branch Divider */
        .branch-divider {
            background: #f1f5f9;
            padding: 8px 15px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            margin: 20px 0 10px 0;
            border-left: 5px solid #1a365d;
        }

        .branch-divider::before {
            content: '';
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header-logos { flex-wrap: wrap; }
            .header-logo { height: 35px; }
            .signature-section { flex-direction: column; }
            .signature-left, .signature-right { width: 100%; margin-bottom: 20px; }
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
                <span class="info-label">TENTANG</span><br/>
                <span class="info-value"><b>PENETAPAN JUARA I, II, III DAN HARAPAN I, II, III MTQ TINGKAT KABUPATEN TANAH DATAR Ke-43 Tahun 2026 DI KECAMATAN PARIANGAN</b></span>
            </div>
        </div>

        <?php $previousBranch = null; ?>
        <?php foreach ($groupedResults as $group): ?>
            <?php $previousBranch = $group['branch']; ?>

            <?php foreach ($group['categories'] as $categoryData): ?>
                <div class="category-section">
                    <div class="category-header">
                        <div class="category-title"><?= e($categoryData['branch']) ?> - <?= e($categoryData['name']) ?></div>
                    </div>

                    <?php
                    $hasPutra = !empty($categoryData['putra']['juara']) || !empty($categoryData['putra']['harapan']);
                    $hasPutri = !empty($categoryData['putri']['juara']) || !empty($categoryData['putri']['harapan']);
                    ?>

                    <?php if ($hasPutra): ?>
                        <div class="gender-subsection">
                            <div class="gender-header putra">
                                <i class="fas fa-mars"></i> Golongan Putra
                            </div>
                            <?= view('pages.partials.lampiran-table', [
                                'juaraList' => $categoryData['putra']['juara'] ?? [],
                                'harapanList' => $categoryData['putra']['harapan'] ?? [],
                                'isMfq' => $categoryData['is_mfq'] ?? false,
                            ]) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasPutri): ?>
                        <div class="gender-subsection">
                            <div class="gender-header putri">
                                <i class="fas fa-venus"></i> Golongan Putri
                            </div>
                            <?= view('pages.partials.lampiran-table', [
                                'juaraList' => $categoryData['putri']['juara'] ?? [],
                                'harapanList' => $categoryData['putri']['harapan'] ?? [],
                                'isMfq' => $categoryData['is_mfq'] ?? false,
                            ]) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$hasPutra && !$hasPutri): ?>
                        <div class="no-data">Belum ada data juara untuk golongan ini</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <!-- Signature Section -->
        <div class="signature-section">
            <!-- Left Bottom - Ketua -->
            <div class="signature-left">
                <div class="signature-title">
                    KETUA
                </div>
                <div class="signature-name-editable" contenteditable="true" spellcheck="false"></div>
                <div class="signature-role">NIP.</div>
            </div>

            <!-- Right Bottom - Sekretaris -->
            <div class="signature-right">
                <div class="signature-date-place">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smart page-break: if category-section starts at bottom 80% of page, break before it
            const categories = document.querySelectorAll('.category-section');
            categories.forEach(function(cat) {
                const rect = cat.getBoundingClientRect();
                const offsetFromTop = rect.top + window.scrollY;
                const printPageHeight = 247 * 3.78; // ~297mm - 50mm margins, converted to px
                const currentPageTop = Math.floor(offsetFromTop / printPageHeight) * printPageHeight;
                const positionInPage = offsetFromTop - currentPageTop;

                // If position is past 80% of a print page, add page-break before
                if (positionInPage > printPageHeight * 0.80) {
                    cat.style.pageBreakBefore = 'always';
                    cat.style.breakBefore = 'page';
                    cat.classList.add('page-break-before');
                }
            });
        });
    </script>
</body>
</html>
