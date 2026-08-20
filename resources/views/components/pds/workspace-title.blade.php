@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'pds-workspace-title min-w-0']) }}>
    <h1 class="truncate text-xl font-semibold text-slate-950">{{ $title }}</h1>
    @if ($description)
        <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
    @endif
</div>
