<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\HandlesEnterpriseAuthorization;

class InvoicePolicy
{
    use HandlesEnterpriseAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canUseSaas($user, 'invoices');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canUseSaas($user, 'invoices', 'add');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->canUseSaas($user, 'invoices', 'update');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->canUseSaas($user, 'invoices', 'delete');
    }

    public function download(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
