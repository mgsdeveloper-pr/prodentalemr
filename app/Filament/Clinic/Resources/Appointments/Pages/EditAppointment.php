<?php

namespace App\Filament\Clinic\Resources\Appointments\Pages;

use App\Filament\Clinic\Resources\Appointments\AppointmentResource;
use App\Filament\Clinic\Resources\Appointments\Pages\Concerns\InteractsWithAppointmentEditor;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentSchedulingService;
use App\Support\AppointmentVerificationSender;
use App\Support\AppointmentWorkspaceScope;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditAppointment extends EditRecord
{
    use InteractsWithAppointmentEditor;

    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.clinic.resources.appointments.pages.appointment-editor';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['organization_id'] = AppointmentWorkspaceScope::selectedOrganizationId();
        $data['clinic_id'] = AppointmentWorkspaceScope::selectedClinicId();
        $data = $this->syncStatusTimestamps($data);

        return app(AppointmentSchedulingService::class)->validateAndNormalize($data, $this->record);
    }

    protected function afterSave(): void
    {
        if (! $this->record->verification_required || filled($this->record->verification_work_item_id)) {
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
                ->title('Verification workflow created')
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
