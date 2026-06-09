<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rangkaian Kegiatan MTQ Kabupaten Tanah Datar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
            color: #1e293b;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            padding: 15pt;
        }
        .pdf-wrapper {
            max-width: 210mm;
            margin: 50pt auto 0;
            background: white;
            border-radius: 20pt;
            box-shadow: 0 20pt 60pt rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .cover {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            color: white;
            padding: 35pt 25pt;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .cover::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm1-11V10H18v-2h11v2h9v4H18v2h11v4h2V23H18v-2h9v-4h9v-2H19v-4h18z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .cover-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12pt;
            margin-bottom: 20pt;
        }
        .cover-logo {
            width: 55pt;
            height: 55pt;
            object-fit: contain;
        }
        .cover-logo-lg { width: 75pt; height: 75pt; }
        .cover-badge {
            display: inline-block;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #1e293b;
            padding: 5pt 14pt;
            border-radius: 20pt;
            font-size: 8pt;
            font-weight: 600;
            margin-bottom: 15pt;
        }
        .cover h1 {
            font-family: 'Playfair Display', serif;
            font-size: 22pt;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 8pt;
        }
        .cover h2 { font-size: 11pt; opacity: 0.9; margin-bottom: 10pt; }
        .cover-location {
            display: inline-flex;
            align-items: center;
            gap: 6pt;
            background: rgba(255,255,255,0.1);
            padding: 6pt 14pt;
            border-radius: 20pt;
            font-size: 9pt;
            backdrop-filter: blur(10px);
        }
        /* Unified Table */
        .schedule-section { padding: 15pt 20pt 20pt; }
        .section-header {
            display: flex;
            align-items: center;
            gap: 12pt;
            margin-bottom: 12pt;
            padding-bottom: 10pt;
            border-bottom: 2px solid #e2e8f0;
        }
        .section-icon { width: 40pt; height: 40pt; object-fit: contain; }
        .section-title { flex: 1; }
        .section-title h3 { font-size: 16pt; color: #1e3a5f; }
        .section-title p { font-size: 8pt; color: #64748b; margin-top: 2pt; }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12pt;
            overflow: hidden;
            box-shadow: 0 4pt 15pt rgba(0,0,0,0.06);
            font-size: 10pt;
        }
        thead th {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: white;
            padding: 10pt 6pt;
            text-align: center;
            font-weight: 600;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }
        thead th:first-child { border-radius: 12pt 0 0 0; }
        thead th:last-child { border-radius: 0 12pt 0 0; }
        td {
            padding: 8pt 6pt;
            border-bottom: 1px dashed #f1f5f9;
            vertical-align: top;
            font-size: 9pt;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f8fafc; }
        /* Merged date/time cells */
        .merged { background: #f0fdfa !important; text-align: center; vertical-align: middle; }
        .cell-date { font-weight: 600; color: #0891b2; font-size: 10pt; }
        .cell-date-sub { font-size: 8pt; color: #64748b; font-weight: 400; }
        .cell-time { font-size: 9pt; color: #475569; }
        .cell-time-end { font-size: 7pt; color: #94a3b8; }
        /* Type badges */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4pt;
            padding: 4pt 10pt;
            border-radius: 12pt;
            font-size: 9pt;
            font-weight: 700;
            line-height: 1.2;
        }
        .type-kegiatan {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
        .type-penampilan {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1d4ed8;
        }
        .type-icon {
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
            color: inherit;
        }
        .type-icon svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }
        /* Activity content */
        .activity-name { font-weight: 600; color: #1e293b; font-size: 10pt; margin-bottom: 3pt; }
        .activity-notes { font-size: 8pt; color: #64748b; font-style: italic; }
        /* Participant content */
        .gol-name { font-weight: 600; color: #0f766e; font-size: 9pt; }
        .gol-branch { font-size: 7pt; color: #64748b; margin-top: 1pt; }
        .badge-district {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 1pt 4pt;
            font-size: 6pt;
            border-radius: 8pt;
            margin-top: 2pt;
        }
        .session-name { font-size: 8pt; color: #64748b; background: #f1f5f9; padding: 2pt 6pt; border-radius: 6pt; }
        /* Lot badges */
        .lot-badges { display: flex; flex-wrap: wrap; gap: 2pt; }
        .lot-badge {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            padding: 2pt 5pt;
            font-weight: 600;
            font-size: 7pt;
            border: 0.5pt solid #fcd34d;
            border-radius: 4pt;
        }
        .lot-count { font-size: 7pt; color: #94a3b8; margin-top: 3pt; }
        /* Location */
        .location-name { font-weight: 500; color: #374151; font-size: 8pt; }
        .location-url { color: #0891b2; font-size: 7pt; word-break: break-all; }
        /* Footer */
        .pdf-footer {
            background: linear-gradient(135deg, #1e3a5f, #0f172a);
            color: white;
            padding: 15pt 20pt;
            text-align: center;
            font-size: 8pt;
        }
        .footer-brand { font-weight: 600; margin-bottom: 3pt; }
        .footer-text { opacity: 0.8; }
        /* Column widths */
        .col-no { width: 30pt; }
        .col-date { width: 75pt; }
        .col-time { width: 70pt; }
        .col-content { }
        /* Download button - hidden */
        .download-section { display: none; }
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            color: white;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
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
            <span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
            </span>
            <span>Download PDF</span>
        </button>
    </div>

    <div class="pdf-wrapper" id="pdfContent">
        <!-- Cover -->
        <div class="cover">
            <div class="cover-logos">
                <img src="/images/logo-kabupaten.webp" alt="" class="cover-logo">
                <img src="/images/favicon.webp" alt="" class="cover-logo">
                <img src="/images/logo-lptq.webp" alt="" class="cover-logo">
                <img src="/images/emtq-resmi.webp" alt="" class="cover-logo cover-logo-lg">
            </div>
            <div class="cover-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:4pt;">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l8-4v18"></path>
                    <path d="M19 21V11l-6-4"></path>
                </svg>
                Dokumen Resmi MTQ
            </div>
            <h1>Rangkaian Kegiatan MTQ &<br>Jadwal Penampilan Peserta</h1>
            <h2>{{ $eventTitle }}</h2>
            <div class="cover-location">
                <span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                </span>
                <span>{{ $eventLocation }}</span>
            </div>
        </div>

        <!-- Info Cards -->
        <div style="padding: 15pt 20pt; display: flex; gap: 10pt; flex-wrap: wrap;">
<div style="flex: 1; min-width: 150pt; background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 12pt; border-radius: 10pt; text-align: center;">
                <div style="font-size: 9pt; color: #92400e; font-weight: 600;">KATEGORI LOMBA</div>
                <div style="font-size: 18pt; font-weight: 700; color: #92400e;">{{ $totalCategories }}</div>
            </div>
            <div style="flex: 1; min-width: 150pt; background: linear-gradient(135deg, #dbeafe, #bfdbfe); padding: 12pt; border-radius: 10pt; text-align: center;">
                <div style="font-size: 9pt; color: #1d4ed8; font-weight: 600;">TOTAL NOMOR LOT</div>
                <div style="font-size: 18pt; font-weight: 700; color: #1d4ed8;">{{ $totalScheduledLots }}</div>
            </div>
            <div style="flex: 1; min-width: 150pt; background: linear-gradient(135deg, #d1fae5, #a7f3d0); padding: 12pt; border-radius: 10pt; text-align: center;">
                <div style="font-size: 9pt; color: #065f46; font-weight: 600;">PESERTA TERVERIFIKASI</div>
                <div style="font-size: 18pt; font-weight: 700; color: #065f46;">{{ $totalVerifiedParticipants }}</div>
            </div>
            <div style="flex: 1; min-width: 150pt; background: linear-gradient(135deg, #f3e8ff, #e9d5ff); padding: 12pt; border-radius: 10pt; text-align: center;">
                <div style="font-size: 9pt; color: #6b21a8; font-weight: 600;">ACARA</div>
                <div style="font-size: 11pt; font-weight: 700; color: #6b21a8;">{{ count($eventSchedule) + array_sum(array_map(function($g) { return count($g['categories'] ?? []); }, $dateGroups)) }}</div>
            </div>
        </div>

        <!-- Unified Schedule Table -->
        <div class="schedule-section">
            <div class="section-header">
                <img src="/images/favicon.webp" alt="" class="section-icon">
                <div class="section-title">
                    <h3>Jadwal Lengkap MTQ</h3>
                    <p>Digabungkan dalam satu timeline berdasarkan tanggal dan waktu</p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-date">Tanggal</th>
                        <th class="col-time">Waktu</th>
                        <th class="col-content">Detail Kegiatan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $allEvents = [];

                    // Add event schedule events
                    foreach ($eventSchedule as $event) {
                        $allEvents[] = [
                            'type' => 'kegiatan',
                            'date' => $event['date'] ?? null,
                            'time' => $event['time'] ?? null,
                            'name' => $event['activity'] ?? '-',
                            'notes' => $event['notes'] ?? null,
                            'golongan' => null,
                            'sesi' => null,
                            'lots' => [],
                            'location' => null,
                        ];
                    }

                    // Add participant schedule
                    foreach ($dateGroups as $group) {
                        foreach ($group['categories'] as $cat) {
                            $allEvents[] = [
                                'type' => 'penampilan',
                                'date' => $group['date'] ?? null,
                                'time' => ($group['time'] ?? '') . ($group['end_time'] ? ' - ' . $group['end_time'] : ''),
                                'name' => $cat['category_name'] ?? '-',
                                'notes' => $cat['category_branch'] ?? null,
                                'golongan' => $cat['category_name'] ?? null,
                                'sesi' => $cat['session_name'] ?? '-',
                                'lots' => $cat['lot_badges'] ?? [],
                                'lot_count' => $cat['lot_count'] ?? 0,
                                'is_district' => $cat['is_lot_per_district'] ?? false,
                                'location' => $cat['location_name'] ?? null,
                                'location_url' => $cat['location_map_url'] ?? null,
                            ];
                        }
                    }

                    // Helper function to normalize Indonesian date format
                    $normalizeDate = function($date) {
                        if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i', $date, $matches)) {
                            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                            $monthNames = ['januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04', 'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08', 'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12'];
                            $month = strtolower($matches[2]);
                            $month = $monthNames[$month] ?? '01';
                            $year = $matches[3];
                            return "{$year}-{$month}-{$day}";
                        }
                        return $date;
                    };

                    // Sort by date then time
                    usort($allEvents, function($a, $b) use ($normalizeDate) {
                        $dateA = $a['date'] ?? '';
                        $dateB = $b['date'] ?? '';

                        // Handle null dates - put at end
                        if (empty($dateA) && empty($dateB)) return 0;
                        if (empty($dateA)) return 1;
                        if (empty($dateB)) return -1;

                        // Normalize Indonesian date format first (e.g., "19 Juni 2026" → "2026-06-19")
                        $dateANorm = $normalizeDate($dateA);
                        $dateBNorm = $normalizeDate($dateB);

                        // If dates are different, compare by normalized date
                        if ($dateANorm !== $dateBNorm) {
                            return strcmp($dateANorm, $dateBNorm);
                        }

                        // Same date - compare by time
                        $timeA = $a['time'] ?? '';
                        $timeB = $b['time'] ?? '';

                        // Normalize time to HHMM format for comparison
                        $timeANum = 9999;
                        $timeBNum = 9999;

                        if (preg_match('/^(\d{2})[.:](\d{2})/', $timeA, $matchA)) {
                            $timeANum = (int)($matchA[1] . $matchA[2]);
                        }
                        if (preg_match('/^(\d{2})[.:](\d{2})/', $timeB, $matchB)) {
                            $timeBNum = (int)($matchB[1] . $matchB[2]);
                        }

                        // Handle empty or invalid times
                        if ($timeANum === 9999 && $timeBNum === 9999) return 0;
                        if ($timeANum === 9999) return 1;
                        if ($timeBNum === 9999) return -1;

                        return $timeANum - $timeBNum;
                    });

                    // Calculate rowspans
                    $mergedGroups = [];
                    $prevKey = null;
                    $currentCount = 0;
                    $rowspans = [];
                    foreach ($allEvents as $i => $event) {
                        $key = ($event['date'] ?? 'nodate') . '|' . ($event['time'] ?? 'notime');
                        if ($key !== $prevKey) {
                            if ($prevKey !== null) {
                                $rowspans[$i - $currentCount] = $currentCount;
                            }
                            $prevKey = $key;
                            $currentCount = 1;
                        } else {
                            $currentCount++;
                        }
                        // Last group
                        if ($i === count($allEvents) - 1) {
                            $rowspans[$i - $currentCount + 1] = $currentCount;
                        }
                    }
                    $currentRowspan = 0;
                    $shownKeys = [];
                    $rowNumber = 1;
                    @endphp

                    @foreach ($allEvents as $index => $event)
                    @php
                        $key = ($event['date'] ?? 'nodate') . '|' . ($event['time'] ?? 'notime');
                        $isFirst = !isset($shownKeys[$key]);
                        $shownKeys[$key] = true;
                        $currentRowspan = $rowspans[$index] ?? 1;
                    @endphp
                    <tr>
                        @if ($isFirst)
                        <td class="col-no merged" rowspan="{{ $currentRowspan }}">
                            <div style="font-size:12pt;font-weight:700;color:#0891b2;">{{ $rowNumber }}</div>
                        </td>
                        <td class="col-date merged" rowspan="{{ $currentRowspan }}">
                            @if ($event['date'])
                                <div class="cell-date">{{ \Carbon\Carbon::parse($event['date'])->translatedFormat('l') }}</div>
                                <div class="cell-date-sub">{{ \Carbon\Carbon::parse($event['date'])->translatedFormat('d M Y') }}</div>
                            @else
                                <div class="cell-date">-</div>
                            @endif
                        </td>
                        <td class="col-time merged" rowspan="{{ $currentRowspan }}">
                            <div class="cell-time">{{ $event['time'] ?: '-' }}</div>
                        </td>
                        @endif

                        <td class="col-content">
                            {{-- Kegiatan MTQ --}}
                            @if ($event['type'] === 'kegiatan')
                                <div style="display:inline-flex;align-items:center;gap:4pt;background:#fef3c7;color:#92400e;padding:3pt 8pt;border-radius:10pt;font-size:9pt;font-weight:700;margin-bottom:4pt;">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="2" fill="#fef3c7"/></svg>
                                    KEGIATAN
                                </div>
                                <div class="activity-name" style="margin-top:6pt;">{{ $event['name'] }}</div>
                                @if ($event['notes'])
                                    <div class="activity-notes">{{ $event['notes'] }}</div>
                                @endif

                            {{-- Penampilan Peserta --}}
                            @else
                                <div style="display:inline-flex;align-items:center;gap:4pt;background:#dbeafe;color:#1d4ed8;padding:3pt 8pt;border-radius:10pt;font-size:9pt;font-weight:700;margin-bottom:4pt;">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                                    PENAMPILAN
                                </div>
                                <div class="gol-name" style="margin-top:6pt;">
                                    @if ($event['notes'])
                                        {{ $event['notes'] }} -
                                    @endif
                                    {{ $event['name'] }}
                                </div>
                                @if ($event['is_district'])
                                    <div class="badge-district">1 Kecamatan = 1 Lot</div>
                                @endif
                                <div style="margin-top:6pt;font-size:8pt;color:#64748b;">Sesi: {{ $event['sesi'] }}</div>
                                @if (count($event['lots']) > 0)
                                    <div class="lot-badges" style="margin-top:5pt;">
                                        @foreach ($event['lots'] as $lot)
                                        <span class="lot-badge">{{ $lot }}</span>
                                        @endforeach
                                    </div>
                                    <div class="lot-count">{{ $event['lot_count'] }} nomor lot</div>
                                @endif
                                @if ($event['location'])
                                    <div style="margin-top:8pt;padding:6pt;background:#f8fafc;border-radius:6pt;">
                                        <div style="font-size:9pt;font-weight:600;color:#374151;">
                                           <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                           {{ $event['location'] }}
                                        </div>
                                        @if ($event['location_url'])
                                            <div style="font-size:7pt;color:#64748b;margin-top:3pt;word-break:break-all;">
                                               <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                                               {{ $event['location_url'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @php if ($isFirst) $rowNumber++; @endphp
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="pdf-footer">
            <div class="footer-brand">e-MTQ Kabupaten Tanah Datar</div>
            <div class="footer-text">Dokumen resmi | Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB</div>
        </div>
    </div>

    
    <!-- jsPDF and html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        async function downloadPDF() {
            const btn = document.getElementById('downloadPdfBtn');
            const overlay = document.getElementById('loadingOverlay');

            // Disable button and show loading
            btn.disabled = true;
            btn.innerHTML = '<span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></span><span>Processing...</span>';
            overlay.style.display = 'flex';

            try {
                const { jsPDF } = window.jspdf;

                // Get the content element
                const element = document.getElementById('pdfContent');

                // Configure html2canvas options for better quality
                const canvas = await html2canvas(element, {
                    scale: 2, // Higher scale = better quality
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    imageTimeout: 0, // No timeout for images
                });

                // PDF dimensions (A4 in mm)
                const pdfWidth = 210; // A4 width in mm
                const pdfHeight = 297; // A4 height in mm

                // Calculate page height in canvas pixels
                // 297mm / 210mm * canvas.width = canvas height for one A4 page
                const pageHeightInCanvasPixels = (pdfHeight / pdfWidth) * canvas.width;

                // Create PDF
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                // Total pages needed
                const totalPages = Math.ceil(canvas.height / pageHeightInCanvasPixels);

                // Add each page slice to PDF
                for (let i = 0; i < totalPages; i++) {
                    // Calculate source y position (where to cut in canvas)
                    const sourceY = i * pageHeightInCanvasPixels;

                    // Create a temporary canvas for this page slice
                    const pageCanvas = document.createElement('canvas');
                    pageCanvas.width = canvas.width;
                    pageCanvas.height = pageHeightInCanvasPixels;
                    const ctx = pageCanvas.getContext('2d');

                    // Draw the slice onto temporary canvas
                    ctx.drawImage(
                        canvas,
                        0, sourceY, // source x, y
                        canvas.width, pageHeightInCanvasPixels, // source width, height
                        0, 0, // dest x, y
                        canvas.width, pageHeightInCanvasPixels // dest width, height
                    );

                    // Add page to PDF
                    if (i > 0) {
                        pdf.addPage();
                    }
                    pdf.addImage(
                        pageCanvas.toDataURL('image/jpeg', 0.95),
                        'JPEG',
                        0, 0, // x, y position in mm
                        pdfWidth, // width in mm (full page)
                        pdfHeight // height in mm (full page)
                    );
                }

                // Generate filename with timestamp
                const now = new Date();
                const timestamp = now.getFullYear() +
                    String(now.getMonth() + 1).padStart(2, '0') +
                    String(now.getDate()).padStart(2, '0') + '_' +
                    String(now.getHours()).padStart(2, '0') +
                    String(now.getMinutes()).padStart(2, '0') +
                    String(now.getSeconds()).padStart(2, '0');
                const filename = 'Rangkaian_Kegiatan_MTQ_' + timestamp + '.pdf';

                // Download the PDF
                pdf.save(filename);

                // Success feedback
                btn.innerHTML = '<span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;"><polyline points="20 6 9 17 4 12"></polyline></svg></span><span>Downloaded!</span>';
                setTimeout(() => {
                    btn.innerHTML = '<span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></span><span>Download PDF</span>';
                    btn.disabled = false;
                }, 2000);

            } catch (error) {
                console.error('PDF generation error:', error);
                alert('Gagal generate PDF: ' + error.message);
                btn.innerHTML = '<span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></span><span>Download PDF</span>';
                btn.disabled = false;
            } finally {
                overlay.style.display = 'none';
            }
        }
    </script>
</body>
</html>
