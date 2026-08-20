@props(['title' => null])

<aside {{ $attributes->merge(['class' => 'pds-info-card rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900']) }}>
    @if ($title)
        <h3 class="mb-1 font-semibold">{{ $title }}</h3>
    @endif

    {{ $slot }}
</aside>
