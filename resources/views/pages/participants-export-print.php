<?php
$rows = $rows ?? [];
$summary = $summary ?? [];
$generatedAt = $generatedAt ?? now();
$selectedDistrict = $selectedDistrict ?? null;
$filters = $filters ?? [];
$documentConfig = $documentConfig ?? config('documents');
$officials = $documentConfig['officials'] ?? [];
$isPreview = request()->boolean('preview');
$scopeLabel = $selectedDistrict?->name ?? 'Semua Kecamatan';
$statusLabel = match ($filters['verification_status'] ?? '') {
    'verified' => 'Terverifikasi',
    'submitted' => 'Menunggu',
    'rejected' => 'Ditolak',
    'draft' => 'Draf',
    default => 'Semua Status',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app.name', 'e-MTQ').' - Export Rekap Peserta') ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 24px; }
        h1, h2, h3, p { margin: 0; }
        .header { display: flex; justify-content: space-between; gap: 18px; border-bottom: 3px solid #0f172a; padding-bottom: 16px; margin-bottom: 18px; }
        .muted { color: #475569; }
        .chip { display: inline-block; border: 1px solid #94a3b8; border-radius: 999px; padding: 5px 10px; font-size: 12px; margin-top: 8px; }
        .summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 18px 0; }
        .card { border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; }
        .card strong { display: block; font-size: 22px; margin-top: 8px; }
        .meta { display: grid; grid-template-columns: 1.5fr 1fr; gap: 14px; margin-bottom: 14px; }
        .meta-box { border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 12px; }
        .table th, .table td { border: 1px solid #cbd5e1; padding: 8px; vertical-align: top; }
        .table th { background: #e2e8f0; text-align: center; }
        .center { text-align: center; }
        .note { border: 1px dashed #94a3b8; border-radius: 12px; padding: 12px; margin-top: 18px; font-size: 13px; line-height: 1.6; }
        .signatures { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin-top: 36px; }
        .signature-box { text-align: center; }
        .signature-line { margin: 66px auto 8px; width: 80%; border-top: 1px solid #0f172a; }
        @media print {
            body { margin: 14px; }
            .summary { page-break-inside: avoid; }
        }
    </style>
</head>
<body<?= $isPreview ? '' : ' onload="window.print()"' ?>>
    <div class="header">
        <div>
            <h1>Rekap Peserta Terdaftar</h1>
            <p class="muted" style="margin-top: 6px;"><?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?></p>
            <p class="muted" style="margin-top: 4px;"><?= e($documentConfig['event_title'] ?? '') ?></p>
            <div class="chip">Siap disimpan sebagai PDF</div>
        </div>
        <div style="text-align: right;">
            <p><strong>Dicetak:</strong> <?= e($generatedAt->format('d M Y H:i')) ?></p>
            <p class="muted" style="margin-top: 6px;">Ruang lingkup: <?= e($scopeLabel) ?></p>
        </div>
    </div>

    <div class="meta">
        <div class="meta-box">
            <h3>Filter Export</h3>
            <p class="muted" style="margin-top: 8px;">Dokumen ini mengikuti filter `Data Peserta` yang aktif saat export dijalankan.</p>
            <p style="margin-top: 10px;"><strong>Kecamatan:</strong> <?= e($scopeLabel) ?></p>
            <p style="margin-top: 4px;"><strong>Status:</strong> <?= e($statusLabel) ?></p>
            <p style="margin-top: 4px;"><strong>Kata kunci:</strong> <?= e($filters['keyword'] ?? '-') ?></p>
        </div>
        <div class="meta-box">
            <h3>Keterangan Checklist</h3>
            <p class="muted" style="margin-top: 8px;">Kolom dokumen memakai tanda `✓` jika file sudah diupload dan `-` jika belum tersedia.</p>
        </div>
    </div>

    <div class="summary">
        <div class="card"><span class="muted">Total Peserta</span><strong><?= e($summary['total'] ?? 0) ?></strong></div>
        <div class="card"><span class="muted">Peserta Inti</span><strong><?= e($summary['main_total'] ?? 0) ?></strong></div>
        <div class="card"><span class="muted">Peserta Cadangan</span><strong><?= e($summary['reserve_total'] ?? 0) ?></strong></div>
        <div class="card"><span class="muted">Terverifikasi</span><strong><?= e($summary['verified'] ?? 0) ?></strong></div>
    </div>
    <div class="summary" style="margin-top: 0;">
        <div class="card"><span class="muted">Menunggu</span><strong><?= e($summary['pending'] ?? 0) ?></strong></div>
        <div class="card"><span class="muted">Draf</span><strong><?= e($summary['draft'] ?? 0) ?></strong></div>
        <div class="card"><span class="muted">Ditolak</span><strong><?= e($summary['rejected'] ?? 0) ?></strong></div>
        <div class="card"><span class="muted">Keperluan</span><strong style="font-size:16px;">Arsip dan Verifikasi</strong></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Registrasi / Status</th>
                <th>Peserta</th>
                <th>Kecamatan</th>
                <th>Cabang / Golongan</th>
                <th>Dokumen</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" class="center">Belum ada data peserta yang sesuai dengan filter export.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td class="center"><?= e($index + 1) ?></td>
                    <td>
                        <strong><?= e($row['registration_number']) ?></strong><br>
                        <span class="muted"><?= e($row['role_label']) ?> | <?= e($row['verification_status']) ?></span>
                    </td>
                    <td>
                        <strong><?= e($row['name']) ?></strong><br>
                        <span class="muted"><?= e($row['gender']) ?> | <?= e($row['nik']) ?></span><br>
                        <span class="muted"><?= e(trim($row['place_of_birth'].', '.$row['date_of_birth'], ', ')) ?></span><br>
                        <span class="muted"><?= e($row['institution']) ?> | <?= e($row['last_education']) ?></span><br>
                        <span class="muted">HP <?= e($row['phone']) ?></span>
                    </td>
                    <td><?= e($row['district']) ?></td>
                    <td><?= e($row['branch']) ?><br><span class="muted"><?= e($row['category']) ?></span></td>
                    <td>
                        KK <?= e($row['documents']['kk']) ?> |
                        KTP <?= e($row['documents']['ktp']) ?> |
                        Akta <?= e($row['documents']['birth_certificate']) ?><br>
                        Foto <?= e($row['documents']['photo']) ?> |
                        Ijazah <?= e($row['documents']['last_diploma']) ?> |
                        Tabungan <?= e($row['documents']['bank_book']) ?><br>
                        Piagam <?= e($row['documents']['certificates']) ?> |
                        Lainnya <?= e($row['documents']['other_files']) ?>
                    </td>
                    <td>
                        <span class="muted">KK <?= e($row['kk_number']) ?> | <?= e($row['kk_date']) ?></span><br>
                        <span class="muted">KTP <?= e($row['ktp_date']) ?></span><br>
                        <span class="muted">Bank <?= e($row['bank_name']) ?> / <?= e($row['bank_account_number']) ?></span><br>
                        <span class="muted"><?= e($row['verification_notes']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="note">
        Rekap ini memuat data peserta yang telah didaftarkan pada sistem beserta indikator kelengkapan file upload. Official kecamatan hanya dapat mencetak data kecamatan sendiri, sedangkan admin dan panitia dapat mencetak per kecamatan atau seluruh kecamatan sesuai filter.
    </div>

    <div style="margin-top: 24px; text-align: right;">
        <p><?= e(($documentConfig['signature_city'] ?? 'Batusangkar').', '.$generatedAt->translatedFormat('d F Y')) ?></p>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <p><?= e($officials['committee_chair']['title'] ?? 'Ketua Panitia') ?></p>
            <div class="signature-line"></div>
            <p class="muted"><?= e($officials['committee_chair']['name'] ?? '........................................') ?></p>
        </div>
        <div class="signature-box">
            <p><?= e($officials['secretary']['title'] ?? 'Sekretaris') ?></p>
            <div class="signature-line"></div>
            <p class="muted"><?= e($officials['secretary']['name'] ?? '........................................') ?></p>
        </div>
        <div class="signature-box">
            <p><?= e($selectedDistrict?->name ? 'Official Kecamatan / Penanggung Jawab' : 'Koordinator Data') ?></p>
            <div class="signature-line"></div>
            <p class="muted"><?= e($selectedDistrict?->name ?? '........................................') ?></p>
        </div>
    </div>
</body>
</html>
