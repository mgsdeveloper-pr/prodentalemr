<x-filament-widgets::widget>
    <style>
        .vq-overview { container-type: inline-size; color: var(--pwdl-text-primary); font-size: var(--pwdl-font-size-body, 0.875rem); line-height: 1.5; }
        .vq-heading { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
        .vq-title { margin: 0; font-size: var(--pwdl-font-size-body, 0.875rem); line-height: 1.5; font-weight: 700; letter-spacing: 0; }
        .vq-reset { display: inline-flex; align-items: center; gap: 8px; min-height: 36px; padding: 6px 0; color: var(--pwdl-brand-accent); font-size: 14px; font-weight: 600; cursor: pointer; }
        .vq-icon { width: 18px; height: 18px; flex-shrink: 0; }
        .vq-strip { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); border: 1px solid var(--pwdl-border-default); border-radius: 8px; background: var(--pwdl-surface-card); overflow: hidden; }
        .vq-queue { position: relative; display: flex; flex-direction: column; gap: 5px; min-width: 0; min-height: 90px; padding: 14px 17px; border: 0; border-right: 1px solid var(--pwdl-border-default); background: transparent; text-align: left; cursor: pointer; color: inherit; }
        .vq-queue:last-child { border-right: 0; }
        .vq-label { display: block; min-height: 36px; color: var(--pwdl-text-muted); font-size: var(--pwdl-font-size-caption, 0.75rem); line-height: 18px; font-weight: 400; overflow-wrap: anywhere; }
        .vq-total { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .vq-number { font-size: 21px; line-height: 28px; font-weight: 850; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
        .vq-chevron { width: 16px; height: 16px; color: var(--pwdl-text-muted); flex-shrink: 0; }
        .vq-queue:hover { background: var(--pwdl-surface-muted); }
        .vq-queue[aria-pressed="true"] { background: var(--pwdl-brand-primary-soft); box-shadow: inset 0 3px 0 var(--pwdl-brand-accent); }
        .vq-queue[aria-pressed="true"] .vq-label, .vq-queue[aria-pressed="true"] .vq-chevron { color: var(--pwdl-brand-accent); }
        .vq-queue:focus-visible { outline: 2px solid var(--pwdl-brand-accent); outline-offset: -4px; }
        .vq-reset:focus-visible { outline: 2px solid var(--pwdl-brand-accent); outline-offset: 4px; }
        .vq-queue--alert .vq-number { color: var(--pwdl-status-danger-text); }
        .vq-queue--warning .vq-number { color: var(--pwdl-status-warning-text); }
        @container (max-width: 850px) {
            .vq-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .vq-queue { border-bottom: 1px solid var(--pwdl-border-subtle); }
            .vq-queue:nth-child(even) { border-right: 0; }
            .vq-queue:last-child { grid-column: 1 / -1; border-bottom: 0; }
        }
        @container (max-width: 380px) {
            .vq-strip { grid-template-columns: minmax(0, 1fr); }
            .vq-queue { border-right: 0; min-height: 78px; }
            .vq-label { min-height: 20px; }
        }
    </style>
    <section class="vq-overview" aria-label="Verification work queues">
        <div class="vq-heading">
            <h2 class="vq-title">Work Queues</h2>
            <button type="button" class="vq-reset" wire:click="applyFilter" aria-pressed="{{ blank($activeFilter) ? 'true' : 'false' }}">
                <x-filament::icon icon="heroicon-o-list-bullet" class="vq-icon" />
                All requests
            </button>
        </div>
        <div class="vq-strip">
            @foreach ($links as $link)
                @php
                    $isActive = filled($link['filter']) && $activeFilter === $link['filter'];
                    $tone = $link['metric'] > 0 ? match ($link['filter']) {
                        'urgent_requests', 'overdue' => 'alert',
                        'returned_for_rework' => 'warning',
                        default => 'neutral',
                    } : 'neutral';
                @endphp
                <button type="button"
                    wire:key="overview-{{ $link['filter'] }}"
                    wire:click="applyFilter('{{ $link['filter'] }}')"
                    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                    class="vq-queue vq-queue--{{ $tone }}">
                    <span class="vq-label">
                        {{ $link['title'] }}
                    </span>
                    <span class="vq-total">
                        <span class="vq-number">{{ number_format($link['metric']) }}</span>
                        <x-filament::icon icon="heroicon-o-chevron-right" class="vq-chevron" />
                    </span>
                </button>
            @endforeach
        </div>
    </section>
</x-filament-widgets::widget>
