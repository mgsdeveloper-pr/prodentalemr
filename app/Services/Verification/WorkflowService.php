<?php

namespace App\Services\Verification;

use App\Actions\Verification\CreateVerificationRequestAction;
use App\Actions\Verification\EscalateVerificationRequestAction;
use App\Models\BillingWorkItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class WorkflowService
{
    public function __construct(
        protected CreateVerificationRequestAction $createRequest,
        protected AssignmentService $assignments,
        protected StatusService $statuses,
        protected QualityService $quality,
        protected DeliveryService $delivery,
        protected TimelineService $timeline,
        protected EscalateVerificationRequestAction $escalate,
    ) {
    }

    public function create(array $data): BillingWorkItem
    {
        return $this->createRequest->execute($data);
    }

    public function assign(BillingWorkItem $request, User|int|null $assignee, ?User $actor = null): BillingWorkItem
    {
        return $this->assignments->assign($request, $assignee, $actor);
    }

    public function accept(BillingWorkItem $request, User $actor): BillingWorkItem
    {
        return $this->statuses->start($request, $actor);
    }

    public function start(BillingWorkItem $request, ?User $actor = null): BillingWorkItem
    {
        return $this->statuses->start($request, $actor);
    }

    public function transition(BillingWorkItem $request, string $targetStatus, ?User $actor = null): BillingWorkItem
    {
        if ($actor && ! $this->statuses->canTransition($request, $actor, $targetStatus)) {
            throw new AuthorizationException('You are not authorized to perform this verification workflow action.');
        }

        return $this->statuses->transition($request, $targetStatus);
    }

    public function saveDraft(BillingWorkItem $request, array $meta = [], ?User $actor = null): void
    {
        $this->timeline->record($request, 'verification_draft_saved', 'Verification draft saved.', $meta, $actor);
    }

    public function submitForQa(BillingWorkItem $request, ?User $actor = null): BillingWorkItem
    {
        return $this->quality->submitForReview($request, $actor);
    }

    public function approveQa(BillingWorkItem $request, ?User $actor = null): BillingWorkItem
    {
        return $this->quality->approve($request, $actor);
    }

    public function rejectQa(BillingWorkItem $request, string $reason, ?User $actor = null): BillingWorkItem
    {
        return $this->quality->returnForCorrection($request, $reason, $actor);
    }

    public function requestCorrection(BillingWorkItem $request, string $reason, ?User $actor = null, array $requestedFields = []): BillingWorkItem
    {
        return $this->quality->requestCorrection($request, $reason, $actor, $requestedFields);
    }

    public function complete(BillingWorkItem $request, ?User $actor = null): BillingWorkItem
    {
        return $this->quality->approve($request, $actor);
    }

    public function deliver(BillingWorkItem $request, string $channel, ?User $actor = null): BillingWorkItem
    {
        $this->delivery->recordDelivery($request, $channel, $actor);

        return $request->refresh();
    }

    public function resendDelivery(BillingWorkItem $request, string $channel, ?User $actor = null): BillingWorkItem
    {
        $this->delivery->recordResend($request, $channel, $actor);

        return $request->refresh();
    }

    public function reopen(BillingWorkItem $request, string $reason, ?User $actor = null): BillingWorkItem
    {
        $actor ??= auth()->user();
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reopen_reason' => 'A reason is required to reopen a verification request.',
            ]);
        }

        if (! $actor || ! $this->statuses->canShowReopen($request, $actor)) {
            throw new AuthorizationException('You are not authorized to reopen this verification request.');
        }

        $request = $this->statuses->transition($request, BillingWorkItem::STATUS_IN_PROGRESS);
        $this->timeline->record($request, 'verification_reopened', 'Verification request reopened.', [
            'reason' => $reason,
            'reopened_by' => $actor->name,
        ], $actor);

        return $request->refresh();
    }

    public function escalate(BillingWorkItem $request, ?string $reason = null, ?User $actor = null): BillingWorkItem
    {
        return $this->escalate->execute($request, $reason, $actor);
    }

    public function lifecycleSnapshot(BillingWorkItem $request): array
    {
        return $this->statuses->lifecycleSnapshot($request);
    }
}
