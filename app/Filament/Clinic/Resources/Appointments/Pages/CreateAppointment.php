<?php

namespace App\Filament\Clinic\Resources\Appointments\Pages;

use App\Filament\Clinic\Resources\Appointments\AppointmentResource;
use App\Filament\Clinic\Resources\Appointments\Pages\Concerns\InteractsWithAppointmentEditor;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentSchedulingService;
use App\Support\AppointmentVerificationSender;
use App\Support\AppointmentWorkspaceScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Validation\ValidationException;

class CreateAppointment extends CreateRecord
{
    use InteractsWithAppointmentEditor;

    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.clinic.resources.appointments.pages.appointment-editor';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] ??= AppointmentWorkspaceScope::selectedOrganizationId();
        $data['clinic_id'] ??= AppointmentWorkspaceScope::selectedClinicId();
        if (AppointmentWorkspaceScope::hasLockedLocation()) {
            $data['location_id'] = AppointmentWorkspaceScope::mappedLocationId();
        }
        $data = $this->syncStatusTimestamps($data);

        return app(AppointmentSchedulingService::class)->validateAndNormalize($data);
    }

    protected function afterCreate(): void
    {
        if (! $this->record->verification_required) {
            return;
        }

        if (blank($this->record->patient_insurance_policy_id)) {
            $this->record->update([
                'verification_status' => Appointment::VERIFICATION_STATUS_NEEDS_INSURANCE,
            ]);

            Notification::make()
                ->title('Appointment saved')
                ->body('Insurance information is missing. Add an active policy before starting verification.')
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        try {
            app(AppointmentVerificationSender::class)->send(
                $this->record,
                $this->record->verification_processing_mode,
            );

            Notification::make()
                ->title('Appointment and verification request created')
                ->body('The appointment is scheduled and its verification workflow is ready.')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Appointment saved; verification needs attention')
                ->body($exception->getMessage())
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function onValidationError(ValidationException $exception): void
    {
        parent::onValidationError($exception);

        Notification::make()
            ->danger()
            ->title('Appointment is incomplete')
            ->body('Complete the required fields highlighted in the form, then save the appointment again.')
            ->persistent()
            ->send();

        $this->dispatch('appointment-validation-error');
    }

    protected function syncStatusTimestamps(array $data): array
    {
        $status = $data['status'] ?? 'scheduled';

        if ($status === 'confirmed') {
            $data['confirmed_at'] ??= now();
        }

        if ($status === 'checked_in') {
            $data['confirmed_at'] ??= now();
            $data['checked_in_at'] ??= now();
        }

        if ($status === 'in_chair') {
            $data['confirmed_at'] ??= now();
            $data['checked_in_at'] ??= now();
            $data['seated_at'] ??= now();
        }

        if ($status === 'completed') {
            $data['confirmed_at'] ??= now();
            $data['checked_in_at'] ??= now();
            $data['seated_at'] ??= now();
            $data['completed_at'] ??= now();
        }

        if ($status === 'cancelled') {
            $data['cancelled_at'] ??= now();
        }

        return $data;
    }
}
