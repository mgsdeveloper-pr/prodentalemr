@props(['title' => null, 'open' => false])

<x-pds.drawer :title="$title" :open="$open" {{ $attributes->merge(['class' => 'pds-slide-panel']) }}>
    {{ $slot }}
</x-pds.drawer>
