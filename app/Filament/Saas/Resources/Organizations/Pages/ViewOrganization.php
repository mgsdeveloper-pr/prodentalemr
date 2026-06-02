<?php

namespace App\Filament\Saas\Resources\Organizations\Pages;

use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Filament\Saas\Resources\Organizations\OrganizationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
