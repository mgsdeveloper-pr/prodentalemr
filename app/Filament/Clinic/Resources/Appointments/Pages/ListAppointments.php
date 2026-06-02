<?php

namespace App\Filament\Clinic\Resources\Appointments\Pages;

use App\Filament\Clinic\Resources\Appointments\AppointmentResource;
use App\Filament\Clinic\Pages\AppointmentCalendar;
use App\Models\Appointment;
use App\Support\ClinicPanelScope;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.clinic.resources.appointments.pages.list-appointments';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')
                ->label('Open Calendar')
                ->url(AppointmentCalendar::getUrl())
                ->color('gray'),
            CreateAction::make()
                ->label('Add Appointment')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicAppointments() ?? false),
        ];
    }

    public function getCreateUrl(): string
    {
        return AppointmentResource::getUrl('create');
    }

    public function getSelectedClinicName(): ?string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name;
    }

    public function getDisplayTimezone(): string
    {
        return ClinicPanelScope::selectedClinic()?->timezone ?: config('app.timezone', 'UTC');
    }

    public function getAppointmentStats(): array
    {
        $today = Carbon::today();
        $query = AppointmentResource::getEloquentQuery();

        return [
            'upcoming' => (clone $query)
                ->whereDate('appointment_date', '>=', $today)
                ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
                ->count(),
            'today' => (clone $query)
                ->whereDate('appointment_date', $today)
                ->count(),
            'completed' => (clone $query)
                ->where('status', 'completed')
                ->count(),
            'pending' => (clone $query)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->count(),
        ];
    }
}
