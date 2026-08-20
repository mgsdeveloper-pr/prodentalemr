@props(['label' => 'Loading page'])

<div {{ $attributes->merge(['class' => 'pds-page-loader flex min-h-48 flex-col items-center justify-center gap-3 rounded-lg border border-slate-200 bg-white text-sm text-slate-600']) }} role="status">
    <x-pds.spinner class="h-6 w-6" />
    <span>{{ $label }}</span>
</div>
