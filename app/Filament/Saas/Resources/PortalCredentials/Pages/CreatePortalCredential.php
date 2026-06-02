<?php

namespace App\Filament\Saas\Resources\PortalCredentials\Pages;

use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Support\AdminClinicScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePortalCredential extends CreateRecord
{
    protected static string $resource = PortalCredentialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $clinic = AdminClinicScope::selectedClinic();

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->body('Choose a clinic from the Workspace menu before adding a portal credential.')
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
