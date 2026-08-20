@props(['title' => 'Workspace Readiness', 'description' => null, 'items' => []])

@php
    $collection = collect($items);
    $readyCount = $collection->where('status', 'ready')->count();
    $totalCount = $collection->count();
    $summary = $totalCount > 0 ? "{$readyCount}/{$totalCount} Ready" : 'No checks';
@endphp

<x-pds.section-card :title="$title" :description="$description" {{ $attributes->merge(['class' => 'pds-workspace-readiness-card']) }}>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-pds.status-pill status="{{ $readyCount === $totalCount && $totalCount > 0 ? 'success' : 'warning' }}">
            {{ $summary }}
        </x-pds.status-pill>
    </div>

    @if ($collection->isEmpty())
        <x-pds.empty-state title="No readiness checks available" description="This workspace has no readiness state to display." />
    @else
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @foreach ($collection as $item)
                @php
                    $isReady = ($item['status'] ?? null) === 'ready';
                @endphp

                <div class="rounded-lg border {{ $isReady ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-3">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $isReady ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }} text-xs font-bold">
                            {!! $isReady ? '&check;' : '!' !!}
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold {{ $isReady ? 'text-emerald-950' : 'text-amber-950' }}">{{ $item['label'] ?? 'Readiness check' }}</p>
                            @if (filled($item['description'] ?? null))
                                <p class="mt-1 text-sm {{ $isReady ? 'text-emerald-800' : 'text-amber-800' }}">{{ $item['description'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-pds.section-card>
