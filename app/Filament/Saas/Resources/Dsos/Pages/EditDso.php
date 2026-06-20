<?php

namespace App\Filament\Saas\Resources\Dsos\Pages;

use App\Filament\Saas\Resources\Dsos\DsoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDso extends EditRecord
{
    protected static string $resource = DsoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
