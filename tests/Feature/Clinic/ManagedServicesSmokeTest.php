<?php

use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::create([
        'name' => 'Clinic Managed Services Org',
        'owner_name' => 'Owner',
        'email' => 'owner@clinic-ops.test',
        'phone' => '5557771000',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Clinic Managed Services',
        'clinic_code' => 'CLN-CMS',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $this->location = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Main Location',
        'address' => '100 Main',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10001',
        'country' => 'USA',
        'phone' => '5558881000',
        'status' => true,
    ]);

    $this->clinicUser = User::factory()->create([
        'name' => 'Clinic Manager',
        'email' => 'clinic-manager@example.com',
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'status' => true,
    ]);
    $this->clinicUser->assignRole('clinic_manager');

    $providerUser = User::factory()->create([
        'name' => 'Dr. Ops',
        'email' => 'doctor@clinic-ops.test',
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'status' => true,
    ]);
    $providerUser->assignRole('doctor');

    $this->provider = Provider::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'user_id' => $providerUser->id,
        'specialization' => 'General Dentistry',
        'license_number' => 'LIC-900',
        'npi_number' => 'NPI-900',
        'tax_id' => 'TAX-900',
        'status' => true,
    ]);

    $this->patient = Patient::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'first_name' => 'Lena',
        'last_name' => 'Stone',
        'dob' => '1992-04-10',
        'status' => true,
    ]);

    $this->appointment = Appointment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => today(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'duration_minutes' => 30,
        'status' => 'confirmed',
        'appointment_type' => 'Hygiene',
    ]);

    $this->policy = PatientInsurancePolicy::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'coverage_priority' => 'primary',
        'insurance_company' => 'Delta Dental',
        'member_id' => 'MEM900',
        'subscriber_name' => 'Lena Stone',
        'subscriber_relationship' => 'self',
        'status' => true,
    ]);

    $this->verificationService = ManagedBillingService::create([
        'name' => 'Eligibility & Benefits Verification',
        'slug' => 'eligibility-benefits-verification',
        'category' => 'verification',
        'service_level_agreement_hours' => 24,
        'default_priority' => 'normal',
        'requires_appointment' => true,
        'requires_patient' => true,
        'requires_policy' => true,
        'requires_claim' => false,
        'status' => true,
    ]);
});

it('registers clinic managed service and verification request routes', function () {
    $router = app('router');

    expect($router->getRoutes()->match(Request::create('/clinic/service-requests', 'GET'))->uri())
        ->toBe('clinic/service-requests');
    expect($router->getRoutes()->match(Request::create('/clinic/verification-requests', 'GET'))->uri())
        ->toBe('clinic/verification-requests');
});

it('lets clinics create requested service enrollments', function () {
    $this->actingAs($this->clinicUser);

    $enrollment = ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->verificationService->id,
        'created_by' => $this->clinicUser->id,
        'status' => 'requested',
        'notes' => 'Please turn on verification handling for hygiene appointments.',
    ]);

    expect($enrollment->status)->toBe('requested');
    expect($enrollment->managedBillingService?->name)->toBe('Eligibility & Benefits Verification');
});

it('creates clinic verification work items from active enrollments', function () {
    $this->actingAs($this->clinicUser);

    $enrollment = ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->verificationService->id,
        'created_by' => $this->clinicUser->id,
        'status' => 'active',
        'start_date' => today(),
    ]);

    $workItem = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->verificationService->id,
        'client_service_enrollment_id' => $enrollment->id,
        'appointment_id' => $this->appointment->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'created_by' => $this->clinicUser->id,
        'title' => 'Verify hygiene benefits before visit',
        'source' => 'clinic_request',
        'status' => 'unassigned',
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'pms_sync_status' => 'pending',
        'writeback_status' => 'not_requested',
        'notes' => 'Please confirm preventive frequency and annual maximum remaining.',
    ]);

    expect($workItem->source)->toBe('clinic_request');
    expect($workItem->enrollment?->id)->toBe($enrollment->id);
    expect($workItem->patient?->full_name)->toBe('Lena Stone');
});
