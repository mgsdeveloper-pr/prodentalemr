@php
    $settings = \App\Models\SaasSetting::current();
    $logoUrl = $settings->brandLogoUrl();
    $brandName = $settings->brandName();
    $initials = collect(explode(' ', trim($brandName)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

@if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $brandName }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
@else
    <div {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-lg bg-teal-700 font-bold text-white shadow-sm']) }}>
        {{ $initials ?: 'PE' }}
    </div>
@endif
