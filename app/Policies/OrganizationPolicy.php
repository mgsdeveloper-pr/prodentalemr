<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\HandlesEnterpriseAuthorization;

class OrganizationPolicy
{
    use HandlesEnterpriseAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canUseSaas($user, 'organizations');
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canUseSaas($user, 'organizations', 'add');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->canUseSaas($user, 'organizations', 'update');
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->canUseSaas($user, 'organizations', 'delete');
    }

    public function restore(User $user, Organization $organization): bool
    {
        return $this->update($user, $organization);
    }

    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }
}
