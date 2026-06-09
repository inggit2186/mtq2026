<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Penampilan Peserta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            font-size: 12pt;
            color: #1e293b;
            background: #f1f5f9;
            min-height: 100vh;
            padding: 20pt;
        }
        .preview-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 12pt 20pt;
            text-align: center;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10pt;
        }
        .preview-banner .icon {
            font-size: 18pt;
        }
        .pdf-container {
            background: white;
            border-radius: 16pt;
            box-shadow: 0 10pt 40pt rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 60pt;
        }
        .pdf-header {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 50%, #06b6d4 100%);
            color: white;
            padding: 30pt;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .pdf-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            pointer-events: none;
        }
        .pdf-header-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70pt;
            height: 70pt;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            margin-bottom: 15pt;
            font-size: 32pt;
            backdrop-filter: blur(10px);
        }
        .pdf-header h1 {
            font-size: 24pt;
            font-weight: 700;
            letter-spacing: 2pt;
            margin-bottom: 8pt;
            text-shadow: 0 2pt 4pt rgba(0,0,0,0.2);
        }
        .pdf-header-subtitle {
            font-size: 11pt;
            opacity: 0.9;
            font-weight: 400;
        }
        .pdf-header-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4pt 12pt;
            border-radius: 20pt;
            font-size: 9pt;
            margin-top: 12pt;
            backdrop-filter: blur(5px);
        }
        .pdf-info {
            background: linear-gradient(135deg, #f0fdfa, #ecfeff);
            padding: 15pt 25pt;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10pt;
            border-bottom: 1px solid #e2e8f0;
        }
        .pdf-info-item {
            display: flex;
            align-items: center;
            gap: 8pt;
            font-size: 10pt;
            color: #475569;
        }
        .pdf-info-icon {
            width: 32pt;
            height: 32pt;
            background: white;
            border-radius: 8pt;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14pt;
            box-shadow: 0 2pt 8pt rgba(0,0,0,0.08);
        }
        .pdf-info-value {
            font-weight: 600;
            color: #0891b2;
            font-size: 12pt;
        }
        .pdf-table-container {
            padding: 20pt;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 10pt;
            border-radius: 12pt;
            overflow: hidden;
            box-shadow: 0 4pt 20pt rgba(0,0,0,0.08);
        }
        thead th {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            padding: 14pt 10pt;
            text-align: center;
            font-weight: 600;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 1pt;
        }
        thead th:first-child { border-radius: 12pt 0 0 0; }
        thead th:last-child { border-radius: 0 12pt 0 0; }
        td {
            padding: 10pt;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            font-size: 10pt;
        }
        tbody tr:hover td { background-color: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:first-child td:first-child { border-radius: 0 0 0 12pt; }
        tbody tr:last-child td:last-child { border-radius: 0 0 12pt 0; }

        .merged-cell {
            background-color: #f0fdfa !important;
            text-align: center;
            vertical-align: middle;
        }
        .cell-no {
            font-weight: 700;
            color: #0891b2;
            font-size: 14pt;
        }
        .cell-date {
            font-weight: 600;
            color: #0891b2;
            font-size: 11pt;
        }
        .cell-date-sub {
            font-size: 9pt;
            color: #64748b;
            margin-top: 2pt;
        }
        .cell-time {
            font-size: 10pt;
            color: #475569;
        }
        .cell-time-end { font-size: 8pt; color: #94a3b8; }
        .cell-gol-name { font-weight: 600; color: #0f766e; font-size: 10pt; }
        .cell-gol-branch { font-size: 8pt; color: #64748b; margin-top: 2pt; }
        .badge-district {
            display: inline-flex;
            align-items: center;
            gap: 4pt;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            color: #0369a1;
            padding: 3pt 8pt;
            font-size: 7pt;
            border-radius: 12pt;
            margin-top: 4pt;
            font-weight: 500;
        }
        .badge-icon { font-size: 10pt; }
        .cell-sesi {
            font-size: 9pt;
            color: #64748b;
            background: #f8fafc;
            padding: 4pt 8pt;
            border-radius: 6pt;
        }
        .cell-lot { font-size: 10pt; }
        .lot-badge {
            display: inline-block;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            padding: 4pt 8pt;
            font-weight: 600;
            font-size: 9pt;
            border: 1px solid #fcd34d;
            border-radius: 6pt;
            margin: 2pt;
        }
        .lot-count {
            font-size: 8pt;
            color: #94a3b8;
            margin-top: 6pt;
        }
        .cell-lok-name { font-weight: 600; color: #374151; font-size: 9pt; }
        .cell-lok-link { color: #0891b2; font-size: 8pt; word-break: break-all; margin-top: 2pt; }
        .no-lot { color: #94a3b8; font-style: italic; font-size: 9pt; }

        .pdf-footer {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 15pt 25pt;
            text-align: center;
            border-top: 1px dashed #e2e8f0;
        }
        .pdf-footer-brand {
            font-weight: 600;
            color: #0891b2;
        }
        .pdf-footer-text {
            font-size: 8pt;
            color: #94a3b8;
            margin-top: 4pt;
        }

        /* Column widths */
        .col-no { width: 40pt; }
        .col-date { width: 90pt; }
        .col-time { width: 80pt; }
        .col-gol { width: 130pt; }
        .col-sesi { width: 80pt; }
        .col-lot { }
        .col-lok { width: 110pt; }

        /* Download button */
        .download-section {
            position: fixed;
            bottom: 20pt;
            right: 20pt;
            z-index: 1000;
        }
        .download-btn {
            display: flex;
            align-items: center;
            gap: 8pt;
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            padding: 12pt 20pt;
            border-radius: 50pt;
            font-size: 11pt;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 4pt 20pt rgba(8, 145, 178, 0.4);
            transition: all 0.3s ease;
        }
        .download-btn:hover {
            transform: translateY(-2pt);
            box-shadow: 0 6pt 25pt rgba(8, 145, 178, 0.5);
        }
        .download-btn .icon { font-size: 16pt; }
    </style>
</head>
<body>
    <!-- Preview Banner -->
    <div class="preview-banner">
        <span class="icon">&#x1F4AC;</span>
        <span>PREVIEW MODE - Auto-download dinonaktifkan untuk melihat hasil desain</span>
    </div>

    <div class="pdf-container">
        <!-- Header -->
        <div class="pdf-header">
            <div class="pdf-header-icon">&#x1F549;</div>
            <h1>JADWAL PENAMPILAN PESERTA</h1>
            <div class="pdf-header-subtitle">{{ $eventName ?? 'MTQ Kabupaten Tanah Datar 2026' }}</div>
            <div class="pdf-header-badge">&#x1F4C5; Diurutkan berdasarkan tanggal dan waktu</div>
        </div>

        <!-- Info Bar -->
        <div class="pdf-info">
            <div class="pdf-info-item">
                <div class="pdf-info-icon">&#x1F4C5;</div>
                <div>
                    <div style="font-size: 8pt; color: #64748b;">Tanggal Cetak</div>
                    <div>{{ now()->translatedFormat('d F Y') }} | {{ now()->format('H:i') }} WIB</div>
                </div>
            </div>
            <div class="pdf-info-item">
                <div class="pdf-info-icon">&#x1F3DB;</div>
                <div>
                    <div style="font-size: 8pt; color: #64748b;">Total Golongan</div>
                    <div class="pdf-info-value">{{ $totalCategories }}</div>
                </div>
            </div>
            <div class="pdf-info-item">
                <div class="pdf-info-icon">&#x1F3F7;</div>
                <div>
                    <div style="font-size: 8pt; color: #64748b;">Total Lot</div>
                    <div class="pdf-info-value">{{ $totalPoolLots }}</div>
                </div>
            </div>
            <div class="pdf-info-item">
                <div class="pdf-info-icon">&#x2705;</div>
                <div>
                    <div style="font-size: 8pt; color: #64748b;">Peserta Terjadwal</div>
                    <div class="pdf-info-value">{{ $totalScheduledLots }}</div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="pdf-table-container">
            <table>
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-date">Tanggal</th>
                        <th class="col-time">Waktu</th>
                        <th class="col-gol">Golongan</th>
                        <th class="col-sesi">Sesi</th>
                        <th class="col-lot">Nomor Lot</th>
                        <th class="col-lok">Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $rowNumber = 1;
                    $groupRows = [];
                    $keyCounts = [];
                    $shown = [];

                    foreach ($dateGroups as $group) {
                        $dateStr = $group['date'] ?? '';
                        $timeStr = $group['time'] ?? '';
                        $endTimeStr = $group['end_time'] ?? '';
                        $key = ($dateStr ?: 'nodate') . '|' . ($timeStr ?: 'notime') . '|' . ($endTimeStr ?: 'noend');

                        $formattedDate = '';
                        $dayName = '';
                        if ($dateStr) {
                            $carbonDate = \Carbon\Carbon::parse($dateStr);
                            $dayName = $carbonDate->translatedFormat('l');
                            $formattedDate = $carbonDate->translatedFormat('d M Y');
                        }

                        foreach ($group['categories'] as $cat) {
                            $groupRows[] = [
                                'key' => $key,
                                'date' => $dayName,
                                'date_full' => $formattedDate,
                                'time_start' => $timeStr,
                                'time_end' => $endTimeStr,
                                'category_name' => $cat['category_name'],
                                'category_branch' => $cat['category_branch'],
                                'session_name' => $cat['session_name'],
                                'lot_badges' => $cat['lot_badges'],
                                'lot_count' => $cat['lot_count'],
                                'is_lot_per_district' => $cat['is_lot_per_district'] ?? false,
                                'location_name' => $cat['location_name'] ?? 'Lokasi Belum Ditentukan',
                                'location_map_url' => $cat['location_map_url'] ?? '',
                            ];
                        }
                    }

                    foreach ($groupRows as $row) {
                        $k = $row['key'];
                        if (!isset($keyCounts[$k])) $keyCounts[$k] = 0;
                        $keyCounts[$k]++;
                    }
                    @endphp

                    @foreach ($groupRows as $row)
                    @php
                        $key = $row['key'];
                        $isFirst = !isset($shown[$key]);
                        $shown[$key] = true;
                        $rowspan = $keyCounts[$key] ?? 1;
                    @endphp
                    <tr>
                        @if ($isFirst)
                        <td class="col-no merged-cell" rowspan="{{ $rowspan }}">
                            <div class="cell-no">{{ $rowNumber }}</div>
                        </td>
                        <td class="col-date merged-cell" rowspan="{{ $rowspan }}">
                            <div class="cell-date">{{ $row['date'] ?: '-' }}</div>
                            <div class="cell-date-sub">{{ $row['date_full'] ?: 'Belum diatur' }}</div>
                        </td>
                        <td class="col-time merged-cell" rowspan="{{ $rowspan }}">
                            <div class="cell-time">{{ $row['time_start'] ?: '-' }}</div>
                            @if ($row['time_end'])
                            <div class="cell-time-end">- {{ $row['time_end'] }} WIB</div>
                            @endif
                        </td>
                        @endif

                        <td class="col-gol">
                            <div class="cell-gol-name">{{ $row['category_name'] }}</div>
                            <div class="cell-gol-branch">{{ $row['category_branch'] }}</div>
                            @if ($row['is_lot_per_district'])
                            <div class="badge-district">
                                <span class="badge-icon">&#x1F4E6;</span>
                                1 Kecamatan = 1 Lot
                            </div>
                            @endif
                        </td>

                        <td class="col-sesi">
                            <div class="cell-sesi">{{ $row['session_name'] }}</div>
                        </td>

                        <td class="col-lot">
                            @if (count($row['lot_badges']) > 0)
                            <div>
                                @foreach ($row['lot_badges'] as $badge)
                                <span class="lot-badge">{{ $badge }}</span>
                                @endforeach
                            </div>
                            <div class="lot-count">&#x1F4C6; {{ $row['lot_count'] }} nomor lot</div>
                            @else
                            <span class="no-lot">-</span>
                            @endif
                        </td>

                        <td class="col-lok">
                            <div class="cell-lok-name">&#x1F4CD; {{ $row['location_name'] }}</div>
                            @if ($row['location_map_url'])
                            <div class="cell-lok-link">&#x1F310; {{ $row['location_map_url'] }}</div>
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
            <div>Dokumen resmi dari <span class="pdf-footer-brand">e-MTQ</span></div>
            <div class="pdf-footer-text">{{ config('app.url', 'http://localhost') }} | Diurutkan berdasarkan tanggal dan waktu pelaksanaan</div>
        </div>
    </div>

    <!-- Download Button (disabled for preview) -->
    <div class="download-section">
        <div class="download-btn" style="opacity: 0.5; cursor: not-allowed;">
            <span class="icon">&#x1F4E4;</span>
            <span>Auto-Download Dinonaktifkan (Preview Mode)</span>
        </div>
    </div>
</body>
</html>