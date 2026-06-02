<?php

namespace App\Filament\Saas\Resources\ServiceItems\Pages;

use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Filament\Saas\Resources\ServiceItems\ServiceItemResource;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceItem extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = ServiceItemResource::class;
}
