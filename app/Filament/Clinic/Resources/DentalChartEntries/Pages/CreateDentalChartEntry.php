<?php

namespace App\Filament\Clinic\Resources\DentalChartEntries\Pages;

use App\Filament\Clinic\Resources\DentalChartEntries\DentalChartEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDentalChartEntry extends CreateRecord
{
    protected static string $resource = DentalChartEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;

        return $data;
    }
}
