<?php

namespace App\Filament\Saas\Resources\InsuranceCarriers\Pages;

use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceCarriers extends ListRecords
{
    protected static string $resource = InsuranceCarrierResource::class;

    protected string $view = 'filament.saas.resources.insurance-carriers.pages.list-insurance-carriers';

    public function getHeading(): string
    {
        return 'Insurance Directory';
    }

    public function getSubheading(): ?string
    {
        return 'Maintain the central insurance payer directory inherited by clinics. Clinic-specific overrides remain isolated from the platform master.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importInsurance')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(InsuranceCarrierResource::getUrl('import'))
                ->visible(fn (): bool => InsuranceCarrierResource::canCreate()),
            CreateAction::make()
                ->label('Add Insurance')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
