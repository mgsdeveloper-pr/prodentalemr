@props(['status' => 'neutral'])

@php
    $statusClass = match ($status) {
        'success', 'active', 'complete' => 'pds-status-pill--success border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning', 'pending' => 'border-amber-200 bg-amber-50 text-amber-800',
        'danger', 'failed' => 'border-rose-200 bg-rose-50 text-rose-700',
        'info' => 'border-sky-200 bg-sky-50 text-sky-700',
        default => 'border-slate-200 bg-slate-50 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "pds-status-pill inline-flex min-h-6 items-center rounded-full border px-2.5 text-xs font-semibold {$statusClass}"]) }}>
    {{ $slot }}
</span>
