<?php

namespace App\Filament\Clinic\Resources\TreatmentPlans\Pages;

use App\Filament\Clinic\Resources\TreatmentPlans\TreatmentPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTreatmentPlan extends CreateRecord
{
    protected static string $resource = TreatmentPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->refreshEstimateSummary();
    }
}
