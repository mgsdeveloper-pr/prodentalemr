<?php

namespace App\Providers\Filament;

use App\Filament\Clinic\Widgets\ClinicAccountWidget;
use App\Filament\Clinic\Pages\Dashboard;
use App\Filament\Clinic\Pages\DocumentCenter;
use App\Filament\Clinic\Pages\VerificationRequestResponse;
use App\Filament\Clinic\Pages\VerificationNotificationCentre;
use App\Http\Middleware\EnsureClinicWorkspaceSelected;
use App\Http\Middleware\PanelAuthenticateRedirect;
use App\Support\ClinicWorkspace;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ClinicPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('clinic')
            ->path('clinic')
            ->homeUrl(fn (): string => ClinicWorkspace::homeUrl(ClinicWorkspace::selectedOrDefault(ClinicWorkspace::clinicForUser()) ?: ClinicWorkspace::CLINIC_PMS))
            ->userMenu()
            ->userMenuItems([
                'choose_workspace' => MenuItem::make()
                    ->label('Choose Workspace')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedSquares2x2)
                    ->url(fn (): string => route('clinic.choose-workspace'))
                    ->visible(fn (): bool => ClinicWorkspace::needsChoice(ClinicWorkspace::clinicForUser()))
                    ->sort(850),
                'switch_to_verification' => MenuItem::make()
                    ->label('Go to Verification Zone')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedClipboardDocumentCheck)
                    ->url(fn (): string => route('clinic.switch-workspace', ['workspace' => ClinicWorkspace::VERIFICATION]))
                    ->visible(fn (): bool => ClinicWorkspace::needsChoice(ClinicWorkspace::clinicForUser()) && ClinicWorkspace::selected() !== ClinicWorkspace::VERIFICATION)
                    ->sort(860),
                'switch_to_clinic_pms' => MenuItem::make()
                    ->label('Go to Clinic PMS')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedBuildingOffice2)
                    ->url(fn (): string => route('clinic.switch-workspace', ['workspace' => ClinicWorkspace::CLINIC_PMS]))
                    ->visible(fn (): bool => ClinicWorkspace::needsChoice(ClinicWorkspace::clinicForUser()) && ClinicWorkspace::selected() !== ClinicWorkspace::CLINIC_PMS)
                    ->sort(870),
                'logout' => MenuItem::make()->hidden(),
                'sign_out' => MenuItem::make()
                    ->label('Sign out')
                    ->icon(\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle)
                    ->url(fn (): string => route('clinic.signout'))
                    ->sort(PHP_INT_MAX),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Dashboard'),
                NavigationGroup::make()->label('Verifications'),
                NavigationGroup::make()->label('Scheduling'),
                NavigationGroup::make()->label('Clinical Records'),
                NavigationGroup::make()->label('Dental Charting'),
                NavigationGroup::make()->label('Treatment Planning'),
                NavigationGroup::make()->label('Patient Care'),
                NavigationGroup::make()->label('Insurance Verification'),
                NavigationGroup::make()->label('Notifications'),
                NavigationGroup::make()->label('Managed Services'),
                NavigationGroup::make()->label('Financial Records'),
                NavigationGroup::make()->label('Access Management'),
                NavigationGroup::make()->label('Settings'),
            ])
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_BEFORE,
                fn (): string => view('filament.shared.partials.sidebar-greeting')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                fn (): string => view('filament.shared.partials.sidebar-toggle')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => view('filament.clinic.partials.clinic-scope-switcher', [
                    'clinicOptions' => \App\Support\ClinicPanelScope::clinicOptions(),
                ])->render(),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.shared.partials.sidebar-theme')->render()
                    . view('filament.shared.partials.page-header-theme')->render(),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.shared.partials.verification-notification-bell', [
                    'panel' => 'clinic',
                    'clinicId' => \App\Support\ClinicPanelScope::selectedClinicId(),
                ])->render(),
            )
            ->discoverResources(in: app_path('Filament/Clinic/Resources'), for: 'App\Filament\Clinic\Resources')
            ->discoverPages(in: app_path('Filament/Clinic/Pages'), for: 'App\Filament\Clinic\Pages')
            ->pages([
                Dashboard::class,
                DocumentCenter::class,
                VerificationRequestResponse::class,
                VerificationNotificationCentre::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Clinic/Widgets'), for: 'App\Filament\Clinic\Widgets')
            ->widgets([
                ClinicAccountWidget::class,
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
                EnsureClinicWorkspaceSelected::class,
            ]);
    }
}
