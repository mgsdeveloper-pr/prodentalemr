<?php

namespace App\Filament\Clinic\Resources\Providers\Pages;

use App\Filament\Clinic\Resources\Providers\ProviderResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProvider extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canEditClinicProviders() ?? false),
        ];
    }
}
