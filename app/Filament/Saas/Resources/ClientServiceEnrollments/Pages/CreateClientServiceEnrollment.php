<?php

namespace App\Filament\Saas\Resources\ClientServiceEnrollments\Pages;

use App\Filament\Saas\Resources\ClientServiceEnrollments\ClientServiceEnrollmentResource;
use App\Filament\Saas\Resources\ClientServiceEnrollments\Pages\Concerns\InteractsWithClientServiceEnrollmentEditor;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateClientServiceEnrollment extends CreateRecord
{
    use InteractsWithClientServiceEnrollmentEditor;

    protected static string $resource = ClientServiceEnrollmentResource::class;

    protected string $view = 'filament.saas.resources.client-service-enrollments.pages.client-service-enrollment-editor';

    protected Width | string | null $maxContentWidth = Width::Full;
}
