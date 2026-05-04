<?php
$categoryBlocks = $categoryBlocks ?? collect();
$summary = $summary ?? [];
$generatedAt = $generatedAt ?? now();
$documentConfig = $documentConfig ?? config('documents');
$organizationName = $documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar';
$eventTitle = $documentConfig['event_title'] ?? '';
$roundLabel = $roundLabel ?? 'Penyisihan';
$roundTitle = $roundLabel === 'Final' ? 'Rekap Juara Final' : 'Rekap Juara Penyisihan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18px 20px 26px; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111827;
            font-size: 10pt;
            line-height: 1.45;
            background: #ffffff;
        }
        h1, h2, h3, p { margin: 0; }
        .hero {
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            padding: 18px 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        }
        .eyebrow {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #0f172a;
            color: #f8fafc;
            font-size: 8.5pt;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .title {
            font-size: 20pt;
            font-weight: 900;
            margin-top: 10px;
            color: #0f172a;
        }
        .subtitle {
            color: #334155;
            margin-top: 6px;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .summary td {
            border: 1px solid #94a3b8;
            padding: 11px 12px;
            background: #ffffff;
        }
        .summary .label {
            display: block;
            font-size: 8.5pt;
            color: #1d4ed8;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 800;
        }
        .summary .value {
            display: block;
            margin-top: 6px;
            font-size: 16pt;
            color: #0f172a;
            font-weight: 900;
        }
        .category {
            margin-top: 16px;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            overflow: hidden;
            page-break-after: always;
        }
        .category:last-child { page-break-after: auto; }
        .category-head {
            padding: 14px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
        }
        .category-title {
            font-size: 14pt;
            font-weight: 900;
            color: #0f172a;
        }
        .category-meta {
            margin-top: 4px;
            color: #334155;
            font-size: 9.5pt;
        }
        .pill {
            display: inline-block;
            margin-right: 6px;
            margin-top: 8px;
            padding: 3px 8px;
            border-radius: 999px;
            background: #eff6ff;
            border: 1px solid #93c5fd;
            color: #1d4ed8;
            font-size: 8.5pt;
            font-weight: 700;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            border: 1px solid #cbd5e1;
            padding: 8px 9px;
            vertical-align: top;
        }
        .table thead th {
            background: #1d4ed8;
            color: #ffffff;
            font-size: 9pt;
            font-weight: 800;
            text-align: center;
        }
        .table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }
        .rank {
            text-align: center;
            font-weight: 900;
            width: 76px;
            color: #0f172a;
        }
        .winner-name {
            font-weight: 800;
            color: #0f172a;
        }
        .muted { color: #475569; font-size: 8.5pt; }
        .footer-note {
            margin-top: 16px;
            color: #334155;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="eyebrow"><?= e($roundLabel) ?></div>
        <div class="title"><?= e($roundTitle) ?></div>
        <p class="subtitle"><?= e($organizationName) ?> | <?= e($eventTitle) ?></p>
        <p class="subtitle">Dokumen ini hanya menampilkan hasil babak <?= e($roundLabel) ?>, jadi lebih cepat dibaca untuk rapat hasil dan penetapan juara.</p>
    </div>

    <table class="summary">
        <tr>
            <td><span class="label">Golongan</span><span class="value"><?= e($summary['categories'] ?? 0) ?></span></td>
            <td><span class="label">Peserta</span><span class="value"><?= e($summary['participants'] ?? 0) ?></span></td>
            <td><span class="label">Cabang</span><span class="value"><?= e($summary['branches'] ?? 0) ?></span></td>
            <td><span class="label">Entri Nilai</span><span class="value"><?= e($summary['score_entries'] ?? 0) ?></span></td>
        </tr>
    </table>

    <?php foreach ($categoryBlocks as $block): ?>
        <div class="category">
            <div class="category-head">
                <div class="category-title"><?= e($block['branch'].' - '.$block['category_name']) ?></div>
                <div class="category-meta">
                    <?= e($block['participant_total']) ?> peserta | <?= e($block['score_entries']) ?> entri nilai | <?= e($block['priority_context']['text'] ?? '-') ?>
                </div>
                <div>
                    <span class="pill">Juara 1</span>
                    <span class="pill">Juara 2</span>
                    <span class="pill">Juara 3</span>
                    <span class="pill">Harapan 1</span>
                    <span class="pill">Harapan 2</span>
                    <span class="pill">Harapan 3</span>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 78px;">Rank</th>
                        <th>Peserta</th>
                        <th>Kecamatan</th>
                        <th>Institusi</th>
                        <th style="width: 100px;">Total Nilai</th>
                        <th style="width: 100px;">Nilai Terakhir</th>
                        <th style="width: 70px;">Entri</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($block['winners'] as $index => $row): ?>
                        <?php
                        $rankLabel = match ($index) {
                            0 => 'Juara 1',
                            1 => 'Juara 2',
                            2 => 'Juara 3',
                            3 => 'Harapan 1',
                            4 => 'Harapan 2',
                            5 => 'Harapan 3',
                            default => 'Peringkat '.($index + 1),
                        };
                        ?>
                        <tr>
                            <td class="rank">
                                <?= e($rankLabel) ?><br>
                                <span class="muted">#<?= e($row['rank']) ?></span>
                            </td>
                            <td>
                                <div class="winner-name"><?= e($row['name']) ?></div>
                                <div class="muted"><?= e($row['registration_number']) ?></div>
                            </td>
                            <td><?= e($row['district']) ?></td>
                            <td><?= e($row['institution']) ?></td>
                            <td><strong><?= e($row['average_score']) ?></strong></td>
                            <td><?= e($row['latest_score']) ?></td>
                            <td><?= e($row['entry_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <div class="footer-note">
        Dicetak <?= e($generatedAt->format('d/m/Y H:i')) ?>. Rekap ini khusus untuk babak <?= e($roundLabel) ?>.
    </div>
</body>
</html>
