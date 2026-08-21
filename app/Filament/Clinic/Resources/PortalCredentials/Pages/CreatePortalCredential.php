<?php

namespace App\Filament\Clinic\Resources\PortalCredentials\Pages;

use App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource;
use App\Support\ClinicPanelScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePortalCredential extends CreateRecord
{
    protected static string $resource = PortalCredentialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $clinic = ClinicPanelScope::selectedClinic();

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->body('Choose a clinic from the Clinic Scope menu before adding a portal credential.')
                ->danger()
                ->send();

            $this->halt();
        }

        $data['clinic_id'] = $clinic->getKey();
        $data['organization_id'] = $clinic->organization_id;
        $data['visible_to_clinic'] = true;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
