<?php

namespace App\Filament\Saas\Resources\Payments\Pages;

use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Filament\Saas\Resources\Payments\PaymentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
