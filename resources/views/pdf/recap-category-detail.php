<?php
$categoryBlock = $categoryBlock ?? [];
$generatedAt = $generatedAt ?? now();
$documentConfig = $documentConfig ?? config('documents');
$priorityContext = $priorityContext ?? ['text' => '', 'labels' => []];
$labels = $categoryBlock['priority_labels'] ?? [];
$rankingRows = $categoryBlock['ranking_rows'] ?? collect();
$organizationName = $documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar';
$eventTitle = $documentConfig['event_title'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18px 18px 24px; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0f172a;
            font-size: 10.5pt;
            line-height: 1.45;
            background: #ffffff;
        }
        h1, h2, h3, p { margin: 0; }
        .cover {
            border: 1px solid #94a3b8;
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 20px;
        }
        .eyebrow {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #0f172a;
            color: #ffffff;
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
        .meta {
            width: 100%;
            margin-top: 16px;
            border-collapse: collapse;
        }
        .meta td {
            padding: 9px 10px;
            border: 1px solid #94a3b8;
            vertical-align: top;
        }
        .meta .label {
            width: 180px;
            background: #eff6ff;
            font-weight: 800;
            color: #1d4ed8;
        }
        .cards {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .cards td {
            border: 1px solid #94a3b8;
            padding: 12px 14px;
            width: 25%;
            background: #ffffff;
        }
        .cards .label {
            display: block;
            font-size: 8.5pt;
            color: #1d4ed8;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 800;
        }
        .cards .value {
            display: block;
            margin-top: 6px;
            font-size: 17pt;
            font-weight: 900;
            color: #0f172a;
        }
        .section-title {
            margin-top: 22px;
            margin-bottom: 10px;
            font-size: 12pt;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            border: 1px solid #94a3b8;
            padding: 8px 9px;
            vertical-align: top;
        }
        .table thead th {
            background: #1d4ed8;
            color: #ffffff;
            font-size: 9pt;
            font-weight: 800;
        }
        .table tbody tr:nth-child(even) td { background: #f8fafc; }
        .rank {
            text-align: center;
            font-weight: 900;
            width: 68px;
            color: #0f172a;
        }
        .winner-name {
            font-weight: 800;
            color: #0f172a;
        }
        .muted { color: #475569; font-size: 8.5pt; }
        .tag {
            display: inline-block;
            margin-top: 4px;
            padding: 3px 7px;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid #94a3b8;
            color: #334155;
            font-size: 8.5pt;
        }
        .participant {
            margin-top: 18px;
            border: 1px solid #94a3b8;
            border-radius: 14px;
            padding: 14px;
            page-break-inside: avoid;
        }
        .participant-head {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
        }
        .participant-name { font-size: 13pt; font-weight: 800; color: #0f172a; }
        .participant-meta { margin-top: 4px; color: #475569; }
        .participant-score {
            text-align: right;
            min-width: 130px;
        }
        .participant-score .value {
            font-size: 18pt;
            font-weight: 900;
            color: #0f172a;
        }
        .entry-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .entry-table th,
        .entry-table td {
            border: 1px solid #94a3b8;
            padding: 7px 8px;
            font-size: 9pt;
        }
        .entry-table th {
            background: #f8fafc;
            font-weight: 900;
            color: #0f172a;
        }
        .footer-note {
            margin-top: 18px;
            border: 1px dashed #64748b;
            border-radius: 14px;
            padding: 12px 14px;
            color: #334155;
            font-size: 9.5pt;
        }
        .signature-wrap {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
        }
        .signature {
            width: 260px;
            text-align: center;
        }
        .signature-line {
            margin: 54px auto 6px;
            border-top: 1px solid #0f172a;
            width: 85%;
        }
    </style>
</head>
<body>
    <div class="cover">
        <div class="eyebrow">Rekap Golongan</div>
        <div class="title"><?= e(($categoryBlock['branch'] ?? '-') . ' - ' . ($categoryBlock['category_name'] ?? '-')) ?></div>
        <p class="subtitle">Dokumen rekap lengkap berisi ranking, detail nilai per poin, dan riwayat penilaian per peserta.</p>

        <table class="meta">
            <tr>
                <td class="label">Penerbit</td>
                <td><?= e($organizationName) ?></td>
                <td class="label">Waktu Cetak</td>
                <td><?= e($generatedAt->format('d/m/Y H:i')) ?></td>
            </tr>
            <tr>
                <td class="label">Event</td>
                <td><?= e($eventTitle) ?></td>
                <td class="label">Prioritas Tie-break</td>
                <td><?= e($priorityContext['text'] ?? '-') ?></td>
            </tr>
        </table>

        <table class="cards">
            <tr>
                <td><span class="label">Peserta</span><span class="value"><?= e($categoryBlock['participant_total'] ?? 0) ?></span></td>
                <td><span class="label">Entri Nilai</span><span class="value"><?= e($categoryBlock['score_entries'] ?? 0) ?></span></td>
                <td><span class="label">Golongan</span><span class="value"><?= e($categoryBlock['category_name'] ?? '-') ?></span></td>
                <td><span class="label">Cabang</span><span class="value"><?= e($categoryBlock['branch'] ?? '-') ?></span></td>
            </tr>
        </table>
    </div>

    <div class="section-title">Peringkat Golongan</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 54px;">Rank</th>
                <th>Peserta</th>
                <th>Kecamatan</th>
                <th>Institusi</th>
                <th style="width: 95px;">Nilai Terakhir</th>
                <th style="width: 95px;">Rata-rata</th>
                <th style="width: 95px;">Terbaik</th>
                <th style="width: 62px;">Entry</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rankingRows as $row): ?>
                <tr>
                    <td class="rank"><?= e($row['rank']) ?></td>
                    <td>
                        <strong><?= e($row['name']) ?></strong><br>
                        <span class="muted"><?= e($row['registration_number']) ?></span>
                    </td>
                    <td><?= e($row['district']) ?></td>
                    <td><?= e($row['institution']) ?></td>
                    <td><?= e($row['latest_score']) ?></td>
                    <td><?= e($row['average_score']) ?></td>
                    <td><?= e($row['best_score']) ?></td>
                    <td><?= e($row['entry_count']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="section-title">Rincian Nilai Per Peserta</div>
    <?php foreach ($rankingRows as $row): ?>
        <div class="participant">
            <div class="participant-head">
                <div>
                    <div class="eyebrow">Peringkat <?= e($row['rank']) ?></div>
                    <div class="participant-name" style="margin-top: 8px;"><?= e($row['name']) ?></div>
                    <div class="participant-meta"><?= e($row['registration_number']) ?> | <?= e($row['district']) ?> | <?= e($row['institution']) ?></div>
                    <div style="margin-top: 8px;">
                        <?php foreach (($row['priority_label_values'] ?? []) as $label => $value): ?>
                            <span class="tag"><?= e($label) ?>: <?= e($value) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="participant-score">
                    <div class="muted">Rata-rata</div>
                    <div class="value"><?= e($row['average_score']) ?></div>
                    <div class="muted">Terakhir <?= e($row['latest_score']) ?> | Terbaik <?= e($row['best_score']) ?></div>
                </div>
            </div>

            <table class="entry-table">
                <thead>
                    <tr>
                        <th style="width: 90px;">Babak</th>
                        <th style="width: 110px;">Hakim</th>
                        <th style="width: 90px;">Total</th>
                        <?php foreach ($labels as $label): ?>
                            <th><?= e($label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($row['score_entries'] ?? []) as $entry): ?>
                        <tr>
                            <td><?= e($entry['judging_round']) ?><br><span class="muted"><?= e($entry['submitted_at']) ?></span></td>
                            <td><?= e($entry['judge_name']) ?></td>
                            <td><strong><?= e($entry['score']) ?></strong></td>
                            <?php foreach ($labels as $label): ?>
                                <td><?= e($entry['breakdown'][$label] ?? '0.00') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <div class="footer-note">
        Dokumen ini dibuat dari data yang sudah terverifikasi. Angka pada kolom per poin mengikuti setting penilaian pada golongan ini dan cocok untuk arsip, rapat pleno, maupun rekap dewan hakim.
    </div>

    <div class="signature-wrap">
        <div class="signature">
            <p><?= e(($documentConfig['signature_city'] ?? 'Batusangkar').', '.$generatedAt->translatedFormat('d F Y')) ?></p>
            <div class="signature-line"></div>
            <p><?= e(($documentConfig['officials']['chief_judge']['title'] ?? 'Ketua Majelis Hakim')) ?></p>
            <p class="muted"><?= e(($documentConfig['officials']['chief_judge']['name'] ?? '........................................')) ?></p>
        </div>
    </div>
</body>
</html>
