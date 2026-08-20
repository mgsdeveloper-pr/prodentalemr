@props([
    'label' => 'Workspace awareness',
])

<aside
    {{ $attributes->merge(['class' => 'pds-workspace-panel pds-workspace-panel--right']) }}
    aria-label="{{ $label }}"
>
    {{ $slot }}
</aside>
