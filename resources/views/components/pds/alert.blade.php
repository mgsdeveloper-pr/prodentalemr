@props(['type' => 'info', 'title' => null])

@php
    $typeClass = match ($type) {
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger', 'error' => 'border-rose-200 bg-rose-50 text-rose-800',
        default => 'border-sky-200 bg-sky-50 text-sky-900',
    };
@endphp

<div {{ $attributes->merge(['class' => "pds-alert rounded-lg border p-3 text-sm {$typeClass}"]) }} role="status">
    @if ($title)
        <p class="font-semibold">{{ $title }}</p>
    @endif
    <div @class(['mt-1' => $title])>{{ $slot }}</div>
</div>
