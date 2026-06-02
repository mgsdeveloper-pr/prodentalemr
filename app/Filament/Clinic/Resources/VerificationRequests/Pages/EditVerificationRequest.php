<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Pages;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Models\BillingWorkItemAttachment;
use App\Filament\Saas\Resources\Verifications\Pages\EditVerificationWorkItem;
use Filament\Support\Enums\Width;

class EditVerificationRequest extends EditVerificationWorkItem
{
    protected static string $resource = VerificationRequestResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getViewUrl(): string
    {
        return VerificationRequestResource::getUrl('view', ['record' => $this->record]);
    }

    public function getIndexUrl(): string
    {
        return VerificationRequestResource::getUrl('index');
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
        return 'Save verification';
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
        $status = $this->record->normalized_status;

        return [
            [
                'label' => 'Start Work',
                'target' => \App\Models\BillingWorkItem::STATUS_IN_PROGRESS,
                'tone' => 'primary',
                'visible' => $status === \App\Models\BillingWorkItem::STATUS_PENDING
                    && $this->record->canUserTransitionTo($user, \App\Models\BillingWorkItem::STATUS_IN_PROGRESS),
            ],
            [
                'label' => 'Send to Review',
                'target' => \App\Models\BillingWorkItem::STATUS_REVIEW,
                'tone' => 'info',
                'visible' => in_array($status, [
                    \App\Models\BillingWorkItem::STATUS_IN_PROGRESS,
                    \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
                    \App\Models\BillingWorkItem::STATUS_INCOMPLETE,
                ], true) && $this->record->canUserTransitionTo($user, \App\Models\BillingWorkItem::STATUS_REVIEW),
            ],
            [
                'label' => 'Respond to Request',
                'target' => \App\Models\BillingWorkItem::STATUS_IN_PROGRESS,
                'tone' => 'primary',
                'visible' => $status === \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE
                    && $this->record->canUserTransitionTo($user, \App\Models\BillingWorkItem::STATUS_IN_PROGRESS),
            ],
            [
                'label' => 'Return for Rework',
                'target' => \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
                'tone' => 'danger',
                'visible' => in_array($status, [
                    \App\Models\BillingWorkItem::STATUS_REVIEW,
                    \App\Models\BillingWorkItem::STATUS_DONE,
                ], true) && $this->record->canUserTransitionTo($user, \App\Models\BillingWorkItem::STATUS_RETURNED_FOR_REWORK),
            ],
            [
                'label' => 'Mark Incomplete',
                'target' => \App\Models\BillingWorkItem::STATUS_INCOMPLETE,
                'tone' => 'warning',
                'visible' => in_array($status, [
                    \App\Models\BillingWorkItem::STATUS_PENDING,
                    \App\Models\BillingWorkItem::STATUS_IN_PROGRESS,
                ], true) && $this->record->canUserTransitionTo($user, \App\Models\BillingWorkItem::STATUS_INCOMPLETE),
            ],
            [
                'label' => 'Mark Done',
                'target' => \App\Models\BillingWorkItem::STATUS_DONE,
                'tone' => 'success',
                'visible' => $status === \App\Models\BillingWorkItem::STATUS_REVIEW
                    && $this->record->canUserTransitionTo($user, \App\Models\BillingWorkItem::STATUS_DONE),
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

    public function getAttachmentDownloadUrl(BillingWorkItemAttachment $attachment): string
    {
        return route('clinic.billing-work-item-attachments.download', $attachment);
    }
}
