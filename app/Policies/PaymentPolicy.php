<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\HandlesEnterpriseAuthorization;

class PaymentPolicy
{
    use HandlesEnterpriseAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canUseSaas($user, 'payments');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canUseSaas($user, 'payments', 'add');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->canUseSaas($user, 'payments', 'update');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->canUseSaas($user, 'payments', 'delete');
    }
}
