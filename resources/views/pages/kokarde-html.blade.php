<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kokarde Peserta MTQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .kokarde-card {
            width: 420px;
            background: linear-gradient(165deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 100px rgba(8, 145, 178, 0.15);
            position: relative;
        }
        .kokarde-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #0ea5e9, #06b6d4, #fbbf24, #f59e0b, #06b6d4, #0ea5e9);
        }
        .kokarde-header {
            background: linear-gradient(180deg, #0f172a 0%, #1e3a5f 100%);
            padding: 28px 24px 20px;
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
        .header-star {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
        }
        .header-star svg { width: 22px; height: 22px; color: #1e293b; }
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
            font-size: 18px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }
        .header-year {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding: 5px 14px;
            background: rgba(251, 191, 36, 0.15);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 20px;
            font-size: 11px;
            color: rgba(255,255,255,0.9);
        }
        .header-year svg { width: 14px; height: 14px; color: #fbbf24; }
        .kokarde-body { padding: 20px 24px; }
        .kokarde-photo-section {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }
        .photo-wrapper {
            flex-shrink: 0;
            width: 120px; height: 145px;
            border-radius: 14px;
            overflow: hidden;
            border: 3px solid transparent;
            background: linear-gradient(#fff, #fff) padding-box, linear-gradient(135deg, #0ea5e9, #06b6d4) border-box;
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
        .photo-placeholder svg { width: 32px; height: 32px; opacity: 0.5; margin-bottom: 6px; }
        .info-section { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .lot-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            padding: 12px 14px;
            border-radius: 12px;
            text-align: center;
            position: relative;
        }
        .lot-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #0ea5e9, #fbbf24, #0ea5e9);
        }
        .lot-label {
            font-size: 9px;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .lot-label svg { width: 12px; height: 12px; color: #fbbf24; }
        .lot-number {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: #fbbf24;
            text-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
            line-height: 1.2;
            margin-top: 4px;
        }
        .lot-empty { color: rgba(255,255,255,0.2); }
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: linear-gradient(135deg, #f0fdfa, #ecfeff);
            border-radius: 10px;
            border-left: 3px solid #0ea5e9;
        }
        .info-icon {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .info-icon svg { width: 14px; height: 14px; color: white; }
        .info-icon.male { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .info-icon.female { background: linear-gradient(135deg, #ec4899, #db2777); }
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
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .info-value.small { font-size: 11px; }
        .gender-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .gender-badge svg { width: 12px; height: 12px; }
        .gender-badge.male { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
        .gender-badge.female { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #be185d; }
        .kokarde-footer {
            background: linear-gradient(180deg, #0f172a, #1e3a5f);
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
        .footer-brand svg { width: 16px; height: 16px; }
        .corner-deco {
            position: absolute;
            width: 40px; height: 40px;
            opacity: 0.08;
        }
        .corner-tl { top: 8px; left: 8px; border-top: 2px solid #0ea5e9; border-left: 2px solid #0ea5e9; border-radius: 6px 0 0 0; }
        .corner-tr { top: 8px; right: 8px; border-top: 2px solid #0ea5e9; border-right: 2px solid #0ea5e9; border-radius: 0 6px 0 0; }
        .corner-bl { bottom: 8px; left: 8px; border-bottom: 2px solid #0ea5e9; border-left: 2px solid #0ea5e9; border-radius: 0 0 0 6px; }
        .corner-br { bottom: 8px; right: 8px; border-bottom: 2px solid #0ea5e9; border-right: 2px solid #0ea5e9; border-radius: 0 0 6px 0; }
        @media print { body { background: white; padding: 0; } .kokarde-card { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="kokarde-card" id="kokardeCard">
        <div class="corner-deco corner-tl"></div>
        <div class="corner-deco corner-tr"></div>
        <div class="corner-deco corner-bl"></div>
        <div class="corner-deco corner-br"></div>

        <div class="kokarde-header">
            <div class="header-logos">
                <img src="/images/logo-kabupaten.webp" alt="" class="header-logo">
                <img src="/images/favicon.webp" alt="" class="header-logo">
                <div class="header-star">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
                <img src="/images/logo-lptq.webp" alt="" class="header-logo">
                <img src="/images/emtq-resmi.webp" alt="" class="header-logo header-logo-main">
            </div>
            <div class="header-title" id="headerTitle"></div>
            <div class="header-event" id="headerEvent"></div>
            <div class="header-year">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Pariangan, 19 - 23 Juni 2026
            </div>
        </div>

        <div class="kokarde-body">
            <div class="kokarde-photo-section">
                <div class="photo-wrapper" id="photoWrapper">
                    <div class="photo-placeholder" id="photoPlaceholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Pas Foto
                    </div>
                </div>

                <div class="info-section">
                    <div class="lot-card">
                        <div class="lot-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                            Nomor Lot
                        </div>
                        <div class="lot-number" id="lotNumber"></div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Nama Peserta</div>
                            <div class="info-value" id="participantName"></div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Cabang / Golongan</div>
                            <div class="info-value small" id="categoryName"></div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon" id="genderIcon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="7" r="5"></circle>
                                <path d="M12 12v5"></path>
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Golongan</div>
                            <div class="gender-badge" id="genderBadge"></div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13a9 9 0 0 1 18 0c0 0-9-6-9-13"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Kecamatan</div>
                            <div class="info-value" id="districtName"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="kokarde-footer">
            <div class="footer-text">
                <span class="footer-brand" id="footerBrand"></span> | LPTQ Kabupaten Tanah Datar
            </div>
        </div>
    </div>

    <script>
        const kokardeData = <?php echo json_encode([
            'eventTitle' => $eventTitle ?? 'MTQ',
            'participant' => [
                'name' => $participant->name ?? '',
                'lot_number' => $participant->lot_number,
                'gender' => $participant->gender,
                'category_branch' => $participant->category?->branch ?? '',
                'category_name' => $participant->category?->name ?? '-',
                'district_name' => $participant->district?->name ?? '-',
                'photo' => ($participant->document_photo && file_exists(public_path('storage/' . $participant->document_photo))
                    ? asset('storage/' . $participant->document_photo)
                    : null),
            ],
        ]); ?>

        document.getElementById('headerTitle').textContent = 'Kokarde Peserta';
        document.getElementById('headerEvent').textContent = kokardeData.eventTitle;
        document.getElementById('footerBrand').textContent = 'e-MTQ ' + kokardeData.eventTitle;

        const p = kokardeData.participant;
        document.getElementById('participantName').textContent = p.name;

        if (p.lot_number) {
            document.getElementById('lotNumber').textContent = p.lot_number;
        } else {
            document.getElementById('lotNumber').innerHTML = '&nbsp;';
            document.getElementById('lotNumber').classList.add('lot-empty');
        }

        const catText = [p.category_branch, p.category_name].filter(Boolean).join(' / ');
        document.getElementById('categoryName').textContent = catText || '-';

        document.getElementById('districtName').textContent = p.district_name;

        const isFemale = p.gender === 'putri';
        const genderClass = isFemale ? 'female' : 'male';
        const genderLabel = p.gender ? p.gender.charAt(0).toUpperCase() + p.gender.slice(1) : '-';

        document.getElementById('genderIcon').classList.add(genderClass);
        document.getElementById('genderIcon').innerHTML = isFemale
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"></circle><path d="M12 13v8"></path><path d="M9 18h6"></path></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="5"></circle><path d="M12 12v5"></path></svg>';

        const badge = document.getElementById('genderBadge');
        badge.classList.add(genderClass);
        badge.innerHTML = isFemale
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
        badge.innerHTML += genderLabel;

        if (p.photo) {
            document.getElementById('photoWrapper').innerHTML = '<img src="' + p.photo + '" alt="Foto" onerror="this.parentNode.innerHTML=\'<div class=photo-placeholder><svg viewBox=0 0 24 24 fill=none stroke=currentColor stroke-width=1.5><path d=M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2></path><circle cx=12 cy=7 r=4></circle></svg>Pas Foto</div>\'">';
        }
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
                link.download = 'Kokarde_' + (kokardeData.participant.name || 'peserta').replace(/[^a-zA-Z0-9]/g, '_') + '.png';
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
