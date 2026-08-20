@props([
    'left' => true,
    'right' => true,
])

<div
    {{ $attributes->class([
        'pds-workspace-body',
        'pds-workspace-body--no-left' => ! $left,
        'pds-workspace-body--no-right' => ! $right,
    ]) }}
>
    {{ $slot }}
</div>
