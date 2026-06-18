<?php
$eventTitle = config('juknis.title', 'MTQ');
$eventYear = config('juknis.year', date('Y'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kompilasi Kokarde Panitia MTQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #e5e5e5;
        }
        .print-header {
            background: linear-gradient(135deg, #14532d, #166534);
            color: white;
            padding: 20px 30px;
            text-align: center;
            margin-bottom: 20px;
        }
        .print-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .print-header p {
            font-size: 14px;
            opacity: 0.8;
        }
        .print-header .btn-print {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 25px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .kokarde-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            max-width: 800px;
            margin: 0 auto;
            padding: 15px;
        }
        .kokarde-card {
            width: 301px;
            height: 364px;
            background: linear-gradient(165deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
            position: relative;
            display: flex;
            flex-direction: column;
            margin: 0 auto;
        }
        .kokarde-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #22c55e, #4ade80, #86efac, #4ade80, #22c55e);
        }
        .kokarde-header {
            background: linear-gradient(180deg, #14532d 0%, #15803d 100%);
            padding: 7px 12px 5px;
            text-align: center;
            position: relative;
        }
        .kokarde-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 50%; transform: translateX(-50%);
            width: 60%; height: 2px;
            background: linear-gradient(90deg, transparent, #fbbf24, transparent);
        }
        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
            margin-bottom: 3px;
        }
        .header-logo {
            width: 24px; height: 24px;
            object-fit: contain;
            /* filter: brightness(0) invert(1); */
            opacity: 0.85;
        }
        .header-logo-main { width: 32px; height: 32px; }
        .header-title {
            font-size: 8px;
            font-weight: 600;
            color: #fbbf24;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 3px;
        }
        .header-event {
            font-family: 'Playfair Display', serif;
            font-size: 11px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
            white-space: pre-line;
        }
        .header-year {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 5px;
            padding: 3px 10px;
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.4);
            border-radius: 14px;
            font-size: 8px;
            color: rgba(255,255,255,0.9);
        }
        .kokarde-body { padding: 10px 14px; flex: 1; overflow: hidden; }
        .profile-section {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }
        .photo-wrapper {
            flex-shrink: 0;
            width: 90px; height: 108px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid transparent;
            background: linear-gradient(#fff, #fff) padding-box, linear-gradient(135deg, #22c55e, #4ade80) border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
        }
        .photo-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        .photo-placeholder {
            color: #94a3b8;
            text-align: center;
            font-size: 7px;
        }
        .photo-placeholder svg { width: 24px; height: 24px; opacity: 0.5; margin-bottom: 3px; }
        .info-section { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .role-card {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            padding: 6px 10px;
            border-radius: 8px;
            text-align: center;
            position: relative;
            box-shadow: 0 3px 10px rgba(22, 163, 74, 0.3);
        }
        .role-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, #fbbf24, #fcd34d, #fbbf24);
        }
        .role-label {
            font-size: 7px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .role-label svg { width: 10px; height: 10px; color: #fbbf24; }
        .role-name {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            line-height: 1.1;
            margin-top: 2px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-radius: 6px;
            border-left: 2px solid #22c55e;
        }
        .info-icon {
            width: 20px; height: 20px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .info-icon svg { width: 10px; height: 10px; color: white; }
        .info-icon.admin { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .info-icon.panitia { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .info-content { flex: 1; min-width: 0; }
        .info-label {
            font-size: 6px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 9px;
            font-weight: 600;
            color: #14532d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .categories-section {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-radius: 8px;
            padding: 6px 8px;
            border: 1px solid #bbf7d0;
        }
        .categories-label {
            font-size: 7px;
            font-weight: 600;
            color: #15803d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .categories-label svg { width: 10px; height: 10px; }
        .categories-list {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
        }
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            padding: 2px 6px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 12px;
            font-size: 7px;
            font-weight: 600;
            color: white;
        }
        .kokarde-footer {
            background: linear-gradient(180deg, #14532d, #15803d);
            padding: 8px 14px;
            text-align: center;
            position: relative;
        }
        .kokarde-footer::before {
            content: '';
            position: absolute;
            top: 0; left: 50%; transform: translateX(-50%);
            width: 50%; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(251, 191, 36, 0.5), transparent);
        }
        .footer-text {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 7px;
            color: rgba(255,255,255,0.8);
        }
        .footer-brand { font-weight: 600; color: #fbbf24; }
        .footer-credit {
            font-size: 5px;
            color: rgba(255,255,255,0.25);
            margin-top: 2px;
            font-style:italic;
            letter-spacing: 0.2px;
        }
        .corner-deco {
            position: absolute;
            width: 24px; height: 24px;
            opacity: 0.06;
        }
        .corner-tl { top: 5px; left: 5px; border-top: 2px solid #22c55e; border-left: 2px solid #22c55e; border-radius: 4px 0 0 0; }
        .corner-tr { top: 5px; right: 5px; border-top: 2px solid #22c55e; border-right: 2px solid #22c55e; border-radius: 0 4px 0 0; }
        .corner-bl { bottom: 5px; left: 5px; border-bottom: 2px solid #22c55e; border-left: 2px solid #22c55e; border-radius: 0 0 0 4px; }
        .corner-br { bottom: 5px; right: 5px; border-bottom: 2px solid #22c55e; border-right: 2px solid #22c55e; border-radius: 0 0 4px 0; }

        /* Print Styles */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            body {
                background: white;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .print-header {
                display: none;
            }
            .kokarde-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
                padding: 8px;
            }
            .kokarde-card {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h1>Kompilasi Kokarde Panitia</h1>
        <p>Admin &amp; Panitia (<?= $committees->count() ?> orang)</p>
        <button onclick="window.print()" class="btn-print">
            Print / Save as PDF
        </button>
        <br><br>
        <small style="opacity: 0.7;">Tekan Ctrl+P atau pilih Print, lalu pilih "Save as PDF" untuk menyimpan</small>
    </div>

    <?php
    $perPage = 4;
    $chunks = $committees->chunk($perPage);
    ?>

    <?php foreach ($chunks as $chunkIndex => $chunk): ?>
        <div class="kokarde-grid<?= $chunkIndex < $chunks->count() - 1 ? ' page-break' : '' ?>">
            <?php foreach ($chunk as $user): ?>
                <?php
                $photoUrl = $user->profilePhotoUrl();
                $categories = $user->categoryAccesses->map(fn ($access) => $access->category)->filter();
                ?>
                <div class="kokarde-card">
                    <div class="corner-deco corner-tl"></div>
                    <div class="corner-deco corner-tr"></div>
                    <div class="corner-deco corner-bl"></div>
                    <div class="corner-deco corner-br"></div>

                    <div class="kokarde-header">
                        <div class="header-logos">
                            <img src="/images/logo-kabupaten.webp" alt="" class="header-logo">
                            <img src="/images/favicon.webp" alt="" class="header-logo">
                            <img src="/images/logo-lptq.webp" alt="" class="header-logo">
                            <img src="/images/emtq-resmi.webp" alt="" class="header-logo header-logo-main">
                        </div>
                        <div class="header-title">Kokarde <?= e(ucfirst($user->role)) ?></div>
                        <div class="header-event">MTQ Nasional ke XLIII
Tingkat Kabupaten Tanah Datar
Tahun 2026</div>
                        <div class="header-year">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="10" height="10">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            Pariangan, 19 - 23 Juni 2026
                        </div>
                    </div>

                    <div class="kokarde-body">
                        <div class="profile-section">
                            <div class="photo-wrapper">
                                <?php if ($photoUrl): ?>
                                    <img src="<?= e($photoUrl) ?>" alt="Foto">
                                <?php else: ?>
                                    <div class="photo-placeholder">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        Pas Foto
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="info-section">
                                <div class="role-card">
                                    <div class="role-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <?php if ($user->role === 'admin'): ?>
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                            <?php else: ?>
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            <?php endif; ?>
                                        </svg>
                                        Jabatan
                                    </div>
                                    <div class="role-name"><?= e(ucfirst($user->role)) ?></div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon <?= e($user->role) ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">Nama Lengkap</div>
                                        <div class="info-value"><?= e($user->name) ?></div>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                        </svg>
                                    </div>
                                    <div class="info-content">
                                        <div class="info-label">NIP</div>
                                        <div class="info-value"><?= e($user->nomor_induk ?? '-') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($categories->isNotEmpty()): ?>
                            <div class="categories-section">
                                <div class="categories-label">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                    Penanggung Jawab
                                </div>
                                <div class="categories-list">
                                    <?php foreach ($categories as $category): ?>
                                        <span class="category-badge">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="8" height="8">
                                                <polyline points="9 11 12 14 22 4"></polyline>
                                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                            </svg>
                                            <?= e(trim(($category->branch ?? '-').' - '.($category->name ?? '-'))) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="kokarde-footer">
                        <div class="footer-text">
                            <span class="footer-brand">e-MTQ Kabupaten Tanah Datar Tahun 2026</span>
                        </div>
                        <div class="footer-credit">Ridho Saputra @Kankemenag Tanah Datar</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</body>
</html>
