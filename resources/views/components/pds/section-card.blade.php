@props(['title' => null, 'description' => null])

<x-pds.card :title="$title" :description="$description" {{ $attributes->merge(['class' => 'pds-section-card']) }}>
    {{ $slot }}
</x-pds.card>
