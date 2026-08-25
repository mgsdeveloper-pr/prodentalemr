<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateVersion;

class VerificationTemplateVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', VerificationFormQuestion::class);
    }

    public function view(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('create', VerificationFormQuestion::class);
    }

    public function update(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->create($user) && $version->canEditDirectly();
    }

    public function delete(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->create($user) && $version->canDeletePermanently();
    }

    public function publish(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->create($user)
            && $version->status === VerificationTemplateVersion::STATUS_DRAFT;
    }
}
