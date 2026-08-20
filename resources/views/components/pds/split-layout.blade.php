@props(['ratio' => 'balanced'])

@php
    $gridClass = match ($ratio) {
        'sidebar-left' => 'lg:grid-cols-[18rem_minmax(0,1fr)]',
        'sidebar-right' => 'lg:grid-cols-[minmax(0,1fr)_18rem]',
        'wide-left' => 'lg:grid-cols-[1.35fr_0.65fr]',
        'wide-right' => 'lg:grid-cols-[0.65fr_1.35fr]',
        default => 'lg:grid-cols-2',
    };
@endphp

<div {{ $attributes->merge(['class' => "pds-split-layout grid grid-cols-1 gap-4 {$gridClass}"]) }}>
    {{ $slot }}
</div>
