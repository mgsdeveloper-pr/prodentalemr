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
        return self::evaluate($user, $workItem)['available'];
    }

    public static function workspace(?User $user, BillingWorkItem $workItem): array
    {
        $status = self::evaluate($user, $workItem);

        if (! $status['available']) {
            return [
                'available' => false,
                'visible' => $status['visible'],
                'reason' => $status['reason'],
            ];
        }

        $account = $status['account'];
        $assignment = $status['assignment'];

        return [
            'available' => true,
            'visible' => true,
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

    private static function evaluate(?User $user, BillingWorkItem $workItem): array
    {
        $visible = (bool) ($user?->isSaasAdmin()
            || self::hasRolePermission($user, 'view')
            || self::hasRolePermission($user, 'add'));

        $unavailable = fn (string $reason): array => [
            'available' => false,
            'visible' => $visible,
            'reason' => $reason,
            'account' => null,
            'assignment' => null,
        ];

        if (! $user?->status) {
            return $unavailable('Your portal user account is inactive.');
        }

        if (! $workItem->organization) {
            return $unavailable('This verification request is not connected to a client organization.');
        }

        if (! SaasEntitlements::userFeatureAllowed($user, 'calling', $workItem->clinic)) {
            return $unavailable('Portal Calling is not enabled in this client\'s subscription plan.');
        }

        if (! self::hasRolePermission($user, 'add')) {
            return $unavailable('Your role does not have permission to place calls.');
        }

        $account = self::accountFor($workItem->organization);

        if (! $account) {
            return $unavailable('No active Calling Account is assigned to this client.');
        }

        $assignment = $account->userAssignments()
            ->where('user_id', $user->getKey())
            ->first();

        if (! $assignment) {
            return $unavailable('Your portal user is not assigned under User Calling Access.');
        }

        if (! $assignment->is_active) {
            return $unavailable('Your User Calling Access assignment is inactive.');
        }

        if (! $assignment->can_call) {
            return $unavailable('Calling is disabled for your user assignment.');
        }

        $callingUserLimit = SaasEntitlements::limitFor($workItem->clinic, 'calling_users');

        if ($callingUserLimit !== null) {
            $allowedUserIds = $account->userAssignments()
                ->where('is_active', true)
                ->where('can_call', true)
                ->orderBy('id')
                ->limit(max(0, (int) $callingUserLimit))
                ->pluck('user_id')
                ->all();

            if (! in_array($user->getKey(), $allowedUserIds, true)) {
                return $unavailable('The client has reached its Calling users plan limit.');
            }
        }

        if (blank($account->api_key)) {
            return $unavailable('The Calling Account is missing its MightyCall API key.');
        }

        if (blank($assignment->user_key)) {
            return $unavailable('Your User Calling Access assignment is missing its MightyCall User Key.');
        }

        return [
            'available' => true,
            'visible' => true,
            'reason' => null,
            'account' => $account,
            'assignment' => $assignment,
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
