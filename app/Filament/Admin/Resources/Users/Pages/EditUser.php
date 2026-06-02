<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Support\SaasNotifications;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $selectedRole = null;
    protected array $assignedClinicIds = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['selected_role'] = $this->record->getPrimaryRoleName();
        $data['assigned_clinic_ids'] = $this->record->verificationClinics()->pluck('clinics.id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedRole = $data['selected_role'] ?? null;
        $this->assignedClinicIds = array_map('intval', $data['assigned_clinic_ids'] ?? []);

        abort_unless(auth()->user()?->canAssignVerificationRole($this->selectedRole), 403);
        abort_unless(auth()->user()?->canAssignVerificationClinics($this->assignedClinicIds), 403);

        unset($data['selected_role'], $data['assigned_clinic_ids'], $data['password_confirmation']);

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

        $this->record->verificationClinics()->sync(
            $this->selectedRole === 'verification_admin' ? [] : $this->assignedClinicIds
        );

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
                ->visible(fn (): bool => UserResource::canDelete($this->record)),
        ];
    }
}
