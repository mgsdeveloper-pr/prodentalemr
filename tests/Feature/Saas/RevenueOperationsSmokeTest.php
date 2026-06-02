<?php

use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemAttachment;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\User;
use App\Models\VerificationProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    $this->organization = Organization::create([
        'name' => 'Revenue Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@revenue.test',
        'phone' => '5551000000',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Revenue Downtown',
        'clinic_code' => 'CLN-REV',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $this->location = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Revenue Main',
        'address' => '100 Main',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10001',
        'country' => 'USA',
        'phone' => '5552000000',
        'status' => true,
    ]);

    $this->saasUser = User::factory()->create([
        'name' => 'SaaS Manager',
        'email' => 'saas-manager@example.com',
        'status' => true,
    ]);
    $this->saasUser->assignRole('saas_manager');

    $providerUser = User::factory()->create([
        'name' => 'Dr. Revenue',
        'email' => 'doctor@revenue.test',
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
        'license_number' => 'LIC-500',
        'npi_number' => 'NPI-500',
        'tax_id' => 'TAX-500',
        'status' => true,
    ]);

    $this->patient = Patient::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'first_name' => 'Mary',
        'last_name' => 'Jones',
        'dob' => '1991-05-10',
        'status' => true,
    ]);

    $this->appointment = Appointment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => today(),
        'start_time' => '09:00:00',
        'end_time' => '09:30:00',
        'duration_minutes' => 30,
        'status' => 'confirmed',
        'appointment_type' => 'Consultation',
    ]);

    $this->policy = PatientInsurancePolicy::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'coverage_priority' => 'primary',
        'insurance_company' => 'Delta Dental',
        'member_id' => 'MEM500',
        'subscriber_name' => 'Mary Jones',
        'subscriber_relationship' => 'self',
        'status' => true,
    ]);

    $this->service = ManagedBillingService::create([
        'name' => 'Eligibility & Benefits Verification',
        'slug' => 'eligibility-benefits-verification',
        'category' => 'verification',
        'service_level_agreement_hours' => 24,
        'default_priority' => 'high',
        'requires_appointment' => true,
        'requires_patient' => true,
        'requires_policy' => true,
        'requires_claim' => false,
        'status' => true,
    ]);

    $this->enrollment = ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'created_by' => $this->saasUser->id,
        'status' => 'active',
        'start_date' => today(),
    ]);
});

it('registers the new saas revenue operations routes cleanly', function () {
    $router = app('router');

    expect($router->getRoutes()->match(Request::create('/saas/managed-billing-services', 'GET'))->uri())
        ->toBe('saas/managed-billing-services');
    expect($router->getRoutes()->match(Request::create('/saas/client-service-enrollments', 'GET'))->uri())
        ->toBe('saas/client-service-enrollments');
    expect($router->getRoutes()->match(Request::create('/saas/billing-work-items', 'GET'))->uri())
        ->toBe('saas/billing-work-items');
});

it('logs creation and status changes for billing work items', function () {
    $this->actingAs($this->saasUser);

    $workItem = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'appointment_id' => $this->appointment->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'assigned_to' => $this->saasUser->id,
        'title' => 'Verify benefits before visit',
        'status' => 'assigned',
        'priority' => 'high',
        'source' => 'appointment_sync',
    ]);

    expect($workItem->activities()->count())->toBeGreaterThanOrEqual(1);

    $workItem->update([
        'status' => 'completed',
        'outcome_status' => 'verified',
    ]);

    expect($workItem->activities()->where('activity_type', 'status_changed')->exists())->toBeTrue();
    expect($workItem->activities()->where('activity_type', 'outcome_changed')->exists())->toBeTrue();
});

it('allows authorized saas users to download a billing work attachment', function () {
    $this->actingAs($this->saasUser);

    $workItem = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'title' => 'Eligibility verification packet',
        'status' => 'assigned',
        'priority' => 'high',
        'source' => 'manual',
    ]);

    Storage::disk('local')->put('billing-work-items/verification-proof.pdf', 'verification-proof');

    $attachment = BillingWorkItemAttachment::create([
        'billing_work_item_id' => $workItem->id,
        'user_id' => $this->saasUser->id,
        'title' => 'Verification PDF',
        'file_path' => 'billing-work-items/verification-proof.pdf',
        'original_file_name' => 'verification-proof.pdf',
    ]);

    $response = $this->get(route('saas.billing-work-item-attachments.download', $attachment));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('verification-proof.pdf');
});

it('persists structured verification profile details on a work item', function () {
    $this->actingAs($this->saasUser);

    $workItem = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'appointment_id' => $this->appointment->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'assigned_to' => $this->saasUser->id,
        'title' => 'Full verification review',
        'status' => 'in_progress',
        'outcome_status' => 'pending',
        'priority' => 'urgent',
        'source' => 'appointment_sync',
    ]);

    $profile = $workItem->verificationProfile()->create([
        'form_type' => 'full_form',
        'subscriber_name' => 'Mary Jones',
        'subscriber_id' => 'SUB500',
        'insurance_provider_name' => 'Delta Dental',
        'group_number' => 'GRP500',
        'coverage_preventive' => 100,
        'coverage_basic_restorative' => 80,
        'verification_notes' => 'Preventive covered at 100%, basic restorative at 80%.',
    ]);

    expect($profile)->toBeInstanceOf(VerificationProfile::class);
    expect($workItem->fresh()->verificationProfile?->subscriber_name)->toBe('Mary Jones');
    expect($workItem->fresh()->verificationProfile?->coverage_preventive)->toBe(100);
});
