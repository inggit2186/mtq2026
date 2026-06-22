<?php
$categoryBlocks = $categoryBlocks ?? [];
$generatedAt = $generatedAt ?? now();
$documentConfig = $documentConfig ?? config('documents');
$organizationName = $organizationName ?? 'e-MTQ Kabupaten Tanah Datar';
$eventTitle = $eventTitle ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12px; size: A4 landscape; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111827;
            font-size: 8pt;
            line-height: 1.3;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        h1, h2, h3, p { margin: 0 0 4px 0; }

        .cover {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
            background: #f8fafc;
            margin-bottom: 10px;
        }
        .title {
            font-size: 14pt;
            font-weight: 900;
            color: #0f172a;
        }
        .subtitle {
            color: #475569;
            font-size: 8pt;
        }
        .meta-row {
            display: flex;
            gap: 12px;
            margin-top: 6px;
            font-size: 7pt;
            color: #64748b;
        }
        .meta-item {
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .category {
            margin-bottom: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            page-break-after: always;
        }
        .category:last-child { page-break-after: auto; }
        .category-head {
            padding: 6px 10px;
            background: #1d4ed8;
            color: #ffffff;
        }
        .category-title {
            font-size: 10pt;
            font-weight: 900;
        }
        .category-meta {
            font-size: 7pt;
            opacity: 0.9;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }
        .table th,
        .table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            vertical-align: middle;
        }
        .table thead th {
            background: #1d4ed8;
            color: #ffffff;
            font-size: 6.5pt;
            font-weight: 700;
            text-align: center;
        }
        .table tbody tr:nth-child(even) td { background: #f8fafc; }
        .rank { text-align: center; font-weight: 700; width: 20px; }
        .score { text-align: center; font-weight: 700; }

        .detail-box {
            margin: 4px 0;
            padding: 4px 6px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 6pt;
        }
        .detail-title {
            font-weight: 700;
            color: #475569;
            margin-bottom: 2px;
            font-size: 6pt;
        }
        .detail-row {
            display: flex;
            gap: 4px;
            white-space: nowrap;
        }
        .detail-label {
            color: #64748b;
        }
        .detail-value {
            font-weight: 600;
            color: #0f172a;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #cbd5e1;
            font-size: 7pt;
        }
        .signature-left { width: 45%; }
        .signature-right { width: 35%; text-align: right; }
        .signature-title { font-weight: 700; }
        .signature-line { margin-top: 30px; border-top: 1px solid #0f172a; width: 80%; }

        .footer-note {
            margin-top: 8px;
            padding: 6px 8px;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            color: #475569;
            font-size: 6.5pt;
        }

        .gender-section { font-weight: 700; background: #f1f5f9; }
        .putra { background: rgba(14, 165, 233, 0.1); }
        .putri { background: rgba(219, 39, 119, 0.1); }
    </style>
</head>
<body>
    <div class="cover">
        <div class="title">REKAP DETAIL NILAI PENYISIHAN & FINAL</div>
        <div class="subtitle"><?= e($organizationName) ?> | <?= e($eventTitle) ?></div>
        <div class="meta-row">
            <span class="meta-item"><?= count($categoryBlocks) ?> Golongan</span>
            <span class="meta-item"><?= array_sum(array_column($categoryBlocks, 'participant_total')) ?> Peserta</span>
            <span class="meta-item"><?= e($generatedAt->format('d/m/Y H:i')) ?></span>
        </div>
    </div>

    <?php foreach ($categoryBlocks as $block): ?>
        <?php
        $category = $block['category'];
        $priorityLabels = $block['priority_labels'] ?? [];
        $priorityKeys = $block['priority_keys'] ?? [];
        $rankingRows = $block['ranking_rows'] ?? [];
        $hakimList = $block['hakim_list'] ?? collect();
        $participantTotal = $block['participant_total'] ?? 0;

        $putraRows = array_filter($rankingRows, fn ($row) => ($row['gender'] ?? '') === 'putra');
        $putriRows = array_filter($rankingRows, fn ($row) => ($row['gender'] ?? '') === 'putri');
        ?>
        <div class="category">
            <div class="category-head">
                <div class="category-title"><?= e($category->branch . ' - ' . $category->name) ?></div>
                <div class="category-meta"><?= e($participantTotal) ?> Peserta | Ranking Penyisihan</div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th class="rank">#</th>
                        <th>Nama Peserta</th>
                        <th>Kecamatan</th>
                        <?php foreach ($priorityLabels as $i => $label): ?>
                            <th title="<?= e($label) ?>">P<?= $i + 1 ?></th>
                        <?php endforeach; ?>
                        <th>Total</th>
                        <th>Detail Hakim</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_merge($putraRows, $putriRows) as $row): ?>
                        <?php $genderClass = ($row['gender'] ?? '') === 'putri' ? 'putri' : 'putra'; ?>
                        <tr class="<?= $genderClass ?>">
                            <td class="rank"><?= e($row['rank']) ?></td>
                            <td>
                                <strong><?= e($row['name']) ?></strong>
                                <div class="subtitle"><?= e($row['registration_number']) ?></div>
                            </td>
                            <td><?= e($row['district']) ?></td>
                            <?php foreach ($priorityLabels as $label): ?>
                                <td class="score"><?= e($row['priority_label_values'][$label] ?? '0.00') ?></td>
                            <?php endforeach; ?>
                            <td class="score"><strong><?= e($row['average_score']) ?></strong></td>
                            <td>
                                <?php
                                $scoreEntries = $row['score_entries'] ?? [];
                                $detailParts = [];
                                foreach ($scoreEntries as $entry) {
                                    foreach ($entry['judge_breakdown'] ?? [] as $judge) {
                                        $parts = [];
                                        foreach ($priorityKeys as $key) {
                                            $val = $judge['breakdown'][$key] ?? 0;
                                            $parts[] = $val > 0 ? number_format($val, 1) : '-';
                                        }
                                        $detailParts[] = 'H:' . implode('/', $parts) . '=' . number_format($judge['score'], 2);
                                    }
                                }
                                echo implode(' | ', array_slice($detailParts, 0, 3));
                                if (count($detailParts) > 3) echo ' ...';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="signature-section">
                <div class="signature-left">
                    <div class="signature-title">Dewan Hakim:</div>
                    <div class="subtitle">
                        <?php foreach ($hakimList as $index => $hakim): ?>
                            <?= ($index + 1) ?>. <?= e($hakim->nama) ?><br>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="signature-right">
                    <div><?= e(($documentConfig['signature_city'] ?? 'Pariangan')) ?>, <?= e($generatedAt->translatedFormat('F Y')) ?></div>
                    <div class="signature-title"><?= e(($documentConfig['officials']['chief_judge']['title'] ?? 'Ketua Dewan Hakim')) ?></div>
                    <div class="signature-line"></div>
                    <div class="subtitle">(<?= e(($documentConfig['officials']['chief_judge']['name'] ?? '................................')) ?>)</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="footer-note">
        <strong>Catatan:</strong> Ranking berdasarkan Babak Penyisihan dengan tie-break rules. Total = rata-rata semua hakim.
        Detail: H1/H2/H3 = Nilai per hakim (P1/P2/P3 = Nilai per poin). Dicetak <?= e($generatedAt->format('d/m/Y H:i')) ?>.
    </div>
</body>
</html>
