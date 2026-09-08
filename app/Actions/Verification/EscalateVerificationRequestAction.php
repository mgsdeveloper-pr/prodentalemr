<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Services\Verification\TimelineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;

class EscalateVerificationRequestAction
{
    public function __construct(
        protected TimelineService $timeline,
    ) {
    }

    public function execute(BillingWorkItem $request, ?string $reason = null, ?User $actor = null): BillingWorkItem
    {
        $actor ??= auth()->user();
        if (! $actor) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($request, $reason, $actor): BillingWorkItem {
            $current = BillingWorkItem::query()->lockForUpdate()->findOrFail($request->getKey());
            Gate::forUser($actor)->authorize('update', $current);
            if ($current->normalized_status === BillingWorkItem::STATUS_DONE) {
                throw new AuthorizationException('Completed requests cannot be raised as urgent.');
            }
            if ($current->priority === 'urgent') {
                return $current;
            }
            $reason = trim((string) $reason);
            Validator::make(['urgentReason' => $reason], [
                'urgentReason' => ['required', 'string', 'max:1000'],
            ])->validate();

            $current->priority = 'urgent';
            $current->save();
            $this->timeline->record($current, 'verification_escalated', 'Urgent request raised.', [
                'reason' => $reason,
            ], $actor);

            return $current->refresh();
        });
    }
}
