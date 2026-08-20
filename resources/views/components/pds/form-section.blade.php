@props(['title' => null, 'description' => null])

<fieldset {{ $attributes->merge(['class' => 'pds-form-section space-y-4 rounded-lg border border-slate-200 bg-white p-4']) }}>
    @if ($title || $description)
        <legend class="px-1">
            @if ($title)
                <span class="text-sm font-semibold text-slate-950">{{ $title }}</span>
            @endif

            @if ($description)
                <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
            @endif
        </legend>
    @endif

    {{ $slot }}
</fieldset>
