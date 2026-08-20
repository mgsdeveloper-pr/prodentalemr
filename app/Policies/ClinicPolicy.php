<?php

namespace App\Policies;

use App\Models\Clinic;
use App\Models\User;
use App\Policies\Concerns\HandlesEnterpriseAuthorization;

class ClinicPolicy
{
    use HandlesEnterpriseAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canUseSaas($user, 'clinics');
    }

    public function view(User $user, Clinic $clinic): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canUseSaas($user, 'clinics', 'add');
    }

    public function update(User $user, Clinic $clinic): bool
    {
        return $this->canUseSaas($user, 'clinics', 'update');
    }

    public function delete(User $user, Clinic $clinic): bool
    {
        return $this->canUseSaas($user, 'clinics', 'delete');
    }
}
