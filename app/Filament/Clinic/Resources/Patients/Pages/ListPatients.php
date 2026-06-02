<?php

namespace App\Filament\Clinic\Resources\Patients\Pages;

use App\Filament\Clinic\Resources\Patients\PatientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New patient')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicPatients() ?? false),
        ];
    }
}
