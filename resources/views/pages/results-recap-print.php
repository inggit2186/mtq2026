<?php
$rows = $rows ?? collect();
$generatedAt = $generatedAt ?? now();
$filters = $filters ?? [];
$selectedCategoryLabel = $selectedCategoryLabel ?? 'Semua';
$rankingPriorityContext = $rankingPriorityContext ?? ['text' => '', 'specific' => false];
$documentConfig = $documentConfig ?? config('documents');
$officials = $documentConfig['officials'] ?? [];
$isPreview = request()->boolean('preview');
$filterBranch = $filters['branch'] ?? '';
$filterKeyword = $filters['keyword'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app.name', 'e-MTQ').' - Cetak Rekap Penilaian') ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 28px; }
        h1, h2, p { margin: 0; }
        .header { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 24px; border-bottom: 3px solid #0f172a; padding-bottom: 16px; }
        .muted { color: #475569; }
        .doc-chip { display: inline-block; border: 1px solid #94a3b8; border-radius: 999px; padding: 5px 10px; font-size: 12px; color: #334155; margin-top: 8px; }
        .summary { display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px; margin: 18px 0 10px; }
        .card { border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px; }
        .section-title { font-size: 16px; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.08em; }
        .table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .table th, .table td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; vertical-align: top; }
        .table th { background: #e2e8f0; }
        .formal-note { border: 1px dashed #94a3b8; border-radius: 12px; padding: 14px; margin-top: 18px; font-size: 13px; line-height: 1.6; }
        .signatures { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; margin-top: 44px; }
        .signature-box { text-align: center; }
        .signature-line { margin: 72px auto 8px; width: 80%; border-top: 1px solid #0f172a; }
        @media print { body { margin: 18px; } }
    </style>
</head>
<body<?= $isPreview ? '' : ' onload="window.print()"' ?>>
    <div class="header">
        <div>
            <h1>Berita Acara Ringkas Rekap Penilaian</h1>
            <p class="muted" style="margin-top: 6px;"><?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?></p>
            <p class="muted" style="margin-top: 4px;"><?= e($documentConfig['event_title'] ?? '') ?></p>
            <div class="doc-chip">Dokumen Rekap Resmi</div>
            <p class="muted" style="margin-top: 6px;">
                Filter:
                Cabang <?= e($filterBranch ?: 'Semua') ?>,
                Golongan <?= e($selectedCategoryLabel) ?>,
                Kata kunci <?= e($filterKeyword ?: '-') ?>
            </p>
        </div>
        <div style="text-align: right;">
            <p><strong>Dicetak:</strong> <?= e($generatedAt->format('d M Y H:i')) ?></p>
            <p class="muted" style="margin-top: 6px;">Total peserta: <?= e($rows->count()) ?></p>
        </div>
    </div>

    <div class="summary">
        <div class="card">
            <div class="section-title">Ruang Lingkup Rekap</div>
            <p class="muted">Dokumen ini merangkum peringkat dan performa peserta berdasarkan filter cabang, golongan, dan kata kunci yang sedang aktif pada sistem.</p>
            <p style="margin-top: 10px;"><strong>Cabang aktif:</strong> <?= e($filterBranch ?: 'Semua cabang') ?></p>
            <p style="margin-top: 4px;"><strong>Golongan aktif:</strong> <?= e($selectedCategoryLabel) ?></p>
        </div>
        <div class="card">
            <div class="section-title">Ringkasan Dokumen</div>
            <p><strong>Tanggal Cetak:</strong> <?= e($generatedAt->format('d M Y H:i')) ?></p>
            <p style="margin-top: 4px;"><strong>Total Peserta:</strong> <?= e($rows->count()) ?></p>
            <p style="margin-top: 4px;"><strong>Keperluan:</strong> Rapat, verifikasi, arsip resmi</p>
            <?php if (($rankingPriorityContext['text'] ?? '') !== ''): ?>
                <p style="margin-top: 4px;"><strong>Dasar tie-break:</strong> <?= e($rankingPriorityContext['text']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Peringkat</th>
                <th>Peserta</th>
                <th>Cabang / Golongan</th>
                <th>Kecamatan</th>
                <th>Nilai Terakhir</th>
                <th>Rata-rata</th>
                <th>Terbaik</th>
                <th>Entri</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows->isEmpty()): ?>
                <tr>
                    <td colspan="8">Belum ada data yang sesuai dengan filter.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td><?= e($index + 1) ?></td>
                    <td><?= e($row['participant_name']) ?><br><span class="muted"><?= e($row['registration_number']) ?> | <?= e($row['institution']) ?></span></td>
                    <td><?= e($row['branch']) ?><br><span class="muted"><?= e($row['category_name']) ?></span></td>
                    <td><?= e($row['district']) ?></td>
                    <td><?= e($row['latest_score']) ?></td>
                    <td><?= e($row['average_score']) ?></td>
                    <td><?= e($row['best_score']) ?></td>
                    <td><?= e($row['entry_count']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="formal-note">
        Rekap ini disusun dari data penilaian yang tercatat pada sistem e-MTQ dan dapat digunakan sebagai lampiran berita acara, bahan rapat pleno, serta dokumentasi resmi panitia pelaksana.
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
            <p><?= e($officials['chief_judge']['title'] ?? 'Ketua Majelis Hakim') ?></p>
            <div class="signature-line"></div>
            <p class="muted"><?= e($officials['chief_judge']['name'] ?? '........................................') ?></p>
        </div>
        <div class="signature-box">
            <p><?= e($officials['secretary']['title'] ?? 'Sekretaris') ?></p>
            <div class="signature-line"></div>
            <p class="muted"><?= e($officials['secretary']['name'] ?? '........................................') ?></p>
        </div>
    </div>
</body>
</html>
