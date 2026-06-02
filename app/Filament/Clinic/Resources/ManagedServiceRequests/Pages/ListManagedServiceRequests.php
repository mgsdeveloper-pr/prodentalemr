<?php

namespace App\Filament\Clinic\Resources\ManagedServiceRequests\Pages;

use App\Filament\Clinic\Resources\ManagedServiceRequests\ManagedServiceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManagedServiceRequests extends ListRecords
{
    protected static string $resource = ManagedServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Request managed service'),
        ];
    }
}
