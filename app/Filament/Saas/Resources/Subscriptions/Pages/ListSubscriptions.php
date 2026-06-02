<?php

namespace App\Filament\Saas\Resources\Subscriptions\Pages;

use App\Filament\Saas\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
