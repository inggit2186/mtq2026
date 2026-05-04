<?php
$selectedParticipant = $selectedParticipant ?? null;
$scoreTimeline = $scoreTimeline ?? collect();
$branchCriteria = $branchCriteria ?? [];
$resultStats = $resultStats ?? ['entries' => 0, 'latest' => '0.00', 'best' => '0.00', 'average' => '0.00'];
$generatedAt = $generatedAt ?? now();
$documentConfig = $documentConfig ?? config('documents');
$officials = $documentConfig['officials'] ?? [];
$isPreview = request()->boolean('preview');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app.name', 'e-MTQ').' - Cetak Hasil Nilai') ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 32px; }
        h1, h2, h3, p { margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 3px solid #0f172a; padding-bottom: 16px; }
        .muted { color: #475569; }
        .doc-chip { display: inline-block; border: 1px solid #94a3b8; border-radius: 999px; padding: 5px 10px; font-size: 12px; color: #334155; margin-top: 8px; }
        .grid { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 20px 0 28px; }
        .card { border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px; }
        .section { margin-top: 28px; }
        .section-title { font-size: 16px; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.08em; }
        .table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .table th, .table td { border: 1px solid #cbd5e1; padding: 10px; vertical-align: top; text-align: left; }
        .table th { background: #e2e8f0; }
        .breakdown { margin-top: 8px; font-size: 12px; color: #334155; }
        .summary { display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; margin-top: 20px; }
        .formal-note { border: 1px dashed #94a3b8; border-radius: 12px; padding: 14px; margin-top: 18px; font-size: 13px; line-height: 1.6; }
        .signatures { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; margin-top: 44px; }
        .signature-box { text-align: center; }
        .signature-line { margin: 72px auto 8px; width: 80%; border-top: 1px solid #0f172a; }
        @media print {
            body { margin: 18px; }
        }
    </style>
</head>
<body<?= $isPreview ? '' : ' onload="window.print()"' ?>>
    <div class="header">
        <div>
            <h1>Berita Acara Hasil Nilai Peserta</h1>
            <p class="muted" style="margin-top: 6px;"><?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?></p>
            <p class="muted" style="margin-top: 4px;"><?= e($documentConfig['event_title'] ?? '') ?></p>
            <div class="doc-chip">Dokumen Resmi Panitia</div>
        </div>
        <div style="text-align: right;">
            <p><strong>Dicetak:</strong> <?= e($generatedAt->format('d M Y H:i')) ?></p>
            <p class="muted" style="margin-top: 4px;"><?= e($selectedParticipant?->registration_number ?? '-') ?></p>
        </div>
    </div>

    <div class="summary">
        <div class="card">
            <div class="section-title">Identitas Peserta</div>
            <h2><?= e($selectedParticipant?->name ?? 'Peserta belum dipilih') ?></h2>
            <p class="muted" style="margin-top: 8px;"><strong>Cabang / Golongan:</strong> <?= e(($selectedParticipant?->category?->branch ?? '-').' | '.($selectedParticipant?->category?->name ?? '-')) ?></p>
            <p class="muted" style="margin-top: 4px;"><strong>Kecamatan / Kafilah:</strong> <?= e(($selectedParticipant?->district?->name ?? '-').' | '.($selectedParticipant?->institution ?? '-')) ?></p>
        </div>
        <div class="card">
            <div class="section-title">Keterangan Dokumen</div>
            <p class="muted">Dokumen ini memuat ringkasan hasil penilaian peserta yang dapat dipakai sebagai bahan rapat, verifikasi dewan hakim, dan arsip panitia.</p>
            <p style="margin-top: 10px;"><strong>Nomor Registrasi:</strong> <?= e($selectedParticipant?->registration_number ?? '-') ?></p>
            <p style="margin-top: 4px;"><strong>Jumlah Entri Nilai:</strong> <?= e($resultStats['entries']) ?></p>
        </div>
    </div>

    <div class="grid">
        <div class="card"><strong>Nilai Terakhir</strong><p style="margin-top: 8px; font-size: 22px;"><?= e($resultStats['latest']) ?></p></div>
        <div class="card"><strong>Nilai Terbaik</strong><p style="margin-top: 8px; font-size: 22px;"><?= e($resultStats['best']) ?></p></div>
        <div class="card"><strong>Rata-rata</strong><p style="margin-top: 8px; font-size: 22px;"><?= e($resultStats['average']) ?></p></div>
        <div class="card"><strong>Jumlah Entri</strong><p style="margin-top: 8px; font-size: 22px;"><?= e($resultStats['entries']) ?></p></div>
    </div>

    <div class="section">
        <h3 class="section-title">Histori Penilaian</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Hakim / Operator</th>
                    <th>Babak</th>
                    <th>Total Nilai</th>
                    <th>Rincian</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($scoreTimeline->isEmpty()): ?>
                    <tr>
                        <td colspan="6">Belum ada nilai yang tercatat untuk peserta ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($scoreTimeline as $entry): ?>
                        <tr>
                            <td><?= e(optional($entry->submitted_at)->format('d/m/Y H:i')) ?></td>
                            <td><?= e($entry->judge_name) ?></td>
                            <td><?= e($entry->judging_round ?: '-') ?></td>
                            <td><?= e(number_format((float) $entry->score, 2)) ?></td>
                            <td>
                                <?php if (! empty($entry->score_breakdown)): ?>
                                    <?php foreach ($entry->score_breakdown as $key => $value): ?>
                                        <div class="breakdown"><?= e($branchCriteria[$key] ?? ucwords(str_replace('_', ' ', (string) $key))) ?>: <?= e(number_format((float) $value, 2)) ?></div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= e($entry->remarks ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="formal-note">
        Berdasarkan data penilaian yang tercatat pada sistem e-MTQ, ringkasan ini disusun sebagai dokumen resmi panitia untuk keperluan verifikasi hasil, rapat pleno, dan pengarsipan pelaksanaan musabaqah.
    </div>

    <div style="margin-top: 24px; text-align: right;">
        <p><?= e(($documentConfig['signature_city'] ?? 'Batusangkar').', '.$generatedAt->translatedFormat('d F Y')) ?></p>
    </div>

    <div class="signatures">
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
        <div class="signature-box">
            <p><?= e($officials['committee_coordinator']['title'] ?? 'Koordinator Panitia') ?></p>
            <div class="signature-line"></div>
            <p class="muted"><?= e($officials['committee_coordinator']['name'] ?? '........................................') ?></p>
        </div>
    </div>
</body>
</html>
