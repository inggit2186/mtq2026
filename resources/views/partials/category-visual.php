<?php

if (! function_exists('mtq_category_visual_key')) {
    function mtq_category_visual_key(string $value): string
    {
        $value = str_replace(["'", '`'], '', mb_strtoupper($value));
        $value = preg_replace('/\s+/', ' ', $value) ?: $value;

        return trim($value);
    }
}

if (! function_exists('mtq_category_visual_wrap')) {
    function mtq_category_visual_wrap(string $value, int $limit): array
    {
        $words = preg_split('/\s+/', trim($value)) ?: [];
        $lines = [''];

        foreach ($words as $word) {
            $current = $lines[count($lines) - 1];
            $candidate = trim($current.' '.$word);

            if ($current !== '' && mb_strlen($candidate) > $limit && count($lines) < 2) {
                $lines[] = $word;
            } else {
                $lines[count($lines) - 1] = $candidate;
            }
        }

        return array_pad($lines, 2, '');
    }
}

if (! function_exists('mtq_category_visual_variant')) {
    function mtq_category_visual_variant(string $name): array
    {
        $key = mtq_category_visual_key($name);

        if (str_contains($key, 'KANAK') || str_contains($key, 'ANAK') || str_contains($key, 'DASAR')) {
            return ['label' => 'Dasar', 'symbol' => 'M92 276l18 28 34-54 34 54 18-28', 'shape' => 'star'];
        }

        if (str_contains($key, 'REMAJA') || str_contains($key, 'MENENGAH')) {
            return ['label' => 'Remaja', 'symbol' => 'M92 300c22-36 58-36 80 0', 'shape' => 'spark'];
        }

        if (str_contains($key, 'DEWASA') || str_contains($key, 'UMUM')) {
            return ['label' => 'Umum', 'symbol' => 'M90 286h88M112 264h44M126 242h16', 'shape' => 'arch'];
        }

        if (preg_match('/\b1 JUZ\b/', $key)) {
            return ['label' => '1 Juz', 'symbol' => 'M108 258h48v64h-48zM120 274h24M120 292h24', 'shape' => 'book'];
        }

        if (preg_match('/\b5 JUZ\b/', $key)) {
            return ['label' => '5 Juz', 'symbol' => 'M98 250h58v72H98zM114 268h26M114 286h26M114 304h18', 'shape' => 'book'];
        }

        if (preg_match('/\b10 JUZ\b/', $key)) {
            return ['label' => '10 Juz', 'symbol' => 'M88 248h78v76H88zM106 266h42M106 286h42M106 306h30', 'shape' => 'book'];
        }

        if (str_contains($key, 'BAHASA INDONESIA')) {
            return ['label' => 'Indonesia', 'symbol' => 'M92 278h88M104 256h64M118 300h36', 'shape' => 'lines'];
        }

        if (str_contains($key, 'BAHASA ARAB')) {
            return ['label' => 'Arab', 'symbol' => 'M92 300c24-44 60-44 84 0M114 278c10-12 28-12 38 0', 'shape' => 'calligraphy'];
        }

        if (str_contains($key, 'BAHASA INGGRIS')) {
            return ['label' => 'Inggris', 'symbol' => 'M96 260h72M96 282h52M96 304h72', 'shape' => 'lines'];
        }

        if (str_contains($key, 'NASKAH')) {
            return ['label' => 'Naskah', 'symbol' => 'M96 250h70v72H96zM112 270h38M112 290h38M112 310h22', 'shape' => 'paper'];
        }

        if (str_contains($key, 'HIASAN')) {
            return ['label' => 'Mushaf', 'symbol' => 'M88 286c24-42 72-42 96 0-24 42-72 42-96 0zM136 262v48', 'shape' => 'ornament'];
        }

        if (str_contains($key, 'DEKORASI')) {
            return ['label' => 'Dekorasi', 'symbol' => 'M96 252h72v72H96zM110 266h44v44h-44z', 'shape' => 'frame'];
        }

        if (str_contains($key, 'KONTEMPORER')) {
            return ['label' => 'Kontemporer', 'symbol' => 'M92 314c18-56 52-80 92-58M98 266c34 20 58 36 70 58', 'shape' => 'brush'];
        }

        if (str_contains($key, 'PUTRA')) {
            return ['label' => 'Putra', 'symbol' => 'M136 252a25 25 0 110 50 25 25 0 010-50zM92 324c14-34 74-34 88 0', 'shape' => 'person'];
        }

        if (str_contains($key, 'PUTRI')) {
            return ['label' => 'Putri', 'symbol' => 'M136 252a25 25 0 110 50 25 25 0 010-50zM92 324c14-34 74-34 88 0M112 252c14-20 34-20 48 0', 'shape' => 'person'];
        }

        if (str_contains($key, 'KHATIB') || str_contains($key, 'MUADZIN')) {
            return ['label' => 'Mimbar', 'symbol' => 'M96 322h78v-62h-78zM116 260v-28h38v28M108 282h54', 'shape' => 'minbar'];
        }

        if (str_contains($key, '250 HADITS')) {
            return ['label' => '250 Hadits', 'symbol' => 'M94 318c12-48 34-74 74-92M106 300h52M112 282h40', 'shape' => 'scroll'];
        }

        if (str_contains($key, '50 HADITS')) {
            return ['label' => '50 Hadits', 'symbol' => 'M102 316c10-40 30-64 64-86M112 294h42', 'shape' => 'scroll'];
        }

        return ['label' => 'Golongan', 'symbol' => 'M96 264h76M96 286h56M96 308h76', 'shape' => 'lines'];
    }
}

if (! function_exists('mtq_category_cartoon_prop')) {
    function mtq_category_cartoon_prop(string $branchKey): string
    {
        return match ($branchKey) {
            'SENI BACA AL QURAN' => '<g transform="translate(430 92)"><rect x="40" y="96" width="112" height="74" rx="14" fill="#f8fafc" stroke="#0f172a" stroke-width="6"/><path d="M96 96v74M58 120h28M58 142h26M108 120h28M108 142h24" stroke="#0f172a" stroke-width="5" stroke-linecap="round"/><path d="M52 62c28-22 64-22 92 0M66 38c18-12 42-12 60 0" stroke="#f8fafc" stroke-width="10" stroke-linecap="round"/><circle cx="154" cy="74" r="18" fill="#facc15" stroke="#0f172a" stroke-width="5"/><path d="M154 92v42" stroke="#0f172a" stroke-width="7" stroke-linecap="round"/></g>',
            'HAFALAN AL QURAN' => '<g transform="translate(430 86)"><rect x="28" y="92" width="142" height="102" rx="22" fill="#dcfce7" stroke="#052e2b" stroke-width="7"/><path d="M99 92v102M54 122h30M54 148h26M116 122h30M116 148h26" stroke="#052e2b" stroke-width="6" stroke-linecap="round"/><circle cx="100" cy="48" r="34" fill="#bbf7d0" stroke="#052e2b" stroke-width="7"/><path d="M78 48c14-12 30-12 44 0M84 64c10 8 22 8 32 0" stroke="#052e2b" stroke-width="5" stroke-linecap="round"/></g>',
            'TARTIL AL QURAN' => '<g transform="translate(426 90)"><path d="M36 154c34-44 76-62 126-54 22 4 40-8 54-34" stroke="#ecfeff" stroke-width="16" stroke-linecap="round"/><path d="M48 196c42-32 88-44 138-34" stroke="#cffafe" stroke-width="10" stroke-linecap="round"/><rect x="54" y="104" width="118" height="72" rx="16" fill="#f8fafc" stroke="#082f49" stroke-width="6"/><path d="M112 104v72M72 128h28M126 128h28" stroke="#082f49" stroke-width="5" stroke-linecap="round"/></g>',
            'TAFSIR AL QURAN' => '<g transform="translate(428 86)"><rect x="36" y="58" width="150" height="152" rx="20" fill="#fff7ed" stroke="#312e81" stroke-width="7"/><path d="M62 92h92M62 122h70M62 152h98M62 182h56" stroke="#312e81" stroke-width="6" stroke-linecap="round"/><circle cx="184" cy="54" r="28" fill="#fde047" stroke="#312e81" stroke-width="6"/><path d="M176 54h16M184 46v16" stroke="#312e81" stroke-width="5" stroke-linecap="round"/></g>',
            'SENI KALIGRAFI AL QURAN' => '<g transform="translate(426 86)"><rect x="32" y="58" width="164" height="150" rx="24" fill="#fdf2f8" stroke="#312e81" stroke-width="7"/><path d="M66 162c26-58 62-84 104-78 24 4 30 22 14 36-22 20-70-6-88 24-10 18 4 38 42 48" stroke="#db2777" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M152 54l54 42M190 86l-34 38" stroke="#312e81" stroke-width="8" stroke-linecap="round"/><circle cx="206" cy="96" r="10" fill="#facc15"/></g>',
            'FAHMIL QURAN' => '<g transform="translate(430 92)"><rect x="30" y="70" width="152" height="84" rx="18" fill="#f0fdfa" stroke="#083344" stroke-width="7"/><path d="M70 98h72M82 126h48" stroke="#083344" stroke-width="7" stroke-linecap="round"/><circle cx="58" cy="194" r="30" fill="#5eead4" stroke="#083344" stroke-width="6"/><circle cx="154" cy="194" r="30" fill="#5eead4" stroke="#083344" stroke-width="6"/><path d="M106 154v32M58 164v-10M154 164v-10" stroke="#083344" stroke-width="7" stroke-linecap="round"/></g>',
            'SYARHIL QURAN' => '<g transform="translate(430 86)"><rect x="72" y="92" width="78" height="122" rx="18" fill="#eff6ff" stroke="#1f2937" stroke-width="7"/><circle cx="111" cy="58" r="34" fill="#bfdbfe" stroke="#1f2937" stroke-width="7"/><path d="M94 58h.1M128 58h.1M100 74c8 8 20 8 28 0" stroke="#1f2937" stroke-width="6" stroke-linecap="round"/><path d="M62 142l-44 34M160 142l44 34" stroke="#eff6ff" stroke-width="13" stroke-linecap="round"/><path d="M96 124h30M96 150h30M96 176h30" stroke="#2563eb" stroke-width="6" stroke-linecap="round"/></g>',
            'KHUTBAH JUMAT DAN ADZAN' => '<g transform="translate(430 88)"><path d="M112 52c-42 34-66 82-66 142h36c0-38 12-70 36-96 24 26 38 58 44 96h36c-8-58-34-106-78-142v166h-8z" fill="#fff1f2" stroke="#111827" stroke-width="7" stroke-linejoin="round"/><rect x="42" y="206" width="160" height="32" rx="10" fill="#fca5a5" stroke="#111827" stroke-width="7"/><circle cx="180" cy="70" r="18" fill="#facc15" stroke="#111827" stroke-width="6"/><path d="M180 88v50" stroke="#111827" stroke-width="7" stroke-linecap="round"/></g>',
            'KITAB STANDAR' => '<g transform="translate(430 84)"><rect x="42" y="58" width="146" height="178" rx="20" fill="#fffbeb" stroke="#1c1917" stroke-width="7"/><path d="M114 58v178M66 96h32M66 126h32M132 96h34M132 126h34M66 168h32" stroke="#1c1917" stroke-width="6" stroke-linecap="round"/><path d="M78 42h78" stroke="#fbbf24" stroke-width="14" stroke-linecap="round"/></g>',
            'KARYA TULIS ILMIAH AL QURAN (KTIQ)' => '<g transform="translate(428 86)"><rect x="44" y="66" width="144" height="166" rx="18" fill="#ecfdf5" stroke="#0f172a" stroke-width="7"/><path d="M70 104h92M70 134h66M70 164h90" stroke="#0f172a" stroke-width="6" stroke-linecap="round"/><path d="M154 208l58-92M192 108l28 18-58 92-30 12 10-32z" fill="#facc15" stroke="#0f172a" stroke-width="6" stroke-linejoin="round"/></g>',
            'HAFALAN HADITS NABI' => '<g transform="translate(430 88)"><path d="M52 84h128c22 0 34 18 22 36l-54 84H20l54-84c12-18 0-36-22-36z" fill="#eef2ff" stroke="#1e1b4b" stroke-width="7" stroke-linejoin="round"/><path d="M76 122h84M58 154h84M40 186h76" stroke="#1e1b4b" stroke-width="6" stroke-linecap="round"/><circle cx="190" cy="84" r="24" fill="#a5b4fc" stroke="#1e1b4b" stroke-width="6"/></g>',
            default => '<g transform="translate(430 88)"><rect x="40" y="70" width="150" height="142" rx="24" fill="#eff6ff" stroke="#0f172a" stroke-width="7"/><path d="M68 108h94M68 140h62M68 172h94" stroke="#0f172a" stroke-width="6" stroke-linecap="round"/><circle cx="182" cy="66" r="22" fill="#facc15" stroke="#0f172a" stroke-width="6"/></g>',
        };
    }
}

if (! function_exists('mtq_category_visual')) {
    function mtq_category_visual(string $branch, string $name): string
    {
        $branchKey = mtq_category_visual_key($branch);
        $palettes = [
            'SENI BACA AL QURAN' => ['#0f172a', '#0ea5e9', '#22d3ee', '#f8fafc'],
            'HAFALAN AL QURAN' => ['#052e2b', '#16a34a', '#a3e635', '#f7fee7'],
            'TARTIL AL QURAN' => ['#082f49', '#0891b2', '#67e8f9', '#ecfeff'],
            'TAFSIR AL QURAN' => ['#312e81', '#d97706', '#fde047', '#fff7ed'],
            'SENI KALIGRAFI AL QURAN' => ['#312e81', '#db2777', '#f9a8d4', '#fdf2f8'],
            'FAHMIL QURAN' => ['#083344', '#0f766e', '#5eead4', '#f0fdfa'],
            'SYARHIL QURAN' => ['#1f2937', '#2563eb', '#93c5fd', '#eff6ff'],
            'KHUTBAH JUMAT DAN ADZAN' => ['#111827', '#dc2626', '#fca5a5', '#fff1f2'],
            'KITAB STANDAR' => ['#1c1917', '#ea580c', '#fbbf24', '#fffbeb'],
            'KARYA TULIS ILMIAH AL QURAN (KTIQ)' => ['#0f172a', '#059669', '#6ee7b7', '#ecfdf5'],
            'HAFALAN HADITS NABI' => ['#1e1b4b', '#4f46e5', '#a5b4fc', '#eef2ff'],
        ];

        $illustrations = [
            'SENI BACA AL QURAN' => ['label' => 'Tilawah', 'path' => 'M410 178c24-48 58-72 104-72 34 0 64 14 90 42M420 220c34-34 72-52 114-52 28 0 54 8 78 24M448 262c30-20 62-30 96-30 24 0 46 4 66 14', 'extra' => 'M502 82v190M472 112h60M462 144h80'],
            'HAFALAN AL QURAN' => ['label' => 'Hafalan', 'path' => 'M420 116h72c22 0 38 16 38 38v128h-92c-20 0-36-16-36-36V134c0-10 8-18 18-18zM530 116h72c10 0 18 8 18 18v112c0 20-16 36-36 36h-54V116z', 'extra' => 'M438 154h58M438 184h58M438 214h44M552 154h50M552 184h50M552 214h36'],
            'TARTIL AL QURAN' => ['label' => 'Tartil', 'path' => 'M410 246c40-46 84-72 132-78 34-4 60-24 80-60M424 286c44-34 90-52 138-54', 'extra' => 'M454 116c30 28 58 28 84 0M478 92c10 12 22 12 34 0'],
            'TAFSIR AL QURAN' => ['label' => 'Tafsir', 'path' => 'M414 104h180v202H414zM448 142h112M448 176h90M448 210h122M448 244h74', 'extra' => 'M572 94l48 48M620 94l-48 48'],
            'SENI KALIGRAFI AL QURAN' => ['label' => 'Kaligrafi', 'path' => 'M440 242c38-90 84-132 138-126 32 4 46 24 34 48-16 32-72 28-104 8-28-18-48-12-60 18-16 38 4 76 50 96', 'extra' => 'M410 284c40-6 76-4 108 8M558 96c18-22 36-32 56-30'],
            'FAHMIL QURAN' => ['label' => 'Fahmil', 'path' => 'M410 112h190v72H410zM438 214h134v76H438zM460 148h90M476 252h58', 'extra' => 'M458 184v30M552 184v30M505 290v34'],
            'SYARHIL QURAN' => ['label' => 'Syarhil', 'path' => 'M506 104c52 20 88 68 88 130v48c-34 4-64 20-88 46-24-26-54-42-88-46v-48c0-62 36-110 88-130z', 'extra' => 'M506 158v118M462 214h88M458 246h96'],
            'KHUTBAH JUMAT DAN ADZAN' => ['label' => 'Khutbah', 'path' => 'M500 92c-48 38-76 92-76 160h46c0-48 14-88 44-120 30 32 48 72 54 120h46c-8-66-38-120-92-160v202h-22z', 'extra' => 'M420 294h202M444 252h146'],
            'KITAB STANDAR' => ['label' => 'Kitab', 'path' => 'M414 112c0-18 14-32 32-32h136c18 0 32 14 32 32v176c0 18-14 32-32 32H446c-18 0-32-14-32-32zM514 80v240', 'extra' => 'M438 136h50M438 170h50M540 136h50M540 170h50M438 226h50'],
            'KARYA TULIS ILMIAH AL QURAN (KTIQ)' => ['label' => 'KTIQ', 'path' => 'M418 302l46-176 44 112 38-82 58 146M456 302h168', 'extra' => 'M432 98h128M432 124h92M432 150h60'],
            'HAFALAN HADITS NABI' => ['label' => 'Hadits', 'path' => 'M416 292c26-82 58-142 98-180 40 38 72 98 98 180M456 260h116M476 224h76', 'extra' => 'M514 92v208M482 116c20 18 44 18 64 0'],
        ];

        $palette = $palettes[$branchKey] ?? ['#0f172a', '#2563eb', '#93c5fd', '#eff6ff'];
        $illustration = $illustrations[$branchKey] ?? ['label' => 'MTQ', 'path' => 'M430 118h166M430 160h132M430 202h166M430 244h112', 'extra' => 'M404 286h214'];
        $variant = mtq_category_visual_variant($name);
        $branchLines = mtq_category_visual_wrap($branch, 28);
        $nameLines = mtq_category_visual_wrap($name, 32);
        $branchLineTwo = $branchLines[1] !== ''
            ? '<text x="56" y="266" font-family="Segoe UI, Arial, sans-serif" font-size="26" font-weight="800" fill="white">'.htmlspecialchars($branchLines[1], ENT_QUOTES, 'UTF-8').'</text>'
            : '';
        $nameLineTwo = $nameLines[1] !== ''
            ? '<text x="56" y="334" font-family="Segoe UI, Arial, sans-serif" font-size="20" font-weight="600" fill="rgba(255,255,255,0.82)">'.htmlspecialchars($nameLines[1], ENT_QUOTES, 'UTF-8').'</text>'
            : '';
        $branchLabel = htmlspecialchars($branchLines[0], ENT_QUOTES, 'UTF-8');
        $nameLabel = htmlspecialchars($nameLines[0], ENT_QUOTES, 'UTF-8');
        $accentLabel = htmlspecialchars($illustration['label'], ENT_QUOTES, 'UTF-8');
        $variantLabel = htmlspecialchars($variant['label'], ENT_QUOTES, 'UTF-8');
        $cartoonProp = mtq_category_cartoon_prop($branchKey);
        $variantPath = $variant['symbol'];
        $initial = htmlspecialchars(mb_strtoupper(mb_substr($branch, 0, 1)).mb_strtoupper(mb_substr($name, 0, 1)), ENT_QUOTES, 'UTF-8');
        $isPutri = str_contains(mtq_category_visual_key($name), 'PUTRI');
        $headwear = $isPutri
            ? '<path d="M72 52c28-48 92-48 120 0v70H72z" fill="#fdf2f8" stroke="#0f172a" stroke-width="7"/><path d="M88 52c16-26 72-26 88 0" stroke="#f9a8d4" stroke-width="9" stroke-linecap="round"/>'
            : '<path d="M66 56c18-42 96-42 132 0 0 0-28 18-66 18s-66-18-66-18z" fill="#f8fafc" stroke="#0f172a" stroke-width="7"/><path d="M96 36h70" stroke="#facc15" stroke-width="9" stroke-linecap="round"/>';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 420" fill="none">
  <defs>
    <linearGradient id="bg" x1="42" y1="26" x2="680" y2="394" gradientUnits="userSpaceOnUse">
      <stop stop-color="{$palette[0]}"/>
      <stop offset="0.58" stop-color="{$palette[1]}"/>
      <stop offset="1" stop-color="{$palette[2]}"/>
    </linearGradient>
    <linearGradient id="floor" x1="164" y1="254" x2="634" y2="376" gradientUnits="userSpaceOnUse">
      <stop stop-color="{$palette[3]}" stop-opacity="0.78"/>
      <stop offset="1" stop-color="white" stop-opacity="0.24"/>
    </linearGradient>
    <linearGradient id="floorSide" x1="190" y1="318" x2="626" y2="390" gradientUnits="userSpaceOnUse">
      <stop stop-color="{$palette[1]}"/>
      <stop offset="1" stop-color="{$palette[0]}"/>
    </linearGradient>
    <linearGradient id="skin" x1="318" y1="104" x2="390" y2="176" gradientUnits="userSpaceOnUse">
      <stop stop-color="#ffedd5"/>
      <stop offset="1" stop-color="#fb923c"/>
    </linearGradient>
    <linearGradient id="cloth" x1="300" y1="236" x2="430" y2="332" gradientUnits="userSpaceOnUse">
      <stop stop-color="{$palette[3]}"/>
      <stop offset="0.5" stop-color="{$palette[2]}"/>
      <stop offset="1" stop-color="{$palette[1]}"/>
    </linearGradient>
    <linearGradient id="cap" x1="300" y1="46" x2="432" y2="132" gradientUnits="userSpaceOnUse">
      <stop stop-color="white"/>
      <stop offset="1" stop-color="{$palette[3]}"/>
    </linearGradient>
    <filter id="softShadow" x="-20%" y="-20%" width="140%" height="150%" color-interpolation-filters="sRGB">
      <feDropShadow dx="0" dy="18" stdDeviation="14" flood-color="#020617" flood-opacity="0.28"/>
    </filter>
    <filter id="cardShadow" x="-20%" y="-20%" width="140%" height="150%" color-interpolation-filters="sRGB">
      <feDropShadow dx="0" dy="16" stdDeviation="18" flood-color="#020617" flood-opacity="0.38"/>
    </filter>
    <radialGradient id="shine" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(570 84) rotate(130) scale(236 226)">
      <stop stop-color="{$palette[3]}" stop-opacity="0.44"/>
      <stop offset="1" stop-color="{$palette[3]}" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="panel" x1="374" y1="64" x2="660" y2="338" gradientUnits="userSpaceOnUse">
      <stop stop-color="white" stop-opacity="0.24"/>
      <stop offset="1" stop-color="white" stop-opacity="0.06"/>
    </linearGradient>
    <pattern id="dots" width="28" height="28" patternUnits="userSpaceOnUse">
      <circle cx="2" cy="2" r="2" fill="white" opacity="0.13"/>
    </pattern>
  </defs>
  <rect width="720" height="420" rx="42" fill="url(#bg)"/>
  <rect width="720" height="420" rx="42" fill="url(#dots)"/>
  <rect x="24" y="24" width="672" height="372" rx="32" stroke="white" stroke-opacity="0.18"/>
  <circle cx="586" cy="78" r="146" fill="url(#shine)"/>
  <circle cx="92" cy="82" r="14" fill="white" fill-opacity="0.28"/>
  <circle cx="642" cy="212" r="10" fill="white" fill-opacity="0.24"/>
  <path d="M54 350c92-80 210-112 334-92 80 14 154 54 250 66" stroke="white" stroke-opacity="0.16" stroke-width="18" stroke-linecap="round"/>
  <path d="M148 316l132-70 342 52-138 82z" fill="url(#floor)" stroke="white" stroke-opacity="0.18" stroke-width="4" filter="url(#softShadow)"/>
  <path d="M148 316l336 64 138-82v30l-138 82-336-64z" fill="url(#floorSide)" fill-opacity="0.86"/>
  <path d="M214 316l80-42M302 334l88-48M390 350l94-52M478 366l96-56" stroke="white" stroke-opacity="0.14" stroke-width="4" stroke-linecap="round"/>
  <rect x="384" y="54" width="286" height="286" rx="34" fill="url(#panel)" stroke="white" stroke-opacity="0.18" filter="url(#cardShadow)"/>
  <rect x="402" y="72" width="250" height="246" rx="28" fill="white" fill-opacity="0.05"/>
  <g filter="url(#softShadow)">{$cartoonProp}</g>
  <g transform="translate(220 58)" filter="url(#softShadow)">
    <path d="M112 188c-42 14-66 44-70 92 46 18 142 18 188 0-4-48-28-78-70-92z" fill="url(#cloth)" stroke="#0f172a" stroke-width="7"/>
    <path d="M76 236l-46 30M196 236l50 30" stroke="url(#cloth)" stroke-width="18" stroke-linecap="round"/>
    <path d="M44 266c18 18 52 20 68 2M222 268c18 18 50 14 66-6" stroke="#0f172a" stroke-width="6" stroke-linecap="round"/>
    <circle cx="136" cy="104" r="60" fill="url(#skin)" stroke="#0f172a" stroke-width="7"/>
    <ellipse cx="136" cy="152" rx="38" ry="9" fill="#c2410c" fill-opacity="0.22"/>
    {$headwear}
    <circle cx="110" cy="100" r="6" fill="#0f172a"/>
    <circle cx="154" cy="100" r="6" fill="#0f172a"/>
    <path d="M116 126c10 12 28 12 40 0" stroke="#0f172a" stroke-width="6" stroke-linecap="round"/>
    <circle cx="92" cy="118" r="7" fill="#fb7185" fill-opacity="0.68"/>
    <circle cx="172" cy="118" r="7" fill="#fb7185" fill-opacity="0.68"/>
    <path d="M92 190h88l-12 96H104z" fill="url(#cloth)" stroke="#0f172a" stroke-width="7" stroke-linejoin="round"/>
    <path d="M116 206h40M112 232h48" stroke="white" stroke-opacity="0.78" stroke-width="6" stroke-linecap="round"/>
    <path d="M166 74c18 8 28 22 30 42" stroke="white" stroke-opacity="0.42" stroke-width="9" stroke-linecap="round"/>
  </g>
  <circle cx="136" cy="118" r="58" fill="white" fill-opacity="0.14"/>
  <circle cx="136" cy="118" r="76" stroke="white" stroke-opacity="0.2" stroke-dasharray="4 10"/>
  <text x="136" y="136" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="46" font-weight="800" fill="white">{$initial}</text>
  <rect x="56" y="52" width="170" height="34" rx="17" fill="white" fill-opacity="0.14"/>
  <text x="78" y="75" font-family="Segoe UI, Arial, sans-serif" font-size="15" font-weight="800" fill="white" fill-opacity="0.82">{$accentLabel}</text>
  <text x="56" y="208" font-family="Segoe UI, Arial, sans-serif" font-size="17" font-weight="800" letter-spacing="3" fill="white" fill-opacity="0.68">CABANG MTQ</text>
  <text x="56" y="238" font-family="Segoe UI, Arial, sans-serif" font-size="29" font-weight="800" fill="white">{$branchLabel}</text>
  {$branchLineTwo}
  <text x="56" y="310" font-family="Segoe UI, Arial, sans-serif" font-size="21" font-weight="700" fill="white" fill-opacity="0.88">{$nameLabel}</text>
  {$nameLineTwo}
  <g transform="translate(462 18)">
    <rect x="58" y="236" width="160" height="92" rx="24" fill="#fff7ed" stroke="#0f172a" stroke-width="6"/>
    <path d="{$variantPath}" stroke="#0f172a" stroke-opacity="0.82" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="138" y="318" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="16" font-weight="800" fill="#0f172a">{$variantLabel}</text>
  </g>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
