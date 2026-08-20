@props(['label' => 'Loading'])

<div {{ $attributes->merge(['class' => 'pds-loading-state flex items-center gap-2 text-sm text-slate-600']) }} role="status">
    <x-pds.spinner />
    <span>{{ $label }}</span>
</div>
