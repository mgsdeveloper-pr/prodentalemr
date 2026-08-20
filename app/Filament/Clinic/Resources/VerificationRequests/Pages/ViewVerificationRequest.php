<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Pages;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Filament\Saas\Resources\Verifications\Pages\ViewVerificationRequest as BaseViewVerificationRequest;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemAttachment;
use App\Services\Verification\WorkflowService;
use App\Support\ClinicPanelScope;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ViewVerificationRequest extends BaseViewVerificationRequest
{
    protected static string $resource = VerificationRequestResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('queue')
                ->label('Back to Queue')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => $this->getQueueUrl()),
        ];

        if ($this->canAccessSensitiveOutputs()) {
            $actions[] = $this->getPdfOutputActionGroup();
            $actions[] = Action::make('downloadAuditTrail')
                ->label('Audit Trail')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(fn (): string => route('clinic.verification-requests.audit.download', $this->record));
        }

        if ($this->canRequestCorrection()) {
            $actions[] = $this->getRequestCorrectionAction();
        }

        if ($this->record->clinicUserCanOpenVerificationForm(auth()->user())) {
            $actions[] = Action::make('edit')
                ->label('Open Form')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => VerificationRequestResource::getUrl('edit', [
                    'record' => $this->record,
                    'return' => $this->getQueueUrl(),
                ]));
        } elseif ($this->record->clinicUserCanRespondToVerification(auth()->user())) {
            $actions[] = Action::make('respond')
                ->label('Respond to Clinic Request')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(fn (): string => route('filament.clinic.pages.request-response', [
                    'respond' => $this->record->getKey(),
                    'return' => $this->getQueueUrl(),
                ]));
        }

        return $actions;
    }

    public function canRequestCorrection(): bool
    {
        $user = auth()->user();

        return in_array($this->record->normalized_status, [
            BillingWorkItem::STATUS_REVIEW,
            BillingWorkItem::STATUS_DONE,
        ], true) && $this->record->canUserTransitionTo($user, BillingWorkItem::STATUS_RETURNED_FOR_REWORK);
    }

    public function getResultAccessMessage(): ?string
    {
        if ($this->record->normalized_status === BillingWorkItem::STATUS_DONE) {
            return 'This completed result is read-only. The saved audit snapshot will remain unchanged if a correction is requested.';
        }

        if ($this->record->isManagedServiceMode()) {
            return $this->record->clinicUserCanRespondToVerification(auth()->user())
                ? 'The Verification Team needs information from the clinic. Use Respond to Clinic Request to continue.'
                : 'This request is handled by the Verification Team. The clinic can review progress and results here.';
        }

        if ($this->record->clinicUserCanOpenVerificationForm(auth()->user())) {
            return 'This is a Self-Managed request. Use Open Form to continue the verification.';
        }

        return null;
    }

    protected function getRequestCorrectionAction(): Action
    {
        return Action::make('requestCorrection')
            ->label('Request Correction')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('gray')
            ->outlined()
            ->modalHeading('Request a correction')
            ->modalDescription('The completed result and its audit snapshot will remain saved. The request will return to work for the correction described below.')
            ->modalSubmitActionLabel('Request Correction')
            ->form([
                Textarea::make('correction_reason')
                    ->label('What needs to be corrected?')
                    ->rows(4)
                    ->required(),
                CheckboxList::make('correction_fields')
                    ->label('Questions or fields to correct')
                    ->helperText('Select only the items the Verification Team should review again.')
                    ->options(fn (): array => $this->getCorrectionFieldOptions())
                    ->columns(2)
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data): void {
                $options = $this->getCorrectionFieldOptions();
                $requestedFields = collect($data['correction_fields'] ?? [])
                    ->mapWithKeys(fn (string $key): array => [$key => $options[$key] ?? str($key)->afterLast('.')->replace('_', ' ')->headline()->toString()])
                    ->all();

                $this->record = app(WorkflowService::class)->requestCorrection(
                    $this->record,
                    $data['correction_reason'],
                    auth()->user(),
                    $requestedFields,
                );

                Notification::make()
                    ->title('Correction requested')
                    ->body('The saved result remains available while the verification is returned for correction.')
                    ->success()
                    ->send();
            });
    }

    public function getCorrectionFieldOptions(): array
    {
        $submission = $this->record->formSubmissions()
            ->where('status', BillingWorkItem::STATUS_DONE)
            ->latest('version')
            ->first();

        if (! $submission) {
            return [];
        }

        return collect($this->buildSubmissionComparableEntries($submission->payload ?? []))
            ->reject(fn (array $entry): bool => in_array($entry['group'] ?? null, ['Submission Summary', 'Queue State'], true))
            ->filter(fn (array $entry): bool => filled($entry['value'] ?? null))
            ->mapWithKeys(fn (array $entry, string $key): array => [
                $key => ($entry['group'] ?? 'Verification Form') . ' - ' . ($entry['label'] ?? str($key)->headline()->toString()),
            ])
            ->all();
    }

    public function getRequestResponseUrl(): string
    {
        return route('filament.clinic.pages.request-response', [
            'return' => $this->getQueueUrl(),
        ]);
    }

    public function canAccessSensitiveOutputs(): bool
    {
        $user = auth()->user();

        if (! $user?->shouldBypassClinicScope()) {
            return true;
        }

        return ClinicPanelScope::selectedClinicId()
            && (int) ClinicPanelScope::selectedClinicId() === (int) $this->record->clinic_id;
    }

    protected function buildPdfDownloadUrl(string $mode = 'standard', ?int $submissionId = null): string
    {
        return route('clinic.verification-requests.pdf.download', [
            'billingWorkItem' => $this->record,
            'mode' => $mode,
            'submission_id' => $submissionId,
        ]);
    }

    protected function buildPdfPreviewUrl(string $mode = 'standard', ?int $submissionId = null): string
    {
        return route('clinic.verification-requests.pdf.preview', [
            'billingWorkItem' => $this->record,
            'mode' => $mode,
            'submission_id' => $submissionId,
        ]);
    }

    public function getAttachmentDownloadUrl(BillingWorkItemAttachment $attachment): string
    {
        return route('clinic.verification-request-attachments.download', $attachment);
    }

    protected function getViewPanel(): string
    {
        return 'clinic';
    }

    protected function getDefaultQueueUrl(): string
    {
        return route('filament.clinic.resources.verification-requests.index');
    }
}
