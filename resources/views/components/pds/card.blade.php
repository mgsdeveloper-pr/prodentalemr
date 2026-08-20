@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'pds-card pwdl-card rounded-lg border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title || $description || isset($header))
        <header class="border-b border-slate-100 px-4 py-3">
            @isset($header)
                {{ $header }}
            @else
                @if ($title)
                    <h3 class="text-sm font-semibold text-slate-950">{{ $title }}</h3>
                @endif

                @if ($description)
                    <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
                @endif
            @endisset
        </header>
    @endif

    <div class="p-4">
        {{ $slot }}
    </div>
</section>
