<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengumuman Juara Per Golongan - MTQ Nasional ke-XLIII Tanah Datar</title>
    <link rel="icon" type="image/webp" href="<?= asset('images/favicon.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cinzel:wght@600;700;900&family=Inter:wght@400;500;600;700;800;900&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            --putra-color: #0891b2;
            --putri-color: #be185d;
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
            background: radial-gradient(circle, rgba(34, 211, 238, 0.2) 0%, transparent 70%);
        }
        .hero-orb-blue {
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
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
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .logo {
            height: 70px;
            width: auto;
            filter: drop-shadow(0 0 20px rgba(34, 211, 238, 0.5));
            transition: transform 0.3s ease;
        }

        .logo:hover { transform: scale(1.1); }
        .logo.emtq { height: 90px; }

        .logo-divider {
            width: 3px; height: 50px;
            background: linear-gradient(180deg, transparent, var(--cyan), transparent);
            border-radius: 2px;
        }

        .event-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.3) 0%, rgba(5, 150, 105, 0.4) 100%);
            border: 1px solid rgba(16, 185, 129, 0.5);
            color: #6ee7b7;
            padding: 10px 30px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 20px;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
            animation: glow-badge 2s ease-in-out infinite alternate;
        }

        .event-badge i { animation: bounce 1s ease infinite; }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        @keyframes glow-badge {
            from { box-shadow: 0 0 30px rgba(16, 185, 129, 0.3); }
            to { box-shadow: 0 0 50px rgba(16, 185, 129, 0.5); }
        }

        .title {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.8rem, 5vw, 3rem);
            font-weight: 900;
            background: linear-gradient(135deg, var(--cyan-light) 0%, var(--cyan) 50%, var(--cyan-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 10px;
            text-shadow: 0 0 60px rgba(34, 211, 238, 0.5);
        }

        .subtitle {
            font-family: 'Amiri', serif;
            font-size: clamp(1rem, 2.5vw, 1.5rem);
            color: var(--cyan-light);
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .date-location {
            color: #94a3b8;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .date-location i { color: var(--cyan); }

        /* Branch Section */
        .branch-section {
            margin-bottom: 60px;
        }

        .branch-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .branch-title {
            font-family: 'Cinzel', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--cyan);
            text-transform: uppercase;
            letter-spacing: 3px;
            position: relative;
            display: inline-block;
            padding: 0 40px;
        }

        .branch-title::before,
        .branch-title::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 80px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
        }

        .branch-title::before { right: 100%; }
        .branch-title::after { left: 100%; }

        .branch-icon {
            display: block;
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--cyan);
        }

        /* Category Card */
        .category-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.9) 100%);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(34, 211, 238, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }

        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(34, 211, 238, 0.2);
        }

        .category-name {
            font-family: 'Cinzel', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
        }

        .category-meta {
            display: flex;
            gap: 15px;
        }

        .meta-badge {
            background: rgba(34, 211, 238, 0.15);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--cyan-light);
            border: 1px solid rgba(34, 211, 238, 0.3);
        }

        /* Gender Tabs */
        .gender-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .gender-tab {
            flex: 1;
            padding: 15px 25px;
            border-radius: 15px;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .gender-tab.putra {
            background: rgba(8, 145, 178, 0.2);
            color: #67e8f9;
            border-color: rgba(8, 145, 178, 0.3);
        }

        .gender-tab.putra.active {
            background: linear-gradient(135deg, var(--putra-color) 0%, #0e7490 100%);
            color: white;
            border-color: var(--putra-color);
            box-shadow: 0 10px 30px rgba(8, 145, 178, 0.4);
        }

        .gender-tab.putri {
            background: rgba(190, 24, 93, 0.2);
            color: #f9a8d4;
            border-color: rgba(190, 24, 93, 0.3);
        }

        .gender-tab.putri.active {
            background: linear-gradient(135deg, var(--putri-color) 0%, #9d174d 100%);
            color: white;
            border-color: var(--putri-color);
            box-shadow: 0 10px 30px rgba(190, 24, 93, 0.4);
        }

        .gender-tab i { margin-right: 8px; }

        /* Winner Section */
        .winner-section {
            margin-bottom: 40px;
        }

        .winner-section-title {
            font-family: 'Cinzel', serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: #fbbf24;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
        }

        .winner-section-title i {
            font-size: 1.4rem;
            color: #fbbf24;
            animation: star-bounce 1s ease infinite;
        }

        @keyframes star-bounce {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-5px) scale(1.2); }
        }

        .winner-section-title span {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #fbbf24 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: title-shimmer-gold 3s ease infinite;
        }

        @keyframes title-shimmer-gold {
            0%, 100% { background-position: 0% center; }
            50% { background-position: 100% center; }
        }

        .winner-section-title::before {
            content: '';
            flex: 1;
            max-width: 80px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #fbbf24);
            border-radius: 2px;
        }

        .winner-section-title::after {
            content: '';
            flex: 1;
            max-width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #fbbf24, transparent);
            border-radius: 2px;
        }

        .winner-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        /* Winner Card */
        .winner-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(34, 211, 238, 0.2);
            transition: all 0.3s ease;
        }

        .winner-card:hover {
            transform: translateY(-5px);
            border-color: var(--cyan);
            box-shadow: 0 20px 40px rgba(34, 211, 238, 0.2);
        }

        /* JUARA 1 - MOST PROMINENT */
        .winner-card.rank-1 {
            border: 3px solid var(--cyan);
            background: linear-gradient(145deg, rgba(34, 211, 238, 0.2) 0%, rgba(15, 23, 42, 0.95) 100%);
            box-shadow:
                0 0 30px rgba(34, 211, 238, 0.4),
                0 25px 50px rgba(0, 0, 0, 0.5),
                inset 0 0 60px rgba(34, 211, 238, 0.1);
            transform: scale(1.05);
            z-index: 10;
        }

        .winner-card.rank-1::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--cyan), var(--cyan-light), var(--cyan));
        }

        .winner-card.rank-1::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 40%, rgba(34, 211, 238, 0.1) 50%, transparent 60%);
            animation: shine-gold 3s infinite;
        }

        @keyframes shine-gold {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        /* JUARA 2 - SILVER */
        .winner-card.rank-2 {
            border: 3px solid #94a3b8;
            background: linear-gradient(145deg, rgba(148, 163, 184, 0.15) 0%, rgba(15, 23, 42, 0.95) 100%);
            box-shadow:
                0 0 20px rgba(148, 163, 184, 0.3),
                0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .winner-card.rank-2::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #94a3b8, #cbd5e1, #94a3b8);
        }

        /* JUARA 3 - BRONZE */
        .winner-card.rank-3 {
            border: 3px solid #cd7f32;
            background: linear-gradient(145deg, rgba(205, 127, 50, 0.12) 0%, rgba(15, 23, 42, 0.95) 100%);
            box-shadow:
                0 0 15px rgba(205, 127, 50, 0.25),
                0 15px 35px rgba(0, 0, 0, 0.35);
        }

        .winner-card.rank-3::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #cd7f32, #f59e0b, #cd7f32);
        }

        .rank-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            min-width: 55px;
            min-height: 55px;
            padding: 5px 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cinzel', serif;
            font-weight: 900;
            z-index: 5;
            border: 3px solid rgba(255,255,255,0.3);
            white-space: nowrap;
        }

        .rank-badge.juara1 {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #fbbf24 100%);
            background-size: 200% auto;
            color: #78350f;
            box-shadow: 0 0 35px rgba(251, 191, 36, 0.8), 0 8px 25px rgba(0,0,0,0.4);
            min-width: 65px;
            min-height: 65px;
            font-size: 1.4rem;
            animation: badge-glow-gold 2s ease-in-out infinite;
        }

        @keyframes badge-glow-gold {
            0%, 100% { box-shadow: 0 0 35px rgba(251, 191, 36, 0.7), 0 8px 25px rgba(0,0,0,0.4); }
            50% { box-shadow: 0 0 50px rgba(251, 191, 36, 0.9), 0 10px 30px rgba(0,0,0,0.5); }
        }

        .rank-badge.juara2 {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 50%, #e2e8f0 100%);
            background-size: 200% auto;
            color: #334155;
            box-shadow: 0 0 20px rgba(203, 213, 225, 0.6), 0 6px 20px rgba(0,0,0,0.3);
        }

        .rank-badge.juara3 {
            background: linear-gradient(135deg, #fcd34d 0%, #d97706 50%, #fcd34d 100%);
            background-size: 200% auto;
            color: #451a03;
            box-shadow: 0 0 20px rgba(253, 211, 77, 0.6), 0 6px 20px rgba(0,0,0,0.35);
            animation: badge-glow-bronze 2.5s ease-in-out infinite;
        }

        @keyframes badge-glow-bronze {
            0%, 100% { box-shadow: 0 0 20px rgba(253, 211, 77, 0.5), 0 6px 20px rgba(0,0,0,0.35); }
            50% { box-shadow: 0 0 30px rgba(253, 211, 77, 0.8), 0 8px 25px rgba(0,0,0,0.4); }
        }

        /* Winner Photo - More Prominent */
        .winner-photo {
            width: 120px; height: 120px;
            border-radius: 50%;
            margin: 10px auto 15px;
            overflow: hidden;
            border: 4px solid var(--cyan);
            background: linear-gradient(135deg, var(--slate) 0%, var(--onyx) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        .winner-card.rank-1 .winner-photo {
            width: 150px; height: 150px;
            border-width: 5px;
            box-shadow:
                0 0 30px rgba(34, 211, 238, 0.6),
                0 0 60px rgba(34, 211, 238, 0.3);
        }

        .winner-card.rank-2 .winner-photo {
            border-color: #94a3b8;
            width: 130px; height: 130px;
        }

        .winner-card.rank-3 .winner-photo {
            border-color: #cd7f32;
            width: 125px; height: 125px;
        }

        .winner-photo img {
            width: 100%; height: 100%;
            object-fit: cover;
        }

        .winner-photo i {
            font-size: 3rem;
            color: var(--silver);
        }

        .winner-card.rank-1 .winner-photo i {
            font-size: 4rem;
        }

        /* Crown for Rank 1 */
        .winner-card.rank-1 .crown {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2.5rem;
            animation: bounce-crown 1s ease-in-out infinite;
            z-index: 10;
        }

        @keyframes bounce-crown {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-8px); }
        }

        .winner-name {
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
            line-height: 1.3;
            position: relative;
            z-index: 2;
        }

        .winner-card.rank-1 .winner-name {
            font-size: 1.5rem;
            color: var(--cyan-light);
            text-shadow: 0 0 20px rgba(34, 211, 238, 0.5);
        }

        .winner-card.rank-2 .winner-name {
            font-size: 1.35rem;
            color: #e2e8f0;
        }

        .winner-card.rank-3 .winner-name {
            font-size: 1.3rem;
            color: #fcd34d;
        }

        .winner-district {
            color: var(--silver);
            font-size: 0.95rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            position: relative;
            z-index: 2;
        }

        .winner-district i { color: var(--cyan); font-size: 0.9rem; }

        .winner-lot {
            display: inline-block;
            background: rgba(34, 211, 238, 0.2);
            padding: 4px 15px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--cyan);
            position: relative;
            z-index: 2;
        }

        .winner-score {
            margin-top: 12px;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--emerald-light);
            text-shadow: 0 0 15px rgba(16, 185, 129, 0.5);
            position: relative;
            z-index: 2;
        }

        .winner-card.rank-1 .winner-score {
            font-size: 1.6rem;
        }

        .fallback-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255, 200, 0, 0.2);
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #fcd34d;
            border: 1px solid rgba(255, 200, 0, 0.3);
            z-index: 5;
        }

        /* Harapan Section */
        .harapan-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .harapan-card {
            background: linear-gradient(145deg, rgba(251, 191, 36, 0.15) 0%, rgba(15, 23, 42, 0.9) 100%);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            border: 2px solid rgba(251, 191, 36, 0.3);
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .harapan-card:hover {
            transform: translateY(-5px);
            border-color: rgba(251, 191, 36, 0.5);
            box-shadow: 0 15px 40px rgba(251, 191, 36, 0.2);
        }

        .harapan-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 45px;
            min-height: 45px;
            padding: 8px 12px;
            border-radius: 25px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #fbbf24 100%);
            background-size: 200% auto;
            color: #78350f;
            font-weight: 800;
            font-size: 0.9rem;
            margin-bottom: 12px;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
            white-space: nowrap;
        }

        .harapan-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: white;
            margin-bottom: 5px;
        }

        .harapan-district {
            color: var(--silver);
            font-size: 0.8rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--silver);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 40px 20px;
            border-top: 1px solid rgba(148, 163, 184, 0.2);
            margin-top: 60px;
        }

        .footer-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .footer-logo { height: 40px; opacity: 0.7; transition: opacity 0.3s; }
        .footer-logo:hover { opacity: 1; }

        .footer-text {
            color: var(--silver);
            font-size: 0.9rem;
        }

        .footer-text a {
            color: var(--cyan);
            text-decoration: none;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .winner-grid { grid-template-columns: repeat(3, 1fr); gap: 15px; }
            .harapan-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
            .winner-card.rank-1 { transform: scale(1); }
        }

        @media (max-width: 768px) {
            .logos-row { gap: 15px; }
            .logo { height: 50px; }
            .logo.emtq { height: 60px; }
            .logo-divider { height: 35px; }
            .winner-grid { grid-template-columns: 1fr; max-width: 350px; margin: 0 auto; }
            .harapan-grid { grid-template-columns: 1fr; max-width: 300px; margin: 0 auto; }
            .branch-title::before, .branch-title::after { display: none; }
            .category-header { flex-direction: column; gap: 15px; text-align: center; }
            .winner-card.rank-1 { transform: scale(1); }
            .winner-card.rank-1 .winner-photo { width: 130px; height: 130px; }
            .winner-card.rank-2 .winner-photo,
            .winner-card.rank-3 .winner-photo { width: 110px; height: 110px; }
            .winner-card.rank-1 .winner-name { font-size: 1.3rem; }
            .winner-card.rank-2 .winner-name { font-size: 1.2rem; }
            .winner-card.rank-3 .winner-name { font-size: 1.15rem; }
            .winner-card.rank-1 .winner-score { font-size: 1.4rem; }
            .winner-card.rank-1 .crown { font-size: 2rem; top: -20px; }
        }

        @media print {
            .hero-orb, .particle, .islamic-icon, .light-rays { display: none !important; }
            body { background: white !important; color: black !important; }
            .container { max-width: 100%; }
            .category-card { background: white; border: 1px solid #ccc; }
            .winner-card { border: 1px solid #ccc; }
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
                <img src="<?= e(asset('images/logo-kabupaten.webp')) ?>" alt="Logo Kabupaten" class="logo">
                <div class="logo-divider"></div>
                <img src="<?= e(asset('images/favicon.webp')) ?>" alt="Logo MTQ" class="logo">
                <div class="logo-divider"></div>
                <img src="<?= e(asset('images/logo-lptq.webp')) ?>" alt="Logo LPTQ" class="logo">
                <div class="logo-divider"></div>
                <img src="<?= e(asset('images/emtq-resmi.webp')) ?>" alt="e-MTQ" class="logo emtq">
            </div>

            <div class="event-badge">
                <i class="fas fa-check-circle"></i> Event Telah Selesai
            </div>

            <h1 class="title">Juara Per Golongan</h1>
            <p class="subtitle"><?= e($eventTitle) ?></p>
            <div class="subtitle" style="font-size: 1.3rem; margin-top: 5px;">
                <?= e($organizationName) ?>
            </div>
            <div class="date-location">
                <span><i class="fas fa-calendar-alt"></i> 19 - 23 Juni 2026</span>
                <span>|</span>
                <span><i class="fas fa-map-marker-alt"></i> Kecamatan Pariangan</span>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="<?= e(route('pengumuman.juara')) ?>" style="display: inline-flex; align-items: center; gap: 12px; padding: 18px 36px; background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 30%, #0891b2 70%, #22d3ee 100%); background-size: 200% 200%; color: #0f172a; text-decoration: none; border-radius: 60px; font-weight: 800; font-size: 1rem; letter-spacing: 1px; text-transform: uppercase; box-shadow: 0 15px 50px rgba(34, 211, 238, 0.5), inset 0 2px 0 rgba(255,255,255,0.3); transition: all 0.4s ease; animation: btn-shimmer 3s ease infinite; position: relative; overflow: hidden;">
                    <i class="fas fa-trophy"></i> Juara Umum
                </a>
                <a href="<?= route('pengumuman.juara-peserta') ?>" style="display: inline-flex; align-items: center; gap: 12px; padding: 18px 36px; background: linear-gradient(135deg, #10b981 0%, #059669 30%, #047857 70%, #10b981 100%); background-size: 200% 200%; color: white; text-decoration: none; border-radius: 60px; font-weight: 800; font-size: 1rem; letter-spacing: 1px; text-transform: uppercase; box-shadow: 0 15px 50px rgba(16, 185, 129, 0.5), inset 0 2px 0 rgba(255,255,255,0.2); transition: all 0.4s ease; animation: btn-shimmer-green 3s ease infinite; position: relative; overflow: hidden;">
                    <i class="fas fa-medal"></i> Juara Per Golongan
                </a>
            </div>

            <style>
                @keyframes btn-shimmer {
                    0% { background-position: 0% 50%; }
                    50% { background-position: 100% 50%; }
                    100% { background-position: 0% 50%; }
                }
                @keyframes btn-shimmer-green {
                    0% { background-position: 0% 50%; }
                    50% { background-position: 100% 50%; }
                    100% { background-position: 0% 50%; }
                }
                a[style*="padding: 18px"]:hover {
                    transform: translateY(-4px) scale(1.05);
                    box-shadow: 0 25px 70px rgba(34, 211, 238, 0.6), inset 0 2px 0 rgba(255,255,255,0.4);
                }
                a[style*="rgba(16, 185, 129"]:hover {
                    box-shadow: 0 25px 70px rgba(16, 185, 129, 0.6), inset 0 2px 0 rgba(255,255,255,0.3);
                }
            </style>
        </header>

        <?php foreach ($groupedResults as $group): ?>
        <section class="branch-section">
            <div class="branch-header">
                <span class="branch-icon">
                    <?php
                    $branchIcons = [
                        'Tilawah' => '<i class="fas fa-book-quran"></i>',
                        'Hafalan' => '<i class="fas fa-brain"></i>',
                        'Tartil' => '<i class="fas fa-book-open"></i>',
                        'Fahmil' => '<i class="fas fa-lightbulb"></i>',
                        'Syarhil' => '<i class="fas fa-comment-dots"></i>',
                        'Tafsir' => '<i class="fas fa-book"></i>',
                        'Kaligrafi' => '<i class="fas fa-pen-fancy"></i>',
                        'Khutbah' => '<i class="fas fa-mosque"></i>',
                        'Adzan' => '<i class="fas fa-bullhorn"></i>',
                    ];
                    echo $branchIcons[$group['branch']] ?? '<i class="fas fa-award"></i>';
                    ?>
                </span>
                <h2 class="branch-title"><?= e($group['branch']) ?></h2>
            </div>

            <?php foreach ($group['categories'] as $result): ?>
            <div class="category-card">
                <div class="category-header">
                    <h3 class="category-name"><?= e($result['name']) ?></h3>
                    <div class="category-meta">
                        <span class="meta-badge">
                            <i class="fas fa-users"></i> <?= $result['participant_count'] ?> Peserta
                        </span>
                    </div>
                </div>

                <?php
                $hasPutra = isset($result['putra']) && (!empty($result['putra']['juara']) || !empty($result['putra']['harapan']));
                $hasPutri = isset($result['putri']) && (!empty($result['putri']['juara']) || !empty($result['putri']['harapan']));
                $showBothGenders = $hasPutra && $hasPutri;
                $isSingleGender = ($result['is_mfq'] ?? false) || ($result['is_msq'] ?? false);

                // Determine which genders to iterate
                if ($isSingleGender) {
                    $gendersToShow = $hasPutra ? ['putra'] : ($hasPutri ? ['putri'] : []);
                } else {
                    $gendersToShow = ['putra', 'putri'];
                }
                ?>

                <?php if ($showBothGenders): ?>
                <!-- Gender Tabs -->
                <div class="gender-tabs">
                    <div class="gender-tab putra active" data-gender="putra" onclick="switchGender(this, 'putra')">
                        <i class="fas fa-mars"></i> Putra
                    </div>
                    <div class="gender-tab putri" data-gender="putri" onclick="switchGender(this, 'putri')">
                        <i class="fas fa-venus"></i> Putri
                    </div>
                </div>
                <?php endif; ?>

                <?php foreach ($gendersToShow as $gender): ?>
                    <?php
                    $data = $result[$gender] ?? null;
                    if (!$data) continue;
                    $juara = $data['juara'] ?? [];
                    $harapan = $data['harapan'] ?? [];
                    $isEmpty = empty($juara) && empty($harapan);
                    ?>
                    <?php if ($isEmpty) continue; ?>
                    <div class="gender-content" data-gender="<?= $gender ?>" style="<?= $showBothGenders && $gender === 'putri' ? 'display: none;' : '' ?>">
                        <?php if (!empty($juara) || !empty($harapan)): ?>
                            <!-- JUARA Section -->
                            <div class="winner-section">
                                <h4 class="winner-section-title">
                                    <i class="fas fa-trophy"></i> <span>Juara</span>
                                </h4>
                                <div class="winner-grid">
                                    <?php foreach ($juara as $idx => $winner): ?>
                                    <div class="winner-card rank-<?= $idx + 1 ?>">
                                        <?php if ($idx === 0): ?>
                                            <span class="crown">👑</span>
                                        <?php endif; ?>
                                        <?php if (!empty($winner['is_fallback'])): ?>
                                            <span class="fallback-badge"><i class="fas fa-info-circle"></i> Penyisihan</span>
                                        <?php endif; ?>
                                        <span class="rank-badge juara<?= $idx + 1 ?>">
                                            <?= e($winner['rank_label'] ?? ($idx + 1)) ?>
                                        </span>
                                        <div class="winner-photo">
                                            <?php if (!empty($winner['photo_url'])): ?>
                                                <img src="<?= e($winner['photo_url']) ?>" alt="<?= e($winner['name']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fas fa-user\'></i>'">
                                            <?php else: ?>
                                                <i class="fas fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="winner-name"><?= e($winner['name']) ?></div>
                                        <div class="winner-district">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= e($winner['district']) ?>
                                        </div>
                                        <?php if (!empty($winner['lot_number'])): ?>
                                            <span class="winner-lot">Lot <?= e($winner['lot_number']) ?></span>
                                        <?php endif; ?>
                                        <div class="winner-score"><?= e($winner['score']) ?></div>
                                    </div>
                                    <?php endforeach; ?>

                                    <?php if (count($juara) < 3): ?>
                                        <?php for ($i = count($juara); $i < 3; $i++): ?>
                                        <div class="winner-card" style="opacity: 0.4; border-style: dashed;">
                                            <span class="rank-badge juara<?= $i + 1 ?>" style="opacity: 0.6;"><?= ($i + 1) ?></span>
                                            <div class="winner-photo" style="border-color: rgba(148, 163, 184, 0.3);">
                                                <i class="fas fa-user-slash"></i>
                                            </div>
                                            <div class="winner-name">-</div>
                                            <div class="winner-district">Belum ada juara</div>
                                        </div>
                                        <?php endfor; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- HARAPAN Section -->
                            <?php if (!empty($harapan)): ?>
                            <div class="winner-section">
                                <h4 class="winner-section-title">
                                    <i class="fas fa-star"></i> <span>Harapan</span>
                                </h4>
                                <div class="harapan-grid">
                                    <?php foreach ($harapan as $idx => $winner): ?>
                                    <div class="harapan-card">
                                        <span class="harapan-rank"><?= e($winner['rank_label'] ?? ($idx + 1)) ?></span>
                                        <div class="winner-photo" style="width: 70px; height: 70px; border-width: 2px;">
                                            <?php if (!empty($winner['photo_url'])): ?>
                                                <img src="<?= e($winner['photo_url']) ?>" alt="<?= e($winner['name']) ?>" onerror="this.parentElement.innerHTML='<i class=\'fas fa-user\'></i>'">
                                            <?php else: ?>
                                                <i class="fas fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="harapan-name"><?= e($winner['name']) ?></div>
                                        <div class="harapan-district"><?= e($winner['district']) ?></div>
                                        <?php if (!empty($winner['lot_number'])): ?>
                                            <div class="winner-lot" style="margin-top: 5px; font-size: 0.75rem;">Lot <?= e($winner['lot_number']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>Belum ada data juara untuk golongan ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endforeach; ?>

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
        function switchGender(tab, gender) {
            // Update tab styles
            document.querySelectorAll('.gender-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            // Show/hide content
            document.querySelectorAll('.gender-content').forEach(content => {
                if (content.dataset.gender === gender) {
                    content.style.display = 'block';
                } else {
                    content.style.display = 'none';
                }
            });
        }

        // Entrance animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.category-card');
            cards.forEach((card, idx) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s ease';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 200 + (idx * 100));
            });

            const winners = document.querySelectorAll('.winner-card');
            winners.forEach((winner, idx) => {
                winner.style.opacity = '0';
                winner.style.transform = 'scale(0.8) translateY(20px)';
                winner.style.transition = 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
                setTimeout(() => {
                    winner.style.opacity = '1';
                    winner.style.transform = 'scale(1) translateY(0)';
                }, 500 + (idx * 100));
            });
        });

        // Auto-refresh every 60 seconds
        setTimeout(() => { location.reload(); }, 60000);
    </script>
</body>
</html>