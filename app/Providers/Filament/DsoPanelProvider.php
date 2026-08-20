<?php

namespace App\Providers\Filament;

use App\Filament\Dso\Pages\ClinicDirectory;
use App\Filament\Dso\Pages\Dashboard;
use App\Filament\Dso\Pages\Reports;
use App\Filament\Dso\Pages\RolesAndPermissions;
use App\Filament\Dso\Pages\Users;
use App\Http\Middleware\PanelAuthenticateRedirect;
use App\Support\AppShell\AppShell;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DsoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return AppShell::registerSharedSidebar(AppShell::register($panel
            ->id('dso')
            ->path('dso')
            ->login()
            ->userMenu()
            ->userMenuItems([
                'clinic_workspace' => MenuItem::make()
                    ->label('Open Clinic Workspace')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (): string => url('/clinic'))
                    ->visible(fn (): bool => auth()->check() && auth()->user()->canAccessPanel(app(\Filament\PanelRegistry::class)->get('clinic')))
                    ->sort(850),
                'logout' => MenuItem::make()->hidden(),
                'sign_out' => MenuItem::make()
                    ->label('Sign out')
                    ->icon(\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle)
                    ->url(fn (): string => route('dso.signout'))
                    ->sort(PHP_INT_MAX),
            ])
            ->colors([
                'primary' => Color::Teal,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Dashboard'),
                NavigationGroup::make()->label('Network'),
                NavigationGroup::make()->label('Reports'),
                NavigationGroup::make()->label('Settings'),
            ])
            ->pages([
                Dashboard::class,
                ClinicDirectory::class,
                Reports::class,
                Users::class,
                RolesAndPermissions::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                PanelAuthenticateRedirect::class,
                EnsureEmailIsVerified::class,
            ]), 'dso'));
    }
}
