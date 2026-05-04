<section class="mx-auto max-w-3xl">
    <div class="rounded-[2rem] border p-6 sm:p-8 {{ $panelClass }}">
        <div class="flex flex-wrap items-start gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl border {{ $iconShellClass }}">
                {!! $iconSvg !!}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] {{ $eyebrowClass }}">{{ $codeLabel }}</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-white">{{ $title }}</h1>
                <p class="mt-4 text-base leading-7 text-slate-200">{{ $message }}</p>
                <p class="mt-3 text-sm leading-6 text-slate-400">{{ $hint }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-[1.5rem] border border-white/8 bg-white/5 p-4">
            <p class="text-sm font-semibold text-white">Yang bisa Anda lakukan sekarang</p>
            <div class="mt-3 space-y-2 text-sm leading-6 text-slate-300">
                @foreach ($steps as $step)
                    <p>{{ $step }}</p>
                @endforeach
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            @foreach ($actions as $action)
                @if (($action['type'] ?? 'link') === 'button')
                    <button type="button" @if (!empty($action['onclick'])) onclick="{{ $action['onclick'] }}" @endif class="{{ $action['class'] }}">
                        {{ $action['label'] }}
                    </button>
                @else
                    <a href="{{ $action['href'] }}" class="{{ $action['class'] }}">
                        @if (!empty($action['prefix']))
                            <span>{{ $action['prefix'] }}</span>
                        @endif
                        {{ $action['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</section>
