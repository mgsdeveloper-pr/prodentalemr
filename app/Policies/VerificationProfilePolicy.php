<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationProfile;

class VerificationProfilePolicy
{
    public function view(User $user, VerificationProfile $profile): bool
    {
        return $user->can('view', $profile->workItem);
    }

    public function update(User $user, VerificationProfile $profile): bool
    {
        return $user->can('update', $profile->workItem);
    }
}
