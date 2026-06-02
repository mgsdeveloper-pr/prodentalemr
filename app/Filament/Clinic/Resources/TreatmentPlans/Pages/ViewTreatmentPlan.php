<?php

namespace App\Filament\Clinic\Resources\TreatmentPlans\Pages;

use App\Filament\Clinic\Resources\TreatmentPlans\TreatmentPlanResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTreatmentPlan extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = TreatmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canEditClinicTreatmentPlans() ?? false),
        ];
    }
}
