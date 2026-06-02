<?php

namespace App\Filament\Clinic\Resources\Users\Pages;

use App\Filament\Clinic\Resources\Users\UserResource;
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

        $data['created_by'] = auth()->id();
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;

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
