<?php

namespace App\Filament\Saas\Resources\ManagedBillingServices\Pages;

use App\Filament\Saas\Resources\ManagedBillingServices\ManagedBillingServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManagedBillingServices extends ListRecords
{
    protected static string $resource = ManagedBillingServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
