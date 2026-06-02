<?php

namespace App\Filament\Saas\Resources\ClientServiceEnrollments\Pages;

use App\Filament\Saas\Resources\ClientServiceEnrollments\ClientServiceEnrollmentResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Resources\Pages\ViewRecord;

class ViewClientServiceEnrollment extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = ClientServiceEnrollmentResource::class;
}
