@props([
    'actions' => [],
    'actionsAlignment' => null,
    'breadcrumbs' => [],
    'heading' => null,
    'subheading' => null,
])

<style>
    .pd-hero-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 1rem;
        width: 100%;
        margin: 0;
        padding: 1rem clamp(1.25rem, 2vw, 2rem);
        border: 0;
        border-bottom: 1px solid #e4e7ec;
        border-radius: 0;
        background: #ffffff;
        box-shadow: none;
    }

    .pd-hero-header__content {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        gap: 0.35rem;
        min-width: 0;
    }

    .pd-hero-header__copy {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
    }

    .pd-hero-header .fi-header-heading {
        margin: 0;
        color: #101828;
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .pd-hero-header .fi-header-subheading {
        margin: 0;
        max-width: 62rem;
        color: #667085;
        font-size: 0.82rem;
        line-height: 1.45;
    }

    .pd-hero-header__breadcrumbs {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
    }

    .pd-hero-header__breadcrumbs .fi-breadcrumbs,
    .pd-hero-header__breadcrumbs .fi-breadcrumbs-list {
        margin: 0;
        padding: 0;
        background: transparent;
    }

    .pd-hero-header__breadcrumbs :is(a, span, li) {
        color: #667085;
        font-size: 0.74rem;
        font-weight: 600;
    }

    .pd-hero-header__breadcrumbs li:last-child :is(a, span),
    .pd-hero-header__breadcrumbs li:last-child {
        color: #344054;
        font-weight: 700;
    }

    .pd-hero-header__actions {
        display: flex;
        flex: 0 0 auto;
        gap: 0.5rem;
        align-items: center;
        justify-content: flex-end;
        align-self: center;
        min-width: max-content;
    }

    .pd-hero-header__actions .fi-btn {
        min-height: 2.35rem;
        border-radius: 0.5rem;
        border-width: 1px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
    }

    @media (max-width: 960px) {
        .pd-hero-header {
            display: flex;
            align-items: stretch;
            flex-direction: column;
            padding: 0.9rem 1.25rem;
        }

        .pd-hero-header__actions {
            align-items: flex-start;
            justify-content: flex-start;
            align-self: stretch;
            min-width: 0;
        }
    }
</style>

<header
    {{
        $attributes->class([
            'fi-header',
            'pd-hero-header',
            'fi-header-has-breadcrumbs' => $breadcrumbs,
        ])
    }}
>
    <div class="pd-hero-header__content">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_HEADING_BEFORE, scopes: $this->getRenderHookScopes()) }}

        <div class="pd-hero-header__copy">
            @if (filled($heading))
                <h1 class="fi-header-heading">
                    {{ $heading }}
                </h1>
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_HEADING_AFTER, scopes: $this->getRenderHookScopes()) }}

            @if (filled($subheading))
                <p class="fi-header-subheading">
                    {{ $subheading }}
                </p>
            @endif
        </div>

        @if ($breadcrumbs)
            <div class="pd-hero-header__breadcrumbs">
                <x-filament::breadcrumbs
                    :breadcrumbs="$breadcrumbs"
                />
            </div>
        @endif
    </div>

    @php
        $beforeActions = \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE, scopes: $this->getRenderHookScopes());
        $afterActions = \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER, scopes: $this->getRenderHookScopes());
    @endphp

    @if (filled($beforeActions) || $actions || filled($afterActions))
        <div class="fi-header-actions-ctn pd-hero-header__actions">
            {{ $beforeActions }}

            @if ($actions)
                <div style="display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.5rem;">
                    <x-filament::actions
                        :actions="$actions"
                        :alignment="$actionsAlignment"
                    />
                </div>
            @endif

            {{ $afterActions }}
        </div>
    @endif
</header>
