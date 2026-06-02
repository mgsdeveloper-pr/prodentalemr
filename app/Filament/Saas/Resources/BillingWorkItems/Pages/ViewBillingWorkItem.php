<?php

namespace App\Filament\Saas\Resources\BillingWorkItems\Pages;

use App\Filament\Saas\Resources\BillingWorkItems\BillingWorkItemResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Resources\Pages\ViewRecord;

class ViewBillingWorkItem extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = BillingWorkItemResource::class;
}
