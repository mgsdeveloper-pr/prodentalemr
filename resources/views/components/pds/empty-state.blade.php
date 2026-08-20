@props(['title' => 'No records found', 'description' => null])

<div {{ $attributes->merge(['class' => 'pds-empty-state pwdl-empty-state rounded-lg border border-dashed border-slate-300 bg-white p-6 text-center']) }}>
    <p class="text-sm font-semibold text-slate-950">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
