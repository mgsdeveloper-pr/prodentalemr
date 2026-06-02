<?php

namespace App\Filament\Clinic\Resources\ClinicOperatories\Pages;

use App\Filament\Clinic\Resources\ClinicOperatories\ClinicOperatoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClinicOperatories extends ListRecords
{
    protected static string $resource = ClinicOperatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
