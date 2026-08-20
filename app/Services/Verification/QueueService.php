<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use Illuminate\Database\Eloquent\Builder;

class QueueService
{
    public function baseQuery(): Builder
    {
        return BillingWorkItem::query()
            ->with(['clinic', 'location', 'patient', 'provider.user', 'assignedTo', 'reviewedBy', 'verificationProfile']);
    }
}
