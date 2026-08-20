<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Support\VerificationAutoAssigner;

class AssignmentService
{
    public function autoAssign(?string $source = null, ?int $clinicId = null): ?User
    {
        return VerificationAutoAssigner::resolve($source, $clinicId);
    }

    public function options(?int $clinicId = null): array
    {
        return VerificationAutoAssigner::optionList($clinicId);
    }

    public function assign(BillingWorkItem $request, User|int|null $assignee, ?User $actor = null): BillingWorkItem
    {
        $assigneeId = $assignee instanceof User ? $assignee->getAuthIdentifier() : $assignee;

        $request->assigned_to = $assigneeId;

        if ($request->normalized_status === BillingWorkItem::STATUS_PENDING && filled($assigneeId)) {
            $request->started_at ??= now();
        }

        $request->save();

        return $request->refresh();
    }

    public function takeOwnership(BillingWorkItem $request, User $actor): BillingWorkItem
    {
        return $this->assign($request, $actor, $actor);
    }
}
