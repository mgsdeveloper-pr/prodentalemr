<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;

class VerificationService
{
    public function __construct(
        protected StatusService $statuses,
    ) {
    }

    public function requestClass(): string
    {
        return BillingWorkItem::class;
    }

    public function normalizeStatus(?string $status): string
    {
        return $this->statuses->normalize($status);
    }

    public function statusLabel(?string $status): string
    {
        return $this->statuses->label($status);
    }
}
