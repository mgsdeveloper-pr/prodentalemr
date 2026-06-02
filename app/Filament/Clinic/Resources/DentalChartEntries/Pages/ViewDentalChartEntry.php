<?php

namespace App\Filament\Clinic\Resources\DentalChartEntries\Pages;

use App\Filament\Clinic\Resources\DentalChartEntries\DentalChartEntryResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDentalChartEntry extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = DentalChartEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canEditClinicDentalCharting() ?? false),
        ];
    }
}
