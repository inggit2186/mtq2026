<?php
$categoryLabel = $categoryLabel ?? 'Semua Golongan';
$selectedJudgingRound = $selectedJudgingRound ?? 'Penyisihan';
$judgeNames = $judgeNames ?? [];
$judgeIds = $judgeIds ?? [];
$criteria = $criteria ?? [];
$scoringSetting = $scoringSetting ?? null;
$participants = $participants ?? collect();
$totalCount = $totalCount ?? 0;
$printDate = $printDate ?? date('d F Y H:i');
$eventName = $eventName ?? 'MTQ';
$eventYear = $eventYear ?? date('Y');
$isMsqCategory = $isMsqCategory ?? false;

$shortenName = function($name) {
    $name = preg_replace('/\b(S\.?|H\.?|Ag|S\.Ag|S\.I\.Q|S\.SOS|S\.Pd\.I?|MA|M\.Pd|LC|A\.Ma)\b\.?/i', '', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));
    if (strlen($name) > 25) {
        $parts = explode(' ', $name);
        $name = '';
        foreach ($parts as $part) {
            if (strlen($name) + strlen($part) < 22) {
                $name .= ($name ? ' ' : '') . $part;
            } else {
                break;
            }
        }
    }
    return trim($name);
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Nilai Berdasarkan Lot - <?= e($categoryLabel) ?> - <?= e($selectedJudgingRound) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1e293b;
            background: #fff;
            padding: 0;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            color: #fff;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-icon {
            width: 50px; height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
        .header-title { font-size: 22px; font-weight: 800; letter-spacing: 1px; }
        .header-subtitle { font-size: 14px; opacity: 0.9; margin-top: 2px; }
        .header-badges { display: flex; gap: 10px; }
        .badge {
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-warning { background: #fef3c7; color: #92400e; }

        /* Info Box */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
        }
        .info-title {
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-title .icon { font-size: 16px; }
        .info-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .info-tag {
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
        }

        /* Section */
        .section { margin-bottom: 25px; }
        .section-header {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 16px;
            font-weight: 700;
        }
        .section-header .icon { font-size: 20px; margin-right: 8px; }
        .section-header .count { font-size: 12px; opacity: 0.9; font-weight: 500; }

        /* Table */
        .table-box {
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 10px 10px;
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th {
            background: #f1f5f9;
            padding: 10px 8px;
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        th.sticky { position: sticky; left: 0; z-index: 2; background: #f1f5f9; }
        td { padding: 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        td.sticky { position: sticky; left: 0; background: inherit; z-index: 1; }
        tr:last-child td { border-bottom: none; }

        /* Participant Row */
        .row-main { font-weight: 600; }
        .row-main.putra { background: #eff6ff; }
        .row-main.putra td { border-bottom: 2px solid #bfdbfe; }
        .row-main.putri { background: #fdf2f8; }
        .row-main.putri td { border-bottom: 2px solid #fbcfe8; }

        .name-cell { text-align: left; }
        .name-main { font-weight: 700; font-size: 13px; color: #1e293b; }
        .name-sub { font-size: 10px; color: #64748b; margin-top: 2px; }

        /* Gender Badge */
        .gender-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .gender-badge.putra { background: #dbeafe; color: #1e40af; }
        .gender-badge.putri { background: #fce7f3; color: #be185d; }

        /* Score Cells */
        .score-cell { text-align: center; }
        .score-main { font-weight: 800; font-size: 16px; }
        .score-filled { color: #0f766e; }
        .score-high { color: #16a34a; }
        .score-low { color: #dc2626; }
        .score-empty { color: #cbd5e1; }

        /* Judge Detail Row */
        .row-detail { background: #f8fafc; }
        .row-detail td { padding: 5px 8px; font-size: 10px; }
        .detail-label {
            color: #64748b;
            font-style: italic;
            font-size: 9px;
        }
        .detail-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 3px 8px;
            border-radius: 4px;
            margin: 2px;
            font-size: 10px;
        }
        .detail-name { font-weight: 600; color: #475569; }
        .detail-value { font-weight: 700; color: #7c3aed; }

        /* Total Cell */
        .total-cell {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            color: #fff;
            font-weight: 800;
            font-size: 15px;
            text-align: center;
        }
        .total-empty { color: #94a3b8; }

        /* Lot Badge */
        .lot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 28px;
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            color: #fff;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
        }

        /* Empty State */
        .empty { text-align: center; padding: 40px; color: #94a3b8; font-style: italic; }

        /* Footer */
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-top: 2px solid #e2e8f0;
            font-size: 11px;
            color: #64748b;
        }
        .footer-logo { font-weight: 700; color: #7c3aed; font-size: 14px; }

        /* Remarks */
        .remarks-row { background: #fffbeb; }
        .remarks-cell { padding: 8px 12px; }
        .remarks-content { display: flex; flex-wrap: wrap; gap: 8px; }
        .remarks-item {
            background: #fef3c7;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 10px;
        }
        .remarks-item strong { color: #92400e; }
        .remarks-text { color: #78350f; }

        /* Spacer */
        .spacer td { height: 10px; background: #fff; border: none; }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="header-icon">📋</div>
            <div>
                <div class="header-title">📋 REKAP NILAI BERDASARKAN NOMOR LOT</div>
                <div class="header-subtitle"><?= e($categoryLabel) ?></div>
            </div>
        </div>
        <div class="header-badges">
            <?php if ($isMsqCategory): ?>
                <span class="badge" style="background:#fef3c7;color:#92400e;">📌 Nilai per Kecamatan</span>
            <?php endif; ?>
            <span class="badge">🏆 <?= e($selectedJudgingRound) ?></span>
            <span class="badge">📅 <?= e($eventName.' '.$eventYear) ?></span>
            <span class="badge badge-warning">🕐 <?= e($printDate) ?></span>
        </div>
    </div>

    <!-- Info Boxes -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-title">
                <span class="icon">👥</span>
                Dewan Hakim (<?= count($judgeNames) ?> Orang)
            </div>
            <div class="info-list">
                <?php $i = 1; foreach ($judgeNames as $n): ?>
                    <span class="info-tag"><?= e($i) ?>. <?= e($shortenName($n)) ?></span>
                <?php $i++; endforeach; ?>
            </div>
        </div>
        <div class="info-box">
            <div class="info-title">
                <span class="icon">📝</span>
                Poin Penilaian (<?= count($criteria) ?> Poin)
            </div>
            <div class="info-list">
                <?php $j = 1; foreach ($criteria as $k => $l): ?>
                    <span class="info-tag"><?= e($j) ?>. <?= e($l) ?></span>
                <?php $j++; endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Combined Section -->
    <div class="section">
        <div class="section-header">
            <span><span class="icon">📊</span> DAFTAR NILAI BERDASARKAN NOMOR LOT</span>
            <span class="count">🎯 <?= $totalCount ?> Kecamatan<?= $isMsqCategory ? ' (Nilai MSQ)' : ' Peserta' ?></span>
        </div>
        <div class="table-box">
            <?php if ($participants->isEmpty()): ?>
                <div class="empty">Belum ada data peserta</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th class="sticky" style="width: 40px;">#</th>
                            <th style="width: 50px;">Foto</th>
                            <th style="width: 50px;">Lot</th>
                            <th style="min-width: 120px;">Nama Peserta</th>
                            <th style="width: 55px;">Gender</th>
                            <th style="min-width: 90px;">Kecamatan</th>
                            <?php foreach ($criteria as $k => $l): ?>
                                <th style="min-width: 65px;"><?= e($l) ?></th>
                            <?php endforeach; ?>
                            <th style="width: 65px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $p): ?>
                            <!-- Main Row -->
                            <tr class="row-main <?= e($p['gender']) ?>">
                                <td class="sticky" style="text-align: center; background: inherit;">
                                    <span style="font-weight: 700; font-size: 12px; color: #7c3aed;"><?= e($p['lot_index']) ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!empty($p['photo_url'])): ?>
                                        <img src="<?= e($p['photo_url']) ?>" alt="<?= e($p['name']) ?>" style="width: 38px; height: 48px; object-fit: cover; border-radius: 6px; border: 2px solid #e2e8f0;">
                                    <?php else: ?>
                                        <div style="width: 38px; height: 48px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 2px solid #e2e8f0;">
                                            <span style="font-size: 18px;">👤</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="lot"><?= e($p['lot_number']) ?></span>
                                </td>
                                <td class="name-cell" style="background: inherit;">
                                    <div class="name-main"><?= e($p['name']) ?></div>
                                    <div class="name-sub"><?= e($p['institution'] ?? '') ?></div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="gender-badge <?= e($p['gender']) ?>"><?= e($p['gender']) ?></span>
                                </td>
                                <td style="text-align: center; font-size: 10px;"><?= e($p['district_name']) ?></td>
                                <?php foreach ($criteria as $key => $label): ?>
                                    <?php
                                    $v = $p['point_averages'][$key] ?? null;
                                    $c = $p['point_counts'][$key] ?? 0;
                                    $cls = 'score-cell score-main';
                                    $txt = '';
                                    if ($v !== null && $v > 0) {
                                        $txt = number_format($v, 2);
                                        $cls .= ' score-filled';
                                        if ($v >= 25) $cls .= ' score-high';
                                        elseif ($v < 15) $cls .= ' score-low';
                                    } else {
                                        $cls .= ' score-empty';
                                    }
                                    ?>
                                    <td class="<?= e($cls) ?>" title="<?= e($label) ?> (<?= $c ?> hakim)"><?= e($txt) ?></td>
                                <?php endforeach; ?>
                                <td class="total-cell <?= !$p['has_score'] ? 'total-empty' : '' ?>">
                                    <?= $p['has_score'] ? number_format($p['total_score'], 2) : '-' ?>
                                </td>
                            </tr>
                            <!-- Judge Detail Row -->
                            <tr class="row-detail">
                                <td class="sticky" style="background: #f8fafc;"></td>
                                <td></td>
                                <td></td>
                                <td class="detail-label">📋 Nilai per Hakim ▼</td>
                                <td></td>
                                <td></td>
                                <?php foreach ($criteria as $key => $label): ?>
                                    <td>
                                        <?php
                                        $hasAny = false;
                                        foreach ($judgeNames as $jn) {
                                            $jd = $p['judge_score_details'][$jn] ?? null;
                                            $v = $jd['point_scores'][$key]['value'] ?? null;
                                            if ($v !== null && $v > 0) $hasAny = true;
                                        }
                                        if (!$hasAny) {
                                            echo '<span style="color:#cbd5e1;font-size:9px;">-</span>';
                                        } else {
                                            echo '<div style="display:flex;flex-wrap:wrap;gap:3px;justify-content:center;">';
                                            foreach ($judgeNames as $jn) {
                                                $jd = $p['judge_score_details'][$jn] ?? null;
                                                $v = $jd['point_scores'][$key]['value'] ?? null;
                                                if ($v !== null && $v > 0) {
                                                    echo '<span class="detail-item"><span class="detail-name">'.e(substr($shortenName($jn),0,8)).'</span><span class="detail-value">'.number_format($v,1).'</span></span>';
                                                }
                                            }
                                            echo '</div>';
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                <td></td>
                            </tr>
                            <!-- Remarks Row -->
                            <?php
                            $hasRemarks = false;
                            foreach ($judgeNames as $jn) {
                                $jd = $p['judge_score_details'][$jn] ?? null;
                                if (!empty($jd['remarks'])) { $hasRemarks = true; break; }
                            }
                            ?>
                            <?php if ($hasRemarks): ?>
                            <tr class="remarks-row">
                                <td class="sticky"></td>
                                <td></td>
                                <td></td>
                                <td class="remarks-cell" style="font-size:10px;color:#92400e;"><strong>💬 Catatan:</strong></td>
                                <td></td>
                                <td></td>
                                <td colspan="<?= count($criteria) ?>" class="remarks-cell">
                                    <div class="remarks-content">
                                        <?php foreach ($judgeNames as $jn): ?>
                                            <?php $jd = $p['judge_score_details'][$jn] ?? null; ?>
                                            <?php if (!empty($jd['remarks'])): ?>
                                                <span class="remarks-item"><strong><?= e(substr($shortenName($jn),0,10)) ?>:</strong> <span class="remarks-text"><?= e($jd['remarks']) ?></span></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                            <?php endif; ?>
                            <!-- Spacer -->
                            <tr class="spacer"><td colspan="<?= 5 + count($criteria) + 1 ?>"></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>
            <span class="footer-logo">🏆 <?= e($eventName) ?></span>
            <span> | Dicetak pada <?= e($printDate) ?></span>
        </div>
        <div>📄 Halaman 1 dari 1</div>
    </div>

</body>
</html>
