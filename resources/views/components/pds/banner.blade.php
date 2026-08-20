@props(['type' => 'info'])

<x-pds.alert :type="$type" {{ $attributes->merge(['class' => 'pds-banner']) }}>
    {{ $slot }}
</x-pds.alert>
