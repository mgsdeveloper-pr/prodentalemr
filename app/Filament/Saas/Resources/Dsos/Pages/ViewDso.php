<?php

namespace App\Filament\Saas\Resources\Dsos\Pages;

use App\Filament\Saas\Resources\Dsos\DsoResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDso extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = DsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
