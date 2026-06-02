<?php

namespace App\Filament\Clinic\Resources\Appointments\Pages;

use App\Filament\Clinic\Resources\Appointments\AppointmentResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAppointment extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canEditClinicAppointments() ?? false),
        ];
    }
}
