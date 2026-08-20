<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Services\Verification\DeliveryService;

class DeliverVerificationRequestAction
{
    public function __construct(
        protected DeliveryService $delivery,
    ) {
    }

    public function execute(BillingWorkItem $request, string $channel, ?User $actor = null): BillingWorkItem
    {
        $this->delivery->recordDelivery($request, $channel, $actor);

        return $request->refresh();
    }

    public function resend(BillingWorkItem $request, string $channel, ?User $actor = null): BillingWorkItem
    {
        $this->delivery->recordResend($request, $channel, $actor);

        return $request->refresh();
    }

    public function recordPdfAccess(BillingWorkItem $request, string $action, string $panel, string $mode, ?User $actor = null): void
    {
        $this->delivery->recordPdfAccess($request, $action, $panel, $mode, $actor);
    }
}
