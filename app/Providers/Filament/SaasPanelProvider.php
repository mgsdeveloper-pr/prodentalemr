<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SaasAccessMiddleware;
use App\Http\Middleware\PanelAuthenticateRedirect;
use App\Filament\Saas\Pages\BillingSettings;
use App\Filament\Saas\Pages\DsoOnboarding;
use App\Filament\Saas\Pages\ModuleSettings;
use App\Filament\Saas\Pages\NotificationCentre;
use App\Filament\Saas\Pages\PaymentCredentials;
use App\Filament\Saas\Pages\RolesAndPermissions;
use App\Filament\Saas\Pages\SaasSettings;
use App\Filament\Saas\Pages\SetupChecks;
use App\Filament\Saas\Pages\TenantOnboarding;
use App\Filament\Saas\Resources\Clinics\ClinicResource;
use App\Filament\Saas\Resources\ClientServiceEnrollments\ClientServiceEnrollmentResource;
use App\Filament\Saas\Resources\Dsos\DsoResource;
use App\Filament\Saas\Resources\Invoices\InvoiceResource;
use App\Filament\Saas\Resources\Locations\LocationResource;
use App\Filament\Saas\Resources\Organizations\OrganizationResource;
use App\Filament\Saas\Resources\Payments\PaymentResource;
use App\Filament\Saas\Resources\SaasEntitlementAuditLogs\SaasEntitlementAuditLogResource;
use App\Filament\Saas\Resources\SubscriptionPlans\SubscriptionPlanResource;
use App\Filament\Saas\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Saas\Resources\Users\UserResource;
use App\Filament\Saas\Widgets\BillingHealthOverview;
use App\Filament\Saas\Widgets\IncompleteOnboarding;
use App\Filament\Saas\Widgets\InvoiceStatusOverview;
use App\Filament\Saas\Widgets\PaymentMethodsOverview;
use App\Filament\Saas\Widgets\RecentBillingActivity;
use App\Filament\Saas\Widgets\RecentInvoices;
use App\Filament\Saas\Widgets\RecentPayments;
use App\Filament\Saas\Widgets\SaasNotificationsOverview;
use App\Filament\Saas\Widgets\SaasBusinessOverview;
use App\Models\SaasSetting;
use Filament\Navigation\MenuItem;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
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

class SaasPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $settings = SaasSetting::current();

        return $panel
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
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (): string => url('/verification'))
                    ->visible(fn (): bool => auth()->check() && auth()->user()->canAccessPanel(app(\Filament\PanelRegistry::class)->get('admin')))
                    ->sort(900),
                'logout' => MenuItem::make()->hidden(),
                'sign_out' => MenuItem::make()
                    ->label('Sign out')
                    ->icon(\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle)
                    ->url(fn (): string => route('saas.signout'))
                    ->sort(PHP_INT_MAX),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Organizations'),
                NavigationGroup::make()->label('Plans'),
                NavigationGroup::make()->label('Payment Systems'),
                NavigationGroup::make()->label('Notification'),
                NavigationGroup::make()->label('User Management'),
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
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.shared.partials.sidebar-theme')->render()
                    . view('filament.shared.partials.page-header-theme')->render(),
            )
            ->resources([
                DsoResource::class,
                OrganizationResource::class,
                ClinicResource::class,
                LocationResource::class,
                UserResource::class,
                ClientServiceEnrollmentResource::class,
                InvoiceResource::class,
                PaymentResource::class,
                SubscriptionPlanResource::class,
                SubscriptionResource::class,
                SaasEntitlementAuditLogResource::class,
            ])
            ->pages([
                Dashboard::class,
                TenantOnboarding::class,
                DsoOnboarding::class,
                NotificationCentre::class,
                PaymentCredentials::class,
                BillingSettings::class,
                RolesAndPermissions::class,
                ModuleSettings::class,
                SetupChecks::class,
                SaasSettings::class,
            ])
            ->widgets([
                SaasBusinessOverview::class,
                BillingHealthOverview::class,
                InvoiceStatusOverview::class,
                PaymentMethodsOverview::class,
                RecentBillingActivity::class,
                RecentInvoices::class,
                RecentPayments::class,
                IncompleteOnboarding::class,
                SaasNotificationsOverview::class,
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
                SaasAccessMiddleware::class,
            ]);
    }
}
