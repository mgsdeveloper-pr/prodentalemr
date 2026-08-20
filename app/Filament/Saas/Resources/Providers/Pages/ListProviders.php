<?php

namespace App\Filament\Saas\Resources\Providers\Pages;

use App\Filament\Saas\Resources\Providers\ProviderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Support Add Provider')
                ->visible(fn (): bool => ProviderResource::canCreate())
                ->url(fn (): string => ProviderResource::getUrl('create', [
                    ...request()->only(['organization_id', 'clinic_id']),
                ])),
        ];
    }
}
