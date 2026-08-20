@props(['width' => 'default'])

@php
    $widthClass = match ($width) {
        'narrow' => 'max-w-5xl',
        'wide' => 'max-w-7xl',
        'full' => 'max-w-none',
        default => 'max-w-6xl',
    };
@endphp

<div {{ $attributes->merge(['class' => "pds-page-container mx-auto w-full {$widthClass} px-4 py-4 sm:px-6 lg:px-8"]) }}>
    {{ $slot }}
</div>
