<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Services\Verification\AssignmentService;

class TakeVerificationOwnershipAction
{
    public function __construct(
        protected AssignmentService $assignments,
    ) {
    }

    public function execute(BillingWorkItem $request, User $actor): BillingWorkItem
    {
        return $this->assignments->takeOwnership($request, $actor);
    }
}
