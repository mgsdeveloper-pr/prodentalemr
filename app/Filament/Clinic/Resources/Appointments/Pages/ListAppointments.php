<?php

namespace App\Filament\Clinic\Resources\Appointments\Pages;

use App\Filament\Clinic\Resources\Appointments\AppointmentResource;
use App\Filament\Clinic\Pages\AppointmentCalendar;
use App\Support\ClinicPanelScope;
use App\Support\SaasEntitlements;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected string $view = 'filament.clinic.resources.appointments.pages.list-appointments';

    public string $appointmentDatePreset = 'current_month';

    public ?string $customDateFrom = null;

    public ?string $customDateTo = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')
                ->label('Open Calendar')
                ->url(AppointmentCalendar::getUrl())
                ->color('gray'),
        ];
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
        return auth()->user()?->canCreateClinicAppointments() ?? false;
    }

    public function canImportAppointments(): bool
    {
        return (auth()->user()?->canCreateClinicAppointments() ?? false)
            && SaasEntitlements::userFeatureAllowed(auth()->user(), 'appointment_import', ClinicPanelScope::selectedClinic());
    }

    public function getSelectedClinicName(): ?string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name;
    }

    public function getDisplayTimezone(): string
    {
        return ClinicPanelScope::selectedClinic()?->timezone ?: config('app.timezone', 'UTC');
    }

    public function getControlsDescription(): string
    {
        return '';
    }

    public function getAppointmentStats(): array
    {
        $today = Carbon::today($this->getDisplayTimezone())->toDateString();
        $query = $this->applyDashboardDateFilter(AppointmentResource::getEloquentQuery());

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
            'cancelled' => (clone $query)
                ->where('status', 'cancelled')
                ->count(),
        ];
    }

    protected function getTableQuery(): Builder | Relation | null
    {
        $query = parent::getTableQuery();

        return $query ? $this->applyDashboardDateFilter($query) : $query;
    }

    public function updatedAppointmentDatePreset(): void
    {
        $this->resetTable();
    }

    public function updatedCustomDateFrom(): void
    {
        $this->resetTable();
    }

    public function updatedCustomDateTo(): void
    {
        $this->resetTable();
    }

    public function getDashboardDatePresetOptions(): array
    {
        return [
            'today' => 'Today',
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
        $now = now($this->getDisplayTimezone());

        return match ($this->appointmentDatePreset) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
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
                ? Carbon::parse($this->customDateFrom, $this->getDisplayTimezone())->startOfDay()
                : $fallback->copy()->startOfMonth();
            $end = filled($this->customDateTo)
                ? Carbon::parse($this->customDateTo, $this->getDisplayTimezone())->endOfDay()
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
