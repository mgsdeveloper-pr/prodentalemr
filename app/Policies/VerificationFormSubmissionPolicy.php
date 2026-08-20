<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationFormSubmission;

class VerificationFormSubmissionPolicy
{
    public function view(User $user, VerificationFormSubmission $submission): bool
    {
        return $user->can('view', $submission->workItem);
    }

    public function create(User $user): bool
    {
        return $user->canAccessVerificationWorkspace() || $user->canEditClinicVerificationRequests();
    }

    public function export(User $user): bool
    {
        return $user->canAccessSaasRevenueOperations();
    }
}
