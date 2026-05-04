<?php
$documentConfig = $documentConfig ?? [];
$guideTitle = $guideTitle ?? 'Buku Petunjuk Pendaftaran Peserta MTQ';
$guideSubtitle = $guideSubtitle ?? '';
$stepScreenshots = $stepScreenshots ?? [];
$checklistItems = $checklistItems ?? [];
$tipsItems = $tipsItems ?? [];
$footerNote = $footerNote ?? '';
$orgName = $documentConfig['organization_name'] ?? 'e-MTQ';
$eventTitle = $documentConfig['event_title'] ?? 'Panduan Peserta';
$signatureCity = $documentConfig['signature_city'] ?? 'Batusangkar';
$firstImage = $stepScreenshots[0]['src'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16mm 15mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 10.5px;
            line-height: 1.55;
            background: #ffffff;
        }
        h1, h2, h3, p { margin: 0; }
        .cover { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .cover-left { width: 57%; background: #0f172a; color: #f8fafc; padding: 26px; border-radius: 24px 0 0 24px; vertical-align: top; }
        .cover-right { width: 43%; background: #e2e8f0; padding: 18px; border-radius: 0 24px 24px 0; vertical-align: top; }
        .eyebrow { color: #7dd3fc; text-transform: uppercase; letter-spacing: 0.18em; font-size: 9px; font-weight: 700; }
        .cover-title { margin-top: 14px; font-size: 28px; line-height: 1.15; font-weight: 800; }
        .cover-subtitle { margin-top: 12px; color: #cbd5e1; font-size: 12px; line-height: 1.7; }
        .cover-points { margin-top: 18px; border-top: 1px solid rgba(148, 163, 184, 0.18); padding-top: 14px; }
        .cover-point { display: block; margin-bottom: 8px; font-size: 11px; color: #e2e8f0; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 999px; background: #22d3ee; color: #022c41; font-size: 9px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; }
        .cover-frame { padding: 12px; background: #ffffff; border-radius: 18px; border: 1px solid #cbd5e1; }
        .cover-frame img { width: 100%; display: block; border-radius: 14px; border: 1px solid #e2e8f0; }
        .cover-caption { margin-top: 10px; color: #475569; font-size: 10px; line-height: 1.5; }
        .section { border: 1px solid #cbd5e1; border-radius: 18px; overflow: hidden; margin-bottom: 14px; }
        .section-head { background: #eff6ff; border-bottom: 1px solid #cbd5e1; padding: 12px 16px; }
        .section-kicker { color: #0284c7; text-transform: uppercase; letter-spacing: 0.16em; font-size: 9px; font-weight: 700; }
        .section-title { margin-top: 6px; font-size: 17px; font-weight: 800; color: #0f172a; }
        .section-body { padding: 16px; }
        .grid-2 { width: 100%; border-collapse: collapse; }
        .grid-2 td { width: 50%; vertical-align: top; padding: 0 6px; }
        .checklist { width: 100%; border-collapse: collapse; }
        .checklist td { padding: 10px 0; border-bottom: 1px dashed #dbe3ec; vertical-align: top; }
        .check { width: 20px; color: #16a34a; font-weight: 800; }
        .check-text { color: #0f172a; font-size: 10.5px; line-height: 1.6; }
        .step { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .step td { vertical-align: top; }
        .step-num { width: 34px; height: 34px; border-radius: 999px; background: #0ea5e9; color: #ffffff; text-align: center; font-size: 13px; font-weight: 800; line-height: 34px; }
        .step-text { padding-left: 12px; }
        .step-title { font-size: 14px; font-weight: 800; color: #0f172a; }
        .step-desc { margin-top: 4px; color: #334155; font-size: 10.5px; }
        .shot { margin-top: 12px; border: 1px solid #cbd5e1; border-radius: 16px; overflow: hidden; background: #ffffff; }
        .shot img { width: 100%; display: block; }
        .shot-caption { padding: 10px 12px; background: #f8fafc; color: #475569; font-size: 9.5px; line-height: 1.5; }
        .pill { display: inline-block; padding: 5px 10px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 9px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; }
        .tips { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 16px; padding: 14px; }
        .tips ul { margin: 8px 0 0; padding: 0 0 0 18px; }
        .tips li { margin-bottom: 8px; color: #9a3412; font-size: 10.5px; line-height: 1.6; }
        .footer-note { margin-top: 10px; padding: 12px 14px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; color: #1e3a8a; font-size: 10.5px; line-height: 1.6; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <table class="cover">
        <tr>
            <td class="cover-left">
                <div class="eyebrow"><?= e($orgName) ?></div>
                <h1 class="cover-title"><?= e($guideTitle) ?></h1>
                <p class="cover-subtitle"><?= e($guideSubtitle ?: $eventTitle) ?></p>
                <div style="margin-top:16px;"><span class="badge">PDF Guide</span></div>
                <div class="cover-points">
                    <span class="cover-point">Untuk official kecamatan yang mendaftarkan peserta MTQ ke dalam aplikasi e-MTQ.</span>
                    <span class="cover-point">Menjelaskan alur dari pemilihan golongan sampai review akhir sebelum simpan.</span>
                    <span class="cover-point">Disusun ringkas, jelas, dan nyaman dibaca saat dicetak atau dibagikan.</span>
                </div>
            </td>
            <td class="cover-right">
                <div class="cover-frame">
                    <?php if ($firstImage): ?>
                        <img src="<?= e($firstImage) ?>" alt="Screenshot peserta">
                    <?php endif; ?>
                    <div class="cover-caption">Tampilan aplikasi yang dipakai sebagai acuan visual untuk panduan pendaftaran peserta MTQ.</div>
                </div>
                <div style="margin-top:14px;"><span class="pill">Buku petunjuk resmi</span></div>
                <div style="margin-top:14px; color:#334155; font-size:10.5px; line-height:1.7;">Dokumen ini dapat dijadikan panduan cepat bagi official saat mendaftarkan peserta baru.</div>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>

    <table class="section">
        <tr>
            <td class="section-head">
                <div class="section-kicker">Persiapan</div>
                <div class="section-title">Sebelum memulai pendaftaran</div>
            </td>
        </tr>
        <tr>
            <td class="section-body">
                <table class="grid-2">
                    <tr>
                        <td>
                            <table class="checklist">
                                <?php foreach ($checklistItems as $item): ?>
                                    <tr>
                                        <td class="check">✓</td>
                                        <td class="check-text"><?= e($item) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </td>
                        <td>
                            <div class="tips">
                                <div class="section-kicker" style="color:#c2410c;">Catatan cepat</div>
                                <div class="section-title" style="font-size:15px; margin-top:6px;">Hal yang perlu diperhatikan</div>
                                <ul>
                                    <?php foreach ($tipsItems as $item): ?>
                                        <li><?= e($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>

    <?php foreach ($stepScreenshots as $index => $step): ?>
        <table class="section">
            <tr>
                <td class="section-head">
                    <div class="section-kicker">Langkah <?= e($index + 1) ?></div>
                    <div class="section-title"><?= e($step['title'] ?? '') ?></div>
                </td>
            </tr>
            <tr>
                <td class="section-body">
                    <table class="step">
                        <tr>
                            <td style="width:34px;"><div class="step-num"><?= e($index + 1) ?></div></td>
                            <td class="step-text">
                                <div class="step-title"><?= e($step['title'] ?? '') ?></div>
                                <div class="step-desc"><?= e($step['caption'] ?? '') ?></div>
                            </td>
                        </tr>
                    </table>
                    <div class="shot">
                        <?php if (! empty($step['src'])): ?>
                            <img src="<?= e($step['src']) ?>" alt="<?= e($step['title'] ?? 'Screenshot') ?>">
                        <?php endif; ?>
                        <div class="shot-caption"><?= e($step['caption'] ?? '') ?></div>
                    </div>
                </td>
            </tr>
        </table>
        <?php if (($index + 1) < count($stepScreenshots)): ?>
            <div class="page-break"></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <table class="section" style="margin-top:14px;">
        <tr>
            <td class="section-head">
                <div class="section-kicker">Penutup</div>
                <div class="section-title">Tips agar proses berjalan mulus</div>
            </td>
        </tr>
        <tr>
            <td class="section-body">
                <table class="grid-2">
                    <tr>
                        <td style="padding-right:10px;">
                            <span class="pill">Ringkasan</span>
                            <div style="margin-top:10px; color:#334155; font-size:10.5px; line-height:1.7;">Setelah seluruh data dan berkas terisi, lakukan review sebelum menyimpan agar peserta tidak perlu diedit ulang.</div>
                        </td>
                        <td style="padding-left:10px;">
                            <div class="footer-note">
                                <?= e($footerNote) ?><br><br>
                                <strong><?= e($signatureCity) ?></strong><br>
                                Dokumen panduan e-MTQ
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
