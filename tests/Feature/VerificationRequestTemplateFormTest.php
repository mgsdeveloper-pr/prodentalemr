<?php

use App\Actions\Verification\ArchiveClinicTemplateVersionAction;
use App\Actions\Verification\CreateVerificationRequestAction;
use App\Actions\Verification\EscalateVerificationRequestAction;
use App\Actions\Verification\SaveVerificationAnswerAction;
use App\Filament\Saas\Resources\Verifications\Pages\EditVerificationRequest;
use App\Filament\Saas\Resources\Verifications\Tables\VerificationRequestsTable;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\AuditLog;
use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateSection;
use App\Models\VerificationTemplateVersion;
use App\Services\Verification\CoverageAssessmentService;
use App\Services\Verification\PDFService;
use App\Services\Verification\QualityService;
use App\Services\Verification\StatusService;
use App\Services\Verification\VerificationAuditService;
use App\Services\Verification\VerificationResultService;
use App\Support\VerificationResultPdf;
use App\Support\VerificationTemplateVersionService;
use Barryvdh\DomPDF\PDF;
use Database\Seeders\RoleSeeder;
use Dompdf\Dompdf;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

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
    foreach (['view', 'update'] as $action) {
        $this->user->givePermissionTo(Permission::findOrCreate('verification.template_publishing.'.$action, 'web'));
    }
    $this->user->verificationClinics()->attach($this->clinic->id);

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
    // Request rendering tests require an explicitly available master, not implicit production provisioning.
    app(VerificationTemplateVersionService::class)->ensureMasterVersion();
});

it('validates coverage evidence without inventing a termination date or reference', function () {
    $service = app(CoverageAssessmentService::class);
    $data = ['outcome' => 'inactive', 'patient_name' => 'QA Patient', 'insurance_name' => 'QA Policy',
        'member_id' => 'TEST-ONLY', 'service_date' => '2026-10-15', 'source' => 'Insurance phone call', 'reason' => 'Policy terminated before service.'];
    expect($service->missing($data, '2026-10-15'))->toBe([])
        ->and($service->missing($data, '2026-10-16'))->toHaveKey('coverage.service_date')
        ->and($service->missing(array_replace($data, ['outcome' => 'unconfirmed'])))->toHaveKey('coverage.outcome')
        ->and($service->missing(array_replace($data, ['reason' => ' '])))->toHaveKey('coverage.reason')
        ->and($service->missing(array_replace($data, ['service_date' => 'not-a-date'])))->toHaveKey('coverage.service_date');
});

it('retains answers while inactive coverage bypasses benefits but not review', function (string $formType) {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);
    $question = VerificationFormQuestion::create(['template_version_id' => $version->id, 'template_key' => 'template_3',
        'section_key' => 'template_3_maximums_deductibles', 'prompt' => 'Annual maximum?', 'input_type' => 'text',
        'form_type' => $formType, 'is_active' => true, 'is_builtin' => false, 'is_required_for_audit' => true]);
    $assessment = ['outcome' => 'inactive', 'patient_name' => 'QA Patient', 'insurance_name' => 'QA Policy',
        'member_id' => 'TEST-ONLY', 'service_date' => '2026-10-15', 'source' => 'Insurance phone call', 'reason' => 'Confirmed terminated.'];
    $work = BillingWorkItem::create(['organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id, 'title' => 'Coverage test', 'status' => 'in_progress', 'priority' => 'normal',
        'verification_template_version_id' => $version->id, 'coverage_assessment' => $assessment]);
    $work->verificationProfile()->create(['form_type' => $formType, 'appointment_date' => '2026-10-15']);
    $audit = app(VerificationAuditService::class);
    expect($audit->missingRequiredAnswers($work))->toBe([]);
    expect(fn () => app(QualityService::class)->approve($work, $this->user))->toThrow(AuthorizationException::class);
    $work->coverage_assessment = array_replace($assessment, ['outcome' => 'active']);
    $work->save();
    expect($audit->missingRequiredAnswers($work))->not->toBe([]);
    $work->verificationFormAnswers()->create(['verification_form_question_id' => $question->id, 'answer_value' => '1500']);
    $work->coverage_assessment = $assessment;
    $work->save();
    $work->formSubmissions()->create(['user_id' => $this->user->id, 'panel' => 'verification', 'status' => 'in_progress',
        'version' => 1, 'payload' => ['work_item' => ['coverage_assessment' => $assessment], 'answers' => [['question_id' => $question->id, 'answer_value' => '1500']]]]);
    $quality = app(QualityService::class);
    $quality->submitForReview($work, $this->user);
    $quality->approve($work->refresh(), $this->user);
    expect($work->refresh()->status)->toBe('done')->and($work->outcome_status)->toBe('inactive_coverage')
        ->and($work->verificationFormAnswers()->first()->answer_value)->toBe('1500');
    $result = app(VerificationResultService::class)->summary($work);
    expect($result['eligibility_status'])->toBe('Inactive Coverage')->and($result['annual_maximum'])->not->toContain('1500');
    $snapshot = $work->formSubmissions()->where('status', 'done')->first();
    expect(data_get($snapshot->payload, 'work_item.coverage_assessment.outcome'))->toBe('inactive');
    $pdf = VerificationResultPdf::output($work);
    expect($pdf)->toStartWith('%PDF');
})->with(['full_form', 'short_form']);

it('saves and reopens coverage evidence without completing the request or clearing answers', function () {
    Filament::setCurrentPanel(Filament::getPanel('verification'));
    $work = BillingWorkItem::create(['organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id, 'title' => 'Coverage UI test', 'status' => 'pending', 'priority' => 'normal']);
    app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);
    $work = app(VerificationTemplateVersionService::class)->attachSnapshotToWorkItem($work);
    $assessment = ['outcome' => 'inactive', 'patient_name' => 'QA Patient', 'insurance_name' => 'QA Policy',
        'member_id' => 'TEST-ONLY', 'service_date' => '2026-10-15', 'source' => 'Payer portal', 'reason' => 'Confirmed terminated.'];
    Livewire::test(EditVerificationRequest::class, ['record' => $work->getRouteKey()])
        ->assertSet('data.coverage_assessment', [])
        ->set('data.coverage_assessment', $assessment)
        ->call('saveAsDraft')->assertHasNoErrors()
        ->assertSee('Retained form answers (not assessed)')
        ->assertSet('data.coverage_assessment.outcome', 'inactive');
    expect($work->fresh()->status)->toBe('pending')->and($work->fresh()->coverage_assessment['reason'])->toBe('Confirmed terminated.');
    Livewire::test(EditVerificationRequest::class, ['record' => $work->getRouteKey()])
        ->assertSet('data.coverage_assessment.outcome', 'inactive')
        ->set('data.coverage_assessment.outcome', 'active')
        ->call('saveAsDraft')->assertHasNoErrors();
    expect($work->fresh()->status)->toBe('pending')->and($work->fresh()->coverage_assessment['outcome'])->toBe('active');
});

it('blocks unconfirmed coverage and missing evidence at review and direct completion', function () {
    $work = BillingWorkItem::create(['organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id, 'title' => 'Coverage guard test', 'status' => 'review', 'priority' => 'normal',
        'coverage_assessment' => ['outcome' => 'unconfirmed']]);
    app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);
    $work = app(VerificationTemplateVersionService::class)->attachSnapshotToWorkItem($work);
    expect(fn () => app(StatusService::class)->transition($work, 'done'))->toThrow(ValidationException::class);
    $work->update(['coverage_assessment' => ['outcome' => 'inactive']]);
    expect(fn () => app(QualityService::class)->approve($work, $this->user))->toThrow(ValidationException::class);
    expect($work->fresh()->status)->toBe('review');
});

it('keeps structured progress scoped to active visible questions in the selected form', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);
    foreach (['progress_visible' => true, 'progress_hidden' => false] as $key => $active) {
        VerificationTemplateSection::create([
            'template_version_id' => $version->id, 'template_key' => 'template_3',
            'section_key' => $key, 'label' => $key, 'is_active' => $active, 'sort_order' => 900,
        ]);
    }
    $base = ['template_version_id' => $version->id, 'template_key' => 'template_3',
        'section_key' => 'progress_visible', 'is_active' => true, 'is_builtin' => false,
        'form_type' => 'full_form', 'input_type' => 'text', 'prompt' => 'Progress question'];
    $parent = VerificationFormQuestion::create(array_replace($base, ['input_type' => 'yes_no']));
    $child = VerificationFormQuestion::create(array_replace($base, [
        'question_kind' => 'conditional', 'parent_question_id' => $parent->id, 'trigger_answer' => 'yes',
    ]));
    VerificationFormQuestion::create(array_replace($base, ['form_type' => 'short_form']));
    VerificationFormQuestion::create(array_replace($base, ['is_active' => false]));
    VerificationFormQuestion::create(array_replace($base, ['section_key' => 'progress_hidden']));
    $page = new class extends EditVerificationRequest
    {
        public function configureProgress($version): void
        {
            $this->record = new BillingWorkItem(['verification_template_version_id' => $version->id]);
            $this->data = ['vf_form_type' => 'full_form'];
        }
    };
    $page->configureProgress($version);
    $page->data['custom_question_'.$parent->id] = 'No';
    $sections = collect($page->getStructuredTemplateSections())->keyBy('key');
    expect($sections->has('progress_hidden'))->toBeFalse()
        ->and($page->templateGroupProgress($sections['progress_visible']))->toBe(['answered' => 1, 'total' => 1]);
    $page->data['custom_question_'.$parent->id] = 'Yes';
    $section = collect($page->getStructuredTemplateSections())->firstWhere('key', 'progress_visible');
    expect($page->templateGroupProgress($section))->toBe(['answered' => 1, 'total' => 2]);
    $page->data['custom_question_'.$child->id] = 'Confirmed';
    expect($page->templateGroupProgress($section))->toBe(['answered' => 2, 'total' => 2]);
});

it('serves an authenticated PDF viewer with matching download and raw PDF URLs', function () {
    $request = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Viewer test', 'status' => 'pending', 'priority' => 'normal',
    ]);
    $this->withoutVite();
    $url = route('admin.verifications.pdf.preview', ['billingWorkItem' => $request, 'mode' => 'custom_landscape']);
    $this->get($url)->assertOk()->assertHeader('Content-Type', 'text/html; charset=utf-8')
        ->assertSee('Download PDF')->assertSee('raw=1')->assertSee('custom_landscape')
        ->assertSee('id="pdf-page"', false);

    $pdf = Mockery::mock(PDFService::class);
    $pdf->shouldReceive('output')->once()->withArgs(fn ($item, $mode) => $item->id === $request->id && $mode === 'custom_landscape')
        ->andReturn('%PDF-1.7 test');
    $pdf->shouldReceive('fileName')->andReturn('report.pdf');
    app()->instance(PDFService::class, $pdf);
    $this->get($url.'&raw=1')->assertOk()->assertHeader('Content-Type', 'application/pdf')
        ->assertContent('%PDF-1.7 test');

    $other = User::factory()->create(['status' => true]);
    $other->assignRole('verification_user');
    $this->actingAs($other);
    $this->get($url)->assertForbidden();
    $this->get($url.'&raw=1')->assertForbidden();
});

it('rejects a multipage custom landscape render before returning PDF bytes', function () {
    $request = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Oversized report', 'status' => 'pending', 'priority' => 'normal',
    ]);
    $dompdf = new Dompdf;
    $dompdf->loadHtml('<p>First page</p><p style="page-break-before: always">Second page</p>');
    $dompdf->render();
    $pdf = Mockery::mock(PDF::class);
    $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
    $pdf->shouldReceive('render')->once()->andReturnSelf();
    $pdf->shouldReceive('getDomPDF')->once()->andReturn($dompdf);
    $pdf->shouldNotReceive('output');
    Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()->andReturn($pdf);

    try {
        VerificationResultPdf::output($request, 'custom_landscape');
        test()->fail('An oversized custom report must not download.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('pdf_layout')
            ->and($exception->response->getStatusCode())->toBe(422)
            ->and($exception->response->getContent())->toContain('Select fewer sections');
    }
});

it('raises an urgent request once and preserves saved answers and workflow', function () {
    $request = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Urgent review', 'status' => 'pending', 'priority' => 'normal',
        'due_at' => now()->addHour(),
    ]);
    $due = $request->due_at->toDateTimeString();
    $action = app(EscalateVerificationRequestAction::class);
    $action->execute($request, 'Appointment brought forward', $this->user);
    $action->execute($request, 'Repeated click', $this->user);
    expect($request->fresh()->priority)->toBe('urgent')
        ->and($request->fresh()->status)->toBe('pending')
        ->and($request->fresh()->due_at->toDateTimeString())->toBe($due)
        ->and($request->activities()->where('activity_type', 'verification_escalated')->count())->toBe(1);
});

it('rejects unauthorized and completed urgent escalations', function () {
    $request = BillingWorkItem::create([
        'organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'assigned_to' => $this->user->id, 'title' => 'Protected request',
        'status' => 'pending', 'priority' => 'normal',
    ]);
    $action = app(EscalateVerificationRequestAction::class);
    $outsider = User::factory()->create();
    expect(fn () => $action->execute($request, 'Unauthorized', $outsider))
        ->toThrow(AuthorizationException::class);
    expect(fn () => $action->execute($request, ' ', $this->user))
        ->toThrow(ValidationException::class);
    $request->forceFill(['status' => BillingWorkItem::STATUS_DONE])->saveQuietly();
    expect(fn () => $action->execute($request, 'Too late', $this->user))
        ->toThrow(AuthorizationException::class);
    expect($request->fresh()->priority)->toBe('normal');
});

it('separates saved state from audit readiness', function () {
    $page = new EditVerificationRequest;
    $page->auditReady = false;
    expect($page->getFocusModeSaveState()['label'])->toBe('Saved');
    $page->updated('data.custom_question_1', 'Changed');
    expect($page->getFocusModeSaveState()['label'])->toBe('Unsaved Changes');
});

it('includes validation and response rules in template comparisons', function () {
    $question = new VerificationFormQuestion(['input_type' => 'select', 'select_options' => 'Yes,No', 'is_required_for_audit' => false]);
    $before = $question->reviewRules();
    $question->is_required_for_audit = true;
    expect($question->reviewRules())->not->toBe($before);
    $before = $question->reviewRules();
    $question->select_options = 'Yes,No,Unknown';
    expect($question->reviewRules())->not->toBe($before);
});

it('blocks publication of conditional questions without an active parent', function () {
    $service = app(VerificationTemplateVersionService::class);
    $published = $service->ensureClinicPublishedVersion($this->clinic);
    $draft = $service->createDraftFromPublished($published);
    VerificationFormQuestion::create([
        'template_version_id' => $draft->id, 'template_key' => 'template_3',
        'organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'prompt' => 'Orphan follow-up', 'section_key' => 'template_3_plan_provisions',
        'question_kind' => 'conditional', 'trigger_answer' => 'yes', 'input_type' => 'text',
        'is_active' => true, 'form_type' => 'both',
    ]);
    expect(fn () => $service->publishDraft($draft))->toThrow(ValidationException::class);
    expect($published->fresh()->is_active)->toBeTrue();
});

it('preserves question identity and reuse settings through clinic template versions', function () {
    $service = app(VerificationTemplateVersionService::class);
    $published = $service->ensureClinicPublishedVersion($this->clinic);
    $draft = $service->createDraftFromPublished($published);
    $question = $draft->questions()->firstOrFail();
    expect($question->semantic_key)->not->toBeEmpty()
        ->and($question->reuse_policy)->toBe('fresh_verification');
    $question->update(['information_scope' => 'plan', 'reuse_policy' => 'review_required', 'is_active' => true]);
    $next = $service->publishDraft($draft);
    $copy = $service->createDraftFromPublished($next)->questions()
        ->where('source_question_id', $question->id)->firstOrFail();
    expect($copy->semantic_key)->toBe($question->semantic_key)
        ->and($copy->information_scope)->toBe('plan')
        ->and($copy->reuse_policy)->toBe('review_required');
    $copy->update(['information_scope' => 'member']);
    expect(fn () => $service->publishDraft($copy->templateVersion))->toThrow(ValidationException::class);
});

it('archives an inactive used template without changing its request snapshot or answers', function () {
    $this->user->assignRole('saas_admin');
    $versions = app(VerificationTemplateVersionService::class);
    $version = $versions->ensureClinicPublishedVersion($this->clinic);
    $version->update(['uses_section_layout' => true]);
    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id, 'title' => 'Archive preservation test',
        'status' => 'pending', 'outcome_status' => 'pending', 'priority' => 'normal', 'source' => 'manual',
    ]);
    $question = $version->questions()->firstOrFail();
    $answer = $request->verificationFormAnswers()->create([
        'verification_form_question_id' => $question->id, 'answer_value' => 'Preserve test answer',
    ]);
    $snapshot = $request->fresh()->getRawOriginal();
    $questionRows = $version->questions()->get()->toArray();
    $version->update(['is_active' => false, 'active_full_form' => false, 'active_short_form' => false]);
    $archived = app(ArchiveClinicTemplateVersionAction::class)
        ->execute($this->user, $this->clinic, $version);
    expect($archived->status)->toBe('archived')->and($archived->deleted_at)->toBeNull()
        ->and($archived->canEditDirectly())->toBeFalse()->and($archived->canDeletePermanently())->toBeFalse()
        ->and($request->fresh()->getRawOriginal())->toBe($snapshot)
        ->and($answer->fresh()->answer_value)->toBe('Preserve test answer')
        ->and($archived->questions()->get()->toArray())->toBe($questionRows);
    expect(fn () => $archived->forceDelete())->toThrow(ValidationException::class);
    expect(fn () => $versions->activatePublishedVersion($archived))->toThrow(ValidationException::class);
    $log = AuditLog::where('module', 'verification_templates')->where('action', 'archive')->firstOrFail();
    expect($log->user_id)->toBe($this->user->id)
        ->and($log->clinic_id)->toBe($this->clinic->id)
        ->and($log->created_at)->not->toBeNull()
        ->and(json_decode($log->old_values, true)['status'])->toBe('published')
        ->and(json_decode($log->new_values, true)['status'])->toBe('archived')
        ->and(json_decode($log->new_values, true)['template_version_id'])->toBe($version->id);
    $copy = $versions->createDraftFromPublished($archived);
    expect($copy->status)->toBe('draft')->and($copy->questions()->count())->toBe(count($questionRows));
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

it('renders and counts custom sections from the request template version', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    VerificationTemplateSection::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'template_version_id' => $version->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'custom_testing',
        'label' => 'Testing',
        'sort_order' => 900,
        'is_builtin' => false,
        'is_active' => true,
    ]);

    $question = VerificationFormQuestion::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'template_version_id' => $version->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'custom_testing',
        'prompt' => 'Custom required answer',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 10,
        'is_builtin' => false,
        'is_active' => true,
        'is_required_for_audit' => true,
    ]);

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Custom section request',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $page = new class extends EditVerificationRequest
    {
        public function configure(BillingWorkItem $request, array $data): void
        {
            $this->record = $request;
            $this->data = $data;
        }
    };
    $page->configure($request, ['vf_form_type' => 'full_form']);

    $sections = $page->getTemplateThreeCustomSections();

    expect($sections)->toHaveCount(1)
        ->and(data_get($sections, '0.key'))->toBe('custom_testing')
        ->and(data_get($sections, '0.label'))->toBe('Testing')
        ->and(data_get($sections, '0.completed'))->toBe(0)
        ->and(data_get($sections, '0.total'))->toBe(1)
        ->and(data_get($sections, '0.questions.0.label'))->toBe('Custom required answer');

    $page->configure($request, [
        'vf_form_type' => 'full_form',
        'custom_question_'.$question->id => 'Answered',
    ]);

    expect(data_get($page->getTemplateThreeCustomSections(), '0.completed'))->toBe(1);
    $pdf = new class extends VerificationResultPdf
    {
        public static function sections($request, $state)
        {
            return self::buildSections($request, $state, true);
        }
    };
    $sections = collect($pdf::sections($request->fresh(), ['vf_form_type' => 'full_form', 'custom_question_'.$question->id => 'Answered']));
    expect($sections->firstWhere('key', 'custom_testing')['rows'][0]['value'])->toBe('Answered');
});

it('renders verification information from the request template order and counts every configured question', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    $modeQuestion = VerificationFormQuestion::create([
        'template_version_id' => $version->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'prompt' => 'Mode of Verification',
        'form_type' => 'both',
        'input_type' => 'select',
        'select_options' => "On Call\nWeb",
        'sort_order' => 0,
        'is_builtin' => true,
        'is_active' => true,
    ]);

    foreach ([
        ['Reference Number', null, 'text', 10],
        ['Insurance Representative', 'vf_insurance_representative_name', 'text', 20],
        ['Verified By', 'vf_verified_by', 'text', 30],
        ['Verification Date', 'vf_verification_date', 'date', 40],
        ['Additional Information', 'vf_verification_notes', 'textarea', 50],
    ] as [$prompt, $fieldKey, $inputType, $sortOrder]) {
        VerificationFormQuestion::create([
            'template_version_id' => $version->id,
            'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
            'section_key' => 'template_3_verification_information',
            'prompt' => $prompt,
            'field_key' => $fieldKey,
            'form_type' => 'both',
            'input_type' => $inputType,
            'sort_order' => $sortOrder,
            'is_builtin' => true,
            'is_active' => true,
        ]);
    }

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Template ordered verification information',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $page = new class extends EditVerificationRequest
    {
        public function useRecord(BillingWorkItem $record): void
        {
            $this->record = $record;
        }

        public function useData(array $data): void
        {
            $this->data = $data;
        }
    };
    $page->useRecord($request);
    $page->useData([
        'vf_form_type' => 'full_form',
        'custom_question_'.$modeQuestion->id => 'Web',
    ]);

    $section = $page->getTemplateThreeVerificationInformationSection();

    expect(collect($section['rows'])->pluck('label')->all())
        ->toBe([
            'Mode of Verification',
            'Portal Reference #',
            'Verified By',
            'Verification Date',
            'Additional Information',
        ])
        ->and($section['total'])->toBe(5)
        ->and($section['completed'])->toBe(3)
        ->and(data_get($section, 'rows.0.field'))->toBe('custom_question_'.$modeQuestion->id)
        ->and(data_get($section, 'rows.0.reactive'))->toBeTrue()
        ->and(data_get($section, 'rows.1.field'))->not->toBeNull()
        ->and(data_get($section, 'rows.1.readonly'))->toBeFalse()
        ->and(data_get($section, 'rows.1.value'))->toBeNull()
        ->and(data_get($section, 'rows.1.placeholder'))->toContain('portal confirmation');

    $page->useData([
        'vf_form_type' => 'full_form',
        'custom_question_'.$modeQuestion->id => 'On Call',
    ]);

    $phoneSection = $page->getTemplateThreeVerificationInformationSection();

    expect(collect($phoneSection['rows'])->pluck('label')->all())
        ->toBe([
            'Mode of Verification',
            'Insurance Reference #',
            'Insurance Representative',
            'Verified By',
            'Verification Date',
            'Additional Information',
        ])
        ->and($phoneSection['total'])->toBe(6)
        ->and(data_get($phoneSection, 'rows.1.placeholder'))->toContain('provided by insurance');
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

it('selects independent short and full templates for new requests', function () {
    $versions = app(VerificationTemplateVersionService::class);
    $full = $versions->ensureClinicPublishedVersion($this->clinic);
    $short = $versions->createDraftFromSource($full, ['form_type' => 'short_form', 'name' => 'Short release']);
    $short->questions()->create(['template_key' => 'template_3', 'section_key' => 'template_3_verification_information', 'prompt' => 'Reference', 'input_type' => 'text', 'form_type' => 'short_form', 'is_active' => true]);
    $versions->publishDraft($short, activate: true);
    $data = [
        'organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id, 'title' => 'Form routing test', 'priority' => 'normal', 'source' => 'manual',
    ];
    $shortRequest = app(CreateVerificationRequestAction::class)->execute($data, ['form_type' => 'short_form']);
    $fullRequest = app(CreateVerificationRequestAction::class)->execute($data, ['form_type' => 'full_form']);
    expect($shortRequest->verification_template_version_id)->toBe($short->id)
        ->and($fullRequest->verification_template_version_id)->toBe($full->id);
    $versions->activatePublishedVersion($full, 'short_form');
    expect($full->fresh()->active_short_form)->toBeTrue()->and($full->fresh()->active_full_form)->toBeTrue()
        ->and($short->fresh()->is_active)->toBeFalse()
        ->and($shortRequest->fresh()->verification_template_version_id)->toBe($short->id);
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

    $draft = $versions->createDraftFromPublished($original);
    $draft->questions()->create(['template_key' => 'template_3', 'section_key' => 'template_3_verification_information', 'prompt' => 'Reference', 'input_type' => 'text', 'form_type' => 'both', 'is_active' => true]);
    $latest = $versions->publishDraft($draft, activate: true);

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

    $latest = $versions->publishDraft($versions->createDraftFromPublished($original), activate: true);

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

it('never mixes frequency rows from draft current or historical template versions', function () {
    $versions = app(VerificationTemplateVersionService::class);
    $original = $versions->ensureClinicPublishedVersion($this->clinic);

    foreach ([
        ['', 'Test CDT Code Here', 10],
        ['D0140', 'Limited oral evaluation - problem focused', 20],
    ] as [$code, $prompt, $sortOrder]) {
        VerificationFormQuestion::create([
            'template_version_id' => $original->id,
            'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
            'section_key' => 'template_3_frequency_basic',
            'code' => $code,
            'prompt' => $prompt,
            'form_type' => 'both',
            'input_type' => 'frequency_row',
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    $payerQuestion = VerificationFormQuestion::query()
        ->where('template_version_id', $original->id)
        ->where('field_key', 'vf_payer_id')
        ->first();

    if ($payerQuestion) {
        VerificationFormQuestion::query()
            ->where('template_version_id', $original->id)
            ->where('field_key', 'vf_payer_id')
            ->update(['is_active' => false]);
    } else {
        VerificationFormQuestion::create([
            'template_version_id' => $original->id,
            'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
            'section_key' => 'template_3_insurance',
            'field_key' => 'vf_payer_id',
            'prompt' => 'Payer ID',
            'form_type' => 'both',
            'input_type' => 'text',
            'sort_order' => 10,
            'is_builtin' => true,
            'is_active' => false,
        ]);
    }

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Frequency snapshot request',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $latest = $versions->publishDraft($versions->createDraftFromPublished($original), activate: true);

    VerificationFormQuestion::create([
        'template_version_id' => $latest->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_frequency_basic',
        'code' => 'D9999',
        'prompt' => 'Latest template only row',
        'form_type' => 'both',
        'input_type' => 'frequency_row',
        'sort_order' => 30,
        'is_active' => true,
    ]);

    VerificationFormQuestion::create([
        'template_version_id' => $latest->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_insurance',
        'field_key' => 'vf_payer_id',
        'prompt' => 'Payer ID',
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 10,
        'is_builtin' => true,
        'is_active' => true,
    ]);

    foreach ([1, 2] as $sortOrder) {
        $request->verificationCoverageCodes()->create([
            'category' => 'Basic',
            'code' => '',
            'description' => 'Test CDT Code Here',
            'coverage_percent' => 80,
            'sort_order' => $sortOrder,
        ]);
    }

    $request->verificationCoverageCodes()->create([
        'category' => 'Basic',
        'code' => 'D9999',
        'description' => 'Latest template only row',
        'coverage_percent' => 50,
        'sort_order' => 3,
    ]);

    $page = new class extends EditVerificationRequest
    {
        public function useRequest(BillingWorkItem $request): void
        {
            $this->record = $request;
            $this->data = ['vf_form_type' => 'full_form'];
            $this->formTemplate = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;
        }

        public function frequencyRows(): array
        {
            return $this->templateThreeFrequencyQuestionRows();
        }

        public function resolvedCoverageRows(): array
        {
            return $this->resolveCodeCoverageRows();
        }
    };
    $page->useRequest($request->fresh());

    expect(collect($page->frequencyRows())->pluck('description')->all())
        ->toBe([
            'Test CDT Code Here',
            'Limited oral evaluation - problem focused',
        ])
        ->and($page->resolvedCoverageRows())->toHaveCount(2)
        ->and(collect($page->resolvedCoverageRows())->pluck('description')->all())
        ->toBe([
            'Test CDT Code Here',
            'Limited oral evaluation - problem focused',
        ])
        ->and($page->templateThreeFieldIsVisible('vf_payer_id'))->toBeFalse();

    $pdfCoverageRows = (new ReflectionMethod(VerificationResultPdf::class, 'mapCoverageCodeRowsForSection'))
        ->invoke(null, $request->fresh()->load('verificationCoverageCodes'), 'template_3_frequency_basic');

    expect($pdfCoverageRows)->toHaveCount(1)
        ->and($pdfCoverageRows->pluck('label')->all())
        ->toBe(['Test CDT Code Here']);

    foreach (['standard', 'custom_portrait', 'custom_landscape'] as $mode) {
        expect(VerificationResultPdf::output($request->fresh(), $mode))->toStartWith('%PDF-');
    }

    $snapshot = $request->formSubmissions()->create([
        'user_id' => $this->user->id,
        'panel' => 'verification',
        'status' => BillingWorkItem::STATUS_DONE,
        'outcome_status' => 'verified',
        'priority' => 'normal',
        'version' => 1,
        'payload' => [
            'coverage_codes' => [[
                'category' => 'Basic',
                'code' => '',
                'description' => 'Test CDT Code Here',
                'coverage_percent' => 65,
                'sort_order' => 1,
            ]],
        ],
    ]);

    $snapshotRows = (new ReflectionMethod(VerificationResultPdf::class, 'mapCoverageCodeRowsForSection'))
        ->invoke(null, $request->fresh()->load('verificationCoverageCodes'), 'template_3_frequency_basic', $snapshot);

    expect($snapshotRows)->toHaveCount(1)
        ->and($snapshotRows->first()['value'])->toBe('65%')
        ->and(VerificationResultPdf::output($request->fresh(), 'standard', submission: $snapshot))->toStartWith('%PDF-');
});

it('keeps the exact optional response fields configured for each frequency row', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    foreach ([
        ['D9911', 'Selected optional fields', ['pre_auth_required', 'notes'], true],
        ['D9912', 'No optional fields', [], false],
    ] as [$code, $prompt, $fields, $required]) {
        VerificationFormQuestion::create([
            'template_version_id' => $version->id,
            'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
            'section_key' => 'template_3_frequency_diagnostic_preventative',
            'code' => $code,
            'prompt' => $prompt,
            'form_type' => 'both',
            'input_type' => 'frequency_row',
            'frequency_response_mode' => 'current',
            'frequency_response_fields' => $fields,
            'sort_order' => 900,
            'is_required_for_audit' => $required,
            'is_active' => true,
        ]);
    }

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Optional frequency response fields',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $page = new class extends EditVerificationRequest
    {
        public function useRequest(BillingWorkItem $request): void
        {
            $this->record = $request;
            $this->data = ['vf_form_type' => 'full_form'];
            $this->formTemplate = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;
        }

        public function frequencyRows(): array
        {
            return $this->templateThreeFrequencyQuestionRows();
        }

        public function resolvedCoverageRows(): array
        {
            return $this->resolveCodeCoverageRows();
        }
    };
    $page->useRequest($request->fresh());

    $rows = collect($page->frequencyRows())->keyBy('code');

    $resolvedRows = collect($page->resolvedCoverageRows())->keyBy('code');

    expect($rows->get('D9911')['frequency_response_fields'])->toBe(['pre_auth_required', 'notes'])
        ->and($rows->get('D9912')['frequency_response_fields'])->toBe([])
        ->and($resolvedRows->get('D9911')['required'])->toBeTrue()
        ->and($resolvedRows->get('D9912')['required'])->toBeFalse();
});

it('does not default authorization or downgrade responses or infer them from zero coverage', function () {
    foreach (['current', 'advanced'] as $mode) {
        expect(VerificationFormQuestion::defaultFrequencyResponseFields($mode))
            ->not->toContain('pre_auth_required', 'downgrade_applies', 'pre_auth_details', 'downgrade_to');
    }
    $page = new class extends EditVerificationRequest
    {
        public function normalizeForTest(array $rows): array
        {
            return $this->normalizeCodeCoverageRows($rows);
        }
    };
    foreach ([['coverage_percent' => 0], ['coverage_status' => 'Not Covered'], []] as $input) {
        $row = $page->normalizeForTest([['description' => 'Test benefit', ...$input]])[0];
        expect($row['pre_auth_required'])->toBeNull()->and($row['downgrade_applies'])->toBeNull();
    }
    $row = $page->normalizeForTest([['description' => 'Test benefit', 'coverage_percent' => 0, 'pre_auth_required' => 'Yes',
        'pre_auth_details' => 'Explicit rule', 'downgrade_applies' => 'Yes', 'downgrade_to' => 'D2750']])[0];
    expect($row['pre_auth_required'])->toBe('Yes')->and($row['pre_auth_details'])->toBe('Explicit rule')
        ->and($row['downgrade_applies'])->toBe('Yes')->and($row['downgrade_to'])->toBe('D2750');
    $question = new VerificationFormQuestion(['input_type' => 'frequency_row', 'answer_layout' => 'downgrade']);
    expect($question->frequencyResponseConfiguration()['detail_fields'])->toBe(['downgrade_applies', 'downgrade_to']);
});

it('keeps different questions for the same procedure code in PDF output', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);
    foreach (['Exam frequency', 'Exam benefit limitations'] as $prompt) {
        $version->questions()->create([
            'template_key' => $version->template_key,
            'section_key' => 'template_3_frequency_diagnostic_preventative',
            'prompt' => $prompt, 'code' => 'D0120', 'form_type' => 'both',
            'input_type' => 'frequency_row', 'is_active' => true,
        ]);
    }
    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id, 'title' => 'Repeated code regression',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS, 'priority' => 'normal', 'source' => 'manual',
    ]);
    foreach (['Exam frequency' => 'Twice per year', 'Exam benefit limitations' => 'Separate limitation'] as $description => $frequency) {
        $request->verificationCoverageCodes()->create([
            'code_system' => 'ada', 'category' => 'Diagnostic & Preventative',
            'code' => 'D0120', 'description' => $description, 'frequency' => $frequency,
            'pre_auth_required' => 'No', 'downgrade_applies' => 'No',
        ]);
    }
    $pdf = new class extends VerificationResultPdf
    {
        public static function rows($request)
        {
            return self::mapCoverageCodeRowsForSection($request, 'template_3_frequency_diagnostic_preventative');
        }
    };
    $rows = $pdf::rows($request->fresh());
    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('value')->implode(' '))->toContain('Twice per year', 'Separate limitation')
        ->not->toContain('Pre-auth:', 'Downgrade:');
    expect($request->verificationCoverageCodes()->where('pre_auth_required', 'No')->count())->toBe(2);
});

it('uses and persists the correct responses for downgrade and orthodontic business questions', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);
    $definitions = [
        ['template_3_frequency_major', 'Crowns (D2740) Downgrade (Yes/No)? If YES need Code.'],
        ['template_3_frequency_orthodontics', 'Ortho Lifetime Maximum?'],
        ['template_3_frequency_orthodontics', 'Remaining Ortho maximum?'],
        ['template_3_frequency_orthodontics', 'Ortho Deductibles?'],
        ['template_3_frequency_orthodontics', 'Ortho Age limit?'],
        ['template_3_frequency_orthodontics', 'Initial Payment %'],
        ['template_3_frequency_orthodontics', 'How is Ortho Paid?'],
        ['template_3_frequency_orthodontics', 'Work In Progress Covered (Yes/No)?'],
    ];

    $questions = collect($definitions)->map(function (array $definition, int $index) use ($version): VerificationFormQuestion {
        return VerificationFormQuestion::create([
            'template_version_id' => $version->id,
            'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
            'section_key' => $definition[0],
            'prompt' => $definition[1],
            'form_type' => 'both',
            'input_type' => 'frequency_row',
            'frequency_response_mode' => 'advanced',
            'frequency_response_fields' => [],
            'sort_order' => 5000 + $index,
            'is_required_for_audit' => true,
            'is_active' => true,
        ]);
    });

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Major and orthodontic response request',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $coverageRows = [
        ['category' => 'Major', 'description' => $definitions[0][1], 'downgrade_applies' => 'Yes', 'downgrade_to' => 'D2751'],
        ['category' => 'Orthodontics', 'description' => $definitions[1][1], 'payment_guideline' => '$2,000'],
        ['category' => 'Orthodontics', 'description' => $definitions[2][1], 'payment_guideline' => '$1,200'],
        ['category' => 'Orthodontics', 'description' => $definitions[3][1], 'payment_guideline' => '$50'],
        ['category' => 'Orthodontics', 'description' => $definitions[4][1], 'age_limit' => '19'],
        ['category' => 'Orthodontics', 'description' => $definitions[5][1], 'coverage_percent' => 25],
        ['category' => 'Orthodontics', 'description' => $definitions[6][1], 'payment_guideline' => 'Dental', 'frequency' => 'Monthly installments'],
        ['category' => 'Orthodontics', 'description' => $definitions[7][1], 'coverage_status' => 'Yes'],
    ];
    $coverageRows = collect($coverageRows)->map(fn (array $row, int $index): array => [
        'id' => null,
        'code_system' => 'ada',
        'code' => '',
        'coverage_status' => null,
        'coverage_percent' => null,
        'frequency' => null,
        'age_limit' => null,
        'waiting_period' => null,
        'service_history' => null,
        'pre_auth_required' => null,
        'pre_auth_details' => null,
        'downgrade_applies' => null,
        'downgrade_to' => null,
        'payment_guideline' => null,
        'notes' => null,
        'sort_order' => $index + 1,
        ...$row,
    ])->all();

    $page = new class extends EditVerificationRequest
    {
        public function configure(BillingWorkItem $request, array $coverageRows): void
        {
            $this->record = $request;
            $this->data = ['vf_form_type' => 'full_form'];
            $this->codeCoverageData = $coverageRows;
            $this->formTemplate = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;
            $this->waitingPeriodAnswer = 'no';
        }

        public function persist(): void
        {
            $this->shouldSkipWorkflowSyncOnSave = true;
            $this->shouldCaptureSubmissionOnSave = false;

            try {
                $this->persistTemplateThreeWithoutResourceValidation(['outcome_status' => 'pending']);
            } finally {
                $this->shouldSkipWorkflowSyncOnSave = false;
                $this->shouldCaptureSubmissionOnSave = true;
            }
        }

        public function frequencyRows(): array
        {
            return $this->templateThreeFrequencyQuestionRows();
        }
    };
    $page->configure($request, $coverageRows);

    $configurations = collect($page->frequencyRows())
        ->whereIn('description', collect($definitions)->pluck(1))
        ->mapWithKeys(fn (array $row): array => [$row['description'] => $row['response_configuration']]);

    expect($configurations[$definitions[0][1]]['primary_fields'])->toBe([])
        ->and($configurations[$definitions[0][1]]['detail_fields'])->toBe(['downgrade_applies', 'downgrade_to'])
        ->and($configurations[$definitions[1][1]]['detail_fields'])->toBe(['payment_guideline'])
        ->and($configurations[$definitions[4][1]]['detail_fields'])->toBe(['age_limit'])
        ->and($configurations[$definitions[5][1]]['primary_fields'])->toBe(['coverage_percent'])
        ->and($configurations[$definitions[6][1]]['detail_fields'])->toBe(['payment_guideline', 'frequency'])
        ->and($configurations[$definitions[7][1]]['yes_no_fields'])->toBe(['coverage_status'])
        ->and($questions->first()->missingFrequencyResponseFields(['downgrade_applies' => 'Yes']))->toHaveKey('downgrade_to');

    $page->persist();

    $savedRows = $request->verificationCoverageCodes()->get()->keyBy('description');

    expect($savedRows[$definitions[0][1]]->downgrade_to)->toBe('D2751')
        ->and($savedRows[$definitions[1][1]]->payment_guideline)->toBe('$2,000')
        ->and($savedRows[$definitions[4][1]]->age_limit)->toBe('19')
        ->and($savedRows[$definitions[5][1]]->coverage_percent)->toBe('25.00')
        ->and($savedRows[$definitions[6][1]]->payment_guideline)->toBe('Dental')
        ->and($savedRows[$definitions[6][1]]->frequency)->toBe('Monthly installments')
        ->and($savedRows[$definitions[7][1]]->coverage_status)->toBe('Yes');

    foreach ($questions as $question) {
        expect($question->missingFrequencyResponseFields($savedRows[$question->prompt]))->toBe([]);
    }

    $orthoMethod = new ReflectionMethod(VerificationResultPdf::class, 'mapCoverageCodeRowsForSection');
    $orthoRows = $orthoMethod->invoke(null, $request->fresh()->load('verificationCoverageCodes'), 'template_3_frequency_orthodontics')->keyBy('label');
    expect($orthoRows['Benefit Paid Under']['value'])->toBe('Dental')
        ->and($orthoRows['Payment Schedule']['value'])->toBe('Monthly installments')
        ->and($orthoRows[$definitions[1][1]]['value'])->toBe('$2,000')
        ->and($orthoRows[$definitions[7][1]]['value'])->toBe('Yes');

    $savedRows[$definitions[6][1]]->update(['payment_guideline' => 'Quarterly installments', 'frequency' => null]);
    $savedRows[$definitions[3][1]]->update(['payment_guideline' => '10']);
    $savedRows[$definitions[1][1]]->update(['payment_guideline' => 'No']);
    $legacyRows = $orthoMethod->invoke(null, $request->fresh()->load('verificationCoverageCodes'), 'template_3_frequency_orthodontics')->keyBy('label');
    expect($legacyRows['Benefit Paid Under']['value'])->toBe('Not confirmed')
        ->and($legacyRows['Payment Schedule']['value'])->toBe('Not confirmed')
        ->and($legacyRows['Previously Recorded Payment Answer']['value'])->toBe('Quarterly installments')
        ->and($legacyRows[$definitions[3][1]]['value'])->toBe('$10.00')
        ->and($legacyRows[$definitions[1][1]]['value'])->toBe('No');

    $pdfRows = (new ReflectionMethod(VerificationResultPdf::class, 'mapCoverageCodeRowsForSection'))
        ->invoke(null, $request->fresh()->load('verificationCoverageCodes'), 'template_3_frequency_major');

    expect($pdfRows->firstWhere('label', $definitions[0][1])['value'])
        ->toContain('Downgrade: Yes', 'Downgrade code: D2751')
        ->and(file_get_contents(resource_path('views/filament/saas/resources/verifications/pages/partials/verification-form-template-3-content.blade.php')))
        ->toContain('uel2-question-list', 'uel2-input-addon--prefix')
        ->not->toContain('Audit-required only', 'No extra response fields selected.', "benefitGroupName === 'General' ? 'Diagnostic & Preventive'");
});

it('counts configured frequency responses and conditional details accurately', function () {
    $page = new class extends EditVerificationRequest {};
    $page->codeCoverageData = [
        [
            'category' => 'Major',
            'description' => 'Crown downgrade',
            'downgrade_applies' => 'Yes',
            'downgrade_to' => null,
            'response_configuration' => [
                'required_any' => ['downgrade_applies'],
                'required_when' => [
                    'field' => 'downgrade_to',
                    'when_field' => 'downgrade_applies',
                    'when_value' => 'Yes',
                ],
            ],
        ],
        [
            'category' => 'Orthodontics',
            'description' => 'Lifetime maximum',
            'payment_guideline' => '$2,000',
            'response_configuration' => [
                'required_any' => ['payment_guideline'],
                'required_when' => [],
            ],
        ],
        [
            'category' => 'General',
            'description' => 'Zero percent benefit',
            'coverage_percent' => 0,
            'response_configuration' => [
                'required_any' => ['coverage_status', 'coverage_percent'],
                'required_when' => [],
            ],
        ],
    ];

    expect($page->getCodeCoverageSection()['completed'])->toBe(2)
        ->and($page->codeCoverageRowIsComplete($page->codeCoverageData[0]))->toBeFalse();

    $page->codeCoverageData[0]['downgrade_to'] = 'D2751';

    expect($page->getCodeCoverageSection()['completed'])->toBe(3)
        ->and($page->codeCoverageRowIsComplete($page->codeCoverageData[0]))->toBeTrue();
});

it('preserves structured select values and formats hydrated dates for native controls', function () {
    $page = new class extends EditVerificationRequest
    {
        public function getInsuranceCarrierOptions(): array
        {
            return ['Carrier A' => 'Carrier A'];
        }

        public function displayDates(array $data): array
        {
            return $this->normalizeVerificationDateFieldsForDisplay($data);
        }
    };
    $page->data = ['vf_insured_relation' => 'self', 'vf_insurance_provider_name' => 'Historical Carrier'];
    expect($page->managedAnswerOptions(['field' => 'vf_insured_relation', 'options' => ['Self', 'Dependent']]))
        ->toBe(['self' => 'Self', 'dependent' => 'Dependent'])
        ->and($page->managedAnswerOptions(['field' => 'vf_insurance_provider_name', 'options' => []]))
        ->toBe(['Carrier A' => 'Carrier A', 'Historical Carrier' => 'Historical Carrier'])
        ->and($page->displayDates(['vf_subscriber_dob' => '1990-01-15 00:00:00', 'vf_patient_dob' => '1990-01-15', 'vf_effective_date' => null]))
        ->toBe(['vf_subscriber_dob' => '1990-01-15', 'vf_patient_dob' => '1990-01-15', 'vf_effective_date' => null]);
    $page->data = ['custom_question_99' => 'Previously selected'];
    expect($page->managedAnswerOptions(['field' => 'custom_question_99', 'options' => ['Yes', 'No']]))
        ->toBe(['Yes' => 'Yes', 'No' => 'No', 'Previously selected' => 'Previously selected']);
});

it('does not pre-answer the waiting period question', function () {
    $page = new class extends EditVerificationRequest {};

    expect($page->waitingPeriodAnswer)->toBe('');
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

it('saves a full template draft in bulk without creating an audit submission', function () {
    $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);

    $questions = collect(range(1, 45))->map(fn (int $number): VerificationFormQuestion => VerificationFormQuestion::create([
        'template_version_id' => $version->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'prompt' => 'Full form question '.$number,
        'form_type' => 'both',
        'input_type' => 'text',
        'sort_order' => 1000 + $number,
        'is_active' => true,
    ]));

    collect(range(1, 40))->each(fn (int $number): VerificationFormQuestion => VerificationFormQuestion::create([
        'template_version_id' => $version->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => $number <= 20 ? 'template_3_frequency_basic' : 'template_3_frequency_major',
        'code' => 'D'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
        'prompt' => 'Procedure '.$number,
        'form_type' => 'both',
        'input_type' => 'frequency_row',
        'sort_order' => $number,
        'is_active' => true,
    ]));

    $request = app(CreateVerificationRequestAction::class)->execute([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Large full form draft save',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    $formData = ['vf_form_type' => 'full_form', 'vf_ortho_benefit' => 50, 'vf_ortho_lifetime_maximum' => 2000];

    foreach ($questions as $question) {
        $formData['custom_question_'.$question->id] = 'Answer '.$question->id;
        $formData['custom_question_note_'.$question->id] = 'Note '.$question->id;
    }

    $coverageRows = collect(range(1, 40))->map(fn (int $number): array => [
        'id' => null,
        'code_system' => 'ada',
        'category' => $number <= 20 ? 'Basic' : 'Major',
        'code' => 'D'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
        'description' => 'Procedure '.$number,
        'coverage_status' => 'Covered',
        'coverage_percent' => $number % 2 === 0 ? 80 : 50,
        'frequency' => '1 per year',
        'age_limit' => null,
        'waiting_period' => null,
        'service_history' => null,
        'pre_auth_required' => $number % 3 === 0 ? 'Yes' : 'No',
        'pre_auth_details' => $number % 3 === 0 ? 'Required before treatment' : null,
        'downgrade_applies' => 'No',
        'downgrade_to' => null,
        'payment_guideline' => null,
        'notes' => 'Coverage note '.$number,
        'sort_order' => $number,
    ])->all();

    $page = new class extends EditVerificationRequest
    {
        public function configureDraft(BillingWorkItem $request, array $formData, array $coverageRows): void
        {
            $this->record = $request;
            $this->data = $formData;
            $this->codeCoverageData = $coverageRows;
            $this->formTemplate = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;
            $this->waitingPeriodAnswer = 'no';
        }

        public function persistDraftForTest(): void
        {
            $this->shouldSkipWorkflowSyncOnSave = true;
            $this->shouldCaptureSubmissionOnSave = false;

            try {
                $this->persistTemplateThreeWithoutResourceValidation(['outcome_status' => 'pending']);
            } finally {
                $this->shouldSkipWorkflowSyncOnSave = false;
                $this->shouldCaptureSubmissionOnSave = true;
            }
        }
    };

    $page->configureDraft($request, $formData, $coverageRows);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $page->persistDraftForTest();
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $page->persistDraftForTest();
    $repeatSaveQueries = collect(DB::getQueryLog());
    DB::disableQueryLog();

    $coverageUpsert = $repeatSaveQueries->first(fn (array $query): bool => str_contains(
        strtolower($query['query']),
        'verification_coverage_codes'
    ) && str_contains(strtolower($query['query']), 'update'));

    expect($request->verificationFormAnswers()->count())->toBe(45)
        ->and($request->verificationCoverageCodes()->count())->toBe(40)
        ->and($request->fresh()->verificationProfile?->waiting_periods)->toBe('No')
        ->and((float) $request->fresh()->verificationProfile?->ortho_benefit)->toBe(50.0)
        ->and((float) $request->fresh()->verificationProfile?->ortho_lifetime_maximum)->toBe(2000.0)
        ->and($request->formSubmissions()->count())->toBe(0)
        ->and($request->activities()->where('activity_type', 'form_submitted')->count())->toBe(0)
        ->and($queryCount)->toBeLessThan(30)
        ->and($coverageUpsert)->not->toBeNull()
        ->and(strtolower($coverageUpsert['query']))->toContain('public_id');
});

it('opens a legacy request and checks refresh availability without provisioning or attaching a snapshot', function (bool $configured) {
    Filament::setCurrentPanel(Filament::getPanel('verification'));
    if ($configured) {
        $version = app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);
        $version->update(['uses_section_layout' => true]);
    }
    $request = BillingWorkItem::create([
        'organization_id' => $this->organization->id, 'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id, 'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id, 'title' => 'Legacy request', 'status' => 'pending', 'priority' => 'normal',
    ]);
    $before = VerificationTemplateVersion::count();
    $audit = app(VerificationAuditService::class);

    Livewire::test(EditVerificationRequest::class, ['record' => $request->getRouteKey()])
        ->assertOk()->call('usesSectionLayout')->assertReturned($configured);
    expect($audit->templateVersionId($request))->toBe($version->id ?? 0)
        ->and(array_key_exists('template', $audit->missingRequiredAnswers($request)))->toBe(! $configured)
        ->and(VerificationTemplateVersion::count())->toBe($before)
        ->and($request->fresh()->verification_template_version_id)->toBeNull()
        ->and($request->fresh()->verification_template_snapshot)->toBeNull();

    if (! $configured) {
        expect(app(\App\Actions\Verification\RefreshVerificationTemplateAction::class)->canRefresh($request, $this->user))->toBeFalse();
    }
})->with(['missing active form' => [false], 'existing active form' => [true]]);

it('enforces independent active template slots at the database boundary', function (string $scope, string $form) {
    $source = $scope === 'master'
        ? VerificationTemplateVersion::query()->where('scope', 'master')->where('active_'.$form, true)->firstOrFail()
        : app(VerificationTemplateVersionService::class)->ensureClinicPublishedVersion($this->clinic);
    $candidate = $source->replicate();
    $candidate->forceFill([
        'version_number' => $source->version_number + 1,
        'is_active' => false,
        'active_full_form' => false,
        'active_short_form' => false,
    ])->save();

    expect(fn () => DB::table('verification_template_versions')->where('id', $candidate->id)
        ->update(['active_'.$form => true]))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);

    expect($candidate->fresh()->{'active_'.$form})->toBeFalse()
        ->and($source->fresh()->{'active_'.$form})->toBeTrue();
})->with(['master', 'clinic'])->with(['full_form', 'short_form']);

it('renders and preserves a historical clinic request that references a master snapshot', function () {
    $versions = app(VerificationTemplateVersionService::class);
    $master = VerificationTemplateVersion::query()->where('scope', 'master')->firstOrFail();
    $question = VerificationFormQuestion::create([
        'template_version_id' => $master->id,
        'template_key' => VerificationFormQuestion::DEFAULT_TEMPLATE_KEY,
        'section_key' => 'template_3_verification_information',
        'prompt' => 'Historical master audit question',
        'form_type' => 'both',
        'input_type' => 'text',
        'is_required_for_audit' => true,
        'is_active' => true,
    ]);
    $request = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => $this->enrollment->id,
        'assigned_to' => $this->user->id,
        'title' => 'Historical master snapshot QA',
        'status' => 'pending',
        'priority' => 'normal',
        'verification_template_version_id' => $master->id,
        'verification_template_snapshot' => $versions->snapshot($master),
        'verification_template_snapshot_at' => now(),
    ]);
    $target = $versions->ensureClinicPublishedVersion($this->clinic);
    $before = $request->fresh()->getRawOriginal();
    $audit = app(VerificationAuditService::class);
    $refresh = app(\App\Actions\Verification\RefreshVerificationTemplateAction::class);
    $review = $refresh->review($request);

    expect($review['to'])->toBe($target->name.' v'.$target->version_number)
        ->and($review['pairs'][$question->id] ?? null)->not->toBeNull()
        ->and($audit->templateVersionId($request))->toBe($master->id)
        ->and($audit->applicableQuestions($request, $master->template_key, 'full_form', frequencyRows: false)->modelKeys())
        ->toContain($question->id)
        ->and(app(PDFService::class)->output($request))->toStartWith('%PDF-')
        ->and($versions->attachSnapshotToWorkItem($request)->fresh()->getRawOriginal())->toBe($before);
});
