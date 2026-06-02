<?php

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicOperatory;
use App\Models\Encounter;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientConsentForm;
use App\Models\PatientInsuranceClaim;
use App\Models\PatientInsuranceClaimLineItem;
use App\Models\PatientInsurancePolicy;
use App\Models\PatientLedgerEntry;
use App\Models\PatientStatement;
use App\Models\Provider;
use App\Models\SaasSetting;
use App\Models\ServiceItem;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use App\Support\ClinicStatementNotifications;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Database\Seeders\RoleSeeder;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    $compiledViews = storage_path('framework/testing/views');
    File::ensureDirectoryExists($compiledViews);
    config(['view.compiled' => $compiledViews]);

    $this->organization = Organization::create([
        'name' => 'Bright Dental Group',
        'owner_name' => 'Clinic Owner',
        'email' => 'owner@example.com',
        'phone' => '5551001000',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Bright Dental Downtown',
        'clinic_code' => 'CLN-TEST',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $this->location = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Downtown',
        'address' => '100 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10001',
        'country' => 'USA',
        'phone' => '5552002000',
        'status' => true,
    ]);

    $this->admin = User::factory()->create([
        'name' => 'Clinic Admin',
        'email' => 'clinic-admin@example.com',
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'status' => true,
    ]);
    $this->admin->assignRole('clinic_admin');

    $providerUser = User::factory()->create([
        'name' => 'Dr. Adams',
        'email' => 'doctor@example.com',
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
        'license_number' => 'LIC-123',
        'npi_number' => 'NPI-123',
        'tax_id' => 'TAX-123',
        'status' => true,
    ]);

    $this->patient = Patient::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'created_by' => $this->admin->id,
        'first_name' => 'John',
        'last_name' => 'Smith',
        'dob' => '1990-01-01',
        'gender' => 'male',
        'phone' => '5553003000',
        'email' => 'john.smith@example.com',
        'address' => '500 Park Ave',
        'status' => true,
    ]);

    $this->operatory = ClinicOperatory::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'name' => 'Operatory 1',
        'code' => 'OP1',
        'display_order' => 1,
        'status' => true,
    ]);

    $this->serviceItem = ServiceItem::create([
        'name' => 'Composite Restoration',
        'description' => 'Posterior composite restoration',
        'default_price' => 250,
        'status' => true,
    ]);

    $this->appointment = Appointment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'clinic_operatory_id' => $this->operatory->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_date' => today(),
        'start_time' => '09:00:00',
        'end_time' => '09:45:00',
        'duration_minutes' => 45,
        'status' => 'confirmed',
        'appointment_type' => 'Restorative',
        'notes' => 'Initial restorative visit',
    ]);

    $this->encounter = Encounter::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_id' => $this->appointment->id,
        'created_by' => $this->admin->id,
        'encounter_date' => today(),
        'status' => 'finalized',
        'chief_complaint' => 'Tooth pain',
    ]);

    $this->treatmentPlan = TreatmentPlan::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_id' => $this->appointment->id,
        'encounter_id' => $this->encounter->id,
        'created_by' => $this->admin->id,
        'plan_number' => 'TP-TEST-0001',
        'plan_date' => today(),
        'status' => 'accepted',
        'phase' => 'phase_1',
        'priority' => 'normal',
    ]);

    $this->treatmentPlanItem = TreatmentPlanItem::create([
        'treatment_plan_id' => $this->treatmentPlan->id,
        'service_item_id' => $this->serviceItem->id,
        'description' => 'Composite on #14',
        'tooth_number' => '14',
        'tooth_surface' => 'MOD',
        'quantity' => 1,
        'unit_fee' => 250,
        'estimated_insurance' => 150,
        'estimated_patient' => 100,
        'line_total' => 250,
        'status' => 'accepted',
        'target_date' => today()->addWeek(),
    ]);

    $this->policy = PatientInsurancePolicy::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'created_by' => $this->admin->id,
        'coverage_priority' => 'primary',
        'insurance_company' => 'Dental Shield',
        'plan_name' => 'PPO Plus',
        'member_id' => 'MEM123',
        'group_number' => 'GRP456',
        'subscriber_name' => 'John Smith',
        'subscriber_relationship' => 'self',
        'status' => true,
    ]);

    $this->claim = PatientInsuranceClaim::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'provider_id' => $this->provider->id,
        'appointment_id' => $this->appointment->id,
        'encounter_id' => $this->encounter->id,
        'treatment_plan_id' => $this->treatmentPlan->id,
        'created_by' => $this->admin->id,
        'claim_number' => 'CLM-TEST-0001',
        'claim_type' => 'claim',
        'claim_date' => today(),
        'service_date' => today(),
        'status' => 'ready',
    ]);

    $this->claimLineItem = PatientInsuranceClaimLineItem::create([
        'patient_insurance_claim_id' => $this->claim->id,
        'treatment_plan_item_id' => $this->treatmentPlanItem->id,
        'service_item_id' => $this->serviceItem->id,
        'procedure_code' => 'D2392',
        'description' => 'Composite on #14',
        'tooth_number' => '14',
        'tooth_surface' => 'MOD',
        'quantity' => 1,
        'unit_fee' => 250,
        'estimated_coverage' => 150,
        'insurance_paid' => 0,
        'status' => 'ready',
    ]);

    $this->statement = PatientStatement::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'created_by' => $this->admin->id,
        'statement_number' => 'STM-TEST-0001',
        'statement_date' => today(),
        'period_from' => today()->startOfMonth(),
        'period_to' => today()->endOfMonth(),
        'status' => 'issued',
        'recipient_email' => $this->patient->email,
    ]);

    PatientLedgerEntry::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'appointment_id' => $this->appointment->id,
        'encounter_id' => $this->encounter->id,
        'treatment_plan_id' => $this->treatmentPlan->id,
        'service_item_id' => $this->serviceItem->id,
        'created_by' => $this->admin->id,
        'posted_on' => today(),
        'entry_type' => 'charge',
        'status' => 'posted',
        'reference_number' => 'LED-CHG-1',
        'description' => 'Composite restoration charge',
        'quantity' => 1,
        'unit_amount' => 250,
        'debit_amount' => 250,
        'credit_amount' => 0,
        'insurance_portion' => 150,
        'patient_portion' => 100,
    ]);

    $this->statement->refreshSummary();

    Storage::disk('local')->put('patient-consent-forms/test-consent.pdf', 'consent-pdf');

    $this->consentForm = PatientConsentForm::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'encounter_id' => $this->encounter->id,
        'uploaded_by' => $this->admin->id,
        'form_type' => 'treatment_consent',
        'title' => 'Treatment Consent',
        'status' => 'signed',
        'document_date' => today(),
        'signed_on' => today(),
        'signed_by_name' => 'John Smith',
        'typed_signature' => 'John Smith',
        'file_path' => 'patient-consent-forms/test-consent.pdf',
        'original_filename' => 'test-consent.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 12,
        'body_text' => 'Patient consents to restorative treatment.',
    ]);

    SaasSetting::current()->forceFill([
        'email_enabled' => true,
        'email_mailer' => 'log',
        'email_from_address' => 'support@prodental.test',
        'email_from_name' => 'ProDental EMR',
    ])->save();
});

it('registers the new clinic workflow routes cleanly', function () {
    $router = app('router');

    expect($router->getRoutes()->match(Request::create('/clinic/patient-insurance-claims', 'GET'))->uri())
        ->toBe('clinic/patient-insurance-claims');
    expect($router->getRoutes()->match(Request::create('/clinic/dental-chart', 'GET'))->uri())
        ->toBe('clinic/dental-chart');
    expect($router->getRoutes()->match(Request::create('/clinic/patient-statements', 'GET'))->uri())
        ->toBe('clinic/patient-statements');
    expect($router->getRoutes()->match(Request::create('/clinic/clinic-operatories', 'GET'))->uri())
        ->toBe('clinic/clinic-operatories');
    expect($router->getRoutes()->match(Request::create('/clinic/patient-consent-forms', 'GET'))->uri())
        ->toBe('clinic/patient-consent-forms');
    expect($router->getRoutes()->match(Request::create('/clinic/appointments', 'GET'))->uri())
        ->toBe('clinic/appointments');
    expect($router->getRoutes()->match(Request::create('/clinic/treatment-plans', 'GET'))->uri())
        ->toBe('clinic/treatment-plans');
});

it('rolls claim line item totals into the claim header', function () {
    expect((float) $this->claim->fresh()->billed_amount)->toBe(250.0);
    expect((float) $this->claim->fresh()->estimated_coverage)->toBe(150.0);
    expect((float) $this->claim->fresh()->patient_responsibility)->toBe(100.0);
    expect($this->claim->fresh()->procedure_summary)->toContain('Composite on #14');
});

it('can send a patient statement and track delivery metadata', function () {
    Mail::fake();

    $this->actingAs($this->admin);

    $result = ClinicStatementNotifications::send($this->statement->fresh(), $this->admin);

    expect($result)->toBeTrue();

    $statement = $this->statement->fresh();

    expect($statement->recipient_email)->toBe('john.smith@example.com');
    expect($statement->sent_at)->not->toBeNull();
    expect($statement->last_sent_by)->toBe($this->admin->id);
    expect($statement->status)->toBe('sent');
});

it('serves statement pdf and consent preview routes for authorized clinic users', function () {
    $this->actingAs($this->admin);

    $pdf = \Mockery::mock(DomPdfWrapper::class, [
        new Dompdf(),
        app('config'),
        app('files'),
        app('view'),
    ])->makePartial();
    $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
    $pdf->shouldReceive('output')->once()->andReturn('%PDF-1.4 test statement');

    Pdf::shouldReceive('loadView')
        ->once()
        ->with('pdf.patient-statements.show', \Mockery::type('array'))
        ->andReturn($pdf);

    $this->get(route('clinic.patient-statements.show', $this->statement))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('clinic.patient-consent-forms.show', $this->consentForm))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
