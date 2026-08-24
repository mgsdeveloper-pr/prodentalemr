<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationInboxMessage;
use App\Support\AdminClinicScope;
use App\Support\ClinicPanelScope;

class VerificationInboxMessagePolicy
{
    public function view(User $user, VerificationInboxMessage $message): bool
    {
        if (request()->is('clinic/*')) {
            if (! $user->canAccessClinicVerificationRequests()) {
                return false;
            }

            $selectedClinicId = ClinicPanelScope::selectedClinicId();

            return filled($selectedClinicId)
                && (int) $message->clinic_id === (int) $selectedClinicId
                && (
                    $user->shouldBypassClinicScope()
                    || (
                        (int) $message->organization_id === (int) $user->organization_id
                        && (int) $message->clinic_id === (int) $user->clinic_id
                    )
                );
        }

        if (! $user->canAccessVerificationWorkspace()) {
            return false;
        }

        $selectedClinicId = AdminClinicScope::selectedClinicId();

        return (! $selectedClinicId || (int) $message->clinic_id === (int) $selectedClinicId)
            && (
                $user->hasFullVerificationClinicAccess()
                || $user->canAccessVerificationClinic($message->clinic_id)
            );
    }
}
