<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Pages;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Models\BillingWorkItemAttachment;
use App\Filament\Saas\Resources\Verifications\Pages\EditVerificationRequest as BaseEditVerificationRequest;
use App\Services\Verification\StatusService;
use Filament\Support\Enums\Width;

class EditVerificationRequest extends BaseEditVerificationRequest
{
    protected static string $resource = VerificationRequestResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

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
        if ($this->record->normalized_status !== \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            return null;
        }

        return route('filament.clinic.pages.request-response', [
            'respond' => $this->record->getKey(),
            'return' => $this->getIndexUrl(),
        ]);
    }

    public function getPdfDownloadUrl(): string
    {
        return route('clinic.verification-requests.pdf.download', $this->record);
    }

    public function getPdfPreviewUrl(): string
    {
        return route('clinic.verification-requests.pdf.preview', $this->record);
    }

    public function getFormDescription(): string
    {
        if ($this->record->normalized_status === \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            return 'The verification team is waiting on missing clinic information. Review the request note on the left, update the requested details, and send the request back when ready.';
        }

        if (in_array($this->record->normalized_status, [
            \App\Models\BillingWorkItem::STATUS_REVIEW,
            \App\Models\BillingWorkItem::STATUS_DONE,
        ], true)) {
            return 'Review the completed verification carefully. If something looks incorrect, add a correction note and return it for rework.';
        }

        return 'Review the request context on the left and complete the verification answers in the center.';
    }

    public function getSaveButtonLabel(): string
    {
        return $this->auditReady ? 'Audit' : 'Save verification';
    }

    public function getViewButtonLabel(): string
    {
        return 'View details';
    }

    public function getIndexButtonLabel(): string
    {
        return 'Back to queue';
    }

    public function getCancelButtonLabel(): string
    {
        return 'Cancel';
    }

    public function getStatusActionButtons(): array
    {
        $user = auth()->user();
        $statuses = app(StatusService::class);

        return [
            [
                'label' => 'Start Work',
                'target' => \App\Models\BillingWorkItem::STATUS_IN_PROGRESS,
                'tone' => 'primary',
                'visible' => $statuses->canShowStartWork($this->record, $user),
            ],
            [
                'label' => 'Send to Audit',
                'target' => \App\Models\BillingWorkItem::STATUS_REVIEW,
                'tone' => 'info',
                'visible' => $statuses->canShowSendToReview($this->record, $user),
            ],
            [
                'label' => 'Return for Rework',
                'target' => \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
                'tone' => 'danger',
                'visible' => $statuses->canShowReturnForRework($this->record, $user),
            ],
            [
                'label' => 'Mark Incomplete',
                'target' => \App\Models\BillingWorkItem::STATUS_INCOMPLETE,
                'tone' => 'warning',
                'visible' => $statuses->canShowMarkIncomplete($this->record, $user),
            ],
            [
                'label' => 'Mark Done',
                'target' => \App\Models\BillingWorkItem::STATUS_DONE,
                'tone' => 'success',
                'visible' => $statuses->canShowMarkDone($this->record, $user),
            ],
        ];
    }

    public function canManageQueueControl(): bool
    {
        return false;
    }

    public function canSubmitForm(): bool
    {
        return $this->record->clinicUserCanEditVerification(auth()->user());
    }

    protected function shouldRequireClinicResponseNote(string $targetStatus): bool
    {
        return $this->record->normalized_status === \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE
            && \App\Models\BillingWorkItem::normalizeStatus($targetStatus) === \App\Models\BillingWorkItem::STATUS_IN_PROGRESS;
    }

    protected function getSubmissionPanel(): string
    {
        return 'clinic';
    }

    protected function getDefaultIndexUrl(): string
    {
        return route('filament.clinic.resources.verification-requests.index');
    }

    public function getAttachmentDownloadUrl(BillingWorkItemAttachment $attachment): string
    {
        return route('clinic.verification-request-attachments.download', $attachment);
    }
}
