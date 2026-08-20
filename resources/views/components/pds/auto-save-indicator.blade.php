@props(['status' => 'neutral'])

@php
    $statusClass = match ($status) {
        'success', 'saved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning', 'unsaved' => 'border-amber-200 bg-amber-50 text-amber-800',
        'info', 'saving' => 'border-sky-200 bg-sky-50 text-sky-700',
        default => 'border-slate-200 bg-slate-50 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "pds-auto-save-indicator inline-flex min-h-7 items-center gap-2 rounded-full border px-3 text-xs font-semibold {$statusClass}"]) }}>
    <span class="h-2 w-2 rounded-full bg-current opacity-70" aria-hidden="true"></span>
    {{ $slot }}
</span>
