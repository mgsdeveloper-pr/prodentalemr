<?php

namespace App\Support\AppShell;

use Filament\Panel;
use Filament\View\PanelsRenderHook;

class AppShell
{
    public static function register(Panel $panel, string $workspace): Panel
    {
        return $panel
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.appshell.styles')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => self::renderForAuthenticatedUser('filament.appshell.global-header', [
                    'workspace' => $workspace,
                    'signOutUrl' => self::signOutUrl($workspace),
                ]),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_HEADING_AFTER,
                fn (): string => self::renderForAuthenticatedUser('filament.appshell.workspace-header', [
                    'workspace' => $workspace,
                ]),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
                fn (): string => self::renderForAuthenticatedUser('filament.appshell.action-toolbar'),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => self::renderForAuthenticatedUser('filament.appshell.compact-footer', [
                    'workspace' => $workspace,
                ]),
            );
    }

    public static function registerSharedSidebar(
        Panel $panel,
        ?callable $scopeSwitcher = null,
        ?callable $notificationContext = null,
    ): Panel {
        $panel
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_BEFORE,
                fn (): string => self::renderForAuthenticatedUser('filament.shared.partials.sidebar-greeting'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                fn (): string => self::renderForAuthenticatedUser('filament.shared.partials.sidebar-toggle'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => self::renderForAuthenticatedUser('filament.shared.partials.sidebar-user-footer'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.shared.partials.sidebar-theme')->render()
                    . view('filament.shared.partials.page-header-theme')->render(),
            );

        if ($scopeSwitcher !== null) {
            $panel->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => auth()->check() ? (string) $scopeSwitcher() : '',
            );
        }

        if ($notificationContext !== null) {
            $panel->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => self::renderNotificationBell((array) $notificationContext()),
            );
        }

        return $panel;
    }

    protected static function signOutUrl(string $workspace): string
    {
        $route = match ($workspace) {
            'platform' => 'saas.signout',
            'verification' => 'admin.signout',
            'clinic', 'organization' => 'clinic.signout',
            'dso' => 'dso.signout',
            default => null,
        };

        return $route !== null && \Route::has($route) ? route($route) : url('/logout');
    }

    protected static function renderNotificationBell(array $context): string
    {
        if (! auth()->check()) {
            return '';
        }

        return view('filament.shared.partials.verification-notification-bell', [
            'panel' => $context['panel'] ?? 'verification',
            'clinicId' => $context['clinicId'] ?? null,
        ])->render();
    }

    protected static function renderForAuthenticatedUser(string $view, array $data = []): string
    {
        if (! auth()->check()) {
            return '';
        }

        return view($view, $data)->render();
    }
}
