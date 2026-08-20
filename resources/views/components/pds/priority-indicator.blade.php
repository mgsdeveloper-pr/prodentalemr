@props(['priority' => 'normal'])

@php
    $colorClass = match ($priority) {
        'urgent', 'high' => 'bg-rose-600',
        'medium' => 'bg-amber-500',
        'low' => 'bg-sky-500',
        default => 'bg-slate-400',
    };
@endphp

<span {{ $attributes->merge(['class' => 'pds-priority-indicator inline-flex items-center gap-2 text-xs font-semibold text-slate-700']) }}>
    <span class="h-2.5 w-2.5 rounded-full {{ $colorClass }}" aria-hidden="true"></span>
    {{ $slot->isEmpty() ? ucfirst((string) $priority) : $slot }}
</span>
