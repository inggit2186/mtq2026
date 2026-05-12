<?php
require_once __DIR__.'/../partials/icon.php';
require_once __DIR__.'/../partials/category-visual.php';
if (! function_exists('mtq_category_visual')) {
    function mtq_category_visual(string $branch, string $name): string
    {
        $palettes = [
            'SENI BACA AL QUR`AN' => ['#0f172a', '#0ea5e9', '#38bdf8'],
            'HAFALAN AL QUR`AN' => ['#111827', '#22c55e', '#86efac'],
            'TARTIL AL QUR`AN' => ['#0f172a', '#14b8a6', '#67e8f9'],
            'TAFSIR AL QUR`AN' => ['#1e1b4b', '#f59e0b', '#fde68a'],
            'SENI KALIGRAFI AL QUR`AN' => ['#172554', '#8b5cf6', '#c4b5fd'],
            'FAHMIL QURAN' => ['#082f49', '#06b6d4', '#67e8f9'],
            'SYARHIL QUR`AN' => ['#1e293b', '#3b82f6', '#93c5fd'],
            'KHUTBAH JUM`AT DAN ADZAN' => ['#111827', '#ef4444', '#fca5a5'],
            'KITAB STANDAR' => ['#0f172a', '#f97316', '#fdba74'],
            'KARYA TULIS ILMIAH AL QUR`AN (KTIQ )' => ['#111827', '#10b981', '#6ee7b7'],
            'HAFALAN HADITS NABI' => ['#172554', '#6366f1', '#a5b4fc'],
        ];

        $motifs = [
            'SENI BACA AL QUR`AN' => ['pattern' => 'M410 78C452 110 484 140 528 174C492 194 454 218 424 248', 'accent' => 'Tilawah'],
            'HAFALAN AL QUR`AN' => ['pattern' => 'M404 92L474 92L474 230L404 230ZM418 108H460M418 136H460M418 164H460M418 192H448', 'accent' => 'Hafalan'],
            'TARTIL AL QUR`AN' => ['pattern' => 'M396 218C426 202 458 192 492 188C524 184 548 166 566 136M400 254C434 236 470 226 508 220', 'accent' => 'Tartil'],
            'TAFSIR AL QUR`AN' => ['pattern' => 'M396 100H562M396 132H540M396 164H554M396 196H522M396 228H548', 'accent' => 'Tafsir'],
            'SENI KALIGRAFI AL QUR`AN' => ['pattern' => 'M438 86C470 108 496 132 516 160C526 174 534 190 540 210C518 198 500 190 482 186C458 180 436 176 414 162C402 154 392 144 384 132C392 114 410 96 438 86Z', 'accent' => 'Kaligrafi'],
            'FAHMIL QURAN' => ['pattern' => 'M416 104H542V146H416ZM432 162H526V214H432ZM446 230H512V264H446Z', 'accent' => 'Fahmil'],
            'SYARHIL QUR`AN' => ['pattern' => 'M414 230C414 176 448 134 488 118C528 134 562 176 562 230V248C534 252 510 266 488 286C466 266 442 252 414 248Z', 'accent' => 'Syarhil'],
            'KHUTBAH JUM`AT DAN ADZAN' => ['pattern' => 'M466 90C430 122 406 164 406 216H438C438 172 452 140 476 112C494 132 510 158 520 190H552C540 148 520 114 490 88L490 246H466Z', 'accent' => 'Khutbah'],
            'KITAB STANDAR' => ['pattern' => 'M404 110C404 98 414 88 426 88H538C550 88 560 98 560 110V238C560 250 550 260 538 260H426C414 260 404 250 404 238ZM482 88V260', 'accent' => 'Kitab'],
            'KARYA TULIS ILMIAH AL QUR`AN (KTIQ )' => ['pattern' => 'M408 248L446 96L486 210L524 126L554 248', 'accent' => 'KTIQ'],
            'HAFALAN HADITS NABI' => ['pattern' => 'M408 230C428 174 448 132 488 94C528 132 548 174 568 230M434 212H542', 'accent' => 'Hadits'],
        ];

        $palette = $palettes[strtoupper($branch)] ?? ['#0f172a', '#2563eb', '#93c5fd'];
        $motif = $motifs[strtoupper($branch)] ?? ['pattern' => 'M410 100H560M410 140H536M410 180H560M410 220H520', 'accent' => 'Musabaqah'];
        $abbr = mb_strtoupper(mb_substr($branch, 0, 1)).mb_strtoupper(mb_substr($name, 0, 1));
        $branchLabel = htmlspecialchars($branch, ENT_QUOTES, 'UTF-8');
        $nameLabel = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $accentLabel = htmlspecialchars($motif['accent'], ENT_QUOTES, 'UTF-8');
        $motifPath = $motif['pattern'];
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360" fill="none">
  <defs>
    <linearGradient id="bg" x1="40" y1="20" x2="620" y2="340" gradientUnits="userSpaceOnUse">
      <stop stop-color="{$palette[0]}"/>
      <stop offset="0.55" stop-color="{$palette[1]}"/>
      <stop offset="1" stop-color="{$palette[2]}"/>
    </linearGradient>
    <radialGradient id="glow" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(510 78) rotate(136.4) scale(206.167 203.839)">
      <stop stop-color="white" stop-opacity="0.35"/>
      <stop offset="1" stop-color="white" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="panel" x1="372" y1="64" x2="586" y2="270" gradientUnits="userSpaceOnUse">
      <stop stop-color="rgba(255,255,255,0.18)"/>
      <stop offset="1" stop-color="rgba(255,255,255,0.04)"/>
    </linearGradient>
  </defs>
  <rect width="640" height="360" rx="40" fill="url(#bg)"/>
  <rect x="24" y="24" width="592" height="312" rx="30" stroke="rgba(255,255,255,0.18)"/>
  <circle cx="525" cy="92" r="126" fill="url(#glow)"/>
  <rect x="372" y="62" width="212" height="226" rx="28" fill="url(#panel)" stroke="rgba(255,255,255,0.16)"/>
  <path d="{$motifPath}" stroke="rgba(255,255,255,0.74)" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M66 288C151 213 259 183 370 199C433 209 490 241 555 286" stroke="rgba(255,255,255,0.18)" stroke-width="16" stroke-linecap="round"/>
  <path d="M90 240C178 166 274 146 380 161C447 171 505 206 561 249" stroke="rgba(255,255,255,0.28)" stroke-width="8" stroke-linecap="round"/>
  <circle cx="118" cy="112" r="54" fill="rgba(255,255,255,0.12)"/>
  <circle cx="118" cy="112" r="68" stroke="rgba(255,255,255,0.18)" stroke-dasharray="4 8"/>
  <text x="118" y="129" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="44" font-weight="700" fill="white">{$abbr}</text>
  <text x="62" y="212" font-family="Segoe UI, Arial, sans-serif" font-size="20" font-weight="600" fill="rgba(255,255,255,0.72)">CABANG MTQ</text>
  <text x="62" y="245" font-family="Segoe UI, Arial, sans-serif" font-size="34" font-weight="700" fill="white">{$branchLabel}</text>
  <text x="62" y="290" font-family="Segoe UI, Arial, sans-serif" font-size="24" font-weight="500" fill="rgba(255,255,255,0.88)">{$nameLabel}</text>
  <text x="396" y="270" font-family="Segoe UI, Arial, sans-serif" font-size="18" font-weight="700" fill="rgba(255,255,255,0.82)">{$accentLabel}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$participant = $participant ?? null;
$documentMap = $documentMap ?? [];
$districtLocked = $districtLocked ?? false;
$existingPhotoPath = (string) ($documentMap['photo']['path'] ?? '');
$existingPhotoPreviewUrl = filled($existingPhotoPath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($existingPhotoPath)
    ? \Illuminate\Support\Facades\Storage::disk('public')->url($existingPhotoPath)
    : '';
if (! function_exists('mtq_gender_quota_rule')) {
    function mtq_gender_quota_rule(object $category): ?string
    {
        $branch = mb_strtolower((string) ($category->branch ?? ''));
        $name = mb_strtolower((string) ($category->name ?? ''));
        $quota = (int) ($category->quota ?? 0);

        if (str_contains($branch, 'khutbah') && str_contains($branch, 'adzan')) {
            return 'putra_two';
        }

        if ((str_contains($branch, 'fahmil') || str_contains($branch, 'syarhil')) && str_contains($name, 'putra') && $quota === 3) {
            return 'putra_three';
        }

        if ((str_contains($branch, 'fahmil') || str_contains($branch, 'syarhil')) && str_contains($name, 'putri') && $quota === 3) {
            return 'putri_three';
        }

        if (str_contains($name, 'putra') || str_contains($name, 'putri')) {
            return null;
        }

        if ($quota === 2) {
            return 'paired_two';
        }

        return null;
    }
}
$categoryCards = collect($categories ?? [])->map(function ($category): array {
    return [
        'id' => (string) $category->id,
        'branch' => (string) $category->branch,
        'name' => (string) $category->name,
        'quota' => (int) $category->quota,
        'age_requirement' => (string) $category->age_requirement,
        'notes' => (string) ($category->notes ?? ''),
        'description' => (string) ($category->description ?? ''),
        'image' => mtq_category_visual((string) $category->branch, (string) $category->name),
        'gender_rule' => (string) (mtq_gender_quota_rule($category) ?? ''),
    ];
})->values();
$categoryBranches = $categoryCards
    ->filter(fn (array $category): bool => filled($category['branch']))
    ->groupBy('branch')
    ->map(fn ($items, string $branch): array => [
        'branch' => $branch,
        'image' => mtq_category_visual($branch, 'Pilihan Kategori'),
        'category_total' => collect($items)->count(),
    ])
    ->values();
$standaloneCategories = $categoryCards
    ->filter(fn (array $category): bool => ! filled($category['branch']))
    ->values();
$selectedCategoryId = (string) old('competition_category_id', $participant?->competition_category_id);
$selectedCategory = $categoryCards->firstWhere('id', $selectedCategoryId);
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) auth()->user()?->role, 'participants.list');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Edit Peserta') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('id-card') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Perbaikan Peserta</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php require __DIR__.'/../partials/console-navigation.php'; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">No. Registrasi</p>
                        <p class="mt-2 text-lg font-bold text-white"><?= e($participant?->registration_number) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Status Saat Ini</p>
                        <p class="mt-2 text-lg font-bold text-white"><?= e(ucfirst((string) $participant?->verification_status)) ?></p>
                    </div>
                    <form method="POST" action="<?= e(route('logout')) ?>">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="secondary-button w-full">
                            <?= mtq_icon('logout', 'h-4 w-4') ?>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <div class="min-w-0 space-y-6">
                <header class="topbar-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = true">
                            <?= mtq_icon('menu', 'h-4 w-4') ?>
                        </button>
                        <div>
                            <p class="section-kicker">Perbaikan Data</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white"><?= e($participant?->name) ?></h2>
                            <p class="mt-2 text-sm text-slate-300">Perbarui identitas atau unggah ulang dokumen yang perlu diperbaiki.</p>
                        </div>
                    </div>
                    <a href="<?= e(route('participants.show', $participant)) ?>" class="secondary-button">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        Kembali ke detail
                    </a>
                </header>

                <section class="glass-card rounded-[2rem] p-6" x-data="participantEditFlow(<?= e(json_encode([
                    'selectedCategoryId' => $selectedCategoryId,
                    'selectedBranch' => $selectedCategory['branch'] ?? '',
                    'categories' => $categoryCards,
                    'branches' => $categoryBranches,
                    'existingPhotoPreviewUrl' => $existingPhotoPreviewUrl,
                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('upload') ?></div>
                        <div>
                            <p class="section-kicker">Wizard Edit</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Cabang, golongan, lalu tinjau revisi</h3>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 md:grid-cols-3">
                        <div class="rounded-2xl border px-4 py-4 transition" x-bind:class="currentStep >= 1 ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-cyan-400/18 bg-cyan-400/8 text-slate-200'">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em]">Langkah 1</p>
                            <p class="mt-2 text-base font-bold">Pilih cabang</p>
                            <p class="mt-1 text-sm opacity-80">Tentukan cabang dan golongan revisi.</p>
                        </div>
                        <div class="rounded-2xl border px-4 py-4 transition" x-bind:class="currentStep >= 2 ? 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-900/70 text-slate-400'">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em]">Langkah 2</p>
                            <p class="mt-2 text-base font-bold">Perbarui data</p>
                            <p class="mt-1 text-sm opacity-80">Edit identitas dan dokumen.</p>
                        </div>
                        <div class="rounded-2xl border px-4 py-4 transition" x-bind:class="currentStep >= 3 ? 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-900/70 text-slate-400'">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em]">Langkah 3</p>
                            <p class="mt-2 text-base font-bold">Review revisi</p>
                            <p class="mt-1 text-sm opacity-80">Cek kembali sebelum menyimpan atau mengirim ulang.</p>
                        </div>
                    </div>

                    <div class="mt-6" x-show="currentStep === 1">
                        <div class="max-w-xl">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Cari cabang atau golongan</label>
                            <input type="text" x-model.trim="searchTerm" placeholder="Contoh: tilawah, tafsir, hadits" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div x-show="!selectedBranch">
                            <div class="mt-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cabang Utama</p>
                                <div class="mt-4 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                                    <?php foreach ($categoryBranches as $branchCard): ?>
                                        <button type="button" class="group overflow-hidden rounded-[1.75rem] border border-slate-700/80 bg-slate-900/80 text-left transition duration-200 hover:-translate-y-1 hover:border-cyan-300/40 hover:shadow-[0_18px_55px_-28px_rgba(34,211,238,0.55)]" x-on:click="selectBranch('<?= e($branchCard['branch']) ?>')" x-show="matchesBranch('<?= e(mb_strtolower($branchCard['branch'])) ?>')">
                                            <div class="aspect-[16/9] overflow-hidden bg-slate-950/70">
                                                <img src="<?= e($branchCard['image']) ?>" alt="<?= e($branchCard['branch']) ?>" loading="lazy" decoding="async" class="h-full w-full object-contain p-2">
                                            </div>
                                            <div class="space-y-3 p-5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Cabang MTQ</p>
                                                <h4 class="text-lg font-bold text-white"><?= e($branchCard['branch']) ?></h4>
                                                <p class="text-sm text-slate-300"><?= e($branchCard['category_total']) ?> golongan tersedia</p>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($standaloneCategories->isNotEmpty()): ?>
                                <div class="mt-8">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Golongan Mandiri</p>
                                    <div class="mt-4 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                                        <?php foreach ($standaloneCategories as $categoryCard): ?>
                                            <button type="button" class="group overflow-hidden rounded-[1.75rem] border border-slate-700/80 bg-slate-900/80 text-left transition duration-200 hover:-translate-y-1 hover:border-cyan-300/40 hover:shadow-[0_18px_55px_-28px_rgba(34,211,238,0.55)]" x-on:click="selectCategory('<?= e($categoryCard['id']) ?>')" x-show="matchesCategory('<?= e(mb_strtolower($categoryCard['name'].' '.$categoryCard['notes'].' '.$categoryCard['description'])) ?>')">
                                                <div class="aspect-[16/9] overflow-hidden bg-slate-950/70">
                                                    <img src="<?= e($categoryCard['image']) ?>" alt="<?= e($categoryCard['name']) ?>" loading="lazy" decoding="async" class="h-full w-full object-contain p-2">
                                                </div>
                                                <div class="space-y-3 p-5">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Golongan Mandiri</p>
                                                    <h4 class="text-lg font-bold text-white"><?= e($categoryCard['name']) ?></h4>
                                                </div>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div x-show="selectedBranch">
                            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Golongan dalam Cabang</p>
                                    <p class="mt-2 text-lg font-bold text-white" x-text="selectedBranch"></p>
                                </div>
                                <button type="button" class="secondary-button" x-on:click="resetBranch()">
                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                    Kembali ke Cabang
                                </button>
                            </div>
                            <div class="mt-4 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                                <?php foreach ($categoryCards as $categoryCard): ?>
                                    <template x-if="selectedBranch === '<?= e($categoryCard['branch']) ?>' && matchesCategory('<?= e(mb_strtolower($categoryCard['branch'].' '.$categoryCard['name'].' '.$categoryCard['notes'].' '.$categoryCard['description'])) ?>')">
                                        <button type="button" class="group overflow-hidden rounded-[1.75rem] border border-slate-700/80 bg-slate-900/80 text-left transition duration-200 hover:-translate-y-1 hover:border-cyan-300/40 hover:shadow-[0_18px_55px_-28px_rgba(34,211,238,0.55)]" x-on:click="selectCategory('<?= e($categoryCard['id']) ?>')" x-bind:class="selectedCategoryId === '<?= e($categoryCard['id']) ?>' ? 'border-cyan-300/70 ring-2 ring-cyan-300/30' : ''">
                                            <div class="aspect-[16/9] overflow-hidden bg-slate-950/70">
                                                <img src="<?= e($categoryCard['image']) ?>" alt="<?= e($categoryCard['name']) ?>" loading="lazy" decoding="async" class="h-full w-full object-contain p-2">
                                            </div>
                                            <div class="space-y-3 p-5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200"><?= e($categoryCard['branch']) ?></p>
                                                <h4 class="text-lg font-bold text-white"><?= e($categoryCard['name']) ?></h4>
                                                <p class="text-sm text-slate-300"><?= e($categoryCard['age_requirement']) ?></p>
                                                <?php if ($categoryCard['gender_rule'] === 'putra_three'): ?>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">Khusus 3 peserta putra</p>
                                                <?php elseif ($categoryCard['gender_rule'] === 'putri_three'): ?>
                                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-fuchsia-200">Khusus 3 peserta putri</p>
                                                <?php endif; ?>
                                            </div>
                                        </button>
                                    </template>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="<?= e(route('participants.update', $participant)) ?>" enctype="multipart/form-data" class="mt-6" x-ref="editForm" data-loading-text="Menyimpan perubahan peserta dan mengunggah berkas baru...">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="competition_category_id" x-model="selectedCategoryId">

                        <div id="edit-form-panel" class="rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/70 to-blue-950/60 p-5" x-show="selectedCategory && currentStep >= 2">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-200">Langkah 2</p>
                                    <h4 class="mt-2 text-xl font-bold text-white">Perbarui data peserta</h4>
                                </div>
                                <template x-if="selectedCategory">
                                    <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3">
                                    <div class="h-16 w-24 overflow-hidden rounded-xl bg-slate-950/70">
                                        <img x-bind:src="selectedCategory.image" x-bind:alt="selectedCategory.name" loading="lazy" decoding="async" class="h-full w-full object-contain p-1">
                                    </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.18em] text-cyan-200" x-text="selectedCategory.branch"></p>
                                            <p class="mt-1 font-semibold text-white" x-text="selectedCategory.name"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <fieldset class="mt-6 grid gap-5 lg:grid-cols-2" x-show="currentStep === 2" x-bind:disabled="!selectedCategoryId">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kecamatan</label>
                            <?php if ($districtLocked): ?>
                                <div class="rounded-2xl border border-cyan-400/20 bg-cyan-400/10 px-4 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Kecamatan Official Login</p>
                                    <p class="mt-2 text-lg font-bold text-white"><?= e($user?->district?->name ?? $districts->firstWhere('id', $participant?->district_id)?->name ?? '-') ?></p>
                                    <p class="mt-2 text-sm text-slate-300">Terkunci otomatis sesuai akun official yang sedang login.</p>
                                </div>
                                <input type="hidden" name="district_id" value="<?= e($participant?->district_id) ?>">
                            <?php else: ?>
                                <select name="district_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?= e($district->id) ?>" <?= (string) old('district_id', $participant?->district_id) === (string) $district->id ? 'selected' : '' ?>><?= e($district->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Cabang & Golongan MTQ</p>
                            <p class="mt-2 text-lg font-bold text-white" x-text="selectedCategory ? selectedCategory.branch + ' - ' + selectedCategory.name : 'Belum dipilih'"></p>
                            <p class="mt-2 text-sm text-slate-300" x-text="selectedCategory ? (selectedCategory.notes || selectedCategory.description || selectedCategory.age_requirement) : ''"></p>
                            <template x-if="selectedCategory && selectedCategory.gender_rule === 'putra_two'">
                                <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">Kategori ini hanya menerima 2 peserta putra.</p>
                            </template>
                            <template x-if="selectedCategory && selectedCategory.gender_rule === 'putra_three'">
                                <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">Kategori ini hanya menerima 3 peserta putra.</p>
                            </template>
                            <template x-if="selectedCategory && selectedCategory.gender_rule === 'putri_three'">
                                <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-fuchsia-200">Kategori ini hanya menerima 3 peserta putri.</p>
                            </template>
                        </div>

                        <div class="lg:col-span-2 rounded-[1.4rem] border border-slate-800 bg-slate-950/55 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Input</p>
                            <p class="mt-2 text-lg font-bold text-white">Data identitas peserta</p>
                            <p class="mt-1 text-sm text-slate-300">Bagian ini untuk memperbarui identitas inti peserta sebelum masuk ke upload ulang dokumen.</p>
                        </div>

                        <div class="lg:col-span-2">
                            <input type="hidden" name="participant_role" value="<?= e(old('participant_role', $participant?->participant_role ?? 'main')) ?>" x-ref="participantRoleField">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Jenis Peserta</label>
                            <div class="overflow-hidden rounded-[1.25rem] border border-white/10 bg-slate-900/70">
                                <div class="grid grid-cols-2">
                                    <button
                                        type="button"
                                        class="px-4 py-3 text-left text-sm font-semibold transition"
                                        x-on:click="$refs.participantRoleField.value = 'main'"
                                        x-bind:class="$refs.participantRoleField.value === 'main' ? 'bg-cyan-400/12 text-cyan-200' : 'bg-transparent text-slate-300 hover:bg-white/5'"
                                    >
                                        Peserta Inti
                                    </button>
                                    <button
                                        type="button"
                                        class="px-4 py-3 text-left text-sm font-semibold transition"
                                        x-on:click="$refs.participantRoleField.value = 'reserve'"
                                        x-bind:class="$refs.participantRoleField.value === 'reserve' ? 'bg-emerald-400/12 text-emerald-200' : 'bg-transparent text-slate-300 hover:bg-white/5'"
                                    >
                                        Peserta Cadangan
                                    </button>
                                </div>
                                <div class="border-t border-white/10 px-4 py-4">
                                    <p class="text-xs uppercase tracking-[0.18em]" x-bind:class="$refs.participantRoleField.value === 'reserve' ? 'text-emerald-200' : 'text-cyan-200'" x-text="$refs.participantRoleField.value === 'reserve' ? 'Status Cadangan' : 'Status Inti'"></p>
                                    <p class="mt-2 text-sm font-semibold text-white" x-text="$refs.participantRoleField.value === 'reserve' ? 'Peserta ini dicatat sebagai cadangan untuk golongan terpilih.' : 'Peserta ini dicatat sebagai peserta inti untuk golongan terpilih.'"></p>
                                </div>
                            </div>
                        </div>
                        <div><label class="mb-2 block text-sm font-semibold text-slate-200">Nama lengkap</label><input name="name" type="text" value="<?= e(old('name', $participant?->name)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"></div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Jenis kelamin</label>
                            <select name="gender" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="putra" <?= old('gender', $participant?->gender) === 'putra' ? 'selected' : '' ?> x-bind:disabled="selectedCategory && selectedCategory.gender_rule === 'putri_three'">Putra</option>
                                <option value="putri" <?= old('gender', $participant?->gender) === 'putri' ? 'selected' : '' ?> x-bind:disabled="selectedCategory && ['putra_two', 'putra_three'].includes(selectedCategory.gender_rule)">Putri</option>
                            </select>
                            <template x-if="selectedCategory && selectedCategory.gender_rule === 'putra_two'">
                                <p class="mt-2 text-xs text-amber-100">Golongan ini hanya untuk peserta putra.</p>
                            </template>
                            <template x-if="selectedCategory && selectedCategory.gender_rule === 'putra_three'">
                                <p class="mt-2 text-xs text-amber-100">Golongan ini hanya untuk peserta putra.</p>
                            </template>
                            <template x-if="selectedCategory && selectedCategory.gender_rule === 'putri_three'">
                                <p class="mt-2 text-xs text-fuchsia-100">Golongan ini hanya untuk peserta putri.</p>
                            </template>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Tempat lahir</label>
                            <input name="place_of_birth" type="text" value="<?= e(old('place_of_birth', $participant?->place_of_birth)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Tanggal lahir</label>
                            <input name="date_of_birth" type="date" max="2026-07-01" value="<?= e(old('date_of_birth', optional($participant?->date_of_birth)->format('Y-m-d'))) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <template x-if="isUnderSeventeen()">
                            <div class="lg:col-span-2 rounded-[1.4rem] border border-cyan-400/20 bg-cyan-400/8 px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Penanda otomatis</p>
                                <p class="mt-2 text-lg font-bold text-white">Peserta di bawah 17 tahun</p>
                                <p class="mt-1 text-sm text-slate-300">Tanggal KTP, upload KTP, dan Ijazah otomatis menjadi opsional. Data bank dan buku tabungan juga tetap opsional.</p>
                            </div>
                        </template>
                        <?php
                        $educationOptions = [
                            'PAUD/TK',
                            'SD/MI',
                            'SMP/MTs',
                            'SMA/SMK/MA',
                            'D1/D2/D3',
                            'S1/D4',
                            'S2',
                            'S3',
                            'Lainnya',
                        ];
                        ?>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Asal lembaga / kafilah</label>
                            <input name="institution" type="text" value="<?= e(old('institution', $participant?->institution)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">No. HP</label>
                            <input name="phone" type="text" value="<?= e(old('phone', $participant?->phone)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor KK</label>
                            <input name="kk_number" type="text" value="<?= e(old('kk_number', $participant?->kk_number)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Tanggal KK</label>
                            <input name="kk_date" type="date" value="<?= e(old('kk_date', optional($participant?->kk_date)->format('Y-m-d'))) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div x-bind:class="isUnderSeventeen() ? 'rounded-2xl border border-cyan-400/20 bg-cyan-400/6 px-4 py-4' : ''">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">NIK <span class="text-xs font-medium text-rose-300">(wajib)</span></label>
                            <input name="nik" type="text" value="<?= e(old('nik', $participant?->nik)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div x-bind:class="isUnderSeventeen() ? 'rounded-2xl border border-cyan-400/20 bg-cyan-400/6 px-4 py-4' : ''">
                            <label class="mb-2 block text-sm font-semibold text-slate-200" x-bind:class="isUnderSeventeen() ? 'text-cyan-100' : ''">Tanggal KTP <span class="text-xs font-medium text-cyan-200" x-show="isUnderSeventeen()">(otomatis opsional)</span></label>
                            <input name="ktp_date" type="date" value="<?= e(old('ktp_date', optional($participant?->ktp_date)->format('Y-m-d'))) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Pendidikan terakhir</label>
                            <select name="last_education" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Pilih pendidikan terakhir</option>
                                <?php foreach ($educationOptions as $educationOption): ?>
                                    <option value="<?= e($educationOption) ?>" <?= old('last_education', $participant?->last_education) === $educationOption ? 'selected' : '' ?>><?= e($educationOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor rekening <span class="text-xs font-medium text-cyan-200">(opsional)</span></label>
                            <input name="bank_account_number" type="text" value="<?= e(old('bank_account_number', $participant?->bank_account_number)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Bank <span class="text-xs font-medium text-cyan-200">(opsional)</span></label>
                            <input name="bank_name" type="text" value="<?= e(old('bank_name', $participant?->bank_name)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Atas nama rekening <span class="text-xs font-medium text-cyan-200">(opsional)</span></label>
                            <input name="bank_account_name" type="text" value="<?= e(old('bank_account_name', $participant?->bank_account_name)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Alamat sesuai KTP</label>
                            <textarea name="ktp_address" rows="3" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"><?= e(old('ktp_address', $participant?->ktp_address)) ?></textarea>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kecamatan sesuai KTP</label>
                            <input name="ktp_district" type="text" value="<?= e(old('ktp_district', $participant?->ktp_district)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kabupaten sesuai KTP</label>
                            <input name="ktp_regency" type="text" value="<?= e(old('ktp_regency', $participant?->ktp_regency)) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Alamat saat ini</label>
                            <textarea name="current_address" rows="3" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"><?= e(old('current_address', $participant?->current_address)) ?></textarea>
                        </div>
                        <div class="lg:col-span-2 mt-2 rounded-[1.4rem] border border-emerald-400/16 bg-emerald-400/8 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">Form Upload</p>
                            <p class="mt-2 text-lg font-bold text-white">Upload ulang dokumen</p>
                            <p class="mt-1 text-sm text-slate-300">Bagian ini dipisahkan khusus untuk revisi atau penggantian berkas administrasi.</p>
                        </div>

                        <?php
                        $editDocumentInputs = [
                            'photo' => ['input' => 'photo_document', 'accept' => 'image/*', 'preview' => 'photo', 'multiple' => false],
                            'birth_certificate' => ['input' => 'birth_certificate_document', 'accept' => 'image/*,.pdf', 'preview' => 'document', 'multiple' => false],
                            'kk' => ['input' => 'kk_document', 'accept' => 'image/*,.pdf', 'preview' => 'document', 'multiple' => false],
                            'ktp' => ['input' => 'ktp_document', 'accept' => 'image/*,.pdf', 'preview' => 'document', 'multiple' => false],
                            'last_diploma' => ['input' => 'last_diploma_document', 'accept' => 'image/*,.pdf', 'preview' => 'document', 'multiple' => false],
                            'bank_book' => ['input' => 'bank_book_document', 'accept' => 'image/*,.pdf', 'preview' => 'document', 'multiple' => false],
                            'certificates' => ['input' => 'certificate_documents', 'accept' => 'image/*,.pdf', 'preview' => 'multiple', 'multiple' => true],
                            'other_files' => ['input' => 'other_documents', 'accept' => 'image/*,.pdf', 'preview' => 'multiple', 'multiple' => true],
                        ];
                        ?>
                        <?php foreach ($editDocumentInputs as $key => $config): ?>
                            <?php $document = $documentMap[$key] ?? ['label' => '-', 'path' => null, 'files' => [], 'revision_note' => null]; ?>
                            <?php $ageOptionalDocument = in_array($key, ['ktp', 'last_diploma'], true); ?>
                            <?php $hasExistingDocument = $config['multiple'] ? ! empty($document['files']) : filled($document['path']); ?>
                            <?php $existingDocumentLabel = $config['multiple']
                                ? count($document['files'] ?? []).' file sudah diupload sebelumnya'
                                : 'Dokumen sudah diupload sebelumnya'; ?>
                            <div x-bind:class="<?= $ageOptionalDocument ? 'isUnderSeventeen() ? \'rounded-2xl border border-cyan-400/20 bg-cyan-400/6 px-4 py-4\' : \'\' ' : '\'\' ' ?>">
                                <label class="mb-2 block text-sm font-semibold text-slate-200" x-bind:class="<?= $ageOptionalDocument ? 'isUnderSeventeen() ? \'text-cyan-100\' : \'\' ' : '\'\' ' ?>"><?= e('Upload ulang '.$document['label']) ?><?php if ($ageOptionalDocument): ?> <span class="text-xs font-medium text-cyan-200" x-show="isUnderSeventeen()">(otomatis opsional)</span><?php endif; ?></label>
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] <?= $hasExistingDocument ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-slate-600 bg-slate-900/70 text-slate-300' ?>">
                                        <?= e($hasExistingDocument ? 'Sudah ada dokumen' : 'Belum ada dokumen') ?>
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        <?= e($hasExistingDocument ? $existingDocumentLabel : 'Jika Anda belum mengunggah file baru, dokumen lama akan tetap dipakai jika tersedia.') ?>
                                    </span>
                                </div>
                                <input
                                    name="<?= e($config['multiple'] ? $config['input'].'[]' : $config['input']) ?>"
                                    type="file"
                                    <?= $config['multiple'] ? 'multiple' : '' ?>
                                    accept="<?= e($config['accept']) ?>"
                                    x-on:change="<?= $config['preview'] === 'photo'
                                        ? "trackPhotoFile(\$event, '".$config['input']."')"
                                        : ($config['multiple']
                                            ? "trackMultipleFiles(\$event, '".$config['input']."')"
                                            : "trackDocumentFile(\$event, '".$config['input']."')") ?>"
                                    class="w-full rounded-2xl border border-emerald-400/18 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400/10 file:px-4 file:py-2 file:text-emerald-200">
                                <p class="mt-2 text-xs text-slate-400" x-text="'File terpilih: ' + <?= $config['multiple']
                                    ? "displayMultipleFileNames('".$config['input']."', 'Belum ada file baru dipilih')"
                                    : "displayFileName('".$config['input']."', 'Belum ada file baru dipilih')" ?>"></p>
                                <?php if ($key === 'photo'): ?>
                                    <p class="mt-1 text-xs text-slate-500">Gunakan JPG/PNG, rasio 3:4, minimal 300 x 400 px, maksimal 2 MB.</p>
                                    <template x-if="photoPreviewUrl">
                                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                            <span class="rounded-full border px-3 py-1 font-semibold uppercase tracking-[0.18em]"
                                                x-bind:class="photoRatioValid() ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100' : 'border-rose-300/20 bg-rose-400/10 text-rose-100'"
                                                x-text="photoRatioValid() ? 'Rasio 3:4 sesuai' : 'Rasio belum sesuai'"></span>
                                            <span class="text-slate-400" x-text="photoDimensionText()"></span>
                                        </div>
                                    </template>
                                    <div class="mt-3 flex flex-wrap items-center gap-3" x-show="photoPreviewUrl">
                                        <button type="button" class="secondary-button px-4 py-2 text-sm" x-bind:disabled="photoProcessing || !photoPreviewUrl" x-bind:class="photoProcessing ? 'pointer-events-none opacity-60' : ''" x-on:click="convertPhotoToStandard()">
                                            <?= mtq_icon('spark', 'h-4 w-4') ?>
                                            <span x-text="photoProcessing ? 'Memproses 300 x 400...' : 'Sesuaikan otomatis ke 300 x 400'"></span>
                                        </button>
                                        <span class="text-xs text-emerald-200" x-show="photoAdjusted">Foto sudah disesuaikan ke 300 x 400 px.</span>
                                    </div>
                                    <template x-if="photoPreviewUrl && !photoRatioValid()">
                                        <p class="mt-2 rounded-2xl border border-rose-400/20 bg-rose-400/10 px-3 py-2 text-xs font-semibold text-rose-100">Pas foto harus memakai rasio 3:4 sebelum bisa dikirim untuk verifikasi.</p>
                                    </template>
                                <?php endif; ?>
                                <?php if ($config['preview'] === 'photo'): ?>
                                    <template x-if="photoPreviewUrl">
                                        <div class="mt-3 overflow-hidden rounded-2xl border border-emerald-400/20 bg-slate-950/70 p-3">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">Pratinjau Pas Foto Baru</p>
                                            <img x-bind:src="photoPreviewUrl" alt="Pratinjau pas foto baru" class="mt-3 h-40 w-32 rounded-xl border border-slate-700 object-cover shadow-[0_12px_40px_-20px_rgba(34,211,238,0.45)]">
                                        </div>
                                    </template>
                                <?php elseif (! $config['multiple']): ?>
                                    <template x-if="documentPreviewUrl('<?= e($config['input']) ?>')">
                                        <div class="mt-3 overflow-hidden rounded-2xl border border-emerald-400/20 bg-slate-950/70 p-3">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">Pratinjau Dokumen Baru</p>
                                            <img x-bind:src="documentPreviewUrl('<?= e($config['input']) ?>')" alt="Pratinjau dokumen baru" class="mt-3 h-40 w-full rounded-xl border border-slate-700 object-cover shadow-[0_12px_40px_-20px_rgba(34,211,238,0.45)]">
                                        </div>
                                    </template>
                                <?php endif; ?>
                                <p class="mt-2 text-xs text-slate-400">
                                    <?php if ($config['multiple']): ?>
                                        <?= ! empty($document['files']) ? e(count($document['files']).' file lama tersedia dan akan diganti jika upload baru dilakukan.') : 'Belum ada dokumen sebelumnya.' ?>
                                    <?php else: ?>
                                        <?= $document['path'] ? 'Dokumen lama tersedia dan akan diganti jika upload baru dilakukan.' : 'Belum ada dokumen sebelumnya.' ?>
                                    <?php endif; ?>
                                </p>
                                <?php if (! empty($document['revision_note'])): ?>
                                    <div class="mt-3 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-3 py-3 text-xs leading-6 text-amber-100">
                                        <p class="font-semibold uppercase tracking-[0.18em] text-amber-200">Catatan Revisi</p>
                                        <p class="mt-2"><?= e($document['revision_note']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="lg:col-span-2 flex flex-wrap gap-3">
                            <button type="button" class="secondary-button" x-on:click="goToStep(1)">
                                <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                Ganti Kategori
                            </button>
                            <button type="button" class="primary-button" x-on:click="goToStep(3)">
                                <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                Lanjut ke Review
                            </button>
                        </div>
                        </fieldset>

                        <div class="space-y-5" x-show="currentStep === 3">
                            <div class="rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/70 to-blue-950/60 p-5">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-200">Langkah 3</p>
                                <h4 class="mt-2 text-xl font-bold text-white">Tinjau revisi peserta</h4>
                                <p class="mt-2 text-sm text-slate-300">Simpan draft jika belum lengkap, atau kirim ulang untuk verifikasi bila semua data dan berkas sudah siap.</p>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Cabang / Golongan dipilih</p>
                                    <p class="mt-2 text-lg font-bold text-white" x-text="selectedCategory ? selectedCategory.branch + ' - ' + selectedCategory.name : '-'"></p>
                                    <p class="mt-2 text-sm text-slate-300" x-text="selectedCategory ? (selectedCategory.notes || selectedCategory.description || selectedCategory.age_requirement) : ''"></p>
                                </div>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Identitas inti</p>
                                    <div class="mt-3 space-y-2 text-sm text-slate-300">
                                        <p><span class="text-slate-500">Nama:</span> <span x-text="fieldValue('name') || '-'"></span></p>
                                        <p><span class="text-slate-500">Jenis peserta:</span> <span x-text="fieldValue('participant_role') === 'reserve' ? 'Cadangan' : 'Inti'"></span></p>
                                        <p><span class="text-slate-500">Gender:</span> <span x-text="fieldValue('gender') || '-'"></span></p>
                                        <p><span class="text-slate-500">Tempat lahir:</span> <span x-text="fieldValue('place_of_birth') || '-'"></span></p>
                                        <p><span class="text-slate-500">Tanggal lahir:</span> <span x-text="fieldValue('date_of_birth') || '-'"></span></p>
                                        <p><span class="text-slate-500">Asal lembaga:</span> <span x-text="fieldValue('institution') || '-'"></span></p>
                                        <p><span class="text-slate-500">No. HP:</span> <span x-text="fieldValue('phone') || '-'"></span></p>
                                        <p><span class="text-slate-500">No. KK:</span> <span x-text="fieldValue('kk_number') || '-'"></span></p>
                                        <p><span class="text-slate-500">Tanggal KK:</span> <span x-text="fieldValue('kk_date') || '-'"></span></p>
                                        <p><span class="text-slate-500">NIK:</span> <span x-text="fieldValue('nik') || '-'"></span></p>
                                        <p><span class="text-slate-500">Tanggal KTP:</span> <span x-text="fieldValue('ktp_date') || '-'"></span></p>
                                        <p><span class="text-slate-500">Pendidikan:</span> <span x-text="fieldValue('last_education') || '-'"></span></p>
                                        <p><span class="text-slate-500">No. Rek:</span> <span x-text="fieldValue('bank_account_number') || '-'"></span></p>
                                        <p><span class="text-slate-500">Bank:</span> <span x-text="fieldValue('bank_name') || '-'"></span></p>
                                        <p><span class="text-slate-500">A.n. Rek:</span> <span x-text="fieldValue('bank_account_name') || '-'"></span></p>
                                        <p><span class="text-slate-500">Alamat KTP:</span> <span x-text="fieldValue('ktp_address') || '-'"></span></p>
                                        <p><span class="text-slate-500">Kecamatan KTP:</span> <span x-text="fieldValue('ktp_district') || '-'"></span></p>
                                        <p><span class="text-slate-500">Kabupaten KTP:</span> <span x-text="fieldValue('ktp_regency') || '-'"></span></p>
                                        <p><span class="text-slate-500">Alamat saat ini:</span> <span x-text="fieldValue('current_address') || '-'"></span></p>
                                    </div>
                                </div>
                                <div class="data-card lg:col-span-2">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Dokumen revisi terpilih</p>
                                    <div class="mt-3 grid gap-2 text-sm text-slate-300 md:grid-cols-2">
                                        <p><span class="text-slate-500">Pas foto:</span> <span x-text="displayFileName('photo_document', 'tidak diganti')"></span></p>
                                        <p><span class="text-slate-500">Akta kelahiran:</span> <span x-text="displayFileName('birth_certificate_document', 'tidak diganti')"></span></p>
                                        <p><span class="text-slate-500">KK:</span> <span x-text="displayFileName('kk_document', 'tidak diganti')"></span></p>
                                        <p><span class="text-slate-500">KTP:</span> <span x-text="displayFileName('ktp_document', 'tidak diganti')"></span></p>
                                        <p><span class="text-slate-500">Ijazah terakhir:</span> <span x-text="displayFileName('last_diploma_document', 'tidak diganti')"></span></p>
                                        <p><span class="text-slate-500">Buku tabungan:</span> <span x-text="displayFileName('bank_book_document', 'tidak diganti')"></span></p>
                                        <p><span class="text-slate-500">Piagam:</span> <span x-text="displayMultipleFileNames('certificate_documents', 'tidak diganti')"></span></p>
                                        <p><span class="text-slate-500">Dokumen lainnya:</span> <span x-text="displayMultipleFileNames('other_documents', 'tidak diganti')"></span></p>
                                    </div>
                                </div>
                                <div class="data-card lg:col-span-2" x-show="documentPreviewUrl('photo_document') || documentPreviewUrl('kk_document') || documentPreviewUrl('ktp_document') || documentPreviewUrl('birth_certificate_document') || documentPreviewUrl('last_diploma_document') || documentPreviewUrl('bank_book_document')">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Pratinjau Dokumen Gambar Baru</p>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
                                        <template x-if="photoPreviewUrl">
                                            <img x-bind:src="photoPreviewUrl" alt="Pratinjau pas foto baru" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                        <template x-if="documentPreviewUrl('birth_certificate_document')">
                                            <img x-bind:src="documentPreviewUrl('birth_certificate_document')" alt="Pratinjau akta baru" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                        <template x-if="documentPreviewUrl('kk_document')">
                                            <img x-bind:src="documentPreviewUrl('kk_document')" alt="Pratinjau KK baru" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                        <template x-if="documentPreviewUrl('ktp_document')">
                                            <img x-bind:src="documentPreviewUrl('ktp_document')" alt="Pratinjau KTP baru" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                        <template x-if="documentPreviewUrl('last_diploma_document')">
                                            <img x-bind:src="documentPreviewUrl('last_diploma_document')" alt="Pratinjau ijazah baru" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                        <template x-if="documentPreviewUrl('bank_book_document')">
                                            <img x-bind:src="documentPreviewUrl('bank_book_document')" alt="Pratinjau buku tabungan baru" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                    </div>
                                </div>
                                <div class="data-card lg:col-span-2" x-show="photoPreviewUrl">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Pratinjau Pas Foto Baru</p>
                                    <img x-bind:src="photoPreviewUrl" alt="Pratinjau pas foto review edit" class="mt-3 h-44 w-36 rounded-2xl border border-slate-700 object-cover">
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button type="button" class="secondary-button" x-on:click="goToStep(2)">
                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                    Kembali ke Form
                                </button>
                                <button type="submit" name="submit_action" value="draft" class="secondary-button">
                                    <?= mtq_icon('upload', 'h-4 w-4') ?>
                                    Simpan Draft
                                </button>
                                <button type="submit" name="submit_action" value="submitted" class="primary-button">
                                    <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                    Kirim Ulang untuk Verifikasi
                                </button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <script>
        function participantEditFlow(initialState) {
            const initialCategory = (initialState.categories ?? []).find((category) => category.id === (initialState.selectedCategoryId ?? '')) ?? null;
            return {
                selectedCategoryId: initialState.selectedCategoryId ?? '',
                selectedBranch: initialCategory ? (initialCategory.branch || '') : (initialState.selectedBranch ?? ''),
                currentStep: (initialState.selectedCategoryId ?? '') ? 2 : 1,
                searchTerm: '',
                categories: initialState.categories ?? [],
                branches: initialState.branches ?? [],
                fileNames: {},
                multipleFileNames: {},
                documentPreviewUrls: {},
                photoPreviewUrl: initialState.existingPhotoPreviewUrl ?? '',
                photoProcessing: false,
                photoAdjusted: false,
                photoMeta: {
                    width: 0,
                    height: 0,
                    ratioValid: false,
                    measured: false,
                },
                init() {
                    if (this.photoPreviewUrl) {
                        this.readPhotoMeta(this.photoPreviewUrl);
                    }
                },
                get selectedCategory() {
                    return this.categories.find((category) => category.id === this.selectedCategoryId) ?? null;
                },
                goToStep(step) {
                    if (step === 2 && !this.selectedCategoryId) {
                        this.currentStep = 1;
                        return;
                    }
                    this.currentStep = step;
                    const targetId = step > 1 ? 'edit-form-panel' : null;
                    if (targetId) {
                        const target = document.getElementById(targetId);
                        if (target) {
                            requestAnimationFrame(() => {
                                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            });
                        }
                    }
                },
                selectCategory(categoryId) {
                    this.selectedCategoryId = categoryId;
                    const category = this.categories.find((item) => item.id === categoryId);
                    this.selectedBranch = category ? (category.branch || '') : '';
                    this.goToStep(2);
                },
                selectBranch(branch) {
                    this.selectedBranch = branch;
                    this.selectedCategoryId = '';
                },
                resetBranch() {
                    this.selectedBranch = '';
                    this.selectedCategoryId = '';
                },
                normalizedSearch() {
                    return (this.searchTerm || '').toLowerCase();
                },
                matchesBranch(branchText) {
                    const search = this.normalizedSearch();
                    return !search || branchText.includes(search);
                },
                matchesCategory(categoryText) {
                    const search = this.normalizedSearch();
                    return !search || categoryText.includes(search);
                },
                fieldValue(name) {
                    const form = this.$refs.editForm;
                    if (!form) {
                        return '';
                    }
                    const field = form.querySelector(`[name="${name}"]`);
                    return field ? field.value : '';
                },
                hasFile(name) {
                    if (this.fileNames[name]) {
                        return true;
                    }

                    const form = this.$refs.editForm;
                    if (!form) {
                        return false;
                    }
                    const field = form.querySelector(`[name="${name}"]`);
                    return !!(field && field.files && field.files.length > 0);
                },
                trackFileName(event, name) {
                    const file = event?.target?.files?.[0] ?? null;
                    this.fileNames[name] = file ? file.name : '';
                },
                trackMultipleFiles(event, name) {
                    const files = Array.from(event?.target?.files ?? []);
                    this.multipleFileNames[name] = files.map((file) => file.name);
                },
                trackDocumentFile(event, name) {
                    this.trackFileName(event, name);
                    const file = event?.target?.files?.[0] ?? null;
                    if (this.documentPreviewUrls[name]) {
                        URL.revokeObjectURL(this.documentPreviewUrls[name]);
                    }
                    this.documentPreviewUrls[name] = file && (file.type || '').startsWith('image/')
                        ? URL.createObjectURL(file)
                        : '';
                },
                trackPhotoFile(event, name) {
                    this.trackFileName(event, name);
                    const file = event?.target?.files?.[0] ?? null;
                    if (this.photoPreviewUrl) {
                        URL.revokeObjectURL(this.photoPreviewUrl);
                    }
                    this.photoProcessing = false;
                    this.photoAdjusted = false;
                    this.photoMeta = {
                        width: 0,
                        height: 0,
                        ratioValid: false,
                        measured: false,
                    };

                    if (!file) {
                        this.photoPreviewUrl = '';
                        return;
                    }

                    this.setPhotoPreviewFromFile(file);
                },
                setPhotoPreviewFromFile(file) {
                    if (this.photoPreviewUrl) {
                        URL.revokeObjectURL(this.photoPreviewUrl);
                    }

                    const previewUrl = URL.createObjectURL(file);
                    this.photoPreviewUrl = previewUrl;
                    this.readPhotoMeta(previewUrl);
                },
                readPhotoMeta(previewUrl) {
                    const image = new Image();
                    image.onload = () => {
                        const width = Number(image.naturalWidth || 0);
                        const height = Number(image.naturalHeight || 0);
                        this.photoMeta = {
                            width,
                            height,
                            ratioValid: width > 0 && height > 0 && (width * 4 === height * 3),
                            measured: true,
                        };
                    };
                    image.onerror = () => {
                        this.photoMeta = {
                            width: 0,
                            height: 0,
                            ratioValid: false,
                            measured: false,
                        };
                    };
                    image.src = previewUrl;
                },
                convertPhotoToStandard() {
                    const form = this.$refs.editForm;
                    const field = form ? form.querySelector('[name="photo_document"]') : null;
                    const file = field?.files?.[0] ?? null;

                    if (!file || this.photoProcessing) {
                        return;
                    }

                    this.photoProcessing = true;
                    this.photoAdjusted = false;

                    const sourceUrl = URL.createObjectURL(file);
                    const image = new Image();
                    image.onload = () => {
                        const sourceWidth = Number(image.naturalWidth || 0);
                        const sourceHeight = Number(image.naturalHeight || 0);
                        const targetWidth = 300;
                        const targetHeight = 400;
                        const targetRatio = targetWidth / targetHeight;

                        if (!sourceWidth || !sourceHeight) {
                            this.photoProcessing = false;
                            URL.revokeObjectURL(sourceUrl);
                            return;
                        }

                        let cropWidth = sourceWidth;
                        let cropHeight = sourceHeight;
                        let cropX = 0;
                        let cropY = 0;
                        const sourceRatio = sourceWidth / sourceHeight;

                        if (sourceRatio > targetRatio) {
                            cropWidth = Math.round(sourceHeight * targetRatio);
                            cropX = Math.max(0, Math.round((sourceWidth - cropWidth) / 2));
                        } else if (sourceRatio < targetRatio) {
                            cropHeight = Math.round(sourceWidth / targetRatio);
                            cropY = Math.max(0, Math.round((sourceHeight - cropHeight) / 2));
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = targetWidth;
                        canvas.height = targetHeight;
                        const context = canvas.getContext('2d');

                        if (!context) {
                            this.photoProcessing = false;
                            URL.revokeObjectURL(sourceUrl);
                            return;
                        }

                        context.drawImage(image, cropX, cropY, cropWidth, cropHeight, 0, 0, targetWidth, targetHeight);

                        canvas.toBlob((blob) => {
                            this.photoProcessing = false;
                            URL.revokeObjectURL(sourceUrl);

                            if (!blob || !field) {
                                return;
                            }

                            const baseName = (file.name || 'pas-foto').replace(/\.[^.]+$/, '');
                            const convertedFile = new File([blob], `${baseName}-300x400.jpg`, { type: 'image/jpeg' });
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(convertedFile);
                            field.files = dataTransfer.files;
                            this.fileNames.photo_document = convertedFile.name;
                            this.photoAdjusted = true;
                            this.setPhotoPreviewFromFile(convertedFile);
                        }, 'image/jpeg', 0.92);
                    };
                    image.onerror = () => {
                        this.photoProcessing = false;
                        URL.revokeObjectURL(sourceUrl);
                    };
                    image.src = sourceUrl;
                },
                photoDimensionText() {
                    if (!this.photoMeta.measured || !this.photoMeta.width || !this.photoMeta.height) {
                        return 'Dimensi foto belum terbaca.';
                    }

                    return `${this.photoMeta.width} x ${this.photoMeta.height} px`;
                },
                photoRatioValid() {
                    return this.photoMeta.measured && this.photoMeta.ratioValid;
                },
                photoRatioStatusLabel() {
                    if (!this.photoPreviewUrl && !this.hasFile('photo_document')) {
                        return 'belum dicek';
                    }

                    if (!this.photoMeta.measured) {
                        return 'sedang membaca dimensi foto';
                    }

                    return this.photoRatioValid()
                        ? `sesuai 3:4 (${this.photoDimensionText()})`
                        : `belum sesuai 3:4 (${this.photoDimensionText()})`;
                },
                displayFileName(name, fallback = '-') {
                    return this.fileNames[name] || fallback;
                },
                displayMultipleFileNames(name, fallback = '-') {
                    const files = this.multipleFileNames[name] || [];
                    return files.length ? files.join(', ') : fallback;
                },
                documentPreviewUrl(name) {
                    return this.documentPreviewUrls[name] || '';
                },
                participantAge() {
                    const value = this.fieldValue('date_of_birth');

                    if (!value) {
                        return null;
                    }

                    const birthDate = new Date(`${value}T00:00:00`);
                    const referenceDate = new Date(2026, 6, 1);

                    if (Number.isNaN(birthDate.getTime())) {
                        return null;
                    }

                    if (birthDate > referenceDate) {
                        return { future: true, years: 0, months: 0, days: 0 };
                    }

                    let years = referenceDate.getFullYear() - birthDate.getFullYear();
                    let months = referenceDate.getMonth() - birthDate.getMonth();
                    let days = referenceDate.getDate() - birthDate.getDate();

                    if (days < 0) {
                        months -= 1;
                        days += new Date(referenceDate.getFullYear(), referenceDate.getMonth(), 0).getDate();
                    }

                    if (months < 0) {
                        years -= 1;
                        months += 12;
                    }

                    return { future: false, years, months, days };
                },
                isUnderSeventeen() {
                    const age = this.participantAge();
                    return !!age && !age.future && age.years < 17;
                },
            };
        }
    </script>
</body>
</html>
