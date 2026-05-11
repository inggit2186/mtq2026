<?php
require_once __DIR__.'/../partials/icon.php';

$mode = $mode ?? 'form';
$documentConfig = $documentConfig ?? [];
$previewUser = $previewUser ?? null;
$districts = collect($districts ?? []);
$participants = collect($participants ?? []);
$categories = collect($categories ?? []);
$stats = $stats ?? ['participants' => 0, 'districts' => 0, 'verified' => 0, 'categories' => 0];
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation('official', 'participants.index');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($documentConfig['organization_name'] ?? 'e-MTQ').' - Snapshot Panduan Peserta') ?></title>
</head>
<body style="margin:0; font-family: Arial, Helvetica, sans-serif; background:#0b1220; color:#e2e8f0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <td width="300" valign="top" style="background:#07111f; border-right:1px solid #1f2937; padding:24px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td width="54" valign="top">
                            <div style="width:54px; height:54px; border-radius:16px; background:#11253a; border:1px solid #1f3b57; overflow:hidden;">
                                <img src="<?= e(asset('images/favicon.webp')) ?>" alt="Logo" style="width:100%; height:100%; object-fit:cover; display:block;">
                            </div>
                        </td>
                        <td valign="top" style="padding-left:14px;">
                            <div style="font-size:10px; letter-spacing:0.26em; text-transform:uppercase; color:#7dd3fc; margin-top:4px;">e-MTQ Console</div>
                            <div style="font-size:18px; font-weight:700; color:#ffffff; margin-top:6px;">Panduan Peserta</div>
                            <div style="font-size:12px; line-height:1.7; color:#cbd5e1; margin-top:10px;">Snapshot ini dipakai sebagai bahan screenshot di buku petunjuk pendaftaran peserta MTQ. Tampilan dibuat sederhana agar jelas saat dicetak.</div>
                            <div style="display:inline-block; margin-top:14px; padding:7px 12px; border-radius:999px; background:#0f172a; border:1px solid #1f3b57; color:#bff8ff; font-size:11px; font-weight:700;">
                                Mode <?= e(strtoupper($mode)) ?>
                            </div>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:22px;">
                    <?php foreach ($navigation as $item): ?>
                        <div style="margin-bottom:8px; padding:10px 12px; border-radius:14px; background:<?= $item['active'] ? '#12364a' : '#0f172a' ?>; border:1px solid <?= $item['active'] ? '#1d688a' : '#1f2937' ?>; color:#e2e8f0; font-size:12px;">
                            <span style="display:inline-block; width:20px; vertical-align:middle; margin-right:8px;"><?= mtq_icon($item['icon'], 'h-4 w-4') ?></span>
                            <span style="vertical-align:middle;"><?= e($item['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <table width="100%" cellpadding="0" cellspacing="10" style="border-collapse:separate; margin-top:16px;">
                    <tr>
                        <td width="50%" style="background:#0f172a; border:1px solid #1f2937; border-radius:14px; padding:12px;">
                            <div style="font-size:9px; letter-spacing:0.18em; text-transform:uppercase; color:#94a3b8;">Peserta</div>
                            <div style="font-size:22px; font-weight:700; color:#ffffff; margin-top:6px;"><?= e($stats['participants']) ?></div>
                        </td>
                        <td width="50%" style="background:#0f172a; border:1px solid #1f2937; border-radius:14px; padding:12px;">
                            <div style="font-size:9px; letter-spacing:0.18em; text-transform:uppercase; color:#94a3b8;">Verified</div>
                            <div style="font-size:22px; font-weight:700; color:#ffffff; margin-top:6px;"><?= e($stats['verified']) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td width="50%" style="background:#0f172a; border:1px solid #1f2937; border-radius:14px; padding:12px;">
                            <div style="font-size:9px; letter-spacing:0.18em; text-transform:uppercase; color:#94a3b8;">Kecamatan</div>
                            <div style="font-size:22px; font-weight:700; color:#ffffff; margin-top:6px;"><?= e($stats['districts']) ?></div>
                        </td>
                        <td width="50%" style="background:#0f172a; border:1px solid #1f2937; border-radius:14px; padding:12px;">
                            <div style="font-size:9px; letter-spacing:0.18em; text-transform:uppercase; color:#94a3b8;">Kategori</div>
                            <div style="font-size:22px; font-weight:700; color:#ffffff; margin-top:6px;"><?= e($stats['categories']) ?></div>
                        </td>
                    </tr>
                </table>
            </td>

            <td valign="top" style="padding:24px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="background:#111827; border:1px solid #243042; border-radius:24px; padding:22px;">
                            <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#7dd3fc;"><?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?></div>
                            <div style="font-size:28px; line-height:1.15; font-weight:800; color:#ffffff; margin-top:10px;">Buku Petunjuk Pendaftaran Peserta MTQ</div>
                            <div style="font-size:13px; line-height:1.7; color:#cbd5e1; margin-top:10px; max-width:780px;"><?= e($documentConfig['event_title'] ?? 'Panduan step by step untuk official kecamatan saat mendaftarkan peserta di e-MTQ') ?></div>
                        </td>
                    </tr>
                </table>

                <?php if ($mode === 'form'): ?>
                    <table width="100%" cellpadding="0" cellspacing="16" style="border-collapse:separate; margin-top:16px;">
                        <tr>
                            <td width="46%" valign="top" style="background:#111827; border:1px solid #243042; border-radius:22px; padding:18px;">
                                <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#7dd3fc;">Langkah 1</div>
                                <div style="font-size:22px; line-height:1.25; font-weight:800; color:#ffffff; margin-top:8px;">Pilih cabang dan golongan peserta</div>
                                <div style="margin-top:14px; border-top:1px solid #243042; padding-top:14px;">
                                    <div style="margin-bottom:12px;">
                                        <div style="font-size:13px; font-weight:700; color:#ffffff;">1. Tentukan cabang lomba</div>
                                        <div style="font-size:12px; line-height:1.7; color:#cbd5e1; margin-top:4px;">Pilih cabang yang sesuai agar form pendaftaran membuka kategori yang benar.</div>
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <div style="font-size:13px; font-weight:700; color:#ffffff;">2. Pilih golongan</div>
                                        <div style="font-size:12px; line-height:1.7; color:#cbd5e1; margin-top:4px;">Golongan harus disesuaikan dengan usia dan ketentuan masing-masing peserta.</div>
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <div style="font-size:13px; font-weight:700; color:#ffffff;">3. Lanjutkan ke form</div>
                                        <div style="font-size:12px; line-height:1.7; color:#cbd5e1; margin-top:4px;">Setelah kategori dipilih, form peserta akan terbuka untuk diisi lengkap.</div>
                                    </div>
                                </div>
                                <div style="margin-top:14px; padding:12px; border-radius:16px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12px; line-height:1.6;">
                                    Screenshot ini menunjukkan alur awal pemilihan cabang dan golongan sebelum data peserta dimasukkan.
                                </div>
                            </td>
                            <td width="54%" valign="top" style="background:#111827; border:1px solid #243042; border-radius:22px; padding:18px;">
                                <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#7dd3fc;">Snapshot Aplikasi</div>
                                <div style="font-size:20px; line-height:1.25; font-weight:800; color:#ffffff; margin-top:8px;">Pilih cabang dan golongan</div>
                                <div style="margin-top:14px; background:#0b1220; border:1px solid #243042; border-radius:18px; padding:16px;">
                                    <div style="font-size:13px; font-weight:700; color:#dbeafe; margin-bottom:8px;">Cabang MTQ</div>
                                    <div style="background:#020817; border:1px solid #334155; border-radius:14px; padding:12px 14px; color:#f8fafc; font-size:13px;">Seni Baca Al Qur`an</div>
                                    <div style="font-size:11px; line-height:1.6; color:#94a3b8; margin-top:8px;">Pilih salah satu cabang untuk menampilkan daftar golongan yang tersedia.</div>

                                    <div style="margin-top:14px; font-size:13px; font-weight:700; color:#dbeafe; margin-bottom:8px;">Golongan Tersedia</div>
                                    <div>
                                        <?php foreach ($categories->take(5) as $category): ?>
                                            <span style="display:inline-block; margin:6px 8px 0 0; padding:6px 10px; border-radius:999px; background:#0f172a; border:1px solid #1f3b57; color:#d7f8ff; font-size:11px;"><?= e($category->name) ?></span>
                                        <?php endforeach; ?>
                                    </div>

                                    <div style="margin-top:14px; background:#0a1220; border:1px solid #243042; border-radius:16px; padding:14px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                            <tr>
                                                <td width="104" valign="top">
                                                    <div style="width:104px; height:104px; border-radius:18px; overflow:hidden; border:1px solid #1f3b57; background:#11253a;">
                                                        <?php if ($previewUser?->profilePhotoUrl()): ?>
                                                            <img src="<?= e($previewUser->profilePhotoUrl()) ?>" alt="Preview" style="width:100%; height:100%; object-fit:cover; display:block;">
                                                        <?php else: ?>
                                                            <div style="width:104px; height:104px; line-height:104px; text-align:center; color:#d7f8ff; font-size:32px; font-weight:700;"><?= e($previewUser?->profileInitials() ?? 'A') ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td valign="top" style="padding-left:12px; color:#cbd5e1; font-size:12px; line-height:1.7;">
                                                    <div><strong style="color:#ffffff;">Nama:</strong> Peserta Demo</div>
                                                    <div><strong style="color:#ffffff;">Kecamatan:</strong> Batipuh</div>
                                                    <div><strong style="color:#ffffff;">Status:</strong> Siap didaftarkan</div>
                                                    <div><strong style="color:#ffffff;">Berkas:</strong> Lengkap</div>
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="margin-top:14px; display:inline-block; padding:10px 14px; border-radius:14px; background:#22d3ee; color:#00111f; font-size:12px; font-weight:700;">Lanjut ke Form Peserta</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                <?php else: ?>
                    <table width="100%" cellpadding="0" cellspacing="16" style="border-collapse:separate; margin-top:16px;">
                        <tr>
                            <td width="46%" valign="top" style="background:#111827; border:1px solid #243042; border-radius:22px; padding:18px;">
                                <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#7dd3fc;">Langkah 2</div>
                                <div style="font-size:22px; line-height:1.25; font-weight:800; color:#ffffff; margin-top:8px;">Isi data peserta dan cek berkas sebelum simpan</div>
                                <div style="margin-top:14px; border-top:1px solid #243042; padding-top:14px;">
                                    <div style="margin-bottom:12px;">
                                        <div style="font-size:13px; font-weight:700; color:#ffffff;">1. Lengkapi identitas</div>
                                        <div style="font-size:12px; line-height:1.7; color:#cbd5e1; margin-top:4px;">Isi nama, NIK, alamat, dan kontak peserta dengan data yang benar.</div>
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <div style="font-size:13px; font-weight:700; color:#ffffff;">2. Upload berkas wajib</div>
                                        <div style="font-size:12px; line-height:1.7; color:#cbd5e1; margin-top:4px;">Pastikan file pendukung seperti KK, KTP, dan dokumen lain sudah tersedia.</div>
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <div style="font-size:13px; font-weight:700; color:#ffffff;">3. Review lalu simpan</div>
                                        <div style="font-size:12px; line-height:1.7; color:#cbd5e1; margin-top:4px;">Gunakan tampilan review untuk memastikan semua data sudah benar sebelum disimpan.</div>
                                    </div>
                                </div>
                                <div style="margin-top:14px; padding:12px; border-radius:16px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12px; line-height:1.6;">
                                    Screenshot ini menampilkan tahap review yang membantu official mengecek data sebelum pendaftaran dikirim.
                                </div>
                            </td>
                            <td width="54%" valign="top" style="background:#111827; border:1px solid #243042; border-radius:22px; padding:18px;">
                                <div style="font-size:10px; letter-spacing:0.18em; text-transform:uppercase; color:#7dd3fc;">Snapshot Aplikasi</div>
                                <div style="font-size:20px; line-height:1.25; font-weight:800; color:#ffffff; margin-top:8px;">Review data peserta</div>
                                <table width="100%" cellpadding="0" cellspacing="10" style="border-collapse:separate; margin-top:12px;">
                                    <tr>
                                        <td width="25%" style="background:#0f172a; border:1px solid #1f2937; border-radius:14px; padding:12px;">
                                            <div style="font-size:9px; letter-spacing:0.18em; text-transform:uppercase; color:#94a3b8;">Peserta</div>
                                            <div style="font-size:22px; font-weight:700; color:#ffffff; margin-top:6px;"><?= e($stats['participants']) ?></div>
                                        </td>
                                        <td width="25%" style="background:#0f172a; border:1px solid #1f2937; border-radius:14px; padding:12px;">
                                            <div style="font-size:9px; letter-spacing:0.18em; text-transform:uppercase; color:#94a3b8;">Berkas</div>
                                            <div style="font-size:22px; font-weight:700; color:#ffffff; margin-top:6px;">4/4</div>
                                        </td>
                                        <td width="25%" style="background:#0f172a; border:1px solid #1f2937; border-radius:14px; padding:12px;">
                                            <div style="font-size:9px; letter-spacing:0.18em; text-transform:uppercase; color:#94a3b8;">Kategori</div>
                                            <div style="font-size:22px; font-weight:700; color:#ffffff; margin-top:6px;"><?= e($stats['categories']) ?></div>
                                        </td>
                                        <td width="25%" style="background:#0f172a; border:1px solid #1f2937; border-radius:14px; padding:12px;">
                                            <div style="font-size:9px; letter-spacing:0.18em; text-transform:uppercase; color:#94a3b8;">Simpan</div>
                                            <div style="font-size:22px; font-weight:700; color:#ffffff; margin-top:6px;">Siap</div>
                                        </td>
                                    </tr>
                                </table>

                                <?php foreach ($participants as $participant): ?>
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-top:10px;">
                                        <tr>
                                            <td style="background:#0a1220; border:1px solid #243042; border-radius:16px; padding:12px;">
                                                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                                    <tr>
                                                        <td width="52" valign="top">
                                                            <div style="width:52px; height:52px; line-height:52px; text-align:center; border-radius:16px; border:1px solid #1f3b57; background:#11253a; color:#d7f8ff; font-size:16px; font-weight:700;"><?= e($participant->name ? mb_substr($participant->name, 0, 2) : 'P') ?></div>
                                                        </td>
                                                        <td valign="top" style="padding-left:12px;">
                                                            <div style="font-size:13px; font-weight:700; color:#ffffff;"><?= e($participant->name) ?></div>
                                                            <div style="font-size:11px; line-height:1.6; color:#94a3b8; margin-top:4px;">
                                                                <?= e($participant->category?->branch ?: '-') ?> | <?= e($participant->category?->name ?: '-') ?><br>
                                                                <?= e($participant->district?->name ?: '-') ?> | <?= e($participant->registration_number ?: '-') ?>
                                                            </div>
                                                        </td>
                                                        <td valign="top" align="right">
                                                            <div style="display:inline-block; padding:6px 10px; border-radius:999px; background:#052e1b; border:1px solid #14532d; color:#b8f7ca; font-size:10px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase;">Siap</div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</body>
</html>
