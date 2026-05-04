<?php
require_once __DIR__.'/../partials/icon.php';
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$user = auth()->user();
$officials = $officials ?? collect();
$districts = $districts ?? collect();
$officialStats = $officialStats ?? ['total' => 0, 'districts_covered' => 0, 'with_email' => 0];
$generatedCredentials = session('generated_credentials');
$navigation = app(\App\Http\Controllers\PageController::class)->consoleNavigation((string) $user?->role, 'officials.index');
$officialsByDistrict = $officials->groupBy(fn ($official) => $official->district?->name ?? 'Tanpa Kecamatan');
$districtOptions = $districts->map(fn ($district): array => ['id' => $district->id, 'name' => $district->name])->values()->all();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Official Kecamatan') ?></title>
    <style>[x-cloak]{display:none!important;}</style>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased">
    <?php require __DIR__.'/../partials/live-notifications.php'; ?>
    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8" x-data="officialRegistrationPage(<?= e(json_encode([
        'districts' => $districtOptions,
        'previewUrl' => route('officials.preview'),
        'storeUrl' => route('officials.store'),
        'csrfToken' => csrf_token(),
        'initialNip' => old('nip', ''),
        'initialDistrictId' => old('district_id', ''),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)">
        <div class="hero-orb hero-orb-cyan right-[-8rem] top-10 h-72 w-72"></div>
        <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

        <div class="grid gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="sidebar-shell fixed inset-y-4 left-4 z-30 w-[290px] rounded-[2rem] p-5 transition duration-300 lg:static lg:inset-auto lg:block"
                x-bind:class="mobileNavOpen ? 'translate-x-0 opacity-100' : '-translate-x-[120%] opacity-0 lg:translate-x-0 lg:opacity-100'">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('users') ?></div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-cyan-200">e-MTQ Console</p>
                            <h1 class="mt-1 text-lg font-bold text-white">Official Kecamatan</h1>
                        </div>
                    </div>
                    <button type="button" class="secondary-button rounded-xl px-3 py-2 lg:hidden" x-on:click="mobileNavOpen = false">
                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    </button>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-cyan-400/14 bg-gradient-to-br from-slate-900/90 via-sky-950/70 to-blue-950/60 p-5">
                    <p class="section-kicker">Registrasi SILATAR</p>
                    <h2 class="mt-3 text-xl font-bold text-white"><?= e($user?->name) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Input cukup NIP pegawai. Sistem akan mengambil data SILATAR, memetakan kecamatan, lalu membuat akun official secara otomatis.</p>
                    <div class="mt-4 status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        Hanya Admin
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
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Total Official</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($officialStats['total']) ?></p>
                    </div>
                    <div class="data-card">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Kecamatan Terjangkau</p>
                        <p class="mt-2 text-3xl font-extrabold text-white"><?= e($officialStats['districts_covered']) ?></p>
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
                            <p class="section-kicker">Manajemen Akun Official</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Daftarkan official kecamatan langsung dari NIP SILATAR</h2>
                            <p class="mt-2 text-sm text-slate-300">Akun official tidak perlu diinput manual satu per satu. Cukup masukkan NIP, lalu e-MTQ menyalin data dasar yang relevan dari SILATAR.</p>
                        </div>
                    </div>
                    <div class="status-pill">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                        <?= e($districts->count()) ?> Kecamatan Master
                    </div>
                </header>

                <?php if ($generatedCredentials): ?>
                    <section class="rounded-[2rem] border border-emerald-400/20 bg-emerald-400/10 p-6">
                        <p class="section-kicker text-emerald-200">Akun Baru Berhasil Dibuat</p>
                        <h3 class="mt-2 text-2xl font-bold text-white"><?= e($generatedCredentials['name'] ?? '-') ?></h3>
                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                            <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Email</p><p class="mt-2 text-sm font-semibold text-white"><?= e($generatedCredentials['email'] ?? '-') ?></p></div>
                            <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">No. HP</p><p class="mt-2 text-sm font-semibold text-white"><?= e($generatedCredentials['phone'] ?? '-') ?></p></div>
                            <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nomor Induk</p><p class="mt-2 text-sm font-semibold text-white"><?= e($generatedCredentials['nomor_induk'] ?? '-') ?></p></div>
                            <div class="data-card"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Kecamatan</p><p class="mt-2 text-sm font-semibold text-white"><?= e($generatedCredentials['district'] ?? '-') ?></p></div>
                            <div class="data-card md:col-span-2 xl:col-span-2"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Password Awal</p><p class="mt-2 text-lg font-black tracking-[0.08em] text-emerald-300"><?= e($generatedCredentials['password'] ?? '-') ?></p></div>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-emerald-100">Password ini hanya ditampilkan sekali setelah akun dibuat. Silakan catat dan sampaikan ke official terkait.</p>
                    </section>
                <?php endif; ?>

                <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('id-card') ?></div>
                            <div>
                                <p class="section-kicker">Form Registrasi</p>
                                <h3 class="mt-1 text-2xl font-bold text-white">Daftarkan official dari NIP</h3>
                            </div>
                        </div>

                        <form class="mt-6 space-y-4" x-on:submit.prevent="openPreview()">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">NIP Pegawai SILATAR</label>
                                <input name="nip" type="text" inputmode="numeric" x-model="nip" placeholder="Contoh: 199201152022031001" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                            <p class="mt-2 text-xs leading-6 text-slate-400">Sistem akan mengambil `nama`, `email`, `no hp`, `kecamatan`, dan `foto profil` dari SILATAR. Kecamatan hasil pembacaan SILATAR akan langsung dijadikan pilihan default di modal pratinjau.</p>
                                <template x-if="previewError">
                                    <p class="mt-3 text-sm text-rose-200" x-text="previewError"></p>
                                </template>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="primary-button" x-bind:disabled="loadingPreview" x-bind:class="loadingPreview ? 'pointer-events-none opacity-60' : ''">
                                    <?= mtq_icon('eye', 'h-4 w-4') ?>
                                    <span x-text="loadingPreview ? 'Memuat Pratinjau...' : 'Pratinjau User'"></span>
                                </button>
                                <a href="<?= e(route('admin.content')) ?>" class="secondary-button">
                                    <?= mtq_icon('bell', 'h-4 w-4') ?>
                                    Kembali ke Admin
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="glass-card rounded-[2rem] p-6">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip"><?= mtq_icon('check-circle') ?></div>
                            <div>
                                <p class="section-kicker">Aturan Otomatis</p>
                                <h3 class="mt-1 text-2xl font-bold text-white">Yang disalin dari SILATAR</h3>
                            </div>
                        </div>
                        <div class="mt-6 grid gap-3">
                            <div class="data-card"><p class="font-semibold text-white">Nama pegawai</p><p class="mt-2 text-sm leading-6 text-slate-300">Dipakai sebagai nama akun official di e-MTQ.</p></div>
                            <div class="data-card"><p class="font-semibold text-white">Email</p><p class="mt-2 text-sm leading-6 text-slate-300">Dipakai jika email SILATAR tersedia dan belum bentrok dengan akun lain.</p></div>
                            <div class="data-card"><p class="font-semibold text-white">No. HP</p><p class="mt-2 text-sm leading-6 text-slate-300">Nomor telepon dari SILATAR ikut disalin ke akun official e-MTQ.</p></div>
                            <div class="data-card"><p class="font-semibold text-white">Kecamatan</p><p class="mt-2 text-sm leading-6 text-slate-300">Dibaca dari data SILATAR dan langsung dijadikan pilihan default sebelum admin memutuskan daftar atau mengubah kecamatan.</p></div>
                            <div class="data-card"><p class="font-semibold text-white">Foto profil</p><p class="mt-2 text-sm leading-6 text-slate-300">Avatar SILATAR diunduh ke e-MTQ bila tersedia, sehingga official langsung punya identitas visual.</p></div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('users') ?></div><p class="mt-4 text-sm text-slate-400">Total Official</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($officialStats['total']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('building') ?></div><p class="mt-4 text-sm text-slate-400">Kecamatan Aktif</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($officialStats['districts_covered']) ?></p></div>
                    <div class="metric-card"><div class="icon-chip"><?= mtq_icon('mail') ?></div><p class="mt-4 text-sm text-slate-400">Email Tersedia</p><p class="mt-2 text-3xl font-extrabold text-white"><?= e($officialStats['with_email']) ?></p></div>
                </section>

                <section class="glass-card rounded-[2rem] p-6">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip"><?= mtq_icon('users') ?></div>
                        <div>
                            <p class="section-kicker">Daftar Official</p>
                            <h3 class="mt-1 text-2xl font-bold text-white">Official per kecamatan</h3>
                        </div>
                    </div>

                    <div class="mt-6 space-y-5">
                        <?php if ($officialsByDistrict->isEmpty()): ?>
                            <div class="data-card text-sm text-slate-300">Belum ada official kecamatan yang terdaftar.</div>
                        <?php else: ?>
                            <?php foreach ($officialsByDistrict as $districtName => $districtOfficials): ?>
                                <div class="rounded-[1.5rem] border border-white/8 bg-slate-950/30 p-5">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Kecamatan</p>
                                            <h4 class="mt-2 text-xl font-bold text-white"><?= e($districtName) ?></h4>
                                        </div>
                                        <div class="status-pill">
                                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-300"></span>
                                            <?= e($districtOfficials->count()) ?> Official
                                        </div>
                                    </div>

                                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                        <?php foreach ($districtOfficials as $official): ?>
                                            <?php $photoUrl = $official->profilePhotoUrl(); ?>
                                            <div class="data-card relative">
                                                <div class="absolute right-4 top-4 flex items-center gap-2">
                                                    <form method="POST" action="<?= e(route('officials.destroy', $official)) ?>" data-swal-confirm data-swal-title="Hapus akun official?" data-swal-text="Akun official <?= e($official->name) ?> akan dihapus dari e-MTQ." data-swal-confirm="Ya, hapus" data-swal-cancel="Batal">
                                                        <?= csrf_field() ?>
                                                        <button
                                                            type="submit"
                                                            class="secondary-button rounded-full border-rose-400/20 bg-rose-400/10 p-2 text-rose-100 hover:border-rose-300/40"
                                                            title="Hapus akun official"
                                                            aria-label="Hapus akun official"
                                                        >
                                                            <?= mtq_icon('trash', 'h-4 w-4') ?>
                                                        </button>
                                                    </form>
                                                </div>
                                                <div class="flex items-center gap-3 pr-12">
                                                    <?php if ($photoUrl): ?>
                                                        <img src="<?= e($photoUrl) ?>" alt="Foto <?= e($official->name) ?>" class="h-12 w-12 rounded-full border border-cyan-300/16 object-cover">
                                                    <?php else: ?>
                                                        <div class="flex h-12 w-12 items-center justify-center rounded-full border border-cyan-300/16 bg-cyan-400/10 text-sm font-black text-cyan-100"><?= e($official->profileInitials()) ?></div>
                                                    <?php endif; ?>
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-white"><?= e($official->name) ?></p>
                                                        <p class="mt-1 truncate text-xs text-slate-400"><?= e($official->email ?: '-') ?></p>
                                                    </div>
                                                </div>
                                                <div class="mt-4 space-y-1 text-sm text-slate-300">
                                                    <p><span class="text-slate-500">No. HP:</span> <?= e($official->phone ?: '-') ?></p>
                                                    <p><span class="text-slate-500">NIP:</span> <?= e($official->nomor_induk ?: '-') ?></p>
                                                    <p><span class="text-slate-500">SILATAR ID:</span> <?= e($official->silatar_user_id ?: '-') ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
            <div x-cloak x-show="previewOpen" x-on:keydown.escape.window="closePreview()" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-3">
                <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" x-on:click="closePreview()"></div>
                <div class="relative z-10 flex max-h-[76vh] w-full max-w-xl flex-col overflow-hidden rounded-[1.75rem] border border-cyan-400/14 bg-slate-950 shadow-[0_40px_100px_-35px_rgba(14,165,233,0.45)]">
                    <div class="flex items-center justify-between gap-4 border-b border-white/8 px-4 py-3">
                        <div>
                            <p class="section-kicker">Pratinjau User SILATAR</p>
                            <h3 class="mt-1.5 text-lg font-bold text-white">Tinjau data official</h3>
                        </div>
                        <button type="button" class="secondary-button rounded-xl px-3 py-2" x-on:click="closePreview()">
                            <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                        </button>
                    </div>

                    <div class="overflow-y-auto px-4 py-4">
                        <template x-if="preview">
                            <form method="POST" x-bind:action="storeUrl" class="space-y-4" data-loading-text="Menyimpan akun official dan akses kecamatan...">
                                <input type="hidden" name="_token" x-bind:value="csrfToken">
                                <input type="hidden" name="nip" x-bind:value="nip">
                                <div class="grid gap-4 md:grid-cols-[120px_minmax(0,1fr)]">
                                    <div class="rounded-[1.25rem] border border-white/8 bg-slate-900/70 p-3">
                                        <template x-if="preview.avatar_url">
                                            <img x-bind:src="preview.avatar_url" x-bind:alt="`Foto ${preview.name}`" class="mx-auto h-20 w-20 rounded-[1rem] border border-cyan-300/16 object-cover">
                                        </template>
                                        <template x-if="!preview.avatar_url">
                                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[1rem] border border-cyan-300/16 bg-cyan-400/10 text-2xl font-black text-cyan-100" x-text="initials(preview.name)"></div>
                                        </template>
                                        <p class="mt-2 text-center text-[11px] uppercase tracking-[0.2em] text-slate-500">Foto</p>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="data-card md:col-span-2">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nama</p>
                                            <p class="mt-1.5 text-base font-bold text-white" x-text="preview.name"></p>
                                        </div>
                                        <div class="data-card">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Email</p>
                                            <p class="mt-1.5 text-sm font-semibold text-white break-all" x-text="preview.email || '-'"></p>
                                        </div>
                                        <div class="data-card">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">No. HP</p>
                                            <p class="mt-1.5 text-sm font-semibold text-white" x-text="preview.phone || '-'"></p>
                                        </div>
                                        <div class="data-card">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Nomor Induk</p>
                                            <p class="mt-1.5 text-sm font-semibold text-white" x-text="preview.nomor_induk"></p>
                                        </div>
                                        <div class="data-card md:col-span-2">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Unit Kerja SILATAR</p>
                                            <p class="mt-1.5 text-sm leading-6 text-slate-200" x-text="preview.unit_label || '-'"></p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-semibold text-slate-200">Kecamatan e-MTQ</label>
                                            <select name="district_id" x-model="selectedDistrictId" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-slate-100 outline-none focus:border-cyan-300 focus:ring-2 focus:ring-cyan-400/20">
                                                <option value="">Pilih kecamatan</option>
                                                <template x-for="district in districts" :key="district.id">
                                                    <option :value="String(district.id)" :selected="String(district.id) === String(selectedDistrictId)" x-text="district.name"></option>
                                                </template>
                                            </select>
                                            <p class="mt-2 text-xs leading-5 text-slate-400" x-text="preview.mapping_note"></p>
                                            <template x-if="preview.district_name">
                                                <p class="mt-2 text-xs font-semibold text-cyan-200">Default SILATAR: <span x-text="preview.district_name"></span></p>
                                            </template>
                                            <template x-if="preview.district_name === 'Non-KUA'">
                                                <div class="mt-3 inline-flex items-center gap-2 rounded-full border border-rose-400/24 bg-rose-400/10 px-3 py-1.5 text-xs font-semibold text-rose-100">
                                                    <span class="inline-flex h-2 w-2 rounded-full bg-rose-300"></span>
                                                    Kecamatan wajib dipilih manual
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="sticky bottom-0 flex flex-wrap gap-3 border-t border-white/8 bg-slate-950 pt-3">
                                    <button type="submit" class="primary-button" x-bind:disabled="!selectedDistrictId" x-bind:class="!selectedDistrictId ? 'pointer-events-none opacity-50 saturate-50' : ''">
                                        <?= mtq_icon('arrow-right', 'h-4 w-4') ?>
                                        Daftarkan Official
                                    </button>
                                    <button type="button" class="secondary-button" x-on:click="closePreview()">
                                        <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                                        Tutup Pratinjau
                                    </button>
                                    <template x-if="!selectedDistrictId">
                                        <p class="w-full text-sm font-medium text-rose-200">Pilih kecamatan e-MTQ terlebih dahulu sebelum official dapat didaftarkan.</p>
                                    </template>
                                </div>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>
    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
    <script>
        function officialRegistrationPage(initialState) {
            return {
                mobileNavOpen: false,
                districts: initialState.districts || [],
                previewUrl: initialState.previewUrl || '',
                storeUrl: initialState.storeUrl || '',
                csrfToken: initialState.csrfToken || '',
                nip: initialState.initialNip || '',
                selectedDistrictId: initialState.initialDistrictId ? String(initialState.initialDistrictId) : '',
                loadingPreview: false,
                previewOpen: false,
                previewError: '',
                preview: null,
                async openPreview() {
                    this.previewError = '';
                    this.preview = null;

                    const normalizedNip = String(this.nip || '').replace(/\D+/g, '');
                    if (!normalizedNip) {
                        this.previewError = 'NIP pegawai wajib diisi dengan angka yang valid.';
                        return;
                    }

                    this.loadingPreview = true;

                    try {
                        const response = await fetch(this.previewUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ nip: normalizedNip }),
                            credentials: 'same-origin',
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            this.previewError = payload.message || 'Pratinjau user SILATAR gagal dimuat.';
                            return;
                        }

                        this.nip = normalizedNip;
                        this.preview = payload.preview || null;
                        this.selectedDistrictId = this.preview && this.preview.district_id ? String(this.preview.district_id) : '';
                        this.previewOpen = true;
                    } catch (error) {
                        this.previewError = 'Koneksi ke SILATAR gagal. Silakan coba beberapa saat lagi.';
                    } finally {
                        this.loadingPreview = false;
                    }
                },
                closePreview() {
                    this.previewOpen = false;
                },
                initials(name) {
                    return String(name || '')
                        .trim()
                        .split(/\s+/)
                        .filter(Boolean)
                        .slice(0, 2)
                        .map((part) => part.charAt(0).toUpperCase())
                        .join('') || 'U';
                },
            };
        }
    </script>
</body>
</html>
