<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationNotification;

class VerificationNotificationPolicy
{
    public function view(User $user, VerificationNotification $notification): bool
    {
        if ((int) $notification->user_id === (int) $user->id) {
            return true;
        }

        return $notification->workItem && $user->can('view', $notification->workItem);
    }

    public function update(User $user, VerificationNotification $notification): bool
    {
        return $this->view($user, $notification);
    }
}
