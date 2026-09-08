<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\TelephonyAccount;
use App\Models\TelephonyCall;
use App\Models\TelephonyUserAssignment;
use App\Models\User;

class TelephonyAccess
{
    public static function accountForUser(?User $user): ?TelephonyAccount
    {
        $account = self::assignmentFor($user)?->telephonyAccount;

        return $account?->is_active ? $account : null;
    }

    public static function assignmentFor(?User $user): ?TelephonyUserAssignment
    {
        if (! $user) {
            return null;
        }

        return TelephonyUserAssignment::query()->with('telephonyAccount')
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

    public static function canAccessRecording(?User $user, TelephonyCall $call): bool
    {
        $workItem = $call->workItem;

        if (! $user?->status || ! $workItem || ! $user->can('view', $workItem)) {
            return false;
        }

        if ($user->isSaasAdmin()) {
            return true;
        }

        if (! $user->hasVerificationWorkspaceRole() || ! self::hasRolePermission($user, 'view')) {
            return false;
        }

        if (! SaasEntitlements::userFeatureAllowed($user, 'call_recording', $workItem->clinic)) {
            return false;
        }

        $assignment = self::assignmentFor($user);

        return (bool) ($assignment?->can_access_recordings);
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

        if (! $user->can('view', $workItem)
            || ! $user->canAccessVerificationClinic((int) $workItem->clinic_id)) {
            return $unavailable('You do not have access to this verification request or its clinic.');
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

        $assignment = TelephonyUserAssignment::query()->with('telephonyAccount')
            ->where('user_id', $user->getKey())
            ->first();

        if (! $assignment) {
            return $unavailable('Your portal user is not assigned under User Calling Access.');
        }

        if (! $assignment->is_active) {
            return $unavailable('Your User Calling Access assignment is inactive.');
        }

        $account = $assignment->telephonyAccount;
        if (! $account?->is_active) {
            return $unavailable('Your assigned Calling Account is inactive or unavailable.');
        }

        if (! $assignment->can_call) {
            return $unavailable('Calling is disabled for your user assignment.');
        }

        $callingUserLimit = SaasEntitlements::limitFor($workItem->clinic, 'calling_users');

        if ($callingUserLimit !== null) {
            $allowedUserIds = TelephonyUserAssignment::query()->with('user')
                ->where('is_active', true)
                ->where('can_call', true)
                ->whereHas('telephonyAccount', fn ($query) => $query->where('is_active', true))
                ->orderBy('id')
                ->get()
                ->filter(fn ($item): bool => $item->user?->status
                    && $item->user->canAccessVerificationClinic((int) $workItem->clinic_id))
                ->take(max(0, (int) $callingUserLimit))
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
