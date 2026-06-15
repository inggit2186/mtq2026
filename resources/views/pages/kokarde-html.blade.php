<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kokarde Peserta MTQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .kokarde-card {
            width: 400px;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
        }
        /* Top decorative border */
        .kokarde-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #1e3a5f, #0891b2, #fbbf24, #0891b2, #1e3a5f);
        }
        /* Header with logos */
        .kokarde-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            padding: 20px 24px 16px;
            text-align: center;
            position: relative;
        }
        .kokarde-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, #fbbf24, transparent);
        }
        .header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .header-logo {
            width: 36px;
            height: 36px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .header-logo-main {
            width: 48px;
            height: 48px;
        }
        .header-title {
            font-family: 'Playfair Display', serif;
            font-size: 11px;
            font-weight: 600;
            color: #fbbf24;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .header-event {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }
        .header-year {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            margin-top: 4px;
        }
        /* Photo section */
        .kokarde-photo-section {
            padding: 20px 24px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .photo-wrapper {
            flex-shrink: 0;
            width: 120px;
            height: 140px;
            border-radius: 12px;
            overflow: hidden;
            border: 3px solid #0891b2;
            box-shadow: 0 8px 16px rgba(8, 145, 178, 0.2);
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-placeholder {
            color: #94a3b8;
            text-align: center;
            font-size: 10px;
        }
        .photo-placeholder svg {
            width: 32px;
            height: 32px;
            margin-bottom: 4px;
            opacity: 0.5;
        }
        /* Info section */
        .info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .info-item {
            background: linear-gradient(135deg, #f0fdfa, #ecfeff);
            padding: 8px 12px;
            border-radius: 10px;
            border-left: 3px solid #0891b2;
        }
        .info-label {
            font-size: 9px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
        }
        .info-value.lot-number {
            font-size: 20px;
            font-weight: 700;
            color: #0891b2;
            font-family: 'Playfair Display', serif;
        }
        /* Gender badge */
        .gender-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .gender-badge.putra {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1d4ed8;
        }
        .gender-badge.putri {
            background: linear-gradient(135deg, #fce7f3, #fbcfe8);
            color: #9d174d;
        }
        /* Footer */
        .kokarde-footer {
            background: linear-gradient(135deg, #1e3a5f, #0f172a);
            padding: 12px 24px;
            text-align: center;
            position: relative;
        }
        .kokarde-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #fbbf24, transparent);
        }
        .footer-text {
            font-size: 10px;
            color: rgba(255,255,255,0.8);
        }
        .footer-brand {
            font-weight: 600;
            color: #fbbf24;
        }
        /* Decorative elements */
        .corner-decoration {
            position: absolute;
            width: 60px;
            height: 60px;
            opacity: 0.1;
        }
        .corner-tl { top: 10px; left: 10px; border-top: 3px solid #0891b2; border-left: 3px solid #0891b2; border-radius: 8px 0 0 0; }
        .corner-tr { top: 10px; right: 10px; border-top: 3px solid #0891b2; border-right: 3px solid #0891b2; border-radius: 0 8px 0 0; }
        .corner-bl { bottom: 10px; left: 10px; border-bottom: 3px solid #0891b2; border-left: 3px solid #0891b2; border-radius: 0 0 0 8px; }
        .corner-br { bottom: 10px; right: 10px; border-bottom: 3px solid #0891b2; border-right: 3px solid #0891b2; border-radius: 0 0 8px 0; }
        /* Print styles */
        @media print {
            body { background: white; padding: 0; }
            .kokarde-card { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="kokarde-card" id="kokardeCard">
        <!-- Corner decorations -->
        <div class="corner-decoration corner-tl"></div>
        <div class="corner-decoration corner-tr"></div>
        <div class="corner-decoration corner-bl"></div>
        <div class="corner-decoration corner-br"></div>

        <!-- Header -->
        <div class="kokarde-header">
            <div class="header-logos">
                <img src="/images/logo-kabupaten.webp" alt="" class="header-logo">
                <img src="/images/favicon.webp" alt="" class="header-logo">
                <img src="/images/logo-lptq.webp" alt="" class="header-logo">
                <img src="/images/emtq-resmi.webp" alt="" class="header-logo header-logo-main">
            </div>
            <div class="header-title">Peserta</div>
            <div class="header-event">{{ $eventTitle }}</div>
            <div class="header-year">{{ $eventYear }}</div>
        </div>

        <!-- Photo & Info Section -->
        <div class="kokarde-photo-section">
            <div class="photo-wrapper">
                @if ($participant->document_photo && file_exists(public_path('storage/' . $participant->document_photo)))
                    <img src="{{ asset('storage/' . $participant->document_photo) }}" alt="Foto Peserta">
                @else
                    <div class="photo-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <div>Foto</div>
                    </div>
                @endif
            </div>

            <div class="info-section">
                <!-- Lot Number -->
                <div class="info-item">
                    <div class="info-label">Nomor Lot</div>
                    <div class="info-value lot-number">{{ $participant->lot_number ?? '-' }}</div>
                </div>

                <!-- Name -->
                <div class="info-item">
                    <div class="info-label">Nama Peserta</div>
                    <div class="info-value">{{ $participant->name }}</div>
                </div>

                <!-- Branch & Category -->
                <div class="info-item">
                    <div class="info-label">Cabang / Golongan</div>
                    <div class="info-value">{{ $participant->category?->branch ?? '-' }} / {{ $participant->category?->name ?? '-' }}</div>
                </div>

                <!-- Gender -->
                <div class="info-item">
                    <div class="info-label">Golongan</div>
                    <span class="gender-badge {{ $participant->gender }}">
                        {{ ucfirst($participant->gender) }}
                    </span>
                </div>

                <!-- District -->
                <div class="info-item">
                    <div class="info-label">Kecamatan</div>
                    <div class="info-value">{{ $participant->district?->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="kokarde-footer">
            <div class="footer-text">
                <span class="footer-brand">e-MTQ {{ $eventTitle }}</span> | LPTQ Kabupaten Tanah Datar
            </div>
        </div>
    </div>

    <!-- html2canvas for image generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        async function downloadKokarde() {
            const element = document.getElementById('kokardeCard');
            const overlay = document.getElementById('loadingOverlay') || document.createElement('div');

            try {
                const canvas = await html2canvas(element, {
                    scale: 3,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                });

                const link = document.createElement('a');
                link.download = 'Kokarde_{{ preg_replace('/[^a-zA-Z0-9]/', '_', $participant->name) }}.png';
                link.href = canvas.toDataURL('image/png', 1.0);
                link.click();
            } catch (error) {
                console.error('Error generating kokarde:', error);
                alert('Gagal generate kokarde');
            }
        }

        // Auto download on page load
        window.onload = function() {
            setTimeout(downloadKokarde, 500);
        };
    </script>
</body>
</html>
