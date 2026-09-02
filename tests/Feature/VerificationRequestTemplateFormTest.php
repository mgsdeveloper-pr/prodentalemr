<?php

use App\Actions\Verification\CreateVerificationRequestAction;
use App\Actions\Verification\SaveVerificationAnswerAction;
use App\Filament\Saas\Resources\Verifications\Pages\EditVerificationRequest;
use App\Filament\Saas\Resources\Verifications\Tables\VerificationRequestsTable;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateVersion;
use App\Services\Verification\VerificationAuditService;
use App\Support\VerificationResultPdf;
use App\Support\VerificationTemplateVersionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
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
            'Reference Number',
            'Insurance Representative',
            'Verified By',
            'Verification Date',
            'Additional Information',
        ])
        ->and($section['total'])->toBe(6)
        ->and($section['completed'])->toBe(4)
        ->and(data_get($section, 'rows.0.field'))->toBe('custom_question_'.$modeQuestion->id);
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

    $latest = $versions->publishDraft($versions->createDraftFromPublished($original));

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
        ['D9911', 'Selected optional fields', ['pre_auth_required', 'notes']],
        ['D9912', 'No optional fields', []],
    ] as [$code, $prompt, $fields]) {
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
    };
    $page->useRequest($request->fresh());

    $rows = collect($page->frequencyRows())->keyBy('code');

    expect($rows->get('D9911')['frequency_response_fields'])->toBe(['pre_auth_required', 'notes'])
        ->and($rows->get('D9912')['frequency_response_fields'])->toBe([]);
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
        ['category' => 'Orthodontics', 'description' => $definitions[6][1], 'payment_guideline' => 'Monthly installments'],
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
        ->and($configurations[$definitions[7][1]]['yes_no_fields'])->toBe(['coverage_status'])
        ->and($questions->first()->missingFrequencyResponseFields(['downgrade_applies' => 'Yes']))->toHaveKey('downgrade_to');

    $page->persist();

    $savedRows = $request->verificationCoverageCodes()->get()->keyBy('description');

    expect($savedRows[$definitions[0][1]]->downgrade_to)->toBe('D2751')
        ->and($savedRows[$definitions[1][1]]->payment_guideline)->toBe('$2,000')
        ->and($savedRows[$definitions[4][1]]->age_limit)->toBe('19')
        ->and($savedRows[$definitions[5][1]]->coverage_percent)->toBe('25.00')
        ->and($savedRows[$definitions[7][1]]->coverage_status)->toBe('Yes');

    foreach ($questions as $question) {
        expect($question->missingFrequencyResponseFields($savedRows[$question->prompt]))->toBe([]);
    }

    $pdfRows = (new ReflectionMethod(VerificationResultPdf::class, 'mapCoverageCodeRowsForSection'))
        ->invoke(null, $request->fresh()->load('verificationCoverageCodes'), 'template_3_frequency_major');

    expect($pdfRows->firstWhere('label', $definitions[0][1])['value'])
        ->toContain('Downgrade: Yes', 'Downgrade code: D2751')
        ->and(file_get_contents(resource_path('views/filament/saas/resources/verifications/pages/partials/verification-form-template-3-content.blade.php')))
        ->not->toContain('No extra response fields selected.');
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

    $formData = ['vf_form_type' => 'full_form'];

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

    $page->persistDraftForTest();

    expect($request->verificationFormAnswers()->count())->toBe(45)
        ->and($request->verificationCoverageCodes()->count())->toBe(40)
        ->and($request->formSubmissions()->count())->toBe(0)
        ->and($request->activities()->where('activity_type', 'form_submitted')->count())->toBe(0)
        ->and($queryCount)->toBeLessThan(30);
});
