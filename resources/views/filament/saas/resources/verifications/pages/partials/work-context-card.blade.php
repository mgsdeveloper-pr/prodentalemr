@props(['title', 'description' => null])

<x-pds.side-panel :title="$title" {{ $attributes->merge(['class' => 'verification-work-context-card']) }}>
    @if ($description)
        <x-pds.helper-text class="mb-3">{{ $description }}</x-pds.helper-text>
    @endif

    {{ $slot }}
</x-pds.side-panel>
