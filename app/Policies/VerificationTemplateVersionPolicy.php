<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationTemplateVersion;

class VerificationTemplateVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', \App\Models\VerificationFormQuestion::class);
    }

    public function view(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('create', \App\Models\VerificationFormQuestion::class);
    }

    public function update(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->create($user);
    }

    public function publish(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->update($user, $version);
    }
}
