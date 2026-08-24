<?php

namespace App\Providers\Filament;

use App\Filament\Clinic\Pages\Dashboard;
use App\Filament\Clinic\Pages\DocumentCenter;
use App\Filament\Clinic\Pages\VerificationNotificationCentre;
use App\Filament\Clinic\Pages\VerificationRequestResponse;
use App\Filament\Clinic\Widgets\ClinicAccountWidget;
use App\Http\Middleware\EnsureClinicWorkspaceSelected;
use App\Http\Middleware\PanelAuthenticateRedirect;
use App\Support\AppShell\AppShell;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\PanelRegistry;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
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
        return AppShell::registerSharedSidebar(AppShell::register($panel
            ->id('clinic')
            ->path('clinic')
            ->homeUrl(fn (): string => ClinicWorkspace::homeUrl(ClinicWorkspace::selectedOrDefault(ClinicWorkspace::clinicForUser()) ?: ClinicWorkspace::CLINIC_PMS))
            ->userMenu()
            ->userMenuItems([
                'saas_workspace' => MenuItem::make()
                    ->label('Open SaaS Workspace')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (): string => url('/saas'))
                    ->visible(fn (): bool => auth()->check() && auth()->user()->canAccessPanel(app(PanelRegistry::class)->get('saas')))
                    ->sort(830),
                'managed_verification_workspace' => MenuItem::make()
                    ->label('Open Managed Verification')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->url(fn (): string => url('/verification'))
                    ->visible(fn (): bool => auth()->check() && auth()->user()->canAccessPanel(app(PanelRegistry::class)->get('admin')))
                    ->sort(840),
                'choose_workspace' => MenuItem::make()
                    ->label('Choose Workspace')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->url(fn (): string => route('clinic.choose-workspace'))
                    ->visible(fn (): bool => ClinicWorkspace::needsChoice(ClinicWorkspace::clinicForUser()))
                    ->sort(850),
                'switch_to_verification' => MenuItem::make()
                    ->label('Go to Verification Zone')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->url(fn (): string => route('clinic.choose-workspace'))
                    ->visible(fn (): bool => ClinicWorkspace::needsChoice(ClinicWorkspace::clinicForUser()) && ClinicWorkspace::selected() !== ClinicWorkspace::VERIFICATION)
                    ->sort(860),
                'switch_to_clinic_pms' => MenuItem::make()
                    ->label('Go to Clinic PMS')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->url(fn (): string => route('clinic.choose-workspace'))
                    ->visible(fn (): bool => ClinicWorkspace::needsChoice(ClinicWorkspace::clinicForUser()) && ClinicWorkspace::selected() !== ClinicWorkspace::CLINIC_PMS)
                    ->sort(870),
                'logout' => MenuItem::make()->hidden(),
                'sign_out' => MenuItem::make()
                    ->label('Sign out')
                    ->icon(Heroicon::ArrowLeftEndOnRectangle)
                    ->url(fn (): string => route('clinic.signout'))
                    ->sort(PHP_INT_MAX),
            ])
            ->colors([
                'primary' => Color::Teal,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Overview'),
                NavigationGroup::make()->label('Verification'),
                NavigationGroup::make()->label('Scheduling'),
                NavigationGroup::make()->label('Clinic Management'),
                NavigationGroup::make()->label('Clinic Directory'),
                NavigationGroup::make()->label('Managed Services'),
                NavigationGroup::make()->label('Clinical Records'),
                NavigationGroup::make()->label('Dental Charting'),
                NavigationGroup::make()->label('Treatment Planning'),
                NavigationGroup::make()->label('Financial Records'),
                NavigationGroup::make()->label('Administration'),
                NavigationGroup::make()->label('Settings'),
            ])
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
                EnsureEmailIsVerified::class,
                EnsureClinicWorkspaceSelected::class,
            ]), 'clinic'), fn (): string => view('filament.clinic.partials.clinic-scope-switcher', [
                'clinicOptions' => ClinicPanelScope::clinicOptions(),
            ])->render(), fn (): array => [
                'panel' => 'clinic',
                'clinicId' => ClinicPanelScope::selectedClinicId(),
            ]);
    }
}
