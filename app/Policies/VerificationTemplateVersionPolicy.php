<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateVersion;

class VerificationTemplateVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->status && $user->can('viewAny', VerificationFormQuestion::class);
    }

    public function view(User $user, VerificationTemplateVersion $version): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($this->isMaster($version)) {
            return $user->isSaasAdmin()
                || $user->canAccessSaasModule('template_management')
                || $user->canAccessVerificationModule('template_management');
        }

        return $version->scope === VerificationTemplateVersion::SCOPE_CLINIC
            && $version->clinic
            && ($user->isSaasAdmin()
                || (int) $user->clinic_id === (int) $version->clinic_id
                || $user->canAccessVerificationClinic($version->clinic_id));
    }

    public function create(User $user): bool
    {
        return $user->status && $user->can('create', VerificationFormQuestion::class);
    }

    public function manage(User $user, VerificationTemplateVersion $version): bool
    {
        if ($this->isMaster($version)) {
            return $user->canManageVerificationTemplateSections();
        }

        return $version->scope === VerificationTemplateVersion::SCOPE_CLINIC
            && $version->clinic
            && $user->canManageClinicTemplateSections($version->clinic);
    }

    public function update(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->manage($user, $version) && $version->canEditDirectly();
    }

    public function delete(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->manage($user, $version) && $version->canDeletePermanently();
    }

    public function release(User $user, VerificationTemplateVersion $version): bool
    {
        if ($this->isMaster($version)) {
            return $user->canPublishVerificationTemplate();
        }

        return $version->scope === VerificationTemplateVersion::SCOPE_CLINIC
            && $version->clinic
            && $user->canPublishVerificationTemplate($version->clinic);
    }

    public function publish(User $user, VerificationTemplateVersion $version): bool
    {
        return $this->release($user, $version) && $version->canEditDirectly();
    }

    private function isMaster(VerificationTemplateVersion $version): bool
    {
        return $version->scope === VerificationTemplateVersion::SCOPE_MASTER && $version->clinic_id === null;
    }
}
