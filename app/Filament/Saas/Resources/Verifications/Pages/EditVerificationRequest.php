<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Actions\Verification\RefreshVerificationTemplateAction;
use App\Actions\Verification\SaveVerificationAnswerAction;
use App\Actions\Verification\TakeVerificationOwnershipAction;
use App\Filament\Saas\Resources\Verifications\Pages\Concerns\InteractsWithVerificationWorkbench;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\BillingWorkItem;
use App\Models\InsuranceCarrier;
use App\Models\InsuranceCarrierNetworkProfile;
use App\Models\User;
use App\Models\VerificationCoverageCode;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationFormSubmission;
use App\Services\Verification\StatusService;
use App\Services\Verification\VerificationAuditService;
use App\Services\Verification\WorkflowService;
use App\Support\VerificationAutoAssigner;
use App\Support\VerificationTemplateVersionService;
use App\Support\WorkContext\Providers\VerificationContextProvider;
use App\Support\WorkContext\WorkContext;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class EditVerificationRequest extends EditRecord
{
    use InteractsWithVerificationWorkbench;
    use WithFileUploads;

    protected static string $resource = VerificationRequestResource::class;

    protected string $view = 'filament.saas.resources.verifications.pages.edit-verification-request';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected array $verificationProfileData = [];

    protected array $verificationFormAnswerData = [];

    protected array $verificationFormAnswerNoteData = [];

    protected array $verificationCoverageCodeData = [];

    protected array $templateThreeFieldVisibilityCache = [];

    public array $codeCoverageData = [];

    public array $clinicResponseAttachments = [];

    public bool $auditReady = false;

    public ?string $returnToQueue = null;

    public bool $focusMode = false;

    public bool $openInfoRequestModalOnLoad = false;

    public bool $showInfoRequestModal = false;

    public bool $showAddInsuranceModal = false;

    public array $newInsuranceCarrier = [];

    public string $formTemplate = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;

    public string $waitingPeriodAnswer = 'no';

    public array $waitingPeriodDetails = [];

    protected bool $shouldSkipWorkflowSyncOnSave = false;

    protected bool $shouldCaptureSubmissionOnSave = true;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        if ($this->record->normalized_status === BillingWorkItem::STATUS_DONE) {
            $resource = static::getResource();
            $this->redirect($resource::getUrl('view', [
                'record' => $this->record,
                'return' => request()->query('return'),
            ]));

            return;
        }

        parent::mount($record);

        $this->record = app(VerificationTemplateVersionService::class)->attachSnapshotToWorkItem($this->record);
        $this->returnToQueue = $this->resolveReturnToQueueUrl(request()->query('return'));

        $this->record->recordActivity('verification_console_opened', 'Verification console opened.', [
            'panel' => $this->getSubmissionPanel(),
            'user_name' => auth()->user()?->name,
            'status' => $this->record->normalized_status,
        ]);

        $this->openInfoRequestModalOnLoad = request()->boolean('request_clinic')
            && ($this->canRequestClinicInfo()
                || $this->record->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);
        $this->showInfoRequestModal = $this->openInfoRequestModalOnLoad;

        if ($this->showInfoRequestModal) {
            $this->data['info_request_reason'] = '';
        }

        $this->formTemplate = VerificationFormQuestion::defaultTemplateKey();

        $this->initializeWaitingPeriodDetails();
        $this->auditReady = $this->missingRequiredVerificationFields() === [];
    }

    public function selectFormTemplate(string $template): void
    {
        $template = VerificationFormQuestion::normalizeTemplateKey($template);

        abort_unless(array_key_exists($template, VerificationFormQuestion::ACTIVE_TEMPLATE_OPTIONS), 404);

        $this->formTemplate = $template;
    }

    public function getTitle(): string
    {
        return 'Verification Form';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getViewUrl(): string
    {
        return VerificationRequestResource::getUrl('view', ['record' => $this->record]);
    }

    public function getIndexUrl(): string
    {
        return $this->returnToQueue ?? $this->getDefaultIndexUrl();
    }

    public function getClinicResponseUrl(): ?string
    {
        return null;
    }

    public function getCorrectionFocus(): ?array
    {
        if (! in_array($this->record->normalized_status, [
            BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
            BillingWorkItem::STATUS_IN_PROGRESS,
            BillingWorkItem::STATUS_REVIEW,
        ], true)) {
            return null;
        }

        $activity = $this->record->activities()
            ->where('activity_type', 'clinic_correction_requested')
            ->latest('id')
            ->first();

        if (! $activity) {
            return null;
        }

        return [
            'reason' => data_get($activity->meta, 'reason'),
            'requested_by' => data_get($activity->meta, 'requested_by'),
            'requested_fields' => collect(data_get($activity->meta, 'requested_fields', []))->values()->all(),
            'baseline_submission_id' => data_get($activity->meta, 'baseline_submission_id'),
            'baseline_submission_version' => data_get($activity->meta, 'baseline_submission_version'),
            'requested_at' => optional($activity->created_at)->format('M d, Y h:i A'),
        ];
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
        return $this->auditReady ? 'Audit' : 'Save';
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

    public function enterFocusMode(): void
    {
        $this->focusMode = true;
    }

    public function exitFocusMode(): void
    {
        $this->focusMode = false;
    }

    public function getFocusModeSaveState(): array
    {
        return $this->auditReady
            ? ['label' => 'Saved', 'status' => 'success']
            : ['label' => 'Unsaved Changes', 'status' => 'warning'];
    }

    public function getWorkContextEngine(
        array $quickReference,
        iterable $summaryCards,
        iterable $attachments,
        iterable $activityTimeline,
        ?string $copyText = null,
    ): WorkContext {
        return (new VerificationContextProvider(
            record: $this->record,
            quickReference: $quickReference,
            summaryCards: $summaryCards,
            attachments: $attachments,
            timeline: $activityTimeline,
            copyText: $copyText,
        ))->context();
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

        $this->record = app(TakeVerificationOwnershipAction::class)->execute($this->record, $user);

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

        if (
            $name === 'data.vf_insured_relation'
            || in_array($name, ['data.vf_patient_full_name', 'data.vf_patient_dob'], true)
        ) {
            $this->syncSubscriberFieldsForSelfRelationship();
        }
    }

    protected function syncSubscriberFieldsForSelfRelationship(): void
    {
        $relationship = strtolower(trim((string) data_get($this->data, 'vf_insured_relation')));

        if ($relationship !== 'self') {
            return;
        }

        $this->data['vf_subscriber_name'] = data_get($this->data, 'vf_patient_full_name');
        $this->data['vf_subscriber_dob'] = data_get($this->data, 'vf_patient_dob');
    }

    protected function normalizeSelfSubscriberFields(array $data): array
    {
        $relationship = strtolower(trim((string) data_get($data, 'vf_insured_relation')));

        if ($relationship !== 'self') {
            return $data;
        }

        $data['vf_subscriber_name'] = data_get($data, 'vf_patient_full_name');
        $data['vf_subscriber_dob'] = data_get($data, 'vf_patient_dob');

        return $data;
    }

    protected function verificationDateFields(): array
    {
        return [
            'vf_patient_dob',
            'vf_subscriber_dob',
            'vf_appointment_date',
            'vf_effective_date',
            'vf_future_termination_date',
            'vf_verification_date',
        ];
    }

    protected function normalizeVerificationDateFieldsForDisplay(array $data): array
    {
        $format = 'Y-m-d';

        foreach ($this->verificationDateFields() as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->formatDateValue($data[$field], $format);
        }

        return $data;
    }

    protected function normalizeVerificationDateFieldsForStorage(array $data): array
    {
        foreach ($this->verificationDateFields() as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = $this->formatDateValue($data[$field], 'Y-m-d');
        }

        return $data;
    }

    public function getInsuranceCarrierOptions(): array
    {
        $clinicId = filled($this->record->clinic_id) ? (int) $this->record->clinic_id : null;

        return Cache::remember(
            'verification.insurance-carrier-options.'.($clinicId ?: 'global'),
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

        Cache::forget('verification.insurance-carrier-options.'.($this->record->clinic_id ?: 'global'));
        $this->data['vf_insurance_provider_name'] = $carrier->insurance_name;
        $this->applySelectedInsuranceCarrier($carrier->insurance_name);
        $this->closeAddInsuranceModal();

        Notification::make()
            ->title($carrier->wasRecentlyCreated ? 'Insurance added' : 'Insurance selected')
            ->body($carrier->insurance_name.' is now selected for this verification.')
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
        $user = auth()->user();

        if (! $user?->canWorkVerificationQueue()) {
            return false;
        }

        $status = $this->record->normalized_status;

        if ($status === BillingWorkItem::STATUS_DONE) {
            return false;
        }

        if ($status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            return $user->canManageVerificationQueue()
                || (filled($this->record->assigned_to) && (int) $this->record->assigned_to === (int) $user->getAuthIdentifier());
        }

        return in_array($status, [
            BillingWorkItem::STATUS_PENDING,
            BillingWorkItem::STATUS_IN_PROGRESS,
            BillingWorkItem::STATUS_REVIEW,
            BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
            BillingWorkItem::STATUS_INCOMPLETE,
        ], true);
    }

    public function openInfoRequestModal(): void
    {
        abort_unless(
            $this->canRequestClinicInfo()
                || $this->record->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
            403
        );

        $this->data['info_request_reason'] = '';
        $this->resetErrorBag('data.info_request_reason');
        $this->showInfoRequestModal = true;
    }

    public function closeInfoRequestModal(): void
    {
        $this->data['info_request_reason'] = '';
        $this->resetErrorBag('data.info_request_reason');
        $this->showInfoRequestModal = false;
    }

    public function canRefreshVerificationTemplate(): bool
    {
        return app(RefreshVerificationTemplateAction::class)->canRefresh($this->record, auth()->user());
    }

    public function refreshVerificationTemplate(): void
    {
        abort_unless($this->canRefreshVerificationTemplate(), 403);

        $refreshTemplate = app(RefreshVerificationTemplateAction::class);

        if ($refreshTemplate->isAlreadyCurrent($this->record)) {
            Notification::make()
                ->title('Template already current')
                ->body('This request is already using the latest published clinic template.')
                ->info()
                ->send();

            return;
        }

        $this->record = $refreshTemplate->execute($this->record);

        $this->templateThreeFieldVisibilityCache = [];
        $this->codeCoverageData = $this->resolveCodeCoverageRows();
        $this->refreshVerificationFormStateFromRecord();
        $this->auditReady = false;

        Notification::make()
            ->title('Template refreshed')
            ->body('The request now uses the latest clinic template. Workflow status was preserved.')
            ->success()
            ->send();
    }

    public function auditVerification(): void
    {
        abort_unless($this->canSubmitForm(), 403);

        $this->resetErrorBag();

        if ($this->formTemplate !== 'template_3') {
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
        }

        // Audit must validate the exact answers currently visible to the user.
        // Persist first so the audit service, timeline, and later PDF snapshot all
        // read the same state instead of the previous saved draft.
        $this->shouldSkipWorkflowSyncOnSave = true;
        if ($this->formTemplate === 'template_3') {
            $this->persistTemplateThreeWithoutResourceValidation();
        } else {
            $this->save(false, false);
        }
        $this->shouldSkipWorkflowSyncOnSave = false;
        $this->refreshVerificationFormStateFromRecord();

        $missingFields = $this->missingRequiredVerificationFields();

        if ($missingFields !== []) {
            foreach ($missingFields as $fieldKey => $label) {
                $isCodeField = str_starts_with((string) $fieldKey, 'codeCoverageData.');

                $this->addError(
                    $isCodeField ? $fieldKey : 'data.'.$fieldKey,
                    $isCodeField ? $label.'.' : $label.' is required before saving.'
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

        $statusService = app(StatusService::class);

        // The form's user-facing flow is Save -> Audit. Assigned specialists should
        // not need a separate hidden Start Work action before submitting a complete form.
        if ($statusService->canShowStartWork($this->record, auth()->user())) {
            $this->record = app(WorkflowService::class)->start($this->record, auth()->user());
        }

        if ($statusService->canShowSendToReview($this->record, auth()->user())) {
            $this->record = app(WorkflowService::class)->submitForQa($this->record, auth()->user());

            Notification::make()
                ->title('Sent to Audit')
                ->body('Validation passed and the request is now ready for audit review.')
                ->success()
                ->send();

            $redirectUrl = $this->getViewUrl();
            $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));

            return;
        }

        Notification::make()
            ->title('Audit complete')
            ->body('Validation passed. The request is ready for the next workflow step.')
            ->success()
            ->send();
    }

    protected function resolveReturnToQueueUrl(mixed $candidate): ?string
    {
        if (! is_string($candidate) || $candidate === '') {
            return null;
        }

        $defaultUrl = $this->getDefaultIndexUrl();

        return str_starts_with($candidate, $defaultUrl) ? $candidate : null;
    }

    protected function getDefaultIndexUrl(): string
    {
        return VerificationRequestResource::getUrl('index');
    }

    public function saveTemplateThreeVerification(): void
    {
        abort_unless($this->canSubmitForm(), 403);

        if ($this->formTemplate !== 'template_3') {
            $this->save(false, false);

            return;
        }

        $this->shouldSkipWorkflowSyncOnSave = false;
        $this->shouldCaptureSubmissionOnSave = false;

        try {
            $this->persistTemplateThreeWithoutResourceValidation();
        } finally {
            $this->shouldCaptureSubmissionOnSave = true;
        }

        $this->refreshVerificationFormStateFromRecord();
        $this->auditReady = false;

        Notification::make()
            ->title('Verification saved')
            ->body('Master Template verification answers were saved successfully.')
            ->success()
            ->send();
    }

    public function saveAndBack(): void
    {
        abort_unless($this->canSubmitForm(), 403);

        $this->shouldSkipWorkflowSyncOnSave = true;
        $this->shouldCaptureSubmissionOnSave = false;

        try {
            if ($this->formTemplate === 'template_3') {
                $this->persistTemplateThreeWithoutResourceValidation();
            } else {
                $this->save(false, false);
            }
        } finally {
            $this->shouldSkipWorkflowSyncOnSave = false;
            $this->shouldCaptureSubmissionOnSave = true;
        }

        Notification::make()
            ->title('Draft saved')
            ->body('Your verification progress was saved.')
            ->success()
            ->send();

        $redirectUrl = $this->getIndexUrl();

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
    }

    public function saveAsDraft(): void
    {
        abort_unless($this->canSubmitForm(), 403);

        $this->data['outcome_status'] = 'pending';
        $this->record->outcome_status = 'pending';

        $this->shouldSkipWorkflowSyncOnSave = true;
        $this->shouldCaptureSubmissionOnSave = false;

        try {
            if ($this->formTemplate === 'template_3') {
                $this->persistTemplateThreeWithoutResourceValidation(['outcome_status' => 'pending']);
            } else {
                $this->save(false, false);
            }
        } finally {
            $this->shouldSkipWorkflowSyncOnSave = false;
            $this->shouldCaptureSubmissionOnSave = true;
        }

        $this->refreshVerificationFormStateFromRecord();
        $missingFields = $this->missingRequiredVerificationFields();

        if ($missingFields !== [] && $this->record->normalized_status !== BillingWorkItem::STATUS_INCOMPLETE) {
            $this->record = app(WorkflowService::class)->transition($this->record, BillingWorkItem::STATUS_INCOMPLETE);
        } elseif ($missingFields === [] && $this->record->normalized_status === BillingWorkItem::STATUS_INCOMPLETE) {
            $this->record = app(WorkflowService::class)->transition($this->record, BillingWorkItem::STATUS_IN_PROGRESS);
        } else {
            $this->record->refresh();
        }

        $this->refreshVerificationFormStateFromRecord();
        $this->auditReady = $missingFields === [];

        if ($missingFields !== []) {
            Notification::make()
                ->title('Verification saved as incomplete')
                ->body('Required answers are still missing. Complete all required fields before Audit.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Verification saved')
            ->body('All required answers are complete. You can now run Audit.')
            ->success()
            ->send();
    }

    protected function persistTemplateThreeWithoutResourceValidation(array $overrides = []): void
    {
        $this->resetErrorBag();
        $this->record = app(VerificationTemplateVersionService::class)->attachSnapshotToWorkItem($this->record);

        DB::transaction(function () use ($overrides): void {
            $baseData = array_merge(
                $this->record->attributesToArray(),
                $this->data ?? [],
                $overrides
            );

            $this->mutateFormDataBeforeSave($baseData);

            if ($overrides !== []) {
                $this->record->forceFill($overrides)->save();
            }

            $this->afterSave();
        });
    }

    protected function refreshVerificationFormStateFromRecord(): void
    {
        $this->record->refresh();

        $refilled = $this->mutateFormDataBeforeFill([]);
        $this->data = $refilled;
        $this->syncSubscriberFieldsForSelfRelationship();

        if (isset($this->form) && $this->form) {
            $this->form->fill($this->data);
        }
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

        if ($appointmentTime instanceof Carbon || $appointmentTime instanceof CarbonInterface) {
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
                    ['label' => 'COB', 'field' => 'vf_coverage_role', 'type' => 'select', 'options' => ['No COB' => 'No COB', 'Primary' => 'Primary', 'Secondary' => 'Secondary', 'Unknown' => 'Unknown']],
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
        return $this->getManagedTemplateQuestionsForSection($sectionKey, $this->formTemplate);
    }

    public function getTemplateThreeQuestionsForSection(string $sectionKey): array
    {
        return $this->getManagedTemplateQuestionsForSection($sectionKey, VerificationFormQuestion::DEFAULT_TEMPLATE_KEY);
    }

    public function getTemplateThreeVerificationInformationSection(): array
    {
        $formType = data_get($this->data, 'vf_form_type', 'full_form');
        $templateVersionId = app(VerificationAuditService::class)->templateVersionId($this->record);

        $questions = VerificationFormQuestion::query()
            ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
            ->where('section_key', 'template_3_verification_information')
            ->where('is_active', true)
            ->where('input_type', '!=', 'frequency_row')
            ->whereIn('form_type', ['both', $formType])
            ->where(function ($query): void {
                $query
                    ->whereNull('question_kind')
                    ->orWhere('question_kind', VerificationFormQuestion::QUESTION_KIND_NORMAL);
            })
            ->where('template_version_id', $templateVersionId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (VerificationFormQuestion $question): array {
                $promptKey = Str::lower(trim($question->prompt));
                $isReference = $promptKey === 'reference number';
                $isReadonly = $isReference || in_array($question->field_key, [
                    'vf_verified_by',
                    'vf_verification_date',
                ], true);
                $field = filled($question->field_key)
                    ? $question->field_key
                    : ($isReference ? null : $this->customQuestionFieldName($question->getKey()));
                $value = match (true) {
                    $isReference => $this->record->reference_number,
                    $question->field_key === 'vf_verified_by' => data_get($this->data, 'vf_verified_by') ?: auth()->user()?->name ?: '-',
                    $question->field_key === 'vf_verification_date' => data_get($this->data, 'vf_verification_date') ?: now()->format('Y-m-d'),
                    filled($field) => data_get($this->data, $field),
                    default => null,
                };

                return [
                    'id' => $question->getKey(),
                    'label' => $question->prompt,
                    'field' => $field,
                    'note_field' => $this->customQuestionNoteFieldName($question->getKey()),
                    'type' => $question->input_type,
                    'help_text' => $question->help_text,
                    'placeholder' => $question->placeholder,
                    'options' => $question->getSelectOptionValues(),
                    'has_note' => $question->has_note,
                    'note_label' => $question->note_label ?: 'Note',
                    'note_placeholder' => $question->note_placeholder ?: 'Add note',
                    'readonly' => $isReadonly,
                    'value' => $value,
                    'completed' => filled($value),
                ];
            })
            ->values();

        return [
            'rows' => $questions->all(),
            'completed' => $questions->where('completed', true)->count(),
            'total' => $questions->count(),
        ];
    }

    public function getManagedTemplateQuestionsForSection(string $sectionKey, ?string $templateKey = null): array
    {
        $formType = data_get($this->data, 'vf_form_type', 'full_form');
        $clinicId = $this->record->clinic_id;
        $organizationId = $this->record->organization_id;

        $templateKey = VerificationFormQuestion::normalizeTemplateKey($templateKey ?: $this->formTemplate);
        $resolvedSectionKey = $this->resolveTemplateSectionKey($sectionKey, $templateKey);

        $questionsQuery = VerificationFormQuestion::query()
            ->where('template_key', $templateKey)
            ->where('section_key', $resolvedSectionKey)
            ->where('is_active', true)
            ->where('input_type', '!=', 'frequency_row')
            ->whereIn('form_type', ['both', $formType])
            ->where(function ($query): void {
                $query
                    ->whereNull('question_kind')
                    ->orWhere('question_kind', VerificationFormQuestion::QUESTION_KIND_NORMAL);
            })
            ->orderBy('sort_order')
            ->orderBy('id');

        $questionsQuery->where(
            'template_version_id',
            app(VerificationAuditService::class)->templateVersionId($this->record),
        );

        $fixedTemplateThreeFields = $this->fixedTemplateThreeFieldKeysForSection($resolvedSectionKey);
        $fixedTemplateThreePrompts = $this->fixedTemplateThreePromptsForSection($resolvedSectionKey);

        if ($templateKey === VerificationFormQuestion::DEFAULT_TEMPLATE_KEY && filled($fixedTemplateThreeFields)) {
            $questionsQuery->where(function ($query) use ($fixedTemplateThreeFields): void {
                $query
                    ->whereNull('field_key')
                    ->orWhere('field_key', '')
                    ->orWhereNotIn('field_key', $fixedTemplateThreeFields);
            });
        }

        if ($templateKey === VerificationFormQuestion::DEFAULT_TEMPLATE_KEY && filled($fixedTemplateThreePrompts)) {
            $questionsQuery->whereNotIn('prompt', $fixedTemplateThreePrompts);
        }

        $questions = $questionsQuery->get();

        return $questions
            ->map(function (VerificationFormQuestion $question) use ($formType, $templateKey, $resolvedSectionKey): array {
                $row = $this->mapManagedTemplateQuestionToRow($question);
                $answer = data_get($this->data, $row['field']);

                $childrenQuery = VerificationFormQuestion::query()
                    ->where('template_key', $templateKey)
                    ->where('section_key', $resolvedSectionKey)
                    ->where('parent_question_id', $question->getKey())
                    ->where('question_kind', VerificationFormQuestion::QUESTION_KIND_CONDITIONAL)
                    ->where('is_active', true)
                    ->where('input_type', '!=', 'frequency_row')
                    ->whereIn('form_type', ['both', $formType])
                    ->orderBy('sort_order')
                    ->orderBy('id');

                $childrenQuery->where(
                    'template_version_id',
                    app(VerificationAuditService::class)->templateVersionId($this->record),
                );

                $row['children'] = $childrenQuery
                    ->get()
                    ->filter(fn (VerificationFormQuestion $child): bool => $child->matchesTrigger($answer))
                    ->map(fn (VerificationFormQuestion $child): array => $this->mapManagedTemplateQuestionToRow($child, true))
                    ->values()
                    ->all();

                return $row;
            })
            ->all();
    }

    protected function mapManagedTemplateQuestionToRow(VerificationFormQuestion $question, bool $isChild = false): array
    {
        return [
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
            'is_child' => $isChild,
            'children' => [],
        ];
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

        return app(VerificationAuditService::class)
            ->applicableQuestions(
                $this->record,
                VerificationFormQuestion::defaultTemplateKey(),
                $formType,
                frequencyRows: false,
            )
            ->where('section_key', $sectionKey)
            ->where('is_builtin', $builtIn)
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
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
        $this->record->load([
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

                $data['vf_'.$key] = $value;
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
        $data = $this->normalizeSelfSubscriberFields($data);
        $data = $this->normalizeVerificationDateFieldsForDisplay($data);
        $data['vf_network_status'] = $this->resolveNetworkStatus(
            data_get($data, 'vf_network_status'),
            data_get($data, 'vf_is_provider_in_network')
        );

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->prepareTemplateThreeSaveState($data);
    }

    protected function prepareTemplateThreeSaveState(array $data): array
    {
        $payload = $this->buildTemplateThreeSavePayload($data);

        $this->data = $payload['form_data'];
        $this->verificationProfileData = $payload['profile_data'];
        $this->verificationFormAnswerData = $payload['answer_data'];
        $this->verificationFormAnswerNoteData = $payload['answer_note_data'];
        $this->verificationCoverageCodeData = $payload['coverage_rows'];

        return $payload['work_item_data'];
    }

    protected function buildTemplateThreeSavePayload(array $data): array
    {
        $formData = $this->normalizeTemplateThreeCobFields($this->data ?? []);
        $workItemData = $this->normalizeTemplateThreeCobFields($data);

        foreach ($formData as $key => $value) {
            if (
                str_starts_with((string) $key, 'vf_')
                || str_starts_with((string) $key, 'custom_question_')
                || str_starts_with((string) $key, 'context_')
            ) {
                $workItemData[$key] = $value;
            }
        }

        $formData = $this->normalizeSelfSubscriberFields($formData);
        $workItemData = $this->normalizeSelfSubscriberFields($workItemData);

        $formData = $this->normalizeVerificationDateFieldsForStorage($formData);
        $workItemData = $this->normalizeVerificationDateFieldsForStorage($workItemData);

        $waitingPeriodSummary = $this->waitingPeriodAnswer === 'yes'
            ? $this->formatWaitingPeriodDetails()
            : null;

        $formData['vf_waiting_periods'] = $waitingPeriodSummary;
        $workItemData['vf_waiting_periods'] = $waitingPeriodSummary;

        [$answerData, $answerNoteData] = $this->extractTemplateThreeCustomAnswerPayloads($formData);
        [$workItemData, $profileData] = static::splitVerificationProfileData($workItemData);

        foreach (array_keys($workItemData) as $key) {
            if (str_starts_with((string) $key, 'custom_question_') || str_starts_with((string) $key, 'context_')) {
                unset($workItemData[$key]);
            }
        }

        return [
            'form_data' => $formData,
            'work_item_data' => $workItemData,
            'profile_data' => $this->normalizeVerificationProfileDataForStorage($profileData),
            'answer_data' => $answerData,
            'answer_note_data' => $answerNoteData,
            'coverage_rows' => $this->normalizeCodeCoverageRows($this->codeCoverageData),
        ];
    }

    protected function extractTemplateThreeCustomAnswerPayloads(array $formData): array
    {
        $answerData = collect($formData)
            ->filter(fn ($value, $key): bool => str_starts_with((string) $key, 'custom_question_')
                && ! str_starts_with((string) $key, 'custom_question_note_'))
            ->mapWithKeys(function ($value, $key): array {
                return [(int) str_replace('custom_question_', '', (string) $key) => $value];
            })
            ->all();

        $answerNoteData = collect($formData)
            ->filter(fn ($value, $key): bool => str_starts_with((string) $key, 'custom_question_note_'))
            ->mapWithKeys(function ($value, $key): array {
                return [(int) str_replace('custom_question_note_', '', (string) $key) => $value];
            })
            ->all();

        return [$answerData, $answerNoteData];
    }

    protected function normalizeVerificationProfileDataForStorage(array $profileData): array
    {
        foreach ($this->verificationProfileIntegerFields() as $field) {
            if (array_key_exists($field, $profileData)) {
                $profileData[$field] = $this->normalizeNullableInteger($profileData[$field]);
            }
        }

        foreach ($this->verificationProfileDecimalFields() as $field) {
            if (array_key_exists($field, $profileData)) {
                $profileData[$field] = $this->normalizeNullableDecimal($profileData[$field]);
            }
        }

        return $profileData;
    }

    protected function verificationProfileIntegerFields(): array
    {
        return [
            'coverage_diagnostic',
            'coverage_preventive',
            'coverage_basic_restorative',
            'coverage_endodontics',
            'coverage_periodontics',
            'coverage_oral_surgery',
            'coverage_major_restorative',
            'coverage_prosthodontics',
            'coverage_implant',
        ];
    }

    protected function verificationProfileDecimalFields(): array
    {
        return [
            'annual_maximum',
            'annual_maximum_remaining',
            'individual_deductible',
            'individual_deductible_remaining',
            'family_deductible',
            'family_deductible_remaining',
        ];
    }

    protected function normalizeNullableInteger(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($cleaned) ? (int) round((float) $cleaned) : null;
    }

    protected function normalizeNullableDecimal(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($cleaned) ? number_format((float) $cleaned, 2, '.', '') : null;
    }

    protected function normalizeTemplateThreeCobFields(array $data): array
    {
        if ($this->formTemplate !== 'template_3') {
            return $data;
        }

        $legacyCob = data_get($data, 'vf_cob');

        if (blank(data_get($data, 'vf_coverage_role')) && in_array($legacyCob, ['No COB', 'Primary', 'Secondary', 'Unknown'], true)) {
            $data['vf_coverage_role'] = $legacyCob;
        }

        if (blank(data_get($data, 'vf_coordination_of_benefits')) && in_array($legacyCob, ['Standard', 'Non-Dup', 'Birthday Rule', 'Other'], true)) {
            $data['vf_coordination_of_benefits'] = $legacyCob;
        }

        unset($data['vf_cob']);

        return $data;
    }

    protected function legacyCobValueForCoverageRole(?string $value): ?string
    {
        return in_array($value, ['No COB', 'Primary', 'Secondary', 'Unknown'], true) ? $value : null;
    }

    protected function legacyCobValueForCoordination(?string $value): ?string
    {
        return in_array($value, ['Standard', 'Non-Dup', 'Birthday Rule', 'Other'], true) ? $value : null;
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
                $notes = filled($row['notes'] ?? null) ? ' | '.trim((string) $row['notes']) : '';

                return trim((string) $row['category']).': '.$period.' '.$unit.$notes;
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
        if (! $this->shouldCaptureSubmissionOnSave) {
            return;
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
            $this->record = app(WorkflowService::class)->start($this->record, auth()->user());

            return;
        }

        $this->record = app(WorkflowService::class)->transition($this->record, $targetStatus);
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
        $organizationId = $this->record->organization_id;

        if (! filled($clinicId)) {
            return [];
        }

        $formType = data_get($this->data, 'vf_form_type', 'full_form');
        $templateKey = VerificationFormQuestion::normalizeTemplateKey($this->formTemplate);
        $ignoredFields = [
            'notes',
            'internal_summary',
            'vf_quick_reference',
        ];

        $missingFields = [];

        $questions = app(VerificationAuditService::class)->requiredApplicableQuestions(
            $this->record,
            $templateKey,
            $formType,
            frequencyRows: false,
        );

        foreach ($questions as $question) {
            if ($question->isConditionalQuestion()) {
                $parentQuestion = $questions->firstWhere('id', $question->parent_question_id)
                    ?? VerificationFormQuestion::query()
                        ->whereKey($question->parent_question_id)
                        ->where(
                            'template_version_id',
                            app(VerificationAuditService::class)->templateVersionId($this->record),
                        )
                        ->first();

                if (! $parentQuestion instanceof VerificationFormQuestion
                    || ! $question->matchesTrigger($this->auditQuestionAnswer($parentQuestion))) {
                    continue;
                }
            }

            foreach ($this->auditQuestionFieldKeys($question) as $fieldKey) {
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

        $savedRowsBySignature = collect($this->normalizeCodeCoverageRows($this->codeCoverageData))
            ->mapWithKeys(fn (array $row, int $index): array => [
                $this->codeCoverageRowSignature($row) => [
                    'index' => $index,
                    'row' => $row,
                ],
            ]);

        $requiredFrequencyRows = app(VerificationAuditService::class)->requiredApplicableQuestions(
            $this->record,
            $templateKey,
            $formType,
            frequencyRows: true,
        )
            ->whereIn('section_key', $this->frequencySectionKeysForTemplate($templateKey))
            ->values();

        foreach ($requiredFrequencyRows as $question) {
            $signature = $this->codeCoverageRowSignature([
                'category' => VerificationFormQuestion::templateThreeFrequencyCategory($question->section_key),
                'code' => $question->code ?: '',
                'description' => $question->prompt,
            ]);

            $match = $savedRowsBySignature->get($signature);
            $row = $match['row'] ?? [];
            $fieldKey = 'codeCoverageData.'.($match['index'] ?? 'required_'.$question->getKey()).'.coverage_status';
            $label = filled($question->code)
                ? "{$question->code} - {$question->prompt}"
                : $question->prompt;

            if (! filled($row['coverage_status'] ?? null) && ! filled($row['coverage_percent'] ?? null)) {
                $missingFields[$fieldKey] = 'Coverage status or percent is required for '.$label;
            }
        }

        return $missingFields;
    }

    protected function auditQuestionFieldKeys(VerificationFormQuestion $question): array
    {
        if ($question->is_builtin && $question->section_key === 'coverage_matrix') {
            return array_values(array_filter([
                $this->resolveBuiltInField($question),
                $question->secondary_field_key,
            ]));
        }

        return array_values(array_filter([
            $question->is_builtin && filled($this->resolveBuiltInField($question))
                ? $this->resolveBuiltInField($question)
                : $this->customQuestionFieldName($question->id),
        ]));
    }

    protected function auditQuestionAnswer(VerificationFormQuestion $question): mixed
    {
        foreach ($this->auditQuestionFieldKeys($question) as $fieldKey) {
            $value = data_get($this->data, $fieldKey);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
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
            $storedName = now()->format('YmdHis').'_'.Str::uuid()->toString().'_'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $extension = $attachment->getClientOriginalExtension();
            $finalName = filled($extension) ? "{$storedName}.{$extension}" : $storedName;
            $storedPath = $attachment->storeAs(
                'billing-work-items/'.$this->record->getKey().'/clinic-response',
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
        $normalizedTargetStatus = BillingWorkItem::normalizeStatus($targetStatus);
        $wasAlreadyWaitingOnClinic = $this->record->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE;
        $clinicRequestReason = trim((string) data_get($this->data, 'info_request_reason'));

        if ($normalizedTargetStatus === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            $this->showInfoRequestModal = true;
            abort_unless($this->canRequestClinicInfo(), 403);
        } else {
            abort_unless($this->record->canUserTransitionTo(auth()->user(), $targetStatus), 403);
        }

        if (! $this->validateWorkflowTransitionReason($targetStatus)) {
            return;
        }

        if ($targetStatus === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            $this->data['outcome_status'] = 'info_requested';
            $this->record->outcome_status = 'info_requested';
        }

        $this->shouldSkipWorkflowSyncOnSave = true;
        if ($this->formTemplate === 'template_3') {
            $this->persistTemplateThreeWithoutResourceValidation();
        } else {
            $this->save(false, false);
        }
        $this->shouldSkipWorkflowSyncOnSave = false;
        $this->record->refresh();

        if ($targetStatus === BillingWorkItem::STATUS_IN_PROGRESS && $this->record->normalized_status === BillingWorkItem::STATUS_PENDING) {
            $this->record = app(WorkflowService::class)->start($this->record, auth()->user());
        } elseif (blank($this->record->assigned_to) && auth()->check()) {
            $this->record = app(WorkflowService::class)->assign($this->record, auth()->user(), auth()->user());
        }

        if ($this->record->normalized_status !== $targetStatus) {
            $workflow = app(WorkflowService::class);

            $this->record = match ($targetStatus) {
                BillingWorkItem::STATUS_REVIEW => $workflow->submitForQa($this->record, auth()->user()),
                BillingWorkItem::STATUS_DONE => $workflow->approveQa($this->record, auth()->user()),
                BillingWorkItem::STATUS_RETURNED_FOR_REWORK => $workflow->rejectQa(
                    $this->record,
                    trim((string) data_get($this->data, 'return_reason')),
                    auth()->user()
                ),
                default => $workflow->transition($this->record, $targetStatus),
            };
        } elseif ($normalizedTargetStatus === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE && $wasAlreadyWaitingOnClinic) {
            $this->record->recordActivity(
                'info_requested_from_clinic',
                'Verification sent a follow-up information request to the clinic.',
                [
                    'info_request_reason' => $clinicRequestReason,
                    'requested_by_role' => auth()->user()?->getPrimaryRoleName(),
                    'follow_up' => true,
                ]
            );
        }

        $this->record->refresh();

        Notification::make()
            ->title($wasAlreadyWaitingOnClinic && $normalizedTargetStatus === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE
                ? 'Follow-up sent to clinic'
                : 'Verification updated')
            ->body($wasAlreadyWaitingOnClinic && $normalizedTargetStatus === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE
                ? 'The follow-up was added to the request history without replacing the earlier request.'
                : 'Status moved to '.(BillingWorkItem::STATUS_OPTIONS[$this->record->normalized_status] ?? str($this->record->normalized_status)->headline()->toString()).'.')
            ->success()
            ->send();

        if ($this->record->normalized_status === BillingWorkItem::STATUS_DONE) {
            $redirectUrl = $this->getViewUrl();
            $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));

            return;
        }

        if ($normalizedTargetStatus === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            $this->data['info_request_reason'] = '';
            $this->showInfoRequestModal = false;
        }
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
        app(SaveVerificationAnswerAction::class)->executeMany(
            $this->record,
            $this->verificationFormAnswerData,
            $this->verificationFormAnswerNoteData,
            auth()->user(),
        );
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
        return $this->templateThreeFrequencyQuestionRows();
    }

    protected function templateThreeFrequencyQuestionRows(): array
    {
        $formType = data_get($this->data, 'vf_form_type', 'full_form');

        $templateKey = VerificationFormQuestion::normalizeTemplateKey($this->formTemplate);
        $sectionKeys = $this->frequencySectionKeysForTemplate($templateKey);
        $questions = app(VerificationAuditService::class)
            ->applicableQuestions($this->record, $templateKey, $formType, frequencyRows: true)
            ->sortBy(function (VerificationFormQuestion $question) use ($sectionKeys): string {
                $sectionOrder = array_search($question->section_key, $sectionKeys, true);

                return sprintf(
                    '%03d-%010d-%010d',
                    $sectionOrder === false ? count($sectionKeys) : $sectionOrder,
                    (int) $question->sort_order,
                    (int) $question->getKey(),
                );
            })
            ->values();

        return $questions
            ->map(fn (VerificationFormQuestion $question): array => [
                'category' => VerificationFormQuestion::templateThreeFrequencyCategory($question->section_key),
                'code' => $question->code ?: '',
                'description' => $question->prompt,
                'frequency_response_mode' => $question->frequency_response_mode ?: 'current',
                'frequency_response_fields' => VerificationFormQuestion::normalizeFrequencyResponseFields(
                    $question->frequency_response_fields,
                    $question->frequency_response_mode ?: 'current',
                ),
            ])
            ->all();
    }

    protected function resolveTemplateSectionKey(string $sectionKey, string $templateKey): string
    {
        return $sectionKey;
    }

    public function templateThreeFieldIsVisible(string $fieldKey, bool $default = true): bool
    {
        if (array_key_exists($fieldKey, $this->templateThreeFieldVisibilityCache)) {
            return $this->templateThreeFieldVisibilityCache[$fieldKey];
        }

        $question = VerificationFormQuestion::query()
            ->where(
                'template_version_id',
                app(VerificationAuditService::class)->templateVersionId($this->record),
            )
            ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
            ->where('field_key', $fieldKey)
            ->orderBy('id')
            ->first();

        return $this->templateThreeFieldVisibilityCache[$fieldKey] = $question
            ? (bool) $question->is_active
            : $default;
    }

    protected function fixedTemplateThreeFieldKeysForSection(string $sectionKey): array
    {
        return match ($sectionKey) {
            'template_3_patient_subscriber' => [
                'vf_patient_full_name',
                'vf_patient_dob',
                'vf_patient_identifier',
                'vf_subscriber_name',
                'vf_subscriber_dob',
                'vf_subscriber_id',
                'vf_insured_relation',
                'vf_coverage_role',
            ],
            'template_3_insurance' => [
                'vf_insurance_provider_name',
                'vf_group_number',
                'vf_plan_type',
                'vf_network_status',
                'vf_effective_date',
                'vf_future_termination_date',
                'vf_plan_renewal_month',
                'vf_insurance_claim_mailing_address',
                'vf_payer_id',
                'vf_insurance_company_phone_number',
                'vf_fee_schedule',
                'vf_group_name',
            ],
            'template_3_maximums_deductibles' => [
                'vf_annual_maximum',
                'vf_annual_maximum_used_display',
                'vf_annual_maximum_remaining',
                'vf_individual_deductible',
                'vf_individual_deductible_remaining',
                'vf_individual_deductible_met_display',
                'vf_family_deductible',
                'vf_family_deductible_remaining',
                'vf_family_deductible_met_display',
                'vf_deductible_applies_notes',
            ],
            'template_3_coverage_category' => [
                'vf_coverage_diagnostic_deductible_applies',
                'vf_coverage_basic_restorative_deductible_applies',
                'vf_coverage_endodontics_deductible_applies',
                'vf_coverage_periodontics_deductible_applies',
                'vf_coverage_oral_surgery_deductible_applies',
                'vf_coverage_major_restorative_deductible_applies',
                'vf_coverage_orthodontics_deductible_applies',
                'vf_coverage_diagnostic',
                'vf_coverage_preventive',
                'vf_coverage_basic_restorative',
                'vf_coverage_endodontics',
                'vf_coverage_periodontics',
                'vf_coverage_oral_surgery',
                'vf_coverage_major_restorative',
                'vf_coverage_prosthodontics',
                'vf_coverage_implant',
                'vf_ortho_lifetime_maximum',
            ],
            'template_3_plan_provisions' => [
                'vf_waiting_periods',
                'vf_missing_tooth_clause',
                'vf_crowns_paid_on',
                'vf_prosthetic_replacement_period',
                'vf_coordination_of_benefits',
                'vf_cob',
                'vf_plan_provisions',
            ],
            'template_3_service_history' => [
                'vf_service_history',
                'vf_history_exams',
                'vf_history_prophylaxis',
                'vf_history_bitewings',
                'vf_history_full_mouth_xray',
                'vf_history_basic_or_major',
            ],
            'template_3_verification_information' => [
                'vf_verification_date',
                'vf_verified_by',
                'vf_insurance_representative_name',
                'vf_verification_notes',
            ],
            default => [],
        };
    }

    protected function fixedTemplateThreePromptsForSection(string $sectionKey): array
    {
        return match ($sectionKey) {
            'template_3_verification_information' => [
                'Reference Number',
            ],
            default => [],
        };
    }

    protected function frequencySectionKeysForTemplate(string $templateKey): array
    {
        return [
            'template_3_frequency_diagnostic_preventative',
            'template_3_frequency_basic',
            'template_3_frequency_major',
            'template_3_frequency_orthodontics',
        ];
    }

    protected function frequencySectionOrderExpression(array $sectionKeys): string
    {
        $quoted = collect($sectionKeys)
            ->map(fn (string $key): string => "'".str_replace("'", "\\'", $key)."'")
            ->implode(', ');

        return "FIELD(section_key, {$quoted})";
    }

    protected function mergeConfiguredCodeCoverageRows(array $rows): array
    {
        $configuredRowsBySignature = collect($this->configuredCodeCoverageTemplate())
            ->mapWithKeys(fn (array $row): array => [$this->codeCoverageRowSignature($row) => $row]);

        $rows = collect($rows)
            ->filter(fn (array $row): bool => $configuredRowsBySignature->has($this->codeCoverageRowSignature($row)))
            ->unique(fn (array $row): string => $this->codeCoverageRowSignature($row))
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
        $existingIds = $this->record->verificationCoverageCodes()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->flip();
        $keptIds = [];
        $existingPayloads = [];
        $newPayloads = [];
        $now = now();

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

            $rowId = filled($row['id'] ?? null) ? (int) $row['id'] : null;

            if ($rowId && $existingIds->has($rowId)) {
                $existingPayloads[] = [
                    'id' => $rowId,
                    'billing_work_item_id' => $this->record->getKey(),
                    ...$payload,
                    'updated_at' => $now,
                ];
                $keptIds[] = $rowId;

                continue;
            }

            $newPayloads[] = [
                'public_id' => (string) Str::ulid(),
                'billing_work_item_id' => $this->record->getKey(),
                ...$payload,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($existingPayloads !== []) {
            VerificationCoverageCode::query()->upsert(
                $existingPayloads,
                ['id'],
                [
                    'code_system',
                    'category',
                    'code',
                    'description',
                    'coverage_status',
                    'coverage_percent',
                    'frequency',
                    'age_limit',
                    'waiting_period',
                    'service_history',
                    'pre_auth_required',
                    'pre_auth_details',
                    'downgrade_applies',
                    'downgrade_to',
                    'payment_guideline',
                    'notes',
                    'sort_order',
                    'updated_at',
                ],
            );
        }

        $this->record->verificationCoverageCodes()
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
            ->delete();

        if ($newPayloads !== []) {
            VerificationCoverageCode::query()->insert($newPayloads);
        }

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
        return 'custom_question_'.$questionId;
    }

    protected function customQuestionNoteFieldName(int $questionId): string
    {
        return 'custom_question_note_'.$questionId;
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
        $patientNameDefault = $profile?->patient_full_name ?: $patient?->full_name;
        $patientDobDefault = $this->formatDateForInput($profile?->patient_dob ?: $patient?->dob);
        $subscriberNameDefault = $profile?->subscriber_name ?: $policy?->subscriber_name ?: $primaryPlan?->subscriber_name;
        $subscriberDobDefault = $this->formatDateForInput($profile?->subscriber_dob ?: $policy?->subscriber_dob ?: $primaryPlan?->subscriber_dob);
        $relationshipDefault = $this->normalizeInsuranceRelationship(
            $profile?->insured_relation ?: $policy?->subscriber_relationship
        );

        if (filled(data_get($data, 'vf_insured_relation'))) {
            $data['vf_insured_relation'] = $this->normalizeInsuranceRelationship(
                data_get($data, 'vf_insured_relation')
            );
        }

        if (
            blank($relationshipDefault)
            && filled($patientNameDefault)
            && filled($subscriberNameDefault)
            && strcasecmp(trim((string) $patientNameDefault), trim((string) $subscriberNameDefault)) === 0
            && filled($patientDobDefault)
            && filled($subscriberDobDefault)
            && $patientDobDefault === $subscriberDobDefault
        ) {
            $relationshipDefault = 'self';
        }

        $defaults = [
            'context_clinic_name' => $clinicDisplayName,
            'vf_form_type' => $profile?->form_type ?: 'full_form',
            'vf_patient_full_name' => $patientNameDefault,
            'vf_patient_dob' => $patientDobDefault,
            'vf_patient_identifier' => $profile?->patient_identifier ?: $policy?->member_id ?: $primaryPlan?->member_id ?: $patient?->insurance_number,
            'vf_patient_zip' => $profile?->patient_zip,
            'vf_appointment_date' => $this->formatDateForInput($profile?->appointment_date ?: $record->appointment?->appointment_date),
            'vf_appointment_time' => $profile?->appointment_time ?: $record->appointment?->start_time,
            'vf_subscriber_name' => $subscriberNameDefault,
            'vf_subscriber_dob' => $subscriberDobDefault,
            'vf_subscriber_id' => $profile?->subscriber_id ?: $primaryPlan?->member_id ?: $policy?->member_id ?: $patient?->insurance_number,
            'vf_insured_relation' => $relationshipDefault,
            'vf_coverage_role' => $profile?->coverage_role ?: $this->legacyCobValueForCoverageRole($profile?->cob),
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
            'vf_coordination_of_benefits' => $profile?->coordination_of_benefits ?: $this->legacyCobValueForCoordination($profile?->cob),
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

    protected function normalizeInsuranceRelationship(mixed $relationship): string
    {
        return match (strtolower(trim((string) $relationship))) {
            'child' => 'dependent',
            'subscriber' => 'self',
            default => strtolower(trim((string) $relationship)),
        };
    }

    protected function formatDateForInput($value): ?string
    {
        return $this->formatDateValue($value, 'Y-m-d');
    }

    protected function formatDateValue($value, string $format = 'Y-m-d'): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof Carbon || $value instanceof CarbonInterface) {
            return $value->format($format);
        }

        try {
            return Carbon::parse($value)->format($format);
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
            filled($patient?->full_name) ? 'Verification request for '.$patient->full_name : null,
            filled($clinicDisplayName) ? 'Clinic: '.$clinicDisplayName : null,
            optional($record->appointment?->appointment_date)->format('M d, Y') ? 'Appointment: '.optional($record->appointment?->appointment_date)->format('M d, Y') : null,
            $record->priority ? 'Priority: '.(BillingWorkItem::PRIORITY_OPTIONS[$record->priority] ?? str($record->priority)->headline()->toString()) : null,
        ])->filter(fn ($value): bool => filled($value));

        return $segments->isNotEmpty() ? $segments->implode(' | ') : null;
    }
}
