<?php

namespace App\Filament\Saas\Resources\ManagedBillingServices\Pages;

use App\Filament\Saas\Resources\ManagedBillingServices\ManagedBillingServiceResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Resources\Pages\ViewRecord;

class ViewManagedBillingService extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = ManagedBillingServiceResource::class;
}
