<?php

namespace App\Filament\Saas\Resources\SubscriptionPlans\Pages;

use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Filament\Saas\Resources\SubscriptionPlans\SubscriptionPlanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscriptionPlan extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
