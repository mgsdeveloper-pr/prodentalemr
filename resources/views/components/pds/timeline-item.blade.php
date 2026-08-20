@props(['title' => null, 'meta' => null, 'tone' => 'info'])

@php
    $dotClass = match ($tone) {
        'success' => 'bg-emerald-600',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-rose-600',
        'neutral' => 'bg-slate-400',
        default => 'bg-teal-600',
    };
@endphp

<article {{ $attributes->merge(['class' => 'pds-timeline-item relative ps-5']) }}>
    <span class="absolute left-0 top-2 h-2.5 w-2.5 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
        @if ($title || $meta || isset($header))
            <header class="mb-2 flex flex-wrap items-start justify-between gap-2">
                @isset($header)
                    {{ $header }}
                @else
                    @if ($title)
                        <h3 class="text-sm font-semibold text-slate-950">{{ $title }}</h3>
                    @endif

                    @if ($meta)
                        <span class="text-xs text-slate-500">{{ $meta }}</span>
                    @endif
                @endisset
            </header>
        @endif

        <div class="text-sm leading-6 text-slate-700">
            {{ $slot }}
        </div>
    </div>
</article>
