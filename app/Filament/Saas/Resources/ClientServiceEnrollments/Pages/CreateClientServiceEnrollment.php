<?php

namespace App\Filament\Saas\Resources\ClientServiceEnrollments\Pages;

use App\Filament\Saas\Resources\ClientServiceEnrollments\ClientServiceEnrollmentResource;
use App\Filament\Saas\Resources\ClientServiceEnrollments\Pages\Concerns\InteractsWithClientServiceEnrollmentEditor;
use App\Services\ClientServiceEnrollmentWorkflow;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateClientServiceEnrollment extends CreateRecord
{
    use InteractsWithClientServiceEnrollmentEditor;

    protected static string $resource = ClientServiceEnrollmentResource::class;

    protected string $view = 'filament.saas.resources.client-service-enrollments.pages.client-service-enrollment-editor';

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(ClientServiceEnrollmentWorkflow::class)->prepare($data);
    }

    protected function afterCreate(): void
    {
        app(ClientServiceEnrollmentWorkflow::class)->synchronizeClinic($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
