<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Filament\Saas\Resources\Verifications\Pages\Concerns\InteractsWithVerificationWorkbench;
use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Models\BillingWorkItem;
use App\Models\InsuranceCarrier;
use App\Models\InsuranceCarrierNetworkProfile;
use App\Models\User;
use App\Models\VerificationCoverageCode;
use App\Models\VerificationFormSubmission;
use App\Models\VerificationFormQuestion;
use App\Support\VerificationAutoAssigner;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class EditVerificationWorkItem extends EditRecord
{
    use InteractsWithVerificationWorkbench;
    use WithFileUploads;

    protected static string $resource = VerificationWorkItemResource::class;

    protected string $view = 'filament.saas.resources.verifications.pages.edit-verification-work-item';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected array $verificationProfileData = [];
    protected array $verificationFormAnswerData = [];
    protected array $verificationFormAnswerNoteData = [];
    protected array $verificationCoverageCodeData = [];
    public array $codeCoverageData = [];
    public array $clinicResponseAttachments = [];
    public bool $auditReady = false;
    public bool $openInfoRequestModalOnLoad = false;
    public bool $showAddInsuranceModal = false;
    public array $newInsuranceCarrier = [];
    public string $formTemplate = 'template_2';
    public string $waitingPeriodAnswer = 'no';
    public array $waitingPeriodDetails = [];
    protected bool $shouldSkipWorkflowSyncOnSave = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->recordActivity('verification_console_opened', 'Verification console opened.', [
            'panel' => $this->getSubmissionPanel(),
            'user_name' => auth()->user()?->name,
            'status' => $this->record->normalized_status,
        ]);

        $this->openInfoRequestModalOnLoad = request()->boolean('request_clinic')
            && ($this->canRequestClinicInfo()
                || $this->record->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);

        $requestedTemplate = request()->string('template')->toString();
        $this->formTemplate = in_array($requestedTemplate, ['template_1', 'template_2'], true)
            ? $requestedTemplate
            : ($this->record->clinic?->getVerificationDefaultFormTemplate() ?? 'template_2');

        $this->initializeWaitingPeriodDetails();
    }

    public function selectFormTemplate(string $template): void
    {
        abort_unless(in_array($template, ['template_1', 'template_2'], true), 404);

        $this->formTemplate = $template;
    }

    public function getTitle(): string
    {
        return 'Verification Form';
    }

    public function getViewUrl(): string
    {
        return VerificationWorkItemResource::getUrl('view', ['record' => $this->record]);
    }

    public function getIndexUrl(): string
    {
        return VerificationWorkItemResource::getUrl('index');
    }

    public function getPdfDownloadUrl(): string
    {
        return route('admin.verifications.pdf.download', $this->record);
    }

    public function getPdfPreviewUrl(): string
    {
        return route('admin.verifications.pdf.preview', $this->record);
    }

    public function getFormDescription(): string
    {
        return 'Review the request context on the left and complete the verification answers in the center.';
    }

    public function getSaveButtonLabel(): string
    {
        return $this->auditReady ? 'Save' : 'Audit';
    }

    public function getViewButtonLabel(): string
    {
        return 'View details';
    }

    public function getIndexButtonLabel(): string
    {
        return 'Save & Back';
    }

    public function getCancelButtonLabel(): string
    {
        return 'Cancel';
    }

    public function canManageQueueControl(): bool
    {
        return auth()->user()?->canManageVerificationQueue() ?? false;
    }

    public function canSubmitForm(): bool
    {
        return $this->record->verificationUserCanEditVerification(auth()->user());
    }

    public function takeOwnership(): void
    {
        abort_unless($this->canManageQueueControl(), 403);

        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $this->record->assigned_to = $user->getKey();

        if ($this->record->normalized_status === BillingWorkItem::STATUS_PENDING) {
            $this->record->started_at ??= now();
        }

        $this->record->save();
        $this->record->refresh();

        Notification::make()
            ->title('Ownership updated')
            ->body('This verification request is now assigned to you.')
            ->success()
            ->send();
    }

    protected function beforeSave(): void
    {
        abort_unless($this->canSubmitForm(), 403);
    }

    public function updated($name, $value): void
    {
        if (
            str_starts_with((string) $name, 'data.')
            || str_starts_with((string) $name, 'codeCoverageData.')
            || str_starts_with((string) $name, 'waitingPeriod')
        ) {
            $this->auditReady = false;
        }

        if ($name === 'waitingPeriodAnswer' && $value !== 'yes') {
            $this->waitingPeriodDetails = $this->defaultWaitingPeriodDetails();
            $this->data['vf_waiting_periods'] = null;
        }

        if ($name === 'data.vf_insurance_provider_name') {
            $this->applySelectedInsuranceCarrier((string) $value);
        }
    }

    public function getInsuranceCarrierOptions(): array
    {
        $clinicId = filled($this->record->clinic_id) ? (int) $this->record->clinic_id : null;

        return Cache::remember(
            'verification.insurance-carrier-options.' . ($clinicId ?: 'global'),
            now()->addMinutes(10),
            fn (): array => InsuranceCarrier::query()
                ->with(['overrides' => fn ($query) => $query->when(
                    filled($clinicId),
                    fn ($builder) => $builder->where('clinic_id', $clinicId),
                    fn ($builder) => $builder->whereRaw('1 = 0')
                )])
                ->where('is_active', true)
                ->orderBy('insurance_name')
                ->get()
                ->mapWithKeys(function (InsuranceCarrier $carrier) use ($clinicId): array {
                    $effective = $carrier->effectiveAttributesForClinic($clinicId);
                    $value = trim((string) $carrier->insurance_name);
                    $label = trim((string) ($effective['insurance_name'] ?? $carrier->insurance_name));

                    return $value !== '' && ($effective['is_active'] ?? true)
                        ? [$value => ($label !== '' ? $label : $value)]
                        : [];
                })
                ->all(),
        );
    }

    public function canAddInsuranceCarrier(): bool
    {
        $user = auth()->user();

        return (bool) (
            $user?->canPerformVerificationModuleAction('insurance_directory', 'add')
            || $user?->canPerformSaasModuleAction('insurance_directory', 'add')
        );
    }

    public function openAddInsuranceModal(): void
    {
        abort_unless($this->canAddInsuranceCarrier(), 403);

        $this->resetErrorBag();
        $this->newInsuranceCarrier = [
            'insurance_name' => '',
            'payer_id' => '',
            'payer_phone' => '',
            'claims_address' => '',
        ];
        $this->showAddInsuranceModal = true;
    }

    public function closeAddInsuranceModal(): void
    {
        $this->showAddInsuranceModal = false;
        $this->newInsuranceCarrier = [];
        $this->resetErrorBag();
    }

    public function addInsuranceCarrier(): void
    {
        abort_unless($this->canAddInsuranceCarrier(), 403);

        $validated = $this->validate([
            'newInsuranceCarrier.insurance_name' => ['required', 'string', 'max:255'],
            'newInsuranceCarrier.payer_id' => ['nullable', 'string', 'max:255'],
            'newInsuranceCarrier.payer_phone' => ['nullable', 'string', 'max:255'],
            'newInsuranceCarrier.claims_address' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = $validated['newInsuranceCarrier'];
        $name = trim((string) $payload['insurance_name']);

        $carrier = InsuranceCarrier::query()->firstOrCreate(
            ['insurance_name' => $name],
            [
                'payer_id' => filled($payload['payer_id'] ?? null) ? trim((string) $payload['payer_id']) : null,
                'payer_phone' => filled($payload['payer_phone'] ?? null) ? trim((string) $payload['payer_phone']) : null,
                'claims_address' => filled($payload['claims_address'] ?? null) ? trim((string) $payload['claims_address']) : null,
                'is_active' => true,
            ],
        );

        if (! $carrier->wasRecentlyCreated && ! $carrier->is_active) {
            $carrier->update(['is_active' => true]);
        }

        Cache::forget('verification.insurance-carrier-options.' . ($this->record->clinic_id ?: 'global'));
        $this->data['vf_insurance_provider_name'] = $carrier->insurance_name;
        $this->applySelectedInsuranceCarrier($carrier->insurance_name);
        $this->closeAddInsuranceModal();

        Notification::make()
            ->title($carrier->wasRecentlyCreated ? 'Insurance added' : 'Insurance selected')
            ->body($carrier->insurance_name . ' is now selected for this verification.')
            ->success()
            ->send();
    }

    protected function applySelectedInsuranceCarrier(string $carrierName): void
    {
        $carrierName = trim($carrierName);

        if ($carrierName === '') {
            return;
        }

        $clinicId = filled($this->record->clinic_id) ? (int) $this->record->clinic_id : null;
        $carrier = InsuranceCarrier::query()
            ->with([
                'networkProfile',
                'overrides' => fn ($query) => $query->when(
                    filled($clinicId),
                    fn ($builder) => $builder->where('clinic_id', $clinicId),
                    fn ($builder) => $builder->whereRaw('1 = 0')
                ),
            ])
            ->whereRaw('LOWER(insurance_name) = ?', [mb_strtolower($carrierName)])
            ->first();

        if (! $carrier) {
            return;
        }

        $effective = $carrier->effectiveAttributesForClinic($clinicId);
        $this->data['vf_insurance_provider_name'] = $effective['insurance_name'] ?: $carrier->insurance_name;
        $this->data['vf_payer_id'] = $effective['payer_id'] ?: null;
        $this->data['vf_insurance_company_phone_number'] = $effective['payer_phone'] ?: null;
        $this->data['vf_insurance_claim_mailing_address'] = $effective['claims_address'] ?: null;
        $this->data['vf_fee_schedule'] = $carrier->networkProfile?->feeScheduleReferenceName();
        $this->auditReady = false;
    }

    public function canRequestClinicInfo(): bool
    {
        return $this->record->canUserTransitionTo(auth()->user(), BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);
    }

    public function auditVerification(): void
    {
        abort_unless($this->canSubmitForm(), 403);

        $this->resetErrorBag();

        try {
            $this->callHook('beforeValidate');

            $this->form->getState(afterValidate: function (): void {
                $this->callHook('afterValidate');
            });
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Audit found issues')
                ->body('Please resolve the highlighted validation errors before saving.')
                ->danger()
                ->send();

            throw $exception;
        }

        $missingFields = $this->missingRequiredVerificationFields();

        if ($missingFields !== []) {
            foreach ($missingFields as $fieldKey => $label) {
                $isCodeField = str_starts_with((string) $fieldKey, 'codeCoverageData.');

                $this->addError(
                    $isCodeField ? $fieldKey : 'data.' . $fieldKey,
                    $isCodeField ? $label . '.' : $label . ' is required before saving.'
                );
            }

            Notification::make()
                ->title('Audit incomplete')
                ->body('Some required verification answers are still missing. Complete them before saving.')
                ->danger()
                ->send();

            $this->auditReady = false;

            return;
        }

        $this->auditReady = true;

        Notification::make()
            ->title('Audit complete')
            ->body('Validation passed. The Audit button is now ready to Save.')
            ->success()
            ->send();
    }

    public function saveAndBack(): void
    {
        abort_unless($this->canSubmitForm(), 403);

        $this->shouldSkipWorkflowSyncOnSave = true;
        $this->save(false, false);
        $this->shouldSkipWorkflowSyncOnSave = false;

        Notification::make()
            ->title('Draft saved')
            ->body('Your verification progress was saved.')
            ->success()
            ->send();

        $redirectUrl = $this->getIndexUrl();

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
    }

    public function clearVerificationForm(): void
    {
        abort_unless($this->canSubmitForm(), 403);

        $formType = data_get($this->data, 'vf_form_type')
            ?: $this->record->verificationProfile?->form_type
            ?: 'full_form';

        foreach (array_keys($this->data ?? []) as $key) {
            if (
                str_starts_with((string) $key, 'vf_')
                && $key !== 'vf_form_type'
                && $key !== 'vf_is_provider_in_network'
                && $key !== 'vf_network_status'
                && $key !== 'vf_insurance_provider_name'
                && $key !== 'vf_payer_id'
                || str_starts_with((string) $key, 'custom_question_')
                || in_array($key, ['notes', 'internal_summary', 'info_request_reason', 'return_reason'], true)
            ) {
                $this->data[$key] = null;
            }
        }

        $this->data['vf_form_type'] = $formType;
        $this->data['outcome_status'] = 'pending';
        $this->codeCoverageData = collect($this->configuredCodeCoverageTemplate())
            ->values()
            ->map(fn (array $row, int $index): array => [
                'id' => null,
                'code_system' => 'ada',
                'category' => $row['category'],
                'code' => $row['code'],
                'description' => $row['description'],
                'frequency_response_mode' => $row['frequency_response_mode'] ?? 'current',
                'frequency_response_fields' => $row['frequency_response_fields'] ?? VerificationFormQuestion::defaultFrequencyResponseFields($row['frequency_response_mode'] ?? 'current'),
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
            ])
            ->all();
        $this->clinicResponseAttachments = [];
        $this->waitingPeriodAnswer = 'no';
        $this->waitingPeriodDetails = $this->defaultWaitingPeriodDetails();
        $this->data = $this->applyAutofillDefaults($this->data ?? []);
        $this->auditReady = false;
        $this->resetErrorBag();

        Notification::make()
            ->title('Form cleared')
            ->body('Verification answers were reset to their base defaults.')
            ->success()
            ->send();
    }

    public function getQueueControlSnapshot(): array
    {
        $record = $this->record;

        return [
            ['label' => 'Status', 'value' => BillingWorkItem::STATUS_OPTIONS[$record->normalized_status] ?? str($record->normalized_status)->headline()->toString()],
            ['label' => 'Verification Result', 'value' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$record->outcome_status] ?? str($record->outcome_status)->headline()->toString()],
            ['label' => 'Priority', 'value' => BillingWorkItem::PRIORITY_OPTIONS[$record->priority] ?? str($record->priority)->headline()->toString()],
            ['label' => 'Due At', 'value' => optional($record->due_at)->format('d-M-Y h:i A') ?: '-'],
            ['label' => 'Assignee', 'value' => $record->assignedTo?->name ?: 'Unassigned'],
            ['label' => 'Reviewer', 'value' => $record->reviewedBy?->name ?: 'No reviewer'],
            ['label' => 'PMS Sync', 'value' => BillingWorkItem::PMS_SYNC_STATUS_OPTIONS[$record->pms_sync_status] ?? str($record->pms_sync_status)->headline()->toString()],
            ['label' => 'Writeback', 'value' => BillingWorkItem::WRITEBACK_STATUS_OPTIONS[$record->writeback_status] ?? str($record->writeback_status)->headline()->toString()],
        ];
    }

    public function getTopControlOptions(): array
    {
        return [
            'status' => BillingWorkItem::STATUS_OPTIONS,
            'outcome_status' => BillingWorkItem::OUTCOME_STATUS_OPTIONS,
            'priority' => BillingWorkItem::PRIORITY_OPTIONS,
            'pms_sync_status' => BillingWorkItem::PMS_SYNC_STATUS_OPTIONS,
            'writeback_status' => BillingWorkItem::WRITEBACK_STATUS_OPTIONS,
            'assigned_to' => VerificationAutoAssigner::optionList($this->record->clinic_id),
            'reviewed_by' => VerificationAutoAssigner::optionList($this->record->clinic_id),
        ];
    }

    public function getSmartVerificationForm(): array
    {
        $record = $this->record;
        $clinicName = $record->clinic?->clinic_name ?: $record->organization?->name ?: '-';
        $locationName = $record->location?->location_name ?: '-';
        $providerName = $record->provider?->display_name ?: $record->provider?->user?->name ?: '-';
        $appointment = $record->appointment;
        $appointmentTime = $appointment?->start_time;

        if ($appointmentTime instanceof \Illuminate\Support\Carbon || $appointmentTime instanceof \Carbon\CarbonInterface) {
            $appointmentTime = $appointmentTime->format('h:i A');
        }

        return [
            [
                'title' => 'Clinic & Insurance Participation',
                'description' => 'Confirm clinic context, provider participation, and payer contact details.',
                'accent' => '#0f766e',
                'fields' => [
                    ['label' => 'Clinic', 'value' => $clinicName, 'readonly' => true],
                    ['label' => 'Location', 'value' => $locationName, 'readonly' => true],
                    ['label' => 'Provider', 'value' => $providerName, 'readonly' => true],
                    ['label' => 'Insurance Provider', 'field' => 'vf_insurance_provider_name'],
                    ['label' => 'Insurance Phone', 'field' => 'vf_insurance_company_phone_number'],
                    ['label' => 'Payer ID', 'field' => 'vf_payer_id'],
                    ['label' => 'Provider Participating?', 'field' => 'vf_network_status', 'type' => 'select', 'options' => ['Yes' => 'Yes', 'No' => 'No', 'Unknown' => 'Unknown']],
                    ['label' => 'Fee Schedule', 'field' => 'vf_fee_schedule'],
                    ['label' => 'Claim Mailing Address', 'field' => 'vf_insurance_claim_mailing_address', 'type' => 'textarea', 'wide' => true],
                ],
            ],
            [
                'title' => 'Patient Information',
                'description' => 'Capture the patient and subscriber details required to verify benefits.',
                'accent' => '#2563eb',
                'fields' => [
                    ['label' => 'Patient Name', 'field' => 'vf_patient_full_name'],
                    ['label' => 'Patient DOB', 'field' => 'vf_patient_dob', 'type' => 'date'],
                    ['label' => 'Member ID', 'field' => 'vf_patient_identifier'],
                    ['label' => 'Patient ZIP', 'field' => 'vf_patient_zip'],
                    ['label' => 'Subscriber Name', 'field' => 'vf_subscriber_name'],
                    ['label' => 'Subscriber DOB', 'field' => 'vf_subscriber_dob', 'type' => 'date'],
                    ['label' => 'Subscriber ID', 'field' => 'vf_subscriber_id'],
                    ['label' => 'Relationship', 'field' => 'vf_insured_relation'],
                    ['label' => 'COB', 'field' => 'vf_cob', 'type' => 'select', 'options' => ['No COB' => 'No COB', 'Primary' => 'Primary', 'Secondary' => 'Secondary', 'Unknown' => 'Unknown']],
                ],
            ],
            [
                'title' => 'Appointment / Service',
                'description' => 'Keep the service date and requested verification scope visible at the top of the workflow.',
                'accent' => '#f59e0b',
                'fields' => [
                    ['label' => 'Appointment Date', 'field' => 'vf_appointment_date', 'type' => 'date'],
                    ['label' => 'Appointment Time', 'field' => 'vf_appointment_time', 'placeholder' => $appointmentTime],
                    ['label' => 'Service / Procedure', 'field' => 'title', 'placeholder' => $appointment?->appointment_type ?: 'Service being verified'],
                    ['label' => 'Group Name', 'field' => 'vf_group_name'],
                    ['label' => 'Group Number', 'field' => 'vf_group_number'],
                    ['label' => 'Effective Date', 'field' => 'vf_effective_date', 'type' => 'date'],
                    ['label' => 'Plan Renewal Month', 'field' => 'vf_plan_renewal_month'],
                    ['label' => 'Future Termination Date', 'field' => 'vf_future_termination_date', 'type' => 'date'],
                ],
            ],
            [
                'title' => 'Plan Benefits Snapshot',
                'description' => 'High-value benefit fields that usually decide whether the verification can move forward.',
                'accent' => '#7c3aed',
                'fields' => [
                    ['label' => 'Annual Maximum', 'field' => 'vf_annual_maximum', 'type' => 'currency'],
                    ['label' => 'Remaining Maximum', 'field' => 'vf_annual_maximum_remaining', 'type' => 'currency'],
                    ['label' => 'Individual Deductible', 'field' => 'vf_individual_deductible', 'type' => 'currency'],
                    ['label' => 'Individual Deductible Remaining', 'field' => 'vf_individual_deductible_remaining', 'type' => 'currency'],
                    ['label' => 'Family Deductible', 'field' => 'vf_family_deductible', 'type' => 'currency'],
                    ['label' => 'Family Deductible Remaining', 'field' => 'vf_family_deductible_remaining', 'type' => 'currency'],
                    ['label' => 'Waiting Periods', 'field' => 'vf_waiting_periods', 'type' => 'textarea', 'wide' => true],
                    ['label' => 'Plan Provisions', 'field' => 'vf_plan_provisions', 'type' => 'textarea', 'wide' => true],
                ],
            ],
            [
                'title' => 'Verification Information',
                'description' => 'System generated verification context. Only the user comment stays editable.',
                'accent' => '#64748b',
                'fields' => [
                    ['label' => 'Reference', 'value' => $record->reference_number, 'readonly' => true],
                    ['label' => 'Status', 'value' => BillingWorkItem::STATUS_OPTIONS[$record->normalized_status] ?? str($record->normalized_status)->headline()->toString(), 'readonly' => true],
                    ['label' => 'Result', 'value' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$record->outcome_status] ?? str($record->outcome_status)->headline()->toString(), 'readonly' => true],
                    ['label' => 'Priority', 'value' => BillingWorkItem::PRIORITY_OPTIONS[$record->priority] ?? str($record->priority)->headline()->toString(), 'readonly' => true],
                    ['label' => 'Verified By', 'value' => data_get($this->data, 'vf_verified_by') ?: auth()->user()?->name ?: '-', 'readonly' => true],
                    ['label' => 'Verification Date', 'value' => data_get($this->data, 'vf_verification_date') ?: now()->format('Y-m-d'), 'readonly' => true],
                    ['label' => 'User Comment / Notes', 'field' => 'vf_verification_notes', 'type' => 'textarea', 'wide' => true],
                ],
            ],
        ];
    }

    public function getQuestionSections(): array
    {
        return [];
    }

    public function getCoreDetailRows(): array
    {
        return $this->withCompletion([
            'title' => 'Core Eligibility Snapshot',
            'rows' => $this->getBuiltInRowsForSection('core_details'),
        ]);
    }

    public function getCoverageMatrix(): array
    {
        $rows = $this->getBuiltInCoverageRows();

        return [
            'title' => 'Category Coverage',
            'completed' => collect($rows)->filter(fn (array $row): bool => filled(data_get($this->data, $row['percent_field'])) || filled(data_get($this->data, $row['deductible_field'])))->count(),
            'total' => count($rows),
            'rows' => $rows,
        ];
    }

    public function getPlanProvisionRows(): array
    {
        return $this->withCompletion([
            'title' => 'Plan Provisions',
            'rows' => $this->getBuiltInRowsForSection('plan_provisions'),
        ]);
    }

    public function getHistorySection(): array
    {
        return $this->withCompletion([
            'title' => 'History',
            'rows' => $this->getBuiltInRowsForSection('history'),
        ]);
    }

    public function getFrequencyGroups(): array
    {
        return [
            [
                'title' => 'Diagnostic & Preventative',
                'rows' => $this->getBuiltInRowsForSection('frequency_diagnostic_preventative'),
            ],
            [
                'title' => 'Basic',
                'rows' => $this->getBuiltInRowsForSection('frequency_basic'),
            ],
            [
                'title' => 'Major',
                'rows' => $this->getBuiltInRowsForSection('frequency_major'),
            ],
            [
                'title' => 'Orthodontics Benefit',
                'rows' => $this->getBuiltInRowsForSection('frequency_orthodontics_benefit'),
            ],
        ];
    }

    public function getDynamicQuestionsForSection(string $sectionKey): array
    {
        return $this->getQuestionsForSection($sectionKey, false)
            ->map(fn (VerificationFormQuestion $question): array => $this->mapQuestionToRow($question))
            ->all();
    }

    public function getTemplateTwoQuestionsForSection(string $sectionKey): array
    {
        $formType = data_get($this->data, 'vf_form_type', 'full_form');
        $clinicId = $this->record->clinic_id;

        if (! filled($clinicId)) {
            return [];
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_key', 'template_2')
            ->where('section_key', $sectionKey)
            ->where('is_active', true)
            ->where('input_type', '!=', 'frequency_row')
            ->whereIn('form_type', ['both', $formType])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VerificationFormQuestion $question): array => [
                'id' => $question->getKey(),
                'label' => $question->prompt,
                'field' => $this->customQuestionFieldName($question->getKey()),
                'note_field' => $this->customQuestionNoteFieldName($question->getKey()),
                'type' => $question->input_type,
                'help_text' => $question->help_text,
                'placeholder' => $question->placeholder,
                'options' => $question->getSelectOptionValues(),
                'has_note' => $question->has_note,
                'note_label' => $question->note_label ?: 'Note',
                'note_placeholder' => $question->note_placeholder ?: 'Add note',
            ])
            ->all();
    }

    protected function withCompletion(array $section): array
    {
        $section['completed'] = collect($section['rows'])
            ->filter(fn (array $row): bool => filled(data_get($this->data, $row['field'])))
            ->count();
        $section['total'] = count($section['rows']);

        return $section;
    }

    public function getClosingSection(): array
    {
        $rows = collect($this->getBuiltInRowsForSection('verification_information'))
            ->reject(function (array $row): bool {
                return $row['field'] === 'vf_verified_by' && ! $this->canViewVerifiedByField();
            })
            ->values()
            ->all();

        return [
            'title' => 'Verification Information',
            'description' => 'Finish the request with verification notes, representative details, and internal handoff context.',
            'completed' => collect($rows)->filter(fn (array $row): bool => filled(data_get($this->data, $row['field'])))->count(),
            'total' => count($rows),
            'rows' => $rows,
        ];
    }

    public function getServiceHistoryRows(): array
    {
        return $this->getQuestionsForSection('service_history', true)
            ->map(function (VerificationFormQuestion $question): array {
                return [
                    'code' => $question->code ?: 'Custom',
                    'label' => $question->prompt,
                    'field' => $question->is_builtin && filled($question->field_key)
                        ? $question->field_key
                        : $this->customQuestionFieldName($question->id),
                    'type' => $question->input_type,
                    'help_text' => $question->help_text,
                    'placeholder' => $question->placeholder,
                    'is_builtin' => $question->is_builtin,
                ];
            })
            ->all();
    }

    protected function getBuiltInRowsForSection(string $sectionKey): array
    {
        return $this->getQuestionsForSection($sectionKey, true)
            ->map(fn (VerificationFormQuestion $question): array => $this->mapQuestionToRow($question))
            ->all();
    }

    protected function getBuiltInCoverageRows(): array
    {
        return $this->getQuestionsForSection('coverage_matrix', true)
            ->map(function (VerificationFormQuestion $question): array {
                return [
                    'id' => $question->id,
                    'label' => $question->prompt,
                    'deductible_field' => $question->field_key,
                    'percent_field' => $question->secondary_field_key,
                    'type' => $question->input_type,
                    'secondary_type' => $question->secondary_input_type,
                    'help_text' => $question->help_text,
                    'placeholder' => $question->placeholder,
                ];
            })
            ->filter(fn (array $row): bool => filled($row['deductible_field']) && filled($row['percent_field']))
            ->values()
            ->all();
    }

    protected function getQuestionsForSection(string $sectionKey, bool $builtIn): Collection
    {
        $formType = data_get($this->data, 'vf_form_type', 'full_form');
        $clinicId = $this->record->clinic_id;

        if (! filled($clinicId)) {
            return collect();
        }

        return VerificationFormQuestion::query()
            ->where('is_active', true)
            ->where('template_key', 'template_1')
            ->where('section_key', $sectionKey)
            ->where('is_builtin', $builtIn)
            ->where('clinic_id', $clinicId)
            ->whereIn('form_type', ['both', $formType])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    protected function mapQuestionToRow(VerificationFormQuestion $question): array
    {
        $resolvedField = $this->resolveBuiltInField($question);
        $resolvedType = $this->resolveBuiltInInputType($question);

        return [
            'id' => $question->id,
            'label' => $question->prompt,
            'field' => $question->is_builtin && filled($resolvedField)
                ? $resolvedField
                : $this->customQuestionFieldName($question->id),
            'type' => $resolvedType,
            'help_text' => $question->help_text,
            'placeholder' => $question->placeholder,
            'code' => $question->code,
            'options' => $question->getSelectOptionValues(),
            'is_builtin' => $question->is_builtin,
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing([
            'organization',
            'clinic',
            'location',
            'appointment',
            'patient.insurancePolicies',
            'provider.user',
            'insurancePolicy',
            'verificationPlanSnapshots',
            'verificationProfile',
            'verificationFormAnswers.question',
            'verificationCoverageCodes',
        ]);

        $profile = $this->record->verificationProfile;

        if ($profile) {
            foreach ($profile->getAttributes() as $key => $value) {
                if (in_array($key, ['id', 'billing_work_item_id', 'created_at', 'updated_at'], true)) {
                    continue;
                }

                $data['vf_' . $key] = $value;
            }
        }

        $this->record->verificationFormAnswers()
            ->with('question')
            ->get()
            ->each(function ($answer) use (&$data): void {
                if (! $answer->question) {
                    return;
                }

                $data[$this->customQuestionFieldName($answer->verification_form_question_id)] = $this->decodeCustomQuestionAnswerValue(
                    $answer->answer_value,
                    $answer->question->input_type,
                );
                $data[$this->customQuestionNoteFieldName($answer->verification_form_question_id)] = $answer->note_value;
            });

        $this->codeCoverageData = $this->resolveCodeCoverageRows();

        $data = $this->applyAutofillDefaults($data);
        $data['vf_network_status'] = $this->resolveNetworkStatus(
            data_get($data, 'vf_network_status'),
            data_get($data, 'vf_is_provider_in_network')
        );

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $waitingPeriodSummary = $this->waitingPeriodAnswer === 'yes'
            ? $this->formatWaitingPeriodDetails()
            : null;
        $data['vf_waiting_periods'] = $waitingPeriodSummary;
        $this->data['vf_waiting_periods'] = $waitingPeriodSummary;

        $this->verificationFormAnswerData = collect($this->data)
            ->filter(fn ($value, $key): bool => str_starts_with((string) $key, 'custom_question_')
                && ! str_starts_with((string) $key, 'custom_question_note_'))
            ->mapWithKeys(function ($value, $key): array {
                return [(int) str_replace('custom_question_', '', (string) $key) => $value];
            })
            ->all();
        $this->verificationFormAnswerNoteData = collect($this->data)
            ->filter(fn ($value, $key): bool => str_starts_with((string) $key, 'custom_question_note_'))
            ->mapWithKeys(function ($value, $key): array {
                return [(int) str_replace('custom_question_note_', '', (string) $key) => $value];
            })
            ->all();

        [$data, $this->verificationProfileData] = static::splitVerificationProfileData($data);
        $this->verificationCoverageCodeData = $this->normalizeCodeCoverageRows($this->codeCoverageData);

        foreach (array_keys($data) as $key) {
            if (str_starts_with((string) $key, 'custom_question_') || str_starts_with((string) $key, 'context_')) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function initializeWaitingPeriodDetails(): void
    {
        $this->waitingPeriodDetails = $this->defaultWaitingPeriodDetails();
        $savedValue = trim((string) data_get($this->data, 'vf_waiting_periods'));

        if ($savedValue === '') {
            $this->waitingPeriodAnswer = 'no';

            return;
        }

        $this->waitingPeriodAnswer = 'yes';
        foreach (preg_split('/\r\n|\r|\n/', $savedValue) ?: [] as $line) {
            if (! preg_match('/^([^:]+):\s*([^\s|]*)\s*(Months|Years|None)?(?:\s*\|\s*(.*))?$/i', trim($line), $matches)) {
                continue;
            }

            $category = trim($matches[1]);
            $rowIndex = collect($this->waitingPeriodDetails)
                ->search(fn (array $row): bool => strcasecmp($row['category'], $category) === 0);

            if ($rowIndex === false) {
                continue;
            }

            $this->waitingPeriodDetails[$rowIndex]['period'] = trim($matches[2] ?? '');
            $this->waitingPeriodDetails[$rowIndex]['unit'] = ucfirst(strtolower(trim($matches[3] ?? 'Months'))) ?: 'Months';
            $this->waitingPeriodDetails[$rowIndex]['notes'] = trim($matches[4] ?? '');
        }
    }

    protected function defaultWaitingPeriodDetails(): array
    {
        return collect([
            'Basic Restorative',
            'Endodontics',
            'Periodontics',
            'Oral Surgery',
            'Major Restorative',
            'Orthodontics',
        ])->map(fn (string $category): array => [
            'category' => $category,
            'period' => null,
            'unit' => 'Months',
            'notes' => null,
        ])->all();
    }

    protected function formatWaitingPeriodDetails(): string
    {
        $lines = collect($this->waitingPeriodDetails)
            ->filter(fn (array $row): bool => filled($row['period'] ?? null) || filled($row['notes'] ?? null))
            ->map(function (array $row): string {
                $period = filled($row['period'] ?? null) ? trim((string) $row['period']) : '0';
                $unit = filled($row['unit'] ?? null) ? trim((string) $row['unit']) : 'Months';
                $notes = filled($row['notes'] ?? null) ? ' | ' . trim((string) $row['notes']) : '';

                return trim((string) $row['category']) . ': ' . $period . ' ' . $unit . $notes;
            })
            ->values();

        return $lines->isNotEmpty()
            ? $lines->implode(PHP_EOL)
            : 'Waiting period applies.';
    }

    protected function afterSave(): void
    {
        $this->record->verificationProfile()->updateOrCreate([], $this->verificationProfileData);
        $this->syncVerificationFormAnswers();
        $this->syncVerificationCoverageCodes();
        $this->persistClinicResponseAttachments();
        if (! $this->shouldSkipWorkflowSyncOnSave) {
            $this->syncWorkflowStatusFromForm();
        }
        $submission = $this->captureFormSubmissionSnapshot();

        if ($submission) {
            $this->record->recordActivity('form_submitted', 'Verification form submitted and stored in timeline.', [
                'submission_id' => $submission->getKey(),
                'submission_version' => $submission->version,
                'panel' => $this->getSubmissionPanel(),
                'status' => $this->record->normalized_status,
                'outcome_status' => $this->record->outcome_status,
                'priority' => $this->record->priority,
                'filled_profile_fields' => data_get($submission->payload, 'summary.filled_profile_fields', 0),
                'answered_questions' => data_get($submission->payload, 'summary.answered_questions', 0),
            ]);
        }
    }

    protected function syncWorkflowStatusFromForm(): void
    {
        $targetStatus = $this->deriveWorkflowStatusFromForm();

        if (! filled($targetStatus)) {
            return;
        }

        if ($this->record->isDirty('outcome_status')) {
            $this->record->save();
            $this->record->refresh();
        }

        if ($this->record->normalized_status === $targetStatus) {
            return;
        }

        if ($this->record->normalized_status === BillingWorkItem::STATUS_PENDING && $targetStatus === BillingWorkItem::STATUS_IN_PROGRESS) {
            $this->record->startWork(auth()->id());
            $this->record->refresh();

            return;
        }

        $this->record->transitionStatus($targetStatus);
        $this->record->refresh();
    }

    protected function deriveWorkflowStatusFromForm(): string
    {
        $outcomeStatus = (string) ($this->record->outcome_status ?? 'pending');

        if ($this->shouldForceAwaitingClinicResponse($outcomeStatus)) {
            if ($outcomeStatus !== 'info_requested') {
                $this->record->outcome_status = 'info_requested';
            }

            return BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE;
        }

        if ($outcomeStatus === 'audit_required') {
            return BillingWorkItem::STATUS_REVIEW;
        }

        if ($outcomeStatus === 'unable_to_verify' || $this->hasMissingRequiredVerificationData()) {
            if (! in_array($outcomeStatus, ['unable_to_verify', 'info_requested', 'audit_required'], true)) {
                $this->record->outcome_status = 'unable_to_verify';
            }

            return BillingWorkItem::STATUS_INCOMPLETE;
        }

        if ($outcomeStatus === 'pending') {
            $this->record->outcome_status = 'verified';
        }

        return BillingWorkItem::STATUS_DONE;
    }

    protected function shouldForceAwaitingClinicResponse(string $outcomeStatus): bool
    {
        return $outcomeStatus === 'info_requested';
    }

    protected function hasMissingRequiredVerificationData(): bool
    {
        return $this->missingRequiredVerificationFields() !== [];
    }

    protected function missingRequiredVerificationFields(): array
    {
        $clinicId = $this->record->clinic_id;

        if (! filled($clinicId)) {
            return [];
        }

        $formType = data_get($this->data, 'vf_form_type', 'full_form');
        $ignoredFields = [
            'notes',
            'internal_summary',
            'vf_quick_reference',
        ];

        $missingFields = [];

        foreach ($this->normalizeCodeCoverageRows($this->codeCoverageData) as $index => $row) {
            if (! filled($row['code'])) {
                continue;
            }

            if (! filled($row['coverage_status']) && ! filled($row['coverage_percent'])) {
                $missingFields['codeCoverageData.' . $index . '.coverage_status'] = 'Coverage status or percent is required for code ' . $row['code'];
            }
        }

        $questions = VerificationFormQuestion::query()
            ->where('is_active', true)
            ->where('clinic_id', $clinicId)
            ->whereIn('form_type', ['both', $formType])
            ->where('input_type', '!=', 'frequency_row')
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($questions as $question) {
            $requiredFields = [];

            if ($question->is_builtin && $question->section_key === 'coverage_matrix') {
                $requiredFields = array_filter([
                    $this->resolveBuiltInField($question),
                    $question->secondary_field_key,
                ]);
            } else {
                $requiredFields = [
                    $question->is_builtin && filled($this->resolveBuiltInField($question))
                        ? $this->resolveBuiltInField($question)
                        : $this->customQuestionFieldName($question->id),
                ];
            }

            foreach ($requiredFields as $fieldKey) {
                if (in_array($fieldKey, $ignoredFields, true)) {
                    continue;
                }

                if ($fieldKey === 'vf_verified_by' && ! $this->canViewVerifiedByField()) {
                    continue;
                }

                if (blank(data_get($this->data, $fieldKey))) {
                    $missingFields[$fieldKey] = $question->prompt;
                }
            }
        }

        return $missingFields;
    }

    protected function persistClinicResponseAttachments(): void
    {
        if (empty($this->clinicResponseAttachments)) {
            return;
        }

        $this->validate([
            'clinicResponseAttachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        foreach ($this->clinicResponseAttachments as $attachment) {
            if (! $attachment instanceof TemporaryUploadedFile) {
                continue;
            }

            $originalName = $attachment->getClientOriginalName();
            $storedName = now()->format('YmdHis') . '_' . Str::uuid()->toString() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $extension = $attachment->getClientOriginalExtension();
            $finalName = filled($extension) ? "{$storedName}.{$extension}" : $storedName;
            $storedPath = $attachment->storeAs(
                'billing-work-items/' . $this->record->getKey() . '/clinic-response',
                $finalName,
                'local'
            );

            $this->record->attachments()->create([
                'title' => 'Clinic response attachment',
                'file_path' => $storedPath,
                'original_file_name' => $originalName,
                'notes' => trim((string) data_get($this->data, 'notes')) ?: 'Uploaded while responding to a clinic information request.',
            ]);
        }

        $this->clinicResponseAttachments = [];
    }

    public function saveAndTransition(string $targetStatus): void
    {
        abort_unless($this->record->canUserTransitionTo(auth()->user(), $targetStatus), 403);

        if (! $this->validateWorkflowTransitionReason($targetStatus)) {
            return;
        }

        if ($targetStatus === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            $this->data['outcome_status'] = 'info_requested';
            $this->record->outcome_status = 'info_requested';
        }

        $this->save();
        $this->record->refresh();

        if ($targetStatus === BillingWorkItem::STATUS_IN_PROGRESS && $this->record->normalized_status === BillingWorkItem::STATUS_PENDING) {
            $this->record->startWork(auth()->id());
        } elseif (blank($this->record->assigned_to) && auth()->check()) {
            $this->record->assigned_to = auth()->id();
            $this->record->save();
            $this->record->refresh();
        }

        if ($this->record->normalized_status !== $targetStatus) {
            $this->record->transitionStatus($targetStatus);
        }

        $this->record->refresh();

        Notification::make()
            ->title('Verification updated')
            ->body('Status moved to ' . (BillingWorkItem::STATUS_OPTIONS[$this->record->normalized_status] ?? str($this->record->normalized_status)->headline()->toString()) . '.')
            ->success()
            ->send();
    }

    protected function validateWorkflowTransitionReason(string $targetStatus): bool
    {
        $targetStatus = BillingWorkItem::normalizeStatus($targetStatus);

        if ($targetStatus === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            $reason = trim((string) data_get($this->data, 'info_request_reason'));

            if ($reason === '') {
                $this->addError('data.info_request_reason', 'Please explain what information is required from the clinic before sending this request back.');

                Notification::make()
                    ->title('Information request required')
                    ->body('Add the missing-information note before moving this request to Awaiting Clinic Response.')
                    ->danger()
                    ->send();

                return false;
            }

            $this->resetErrorBag('data.info_request_reason');
        }

        if ($targetStatus === BillingWorkItem::STATUS_RETURNED_FOR_REWORK) {
            $reason = trim((string) data_get($this->data, 'return_reason'));

            if ($reason === '') {
                $this->addError('data.return_reason', 'Please describe the correction or quality issue before returning this request for rework.');

                Notification::make()
                    ->title('Rework reason required')
                    ->body('Add the correction note before returning this request for rework.')
                    ->danger()
                    ->send();

                return false;
            }

            $this->resetErrorBag('data.return_reason');
        }

        if ($this->shouldRequireClinicResponseNote($targetStatus)) {
            $responseNote = trim((string) data_get($this->data, 'notes'));

            if ($responseNote === '') {
                $this->addError('data.notes', 'Please explain the clinic response or the update provided before sending the request back to verification.');

                Notification::make()
                    ->title('Clinic response note required')
                    ->body('Add a short response note so the verification team understands what was updated before you resume verification.')
                    ->danger()
                    ->send();

                return false;
            }

            $this->resetErrorBag('data.notes');
        }

        return true;
    }

    protected function shouldRequireClinicResponseNote(string $targetStatus): bool
    {
        return false;
    }

    protected static function splitVerificationProfileData(array $data): array
    {
        $verificationData = [];

        foreach ($data as $key => $value) {
            if (! str_starts_with($key, 'vf_')) {
                continue;
            }

            $verificationData[str_replace('vf_', '', $key)] = $value;
            unset($data[$key]);
        }

        return [$data, $verificationData];
    }

    protected function syncVerificationFormAnswers(): void
    {
        $questionIds = collect(array_keys($this->verificationFormAnswerData))
            ->merge(array_keys($this->verificationFormAnswerNoteData))
            ->map(fn ($questionId): int => (int) $questionId)
            ->unique();

        foreach ($questionIds as $questionId) {
            $answerValue = $this->verificationFormAnswerData[$questionId] ?? null;
            $noteValue = $this->verificationFormAnswerNoteData[$questionId] ?? null;

            if (is_array($answerValue)) {
                $answerValue = array_values(array_filter($answerValue, fn ($value): bool => filled($value)));
                $answerValue = $answerValue === [] ? null : json_encode($answerValue);
            }

            if (
                blank($answerValue) && $answerValue !== '0' && $answerValue !== 0
                && blank($noteValue) && $noteValue !== '0' && $noteValue !== 0
            ) {
                $this->record->verificationFormAnswers()
                    ->where('verification_form_question_id', $questionId)
                    ->delete();

                continue;
            }

            $this->record->verificationFormAnswers()->updateOrCreate(
                ['verification_form_question_id' => $questionId],
                [
                    'answer_value' => $answerValue,
                    'note_value' => $noteValue,
                ],
            );
        }
    }

    public function getCodeCoverageSection(): array
    {
        $rows = $this->normalizeCodeCoverageRows($this->codeCoverageData);

        return [
            'title' => 'Codes',
            'completed' => collect($rows)
                ->filter(fn (array $row): bool => filled($row['coverage_status'] ?? null) || filled($row['coverage_percent'] ?? null))
                ->count(),
            'total' => count($rows),
            'groups' => collect($rows)
                ->groupBy(fn (array $row): string => $row['category'] ?: 'Uncategorized')
                ->map(fn (Collection $categoryRows, string $category): array => [
                    'category' => $category,
                    'completed' => $categoryRows
                        ->filter(fn (array $row): bool => filled($row['coverage_status'] ?? null) || filled($row['coverage_percent'] ?? null))
                        ->count(),
                    'total' => $categoryRows->count(),
                    'rows' => $categoryRows->values()->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    protected function resolveCodeCoverageRows(): array
    {
        $savedRows = $this->record->verificationCoverageCodes()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($savedRows->isNotEmpty()) {
            $rows = $savedRows
                ->map(fn (VerificationCoverageCode $row): array => [
                    'id' => $row->getKey(),
                    'code_system' => $row->code_system ?: 'ada',
                    'category' => $row->category,
                    'code' => $row->code,
                    'description' => $row->description,
                    'coverage_status' => $row->coverage_status,
                    'coverage_percent' => $row->coverage_percent,
                    'frequency' => $row->frequency,
                    'age_limit' => $row->age_limit,
                    'waiting_period' => $row->waiting_period,
                    'service_history' => $row->service_history,
                    'pre_auth_required' => $row->pre_auth_required,
                    'pre_auth_details' => $row->pre_auth_details,
                    'downgrade_applies' => $row->downgrade_applies,
                    'downgrade_to' => $row->downgrade_to,
                    'payment_guideline' => $row->payment_guideline,
                    'notes' => $row->notes,
                    'sort_order' => $row->sort_order,
                ])
                ->values()
                ->all();

            return $this->mergeConfiguredCodeCoverageRows($rows);
        }

        return collect($this->configuredCodeCoverageTemplate())
            ->values()
            ->map(fn (array $row, int $index): array => $this->makeDefaultCodeCoverageRow($row, $index + 1))
            ->all();
    }

    protected function configuredCodeCoverageTemplate(): array
    {
        return $this->templateTwoFrequencyQuestionRows();
    }

    protected function templateTwoFrequencyQuestionRows(): array
    {
        $clinicId = $this->record->clinic_id;
        $formType = data_get($this->data, 'vf_form_type', 'full_form');

        if (! filled($clinicId)) {
            return [];
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_key', 'template_2')
            ->whereIn('section_key', [
                'template_2_frequency_general',
                'template_2_frequency_basic',
                'template_2_frequency_major',
                'template_2_frequency_orthodontics',
            ])
            ->where('input_type', 'frequency_row')
            ->where('is_active', true)
            ->whereIn('form_type', ['both', $formType])
            ->orderByRaw("FIELD(section_key, 'template_2_frequency_general', 'template_2_frequency_basic', 'template_2_frequency_major', 'template_2_frequency_orthodontics')")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VerificationFormQuestion $question): array => [
                'category' => VerificationFormQuestion::templateTwoFrequencyCategory($question->section_key),
                'code' => $question->code ?: '',
                'description' => $question->prompt,
                'frequency_response_mode' => $question->frequency_response_mode ?: 'current',
                'frequency_response_fields' => $question->frequency_response_fields ?: VerificationFormQuestion::defaultFrequencyResponseFields($question->frequency_response_mode ?: 'current'),
            ])
            ->all();
    }

    protected function mergeConfiguredCodeCoverageRows(array $rows): array
    {
        $configuredRowsBySignature = collect($this->configuredCodeCoverageTemplate())
            ->mapWithKeys(fn (array $row): array => [$this->codeCoverageRowSignature($row) => $row]);

        $rows = collect($rows)
            ->filter(fn (array $row): bool => $configuredRowsBySignature->has($this->codeCoverageRowSignature($row)))
            ->map(function (array $row) use ($configuredRowsBySignature): array {
                $defaultRow = $configuredRowsBySignature->get($this->codeCoverageRowSignature($row), []);

                $row['frequency_response_mode'] = $defaultRow['frequency_response_mode'] ?? 'current';
                $row['frequency_response_fields'] = $defaultRow['frequency_response_fields'] ?? VerificationFormQuestion::defaultFrequencyResponseFields($row['frequency_response_mode']);

                return $row;
            })
            ->all();

        $existingSignatures = collect($rows)
            ->map(fn (array $row): string => $this->codeCoverageRowSignature($row))
            ->all();

        foreach ($this->configuredCodeCoverageTemplate() as $defaultRow) {
            $signature = $this->codeCoverageRowSignature($defaultRow);

            if (in_array($signature, $existingSignatures, true)) {
                continue;
            }

            $rows[] = $this->makeDefaultCodeCoverageRow($defaultRow, count($rows) + 1);
            $existingSignatures[] = $signature;
        }

        return $rows;
    }

    protected function makeDefaultCodeCoverageRow(array $row, int $sortOrder): array
    {
        return [
            'id' => null,
            'code_system' => 'ada',
            'category' => $row['category'],
            'code' => $row['code'],
            'description' => $row['description'],
            'frequency_response_mode' => $row['frequency_response_mode'] ?? 'current',
            'frequency_response_fields' => $row['frequency_response_fields'] ?? VerificationFormQuestion::defaultFrequencyResponseFields($row['frequency_response_mode'] ?? 'current'),
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
            'sort_order' => $sortOrder,
        ];
    }

    protected function codeCoverageRowSignature(array $row): string
    {
        return implode('|', [
            Str::lower(trim((string) ($row['category'] ?? ''))),
            Str::lower(trim((string) ($row['code'] ?? ''))),
            Str::lower(trim((string) ($row['description'] ?? ''))),
        ]);
    }

    protected function normalizeCodeCoverageRows(array $rows): array
    {
        return collect($rows)
            ->values()
            ->map(function (array $row, int $index): array {
                $coverageStatus = trim((string) ($row['coverage_status'] ?? ''));
                $coveragePercent = $row['coverage_percent'] ?? null;
                $isNotCovered = $coverageStatus === 'Not Covered'
                    || ((string) $coveragePercent !== '' && is_numeric($coveragePercent) && (float) $coveragePercent <= 0.0);

                if ($isNotCovered) {
                    $coverageStatus = 'Not Covered';
                    $coveragePercent = $coveragePercent === null || $coveragePercent === '' ? 0 : $coveragePercent;
                    $row['frequency'] = null;
                    $row['age_limit'] = null;
                    $row['waiting_period'] = null;
                    $row['pre_auth_required'] = 'No';
                    $row['pre_auth_details'] = null;
                    $row['downgrade_applies'] = 'No';
                    $row['downgrade_to'] = null;
                    $row['payment_guideline'] = null;
                }

                if (($row['pre_auth_required'] ?? null) !== 'Yes') {
                    $row['pre_auth_details'] = null;
                }

                if (($row['downgrade_applies'] ?? null) !== 'Yes') {
                    $row['downgrade_to'] = null;
                }

                return [
                    'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                    'code_system' => filled($row['code_system'] ?? null) ? (string) $row['code_system'] : 'ada',
                    'category' => trim((string) ($row['category'] ?? '')),
                    'code' => strtoupper(trim((string) ($row['code'] ?? ''))),
                    'description' => trim((string) ($row['description'] ?? '')),
                    'frequency_response_mode' => $row['frequency_response_mode'] ?? 'current',
                    'frequency_response_fields' => $row['frequency_response_fields'] ?? VerificationFormQuestion::defaultFrequencyResponseFields($row['frequency_response_mode'] ?? 'current'),
                    'coverage_status' => $coverageStatus ?: null,
                    'coverage_percent' => $coveragePercent === '' ? null : $coveragePercent,
                    'frequency' => filled($row['frequency'] ?? null) ? trim((string) $row['frequency']) : null,
                    'age_limit' => filled($row['age_limit'] ?? null) ? trim((string) $row['age_limit']) : null,
                    'waiting_period' => filled($row['waiting_period'] ?? null) ? trim((string) $row['waiting_period']) : null,
                    'service_history' => filled($row['service_history'] ?? null) ? trim((string) $row['service_history']) : null,
                    'pre_auth_required' => filled($row['pre_auth_required'] ?? null) ? (string) $row['pre_auth_required'] : null,
                    'pre_auth_details' => filled($row['pre_auth_details'] ?? null) ? trim((string) $row['pre_auth_details']) : null,
                    'downgrade_applies' => filled($row['downgrade_applies'] ?? null) ? (string) $row['downgrade_applies'] : null,
                    'downgrade_to' => filled($row['downgrade_to'] ?? null) ? trim((string) $row['downgrade_to']) : null,
                    'payment_guideline' => filled($row['payment_guideline'] ?? null) ? trim((string) $row['payment_guideline']) : null,
                    'notes' => filled($row['notes'] ?? null) ? trim((string) $row['notes']) : null,
                    'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : $index + 1,
                ];
            })
            ->filter(fn (array $row): bool => filled($row['category']) || filled($row['code']) || filled($row['description']))
            ->values()
            ->all();
    }

    protected function syncVerificationCoverageCodes(): void
    {
        $rows = collect($this->verificationCoverageCodeData);
        $keptIds = [];

        foreach ($rows as $index => $row) {
            $payload = [
                'code_system' => $row['code_system'] ?: 'ada',
                'category' => $row['category'],
                'code' => $row['code'],
                'description' => $row['description'],
                'coverage_status' => $row['coverage_status'],
                'coverage_percent' => $row['coverage_percent'],
                'frequency' => $row['frequency'],
                'age_limit' => $row['age_limit'],
                'waiting_period' => $row['waiting_period'],
                'service_history' => $row['service_history'],
                'pre_auth_required' => $row['pre_auth_required'],
                'pre_auth_details' => $row['pre_auth_details'],
                'downgrade_applies' => $row['downgrade_applies'],
                'downgrade_to' => $row['downgrade_to'],
                'payment_guideline' => $row['payment_guideline'],
                'notes' => $row['notes'],
                'sort_order' => $index + 1,
            ];

            if (filled($row['id'] ?? null)) {
                $coverageCode = $this->record->verificationCoverageCodes()->find($row['id']);

                if ($coverageCode) {
                    $coverageCode->update($payload);
                    $keptIds[] = $coverageCode->getKey();
                }

                continue;
            }

            $coverageCode = $this->record->verificationCoverageCodes()->create($payload);
            $keptIds[] = $coverageCode->getKey();
        }

        $this->record->verificationCoverageCodes()
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
            ->delete();

        $this->codeCoverageData = $this->mergeConfiguredCodeCoverageRows($this->record->verificationCoverageCodes()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VerificationCoverageCode $row): array => [
                'id' => $row->getKey(),
                'code_system' => $row->code_system ?: 'ada',
                'category' => $row->category,
                'code' => $row->code,
                'description' => $row->description,
                'coverage_status' => $row->coverage_status,
                'coverage_percent' => $row->coverage_percent,
                'frequency' => $row->frequency,
                'age_limit' => $row->age_limit,
                'waiting_period' => $row->waiting_period,
                'service_history' => $row->service_history,
                'pre_auth_required' => $row->pre_auth_required,
                'pre_auth_details' => $row->pre_auth_details,
                'downgrade_applies' => $row->downgrade_applies,
                'downgrade_to' => $row->downgrade_to,
                'payment_guideline' => $row->payment_guideline,
                'notes' => $row->notes,
                'sort_order' => $row->sort_order,
            ])
            ->values()
            ->all());
    }

    protected function customQuestionFieldName(int $questionId): string
    {
        return 'custom_question_' . $questionId;
    }

    protected function customQuestionNoteFieldName(int $questionId): string
    {
        return 'custom_question_note_' . $questionId;
    }

    protected function getSubmissionPanel(): string
    {
        return 'verification';
    }

    protected function captureFormSubmissionSnapshot(): ?VerificationFormSubmission
    {
        $this->record->load([
            'verificationProfile',
            'verificationFormAnswers.question',
            'verificationCoverageCodes',
            'assignedTo',
            'reviewedBy',
            'closedBy',
        ]);

        $profile = $this->record->verificationProfile;
        $answers = $this->record->verificationFormAnswers;
        $coverageCodes = $this->record->verificationCoverageCodes;

        $profileAttributes = $profile?->getAttributes() ?? [];
        unset(
            $profileAttributes['id'],
            $profileAttributes['billing_work_item_id'],
            $profileAttributes['created_at'],
            $profileAttributes['updated_at']
        );

        $answerPayload = $answers
            ->map(fn ($answer): array => [
                'question_id' => $answer->verification_form_question_id,
                'code' => $answer->question?->code,
                'prompt' => $answer->question?->prompt,
                'answer_value' => $this->formatCustomQuestionAnswerValue(
                    $answer->answer_value,
                    $answer->question?->input_type,
                ),
                'note_value' => $answer->note_value,
            ])
            ->values()
            ->all();

        $coverageCodePayload = $coverageCodes
            ->sortBy('sort_order')
            ->map(fn (VerificationCoverageCode $row): array => [
                'category' => $row->category,
                'code_system' => $row->code_system,
                'code' => $row->code,
                'description' => $row->description,
                'coverage_status' => $row->coverage_status,
                'coverage_percent' => $row->coverage_percent,
                'frequency' => $row->frequency,
                'age_limit' => $row->age_limit,
                'waiting_period' => $row->waiting_period,
                'service_history' => $row->service_history,
                'pre_auth_required' => $row->pre_auth_required,
                'pre_auth_details' => $row->pre_auth_details,
                'downgrade_applies' => $row->downgrade_applies,
                'downgrade_to' => $row->downgrade_to,
                'payment_guideline' => $row->payment_guideline,
                'notes' => $row->notes,
            ])
            ->values()
            ->all();

        $filledProfileFields = collect($profileAttributes)
            ->filter(function ($value): bool {
                if ($value === 0 || $value === 0.0 || $value === '0') {
                    return true;
                }

                return filled($value);
            })
            ->count();

        $answeredQuestions = collect($answerPayload)
            ->filter(function (array $row): bool {
                $value = $row['answer_value'] ?? null;
                $note = $row['note_value'] ?? null;

                if ($value === 0 || $value === 0.0 || $value === '0') {
                    return true;
                }

                return filled($value) || filled($note);
            })
            ->count();

        $answeredCoverageCodes = collect($coverageCodePayload)
            ->filter(fn (array $row): bool => filled($row['code']) && (filled($row['coverage_status']) || filled($row['coverage_percent'])))
            ->count();

        $hasMeaningfulPayload = $filledProfileFields > 0
            || $answeredQuestions > 0
            || $answeredCoverageCodes > 0
            || filled($this->record->notes)
            || filled($this->record->internal_summary);

        if (! $hasMeaningfulPayload) {
            return null;
        }

        $nextVersion = ((int) $this->record->formSubmissions()->max('version')) + 1;

        return $this->record->formSubmissions()->create([
            'user_id' => auth()->id(),
            'panel' => $this->getSubmissionPanel(),
            'status' => $this->record->normalized_status,
            'outcome_status' => $this->record->outcome_status,
            'priority' => $this->record->priority,
            'version' => $nextVersion,
            'payload' => [
                'summary' => [
                    'filled_profile_fields' => $filledProfileFields,
                    'answered_questions' => $answeredQuestions,
                    'answered_coverage_codes' => $answeredCoverageCodes,
                ],
                'work_item' => [
                    'status' => $this->record->normalized_status,
                    'outcome_status' => $this->record->outcome_status,
                    'priority' => $this->record->priority,
                    'assigned_to' => $this->record->assignedTo?->name,
                    'reviewed_by' => $this->record->reviewedBy?->name,
                    'closed_by' => $this->record->closedBy?->name,
                    'notes' => $this->record->notes,
                    'internal_summary' => $this->record->internal_summary,
                ],
                'verification_profile' => $profileAttributes,
                'coverage_codes' => $coverageCodePayload,
                'answers' => $answerPayload,
            ],
        ]);
    }

    protected function resolveBuiltInField(VerificationFormQuestion $question): ?string
    {
        if (! $question->is_builtin) {
            return $question->field_key;
        }

        return match ($question->prompt) {
            'Clinic name' => 'context_clinic_name',
            default => $question->field_key,
        };
    }

    protected function resolveBuiltInInputType(VerificationFormQuestion $question): string
    {
        if (! $question->is_builtin) {
            return $question->input_type;
        }

        return match ($question->prompt) {
            'Is the provider in network with this plan?' => 'yes_no',
            default => $question->input_type,
        };
    }

    protected function decodeCustomQuestionAnswerValue(mixed $value, ?string $inputType): mixed
    {
        if ($inputType !== 'multi_select') {
            return $value;
        }

        if (is_array($value)) {
            return $value;
        }

        if (blank($value)) {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        if (is_array($decoded)) {
            return array_values($decoded);
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    protected function formatCustomQuestionAnswerValue(mixed $value, ?string $inputType): mixed
    {
        if ($inputType !== 'multi_select') {
            return $value;
        }

        $decoded = $this->decodeCustomQuestionAnswerValue($value, $inputType);

        if (! is_array($decoded)) {
            return $decoded;
        }

        return implode(', ', array_values(array_filter($decoded, fn ($option): bool => filled($option))));
    }

    protected function applyAutofillDefaults(array $data): array
    {
        $record = $this->record;
        $profile = $record->verificationProfile;
        $patient = $record->patient;
        $provider = $record->provider;
        $clinic = $record->clinic;
        $location = $record->location;
        $policy = $record->insurancePolicy ?: $patient?->insurancePolicies?->sortByDesc('coverage_priority')->first();
        $primaryPlan = $record->verificationPlanSnapshots
            ->sortBy(fn ($plan) => array_search($plan->plan_priority, ['primary', 'secondary', 'tertiary'], true))
            ->first();
        $verifierName = auth()->user()?->name;
        $clinicDisplayName = $clinic?->clinic_name ?: $location?->location_name ?: $record->organization?->name;

        $defaults = [
            'context_clinic_name' => $clinicDisplayName,
            'vf_form_type' => $profile?->form_type ?: 'full_form',
            'vf_patient_full_name' => $profile?->patient_full_name ?: $patient?->full_name,
            'vf_patient_dob' => $this->formatDateForInput($profile?->patient_dob ?: $patient?->dob),
            'vf_patient_identifier' => $profile?->patient_identifier ?: $policy?->member_id ?: $primaryPlan?->member_id ?: $patient?->insurance_number,
            'vf_patient_zip' => $profile?->patient_zip,
            'vf_appointment_date' => $this->formatDateForInput($profile?->appointment_date ?: $record->appointment?->appointment_date),
            'vf_appointment_time' => $profile?->appointment_time ?: $record->appointment?->start_time,
            'vf_subscriber_name' => $profile?->subscriber_name ?: $policy?->subscriber_name ?: $primaryPlan?->subscriber_name,
            'vf_subscriber_dob' => $this->formatDateForInput($profile?->subscriber_dob ?: $policy?->subscriber_dob ?: $primaryPlan?->subscriber_dob),
            'vf_subscriber_id' => $profile?->subscriber_id ?: $primaryPlan?->member_id ?: $policy?->member_id ?: $patient?->insurance_number,
            'vf_insured_relation' => $profile?->insured_relation ?: $policy?->subscriber_relationship,
            'vf_insurance_provider_name' => $profile?->insurance_provider_name ?: $policy?->insurance_company ?: $primaryPlan?->payer_name ?: $patient?->insurance_provider,
            'vf_insurance_claim_mailing_address' => $profile?->insurance_claim_mailing_address ?: $policy?->claims_address,
            'vf_insurance_company_phone_number' => $profile?->insurance_company_phone_number ?: $policy?->payer_phone,
            'vf_payer_id' => $profile?->payer_id,
            'vf_effective_date' => $this->formatDateForInput($profile?->effective_date ?: $policy?->effective_date),
            'vf_group_name' => $profile?->group_name ?: $policy?->subscriber_employer ?: $policy?->plan_name,
            'vf_group_number' => $profile?->group_number ?: $policy?->group_number ?: $primaryPlan?->group_number,
            'vf_plan_renewal_month' => $profile?->plan_renewal_month,
            'vf_future_termination_date' => $this->formatDateForInput($profile?->future_termination_date ?: $policy?->termination_date),
            'vf_fee_schedule' => $profile?->fee_schedule,
            'vf_network_status' => $this->resolveNetworkStatus($profile?->network_status, $profile?->is_provider_in_network),
            'vf_verification_date' => $this->formatDateForInput(
                $profile?->verification_date
                ?: $record->started_at
                ?: $record->updated_at
                ?: $record->created_at
                ?: now()
            ),
            'vf_verified_by' => $profile?->verified_by ?: $verifierName,
            'vf_quick_reference' => $profile?->quick_reference ?: $this->buildQuickReference($record, $patient, $policy, $primaryPlan, $provider),
            'internal_summary' => $record->internal_summary ?: $this->buildInternalSummary($record, $patient, $clinicDisplayName),
        ];

        foreach ($defaults as $key => $value) {
            if (blank(data_get($data, $key)) && filled($value)) {
                $data[$key] = $value;
            }
        }

        if (
            filled($verifierName)
            && filled($clinicDisplayName)
            && data_get($data, 'vf_verified_by') === $clinicDisplayName
        ) {
            $data['vf_verified_by'] = $verifierName;
        }

        return $data;
    }

    protected function formatDateForInput($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof \Illuminate\Support\Carbon || $value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveNetworkStatus(?string $networkStatus, $providerInNetwork): ?string
    {
        if (filled($networkStatus)) {
            return str_contains(strtolower($networkStatus), 'out') ? 'No' : (str_contains(strtolower($networkStatus), 'in') ? 'Yes' : $networkStatus);
        }

        if (is_bool($providerInNetwork)) {
            return $providerInNetwork ? 'Yes' : 'No';
        }

        return null;
    }

    protected function canViewVerifiedByField(): bool
    {
        return $this->canManageQueueControl();
    }

    public function getFeeScheduleReference(): ?array
    {
        $carrierName = (string) (data_get($this->data, 'vf_insurance_provider_name') ?: '');
        $payerId = (string) (data_get($this->data, 'vf_payer_id') ?: '');

        $profile = InsuranceCarrierNetworkProfile::resolveFor($carrierName, $payerId);

        if (! $profile || ! $profile->hasFeeScheduleReference() || blank($profile->feeScheduleReferenceUrl())) {
            return null;
        }

        return [
            'name' => $profile->feeScheduleReferenceName() ?: 'Saved fee schedule reference',
            'url' => $profile->feeScheduleReferenceUrl(),
        ];
    }

    protected function buildQuickReference(BillingWorkItem $record, $patient, $policy, $primaryPlan, $provider): ?string
    {
        $parts = collect([
            $record->reference_number,
            $patient?->full_name,
            $policy?->insurance_company ?: $primaryPlan?->payer_name,
            $policy?->member_id ?: $primaryPlan?->member_id ?: $patient?->insurance_number,
            optional($record->appointment?->appointment_date)->format('M d, Y'),
            $provider?->display_name,
        ])->filter(fn ($value): bool => filled($value));

        return $parts->isNotEmpty() ? $parts->implode(' | ') : null;
    }

    protected function buildInternalSummary(BillingWorkItem $record, $patient, ?string $clinicDisplayName): ?string
    {
        $segments = collect([
            filled($patient?->full_name) ? 'Verification request for ' . $patient->full_name : null,
            filled($clinicDisplayName) ? 'Clinic: ' . $clinicDisplayName : null,
            optional($record->appointment?->appointment_date)->format('M d, Y') ? 'Appointment: ' . optional($record->appointment?->appointment_date)->format('M d, Y') : null,
            $record->priority ? 'Priority: ' . (BillingWorkItem::PRIORITY_OPTIONS[$record->priority] ?? str($record->priority)->headline()->toString()) : null,
        ])->filter(fn ($value): bool => filled($value));

        return $segments->isNotEmpty() ? $segments->implode(' | ') : null;
    }
}
