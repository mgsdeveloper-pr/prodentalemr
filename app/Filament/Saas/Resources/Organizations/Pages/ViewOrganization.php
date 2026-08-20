<?php

namespace App\Filament\Saas\Resources\Organizations\Pages;

use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Filament\Saas\Resources\Organizations\OrganizationResource;
use App\Filament\Saas\Pages\OrganizationWorkspace;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openWorkspace')
                ->label('Open Workspace')
                ->icon('heroicon-o-squares-2x2')
                ->url(fn (): string => OrganizationWorkspace::getUrl([
                    'record' => $this->record->public_id ?: $this->record->getKey(),
                ])),
            EditAction::make()
                ->color('gray'),
        ];
    }
}
