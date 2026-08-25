<?php

use App\Filament\Admin\Pages\Dashboard as VerificationDashboard;
use App\Filament\Admin\Pages\DocumentCenter as VerificationDocumentCenter;
use App\Filament\Admin\Pages\VerificationClinicAssignments;
use App\Filament\Admin\Pages\VerificationInbox;
use App\Filament\Admin\Pages\VerificationRequestResponse;
use App\Filament\Admin\Pages\VerificationUnassignedRequests;
use App\Filament\Admin\Resources\Appointments\AppointmentResource as VerificationAppointmentResource;
use App\Filament\Admin\Resources\Patients\PatientResource as VerificationPatientResource;
use App\Filament\Clinic\Pages\AppointmentCalendar;
use App\Filament\Clinic\Pages\Dashboard as ClinicDashboard;
use App\Filament\Clinic\Pages\DocumentCenter as ClinicDocumentCenter;
use App\Filament\Clinic\Pages\OrganizationOperations as ClinicProfile;
use App\Filament\Clinic\Pages\RolesAndPermissions as ClinicRolesAndPermissions;
use App\Filament\Clinic\Pages\VerificationClinicAssignments as ClinicAccess;
use App\Filament\Clinic\Pages\VerificationSettings as ClinicVerificationSettings;
use App\Filament\Clinic\Resources\Appointments\AppointmentResource as ClinicAppointmentResource;
use App\Filament\Clinic\Resources\Locations\LocationResource as ClinicLocationResource;
use App\Filament\Clinic\Resources\Patients\PatientResource as ClinicPatientResource;
use App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource as ClinicPortalCredentialResource;
use App\Filament\Clinic\Resources\Providers\ProviderResource as ClinicProviderResource;
use App\Filament\Clinic\Resources\Users\UserResource as ClinicUserResource;
use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use Filament\Facades\Filament;

it('keeps clinic navigation organized around daily clinic work', function (): void {
    expect(ClinicDashboard::getNavigationGroup())->toBe('Overview')
        ->and(ClinicAppointmentResource::getNavigationGroup())->toBe('Scheduling')
        ->and(AppointmentCalendar::getNavigationGroup())->toBe('Scheduling')
        ->and(ClinicPatientResource::getNavigationGroup())->toBe('Clinic Directory')
        ->and(ClinicPatientResource::getNavigationLabel())->toBe('Patients')
        ->and(ClinicDocumentCenter::getNavigationGroup())->toBe('Clinic Directory')
        ->and(ClinicProfile::getNavigationGroup())->toBe('Clinic Management')
        ->and(ClinicLocationResource::getNavigationGroup())->toBe('Clinic Management')
        ->and(ClinicProviderResource::getNavigationGroup())->toBe('Clinic Management')
        ->and(ClinicAccess::shouldRegisterNavigation())->toBeFalse()
        ->and(ClinicVerificationSettings::getNavigationGroup())->toBe('Settings')
        ->and(ClinicPortalCredentialResource::getNavigationGroup())->toBe('Settings')
        ->and(ClinicUserResource::getNavigationGroup())->toBe('Clinic Management')
        ->and(ClinicUserResource::getNavigationLabel())->toBe('Users & Access')
        ->and(ClinicRolesAndPermissions::getNavigationGroup())->toBe('Clinic Management');
});

it('keeps portal credentials separate from embedded verification settings menus', function (): void {
    $settingsView = file_get_contents(resource_path('views/filament/clinic/pages/verification-settings.blade.php'));
    $questionView = file_get_contents(resource_path('views/filament/clinic/resources/verification-questions/pages/list-verification-questions.blade.php'));
    $reorderView = file_get_contents(resource_path('views/filament/clinic/resources/verification-questions/pages/reorder-verification-questions.blade.php'));
    $carrierPage = file_get_contents(app_path('Filament/Clinic/Resources/InsuranceCarriers/Pages/ListInsuranceCarriers.php'));

    expect($settingsView)
        ->not->toContain("'label' => 'Portal Credentials'")
        ->and($questionView)
        ->toContain('Back to Templates')
        ->toContain('Clinic Template Builder')
        ->not->toContain("'label' => 'Portal Credentials'")
        ->and($reorderView)
        ->toContain("'label' => 'Verification Settings'")
        ->not->toContain("'label' => 'Portal Credentials'")
        ->and($carrierPage)
        ->toContain("'label' => 'Verification Settings'")
        ->not->toContain("'label' => 'Portal Credentials'");
});

it('keeps clinic template building in one focused workspace', function (): void {
    $settingsView = file_get_contents(resource_path('views/filament/clinic/pages/verification-settings.blade.php'));
    $builderView = file_get_contents(resource_path('views/filament/clinic/resources/verification-questions/pages/list-verification-questions.blade.php'));

    expect($settingsView)
        ->toContain('Open Builder')
        ->toContain('View Structure')
        ->not->toContain('>Add Question</a>')
        ->not->toContain('>Re-order</a>')
        ->and($builderView)
        ->toContain('Template Structure')
        ->toContain('Form Preview')
        ->toContain('Add Question')
        ->toContain('Previous Template Versions');
});

it('keeps the saas master template workspace focused on versions and draft actions', function (): void {
    $masterTemplateView = file_get_contents(resource_path(
        'views/filament/saas/resources/verification-form-questions/pages/list-verification-form-questions.blade.php'
    ));
    $masterTemplatePage = file_get_contents(app_path(
        'Filament/Saas/Resources/VerificationFormQuestions/Pages/ListVerificationFormQuestions.php'
    ));

    expect($masterTemplateView)
        ->toContain('Current Master Template')
        ->toContain('Draft Templates')
        ->toContain('Previous Published')
        ->toContain('Template Versions')
        ->toContain('Open Builder')
        ->toContain('Set as Working')
        ->toContain('Preview')
        ->not->toContain('Search sections or questions from the table below')
        ->not->toContain('Master Scope')
        ->not->toContain('>Export</button>')
        ->not->toContain('>Columns</button>')
        ->and($masterTemplatePage)
        ->toContain("return 'Master Template';")
        ->toContain("->label('Create Draft Template')")
        ->toContain('return $this->getTemplateHeaderActions();');
});

it('keeps verification navigation focused on requests and resources', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(VerificationDashboard::getNavigationGroup())->toBe('Overview')
        ->and(VerificationRequestResource::getNavigationGroup())->toBe('Verification Work')
        ->and(VerificationRequestResource::getNavigationLabel())->toBe('Verification Requests')
        ->and(VerificationUnassignedRequests::getNavigationGroup())->toBe('Verification Work')
        ->and(VerificationUnassignedRequests::getNavigationLabel())->toBe('Unassigned Requests')
        ->and(VerificationRequestResponse::getNavigationLabel())->toBe('Clinic Requests')
        ->and(VerificationInbox::getNavigationLabel())->toBe('Shared Inbox')
        ->and(VerificationClinicAssignments::getNavigationGroup())->toBe('Administration')
        ->and(VerificationClinicAssignments::getNavigationLabel())->toBe('Clinic Assignments')
        ->and(PortalCredentialResource::getNavigationGroup())->toBe('Resources')
        ->and(VerificationDocumentCenter::getNavigationGroup())->toBe('Resources')
        ->and(VerificationAppointmentResource::shouldRegisterNavigation())->toBeFalse()
        ->and(VerificationPatientResource::shouldRegisterNavigation())->toBeFalse();
});
