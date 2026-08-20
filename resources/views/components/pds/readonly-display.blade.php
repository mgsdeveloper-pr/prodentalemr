@props(['label' => null, 'value' => null])

<div {{ $attributes->merge(['class' => 'pds-readonly-display rounded-lg border border-slate-200 bg-slate-50 px-3 py-2']) }}>
    @if ($label)
        <p class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</p>
    @endif

    <p class="mt-1 text-sm font-medium text-slate-950">{{ $value ?? $slot }}</p>
</div>
