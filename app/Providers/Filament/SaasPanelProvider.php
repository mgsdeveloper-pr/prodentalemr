<?php

namespace App\Providers\Filament;

use App\Filament\Saas\Pages\AdaProcedureCodeImport;
use App\Filament\Saas\Pages\BillingSettings;
use App\Filament\Saas\Pages\ClientManagement;
use App\Filament\Saas\Pages\DsoOnboarding;
use App\Filament\Saas\Pages\ModuleSettings;
use App\Filament\Saas\Pages\NotificationCentre;
use App\Filament\Saas\Pages\OrganizationWorkspace;
use App\Filament\Saas\Pages\PaymentCredentials;
use App\Filament\Saas\Pages\RolesAndPermissions;
use App\Filament\Saas\Pages\SaasSettings;
use App\Filament\Saas\Pages\SetupChecks;
use App\Filament\Saas\Pages\SystemUpdates;
use App\Filament\Saas\Pages\TenantOnboarding;
use App\Filament\Saas\Pages\UserManagement;
use App\Filament\Saas\Resources\ClientServiceEnrollments\ClientServiceEnrollmentResource;
use App\Filament\Saas\Resources\Clinics\ClinicResource;
use App\Filament\Saas\Resources\Dsos\DsoResource;
use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Filament\Saas\Resources\Invoices\InvoiceResource;
use App\Filament\Saas\Resources\Locations\LocationResource;
use App\Filament\Saas\Resources\ManagedBillingServices\ManagedBillingServiceResource;
use App\Filament\Saas\Resources\Organizations\OrganizationResource;
use App\Filament\Saas\Resources\Payments\PaymentResource;
use App\Filament\Saas\Resources\SaasEntitlementAuditLogs\SaasEntitlementAuditLogResource;
use App\Filament\Saas\Resources\SubscriptionPlans\SubscriptionPlanResource;
use App\Filament\Saas\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Saas\Resources\Users\UserResource;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Filament\Saas\Widgets\BillingHealthOverview;
use App\Filament\Saas\Widgets\SaasBusinessOverview;
use App\Http\Middleware\PanelAuthenticateRedirect;
use App\Http\Middleware\SaasAccessMiddleware;
use App\Models\SaasSetting;
use App\Support\AppShell\AppShell;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\PanelRegistry;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SaasPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $settings = SaasSetting::current();

        return AppShell::registerSharedSidebar(AppShell::register($panel
            ->id('saas')
            ->path('saas')
            ->login()
            ->userMenu()
            ->brandName($settings->brandName())
            ->brandLogo($settings->brandLogo())
            ->databaseNotifications()
            ->userMenuItems([
                'managed_services_console' => MenuItem::make()
                    ->label('Open Verification Workspace')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (): string => url('/verification'))
                    ->visible(fn (): bool => auth()->check() && auth()->user()->canAccessPanel(app(PanelRegistry::class)->get('admin')))
                    ->sort(900),
                'clinic_workspace' => MenuItem::make()
                    ->label('Open Clinic Workspace')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->url(fn (): string => url('/clinic'))
                    ->visible(fn (): bool => auth()->check() && auth()->user()->canAccessPanel(app(PanelRegistry::class)->get('clinic')))
                    ->sort(910),
                'logout' => MenuItem::make()->hidden(),
                'sign_out' => MenuItem::make()
                    ->label('Sign out')
                    ->icon(Heroicon::ArrowLeftEndOnRectangle)
                    ->url(fn (): string => route('saas.signout'))
                    ->sort(PHP_INT_MAX),
            ])
            ->colors([
                'primary' => Color::Teal,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Client Management'),
                NavigationGroup::make()->label('Billing'),
                NavigationGroup::make()->label('Master Data'),
                NavigationGroup::make()->label('Administration'),
                NavigationGroup::make()->label('Notifications'),
                NavigationGroup::make()->label('Settings'),
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_AFTER,
                fn (): string => auth()->check() ? view('filament.saas.partials.support-access-banner')->render() : '',
            )
            ->resources([
                DsoResource::class,
                OrganizationResource::class,
                ClinicResource::class,
                LocationResource::class,
                UserResource::class,
                ClientServiceEnrollmentResource::class,
                ManagedBillingServiceResource::class,
                InsuranceCarrierResource::class,
                VerificationFormQuestionResource::class,
                InvoiceResource::class,
                PaymentResource::class,
                SubscriptionPlanResource::class,
                SubscriptionResource::class,
                SaasEntitlementAuditLogResource::class,
            ])
            ->pages([
                Dashboard::class,
                ClientManagement::class,
                OrganizationWorkspace::class,
                TenantOnboarding::class,
                DsoOnboarding::class,
                NotificationCentre::class,
                PaymentCredentials::class,
                BillingSettings::class,
                AdaProcedureCodeImport::class,
                UserManagement::class,
                RolesAndPermissions::class,
                ModuleSettings::class,
                SetupChecks::class,
                SystemUpdates::class,
                SaasSettings::class,
            ])
            ->widgets([
                SaasBusinessOverview::class,
                BillingHealthOverview::class,
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
                // Custom SaaS Access Middleware
                SaasAccessMiddleware::class,
            ])
            ->authMiddleware([
                PanelAuthenticateRedirect::class,
                EnsureEmailIsVerified::class,
                SaasAccessMiddleware::class,
            ]), 'platform'));
    }
}
