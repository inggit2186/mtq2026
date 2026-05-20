<?php
require_once __DIR__.'/../partials/icon.php';

$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$documentConfig = $documentConfig ?? [];
$stats = $stats ?? [];
$featuredBranches = $featuredBranches ?? collect();
$announcements = $announcements ?? collect();
$featuredSchedules = $featuredSchedules ?? collect();
$timeline = $timeline ?? collect();
$competitionVenues = $competitionVenues ?? collect();
$galleryImages = $galleryImages ?? null;
$coverSlides = $coverSlides ?? collect();
$featuredParticipants = $featuredParticipants ?? collect();
$featuredParticipantsList = $featuredParticipants->values();
$featuredParticipantSlides = $featuredParticipantsList->chunk(5)->values();
$eventTitle = $documentConfig['event_title'] ?? config('app.name', 'e-MTQ');
$organizationName = $documentConfig['organization_name'] ?? 'e-MTQ';
$eventLocation = $documentConfig['event_location'] ?? config('juknis.host', 'Tanah Datar');
$host = config('juknis.host', $eventLocation);
$registration = config('juknis.registration', []);
$primaryCtaHref = auth()->check() ? route('dashboard') : route('login');
$primaryCtaLabel = auth()->check() ? 'Masuk ke Dashboard' : 'Masuk ke Portal';
$competitionVenueStats = [
    'total' => $competitionVenues->count(),
    'masjid' => $competitionVenues->where('kind', 'Masjid')->count(),
    'sekolah' => $competitionVenues->where('kind', 'Sekolah')->count(),
    'komunitas' => $competitionVenues->where('kind', 'Lapangan / Komunitas')->count(),
];
$featuredVenue = $competitionVenues->firstWhere('no', 2) ?? $competitionVenues->first();
$galleryModalItems = ($galleryImages && method_exists($galleryImages, 'getCollection'))
    ? $galleryImages->getCollection()->values()->all()
    : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app.name', 'e-MTQ')) ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <main class="relative isolate overflow-hidden">
        <div class="hero-orb hero-orb-cyan left-[-8rem] top-12 h-72 w-72 animate-[pulse_7s_ease-in-out_infinite]"></div>
        <div class="hero-orb hero-orb-blue right-[-10rem] top-24 h-96 w-96 animate-[pulse_9s_ease-in-out_infinite]"></div>
        <div class="hero-orb hero-orb-cyan bottom-24 right-[20%] h-64 w-64 opacity-40"></div>

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <header class="glass-card rounded-[2rem] px-5 py-4 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-[1.25rem] border border-cyan-200/30 bg-transparent p-2 shadow-[0_18px_40px_-24px_rgba(125,211,252,0.45)]">
                            <img src="<?= e(asset('images/emtq-resmi.webp')) ?>" alt="Logo resmi e-MTQ" class="h-full w-full object-contain">
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200"><?= e($organizationName) ?></p>
                            <h1 class="mt-1 text-xl font-black tracking-tight text-white sm:text-2xl"><?= e($eventTitle) ?></h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="badge-live"><?= mtq_icon('eye', 'h-4 w-4') ?> Halaman Publik</div>
                        <a href="<?= e($primaryCtaHref) ?>" class="primary-button">
                            <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                            <?= e($primaryCtaLabel) ?>
                        </a>
                    </div>
                </div>
            </header>

            <?php if ($coverSlides->isNotEmpty()): ?>
                <section class="mt-6 overflow-hidden rounded-[2.5rem] border border-cyan-400/12 bg-slate-950/70 shadow-[0_30px_100px_-45px_rgba(14,165,233,0.45)]">
                    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
                        <div
                            class="relative overflow-hidden rounded-[2.2rem] border border-white/10 bg-slate-900/50 shadow-[0_18px_45px_-28px_rgba(15,23,42,0.35)] min-h-[460px] lg:min-h-[520px]"
                            x-data="{
                                active: 0,
                            slides: <?= e($coverSlides->count()) ?>,
                                timer: null,
                                init() {
                                    if (this.slides > 1) {
                                        this.timer = setInterval(() => { this.active = (this.active + 1) % this.slides }, 4500);
                                    }
                                },
                                go(index) {
                                    this.active = index;
                                },
                                prev() {
                                    this.active = (this.active - 1 + this.slides) % this.slides;
                                },
                                next() {
                                    this.active = (this.active + 1) % this.slides;
                                }
                            }"
                            x-init="init()"
                        >
                            <?php foreach ($coverSlides as $index => $image): ?>
                                <figure
                                    x-cloak
                                    x-show="active === <?= $index ?>"
                                    x-transition.opacity.duration.700ms
                                    class="absolute inset-0"
                                >
                                    <img src="<?= e($image['src']) ?>" alt="<?= e($image['label']) ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" class="mtq-cover-image h-full w-full object-cover object-center">
                                    <div class="mtq-cover-overlay"></div>
                                    <figcaption class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-4 p-5">
                                        <div class="max-w-[70%]">
                                            <div class="inline-flex rounded-full border border-white/10 bg-white/12 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/90">
                                                <?= e($image['label']) ?>
                                            </div>
                                            <?php if (! empty($image['caption'])): ?>
                                                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-100/90"><?= e($image['caption']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="hidden rounded-full border border-white/10 bg-white/8 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-white/80 sm:inline-flex">
                                            <?= e(($index + 1) . '/' . $coverSlides->count()) ?>
                                        </div>
                                    </figcaption>
                                </figure>
                            <?php endforeach; ?>

                            <?php if ($coverSlides->count() > 1): ?>
                                <button type="button" class="absolute left-4 top-4 z-10 rounded-full border border-white/10 bg-slate-950/55 p-2 text-white backdrop-blur transition hover:bg-slate-950/80" x-on:click="prev()" aria-label="Sebelumnya">
                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                </button>
                                <button type="button" class="absolute right-4 top-4 z-10 rounded-full border border-white/10 bg-slate-950/55 p-2 text-white backdrop-blur transition hover:bg-slate-950/80" x-on:click="next()" aria-label="Berikutnya">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                </button>

                                <div class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 gap-2 rounded-full border border-white/10 bg-slate-950/45 px-3 py-2 backdrop-blur">
                                    <?php foreach ($coverSlides as $index => $image): ?>
                                        <button
                                            type="button"
                                            class="h-2.5 w-2.5 rounded-full transition"
                                            x-on:click="go(<?= $index ?>)"
                                            x-bind:class="active === <?= $index ?> ? 'bg-cyan-300' : 'bg-white/40 hover:bg-white/70'"
                                            aria-label="Lihat slide <?= e($index + 1) ?>"
                                        ></button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                            <div class="flex flex-col justify-between gap-6">
                                <div class="space-y-5">
                                    <div class="badge-live w-fit"><?= mtq_icon('bell', 'h-4 w-4') ?> Informasi lomba untuk audience, kafilah, dan panitia</div>

                                    <div class="space-y-5">
                                        <h2 class="max-w-4xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
                                            Satu halaman utama untuk mengikuti <span class="text-gradient">MTQ</span> dengan lebih mudah.
                                        </h2>
                                        <p class="max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                                            Pantau jadwal utama, pengumuman terbaru, lokasi lomba, dan cabang yang ditampilkan dalam satu halaman yang rapi.
                                        </p>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-3">
                                        <div class="metric-card">
                                            <p class="text-sm text-slate-400">Lokasi penyelenggaraan</p>
                                            <p class="mt-3 text-2xl font-extrabold text-white"><?= e($eventLocation) ?></p>
                                            <p class="mt-2 text-sm text-slate-300">Tuan rumah: <?= e($host) ?></p>
                                        </div>
                                        <div class="metric-card">
                                            <p class="text-sm text-slate-400">Cabang dan golongan</p>
                                            <p class="mt-3 text-2xl font-extrabold text-white"><?= e($stats['branches'] ?? 0) ?> cabang</p>
                                            <p class="mt-2 text-sm text-slate-300"><?= e($stats['categories'] ?? 0) ?> kategori siap ditampilkan</p>
                                        </div>
                                        <div class="metric-card">
                                            <p class="text-sm text-slate-400">Aktivitas sistem</p>
                                            <p class="mt-3 text-2xl font-extrabold text-white"><?= e($stats['announcements'] ?? 0) ?> info</p>
                                            <p class="mt-2 text-sm text-slate-300"><?= e($stats['participants'] ?? 0) ?> data peserta terdaftar</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-3">
                                        <a href="#agenda-utama" class="primary-button">
                                            <?= mtq_icon('calendar', 'h-4 w-4') ?>
                                            Lihat Agenda Utama
                                        </a>
                                        <a href="#lokasi-lomba" class="secondary-button">
                                            <?= mtq_icon('map-pin', 'h-4 w-4') ?>
                                            Lokasi Lomba
                                        </a>
                                        <a href="#pengumuman-terbaru" class="secondary-button">
                                            <?= mtq_icon('bell', 'h-4 w-4') ?>
                                            Cek Pengumuman
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4">
                                <div id="agenda-utama" class="glass-card rounded-[2rem] p-6 sm:p-7">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="section-kicker">Agenda Utama</p>
                                            <h3 class="mt-2 text-2xl font-bold text-white">Sorotan sesi yang paling dekat</h3>
                                        </div>
                                        <div class="status-pill">
                                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                                            Siap digunakan
                                        </div>
                                    </div>

                                    <div class="mt-6 space-y-3">
                                        <?php if ($featuredSchedules->isEmpty()): ?>
                                            <?php foreach ($timeline as $item): ?>
                                                <div class="data-card">
                                                    <div class="flex items-start gap-4">
                                                        <div class="icon-chip"><?= mtq_icon('calendar') ?></div>
                                                        <div class="min-w-0">
                                                            <p class="font-semibold text-white"><?= e($item['activity'] ?? 'Agenda MTQ') ?></p>
                                                            <p class="mt-2 text-sm text-cyan-200"><?= e($item['date'] ?? '-') ?> | <?= e($item['time'] ?? '-') ?></p>
                                                            <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($item['notes'] ?? 'Informasi detail akan diumumkan panitia.') ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php foreach ($featuredSchedules as $schedule): ?>
                                                <?php
                                                $status = (string) ($schedule->status ?? 'scheduled');
                                                $statusLabel = match ($status) {
                                                    'ongoing' => 'Sedang berlangsung',
                                                    'completed' => 'Selesai',
                                                    'postponed' => 'Ditunda',
                                                    default => 'Terjadwal',
                                                };
                                                $statusClasses = match ($status) {
                                                    'ongoing' => 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200',
                                                    'completed' => 'border-slate-500/30 bg-slate-500/10 text-slate-200',
                                                    'postponed' => 'border-rose-400/20 bg-rose-400/10 text-rose-100',
                                                    default => 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200',
                                                };
                                                ?>
                                                <div class="data-card">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div class="min-w-0">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <p class="font-semibold text-white"><?= e($schedule->title) ?></p>
                                                                <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] <?= e($statusClasses) ?>"><?= e($statusLabel) ?></span>
                                                            </div>
                                                            <p class="mt-2 text-sm text-slate-300"><?= e($schedule->stage ?: 'Sesi utama') ?><?php if ($schedule->venue): ?> | <?= e($schedule->venue) ?><?php endif; ?></p>
                                                            <p class="mt-2 text-sm text-cyan-200"><?= e(optional($schedule->starts_at)->format('d M Y H:i') ?? '-') ?><?php if ($schedule->ends_at): ?> - <?= e(optional($schedule->ends_at)->format('H:i')) ?><?php endif; ?></p>
                                                            <?php if ($schedule->notes): ?>
                                                                <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($schedule->notes) ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="icon-chip h-12 w-12 shrink-0"><?= mtq_icon('clock') ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="glass-card rounded-[2rem] p-6 sm:p-7">
                                    <p class="section-kicker">Ringkasan Publik</p>
                                    <h3 class="mt-2 text-2xl font-bold text-white">Informasi inti yang mudah dipindai</h3>
                                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                        <div class="data-card">
                                            <p class="text-sm text-slate-400">Pendaftaran</p>
                                            <p class="mt-2 text-lg font-semibold text-white"><?= e($registration['open'] ?? '-') ?> - <?= e($registration['close'] ?? '-') ?></p>
                                        </div>
                                        <div class="data-card">
                                            <p class="text-sm text-slate-400">Pengumuman administrasi</p>
                                            <p class="mt-2 text-lg font-semibold text-white"><?= e($registration['announcement'] ?? '-') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

                        <section id="lokasi-lomba" class="relative mt-6 overflow-hidden rounded-[2.5rem] border border-cyan-400/12 bg-gradient-to-br from-slate-950/95 via-slate-900/88 to-sky-950/25 p-5 sm:p-6 lg:p-8">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-300/50 to-transparent"></div>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="section-kicker">Lokasi Musabaqah</p>
                        <h3 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">Lokasi Lomba MTQ ke 43 di Kecamatan Pariangan</h3>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-300">Venue lomba ditampilkan dalam bentuk slideshow agar halaman tetap rapi, sambil tetap memberi akses cepat ke foto, cabang, dan peta lokasi.</p>
                    </div>
                    <div class="badge-live"><?= mtq_icon('map-pin', 'h-4 w-4') ?> <?= e($competitionVenueStats['total']) ?> venue aktif</div>
                </div>

                <div
                    class="mt-6 space-y-6"
                    x-data="{
                        active: <?= e((int) ($initialVenueIndex ?? 0)) ?>,
                        venues: <?= e(json_encode($competitionVenues->values()->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>,
                        timer: null,
                        get current() {
                            return this.venues[this.active] || null;
                        },
                        init() {
                            if (this.venues.length > 1) {
                                this.timer = setInterval(() => {
                                    this.active = (this.active + 1) % this.venues.length;
                                }, 5200);
                            }
                        },
                        go(index) {
                            this.active = index;
                        },
                        prev() {
                            if (!this.venues.length) return;
                            this.active = (this.active - 1 + this.venues.length) % this.venues.length;
                        },
                        next() {
                            if (!this.venues.length) return;
                            this.active = (this.active + 1) % this.venues.length;
                        }
                    }"
                    x-init="init()"
                >
                    <?php if ($featuredVenue): ?>
                        <div class="relative overflow-hidden rounded-[2.25rem] border border-white/10 bg-slate-950/72 shadow-[0_24px_70px_-40px_rgba(14,165,233,0.42)]">
                            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-400 via-sky-400 to-blue-500"></div>
                            <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-cyan-400/10 blur-3xl"></div>
                            <div class="grid gap-0 lg:grid-cols-[0.8fr_1.2fr]">
                                <div class="relative p-5 sm:p-6 lg:p-7">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="section-kicker">Slideshow Venue</p>
                                        <span class="status-pill border-white/10 bg-white/5 text-slate-200" x-text="current ? ('No. ' + String(current.no).padStart(2, '0')) : 'No. 02'"></span>
                                    </div>
                                    <h4 class="mt-3 text-2xl font-black text-white sm:text-[2.15rem]" x-text="current ? current.venue : '<?= e($featuredVenue['venue']) ?>'"></h4>
                                    <p class="mt-3 max-w-xl text-sm leading-7 text-slate-300" x-text="current ? current.cabang : '<?= e($featuredVenue['cabang']) ?>'"></p>

                                    <div class="mt-4 flex flex-wrap gap-2" x-cloak x-show="current && current.category_labels && current.category_labels.length">
                                        <template x-for="label in (current ? current.category_labels.slice(0, 3) : [])" :key="label">
                                            <span class="rounded-full border border-cyan-300/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100" x-text="label"></span>
                                        </template>
                                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-200" x-show="current && current.category_labels && current.category_labels.length > 3" x-text="'+' + (current.category_labels.length - 3) + ' golongan'"></span>
                                    </div>

                                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-[1.2rem] border border-white/8 bg-white/5 p-3.5">
                                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">No</p>
                                            <p class="mt-2 text-sm font-semibold text-white" x-text="current ? String(current.no).padStart(2, '0') : '02'"></p>
                                        </div>
                                        <div class="rounded-[1.2rem] border border-white/8 bg-white/5 p-3.5">
                                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Jenis venue</p>
                                            <p class="mt-2 text-sm font-semibold text-white" x-text="current ? current.kind : '<?= e($featuredVenue['kind']) ?>'"></p>
                                        </div>
                                        <div class="rounded-[1.2rem] border border-white/8 bg-white/5 p-3.5">
                                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Total lokasi</p>
                                            <p class="mt-2 text-sm font-semibold text-white"><?= e($competitionVenueStats['total']) ?> titik</p>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex flex-wrap gap-3">
                                        <a href="<?= e($featuredVenue['map_url']) ?>" target="_blank" rel="noreferrer" class="primary-button" x-bind:href="current ? current.map_url : '<?= e($featuredVenue['map_url']) ?>'">
                                            <?= mtq_icon('link-external', 'h-4 w-4') ?>
                                            Buka Google Maps
                                        </a>
                                        <a href="#lokasi-ringan" class="secondary-button">
                                            <?= mtq_icon('map-pin', 'h-4 w-4') ?>
                                            Lihat detail
                                        </a>
                                    </div>
                                </div>

                                <figure x-data="{ loaded: false }" x-init="$nextTick(() => { loaded = $refs.heroImage ? $refs.heroImage.complete : false })" class="relative min-h-[280px] overflow-hidden lg:min-h-[410px]">
                                    <div x-cloak x-show="!loaded" class="absolute inset-0 animate-pulse bg-[linear-gradient(110deg,rgba(15,23,42,0.9)_8%,rgba(30,41,59,0.95)_18%,rgba(15,23,42,0.9)_33%)] bg-[length:200%_100%]"></div>
                                    <img
                                        x-ref="heroImage"
                                        x-bind:src="current ? current.photo_url : '<?= e($featuredVenue['photo_url'] ?? '') ?>'"
                                        x-bind:alt="current ? current.venue : '<?= e($featuredVenue['venue']) ?>'"
                                        loading="eager"
                                        fetchpriority="high"
                                        decoding="async"
                                        sizes="(min-width: 1024px) 58vw, 100vw"
                                        x-bind:srcset="current ? (current.photo_url + ' 1200w') : '<?= e($featuredVenue['photo_url'] ?? '') ?> 1200w'"
                                        x-on:load="loaded = true"
                                        x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                                        class="h-full w-full object-cover object-center transition-opacity duration-500"
                                    >
                                    <div class="mtq-cover-overlay"></div>
                                    <button type="button" class="absolute left-4 top-1/2 z-10 inline-flex -translate-y-1/2 rounded-full border border-white/10 bg-slate-950/55 p-3 text-white backdrop-blur transition hover:bg-slate-950/80" x-on:click="prev()" aria-label="Venue sebelumnya">
                                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                    </button>
                                    <button type="button" class="absolute right-4 top-1/2 z-10 inline-flex -translate-y-1/2 rounded-full border border-white/10 bg-slate-950/55 p-3 text-white backdrop-blur transition hover:bg-slate-950/80" x-on:click="next()" aria-label="Venue berikutnya">
                                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                    </button>
                                    <figcaption class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                                        <div class="max-w-xl rounded-[1.4rem] border border-white/10 bg-slate-950/45 p-4 backdrop-blur">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-100/90">Klik untuk buka lokasi</p>
                                            <p class="mt-2 text-lg font-bold text-white sm:text-xl" x-text="current ? current.venue : '<?= e($featuredVenue['venue']) ?>'"></p>
                                            <p class="mt-2 text-sm leading-6 text-slate-200" x-text="current ? current.cabang : '<?= e($featuredVenue['cabang']) ?>'"></p>
                                        </div>
                                    </figcaption>
                                </figure>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div id="lokasi-ringan" class="mt-5 rounded-[1.8rem] border border-white/10 bg-slate-950/55 p-4 shadow-[0_18px_45px_-28px_rgba(15,23,42,0.4)]">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-300">Panel navigasi cepat</p>
                                <p class="mt-1 text-sm text-slate-300">Pilih venue dengan tombol kecil di bawah ini tanpa memperpanjang halaman.</p>
                            </div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400" x-text="venues.length ? ((active + 1) + ' / ' + venues.length) : '0 / 0'"></p>
                        </div>

                        <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                            <?php foreach ($competitionVenues as $index => $venue): ?>
                                <button
                                    type="button"
                                    class="group relative flex min-w-[10rem] items-center gap-3 rounded-[1.2rem] border border-white/10 bg-white/5 px-3 py-3 text-left transition hover:border-cyan-300/30"
                                    x-on:click="go(<?= (int) $index ?>)"
                                    x-bind:class="active === <?= (int) $index ?> ? 'border-cyan-300/40 bg-cyan-400/10' : ''"
                                    >
                                    <img
                                        src="<?= e($venue['photo_thumb_url'] ?? ($venue['photo_url'] ?? '')) ?>"
                                        alt="<?= e($venue['venue']) ?>"
                                        class="h-12 w-12 rounded-xl object-cover object-center"
                                    >
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-200">No. <?= e(str_pad((string) $venue['no'], 2, '0', STR_PAD_LEFT)) ?></p>
                                        <p class="truncate text-sm font-semibold text-white"><?= e($venue['venue']) ?></p>
                                        <p class="mt-1 text-[11px] text-slate-400"><?= e((int) ($venue['category_count'] ?? 0)) ?> golongan terhubung</p>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3 mt-5">
                        <div class="data-card">
                            <p class="text-sm text-slate-400">Total venue</p>
                            <p class="mt-2 text-3xl font-black text-white"><?= e($competitionVenueStats['total']) ?></p>
                        </div>
                        <div class="data-card">
                            <p class="text-sm text-slate-400">Venue masjid</p>
                            <p class="mt-2 text-3xl font-black text-white"><?= e($competitionVenueStats['masjid']) ?></p>
                        </div>
                        <div class="data-card">
                            <p class="text-sm text-slate-400">Venue sekolah / komunitas</p>
                            <p class="mt-2 text-3xl font-black text-white"><?= e($competitionVenueStats['sekolah'] + $competitionVenueStats['komunitas']) ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-6 overflow-hidden rounded-[2.5rem] border border-fuchsia-400/12 bg-gradient-to-br from-slate-950/90 via-slate-900/85 to-fuchsia-950/20 p-5 sm:p-6 lg:p-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="section-kicker">Peserta Tampil</p>
                        <h3 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">Wajah-wajah kafilah di panggung MTQ</h3>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-300">
                            Bagian ini menampilkan peserta yang sedang berlaga lengkap dengan foto, asal, dan cabang atau golongannya.
                        </p>
                    </div>
                    <div class="badge-live"><?= mtq_icon('users', 'h-4 w-4') ?> Tampilan Publik</div>
                </div>

                <div
                    class="mt-6"
                    x-data="{
                        active: 0,
                        slides: <?= e($featuredParticipantSlides->count()) ?>,
                        timer: null,
                        init() {
                            if (this.slides > 1) {
                                this.timer = setInterval(() => {
                                    this.active = (this.active + 1) % this.slides;
                                }, 5200);
                            }
                        },
                        prev() {
                            if (! this.slides) return;
                            this.active = (this.active - 1 + this.slides) % this.slides;
                        },
                        next() {
                            if (! this.slides) return;
                            this.active = (this.active + 1) % this.slides;
                        },
                        go(index) {
                            this.active = index;
                        }
                    }"
                    x-init="init()"
                >
                    <?php if ($featuredParticipantSlides->isNotEmpty()): ?>
                        <div class="relative overflow-hidden rounded-[2.2rem] border border-white/10 bg-gradient-to-br from-slate-950 via-slate-950/94 to-slate-900/80 p-5 sm:p-6">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.06),transparent_30%),radial-gradient(circle_at_bottom_left,rgba(34,211,238,0.06),transparent_24%)]"></div>

                            <?php foreach ($featuredParticipantSlides as $slideIndex => $slideParticipants): ?>
                                <?php
                                    $primaryParticipant = $slideParticipants->first();
                                    $secondaryParticipants = $slideParticipants->skip(1)->values();
                                ?>
                                <div x-cloak x-show="active === <?= $slideIndex ?>" x-transition.opacity.duration.1000ms class="relative space-y-5">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="relative">
                                            <p class="section-kicker">Kafilah Terverifikasi</p>
                                            <h4 class="mt-2 text-2xl font-bold text-white">Sorotan per putaran</h4>
                                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">Tampilan ini menempatkan satu peserta utama di depan, disertai detail singkat yang mudah dibaca.</p>
                                        </div>
                                        <span class="status-pill border-white/10 bg-white/5 text-slate-200">
                                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-fuchsia-300"></span>
                                            Slide <?= e($slideIndex + 1) ?> / <?= e($featuredParticipantSlides->count()) ?>
                                        </span>
                                    </div>

                                    <div class="grid gap-4">
                                        <?php if ($primaryParticipant): ?>
                                            <article class="group relative overflow-hidden rounded-[2.25rem] border border-white/10 bg-slate-950/72 shadow-[0_24px_70px_-42px_rgba(15,23,42,0.55)]">
                                                <div class="grid gap-0 lg:grid-cols-[minmax(180px,200px)_minmax(0,1fr)]">
                                                    <div class="relative flex items-center justify-center overflow-hidden border-b border-white/8 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-4 lg:border-b-0 lg:border-r">
                                                        <div class="relative w-full max-w-[180px] overflow-hidden rounded-[1.6rem] border border-white/10 bg-slate-900/80 shadow-[0_14px_36px_-26px_rgba(15,23,42,0.5)]">
                                                            <div class="relative aspect-[4/5]">
                                                                <?php if (! empty($primaryParticipant['photo_url'])): ?>
                                                                    <img src="<?= e($primaryParticipant['photo_url']) ?>" alt="<?= e($primaryParticipant['name']) ?>" class="h-full w-full object-cover object-center transition duration-700 group-hover:scale-[1.03]">
                                                                <?php else: ?>
                                                                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-800 via-slate-900 to-slate-800">
                                                                        <div class="text-center">
                                                                            <div class="icon-chip mx-auto h-14 w-14 rounded-[1.15rem]"><?= mtq_icon('users', 'h-7 w-7') ?></div>
                                                                            <p class="mt-3 text-xs font-semibold tracking-[0.28em] text-slate-300">PHOTO</p>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(2,6,23,0)_0%,rgba(2,6,23,0.14)_60%,rgba(2,6,23,0.76)_100%)]"></div>
                                                                <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 bg-gradient-to-t from-slate-950 via-slate-950/82 to-transparent p-3">
                                                                    <div class="inline-flex rounded-full border border-white/10 bg-black/20 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-white/90 backdrop-blur-sm">
                                                                        <?= e($primaryParticipant['branch'] ?? '-') ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="relative flex min-w-0 flex-col justify-between gap-5 p-5 sm:p-6">
                                                        <div class="space-y-4">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-200">Utama</span>
                                                                <span class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-200">Terverifikasi</span>
                                                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-200">Slide <?= e($slideIndex + 1) ?> / <?= e($featuredParticipantSlides->count()) ?></span>
                                                            </div>
                                                            <h5 class="max-w-xl text-[1.55rem] font-black leading-[1.05] tracking-tight text-white sm:text-[1.95rem]"><?= e($primaryParticipant['name']) ?></h5>
                                                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                                                <div class="rounded-[1.2rem] border border-white/8 bg-white/5 p-3.5">
                                                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Asal</p>
                                                                    <p class="mt-2 text-base font-bold text-white"><?= e($primaryParticipant['origin']) ?></p>
                                                                </div>
                                                                <div class="rounded-[1.2rem] border border-white/8 bg-white/5 p-3.5">
                                                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Cabang / Golongan</p>
                                                                    <p class="mt-2 text-base font-bold text-white"><?= e($primaryParticipant['category_label']) ?></p>
                                                                </div>
                                                                <div class="rounded-[1.2rem] border border-white/8 bg-white/5 p-3.5 sm:col-span-2 xl:col-span-1">
                                                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Umur peserta saat ini</p>
                                                                    <p class="mt-2 text-base font-bold text-white"><?= e($primaryParticipant['age_label'] ?? '-') ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        <?php endif; ?>

                                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                            <?php foreach ($secondaryParticipants as $participant): ?>
                                                <article class="group overflow-hidden rounded-[1.35rem] border border-white/10 bg-slate-950/68 shadow-[0_14px_34px_-30px_rgba(15,23,42,0.3)] transition duration-300 hover:-translate-y-1 hover:border-cyan-300/25">
                                                    <div class="relative aspect-[3/4]">
                                                        <?php if (! empty($participant['photo_url'])): ?>
                                                            <img src="<?= e($participant['photo_url']) ?>" alt="<?= e($participant['name']) ?>" class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.04]">
                                                        <?php else: ?>
                                                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-800 via-slate-900 to-slate-800">
                                                                <div class="text-center">
                                                                    <div class="icon-chip mx-auto h-14 w-14 rounded-[1.2rem]"><?= mtq_icon('users', 'h-7 w-7') ?></div>
                                                                    <p class="mt-3 text-[11px] font-semibold tracking-[0.26em] text-slate-300">PHOTO</p>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(2,6,23,0)_0%,rgba(2,6,23,0.08)_58%,rgba(2,6,23,0.72)_100%)]"></div>
                                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent p-3">
                                                            <div class="inline-flex rounded-full border border-white/10 bg-black/20 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/90 backdrop-blur-sm">
                                                                <?= e($participant['branch'] ?? '-') ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-2 p-3.5">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-200">Peserta</span>
                                                            <span class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200">Terverifikasi</span>
                                                        </div>
                                                        <h5 class="text-[14px] font-bold leading-tight text-white"><?= e($participant['name']) ?></h5>
                                                        <p class="text-xs leading-5 text-slate-300"><?= e($participant['origin']) ?></p>
                                                        <p class="text-xs leading-5 text-slate-400"><?= e($participant['category_label']) ?></p>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($featuredParticipantSlides->count() > 1): ?>
                                <button type="button" class="absolute left-4 top-4 z-10 rounded-full border border-white/10 bg-slate-950/60 p-2.5 text-white backdrop-blur transition hover:bg-slate-950/85" x-on:click="prev()" aria-label="Putaran sebelumnya">
                                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                </button>
                                <button type="button" class="absolute right-4 top-4 z-10 rounded-full border border-white/10 bg-slate-950/60 p-2.5 text-white backdrop-blur transition hover:bg-slate-950/85" x-on:click="next()" aria-label="Putaran berikutnya">
                                    <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-6 rounded-[1.8rem] border border-dashed border-slate-700/70 bg-slate-950/50 p-6 text-sm leading-7 text-slate-300">
                            Belum ada peserta verifikasi yang siap ditampilkan di homepage.
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="glass-card rounded-[2rem] p-6">
                    <div class="icon-chip"><?= mtq_icon('eye') ?></div>
                    <h3 class="mt-4 text-xl font-bold text-white">Ringkasan informasi</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-300">Pengunjung bisa melihat bagian penting tanpa perlu membuka banyak halaman.</p>
                </div>
                <div class="glass-card rounded-[2rem] p-6">
                    <div class="icon-chip"><?= mtq_icon('zap') ?></div>
                    <h3 class="mt-4 text-xl font-bold text-white">Jadwal dan pengumuman</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-300">Jadwal utama, pengumuman terbaru, dan dokumentasi tampil di satu tempat.</p>
                </div>
                <div class="glass-card rounded-[2rem] p-6">
                    <div class="icon-chip"><?= mtq_icon('shield') ?></div>
                    <h3 class="mt-4 text-xl font-bold text-white">Akses operasional</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-300">Laman publik tetap terpisah dari alur kerja panitia.</p>
                </div>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <div class="glass-card rounded-[2rem] p-6 sm:p-7">
                    <p class="section-kicker">Cabang Lomba</p>
                    <h3 class="mt-2 text-3xl font-bold text-white">Peta singkat kategori yang ditampilkan</h3>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">Bagian ini membantu pengunjung mengenali ragam cabang MTQ secara cepat dan terstruktur.</p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <?php foreach ($featuredBranches as $branch): ?>
                            <div class="data-card">
                                <div class="flex items-start gap-4">
                                    <div class="icon-chip"><?= mtq_icon('layers') ?></div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-white"><?= e($branch['name']) ?></p>
                                        <p class="mt-2 text-sm text-cyan-200"><?= e($branch['category_total']) ?> kategori | kuota <?= e($branch['quota_total']) ?></p>
                                        <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($branch['highlight'] ?: 'Detail kategori tersedia pada halaman kategori.') ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="pengumuman-terbaru" class="glass-card rounded-[2rem] p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="section-kicker">Pengumuman Terbaru</p>
                            <h3 class="mt-2 text-3xl font-bold text-white">Update penting dari panitia</h3>
                        </div>
                        <div class="badge-live"><?= mtq_icon('bell', 'h-4 w-4') ?> Info Langsung</div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <?php if ($announcements->isEmpty()): ?>
                            <div class="data-card">
                                <p class="font-semibold text-white">Belum ada pengumuman terbaru</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Saat panitia mulai menerbitkan update, bagian ini akan menjadi pusat informasi bagi pengunjung dan peserta.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <?php
                                $priority = (string) ($announcement->priority ?? 'normal');
                                $priorityClasses = match ($priority) {
                                    'high' => 'border-amber-400/20 bg-amber-400/10 text-amber-100',
                                    'low' => 'border-slate-500/30 bg-slate-500/10 text-slate-200',
                                    default => 'border-cyan-400/20 bg-cyan-400/10 text-cyan-200',
                                };
                                $priorityLabel = match ($priority) {
                                    'high' => 'Prioritas tinggi',
                                    'low' => 'Info tambahan',
                                    default => 'Info utama',
                                };
                                ?>
                                <article class="data-card">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] <?= e($priorityClasses) ?>"><?= e($priorityLabel) ?></span>
                                        <p class="text-xs text-slate-500"><?= e(optional($announcement->published_at)->format('d M Y H:i') ?? 'Belum dijadwalkan') ?></p>
                                    </div>
                                    <h4 class="mt-4 text-xl font-bold text-white"><?= e($announcement->title) ?></h4>
                                    <p class="mt-3 text-sm leading-7 text-slate-300"><?= e($announcement->body) ?></p>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <?php if ($galleryImages && $galleryImages->total() > 0): ?>
                <section
                    id="galeri-mtq"
                    class="mt-6 glass-card rounded-[2rem] p-6 sm:p-7"
                    x-data="{
                        open: false,
                        active: 0,
                        transitioning: false,
                        transitionTimer: null,
                        controlsVisible: false,
                        controlsTimer: null,
                        touchStartX: 0,
                        touchStartY: 0,
                        images: <?= e(json_encode($galleryModalItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>,
                        get currentImage() {
                            return this.images[this.active] || null;
                        },
                        setActive(index) {
                            if (!this.images.length) return;

                            const nextIndex = ((index % this.images.length) + this.images.length) % this.images.length;
                            this.transitioning = true;

                            if (this.transitionTimer) {
                                window.clearTimeout(this.transitionTimer);
                            }

                            this.transitionTimer = window.setTimeout(() => {
                                this.active = nextIndex;
                                this.transitioning = false;
                            }, 130);
                        },
                        openAt(index) {
                            this.active = index;
                            this.open = true;
                            this.showControls();
                        },
                        close() {
                            if (this.transitionTimer) {
                                window.clearTimeout(this.transitionTimer);
                                this.transitionTimer = null;
                            }
                            if (this.controlsTimer) {
                                window.clearTimeout(this.controlsTimer);
                                this.controlsTimer = null;
                            }
                            this.transitioning = false;
                            this.controlsVisible = false;
                            this.open = false;
                        },
                        next() {
                            if (!this.images.length) return;
                            this.setActive(this.active + 1);
                            this.showControls();
                        },
                        prev() {
                            if (!this.images.length) return;
                            this.setActive(this.active - 1);
                            this.showControls();
                        },
                        showControls() {
                            this.controlsVisible = true;

                            if (this.controlsTimer) {
                                window.clearTimeout(this.controlsTimer);
                            }

                            this.controlsTimer = window.setTimeout(() => {
                                this.controlsVisible = false;
                            }, 4500);
                        },
                        touchStart(event) {
                            const touch = event.touches && event.touches[0] ? event.touches[0] : null;
                            if (!touch) return;
                            this.touchStartX = touch.clientX;
                            this.touchStartY = touch.clientY;
                            this.showControls();
                        },
                        touchEnd(event) {
                            const touch = event.changedTouches && event.changedTouches[0] ? event.changedTouches[0] : null;
                            if (!touch) return;

                            const deltaX = touch.clientX - this.touchStartX;
                            const deltaY = touch.clientY - this.touchStartY;

                            if (Math.abs(deltaX) < 50 || Math.abs(deltaX) < Math.abs(deltaY)) {
                                return;
                            }

                            if (deltaX < 0) {
                                this.next();
                            } else {
                                this.prev();
                            }
                            this.showControls();
                        }
                    }"
                >
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="section-kicker">Galeri MTQ</p>
                            <h3 class="mt-2 text-3xl font-bold text-white">Cuplikan suasana yang membuat halaman terasa hidup</h3>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">Foto-foto dokumentasi acara ditampilkan di homepage secara bertahap, 9 foto per halaman, supaya halaman tetap ringan. Klik foto untuk membuka tampilan fullscreen.</p>
                        </div>
                        <div class="badge-live"><?= mtq_icon('image', 'h-4 w-4') ?> <?= e($galleryImages->total()) ?> foto | 9/halaman</div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-2 sm:gap-3 md:grid-cols-3 xl:grid-cols-4">
                        <?php foreach ($galleryImages as $index => $image): ?>
                            <figure class="group relative overflow-hidden rounded-[1.3rem] border border-white/10 bg-slate-950/45 shadow-[0_18px_45px_-28px_rgba(15,23,42,0.35)] <?= $index === 0 ? 'sm:col-span-2 sm:row-span-2 sm:min-h-[420px] min-h-[180px]' : 'min-h-[150px] sm:min-h-[180px]' ?>">
                                <button
                                    type="button"
                                    class="absolute inset-0 z-10 cursor-zoom-in focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300/70"
                                    x-on:click="openAt(<?= e($index) ?>)"
                                    aria-label="Buka fullscreen <?= e($image['label']) ?>"
                                ></button>
                                <img src="<?= e($image['src']) ?>" alt="<?= e($image['label']) ?>" loading="lazy" class="mtq-cover-image h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                                <div class="mtq-cover-overlay"></div>
                                <figcaption class="absolute inset-x-0 bottom-0 p-4">
                                    <div class="inline-flex rounded-full border border-white/10 bg-white/12 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/90">
                                        <?= e($image['label']) ?>
                                    </div>
                                    <p class="mt-2 max-w-[18rem] text-sm leading-6 text-slate-200"><?= e($image['caption'] ?? 'Dokumentasi kegiatan e-MTQ yang dipilih untuk tampilan beranda.') ?></p>
                                    <?php if (! empty($image['meta'])): ?>
                                        <p class="mt-2 text-xs uppercase tracking-[0.2em] text-cyan-100/80"><?= e($image['meta']) ?></p>
                                    <?php endif; ?>
                                </figcaption>
                            </figure>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($galleryImages->hasPages()): ?>
                        <?php
                            $currentPage = $galleryImages->currentPage();
                            $lastPage = $galleryImages->lastPage();
                            $windowStart = max(1, $currentPage - 2);
                            $windowEnd = min($lastPage, $currentPage + 2);
                        ?>
                        <nav class="mt-8 flex flex-col gap-3 rounded-[1.5rem] border border-slate-800 bg-slate-950/45 px-4 py-4 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination galeri homepage" data-gallery-pagination>
                            <div class="text-sm text-slate-400">
                                Halaman <?= e($currentPage) ?> dari <?= e($lastPage) ?>
                                <span class="block text-xs text-slate-500">Tampilan 9 foto per halaman.</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a
                                    href="<?= e(($galleryImages->previousPageUrl() ?: '#').'#galeri-mtq') ?>"
                                    class="inline-flex min-h-11 items-center justify-center rounded-2xl border px-4 py-2 text-sm font-semibold transition
                                        <?= $galleryImages->onFirstPage() ? 'pointer-events-none border-slate-800 bg-slate-900/60 text-slate-500' : 'border-cyan-400/18 bg-slate-900/80 text-cyan-100 hover:border-cyan-300/40 hover:bg-slate-900' ?>"
                                    aria-disabled="<?= $galleryImages->onFirstPage() ? 'true' : 'false' ?>"
                                    aria-label="Halaman sebelumnya"
                                    rel="prev"
                                >
                                    Sebelumnya
                                </a>

                                <?php if ($windowStart > 1): ?>
                                    <a href="<?= e($galleryImages->url(1).'#galeri-mtq') ?>" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900/60 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-300/40 hover:bg-slate-900">1</a>
                                    <?php if ($windowStart > 2): ?>
                                        <span class="px-1 text-sm text-slate-500">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($page = $windowStart; $page <= $windowEnd; $page++): ?>
                                    <a
                                        href="<?= e($galleryImages->url($page).'#galeri-mtq') ?>"
                                        class="inline-flex min-h-11 items-center justify-center rounded-2xl border px-4 py-2 text-sm font-semibold transition
                                            <?= $page === $currentPage ? 'border-cyan-300 bg-cyan-400/15 text-cyan-100' : 'border-slate-800 bg-slate-900/60 text-slate-200 hover:border-cyan-300/40 hover:bg-slate-900' ?>"
                                        aria-current="<?= $page === $currentPage ? 'page' : 'false' ?>"
                                    >
                                        <?= e($page) ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($windowEnd < $lastPage): ?>
                                    <?php if ($windowEnd < $lastPage - 1): ?>
                                        <span class="px-1 text-sm text-slate-500">...</span>
                                    <?php endif; ?>
                                    <a href="<?= e($galleryImages->url($lastPage).'#galeri-mtq') ?>" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900/60 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-cyan-300/40 hover:bg-slate-900"><?= e($lastPage) ?></a>
                                <?php endif; ?>

                                <a
                                    href="<?= e(($galleryImages->nextPageUrl() ?: '#').'#galeri-mtq') ?>"
                                    class="inline-flex min-h-11 items-center justify-center rounded-2xl border px-4 py-2 text-sm font-semibold transition
                                        <?= $galleryImages->hasMorePages() ? 'border-cyan-400/18 bg-slate-900/80 text-cyan-100 hover:border-cyan-300/40 hover:bg-slate-900' : 'pointer-events-none border-slate-800 bg-slate-900/60 text-slate-500' ?>"
                                    aria-disabled="<?= $galleryImages->hasMorePages() ? 'false' : 'true' ?>"
                                    aria-label="Halaman berikutnya"
                                    rel="next"
                                >
                                    Berikutnya
                                </a>
                            </div>
                        </nav>
                    <?php endif; ?>

                    <div
                        x-cloak
                        x-show="open"
                        x-transition.opacity.duration.300ms
                        class="fixed inset-0 z-[60] overflow-y-auto bg-slate-950/96 backdrop-blur-2xl"
                        x-on:click.self="close()"
                        x-on:keydown.escape.window="close()"
                    >
                        <div
                            class="relative z-10 flex min-h-[100dvh] w-full items-center justify-center p-4 sm:p-6"
                            x-on:click.stop
                            x-on:pointermove="showControls()"
                            x-on:touchstart.passive="touchStart($event)"
                            x-on:touchend.passive="touchEnd($event)"
                        >
                            <button type="button" class="absolute right-6 top-6 z-20 inline-flex min-h-12 min-w-12 items-center justify-center rounded-full border border-white/10 bg-slate-950/70 px-3 py-3 text-white backdrop-blur transition duration-300 hover:bg-slate-950/90 sm:right-8 sm:top-8" x-on:click="close()" aria-label="Tutup viewer fullscreen">
                                <span class="sr-only">Tutup</span>
                                <span class="text-[11px] font-semibold uppercase tracking-[0.16em] sm:text-sm sm:tracking-normal">Tutup</span>
                            </button>
                            <div class="relative flex h-[calc(100dvh-2rem)] w-full max-w-7xl flex-col overflow-hidden rounded-[2rem] border border-white/10 bg-black/35 p-3 shadow-[0_30px_100px_-30px_rgba(14,165,233,0.45)] transition-all duration-300 ease-out motion-reduce:transition-none sm:p-6" x-bind:class="open ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-[0.985] translate-y-2'">
                                <div
                                    class="absolute left-4 top-4 z-10 hidden rounded-full border border-white/10 bg-slate-950/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-white/80 md:inline-flex transition-all duration-650 ease-out"
                                    x-bind:class="controlsVisible ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1 pointer-events-none'"
                                >
                                    Geser foto
                                </div>
                                <div class="relative flex min-h-0 flex-1 items-center justify-center">
                                    <img
                                        x-bind:src="currentImage ? currentImage.full_src : ''"
                                        x-bind:alt="currentImage ? currentImage.label : 'Galeri MTQ'"
                                        class="mx-auto max-h-[calc(100dvh-13rem)] w-auto max-w-full object-contain transition duration-300"
                                        x-bind:class="transitioning ? 'opacity-60 scale-[0.985]' : 'opacity-100 scale-100'"
                                    >
                                    <button
                                        type="button"
                                        class="absolute left-3 top-1/2 hidden -translate-y-1/2 rounded-full border border-white/10 bg-slate-950/70 px-3 py-3 text-white backdrop-blur transition duration-650 ease-out hover:bg-slate-950/90 md:inline-flex"
                                        x-bind:class="controlsVisible ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-2 pointer-events-none'"
                                        x-on:click="prev()"
                                        aria-label="Foto sebelumnya"
                                    >
                                        <?= mtq_icon('arrow-left', 'h-5 w-5') ?>
                                    </button>
                                    <button
                                        type="button"
                                        class="absolute right-3 top-1/2 hidden -translate-y-1/2 rounded-full border border-white/10 bg-slate-950/70 px-3 py-3 text-white backdrop-blur transition duration-650 ease-out hover:bg-slate-950/90 md:inline-flex"
                                        x-bind:class="controlsVisible ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-2 pointer-events-none'"
                                        x-on:click="next()"
                                        aria-label="Foto berikutnya"
                                    >
                                        <?= mtq_icon('arrow-right', 'h-5 w-5') ?>
                                    </button>
                                    <div class="absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-slate-950 via-slate-950/55 to-transparent p-3 transition-all duration-650 ease-out sm:p-5" x-bind:class="controlsVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2 pointer-events-none'">
                                        <div class="mx-auto flex w-full max-w-5xl flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                            <div class="min-w-0 max-w-3xl rounded-[1.35rem] border border-white/8 bg-slate-950/35 px-4 py-3 backdrop-blur-sm">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-200">Fullscreen Gallery</p>
                                                <h4 class="mt-1.5 text-lg font-bold text-white sm:text-xl" x-text="currentImage ? currentImage.label : ''"></h4>
                                                <p class="mt-1.5 max-w-3xl text-xs leading-5 text-slate-200 sm:text-sm sm:leading-6" x-text="currentImage ? currentImage.caption : ''"></p>
                                                <p class="mt-1.5 text-[11px] uppercase tracking-[0.2em] text-cyan-100/80" x-text="currentImage ? currentImage.meta : ''"></p>
                                            </div>
                                            <div class="flex flex-col items-start gap-2 lg:items-end">
                                                <p class="inline-flex rounded-full border border-white/10 bg-white/8 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/80" x-text="images.length ? ((active + 1) + ' / ' + images.length) : ''"></p>
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-300 md:hidden">Geser kiri / kanan</p>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php foreach ($galleryModalItems as $dotIndex => $dotItem): ?>
                                                        <button
                                                            type="button"
                                                            class="h-2 w-2 rounded-full border border-transparent transition"
                                                            x-on:click="openAt(<?= e((int) $dotIndex) ?>)"
                                                            x-bind:class="active === <?= (int) $dotIndex ?> ? 'bg-cyan-300 shadow-[0_0_0_4px_rgba(34,211,238,0.16)]' : 'bg-white/25 hover:bg-white/50'"
                                                            aria-label="Lihat foto <?= e((int) $dotIndex + 1) ?>"
                                                        ></button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="mt-6 glass-card rounded-[2rem] p-6 sm:p-7">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="section-kicker">Timeline Event</p>
                        <h3 class="mt-2 text-3xl font-bold text-white">Alur momen penting menuju pelaksanaan</h3>
                    </div>
                    <p class="max-w-2xl text-sm leading-7 text-slate-300">Ringkasan ini memberi konteks cepat bagi pengunjung, official, dan peserta tentang ritme utama event.</p>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-4">
                    <?php foreach ($timeline as $item): ?>
                        <div class="data-card relative overflow-hidden">
                            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-400 via-sky-400 to-blue-500"></div>
                            <p class="text-sm font-semibold text-cyan-200"><?= e($item['date'] ?? '-') ?></p>
                            <h4 class="mt-3 text-lg font-bold text-white"><?= e($item['activity'] ?? 'Agenda MTQ') ?></h4>
                            <p class="mt-2 text-sm text-slate-400"><?= e($item['time'] ?? '-') ?></p>
                            <p class="mt-3 text-sm leading-7 text-slate-300"><?= e($item['notes'] ?? '-') ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php require __DIR__.'/../partials/app-footer.php'; ?>
        </div>
    </main>

    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>

