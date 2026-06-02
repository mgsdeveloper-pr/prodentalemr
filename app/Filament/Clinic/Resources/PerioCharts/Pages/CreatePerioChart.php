<?php

namespace App\Filament\Clinic\Resources\PerioCharts\Pages;

use App\Filament\Clinic\Resources\PerioCharts\PerioChartResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePerioChart extends CreateRecord
{
    protected static string $resource = PerioChartResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['organization_id'] ??= auth()->user()?->organization_id;
        $data['clinic_id'] ??= auth()->user()?->clinic_id;

        return $data;
    }
}
