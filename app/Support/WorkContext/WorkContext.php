<?php

namespace App\Support\WorkContext;

use Illuminate\Support\Collection;

class WorkContext
{
    /**
     * @param  iterable<ContextCard|array<string, mixed>>  $cards
     */
    public function __construct(
        public readonly string $title = 'Work Context',
        public readonly ?string $description = null,
        public readonly iterable $cards = [],
        public readonly ?array $search = null,
        public readonly ?array $ai = null,
    ) {}

    public function cards(): Collection
    {
        return collect($this->cards)->map(
            fn (ContextCard|array $card): ContextCard => $card instanceof ContextCard
                ? $card
                : ContextCard::fromArray($card),
        );
    }
}
