<?php

namespace App\Filament\Saas\Resources\BillingWorkItems\Pages;

use App\Filament\Saas\Resources\BillingWorkItems\BillingWorkItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingWorkItems extends ListRecords
{
    protected static string $resource = BillingWorkItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
