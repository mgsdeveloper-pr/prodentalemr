<?php

namespace App\Filament\Saas\Resources\Clinics\Pages;

use App\Filament\Saas\Resources\Clinics\ClinicResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClinic extends EditRecord
{
    protected static string $resource = ClinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
