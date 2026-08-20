@props(['type' => 'info'])

@php
    $typeClass = match ($type) {
        'success' => 'text-emerald-700',
        'warning' => 'text-amber-800',
        'danger', 'error' => 'text-rose-700',
        default => 'text-sky-700',
    };
@endphp

<p {{ $attributes->merge(['class' => "pds-inline-message text-sm font-medium {$typeClass}"]) }}>
    {{ $slot }}
</p>
