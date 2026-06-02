<?php

namespace App\Filament\Clinic\Resources\Appointments\Pages;

use App\Filament\Clinic\Resources\Appointments\AppointmentResource;
use App\Filament\Clinic\Resources\Appointments\Pages\Concerns\InteractsWithAppointmentEditor;
use App\Support\ClinicPanelScope;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditAppointment extends EditRecord
{
    use InteractsWithAppointmentEditor;

    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.clinic.resources.appointments.pages.appointment-editor';

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['organization_id'] ??= ClinicPanelScope::selectedOrganizationId();
        $data['clinic_id'] ??= ClinicPanelScope::selectedClinicId();
        $data = $this->syncStatusTimestamps($data);

        return $data;
    }

    protected function syncStatusTimestamps(array $data): array
    {
        $status = $data['status'] ?? 'scheduled';

        if ($status === 'confirmed') {
            $data['confirmed_at'] ??= now();
        }

        if ($status === 'checked_in') {
            $data['confirmed_at'] ??= $data['confirmed_at'] ?? now();
            $data['checked_in_at'] ??= now();
        }

        if ($status === 'in_chair') {
            $data['confirmed_at'] ??= $data['confirmed_at'] ?? now();
            $data['checked_in_at'] ??= $data['checked_in_at'] ?? now();
            $data['seated_at'] ??= now();
        }

        if ($status === 'completed') {
            $data['confirmed_at'] ??= $data['confirmed_at'] ?? now();
            $data['checked_in_at'] ??= $data['checked_in_at'] ?? now();
            $data['seated_at'] ??= $data['seated_at'] ?? now();
            $data['completed_at'] ??= now();
        }

        if ($status === 'cancelled') {
            $data['cancelled_at'] ??= now();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->canDeleteClinicAppointments() ?? false),
        ];
    }
}
