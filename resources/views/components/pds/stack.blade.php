@props(['gap' => 'md'])

@php
    $gapClass = match ($gap) {
        'xs' => 'gap-1',
        'sm' => 'gap-2',
        'lg' => 'gap-6',
        'xl' => 'gap-8',
        default => 'gap-4',
    };
@endphp

<div {{ $attributes->merge(['class' => "pds-stack flex flex-col {$gapClass}"]) }}>
    {{ $slot }}
</div>
