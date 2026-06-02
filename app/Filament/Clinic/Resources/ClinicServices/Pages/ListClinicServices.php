<?php

namespace App\Filament\Clinic\Resources\ClinicServices\Pages;

use App\Filament\Clinic\Resources\ClinicServices\ClinicServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClinicServices extends ListRecords
{
    protected static string $resource = ClinicServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New clinic service')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicServices() ?? false),
        ];
    }
}
