<?php

namespace App\Filament\Clinic\Resources\Providers\Pages;

use App\Filament\Clinic\Resources\Providers\ProviderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New provider')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicProviders() ?? false),
        ];
    }
}
