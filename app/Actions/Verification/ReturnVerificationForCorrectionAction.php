<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Services\Verification\QualityService;

class ReturnVerificationForCorrectionAction
{
    public function __construct(
        protected QualityService $quality,
    ) {
    }

    public function execute(BillingWorkItem $request, string $reason, ?User $actor = null): BillingWorkItem
    {
        return $this->quality->returnForCorrection($request, $reason, $actor);
    }
}
