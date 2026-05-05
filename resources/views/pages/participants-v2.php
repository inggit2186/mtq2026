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
$rolePanel = $rolePanel ?? [];
$categoryUsage = $categoryUsage ?? collect();
$officialMandate = $officialMandate ?? ['required' => false, 'ready' => true, 'path' => '', 'status' => 'missing', 'notes' => null, 'uploaded_at' => null, 'verified_at' => null, 'preview_url' => null];
$officialAccessSetting = $officialAccessSetting ?? new \App\Models\OfficialAccessSetting();
$officialRegistrationOpen = (bool) ($officialAccessSetting->participant_registration_open ?? true);
$officialEditOpen = (bool) ($officialAccessSetting->participant_edit_open ?? true);
$officialMandateUploadOpen = (bool) ($officialAccessSetting->mandate_upload_open ?? true);
$officialDocumentsOpen = (bool) ($officialAccessSetting->participant_documents_open ?? true);
$isOfficialUser = in_array($user?->role, ['official', 'pendamping'], true);
$registrationCategoryInitial = (string) old('competition_category_id', request()->query('registration_category_id', ''));
$registrationCategoryCards = collect($categories ?? [])->map(function ($category) use ($categoryUsage): array {
    $usage = $categoryUsage[$category->id] ?? [];

    return [
        'id' => (string) $category->id,
        'branch' => $category->branch,
        'name' => $category->name,
        'quota' => (int) $category->quota,
        'age_requirement' => (string) $category->age_requirement,
        'notes' => (string) ($category->notes ?? ''),
        'description' => (string) ($category->description ?? ''),
        'image' => mtq_category_visual((string) $category->branch, (string) $category->name),
        'scope_label' => (string) ($usage['scope_label'] ?? 'kabupaten'),
        'available_slots' => (int) ($usage['available_slots'] ?? 0),
        'registered' => (int) ($usage['registered'] ?? 0),
        'reserve_registered' => (int) ($usage['reserve_registered'] ?? 0),
        'verified' => (int) ($usage['verified'] ?? 0),
        'pending' => (int) ($usage['pending'] ?? 0),
        'draft' => (int) ($usage['draft'] ?? 0),
        'remaining_slots' => (int) ($usage['remaining_slots'] ?? 0),
        'reserve_remaining_slots' => (int) ($usage['reserve_remaining_slots'] ?? 0),
        'district_based' => (bool) ($usage['district_based'] ?? false),
        'quota_multiplier' => (int) ($usage['quota_multiplier'] ?? 1),
        'host_district' => (bool) ($usage['host_district'] ?? false),
        'main_is_full' => (int) ($usage['remaining_slots'] ?? 0) <= 0,
        'reserve_is_full' => (int) ($usage['reserve_remaining_slots'] ?? 0) <= 0,
        'is_selectable' => (int) ($usage['remaining_slots'] ?? 0) > 0 || (int) ($usage['reserve_remaining_slots'] ?? 0) > 0,
        'gender_rule' => (string) ($usage['gender_rule'] ?? ''),
        'putra_registered' => (int) ($usage['putra_registered'] ?? 0),
        'putri_registered' => (int) ($usage['putri_registered'] ?? 0),
    ];
})->values();
$categoryBranches = $registrationCategoryCards
    ->filter(fn (array $category): bool => filled($category['branch']))
    ->groupBy('branch')
    ->map(function ($items, string $branch): array {
        $collection = collect($items);

        return [
            'branch' => $branch,
            'image' => mtq_category_visual($branch, 'Pilihan Kategori'),
            'category_total' => $collection->count(),
            'available_slots' => $collection->sum('available_slots'),
            'registered' => $collection->sum('registered'),
            'remaining_slots' => $collection->sum('remaining_slots'),
            'has_open_slots' => $collection->contains(fn (array $item): bool => (bool) ($item['is_selectable'] ?? false)),
            'host_district' => $collection->contains(fn (array $item): bool => (bool) ($item['host_district'] ?? false)),
        ];
    })
    ->values();
$standaloneCategories = $registrationCategoryCards
    ->filter(fn (array $category): bool => ! filled($category['branch']))
    ->values();
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'participants.index');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Pendaftaran Peserta') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="{ mobileNavOpen: false }">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('id-card') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Pendaftaran Peserta</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Akses Pendaftaran</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($user?->name) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">
                        <?php if ($districtLocked): ?>
                            Official hanya dapat mendaftarkan peserta untuk kecamatan sendiri.
                        <?php else: ?>
                            Admin dan panitia dapat memantau seluruh peserta yang masuk ke sistem.
                        <?php endif; ?>
                    </p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Form Aktif
                    </div>
                </div>

                <nav class="mt-8 space-y-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigasi</p>
                    <?php foreach ($navigation as $item): ?>
                        <a href="<?= e($item['href']) ?>" class="sidebar-link <?= $item['active'] ? 'sidebar-link-active' : '' ?>">
                            <span class="icon-chip h-10 w-10 rounded-xl"><?= mtq_icon($item['icon'], 'h-4 w-4') ?></span>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="mt-8 grid gap-3">
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Total Peserta</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['total']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Draft</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['draft']) ?></p>
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
                            <p class="section-kicker">Registrasi Digital</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Form pendaftaran peserta sesuai juknis</h2>
                            <p class="mt-2 text-sm text-slate-300">Mulai dengan memilih cabang dan golongan MTQ, lalu lanjutkan ke pengisian identitas dan upload berkas peserta.</p>
                        </div>
                    </div>
                    <a href="<?= e(route('participants.list')) ?>" class="secondary-button">
                        <?= mtq_icon('users', 'h-4 w-4') ?>
                        Data Peserta
                    </a>
                </header>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Total</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('check-circle') ?></div><p class="mt-4 text-sm text-slate-400">Terverifikasi</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['verified']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('clock') ?></div><p class="mt-4 text-sm text-slate-400">Menunggu</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['pending']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('upload') ?></div><p class="mt-4 text-sm text-slate-400">Draft</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($registrationStats['draft']) ?></p></div>
                </section>

                <?php if ($officialMandate['required'] ?? false): ?>
                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="icon-chip"><?= mtq_icon('upload') ?></div>
                                <div>
                                    <p class="section-kicker">Surat Mandat Official</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Upload surat mandat kecamatan sebelum mendaftarkan peserta</h3>
                                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">Surat mandat cukup diupload satu kali untuk setiap kecamatan. Setelah terpasang, semua official pada kecamatan yang sama dapat membuka akses pendaftaran peserta. File wajib PDF dengan ukuran maksimal 4 MB.</p>
                                </div>
                            </div>
                            <?php
                            $mandateStatus = $officialMandate['status'] ?? 'missing';
                            $mandateStatusText = match ($mandateStatus) {
                                'verified' => 'Terverifikasi',
                                'submitted' => 'Sudah Upload',
                                'rejected' => 'Ditolak',
                                default => 'Wajib Upload PDF',
                            };
                            $mandateDotClass = match ($mandateStatus) {
                                'verified' => 'bg-emerald-300',
                                'submitted' => 'bg-amber-300',
                                'rejected' => 'bg-rose-300',
                                default => 'bg-amber-300',
                            };
                            ?>
                            <div class="status-pill">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full <?= e($mandateDotClass) ?>"></span>
                                <?= e($mandateStatusText) ?>
                            </div>
                        </div>

                        <?php if ($officialMandate['ready'] ?? false): ?>
                            <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_auto]">
                                <div class="rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-4 text-sm text-emerald-100">
                                    <?php if (($officialMandate['status'] ?? 'missing') === 'verified'): ?>
                                        Surat mandat Kecamatan <?= e($officialMandate['district_name'] ?? '-') ?> sudah diverifikasi<?= $officialMandate['verified_at'] ? ' pada '.e($officialMandate['verified_at']->translatedFormat('d F Y H:i')) : '' ?>. Semua official pada kecamatan ini dapat melanjutkan pendaftaran peserta.
                                    <?php else: ?>
                                        Surat mandat Kecamatan <?= e($officialMandate['district_name'] ?? '-') ?> sudah diupload<?= $officialMandate['uploaded_at'] ? ' pada '.e($officialMandate['uploaded_at']->translatedFormat('d F Y H:i')) : '' ?>. Semua official pada kecamatan ini dapat langsung mendaftarkan peserta.
                                    <?php endif; ?>
                                </div>
                                <?php if ($officialMandate['preview_url']): ?>
                                    <a href="<?= e($officialMandate['preview_url']) ?>" target="_blank" rel="noreferrer" class="secondary-button justify-center">
                                        <?= mtq_icon('book-open', 'h-4 w-4') ?>
                                        Pratinjau Surat
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="mt-6 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-4 text-sm leading-6 text-amber-100">
                                <?php if ($mandateStatus === 'submitted'): ?>
                                    Surat mandat kecamatan sudah diupload<?= $officialMandate['uploaded_at'] ? ' pada '.e($officialMandate['uploaded_at']->translatedFormat('d F Y H:i')) : '' ?>. Form pendaftaran peserta sudah dibuka, dan panitia akan mengecek mandat bersama data peserta.
                                <?php elseif ($mandateStatus === 'rejected'): ?>
                                    Surat mandat kecamatan ditolak. Upload ulang file PDF yang benar agar panitia dapat memverifikasi kembali.
                                    <?php if ($officialMandate['notes']): ?>
                                        <span class="mt-2 block text-rose-100"><?= e($officialMandate['notes']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Form pendaftaran peserta dikunci sampai surat mandat kecamatan PDF diupload.
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (! $isOfficialUser || $officialMandateUploadOpen): ?>
                            <form
                                method="POST"
                                action="<?= e(route('participants.mandate.upload')) ?>"
                                enctype="multipart/form-data"
                                class="mt-6 rounded-[1.5rem] border border-white/8 bg-slate-950/35 p-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end"
                                data-native-submit
                                data-loading-text="Mengunggah surat mandat kecamatan..."
                                data-loading-button-text="Mengunggah..."
                                x-data="{ uploadingMandate: false, mandateFileName: '', mandateFileSize: '', updateMandateFile(event) { const file = event.target.files?.[0]; this.mandateFileName = file ? file.name : ''; this.mandateFileSize = file ? `${(file.size / 1024 / 1024).toFixed(2)} MB` : ''; } }"
                                x-on:submit="uploadingMandate = true"
                                x-on:mtq-submit-failed.window="uploadingMandate = false"
                            >
                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                <div class="min-w-0">
                                    <label class="mb-2 block text-sm font-semibold text-slate-200"><?= ($officialMandate['ready'] ?? false) ? 'Ganti Surat Mandat Kecamatan' : 'Upload Surat Mandat Kecamatan' ?></label>
                                    <input name="mandate_document" type="file" accept="application/pdf,.pdf" required class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20 file:mr-4 file:rounded-xl file:border-0 file:bg-cyan-400/10 file:px-4 file:py-2 file:font-semibold file:text-cyan-200" x-on:change="updateMandateFile($event)">
                                    <div class="mt-3 rounded-2xl border border-cyan-400/14 bg-cyan-400/8 px-4 py-3 text-sm text-cyan-100" x-show="mandateFileName || uploadingMandate" x-cloak>
                                        <p x-show="mandateFileName">
                                            File dipilih: <span class="font-semibold" x-text="mandateFileName"></span>
                                            <span class="text-cyan-200/75" x-text="mandateFileSize ? `(${mandateFileSize})` : ''"></span>
                                        </p>
                                        <p class="mt-1 text-xs leading-5 text-cyan-100/80" x-show="uploadingMandate">
                                            Sedang mengunggah dan memproses surat mandat. Mohon tunggu sampai halaman selesai memuat ulang.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex lg:justify-end lg:self-stretch">
                                    <button type="submit" class="primary-button min-h-[52px] w-auto justify-center whitespace-nowrap px-4 py-3 text-sm lg:h-full" x-bind:disabled="uploadingMandate" x-bind:class="uploadingMandate ? 'pointer-events-none opacity-75' : ''">
                                        <span x-show="!uploadingMandate" class="inline-flex items-center gap-2">
                                            <?= mtq_icon('upload', 'h-4 w-4') ?>
                                            <?= ($officialMandate['status'] ?? 'missing') === 'missing' ? 'Upload Surat' : 'Upload Ulang' ?>
                                        </span>
                                        <span x-show="uploadingMandate" class="inline-flex items-center gap-2" x-cloak>
                                            <span class="mtq-submit-button-spinner" aria-hidden="true"></span>
                                            Mengunggah...
                                        </span>
                                    </button>
                                </div>
                                <p class="text-xs leading-6 text-slate-400 lg:col-span-2">Pastikan surat mandat memuat kecamatan, official yang ditunjuk, dan pengesahan yang diperlukan.</p>
                            </form>
                        <?php else: ?>
                            <div class="mt-6 rounded-[1.5rem] border border-slate-700/80 bg-slate-950/60 p-4 text-sm leading-6 text-slate-300">
                                Upload surat mandat official sedang ditutup oleh admin. Kamu masih bisa melihat status surat mandat, tetapi tidak bisa mengunggah atau menggantinya saat ini.
                            </div>
                        <?php endif; ?>

                    </section>
                <?php endif; ?>

                <?php if (! ($officialMandate['required'] ?? false) || ($officialMandate['ready'] ?? false)): ?>
                <section class="glass-card rounded-[2rem] p-6" x-data="participantRegistrationFlow(<?= e(json_encode([
                    'selectedCategoryId' => $registrationCategoryInitial,
                    'categories' => $registrationCategoryCards,
                    'branches' => $categoryBranches,
                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('upload') ?></div>
                        <div>
                            <p class="section-kicker">Form Pendaftaran</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Pilih kategori dahulu, lalu isi data peserta</h3>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 md:grid-cols-3">
                        <div class="rounded-2xl border px-4 py-4 transition"
                            x-bind:class="currentStep >= 1 ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-cyan-400/18 bg-cyan-400/8 text-slate-200'">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em]">Langkah 1</p>
                            <p class="mt-2 text-base font-bold">Pilih cabang</p>
                            <p class="mt-1 text-sm opacity-80">Tentukan cabang besar terlebih dahulu.</p>
                        </div>
                        <div class="rounded-2xl border px-4 py-4 transition"
                            x-bind:class="currentStep >= 2 ? 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-900/70 text-slate-400'">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em]">Langkah 2</p>
                            <p class="mt-2 text-base font-bold">Isi data peserta</p>
                            <p class="mt-1 text-sm opacity-80">Lengkapi identitas dan dokumen.</p>
                        </div>
                        <div class="rounded-2xl border px-4 py-4 transition"
                            x-bind:class="currentStep >= 3 ? 'border-cyan-400/20 bg-cyan-400/10 text-cyan-100' : 'border-slate-700 bg-slate-900/70 text-slate-400'">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em]">Langkah 3</p>
                            <p class="mt-2 text-base font-bold">Review dan submit</p>
                            <p class="mt-1 text-sm opacity-80">Pastikan data sudah lengkap sebelum dikirim.</p>
                        </div>
                    </div>

                    <div class="mt-6" x-show="currentStep === 1">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-200">Langkah 1</p>
                                <p class="mt-2 text-lg font-bold text-white">Pilih cabang dan golongan MTQ</p>
                                <p class="mt-2 text-sm text-slate-300">Pilih cabang besar terlebih dahulu. Setelah itu baru pilih golongan di dalam cabang tersebut. Jika ada golongan tanpa cabang, pilihannya tampil langsung di awal.</p>
                            </div>
                            <template x-if="selectedCategory">
                                <div class="status-pill">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                    <span x-text="selectedCategory.branch + ' - ' + selectedCategory.name"></span>
                                </div>
                            </template>
                        </div>

                        <div class="mt-6 max-w-xl">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Cari cabang atau golongan</label>
                            <input
                                type="text"
                                x-model.trim="searchTerm"
                                placeholder="Contoh: tilawah, tafsir, hadits, kaligrafi"
                                class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"
                            >
                        </div>

                        <div x-show="!selectedBranch">
                            <div class="mt-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cabang Utama</p>
                                <div class="mt-4 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                                    <?php foreach ($categoryBranches as $branchCard): ?>
                                        <button
                                            type="button"
                                            class="group overflow-hidden rounded-[1.75rem] border border-slate-700/80 bg-slate-900/80 text-left transition duration-200 hover:-translate-y-1 hover:border-cyan-300/40 hover:shadow-[0_18px_55px_-28px_rgba(34,211,238,0.55)]"
                                            x-on:click="selectBranch('<?= e($branchCard['branch']) ?>')"
                                            x-show="matchesBranch('<?= e(mb_strtolower($branchCard['branch'])) ?>')"
                                            style="<?= ! $branchCard['has_open_slots'] ? 'opacity:.72;' : '' ?>"
                                        >
                                            <div class="aspect-[16/9] overflow-hidden bg-slate-950/70">
                                                <img src="<?= e($branchCard['image']) ?>" alt="<?= e($branchCard['branch']) ?>" loading="lazy" decoding="async" class="h-full w-full object-contain p-2">
                                            </div>
                                            <div class="space-y-3 p-5">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Cabang MTQ</p>
                                                        <h4 class="mt-2 text-lg font-bold text-white"><?= e($branchCard['branch']) ?></h4>
                                                    </div>
                                                    <?php if (! empty($branchCard['host_district'])): ?>
                                                        <span class="inline-flex rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-100">
                                                            Tuan Rumah x1
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">
                                                        <?= e($branchCard['category_total']) ?> golongan
                                                    </span>
                                                </div>
                                                <div class="grid grid-cols-3 gap-3 rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                                                    <div>
                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Slot</p>
                                                        <p class="mt-1 text-lg font-bold text-white"><?= e($branchCard['available_slots']) ?></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Terdaftar</p>
                                                        <p class="mt-1 text-lg font-bold text-cyan-200"><?= e($branchCard['registered']) ?></p>
                                                    </div>
                                                    <div>
                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Tersisa</p>
                                                        <p class="mt-1 text-lg font-bold <?= $branchCard['remaining_slots'] > 0 ? 'text-emerald-300' : 'text-rose-200' ?>"><?= e($branchCard['remaining_slots']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-4 rounded-2xl border border-dashed border-slate-700 bg-slate-950/40 px-4 py-4 text-sm text-slate-400" x-show="!hasVisibleBranches()">
                                    Tidak ada cabang yang cocok dengan pencarian Anda.
                                </div>
                            </div>

                            <?php if ($standaloneCategories->isNotEmpty()): ?>
                                <div class="mt-8">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Golongan Mandiri</p>
                                    <div class="mt-4 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                                        <?php foreach ($standaloneCategories as $categoryCard): ?>
                                            <button
                                                type="button"
                                                class="group overflow-hidden rounded-[1.75rem] border border-slate-700/80 bg-slate-900/80 text-left transition duration-200 hover:-translate-y-1 hover:border-cyan-300/40 hover:shadow-[0_18px_55px_-28px_rgba(34,211,238,0.55)]"
                                                x-on:click="selectCategory('<?= e($categoryCard['id']) ?>')"
                                                x-show="matchesCategory('<?= e(mb_strtolower(($categoryCard['branch'] ? $categoryCard['branch'].' ' : '').$categoryCard['name'].' '.$categoryCard['notes'].' '.$categoryCard['description'])) ?>')"
                                                <?= ! $categoryCard['is_selectable'] ? 'disabled' : '' ?>
                                                style="<?= ! $categoryCard['is_selectable'] ? 'opacity:.72;cursor:not-allowed;' : '' ?>"
                                            >
                                                <div class="aspect-[16/9] overflow-hidden bg-slate-950/70">
                                                    <img src="<?= e($categoryCard['image']) ?>" alt="<?= e($categoryCard['name']) ?>" loading="lazy" decoding="async" class="h-full w-full object-contain p-2">
                                                </div>
                                                <div class="space-y-3 p-5">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Golongan Mandiri</p>
                                                        <h4 class="mt-2 text-lg font-bold text-white"><?= e($categoryCard['name']) ?></h4>
                                                    </div>
                                                    <?php if (! empty($categoryCard['host_district'])): ?>
                                                        <span class="inline-flex rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-100">
                                                            Tuan Rumah x1
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                    <div class="grid grid-cols-2 gap-3 rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                                                        <div>
                                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Peserta Inti</p>
                                                            <p class="mt-1 text-sm font-bold text-cyan-200"><?= e($categoryCard['registered']) ?> / <?= e($categoryCard['available_slots']) ?></p>
                                                        </div>
                                                        <div>
                                                            <p class="text-[11px] uppercase tracking-[0.18em] text-emerald-200">Peserta Cadangan</p>
                                                            <p class="mt-1 text-sm font-bold text-emerald-200"><?= e($categoryCard['reserve_registered']) ?> / <?= e($categoryCard['available_slots']) ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-4 rounded-2xl border border-dashed border-slate-700 bg-slate-950/40 px-4 py-4 text-sm text-slate-400" x-show="!hasVisibleStandaloneCategories()">
                                        Tidak ada golongan mandiri yang cocok dengan pencarian Anda.
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
                                <?php foreach ($registrationCategoryCards as $categoryCard): ?>
                                    <template x-if="selectedBranch === '<?= e($categoryCard['branch']) ?>' && matchesCategory('<?= e(mb_strtolower(($categoryCard['branch'] ? $categoryCard['branch'].' ' : '').$categoryCard['name'].' '.$categoryCard['notes'].' '.$categoryCard['description'])) ?>')">
                                <button
                                    type="button"
                                    class="group overflow-hidden rounded-[1.75rem] border border-slate-700/80 bg-slate-900/80 text-left transition duration-200 hover:-translate-y-1 hover:border-cyan-300/40 hover:shadow-[0_18px_55px_-28px_rgba(34,211,238,0.55)]"
                                    x-on:click="selectCategory('<?= e($categoryCard['id']) ?>')"
                                    x-bind:class="selectedCategoryId === '<?= e($categoryCard['id']) ?>' ? 'border-cyan-300/70 ring-2 ring-cyan-300/30' : ''"
                                    <?= ! $categoryCard['is_selectable'] ? 'disabled' : '' ?>
                                    style="<?= ! $categoryCard['is_selectable'] ? 'opacity:.72;cursor:not-allowed;' : '' ?>"
                                >
                                    <div class="aspect-[16/9] overflow-hidden bg-slate-950/70">
                                        <img src="<?= e($categoryCard['image']) ?>" alt="<?= e($categoryCard['branch'].' - '.$categoryCard['name']) ?>" loading="lazy" decoding="async" class="h-full w-full object-contain p-2">
                                    </div>
                                    <div class="space-y-3 p-5">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200"><?= e($categoryCard['branch']) ?></p>
                                                <h4 class="mt-2 text-lg font-bold text-white"><?= e($categoryCard['name']) ?></h4>
                                            </div>
                                            <?php if (! $categoryCard['is_selectable']): ?>
                                                <span class="inline-flex rounded-full border border-rose-400/20 bg-rose-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-rose-100">
                                                    Slot Penuh
                                                </span>
                                            <?php elseif ($categoryCard['main_is_full']): ?>
                                                <span class="inline-flex rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-100">
                                                    Inti Penuh, Cadangan Buka
                                                </span>
                                            <?php elseif ($categoryCard['reserve_is_full']): ?>
                                                <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-100">
                                                    Cadangan Penuh, Inti Buka
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">
                                                    Kuota <?= e($categoryCard['quota']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm leading-6 text-slate-300"><?= e($categoryCard['age_requirement']) ?></p>
                                        <p class="text-xs leading-5 text-slate-400"><?= e($categoryCard['notes'] ?: ($categoryCard['description'] ?: 'Golongan siap dipilih untuk proses pendaftaran.')) ?></p>
                                        <?php if ($categoryCard['gender_rule'] === 'paired_two'): ?>
                                            <div class="rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
                                                Kuota kategori ini dibaca sebagai <strong>1 putra + 1 putri</strong>.
                                            </div>
                                        <?php elseif ($categoryCard['gender_rule'] === 'putra_two'): ?>
                                            <div class="rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
                                                Kuota kategori ini dibaca sebagai <strong>2 peserta putra</strong>.
                                            </div>
                                        <?php elseif ($categoryCard['gender_rule'] === 'putra_three'): ?>
                                            <div class="rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
                                                Kuota kategori ini dibaca sebagai <strong>3 peserta putra</strong>.
                                            </div>
                                        <?php elseif ($categoryCard['gender_rule'] === 'putri_three'): ?>
                                            <div class="rounded-2xl border border-fuchsia-400/20 bg-fuchsia-400/10 px-4 py-3 text-sm text-fuchsia-100">
                                                Kuota kategori ini dibaca sebagai <strong>3 peserta putri</strong>.
                                            </div>
                                        <?php endif; ?>
                                        <div class="grid grid-cols-2 gap-3 rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Slot <?= e($categoryCard['scope_label']) ?></p>
                                                <p class="mt-1 text-lg font-bold text-white"><?= e($categoryCard['available_slots']) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Terdaftar</p>
                                                <p class="mt-1 text-lg font-bold text-cyan-200"><?= e($categoryCard['registered']) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Tersisa</p>
                                                <p class="mt-1 text-lg font-bold <?= $categoryCard['remaining_slots'] > 0 ? 'text-emerald-300' : 'text-rose-200' ?>"><?= e($categoryCard['remaining_slots']) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Status aktif</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-200"><?= e($categoryCard['verified']) ?> verif. | <?= e($categoryCard['pending']) ?> tunggu</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3 rounded-2xl border border-emerald-400/16 bg-emerald-400/8 p-3">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Cadangan Terdaftar</p>
                                                <p class="mt-1 text-lg font-bold text-emerald-200"><?= e($categoryCard['reserve_registered']) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Sisa Cadangan</p>
                                                <p class="mt-1 text-lg font-bold <?= $categoryCard['reserve_remaining_slots'] > 0 ? 'text-emerald-300' : 'text-rose-200' ?>"><?= e($categoryCard['reserve_remaining_slots']) ?></p>
                                            </div>
                                        </div>
                                        <?php if ($categoryCard['gender_rule'] === 'paired_two'): ?>
                                            <div class="grid grid-cols-2 gap-3 rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                                                <div>
                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Slot Putra</p>
                                                    <p class="mt-1 text-lg font-bold <?= $categoryCard['putra_registered'] >= 1 ? 'text-rose-200' : 'text-emerald-300' ?>">
                                                        <?= e($categoryCard['putra_registered']) ?>/1
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Slot Putri</p>
                                                    <p class="mt-1 text-lg font-bold <?= $categoryCard['putri_registered'] >= 1 ? 'text-rose-200' : 'text-emerald-300' ?>">
                                                        <?= e($categoryCard['putri_registered']) ?>/1
                                                    </p>
                                                </div>
                                            </div>
                                        <?php elseif ($categoryCard['gender_rule'] === 'putra_two'): ?>
                                            <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Slot Putra</p>
                                                <p class="mt-1 text-lg font-bold <?= $categoryCard['putra_registered'] >= 2 ? 'text-rose-200' : 'text-emerald-300' ?>">
                                                    <?= e($categoryCard['putra_registered']) ?>/2
                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">Peserta putri tidak dibuka untuk golongan ini.</p>
                                            </div>
                                        <?php elseif ($categoryCard['gender_rule'] === 'putra_three'): ?>
                                            <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Slot Putra</p>
                                                <p class="mt-1 text-lg font-bold <?= $categoryCard['putra_registered'] >= 3 ? 'text-rose-200' : 'text-emerald-300' ?>">
                                                    <?= e($categoryCard['putra_registered']) ?>/3
                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">Peserta putri tidak dibuka untuk golongan ini.</p>
                                            </div>
                                        <?php elseif ($categoryCard['gender_rule'] === 'putri_three'): ?>
                                            <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Slot Putri</p>
                                                <p class="mt-1 text-lg font-bold <?= $categoryCard['putri_registered'] >= 3 ? 'text-rose-200' : 'text-emerald-300' ?>">
                                                    <?= e($categoryCard['putri_registered']) ?>/3
                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">Peserta putra tidak dibuka untuk golongan ini.</p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (! $categoryCard['is_selectable']): ?>
                                            <div class="rounded-2xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                                                Kuota inti dan cadangan untuk kategori ini sudah penuh. Silakan pilih kategori lain atau kelola peserta yang sudah terdaftar.
                                            </div>
                                        <?php elseif ($categoryCard['main_is_full']): ?>
                                            <div class="rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
                                                Slot peserta inti sudah penuh, tetapi slot peserta cadangan masih tersedia.
                                            </div>
                                        <?php elseif ($categoryCard['reserve_is_full']): ?>
                                            <div class="rounded-2xl border border-cyan-400/20 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">
                                                Slot peserta cadangan sudah penuh, tetapi slot peserta inti masih tersedia.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </button>
                                    </template>
                            <?php endforeach; ?>
                            </div>
                            <div class="mt-4 rounded-2xl border border-dashed border-slate-700 bg-slate-950/40 px-4 py-4 text-sm text-slate-400" x-show="!hasVisibleCategoriesInSelectedBranch()">
                                Tidak ada golongan yang cocok di cabang ini.
                            </div>
                        </div>
                    </div>

                    <div id="form-pendaftaran" class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/70 to-blue-950/60 p-5" x-show="selectedCategory && currentStep >= 2" x-transition.opacity.duration.200ms>
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-200">Langkah 2</p>
                                <h4 class="mt-2 text-xl font-bold text-white">Kategori terpilih</h4>
                                <p class="mt-2 text-sm text-slate-300">Form berikut akan mendaftarkan peserta ke kategori yang Anda pilih.</p>
                            </div>
                            <template x-if="selectedCategory">
                                <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3">
                                    <div class="h-16 w-24 overflow-hidden rounded-xl bg-slate-950/70">
                                        <img x-bind:src="selectedCategory.image" x-bind:alt="selectedCategory.name" loading="lazy" decoding="async" class="h-full w-full object-contain p-1">
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.18em] text-cyan-200" x-text="selectedCategory.branch"></p>
                                        <p class="mt-1 font-semibold text-white" x-text="selectedCategory.name"></p>
                                        <p class="mt-1 text-xs text-slate-400" x-text="selectedCategory.age_requirement"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <template x-if="selectedCategory">
                            <div class="mt-4 grid gap-3 md:grid-cols-4">
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Kuota aktif</p>
                                    <p class="mt-2 text-lg font-bold text-white" x-text="selectedCategory.available_slots"></p>
                                </div>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Sudah terdaftar</p>
                                    <p class="mt-2 text-lg font-bold text-cyan-200" x-text="selectedCategory.registered"></p>
                                </div>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Sisa slot</p>
                                    <p class="mt-2 text-lg font-bold" x-bind:class="selectedCategory.remaining_slots > 0 ? 'text-emerald-300' : 'text-rose-200'" x-text="selectedCategory.remaining_slots"></p>
                                </div>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Lingkup kuota</p>
                                    <p class="mt-2 text-lg font-bold text-white" x-text="selectedCategory.scope_label"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-6 rounded-[1.5rem] border border-dashed border-slate-700 bg-slate-950/50 p-6 text-sm text-slate-300" x-show="!selectedCategory">
                        Pilih salah satu kategori di atas terlebih dahulu untuk membuka form pendaftaran peserta.
                    </div>

                    <?php if (! $isOfficialUser || $officialRegistrationOpen): ?>
                    <form method="POST" action="<?= e(route('participants.store')) ?>" enctype="multipart/form-data" class="mt-6" x-ref="registrationForm" data-loading-text="Menyimpan data peserta dan mengunggah berkas...">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="competition_category_id" x-model="selectedCategoryId">
                        <fieldset class="grid gap-5 lg:grid-cols-2" x-show="currentStep === 2" x-bind:disabled="!selectedCategoryId" x-bind:class="!selectedCategoryId ? 'pointer-events-none opacity-50' : ''">

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Kecamatan</label>
                                <?php if ($districtLocked): ?>
                                    <div class="rounded-2xl border border-cyan-400/20 bg-cyan-400/10 px-4 py-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Kecamatan Official Login</p>
                                        <p class="mt-2 text-lg font-bold text-white"><?= e($user?->district?->name ?? $districts->firstWhere('id', $user?->district_id)?->name ?? '-') ?></p>
                                        <p class="mt-2 text-sm text-slate-300">Terkunci otomatis sesuai akun official yang sedang login.</p>
                                    </div>
                                    <input type="hidden" name="district_id" value="<?= e($user?->district_id) ?>">
                                <?php else: ?>
                                    <select name="district_id" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                        <option value="">Pilih kecamatan</option>
                                        <?php foreach ($districts as $district): ?>
                                            <option value="<?= e($district->id) ?>" <?= (string) old('district_id', $user?->district_id) === (string) $district->id ? 'selected' : '' ?>><?= e($district->name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <div class="lg:col-span-2 rounded-[1.4rem] border border-slate-800 bg-slate-950/55 px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Input</p>
                                <p class="mt-2 text-lg font-bold text-white">Data identitas peserta</p>
                                <p class="mt-1 text-sm text-slate-300">Isi identitas inti peserta terlebih dahulu sebelum melanjutkan ke area upload dokumen.</p>
                            </div>

                            <div class="lg:col-span-2">
                                <input type="hidden" name="participant_role" x-model="formValues.participant_role">
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Jenis Peserta</label>
                                <div class="overflow-hidden rounded-[1.25rem] border border-white/10 bg-slate-900/70">
                                    <div class="grid grid-cols-2 gap-2 border-b border-white/10 bg-slate-950/45 p-2">
                                        <button
                                            type="button"
                                            class="rounded-xl border px-4 py-3 text-left text-sm font-semibold transition"
                                            x-on:click="formValues.participant_role = 'main'"
                                            x-bind:class="formValues.participant_role === 'main' ? 'border-cyan-300/40 bg-cyan-400/18 text-white shadow-[0_10px_30px_-18px_rgba(34,211,238,0.85)]' : 'border-transparent bg-transparent text-slate-300 hover:border-white/10 hover:bg-white/5'"
                                        >
                                            <span class="inline-flex items-center gap-2">
                                                <span class="inline-flex h-2.5 w-2.5 rounded-full" x-bind:class="formValues.participant_role === 'main' ? 'bg-cyan-200' : 'bg-slate-500'"></span>
                                                Peserta Inti
                                            </span>
                                            <span class="mt-2 block text-[11px] font-medium uppercase tracking-[0.18em]" x-bind:class="formValues.participant_role === 'main' ? 'text-cyan-100' : 'text-slate-500'">Status utama lomba</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-xl border px-4 py-3 text-left text-sm font-semibold transition"
                                            x-on:click="formValues.participant_role = 'reserve'"
                                            x-bind:class="formValues.participant_role === 'reserve' ? 'border-emerald-300/40 bg-emerald-400/18 text-white shadow-[0_10px_30px_-18px_rgba(16,185,129,0.85)]' : 'border-transparent bg-transparent text-slate-300 hover:border-white/10 hover:bg-white/5'"
                                        >
                                            <span class="inline-flex items-center gap-2">
                                                <span class="inline-flex h-2.5 w-2.5 rounded-full" x-bind:class="formValues.participant_role === 'reserve' ? 'bg-emerald-200' : 'bg-slate-500'"></span>
                                                Peserta Cadangan
                                            </span>
                                            <span class="mt-2 block text-[11px] font-medium uppercase tracking-[0.18em]" x-bind:class="formValues.participant_role === 'reserve' ? 'text-emerald-100' : 'text-slate-500'">Status pengganti siap pakai</span>
                                        </button>
                                    </div>
                                    <div class="grid gap-3 border-b border-white/10 px-4 py-4 sm:grid-cols-2">
                                        <button
                                            type="button"
                                            class="rounded-2xl border px-4 py-3 text-left transition"
                                            x-on:click="formValues.participant_role = 'main'"
                                            x-bind:class="formValues.participant_role === 'main' ? 'border-cyan-300/30 bg-cyan-400/10' : 'border-white/10 bg-slate-950/40 hover:border-white/15 hover:bg-white/5'"
                                        >
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Status Inti</p>
                                            <p class="mt-2 text-sm font-semibold text-white"><span x-text="selectedCategory ? selectedCategory.registered : 0"></span> terdaftar</p>
                                            <p class="mt-1 text-xs text-slate-400"><span x-text="selectedCategory ? selectedCategory.remaining_slots : 0"></span> slot tersisa</p>
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-2xl border px-4 py-3 text-left transition"
                                            x-on:click="formValues.participant_role = 'reserve'"
                                            x-bind:class="formValues.participant_role === 'reserve' ? 'border-emerald-300/30 bg-emerald-400/10' : 'border-white/10 bg-slate-950/40 hover:border-white/15 hover:bg-white/5'"
                                        >
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Status Cadangan</p>
                                            <p class="mt-2 text-sm font-semibold text-white"><span x-text="selectedCategory ? selectedCategory.reserve_registered : 0"></span> terdaftar</p>
                                            <p class="mt-1 text-xs text-slate-400"><span x-text="selectedCategory ? selectedCategory.reserve_remaining_slots : 0"></span> slot tersisa</p>
                                        </button>
                                    </div>
                                    <div class="px-4 py-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cabang & Golongan Aktif</p>
                                        <p class="mt-2 text-base font-bold text-white" x-text="selectedCategory ? selectedCategory.branch + ' - ' + selectedCategory.name : 'Kategori belum dipilih'"></p>
                                        <p class="mt-2 text-sm text-slate-300" x-text="selectedCategory ? (selectedCategory.notes || selectedCategory.description || selectedCategory.age_requirement) : ''"></p>
                                        <p class="mt-3 text-xs uppercase tracking-[0.18em]" x-bind:class="formValues.participant_role === 'reserve' ? 'text-emerald-200' : 'text-cyan-200'" x-text="formValues.participant_role === 'reserve' ? 'Status Cadangan Aktif' : 'Status Inti Aktif'"></p>
                                        <p class="mt-2 text-sm font-semibold text-white" x-text="formValues.participant_role === 'reserve' ? 'Peserta ini akan didaftarkan sebagai cadangan.' : 'Peserta ini akan didaftarkan sebagai peserta inti.'"></p>
                                        <template x-if="selectedCategory && selectedCategory.gender_rule === 'paired_two'">
                                            <div class="mt-4 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
                                                Kuota kategori ini menggunakan aturan 1 putra + 1 putri.
                                            </div>
                                        </template>
                                        <template x-if="selectedCategory && selectedCategory.gender_rule === 'putra_two'">
                                            <div class="mt-4 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
                                                Kuota kategori ini menggunakan aturan 2 peserta putra.
                                            </div>
                                        </template>
                                        <template x-if="selectedCategory && selectedCategory.gender_rule === 'putra_three'">
                                            <div class="mt-4 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-100">
                                                Kuota kategori ini menggunakan aturan 3 peserta putra.
                                            </div>
                                        </template>
                                        <template x-if="selectedCategory && selectedCategory.gender_rule === 'putri_three'">
                                            <div class="mt-4 rounded-2xl border border-fuchsia-400/20 bg-fuchsia-400/10 px-4 py-3 text-sm text-fuchsia-100">
                                                Kuota kategori ini menggunakan aturan 3 peserta putri.
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Nama lengkap</label>
                                <input name="name" x-model="formValues.name" type="text" value="<?= e(old('name')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Jenis kelamin</label>
                            <select name="gender" x-model="formValues.gender" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Pilih gender</option>
                                <option value="putra" <?= old('gender') === 'putra' ? 'selected' : '' ?> x-bind:disabled="selectedCategory && selectedCategory.gender_rule === 'putri_three'">Putra</option>
                                <option value="putri" <?= old('gender') === 'putri' ? 'selected' : '' ?> x-bind:disabled="selectedCategory && ['putra_two', 'putra_three'].includes(selectedCategory.gender_rule)">Putri</option>
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
                            <input name="place_of_birth" x-model="formValues.place_of_birth" type="text" value="<?= e(old('place_of_birth')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Tanggal lahir</label>
                            <input name="date_of_birth" x-model="formValues.date_of_birth" type="date" max="2026-07-01" value="<?= e(old('date_of_birth')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            <p class="mt-2 text-xs text-slate-400">Pratinjau Tanggal: <span class="font-semibold text-slate-200" x-text="displayDate('date_of_birth', '01 Januari 2023')"></span></p>
                            <template x-if="ageValidationMessage()">
                                <p class="mt-2 rounded-2xl border border-rose-400/20 bg-rose-400/10 px-3 py-2 text-xs font-semibold text-rose-100" x-text="ageValidationMessage()"></p>
                            </template>
                            <template x-if="!ageValidationMessage() && participantAgeText()">
                                <p class="mt-2 text-xs text-emerald-200" x-text="'Umur per 1 Juli 2026: ' + participantAgeText()"></p>
                            </template>
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
                            <input name="institution" x-model="formValues.institution" type="text" value="<?= e(old('institution')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">No. HP</label>
                            <input name="phone" x-model="formValues.phone" type="text" value="<?= e(old('phone')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor KK</label>
                            <input name="kk_number" x-model="formValues.kk_number" type="text" value="<?= e(old('kk_number')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Tanggal KK</label>
                            <input name="kk_date" x-model="formValues.kk_date" type="date" value="<?= e(old('kk_date')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            <p class="mt-2 text-xs text-slate-400">Pratinjau Tanggal: <span class="font-semibold text-slate-200" x-text="displayDate('kk_date', '01 Januari 2023')"></span></p>
                        </div>

                        <div x-bind:class="isUnderSeventeen() ? 'rounded-2xl border border-cyan-400/20 bg-cyan-400/6 px-4 py-4' : ''">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">NIK <span class="text-xs font-medium text-rose-300">(wajib)</span></label>
                            <input name="nik" x-model="formValues.nik" type="text" value="<?= e(old('nik')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div x-bind:class="isUnderSeventeen() ? 'rounded-2xl border border-cyan-400/20 bg-cyan-400/6 px-4 py-4' : ''">
                            <label class="mb-2 block text-sm font-semibold text-slate-200" x-bind:class="isUnderSeventeen() ? 'text-cyan-100' : ''">Tanggal KTP <span class="text-xs font-medium text-cyan-200" x-show="isUnderSeventeen()">(otomatis opsional)</span></label>
                            <input name="ktp_date" x-model="formValues.ktp_date" type="date" value="<?= e(old('ktp_date')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            <p class="mt-2 text-xs text-slate-400">Pratinjau Tanggal: <span class="font-semibold text-slate-200" x-text="displayDate('ktp_date', '01 Januari 2023')"></span></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Pendidikan terakhir</label>
                            <select name="last_education" x-model="formValues.last_education" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                <option value="">Pilih pendidikan terakhir</option>
                                <?php foreach ($educationOptions as $educationOption): ?>
                                    <option value="<?= e($educationOption) ?>" <?= old('last_education') === $educationOption ? 'selected' : '' ?>><?= e($educationOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nomor rekening <span class="text-xs font-medium text-cyan-200">(opsional)</span></label>
                            <input name="bank_account_number" x-model="formValues.bank_account_number" type="text" value="<?= e(old('bank_account_number')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Bank <span class="text-xs font-medium text-cyan-200">(opsional)</span></label>
                            <input name="bank_name" x-model="formValues.bank_name" type="text" value="<?= e(old('bank_name')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Atas nama rekening <span class="text-xs font-medium text-cyan-200">(opsional)</span></label>
                            <input name="bank_account_name" x-model="formValues.bank_account_name" type="text" value="<?= e(old('bank_account_name')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div class="lg:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Alamat sesuai KTP</label>
                            <textarea name="ktp_address" x-model="formValues.ktp_address" rows="3" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"><?= e(old('ktp_address')) ?></textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kecamatan sesuai KTP</label>
                            <input name="ktp_district" x-model="formValues.ktp_district" type="text" value="<?= e(old('ktp_district')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kabupaten sesuai KTP</label>
                            <input name="ktp_regency" x-model="formValues.ktp_regency" type="text" value="<?= e(old('ktp_regency')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                        </div>

                        <div class="lg:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Alamat saat ini</label>
                            <textarea name="current_address" x-model="formValues.current_address" rows="3" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20"><?= e(old('current_address')) ?></textarea>
                        </div>
                        <div class="lg:col-span-2 mt-2 rounded-[1.4rem] border border-emerald-400/16 bg-emerald-400/8 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">Form Upload</p>
                            <p class="mt-2 text-lg font-bold text-white">Berkas administrasi peserta</p>
                            <p class="mt-1 text-sm text-slate-300">Bagian ini dipisahkan khusus untuk upload dokumen wajib dan opsional.</p>
                        </div>
                        <div class="mt-1">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Upload Pas Foto</label>
                            <input name="photo_document" type="file" accept="image/*" x-on:change="trackPhotoFile($event, 'photo_document')" class="w-full rounded-2xl border border-emerald-400/18 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400/10 file:px-4 file:py-2 file:text-emerald-200">
                            <p class="mt-2 text-xs text-slate-400" x-text="'File terpilih: ' + displayFileName('photo_document', 'Belum ada file dipilih')"></p>
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
                            <template x-if="photoPreviewUrl">
                                <div class="mt-3 overflow-hidden rounded-2xl border border-cyan-400/20 bg-slate-950/70 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Pratinjau Pas Foto</p>
                                    <img x-bind:src="photoPreviewUrl" alt="Pratinjau pas foto" class="mt-3 h-40 w-32 rounded-xl border border-slate-700 object-cover shadow-[0_12px_40px_-20px_rgba(34,211,238,0.45)]">
                                </div>
                            </template>
                        </div>

                        <div class="mt-1">
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Upload Akta Kelahiran</label>
                            <input name="birth_certificate_document" type="file" accept="image/*,.pdf" x-on:change="trackDocumentFile($event, 'birth_certificate_document')" class="w-full rounded-2xl border border-emerald-400/18 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400/10 file:px-4 file:py-2 file:text-emerald-200">
                            <p class="mt-2 text-xs text-slate-400" x-text="'File terpilih: ' + displayFileName('birth_certificate_document', 'Belum ada file dipilih')"></p>
                            <template x-if="documentPreviewUrl('birth_certificate_document')">
                                <div class="mt-3 overflow-hidden rounded-2xl border border-cyan-400/20 bg-slate-950/70 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Pratinjau Akta</p>
                                    <img x-bind:src="documentPreviewUrl('birth_certificate_document')" alt="Pratinjau akta kelahiran" class="mt-3 h-40 w-full rounded-xl border border-slate-700 object-cover">
                                </div>
                            </template>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Upload KK</label>
                            <input name="kk_document" type="file" accept="image/*,.pdf" x-on:change="trackDocumentFile($event, 'kk_document')" class="w-full rounded-2xl border border-emerald-400/18 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400/10 file:px-4 file:py-2 file:text-emerald-200">
                            <p class="mt-2 text-xs text-slate-400" x-text="'File terpilih: ' + displayFileName('kk_document', 'Belum ada file dipilih')"></p>
                            <template x-if="documentPreviewUrl('kk_document')">
                                <div class="mt-3 overflow-hidden rounded-2xl border border-cyan-400/20 bg-slate-950/70 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Pratinjau KK</p>
                                    <img x-bind:src="documentPreviewUrl('kk_document')" alt="Pratinjau KK" class="mt-3 h-40 w-full rounded-xl border border-slate-700 object-cover">
                                </div>
                            </template>
                        </div>

                        <div x-bind:class="isUnderSeventeen() ? 'rounded-2xl border border-cyan-400/20 bg-cyan-400/6 px-4 py-4' : ''">
                            <label class="mb-2 block text-sm font-semibold text-slate-200" x-bind:class="isUnderSeventeen() ? 'text-cyan-100' : ''">Upload KTP <span class="text-xs font-medium text-cyan-200" x-show="isUnderSeventeen()">(otomatis opsional)</span></label>
                            <input name="ktp_document" type="file" accept="image/*,.pdf" x-on:change="trackDocumentFile($event, 'ktp_document')" class="w-full rounded-2xl border border-emerald-400/18 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400/10 file:px-4 file:py-2 file:text-emerald-200">
                            <p class="mt-2 text-xs text-slate-400" x-text="'File terpilih: ' + displayFileName('ktp_document', 'Belum ada file dipilih')"></p>
                            <template x-if="documentPreviewUrl('ktp_document')">
                                <div class="mt-3 overflow-hidden rounded-2xl border border-cyan-400/20 bg-slate-950/70 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Pratinjau KTP</p>
                                    <img x-bind:src="documentPreviewUrl('ktp_document')" alt="Pratinjau KTP" class="mt-3 h-40 w-full rounded-xl border border-slate-700 object-cover">
                                </div>
                            </template>
                        </div>

                        <div x-bind:class="isUnderSeventeen() ? 'rounded-2xl border border-cyan-400/20 bg-cyan-400/6 px-4 py-4' : ''">
                            <label class="mb-2 block text-sm font-semibold text-slate-200" x-bind:class="isUnderSeventeen() ? 'text-cyan-100' : ''">Upload Ijazah Terakhir <span class="text-xs font-medium text-cyan-200" x-show="isUnderSeventeen()">(otomatis opsional)</span></label>
                            <input name="last_diploma_document" type="file" accept="image/*,.pdf" x-on:change="trackDocumentFile($event, 'last_diploma_document')" class="w-full rounded-2xl border border-emerald-400/18 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400/10 file:px-4 file:py-2 file:text-emerald-200">
                            <p class="mt-2 text-xs text-slate-400" x-text="'File terpilih: ' + displayFileName('last_diploma_document', 'Belum ada file dipilih')"></p>
                            <template x-if="documentPreviewUrl('last_diploma_document')">
                                <div class="mt-3 overflow-hidden rounded-2xl border border-cyan-400/20 bg-slate-950/70 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Pratinjau Ijazah</p>
                                    <img x-bind:src="documentPreviewUrl('last_diploma_document')" alt="Pratinjau ijazah terakhir" class="mt-3 h-40 w-full rounded-xl border border-slate-700 object-cover">
                                </div>
                            </template>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Upload Buku Tabungan <span class="text-xs font-medium text-cyan-200">(opsional)</span></label>
                            <input name="bank_book_document" type="file" accept="image/*,.pdf" x-on:change="trackDocumentFile($event, 'bank_book_document')" class="w-full rounded-2xl border border-emerald-400/18 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400/10 file:px-4 file:py-2 file:text-emerald-200">
                            <p class="mt-2 text-xs text-slate-400" x-text="'File terpilih: ' + displayFileName('bank_book_document', 'Belum ada file dipilih')"></p>
                            <template x-if="documentPreviewUrl('bank_book_document')">
                                <div class="mt-3 overflow-hidden rounded-2xl border border-cyan-400/20 bg-slate-950/70 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">Pratinjau Buku Tabungan</p>
                                    <img x-bind:src="documentPreviewUrl('bank_book_document')" alt="Pratinjau buku tabungan" class="mt-3 h-40 w-full rounded-xl border border-slate-700 object-cover">
                                </div>
                            </template>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Upload Piagam (opsional, bisa lebih dari 1)</label>
                            <input name="certificate_documents[]" type="file" multiple accept="image/*,.pdf" x-on:change="trackMultipleFiles($event, 'certificate_documents')" class="w-full rounded-2xl border border-emerald-400/18 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400/10 file:px-4 file:py-2 file:text-emerald-200">
                            <p class="mt-2 text-xs text-slate-400" x-text="'File terpilih: ' + displayMultipleFileNames('certificate_documents', 'Belum ada file dipilih')"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Upload Dokumen Lainnya (opsional, bisa lebih dari 1)</label>
                            <input name="other_documents[]" type="file" multiple accept="image/*,.pdf" x-on:change="trackMultipleFiles($event, 'other_documents')" class="w-full rounded-2xl border border-emerald-400/18 bg-slate-950/80 px-4 py-3 text-slate-300 outline-none file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400/10 file:px-4 file:py-2 file:text-emerald-200">
                            <p class="mt-2 text-xs text-slate-400" x-text="'File terpilih: ' + displayMultipleFileNames('other_documents', 'Belum ada file dipilih')"></p>
                        </div>
                            <div class="lg:col-span-2 flex flex-wrap gap-3">
                                <button type="button" class="secondary-button" x-on:click="goToStep(1)">
                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                    Ganti Kategori
                                </button>
                                <button type="button" class="primary-button" x-bind:disabled="!canProceedToReview()" x-bind:class="!canProceedToReview() ? 'pointer-events-none opacity-50' : ''" x-on:click="goToStep(3)">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    Lanjut ke Review
                                </button>
                            </div>
                        </fieldset>

                        <div class="space-y-5" x-show="currentStep === 3">
                            <div class="rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/95 via-sky-950/70 to-blue-950/60 p-5">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-200">Langkah 3</p>
                                <h4 class="mt-2 text-xl font-bold text-white">Review data pendaftaran</h4>
                                <p class="mt-2 text-sm text-slate-300">Periksa ringkasan berikut sebelum menyimpan draft atau mengirim untuk verifikasi.</p>
                            </div>

                            <div class="rounded-[1.5rem] border px-5 py-4"
                                x-bind:class="isReadyToSubmit() ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-100' : 'border-amber-400/20 bg-amber-400/10 text-amber-100'">
                                <p class="text-sm font-semibold uppercase tracking-[0.18em]">Status Review</p>
                                <template x-if="isReadyToSubmit()">
                                    <p class="mt-2 text-sm">Data inti dan dokumen wajib sudah terisi. Peserta siap dikirim untuk verifikasi.</p>
                                </template>
                                <template x-if="!isReadyToSubmit()">
                                    <div class="mt-2 space-y-2 text-sm">
                                        <p>Masih ada data wajib yang belum lengkap. Anda tetap bisa menyimpan draft, tetapi belum disarankan untuk kirim verifikasi.</p>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="item in missingRequirements()" :key="item">
                                                <span class="inline-flex rounded-full border border-amber-300/20 bg-slate-950/40 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]" x-text="item"></span>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Cabang / Golongan dipilih</p>
                                    <p class="mt-2 text-lg font-bold text-white" x-text="selectedCategory ? selectedCategory.branch + ' - ' + selectedCategory.name : '-'"></p>
                                    <p class="mt-2 text-sm text-slate-300" x-text="selectedCategory ? (selectedCategory.notes || selectedCategory.description || selectedCategory.age_requirement) : ''"></p>
                                </div>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Checklist berkas</p>
                                    <div class="mt-3 space-y-2 text-sm text-slate-300">
                                        <p x-bind:class="hasFile('photo_document') && photoRatioValid() ? 'text-emerald-200' : 'text-amber-100'" x-text="'Foto: ' + displayFileName('photo_document', 'belum dipilih')"></p>
                                        <p x-bind:class="hasFile('birth_certificate_document') ? 'text-emerald-200' : 'text-amber-100'" x-text="'Akta kelahiran: ' + displayFileName('birth_certificate_document', 'belum dipilih')"></p>
                                        <p x-bind:class="hasFile('kk_document') ? 'text-emerald-200' : 'text-amber-100'" x-text="'KK: ' + displayFileName('kk_document', 'belum dipilih')"></p>
                                        <p x-text="'KTP: ' + displayFileName('ktp_document', 'opsional / belum dipilih')"></p>
                                        <p x-show="photoPreviewUrl" x-bind:class="photoRatioValid() ? 'text-emerald-200' : 'text-rose-200'" x-text="'Rasio pas foto: ' + photoRatioStatusLabel()"></p>
                                        <p x-bind:class="hasFile('last_diploma_document') ? 'text-emerald-200' : 'text-amber-100'" x-text="'Ijazah terakhir: ' + displayFileName('last_diploma_document', 'belum dipilih')"></p>
                                        <p x-bind:class="hasFile('bank_book_document') ? 'text-emerald-200' : 'text-amber-100'" x-text="'Buku tabungan: ' + displayFileName('bank_book_document', 'belum dipilih')"></p>
                                        <p x-text="'Piagam: ' + displayMultipleFileNames('certificate_documents', 'opsional / belum dipilih')"></p>
                                        <p x-text="'Dokumen lainnya: ' + displayMultipleFileNames('other_documents', 'opsional / belum dipilih')"></p>
                                    </div>
                                </div>
                                <div class="data-card" x-show="documentPreviewUrl('kk_document') || documentPreviewUrl('ktp_document') || documentPreviewUrl('birth_certificate_document') || documentPreviewUrl('last_diploma_document') || documentPreviewUrl('bank_book_document')">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Pratinjau Dokumen Gambar</p>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
                                        <template x-if="documentPreviewUrl('kk_document')">
                                            <img x-bind:src="documentPreviewUrl('kk_document')" alt="Pratinjau KK review" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                        <template x-if="documentPreviewUrl('ktp_document')">
                                            <img x-bind:src="documentPreviewUrl('ktp_document')" alt="Pratinjau KTP review" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                        <template x-if="documentPreviewUrl('birth_certificate_document')">
                                    <img x-bind:src="documentPreviewUrl('birth_certificate_document')" alt="Pratinjau akta review" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                        <template x-if="documentPreviewUrl('last_diploma_document')">
                                            <img x-bind:src="documentPreviewUrl('last_diploma_document')" alt="Pratinjau ijazah review" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                        <template x-if="documentPreviewUrl('bank_book_document')">
                                            <img x-bind:src="documentPreviewUrl('bank_book_document')" alt="Pratinjau buku tabungan review" class="h-28 w-full rounded-xl border border-slate-700 object-cover">
                                        </template>
                                    </div>
                                </div>
                                <div class="data-card" x-show="photoPreviewUrl">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Pratinjau Pas Foto</p>
                                    <img x-bind:src="photoPreviewUrl" alt="Pratinjau pas foto review" class="mt-3 h-44 w-36 rounded-2xl border border-slate-700 object-cover">
                                    <p class="mt-3 text-xs" x-bind:class="photoRatioValid() ? 'text-emerald-200' : 'text-rose-200'" x-text="photoRatioStatusLabel()"></p>
                                </div>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Identitas inti</p>
                                    <div class="mt-3 space-y-2 text-sm text-slate-300">
                                        <p x-bind:class="fieldValue('name') ? '' : 'text-amber-100'"><span class="text-slate-500">Nama:</span> <span x-text="fieldValue('name') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('gender') ? '' : 'text-amber-100'"><span class="text-slate-500">Gender:</span> <span x-text="fieldValue('gender') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('participant_role') ? '' : 'text-amber-100'"><span class="text-slate-500">Jenis peserta:</span> <span x-text="fieldValue('participant_role') === 'reserve' ? 'Cadangan' : 'Inti'"></span></p>
                                        <p x-bind:class="fieldValue('place_of_birth') ? '' : 'text-amber-100'"><span class="text-slate-500">Tempat lahir:</span> <span x-text="fieldValue('place_of_birth') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('date_of_birth') ? '' : 'text-amber-100'"><span class="text-slate-500">Tanggal lahir:</span> <span x-text="displayDate('date_of_birth', '-')"></span></p>
                                        <p x-bind:class="fieldValue('institution') ? '' : 'text-amber-100'"><span class="text-slate-500">Asal lembaga:</span> <span x-text="fieldValue('institution') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('phone') ? '' : 'text-amber-100'"><span class="text-slate-500">No. HP:</span> <span x-text="fieldValue('phone') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('kk_number') ? '' : 'text-amber-100'"><span class="text-slate-500">No. KK:</span> <span x-text="fieldValue('kk_number') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('kk_date') ? '' : 'text-amber-100'"><span class="text-slate-500">Tanggal KK:</span> <span x-text="displayDate('kk_date', '-')"></span></p>
                                        <p x-bind:class="fieldValue('nik') ? '' : 'text-amber-100'"><span class="text-slate-500">NIK:</span> <span x-text="fieldValue('nik') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('ktp_date') ? '' : 'text-amber-100'"><span class="text-slate-500">Tanggal KTP:</span> <span x-text="displayDate('ktp_date', '-')"></span></p>
                                        <p x-bind:class="fieldValue('last_education') ? '' : 'text-amber-100'"><span class="text-slate-500">Pendidikan:</span> <span x-text="fieldValue('last_education') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('bank_account_number') ? '' : 'text-amber-100'"><span class="text-slate-500">No. Rek:</span> <span x-text="fieldValue('bank_account_number') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('bank_name') ? '' : 'text-amber-100'"><span class="text-slate-500">Bank:</span> <span x-text="fieldValue('bank_name') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('bank_account_name') ? '' : 'text-amber-100'"><span class="text-slate-500">A.n. Rek:</span> <span x-text="fieldValue('bank_account_name') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('ktp_address') ? '' : 'text-amber-100'"><span class="text-slate-500">Alamat KTP:</span> <span x-text="fieldValue('ktp_address') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('ktp_district') ? '' : 'text-amber-100'"><span class="text-slate-500">Kecamatan KTP:</span> <span x-text="fieldValue('ktp_district') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('ktp_regency') ? '' : 'text-amber-100'"><span class="text-slate-500">Kabupaten KTP:</span> <span x-text="fieldValue('ktp_regency') || '-'"></span></p>
                                        <p x-bind:class="fieldValue('current_address') ? '' : 'text-amber-100'"><span class="text-slate-500">Alamat saat ini:</span> <span x-text="fieldValue('current_address') || '-'"></span></p>
                                    </div>
                                </div>
                                <div class="data-card">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Kesiapan submit</p>
                                    <div class="mt-3 space-y-2 text-sm text-slate-300">
                                        <p>Pastikan identitas sudah sesuai dokumen resmi.</p>
                                        <p>Pastikan kecamatan, cabang, dan golongan sudah benar.</p>
                                        <p>Pastikan dokumen wajib sudah dipilih sebelum kirim verifikasi.</p>
                                        <p>Pastikan pas foto mengikuti rasio 3:4 dan tampil jelas.</p>
                                    </div>
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
                                <button type="submit" name="submit_action" value="submitted" class="primary-button" x-bind:disabled="!isReadyToSubmit()" x-bind:class="!isReadyToSubmit() ? 'pointer-events-none opacity-50' : ''">
                                    <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                    Kirim untuk Verifikasi
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="mt-6 rounded-[1.5rem] border border-slate-700/80 bg-slate-950/60 p-5 text-sm leading-6 text-slate-300">
                            Pendaftaran peserta untuk official sedang ditutup oleh admin. Kamu masih bisa melihat kategori dan kuota, tetapi form kirim data tidak tersedia untuk sementara.
                        </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <script>
        function participantRegistrationFlow(initialState) {
            const initialCategory = (initialState.categories ?? []).find((category) => category.id === (initialState.selectedCategoryId ?? '')) ?? null;
            return {
                selectedCategoryId: initialState.selectedCategoryId ?? '',
                selectedBranch: initialCategory ? (initialCategory.branch || '') : '',
                currentStep: (initialState.selectedCategoryId ?? '') ? 2 : 1,
                searchTerm: '',
                categories: initialState.categories ?? [],
                branches: initialState.branches ?? [],
                fileNames: {},
                multipleFileNames: {},
                documentPreviewUrls: {},
                photoPreviewUrl: '',
                photoProcessing: false,
                photoAdjusted: false,
                photoMeta: {
                    width: 0,
                    height: 0,
                    ratioValid: false,
                    measured: false,
                },
                formValues: {
                    name: <?= json_encode((string) old('name'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    participant_role: <?= json_encode((string) old('participant_role', 'main'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    gender: <?= json_encode((string) old('gender'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    nik: <?= json_encode((string) old('nik'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    ktp_date: <?= json_encode((string) old('ktp_date'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    phone: <?= json_encode((string) old('phone'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    place_of_birth: <?= json_encode((string) old('place_of_birth'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    date_of_birth: <?= json_encode((string) old('date_of_birth'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    kk_number: <?= json_encode((string) old('kk_number'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    kk_date: <?= json_encode((string) old('kk_date'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    institution: <?= json_encode((string) old('institution'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    last_education: <?= json_encode((string) old('last_education'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    bank_name: <?= json_encode((string) old('bank_name'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    bank_account_number: <?= json_encode((string) old('bank_account_number'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    bank_account_name: <?= json_encode((string) old('bank_account_name'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    current_address: <?= json_encode((string) old('current_address'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    ktp_address: <?= json_encode((string) old('ktp_address'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    ktp_district: <?= json_encode((string) old('ktp_district'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                    ktp_regency: <?= json_encode((string) old('ktp_regency'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                },
                get selectedCategory() {
                    return this.categories.find((category) => category.id === this.selectedCategoryId) ?? null;
                },
                goToStep(step) {
                    if (step === 2 && !this.selectedCategoryId) {
                        this.currentStep = 1;
                        return;
                    }
                    if (step === 3 && !this.canProceedToReview()) {
                        this.currentStep = 2;
                        return;
                    }
                    this.currentStep = step;
                    const anchors = {
                        1: null,
                        2: 'form-pendaftaran',
                        3: 'form-pendaftaran',
                    };
                    const targetId = anchors[step];
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
                hasVisibleBranches() {
                    const search = this.normalizedSearch();
                    if (!search) {
                        return this.branches.length > 0;
                    }
                    return this.branches.some((branch) => (branch.branch || '').toLowerCase().includes(search));
                },
                hasVisibleStandaloneCategories() {
                    const standalone = this.categories.filter((category) => !category.branch);
                    const search = this.normalizedSearch();
                    if (!search) {
                        return standalone.length > 0;
                    }
                    return standalone.some((category) => `${category.name} ${category.notes} ${category.description}`.toLowerCase().includes(search));
                },
                hasVisibleCategoriesInSelectedBranch() {
                    const search = this.normalizedSearch();
                    const scoped = this.categories.filter((category) => category.branch === this.selectedBranch);
                    if (!search) {
                        return scoped.length > 0;
                    }
                    return scoped.some((category) => `${category.branch} ${category.name} ${category.notes} ${category.description}`.toLowerCase().includes(search));
                },
                fieldValue(name) {
                    return (this.formValues[name] || '').trim();
                },
                parseDateParts(value) {
                    if (!value) {
                        return null;
                    }

                    const [year, month, day] = value.split('-').map(Number);

                    if (!year || !month || !day) {
                        return null;
                    }

                    return { year, month, day };
                },
                buildIsoDate(year, month, day) {
                    return `${year.toString().padStart(4, '0')}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                },
                displayDate(name, fallback = '') {
                    const parts = this.parseDateParts(this.fieldValue(name));

                    if (!parts) {
                        return fallback;
                    }

                    return new Intl.DateTimeFormat('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric',
                    }).format(new Date(parts.year, parts.month - 1, parts.day)).replace(/\./g, '');
                },
                hasFile(name) {
                    if (this.fileNames[name]) {
                        return true;
                    }

                    const form = this.$refs.registrationForm;
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
                    this.photoProcessing = false;
                    this.photoAdjusted = false;
                    this.photoMeta = {
                        width: 0,
                        height: 0,
                        ratioValid: false,
                        measured: false,
                    };

                    if (!file) {
                        if (this.photoPreviewUrl) {
                            URL.revokeObjectURL(this.photoPreviewUrl);
                        }
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
                    const form = this.$refs.registrationForm;
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
                requiresAdultIdentityFields() {
                    const age = this.participantAge();
                    return !!age && !age.future && age.years >= 17;
                },
                photoRatioStatusLabel() {
                    if (!this.hasFile('photo_document')) {
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
                parseAgeRule(requirement, marker = null) {
                    const text = (requirement || '').toLowerCase();
                    const source = marker ? (text.match(new RegExp(`${marker}\\s+(\\d+)\\s*tahun(?:\\s+(\\d+)\\s*bulan)?(?:\\s+(\\d+)\\s*hari)?`, 'u')) || []) : (text.match(/(\d+)\s*tahun(?:\s+(\d+)\s*bulan)?(?:\s+(\d+)\s*hari)?/u) || []);

                    if (!source.length) {
                        return null;
                    }

                    return {
                        years: Number(source[1] || 0),
                        months: Number(source[2] || 0),
                        days: Number(source[3] || 0),
                    };
                },
                selectedAgeRules() {
                    const requirement = this.selectedCategory ? (this.selectedCategory.age_requirement || '') : '';
                    const text = requirement.toLowerCase();

                    return {
                        min: text.includes('minimal') ? this.parseAgeRule(requirement, 'minimal') : null,
                        max: text.includes('maksimal') ? this.parseAgeRule(requirement, 'maksimal') : this.parseAgeRule(requirement),
                    };
                },
                normalizedAgeRules() {
                    const rules = this.selectedAgeRules();

                    return {
                        min: rules.min || (rules.max ? { years: 1, months: 0, days: 0 } : null),
                        max: rules.max,
                    };
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
                compareAge(age, rule) {
                    for (const key of ['years', 'months', 'days']) {
                        const actual = Number(age?.[key] || 0);
                        const expected = Number(rule?.[key] || 0);

                        if (actual > expected) {
                            return 1;
                        }

                        if (actual < expected) {
                            return -1;
                        }
                    }

                    return 0;
                },
                formatAge(age) {
                    if (!age) {
                        return '';
                    }

                    return [
                        age.years ? `${age.years} tahun` : '',
                        age.months ? `${age.months} bulan` : '',
                        age.days ? `${age.days} hari` : '',
                    ].filter(Boolean).join(' ') || '0 hari';
                },
                participantAgeText() {
                    const age = this.participantAge();
                    return age && !age.future ? this.formatAge(age) : '';
                },
                isUnderSeventeen() {
                    const age = this.participantAge();
                    return !!age && !age.future && age.years < 17;
                },
                requiresAdultIdentityFields() {
                    return !this.isUnderSeventeen();
                },
                ageValidationMessage() {
                    if (!this.selectedCategory || !this.fieldValue('date_of_birth')) {
                        return '';
                    }

                    const age = this.participantAge();
                    const rules = this.normalizedAgeRules();

                    if (!age) {
                        return '';
                    }

                    if (age.future) {
                        return 'Tanggal lahir tidak boleh melebihi tanggal acuan 1 Juli 2026.';
                    }

                    if (rules.min && this.compareAge(age, rules.min) < 0) {
                        return `Umur peserta per 1 Juli 2026 adalah ${this.formatAge(age)}, belum memenuhi batas minimal ${this.formatAge(rules.min)} untuk golongan ini.`;
                    }

                    if (rules.max && this.compareAge(age, rules.max) > 0) {
                        return `Umur peserta per 1 Juli 2026 adalah ${this.formatAge(age)}, melebihi batas maksimal ${this.formatAge(rules.max)} untuk golongan ini.`;
                    }

                    return '';
                },
                canProceedToReview() {
                    return this.selectedCategoryId && !this.ageValidationMessage();
                },
                missingRequirements() {
                    const requiresAdultIdentityFields = this.requiresAdultIdentityFields();
                    const requiredFields = [
                        ['name', 'Nama lengkap'],
                        ['gender', 'Gender'],
                        ['phone', 'No. HP'],
                        ['place_of_birth', 'Tempat lahir'],
                        ['date_of_birth', 'Tanggal lahir'],
                        ['kk_number', 'Nomor KK'],
                        ['kk_date', 'Tanggal KK'],
                        ['institution', 'Asal lembaga'],
                        ['last_education', 'Pendidikan terakhir'],
                        ['bank_name', 'Bank'],
                        ['bank_account_number', 'Nomor rekening'],
                        ['bank_account_name', 'Atas nama rekening'],
                        ['current_address', 'Alamat saat ini'],
                        ['ktp_address', 'Alamat sesuai KTP'],
                        ['ktp_district', 'Kecamatan KTP'],
                        ['ktp_regency', 'Kabupaten KTP'],
                    ];
                    const missing = [];

                    requiredFields.forEach(([name, label]) => {
                        if (!this.fieldValue(name)) {
                            missing.push(label);
                        }
                    });

                    if (requiresAdultIdentityFields) {
                        if (!this.fieldValue('nik')) {
                            missing.push('NIK');
                        }

                        if (!this.fieldValue('ktp_date')) {
                            missing.push('Tanggal KTP');
                        }

                        if (!this.hasFile('ktp_document')) {
                            missing.push('Dokumen KTP');
                        }

                        if (!this.hasFile('last_diploma_document')) {
                            missing.push('Ijazah terakhir');
                        }
                    }

                    if (!this.hasFile('kk_document')) {
                        missing.push('Dokumen KK');
                    }

                    if (!this.hasFile('birth_certificate_document')) {
                        missing.push('Akta kelahiran');
                    }

                    if (!this.hasFile('photo_document')) {
                        missing.push('Pas foto');
                    } else if (!this.photoRatioValid()) {
                        missing.push('Rasio pas foto 3:4');
                    }

                    if (this.ageValidationMessage()) {
                        missing.push('Umur tidak sesuai Juknis');
                    }

                    return missing;
                },
                isReadyToSubmit() {
                    return this.canProceedToReview() && this.missingRequirements().length === 0;
                },
            };
        }
    </script>
</body>
</html>
