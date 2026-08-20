<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserMailbox;

class UserMailboxPolicy
{
    public function view(User $user, UserMailbox $mailbox): bool
    {
        return $user->canAccessVerificationWorkspace() && (int) $mailbox->user_id === (int) $user->id;
    }

    public function update(User $user, UserMailbox $mailbox): bool
    {
        return $this->view($user, $mailbox);
    }

    public function download(User $user, UserMailbox $mailbox): bool
    {
        return $this->view($user, $mailbox);
    }
}
