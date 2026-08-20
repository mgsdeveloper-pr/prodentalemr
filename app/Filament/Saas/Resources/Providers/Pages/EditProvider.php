<?php

namespace App\Filament\Saas\Resources\Providers\Pages;

use App\Filament\Saas\Resources\Providers\ProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => ProviderResource::canDelete($this->record)),
        ];
    }
}
