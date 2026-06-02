<?php

namespace App\Filament\Clinic\Resources\PerioCharts\Pages;

use App\Filament\Clinic\Resources\PerioCharts\PerioChartResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPerioChart extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = PerioChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canEditClinicPerioCharting() ?? false),
        ];
    }
}
