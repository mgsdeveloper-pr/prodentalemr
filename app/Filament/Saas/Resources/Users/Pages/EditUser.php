<?php

namespace App\Filament\Saas\Resources\Users\Pages;

use App\Filament\Saas\Resources\Users\UserResource;
use App\Support\SaasNotifications;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $selectedRole = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['selected_role'] = $this->record->getPrimaryRoleName();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedRole = $data['selected_role'] ?? null;

        unset($data['selected_role'], $data['password_confirmation']);

        $data['organization_id'] = null;
        $data['clinic_id'] = null;
        $data['location_id'] = null;

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->selectedRole) {
            $this->record->syncRoles([$this->selectedRole]);
        }

        SaasNotifications::userUpdated($this->record->fresh(['roles']), auth()->user());
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(function (): void {
                    SaasNotifications::userDeleted($this->record->name, $this->record->email, auth()->user());
                })
                ->visible(fn (): bool => $this->record->id !== auth()->id()),
        ];
    }
}
