<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;

class StatusService
{
    public const LIFECYCLE_STAGES = [
        'request' => 'Request',
        'queue' => 'Queue',
        'assign' => 'Assign',
        'work' => 'Work',
        'qa' => 'Audit',
        'complete' => 'Complete',
    ];

    public const WORKFLOW_STATUS_ALIASES = [
        'draft' => BillingWorkItem::STATUS_PENDING,
        'submitted' => BillingWorkItem::STATUS_PENDING,
        'queued' => BillingWorkItem::STATUS_PENDING,
        'assigned' => BillingWorkItem::STATUS_PENDING,
        'accepted' => BillingWorkItem::STATUS_IN_PROGRESS,
        'in_progress' => BillingWorkItem::STATUS_IN_PROGRESS,
        'waiting_on_clinic' => BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
        'waiting_on_client' => BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
        'waiting_on_insurance' => BillingWorkItem::STATUS_IN_PROGRESS,
        'waiting_on_payer' => BillingWorkItem::STATUS_IN_PROGRESS,
        'ready_for_qa' => BillingWorkItem::STATUS_REVIEW,
        'qa_review' => BillingWorkItem::STATUS_REVIEW,
        'completed' => BillingWorkItem::STATUS_DONE,
        'complete' => BillingWorkItem::STATUS_DONE,
        'delivered' => BillingWorkItem::STATUS_DONE,
        'closed' => BillingWorkItem::STATUS_DONE,
        'reopened' => BillingWorkItem::STATUS_IN_PROGRESS,
    ];

    public function start(BillingWorkItem $request, ?User $actor = null): BillingWorkItem
    {
        $request->startWork($actor?->getAuthIdentifier());

        return $request->refresh();
    }

    public function transition(BillingWorkItem $request, string $targetStatus): BillingWorkItem
    {
        $request->transitionStatus($this->normalize($targetStatus));

        return $request->refresh();
    }

    public function canTransition(BillingWorkItem $request, ?User $actor, string $targetStatus): bool
    {
        return $request->canUserTransitionTo($actor, $this->normalize($targetStatus));
    }

    public function normalize(?string $status): string
    {
        if (blank($status)) {
            return BillingWorkItem::STATUS_PENDING;
        }

        $status = strtolower(trim($status));

        return self::WORKFLOW_STATUS_ALIASES[$status] ?? BillingWorkItem::normalizeStatus($status);
    }

    public function label(?string $status): string
    {
        $normalized = $this->normalize($status);

        return BillingWorkItem::STATUS_OPTIONS[$normalized] ?? str($normalized)->replace('_', ' ')->title()->toString();
    }

    public function lifecycleStageFor(BillingWorkItem $request): string
    {
        return match ($request->normalized_status) {
            BillingWorkItem::STATUS_PENDING => filled($request->assigned_to) ? 'assign' : 'queue',
            BillingWorkItem::STATUS_IN_PROGRESS,
            BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
            BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
            BillingWorkItem::STATUS_INCOMPLETE => 'work',
            BillingWorkItem::STATUS_REVIEW => 'qa',
            BillingWorkItem::STATUS_DONE => 'complete',
            default => 'request',
        };
    }

    public function lifecycleSnapshot(BillingWorkItem $request): array
    {
        $currentStage = $this->lifecycleStageFor($request);

        return collect(self::LIFECYCLE_STAGES)
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'active' => $key === $currentStage,
                'completed' => $this->stagePosition($key) < $this->stagePosition($currentStage),
            ])
            ->values()
            ->all();
    }

    public function isTerminal(?string $status): bool
    {
        return $this->normalize($status) === BillingWorkItem::STATUS_DONE;
    }

    public function canShowTakeOwnership(BillingWorkItem $request, ?User $actor): bool
    {
        return (bool) ($actor?->canManageVerificationQueue()
            && $request->normalized_status !== BillingWorkItem::STATUS_DONE
            && (int) $request->assigned_to !== (int) $actor->getAuthIdentifier());
    }

    public function canShowReassign(BillingWorkItem $request, ?User $actor): bool
    {
        return (bool) ($actor?->canManageVerificationQueue()
            && $request->normalized_status !== BillingWorkItem::STATUS_DONE);
    }

    public function canShowReturnForRework(BillingWorkItem $request, ?User $actor): bool
    {
        return (bool) (($actor?->canManageVerificationQueue() || $actor?->canEditClinicVerificationRequests())
            && $request->normalized_status === BillingWorkItem::STATUS_REVIEW
            && $request->canUserTransitionTo($actor, BillingWorkItem::STATUS_RETURNED_FOR_REWORK));
    }

    public function canShowReopen(BillingWorkItem $request, ?User $actor): bool
    {
        return (bool) ($actor?->canManageVerificationQueue()
            && $request->normalized_status === BillingWorkItem::STATUS_DONE
            && $request->canUserTransitionTo($actor, BillingWorkItem::STATUS_IN_PROGRESS));
    }

    public function canShowStartWork(BillingWorkItem $request, ?User $actor): bool
    {
        return $request->normalized_status === BillingWorkItem::STATUS_PENDING
            && $request->canUserTransitionTo($actor, BillingWorkItem::STATUS_IN_PROGRESS);
    }

    public function canShowSendToReview(BillingWorkItem $request, ?User $actor): bool
    {
        return in_array($request->normalized_status, [
            BillingWorkItem::STATUS_IN_PROGRESS,
            BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
        ], true)
            && $request->canUserTransitionTo($actor, BillingWorkItem::STATUS_REVIEW);
    }

    public function canShowRespondToClinic(BillingWorkItem $request, ?User $actor): bool
    {
        return $request->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE
            && $request->canUserTransitionTo($actor, BillingWorkItem::STATUS_IN_PROGRESS);
    }

    public function canShowMarkIncomplete(BillingWorkItem $request, ?User $actor): bool
    {
        return in_array($request->normalized_status, [
            BillingWorkItem::STATUS_PENDING,
            BillingWorkItem::STATUS_IN_PROGRESS,
        ], true)
            && $request->canUserTransitionTo($actor, BillingWorkItem::STATUS_INCOMPLETE);
    }

    public function canShowMarkDone(BillingWorkItem $request, ?User $actor): bool
    {
        return $request->normalized_status === BillingWorkItem::STATUS_REVIEW
            && $request->canUserTransitionTo($actor, BillingWorkItem::STATUS_DONE);
    }

    protected function stagePosition(string $stage): int
    {
        return array_search($stage, array_keys(self::LIFECYCLE_STAGES), true) ?: 0;
    }
}
