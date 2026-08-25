<?php

use App\Actions\Verification\CreateVerificationRequestAction;
use App\Actions\Verification\RefreshVerificationTemplateAction;
use App\Actions\Verification\SaveVerificationAnswerAction;
use App\Filament\Admin\Pages\VerificationRequestResponse as AdminVerificationRequestResponse;
use App\Filament\Clinic\Pages\VerificationRequestResponse as ClinicVerificationRequestResponse;
use App\Filament\Clinic\Resources\VerificationRequests\Tables\VerificationRequestsTable;
use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\ListVerificationFormQuestions;
use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemAttachment;
use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationNotification;
use App\Models\VerificationProfile;
use App\Models\VerificationTemplateSection;
use App\Models\VerificationTemplateVersion;
use App\Services\Verification\DeliveryService;
use App\Services\Verification\PdfPresetService;
use App\Services\Verification\SLAService;
use App\Services\Verification\StatusService;
use App\Services\Verification\VerificationResultService;
use App\Services\Verification\WorkflowService;
use App\Support\SaasSupportAccess;
use App\Support\VerificationResultPdf;
use App\Support\VerificationTemplateThreeDefaults;
use App\Support\VerificationTemplateVersionService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    expect(fn () => $router->getRoutes()->match(Request::create('/saas/providers', 'GET')))
        ->toThrow(NotFoundHttpException::class);
    expect(fn () => $router->getRoutes()->match(Request::create('/saas/billing-work-items', 'GET')))
        ->toThrow(NotFoundHttpException::class);
    expect(fn () => $router->getRoutes()->match(Request::create('/verification/billing-work-items', 'GET')))
        ->toThrow(NotFoundHttpException::class);
    expect($router->getRoutes()->match(Request::create('/verification/verifications', 'GET'))->uri())
        ->toBe('verification/verifications');
});

it('keeps template management routes scoped to their registered panels', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());
    $uris = $routes->map(fn ($route): string => $route->uri())->all();
    $names = $routes->map(fn ($route): ?string => $route->getName())->filter()->values()->all();

    expect($uris)
        ->toContain('saas/master-template')
        ->toContain('clinic/verification-settings')
        ->toContain('clinic/verification-question-arrangement')
        ->toContain('verification/verification-settings')
        ->not->toContain('verification/master-template')
        ->not->toContain('verification/verification-question-arrangement')
        ->not->toContain('saas/verification-settings')
        ->not->toContain('saas/verification-question-arrangement');

    expect($names)
        ->toContain('filament.saas.resources.master-template.index')
        ->toContain('saas.master-template.legacy.index')
        ->not->toContain('admin.master-template.legacy.index')
        ->not->toContain('filament.saas.pages.verification-settings')
        ->not->toContain('filament.saas.pages.verification-question-arrangement');

    $this->actingAs($this->saasUser)
        ->get('/saas/verification-form-questions')
        ->assertRedirect('/saas/master-template');

    $this->actingAs($this->saasUser)
        ->get('/verification/verification-form-questions')
        ->assertNotFound();
});

it('logs creation and status changes for verification requests', function () {
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

it('maps professional workflow statuses onto the current stable request statuses', function () {
    $statuses = app(StatusService::class);

    expect($statuses->normalize('draft'))->toBe(BillingWorkItem::STATUS_PENDING);
    expect($statuses->normalize('submitted'))->toBe(BillingWorkItem::STATUS_PENDING);
    expect($statuses->normalize('accepted'))->toBe(BillingWorkItem::STATUS_IN_PROGRESS);
    expect($statuses->normalize('waiting_on_clinic'))->toBe(BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);
    expect($statuses->normalize('waiting_on_insurance'))->toBe(BillingWorkItem::STATUS_IN_PROGRESS);
    expect($statuses->normalize('ready_for_qa'))->toBe(BillingWorkItem::STATUS_REVIEW);
    expect($statuses->normalize('qa_review'))->toBe(BillingWorkItem::STATUS_REVIEW);
    expect($statuses->normalize('delivered'))->toBe(BillingWorkItem::STATUS_DONE);
    expect($statuses->normalize('closed'))->toBe(BillingWorkItem::STATUS_DONE);
});

it('calculates verification sla windows and request states through the sla service', function () {
    $sla = app(SLAService::class);

    expect((int) round(now()->diffInHours($sla->calculateDefaultDueAt('urgent'))))->toBe(24);
    expect((int) round(now()->diffInHours($sla->calculateDefaultDueAt('high'))))->toBe(48);
    expect((int) round(now()->diffInDays($sla->calculateDefaultDueAt('normal'))))->toBe(3);

    $request = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'appointment_id' => $this->appointment->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'title' => 'SLA foundation smoke test',
        'status' => BillingWorkItem::STATUS_PENDING,
        'priority' => 'high',
        'source' => 'appointment_sync',
        'due_at' => now()->addDay(),
    ]);

    expect($sla->status($request))->toBe('on_track');
    expect($sla->snapshot($request))
        ->label->toBe('On Track')
        ->priority->toBe('High')
        ->is_paused->toBeFalse();

    $request->forceFill(['due_at' => now()->subHour()])->save();
    expect($sla->status($request->fresh()))->toBe('overdue');

    $request->forceFill([
        'status' => BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
        'sla_pause_started_at' => now()->subMinutes(75),
    ])->save();
    expect($sla->snapshot($request->fresh()))
        ->status->toBe('paused_waiting_clinic')
        ->is_paused->toBeTrue()
        ->pause_reason->toBe('Waiting on clinic response')
        ->paused_for->toBe('1h 15m');

    $request->forceFill([
        'status' => BillingWorkItem::STATUS_DONE,
        'completed_at' => now(),
        'sla_pause_started_at' => null,
    ])->save();
    expect($sla->snapshot($request->fresh()))
        ->status->toBe('closed')
        ->relative->toBe('Completed');
});

it('coordinates the verification workflow foundation through services', function () {
    $this->actingAs($this->saasUser);
    $this->saasUser->assignRole('verification_manager');

    $specialist = User::factory()->create([
        'name' => 'Verification Specialist',
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'status' => true,
    ]);
    $specialist->assignRole('verification_user');

    $request = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'appointment_id' => $this->appointment->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'title' => 'Workflow foundation smoke test',
        'status' => BillingWorkItem::STATUS_PENDING,
        'priority' => 'normal',
        'source' => 'appointment_sync',
    ]);

    $workflow = app(WorkflowService::class);
    $statuses = app(StatusService::class);

    expect($workflow->lifecycleSnapshot($request))
        ->toHaveCount(count(StatusService::LIFECYCLE_STAGES))
        ->and(collect($workflow->lifecycleSnapshot($request))->firstWhere('active', true)['key'])
        ->toBe('queue');
    expect($statuses->canShowTakeOwnership($request, $this->saasUser))->toBeTrue();

    $request = $workflow->assign($request, $specialist, $this->saasUser);
    expect($request->assigned_to)->toBe($specialist->id);
    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_PENDING);
    expect(collect($workflow->lifecycleSnapshot($request))->firstWhere('active', true)['key'])->toBe('assign');
    expect($statuses->canShowReassign($request, $this->saasUser))->toBeTrue();

    $request = $workflow->accept($request, $specialist);
    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_IN_PROGRESS);
    expect($request->started_at)->not->toBeNull();
    expect(collect($workflow->lifecycleSnapshot($request))->firstWhere('active', true)['key'])->toBe('work');

    $workflow->saveDraft($request, ['field_count' => 3], $specialist);
    expect($request->activities()->where('activity_type', 'verification_draft_saved')->exists())->toBeTrue();

    $request = $workflow->submitForQa($request, $specialist);
    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_REVIEW);
    expect(collect($workflow->lifecycleSnapshot($request))->firstWhere('active', true)['key'])->toBe('qa');
    expect($statuses->canShowReturnForRework($request, $this->saasUser))->toBeTrue();
    expect($request->activities()->where('activity_type', 'verification_submitted_for_qa')->exists())->toBeTrue();

    $request = $workflow->rejectQa($request, 'Please verify coverage notes.', $this->saasUser);
    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_RETURNED_FOR_REWORK);
    expect($request->activities()->where('activity_type', 'verification_qa_rejected')->exists())->toBeTrue();
    expect(fn () => $workflow->complete($request, $this->saasUser))
        ->toThrow(AuthorizationException::class);

    $request = $workflow->start($request, $specialist);
    $request = $workflow->submitForQa($request, $specialist);
    $request = $workflow->complete($request, $this->saasUser);
    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_DONE);
    expect($request->completed_at)->not->toBeNull();
    expect(collect($workflow->lifecycleSnapshot($request))->firstWhere('active', true)['key'])->toBe('complete');
    expect($statuses->canShowReturnForRework($request, $this->saasUser))->toBeFalse();
    expect($statuses->canShowReopen($request, $this->saasUser))->toBeTrue();
    expect($request->activities()->where('activity_type', 'verification_qa_approved')->exists())->toBeTrue();

    $request = $workflow->deliver($request, 'portal_download', $this->saasUser);
    expect($request->activities()->where('activity_type', 'verification_delivered')->exists())->toBeTrue();
    expect(app(DeliveryService::class)->deliverySnapshot($request))
        ->is_delivered->toBeTrue()
        ->channel->toBe('Portal Download')
        ->delivered_by->toBe($this->saasUser->name);

    $request = $workflow->resendDelivery($request, 'email', $this->saasUser);
    expect($request->activities()->where('activity_type', 'verification_delivery_resent')->exists())->toBeTrue();
    expect(app(DeliveryService::class)->deliverySnapshot($request))
        ->last_event->toBe('Verification Delivery Resent')
        ->channel->toBe('Email');

    expect(fn () => $workflow->reopen($request, '  ', $this->saasUser))
        ->toThrow(ValidationException::class);

    $request = $workflow->reopen($request, 'Payer supplied corrected benefit information.', $this->saasUser);
    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_IN_PROGRESS);
    expect($request->activities()
        ->where('activity_type', 'verification_reopened')
        ->where('meta->reason', 'Payer supplied corrected benefit information.')
        ->exists())->toBeTrue();
});

it('protects the complete cross panel verification lifecycle and historical output', function () {
    $manager = $this->saasUser;
    $manager->assignRole('verification_manager');
    $manager->verificationClinics()->syncWithoutDetaching([$this->clinic->id]);

    $specialist = User::factory()->create([
        'name' => 'Cross Panel Specialist',
        'email' => 'cross-panel-specialist@example.com',
        'status' => true,
    ]);
    $specialist->assignRole('verification_user');

    $clinicUser = User::factory()->create([
        'name' => 'Cross Panel Clinic User',
        'email' => 'cross-panel-clinic@example.com',
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'status' => true,
    ]);
    $clinicUser->assignRole('clinic_admin');

    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);
    VerificationFormQuestion::query()
        ->where('template_version_id', $version->id)
        ->update(['is_required_for_audit' => false]);
    $requiredQuestion = VerificationFormQuestion::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'template_version_id' => $version->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'prompt' => 'Cross-panel confirmation number',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 999,
        'is_required_for_audit' => true,
        'is_active' => true,
    ]);

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'appointment_id' => $this->appointment->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'assigned_to' => $specialist->id,
        'title' => 'Cross-panel lifecycle verification',
        'status' => BillingWorkItem::STATUS_PENDING,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'clinic_request',
    ]);

    $workflow = app(WorkflowService::class);
    $answers = app(SaveVerificationAnswerAction::class);

    $this->actingAs($specialist);
    $request = $workflow->start($request, $specialist);

    expect(fn () => $workflow->submitForQa($request, $specialist))
        ->toThrow(ValidationException::class);
    expect($request->fresh()->normalized_status)->toBe(BillingWorkItem::STATUS_IN_PROGRESS);

    $request->info_request_reason = 'Please confirm the payer reference with the clinic.';
    $request->save();
    $dueBeforePause = $request->due_at->copy();
    $request = $workflow->transition($request, BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE, $specialist);
    expect($request->sla_pause_started_at)->not->toBeNull();

    $this->travel(2)->hours();
    $this->actingAs($clinicUser);
    $request->notes = 'Payer reference confirmed with the front office.';
    $request = $workflow->transition($request, BillingWorkItem::STATUS_IN_PROGRESS, $clinicUser);
    expect($request->sla_pause_started_at)->toBeNull()
        ->and($request->sla_paused_seconds)->toBeGreaterThanOrEqual(7199)
        ->and($request->due_at->greaterThan($dueBeforePause))->toBeTrue();

    $this->actingAs($specialist);
    $answers->execute($request, $requiredQuestion->id, 'CONF-100', actor: $specialist);
    $request = $workflow->submitForQa($request, $specialist);
    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_REVIEW);

    $this->actingAs($manager);
    $request = $workflow->rejectQa($request, 'Reconfirm the payer reference.', $manager);
    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_RETURNED_FOR_REWORK);

    $this->actingAs($specialist);
    $answers->execute($request, $requiredQuestion->id, 'CONF-101', actor: $specialist);
    $request = $workflow->submitForQa($request, $specialist);

    $this->actingAs($manager);
    $request->formSubmissions()->create([
        'user_id' => $specialist->id,
        'panel' => 'verification',
        'status' => BillingWorkItem::STATUS_REVIEW,
        'outcome_status' => $request->outcome_status,
        'priority' => $request->priority,
        'version' => 1,
        'payload' => [
            'work_item' => ['status' => BillingWorkItem::STATUS_REVIEW],
            'verification_profile' => [
                'effective_date' => '2026-01-01',
                'network_status' => 'in_network',
                'annual_maximum' => '1500.00',
                'annual_maximum_remaining' => '900.00',
                'individual_deductible' => '50.00',
                'individual_deductible_remaining' => '25.00',
                'coverage_preventive' => 100,
                'coverage_basic_restorative' => 80,
                'coverage_major_restorative' => 50,
                'ortho_benefit' => 40,
            ],
            'answers' => [[
                'question_id' => $requiredQuestion->id,
                'code' => $requiredQuestion->code,
                'prompt' => $requiredQuestion->prompt,
                'answer_value' => 'CONF-101',
                'note_value' => null,
            ]],
        ],
    ]);
    $request = $workflow->approveQa($request, $manager);
    $lockedVersionId = $request->verification_template_version_id;

    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_DONE)
        ->and($request->outcome_status)->toBe('verified')
        ->and($request->completed_at)->not->toBeNull()
        ->and($request->formSubmissions()->latest('version')->value('status'))->toBe(BillingWorkItem::STATUS_DONE)
        ->and(data_get($request->formSubmissions()->latest('version')->first()?->payload, 'answers.0.answer_value'))->toBe('CONF-101');

    $request->verificationProfile()->update([
        'effective_date' => '2027-02-02',
        'annual_maximum' => '9999.00',
        'coverage_preventive' => 20,
    ]);
    $recordedResult = app(VerificationResultService::class)->summary($request->fresh());

    expect($recordedResult['eligibility_status'])->toBe('Verified')
        ->and($recordedResult['effective_date'])->toBe('Jan 01, 2026')
        ->and($recordedResult['annual_maximum'])->toBe('$1,500.00')
        ->and($recordedResult['coverage_preventive'])->toBe('100%');
    expect(fn () => $answers->execute($request, $requiredQuestion->id, 'CHANGED', actor: $manager))
        ->toThrow(AuthorizationException::class);

    app(VerificationTemplateVersionService::class)->publishDraft(
        app(VerificationTemplateVersionService::class)->createDraftFromPublished($version)
    );

    expect($request->fresh()->verification_template_version_id)->toBe($lockedVersionId)
        ->and($request->verificationFormAnswers()->where('verification_form_question_id', $requiredQuestion->id)->value('answer_value'))
        ->toBe('CONF-101');

    foreach (['standard', 'custom_portrait', 'custom_landscape'] as $mode) {
        $pdf = VerificationResultPdf::output($request->fresh(), $mode);
        expect($pdf)->toStartWith('%PDF-');
    }

    $originalCompletion = $request->formSubmissions()
        ->where('status', BillingWorkItem::STATUS_DONE)
        ->latest('version')
        ->firstOrFail();

    $this->actingAs($clinicUser);
    $request = $workflow->requestCorrection(
        $request,
        'Please correct the confirmation number.',
        $clinicUser,
        ['answers.'.$requiredQuestion->code => $requiredQuestion->prompt],
    );

    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_RETURNED_FOR_REWORK)
        ->and($request->activities()
            ->where('activity_type', 'clinic_correction_requested')
            ->where('meta->baseline_submission_id', $originalCompletion->id)
            ->exists())->toBeTrue();

    $this->actingAs($specialist);
    $answers->execute($request, $requiredQuestion->id, 'CONF-102', actor: $specialist);
    $request->formSubmissions()->create([
        'user_id' => $specialist->id,
        'panel' => 'verification',
        'status' => BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
        'outcome_status' => $request->outcome_status,
        'priority' => $request->priority,
        'version' => ((int) $request->formSubmissions()->max('version')) + 1,
        'payload' => array_replace_recursive($originalCompletion->payload, [
            'answers' => [[
                'question_id' => $requiredQuestion->id,
                'code' => $requiredQuestion->code,
                'prompt' => $requiredQuestion->prompt,
                'answer_value' => 'CONF-102',
                'note_value' => null,
            ]],
        ]),
    ]);
    $request = $workflow->submitForQa($request, $specialist);

    $this->actingAs($manager);
    $request = $workflow->approveQa($request, $manager);
    $revisedCompletion = $request->formSubmissions()
        ->where('status', BillingWorkItem::STATUS_DONE)
        ->latest('version')
        ->firstOrFail();

    expect($request->normalized_status)->toBe(BillingWorkItem::STATUS_DONE)
        ->and($request->formSubmissions()->where('status', BillingWorkItem::STATUS_DONE)->count())->toBe(2)
        ->and($request->activities()
            ->where('activity_type', 'verification_qa_approved')
            ->where('meta->submission_id', $revisedCompletion->id)
            ->exists())->toBeTrue()
        ->and(data_get($originalCompletion->fresh()->payload, 'answers.0.answer_value'))->toBe('CONF-101')
        ->and(data_get($revisedCompletion->payload, 'answers.0.answer_value'))->toBe('CONF-102')
        ->and(app(VerificationResultService::class)->recordedData($request, $originalCompletion)['answers'][0]['answer_value'])->toBe('CONF-101')
        ->and(app(VerificationResultService::class)->recordedData($request, $revisedCompletion)['answers'][0]['answer_value'])->toBe('CONF-102')
        ->and(VerificationResultPdf::output($request, 'standard', submission: $originalCompletion))->toStartWith('%PDF-')
        ->and(VerificationResultPdf::output($request, 'standard', submission: $revisedCompletion))->toStartWith('%PDF-');

    $this->actingAs($manager)
        ->get(route('admin.verifications.pdf.preview', [
            'billingWorkItem' => $request,
            'mode' => 'standard',
            'submission_id' => $originalCompletion->id,
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($manager)
        ->get(route('admin.verifications.pdf.download', [
            'billingWorkItem' => $request,
            'mode' => 'custom_landscape',
            'submission_id' => $originalCompletion->id,
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload();

    $this->actingAs($clinicUser)
        ->get(route('clinic.verification-requests.pdf.preview', [
            'billingWorkItem' => $request,
            'mode' => 'standard',
            'submission_id' => $originalCompletion->id,
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($clinicUser)
        ->get(route('clinic.verification-requests.pdf.download', [
            'billingWorkItem' => $request,
            'mode' => 'custom_portrait',
            'submission_id' => $originalCompletion->id,
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload();

    expect($request->activities()->where('activity_type', 'info_requested_from_clinic')->exists())->toBeTrue()
        ->and($request->activities()->where('activity_type', 'clinic_response_received')->exists())->toBeTrue()
        ->and($request->activities()->where('activity_type', 'verification_submitted_for_qa')->exists())->toBeTrue()
        ->and($request->activities()->where('activity_type', 'verification_qa_rejected')->exists())->toBeTrue()
        ->and($request->activities()->where('activity_type', 'verification_qa_approved')->exists())->toBeTrue();
});

it('allows authorized saas users to download a verification request attachment', function () {
    $this->actingAs($this->saasUser);
    SaasSupportAccess::start(
        $this->saasUser,
        $this->organization,
        $this->clinic,
        'Download verification proof for support validation.'
    );

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

    $response = $this->get(route('saas.verification-request-attachments.download', $attachment));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('verification-proof.pdf');
});

it('persists structured verification profile details on a verification request', function () {
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

it('allows verification managers to request clinic information before starting work', function () {
    $verificationManager = User::factory()->create([
        'name' => 'Verification Manager',
        'email' => 'verification-manager@example.com',
        'status' => true,
    ]);
    $verificationManager->assignRole('verification_manager');

    $this->actingAs($verificationManager);

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
        'assigned_to' => $verificationManager->id,
        'title' => 'Needs clinic demographics before verification',
        'status' => BillingWorkItem::STATUS_PENDING,
        'outcome_status' => 'pending',
        'priority' => 'high',
        'source' => 'clinic_request',
    ]);

    expect($workItem->canUserTransitionTo($verificationManager, BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE))->toBeTrue();

    $workItem->info_request_reason = 'Please confirm subscriber DOB before verification can continue.';
    $workItem->transitionStatus(BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);

    expect($workItem->fresh()->normalized_status)->toBe(BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);
    expect($workItem->activities()->where('activity_type', 'info_requested_from_clinic')->exists())->toBeTrue();
});

it('allows clinic users to respond to verification information requests', function () {
    $verificationManager = User::factory()->create([
        'name' => 'Verification Manager',
        'email' => 'verification-manager-response@example.com',
        'status' => true,
    ]);
    $verificationManager->assignRole('verification_manager');
    $verificationManager->verificationClinics()->attach($this->clinic->id);

    $clinicUser = User::factory()->create([
        'name' => 'Clinic Coordinator',
        'email' => 'clinic-coordinator@example.com',
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'status' => true,
    ]);
    $clinicUser->assignRole('clinic_admin');

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
        'assigned_to' => $verificationManager->id,
        'title' => 'Needs subscriber details from clinic',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'clinic_request',
        'info_request_reason' => 'Please confirm subscriber date of birth.',
    ]);

    $this->actingAs($verificationManager);
    app(WorkflowService::class)->transition($workItem, BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);

    $workItem = $workItem->fresh();

    expect($workItem->normalized_status)->toBe(BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);
    expect($workItem->canUserTransitionTo($clinicUser, BillingWorkItem::STATUS_IN_PROGRESS))->toBeTrue();
    expect(VerificationNotification::query()
        ->where('user_id', $clinicUser->getKey())
        ->where('panel', 'clinic')
        ->where('activity_type', 'info_requested_from_clinic')
        ->where('billing_work_item_id', $workItem->getKey())
        ->exists())->toBeTrue();

    expect(VerificationRequestsTable::responseUrl($workItem))
        ->toContain('/clinic/request-response')
        ->toContain('respond='.$workItem->getKey());

    $this->actingAs($clinicUser);

    $workItem->notes = 'Subscriber DOB confirmed as 05/10/1991.';
    app(WorkflowService::class)->transition($workItem, BillingWorkItem::STATUS_IN_PROGRESS);

    $workItem = $workItem->fresh();

    expect($workItem->normalized_status)->toBe(BillingWorkItem::STATUS_IN_PROGRESS);
    expect($workItem->clinic_responded_by_user_id)->toBe($clinicUser->id);
    expect($workItem->activities()->where('activity_type', 'clinic_response_received')->exists())->toBeTrue();
    expect(VerificationNotification::query()
        ->where('user_id', $verificationManager->getKey())
        ->where('panel', 'verification')
        ->where('activity_type', 'clinic_response_received')
        ->where('billing_work_item_id', $workItem->getKey())
        ->exists())->toBeTrue();

    Storage::disk('local')->put('billing-work-items/'.$workItem->getKey().'/clinic-response/test-response.png', 'fake image content');

    $attachment = BillingWorkItemAttachment::create([
        'billing_work_item_id' => $workItem->getKey(),
        'user_id' => $clinicUser->getKey(),
        'title' => 'Clinic response attachment',
        'file_path' => 'billing-work-items/'.$workItem->getKey().'/clinic-response/test-response.png',
        'original_file_name' => 'test-response.png',
        'mime_type' => 'image/png',
        'file_size' => 18,
    ]);

    $this->get(route('clinic.verification-request-attachments.preview', $attachment))
        ->assertOk();

    $this->actingAs($verificationManager);

    $this->get(route('admin.verification-request-attachments.preview', $attachment))
        ->assertOk();
});

it('preserves every clinic response and blocks duplicate submissions', function () {
    $verificationManager = User::factory()->create([
        'name' => 'Verification Follow-up Manager',
        'email' => 'verification-follow-up-manager@example.com',
        'status' => true,
    ]);
    $verificationManager->assignRole('verification_manager');

    $clinicUser = User::factory()->create([
        'name' => 'Clinic Follow-up Coordinator',
        'email' => 'clinic-follow-up-coordinator@example.com',
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'status' => true,
    ]);
    $clinicUser->assignRole('clinic_admin');

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
        'assigned_to' => $verificationManager->id,
        'title' => 'Clinic response audit history',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'clinic_request',
        'processing_mode' => BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE,
        'info_request_reason' => 'Please confirm the subscriber date of birth.',
    ]);

    $this->actingAs($verificationManager);
    app(WorkflowService::class)->transition($workItem, BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);

    $this->actingAs($clinicUser);
    Filament::setCurrentPanel(Filament::getPanel('clinic'));
    Livewire::test(ClinicVerificationRequestResponse::class)
        ->call('openResponseComposer', $workItem->getKey())
        ->assertSet('responseComposerNote', '')
        ->set('responseComposerNote', 'Subscriber DOB is May 10, 1991.')
        ->call('sendClinicResponse')
        ->assertHasNoErrors();

    $workItem->refresh();

    expect($workItem->normalized_status)->toBe(BillingWorkItem::STATUS_IN_PROGRESS)
        ->and($workItem->activities()->where('activity_type', 'clinic_response_received')->count())->toBe(1);

    Livewire::test(ClinicVerificationRequestResponse::class)
        ->set('responseComposerWorkItemId', $workItem->getKey())
        ->set('responseComposerNote', 'Duplicate response')
        ->call('sendClinicResponse')
        ->assertForbidden();

    $this->actingAs($verificationManager);
    $workItem->info_request_reason = 'Please also confirm the group number.';
    app(WorkflowService::class)->transition($workItem, BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);

    $adminRequestResponse = app(AdminVerificationRequestResponse::class);

    expect($adminRequestResponse->requestActionLabel($workItem->fresh()))
        ->toBe('Send Follow-up')
        ->and($adminRequestResponse->canCloseRequestResponse($workItem->fresh()))
        ->toBeFalse();

    $this->actingAs($clinicUser);
    Livewire::test(ClinicVerificationRequestResponse::class)
        ->call('openResponseComposer', $workItem->getKey())
        ->assertSet('responseComposerNote', '')
        ->set('responseComposerNote', 'Group number is GRP-2201.')
        ->call('sendClinicResponse')
        ->assertHasNoErrors();

    $responses = $workItem->activities()
        ->where('activity_type', 'clinic_response_received')
        ->oldest('created_at')
        ->get();

    expect($responses)->toHaveCount(2)
        ->and(data_get($responses->get(0)?->meta, 'clinic_response_note'))->toBe('Subscriber DOB is May 10, 1991.')
        ->and(data_get($responses->get(1)?->meta, 'clinic_response_note'))->toBe('Group number is GRP-2201.');
});

it('saves clinic verification pdf preset profiles without changing non-default output', function () {
    $this->actingAs($this->saasUser);

    $service = app(PdfPresetService::class);

    $defaultPreset = $service->saveForClinic($this->clinic, [
        'name' => 'Full Verification Report',
        'description' => 'Complete report for default clinic output.',
        'output_mode' => 'standard',
        'section_keys' => [],
        'question_ids' => [],
        'show_blank_rows' => true,
        'is_default' => true,
    ]);

    $customPreset = $service->saveForClinic($this->clinic->fresh(), [
        'name' => 'Patient Summary',
        'description' => 'Only selected patient-facing details.',
        'output_mode' => 'custom_landscape',
        'section_keys' => ['core_details'],
        'question_ids' => [101, 102],
        'show_blank_rows' => false,
        'is_default' => false,
    ]);

    $this->clinic->refresh();

    expect($defaultPreset->fresh()->is_default)->toBeTrue()
        ->and($customPreset->fresh()->is_default)->toBeFalse()
        ->and($customPreset->fresh()->shouldShowBlankRows())->toBeFalse()
        ->and($this->clinic->default_verification_pdf_preset_id)->toBe($defaultPreset->id)
        ->and($this->clinic->getVerificationPdfOutputMode())->toBe('standard');
});

it('refreshes a verification request to the latest clinic template without changing workflow status', function () {
    $this->actingAs($this->saasUser);

    $service = app(VerificationTemplateVersionService::class);
    $published = $service->ensureClinicPublishedVersion($this->clinic);

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
        'title' => 'Refresh template without closing',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'high',
        'source' => 'clinic_request',
    ]);

    $workItem = $service->attachSnapshotToWorkItem($workItem);

    $draft = $service->createDraftFromPublished($published);
    $latest = $service->publishDraft($draft);

    $refreshed = $service->refreshWorkItemSnapshot($workItem);

    expect($refreshed->verification_template_version_id)->toBe($latest->id);
    expect(data_get($refreshed->verification_template_snapshot, 'version.version_number'))->toBe($latest->version_number);
    expect(data_get($refreshed->verification_template_snapshot, 'version.form_type'))->toBe($latest->form_type);
    expect(data_get($refreshed->verification_template_snapshot, 'version.clinic_visibility'))->toBe($latest->clinic_visibility);
    expect($refreshed->normalized_status)->toBe(BillingWorkItem::STATUS_IN_PROGRESS);
    expect($refreshed->completed_at)->toBeNull();
});

it('shows refresh only for editable requests using an older template version', function () {
    $verificationManager = User::factory()->create([
        'name' => 'Template Refresh Manager',
        'email' => 'template-refresh-manager@example.com',
        'status' => true,
    ]);
    $verificationManager->assignRole('verification_manager');

    $this->actingAs($verificationManager);

    $versions = app(VerificationTemplateVersionService::class);
    $refresh = app(RefreshVerificationTemplateAction::class);
    $published = $versions->ensureClinicPublishedVersion($this->clinic);

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
        'assigned_to' => $verificationManager->id,
        'title' => 'Outdated editable template',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'high',
        'source' => 'clinic_request',
    ]);

    $workItem = $versions->attachSnapshotToWorkItem($workItem);

    expect($refresh->canRefresh($workItem, $verificationManager))->toBeFalse();

    $latest = $versions->publishDraft($versions->createDraftFromPublished($published));

    expect($latest->id)->not->toBe($workItem->verification_template_version_id);
    expect($refresh->canRefresh($workItem->fresh(), $verificationManager))->toBeTrue();

    $workItem->transitionStatus(BillingWorkItem::STATUS_DONE);

    expect($refresh->canRefresh($workItem->fresh(), $verificationManager))->toBeFalse();
});

it('lets the assigned verification user continue and refresh an incomplete request', function () {
    $specialist = User::factory()->create([
        'name' => 'Assigned Template Refresh Specialist',
        'email' => 'assigned-template-refresh-specialist@example.com',
        'status' => true,
    ]);
    $specialist->assignRole('verification_user');

    $otherSpecialist = User::factory()->create([
        'name' => 'Other Template Refresh Specialist',
        'email' => 'other-template-refresh-specialist@example.com',
        'status' => true,
    ]);
    $otherSpecialist->assignRole('verification_user');

    $versions = app(VerificationTemplateVersionService::class);
    $refresh = app(RefreshVerificationTemplateAction::class);
    $published = $versions->ensureClinicPublishedVersion($this->clinic);

    $workItem = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $specialist->id,
        'title' => 'Incomplete request using an older template',
        'status' => BillingWorkItem::STATUS_INCOMPLETE,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'clinic_request',
    ]);
    $workItem = $versions->attachSnapshotToWorkItem($workItem);

    $versions->publishDraft($versions->createDraftFromPublished($published));
    $workItem->refresh();

    expect($workItem->verificationUserCanEditVerification($specialist))->toBeTrue()
        ->and($refresh->canRefresh($workItem, $specialist))->toBeTrue()
        ->and($workItem->verificationUserCanEditVerification($otherSpecialist))->toBeFalse()
        ->and($refresh->canRefresh($workItem, $otherSpecialist))->toBeFalse();

    $refreshed = $refresh->execute($workItem);

    expect($refreshed->normalized_status)->toBe(BillingWorkItem::STATUS_INCOMPLETE)
        ->and($refresh->isAlreadyCurrent($refreshed))->toBeTrue();
});

it('keeps completed verification template snapshots locked for audit history', function () {
    $verificationManager = User::factory()->create([
        'name' => 'Completed Template Refresh Manager',
        'email' => 'completed-template-refresh-manager@example.com',
        'status' => true,
    ]);
    $verificationManager->assignRole('verification_manager');

    $this->actingAs($verificationManager);

    $versions = app(VerificationTemplateVersionService::class);
    $refresh = app(RefreshVerificationTemplateAction::class);
    $published = $versions->ensureClinicPublishedVersion($this->clinic);

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
        'assigned_to' => $verificationManager->id,
        'title' => 'Completed audit snapshot',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'verified',
        'priority' => 'normal',
        'source' => 'clinic_request',
    ]);

    $workItem = $versions->attachSnapshotToWorkItem($workItem);
    $originalVersionId = $workItem->verification_template_version_id;
    $originalSnapshot = $workItem->verification_template_snapshot;

    $versions->publishDraft($versions->createDraftFromPublished($published));
    $workItem->transitionStatus(BillingWorkItem::STATUS_DONE);

    expect($refresh->canRefresh($workItem->fresh(), $verificationManager))->toBeFalse();

    try {
        $refresh->execute($workItem->fresh());
    } catch (AuthorizationException $exception) {
        $locked = $workItem->fresh();

        expect($locked->verification_template_version_id)->toBe($originalVersionId)
            ->and($locked->verification_template_snapshot)->toBe($originalSnapshot)
            ->and($locked->normalized_status)->toBe(BillingWorkItem::STATUS_DONE);

        return;
    }

    $this->fail('Completed verification request was refreshed.');
});

it('replicates the platform master template into a clinic template copy', function () {
    $this->actingAs($this->saasUser);

    $versions = app(VerificationTemplateVersionService::class);
    $master = $versions->ensureMasterVersion();

    $section = VerificationTemplateSection::create([
        'template_version_id' => $master->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'custom_smoke_section',
        'label' => 'Smoke Section',
        'sort_order' => 900,
        'is_active' => true,
    ]);

    $question = VerificationFormQuestion::create([
        'template_version_id' => $master->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => $section->section_key,
        'prompt' => 'Smoke test master question?',
        'form_type' => 'both',
        'input_type' => 'yes_no',
        'sort_order' => 910,
        'is_active' => true,
    ]);

    $clinicVersion = $versions->ensureClinicPublishedVersion($this->clinic);

    $clinicQuestion = VerificationFormQuestion::query()
        ->where('template_version_id', $clinicVersion->id)
        ->where('clinic_id', $this->clinic->id)
        ->where('source_question_id', $question->id)
        ->first();

    expect($clinicVersion->scope)->toBe(VerificationTemplateVersion::SCOPE_CLINIC)
        ->and($clinicVersion->parent_version_id)->toBe($master->id)
        ->and($clinicVersion->form_type)->toBe($master->form_type)
        ->and($clinicVersion->clinic_visibility)->toBe(VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE)
        ->and($clinicQuestion)->not->toBeNull()
        ->and($clinicQuestion->organization_id)->toBe($this->organization->id)
        ->and($clinicQuestion->clinic_id)->toBe($this->clinic->id);

    expect(VerificationFormQuestion::query()
        ->whereKey($question->id)
        ->whereNull('clinic_id')
        ->whereNull('organization_id')
        ->exists())->toBeTrue();
});

it('keeps retired fixed frequency rows out of new template versions', function () {
    $this->actingAs($this->saasUser);

    $versions = app(VerificationTemplateVersionService::class);
    $master = $versions->ensureMasterVersion();

    VerificationFormQuestion::create([
        'template_version_id' => $master->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'frequency_basic',
        'prompt' => 'Retired fixed Frequency question',
        'field_key' => 'vf_basic_scaling_root_planing',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 10,
        'is_builtin' => true,
        'is_active' => true,
    ]);

    VerificationFormQuestion::create([
        'template_version_id' => $master->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'frequency_basic',
        'prompt' => 'Intentionally added Frequency question',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 20,
        'is_builtin' => false,
        'is_active' => true,
    ]);

    $draft = $versions->createDraftFromPublished($master);

    expect(VerificationTemplateThreeDefaults::questions())
        ->not->toContain(fn (array $question): bool => $question['field_key'] === 'vf_basic_scaling_root_planing');

    expect($draft->questions()->where('field_key', 'vf_basic_scaling_root_planing')->exists())->toBeFalse()
        ->and($draft->questions()
            ->where('prompt', 'Intentionally added Frequency question')
            ->where('section_key', 'template_3_frequency_basic')
            ->exists())->toBeTrue();
});

it('blocks publication when a retired template row is reintroduced', function () {
    $this->actingAs($this->saasUser);

    $versions = app(VerificationTemplateVersionService::class);
    $draft = $versions->createDraftFromPublished($versions->ensureMasterVersion());

    VerificationFormQuestion::create([
        'template_version_id' => $draft->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'frequency_basic',
        'prompt' => 'Retired row reintroduced after normalization',
        'field_key' => 'vf_basic_scaling_root_planing',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 10,
        'is_builtin' => true,
        'is_active' => true,
    ]);

    expect(fn () => $versions->publishDraft($draft))
        ->toThrow(ValidationException::class);
});

it('publishes a template draft without deleting earlier published versions', function () {
    $this->actingAs($this->saasUser);

    $versions = app(VerificationTemplateVersionService::class);
    $published = $versions->ensureClinicPublishedVersion($this->clinic);
    $draft = $versions->createDraftFromPublished($published);

    $latest = $versions->publishDraft(
        $draft,
        'Revenue Downtown Template v2',
        'Adjusted clinic-specific template wording.'
    );

    expect($latest->status)->toBe(VerificationTemplateVersion::STATUS_PUBLISHED)
        ->and($latest->is_active)->toBeTrue()
        ->and($latest->name)->toBe('Revenue Downtown Template v2')
        ->and($latest->notes)->toBe('Adjusted clinic-specific template wording.');

    expect($published->fresh()->status)->toBe(VerificationTemplateVersion::STATUS_PUBLISHED)
        ->and($published->fresh()->is_active)->toBeFalse();

    expect(VerificationTemplateVersion::query()
        ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
        ->where('clinic_id', $this->clinic->id)
        ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
        ->count())->toBe(2);
});

it('allows multiple master template drafts while keeping one working draft', function () {
    $this->actingAs($this->saasUser);

    $versions = app(VerificationTemplateVersionService::class);
    $published = $versions->ensureMasterVersion();

    VerificationFormQuestion::create([
        'template_version_id' => $published->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_plan_provisions',
        'prompt' => 'Full-only draft smoke question?',
        'form_type' => 'full_form',
        'input_type' => 'text',
        'sort_order' => 990,
        'is_active' => true,
    ]);

    VerificationFormQuestion::create([
        'template_version_id' => $published->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_plan_provisions',
        'prompt' => 'Short-only draft smoke question?',
        'form_type' => 'short_form',
        'input_type' => 'text',
        'sort_order' => 991,
        'is_active' => true,
    ]);

    $fullDraft = $versions->createDraftFromSource($published, [
        'name' => 'Full Form Draft',
        'form_type' => VerificationTemplateVersion::FORM_TYPE_FULL,
        'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN,
        'starting_point' => 'current_master',
    ]);

    $shortDraft = $versions->createDraftFromSource($published, [
        'name' => 'Short Form Draft',
        'form_type' => VerificationTemplateVersion::FORM_TYPE_SHORT,
        'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
        'starting_point' => 'current_master',
    ]);

    expect($fullDraft->fresh()->is_working_draft)->toBeFalse()
        ->and($shortDraft->fresh()->is_working_draft)->toBeTrue()
        ->and($fullDraft->fresh()->form_type)->toBe(VerificationTemplateVersion::FORM_TYPE_FULL)
        ->and($shortDraft->fresh()->clinic_visibility)->toBe(VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE);

    expect(VerificationFormQuestion::query()
        ->where('template_version_id', $fullDraft->id)
        ->where('prompt', 'Short-only draft smoke question?')
        ->exists())->toBeFalse();

    expect(VerificationFormQuestion::query()
        ->where('template_version_id', $shortDraft->id)
        ->where('prompt', 'Full-only draft smoke question?')
        ->exists())->toBeFalse();

    $versions->markWorkingDraft($fullDraft->fresh());

    expect($fullDraft->fresh()->is_working_draft)->toBeTrue()
        ->and($shortDraft->fresh()->is_working_draft)->toBeFalse();
});

it('opens a master draft without silently changing the working draft', function () {
    $this->actingAs($this->saasUser);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $versions = app(VerificationTemplateVersionService::class);
    $published = $versions->ensureMasterVersion();
    $firstDraft = $versions->createDraftFromSource($published, ['name' => 'First Draft']);
    $secondDraft = $versions->createDraftFromSource($published, ['name' => 'Second Draft']);

    Livewire::test(ListVerificationFormQuestions::class)
        ->call('selectTemplateVersion', $firstDraft->id)
        ->assertSet('selectedTemplateVersionId', $firstDraft->id);

    expect($firstDraft->fresh()->is_working_draft)->toBeFalse()
        ->and($secondDraft->fresh()->is_working_draft)->toBeTrue();
});

it('requires an explicitly selected master draft before publishing', function () {
    $this->actingAs($this->saasUser);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $versions = app(VerificationTemplateVersionService::class);
    $draft = $versions->createDraftFromPublished($versions->ensureMasterVersion());

    Livewire::test(ListVerificationFormQuestions::class)
        ->call('publishDraftVersion');

    expect($draft->fresh()->status)->toBe(VerificationTemplateVersion::STATUS_DRAFT)
        ->and($draft->fresh()->published_at)->toBeNull();
});

it('opens the create master template draft action without a schema type error', function () {
    $this->actingAs($this->saasUser);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    Livewire::test(ListVerificationFormQuestions::class)
        ->call('mountAction', 'createDraftVersion')
        ->assertActionMounted('createDraftVersion')
        ->assertSchemaComponentExists('template_name', 'mountedActionSchema0')
        ->assertSchemaComponentExists('starting_point', 'mountedActionSchema0')
        ->assertSchemaComponentExists('source_version_id', 'mountedActionSchema0')
        ->assertHasNoErrors();
});

it('requires an explicit clinic release choice when publishing a master template', function () {
    $this->actingAs($this->saasUser);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $versions = app(VerificationTemplateVersionService::class);
    $draft = $versions->createDraftFromPublished($versions->ensureMasterVersion());

    Livewire::test(ListVerificationFormQuestions::class)
        ->call('selectTemplateVersion', $draft->id)
        ->call('publishDraftVersion', [
            'version_name' => 'Missing Release Choice',
            'change_description' => 'This should remain a draft.',
        ]);

    expect($draft->fresh()->status)->toBe(VerificationTemplateVersion::STATUS_DRAFT);
});

it('publishes and releases a master template to clinics when selected', function () {
    $this->actingAs($this->saasUser);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $versions = app(VerificationTemplateVersionService::class);
    $draft = $versions->createDraftFromPublished($versions->ensureMasterVersion());

    Livewire::test(ListVerificationFormQuestions::class)
        ->call('selectTemplateVersion', $draft->id)
        ->call('publishDraftVersion', [
            'version_name' => 'Clinic Release',
            'change_description' => 'Released for clinic use.',
            'release_mode' => 'release_to_clinics',
        ]);

    expect($draft->fresh()->status)->toBe(VerificationTemplateVersion::STATUS_PUBLISHED)
        ->and($draft->fresh()->clinic_visibility)->toBe(VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE)
        ->and($draft->fresh()->isAvailableToClinics())->toBeTrue();
});

it('publishes an internal-only master template when selected', function () {
    $this->actingAs($this->saasUser);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $versions = app(VerificationTemplateVersionService::class);
    $draft = $versions->createDraftFromPublished($versions->ensureMasterVersion());

    Livewire::test(ListVerificationFormQuestions::class)
        ->call('selectTemplateVersion', $draft->id)
        ->call('publishDraftVersion', [
            'version_name' => 'Internal Release',
            'change_description' => 'Published for SaaS review only.',
            'release_mode' => 'internal_only',
        ]);

    expect($draft->fresh()->status)->toBe(VerificationTemplateVersion::STATUS_PUBLISHED)
        ->and($draft->fresh()->clinic_visibility)->toBe(VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN)
        ->and($draft->fresh()->isAvailableToClinics())->toBeFalse();
});

it('rejects clinic template versions in the saas master template workspace', function () {
    $this->actingAs($this->saasUser);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $clinicVersion = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    Livewire::test(ListVerificationFormQuestions::class)
        ->call('selectTemplateVersion', $clinicVersion->id)
        ->assertSet('selectedTemplateVersionId', null)
        ->call('setWorkingDraft', $clinicVersion->id)
        ->assertSet('selectedTemplateVersionId', null);
});

it('targets question creation at the explicitly opened master draft', function () {
    $this->actingAs($this->saasUser);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $versions = app(VerificationTemplateVersionService::class);
    $published = $versions->ensureMasterVersion();
    $firstDraft = $versions->createDraftFromSource($published, ['name' => 'First Draft']);
    $versions->createDraftFromSource($published, ['name' => 'Second Draft']);

    Livewire::test(ListVerificationFormQuestions::class)
        ->call('selectTemplateVersion', $firstDraft->id)
        ->assertSet('selectedTemplateVersionId', $firstDraft->id)
        ->assertSee('version='.$firstDraft->id, escape: false);
});

it('allows an unused unpublished template draft to be edited and permanently deleted', function () {
    $this->actingAs($this->saasUser);

    $versions = app(VerificationTemplateVersionService::class);
    $draft = $versions->createDraftFromSource($versions->ensureMasterVersion(), [
        'name' => 'Editable Draft',
    ]);

    expect($draft->canEditDirectly())->toBeTrue()
        ->and($draft->canDeletePermanently())->toBeTrue();

    $updated = $versions->updateUnusedDraft($draft, [
        'name' => 'Updated Editable Draft',
        'form_type' => VerificationTemplateVersion::FORM_TYPE_FULL,
        'notes' => 'Ready for builder changes.',
    ]);

    expect($updated->name)->toBe('Updated Editable Draft')
        ->and($updated->form_type)->toBe(VerificationTemplateVersion::FORM_TYPE_FULL);

    $versions->deleteUnusedDraft($updated);

    expect(VerificationTemplateVersion::withTrashed()->find($draft->id))->toBeNull()
        ->and(VerificationFormQuestion::query()->where('template_version_id', $draft->id)->exists())->toBeFalse()
        ->and(VerificationTemplateSection::query()->where('template_version_id', $draft->id)->exists())->toBeFalse();
});

it('protects published and source template versions from direct edits and deletion', function () {
    $this->actingAs($this->saasUser);

    $versions = app(VerificationTemplateVersionService::class);
    $published = $versions->ensureMasterVersion();
    $sourceDraft = $versions->createDraftFromSource($published, ['name' => 'Source Draft']);
    $versions->createDraftFromSource($sourceDraft, ['name' => 'Derived Draft']);

    expect($published->fresh()->canEditDirectly())->toBeFalse()
        ->and($published->fresh()->canDeletePermanently())->toBeFalse()
        ->and($sourceDraft->fresh()->canEditDirectly())->toBeFalse()
        ->and($sourceDraft->fresh()->lifecycleLockReason())->toContain('source record');

    expect(fn () => $versions->updateUnusedDraft($sourceDraft, ['name' => 'Unsafe Rename']))
        ->toThrow(ValidationException::class);
    expect(fn () => $versions->deleteUnusedDraft($sourceDraft))
        ->toThrow(ValidationException::class);
    expect(fn () => $versions->markWorkingDraft($sourceDraft))
        ->toThrow(ValidationException::class);
    expect(fn () => $versions->archiveUnusedDraft($sourceDraft))
        ->toThrow(ValidationException::class);

    Livewire::test(ListVerificationFormQuestions::class)
        ->call('setWorkingDraft', $sourceDraft->id)
        ->call('archiveDraft', $sourceDraft->id);

    expect($sourceDraft->fresh()->status)->toBe(VerificationTemplateVersion::STATUS_DRAFT)
        ->and($sourceDraft->fresh()->is_working_draft)->toBeFalse();
});

it('protects a template draft once a verification request references it', function () {
    $this->actingAs($this->saasUser);

    $versions = app(VerificationTemplateVersionService::class);
    $draft = $versions->createDraftFromSource($versions->ensureClinicPublishedVersion($this->clinic), [
        'name' => 'Request Referenced Draft',
    ]);

    BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $this->location->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'appointment_id' => $this->appointment->id,
        'patient_id' => $this->patient->id,
        'provider_id' => $this->provider->id,
        'patient_insurance_policy_id' => $this->policy->id,
        'verification_template_version_id' => $draft->id,
        'assigned_to' => $this->saasUser->id,
        'title' => 'Template retention test',
        'status' => BillingWorkItem::STATUS_PENDING,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'clinic_request',
    ]);

    expect($draft->fresh()->requestUsageCount())->toBe(1)
        ->and($draft->fresh()->canEditDirectly())->toBeFalse()
        ->and($draft->fresh()->canDeletePermanently())->toBeFalse()
        ->and($draft->fresh()->lifecycleLockReason())->toContain('verification request');

    expect(fn () => $versions->deleteUnusedDraft($draft))
        ->toThrow(ValidationException::class);
    expect(fn () => $versions->markWorkingDraft($draft))
        ->toThrow(ValidationException::class);
    expect(fn () => $versions->archiveUnusedDraft($draft))
        ->toThrow(ValidationException::class);
});

it('does not replicate a hidden master template into a new clinic', function () {
    $this->actingAs($this->saasUser);

    $versions = app(VerificationTemplateVersionService::class);
    $visibleMaster = $versions->ensureMasterVersion();
    $hiddenDraft = $versions->createDraftFromSource($visibleMaster, [
        'name' => 'Internal Master Draft',
        'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN,
    ]);
    $hiddenMaster = $versions->publishDraft($hiddenDraft);

    $newClinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Revenue Uptown',
        'clinic_code' => 'CLN-REV-UP',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $clinicVersion = $versions->ensureClinicPublishedVersion($newClinic);

    expect($hiddenMaster->fresh()->is_active)->toBeTrue()
        ->and($hiddenMaster->clinic_visibility)->toBe(VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN)
        ->and($clinicVersion->source_version_id)->toBe($visibleMaster->id)
        ->and($clinicVersion->source_version_id)->not->toBe($hiddenMaster->id);
});
