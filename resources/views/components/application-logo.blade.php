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
    <div {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-3xl bg-gradient-to-br from-amber-500 via-orange-500 to-slate-900 font-bold tracking-[0.2em] text-white shadow-lg']) }}>
        {{ $initials ?: 'PE' }}
    </div>
@endif
