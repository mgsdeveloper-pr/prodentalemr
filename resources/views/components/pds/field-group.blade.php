@props(['label' => null, 'for' => null, 'helper' => null])

<div {{ $attributes->merge(['class' => 'pds-field-group space-y-1.5']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="text-sm font-semibold text-slate-700">{{ $label }}</label>
    @endif

    {{ $slot }}

    @if ($helper)
        <p class="text-xs text-slate-500">{{ $helper }}</p>
    @endif
</div>
