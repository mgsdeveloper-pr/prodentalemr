<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Services\Verification\QualityService;

class CompleteVerificationRequestAction
{
    public function __construct(
        protected QualityService $quality,
    ) {
    }

    public function execute(BillingWorkItem $request, ?User $actor = null): BillingWorkItem
    {
        return $this->quality->approve($request, $actor);
    }
}
