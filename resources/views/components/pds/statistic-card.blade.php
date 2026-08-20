@props(['label', 'value', 'hint' => null])

<div {{ $attributes->merge(['class' => 'pds-statistic-card rounded-lg border border-slate-200 bg-white p-4 shadow-sm']) }}>
    <p class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-sm text-slate-600">{{ $hint }}</p>
    @endif
</div>
