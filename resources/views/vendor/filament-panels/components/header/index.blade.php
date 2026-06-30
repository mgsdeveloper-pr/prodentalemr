@props([
    'actions' => [],
    'actionsAlignment' => null,
    'breadcrumbs' => [],
    'heading' => null,
    'subheading' => null,
])

<style>
    .pd-hero-header {
        display: flex;
        gap: 24px;
        justify-content: space-between;
        align-items: stretch;
    }

    .pd-hero-header__content {
        flex: 1 1 auto;
        min-width: 0;
    }

    .pd-hero-header__actions {
        display: flex;
        flex: 0 0 auto;
        gap: 10px;
        align-items: flex-end;
        justify-content: flex-end;
        align-self: flex-end;
        min-width: max-content;
    }

    @media (max-width: 960px) {
        .pd-hero-header {
            flex-direction: column;
        }

        .pd-hero-header__content {
            flex: none;
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
    style="border: 1px solid #dbe4ee; border-radius: 26px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08); overflow: hidden; padding: 24px;"
>
    <div class="pd-hero-header__content" style="display: flex; flex-direction: column; justify-content: space-between; gap: 12px; min-width: 0;">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_HEADING_BEFORE, scopes: $this->getRenderHookScopes()) }}

        <div style="display: flex; flex-direction: column; gap: 8px; min-width: 0;">
            @if (filled($heading))
                <h1 class="fi-header-heading" style="margin: 0; font-size: 32px; font-weight: 800; color: #0f172a; line-height: 1.12;">
                    {{ $heading }}
                </h1>
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_HEADING_AFTER, scopes: $this->getRenderHookScopes()) }}

            @if (filled($subheading))
                <p class="fi-header-subheading" style="margin: 0; max-width: 980px; font-size: 15px; line-height: 1.75; color: #64748b;">
                    {{ $subheading }}
                </p>
            @endif
        </div>

        @if ($breadcrumbs)
            <div style="display: inline-flex; align-items: center; gap: 8px; width: fit-content; padding: 6px 11px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe;">
                <x-filament::breadcrumbs
                    :breadcrumbs="$breadcrumbs"
                    style="margin: 0;"
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
                <div style="display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px;">
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
