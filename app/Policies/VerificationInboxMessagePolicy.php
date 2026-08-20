<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationInboxMessage;
use App\Support\AdminClinicScope;

class VerificationInboxMessagePolicy
{
    public function view(User $user, VerificationInboxMessage $message): bool
    {
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
