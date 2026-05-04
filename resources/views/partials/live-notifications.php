<?php require __DIR__.'/sweet-alerts.php'; ?>
<div
    x-data
    class="pointer-events-none fixed right-4 top-4 z-[80] flex w-full max-w-sm flex-col gap-3"
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
