@props(['columns' => 2, 'gap' => 'md'])

@php
    $columnClass = match ((int) $columns) {
        1 => 'grid-cols-1',
        3 => 'grid-cols-1 md:grid-cols-3',
        4 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4',
        default => 'grid-cols-1 md:grid-cols-2',
    };

    $gapClass = match ($gap) {
        'sm' => 'gap-2',
        'lg' => 'gap-6',
        default => 'gap-4',
    };
@endphp

<div {{ $attributes->merge(['class' => "pds-grid grid {$columnClass} {$gapClass}"]) }}>
    {{ $slot }}
</div>
