<?php

namespace App\Filament\Clinic\Resources\TreatmentPlans\Pages;

use App\Filament\Clinic\Resources\TreatmentPlans\TreatmentPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTreatmentPlan extends EditRecord
{
    protected static string $resource = TreatmentPlanResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refreshEstimateSummary();
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->canDeleteClinicTreatmentPlans() ?? false),
        ];
    }
}
