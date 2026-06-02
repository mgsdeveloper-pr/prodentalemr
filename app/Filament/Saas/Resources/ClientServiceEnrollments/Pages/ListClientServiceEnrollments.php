<?php

namespace App\Filament\Saas\Resources\ClientServiceEnrollments\Pages;

use App\Filament\Saas\Resources\ClientServiceEnrollments\ClientServiceEnrollmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientServiceEnrollments extends ListRecords
{
    protected static string $resource = ClientServiceEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
