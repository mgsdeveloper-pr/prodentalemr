@props(['padded' => true])

<div {{ $attributes->merge(['class' => 'pds-content-section rounded-lg border border-slate-200 bg-white shadow-sm' . ($padded ? ' p-4 sm:p-5' : '')]) }}>
    {{ $slot }}
</div>
