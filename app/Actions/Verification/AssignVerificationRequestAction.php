<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Services\Verification\AssignmentService;

class AssignVerificationRequestAction
{
    public function __construct(
        protected AssignmentService $assignments,
    ) {
    }

    public function execute(BillingWorkItem $request, User|int|null $assignee, ?User $actor = null): BillingWorkItem
    {
        return $this->assignments->assign($request, $assignee, $actor);
    }
}
