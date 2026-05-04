<?php
$documentConfig = $documentConfig ?? config('documents');
$participant = $participant ?? null;
$documentRows = $documentRows ?? [];
$summaryRows = $summaryRows ?? [];
$photoDataUri = $photoDataUri ?? null;
$initials = $initials ?? 'P';
$verifiedAt = $verifiedAt ?? now();
$verifiedBy = $verifiedBy ?? 'Panitia e-MTQ';
$documentComplete = $documentComplete ?? 0;
$documentTotal = $documentTotal ?? count($documentRows);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #0f172a; }
        h1, h2, h3, p { margin: 0; }
        .hero { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .hero td { vertical-align: top; }
        .brand-bar { width: 18px; background: #0ea5e9; }
        .brand-panel { background: #0f172a; color: #f8fafc; padding: 16px 18px; }
        .brand-title { font-size: 18px; font-weight: 700; letter-spacing: 0.04em; }
        .brand-subtitle { margin-top: 4px; color: #cbd5e1; line-height: 1.5; }
        .badge { display: inline-block; margin-top: 10px; padding: 5px 10px; border-radius: 999px; background: #0ea5e9; color: #ffffff; font-size: 9px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; }
        .meta-box { background: #e2e8f0; padding: 14px 16px; text-align: right; }
        .meta-box .label { color: #475569; font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; }
        .meta-box .value { margin-top: 4px; font-size: 12px; font-weight: 700; color: #0f172a; }
        .section { border: 1px solid #cbd5e1; border-radius: 16px; overflow: hidden; margin-bottom: 12px; width: 100%; border-collapse: collapse; }
        .section-head { background: #f8fafc; border-bottom: 1px solid #cbd5e1; padding: 10px 14px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #0f172a; }
        .section-body { padding: 14px; }
        .profile { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .profile td { vertical-align: top; }
        .photo-box { width: 108px; height: 138px; border: 1px solid #cbd5e1; border-radius: 16px; overflow: hidden; background: #e2e8f0; text-align: center; }
        .photo-box img { width: 108px; height: 138px; display: block; }
        .initials { width: 108px; height: 138px; background: #0f172a; color: #f8fafc; font-size: 30px; font-weight: 700; line-height: 138px; }
        .name { font-size: 20px; font-weight: 700; color: #0f172a; }
        .role { margin-top: 4px; color: #0369a1; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.16em; }
        .muted { color: #475569; line-height: 1.6; }
        .pill { display: inline-block; margin-top: 8px; padding: 5px 10px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { width: 50%; padding: 7px 0; vertical-align: top; }
        .summary-label { color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: 0.12em; }
        .summary-value { margin-top: 3px; color: #0f172a; font-weight: 700; line-height: 1.45; }
        .stats { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .stats td { width: 25%; padding: 10px; border: 1px solid #cbd5e1; background: #f8fafc; }
        .stats .label { color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: 0.12em; }
        .stats .value { margin-top: 6px; font-size: 18px; font-weight: 700; color: #0f172a; }
        .document-row { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .document-row td { padding: 8px 0; border-bottom: 1px dashed #dbe3ec; vertical-align: top; }
        .document-row td:last-child { text-align: right; font-weight: 700; }
        .ok { color: #166534; }
        .warn { color: #b45309; }
        .notes { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 14px; padding: 12px 14px; line-height: 1.65; color: #9a3412; }
        .footer { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .signature { width: 50%; vertical-align: top; text-align: center; }
        .line { margin: 54px auto 8px; width: 72%; border-top: 1px solid #0f172a; }
    </style>
</head>
<body>
    <table class="hero">
        <tr>
            <td class="brand-bar"></td>
            <td class="brand-panel">
                <div class="brand-title"><?= e($documentConfig['organization_name'] ?? 'e-MTQ') ?></div>
                <div class="brand-subtitle"><?= e($documentConfig['event_title'] ?? 'Curriculum Vitae Peserta Terverifikasi') ?></div>
                <div class="badge">CV PESERTA TERVERIFIKASI</div>
            </td>
            <td class="meta-box" style="width: 210px;">
                <div class="label">Dicetak</div>
                <div class="value"><?= e($verifiedAt->format('d M Y H:i')) ?></div>
                <div class="label" style="margin-top: 10px;">Status</div>
                <div class="value"><?= e($participant->verification_status === 'verified' ? 'Terverifikasi' : ucfirst((string) $participant->verification_status)) ?></div>
            </td>
        </tr>
    </table>

    <table class="profile">
        <tr>
            <td class="section-head" colspan="2">Profil Utama</td>
        </tr>
        <tr>
            <td class="section-body" style="width: 140px;">
                <div class="photo-box">
                    <?php if ($photoDataUri): ?>
                        <img src="<?= e($photoDataUri) ?>" alt="<?= e($participant->name) ?>">
                    <?php else: ?>
                        <div class="initials"><?= e($initials) ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="section-body">
                <div class="name"><?= e($participant->name) ?></div>
                <div class="role"><?= e($roleLabel) ?></div>
                <p class="muted" style="margin-top: 8px;">
                    <?= e($branchLabel) ?><br>
                    <?= e($districtLabel) ?> | <?= e($participant->institution ?: '-') ?>
                </p>
                <div class="pill">Nomor Registrasi: <?= e($participant->registration_number ?: '-') ?></div>
            </td>
        </tr>
    </table>

    <table class="stats">
        <tr>
            <td><div class="label">NIK</div><div class="value"><?= e($participant->nik ?: '-') ?></div></td>
            <td><div class="label">Tempat, Tgl Lahir</div><div class="value"><?= e(trim((string) ($participant->place_of_birth ?? '-').', '.optional($participant->date_of_birth)->format('d M Y'))) ?></div></td>
            <td><div class="label">Umur</div><div class="value"><?= e($ageLabel) ?></div></td>
            <td><div class="label">No. HP</div><div class="value"><?= e($participant->phone ?: '-') ?></div></td>
        </tr>
    </table>

    <table class="section">
        <tr><td class="section-head">Ringkasan Data</td></tr>
        <tr>
            <td class="section-body">
                <table class="summary-table">
                    <?php foreach (array_chunk($summaryRows, 2) as $pair): ?>
                        <tr>
                            <?php foreach ($pair as $row): ?>
                                <td>
                                    <div class="summary-label"><?= e($row['label']) ?></div>
                                    <div class="summary-value"><?= e($row['value']) ?></div>
                                </td>
                            <?php endforeach; ?>
                            <?php if (count($pair) === 1): ?>
                                <td></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
    </table>

    <table class="section">
        <tr><td class="section-head">Kelengkapan Berkas</td></tr>
        <tr>
            <td class="section-body">
                <table class="stats">
                    <tr>
                        <td><div class="label">Dokumen Siap</div><div class="value"><?= e($documentComplete) ?>/<?= e($documentTotal) ?></div></td>
                        <td><div class="label">Diverifikasi</div><div class="value"><?= e($verifiedBy) ?></div></td>
                        <td><div class="label">Status</div><div class="value"><?= e($verifiedStatusLabel) ?></div></td>
                        <td><div class="label">Cabang</div><div class="value"><?= e($participant->category?->branch ?? '-') ?></div></td>
                    </tr>
                </table>

                <table class="document-row">
                    <?php foreach ($documentRows as $row): ?>
                        <tr>
                            <td>
                                <?= e($row['label']) ?>
                                <?php if (! empty($row['note'])): ?>
                                    <div class="muted" style="font-size: 9px; margin-top: 2px;"><?= e($row['note']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="<?= ! empty($row['available']) ? 'ok' : 'warn' ?>">
                                <?= ! empty($row['available']) ? 'Tersedia'.(isset($row['count']) ? ' ('.$row['count'].' file)' : '') : 'Belum ada' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
    </table>

    <table class="section">
        <tr><td class="section-head">Catatan Verifikasi</td></tr>
        <tr>
            <td class="section-body">
                <div class="notes"><?= e($verificationNotes) ?></div>
            </td>
        </tr>
    </table>

    <table class="footer">
        <tr>
            <td class="signature">
                <div class="muted"><?= e(($documentConfig['signature_city'] ?? 'Batusangkar').', '.$verifiedAt->translatedFormat('d F Y')) ?></div>
                <div class="line"></div>
                <div class="muted"><?= e($verifiedBy) ?></div>
            </td>
            <td class="signature">
                <div class="muted">Dokumen diterbitkan otomatis oleh sistem e-MTQ</div>
                <div class="line"></div>
                <div class="muted">Status peserta terverifikasi</div>
            </td>
        </tr>
    </table>
</body>
</html>
