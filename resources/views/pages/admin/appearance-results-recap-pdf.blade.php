<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Penampilan Per Hari - {{ $category->name }} - {{ $sessionName }}</title>
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
            padding: 15pt;
        }
        .page-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            min-height: 297mm;
            position: relative;
            padding-bottom: 80pt;
        }
        /* Header */
        .main-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            color: white;
            padding: 15pt 25pt;
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
        .header-content { position: relative; }
        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8pt;
            margin-bottom: 8pt;
        }
        .header-logo { width: 35pt; height: 35pt; object-fit: contain; }
        .header-logo-lg { width: 45pt; height: 45pt; }
        .header-title {
            font-family: 'Playfair Display', serif;
            font-size: 10pt;
            font-weight: 600;
            margin-bottom: 3pt;
            opacity: 0.9;
        }
        .header-event {
            font-family: 'Playfair Display', serif;
            font-size: 18pt;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 4pt;
        }
        .header-sk {
            font-size: 8pt;
            line-height: 1.4;
            opacity: 0.85;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 6pt;
            margin-top: 6pt;
        }
        /* Document Title Section */
        .doc-title-section {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white;
            padding: 12pt 25pt;
            text-align: center;
            position: relative;
        }
        .doc-title-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='20' cy='20' r='15'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .doc-title-content { position: relative; }
        .doc-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 2pt 12pt;
            border-radius: 12pt;
            font-size: 8pt;
            font-weight: 600;
            margin-bottom: 5pt;
        }
        .doc-category { font-size: 11pt; margin-bottom: 2pt; }
        .doc-title {
            font-family: 'Playfair Display', serif;
            font-size: 16pt;
            font-weight: 700;
            line-height: 1.2;
        }
        /* Info Section */
        .info-section {
            display: flex;
            gap: 0;
            background: #f8fafc;
        }
        .info-left {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%);
            color: white;
            padding: 10pt 15pt;
            flex: 0 0 35%;
            border-right: 3pt solid #fbbf24;
            position: relative;
        }
        .info-left::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M15 0L30 15L15 30L0 15z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .info-left-content { position: relative; }
        .info-label {
            display: inline-block;
            background: #fbbf24;
            color: #1e293b;
            padding: 2pt 10pt;
            border-radius: 10pt;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin-bottom: 4pt;
        }
        .info-location {
            font-family: 'Playfair Display', serif;
            font-size: 12pt;
            font-weight: 700;
            line-height: 1.2;
        }
        .info-right {
            flex: 1;
            padding: 10pt 15pt;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6pt;
        }
        .info-row {
            display: flex;
            align-items: center;
            gap: 8pt;
        }
        .info-icon {
            width: 28pt;
            height: 28pt;
            background: white;
            border-radius: 6pt;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12pt;
            box-shadow: 0 2pt 6pt rgba(0,0,0,0.08);
            flex-shrink: 0;
        }
        .info-text { font-size: 9pt; }
        .info-text-label { color: #64748b; font-size: 7pt; }
        .info-text-value { font-weight: 600; color: #0891b2; font-size: 10pt; }
        /* Summary Cards */
        .summary-section {
            padding: 10pt 25pt;
            display: flex;
            gap: 10pt;
        }
        .summary-card {
            flex: 1;
            padding: 10pt;
            border-radius: 8pt;
            text-align: center;
        }
        .summary-card.day { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }
        .summary-card.lot { background: linear-gradient(135deg, #fef3c7, #fde68a); }
        .summary-card.count { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
        .summary-label { font-size: 9pt; font-weight: 600; margin-bottom: 2pt; }
        .summary-card.day .summary-label { color: #1d4ed8; }
        .summary-card.lot .summary-label { color: #92400e; }
        .summary-card.count .summary-label { color: #065f46; }
        .summary-value { font-size: 16pt; font-weight: 700; }
        .summary-card.day .summary-value { color: #1d4ed8; }
        .summary-card.lot .summary-value { color: #92400e; }
        .summary-card.count .summary-value { color: #065f46; }
        /* Table Section */
        .table-section { padding: 10pt 25pt 20pt 25pt; }
        .participant-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            box-shadow: 0 2pt 8pt rgba(0,0,0,0.06);
            border-radius: 8pt;
            overflow: hidden;
        }
        .participant-table thead th {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: white;
            padding: 8pt 6pt;
            text-align: center;
            font-weight: 600;
            font-size: 9pt;
            text-transform: uppercase;
        }
        .participant-table tbody td {
            padding: 6pt;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .participant-table tbody tr:last-child td { border-bottom: none; }
        .participant-table tbody tr:hover td { background: #f8fafc; }
        /* Column widths */
        .col-foto { width: 35pt; text-align: center; }
        .col-lot { width: 55pt; text-align: center; }
        .col-name { min-width: 120pt; }
        .col-district { width: 80pt; }
        .col-maqra { width: 50pt; text-align: center; }
        .col-nilai { width: 55pt; text-align: center; }
        /* Cell styles */
        .no-cell {
            font-weight: 700;
            color: #0891b2;
            font-size: 11pt;
        }
        .lot-cell {
            font-weight: 700;
            color: #92400e;
            font-size: 11pt;
        }
        .name-cell { font-weight: 600; color: #1e293b; }
        .name-sub { font-size: 7pt; color: #64748b; margin-top: 2pt; }
        .district-cell { font-size: 9pt; color: #475569; }
        .maqra-cell { font-weight: 600; color: #7c3aed; font-size: 9pt; }
        .nilai-cell { font-weight: 700; color: #059669; font-size: 11pt; }
        .ket-cell { font-size: 8pt; }
        /* Photo styles */
        .photo-placeholder {
            width: 28pt;
            height: 35pt;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            border-radius: 4pt;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #94a3b8;
            font-size: 10pt;
        }
        .photo-img {
            width: 28pt;
            height: 35pt;
            object-fit: cover;
            border-radius: 4pt;
            margin: 0 auto;
            display: block;
        }
        /* Gender badge */
        .gender-badge {
            display: inline-block;
            padding: 1pt 5pt;
            border-radius: 4pt;
            font-size: 7pt;
            font-weight: 600;
            text-transform: uppercase;
        }
        .gender-putra { background: #dbeafe; color: #1d4ed8; }
        .gender-putri { background: #fce7f3; color: #9d174d; }
        .gender-none { background: #f1f5f9; color: #94a3b8; }
        /* Empty row style */
        .empty-row td { background: #fefce8; }
        .empty-cell { color: #a3a3a3; font-style: italic; }
        /* Footer */
        .page-footer {
            background: linear-gradient(135deg, #1e3a5f, #0f172a);
            color: white;
            padding: 8pt 25pt;
            text-align: center;
            font-size: 8pt;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
        }
        .footer-brand { font-weight: 600; margin-bottom: 2pt; }
        .footer-text { opacity: 0.8; }
        /* Signature Section */
        .signature-section {
            padding: 20pt 40pt 15pt 60pt;
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
            page-break-inside: avoid;
        }
        .signature-right {
            text-align: right;
            font-size: 11pt;
            line-height: 1.6;
        }
        .signature-date { margin-bottom: 50pt; }
        .signature-title { font-weight: 600; }
        .signature-name {
            font-weight: 600;
            font-size: 12pt;
            color: #1e3a5f;
            margin-top: 45pt;
            text-decoration: underline;
        }
        .signature-role { font-size: 9pt; color: #64748b; }
        /* Print styles */
        @media print {
            body { padding: 0; background: white; }
            .page-container { box-shadow: none; }
            .participant-table tbody tr:hover td { background: transparent; }
        }
        @page {
            size: A4;
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Main Header -->
        <div class="main-header">
            <div class="header-content">
                <div class="header-logos">
                    <img src="/images/logo-kabupaten.webp" alt="" class="header-logo">
                    <img src="/images/favicon.webp" alt="" class="header-logo">
                    <img src="/images/logo-lptq.webp" alt="" class="header-logo">
                    <img src="/images/emtq-resmi.webp" alt="" class="header-logo header-logo-lg">
                </div>
                <div class="header-title">Daftar Peserta Tampil Per Hari</div>
                <div class="header-event">{{ $eventName }}</div>
                <div class="header-sk">
                    Berdasarkan SK LPTQ Kabupaten Tanah Datar<br>
                    <b>Nomor : 02/LPTQ/TD/2026</b><br>
                    Tanggal 26 Mei 2026
                </div>
            </div>
        </div>

        <!-- Document Title -->
        <div class="doc-title-section">
            <div class="doc-title-content">
                <div class="doc-badge">Daftar Peserta</div>
                <div class="doc-category">{{ $category->branch }}</div>
                <div class="doc-title">{{ $category->name }}</div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-left">
                <div class="info-left-content">
                    <div class="info-label">Lokasi Lomba</div>
                    <div class="info-location">{{ $locationName }}</div>
                </div>
            </div>
            <div class="info-right">
                <div class="info-row">
                    <div class="info-icon">&#x1F4C5;</div>
                    <div class="info-text">
                        <div class="info-text-label">Tanggal</div>
                        <div class="info-text-value">{{ $sessionDate ?? 'Belum diatur' }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon">&#x1F551;</div>
                    <div class="info-text">
                        <div class="info-text-label">Waktu</div>
                        <div class="info-text-value">
                            @if ($sessionTime)
                                {{ $sessionTime }} WIB
                                @if ($sessionEndTime)
                                    - {{ $sessionEndTime }} WIB
                                @endif
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-section">
            <div class="summary-card day">
                <div class="summary-label">HARI KE-</div>
                <div class="summary-value">{{ $dayNumber }}</div>
            </div>
            <div class="summary-card lot">
                <div class="summary-label">NOMOR LOT</div>
                <div class="summary-value">{{ $lotRangeLabel }}</div>
            </div>
            <div class="summary-card count">
                <div class="summary-label">TOTAL PESERTA</div>
                <div class="summary-value">{{ $lotCount }}</div>
            </div>
        </div>

        <!-- Participant Table -->
        <div class="table-section">
            <table class="participant-table">
                <thead>
                    <tr>
                        <th class="col-foto">Foto</th>
                        <th class="col-lot">No. Lot</th>
                        <th class="col-name">Nama Peserta</th>
                        <th class="col-district">Kecamatan</th>
                        <th class="col-maqra">Maqra</th>
                        <th class="col-nilai">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($participantData as $data)
                    <tr class="{{ !$data['has_participant'] ? 'empty-row' : '' }}">
                        <td class="col-foto">
                            @if ($data['has_participant'] && $data['photo'])
                                @if (file_exists(public_path('storage/' . $data['photo'])))
                                    <img src="{{ asset('storage/' . $data['photo']) }}" alt="" class="photo-img">
                                @else
                                    <div class="photo-placeholder">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>
                                @endif
                            @else
                                <div class="photo-placeholder">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                            @endif
                        </td>
                        <td class="col-lot">
                            <span class="lot-cell">{{ $data['lot'] }}</span>
                        </td>
                        <td class="col-name">
                            @if ($data['has_participant'] && $data['name'])
                                <div class="name-cell">{{ $data['name'] }}</div>
                                @if ($data['gender'])
                                    <div class="name-sub">
                                        <span class="gender-badge gender-{{ $data['gender'] }}">
                                            {{ $data['gender'] === 'putra' ? 'L' : 'P' }}
                                        </span>
                                    </div>
                                @endif
                            @else
                                <span class="empty-cell">-</span>
                            @endif
                        </td>
                        <td class="col-district">
                            <span class="district-cell">{{ $data['district'] ?? '' }}</span>
                        </td>
                        <td class="col-maqra">
                            <span class="maqra-cell">{{ $data['maqra'] ?? '' }}</span>
                        </td>
                        <td class="col-nilai">
                            <span class="nilai-cell">{{ $data['total_nilai'] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-right">
                <div class="signature-date">
                    Tanah Datar, {{ $generatedAt->translatedFormat('d F Y') }}
                </div>
                <div style="height: 50pt;">&nbsp;</div>
                <div class="signature-title">Pengurus LPTQ</div>
                <div>Kabupaten Tanah Datar</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="page-footer">
            <div class="footer-brand">e-MTQ {{ $eventName }} | {{ $category->name }} | {{ $sessionName }} | Dicetak: {{ $generatedAt->translatedFormat('d F Y H:i') }} WIB</div>
        </div>
    </div>
</body>
</html>
