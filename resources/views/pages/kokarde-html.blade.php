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
            width: 301px;
            height: 364px;
            background: linear-gradient(165deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.4), 0 0 80px rgba(8, 145, 178, 0.15);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .kokarde-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0ea5e9, #06b6d4, #fbbf24, #f59e0b, #06b6d4, #0ea5e9);
        }
        .kokarde-header {
            background: linear-gradient(180deg, #0f172a 0%, #1e3a5f 100%);
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
            filter: brightness(0) invert(1);
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
            font-size: 13px;
            font-weight: 700;
            color: white;
            line-height: 1.1;
            white-space: pre-line;
        }
        .header-year {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 6px;
            padding: 3px 10px;
            background: rgba(251, 191, 36, 0.15);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 14px;
            font-size: 8px;
            color: rgba(255,255,255,0.9);
        }
        .header-year svg { width: 10px; height: 10px; color: #fbbf24; }
        .kokarde-body { padding: 10px 14px; flex: 1; overflow: hidden; }
        .kokarde-photo-section {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }
        .photo-wrapper {
            flex-shrink: 0;
            width: 85px; height: 105px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid transparent;
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
            font-size: 7px;
        }
        .photo-placeholder svg { width: 24px; height: 24px; opacity: 0.5; margin-bottom: 3px; }
        .info-section { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .lot-card {
            background: #ffffff;
            padding: 6px 10px;
            border-radius: 8px;
            text-align: center;
            position: relative;
            border: 2px solid #e2e8f0;
        }
        .lot-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, #0ea5e9, #fbbf24, #0ea5e9);
        }
        .lot-label {
            font-size: 7px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .lot-label svg { width: 10px; height: 10px; color: #0ea5e9; }
        .lot-number {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            color: #0ea5e9;
            text-shadow: 0 0 15px rgba(14, 165, 233, 0.3);
            line-height: 1.1;
            margin-top: 2px;
        }
        .lot-empty { color: #cbd5e1; }
        .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: linear-gradient(135deg, #f0fdfa, #ecfeff);
            border-radius: 6px;
            border-left: 2px solid #0ea5e9;
        }
        .info-icon {
            width: 20px; height: 20px;
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .info-icon svg { width: 10px; height: 10px; color: white; }
        .info-icon.male { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .info-icon.female { background: linear-gradient(135deg, #ec4899, #db2777); }
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
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .info-value.small { font-size: 8px; }
        .gender-badge {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .gender-badge svg { width: 8px; height: 8px; }
        .gender-badge.male { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
        .gender-badge.female { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #be185d; }
        .kokarde-footer {
            background: linear-gradient(180deg, #0f172a, #1e3a5f);
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
        .footer-brand svg { width: 12px; height: 12px; }
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
        .corner-tl { top: 5px; left: 5px; border-top: 2px solid #0ea5e9; border-left: 2px solid #0ea5e9; border-radius: 4px 0 0 0; }
        .corner-tr { top: 5px; right: 5px; border-top: 2px solid #0ea5e9; border-right: 2px solid #0ea5e9; border-radius: 0 4px 0 0; }
        .corner-bl { bottom: 5px; left: 5px; border-bottom: 2px solid #0ea5e9; border-left: 2px solid #0ea5e9; border-radius: 0 0 0 4px; }
        .corner-br { bottom: 5px; right: 5px; border-bottom: 2px solid #0ea5e9; border-right: 2px solid #0ea5e9; border-radius: 0 0 4px 0; }
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
                            <div class="info-value" id="categoryName"></div>
                            <div class="info-value small" id="categoryGol"></div>
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
                <span class="footer-brand" id="footerBrand"></span>
            </div>
            <div class="footer-credit">Ridho Saputra @Kankemenag Tanah Datar</div>
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

        document.getElementById('headerTitle').textContent = 'Peserta';
        document.getElementById('headerEvent').innerHTML = 'MTQ Nasional ke XLIII\nTingkat Kabupaten Tanah Datar\nTahun 2026';
        document.getElementById('footerBrand').textContent = 'e-MTQ Kabupaten Tanah Datar Tahun 2026';

        const p = kokardeData.participant;
        document.getElementById('participantName').textContent = p.name;

        if (p.lot_number) {
            document.getElementById('lotNumber').textContent = p.lot_number;
        } else {
            document.getElementById('lotNumber').innerHTML = '&nbsp;';
            document.getElementById('lotNumber').classList.add('lot-empty');
        }

        const catText = [p.category_branch, p.category_name].filter(Boolean).join(' / ');
        document.getElementById('categoryName').textContent = p.category_branch || '-';
        document.getElementById('categoryGol').textContent = p.category_name || '-';

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
