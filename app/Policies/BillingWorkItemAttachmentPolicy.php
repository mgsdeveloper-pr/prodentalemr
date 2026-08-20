<?php

namespace App\Policies;

use App\Models\BillingWorkItemAttachment;
use App\Models\User;

class BillingWorkItemAttachmentPolicy
{
    public function view(User $user, BillingWorkItemAttachment $attachment): bool
    {
        return ! $attachment->trashed()
            && $attachment->workItem
            && $user->can('view', $attachment->workItem);
    }

    public function download(User $user, BillingWorkItemAttachment $attachment): bool
    {
        return $this->view($user, $attachment);
    }
}
