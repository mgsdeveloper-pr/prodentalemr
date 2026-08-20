<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Support\VerificationTemplateVersionService;
use Illuminate\Auth\Access\AuthorizationException;

class RefreshVerificationTemplateAction
{
    public function __construct(
        protected VerificationTemplateVersionService $templateVersions,
    ) {
    }

    public function isAlreadyCurrent(BillingWorkItem $workItem): bool
    {
        return $this->templateVersions->workItemUsesLatestPublishedVersion($workItem);
    }

    public function canRefresh(BillingWorkItem $workItem, ?User $user): bool
    {
        if (! $user || ! $workItem->verificationUserCanEditVerification($user)) {
            return false;
        }

        if ($workItem->normalized_status === BillingWorkItem::STATUS_DONE) {
            return false;
        }

        return ! $this->isAlreadyCurrent($workItem);
    }

    public function execute(BillingWorkItem $workItem): BillingWorkItem
    {
        if ($workItem->normalized_status === BillingWorkItem::STATUS_DONE) {
            throw new AuthorizationException('Completed verification requests keep their original template snapshot for audit history.');
        }

        $status = $workItem->normalized_status;
        $outcomeStatus = $workItem->outcome_status;
        $completedAt = $workItem->completed_at;

        $workItem = $this->templateVersions->refreshWorkItemSnapshot($workItem);
        $workItem->forceFill([
            'status' => $status,
            'outcome_status' => $outcomeStatus,
            'completed_at' => $completedAt,
        ])->saveQuietly();
        $workItem->refresh();

        $workItem->recordActivity('template_refreshed', 'Verification template refreshed to the latest clinic version.', [
            'template_version_id' => $workItem->verification_template_version_id,
            'status_preserved' => $status,
        ]);

        return $workItem;
    }
}
