<?php
$generatedAt = $generatedAt ?? now();
$selectedDistrict = $selectedDistrict ?? null;
$documentConfig = $documentConfig ?? config('documents');
$districtSummary = $districtSummary ?? [];
$categorySummary = $categorySummary ?? [];
$grandTotal = $grandTotal ?? ['putra' => 0, 'putri' => 0, 'total' => 0];
$scopeLabel = $selectedDistrict?->name ?? 'Semua Kecamatan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app.name', 'e-MTQ').' - Rekap Jumlah Peserta') ?></title>
    <style>
        @page {
            margin: 15mm 12mm;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            position: relative;
        }

        .header-block {
            border: 1px solid #dbe4f0;
            border-radius: 18px;
            overflow: hidden;
            margin-bottom: 14px;
        }

        .header-accent {
            height: 10px;
            background: linear-gradient(90deg, #0f766e 0%, #38bdf8 55%, #7c3aed 100%);
        }

        .header-body {
            padding: 16px 18px 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .document-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0;
            color: #0f172a;
        }

        .document-subtitle {
            margin-top: 5px;
            font-size: 11px;
            color: #475569;
            line-height: 1.6;
        }

        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 5px 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #334155;
        }

        .badge strong {
            margin-left: 5px;
            letter-spacing: 0;
            text-transform: none;
            font-size: 10px;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }

        .summary-card {
            border: 1px solid #dbe4f0;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 14px 16px;
            text-align: center;
        }

        .summary-card-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 8px;
        }

        .summary-card-value {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }

        .summary-card-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }

        .summary-card.petra .summary-card-value {
            color: #059669;
        }

        .summary-card.putri .summary-card-value {
            color: #db2777;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0 0 10px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-section {
            margin-bottom: 18px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .data-table thead th {
            background: #0f172a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 10px 12px;
            text-align: left;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table thead th:last-child {
            border-right: 0;
            text-align: center;
        }

        .data-table thead th.text-center {
            text-align: center;
        }

        .data-table tbody td {
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            padding: 9px 12px;
            font-size: 11px;
            color: #334155;
            background: #ffffff;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .data-table tbody td:last-child {
            border-right: 0;
            text-align: center;
            font-weight: 700;
        }

        .data-table tbody td.number {
            text-align: center;
            font-weight: 700;
        }

        .data-table .putra-cell {
            color: #059669;
            font-weight: 700;
        }

        .data-table .putri-cell {
            color: #db2777;
            font-weight: 700;
        }

        .data-table .total-cell {
            color: #0f172a;
            font-weight: 800;
        }

        .footer-note {
            margin-top: 14px;
            padding: 12px 14px;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            background: #f8fafc;
            color: #475569;
            font-size: 10.5px;
            line-height: 1.6;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            font-size: 10.5px;
            color: #64748b;
        }

        .empty-message {
            text-align: center;
            padding: 24px 12px;
            color: #475569;
            font-size: 11px;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            background: #f8fafc;
        }

        @media print {
            .header-block, .summary-cards, .table-section {
                break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body<?= request()->boolean('preview') ? '' : ' onload="window.print()"' ?>>
    <div class="sheet">
        <div class="header-block">
            <div class="header-accent"></div>
            <div class="header-body">
                <h1 class="document-title">Rekap Jumlah Peserta</h1>
                <div class="document-subtitle">
                    <?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?> |
                    <?= e($documentConfig['event_title'] ?? '') ?>
                </div>
                <div class="meta-row">
                    <span class="badge">Dicetak <strong><?= e($generatedAt->format('d M Y H:i')) ?></strong></span>
                    <span class="badge">Ruang lingkup <strong><?= e($scopeLabel) ?></strong></span>
                </div>
            </div>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-label">Total Putra</div>
                <div class="summary-card-value"><?= e($grandTotal['putra']) ?></div>
                <div class="summary-card-sub">peserta</div>
            </div>
            <div class="summary-card">
                <div class="summary-card-label">Total Putri</div>
                <div class="summary-card-value"><?= e($grandTotal['putri']) ?></div>
                <div class="summary-card-sub">peserta</div>
            </div>
            <div class="summary-card">
                <div class="summary-card-label">Total Keseluruhan</div>
                <div class="summary-card-value"><?= e($grandTotal['total']) ?></div>
                <div class="summary-card-sub">peserta</div>
            </div>
        </div>

        <div class="table-section">
            <h2 class="section-title">A. Rekap Jumlah Peserta per Kecamatan</h2>
            <?php if (empty($districtSummary)): ?>
                <div class="empty-message">Belum ada data peserta yang terdaftar.</div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">No</th>
                            <th>Kecamatan</th>
                            <th style="width: 15%;" class="text-center">Putra</th>
                            <th style="width: 15%;" class="text-center">Putri</th>
                            <th style="width: 15%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($districtSummary as $districtName => $counts): ?>
                            <tr>
                                <td class="number"><?= e($no++) ?></td>
                                <td><?= e($districtName) ?></td>
                                <td class="number putra-cell"><?= e($counts['putra']) ?></td>
                                <td class="number putri-cell"><?= e($counts['putri']) ?></td>
                                <td class="number total-cell"><?= e($counts['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background: #f1f5f9; font-weight: 700;">
                            <td class="number" colspan="2">JUMLAH TOTAL</td>
                            <td class="number putra-cell"><?= e($grandTotal['putra']) ?></td>
                            <td class="number putri-cell"><?= e($grandTotal['putri']) ?></td>
                            <td class="number total-cell"><?= e($grandTotal['total']) ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="table-section">
            <h2 class="section-title">B. Rekap Jumlah Peserta per Golongan (Kategori)</h2>
            <?php if (empty($categorySummary)): ?>
                <div class="empty-message">Belum ada data peserta yang terdaftar.</div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">No</th>
                            <th style="width: 20%;">Cabang</th>
                            <th>Golongan</th>
                            <th style="width: 12%;" class="text-center">Putra</th>
                            <th style="width: 12%;" class="text-center">Putri</th>
                            <th style="width: 12%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($categorySummary as $categoryName => $counts): ?>
                            <tr>
                                <td class="number"><?= e($no++) ?></td>
                                <td><?= e($counts['branch']) ?></td>
                                <td><?= e($categoryName) ?></td>
                                <td class="number putra-cell"><?= e($counts['putra']) ?></td>
                                <td class="number putri-cell"><?= e($counts['putri']) ?></td>
                                <td class="number total-cell"><?= e($counts['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background: #f1f5f9; font-weight: 700;">
                            <td class="number" colspan="2">JUMLAH TOTAL</td>
                            <td></td>
                            <td class="number putra-cell"><?= e($grandTotal['putra']) ?></td>
                            <td class="number putri-cell"><?= e($grandTotal['putri']) ?></td>
                            <td class="number total-cell"><?= e($grandTotal['total']) ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="footer-note">
            Laporan ini berisi rekapitulasi jumlah peserta berdasarkan kecamatan dan golongan/ketegori. Data diambil dari sistem e-MTQ secara otomatis.
        </div>

        <div class="footer-row">
            <div><?= e($documentConfig['signature_city'] ?? 'Batusangkar') ?>, <?= e($generatedAt->translatedFormat('d F Y')) ?></div>
            <div><?= e($selectedDistrict?->name ?? 'Semua kecamatan') ?></div>
        </div>
    </div>
</body>
</html>