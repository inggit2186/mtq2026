<?php
$rows = $rows ?? [];
$generatedAt = $generatedAt ?? now();
$selectedDistrict = $selectedDistrict ?? null;
$filters = $filters ?? [];
$documentConfig = $documentConfig ?? config('documents');
$scopeLabel = $selectedDistrict?->name ?? 'Semua Kecamatan';
$statusLabel = match ($filters['verification_status'] ?? '') {
    'verified' => 'Terverifikasi',
    'submitted' => 'Menunggu',
    'rejected' => 'Ditolak',
    'draft' => 'Draf',
    default => 'Semua Status',
};
$ageReferenceLabel = (string) config('juknis.age_reference_date', '1 Juli 2026');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=UTF-8">
    <title><?= e(config('app.name', 'e-MTQ').' - Export Data Verifikasi') ?></title>
    <style>
        body {
            margin: 0;
            font-family: Calibri, Arial, sans-serif;
            font-size: 11pt;
            color: #0f172a;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .title {
            font-size: 20pt;
            font-weight: 700;
        }

        .subtitle {
            font-size: 11pt;
            color: #475569;
        }

        .meta {
            margin-top: 12px;
            border: 1px solid #cbd5e1;
        }

        .meta td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            vertical-align: top;
        }

        .meta .label {
            width: 180px;
            font-weight: 700;
            background: #e0f2fe;
        }

        .data {
            margin-top: 18px;
        }

        .data th,
        .data td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
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

        .center {
            text-align: center;
        }

        .no {
            width: 34px;
        }

        .reg {
            width: 120px;
            font-weight: 700;
        }

        .name {
            width: 150px;
            font-weight: 700;
        }

        .district {
            width: 105px;
        }

        .branch {
            width: 120px;
        }

        .category {
            width: 135px;
        }

        .age {
            width: 105px;
            text-align: center;
            font-weight: 700;
        }

        .ver-group {
            width: 108px;
            text-align: center;
        }

        .ver-sub {
            width: 54px;
            text-align: center;
            font-weight: 700;
            line-height: 1.1;
        }

        .ver-sub small {
            display: block;
            margin-top: 3px;
            font-size: 8px;
            line-height: 1.25;
            font-weight: 600;
            color: #475569;
            text-transform: none;
        }

        .check-cell {
            width: 54px;
            text-align: center;
            vertical-align: middle;
        }

        .check-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
        }

        .check-yes {
            background: #dcfce7;
            color: #166534;
        }

        .check-no {
            background: #e2e8f0;
            color: #94a3b8;
        }

        .check-no-tms {
            background: #fee2e2;
            color: #991b1b;
        }

        .notes {
            width: auto;
        }

        .note {
            margin-top: 14px;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            padding: 10px 12px;
            font-size: 10pt;
            line-height: 1.6;
            color: #475569;
        }

        .footer {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 10pt;
            color: #64748b;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="title">Export Data Verifikasi</td>
        </tr>
        <tr>
            <td class="subtitle">
                <?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?> |
                <?= e($documentConfig['event_title'] ?? '') ?> | Dicetak <?= e($generatedAt->format('d M Y H:i')) ?>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="label">Ruang Lingkup</td>
            <td><?= e($scopeLabel) ?></td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td><?= e($statusLabel) ?></td>
        </tr>
        <tr>
            <td class="label">Umur dihitung per</td>
            <td><?= e($ageReferenceLabel) ?></td>
        </tr>
    </table>

    <table class="data">
        <thead>
                    <tr>
                        <th class="no" rowspan="2">No</th>
                        <th class="reg" rowspan="2">No Registrasi</th>
                        <th class="name" rowspan="2">Nama</th>
                        <th class="district" rowspan="2">Kecamatan</th>
                        <th class="branch" rowspan="2">Cabang</th>
                <th class="category" rowspan="2">Golongan</th>
                <th class="age" rowspan="2">Umur per 1 Juli</th>
                <th class="ver-group" colspan="2">Verifikasi</th>
                <th class="notes" rowspan="2">Keterangan</th>
            </tr>
            <tr>
                <th class="ver-sub">MS<small>Memenuhi Syarat</small></th>
                <th class="ver-sub">TMS<small>Tidak Memenuhi Syarat</small></th>
            </tr>
        </thead>
        <tbody>
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="10" class="center">Belum ada data peserta yang sesuai dengan filter export.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php
                        $status = (string) ($row['verification_status'] ?? '-');
                        $isVerified = $status === 'Terverifikasi';
                        $isRejected = $status === 'Ditolak';
                    ?>
                    <tr>
                        <td class="center"><?= e($index + 1) ?></td>
                        <td class="reg"><?= e($row['registration_number'] ?? '-') ?></td>
                        <td class="name"><?= e($row['name'] ?? '-') ?></td>
                        <td class="district"><?= e($row['district'] ?? '-') ?></td>
                        <td class="branch"><?= e($row['branch'] ?? '-') ?></td>
                        <td class="category"><?= e($row['category'] ?? '-') ?></td>
                        <td class="age"><?= e($row['age_per_reference'] ?? '-') ?></td>
                        <td class="check-cell">
                            <span class="check-badge <?= $isVerified ? 'check-yes' : 'check-no' ?>"><?= $isVerified ? '✓' : '' ?></span>
                        </td>
                        <td class="check-cell">
                            <span class="check-badge <?= $isRejected ? 'check-no-tms' : 'check-no' ?>"><?= $isRejected ? '✓' : '' ?></span>
                        </td>
                        <td class="notes"><?= e($row['verification_notes'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="note">
        Export ini sama dengan PDF data verifikasi. MS berarti <strong>Memenuhi Syarat</strong> dan TMS berarti <strong>Tidak Memenuhi Syarat</strong>.
    </div>

    <div class="footer">
        <div><?= e($documentConfig['signature_city'] ?? 'Batusangkar') ?>, <?= e($generatedAt->translatedFormat('d F Y')) ?></div>
        <div><?= e($selectedDistrict?->name ?? 'Semua kecamatan') ?></div>
    </div>
</body>
</html>
