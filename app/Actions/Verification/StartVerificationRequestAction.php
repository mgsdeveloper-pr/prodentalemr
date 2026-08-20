<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Services\Verification\StatusService;

class StartVerificationRequestAction
{
    public function __construct(
        protected StatusService $statuses,
    ) {
    }

    public function execute(BillingWorkItem $request, ?User $actor = null): BillingWorkItem
    {
        return $this->statuses->start($request, $actor);
    }
}
