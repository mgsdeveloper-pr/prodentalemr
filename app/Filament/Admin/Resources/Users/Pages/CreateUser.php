<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Support\SaasNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $selectedRole = null;
    protected array $assignedClinicIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedRole = $data['selected_role'] ?? null;
        $this->assignedClinicIds = array_map('intval', $data['assigned_clinic_ids'] ?? []);

        abort_unless(auth()->user()?->canAssignVerificationRole($this->selectedRole), 403);
        abort_unless(auth()->user()?->canAssignVerificationClinics($this->assignedClinicIds), 403);

        unset($data['selected_role'], $data['assigned_clinic_ids'], $data['password_confirmation']);

        $data['organization_id'] = null;
        $data['clinic_id'] = null;
        $data['location_id'] = null;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->selectedRole) {
            $this->record->syncRoles([$this->selectedRole]);
        }

        $this->record->verificationClinics()->sync(
            $this->selectedRole === 'verification_admin' ? [] : $this->assignedClinicIds
        );

        SaasNotifications::userCreated($this->record->fresh(['roles']), auth()->user());
        SaasNotifications::sendUserVerificationEmail($this->record);
    }
}
