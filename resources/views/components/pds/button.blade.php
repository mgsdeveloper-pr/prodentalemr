@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-300',
        'success' => 'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700 focus-visible:ring-emerald-300',
        'danger' => 'border-rose-600 bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-300',
        'ghost' => 'border-transparent bg-transparent text-slate-700 hover:bg-slate-100 focus-visible:ring-slate-300',
        'toolbar' => 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-teal-300',
        default => 'pds-button--primary-token border-teal-700 bg-teal-700 text-white hover:bg-teal-800 focus-visible:ring-teal-300',
    };

    $sizeClass = match ($size) {
        'sm' => 'min-h-8 px-3 text-xs',
        'lg' => 'min-h-11 px-5 text-sm',
        'icon' => 'h-9 w-9 justify-center p-0',
        default => 'min-h-9 px-4 text-sm',
    };

    $classes = "pds-button inline-flex items-center justify-center gap-2 rounded-lg border font-semibold leading-none transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 {$variantClass} {$sizeClass}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
