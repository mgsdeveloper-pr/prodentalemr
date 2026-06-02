<?php

namespace App\Filament\Saas\Resources\InsuranceCarriers\Pages;

use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInsuranceCarrier extends EditRecord
{
    protected static string $resource = InsuranceCarrierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
