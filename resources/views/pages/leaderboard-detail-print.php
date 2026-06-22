<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($filterTitle ?? 'Rekap Detail Nilai') ?> - e-MTQ</title>
    <link rel="icon" type="image/webp" href="<?= asset('images/favicon.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --gold: #b8860b;
            --gold-dark: #8b6914;
            --cyan: #0891b2;
            --pink: #be185d;
            --green: #059669;
            --bg-white: #ffffff;
            --bg-light: #f8fafc;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-light: #64748b;
            --border: #e2e8f0;
            --border-dark: #cbd5e1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-white);
            color: var(--text-dark);
            line-height: 1.5;
            padding: 0;
            font-size: 10px;
        }

        @media print {
            body { padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .category { page-break-after: always; }
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 15px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #ffffff 0%, #fefce8 50%, #fef9c3 100%);
            border-bottom: 4px solid var(--gold);
            padding: 15px 20px;
            margin-bottom: 15px;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .header-logos {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-logo {
            height: 40px;
            width: auto;
        }

        .header-logo-main { height: 50px; }

        .header-text { text-align: center; flex: 1; }

        .header-title {
            font-family: 'Merriweather', serif;
            font-size: 16px;
            font-weight: 900;
            color: var(--gold-dark);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-event {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-medium);
        }

        .header-year {
            font-size: 9px;
            color: var(--text-light);
        }

        .header-meta {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--border);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 9px;
            color: var(--text-medium);
        }

        .meta-icon {
            width: 16px;
            height: 16px;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 8px;
            font-weight: bold;
        }

        /* Print Button */
        .print-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 24px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            font-size: 12px;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 0 auto 15px;
            width: fit-content;
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3);
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .filter-title {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 12px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-title::before {
            content: '🔍';
        }

        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-medium);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-dark);
            background: white;
            min-width: 180px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-select:hover {
            border-color: var(--gold);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 134, 11, 0.2);
        }

        .filter-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3);
        }

        .filter-btn-reset {
            padding: 10px 20px;
            background: var(--bg-light);
            color: var(--text-medium);
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .filter-btn-reset:hover {
            background: var(--border);
        }

        .filter-info {
            font-size: 11px;
            color: var(--text-light);
            margin-top: 10px;
            font-style: italic;
        }

        /* Category Section */
        .category {
            margin-bottom: 20px;
            border: 2px solid var(--border-dark);
            border-radius: 10px;
            overflow: hidden;
        }

        .category-head {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .category-name {
            font-size: 12px;
            font-weight: 700;
        }

        .category-meta {
            display: flex;
            gap: 10px;
        }

        .meta-badge {
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 5px;
            font-size: 9px;
            font-weight: 600;
        }

        /* Table */
        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .ranking-table thead {
            background: var(--bg-light);
        }

        .ranking-table th {
            padding: 8px 10px;
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: var(--text-medium);
            border-bottom: 2px solid var(--border-dark);
        }

        .ranking-table td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .ranking-table tbody tr:nth-child(even) td { background: var(--bg-light); }
        .ranking-table tbody tr:last-child td { border-bottom: none; }

        .rank-cell { width: 35px; text-align: center; font-weight: 700; }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
        }

        .rank-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f; }
        .rank-2 { background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%); color: #334155; }
        .rank-3 { background: linear-gradient(135deg, #fdba74 0%, #ea580c 100%); color: white; }
        .rank-other { background: var(--bg-light); color: var(--text-medium); border: 1px solid var(--border-dark); }

        .name-cell { width: 150px; }
        .name-text { font-weight: 700; color: var(--text-dark); font-size: 13px; }
        .muted { color: var(--text-light); font-size: 9px; }

        .lot-cell { width: 60px; text-align: center; flex-shrink: 0; }
        .lot-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            font-size: 13px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .score-cell { text-align: center; width: 55px; }
        .score-value { font-size: 16px; font-weight: 800; color: var(--green); }

        /* Gender Subheader */
        .gender-subheader td {
            font-weight: 800;
            font-size: 9px;
            text-transform: uppercase;
            padding: 5px 8px;
            letter-spacing: 0.3px;
        }

        .putra-subheader td {
            background: rgba(8, 145, 178, 0.15);
            color: var(--cyan);
        }

        .putri-subheader td {
            background: rgba(190, 24, 93, 0.15);
            color: var(--pink);
        }

        /* Judge Detail Row */
        .judge-row td {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%) !important;
            font-size: 12px;
            color: #92400e;
            border-bottom: 2px dashed #fbbf24;
            padding: 10px 8px;
        }
        .judge-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: #78350f;
            padding-left: 6px;
            font-size: 12px;
        }
        .judge-label::before {
            content: '📋';
        }
        .judge-item {
            display: inline-flex;
            gap: 5px;
            margin: 3px 5px;
            background: white;
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid #fcd34d;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .judge-name { font-weight: 800; color: #92400e; font-size: 12px; }
        .judge-score { font-weight: 900; color: #78350f; font-size: 13px; }

        /* Spacer Row */
        .spacer-row td {
            height: 6px;
            background: #fff !important;
            border: none;
        }

        /* Signature Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 20px;
            padding: 20px 15px;
            border-top: 2px solid var(--gold);
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-radius: 0 0 8px 8px;
        }

        .signature-left {
            width: 45%;
            padding-right: 15px;
        }

        .signature-right {
            width: 35%;
            text-align: center;
            padding-left: 15px;
            border-left: 1px dashed var(--border-dark);
        }

        .signature-title {
            font-weight: 700;
            color: var(--gold-dark);
            font-size: 11px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .signature-title::before {
            content: '📋';
            font-size: 14px;
        }

        /* Judge List Styling */
        .hakim-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 10px;
        }

        .hakim-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            background: white;
            border-radius: 8px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .hakim-number {
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
            flex-shrink: 0;
        }

        /* Editable Hakim Name */
        .hakim-name {
            flex: 1;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px dashed transparent;
            outline: none;
            background: transparent;
            min-width: 0;
        }

        .hakim-name:hover {
            background: #fffbeb;
            border-color: var(--gold);
        }

        .hakim-name:focus {
            background: #fffbeb;
            border: 2px solid var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 134, 11, 0.15);
        }

        .hakim-edit-icon {
            color: var(--text-light);
            font-size: 12px;
            opacity: 0.5;
            transition: opacity 0.2s;
        }

        .hakim-item:hover .hakim-edit-icon {
            opacity: 1;
            color: var(--gold);
        }

        /* Hakim Action Buttons */
        .hakim-actions {
            display: flex;
            gap: 4px;
        }

        .hakim-btn {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            transition: all 0.2s;
        }

        .hakim-add-row {
            margin-top: 8px;
        }

        .hakim-add-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            background: white;
            border: 2px dashed var(--green);
            border-radius: 8px;
            color: var(--green);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }

        .hakim-add-btn:hover {
            background: #ecfdf5;
            border-style: solid;
        }

        @media print {
            .hakim-btn, .hakim-add-btn {
                display: none !important;
            }
        }

        /* Editable Signature Fields */
        .signature-date, .signature-name {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px dashed transparent;
            outline: none;
            background: transparent;
            transition: all 0.2s;
            text-align: center;
        }

        .signature-date {
            font-size: 11px;
            color: var(--text-medium);
            min-width: 150px;
            display: block;
            margin-bottom: 8px;
        }

        .signature-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-dark);
            width: 100%;
            display: block;
        }

        .signature-date:hover, .signature-name:hover {
            background: #fffbeb;
            border-color: var(--gold);
        }

        .signature-date:focus, .signature-name:focus {
            background: #fffbeb;
            border: 2px solid var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 134, 11, 0.15);
        }

        .signature-label {
            font-size: 10px;
            color: var(--text-light);
            margin-bottom: 4px;
        }

        .hakim-empty {
            font-style: italic;
            color: var(--text-light);
            font-size: 11px;
            padding: 15px;
            text-align: center;
            background: white;
            border-radius: 8px;
            border: 1px dashed var(--border-dark);
        }

        /* Signature Box */
        .signature-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .signature-position {
            font-weight: 700;
            color: var(--gold-dark);
            font-size: 11px;
            margin-bottom: 25px;
            text-align: center;
        }

        .signature-stamp-area {
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 9px;
            border: 2px dashed var(--border);
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .signature-line {
            border-top: 1px solid var(--text-dark);
            width: 100%;
            margin-top: 5px;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            color: var(--text-light);
            font-size: 9px;
            border-top: 2px solid var(--border);
        }

        .footer-brand { font-weight: 700; color: var(--gold-dark); }

        @media (max-width: 600px) {
            .header-content { flex-direction: column; }
            .category-header { flex-direction: column; gap: 8px; }
            .ranking-table { font-size: 8px; }
            .ranking-table th, .ranking-table td { padding: 3px 2px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-logos">
                    <img src="/images/logo-kabupaten.webp" alt="Logo Kabupaten" class="header-logo" onerror="this.style.display='none'">
                    <img src="/images/favicon.webp" alt="Logo MTQ" class="header-logo" onerror="this.style.display='none'">
                </div>

                <div class="header-text">
                    <h1 class="header-title"><?= e($filterTitle ?? 'Rekap Detail Nilai') ?></h1>
                    <div class="header-event">MTQ Nasional ke-XLIII Tingkat Kabupaten Tanah Datar</div>
                    <div class="header-year">Pariangan, <?= date('d F Y') ?></div>
                </div>

                <div class="header-logos">
                    <img src="/images/logo-lptq.webp" alt="Logo LPTQ" class="header-logo" onerror="this.style.display='none'">
                    <img src="/images/emtq-resmi.webp" alt="Logo EMTQ" class="header-logo header-logo-main" onerror="this.style.display='none'">
                </div>
            </div>

            <div class="header-meta">
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <span>Generated: <?= e($generatedAt) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">🏆</span>
                    <span><?= count($categoryBlocks) ?> Golongan</span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📋</span>
                    <span>Ranking Penyisihan</span>
                </div>
            </div>
        </header>

        <!-- Filter Section -->
        <div class="filter-section no-print">
            <div class="filter-title">Filter Rekap Detail Nilai</div>
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label">Cabang Lomba</label>
                    <select name="branch" class="filter-select" onchange="this.form.submit()">
                        <option value="">Semua Cabang</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= e($branch) ?>" <?= $selectedBranch === $branch ? 'selected' : '' ?>>
                                <?= e($branch) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Golongan</label>
                    <select name="category_id" class="filter-select" onchange="this.form.submit()">
                        <option value="">Semua Golongan</option>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?= $cat->id ?>" <?= $selectedCategoryId == $cat->id ? 'selected' : '' ?>>
                                <?= e($cat->branch) ?> - <?= e($cat->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
                <a href="<?= url()->current() ?>" class="filter-btn-reset">
                    <i class="fas fa-times"></i> Reset
                </a>
            </form>
            <?php if ($selectedCategoryId || $selectedBranch): ?>
                <div class="filter-info">
                    Menampilkan <?= count($categoryBlocks) ?> golongan hasil filter
                </div>
            <?php endif; ?>
        </div>

        <!-- Print Button -->
        <button onclick="window.print()" class="print-btn no-print">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak / Save as PDF
        </button>

        <!-- Categories -->
        <?php foreach ($categoryBlocks as $block): ?>
            <?php
            $category = $block['category'];
            $priorityLabels = $block['priority_labels'] ?? [];
            $priorityKeys = $block['priority_keys'] ?? [];
            $rankingRows = $block['ranking_rows'] ?? [];
            $hakimList = $block['hakim_list'] ?? collect();
            $participantTotal = $block['participant_total'] ?? 0;

            $putraRows = array_values(array_filter($rankingRows, fn ($row) => ($row['gender'] ?? '') === 'putra'));
            $putriRows = array_values(array_filter($rankingRows, fn ($row) => ($row['gender'] ?? '') === 'putri'));

            $totalColumns = 3 + count($priorityLabels);
            ?>
            <div class="category">
                <div class="category-head">
                    <div class="category-name">
                        <?= e($category->branch) ?> - <?= e($category->name) ?>
                    </div>
                    <div class="category-meta">
                        <span class="meta-badge"><?= e($participantTotal) ?> Peserta</span>
                        <span class="meta-badge">Putra: <?= count($putraRows) ?></span>
                        <span class="meta-badge">Putri: <?= count($putriRows) ?></span>
                    </div>
                </div>

                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th class="rank-cell">#</th>
                            <th>Nama</th>
                            <th class="lot-cell">Lot</th>
                            <?php foreach ($priorityLabels as $idx => $label): ?>
                                <th class="score-cell" title="<?= e($label) ?>"><?= e($label) ?></th>
                            <?php endforeach; ?>
                            <th class="score-cell">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($putraRows)): ?>
                            <tr class="gender-subheader putra-subheader">
                                <td colspan="<?= $totalColumns ?>">Laki-Laki</td>
                            </tr>
                            <?php foreach ($putraRows as $genderIdx => $row): ?>
                                <!-- Main Row -->
                                <tr>
                                    <td class="rank-cell">
                                        <span class="rank-badge rank-<?= ($genderIdx + 1) <= 3 ? ($genderIdx + 1) : 'other' ?>">
                                            <?= $genderIdx + 1 ?>
                                        </span>
                                    </td>
                                    <td class="name-cell">
                                        <div class="name-text"><?= e($row['name']) ?></div>
                                    </td>
                                    <td class="lot-cell">
                                        <span class="lot-badge"><?= e($row['lot_number']) ?></span>
                                    </td>
                                    <?php foreach ($priorityLabels as $label): ?>
                                        <td class="score-cell">
                                            <?= e($row['priority_label_values'][$label] ?? '0.00') ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="score-cell">
                                        <span class="score-value"><?= e($row['average_score']) ?></span>
                                    </td>
                                </tr>
                                <!-- Judge Detail Row -->
                                <?php
                                $scoreEntries = $row['score_entries'] ?? [];
                                $judgeDetails = [];
                                foreach ($scoreEntries as $entry) {
                                    foreach ($entry['judge_breakdown'] ?? [] as $jIdx => $judge) {
                                        $parts = [];
                                        foreach ($priorityKeys as $pKey) {
                                            $val = $judge['breakdown'][$pKey] ?? 0;
                                            $parts[] = $val > 0 ? number_format($val, 1) : '-';
                                        }
                                        $shortName = substr($judge['judge_name'], 0, 12);
                                        $judgeDetails[] = '<span class="judge-item"><span class="judge-name">H' . ($jIdx + 1) . ':</span>' . implode('/', $parts) . ' = <span class="judge-score">' . number_format($judge['score'], 2) . '</span></span>';
                                    }
                                }
                                ?>
                                <?php if (!empty($judgeDetails)): ?>
                                <tr class="judge-row">
                                    <td class="rank-cell"></td>
                                    <td colspan="2">
                                        <div class="judge-label">Detail Nilai Hakim ▼</div>
                                    </td>
                                    <?php foreach ($priorityLabels as $pIdx => $label): ?>
                                        <td class="score-cell">
                                            <?php
                                            // Show judge's score for this point
                                            $pointScores = [];
                                            foreach ($scoreEntries as $entry) {
                                                foreach ($entry['judge_breakdown'] ?? [] as $jIdx => $judge) {
                                                    $val = $judge['breakdown'][$priorityKeys[$pIdx]] ?? 0;
                                                    if ($val > 0) {
                                                        $pointScores[] = '<span class="judge-item"><span class="judge-name">H' . ($jIdx + 1) . ':</span><span class="judge-score">' . number_format($val, 1) . '</span></span>';
                                                    }
                                                }
                                            }
                                            echo implode('', $pointScores);
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="score-cell"></td>
                                </tr>
                                <?php endif; ?>
                                <!-- Spacer -->
                                <tr class="spacer-row">
                                    <td colspan="<?= $totalColumns ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($putriRows)): ?>
                            <tr class="gender-subheader putri-subheader">
                                <td colspan="<?= $totalColumns ?>">Perempuan</td>
                            </tr>
                            <?php foreach ($putriRows as $genderIdx => $row): ?>
                                <!-- Main Row -->
                                <tr>
                                    <td class="rank-cell">
                                        <span class="rank-badge rank-<?= ($genderIdx + 1) <= 3 ? ($genderIdx + 1) : 'other' ?>">
                                            <?= $genderIdx + 1 ?>
                                        </span>
                                    </td>
                                    <td class="name-cell">
                                        <div class="name-text"><?= e($row['name']) ?></div>
                                    </td>
                                    <td class="lot-cell">
                                        <span class="lot-badge"><?= e($row['lot_number']) ?></span>
                                    </td>
                                    <?php foreach ($priorityLabels as $label): ?>
                                        <td class="score-cell">
                                            <?= e($row['priority_label_values'][$label] ?? '0.00') ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="score-cell">
                                        <span class="score-value"><?= e($row['average_score']) ?></span>
                                    </td>
                                </tr>
                                <!-- Judge Detail Row -->
                                <?php
                                $scoreEntries = $row['score_entries'] ?? [];
                                ?>
                                <?php if (!empty($scoreEntries)): ?>
                                <tr class="judge-row">
                                    <td class="rank-cell"></td>
                                    <td colspan="2">
                                        <div class="judge-label">Detail Nilai Hakim ▼</div>
                                    </td>
                                    <?php foreach ($priorityLabels as $pIdx => $label): ?>
                                        <td class="score-cell">
                                            <?php
                                            $pointScores = [];
                                            foreach ($scoreEntries as $entry) {
                                                foreach ($entry['judge_breakdown'] ?? [] as $jIdx => $judge) {
                                                    $val = $judge['breakdown'][$priorityKeys[$pIdx]] ?? 0;
                                                    if ($val > 0) {
                                                        echo '<span class="judge-item"><span class="judge-name">H' . ($jIdx + 1) . ':</span><span class="judge-score">' . number_format($val, 1) . '</span></span> ';
                                                    }
                                                }
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="score-cell"></td>
                                </tr>
                                <?php endif; ?>
                                <!-- Spacer -->
                                <tr class="spacer-row">
                                    <td colspan="<?= $totalColumns ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (empty($putraRows) && empty($putriRows)): ?>
                            <tr>
                                <td colspan="<?= $totalColumns ?>" style="text-align: center; color: var(--text-light); padding: 20px;">
                                    Belum ada data ranking untuk golongan ini
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="signature-left">
                        <div class="signature-title">Dewan Hakim <?= e($category->branch) ?></div>
                        <div class="hakim-list" id="hakim-list-<?= e($category->id) ?>">
                            <?php if (!$hakimList->isEmpty()): ?>
                                <?php foreach ($hakimList as $index => $hakim): ?>
                                    <div class="hakim-item">
                                        <span class="hakim-number"><?= $index + 1 ?></span>
                                        <span class="hakim-name" contenteditable="true" spellcheck="false"><?= e($hakim->nama) ?></span>
                                        <div class="hakim-actions">
                                            <button class="hakim-btn hakim-btn-delete" onclick="deleteHakim(this)" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="hakim-empty">(Belum ada data hakim)</div>
                            <?php endif; ?>
                        </div>
                        <div class="hakim-add-row">
                            <button class="hakim-add-btn" onclick="addHakim(<?= e($category->id) ?>)">
                                <i class="fas fa-plus"></i> Tambah Hakim
                            </button>
                        </div>
                    </div>
                    <div class="signature-right">
                        <div class="signature-box">
                            <div class="signature-date" contenteditable="true" spellcheck="false"><?= e(($documentConfig['signature_city'] ?? 'Pariangan')) ?>, <?= e(date('d F Y')) ?></div>
                            <div class="signature-position"><?= e(($documentConfig['officials']['chief_judge']['title'] ?? 'Ketua Dewan Hakim')) ?></div>
                            <div class="signature-stamp-area">📌 Area Tanda Tangan / Stempel</div>
                            <div class="signature-name" contenteditable="true" spellcheck="false"><?= e(($documentConfig['officials']['chief_judge']['name'] ?? '..........................')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- JavaScript for Hakim Actions -->
        <script>
        function addHakim(categoryId) {
            const list = document.getElementById('hakim-list-' + categoryId);
            const emptyMsg = list.querySelector('.hakim-empty');
            if (emptyMsg) {
                emptyMsg.remove();
            }

            const itemCount = list.querySelectorAll('.hakim-item').length;
            const newItem = document.createElement('div');
            newItem.className = 'hakim-item';
            newItem.innerHTML = `
                <span class="hakim-number">${itemCount + 1}</span>
                <span class="hakim-name" contenteditable="true" spellcheck="false">Nama Hakim</span>
                <div class="hakim-actions">
                    <button class="hakim-btn hakim-btn-delete" onclick="deleteHakim(this)" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            list.appendChild(newItem);
            newItem.querySelector('.hakim-name').focus();
            renumberHakim(list);
        }

        function deleteHakim(btn) {
            const item = btn.closest('.hakim-item');
            const list = item.closest('.hakim-list');
            item.remove();
            renumberHakim(list);

            // Show empty message if no items left
            if (list.querySelectorAll('.hakim-item').length === 0) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'hakim-empty';
                emptyDiv.textContent = '(Belum ada data hakim)';
                list.appendChild(emptyDiv);
            }
        }

        function renumberHakim(list) {
            const items = list.querySelectorAll('.hakim-item');
            items.forEach((item, index) => {
                item.querySelector('.hakim-number').textContent = index + 1;
            });
        }
        </script>

        <!-- Footer -->
        <footer class="footer">
            <div class="no-print" style="display: flex; justify-content: center; gap: 15px; margin-bottom: 10px; align-items: center;">
                <img src="/images/logo-kabupaten.webp" alt="Logo" style="height: 30px; opacity: 0.7;" onerror="this.style.display='none'">
                <img src="/images/favicon.webp" alt="Logo" style="height: 30px; opacity: 0.7;" onerror="this.style.display='none'">
                <img src="/images/logo-lptq.webp" alt="Logo" style="height: 30px; opacity: 0.7;" onerror="this.style.display='none'">
                <img src="/images/emtq-resmi.webp" alt="Logo" style="height: 30px; opacity: 0.7;" onerror="this.style.display='none'">
            </div>
            <p>Dokumen ini di-generate oleh <span class="footer-brand">e-MTQ System</span></p>
        </footer>
    </div>
</body>
</html>
