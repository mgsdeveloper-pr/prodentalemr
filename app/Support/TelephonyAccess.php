<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\Organization;
use App\Models\TelephonyAccount;
use App\Models\TelephonyUserAssignment;
use App\Models\User;

class TelephonyAccess
{
    public static function accountFor(?Organization $organization): ?TelephonyAccount
    {
        return TelephonyAccount::query()
            ->where('is_active', true)
            ->where(function ($query) use ($organization): void {
                $query
                    ->when($organization, fn ($builder) => $builder->where('organization_id', $organization->getKey()))
                    ->orWhere(fn ($builder) => $builder
                        ->whereNull('organization_id')
                        ->where('is_platform_default', true));
            })
            ->orderByRaw('CASE WHEN organization_id IS NULL THEN 1 ELSE 0 END')
            ->latest('id')
            ->first();
    }

    public static function assignmentFor(?User $user, ?TelephonyAccount $account): ?TelephonyUserAssignment
    {
        if (! $user || ! $account) {
            return null;
        }

        return $account->userAssignments()
            ->where('user_id', $user->getKey())
            ->where('is_active', true)
            ->first();
    }

    public static function canCall(?User $user, BillingWorkItem $workItem): bool
    {
        $account = self::accountFor($workItem->organization);
        $assignment = self::assignmentFor($user, $account);
        $callingUserLimit = SaasEntitlements::limitFor($workItem->clinic, 'calling_users');
        $withinUserLimit = true;

        if ($account && $callingUserLimit !== null) {
            $allowedUserIds = $account->userAssignments()
                ->where('is_active', true)
                ->where('can_call', true)
                ->orderBy('id')
                ->limit(max(0, (int) $callingUserLimit))
                ->pluck('user_id')
                ->all();
            $withinUserLimit = in_array($user?->getKey(), $allowedUserIds, true);
        }

        return $workItem->organization
            && SaasEntitlements::userFeatureAllowed($user, 'calling', $workItem->clinic)
            && self::hasRolePermission($user, 'add')
            && $assignment?->can_call === true
            && $withinUserLimit
            && filled($account?->api_key)
            && filled($assignment?->user_key);
    }

    public static function workspace(?User $user, BillingWorkItem $workItem): array
    {
        $account = self::accountFor($workItem->organization);
        $assignment = self::assignmentFor($user, $account);

        if (! self::canCall($user, $workItem) || ! $account || ! $assignment) {
            return ['available' => false];
        }

        return [
            'available' => true,
            'provider' => $account->provider,
            'provider_label' => 'MightyCall',
            'api_key' => $account->api_key,
            'user_key' => $assignment->user_key,
            'sdk_url' => $account->webphone_sdk_url,
            'business_number' => $account->business_number,
            'recording_enabled' => $account->recording_enabled
                && SaasEntitlements::userFeatureAllowed($user, 'call_recording', $workItem->clinic),
            'ai_summary_enabled' => $account->ai_summary_enabled
                && $assignment->can_use_ai_summary
                && SaasEntitlements::userFeatureAllowed($user, 'call_ai_summary', $workItem->clinic),
        ];
    }

    private static function hasRolePermission(?User $user, string $action): bool
    {
        if (! $user?->status) {
            return false;
        }

        if ($user->isSaasAdmin()) {
            return $user->canPerformSaasModuleAction('calling', $action);
        }

        if ($user->hasVerificationWorkspaceRole()) {
            return $user->canPerformVerificationModuleAction('calling', $action);
        }

        return $user->canPerformClinicModuleAction('calling', $action);
    }
}
