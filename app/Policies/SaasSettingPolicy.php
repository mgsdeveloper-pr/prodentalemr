<?php

namespace App\Policies;

use App\Models\SaasSetting;
use App\Models\User;

class SaasSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessSaasModule('settings') || $user->canManageVerificationSettings();
    }

    public function view(User $user, SaasSetting $setting): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SaasSetting $setting): bool
    {
        return $user->canPerformSaasModuleAction('settings', 'update') || $user->canManageVerificationSettings();
    }
}
