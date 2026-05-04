<section
    x-data
    x-on:mtq-score-updated.window="$wire.$refresh()"
    class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[0.95fr_1.05fr]"
>
    <div class="glass-card rounded-[2rem] p-6 sm:p-8">
        <div class="badge-live">Input Nilai</div>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">Form penilaian yang cepat dan jelas</h1>
        <p class="mt-3 text-sm leading-7 text-slate-300">
            Simpan nilai peserta, lalu sistem pembaruan otomatis akan membantu menyegarkan dashboard tanpa menunggu muat ulang penuh.
        </p>

        @if (session('success'))
            <div class="mt-6 rounded-3xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="save" class="mt-6 space-y-5">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-200">Peserta</label>
                <select wire:model="participant_id" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20">
                    <option value="">Pilih peserta</option>
                    @foreach ($participants as $participant)
                        <option value="{{ $participant->id }}">
                            {{ $participant->name }} - {{ $participant->category?->name }}
                        </option>
                    @endforeach
                </select>
                @error('participant_id') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-200">Nama Dewan Hakim</label>
                    <input wire:model="judge_name" type="text" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20" placeholder="Dewan Hakim 1">
                    @error('judge_name') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-200">Nilai</label>
                    <input wire:model="score" type="number" min="0" max="100" step="0.01" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20" placeholder="92.50">
                    @error('score') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-200">Catatan</label>
                <textarea wire:model="remarks" rows="4" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-400/20" placeholder="Masukan singkat untuk peserta"></textarea>
                @error('remarks') <p class="mt-2 text-sm text-rose-300">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center rounded-2xl bg-gradient-to-r from-cyan-400 to-blue-500 px-6 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/25 transition hover:scale-[1.02]">
                    Simpan nilai
                </button>
                <p class="text-sm text-slate-400">Pembaruan langsung akan tersalurkan ke dashboard.</p>
            </div>
        </form>
    </div>

    <div class="space-y-6">
        <div class="glass-card rounded-[2rem] p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Panduan Singkat</p>
            <div class="mt-5 space-y-4">
                @foreach ([
                    ['title' => 'Pilih peserta', 'body' => 'Pastikan kategori dan nama peserta sesuai sebelum menyimpan skor.'],
                    ['title' => 'Isi nilai dengan tenang', 'body' => 'Komponen ini didesain untuk input cepat tanpa gangguan visual.'],
                    ['title' => 'Simpan, lalu lihat efeknya', 'body' => 'Pembaruan otomatis akan menyalurkan perubahan ke leaderboard dan papan pengumuman.'],
                ] as $step)
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                        <p class="font-semibold text-white">{{ $step['title'] }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $step['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="glass-card rounded-[2rem] p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Peserta Tersedia</p>
            <div class="mt-4 space-y-3">
                @foreach ($participants->take(5) as $participant)
                    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-white">{{ $participant->name }}</p>
                                <p class="text-xs text-slate-400">{{ $participant->institution }}</p>
                            </div>
                            <span class="rounded-full bg-cyan-400/10 px-3 py-1 text-xs font-semibold text-cyan-200">{{ $participant->category?->name }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
