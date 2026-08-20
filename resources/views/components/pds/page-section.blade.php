@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'pds-page-section space-y-3']) }}>
    @if ($title || $description || isset($header))
        <div class="space-y-1">
            @isset($header)
                {{ $header }}
            @else
                @if ($title)
                    <h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2>
                @endif

                @if ($description)
                    <p class="text-sm text-slate-600">{{ $description }}</p>
                @endif
            @endisset
        </div>
    @endif

    {{ $slot }}
</section>
