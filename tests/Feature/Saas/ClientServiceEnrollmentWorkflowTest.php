<?php

use App\Filament\Saas\Resources\ClientServiceEnrollments\ClientServiceEnrollmentResource;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\User;
use App\Services\ClientServiceEnrollmentWorkflow;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::create([
        'name' => 'Enrollment Dental Group',
        'owner_name' => 'Enrollment Owner',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Enrollment Dental Center',
        'clinic_code' => 'ENROLL-01',
        'timezone' => 'America/New_York',
        'status' => true,
        'service_status' => 'active',
        'verification_service_status' => 'not_enabled',
        'verification_services_enabled' => false,
        'managed_services_status' => 'not_enabled',
    ]);

    $this->location = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Main Location',
        'status' => true,
    ]);

    $this->service = ManagedBillingService::create([
        'name' => 'Insurance Verification',
        'slug' => 'insurance-verification-workflow-test',
        'category' => 'verification',
        'service_level_agreement_hours' => 48,
        'default_priority' => 'normal',
        'status' => true,
    ]);
});

it('exposes the client enrollment workflow to a SaaS administrator', function (): void {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $this->actingAs($admin)
        ->get(ClientServiceEnrollmentResource::getUrl('create', [
            'organization' => $this->organization->id,
            'clinic' => $this->clinic->id,
            'service' => $this->service->id,
        ]))
        ->assertOk()
        ->assertSee('Create enrollment')
        ->assertSee('Enrollment Dental Group')
        ->assertSee('Enrollment Dental Center')
        ->assertSee('Insurance Verification');
});

it('activates verification eligibility when an enrollment becomes active', function (): void {
    $enrollment = ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'status' => 'active',
        'clinic_workspace_enabled' => true,
        'normal_sla_days' => 3,
        'urgent_sla_hours' => 24,
    ]);

    app(ClientServiceEnrollmentWorkflow::class)->synchronizeClinic($enrollment);

    expect($this->clinic->fresh())
        ->verification_services_enabled->toBeTrue()
        ->verification_service_status->toBe('active')
        ->managed_services_status->toBe('active');

    $verificationAdmin = User::factory()->create(['status' => true]);
    $verificationAdmin->assignRole('verification_admin');

    expect($verificationAdmin->assignableVerificationClinicOptions())
        ->toHaveKey($this->clinic->id);
});

it('rejects duplicate and cross-client enrollment scopes', function (): void {
    $workflow = app(ClientServiceEnrollmentWorkflow::class);

    ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'status' => 'active',
    ]);

    expect(fn () => $workflow->prepare([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'status' => 'active',
    ]))->toThrow(ValidationException::class);

    $otherOrganization = Organization::create([
        'name' => 'Other Dental Group',
        'owner_name' => 'Other Owner',
        'status' => true,
    ]);

    expect(fn () => $workflow->prepare([
        'organization_id' => $otherOrganization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'status' => 'active',
    ]))->toThrow(ValidationException::class);
});

it('keeps requested enrollment state distinct from active verification access', function (): void {
    $enrollment = ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'status' => 'requested',
    ]);

    app(ClientServiceEnrollmentWorkflow::class)->synchronizeClinic($enrollment);

    expect($this->clinic->fresh())
        ->managed_services_status->toBe('requested')
        ->verification_services_enabled->toBeFalse()
        ->verification_service_status->toBe('not_enabled');

    $verificationAdmin = User::factory()->create(['status' => true]);
    $verificationAdmin->assignRole('verification_admin');

    expect($verificationAdmin->assignableVerificationClinicOptions())
        ->not->toHaveKey($this->clinic->id);
});
