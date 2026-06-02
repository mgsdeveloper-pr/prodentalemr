<?php

namespace App\Filament\Saas\Resources\ServiceItems\Pages;

use App\Filament\Saas\Resources\ServiceItems\ServiceItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceItems extends ListRecords
{
    protected static string $resource = ServiceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
