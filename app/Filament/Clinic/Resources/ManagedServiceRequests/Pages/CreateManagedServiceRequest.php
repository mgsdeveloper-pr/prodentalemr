<?php

namespace App\Filament\Clinic\Resources\ManagedServiceRequests\Pages;

use App\Filament\Clinic\Resources\ManagedServiceRequests\ManagedServiceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManagedServiceRequest extends CreateRecord
{
    protected static string $resource = ManagedServiceRequestResource::class;
}
