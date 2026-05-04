<?php
$rows = $rows ?? [];
$summary = $summary ?? [];
$generatedAt = $generatedAt ?? now();
$selectedDistrict = $selectedDistrict ?? null;
$filters = $filters ?? [];
$documentConfig = $documentConfig ?? config('documents');
$scopeLabel = $selectedDistrict?->name ?? 'Semua Kecamatan';
$statusLabel = match ($filters['verification_status'] ?? '') {
    'verified' => 'Terverifikasi',
    'submitted' => 'Menunggu',
    'rejected' => 'Ditolak',
    'draft' => 'Draft',
    default => 'Semua Status',
};
$categoryLabel = 'Semua Golongan';
if (filled($filters['competition_category_id'] ?? null) && isset($participants) && $participants instanceof \Illuminate\Support\Collection) {
    $firstCategoryParticipant = $participants->firstWhere('competition_category_id', (int) $filters['competition_category_id']);
    if ($firstCategoryParticipant?->category) {
        $categoryLabel = trim(($firstCategoryParticipant->category->branch ?? '').' - '.($firstCategoryParticipant->category->name ?? ''));
    }
}

$textColumnsStyle = "mso-number-format:'\\@'; white-space:nowrap;";
$metaRows = [
    'Ruang Lingkup Export' => $scopeLabel,
    'Status Verifikasi' => $statusLabel,
    'Golongan' => $categoryLabel,
    'Kata Kunci' => filled($filters['keyword'] ?? null) ? (string) $filters['keyword'] : '-',
];
$summaryCards = [
    ['label' => 'Total Peserta', 'value' => $summary['total'] ?? 0],
    ['label' => 'Peserta Inti', 'value' => $summary['main_total'] ?? 0],
    ['label' => 'Peserta Cadangan', 'value' => $summary['reserve_total'] ?? 0],
    ['label' => 'Terverifikasi', 'value' => $summary['verified'] ?? 0],
    ['label' => 'Menunggu', 'value' => $summary['pending'] ?? 0],
    ['label' => 'Draft', 'value' => $summary['draft'] ?? 0],
    ['label' => 'Ditolak', 'value' => $summary['rejected'] ?? 0],
];
$documentValue = static fn ($value): string => $value === '-' ? '-' : 'Ya';
?>
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
            width: 14.28%;
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

        .note {
            margin-top: 12px;
            border: 1px solid #bae6fd;
            background: #f0f9ff;
            color: #0f172a;
            padding: 10px 12px;
            font-size: 10pt;
        }

        .data {
            margin-top: 18px;
        }

        .data th,
        .data td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            vertical-align: top;
        }

        .data thead th {
            background: #dbeafe;
            color: #0f172a;
            font-size: 10pt;
            font-weight: 700;
            text-align: center;
        }

        .data tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .data td.center {
            text-align: center;
        }

        .data td.middle {
            vertical-align: middle;
        }

        .data td.compact {
            font-size: 10pt;
        }

        .data td.wrap {
            white-space: normal;
        }

        .data td.nowrap {
            white-space: nowrap;
        }

        .col-no { width: 42px; }
        .col-reg { width: 150px; }
        .col-role { width: 90px; }
        .col-status { width: 110px; }
        .col-name { width: 180px; }
        .col-gender { width: 70px; }
        .col-nik { width: 145px; }
        .col-birth { width: 180px; }
        .col-phone { width: 110px; }
        .col-district { width: 110px; }
        .col-branch { width: 170px; }
        .col-category { width: 170px; }
        .col-instansi { width: 170px; }
        .col-edu { width: 110px; }
        .col-kk { width: 145px; }
        .col-date { width: 90px; }
        .col-bank { width: 110px; }
        .col-rek { width: 150px; }
        .col-rek-name { width: 150px; }
        .col-address { width: 220px; }
        .col-short { width: 90px; }
        .col-doc { width: 60px; }
        .col-notes { width: 220px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="sheet-title">Rekap Peserta Terdaftar</td>
        </tr>
        <tr>
            <td class="sheet-subtitle"><?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?> | Dicetak <?= e($generatedAt->format('d/m/Y H:i')) ?></td>
        </tr>
    </table>

    <table class="meta">
        <?php foreach ($metaRows as $label => $value): ?>
            <tr>
                <td class="label"><?= e($label) ?></td>
                <td><?= e($value) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <table class="summary">
        <tr>
            <?php foreach ($summaryCards as $card): ?>
                <td class="card-label"><?= e($card['label']) ?></td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <?php foreach ($summaryCards as $card): ?>
                <td class="card-value"><?= e($card['value']) ?></td>
            <?php endforeach; ?>
        </tr>
    </table>

    <table class="note">
        <tr>
            <td>
                Kolom identitas numerik seperti NIK, No. KK, No. HP, dan No. Rekening diformat sebagai teks agar angka panjang tetap utuh saat file dibuka di Excel.
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-reg">No. Registrasi</th>
                <th class="col-role">Status Peserta</th>
                <th class="col-status">Status Verifikasi</th>
                <th class="col-name">Nama</th>
                <th class="col-gender">Gender</th>
                <th class="col-nik">NIK</th>
                <th class="col-birth">Tempat, Tgl Lahir</th>
                <th class="col-phone">No. HP</th>
                <th class="col-district">Kecamatan</th>
                <th class="col-branch">Cabang</th>
                <th class="col-category">Golongan</th>
                <th class="col-instansi">Instansi</th>
                <th class="col-edu">Pendidikan</th>
                <th class="col-kk">No. KK</th>
                <th class="col-date">Tgl KK</th>
                <th class="col-date">Tgl KTP</th>
                <th class="col-bank">Bank</th>
                <th class="col-rek">No. Rekening</th>
                <th class="col-rek-name">Atas Nama Rekening</th>
                <th class="col-address">Alamat Sekarang</th>
                <th class="col-address">Alamat KTP</th>
                <th class="col-short">Kec. KTP</th>
                <th class="col-short">Kab/Kota KTP</th>
                <th class="col-doc">KK</th>
                <th class="col-doc">KTP</th>
                <th class="col-doc">Akta</th>
                <th class="col-doc">Foto</th>
                <th class="col-doc">Ijazah</th>
                <th class="col-doc">Tabungan</th>
                <th class="col-doc">Piagam</th>
                <th class="col-doc">Lainnya</th>
                <th class="col-notes">Catatan Verifikasi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="33" class="center middle">Belum ada peserta yang sesuai dengan filter export.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td class="center middle"><?= e($index + 1) ?></td>
                    <td class="compact nowrap" style="<?= $textColumnsStyle ?>"><?= e($row['registration_number']) ?></td>
                    <td><?= e($row['role_label']) ?></td>
                    <td><?= e($row['verification_status']) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td class="nowrap"><?= e($row['gender']) ?></td>
                    <td class="compact nowrap" style="<?= $textColumnsStyle ?>" x:str><?= e($row['nik']) ?></td>
                    <td class="wrap"><?= e(trim($row['place_of_birth'].', '.$row['date_of_birth'], ', ')) ?></td>
                    <td class="nowrap" style="<?= $textColumnsStyle ?>" x:str><?= e($row['phone']) ?></td>
                    <td><?= e($row['district']) ?></td>
                    <td class="wrap"><?= e($row['branch']) ?></td>
                    <td class="wrap"><?= e($row['category']) ?></td>
                    <td class="wrap"><?= e($row['institution']) ?></td>
                    <td><?= e($row['last_education']) ?></td>
                    <td class="compact nowrap" style="<?= $textColumnsStyle ?>" x:str><?= e($row['kk_number']) ?></td>
                    <td class="nowrap"><?= e($row['kk_date']) ?></td>
                    <td class="nowrap"><?= e($row['ktp_date']) ?></td>
                    <td><?= e($row['bank_name']) ?></td>
                    <td class="compact nowrap" style="<?= $textColumnsStyle ?>" x:str><?= e($row['bank_account_number']) ?></td>
                    <td><?= e($row['bank_account_name']) ?></td>
                    <td class="wrap"><?= e($row['current_address']) ?></td>
                    <td class="wrap"><?= e($row['ktp_address']) ?></td>
                    <td><?= e($row['ktp_district']) ?></td>
                    <td><?= e($row['ktp_regency']) ?></td>
                    <td class="center"><?= e($documentValue($row['documents']['kk'] ?? '-')) ?></td>
                    <td class="center"><?= e($documentValue($row['documents']['ktp'] ?? '-')) ?></td>
                    <td class="center"><?= e($documentValue($row['documents']['birth_certificate'] ?? '-')) ?></td>
                    <td class="center"><?= e($documentValue($row['documents']['photo'] ?? '-')) ?></td>
                    <td class="center"><?= e($documentValue($row['documents']['last_diploma'] ?? '-')) ?></td>
                    <td class="center"><?= e($documentValue($row['documents']['bank_book'] ?? '-')) ?></td>
                    <td class="center"><?= e($documentValue($row['documents']['certificates'] ?? '-')) ?></td>
                    <td class="center"><?= e($documentValue($row['documents']['other_files'] ?? '-')) ?></td>
                    <td class="wrap"><?= e($row['verification_notes']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
