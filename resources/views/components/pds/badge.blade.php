@props(['color' => 'slate'])

@php
    $colorClass = match ($color) {
        'teal' => 'pds-badge--brand border-teal-200 bg-teal-50 text-teal-700',
        'blue' => 'border-sky-200 bg-sky-50 text-sky-700',
        'green' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'yellow' => 'border-amber-200 bg-amber-50 text-amber-800',
        'red' => 'border-rose-200 bg-rose-50 text-rose-700',
        default => 'border-slate-200 bg-slate-50 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "pds-badge inline-flex min-h-6 items-center rounded-md border px-2 text-xs font-semibold {$colorClass}"]) }}>
    {{ $slot }}
</span>
