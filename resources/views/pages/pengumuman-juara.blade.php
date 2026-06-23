<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengumuman Juara Umum - MTQ Nasional ke-XLIII Tanah Datar</title>
    <link rel="icon" type="image/webp" href="<?= asset('images/favicon.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cinzel:wght@600;700;900&family=Inter:wght@400;500;600;700;800;900&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --cyan: #22d3ee;
            --cyan-light: #67e8f9;
            --cyan-dark: #0891b2;
            --sky: #0ea5e9;
            --emerald: #10b981;
            --emerald-dark: #059669;
            --slate-900: #0f172a;
            --slate-950: #020617;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: white;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .grid-bg {
            background:
                linear-gradient(135deg, #020617 0%, #0f172a 50%, #0c4a6e 100%),
                linear-gradient(rgba(14, 165, 233, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(14, 165, 233, 0.08) 1px, transparent 1px);
            background-size: 100% 100%, 40px 40px, 40px 40px;
            background-attachment: fixed;
        }

        /* Hero Orbs */
        .hero-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }
        .hero-orb-cyan {
            background: radial-gradient(circle, rgba(34, 211, 238, 0.15) 0%, transparent 70%);
        }
        .hero-orb-blue {
            background: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, transparent 70%);
        }
        .left-orb { left: -10rem; top: 10rem; width: 40rem; height: 40rem; animation: pulse 8s ease-in-out infinite; }
        .right-orb { right: -12rem; top: 20rem; width: 50rem; height: 50rem; animation: pulse 10s ease-in-out infinite reverse; }
        .bottom-orb { bottom: 10rem; right: 20%; width: 35rem; height: 35rem; opacity: 0.4; }

        @keyframes pulse {
            0%, 100% { transform: scale(1) translate(0, 0); opacity: 0.8; }
            50% { transform: scale(1.1) translate(-20px, 20px); opacity: 1; }
        }

        /* Floating Particles */
        .particle {
            position: fixed;
            width: 4px; height: 4px;
            background: var(--cyan);
            border-radius: 50%;
            opacity: 0.4;
            animation: float-particle 20s infinite ease-in-out;
            pointer-events: none;
            z-index: 1;
        }

        @keyframes float-particle {
            0%, 100% { transform: translateY(0) translateX(0) rotate(0deg); opacity: 0; }
            10% { opacity: 0.6; }
            50% { transform: translateY(-50vh) translateX(50px) rotate(180deg); opacity: 0.4; }
            90% { opacity: 0.6; }
            100% { transform: translateY(-100vh) translateX(0) rotate(360deg); opacity: 0; }
        }

        /* Islamic Decorations */
        .islamic-icon {
            position: fixed;
            font-size: 1.5rem;
            opacity: 0.15;
            pointer-events: none;
            z-index: 1;
            animation: float-icon 15s ease-in-out infinite;
        }

        @keyframes float-icon {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.1; }
            25% { transform: translateY(-30px) rotate(5deg); opacity: 0.2; }
            50% { transform: translateY(-60px) rotate(0deg); opacity: 0.15; }
            75% { transform: translateY(-30px) rotate(-5deg); opacity: 0.2; }
        }

        /* Light Rays */
        .light-rays {
            position: fixed;
            top: -50%; left: 50%;
            transform: translateX(-50%);
            width: 200%; height: 100%;
            background: radial-gradient(ellipse at center, rgba(34, 211, 238, 0.12) 0%, transparent 60%);
            animation: pulse-rays 8s ease-in-out infinite;
            z-index: 0;
            pointer-events: none;
        }

        @keyframes pulse-rays {
            0%, 100% { opacity: 0.5; transform: translateX(-50%) scale(1); }
            50% { opacity: 0.8; transform: translateX(-50%) scale(1.1); }
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Glass Card */
        .glass-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .logos-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .logo {
            height: 70px;
            width: auto;
            filter: drop-shadow(0 0 15px rgba(34, 211, 238, 0.4));
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.1);
        }

        .logo-divider {
            width: 2px;
            height: 50px;
            background: linear-gradient(180deg, transparent, var(--cyan), transparent);
            border-radius: 2px;
        }

        .event-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.3) 100%);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
            padding: 8px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.2);
        }

        .title {
            font-family: 'Cinzel', serif;
            font-size: clamp(2rem, 6vw, 4rem);
            font-weight: 900;
            background: linear-gradient(135deg, #67e8f9 0%, #22d3ee 50%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
            letter-spacing: 5px;
            margin-bottom: 10px;
            text-shadow: 0 0 60px rgba(34, 211, 238, 0.4);
        }

        .subtitle {
            font-family: 'Amiri', serif;
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            color: var(--cyan-light);
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .date-location {
            color: #94a3b8;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .date-location i {
            color: var(--cyan);
        }

        /* Navigation Buttons */
        .nav-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 18px 36px;
            background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 30%, #0891b2 70%, #22d3ee 100%);
            background-size: 200% 200%;
            color: #0f172a;
            text-decoration: none;
            border-radius: 60px;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 15px 50px rgba(34, 211, 238, 0.5), inset 0 2px 0 rgba(255,255,255,0.3);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            animation: btn-shimmer 3s ease infinite;
        }

        @keyframes btn-shimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .nav-btn-primary:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 25px 70px rgba(34, 211, 238, 0.6), inset 0 2px 0 rgba(255,255,255,0.4);
        }

        .nav-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 18px 36px;
            background: linear-gradient(135deg, #10b981 0%, #059669 30%, #047857 70%, #10b981 100%);
            background-size: 200% 200%;
            color: white;
            text-decoration: none;
            border-radius: 60px;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 15px 50px rgba(16, 185, 129, 0.5), inset 0 2px 0 rgba(255,255,255,0.2);
            transition: all 0.4s ease;
            animation: btn-shimmer-green 3s ease infinite;
        }

        @keyframes btn-shimmer-green {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .nav-btn-secondary:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 25px 70px rgba(16, 185, 129, 0.6), inset 0 2px 0 rgba(255,255,255,0.3);
        }

        /* Podium Section */
        .podium-section {
            margin-bottom: 60px;
        }

        .section-title {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--cyan);
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 3px;
            position: relative;
        }

        .section-title::before,
        .section-title::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 100px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
        }

        .section-title::before { right: calc(50% + 150px); }
        .section-title::after { left: calc(50% + 150px); }

        .podium {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 20px;
            padding: 40px 20px;
            width: 100%;
            margin: 0 auto;
        }

        .podium-item {
            text-align: center;
            position: relative;
            flex: 1;
            max-width: 200px;
        }

        .podium-item.first {
            order: 2;
            margin-top: -30px;
        }

        .podium-item.second {
            order: 1;
        }

        .podium-item.third {
            order: 3;
        }

        .podium-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan) 0%, var(--cyan-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 3rem;
            box-shadow: 0 0 40px rgba(34, 211, 238, 0.6);
            animation: avatar-glow 2s ease-in-out infinite alternate;
            position: relative;
            overflow: hidden;
            color: #0f172a;
        }

        @keyframes avatar-glow {
            from { box-shadow: 0 0 40px rgba(34, 211, 238, 0.6); }
            to { box-shadow: 0 0 60px rgba(34, 211, 238, 0.9); }
        }

        .podium-item.first .podium-avatar {
            width: 150px;
            height: 150px;
            font-size: 4rem;
        }

        .podium-crown {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2.5rem;
            animation: bounce-crown 1s ease-in-out infinite;
        }

        @keyframes bounce-crown {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-10px); }
        }

        .podium-name {
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .podium-item.first .podium-name {
            font-size: 1.5rem;
            color: var(--cyan-light);
        }

        .podium-district {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .podium-points {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.9) 100%);
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--cyan);
            border: 1px solid var(--cyan);
        }

        .podium-stand {
            width: 100px;
            background: linear-gradient(180deg, var(--cyan) 0%, var(--cyan-dark) 100%);
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cinzel', serif;
            font-weight: 900;
            font-size: 2rem;
            color: #0f172a;
            margin-top: 10px;
        }

        .podium-item.first .podium-stand {
            height: 120px;
            width: 120px;
            font-size: 3rem;
        }

        .podium-item.second .podium-stand {
            height: 90px;
            width: 100px;
            font-size: 2.5rem;
        }

        .podium-item.third .podium-stand {
            height: 60px;
            width: 100px;
            font-size: 2rem;
            background: linear-gradient(180deg, #94a3b8 0%, #64748b 100%);
            color: #0f172a;
        }

        /* Ensure podium items text centered */
        .podium-item .podium-name,
        .podium-item .podium-district,
        .podium-item .podium-points {
            text-align: center;
        }

        /* Rankings Table */
        .rankings-section {
            margin-bottom: 60px;
        }

        .rankings-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(30, 41, 59, 0.8) 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .rankings-table thead th {
            background: linear-gradient(135deg, var(--cyan) 0%, var(--cyan-dark) 100%);
            color: #0f172a;
            padding: 15px 20px;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .rankings-table thead th:first-child {
            border-radius: 20px 0 0 0;
        }

        .rankings-table thead th:last-child {
            border-radius: 0 20px 0 0;
        }

        .rankings-table tbody tr {
            transition: all 0.3s ease;
        }

        .rankings-table tbody tr:hover {
            transform: scale(1.02);
            background: rgba(34, 211, 238, 0.1);
        }

        .rankings-table tbody td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }

        .rankings-table tbody tr:last-child td {
            border-bottom: none;
        }

        .rank-table tbody tr:last-child td:first-child {
            border-radius: 0 0 0 20px;
        }

        .rank-table tbody tr:last-child td:last-child {
            border-radius: 0 0 20px 0;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-family: 'Cinzel', serif;
            font-weight: 900;
            font-size: 1.2rem;
        }

        .rank-1 {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #78350f;
            box-shadow: 0 0 30px rgba(34, 211, 238, 0.5);
        }

        .rank-2 {
            background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%);
            color: #334155;
        }

        .rank-3 {
            background: linear-gradient(135deg, #fcd34d 0%, #d97706 100%);
            color: #451a03;
        }

        .rank-other {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
        }

        .district-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .district-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--cyan) 0%, var(--cyan-dark) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--onyx);
        }

        .points-display {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            font-size: 1.8rem;
            color: var(--cyan);
            text-shadow: 0 0 20px rgba(34, 211, 238, 0.5);
        }

        .rank-counts {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .count-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .count-badge.juara1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #78350f; }
        .count-badge.juara2 { background: linear-gradient(135deg, #e2e8f0, #94a3b8); color: #334155; }
        .count-badge.juara3 { background: linear-gradient(135deg, #fcd34d, #d97706); color: #451a03; }
        .count-badge.harapan { background: rgba(96, 165, 250, 0.3); color: #93c5fd; }

        /* Footer */
        .footer {
            text-align: center;
            padding: 40px 20px;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
        }

        .footer-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .footer-logo {
            height: 40px;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .footer-logo:hover {
            opacity: 1;
        }

        .footer-text {
            color: var(--silver);
            font-size: 0.9rem;
        }

        .footer-text a {
            color: var(--cyan);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        @media print {
            .hero-orb, .particle, .islamic-icon, .light-rays { display: none !important; }
            body { background: white !important; color: black !important; }
            .container { max-width: 100%; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logos-row { gap: 15px; }
            .logo { height: 50px; }
            .logo.emtq { height: 60px; }
            .logo-divider { height: 40px; }
            .podium { flex-direction: column; align-items: center; gap: 30px; }
            .podium-item { order: unset !important; }
            .podium-avatar { width: 100px; height: 100px; font-size: 2.5rem; }
            .podium-item.first .podium-avatar { width: 120px; height: 120px; font-size: 3rem; }
            .podium-stand { height: 80px !important; width: 100px !important; font-size: 2rem !important; }
            .podium-item.first .podium-stand { height: 100px !important; width: 120px !important; font-size: 2.5rem !important; }
            .section-title::before, .section-title::after { display: none; }
            .rankings-table { font-size: 0.85rem; }
            .rankings-table tbody td { padding: 12px 10px; }
            .signature-section { flex-direction: column; gap: 30px; align-items: center; }
            .signature-left, .signature-right { width: 100%; }
        }
    </style>
</head>
<body class="grid-bg">
    <!-- Hero Orbs Background -->
    <div class="hero-orb hero-orb-cyan left-orb"></div>
    <div class="hero-orb hero-orb-blue right-orb"></div>
    <div class="hero-orb hero-orb-cyan bottom-orb"></div>

    <!-- Light Rays -->
    <div class="light-rays"></div>

    <!-- Floating Particles -->
    <div class="particle" style="left: 5%; top: 80%; animation-delay: 0s;"></div>
    <div class="particle" style="left: 15%; top: 90%; animation-delay: 2s;"></div>
    <div class="particle" style="left: 25%; top: 75%; animation-delay: 4s;"></div>
    <div class="particle" style="left: 35%; top: 85%; animation-delay: 1s;"></div>
    <div class="particle" style="left: 45%; top: 70%; animation-delay: 3s;"></div>
    <div class="particle" style="left: 55%; top: 95%; animation-delay: 5s;"></div>
    <div class="particle" style="left: 65%; top: 80%; animation-delay: 2.5s;"></div>
    <div class="particle" style="left: 75%; top: 88%; animation-delay: 0.5s;"></div>
    <div class="particle" style="left: 85%; top: 72%; animation-delay: 4.5s;"></div>
    <div class="particle" style="left: 95%; top: 82%; animation-delay: 1.5s;"></div>

    <!-- Islamic Decorative Icons -->
    <div class="islamic-icon" style="left: 3%; top: 15%; font-size: 2rem;">🌙</div>
    <div class="islamic-icon" style="left: 8%; top: 45%; font-size: 1.2rem; animation-delay: 2s;">📖</div>
    <div class="islamic-icon" style="left: 5%; top: 75%; font-size: 1.8rem; animation-delay: 4s;">🕌</div>
    <div class="islamic-icon" style="right: 8%; top: 20%; font-size: 1.5rem; animation-delay: 1s;">⭐</div>
    <div class="islamic-icon" style="right: 5%; top: 50%; font-size: 2.2rem; animation-delay: 3s;">✨</div>
    <div class="islamic-icon" style="right: 3%; top: 80%; font-size: 1rem; animation-delay: 5s;">📿</div>
    <div class="islamic-icon" style="left: 50%; top: 5%; font-size: 1.3rem; animation-delay: 2.5s;">🌟</div>
    <div class="islamic-icon" style="left: 30%; top: 3%; font-size: 1.6rem; animation-delay: 1.5s;">🏆</div>
    <div class="islamic-icon" style="right: 30%; top: 8%; font-size: 1.1rem; animation-delay: 3.5s;">🎊</div>
    <div class="islamic-icon" style="left: 70%; top: 2%; font-size: 1.4rem; animation-delay: 0.8s;">🎉</div>

    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logos-row">
                <img src="<?= asset('images/logo-kabupaten.webp') ?>" alt="Logo Kabupaten" class="logo" title="Kabupaten Tanah Datar">
                <div class="logo-divider"></div>
                <img src="<?= asset('images/favicon.webp') ?>" alt="Logo MTQ" class="logo" title="Logo MTQ">
                <div class="logo-divider"></div>
                <img src="<?= asset('images/logo-lptq.webp') ?>" alt="Logo LPTQ" class="logo" title="LPTQ">
                <div class="logo-divider"></div>
                <img src="<?= asset('images/emtq-resmi.webp') ?>" alt="e-MTQ" class="logo" title="e-MTQ System">
            </div>

            <div class="event-badge">
                <i class="fas fa-check-circle"></i> Event Telah Selesai
            </div>

            <h1 class="title">Juara Umum</h1>
            <p class="subtitle"><?= e($eventTitle) ?></p>
            <div class="subtitle" style="font-size: 1.5rem; margin-top: 5px;">
                <?= e($organizationName) ?>
            </div>
            <div class="date-location">
                <span><i class="fas fa-calendar-alt"></i> 19 - 23 Juni 2026</span>
                <span>|</span>
                <span><i class="fas fa-map-marker-alt"></i> Kecamatan Pariangan</span>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="<?= route('pengumuman.juara') ?>" class="nav-btn-primary">
                    <i class="fas fa-trophy"></i> Juara Umum
                </a>
                <a href="<?= route('pengumuman.juara-peserta') ?>" class="nav-btn-secondary">
                    <i class="fas fa-medal"></i> Juara Per Golongan
                </a>
            </div>
        </header>

        <!-- Podium Section -->
        <?php if (count($districtRankings) >= 3): ?>
        <section class="podium-section">
            <h2 class="section-title">
                <i class="fas fa-trophy"></i> Top 3 Kecamatan
            </h2>

            <div class="podium">
                <!-- 2nd Place -->
                <?php $second = $districtRankings[1] ?? null; ?>
                <?php if ($second): ?>
                <div class="podium-item second">
                    <div class="podium-avatar">
                        <i class="fas fa-medal"></i>
                    </div>
                    <center>
                    <div class="podium-name"><?= e($second['district_name']) ?></div>
                    <div class="podium-district">Runner Up</div>
                    <div class="podium-points"><?= $second['points'] ?> Poin</div>
                    <div class="podium-stand">2</div></center>
                </div>
                <?php endif; ?>

                <!-- 1st Place -->
                <?php $first = $districtRankings[0] ?? null; ?>
                <?php if ($first): ?>
                <div class="podium-item first">
                    <div class="podium-crown">👑</div>
                    <div class="podium-avatar">
                        <i class="fas fa-crown"></i>
                    </div>
                    <center>
                    <div class="podium-name"><?= e($first['district_name']) ?></div>
                    <div class="podium-district"><?= $first['participant_count'] ?> Peserta</div>
                    <div class="podium-points"><?= $first['points'] ?> Poin</div>
                    <div class="podium-stand">1</div></center>
                </div>
                <?php endif; ?>

                <!-- 3rd Place -->
                <?php $third = $districtRankings[2] ?? null; ?>
                <?php if ($third): ?>
                <div class="podium-item third" style="align: center;">
                    <div class="podium-avatar">
                        <i class="fas fa-award"></i>
                    </div>
                    <center>
                    <div class="podium-name"><?= e($third['district_name']) ?></div>
                    <div class="podium-district">Third Place</div>
                    <div class="podium-points"><?= $third['points'] ?> Poin</div>
                    <div class="podium-stand">3</div></center>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Rankings Table -->
        <section class="rankings-section">
            <h2 class="section-title">
                <i class="fas fa-list-ol"></i> Peringkat Kecamatan
            </h2>

            <table class="rankings-table">
                <thead>
                    <tr>
                        <th style="width: 80px; text-align: center;">Peringkat</th>
                        <th>Kecamatan</th>
                        <th style="text-align: center;">Total Poin</th>
                        <th>Perolehan Juara</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($districtRankings as $district): ?>
                    <tr>
                        <td style="text-align: center;">
                            <?php
                            $rank = $district['rank'];
                            $rankClass = $rank <= 3 ? 'rank-' . $rank : 'rank-other';
                            ?>
                            <span class="rank-badge <?= e($rankClass) ?>">
                                <?php if ($rank == 1): ?>
                                    <i class="fas fa-trophy"></i>
                                <?php elseif ($rank == 2): ?>
                                    <i class="fas fa-medal"></i>
                                <?php elseif ($rank == 3): ?>
                                    <i class="fas fa-award"></i>
                                <?php else: ?>
                                    <?= $rank ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <div class="district-name">
                                <span class="district-icon">
                                    <i class="fas fa-landmark"></i>
                                </span>
                                <?= e($district['district_name']) ?>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span class="points-display"><?= $district['points'] ?></span>
                        </td>
                        <td>
                            <div class="rank-counts">
                                <?php if (($district['rank_counts'][1] ?? 0) > 0): ?>
                                    <span class="count-badge juara1">
                                        <i class="fas fa-trophy"></i> <?= $district['rank_counts'][1] ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (($district['rank_counts'][2] ?? 0) > 0): ?>
                                    <span class="count-badge juara2">
                                        <i class="fas fa-medal"></i> <?= $district['rank_counts'][2] ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (($district['rank_counts'][3] ?? 0) > 0): ?>
                                    <span class="count-badge juara3">
                                        <i class="fas fa-award"></i> <?= $district['rank_counts'][3] ?>
                                    </span>
                                <?php endif; ?>
                                <?php $harapanTotal = ($district['rank_counts'][4] ?? 0) + ($district['rank_counts'][5] ?? 0) + ($district['rank_counts'][6] ?? 0); ?>
                                <?php if ($harapanTotal > 0): ?>
                                    <span class="count-badge harapan">
                                        <i class="fas fa-star"></i> <?= $harapanTotal ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Aturan Poin Section -->
        <section class="rankings-section">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i> Aturan Pemberian Poin
            </h2>

            <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; max-width: 100%;">
                <?php foreach ($pointRules as $rank => $points): ?>
                    <?php
                    $label = match ($rank) {
                        1 => 'Juara 1',
                        2 => 'Juara 2',
                        3 => 'Juara 3',
                        4 => 'Harapan 1',
                        5 => 'Harapan 2',
                        6 => 'Harapan 3',
                        default => 'Peringkat ' . $rank,
                    };
                    $badgeClass = match ($rank) {
                        1 => 'juara1',
                        2 => 'juara2',
                        3 => 'juara3',
                        4, 5, 6 => 'harapan',
                        default => 'rank-other',
                    };
                    ?>
                    <div style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.9) 100%); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 12px; padding: 15px 25px; text-align: center; min-width: 140px;">
                        <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 5px;"><?= e($label) ?></div>
                        <div style="font-family: 'Cinzel', serif; font-size: 1.8rem; font-weight: 700; color: var(--cyan);"><?= $points ?> Poin</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Rekapitulasi Section -->
        <?php
        $totalRankCounts = ['juara1' => 0, 'juara2' => 0, 'juara3' => 0, 'harapan1' => 0, 'harapan2' => 0, 'harapan3' => 0];
        foreach ($districtRankings as $district) {
            foreach ($district['rank_counts'] as $rank => $count) {
                $key = match ($rank) {
                    1 => 'juara1',
                    2 => 'juara2',
                    3 => 'juara3',
                    4 => 'harapan1',
                    5 => 'harapan2',
                    6 => 'harapan3',
                    default => null,
                };
                if ($key) {
                    $totalRankCounts[$key] += $count;
                }
            }
        }
        ?>
        <section class="rankings-section">
            <h2 class="section-title">
                <i class="fas fa-chart-pie"></i> Rekapitulasi Total Perolehan
            </h2>

            <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; max-width: 100%;">
                <?php foreach ($totalRankCounts as $key => $count): ?>
                    <?php
                    $label = match ($key) {
                        'juara1' => 'Juara 1',
                        'juara2' => 'Juara 2',
                        'juara3' => 'Juara 3',
                        'harapan1' => 'Harapan 1',
                        'harapan2' => 'Harapan 2',
                        'harapan3' => 'Harapan 3',
                        default => $key,
                    };
                    $pointsLabel = match ($key) {
                        'juara1' => 'x 9 Poin',
                        'juara2' => 'x 7 Poin',
                        'juara3' => 'x 5 Poin',
                        'harapan1' => 'x 3 Poin',
                        'harapan2' => 'x 2 Poin',
                        'harapan3' => 'x 1 Poin',
                        default => '',
                    };
                    ?>
                    <div style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.9) 100%); border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 12px; padding: 15px 25px; text-align: center; min-width: 120px;">
                        <div style="font-size: 0.75rem; color: var(--silver); margin-bottom: 5px; text-transform: uppercase;"><?= e($label) ?></div>
                        <div style="font-family: 'Cinzel', serif; font-size: 2rem; font-weight: 700; color: var(--gold);"><?= $count ?></div>
                        <div style="font-size: 0.7rem; color: var(--emerald-light);"><?= e($pointsLabel) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Detail Perolehan Section -->
        <section class="rankings-section">
            <h2 class="section-title">
                <i class="fas fa-list"></i> Rincian Perolehan Per Kecamatan
            </h2>

            <table class="rankings-table">
                <thead>
                    <tr>
                        <th style="width: 80px; text-align: center;">Peringkat</th>
                        <th>Kecamatan</th>
                        <th style="text-align: center;">Total Poin</th>
                        <th>Perolehan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($districtRankings as $district): ?>
                    <?php
                    $rank = $district['rank'];
                    $rankClass = $rank <= 3 ? 'rank-' . $rank : 'rank-other';
                    $rankBadgeClass = match (true) {
                        $rank === 1 => 'juara1',
                        $rank === 2 => 'juara2',
                        $rank === 3 => 'juara3',
                        $rank === 4 => 'harapan1',
                        $rank === 5 => 'harapan2',
                        $rank === 6 => 'harapan3',
                        default => 'rank-other',
                    };
                    ?>
                    <tr>
                        <td style="text-align: center;">
                            <span class="rank-badge <?= e($rankClass) ?>">
                                <?= $rank ?>
                            </span>
                        </td>
                        <td>
                            <div class="district-name">
                                <span class="district-icon">
                                    <i class="fas fa-landmark"></i>
                                </span>
                                <div>
                                    <?= e($district['district_name']) ?>
                                    <div style="font-size: 0.8rem; color: var(--silver); font-weight: 400;">
                                        <?= e($district['participant_count']) ?> Peserta
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span class="points-display"><?= $district['points'] ?></span>
                        </td>
                        <td>
                            <?php if (!empty($district['details'])): ?>
                                <div style="font-size: 0.8rem; line-height: 1.6;">
                                    <?php foreach ($district['details'] as $detail): ?>
                                        <?php
                                        $detailRank = $detail['rank'];
                                        $rankLabel = match (true) {
                                            $detailRank === 1 => 'J1',
                                            $detailRank === 2 => 'J2',
                                            $detailRank === 3 => 'J3',
                                            $detailRank === 4 => 'H1',
                                            $detailRank === 5 => 'H2',
                                            $detailRank === 6 => 'H3',
                                            default => 'R' . $detailRank,
                                        };
                                        $rankLabelClass = match (true) {
                                            $detailRank === 1 => 'juara1',
                                            $detailRank === 2 => 'juara2',
                                            $detailRank === 3 => 'juara3',
                                            $detailRank === 4, 5, 6 => 'harapan',
                                            default => 'rank-other',
                                        };
                                        $gender = $detail['gender'] ?? '';
                                        ?>
                                        <div style="margin-bottom: 4px; display: flex; gap: 8px; align-items: flex-start;">
                                            <span style="font-weight: 700; min-width: 30px; color: <?= match($rankLabelClass) { 'juara1' => '#fbbf24', 'juara2' => '#94a3b8', 'juara3' => '#fcd34d', default => '#60a5fa' }; ?>;">
                                                [<?= e($rankLabel) ?>]
                                            </span>
                                            <span style="flex: 1;">
                                                <?= e($detail['category']) ?> -
                                                <?= e($detail['participant_name']) ?>
                                                <?php if ($gender): ?>
                                                    <span style="font-size: 0.7rem; padding: 1px 4px; border-radius: 2px; margin-left: 3px; background: <?= $gender === 'putra' ? '#e0f2fe' : '#fce7f3'; ?>; color: <?= $gender === 'putra' ? '#0369a1' : '#9d174d'; ?>;">
                                                        <?= e(ucfirst($gender)) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                            <span style="font-weight: 600; color: var(--gold); white-space: nowrap;">
                                                (<?= $detail['points'] ?> poin)
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: var(--silver); font-style: italic;">Belum ada perolehan</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-logos">
                <img src="<?= asset('images/logo-kabupaten.webp') ?>" alt="Logo" class="footer-logo">
                <img src="<?= asset('images/favicon.webp') ?>" alt="Logo" class="footer-logo">
                <img src="<?= asset('images/logo-lptq.webp') ?>" alt="Logo" class="footer-logo">
                <img src="<?= asset('images/emtq-resmi.webp') ?>" alt="Logo" class="footer-logo">
            </div>
            <p class="footer-text">
                Pengumuman ini di-generate oleh <a href="#">e-MTQ System</a><br>
                <small>© <?= date('Y') ?> MTQ Nasional ke-XLIII Tingkat Kabupaten Tanah Datar</small>
            </p>
        </footer>
    </div>

    <script>
        // Add entrance animations
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.rankings-table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                row.style.transition = 'all 0.5s ease';
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 + (index * 50));
            });

            // Animate podium items
            const podiumItems = document.querySelectorAll('.podium-item');
            podiumItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'scale(0.8) translateY(30px)';
                item.style.transition = 'all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'scale(1) translateY(0)';
                }, 300 + (index * 200));
            });
        });

        // Auto-refresh every 60 seconds
        setTimeout(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
