<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Services\Verification\StatusService;

class TransitionVerificationStatusAction
{
    public function __construct(
        protected StatusService $statuses,
    ) {
    }

    public function execute(BillingWorkItem $request, string $targetStatus): BillingWorkItem
    {
        return $this->statuses->transition($request, $targetStatus);
    }
}
