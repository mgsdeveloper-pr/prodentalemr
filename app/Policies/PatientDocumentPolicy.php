<?php

namespace App\Policies;

use App\Models\PatientDocument;
use App\Models\User;
use App\Policies\Concerns\HandlesEnterpriseAuthorization;

class PatientDocumentPolicy
{
    use HandlesEnterpriseAuthorization;

    public function view(User $user, PatientDocument $document): bool
    {
        return $this->canUseClinic($user, 'patient_documents') && $this->withinClinicTenant($user, $document);
    }

    public function download(User $user, PatientDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function create(User $user): bool
    {
        return $this->canUseClinic($user, 'patient_documents', 'add');
    }

    public function update(User $user, PatientDocument $document): bool
    {
        return $this->canUseClinic($user, 'patient_documents', 'update') && $this->view($user, $document);
    }

    public function delete(User $user, PatientDocument $document): bool
    {
        return $this->canUseClinic($user, 'patient_documents', 'delete') && $this->view($user, $document);
    }
}
