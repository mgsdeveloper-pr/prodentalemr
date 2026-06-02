<?php

namespace App\Filament\Clinic\Resources\ClinicOperatories\Pages;

use App\Filament\Clinic\Resources\ClinicOperatories\ClinicOperatoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClinicOperatory extends EditRecord
{
    protected static string $resource = ClinicOperatoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
