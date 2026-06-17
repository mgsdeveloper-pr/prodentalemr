<?php

namespace App\Filament\Admin\Resources\Appointments\Pages;

use App\Filament\Admin\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Support\AppointmentWorkspaceScope;
use Illuminate\Support\Carbon;
use Filament\Resources\Pages\ListRecords;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.clinic.resources.appointments.pages.list-appointments';

    public string $appointmentDatePreset = 'current_month';

    public ?string $customDateFrom = null;

    public ?string $customDateTo = null;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getCreateUrl(): string
    {
        return AppointmentResource::getUrl('create');
    }

    public function getImportUrl(): string
    {
        return AppointmentResource::getUrl('import');
    }

    public function canCreateAppointments(): bool
    {
        return AppointmentResource::canCreate();
    }

    public function canImportAppointments(): bool
    {
        return AppointmentResource::canCreate();
    }

    public function getSelectedClinicName(): ?string
    {
        return AppointmentWorkspaceScope::selectedClinic()?->clinic_name ?: 'All accessible clinics';
    }

    public function getDisplayTimezone(): string
    {
        return AppointmentWorkspaceScope::selectedClinic()?->timezone ?: config('app.timezone', 'UTC');
    }

    public function getWorkspaceBadgeLabel(): string
    {
        return 'Verification Intake';
    }

    public function getAppointmentPageTitle(): string
    {
        return 'Appointments for Verification';
    }

    public function getAppointmentPageDescription(): string
    {
        return 'Review appointments, send eligible patients for verification, and track whether each request is not sent, sent, in progress, or completed.';
    }

    public function getControlsTitle(): string
    {
        return 'Verification controls';
    }

    public function getControlsDescription(): string
    {
        return '';
    }

    public function getAppointmentStats(): array
    {
        $query = $this->applyDashboardDateFilter(AppointmentResource::getEloquentQuery());

        return [
            'not_sent' => (clone $query)
                ->where('status', '!=', 'cancelled')
                ->where(fn ($builder) => $builder
                    ->whereNull('verification_status')
                    ->orWhere('verification_status', Appointment::VERIFICATION_STATUS_NOT_SENT))
                ->count(),
            'sent' => (clone $query)
                ->where('status', '!=', 'cancelled')
                ->where('verification_status', Appointment::VERIFICATION_STATUS_SENT)
                ->count(),
            'in_progress' => (clone $query)
                ->where('status', '!=', 'cancelled')
                ->where('verification_status', Appointment::VERIFICATION_STATUS_IN_PROGRESS)
                ->count(),
            'completed' => (clone $query)
                ->where('status', '!=', 'cancelled')
                ->where('verification_status', Appointment::VERIFICATION_STATUS_COMPLETED)
                ->count(),
            'cancelled' => (clone $query)
                ->where('status', 'cancelled')
                ->count(),
        ];
    }

    public function getDashboardDatePresetOptions(): array
    {
        return [
            'current_month' => 'Current Month',
            'last_month' => 'Last Month',
            'week' => 'This Week',
            'custom' => 'Custom Dates',
        ];
    }

    public function getDashboardDateRangeLabel(): string
    {
        [$start, $end] = $this->dashboardDateRange();

        return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
    }

    protected function applyDashboardDateFilter($query)
    {
        [$start, $end] = $this->dashboardDateRange();

        return $query->whereBetween('appointment_date', [
            $start->toDateString(),
            $end->toDateString(),
        ]);
    }

    protected function dashboardDateRange(): array
    {
        $now = now();

        return match ($this->appointmentDatePreset) {
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ],
            'custom' => $this->customDashboardDateRange($now),
            default => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
        };
    }

    protected function customDashboardDateRange(Carbon $fallback): array
    {
        try {
            $start = filled($this->customDateFrom)
                ? Carbon::parse($this->customDateFrom)->startOfDay()
                : $fallback->copy()->startOfMonth();
            $end = filled($this->customDateTo)
                ? Carbon::parse($this->customDateTo)->endOfDay()
                : $fallback->copy()->endOfMonth();
        } catch (\Throwable) {
            return [
                $fallback->copy()->startOfMonth(),
                $fallback->copy()->endOfMonth(),
            ];
        }

        if ($end->lt($start)) {
            return [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }
}
