<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=UTF-8">
    <style>
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 11pt;
            color: #0f172a;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .sheet-title {
            font-size: 20pt;
            font-weight: 700;
            color: #0f172a;
        }

        .sheet-subtitle {
            font-size: 11pt;
            color: #475569;
        }

        .section-title {
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 18px;
            padding: 6px 10px;
            background: #e0f2fe;
            border: 1px solid #7dd3fc;
        }

        .meta {
            margin-top: 12px;
        }

        .meta td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
        }

        .meta .label {
            width: 180px;
            font-weight: 700;
            background: #e0f2fe;
            color: #0f172a;
        }

        .summary {
            margin-top: 16px;
        }

        .summary td {
            border: 1px solid #bfdbfe;
            padding: 10px 12px;
            width: 33%;
        }

        .summary .card-label {
            background: #eff6ff;
            font-size: 9pt;
            font-weight: 700;
            color: #1d4ed8;
            text-transform: uppercase;
        }

        .summary .card-value {
            background: #ffffff;
            font-size: 16pt;
            font-weight: 700;
            color: #0f172a;
        }

        .summary .card-value.putra {
            color: #059669;
        }

        .summary .card-value.putri {
            color: #db2777;
        }

        .data {
            margin-top: 8px;
        }

        .data th,
        .data td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            vertical-align: middle;
        }

        .data thead th {
            background: #0f172a;
            color: #ffffff;
            font-size: 10pt;
            font-weight: 700;
            text-align: center;
        }

        .data tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .data tbody tr.total-row td {
            background: #f1f5f9;
            font-weight: 700;
        }

        .data td.center {
            text-align: center;
        }

        .data td.putra {
            color: #059669;
            font-weight: 700;
        }

        .data td.putri {
            color: #db2777;
            font-weight: 700;
        }

        .data td.total {
            color: #0f172a;
            font-weight: 800;
        }

        .col-no { width: 50px; }
        .col-name { width: 200px; }
        .col-branch { width: 200px; }
        .col-count { width: 80px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="sheet-title">Rekap Jumlah Peserta</td>
        </tr>
        <tr>
            <td class="sheet-subtitle"><?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?> | <?= e($documentConfig['event_title'] ?? '') ?></td>
        </tr>
        <tr>
            <td class="sheet-subtitle">Dicetak <?= e($generatedAt->format('d/m/Y H:i')) ?> | Ruang Lingkup: <?= e($scopeLabel) ?></td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td class="card-label">Total Putra</td>
            <td class="card-label">Total Putri</td>
            <td class="card-label">Total Keseluruhan</td>
        </tr>
        <tr>
            <td class="card-value putra"><?= e($grandTotal['putra']) ?></td>
            <td class="card-value putri"><?= e($grandTotal['putri']) ?></td>
            <td class="card-value"><?= e($grandTotal['total']) ?></td>
        </tr>
    </table>

    <div class="section-title">A. Rekap Jumlah Peserta per Kecamatan</div>
    <table class="data">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-name">Kecamatan</th>
                <th class="col-count">Putra</th>
                <th class="col-count">Putri</th>
                <th class="col-count">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($districtSummary)): ?>
                <tr>
                    <td colspan="5" class="center">Belum ada data peserta yang terdaftar.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($districtSummary as $districtName => $counts): ?>
                    <tr>
                        <td class="center"><?= e($no++) ?></td>
                        <td><?= e($districtName) ?></td>
                        <td class="center putra"><?= e($counts['putra']) ?></td>
                        <td class="center putri"><?= e($counts['putri']) ?></td>
                        <td class="center total"><?= e($counts['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2">JUMLAH TOTAL</td>
                    <td class="center putra"><?= e($grandTotal['putra']) ?></td>
                    <td class="center putri"><?= e($grandTotal['putri']) ?></td>
                    <td class="center total"><?= e($grandTotal['total']) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-title">B. Rekap Jumlah Peserta per Golongan (Kategori)</div>
    <table class="data">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-branch">Cabang</th>
                <th class="col-name">Golongan</th>
                <th class="col-count">Putra</th>
                <th class="col-count">Putri</th>
                <th class="col-count">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categorySummary)): ?>
                <tr>
                    <td colspan="6" class="center">Belum ada data peserta yang terdaftar.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($categorySummary as $item): ?>
                    <tr>
                        <td class="center"><?= e($no++) ?></td>
                        <td><?= e($item['branch']) ?></td>
                        <td><?= e($item['name']) ?></td>
                        <td class="center putra"><?= e($item['putra']) ?></td>
                        <td class="center putri"><?= e($item['putri']) ?></td>
                        <td class="center total"><?= e($item['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3">JUMLAH TOTAL</td>
                    <td class="center putra"><?= e($grandTotal['putra']) ?></td>
                    <td class="center putri"><?= e($grandTotal['putri']) ?></td>
                    <td class="center total"><?= e($grandTotal['total']) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table style="margin-top: 12px;">
        <tr>
            <td style="font-size: 10pt; color: #475569;">
                Laporan ini berisi rekapitulasi jumlah peserta berdasarkan kecamatan dan golongan/ketegori. Data diambil dari sistem e-MTQ secara otomatis.
            </td>
        </tr>
    </table>
</body>
</html>