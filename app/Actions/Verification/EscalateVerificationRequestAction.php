<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Services\Verification\TimelineService;

class EscalateVerificationRequestAction
{
    public function __construct(
        protected TimelineService $timeline,
    ) {
    }

    public function execute(BillingWorkItem $request, ?string $reason = null, ?User $actor = null): BillingWorkItem
    {
        $request->priority = 'urgent';
        $request->save();

        $this->timeline->record($request, 'verification_escalated', 'Verification request escalated.', [
            'reason' => $reason,
        ], $actor);

        return $request->refresh();
    }
}
