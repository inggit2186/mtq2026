<section
    x-data
    x-on:mtq-score-updated.window="$wire.refreshDashboard()"
    x-on:mtq-announcement-published.window="$wire.refreshDashboard()"
    wire:poll.15s="refreshDashboard"
    class="space-y-8"
>
    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <div class="glass-card rounded-[2rem] p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-4">
                    <div class="badge-live">Dashboard Langsung</div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-white sm:text-5xl">Dashboard e-MTQ</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                            Pantau peserta, sesi, dan pengumuman dalam satu ruang yang tenang, bersih, dan selalu diperbarui.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @php
                        $statLabels = [
                            'participants' => 'Peserta Aktif',
                            'categories' => 'Kategori',
                            'todaySessions' => 'Sesi Hari Ini',
                            'averageScore' => 'Rata-rata Nilai',
                        ];
                    @endphp
                    @foreach ($stats as $label => $value)
                        <div class="rounded-3xl border border-white/10 bg-slate-900/60 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">{{ $statLabels[$label] ?? $label }}</p>
                            <p class="mt-2 text-2xl font-bold text-white">
                                {{ is_numeric($value) ? number_format((float) $value, $label === 'averageScore' ? 2 : 0) : $value }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="glass-card rounded-[2rem] p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-200/80">Status Langsung</p>
            <div class="mt-4 flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-[1.5rem] bg-gradient-to-br from-emerald-400 to-cyan-500 text-2xl font-black text-slate-950">
                    {{ count($leaders) }}
                </div>
                <div>
                    <p class="text-xl font-bold text-white">Papan nilai aktif</p>
                    <p class="text-sm leading-6 text-slate-300">Data terhubung ke sistem pembaruan otomatis dan siap menyegarkan tampilan saat ada skor baru.</p>
                </div>
            </div>

            <div class="mt-6 rounded-[1.75rem] border border-white/10 bg-slate-900/60 p-4">
                <div class="flex items-center justify-between text-sm text-slate-400">
                    <span>Status websocket</span>
                    <span class="font-semibold text-emerald-300">{{ $stats['participants'] ? 'Tersambung' : 'Siaga' }}</span>
                </div>
                <div class="mt-3 h-2 rounded-full bg-white/5">
                    <div class="h-2 w-3/4 rounded-full bg-gradient-to-r from-cyan-400 to-violet-500"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="glass-card rounded-[2rem] p-6 sm:p-8">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Leaderboard</p>
                    <h2 class="mt-1 text-2xl font-bold text-white">Peserta unggulan</h2>
                </div>
                <div class="rounded-full border border-white/10 bg-white/6 px-3 py-2 text-xs text-slate-300">Diperbarui otomatis</div>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.75rem] border border-white/10">
                <table class="min-w-full divide-y divide-white/10">
                    <thead class="bg-white/5 text-left text-xs uppercase tracking-[0.24em] text-slate-400">
                        <tr>
                            <th class="px-5 py-4">Peserta</th>
                            <th class="px-5 py-4">Kategori</th>
                            <th class="px-5 py-4">Skor Terakhir</th>
                            <th class="px-5 py-4">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 bg-slate-950/20">
                        @forelse ($leaders as $leader)
                            <tr class="transition hover:bg-white/5">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-white">{{ $leader['name'] }}</div>
                                    <div class="text-xs text-slate-400">{{ $leader['institution'] }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-300">{{ $leader['category'] }}</td>
                                <td class="px-5 py-4 text-sm font-semibold text-cyan-200">{{ number_format($leader['score'], 2) }}</td>
                                <td class="px-5 py-4 text-sm font-semibold text-emerald-200">{{ number_format($leader['average'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada data peserta.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="glass-card rounded-[2rem] p-6 sm:p-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Jadwal Hari Ini</p>
                        <h2 class="mt-1 text-2xl font-bold text-white">Alur acara</h2>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($schedules as $schedule)
                        <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-white">{{ $schedule['title'] }}</p>
                                    <p class="text-xs text-slate-400">{{ $schedule['stage'] }} · {{ $schedule['venue'] }}</p>
                                </div>
                                <div class="rounded-full bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-200">{{ $schedule['status'] }}</div>
                            </div>
                            <p class="mt-3 text-sm text-slate-300">{{ $schedule['starts_at'] }} - {{ $schedule['ends_at'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada sesi yang dijadwalkan.</p>
                    @endforelse
                </div>
            </div>

            <div class="glass-card rounded-[2rem] p-6 sm:p-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Pengumuman</p>
                        <h2 class="mt-1 text-2xl font-bold text-white">Info terbaru</h2>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($announcements as $announcement)
                        <article class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="font-semibold text-white">{{ $announcement['title'] }}</h3>
                                <span class="rounded-full bg-violet-400/10 px-3 py-1 text-xs font-semibold text-violet-200">{{ $announcement['priority'] }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-300">{{ $announcement['body'] }}</p>
                            <p class="mt-3 text-xs text-slate-400">{{ $announcement['published_at'] }}</p>
                        </article>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada pengumuman.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
