<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;

class TimelineService
{
    public function record(BillingWorkItem $request, string $type, string $description, array $meta = [], ?User $actor = null): void
    {
        $request->activities()->create([
            'user_id' => $actor?->getAuthIdentifier() ?? auth()->id(),
            'activity_type' => $type,
            'description' => $description,
            'meta' => filled($meta) ? $meta : null,
        ]);
    }
}
