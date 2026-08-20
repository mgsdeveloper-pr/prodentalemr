@props(['context'])

@php
    $cards = method_exists($context, 'cards') ? $context->cards() : collect($context['cards'] ?? []);
@endphp

<aside {{ $attributes->merge(['class' => 'pds-work-context-panel']) }} aria-label="{{ $context->title ?? 'Work Context' }}">
    <div class="pds-work-context-panel__header">
        <div>
            <h2>{{ $context->title ?? 'Work Context' }}</h2>
            @if (filled($context->description ?? null))
                <p>{{ $context->description }}</p>
            @endif
        </div>
        @if (filled($context->search ?? null))
            <x-pds.badge>Search Ready</x-pds.badge>
        @endif
    </div>

    <div class="pds-work-context-panel__cards">
        @foreach ($cards as $card)
            <x-pds.context-card :card="$card" />
        @endforeach
    </div>
</aside>
