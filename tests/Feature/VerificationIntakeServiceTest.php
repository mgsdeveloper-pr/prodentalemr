<?php

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Services\Verification\VerificationIntakeService;
use Database\Seeders\RoleSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::create([
        'name' => 'Intake Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@intake.test',
        'phone' => '5551003000',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Intake Clinic',
        'clinic_code' => 'CLN-INTAKE',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $this->location = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Main Office',
        'status' => true,
    ]);

    $this->service = ManagedBillingService::create([
        'name' => 'Verification Intake Service',
        'slug' => 'verification-intake-service',
        'category' => 'verification',
        'service_level_agreement_hours' => 24,
        'default_priority' => 'normal',
        'status' => true,
    ]);

    $this->enrollment = ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'status' => 'active',
        'start_date' => today(),
    ]);

    $providerUser = User::factory()->create(['status' => true]);
    $this->provider = Provider::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'user_id' => $providerUser->id,
        'status' => true,
    ]);

    $this->patient = Patient::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'first_name' => 'Intake',
        'last_name' => 'Patient',
        'dob' => '1990-01-15',
        'status' => true,
    ]);

    $this->appointment = Appointment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => today()->addDay(),
        'start_time' => '09:00:00',
        'status' => 'scheduled',
    ]);
});

it('uses the selected appointment as the authoritative clinic intake context', function () {
    $data = app(VerificationIntakeService::class)->normalizeAndValidate([
        'appointment_id' => $this->appointment->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
    ], [
        'form_type' => 'full_form',
        'patient_full_name' => 'Intake Patient',
        'patient_dob' => '1990-01-15',
        'appointment_date' => today()->addDay()->format('Y-m-d'),
    ], [[
        'payer_name' => 'Aetna Dental',
    ]]);

    expect($data['organization_id'])->toBe($this->organization->id)
        ->and($data['clinic_id'])->toBe($this->clinic->id)
        ->and($data['location_id'])->toBe($this->location->id)
        ->and($data['provider_id'])->toBe($this->provider->id)
        ->and($data['patient_id'])->toBe($this->patient->id);
});

it('blocks a provider from another clinic at intake', function () {
    $otherClinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Other Clinic',
        'clinic_code' => 'CLN-OTHER',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $otherLocation = Location::create([
        'clinic_id' => $otherClinic->id,
        'location_name' => 'Other Office',
        'status' => true,
    ]);

    $otherProvider = Provider::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $otherClinic->id,
        'location_id' => $otherLocation->id,
        'user_id' => User::factory()->create(['status' => true])->id,
        'status' => true,
    ]);

    expect(fn () => app(VerificationIntakeService::class)->normalizeAndValidate([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'provider_id' => $otherProvider->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
    ]))->toThrow(ValidationException::class, 'The selected provider does not belong to this clinic/location.');
});

it('requires complete patient appointment form and insurance details', function () {
    expect(fn () => app(VerificationIntakeService::class)->normalizeAndValidate([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
    ], [
        'form_type' => 'full_form',
    ], []))->toThrow(ValidationException::class);
});
