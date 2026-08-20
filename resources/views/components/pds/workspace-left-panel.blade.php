@props([
    'label' => 'Workspace context',
])

<aside
    {{ $attributes->merge(['class' => 'pds-workspace-panel pds-workspace-panel--left']) }}
    aria-label="{{ $label }}"
>
    {{ $slot }}
</aside>
