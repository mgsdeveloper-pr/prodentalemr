@props(['card'])

@php
    $state = $card->state ?? 'expanded';
    $isCollapsed = method_exists($card, 'isCollapsed') ? $card->isCollapsed() : $state === 'collapsed';
    $isDisabled = method_exists($card, 'isDisabled') ? $card->isDisabled() : $state === 'disabled';
    $isEmpty = method_exists($card, 'isEmpty') ? $card->isEmpty() : (($card->items ?? []) === []);
    $items = collect($card->items ?? []);
@endphp

<details
    {{ $attributes->merge(['class' => 'pds-context-card' . ($card->pinned ? ' pds-context-card--pinned' : '') . ($card->scrollable ? ' pds-context-card--scrollable' : '') . ($isDisabled ? ' pds-context-card--disabled' : '')]) }}
    @if (! $isCollapsed && ! $isDisabled) open @endif
>
    <summary class="pds-context-card__summary">
        <span class="pds-context-card__title">
            {{ $card->title }}
            @if ($card->pinned)
                <span class="pds-context-card__pin">Pinned</span>
            @endif
        </span>
        <span class="pds-context-card__meta">
            @if (filled($card->badge))
                <x-pds.badge>{{ $card->badge }}</x-pds.badge>
            @endif
            <span class="pds-context-card__chevron" aria-hidden="true"></span>
        </span>
    </summary>

    <div class="pds-context-card__body">
        @if (filled($card->description))
            <p class="pds-context-card__description">{{ $card->description }}</p>
        @endif

        @if ($isDisabled)
            <div class="pds-context-card__empty">
                <x-pds.empty-placeholder />
                <span>This context card is reserved for a future platform capability.</span>
            </div>
        @elseif ($state === 'loading')
            <x-pds.loading-state label="Loading context" />
        @elseif ($state === 'error')
            <x-pds.alert type="danger">This context is temporarily unavailable.</x-pds.alert>
        @elseif ($isEmpty)
            <div class="pds-context-card__empty">
                <x-pds.empty-placeholder />
                <span>No context has been supplied for this card.</span>
            </div>
        @elseif (($card->type ?? 'rows') === 'timeline')
            <x-pds.timeline>
                @foreach ($items as $item)
                    <x-pds.timeline-item :title="$item['label'] ?? 'Activity'" :meta="$item['meta'] ?? null">
                        {{ $item['value'] ?? '-' }}
                    </x-pds.timeline-item>
                @endforeach
            </x-pds.timeline>
        @elseif (($card->type ?? 'rows') === 'list')
            <div class="pds-context-card__list">
                @foreach ($items as $item)
                    <div class="pds-context-card__list-item">
                        <div>
                            <div class="pds-context-card__item-label">{{ $item['label'] ?? '-' }}</div>
                            <div class="pds-context-card__item-value">{{ $item['value'] ?? '-' }}</div>
                        </div>
                        @if (filled($item['href'] ?? null))
                            <x-pds.button :href="$item['href']" variant="toolbar" size="sm">Open</x-pds.button>
                        @endif
                    </div>
                @endforeach
            </div>
        @elseif (($card->type ?? 'rows') === 'placeholder')
            <div class="pds-context-card__empty">
                <x-pds.empty-placeholder />
                <span>{{ $card->description ?: 'Reserved for future platform capability.' }}</span>
            </div>
        @else
            <div class="pds-context-card__rows">
                @foreach ($items as $item)
                    <x-pds.readonly-display :label="$item['label'] ?? '-'" :value="$item['value'] ?? '-'" />
                @endforeach
            </div>
        @endif

        @if (! $isDisabled && filled($card->actions))
            <x-pds.action-toolbar class="pds-context-card__actions">
                @foreach ($card->actions as $action)
                    @if (filled($action['onclick'] ?? null))
                        <x-pds.button type="button" variant="toolbar" size="sm" onclick="{{ $action['onclick'] }}">
                            {{ $action['label'] ?? 'Action' }}
                        </x-pds.button>
                    @else
                        <x-pds.button type="button" variant="toolbar" size="sm">
                            {{ $action['label'] ?? 'Action' }}
                        </x-pds.button>
                    @endif
                @endforeach
            </x-pds.action-toolbar>
        @endif

        @if (filled($card->footer))
            <div class="pds-context-card__footer">{{ $card->footer }}</div>
        @endif
    </div>
</details>
