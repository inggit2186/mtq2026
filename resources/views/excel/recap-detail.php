<?php
$filters = $filters ?? [];
$categoryBlocks = $categoryBlocks ?? collect();
$summary = $summary ?? [];
$selectedCategory = $selectedCategory ?? null;
$generatedAt = $generatedAt ?? now();
$documentConfig = $documentConfig ?? config('documents');
$rankingPriorityContext = $rankingPriorityContext ?? ['text' => '', 'labels' => []];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=UTF-8">
    <style>
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 10pt;
            color: #0f172a;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .sheet-title {
            font-size: 20pt;
            font-weight: 800;
            color: #0f172a;
        }
        .sheet-subtitle {
            font-size: 10pt;
            color: #475569;
        }
        .meta, .summary, .table {
            margin-top: 14px;
        }
        .meta td,
        .summary td,
        .table th,
        .table td {
            border: 1px solid #cbd5e1;
            padding: 8px 9px;
            vertical-align: top;
        }
        .meta .label {
            width: 180px;
            background: #eff6ff;
            font-weight: 700;
        }
        .summary td {
            width: 25%;
            background: #f8fafc;
        }
        .summary .label {
            display: block;
            font-size: 8.5pt;
            color: #1d4ed8;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: .04em;
        }
        .summary .value {
            display: block;
            margin-top: 4px;
            font-size: 16pt;
            font-weight: 800;
        }
        .section-title {
            margin-top: 18px;
            margin-bottom: 10px;
            font-size: 12pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .table thead th {
            background: #dbeafe;
            color: #0f172a;
            font-weight: 800;
            text-align: center;
        }
        .table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }
        .group-head {
            margin-top: 24px;
            padding: 12px 14px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
        }
        .group-title {
            font-size: 14pt;
            font-weight: 800;
        }
        .group-meta {
            margin-top: 4px;
            color: #475569;
        }
        .tag {
            display: inline-block;
            margin-right: 6px;
            margin-top: 8px;
            padding: 3px 7px;
            border: 1px solid #bfdbfe;
            background: #ffffff;
            border-radius: 999px;
            color: #1d4ed8;
            font-size: 8.5pt;
        }
        .detail {
            margin-top: 14px;
        }
        .detail .participant {
            font-weight: 700;
        }
        .detail .muted {
            color: #64748b;
            font-size: 8.5pt;
        }
        .breakdown {
            font-size: 8.5pt;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="sheet-title">Rekap Penilaian Detail</td>
        </tr>
        <tr>
            <td class="sheet-subtitle"><?= e(($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar').' | Dicetak '.$generatedAt->format('d/m/Y H:i')) ?></td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="label">Filter Cabang</td>
            <td><?= e($filters['branch'] ?? 'Semua') ?></td>
            <td class="label">Filter Golongan</td>
            <td><?= e($selectedCategory ? trim(($selectedCategory->branch ?? '').' - '.($selectedCategory->name ?? '')) : 'Semua') ?></td>
        </tr>
        <tr>
            <td class="label">Kata Kunci</td>
            <td><?= e($filters['keyword'] ?? '-') ?></td>
            <td class="label">Tie-break</td>
            <td><?= e($rankingPriorityContext['text'] ?? '-') ?></td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><span class="label">Golongan</span><span class="value"><?= e($summary['categories'] ?? 0) ?></span></td>
            <td><span class="label">Peserta</span><span class="value"><?= e($summary['participants'] ?? 0) ?></span></td>
            <td><span class="label">Cabang</span><span class="value"><?= e($summary['branches'] ?? 0) ?></span></td>
            <td><span class="label">Entri Nilai</span><span class="value"><?= e($summary['score_entries'] ?? 0) ?></span></td>
        </tr>
    </table>

    <?php foreach ($categoryBlocks as $block): ?>
        <?php $rows = $block['ranking_rows'] ?? collect(); ?>
        <div class="group-head">
            <div class="group-title"><?= e($block['branch'].' - '.$block['category_name']) ?></div>
            <div class="group-meta">
                <?= e($block['participant_total']) ?> peserta | <?= e($block['score_entries']) ?> entri nilai | <?= e($block['priority_context']['text'] ?? '-') ?>
            </div>
            <div>
                <span class="tag">Juara 1</span>
                <span class="tag">Juara 2</span>
                <span class="tag">Juara 3</span>
                <span class="tag">Harapan 1</span>
                <span class="tag">Harapan 2</span>
                <span class="tag">Harapan 3</span>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Peserta</th>
                    <th>Kecamatan</th>
                    <th>Institusi</th>
                    <th>Nilai Terakhir</th>
                    <th>Rata-rata</th>
                    <th>Terbaik</th>
                    <th>Entri</th>
                    <?php if ($selectedCategory && (int) $selectedCategory->id === (int) $block['category_id']): ?>
                        <?php foreach (($block['priority_labels'] ?? []) as $label): ?>
                            <th><?= e($label) ?></th>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <th>Nilai Perpoin</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['rank']) ?></td>
                        <td>
                            <div class="participant"><?= e($row['name']) ?></div>
                            <div class="muted"><?= e($row['registration_number']) ?></div>
                        </td>
                        <td><?= e($row['district']) ?></td>
                        <td><?= e($row['institution']) ?></td>
                        <td><?= e($row['latest_score']) ?></td>
                        <td><?= e($row['average_score']) ?></td>
                        <td><?= e($row['best_score']) ?></td>
                        <td><?= e($row['entry_count']) ?></td>
                        <?php if ($selectedCategory && (int) $selectedCategory->id === (int) $block['category_id']): ?>
                            <?php foreach (($row['priority_label_values'] ?? []) as $value): ?>
                                <td><?= e($value) ?></td>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <td class="breakdown">
                                <?php foreach (($row['priority_label_values'] ?? []) as $label => $value): ?>
                                    <?= e($label) ?>: <?= e($value) ?><br>
                                <?php endforeach; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($selectedCategory && (int) $selectedCategory->id === (int) $block['category_id']): ?>
            <div class="detail">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            <th>Babak</th>
                            <th>Hakim</th>
                            <th>Total</th>
                            <?php foreach (($block['priority_labels'] ?? []) as $label): ?>
                                <th><?= e($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php foreach (($row['score_entries'] ?? []) as $entry): ?>
                                <tr>
                                    <td>
                                        <div class="participant"><?= e($row['name']) ?></div>
                                        <div class="muted"><?= e($row['registration_number']) ?></div>
                                    </td>
                                    <td><?= e($entry['judging_round']) ?></td>
                                    <td><?= e($entry['judge_name']) ?></td>
                                    <td><?= e($entry['score']) ?></td>
                                    <?php foreach (($block['priority_labels'] ?? []) as $label): ?>
                                        <td><?= e($entry['breakdown'][$label] ?? '0.00') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>
