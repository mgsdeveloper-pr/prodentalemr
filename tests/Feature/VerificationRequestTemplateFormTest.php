<?php

use App\Actions\Verification\CreateVerificationRequestAction;
use App\Actions\Verification\SaveVerificationAnswerAction;
use App\Filament\Saas\Resources\Verifications\Tables\VerificationRequestsTable;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateVersion;
use App\Support\VerificationTemplateVersionService;
use App\Services\Verification\VerificationAuditService;
use Database\Seeders\RoleSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::create([
        'name' => 'Snapshot Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@snapshot.test',
        'phone' => '5551000100',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Snapshot Clinic',
        'clinic_code' => 'CLN-SNAP',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $this->user = User::factory()->create([
        'name' => 'Verification Snapshot User',
        'email' => 'verification-snapshot@example.com',
        'status' => true,
    ]);
    $this->user->assignRole('verification_manager');

    $this->service = ManagedBillingService::create([
        'name' => 'Eligibility & Benefits Verification',
        'slug' => 'eligibility-benefits-snapshot',
        'category' => 'verification',
        'service_level_agreement_hours' => 24,
        'default_priority' => 'normal',
        'requires_appointment' => false,
        'requires_patient' => false,
        'requires_policy' => false,
        'requires_claim' => false,
        'status' => true,
    ]);

    $this->enrollment = ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'created_by' => $this->user->id,
        'status' => 'active',
        'start_date' => today(),
    ]);

    $this->actingAs($this->user);
});

it('attaches the active clinic template snapshot when a verification request is created', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Snapshot protected request',
        'status' => BillingWorkItem::STATUS_PENDING,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    expect($request->verification_template_version_id)->toBe($version->id)
        ->and($request->verification_template_snapshot)->toBeArray()
        ->and(data_get($request->verification_template_snapshot, 'version.id'))->toBe($version->id)
        ->and($request->verification_template_snapshot_at)->not->toBeNull();
});

it('builds form links for active requests and read-only links for completed requests', function () {
    app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Consistent request destination',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $queueUrl = VerificationRequestResource::getUrl('index', ['queue_preset' => 'in_progress']);

    expect(VerificationRequestsTable::requestUrl($request, $queueUrl))
        ->toBe(VerificationRequestResource::getUrl('edit', [
            'record' => $request,
            'return' => $queueUrl,
        ]));

    $request->forceFill(['status' => BillingWorkItem::STATUS_DONE])->save();

    expect(VerificationRequestsTable::requestUrl($request->fresh(), $queueUrl))
        ->toBe(VerificationRequestResource::getUrl('view', [
            'record' => $request,
            'return' => $queueUrl,
        ]))
        ->and(VerificationRequestResource::canEdit($request->fresh()))->toBeFalse()
        ->and($request->fresh()->verificationUserCanEditVerification($this->user))->toBeFalse();
});

it('keeps an existing verification request on its original template after a newer template is published', function () {
    $versions = app(VerificationTemplateVersionService::class);
    $original = $versions->ensureClinicPublishedVersion($this->clinic);

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Historical template request',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $latest = $versions->publishDraft($versions->createDraftFromPublished($original));

    expect($latest->id)->not->toBe($original->id)
        ->and($request->fresh()->verification_template_version_id)->toBe($original->id)
        ->and(data_get($request->fresh()->verification_template_snapshot, 'version.id'))->toBe($original->id);
});

it('evaluates audit questions from the request template snapshot instead of the latest template', function () {
    $versions = app(VerificationTemplateVersionService::class);
    $original = $versions->ensureClinicPublishedVersion($this->clinic);

    $originalQuestion = VerificationFormQuestion::create([
        'template_version_id' => $original->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'prompt' => 'Original audit question',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 10,
        'is_required_for_audit' => true,
        'is_active' => true,
    ]);

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Snapshot-aware audit request',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $latest = $versions->publishDraft($versions->createDraftFromPublished($original));

    $latestQuestion = VerificationFormQuestion::create([
        'template_version_id' => $latest->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'prompt' => 'New audit question',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 20,
        'is_required_for_audit' => true,
        'is_active' => true,
    ]);

    $questions = app(VerificationAuditService::class)->applicableQuestions(
        $request->fresh(),
        VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'full_form',
        frequencyRows: false,
    );

    expect($questions->modelKeys())
        ->toContain($originalQuestion->id)
        ->not->toContain($latestQuestion->id);
});

it('limits the audit checklist to active required questions applicable to the selected form type', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    $attributes = [
        'template_version_id' => $version->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'input_type' => 'text',
        'sort_order' => 10,
    ];

    $required = VerificationFormQuestion::create($attributes + [
        'prompt' => 'Required full-form question',
        'form_type' => 'full_form',
        'is_required_for_audit' => true,
        'is_active' => true,
    ]);

    $optional = VerificationFormQuestion::create($attributes + [
        'prompt' => 'Optional question',
        'form_type' => 'both',
        'is_required_for_audit' => false,
        'is_active' => true,
    ]);

    $shortOnly = VerificationFormQuestion::create($attributes + [
        'prompt' => 'Short-form question',
        'form_type' => 'short_form',
        'is_required_for_audit' => true,
        'is_active' => true,
    ]);

    $inactive = VerificationFormQuestion::create($attributes + [
        'prompt' => 'Inactive question',
        'form_type' => 'both',
        'is_required_for_audit' => true,
        'is_active' => false,
    ]);

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Applicable audit checklist request',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $questions = app(VerificationAuditService::class)->requiredApplicableQuestions(
        $request,
        VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'full_form',
        frequencyRows: false,
    );

    expect($questions->modelKeys())
        ->toContain($required->id)
        ->not->toContain($optional->id, $shortOnly->id, $inactive->id);
});

it('persists answers only for questions attached to the request template version', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    $question = VerificationFormQuestion::create([
        'template_version_id' => $version->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'prompt' => 'Reference number',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 10,
        'is_active' => true,
    ]);

    $otherVersion = VerificationTemplateVersion::create([
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'scope' => VerificationTemplateVersion::SCOPE_CLINIC,
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'version_number' => 99,
        'name' => 'Other Clinic Template',
        'form_type' => VerificationTemplateVersion::FORM_TYPE_BOTH,
        'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN,
        'status' => VerificationTemplateVersion::STATUS_DRAFT,
        'is_active' => false,
    ]);

    $otherQuestion = VerificationFormQuestion::create([
        'template_version_id' => $otherVersion->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'prompt' => 'Wrong template question',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 20,
        'is_active' => true,
    ]);

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Answer persistence request',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $answer = app(SaveVerificationAnswerAction::class)->execute($request, $question->id, 'REF-100');

    expect($answer)->not->toBeNull()
        ->and($answer->answer_value)->toBe('REF-100');

    app(SaveVerificationAnswerAction::class)->execute($request, $otherQuestion->id, 'BAD');
})->throws(ValidationException::class);

it('validates select answers against the attached request template options', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    $question = VerificationFormQuestion::create([
        'template_version_id' => $version->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'prompt' => 'Mode of verification',
        'form_type' => 'both',
        'input_type' => 'select',
        'select_options' => "Phone\nPortal",
        'sort_order' => 10,
        'is_active' => true,
    ]);

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Select validation request',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    app(SaveVerificationAnswerAction::class)->execute($request, $question->id, 'Email');
})->throws(ValidationException::class);
