<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Services\Verification\TimelineService;

class SaveVerificationDraftAction
{
    public function __construct(
        protected TimelineService $timeline,
    ) {
    }

    public function record(BillingWorkItem $request, array $meta = []): void
    {
        $this->timeline->record($request, 'verification_draft_saved', 'Verification draft saved.', $meta);
    }
}
