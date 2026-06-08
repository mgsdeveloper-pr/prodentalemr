<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\RolesAndPermissions;
use App\Filament\Admin\Pages\VerificationQuestionArrangement;
use App\Filament\Admin\Pages\VerificationReports;
use App\Filament\Admin\Pages\VerificationNotificationControl;
use App\Filament\Admin\Pages\VerificationNotificationCentre;
use App\Filament\Admin\Pages\VerificationAssignmentManagement;
use App\Filament\Admin\Pages\VerificationSettings;
use App\Filament\Admin\Pages\VerificationReadiness;
use App\Filament\Admin\Pages\PortalCredentialSettings;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Widgets\ManagedServicesQuickLinks;
use App\Filament\Admin\Widgets\VerificationAttentionQueue;
use App\Filament\Admin\Widgets\VerificationSlaAnalytics;
use App\Http\Middleware\PanelAuthenticateRedirect;
use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\InsuranceCarrierNetworkProfileResource;
use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Filament\Saas\Resources\BillingWorkItems\BillingWorkItemResource;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('verification')
            ->login()
            ->userMenu()
            ->userMenuItems([
                'saas_console' => MenuItem::make()
                    ->label('Open SaaS Workspace')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (): string => url('/saas'))
                    ->visible(fn (): bool => auth()->check() && auth()->user()->canSwitchToSaasWorkspace())
                    ->sort(900),
                'logout' => MenuItem::make()->hidden(),
                'sign_out' => MenuItem::make()
                    ->label('Sign out')
                    ->icon(\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle)
                    ->url(fn (): string => route('admin.signout'))
                    ->sort(PHP_INT_MAX),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Verification Workspace'),
                NavigationGroup::make()->label('Alerts & Notifications'),
                NavigationGroup::make()->label('Team Access'),
            ])
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => view('filament.admin.partials.clinic-scope-switcher', [
                    'clinicOptions' => \App\Support\AdminClinicScope::clinicOptions(),
                ])->render(),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.admin.partials.sidebar-theme')->render(),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.shared.partials.verification-notification-bell', [
                    'panel' => 'verification',
                    'clinicId' => \App\Support\AdminClinicScope::selectedClinicId(),
                ])->render(),
            )
            ->resources([
                BillingWorkItemResource::class,
                VerificationWorkItemResource::class,
                VerificationFormQuestionResource::class,
                InsuranceCarrierResource::class,
                InsuranceCarrierNetworkProfileResource::class,
                PortalCredentialResource::class,
                UserResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                VerificationNotificationControl::class,
                VerificationNotificationCentre::class,
                VerificationReports::class,
                VerificationReadiness::class,
                VerificationAssignmentManagement::class,
                VerificationSettings::class,
                PortalCredentialSettings::class,
                VerificationQuestionArrangement::class,
                RolesAndPermissions::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ManagedServicesQuickLinks::class,
                VerificationSlaAnalytics::class,
                VerificationAttentionQueue::class,
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
            ]);
    }
}
