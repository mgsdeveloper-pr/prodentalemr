<?php

namespace App\Filament\Clinic\Resources\PerioCharts\Pages;

use App\Filament\Clinic\Resources\PerioCharts\PerioChartResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPerioCharts extends ListRecords
{
    protected static string $resource = PerioChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New perio chart')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicPerioCharting() ?? false),
        ];
    }
}
