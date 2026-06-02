<?php

namespace App\Filament\Saas\Resources\Clinics\Pages;

use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Filament\Saas\Resources\Clinics\ClinicResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClinic extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = ClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
