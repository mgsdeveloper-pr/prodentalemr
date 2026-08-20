<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Policies\Concerns\HandlesEnterpriseAuthorization;

class VerificationFormQuestionPolicy
{
    use HandlesEnterpriseAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canUseVerification($user, 'template_management')
            || $this->canUseSaas($user, 'template_management')
            || $user->canManageClinicVerificationSettings();
    }

    public function view(User $user, VerificationFormQuestion $question): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->canManageVerificationTemplateSections()
            || $user->canManageClinicTemplateSections();
    }

    public function update(User $user, VerificationFormQuestion $question): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, VerificationFormQuestion $question): bool
    {
        return $this->create($user);
    }

    public function publish(User $user, VerificationFormQuestion $question): bool
    {
        return $this->update($user, $question);
    }
}
