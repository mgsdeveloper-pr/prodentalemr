<?php

namespace App\Support\WorkContext;

class ContextCard
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $actions
     */
    public function __construct(
        public readonly string $title,
        public readonly string $type = 'rows',
        public readonly array $items = [],
        public readonly ?string $description = null,
        public readonly ?string $badge = null,
        public readonly array $actions = [],
        public readonly ?string $footer = null,
        public readonly string $state = 'expanded',
        public readonly bool $pinned = false,
        public readonly bool $scrollable = false,
    ) {}

    public static function fromArray(array $card): self
    {
        return new self(
            title: (string) ($card['title'] ?? 'Context'),
            type: (string) ($card['type'] ?? 'rows'),
            items: (array) ($card['items'] ?? []),
            description: $card['description'] ?? null,
            badge: $card['badge'] ?? null,
            actions: (array) ($card['actions'] ?? []),
            footer: $card['footer'] ?? null,
            state: (string) ($card['state'] ?? 'expanded'),
            pinned: (bool) ($card['pinned'] ?? false),
            scrollable: (bool) ($card['scrollable'] ?? false),
        );
    }

    public function isCollapsed(): bool
    {
        return $this->state === 'collapsed';
    }

    public function isDisabled(): bool
    {
        return $this->state === 'disabled';
    }

    public function isEmpty(): bool
    {
        return $this->state === 'empty' || $this->items === [];
    }
}
