@props(['type' => 'info', 'title' => null])

<x-pds.alert :type="$type" :title="$title" {{ $attributes->merge(['class' => 'pds-toast shadow-lg']) }}>
    {{ $slot }}
</x-pds.alert>
