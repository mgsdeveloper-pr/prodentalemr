<?php

use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm;
use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\ClinicOperatory;
use App\Models\ClinicService;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Appointments\AppointmentSchedulingService;
use App\Support\AppointmentVerificationSender;
use App\Support\AppointmentWorkspaceScope;
use App\Support\ClinicWorkspace;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::create([
        'name' => 'Schedule Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@schedule.test',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Schedule Clinic',
        'clinic_code' => 'CLN-SCHEDULE',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $this->location = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Main Office',
        'status' => true,
    ]);

    $this->clinicUser = User::factory()->create([
        'name' => 'Clinic Scheduler',
        'email' => 'scheduler@appointments.test',
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'status' => true,
    ]);
    $this->clinicUser->assignRole('clinic_admin');

    $this->service = ManagedBillingService::create([
        'name' => 'Schedule Verification',
        'slug' => 'schedule-verification',
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
        'clinic_workspace_enabled' => true,
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
        'first_name' => 'Calendar',
        'last_name' => 'Patient',
        'dob' => '1990-01-15',
        'insurance_provider' => 'Aetna Dental',
        'insurance_number' => 'MEM-100',
        'status' => true,
    ]);

    $this->policy = PatientInsurancePolicy::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'insurance_company' => 'Aetna Dental',
        'member_id' => 'MEM-100',
        'subscriber_name' => $this->patient->full_name,
        'subscriber_dob' => $this->patient->dob,
        'coverage_priority' => 'primary',
        'subscriber_relationship' => 'self',
        'status' => true,
    ]);

    $this->appointment = Appointment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => today()->addDay(),
        'start_time' => '09:00:00',
        'end_time' => '09:30:00',
        'status' => 'scheduled',
        'verification_status' => Appointment::VERIFICATION_STATUS_NOT_SENT,
    ]);

    $this->clinicService = ClinicService::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'name' => 'Comprehensive Exam',
        'service_code' => 'EXAM',
        'default_fee' => 125,
        'default_duration_minutes' => 45,
        'status' => true,
    ]);

    $this->operatory = ClinicOperatory::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'name' => 'Chair 1',
        'display_order' => 1,
        'status' => true,
    ]);
});

it('keeps the appointment and marks verification as needing insurance when no active policy exists', function () {
    $this->policy->delete();
    $this->appointment->update(['patient_insurance_policy_id' => null]);

    expect(fn () => app(AppointmentVerificationSender::class)->send(
        $this->appointment->fresh(),
        BillingWorkItem::PROCESSING_MODE_SELF_MANAGED,
    ))->toThrow(ValidationException::class, 'Add an active insurance policy');

    expect($this->appointment->fresh()->verification_status)
        ->toBe(Appointment::VERIFICATION_STATUS_NEEDS_INSURANCE)
        ->and($this->appointment->fresh()->verification_work_item_id)->toBeNull()
        ->and(BillingWorkItem::query()->where('appointment_id', $this->appointment->id)->exists())->toBeFalse();
});

it('keeps appointments as a core module on every saved plan', function () {
    $plan = SubscriptionPlan::create([
        'name' => 'Minimal Plan',
        'price' => 25,
        'plan_type' => SubscriptionPlan::PLAN_TYPE_VERIFICATION,
        'workspace_mode' => SubscriptionPlan::WORKSPACE_VERIFICATION,
        'max_clinics' => 1,
        'max_users' => 2,
        'included_modules' => [],
        'included_features' => [],
        'status' => true,
    ]);

    expect($plan->fresh()->included_modules)->toContain('appointments');
});

it('offers both managed and clinic team routing for hybrid clinics', function () {
    expect(VerificationRequestForm::processingModeOptions(
        $this->organization->id,
        $this->clinic->id,
        $this->location->id,
    ))->toHaveKeys([
        BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE,
        BillingWorkItem::PROCESSING_MODE_SELF_MANAGED,
    ]);
});

it('creates one linked verification request from an appointment', function () {
    $sender = app(AppointmentVerificationSender::class);
    $first = $sender->send($this->appointment, BillingWorkItem::PROCESSING_MODE_SELF_MANAGED);
    $second = $sender->send($this->appointment->fresh(), BillingWorkItem::PROCESSING_MODE_SELF_MANAGED);

    expect($second->is($first))->toBeTrue()
        ->and($first->appointment_id)->toBe($this->appointment->id)
        ->and($first->processing_mode)->toBe(BillingWorkItem::PROCESSING_MODE_SELF_MANAGED)
        ->and($this->appointment->fresh()->verification_work_item_id)->toBe($first->id)
        ->and($this->appointment->fresh()->verification_status)->toBe(Appointment::VERIFICATION_STATUS_SENT)
        ->and(BillingWorkItem::query()->where('appointment_id', $this->appointment->id)->count())->toBe(1);
});

it('normalizes the selected clinic service into appointment data', function () {
    $data = app(AppointmentSchedulingService::class)->validateAndNormalize([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'clinic_service_id' => $this->clinicService->id,
        'clinic_operatory_id' => $this->operatory->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => today()->addDays(2)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:45:00',
        'duration_minutes' => 45,
        'status' => 'scheduled',
    ]);

    expect($data['appointment_type'])->toBe('Comprehensive Exam')
        ->and($data['duration_minutes'])->toBe(45);
});

it('allows an appointment without an optional service', function () {
    $data = app(AppointmentSchedulingService::class)->validateAndNormalize([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => today()->addDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'duration_minutes' => 30,
        'status' => 'scheduled',
    ]);

    expect($data)->not->toHaveKey('clinic_service_id')
        ->and($data['duration_minutes'])->toBe(30);
});

it('blocks provider and operatory scheduling conflicts on save', function () {
    $data = [
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'clinic_service_id' => $this->clinicService->id,
        'clinic_operatory_id' => $this->operatory->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => $this->appointment->appointment_date->toDateString(),
        'start_time' => '09:15:00',
        'end_time' => '09:45:00',
        'duration_minutes' => 30,
        'status' => 'scheduled',
    ];

    expect(fn () => app(AppointmentSchedulingService::class)->validateAndNormalize($data))
        ->toThrow(ValidationException::class, 'This provider already has an appointment during the selected time.');
});

it('maps an assigned active location into the appointment workspace', function () {
    $this->actingAs($this->clinicUser);

    expect(AppointmentWorkspaceScope::mappedLocationId())->toBe($this->location->id);
});

it('uses the clinic default location when the user has no assigned location', function () {
    $secondLocation = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Second Office',
        'status' => true,
    ]);
    $this->clinic->update(['default_location_id' => $secondLocation->id]);
    $this->clinicUser->update(['location_id' => null]);

    $this->actingAs($this->clinicUser);

    expect(AppointmentWorkspaceScope::mappedLocationId())->toBe($secondLocation->id);
});

it('encrypts sensitive provider identifiers at rest', function () {
    $this->provider->update([
        'tax_id' => '12-3456789',
        'dea_number' => 'AB1234567',
    ]);

    $stored = DB::table('providers')->where('id', $this->provider->id)->first(['tax_id', 'dea_number']);

    expect($stored->tax_id)->not->toBe('12-3456789')
        ->and($stored->dea_number)->not->toBe('AB1234567')
        ->and($this->provider->fresh()->tax_id)->toBe('12-3456789')
        ->and($this->provider->fresh()->dea_number)->toBe('AB1234567');
});

it('rejects appointments outside configured provider hours', function () {
    $date = today()->next('Monday');
    $this->provider->update([
        'business_hours' => [
            'monday' => ['open' => true, 'opens_at' => '10:00', 'closes_at' => '12:00'],
        ],
    ]);

    $data = [
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => $date->toDateString(),
        'start_time' => '09:00:00',
        'end_time' => '09:30:00',
        'status' => 'scheduled',
    ];

    expect(fn () => app(AppointmentSchedulingService::class)->validateAndNormalize($data))
        ->toThrow(ValidationException::class, 'Select a time within this provider and location schedule.');
});

it('rejects appointments during provider leave and respects scheduling buffers', function () {
    $date = today()->next('Monday');
    $hours = ['monday' => [
        'open' => true,
        'opens_at' => '09:00',
        'closes_at' => '17:00',
        'break_starts_at' => '10:00',
        'break_ends_at' => '10:30',
    ]];
    $this->provider->update([
        'business_hours' => $hours,
        'schedule_exceptions' => [[
            'date' => $date->toDateString(),
            'all_day' => false,
            'starts_at' => '12:00',
            'ends_at' => '13:00',
            'reason' => 'Lunch meeting',
        ]],
        'scheduling_buffer_minutes' => 15,
    ]);

    $base = [
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => $date->toDateString(),
        'status' => 'scheduled',
    ];

    expect(fn () => app(AppointmentSchedulingService::class)->validateAndNormalize([
        ...$base,
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]))->toThrow(ValidationException::class, 'This time overlaps a configured break.');

    expect(fn () => app(AppointmentSchedulingService::class)->validateAndNormalize([
        ...$base,
        'start_time' => '12:15:00',
        'end_time' => '12:45:00',
    ]))->toThrow(ValidationException::class, 'This time is unavailable because of a closure or provider leave.');

    $this->operatory->update(['schedule_exceptions' => [[
        'date' => $date->toDateString(),
        'all_day' => false,
        'starts_at' => '15:00',
        'ends_at' => '16:00',
        'reason' => 'Equipment maintenance',
    ]]]);

    expect(fn () => app(AppointmentSchedulingService::class)->validateAndNormalize([
        ...$base,
        'clinic_operatory_id' => $this->operatory->id,
        'start_time' => '15:15:00',
        'end_time' => '15:45:00',
    ]))->toThrow(ValidationException::class, 'This time is unavailable because of a closure or provider leave.');

    Appointment::create([
        ...$base,
        'start_time' => '14:00:00',
        'end_time' => '14:30:00',
        'duration_minutes' => 30,
    ]);

    expect(fn () => app(AppointmentSchedulingService::class)->validateAndNormalize([
        ...$base,
        'start_time' => '14:30:00',
        'end_time' => '15:00:00',
    ]))->toThrow(ValidationException::class, 'This provider already has an appointment during the selected time.');
});

it('rejects a location outside the active clinic', function () {
    $otherOrganization = Organization::create([
        'name' => 'Other Dental Group',
        'owner_name' => 'Other Owner',
        'email' => 'other-owner@schedule.test',
        'status' => true,
    ]);
    $otherClinic = Clinic::create([
        'organization_id' => $otherOrganization->id,
        'clinic_name' => 'Other Clinic',
        'clinic_code' => 'CLN-OTHER-SCHEDULE',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);
    $otherLocation = Location::create([
        'clinic_id' => $otherClinic->id,
        'location_name' => 'Other Office',
        'status' => true,
    ]);

    $data = [
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $otherLocation->id,
        'clinic_service_id' => $this->clinicService->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => today()->addDays(2)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:45:00',
        'duration_minutes' => 45,
        'status' => 'scheduled',
    ];

    expect(fn () => app(AppointmentSchedulingService::class)->validateAndNormalize($data))
        ->toThrow(ValidationException::class, 'Select an active location from this clinic.');
});

it('renders the connected appointment editor and modal actions', function () {
    $this->actingAs($this->clinicUser)
        ->withSession([ClinicWorkspace::SESSION_KEY => ClinicWorkspace::VERIFICATION])
        ->get('/clinic/appointments/create')
        ->assertOk()
        ->assertSee('Service (Optional)')
        ->assertSee('at <strong>Main Office</strong>', false)
        ->assertSee('General Appointment')
        ->assertSee('Search or select a patient')
        ->assertSee('+ Add Patient')
        ->assertSee('Select a doctor to view appointment availability.')
        ->assertSee('Insurance verification required')
        ->assertDontSee('Add Patient Insurance')
        ->assertSee('Save Appointment')
        ->assertSee('Appointment Details')
        ->assertSee('Appointment Preview')
        ->assertSee('Pending patient selection')
        ->assertSee('Complete required details')
        ->assertDontSee('Booking Snapshot');
});
