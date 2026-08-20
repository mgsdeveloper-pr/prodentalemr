<?php

namespace App\Filament\Clinic\Resources\ClinicServices\Pages;

use App\Filament\Clinic\Resources\ClinicServices\ClinicServiceResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Resources\Pages\ViewRecord;

class ViewClinicService extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = ClinicServiceResource::class;
}
