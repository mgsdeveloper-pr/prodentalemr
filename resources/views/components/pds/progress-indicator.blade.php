@props(['value' => 0, 'label' => null])

@php
    $safeValue = max(0, min(100, (int) $value));
@endphp

<div {{ $attributes->merge(['class' => 'pds-progress-indicator space-y-1']) }}>
    @if ($label)
        <div class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-700">
            <span>{{ $label }}</span>
            <span>{{ $safeValue }}%</span>
        </div>
    @endif

    <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $safeValue }}">
        <div class="h-full rounded-full bg-teal-700" style="width: {{ $safeValue }}%"></div>
    </div>
</div>
