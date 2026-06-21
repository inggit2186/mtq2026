<?php
require_once __DIR__.'/../partials/icon.php';
use App\Models\Finalist;
$cssAssets = $assets['css'] ?? [];
$jsAssets = $assets['js'] ?? [];
$categories = $categories ?? collect();
$groupedFinalists = $groupedFinalists ?? collect();
$existingFinalists = $existingFinalists ?? [];
$selectedCategoryId = $selectedCategoryId ?? null;
$mfqCategoryIds = $mfqCategoryIds ?? [24, 25];
$districtParticipantCounts = $districtParticipantCounts ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(config('app.name', 'e-MTQ').' - Kelola Finalis') ?></title>
    <?php foreach ($cssAssets as $href): ?>
        <link rel="stylesheet" href="<?= e($href) ?>">
    <?php endforeach; ?>
</head>
<body class="grid-bg scroll-smooth min-h-screen overflow-x-hidden bg-slate-950 text-slate-100 antialiased"
      x-data="{
          showGenerateModal: false,
          selectedCategory: null,
          isGenerating: false,
          generateResult: null,
          generateError: null,
          showDeleteModal: false,
          categoryToDelete: null
      }">
    <!-- Background Orbs -->
    <div class="hero-orb hero-orb-cyan right-[-7rem] top-10 h-72 w-72"></div>
    <div class="hero-orb hero-orb-blue left-[-7rem] top-64 h-64 w-64"></div>

    <main class="relative mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <header class="topbar-card flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <a href="<?= e(route('leaderboard.index')) ?>" class="secondary-button rounded-xl px-3 py-2">
                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <?= mtq_icon('trophy', 'h-5 w-5 text-amber-300') ?>
                        <p class="section-kicker">Kelola Peserta Final</p>
                    </div>
                    <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Generate Peserta Final</h2>
                    <p class="mt-2 text-sm text-slate-300">Ambil 3 peserta terbaik (putra & putri) dari setiap golongan.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="<?= e(route('finalists.print')) ?>" target="_blank" class="secondary-button">
                    <?= mtq_icon('printer', 'h-4 w-4') ?>
                    Cetak PDF
                </a>
                <button
                    type="button"
                    @click="showGenerateModal = true; selectedCategory = null"
                    class="rounded-xl border border-amber-400/50 bg-amber-400/10 px-4 py-2 font-semibold text-amber-300 transition-all hover:bg-amber-400/20 hover:shadow-lg hover:shadow-amber-400/20"
                >
                    <?= mtq_icon('zap', 'h-4 w-4 inline mr-1') ?>
                    Generate Semua Golongan
                </button>
                <a href="<?= e(route('leaderboard.index')) ?>" class="secondary-button">
                    <?= mtq_icon('arrow-left', 'h-4 w-4') ?>
                    Kembali
                </a>
            </div>
        </header>

        <!-- Info Card -->
        <section class="glass-card rounded-[1.5rem] p-4 mb-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-full border border-cyan-400/30 bg-cyan-400/10 p-3">
                    <?= mtq_icon('info', 'h-6 w-6 text-cyan-300') ?>
                </div>
                <div>
                    <h3 class="font-bold text-white">Tentang Sistem Finalis</h3>
                    <ul class="mt-2 space-y-1 text-sm text-slate-300">
                        <li>• Finalis dipilih dari <strong class="text-amber-300">3 peserta terbaik</strong> di setiap golongan</li>
                        <li>• Putra dan Putri dihitung <strong class="text-amber-300">terpisah</strong></li>
                        <li>• Peserta dengan nilai <strong class="text-amber-300">Final</strong> akan muncul di atas peserta Penyisihan</li>
                        <li>• Tie-breaker: nilai rata-rata, nilai terbaru, kemudian abjad nama</li>
                        <li>• Data finalis dapat digunakan untuk <strong class="text-amber-300">penilaian final</strong> dan <strong class="text-amber-300">maqra</strong></li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Category Cards Grid -->
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($categories as $category): ?>
                <?php
                $catFinalists = $groupedFinalists->get($category->id, collect());
                $putraCount = $catFinalists->get(Finalist::GENDER_MALE, collect())->count();
                $putriCount = $catFinalists->get(Finalist::GENDER_FEMALE, collect())->count();
                $totalFinalists = $putraCount + $putriCount;
                $hasFinalists = $totalFinalists > 0;
                $isMfq = in_array($category->id, $mfqCategoryIds);
                $isMsq = filled($category->maqra_system_type ?? null) && $category->maqra_system_type === 'syarhil';
                $isMsqMfq = $isMfq || $isMsq;
                ?>
                <div class="rounded-xl border p-4 transition-all hover:-translate-y-1 <?= $hasFinalists ? 'border-emerald-400/30 bg-emerald-400/5' : 'border-slate-700 bg-slate-900/50 hover:border-slate-600' ?>">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="rounded-full border <?= $hasFinalists ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300' : 'border-slate-600/50 bg-slate-700/30 text-slate-400' ?> px-2 py-0.5 text-xs font-semibold">
                                    <?= e($category->branch) ?>
                                </span>
                                <?php if ($isMsqMfq): ?>
                                    <span class="rounded-full border border-purple-400/30 bg-purple-400/10 px-2 py-0.5 text-xs font-semibold text-purple-300">
                                        <?= $isMsq ? '📌 MSQ' : '🏆 MFQ' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="mt-2 truncate font-bold text-white"><?= e($category->name) ?></h3>
                        </div>
                        <?php if ($hasFinalists): ?>
                            <span class="rounded-full border border-emerald-400/40 bg-emerald-400/10 px-2 py-0.5 text-xs font-bold text-emerald-300">
                                <?= $totalFinalists ?> Finalis
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Finalist Count -->
                    <div class="mt-4 flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('gender-male', 'h-4 w-4 text-cyan-400') ?>
                            <span class="text-sm text-slate-300">
                                Putra: <strong class="<?= $putraCount > 0 ? 'text-emerald-300' : 'text-slate-500' ?>"><?= $putraCount ?></strong>/3
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <?= mtq_icon('gender-female', 'h-4 w-4 text-pink-400') ?>
                            <span class="text-sm text-slate-300">
                                Putri: <strong class="<?= $putriCount > 0 ? 'text-emerald-300' : 'text-slate-500' ?>"><?= $putriCount ?></strong>/3
                            </span>
                        </div>
                    </div>

                    <!-- Finalist List (if exists) -->
                    <?php if ($hasFinalists): ?>
                        <?php
                        // For MSQ/MFQ: limit to top 3 per gender
                        $putraFinalists = $catFinalists->get(Finalist::GENDER_MALE, collect());
                        $putriFinalists = $catFinalists->get(Finalist::GENDER_FEMALE, collect());
                        $putraTop3 = $isMsqMfq ? $putraFinalists->filter(fn ($f) => $f->finalist_rank <= 3) : $putraFinalists;
                        $putriTop3 = $isMsqMfq ? $putriFinalists->filter(fn ($f) => $f->finalist_rank <= 3) : $putriFinalists;
                        ?>
                        <div class="mt-4 space-y-2">
                            <?php if ($putraTop3->isNotEmpty()): ?>
                                <div class="rounded-lg border border-cyan-400/20 bg-cyan-400/5 p-2">
                                    <p class="mb-1 text-xs font-semibold uppercase text-cyan-400">Putra</p>
                                    <?php foreach ($putraTop3 as $finalist): ?>
                                        <?php
                                        $participantName = $finalist->participant?->name ?? '-';
                                        if ($isMsqMfq) {
                                            $key = $category->id . '_' . $finalist->participant?->district_id;
                                            $count = $districtParticipantCounts[$key] ?? 1;
                                            if ($count > 1) {
                                                $participantName .= ' dkk.';
                                            }
                                        }
                                        ?>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="<?= $finalist->finalist_rank === 1 ? 'text-amber-300 font-bold' : 'text-slate-300' ?>">
                                                <?= $finalist->finalist_rank ?>. <?= e($participantName) ?>
                                            </span>
                                            <span class="text-cyan-300"><?= number_format($finalist->score, 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($putriTop3->isNotEmpty()): ?>
                                <div class="rounded-lg border border-pink-400/20 bg-pink-400/5 p-2">
                                    <p class="mb-1 text-xs font-semibold uppercase text-pink-400">Putri</p>
                                    <?php foreach ($putriTop3 as $finalist): ?>
                                        <?php
                                        $participantName = $finalist->participant?->name ?? '-';
                                        if ($isMsqMfq) {
                                            $key = $category->id . '_' . $finalist->participant?->district_id;
                                            $count = $districtParticipantCounts[$key] ?? 1;
                                            if ($count > 1) {
                                                $participantName .= ' dkk.';
                                            }
                                        }
                                        ?>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="<?= $finalist->finalist_rank === 1 ? 'text-amber-300 font-bold' : 'text-slate-300' ?>">
                                                <?= $finalist->finalist_rank ?>. <?= e($participantName) ?>
                                            </span>
                                            <span class="text-pink-300"><?= number_format($finalist->score, 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="mt-4 flex gap-2">
                        <button
                            type="button"
                            @click="generateForCategory(<?= $category->id ?>, '<?= e(addslashes($category->name)) ?>')"
                            class="flex-1 rounded-lg border border-amber-400/30 bg-amber-400/10 px-3 py-2 text-sm font-semibold text-amber-300 transition-all hover:bg-amber-400/20"
                        >
                            <?= mtq_icon('zap', 'h-4 w-4 inline mr-1') ?>
                            <?= $hasFinalists ? 'Regenerate' : 'Generate' ?>
                        </button>
                        <?php if ($hasFinalists): ?>
                            <button
                                type="button"
                                @click="confirmDelete(<?= $category->id ?>, '<?= e(addslashes($category->name)) ?>')"
                                class="rounded-lg border border-red-400/30 bg-red-400/10 px-3 py-2 text-sm font-semibold text-red-300 transition-all hover:bg-red-400/20"
                            >
                                <?= mtq_icon('trash', 'h-4 w-4') ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Empty State -->
        <?php if ($categories->isEmpty()): ?>
            <section class="glass-card rounded-[2rem] p-12 text-center">
                <?= mtq_icon('inbox', 'h-16 w-16 mx-auto mb-4 text-slate-600') ?>
                <p class="text-lg text-slate-400">Belum ada golongan yang tersedia.</p>
            </section>
        <?php endif; ?>

        <!-- Generate All Modal -->
        <div
            x-show="showGenerateModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @keydown.escape.window="showGenerateModal = false"
        >
            <div
                x-show="showGenerateModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-md rounded-2xl border border-amber-400/30 bg-gradient-to-br from-slate-900 via-sky-950 to-blue-950 p-6 shadow-2xl"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">
                        <?= mtq_icon('zap', 'h-5 w-5 inline mr-2 text-amber-300') ?>
                        Generate Semua Finalis
                    </h3>
                    <button type="button" @click="showGenerateModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-700 hover:text-white">
                        <?= mtq_icon('x', 'h-5 w-5') ?>
                    </button>
                </div>

                <p class="mt-4 text-sm text-slate-300">
                    Akan membuat finalis untuk <strong class="text-amber-300">SEMUA golongan</strong>.
                    Data finalis yang sudah ada akan <strong class="text-red-300">dihapus terlebih dahulu</strong>.
                </p>

                <!-- Loading State -->
                <div x-show="isGenerating" class="mt-6 space-y-4">
                    <div class="flex items-center justify-center gap-3">
                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-amber-400 border-t-transparent"></div>
                        <span class="text-amber-300">Generating finalists...</span>
                    </div>
                </div>

                <!-- Result State -->
                <div x-show="generateResult && !isGenerating" class="mt-6">
                    <div class="rounded-lg border border-emerald-400/30 bg-emerald-400/10 p-4">
                        <p class="text-emerald-300" x-text="generateResult"></p>
                    </div>
                    <button type="button" @click="showGenerateModal = false; generateResult = null" class="mt-4 w-full rounded-lg border border-slate-600 bg-slate-700 px-4 py-2 font-semibold text-white transition-colors hover:bg-slate-600">
                        Tutup
                    </button>
                </div>

                <!-- Error State -->
                <div x-show="generateError && !isGenerating" class="mt-6">
                    <div class="rounded-lg border border-red-400/30 bg-red-400/10 p-4">
                        <p class="text-red-300" x-text="generateError"></p>
                    </div>
                    <button type="button" @click="isGenerating = false; generateError = null" class="mt-4 w-full rounded-lg border border-slate-600 bg-slate-700 px-4 py-2 font-semibold text-white transition-colors hover:bg-slate-600">
                        Tutup
                    </button>
                </div>

                <!-- Confirm Button -->
                <div x-show="!isGenerating && !generateResult && !generateError">
                    <div class="mt-6 flex gap-3">
                        <button type="button" @click="showGenerateModal = false" class="flex-1 rounded-lg border border-slate-600 bg-slate-700 px-4 py-2 font-semibold text-white transition-colors hover:bg-slate-600">
                            Batal
                        </button>
                        <button type="button" @click="generateAll()" class="flex-1 rounded-lg border border-amber-400/50 bg-amber-400/20 px-4 py-2 font-semibold text-amber-300 transition-colors hover:bg-amber-400/30">
                            Ya, Generate
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            x-show="showDeleteModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @keydown.escape.window="showDeleteModal = false"
        >
            <div
                x-show="showDeleteModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-md rounded-2xl border border-red-400/30 bg-gradient-to-br from-slate-900 via-red-950 to-slate-900 p-6 shadow-2xl"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">
                        <?= mtq_icon('trash', 'h-5 w-5 inline mr-2 text-red-400') ?>
                        Hapus Finalis
                    </h3>
                    <button type="button" @click="showDeleteModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-700 hover:text-white">
                        <?= mtq_icon('x', 'h-5 w-5') ?>
                    </button>
                </div>

                <p class="mt-4 text-sm text-slate-300">
                    Hapus semua finalis untuk <strong class="text-red-300" x-text="categoryToDelete"></strong>?
                    Data tidak dapat dikembalikan.
                </p>

                <div class="mt-6 flex gap-3">
                    <button type="button" @click="showDeleteModal = false" class="flex-1 rounded-lg border border-slate-600 bg-slate-700 px-4 py-2 font-semibold text-white transition-colors hover:bg-slate-600">
                        Batal
                    </button>
                    <button type="button" @click="deleteFinalists()" class="flex-1 rounded-lg border border-red-400/50 bg-red-400/20 px-4 py-2 font-semibold text-red-300 transition-colors hover:bg-red-400/30">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div
            x-data="{ show: false, message: '', type: 'success' }"
            x-show="show"
            x-transition
            class="fixed bottom-4 right-4 z-50 rounded-xl border px-4 py-3 shadow-xl"
            :class="type === 'success' ? 'border-emerald-400/50 bg-emerald-400/10 text-emerald-300' : 'border-red-400/50 bg-red-400/10 text-red-300'"
            @toast.window="
                message = $event.detail.message;
                type = $event.detail.type || 'success';
                show = true;
                setTimeout(() => show = false, 4000);
            "
        >
            <p class="font-semibold" x-text="message"></p>
        </div>
    </main>

    <?php require __DIR__.'/../partials/app-footer.php'; ?>

    <script>
        function generateAll() {
            this.isGenerating = true;
            this.generateError = null;
            this.generateResult = null;

            fetch('<?= e(route('finalists.generate-all')) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                this.isGenerating = false;
                if (data.success) {
                    this.generateResult = data.message;
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'success' } }));
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.generateError = data.message;
                }
            })
            .catch(error => {
                this.isGenerating = false;
                this.generateError = 'Terjadi kesalahan: ' + error.message;
            });
        }

        function generateForCategory(categoryId, categoryName) {
            if (!confirm('Generate finalis untuk ' + categoryName + '?')) return;

            fetch('/admin/finalis/generate/' + categoryId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'success' } }));
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'error' } }));
                }
            })
            .catch(error => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Terjadi kesalahan: ' + error.message, type: 'error' } }));
            });
        }

        function confirmDelete(categoryId, categoryName) {
            this.categoryToDelete = categoryName;
            this.categoryToDeleteId = categoryId;
            this.showDeleteModal = true;
        }

        function deleteFinalists() {
            if (!this.categoryToDeleteId) return;

            fetch('/admin/finalis/category/' + this.categoryToDeleteId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                this.showDeleteModal = false;
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'success' } }));
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: 'error' } }));
                }
            })
            .catch(error => {
                this.showDeleteModal = false;
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Terjadi kesalahan: ' + error.message, type: 'error' } }));
            });
        }
    </script>

    <?php foreach ($jsAssets as $src): ?>
        <script type="module" src="<?= e($src) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
