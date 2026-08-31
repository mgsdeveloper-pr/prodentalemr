<?php

use App\Filament\Clinic\Pages\PortalCredentialSettings;
use App\Filament\Clinic\Pages\VerificationSettings;
use App\Filament\Clinic\Resources\PortalCredentials\Pages\ListPortalCredentials;
use App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource;
use App\Filament\Clinic\Resources\VerificationQuestions\Pages\CreateVerificationQuestion;
use App\Filament\Clinic\Resources\VerificationQuestions\Pages\EditVerificationQuestion;
use App\Filament\Clinic\Resources\VerificationQuestions\Pages\ListVerificationQuestions;
use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\PortalCredential;
use App\Models\PortalCredentialSecurityQuestion;
use App\Models\Provider;
use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationPdfPreset;
use App\Models\VerificationTemplateVersion;
use App\Services\Verification\WorkflowService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

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

it('creates clinic verification requests from active enrollments', function () {
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

it('freezes self-managed and managed-service processing rules on each request', function () {
    $this->actingAs($this->clinicUser);
    $this->clinic->update([
        'verification_services_enabled' => true,
        'verification_service_status' => 'active',
        'service_status' => 'active',
    ]);
    foreach (['view', 'update'] as $action) {
        $this->clinicUser->givePermissionTo(Permission::findOrCreate("clinic.verification_requests.{$action}", 'web'));
    }

    $selfManaged = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->verificationService->id,
        'created_by' => $this->clinicUser->id,
        'title' => 'Clinic team verification',
        'source' => 'clinic_self_service',
        'processing_mode' => BillingWorkItem::PROCESSING_MODE_SELF_MANAGED,
        'status' => BillingWorkItem::STATUS_PENDING,
    ]);

    $managed = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->verificationService->id,
        'created_by' => $this->clinicUser->id,
        'title' => 'Managed verification',
        'source' => 'clinic_request',
        'processing_mode' => BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE,
        'status' => BillingWorkItem::STATUS_PENDING,
    ]);

    expect($selfManaged->workflowMode())->toBe('self_service')
        ->and($selfManaged->clinicUserCanOpenVerificationForm($this->clinicUser))->toBeTrue()
        ->and($managed->workflowMode())->toBe('managed_service')
        ->and($managed->clinicUserCanOpenVerificationForm($this->clinicUser))->toBeFalse();

    $managed->status = BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE;

    expect($managed->clinicUserCanRespondToVerification($this->clinicUser))->toBeTrue();
});

it('offers per-request routing only when the managed enrollment allows clinic workspace', function () {
    expect(VerificationRequestForm::processingModeOptions(
        $this->organization->id,
        $this->clinic->id,
        $this->location->id,
    ))->toBe([BillingWorkItem::PROCESSING_MODE_SELF_MANAGED => 'Self-Managed'])
        ->and(VerificationRequestForm::processingModeHelperText(
            $this->organization->id,
            $this->clinic->id,
            $this->location->id,
        ))->toBe('This request will be completed by the clinic as Self-Managed.');

    $managedEnrollment = ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->verificationService->id,
        'created_by' => $this->clinicUser->id,
        'status' => 'active',
        'clinic_workspace_enabled' => false,
    ]);

    expect(VerificationRequestForm::processingModeOptions(
        $this->organization->id,
        $this->clinic->id,
        $this->location->id,
    ))->toBe([BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE => 'Managed Service'])
        ->and(VerificationRequestForm::processingModeHelperText(
            $this->organization->id,
            $this->clinic->id,
            $this->location->id,
        ))->toBe('This request will be completed by the Managed Service team.');

    $managedEnrollment->update(['clinic_workspace_enabled' => true]);

    expect(VerificationRequestForm::processingModeOptions(
        $this->organization->id,
        $this->clinic->id,
        $this->location->id,
    ))->toBe([
        BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE => 'Managed Service',
        BillingWorkItem::PROCESSING_MODE_SELF_MANAGED => 'Self-Managed',
    ])->and(VerificationRequestForm::processingModeHelperText(
        $this->organization->id,
        $this->clinic->id,
        $this->location->id,
    ))->toBe('Choose whether the clinic will self-manage this request or send it to Managed Service.')
        ->and(VerificationRequestForm::processingModeHelperText(
            null,
            null,
            null,
        ))->toBe('Select a clinic from the Workspace menu to determine who will complete the request.');
});

it('preserves the completed form snapshot when a clinic requests a correction', function () {
    $this->actingAs($this->clinicUser);
    $this->clinic->update([
        'verification_services_enabled' => true,
        'verification_service_status' => 'active',
        'service_status' => 'active',
    ]);
    foreach (['view', 'update'] as $action) {
        $this->clinicUser->givePermissionTo(Permission::findOrCreate("clinic.verification_requests.{$action}", 'web'));
    }

    $request = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->verificationService->id,
        'created_by' => $this->clinicUser->id,
        'title' => 'Completed managed verification',
        'source' => 'clinic_request',
        'processing_mode' => BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE,
        'status' => BillingWorkItem::STATUS_DONE,
        'outcome_status' => 'verified',
    ]);

    $submission = $request->formSubmissions()->create([
        'user_id' => $this->clinicUser->id,
        'panel' => 'verification',
        'status' => BillingWorkItem::STATUS_DONE,
        'outcome_status' => 'verified',
        'priority' => 'normal',
        'version' => 1,
        'payload' => ['answers' => [['prompt' => 'Eligibility status', 'value' => 'Active']]],
    ]);

    $updated = app(WorkflowService::class)->requestCorrection(
        $request,
        'Please confirm the annual maximum remaining.',
        $this->clinicUser,
    );

    expect($updated->normalized_status)->toBe(BillingWorkItem::STATUS_RETURNED_FOR_REWORK)
        ->and($updated->return_reason)->toBe('Please confirm the annual maximum remaining.')
        ->and($updated->formSubmissions()->count())->toBe(1)
        ->and($updated->formSubmissions()->first()->is($submission))->toBeTrue()
        ->and($updated->formSubmissions()->first()->payload)->toBe($submission->payload)
        ->and($updated->activities()->where('activity_type', 'clinic_correction_requested')->exists())->toBeTrue();
});

it('keeps verification settings page actions wired', function () {
    $this->actingAs($this->clinicUser);

    $workItem = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->verificationService->id,
        'client_service_enrollment_id' => null,
        'appointment_id' => $this->appointment->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'created_by' => $this->clinicUser->id,
        'title' => 'Settings preview request',
        'source' => 'clinic_request',
        'status' => BillingWorkItem::STATUS_PENDING,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'pms_sync_status' => 'pending',
        'writeback_status' => 'not_requested',
    ]);

    Livewire::test(VerificationSettings::class)
        ->call('showSettingsSection', 'template-management')
        ->assertSet('activeSettingsSection', 'template-management')
        ->call('showSettingsSection', 'pdf-settings')
        ->assertSet('activeSettingsSection', 'pdf-settings')
        ->call('showSettingsSection', 'unknown-section')
        ->assertSet('activeSettingsSection', 'pdf-settings')
        ->call('createNewPreset')
        ->assertSet('data.verification_pdf_preset_name', 'Custom PDF Preset')
        ->set('data.verification_pdf_preset_name', 'Clinic Action Test Preset')
        ->set('data.verification_pdf_preset_description', 'Saved from settings action test.')
        ->set('data.verification_pdf_output_mode', 'standard')
        ->call('save')
        ->assertHasNoErrors();

    expect(VerificationPdfPreset::query()
        ->where('clinic_id', $this->clinic->id)
        ->where('name', 'Clinic Action Test Preset')
        ->exists())->toBeTrue();

    expect(Livewire::test(VerificationSettings::class)->instance()->getPreviewPdfUrl())
        ->toBe(route('clinic.verification-requests.pdf.preview', $workItem));
});

it('renders the clinic template builder as one focused workspace', function () {
    $this->actingAs($this->clinicUser);
    Filament::setCurrentPanel(Filament::getPanel('clinic'));

    $component = Livewire::test(ListVerificationQuestions::class)
        ->assertSee('Clinic Template Builder')
        ->assertSee('Template Structure')
        ->assertSee('Frequency & Percentage')
        ->assertSee('Create Draft to Reorder')
        ->assertSee('Create Draft to Add Question')
        ->assertSee('Form Preview')
        ->call('beginTemplateChange', 'reorder')
        ->assertSet('pendingBuilderAction', 'reorder')
        ->assertSet('showCreateDraftModal', true)
        ->assertSee('Create an editable copy?')
        ->assertSee('Continue to Reorder')
        ->assertDontSee('Clinic visibility');

    expect($component->instance()->getBuilderCounts())
        ->toMatchArray([
            'main_sections' => 8,
            'sub_sections' => 4,
        ]);
});

it('continues add question and reorder actions inside a clinic draft', function () {
    $this->actingAs($this->clinicUser);
    Filament::setCurrentPanel(Filament::getPanel('clinic'));

    $component = Livewire::test(ListVerificationQuestions::class);
    $sectionKey = $component->instance()->getSelectedBuilderSection()['key'];

    $component
        ->call('selectBuilderSection', $sectionKey)
        ->call('beginTemplateChange', 'questions')
        ->assertSet('showCreateDraftModal', true)
        ->assertSee('Continue to Add Question')
        ->call('submitCreateDraftVersion')
        ->assertHasNoErrors();

    $draft = VerificationTemplateVersion::query()
        ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
        ->where('clinic_id', $this->clinic->id)
        ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
        ->where('is_working_draft', true)
        ->latest('id')
        ->firstOrFail();

    $component->assertRedirect(VerificationQuestionResource::getUrl('create', [
        'section' => $sectionKey,
        'template_version_id' => $draft->id,
    ]));

    Livewire::withQueryParams([
        'section' => 'template_3_verification_information',
        'template_version_id' => $draft->id,
    ])
        ->test(CreateVerificationQuestion::class)
        ->assertSet('requestedSectionKey', 'template_3_verification_information')
        ->assertSet('data.section_key', 'template_3_verification_information')
        ->assertSet('data.sub_section_key', null)
        ->assertSee('Add Question')
        ->assertSee('Verification Information')
        ->assertSee('Question placement')
        ->assertDontSee('Create and organize verification questions')
        ->assertDontSee('Clinic Clinic Template');

    Livewire::withQueryParams([
        'section' => $sectionKey,
        'template_version_id' => $draft->id,
    ])
        ->test(CreateVerificationQuestion::class)
        ->fillForm([
            'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
            'section_key' => $sectionKey,
            'form_type' => 'both',
            'prompt' => 'Clinic action test question',
            'input_type' => 'text',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $createdQuestion = VerificationFormQuestion::query()
        ->where('template_version_id', $draft->id)
        ->where('clinic_id', $this->clinic->id)
        ->where('section_key', $sectionKey)
        ->where('prompt', 'Clinic action test question')
        ->firstOrFail();

    $editUrl = Livewire::test(ListVerificationQuestions::class)
        ->instance()
        ->getEditUrl($createdQuestion->id);
    parse_str((string) parse_url($editUrl, PHP_URL_QUERY), $editQuery);

    expect($editQuery)->toMatchArray([
        'section' => $sectionKey,
        'template_version_id' => (string) $draft->id,
    ]);

    $builderReturnUrl = VerificationQuestionResource::getUrl('index', [
        'draft' => '1',
        'version' => $draft->id,
        'section' => $sectionKey,
    ]);

    Livewire::withQueryParams([
        'draft' => '1',
        'version' => $draft->id,
        'section' => $sectionKey,
    ])
        ->test(ListVerificationQuestions::class)
        ->assertSet('showDraft', true)
        ->assertSet('selectedTemplateVersionId', $draft->id)
        ->assertSet('selectedSectionKey', $sectionKey);

    Livewire::withQueryParams([
        'section' => $sectionKey,
        'template_version_id' => $draft->id,
    ])
        ->test(EditVerificationQuestion::class, ['record' => $createdQuestion->getRouteKey()])
        ->assertSee('Save & Add Another')
        ->set('data.prompt', 'Updated clinic action test question')
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect($builderReturnUrl);

    expect($createdQuestion->fresh()->prompt)->toBe('Updated clinic action test question');

    $createNextUrl = VerificationQuestionResource::getUrl('create', [
        'section' => $sectionKey,
        'template_version_id' => $draft->id,
    ]);

    Livewire::withQueryParams([
        'section' => $sectionKey,
        'template_version_id' => $draft->id,
    ])
        ->test(EditVerificationQuestion::class, ['record' => $createdQuestion->getRouteKey()])
        ->set('data.prompt', 'Updated before adding another question')
        ->call('saveAndAddAnother')
        ->assertHasNoFormErrors()
        ->assertRedirect($createNextUrl);

    expect($createdQuestion->fresh()->prompt)->toBe('Updated before adding another question');

    $publishedVersion = VerificationTemplateVersion::query()
        ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
        ->where('clinic_id', $this->clinic->id)
        ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
        ->where('is_active', true)
        ->firstOrFail();

    Livewire::withQueryParams([
        'section' => $sectionKey,
        'template_version_id' => $publishedVersion->id,
    ])
        ->test(EditVerificationQuestion::class, ['record' => $createdQuestion->getRouteKey()])
        ->assertNotFound();

    $createAnother = Livewire::withQueryParams([
        'section' => $sectionKey,
        'template_version_id' => $draft->id,
    ])
        ->test(CreateVerificationQuestion::class)
        ->assertSee('Save & Add Another')
        ->fillForm([
            'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
            'section_key' => $sectionKey,
            'form_type' => 'both',
            'prompt' => 'First repeated-entry question',
            'input_type' => 'text',
            'is_active' => true,
        ])
        ->call('createAnother')
        ->assertHasNoFormErrors()
        ->assertSet('data.section_key', $sectionKey)
        ->assertSet('data.form_type', 'both');

    expect(VerificationFormQuestion::query()
        ->where('template_version_id', $draft->id)
        ->where('clinic_id', $this->clinic->id)
        ->where('prompt', 'First repeated-entry question')
        ->exists())->toBeTrue()
        ->and($createAnother->get('data.prompt'))->toBeNull();

    $reorderSectionKey = VerificationFormQuestion::query()
        ->where('template_version_id', $draft->id)
        ->selectRaw('section_key, count(*) as total')
        ->groupBy('section_key')
        ->havingRaw('count(*) >= 2')
        ->orderBy('section_key')
        ->value('section_key');

    expect($reorderSectionKey)->not->toBeNull();

    $questions = VerificationFormQuestion::query()
        ->where('template_version_id', $draft->id)
        ->where('section_key', $reorderSectionKey)
        ->orderBy('sort_order')
        ->orderBy('id')
        ->take(2)
        ->get();

    expect($questions)->toHaveCount(2);

    Livewire::test(ListVerificationQuestions::class)
        ->call('selectBuilderSection', $reorderSectionKey)
        ->call('beginTemplateChange', 'reorder')
        ->assertSet('showDraft', true)
        ->assertSet('builderView', 'reorder')
        ->call('repositionQuestion', $questions[1]->id, 'up')
        ->assertHasNoErrors();

    $reorderedIds = VerificationFormQuestion::query()
        ->where('template_version_id', $draft->id)
        ->where('section_key', $reorderSectionKey)
        ->orderBy('sort_order')
        ->orderBy('id')
        ->take(2)
        ->pluck('id')
        ->all();

    expect($reorderedIds)->toBe([$questions[1]->id, $questions[0]->id]);
});

it('deletes only unused clinic drafts and protects the active template', function () {
    $this->actingAs($this->clinicUser);

    Livewire::test(VerificationSettings::class)
        ->call('createClinicTemplateDraft')
        ->assertSet('showCreateTemplateDraftModal', true)
        ->set('newClinicTemplateDraftData.template_name', 'Archive Test Clinic Template Draft')
        ->set('newClinicTemplateDraftData.form_type', VerificationTemplateVersion::FORM_TYPE_BOTH)
        ->set('newClinicTemplateDraftData.starting_point', 'active')
        ->call('submitCreateClinicTemplateDraft')
        ->assertHasNoErrors();

    $draft = VerificationTemplateVersion::query()
        ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
        ->where('clinic_id', $this->clinic->id)
        ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
        ->firstOrFail();

    Livewire::test(VerificationSettings::class)
        ->call('deleteUnusedClinicTemplateDraft', $draft->id)
        ->assertHasNoErrors();

    expect(VerificationTemplateVersion::withTrashed()->find($draft->id))->toBeNull();

    $active = VerificationTemplateVersion::query()
        ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
        ->where('clinic_id', $this->clinic->id)
        ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
        ->where('is_active', true)
        ->firstOrFail();

    Livewire::test(VerificationSettings::class)
        ->call('archiveClinicTemplateVersion', $active->id)
        ->assertHasNoErrors();

    expect($active->fresh()->status)->toBe(VerificationTemplateVersion::STATUS_PUBLISHED);
});

it('lets clinics select the active published template from settings', function () {
    $this->actingAs($this->clinicUser);

    $component = Livewire::test(VerificationSettings::class)
        ->call('createClinicTemplateDraft')
        ->set('newClinicTemplateDraftData.template_name', 'Selectable Clinic Template Draft')
        ->set('newClinicTemplateDraftData.form_type', VerificationTemplateVersion::FORM_TYPE_BOTH)
        ->set('newClinicTemplateDraftData.starting_point', 'active')
        ->call('submitCreateClinicTemplateDraft')
        ->assertHasNoErrors();

    $draft = VerificationTemplateVersion::query()
        ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
        ->where('clinic_id', $this->clinic->id)
        ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
        ->latest('id')
        ->firstOrFail();

    $component
        ->call('publishClinicTemplateDraft', $draft->id)
        ->assertHasNoErrors();

    $previousTemplate = VerificationTemplateVersion::query()
        ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
        ->where('clinic_id', $this->clinic->id)
        ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
        ->where('is_active', false)
        ->firstOrFail();

    $component = Livewire::test(VerificationSettings::class);

    expect($component->instance()->getClinicTemplateOptions())
        ->toHaveKey($previousTemplate->getKey());

    $component
        ->set('data.verification_template_version_id', $previousTemplate->getKey())
        ->call('save')
        ->assertHasNoErrors();

    expect($previousTemplate->fresh()->is_active)->toBeTrue();
    expect(VerificationTemplateVersion::query()
        ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
        ->where('clinic_id', $this->clinic->id)
        ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
        ->where('is_active', true)
        ->count())->toBe(1);
});

it('maps only visible selected clinic portal credentials into verification settings', function () {
    $this->actingAs($this->clinicUser);

    $visibleCredential = PortalCredential::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'portal_name' => 'Delta Dental Portal',
        'portal_category' => 'insurance',
        'login_url' => 'https://example.test/delta',
        'username' => 'delta-user',
        'password' => 'delta-password',
        'mfa_required' => true,
        'mfa_method' => 'email',
        'is_active' => true,
        'visible_to_clinic' => true,
    ]);

    PortalCredential::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'portal_name' => 'Hidden Admin Portal',
        'portal_category' => 'insurance',
        'username' => 'hidden-user',
        'password' => 'hidden-password',
        'is_active' => true,
        'visible_to_clinic' => false,
    ]);

    $credentials = PortalCredentialResource::getEloquentQuery()->get();

    expect($credentials)->toHaveCount(1);
    expect($credentials->first()->is($visibleCredential))->toBeTrue();
});

it('keeps portal secrets out of the initial clinic credential page and audits explicit access', function () {
    $this->clinic->update(['verification_services_enabled' => true]);

    ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->verificationService->id,
        'created_by' => $this->clinicUser->id,
        'status' => 'active',
        'start_date' => today(),
    ]);

    $this->clinicUser->givePermissionTo(
        Permission::findOrCreate('clinic.portal_credentials.view', 'web'),
        Permission::findOrCreate('clinic.portal_credentials.update', 'web'),
    );

    $credential = PortalCredential::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'portal_name' => 'Secure Payer Portal',
        'portal_category' => 'insurance',
        'login_url' => 'https://example.test/secure',
        'username' => 'private-portal-user',
        'password' => 'private-portal-password',
        'is_active' => true,
        'visible_to_clinic' => true,
    ]);

    $securityQuestion = $credential->securityQuestions()->create([
        'question' => 'What was the first office street?',
        'answer' => 'Protected Oak Answer',
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $rawSecurityQuestion = DB::table('portal_credential_security_questions')
        ->where('id', $securityQuestion->id)
        ->first();

    expect($rawSecurityQuestion->question)->not->toContain('first office street')
        ->and($rawSecurityQuestion->answer)->not->toContain('Protected Oak Answer')
        ->and(PortalCredentialSecurityQuestion::find($securityQuestion->id)?->answer)->toBe('Protected Oak Answer');

    $this->actingAs($this->clinicUser);

    $component = Livewire::test(ListPortalCredentials::class)
        ->assertDontSee('private-portal-user')
        ->assertDontSee('private-portal-password')
        ->assertDontSee('Protected Oak Answer')
        ->call('openSecurityQuestions', $credential->id)
        ->assertSet('securityQuestionsModalOpen', true)
        ->assertSee('What was the first office street?')
        ->assertDontSee('Protected Oak Answer')
        ->call('revealSecurityQuestionAnswer', $credential->id, $securityQuestion->id)
        ->call('copySecurityQuestionAnswer', $credential->id, $securityQuestion->id)
        ->call('copyCredentialSecret', $credential->id, 'username')
        ->call('revealCredentialSecret', $credential->id, 'password')
        ->call('openPasswordEditor', $credential->id)
        ->assertSet('passwordModalOpen', true)
        ->call('closePasswordEditor')
        ->assertSet('passwordModalOpen', false)
        ->call('closeSecurityQuestions')
        ->assertSet('securityQuestionsModalOpen', false);

    expect($component->instance()->getPortalCredentials())->toHaveCount(1);
    expect(AuditLog::query()
        ->where('module', 'portal_credentials')
        ->where('action', 'password_revealed')
        ->where('clinic_id', $this->clinic->id)
        ->exists())->toBeTrue();

    $auditPayload = AuditLog::query()
        ->where('module', 'portal_credentials')
        ->where('action', 'password_revealed')
        ->latest('id')
        ->value('new_values');

    expect($auditPayload)
        ->not->toContain('private-portal-password')
        ->toContain('Secure Payer Portal');

    $securityAuditPayload = AuditLog::query()
        ->where('module', 'portal_credentials')
        ->where('action', 'security_answer_revealed')
        ->latest('id')
        ->value('new_values');

    expect($securityAuditPayload)
        ->not->toContain('Protected Oak Answer')
        ->not->toContain('first office street')
        ->toContain('Secure Payer Portal');

    Livewire::test(PortalCredentialSettings::class)
        ->assertRedirect(PortalCredentialResource::getUrl('index'));
});
