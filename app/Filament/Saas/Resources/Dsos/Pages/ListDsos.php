<?php

namespace App\Filament\Saas\Resources\Dsos\Pages;

use App\Filament\Saas\Resources\Dsos\DsoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDsos extends ListRecords
{
    protected static string $resource = DsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
