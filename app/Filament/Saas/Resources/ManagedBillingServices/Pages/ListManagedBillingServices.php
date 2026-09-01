<?php

namespace App\Filament\Saas\Resources\ManagedBillingServices\Pages;

use App\Filament\Saas\Resources\ClientServiceEnrollments\ClientServiceEnrollmentResource;
use App\Filament\Saas\Resources\ManagedBillingServices\ManagedBillingServiceResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManagedBillingServices extends ListRecords
{
    protected static string $resource = ManagedBillingServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('enrollClient')
                ->label('Enroll Client')
                ->icon('heroicon-o-user-plus')
                ->url(ClientServiceEnrollmentResource::getUrl('create'))
                ->visible(fn (): bool => ClientServiceEnrollmentResource::canCreate()),
            CreateAction::make()
                ->label('New Service'),
        ];
    }
}
