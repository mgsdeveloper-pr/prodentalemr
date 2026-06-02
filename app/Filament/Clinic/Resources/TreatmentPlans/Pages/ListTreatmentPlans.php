<?php

namespace App\Filament\Clinic\Resources\TreatmentPlans\Pages;

use App\Filament\Clinic\Resources\TreatmentPlans\TreatmentPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTreatmentPlans extends ListRecords
{
    protected static string $resource = TreatmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New treatment plan')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicTreatmentPlans() ?? false),
        ];
    }
}
