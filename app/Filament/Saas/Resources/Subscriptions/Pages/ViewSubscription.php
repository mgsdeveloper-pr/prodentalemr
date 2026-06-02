<?php

namespace App\Filament\Saas\Resources\Subscriptions\Pages;

use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Filament\Saas\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscription extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
