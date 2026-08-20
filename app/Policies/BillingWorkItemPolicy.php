<?php

namespace App\Policies;

use App\Models\BillingWorkItem;
use App\Models\User;
use App\Policies\Concerns\HandlesEnterpriseAuthorization;
use App\Support\AdminClinicScope;
use App\Support\ClinicPanelScope;

class BillingWorkItemPolicy
{
    use HandlesEnterpriseAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canUseVerification($user, 'verification')
            || $this->canUseSaas($user, 'verification')
            || $user->canAccessClinicVerificationRequests();
    }

    public function view(User $user, BillingWorkItem $workItem): bool
    {
        return $this->canAccessVerificationRequest($user, $workItem)
            || $this->canAccessClinicVerificationRequest($user, $workItem);
    }

    public function create(User $user): bool
    {
        return $this->canUseVerification($user, 'verification', 'add')
            || $this->canUseSaas($user, 'verification', 'add')
            || $user->canCreateClinicVerificationRequests();
    }

    public function update(User $user, BillingWorkItem $workItem): bool
    {
        return (
            $this->canUseVerification($user, 'verification', 'update')
            || $this->canUseSaas($user, 'verification', 'update')
            || $user->canEditClinicVerificationRequests()
        ) && $this->view($user, $workItem);
    }

    public function delete(User $user, BillingWorkItem $workItem): bool
    {
        return (
            $this->canUseVerification($user, 'verification', 'delete')
            || $this->canUseSaas($user, 'verification', 'delete')
        ) && $this->canAccessVerificationRequest($user, $workItem);
    }

    public function assign(User $user, BillingWorkItem $workItem): bool
    {
        return $user->canManageVerificationQueue() && $this->canAccessVerificationRequest($user, $workItem);
    }

    public function approve(User $user, BillingWorkItem $workItem): bool
    {
        return $this->assign($user, $workItem);
    }

    public function download(User $user, BillingWorkItem $workItem): bool
    {
        return $this->view($user, $workItem);
    }

    public function export(User $user): bool
    {
        return $user->canAccessSaasRevenueOperations();
    }

    public function import(User $user): bool
    {
        return $this->create($user);
    }

    protected function canAccessVerificationRequest(User $user, BillingWorkItem $workItem): bool
    {
        if (! $user->canAccessSaasRevenueOperations()) {
            return false;
        }

        if ($user->canAccessVerificationWorkspace() && ! $user->canAccessVerificationRequestRecord($workItem)) {
            return false;
        }

        $selectedClinicId = AdminClinicScope::selectedClinicId();

        return ! $selectedClinicId || (int) $workItem->clinic_id === (int) $selectedClinicId;
    }

    protected function canAccessClinicVerificationRequest(User $user, BillingWorkItem $workItem): bool
    {
        if (! $user->canAccessClinicVerificationRequests()) {
            return false;
        }

        if ($user->shouldBypassClinicScope()) {
            $selectedClinicId = ClinicPanelScope::selectedClinicId();

            return $selectedClinicId && (int) $workItem->clinic_id === (int) $selectedClinicId;
        }

        return $this->withinClinicTenant($user, $workItem);
    }
}
