<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Peserta MTQ per Golongan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            background: #f1f5f9;
            padding: 10pt;
        }
        .pdf-wrapper {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            overflow: hidden;
        }
        /* Category PDF - each is a separate document */
        .category-pdf {
            min-height: 297mm;
            position: relative;
            page-break-after: always;
        }
        .category-pdf:last-child {
            page-break-after: auto;
        }
        /* Main Header - Compact */
        .main-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            color: white;
            padding: 12pt 20pt 10pt 20pt;
            text-align: center;
            position: relative;
        }
        .main-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm1-11V10H18v-2h11v2h9v4H18v2h11v4h2V23H18v-2h9v-4h9v-2H19v-4h18z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .main-header-content { position: relative; }
        .main-header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6pt;
            margin-bottom: 6pt;
        }
        .main-logo { width: 30pt; height: 30pt; object-fit: contain; }
        .main-logo-lg { width: 40pt; height: 40pt; }
        .main-title { font-family: 'Playfair Display', serif; font-size: 11pt; font-weight: 600; margin-bottom: 2pt; }
        .main-event-title { font-family: 'Playfair Display', serif; font-size: 16pt; font-weight: 700; line-height: 1.2; margin-bottom: 4pt; }
        .main-sk {
            font-size: 8pt;
            line-height: 1.3;
            opacity: 0.9;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 4pt;
            margin-top: 4pt;
        }
        /* Combined Venue Section - One Row */
        .venue-combined {
            display: flex;
            gap: 0;
            padding: 0;
            background: #f8fafc;
        }
        .venue-banner {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%);
            color: white;
            padding: 10pt 15pt;
            text-align: center;
            position: relative;
            flex: 0 0 35%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-right: 3pt solid #fbbf24;
        }
        .venue-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M15 0L30 15L15 30L0 15z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .venue-banner-content { position: relative; }
        .venue-label {
            display: inline-block;
            background: #fbbf24;
            color: #1e293b;
            padding: 2pt 10pt;
            border-radius: 10pt;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin-bottom: 4pt;
        }
        .venue-name {
            font-family: 'Playfair Display', serif;
            font-size: 14pt;
            font-weight: 700;
            line-height: 1.2;
            text-shadow: 0 2pt 4pt rgba(0,0,0,0.2);
        }
        /* Venue Photo & QR Section - Combined in one row */
        .venue-info-section {
            display: flex;
            gap: 10pt;
            padding: 10pt 15pt;
            background: #f8fafc;
            flex: 1;
            align-items: center;
        }
        .venue-photo-wrapper {
            flex: 1;
            min-height: 60pt;
            border-radius: 6pt;
            overflow: hidden;
            box-shadow: 0 2pt 6pt rgba(0,0,0,0.1);
            flex: 1;
        }
        .venue-photo {
            width: 100%;
            height: 60pt;
            object-fit: cover;
        }
        .venue-photo-placeholder {
            width: 100%;
            height: 60pt;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 9pt;
            flex-direction: column;
        }
        .venue-qr-section {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 8pt;
            padding: 6pt 8pt;
            background: white;
            border-radius: 6pt;
            box-shadow: 0 1pt 4pt rgba(0,0,0,0.08);
            flex: 0 0 auto;
        }
        .venue-qr-label {
            font-size: 7pt;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
        }
        .venue-qr-code {
            width: 55pt;
            height: 55pt;
            background: white;
        }
        .venue-qr-url {
            font-size: 6pt;
            color: #94a3b8;
            max-width: 55pt;
            word-break: break-all;
            text-align: center;
        }
        /* Category Header */
        .category-header {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white;
            padding: 15pt 20pt;
            text-align: center;
            position: relative;
        }
        .category-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='20' cy='20' r='15'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .category-header-content { position: relative; }
        .category-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 3pt 12pt;
            border-radius: 12pt;
            font-size: 8pt;
            font-weight: 600;
            margin-bottom: 6pt;
        }
        .category-branch { font-size: 11pt; margin-bottom: 3pt; }
        .category-title { font-family: 'Playfair Display', serif; font-size: 18pt; font-weight: 700; line-height: 1.2; }
        /* Info Cards */
        .info-cards { padding: 10pt 20pt; display: flex; gap: 8pt; flex-wrap: wrap; }
        .info-card {
            flex: 1;
            min-width: 80pt;
            padding: 8pt;
            border-radius: 8pt;
            text-align: center;
        }
        .info-card.putra { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
        .info-card.putri { background: linear-gradient(135deg, #fce7f3, #fbcfe8); }
        .info-card.total { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
        .info-card-label { font-size: 10pt; font-weight: 600; margin-bottom: 2pt; }
        .info-card.putra .info-card-label { color: #1d4ed8; }
        .info-card.putri .info-card-label { color: #9d174d; }
        .info-card.total .info-card-label { color: #065f46; }
        .info-card-value { font-size: 14pt; font-weight: 700; }
        .info-card.putra .info-card-value { color: #1d4ed8; }
        .info-card.putri .info-card-value { color: #9d174d; }
        .info-card.total .info-card-value { color: #065f46; }
        /* Table */
        .table-section { padding: 8pt 20pt; }
        .participant-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            box-shadow: 0 2pt 8pt rgba(0,0,0,0.06);
            border-radius: 8pt;
            overflow: hidden;
        }
        .participant-table thead th {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: white;
            padding: 6pt 5pt;
            text-align: center;
            font-weight: 600;
            font-size: 10pt;
            text-transform: uppercase;
        }
        .participant-table tbody td {
            padding: 6pt 5pt;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .participant-table tbody tr:last-child td { border-bottom: none; }
        .participant-table tbody tr:hover td { background: #f8fafc; }
        /* Photo cell */
        .photo-cell { width: 30pt; text-align: center; }
        .photo-placeholder {
            width: 450pt;
            height: 55pt;
            background: #f1f5f9;
            border-radius: 5pt;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #94a3b8;
            font-size: 10pt;
        }
        .photo-img {
            width: 45pt;
            height: 55pt;
            object-fit: cover;
            border-radius: 5pt;
        }
        /* Name cell */
        .name-text { font-weight: 600; color: #1e293b; }
        .name-sub { font-size: 9pt; color: #64748b; margin-top: 2pt; }
        /* District cell */
        .district-text { font-size: 11pt; }
        /* Age badge */
        .age-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 2pt 6pt;
            border-radius: 6pt;
            font-size: 11pt;
            font-weight: 600;
        }
        /* Footer - per category */
        .category-footer {
            background: linear-gradient(135deg, #1e3a5f, #0f172a);
            color: white;
            padding: 6pt 20pt;
            text-align: center;
            font-size: 9pt;
            page-break-inside: avoid;
        }
        .footer-brand { font-weight: 600; margin-bottom: 1pt; }
        .footer-text { opacity: 0.8; }
        /* Signature - per category, right aligned */
        .signature-section {
            page-break-inside: avoid;
            padding: 25pt 40pt 15pt 60pt;
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
        }
        .signature-right {
            text-align: right;
            font-size: 12pt;
            line-height: 1.6;
        }
        .signature-date { margin-bottom: 45pt; }
        .signature-title { font-weight: 600; }
        .signature-name {
            font-weight: 600;
            font-size: 13pt;
            color: #1e3a5f;
            margin-top: 40pt;
        }
        .signature-role { font-size: 10pt; color: #64748b; }
        /* Download buttons */
        .download-section {
            padding: 15pt;
            background: #f1f5f9;
            display: flex;
            flex-wrap: wrap;
            gap: 10pt;
            justify-content: center;
        }
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 6pt;
            padding: 10pt 18pt;
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: white;
            border: none;
            border-radius: 8pt;
            font-size: 11pt;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .download-btn:hover {
            background: linear-gradient(135deg, #0e7490, #0891b2);
            transform: translateY(-1pt);
        }
        .download-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .download-btn svg { width: 16pt; height: 16pt; }
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            color: white;
        }
        .loading-overlay.active { display: flex; }
        .loading-spinner {
            width: 40px; height: 40px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 12px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-text { font-size: 13pt; }
        .loading-category { font-size: 11pt; opacity: 0.8; margin-top: 6pt; }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Generating PDF...</div>
        <div id="loadingCategory" class="loading-category"></div>
    </div>

    <!-- Download Button for Single Category 
    @if ($singleMode && count($categoriesData) === 1)
    <div class="download-section" style="background: linear-gradient(135deg, #059669, #047857);">
        <button class="download-btn" onclick="downloadCategoryImage(0)" style="background: white; color: #059669;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            <span>Download sebagai Gambar (PNG)</span>
        </button>
    </div>
    @endif -->

    <!-- Download Buttons (only show when multiple categories) -->
    @if (count($categoriesData) > 1)
    <div class="download-section">
        @foreach ($categoriesData as $index => $categoryData)
        <button class="download-btn" onclick="downloadCategoryImage({{ $index }})">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span>{{ $categoryData['category']->branch }} - {{ $categoryData['category']->name }}</span>
        </button>
        @endforeach
    </div>
    @endif

    <!-- PDF Content Container -->
    <div id="pdfContainer">
        @foreach ($categoriesData as $index => $categoryData)
        <div class="pdf-wrapper">
            <div class="category-pdf" id="categoryPdf{{ $index }}">
                <!-- Main Header -->
                <div class="main-header">
                    <div class="main-header-content">
                        <div class="main-header-logos">
                            <img src="/images/logo-kabupaten.webp" alt="" class="main-logo">
                            <img src="/images/favicon.webp" alt="" class="main-logo">
                            <img src="/images/logo-lptq.webp" alt="" class="main-logo">
                            <img src="/images/emtq-resmi.webp" alt="" class="main-logo main-logo-lg">
                        </div>
                        <div class="main-title">Daftar Nama Peserta</div>
                        <div class="main-event-title">{{ $eventTitle }}</div>
                        <div class="main-sk">
                            Berdasarkan SK LPTQ Kabupaten Tanah Datar<br>
                            <b>Nomor : 02/LPTQ/TD/2026</b><br>
                            Tanggal 26 Mei 2026
                        </div>
                    </div>
                </div>

                <!-- Category Header - Branch & Category First -->
                <div class="category-header">
                    <div class="category-header-content">
                        <div class="category-badge">Daftar Peserta</div>
                        <div class="category-branch">{{ $categoryData['category']->branch }}</div>
                        <div class="category-title">{{ $categoryData['category']->name }}</div>
                    </div>
                </div>

                <!-- Combined Venue Section - Location Second -->
                <div class="venue-combined">
                    <!-- Venue Banner -->
                    <div class="venue-banner">
                        <div class="venue-banner-content">
                            <div class="venue-label">Lokasi Lomba</div>
                            <div class="venue-name">{{ $categoryData['venue_name'] }}</div>
                        </div>
                    </div>

                    <!-- Venue Photo & QR Code Section -->
                    <div class="venue-info-section">
                        <!-- Venue Photo -->
                        <div class="venue-photo-wrapper">
                            @if ($categoryData['photo_path'] && file_exists(public_path($categoryData['photo_path'])))
                                <img src="{{ asset($categoryData['photo_path']) }}" alt="Foto Lokasi" class="venue-photo">
                            @else
                                <div class="venue-photo-placeholder">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    <span style="margin-top:4pt;font-size:8pt;">Foto</span>
                                </div>
                            @endif
                        </div>

                        <!-- QR Code Section -->
                        @if (!empty($categoryData['map_url']))
                        <div class="venue-qr-section">
                            <div class="venue-qr-label">Peta</div>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=55x55&data={{ urlencode($categoryData['map_url']) }}" alt="QR Code Peta" class="venue-qr-code">
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="info-cards">
                    <div class="info-card putra">
                        <div class="info-card-label">PUTRA</div>
                        <div class="info-card-value">{{ $categoryData['putra_count'] }}</div>
                    </div>
                    <div class="info-card putri">
                        <div class="info-card-label">PUTRI</div>
                        <div class="info-card-value">{{ $categoryData['putri_count'] }}</div>
                    </div>
                    <div class="info-card total">
                        <div class="info-card-label">TOTAL</div>
                        <div class="info-card-value">{{ $categoryData['total'] }}</div>
                    </div>
                </div>

                <!-- Participant Table -->
                <div class="table-section">
                    <table class="participant-table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th class="col-photo">Foto</th>
                                <th class="col-name">Nama Peserta</th>
                                <th class="col-district">Kecamatan</th>
                                <th class="col-age">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $rowNumber = 1; @endphp
                            @foreach ($categoryData['participants'] as $participant)
                            <tr>
                                <td style="text-align:center;font-weight:600;color:#0891b2;">{{ $rowNumber }}</td>
                                <td class="photo-cell">
                                    @if ($participant['photo'] && file_exists(public_path('storage/' . $participant['photo'])))
                                        <img src="{{ asset('storage/' . $participant['photo']) }}" alt="" class="photo-img">
                                    @else
                                        <div class="photo-placeholder">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="name-text">{{ $participant['name'] }}</div>
                                    <div class="name-sub">{{ ucfirst($participant['gender']) }}</div>
                                </td>
                                <td class="district-text">{{ $participant['district'] }}</td>
                                <td style="text-align:center;font-size:11pt;">
                                    &nbsp; &nbsp; &nbsp; &nbsp;
                                </td>
                            </tr>
                            @php $rowNumber++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="category-footer">
                    <div class="footer-brand">e-MTQ {{ $eventTitle }} | {{ $categoryData['category']->name }} | Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }} WIB</div>
                </div>

                <!-- Signature Section - Right aligned -->
                <div class="signature-section">
                    <div class="signature-right">
                        <div class="signature-date">Tanah Datar, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $generatedAt->translatedFormat('F Y') }}</div>
                        <div style="height:50pt;">&nbsp;</div>
                        <div class="signature-title">Pengurus LPTQ &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>
                        <div>Kabupaten Tanah Datar</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- html2canvas only -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        const categoriesData = {!! json_encode(collect($categoriesData)->map(fn($c) => [
            'name' => $c['category']->name,
            'branch' => $c['category']->branch,
        ])->values()) !!};
        const isSingleMode = {{ $singleMode ? 'true' : 'false' }};

        async function downloadCategoryImage(index) {
            const overlay = document.getElementById('loadingOverlay');
            const categoryLabel = document.getElementById('loadingCategory');
            const category = categoriesData[index];

            overlay.classList.add('active');
            categoryLabel.textContent = `${category.branch} - ${category.name}`;

            try {
                const element = document.getElementById('categoryPdf' + index);

                // Get actual element dimensions
                const scale = 2;
                const width = element.scrollWidth || element.offsetWidth;
                const height = element.scrollHeight;

                const canvas = await html2canvas(element, {
                    scale: scale,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    imageTimeout: 0,
                    width: width,
                    height: height,
                    windowWidth: width,
                    windowHeight: height,
                    onclone: function(clonedDoc) {
                        const clonedElement = clonedDoc.getElementById('categoryPdf' + index);
                        if (clonedElement) {
                            clonedElement.style.height = 'auto';
                            clonedElement.style.minHeight = 'auto';
                            clonedElement.style.pageBreakAfter = 'auto';
                            clonedElement.style.pageBreakInside = 'avoid';
                        }
                    }
                });

                // Download as PNG image
                const link = document.createElement('a');
                link.download = 'Daftar_Peserta_' + category.branch.replace(/\s+/g, '_') + '_' + category.name.replace(/\s+/g, '_') + '.png';
                link.href = canvas.toDataURL('image/png', 1.0);
                link.click();

            } catch (error) {
                console.error('Image generation error:', error);
                alert('Gagal generate gambar: ' + error.message);
            } finally {
                overlay.classList.remove('active');
            }
        }

        // Auto-download when only one category and NOT in single mode (i.e. not accessed via /pdf/{id})
        if (categoriesData.length === 1 && !isSingleMode) {
            window.onload = function() {
                setTimeout(function() {
                    downloadCategoryImage(0);
                }, 500);
            };
        }
    </script>
</body>
</html>
