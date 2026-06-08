<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\SaasSetting;
use App\Models\User;
use Illuminate\Support\Collection;

class VerificationAutoAssigner
{
    public static function optionList(?int $clinicId = null): array
    {
        return static::eligibleVerificationUsers($clinicId)
            ->sortBy(fn (User $user): string => strtolower($user->name))
            ->pluck('name', 'id')
            ->all();
    }

    public static function resolve(?string $source = null, ?int $clinicId = null): ?User
    {
        if ($source === 'clinic_self_service') {
            return null;
        }

        $eligibleUsers = static::eligibleVerificationUsers($clinicId);

        if ($eligibleUsers->isEmpty()) {
            return null;
        }

        $primaryPool = $eligibleUsers
            ->reject(fn (User $user): bool => $user->canManageSaasRevenueOperations())
            ->values();

        $candidates = $primaryPool->isNotEmpty() ? $primaryPool : $eligibleUsers;

        if (static::roundRobinEnabled()) {
            return static::resolveRoundRobin($candidates);
        }

        return static::resolveByLightestWorkload($candidates);
    }

    protected static function resolveByLightestWorkload(Collection $candidates): ?User
    {
        $candidateIds = $candidates->pluck('id')->all();

        $openCounts = BillingWorkItem::query()
            ->selectRaw('assigned_to, COUNT(*) as aggregate_count')
            ->whereIn('assigned_to', $candidateIds)
            ->whereNotIn('status', [
                BillingWorkItem::STATUS_DONE,
                BillingWorkItem::STATUS_INCOMPLETE,
            ])
            ->groupBy('assigned_to')
            ->pluck('aggregate_count', 'assigned_to');

        $urgentCounts = BillingWorkItem::query()
            ->selectRaw('assigned_to, COUNT(*) as aggregate_count')
            ->whereIn('assigned_to', $candidateIds)
            ->whereNotIn('status', [
                BillingWorkItem::STATUS_DONE,
                BillingWorkItem::STATUS_INCOMPLETE,
            ])
            ->where('priority', 'urgent')
            ->groupBy('assigned_to')
            ->pluck('aggregate_count', 'assigned_to');

        return $candidates
            ->sortBy([
                fn (User $user): int => (int) ($openCounts[$user->id] ?? 0),
                fn (User $user): int => (int) ($urgentCounts[$user->id] ?? 0),
                fn (User $user): string => strtolower($user->name),
            ])
            ->first();
    }

    protected static function resolveRoundRobin(Collection $candidates): ?User
    {
        $orderedCandidates = $candidates
            ->sortBy(fn (User $user): string => strtolower($user->name))
            ->values();

        if ($orderedCandidates->isEmpty()) {
            return null;
        }

        $settings = SaasSetting::current();
        $lastAssignedUserId = $settings->verification_round_robin_last_user_id;
        $lastAssignedIndex = $orderedCandidates->search(
            fn (User $user): bool => (int) $user->id === (int) $lastAssignedUserId
        );

        $nextIndex = $lastAssignedIndex === false
            ? 0
            : (($lastAssignedIndex + 1) % $orderedCandidates->count());

        /** @var User $selectedUser */
        $selectedUser = $orderedCandidates->get($nextIndex);

        if ($settings->exists) {
            $settings->update([
                'verification_round_robin_last_user_id' => $selectedUser->getKey(),
            ]);
        }

        return $selectedUser;
    }

    protected static function roundRobinEnabled(): bool
    {
        return (bool) SaasSetting::current()->verification_round_robin_enabled;
    }

    protected static function eligibleVerificationUsers(?int $clinicId = null): Collection
    {
        return User::query()
            ->where('status', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::verificationRoleOptions())))
            ->with(['roles', 'permissions', 'verificationClinics'])
            ->get()
            ->filter(fn (User $user): bool => $user->canAccessVerificationWorkspace()
                && (! filled($clinicId) || $user->canAccessVerificationClinic($clinicId)))
            ->values();
    }
}
