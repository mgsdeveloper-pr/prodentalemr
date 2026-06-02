<?php

namespace App\Filament\Saas\Resources\Users\Pages;

use App\Filament\Saas\Resources\Users\UserResource;
use App\Support\SaasNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $selectedRole = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedRole = $data['selected_role'] ?? null;

        unset($data['selected_role'], $data['password_confirmation']);

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

        SaasNotifications::userCreated($this->record->fresh(['roles']), auth()->user());
        SaasNotifications::sendUserVerificationEmail($this->record);
    }
}
