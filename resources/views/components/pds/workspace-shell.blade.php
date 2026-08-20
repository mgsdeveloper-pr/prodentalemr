@props([
    'label' => null,
])

<section
    {{ $attributes->merge(['class' => 'pds-workspace-shell pwdl-workspace']) }}
    @if ($label) aria-label="{{ $label }}" @endif
>
    {{ $slot }}
</section>
