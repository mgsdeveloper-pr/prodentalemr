<?php

namespace App\Filament\Clinic\Resources\ClinicOperatories\Pages;

use App\Filament\Clinic\Resources\ClinicOperatories\ClinicOperatoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClinicOperatory extends ViewRecord
{
    protected static string $resource = ClinicOperatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
