<?php

namespace App\Filament\Clinic\Resources\Locations\Pages;

use App\Filament\Clinic\Resources\Locations\LocationResource;
use App\Support\ClinicPanelScope;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['clinic_id'] = ClinicPanelScope::selectedClinicId();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->label('Archive')->visible(fn (): bool => LocationResource::canDelete($this->record)),
        ];
    }
}
