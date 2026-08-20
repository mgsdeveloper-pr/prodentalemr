<?php

namespace App\Filament\Clinic\Resources\ManagedServiceRequests\Pages;

use App\Filament\Clinic\Resources\ManagedServiceRequests\ManagedServiceRequestResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Resources\Pages\ViewRecord;

class ViewManagedServiceRequest extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = ManagedServiceRequestResource::class;
}
