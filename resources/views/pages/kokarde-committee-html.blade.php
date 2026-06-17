<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kokarde <?= e(ucfirst($user->role)) ?> MTQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #14532d 0%, #166534 50%, #14532d 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .kokarde-card {
            width: 420px;
            height: 630px;
            background: linear-gradient(165deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 100px rgba(22, 163, 74, 0.15);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .kokarde-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #22c55e, #4ade80, #86efac, #4ade80, #22c55e);
        }
        .kokarde-header {
            background: linear-gradient(180deg, #14532d 0%, #15803d 100%);
            padding: 24px 24px 18px;
            text-align: center;
            position: relative;
        }
        .kokarde-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 50%; transform: translateX(-50%);
            width: 60%; height: 3px;
            background: linear-gradient(90deg, transparent, #fbbf24, transparent);
        }
        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
        }
        .header-logo {
            width: 36px; height: 36px;
            object-fit: contain;
            filter: brightness(0) invert(1);
            opacity: 0.85;
        }
        .header-logo-main { width: 48px; height: 48px; }
        .header-title {
            font-size: 10px;
            font-weight: 600;
            color: #fbbf24;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 6px;
        }
        .header-event {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 700;
            color: white;
            line-height: 1.3;
            white-space: pre-line;
        }
        .header-year {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 5px 14px;
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.4);
            border-radius: 20px;
            font-size: 11px;
            color: rgba(255,255,255,0.9);
        }
        .kokarde-body { padding: 20px 24px; flex: 1; overflow: hidden; }
        .profile-section {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }
        .photo-wrapper {
            flex-shrink: 0;
            width: 130px; height: 155px;
            border-radius: 14px;
            overflow: hidden;
            border: 3px solid transparent;
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
            font-size: 10px;
        }
        .photo-placeholder svg { width: 36px; height: 36px; opacity: 0.5; margin-bottom: 6px; }
        .info-section { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .role-card {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            padding: 14px 16px;
            border-radius: 12px;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
        }
        .role-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #fbbf24, #fcd34d, #fbbf24);
        }
        .role-label {
            font-size: 9px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .role-label svg { width: 12px; height: 12px; color: #fbbf24; }
        .role-name {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            line-height: 1.2;
            margin-top: 4px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-radius: 10px;
            border-left: 3px solid #22c55e;
        }
        .info-icon {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .info-icon svg { width: 14px; height: 14px; color: white; }
        .info-icon.admin { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .info-icon.panitia { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .info-content { flex: 1; min-width: 0; }
        .info-label {
            font-size: 8px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-value {
            font-size: 12px;
            font-weight: 600;
            color: #14532d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .categories-section {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-radius: 12px;
            padding: 12px;
            border: 1px solid #bbf7d0;
        }
        .categories-label {
            font-size: 9px;
            font-weight: 600;
            color: #15803d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .categories-label svg { width: 12px; height: 12px; }
        .categories-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 20px;
            font-size: 9px;
            font-weight: 600;
            color: white;
        }
        .kokarde-footer {
            background: linear-gradient(180deg, #14532d, #15803d);
            padding: 12px 24px;
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
            gap: 8px;
            font-size: 10px;
            color: rgba(255,255,255,0.8);
        }
        .footer-brand { font-weight: 600; color: #fbbf24; }
        .footer-credit {
            font-size: 7px;
            color: rgba(255,255,255,0.25);
            margin-top: 4px;
            letter-spacing: 0.3px;
        }
        .corner-deco {
            position: absolute;
            width: 40px; height: 40px;
            opacity: 0.08;
        }
        .corner-tl { top: 8px; left: 8px; border-top: 2px solid #22c55e; border-left: 2px solid #22c55e; border-radius: 6px 0 0 0; }
        .corner-tr { top: 8px; right: 8px; border-top: 2px solid #22c55e; border-right: 2px solid #22c55e; border-radius: 0 6px 0 0; }
        .corner-bl { bottom: 8px; left: 8px; border-bottom: 2px solid #22c55e; border-left: 2px solid #22c55e; border-radius: 0 0 0 6px; }
        .corner-br { bottom: 8px; right: 8px; border-bottom: 2px solid #22c55e; border-right: 2px solid #22c55e; border-radius: 0 0 6px 0; }
        @media print { body { background: white; padding: 0; } .kokarde-card { box-shadow: none; } }
    </style>
</head>
<body>
    <?php $photoUrl = $user->profilePhotoUrl(); ?>
    <div class="kokarde-card" id="kokardeCard">
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
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
                <div class="photo-wrapper" id="photoWrapper">
                    <?php if ($photoUrl): ?>
                        <img src="<?= e($photoUrl) ?>" alt="Foto" onerror="this.parentNode.innerHTML='<div class=photo-placeholder><svg viewBox=0 0 24 24 fill=none stroke=currentColor stroke-width=1.5><path d=M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2></path><circle cx=12 cy=7 r=4></circle></svg>Pas Foto</div>'">
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
                        Penanggung Jawab Cabang / Golongan
                    </div>
                    <div class="categories-list">
                        <?php foreach ($categories as $category): ?>
                            <span class="category-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="10" height="10">
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

    <script>
        const kokardeData = {
            name: '<?= e($user->name) ?>',
            role: '<?= e($user->role) ?>',
        };
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        async function downloadKokarde() {
            const element = document.getElementById('kokardeCard');
            try {
                const canvas = await html2canvas(element, {
                    scale: 3,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                });
                const link = document.createElement('a');
                link.download = 'Kokarde_' + kokardeData.role + '_' + kokardeData.name.replace(/[^a-zA-Z0-9]/g, '_') + '.png';
                link.href = canvas.toDataURL('image/png', 1.0);
                link.click();
            } catch (error) {
                console.error('Error:', error);
            }
        }
        window.onload = function() { setTimeout(downloadKokarde, 500); };
    </script>
</body>
</html>
