<?php

namespace App\Filament\Clinic\Resources\DentalChartEntries\Pages;

use App\Filament\Clinic\Pages\DentalChart;
use App\Filament\Clinic\Resources\DentalChartEntries\DentalChartEntryResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListDentalChartEntries extends ListRecords
{
    protected static string $resource = DentalChartEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openChart')
                ->label('Open visual chart')
                ->icon('heroicon-o-squares-2x2')
                ->url(fn (): string => DentalChart::getUrl()),
            CreateAction::make()
                ->label('New chart entry')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicDentalCharting() ?? false),
        ];
    }
}
