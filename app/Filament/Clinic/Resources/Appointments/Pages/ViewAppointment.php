<?php

namespace App\Filament\Clinic\Resources\Appointments\Pages;

use App\Filament\Clinic\Resources\Appointments\AppointmentResource;
use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm;
use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Models\BillingWorkItem;
use App\Support\AppointmentVerificationSender;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAppointment extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openVerification')
                ->label('Open Verification')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('gray')
                ->visible(fn (): bool => filled($this->record->verification_work_item_id))
                ->url(fn (): string => $this->verificationUrl($this->record->verificationWorkItem)),
            Action::make('sendManagedVerification')
                ->label('Send to Managed Service')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => $this->canStartVerification(BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE))
                ->action(fn () => $this->startVerification(BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE)),
            Action::make('startClinicVerification')
                ->label('Start Verification')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => $this->canStartVerification(BillingWorkItem::PROCESSING_MODE_SELF_MANAGED))
                ->action(fn () => $this->startVerification(BillingWorkItem::PROCESSING_MODE_SELF_MANAGED)),
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canEditClinicAppointments() ?? false),
        ];
    }

    protected function canStartVerification(string $mode): bool
    {
        if (filled($this->record->verification_work_item_id)
            || ! (auth()->user()?->canCreateClinicVerificationRequests() ?? false)) {
            return false;
        }

        return array_key_exists($mode, VerificationRequestForm::processingModeOptions(
            $this->record->organization_id,
            $this->record->clinic_id,
            $this->record->location_id,
        ));
    }

    protected function startVerification(string $mode)
    {
        $request = app(AppointmentVerificationSender::class)->send($this->record, $mode);

        Notification::make()
            ->title($mode === BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE
                ? 'Verification sent to Managed Service'
                : 'Clinic verification started')
            ->success()
            ->send();

        return redirect($this->verificationUrl($request));
    }

    protected function verificationUrl(?BillingWorkItem $request): string
    {
        if (! $request) {
            return VerificationRequestResource::getUrl('index');
        }

        return VerificationRequestResource::getUrl(
            $request->clinicUserCanEditVerification(auth()->user()) ? 'edit' : 'view',
            ['record' => $request],
        );
    }
}
