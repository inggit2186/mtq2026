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
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            padding: 15pt;
        }
        .pdf-wrapper {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            border-radius: 20pt;
            box-shadow: 0 20pt 60pt rgba(0,0,0,0.1);
            overflow: hidden;
        }
        /* Category Section */
        .category-section {
            page-break-after: always;
            min-height: 297mm;
            position: relative;
            padding-bottom: 50pt;
        }
        .category-section:last-child {
            page-break-after: auto;
        }
        /* Main Header */
        .main-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            color: white;
            padding: 25pt 20pt;
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
            gap: 10pt;
            margin-bottom: 15pt;
        }
        .main-logo { width: 50pt; height: 50pt; object-fit: contain; }
        .main-logo-lg { width: 65pt; height: 65pt; }
        .main-title { font-family: 'Playfair Display', serif; font-size: 18pt; font-weight: 600; margin-bottom: 6pt; }
        .main-event-title { font-family: 'Playfair Display', serif; font-size: 26pt; font-weight: 700; line-height: 1.2; margin-bottom: 12pt; }
        .main-sk {
            font-size: 12pt;
            line-height: 1.5;
            opacity: 0.9;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 10pt;
            margin-top: 10pt;
        }
        /* Category Header */
        .category-header {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white;
            padding: 18pt 20pt;
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
            padding: 4pt 14pt;
            border-radius: 14pt;
            font-size: 9pt;
            font-weight: 600;
            margin-bottom: 8pt;
        }
        .category-branch { font-size: 12pt; margin-bottom: 4pt; }
        .category-title { font-family: 'Playfair Display', serif; font-size: 20pt; font-weight: 700; line-height: 1.2; }
        /* Info Cards */
        .info-cards { padding: 12pt 20pt; display: flex; gap: 10pt; flex-wrap: wrap; }
        .info-card {
            flex: 1;
            min-width: 90pt;
            padding: 10pt;
            border-radius: 10pt;
            text-align: center;
        }
        .info-card.putra { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
        .info-card.putri { background: linear-gradient(135deg, #fce7f3, #fbcfe8); }
        .info-card.total { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
        .info-card-label { font-size: 11pt; font-weight: 600; margin-bottom: 3pt; }
        .info-card.putra .info-card-label { color: #1d4ed8; }
        .info-card.putri .info-card-label { color: #9d174d; }
        .info-card.total .info-card-label { color: #065f46; }
        .info-card-value { font-size: 15pt; font-weight: 700; }
        .info-card.putra .info-card-value { color: #1d4ed8; }
        .info-card.putri .info-card-value { color: #9d174d; }
        .info-card.total .info-card-value { color: #065f46; }
        /* Table */
        .table-section { padding: 10pt 20pt; }
        .participant-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12pt;
            box-shadow: 0 4pt 12pt rgba(0,0,0,0.06);
            border-radius: 10pt;
            overflow: hidden;
        }
        .participant-table thead th {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: white;
            padding: 8pt 6pt;
            text-align: center;
            font-weight: 600;
            font-size: 11pt;
            text-transform: uppercase;
        }
        .participant-table tbody td {
            padding: 7pt 6pt;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .participant-table tbody tr:last-child td { border-bottom: none; }
        .participant-table tbody tr:hover td { background: #f8fafc; }
        /* Photo cell */
        .photo-cell { width: 35pt; text-align: center; }
        .photo-placeholder {
            width: 70pt;
            height: 78pt;
            background: #f1f5f9;
            border-radius: 6pt;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #94a3b8;
            font-size: 12pt;
        }
        .photo-img {
            width: 70pt;
            height: 78pt;
            object-fit: cover;
            border-radius: 6pt;
        }
        /* Name cell - normal size */
        .name-text { font-weight: 600; color: #1e293b; }
        .name-sub { font-size: 10pt; color: #64748b; margin-top: 2pt; }
        /* District cell */
        .district-text { font-size: 12pt; }
        /* Age badge */
        .age-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 3pt 8pt;
            border-radius: 8pt;
            font-size: 12pt;
            font-weight: 600;
        }
        /* Category spacing */
        .category-section {
            margin-bottom: 20pt;
            page-break-after: always;
        }
        .category-section:last-of-type {
            margin-bottom: 0;
            page-break-after: auto;
        }
        /* Last category includes footer and signature - keep together */
        .category-section.is-last {
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        /* Footer - single at end */
        .pdf-footer {
            background: linear-gradient(135deg, #1e3a5f, #0f172a);
            color: white;
            padding: 8pt 20pt;
            text-align: center;
            font-size: 11pt;
            page-break-inside: avoid;
        }
        .footer-brand { font-weight: 600; margin-bottom: 2pt; }
        .footer-text { opacity: 0.8; }
        /* Column widths */
        .col-no { width: 35pt; }
        .col-photo { width: 80pt; }
        .col-name { width: 60%; }
        .col-district { width: 20%; }
        .col-age { width: 10%; }
        /* Signature - Only on last page */
        .signature-final {
            page-break-inside: avoid;
            page-break-before: auto;
            padding: 40pt;
            min-height: 150pt;
        }
        .signature-right {
            text-align: right;
            font-size: 14pt;
            line-height: 1.8;
        }
        .signature-date { margin-bottom: 60pt; }
        .signature-title { font-weight: 600; }
        /* Download section hidden */
        .download-section { display: none; }
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            color: white;
        }
        .loading-spinner {
            width: 50px; height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-text { font-size: 14pt; }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-spinner"></div>
        <div class="loading-text">Generating PDF...</div>
    </div>

    <!-- Hidden Download Button -->
    <div class="download-section">
        <button id="downloadPdfBtn" class="download-btn" onclick="downloadPDF()">
            <span>Download PDF</span>
        </button>
    </div>

    <div id="pdfContent">
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

        @foreach ($categoriesData as $categoryIndex => $categoryData)
        @php
            $isLastCategory = ($categoryIndex === count($categoriesData) - 1);
        @endphp
        <div class="category-section{{ $isLastCategory ? ' is-last' : '' }}">
            <!-- Category Header -->
            <div class="category-header">
                <div class="category-header-content">
                    <div class="category-badge">Daftar Peserta</div>
                    <div class="category-branch">{{ $categoryData['category']->branch }}</div>
                    <div class="category-title">{{ $categoryData['category']->name }}</div>
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
                            <th class="col-age">Umur (per 1 Juli)</th>
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
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                            <td style="text-align:center;font-size:12pt;">
                                @if ($participant['age'])
                                    {{ $participant['age']['years'] }} Tahun
                                    <span style="color:#64748b;font-size:9pt;">{{ $participant['age']['months'] }} Bln {{ $participant['age']['days'] }} Hr</span>
                                @else
                                    <span style="color:#94a3b8;font-size:12pt;">-</span>
                                @endif
                            </td>
                        </tr>
                        @php $rowNumber++; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer dan Tanda Tangan - hanya di kategori terakhir --}}
            @if ($isLastCategory)
                <!-- Single Footer at end -->
                <div class="pdf-footer">
                    <div class="footer-brand">e-MTQ {{ $eventTitle }} | Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }} WIB</div>
                </div>

                <!-- Signature - Only on last page -->
                <div class="signature-final">
                    <div class="signature-right">
                        <div class="signature-date">Tanah Datar, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $generatedAt->translatedFormat('F Y') }}</div>
                        <div style="height:50pt;">&nbsp;</div>
                        <div class="signature-title">Pengurus LPTQ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                        <div>Kabupaten Tanah Datar</div>
                    </div>
                </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- jsPDF and html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        async function downloadPDF() {
            const btn = document.getElementById('downloadPdfBtn');
            const overlay = document.getElementById('loadingOverlay');

            btn.disabled = true;
            overlay.style.display = 'flex';

            try {
                const { jsPDF } = window.jspdf;
                const element = document.getElementById('pdfContent');

                const canvas = await html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    imageTimeout: 0,
                });

                const pdfWidth = 210;
                const pdfHeight = 297;
                const pageHeightInCanvasPixels = (pdfHeight / pdfWidth) * canvas.width;

                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                const totalPages = Math.ceil(canvas.height / pageHeightInCanvasPixels);

                for (let i = 0; i < totalPages; i++) {
                    const sourceY = i * pageHeightInCanvasPixels;

                    const pageCanvas = document.createElement('canvas');
                    pageCanvas.width = canvas.width;
                    pageCanvas.height = pageHeightInCanvasPixels;
                    const ctx = pageCanvas.getContext('2d');

                    ctx.drawImage(canvas, 0, sourceY, canvas.width, pageHeightInCanvasPixels, 0, 0, canvas.width, pageHeightInCanvasPixels);

                    if (i > 0) {
                        pdf.addPage();
                    }
                    pdf.addImage(pageCanvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, pdfWidth, pdfHeight);
                }

                const now = new Date();
                const timestamp = now.getFullYear() +
                    String(now.getMonth() + 1).padStart(2, '0') +
                    String(now.getDate()).padStart(2, '0') + '_' +
                    String(now.getHours()).padStart(2, '0') +
                    String(now.getMinutes()).padStart(2, '0') +
                    String(now.getSeconds()).padStart(2, '0');
                const filename = 'Daftar_Peserta_MTQ_' + timestamp + '.pdf';

                pdf.save(filename);

            } catch (error) {
                console.error('PDF generation error:', error);
                alert('Gagal generate PDF: ' + error.message);
            } finally {
                overlay.style.display = 'none';
            }
        }

        // Auto download on page load
        /* window.onload = function() {
            setTimeout(downloadPDF, 500);
        }; */
    </script>
</body>
</html>