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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateAppointment extends CreateRecord
{
    use InteractsWithAppointmentEditor;

    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.clinic.resources.appointments.pages.appointment-editor';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $exception) {
            $this->isCreating = false;

            if (collect(array_keys($exception->errors()))
                ->contains(fn (string $field): bool => str_starts_with($field, 'data.'))) {
                throw $exception;
            }

            foreach ($exception->errors() as $field => $messages) {
                $statePath = str_starts_with($field, 'data.') ? $field : 'data.'.$field;

                foreach ($messages as $message) {
                    $this->addError($statePath, $message);
                }
            }

            $message = collect($exception->errors())->flatten()->filter()->first()
                ?: 'Review the appointment details and select an available time slot.';

            Notification::make()
                ->danger()
                ->title('Appointment was not saved')
                ->body((string) $message)
                ->persistent()
                ->send();

            $this->dispatch('appointment-validation-error');
        } catch (Throwable $exception) {
            $this->isCreating = false;
            $reference = Str::upper(Str::random(8));

            Log::error('Appointment creation failed.', [
                'reference' => $reference,
                'user_id' => auth()->id(),
                'organization_id' => AppointmentWorkspaceScope::selectedOrganizationId(),
                'clinic_id' => AppointmentWorkspaceScope::selectedClinicId(),
                'exception' => $exception,
            ]);

            Notification::make()
                ->danger()
                ->title('Appointment was not saved')
                ->body($exception instanceof QueryException
                    ? "The database rejected the appointment. Confirm that all pending System Updates have been applied. Reference: {$reference}."
                    : "A server error interrupted appointment creation. Reference: {$reference}.")
                ->persistent()
                ->send();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->ensureAppointmentSchemaIsReady();

        $data['organization_id'] = AppointmentWorkspaceScope::selectedOrganizationId();
        $data['clinic_id'] = AppointmentWorkspaceScope::selectedClinicId();
        if (AppointmentWorkspaceScope::hasLockedLocation()) {
            $data['location_id'] = AppointmentWorkspaceScope::mappedLocationId();
        }
        $data = $this->syncStatusTimestamps($data);

        return app(AppointmentSchedulingService::class)->validateAndNormalize($data);
    }

    protected function ensureAppointmentSchemaIsReady(): void
    {
        $requiredColumns = [
            'public_id',
            'clinic_operatory_id',
            'clinic_service_id',
            'patient_insurance_policy_id',
            'parent_appointment_id',
            'duration_minutes',
            'verification_status',
            'verification_work_item_id',
            'verification_required',
            'verification_processing_mode',
            'source',
            'reason_for_visit',
            'arrival_notes',
        ];
        $missingColumns = array_values(array_diff($requiredColumns, Schema::getColumnListing('appointments')));

        if ($missingColumns === []) {
            return;
        }

        throw ValidationException::withMessages([
            'appointment_date' => 'The production database is missing required appointment updates: '.implode(', ', $missingColumns).'. Run all pending System Updates before saving appointments.',
        ]);
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
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Appointment saved; verification needs attention')
                ->body('The appointment was saved, but its verification request could not be prepared. Review it from the appointment page.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function onValidationError(ValidationException $exception): void
    {
        parent::onValidationError($exception);

        $validationErrors = $exception->errors();
        $errors = collect($validationErrors)->flatten()->filter();
        $message = $errors->first() ?: 'Complete the required fields highlighted in the form, then save the appointment again.';

        if (array_key_exists('start_time', $validationErrors) || array_key_exists('data.start_time', $validationErrors)) {
            $this->data['start_time'] = null;
            $this->data['end_time'] = null;
        }

        Notification::make()
            ->danger()
            ->title('Appointment was not saved')
            ->body((string) $message)
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
