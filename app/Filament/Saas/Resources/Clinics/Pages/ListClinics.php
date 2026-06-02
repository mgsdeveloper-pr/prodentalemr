<?php

namespace App\Filament\Saas\Resources\Clinics\Pages;

use App\Filament\Saas\Resources\Clinics\ClinicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClinics extends ListRecords
{
    protected static string $resource = ClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
