@props(['horizontal' => false])
<img src="{{ asset('images/brand/denentiq-' . ($horizontal ? 'horizontal' : 'icon') . '-primary.svg') }}"
    alt="Denentiq" {{ $attributes->merge(['class' => 'object-contain']) }}>
