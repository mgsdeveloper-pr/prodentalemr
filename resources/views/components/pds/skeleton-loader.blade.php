@props(['lines' => 3])

<div {{ $attributes->merge(['class' => 'pds-skeleton-loader animate-pulse space-y-2']) }} aria-hidden="true">
    @for ($i = 0; $i < (int) $lines; $i++)
        <div class="h-3 rounded-full bg-slate-200" style="width: {{ $i === 0 ? '70' : ($i === (int) $lines - 1 ? '45' : '100') }}%"></div>
    @endfor
</div>
