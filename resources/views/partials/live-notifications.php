<?php require __DIR__.'/sweet-alerts.php'; ?>
<?php
    $impersonation = session('impersonation', []);
?>
<?php if (filled($impersonation['original_user_id'] ?? null)): ?>
    <div class="sticky top-4 z-[130] mx-auto mb-4 w-full max-w-7xl px-4">
        <div class="rounded-[1.25rem] border border-amber-400/25 bg-amber-400/12 px-4 py-3 text-amber-50 shadow-[0_20px_45px_-28px_rgba(245,158,11,0.35)] backdrop-blur">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold">Mode login sebagai user lain aktif</p>
                    <p class="mt-1 text-sm leading-6 opacity-90">
                        Admin <?= e((string) ($impersonation['original_user_name'] ?? '-')) ?> sedang masuk sebagai
                        <?= e((string) ($impersonation['target_user_name'] ?? auth()->user()?->name ?? '-')) ?>.
                        Kembali ke akun admin kapan saja lewat tombol di bawah.
                    </p>
                </div>
                <form method="POST" action="<?= e(route('admin.impersonate.stop')) ?>">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="rounded-full border border-amber-300/30 bg-amber-300/10 px-3 py-2 text-xs font-semibold text-amber-50 transition hover:bg-amber-300/20">
                        Kembali ke akun admin
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
<script>
    window.MTQ_USER_ROLE = <?= json_encode((string) auth()->user()?->role) ?>;
</script>
<div
    id="mtq-live-notifications"
    x-data
    class="pointer-events-none fixed right-4 top-24 z-[80] flex w-full max-w-sm flex-col gap-3"
>
    <template x-for="notification in $store.ui.notifications" :key="notification.id">
        <div
            class="pointer-events-auto rounded-[1.5rem] border px-4 py-4 shadow-[0_20px_45px_-28px_rgba(14,165,233,0.55)] backdrop-blur"
            x-bind:class="{
                'border-cyan-400/25 bg-slate-900/92 text-slate-100': notification.tone === 'info' || notification.tone === 'score',
                'border-emerald-400/25 bg-emerald-400/10 text-emerald-50': notification.tone === 'success',
                'border-amber-400/25 bg-amber-400/10 text-amber-50': notification.tone === 'warning'
            }"
        >
            <div class="flex items-start gap-3">
                <div class="mt-1 h-2.5 w-2.5 rounded-full bg-current opacity-80"></div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold" x-text="notification.title"></p>
                    <p class="mt-1 text-sm leading-6 opacity-90" x-text="notification.message"></p>
                </div>
            </div>
        </div>
    </template>
</div>
