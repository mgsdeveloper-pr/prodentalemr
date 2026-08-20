@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'pds-section-title min-w-0']) }}>
    <h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2>
    @if ($description)
        <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
    @endif
</div>
