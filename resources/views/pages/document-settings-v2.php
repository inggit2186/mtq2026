<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$rolePanel = $rolePanel ?? [];
$documentConfig = $documentConfig ?? [];
$documentPreview = $documentPreview ?? [];
$documentSettingsReady = $documentSettingsReady ?? false;
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'admin.documents');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Metadata Dokumen') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/sweet-alerts.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="documentSettingsPreview(<?= e(json_encode($documentConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>, <?= e(json_encode($documentPreview, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)">
        <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('book-open') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Dokumen Resmi</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>
                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Metadata Aktif</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($documentConfig['organization_name'] ?? 'e-MTQ Kabupaten Tanah Datar') ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300"><?= e($documentConfig['event_title'] ?? '') ?></p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Siap Dipakai untuk Cetak
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
                <div class="mt-8">
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
                            <p class="section-kicker">Pengaturan Dokumen</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Metadata dokumen resmi</h2>
                            <p class="mt-2 text-sm text-slate-300"><?= e($rolePanel['description'] ?? '') ?></p>
                        </div>
                    </div>
                </header>

                <?php if (! $documentSettingsReady): ?>
                    <div class="rounded-3xl border border-amber-400/20 bg-amber-400/10 px-4 py-4 text-sm text-amber-100">
                        Tabel `document_settings` belum tersedia. Halaman ini masih memakai konfigurasi bawaan dari file konfigurasi.
                        Jalankan `php artisan migrate` agar perubahan metadata dapat disimpan ke database.
                    </div>
                <?php endif; ?>

                <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <form method="POST" action="<?= e(route('admin.documents.update')) ?>" class="glass-card rounded-[2rem] p-6 space-y-6" data-loading-text="Menyimpan metadata dokumen resmi...">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div><label class="mb-2 block text-sm font-semibold text-slate-200">Nama Organisasi</label><input name="organization_name" x-model="organization_name" type="text" value="<?= e(old('organization_name', $documentConfig['organization_name'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                            <div><label class="mb-2 block text-sm font-semibold text-slate-200">Judul Event</label><input name="event_title" x-model="event_title" type="text" value="<?= e(old('event_title', $documentConfig['event_title'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                            <div><label class="mb-2 block text-sm font-semibold text-slate-200">Lokasi Event</label><input name="event_location" x-model="event_location" type="text" value="<?= e(old('event_location', $documentConfig['event_location'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                            <div><label class="mb-2 block text-sm font-semibold text-slate-200">Kota Tanda Tangan</label><input name="signature_city" x-model="signature_city" type="text" value="<?= e(old('signature_city', $documentConfig['signature_city'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <?php foreach (($documentConfig['officials'] ?? []) as $key => $official): ?>
                                <div class="data-card space-y-3">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500"><?= e(ucwords(str_replace('_', ' ', $key))) ?></p>
                                    <div><label class="mb-2 block text-sm font-semibold text-slate-200">Jabatan</label><input name="officials[<?= e($key) ?>][title]" x-model="officials.<?= e($key) ?>.title" type="text" value="<?= e(old("officials.$key.title", $official['title'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                    <div><label class="mb-2 block text-sm font-semibold text-slate-200">Nama</label><input name="officials[<?= e($key) ?>][name]" x-model="officials.<?= e($key) ?>.name" type="text" value="<?= e(old("officials.$key.name", $official['name'] ?? '')) ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="primary-button">
                                <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                Simpan Metadata
                            </button>
                            <a href="<?= e(route('dashboard')) ?>" class="secondary-button">
                                <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                Kembali ke Dashboard
                            </a>
                        </div>
                    </form>

                    <section class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('spark') ?></div>
                            <div>
                                <p class="section-kicker">Pratinjau Langsung</p>
                                <h3 class="mt-2 text-2xl font-bold text-white">Pratinjau dokumen resmi</h3>
                            </div>
                        </div>

                        <div class="mt-6 rounded-[1.75rem] border border-slate-700 bg-white p-6 text-slate-900 shadow-[0_24px_60px_-28px_rgba(15,23,42,0.45)]">
                            <div class="border-b-2 border-slate-900 pb-4">
                                <p class="text-xl font-bold" x-text="organization_name || 'e-MTQ Kabupaten Tanah Datar'"></p>
                                <p class="mt-1 text-sm text-slate-600" x-text="event_title || 'Judul event akan tampil di sini'"></p>
                                <p class="mt-1 text-sm text-slate-500" x-text="event_location || 'Lokasi event'"></p>
                            </div>

                            <div class="mt-5 rounded-xl border border-slate-200 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Contoh Header Dokumen</p>
                                <p class="mt-3 text-lg font-bold">Berita Acara Ringkas Rekap Penilaian</p>
                                <p class="mt-2 text-sm text-slate-600">Cabang: Semua cabang | Golongan: Semua</p>
                            </div>

                            <div class="mt-5 grid gap-3">
                                <div class="rounded-xl border border-slate-200 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Penandatangan</p>
                                    <div class="mt-3 grid gap-3 text-sm md:grid-cols-2">
                                        <div>
                                            <p class="font-semibold" x-text="officials.chief_judge.title"></p>
                                            <p class="mt-1 text-slate-600" x-text="officials.chief_judge.name"></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold" x-text="officials.secretary.title"></p>
                                            <p class="mt-1 text-slate-600" x-text="officials.secretary.name"></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold" x-text="officials.committee_coordinator.title"></p>
                                            <p class="mt-1 text-slate-600" x-text="officials.committee_coordinator.name"></p>
                                        </div>
                                        <div>
                                            <p class="font-semibold" x-text="officials.committee_chair.title"></p>
                                            <p class="mt-1 text-slate-600" x-text="officials.committee_chair.name"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 text-right text-sm text-slate-600">
                                <p x-text="(signature_city || 'Batusangkar') + ', ' + formattedDate()"></p>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="section-kicker">Pratinjau Dokumen Hasil</p>
                            <h3 class="mt-2 text-2xl font-bold text-white">Dokumen resmi siap cetak</h3>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">Cek tampilan dokumen hasil langsung sebelum dibuka untuk cetak atau arsip.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" x-on:click="previewMode = 'recap'" x-bind:class="previewMode === 'recap' ? 'primary-button' : 'secondary-button'">
                                Rekap Resmi
                            </button>
                            <button type="button" x-on:click="previewMode = 'participant'" x-bind:class="previewMode === 'participant' ? 'primary-button' : 'secondary-button'">
                                Hasil Peserta
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-[280px_minmax(0,1fr)]">
                        <div class="data-card space-y-4">
                            <div x-show="previewMode === 'recap'">
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Cabang</label>
                                <select x-model="selectedBranch" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    <option value="">Semua cabang</option>
                                    <template x-for="branch in branches" x-bind:key="branch">
                                        <option x-bind:value="branch" x-text="branch"></option>
                                    </template>
                                </select>

                                <label class="mb-2 mt-4 block text-sm font-semibold text-slate-200">Golongan</label>
                                <select x-model="selectedCategoryId" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    <option value="">Semua golongan</option>
                                    <template x-for="category in categories" x-bind:key="category.id">
                                        <option x-bind:value="category.id" x-text="category.label"></option>
                                    </template>
                                </select>

                                <label class="mb-2 mt-4 block text-sm font-semibold text-slate-200">Kata Kunci</label>
                                <input x-model="recapKeyword" type="text" placeholder="Nama, nomor, atau kafilah" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                            </div>

                            <div x-show="previewMode === 'participant'">
                                <label class="mb-2 block text-sm font-semibold text-slate-200">Peserta</label>
                                <select x-model="selectedParticipantId" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100">
                                    <template x-if="participants.length === 0">
                                        <option value="">Belum ada peserta terverifikasi</option>
                                    </template>
                                    <template x-for="participant in participants" x-bind:key="participant.id">
                                        <option x-bind:value="participant.id" x-text="participant.label"></option>
                                    </template>
                                </select>
                                <p class="mt-3 text-xs leading-5 text-slate-400" x-text="selectedParticipantCategory()"></p>
                            </div>

                            <div class="flex flex-col gap-2 pt-2">
                                <a x-bind:href="currentPreviewUrl()" target="_blank" rel="noreferrer" class="secondary-button justify-center">
                                    <?= mtq_icon('book-open', 'h-4 w-4') ?>
                                    Buka Pratinjau
                                </a>
                                <a x-bind:href="currentPrintUrl()" target="_blank" rel="noreferrer" class="primary-button justify-center">
                                    <?= mtq_icon('check-circle', 'h-4 w-4') ?>
                                    Cetak Dokumen
                                </a>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-[1.5rem] border border-slate-700 bg-slate-900/80">
                            <iframe x-bind:src="currentPreviewUrl()" title="Pratinjau dokumen hasil resmi" class="h-[720px] w-full bg-white"></iframe>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <script>
        function documentSettingsPreview(initialConfig, previewConfig) {
            return {
                mobileNavOpen: false,
                previewMode: 'recap',
                organization_name: initialConfig.organization_name || '',
                event_title: initialConfig.event_title || '',
                event_location: initialConfig.event_location || '',
                signature_city: initialConfig.signature_city || '',
                officials: initialConfig.officials || {},
                participants: previewConfig.participants || [],
                categories: previewConfig.categories || [],
                branches: previewConfig.branches || [],
                selectedParticipantId: previewConfig.selectedParticipantId || '',
                selectedCategoryId: '',
                selectedBranch: '',
                recapKeyword: '',
                previewUrls: previewConfig.urls || {},
                formattedDate() {
                    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date());
                },
                selectedParticipantCategory() {
                    const participant = this.participants.find((item) => String(item.id) === String(this.selectedParticipantId));

                    return participant ? participant.category : 'Pilih peserta terverifikasi untuk melihat dokumen hasil nilai.';
                },
                withParams(baseUrl, params) {
                    const url = new URL(baseUrl, window.location.origin);

                    Object.entries(params).forEach(([key, value]) => {
                        if (value !== null && value !== undefined && value !== '') {
                            url.searchParams.set(key, value);
                        }
                    });

                    return url.toString();
                },
                currentPreviewUrl() {
                    if (this.previewMode === 'participant') {
                        return this.withParams(this.previewUrls.participantPreview || '', {
                            preview: 1,
                            participant_id: this.selectedParticipantId,
                        });
                    }

                    return this.withParams(this.previewUrls.recapPreview || '', {
                        preview: 1,
                        branch: this.selectedBranch,
                        competition_category_id: this.selectedCategoryId,
                        keyword: this.recapKeyword,
                    });
                },
                currentPrintUrl() {
                    if (this.previewMode === 'participant') {
                        return this.withParams(this.previewUrls.participantPrint || '', {
                            participant_id: this.selectedParticipantId,
                        });
                    }

                    return this.withParams(this.previewUrls.recapPrint || '', {
                        branch: this.selectedBranch,
                        competition_category_id: this.selectedCategoryId,
                        keyword: this.recapKeyword,
                    });
                },
            };
        }
    </script>
</body>
</html>
