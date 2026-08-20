<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationInboxAttachment;

class VerificationInboxAttachmentPolicy
{
    public function download(User $user, VerificationInboxAttachment $attachment): bool
    {
        return $attachment->isAvailable()
            && $attachment->message
            && $user->can('view', $attachment->message);
    }
}
