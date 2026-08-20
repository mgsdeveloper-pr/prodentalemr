@props(['label', 'type' => 'button', 'href' => null])

<x-pds.button :type="$type" :href="$href" variant="toolbar" size="icon" :aria-label="$label" :title="$label" {{ $attributes }}>
    {{ $slot }}
</x-pds.button>
