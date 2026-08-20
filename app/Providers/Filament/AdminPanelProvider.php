<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\DocumentCenter;
use App\Filament\Admin\Pages\VerificationClinicAssignments;
use App\Filament\Admin\Pages\VerificationFormLab;
use App\Filament\Admin\Pages\VerificationGeneralSettings;
use App\Filament\Admin\Pages\RolesAndPermissions;
use App\Filament\Admin\Pages\UserMailboxPage;
use App\Filament\Admin\Pages\UserMailboxSettingsPage;
use App\Filament\Admin\Pages\VerificationQuestionArrangement;
use App\Filament\Admin\Pages\VerificationReports;
use App\Filament\Admin\Pages\VerificationRequestResponse;
use App\Filament\Admin\Pages\VerificationInbox;
use App\Filament\Admin\Pages\VerificationUnassignedRequests;
use App\Filament\Admin\Pages\VerificationInboxSettings;
use App\Filament\Admin\Pages\VerificationNotificationControl;
use App\Filament\Admin\Pages\VerificationNotificationCentre;
use App\Filament\Admin\Pages\VerificationAssignmentManagement;
use App\Filament\Admin\Pages\VerificationSettings;
use App\Filament\Admin\Pages\VerificationReadiness;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Widgets\ManagedServicesQuickLinks;
use App\Filament\Admin\Widgets\VerificationAttentionQueue;
use App\Http\Middleware\PanelAuthenticateRedirect;
use App\Support\AppShell\AppShell;
use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\InsuranceCarrierNetworkProfileResource;
use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return AppShell::registerSharedSidebar(AppShell::register($panel
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
                'clinic_workspace' => MenuItem::make()
                    ->label('Open Clinic Workspace')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedBuildingOffice2)
                    ->url(fn (): string => url('/clinic'))
                    ->visible(fn (): bool => auth()->check() && auth()->user()->canAccessPanel(app(\Filament\PanelRegistry::class)->get('clinic')))
                    ->sort(910),
                'logout' => MenuItem::make()->hidden(),
                'sign_out' => MenuItem::make()
                    ->label('Sign out')
                    ->icon(\Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle)
                    ->url(fn (): string => route('admin.signout'))
                    ->sort(PHP_INT_MAX),
            ])
            ->colors([
                'primary' => Color::Teal,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Dashboard'),
                NavigationGroup::make()->label('Verifications'),
                NavigationGroup::make()->label('Access Management'),
                NavigationGroup::make()->label('Reports'),
                NavigationGroup::make()->label('Settings'),
            ])
            ->resources([
                VerificationRequestResource::class,
                VerificationFormQuestionResource::class,
                InsuranceCarrierResource::class,
                InsuranceCarrierNetworkProfileResource::class,
                PortalCredentialResource::class,
                UserResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                DocumentCenter::class,
                UserMailboxPage::class,
                UserMailboxSettingsPage::class,
                VerificationUnassignedRequests::class,
                VerificationFormLab::class,
                VerificationRequestResponse::class,
                VerificationNotificationControl::class,
                VerificationInbox::class,
                VerificationInboxSettings::class,
                VerificationNotificationCentre::class,
                VerificationReports::class,
                VerificationClinicAssignments::class,
                VerificationReadiness::class,
                VerificationAssignmentManagement::class,
                VerificationGeneralSettings::class,
                VerificationSettings::class,
                VerificationQuestionArrangement::class,
                RolesAndPermissions::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ManagedServicesQuickLinks::class,
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
                EnsureEmailIsVerified::class,
            ]), 'verification'), fn (): string => view('filament.admin.partials.clinic-scope-switcher', [
                'clinicOptions' => \App\Support\AdminClinicScope::clinicOptions(),
            ])->render(), fn (): array => [
                'panel' => 'verification',
                'clinicId' => \App\Support\AdminClinicScope::selectedClinicId(),
            ]);
    }
}
