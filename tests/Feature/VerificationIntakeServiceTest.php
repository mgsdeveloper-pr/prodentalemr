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

it('honors the selected clinic even for a SaaS administrator', function () {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id]);
    $other = $this->clinic->replicate();
    $other->clinic_code = 'OTHER-SCOPE';
    $other->save();
    $otherLocation = Location::create(['clinic_id' => $other->id, 'location_name' => 'Outside', 'status' => true]);
    expect(\App\Support\VerificationCreationContext::locations())->toBe([$this->location->id => 'Main Office']);
    expect(fn () => \App\Support\VerificationCreationContext::validate([
        'location_id' => $otherLocation->id, 'clinic_id' => $other->id, 'priority' => 'normal',
    ]))->toThrow(ValidationException::class);
});

it('defaults to upcoming clinic dates and uses time only to distinguish appointments', function () {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id]);
    $past = $this->appointment->replicate();
    $past->appointment_date = now('America/New_York')->subDays(2)->toDateString();
    $past->save();
    $options = \App\Support\VerificationCreationContext::appointments($this->clinic->id, null);
    expect($options)->toHaveKey($this->appointment->id)->not->toHaveKey($past->id)
        ->and($options[$this->appointment->id])->not->toContain('9:00 AM');
    $second = $this->appointment->replicate();
    $second->start_time = '10:00:00';
    $second->save();
    $options = \App\Support\VerificationCreationContext::appointments($this->clinic->id, null, true);
    expect($options)->toHaveKey($past->id)
        ->and($options[$this->appointment->id])->toContain('9:00 AM')
        ->and($options[$second->id])->toContain('10:00 AM')
        ->and($options[$past->id])->toContain('Past');
});

it('requires an urgency reason and blocks cancelled or previously verified appointments', function () {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id]);
    $data = ['location_id' => $this->location->id, 'clinic_id' => $this->clinic->id, 'priority' => 'urgent'];
    expect(fn () => \App\Support\VerificationCreationContext::validate($data))->toThrow(ValidationException::class);
    $data['urgency_reason'] = 'Appointment moved forward';
    expect(\App\Support\VerificationCreationContext::validate($data))->toBe('Appointment moved forward');
    $data['appointment_id'] = $this->appointment->id;
    $this->appointment->update(['status' => 'cancelled']);
    expect(fn () => \App\Support\VerificationCreationContext::validate($data))->toThrow(ValidationException::class);
    $this->appointment->update(['status' => 'scheduled']);
    \App\Models\BillingWorkItem::create([
        'organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'appointment_id' => $this->appointment->id,
        'title' => 'Existing verification', 'status' => 'done', 'priority' => 'normal',
    ]);
    expect(fn () => \App\Support\VerificationCreationContext::validate($data))->toThrow(ValidationException::class)
        ->and((string) \App\Support\VerificationCreationContext::appointmentNotice($this->appointment->id))->toContain('Open existing request');
});

it('rejects assignees without access to the request clinic', function () {
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_manager');
    $data = ['clinic_id' => $this->clinic->id, 'organization_id' => $this->organization->id,
        'managed_billing_service_id' => $this->service->id, 'assigned_to' => $user->id];
    expect(fn () => app(VerificationIntakeService::class)->normalizeAndValidate($data))->toThrow(ValidationException::class);
    $user->verificationClinics()->attach($this->clinic->id);
    expect(app(VerificationIntakeService::class)->normalizeAndValidate($data)['assigned_to'])->toBe($user->id);
});

it('renders visible priority and date-first intake in both panels', function (string $panel, string $page) {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id,
        \App\Support\ClinicPanelScope::SESSION_KEY => $this->clinic->id]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel($panel));
    \Livewire\Livewire::test($page)->assertSuccessful()->assertSee('Urgent')
        ->assertDontSee('Pre-registered')->assertFormFieldDoesNotExist('vf_is_pre_registered')
        ->assertSee('Existing Patient')->assertDontSee('Appointment Time')
        ->set('data.import_patient_id', (string) $this->patient->id)->assertSee('Include past appointments')
        ->set('data.priority', 'urgent')->assertSee('Reason for urgency')
        ->set('data.intake_mode', 'manual')
        ->assertSee('Additional Details')->assertSee('Patient ID in Clinic System')
        ->assertFormFieldExists('vf_pms_id', fn ($field): bool => ! $field->isRequired());
})->with([
    ['admin', \App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest::class],
    ['clinic', \App\Filament\Clinic\Resources\VerificationRequests\Pages\CreateVerificationRequest::class],
]);

it('creates an urgent imported request and preserves its hidden appointment time', function (string $panel, string $page) {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id,
        \App\Support\ClinicPanelScope::SESSION_KEY => $this->clinic->id]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel($panel));
    $component = \Livewire\Livewire::test($page)
        ->set('data.import_patient_id', (string) $this->patient->id)
        ->set('data.import_appointment_id', (string) $this->appointment->id)
        ->assertSet('data.appointment_id', $this->appointment->id)
        ->assertSet('data.vf_appointment_time', '09:00:00')
        ->assertSee('Edit imported details')
        ->assertDontSee('Appointment Information')
        ->set('data.edit_imported_details', true)->assertSee('Appointment Information')
        ->set('data.edit_imported_details', false)->assertDontSee('Appointment Information')
        ->set('data.priority', 'urgent')
        ->set('data.urgency_reason', 'Appointment moved forward')
        ->set('data.vf_insured_relation', 'self')
        ->set('data.verification_plan_snapshots', [['plan_priority' => 'primary', 'payer_name' => 'Aetna Dental']])
        ->call('create')->assertHasNoFormErrors();
    $record = \App\Models\BillingWorkItem::where('appointment_id', $this->appointment->id)->firstOrFail();
    expect($record->priority)->toBe('urgent')
        ->and($record->verificationProfile->appointment_time)->toBe('09:00:00')
        ->and($record->activities()->where('activity_type', 'verification_escalated')->count())->toBe(1)
        ->and($record->activities()->where('activity_type', 'urgent_priority_flagged')->count())->toBe(1);
})->with([
    ['admin', \App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest::class],
    ['clinic', \App\Filament\Clinic\Resources\VerificationRequests\Pages\CreateVerificationRequest::class],
]);

it('clears incompatible import data when changing location in either panel', function (string $panel, string $page) {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id,
        \App\Support\ClinicPanelScope::SESSION_KEY => $this->clinic->id]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel($panel));
    $second = Location::create(['clinic_id' => $this->clinic->id, 'location_name' => 'Second', 'status' => true]);
    \Livewire\Livewire::test($page)->set('data.import_patient_id', (string) $this->patient->id)
        ->set('data.import_appointment_id', (string) $this->appointment->id)
        ->assertSet('data.patient_id', $this->patient->id)
        ->set('data.edit_imported_details', true)
        ->set('data.location_id', (string) $second->id)
        ->assertSet('data.appointment_id', null)->assertSet('data.patient_id', null)
        ->assertSet('data.provider_id', null)->assertSet('data.vf_appointment_time', null)
        ->assertSet('data.vf_patient_full_name', null)->assertSet('data.assigned_to', null);
})->with([
    ['admin', \App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest::class],
    ['clinic', \App\Filament\Clinic\Resources\VerificationRequests\Pages\CreateVerificationRequest::class],
]);

it('rejects a changed date on an imported appointment rather than silently replacing it', function () {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id]);
    expect(fn () => \App\Support\VerificationCreationContext::validate([
        'clinic_id' => $this->clinic->id, 'location_id' => $this->location->id,
        'priority' => 'normal', 'appointment_id' => $this->appointment->id,
        'vf_appointment_date' => today()->addDays(10)->toDateString(),
    ]))->toThrow(ValidationException::class);
});

it('keeps assignment methods explicit without changing the urgency deadline', function () {
    $this->freezeTime();
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $user->verificationClinics()->attach($this->clinic->id);
    $data = ['appointment_id' => $this->appointment->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'priority' => 'urgent', 'source' => 'manual',
        'assigned_to' => $user->id];
    $action = app(\App\Actions\Verification\CreateVerificationRequestAction::class);
    $unassigned = $action->prepareData([...$data, 'assignment_method' => 'unassigned']);
    $manual = $action->prepareData([...$data, 'assignment_method' => 'manual']);
    $auto = $action->prepareData([...$data, 'assignment_method' => 'auto']);
    expect($unassigned['assigned_to'])->toBeNull()->and($unassigned['status'])->toBe('unassigned')
        ->and($manual['assigned_to'])->toBe($user->id)->and($auto['assigned_to'])->toBe($user->id)
        ->and($manual['due_at']->equalTo($unassigned['due_at']))->toBeTrue()
        ->and($auto['due_at']->equalTo($unassigned['due_at']))->toBeTrue();
    expect(fn () => $action->prepareData([...$data, 'assignment_method' => 'manual', 'assigned_to' => null]))
        ->toThrow(ValidationException::class);
    $record = $action->execute([...$data, 'assignment_method' => 'manual', 'title' => 'Manual assignment']);
    expect($record->activities()->where('activity_type', 'assignment_changed')->first()->meta['assignment_mode'])->toBe('manual');
});

it('uses the clinic setting for managed requests and keeps self-managed work local', function () {
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $user->verificationClinics()->attach($this->clinic->id);
    $data = ['appointment_id' => $this->appointment->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'source' => 'clinic_request',
        'priority' => 'normal', 'assignment_method' => 'manual', 'assigned_to' => $user->id];
    $action = app(\App\Actions\Verification\CreateVerificationRequestAction::class);
    expect($action->prepareData($data)['assigned_to'])->toBeNull();
    $this->clinic->update(['verification_assignment_method' => 'auto']);
    expect($action->prepareData($data)['assigned_to'])->toBe($user->id);
    $local = $action->prepareData([...$data, 'source' => 'clinic_self_service']);
    expect($local['assigned_to'])->toBeNull()->and($local['assignment_method'])->toBe('clinic')
        ->and($local['processing_mode'])->toBe(\App\Models\BillingWorkItem::PROCESSING_MODE_SELF_MANAGED);
});

it('records an automatic-assignment fallback when nobody is eligible', function () {
    $record = app(\App\Actions\Verification\CreateVerificationRequestAction::class)->execute([
        'appointment_id' => $this->appointment->id, 'managed_billing_service_id' => $this->service->id,
        'source' => 'manual', 'priority' => 'urgent', 'assignment_method' => 'auto', 'title' => 'Fallback',
    ]);
    expect($record->assigned_to)->toBeNull()->and($record->status)->toBe('unassigned')
        ->and($record->priority)->toBe('urgent')->and($record->due_at)->not->toBeNull()
        ->and($record->activities()->where('activity_type', 'auto_assignment_unavailable')->count())->toBe(1);
});

it('shows the redesigned intake and defaults the team assignment method to unassigned', function () {
    expect((new \App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest)->areFormActionsSticky())->toBeFalse();
    expect((new \App\Filament\Clinic\Resources\VerificationRequests\Pages\CreateVerificationRequest)->areFormActionsSticky())->toBeFalse();
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    \Livewire\Livewire::test(\App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest::class)
        ->assertSee('New Verification Request')->assertSee('Manual Entry')->assertSee('Existing Patient')
        ->assertSee('Assignment Method')->assertSet('data.assignment_method', 'unassigned')
        ->set('data.assignment_method', 'manual')->assertSee('Verifier')
        ->set('data.assigned_to', $admin->id)->set('data.assignment_method', 'auto')
        ->assertSet('data.assigned_to', null);
    if (getenv('VERIFICATION_INTAKE_PREVIEW')) {
        $response = $this->get('/verification/verifications/create')->assertOk();
        \Illuminate\Support\Facades\File::ensureDirectoryExists(storage_path('app/qa'));
        file_put_contents(storage_path('app/qa/verification-intake.html'), $response->getContent());
        $preview = \Livewire\Livewire::test(\App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest::class)
            ->set('data.import_patient_id', (string) $this->patient->id)
            ->set('data.import_appointment_id', (string) $this->appointment->id);
        file_put_contents(storage_path('app/qa/verification-intake-imported-component.html'), $preview->html());
        file_put_contents(storage_path('app/qa/verification-intake-imported-snapshot.json'), json_encode($preview->snapshot));
        $preview->set('data.assignment_method', 'manual');
        file_put_contents(storage_path('app/qa/verification-intake-assignment-component.html'), $preview->html());
        file_put_contents(storage_path('app/qa/verification-intake-assignment-snapshot.json'), json_encode($preview->snapshot));
        $preview->set('data.assignment_method', 'unassigned');
        $preview->set('data.intake_mode', 'manual');
        file_put_contents(storage_path('app/qa/verification-intake-manual-component.html'), $preview->html());
        file_put_contents(storage_path('app/qa/verification-intake-manual-snapshot.json'), json_encode($preview->snapshot));
    }
});

it('notifies the clinic queue manager when automatic assignment falls back', function () {
    $manager = User::factory()->create(['status' => true]);
    $manager->assignRole('verification_manager');
    $manager->verificationClinics()->attach($this->clinic->id);
    \App\Models\SaasSetting::current()->update([
        'verification_notify_admin_all' => true, 'verification_notify_on_assignment_changed' => true,
    ]);
    $this->mock(\App\Services\Verification\AssignmentService::class, function ($mock): void {
        $mock->shouldReceive('autoAssign')->once()->andReturnNull();
    });
    $record = app(\App\Actions\Verification\CreateVerificationRequestAction::class)->execute([
        'appointment_id' => $this->appointment->id, 'managed_billing_service_id' => $this->service->id,
        'source' => 'manual', 'priority' => 'normal', 'assignment_method' => 'auto', 'title' => 'Notify fallback',
    ]);
    expect(\App\Models\VerificationNotification::where('user_id', $manager->id)
        ->where('billing_work_item_id', $record->id)->where('activity_type', 'auto_assignment_unavailable')->exists())->toBeTrue();
});

it('creates an existing-patient request with an entered date and no appointment', function (string $panel, string $page) {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id,
        \App\Support\ClinicPanelScope::SESSION_KEY => $this->clinic->id]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel($panel));
    $this->appointment->delete();
    $this->patient->update(['pms_patient_id' => 'CLINIC-123']);
    \Livewire\Livewire::test($page)->set('data.import_patient_id', (string) $this->patient->id)
        ->assertSet('data.vf_pms_id', 'CLINIC-123')
        ->assertDontSee('Patient ID in Clinic System')
        ->assertSet('data.patient_id', $this->patient->id)->assertSet('data.appointment_id', null)
        ->set('data.provider_id', $this->provider->id)
        ->set('data.vf_appointment_date', today()->addDays(3)->toDateString())
        ->set('data.vf_insured_relation', 'self')
        ->set('data.verification_plan_snapshots', [['plan_priority' => 'primary', 'payer_name' => 'Test Payer']])
        ->call('create')->assertHasNoFormErrors();
    $record = \App\Models\BillingWorkItem::where('patient_id', $this->patient->id)->firstOrFail();
    expect($record->appointment_id)->toBeNull()
        ->and($record->verificationProfile->pms_id)->toBe('CLINIC-123')
        ->and($record->verificationProfile->patient_full_name)->toBe('Intake Patient')
        ->and($record->verificationProfile->appointment_date->toDateString())->toBe(today()->addDays(3)->toDateString());
})->with([
    ['admin', \App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest::class],
    ['clinic', \App\Filament\Clinic\Resources\VerificationRequests\Pages\CreateVerificationRequest::class],
]);

it('keeps patient policy selection when selecting and clearing an appointment', function (string $panel, string $page) {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id,
        \App\Support\ClinicPanelScope::SESSION_KEY => $this->clinic->id]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel($panel));
    $policy = \App\Models\PatientInsurancePolicy::create([
        'organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id, 'patient_id' => $this->patient->id,
        'coverage_priority' => 'primary', 'insurance_company' => 'First Payer', 'member_id' => 'MEMBER-001',
        'subscriber_name' => 'Intake Patient', 'subscriber_dob' => '1990-01-15',
        'subscriber_relationship' => 'self', 'status' => true,
    ]);
    $secondary = $policy->replicate();
    $secondary->fill(['coverage_priority' => 'secondary', 'insurance_company' => 'Second Payer', 'member_id' => 'MEMBER-002'])->save();
    $component = \Livewire\Livewire::test($page)->set('data.import_patient_id', (string) $this->patient->id)
        ->assertSet('data.vf_patient_identifier', 'MEMBER-001')->assertSee('Patient Insurance Policy')
        ->set('data.selected_patient_policy', $secondary->id)
        ->assertSet('data.patient_insurance_policy_id', $secondary->id)
        ->assertSet('data.vf_patient_identifier', 'MEMBER-002')
        ->set('data.import_appointment_id', $this->appointment->id)
        ->assertSet('data.vf_patient_identifier', 'MEMBER-002')
        ->set('data.import_appointment_id', null)
        ->assertSet('data.patient_id', $this->patient->id)->assertSet('data.appointment_id', null)
        ->assertSet('data.vf_appointment_time', null)->assertSet('data.vf_patient_identifier', 'MEMBER-002')
        ->set('data.intake_mode', 'manual')->assertSet('data.patient_id', null)
        ->assertSet('data.patient_insurance_policy_id', null)->assertSet('data.selected_patient_policy', null);
    $options = \App\Support\VerificationCreationContext::patientOptions($this->clinic->id, $panel === 'clinic', 'Intake Patient');
    expect($options)->toHaveCount(1)
        ->and($options[$this->patient->id])->toBe('Intake Patient (DOB: Jan 15, 1990 | Member ID: MEMBER-001)');
    expect(\App\Support\VerificationCreationContext::patientOptions($this->clinic->id, $panel === 'clinic', 'MEMBER-002'))
        ->toHaveKey($this->patient->id);
})->with([
    ['admin', \App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest::class],
    ['clinic', \App\Filament\Clinic\Resources\VerificationRequests\Pages\CreateVerificationRequest::class],
]);

it('rejects another patients appointment and filters the appointment list', function (string $panel, string $page) {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id,
        \App\Support\ClinicPanelScope::SESSION_KEY => $this->clinic->id]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel($panel));
    $other = $this->patient->replicate();
    $other->first_name = 'Another';
    $other->save();
    $appointment = $this->appointment->replicate();
    $appointment->patient_id = $other->id;
    $appointment->save();
    $options = \App\Support\VerificationCreationContext::appointments($this->clinic->id, null, false, $panel === 'clinic', $this->patient->id);
    expect($options)->toHaveKey($this->appointment->id)->not->toHaveKey($appointment->id);
    \Livewire\Livewire::test($page)->set('data.import_patient_id', $this->patient->id)
        ->set('data.import_appointment_id', $appointment->id)
        ->assertHasErrors(['data.import_appointment_id'])
        ->assertSet('data.patient_id', $this->patient->id)->assertSet('data.appointment_id', null);
})->with([
    ['admin', \App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest::class],
    ['clinic', \App\Filament\Clinic\Resources\VerificationRequests\Pages\CreateVerificationRequest::class],
]);

it('does not expose or import patients outside the selected clinic', function (string $panel, string $page) {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session([\App\Support\AdminClinicScope::SESSION_KEY => $this->clinic->id,
        \App\Support\ClinicPanelScope::SESSION_KEY => $this->clinic->id]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel($panel));
    $clinic = $this->clinic->replicate();
    $clinic->clinic_code = 'OTHER-PATIENT';
    $clinic->save();
    $patient = $this->patient->replicate();
    $patient->clinic_id = $clinic->id;
    $patient->save();
    expect(\App\Support\VerificationCreationContext::patientOptions($this->clinic->id, $panel === 'clinic'))
        ->toHaveKey($this->patient->id)->not->toHaveKey($patient->id);
    \Livewire\Livewire::test($page)->set('data.import_patient_id', $patient->id)
        ->assertHasErrors(['data.import_patient_id'])->assertSet('data.patient_id', null);
})->with([
    ['admin', \App\Filament\Saas\Resources\Verifications\Pages\CreateVerificationRequest::class],
    ['clinic', \App\Filament\Clinic\Resources\VerificationRequests\Pages\CreateVerificationRequest::class],
]);
