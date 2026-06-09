<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Filament\Saas\Resources\Verifications\Pages\Concerns\InteractsWithVerificationWorkbench;
use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Models\BillingWorkItem;
use App\Models\InsuranceCarrierNetworkProfile;
use App\Models\User;
use App\Models\VerificationFormSubmission;
use App\Models\VerificationFormQuestion;
use App\Support\VerificationAutoAssigner;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Collection;
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
    public array $clinicResponseAttachments = [];
    public bool $auditReady = false;
    protected bool $shouldSkipWorkflowSyncOnSave = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->recordActivity('verification_console_opened', 'Verification console opened.', [
            'panel' => $this->getSubmissionPanel(),
            'user_name' => auth()->user()?->name,
            'status' => $this->record->normalized_status,
        ]);
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
        if (str_starts_with((string) $name, 'data.')) {
            $this->auditReady = false;
        }
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
                $this->addError('data.' . $fieldKey, $label . ' is required before saving.');
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

        foreach (array_keys($this->data ?? []) as $key) {
            if (
                str_starts_with((string) $key, 'vf_')
                || str_starts_with((string) $key, 'custom_question_')
                || in_array($key, ['notes', 'internal_summary', 'info_request_reason', 'return_reason'], true)
            ) {
                $this->data[$key] = null;
            }
        }

        $this->data['outcome_status'] = 'pending';
        $this->clinicResponseAttachments = [];
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

                $data[$this->customQuestionFieldName($answer->verification_form_question_id)] = $answer->answer_value;
            });

        return $this->applyAutofillDefaults($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->verificationFormAnswerData = collect($this->data)
            ->filter(fn ($value, $key): bool => str_starts_with((string) $key, 'custom_question_'))
            ->mapWithKeys(function ($value, $key): array {
                return [(int) str_replace('custom_question_', '', (string) $key) => $value];
            })
            ->all();

        [$data, $this->verificationProfileData] = static::splitVerificationProfileData($data);

        foreach (array_keys($data) as $key) {
            if (str_starts_with((string) $key, 'custom_question_') || str_starts_with((string) $key, 'context_')) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->verificationProfile()->updateOrCreate([], $this->verificationProfileData);
        $this->syncVerificationFormAnswers();
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

        $questions = VerificationFormQuestion::query()
            ->where('is_active', true)
            ->where('clinic_id', $clinicId)
            ->whereIn('form_type', ['both', $formType])
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

        if ($targetStatus !== BillingWorkItem::STATUS_IN_PROGRESS || $this->record->normalized_status !== BillingWorkItem::STATUS_IN_PROGRESS) {
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
        foreach ($this->verificationFormAnswerData as $questionId => $answerValue) {
            if (blank($answerValue) && $answerValue !== '0' && $answerValue !== 0) {
                $this->record->verificationFormAnswers()
                    ->where('verification_form_question_id', $questionId)
                    ->delete();

                continue;
            }

            $this->record->verificationFormAnswers()->updateOrCreate(
                ['verification_form_question_id' => $questionId],
                ['answer_value' => $answerValue],
            );
        }
    }

    protected function customQuestionFieldName(int $questionId): string
    {
        return 'custom_question_' . $questionId;
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
            'assignedTo',
            'reviewedBy',
            'closedBy',
        ]);

        $profile = $this->record->verificationProfile;
        $answers = $this->record->verificationFormAnswers;

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
                'answer_value' => $answer->answer_value,
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

                if ($value === 0 || $value === 0.0 || $value === '0') {
                    return true;
                }

                return filled($value);
            })
            ->count();

        $hasMeaningfulPayload = $filledProfileFields > 0
            || $answeredQuestions > 0
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
