<?php

namespace App\Filament\Clinic\Resources\Providers\Pages;

use App\Filament\Clinic\Resources\Providers\ProviderResource;
use App\Support\ClinicPanelScope;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['organization_id'] = ClinicPanelScope::selectedOrganizationId();
        $data['clinic_id'] = ClinicPanelScope::selectedClinicId();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->label('Deactivate')
                ->modalHeading('Deactivate provider')
                ->modalDescription('This keeps historical appointments, verification requests, and reports intact while removing the provider from active use.')
                ->successNotificationTitle('Provider deactivated')
                ->visible(fn (): bool => ProviderResource::canDelete($this->record) && ! $this->record->trashed()),
            RestoreAction::make()
                ->label('Restore Provider')
                ->successNotificationTitle('Provider restored')
                ->visible(fn (): bool => ProviderResource::canDelete($this->record) && $this->record->trashed()),
        ];
    }
}
