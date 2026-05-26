<?php
$rows = $rows ?? [];
$generatedAt = $generatedAt ?? now();
$selectedDistrict = $selectedDistrict ?? null;
$filters = $filters ?? [];
$summary = $summary ?? [];
$documentConfig = $documentConfig ?? config('documents');
$scopeLabel = $selectedDistrict?->name ?? 'Semua Kecamatan';
$districtVerificationSummary = [];
foreach ($rows as $row) {
    $districtName = trim((string) ($row['district'] ?? ''));

    if ($districtName === '' || $districtName === '-') {
        continue;
    }

    if (! array_key_exists($districtName, $districtVerificationSummary)) {
        $districtVerificationSummary[$districtName] = 0;
    }

    if ((string) ($row['verification_status'] ?? '') === 'Terverifikasi') {
        $districtVerificationSummary[$districtName]++;
    }
}
$showDistrictVerificationSummary = $selectedDistrict === null && count($districtVerificationSummary) > 1;
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app.name', 'e-MTQ').' - Export PDF Peserta') ?></title>
    <style>
        @page {
            margin: 18mm 14mm 16mm;
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

        .topbar {
            border: 1px solid #dbe4f0;
            border-radius: 18px;
            overflow: hidden;
            margin-bottom: 14px;
        }

        .topbar-accent {
            height: 10px;
            background: linear-gradient(90deg, #0f766e 0%, #38bdf8 55%, #7c3aed 100%);
        }

        .topbar-body {
            padding: 16px 18px 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0;
        }

        .subtitle {
            margin-top: 5px;
            font-size: 11px;
            color: #475569;
            line-height: 1.6;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .district-summary {
            margin-top: 12px;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            background: #f8fbff;
            padding: 12px 14px;
        }

        .district-summary-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 8px;
        }

        .district-summary-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .district-summary-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 6px 10px;
            font-size: 10px;
            color: #334155;
        }

        .district-summary-item strong {
            color: #0f172a;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 6px 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #334155;
        }

        .pill strong {
            margin-left: 6px;
            letter-spacing: 0;
            text-transform: none;
            font-size: 10px;
            font-weight: 700;
            color: #0f172a;
        }

        .table-wrap {
            border: 1px solid #dbe4f0;
            border-radius: 16px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead th {
            background: #0f172a;
            color: #ffffff;
            font-size: 10px;
            line-height: 1.3;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 10px 8px;
            border-right: 1px solid rgba(255, 255, 255, 0.12);
        }

        thead th:last-child {
            border-right: 0;
        }

        tbody td {
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            padding: 9px 8px;
            font-size: 10.5px;
            line-height: 1.45;
            vertical-align: top;
            word-break: break-word;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        tbody td:last-child {
            border-right: 0;
        }

        .no {
            width: 38px;
            text-align: center;
            font-weight: 700;
        }

        .photo {
            width: 54px;
            text-align: center;
        }

        .photo-thumb {
            display: block;
            width: 34px;
            height: 44px;
            margin: 0 auto;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #cbd5e1;
            background: #e2e8f0;
        }

        .photo-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 44px;
            margin: 0 auto;
            border-radius: 8px;
            border: 1px dashed #94a3b8;
            background: #f8fafc;
            color: #94a3b8;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .reg {
            width: 108px;
            white-space: nowrap;
            font-weight: 700;
        }

        .name {
            width: 134px;
            font-weight: 700;
        }

        .district {
            width: 92px;
        }

        .branch {
            width: 100px;
        }

        .category {
            width: 118px;
        }

        .age {
            width: 92px;
            white-space: nowrap;
            text-align: center;
            font-weight: 700;
        }

        .verification-header {
            width: 112px;
            text-align: center;
        }

        .status-sub {
            width: 56px;
            text-align: center;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.15;
            padding: 8px 4px 7px;
        }

        .status-sub small {
            display: block;
            margin-top: 4px;
            font-size: 8px;
            line-height: 1.25;
            letter-spacing: 0.02em;
            text-transform: none;
            color: rgba(255, 255, 255, 0.78);
            font-weight: 600;
        }

        .status-cell {
            width: 56px;
            text-align: center;
            vertical-align: middle;
            padding: 8px 4px;
        }

        .notes {
            width: auto;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            margin: 0 auto;
        }

        .status-verified {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-draft {
            background: #e2e8f0;
            color: #94a3b8;
        }

        .empty {
            text-align: center;
            padding: 18px 12px;
            color: #475569;
            font-size: 11px;
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

        .footer-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            font-size: 10.5px;
            color: #64748b;
        }

        @media print {
            .topbar, .table-wrap, .footer-note {
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
        <div class="topbar">
            <div class="topbar-accent"></div>
            <div class="topbar-body">
                <h1 class="title">Rekap Data Peserta</h1>
                <div class="subtitle">
                    <?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?> |
                    <?= e($documentConfig['event_title'] ?? '') ?>
                </div>
                <div class="meta">
                    <span class="pill">Dicetak <strong><?= e($generatedAt->format('d M Y H:i')) ?></strong></span>
                    <span class="pill">Ruang lingkup <strong><?= e($scopeLabel) ?></strong></span>
                    <span class="pill">Jumlah terverifikasi <strong><?= e($summary['verified'] ?? 0) ?></strong></span>
                    <span class="pill">Status <strong><?= e($statusLabel) ?></strong></span>
                    <span class="pill">Umur dihitung per <strong><?= e($ageReferenceLabel) ?></strong></span>
                </div>
                <?php if ($showDistrictVerificationSummary): ?>
                    <div class="district-summary">
                        <div class="district-summary-title">Rincian terverifikasi per kecamatan</div>
                        <div class="district-summary-grid">
                            <?php foreach ($districtVerificationSummary as $districtName => $verifiedCount): ?>
                                <span class="district-summary-item">
                                    <?= e($districtName) ?> <strong><?= e($verifiedCount) ?> terverifikasi</strong>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="no" rowspan="2">No</th>
                        <th class="photo" rowspan="2">Foto</th>
                        <th class="reg" rowspan="2">No Registrasi</th>
                        <th class="name" rowspan="2">Nama</th>
                        <th class="district" rowspan="2">Kecamatan</th>
                        <th class="branch" rowspan="2">Cabang</th>
                        <th class="category" rowspan="2">Golongan</th>
                        <th class="age" rowspan="2">Umur per 1 Juli</th>
                        <th class="verification-header" colspan="2">Verifikasi</th>
                        <th class="notes" rowspan="2">Keterangan</th>
                    </tr>
                    <tr>
                        <th class="status-sub">MS<small>Memenuhi Syarat</small></th>
                        <th class="status-sub">BTL<small>Perbaikan</small></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="11" class="empty">Belum ada data peserta yang sesuai dengan filter export.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $index => $row): ?>
                            <?php
                                $status = (string) ($row['verification_status'] ?? '-');
                                $isVerified = $status === 'Terverifikasi';
                                $isRejected = $status === 'Ditolak';
                                $msClass = $isVerified ? 'status-verified' : 'status-draft';
                                $tmsClass = $isRejected ? 'status-rejected' : 'status-draft';
                            ?>
                            <tr>
                                <td class="no"><?= e($index + 1) ?></td>
                                <td class="photo">
                                    <?php if (! empty($row['photo_url'])): ?>
                                        <img src="<?= e($row['photo_url']) ?>" alt="<?= e($row['name'] ?? 'Foto peserta') ?>" class="photo-thumb">
                                    <?php else: ?>
                                        <span class="photo-placeholder">Foto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="reg"><?= e($row['registration_number'] ?? '-') ?></td>
                                <td class="name"><?= e($row['name'] ?? '-') ?></td>
                                <td class="district"><?= e($row['district'] ?? '-') ?></td>
                                <td class="branch"><?= e($row['branch'] ?? '-') ?></td>
                                <td class="category"><?= e($row['category'] ?? '-') ?></td>
                                <td class="age"><?= e($row['age_per_reference'] ?? '-') ?></td>
                                <td class="status-cell">
                                    <span class="status-pill <?= e($msClass) ?>"><?= $isVerified ? '✓' : '' ?></span>
                                </td>
                                <td class="status-cell">
                                    <span class="status-pill <?= e($tmsClass) ?>"><?= $isRejected ? '✓' : '' ?></span>
                                </td>
                                <td class="notes"><?= e($row['verification_notes'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer-note">
            Export ini hanya menampilkan data inti peserta untuk kebutuhan administrasi dan verifikasi. Jika dibutuhkan detail berkas, gunakan tampilan detail peserta di sistem.
        </div>

        <div class="footer-line">
            <div><?= e($documentConfig['signature_city'] ?? 'Batusangkar') ?>, <?= e($generatedAt->translatedFormat('d F Y')) ?></div>
            <div><?= e($selectedDistrict?->name ?? 'Semua kecamatan') ?></div>
        </div>
    </div>
</body>
</html>
