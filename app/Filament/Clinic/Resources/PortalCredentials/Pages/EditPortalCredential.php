<?php

namespace App\Filament\Clinic\Resources\PortalCredentials\Pages;

use App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource;
use App\Support\ClinicPanelScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPortalCredential extends EditRecord
{
    protected static string $resource = PortalCredentialResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $clinic = ClinicPanelScope::selectedClinic();

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->body('Choose a clinic from the Workspace menu before updating portal credentials.')
                ->danger()
                ->send();

            $this->halt();
        }

        $data['clinic_id'] = $clinic->getKey();
        $data['organization_id'] = $clinic->organization_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
