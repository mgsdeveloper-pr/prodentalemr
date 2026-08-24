<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Clinic\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Location;
use App\Models\Provider;
use App\Support\ClinicPanelScope;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class AppointmentCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Scheduling';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Appointment Calendar';

    protected static ?string $slug = 'appointment-calendar';

    protected string $view = 'filament.clinic.pages.appointment-calendar';

    public string $viewMode = 'month';

    public ?string $anchorDate = null;

    public ?string $providerFilter = null;

    public ?string $locationFilter = null;

    public ?string $statusFilter = null;

    public ?string $verificationFilter = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessClinicAppointments() ?? false;
    }

    public function mount(): void
    {
        $this->anchorDate ??= now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addAppointment')
                ->label('Add Appointment')
                ->url(AppointmentResource::getUrl('create'))
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->canCreateClinicAppointments() ?? false),
        ];
    }

    public function previousPeriod(): void
    {
        $date = $this->resolveAnchorDate();

        $this->anchorDate = match ($this->viewMode) {
            'week' => $date->subWeek()->toDateString(),
            'day' => $date->subDay()->toDateString(),
            default => $date->subMonth()->toDateString(),
        };
    }

    public function nextPeriod(): void
    {
        $date = $this->resolveAnchorDate();

        $this->anchorDate = match ($this->viewMode) {
            'week' => $date->addWeek()->toDateString(),
            'day' => $date->addDay()->toDateString(),
            default => $date->addMonth()->toDateString(),
        };
    }

    public function goToToday(): void
    {
        $this->anchorDate = now()->toDateString();
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['month', 'week', 'day'], true)) {
            return;
        }

        $this->viewMode = $mode;
    }

    public function getDisplayLabel(): string
    {
        $date = $this->resolveAnchorDate();

        return match ($this->viewMode) {
            'week' => $date->copy()->startOfWeek(Carbon::SUNDAY)->format('M d').' - '.$date->copy()->endOfWeek(Carbon::SATURDAY)->format('M d, Y'),
            'day' => $date->format('F d, Y'),
            default => $date->format('F Y'),
        };
    }

    public function getDisplayTimezone(): string
    {
        return ClinicPanelScope::selectedClinic()?->timezone ?: config('app.timezone', 'UTC');
    }

    public function getMonthWeeks(): array
    {
        $anchor = $this->resolveAnchorDate()->startOfMonth();
        $start = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $events = $this->getAppointmentsByDate($start, $end);
        $weeks = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $days = [];

            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();
                $items = $events->get($key, collect());
                $days[] = [
                    'date' => $cursor->toDateString(),
                    'day_number' => $cursor->day,
                    'is_current_month' => $cursor->month === $anchor->month,
                    'is_today' => $cursor->isToday(),
                    'is_selected' => $cursor->isSameDay($this->resolveAnchorDate()),
                    'events' => $items->map(fn (Appointment $appointment): array => $this->mapAppointmentEvent($appointment))->all(),
                ];
                $cursor->addDay();
            }

            $weeks[] = $days;
        }

        return $weeks;
    }

    public function getWeekDays(): array
    {
        $anchor = $this->resolveAnchorDate();
        $start = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $anchor->copy()->endOfWeek(Carbon::SATURDAY);
        $events = $this->getAppointmentsByDate($start, $end);

        return collect(CarbonPeriod::create($start, '1 day', $end))
            ->map(function (Carbon $day) use ($events): array {
                $key = $day->toDateString();

                return [
                    'date' => $key,
                    'label' => $day->format('D'),
                    'day_number' => $day->day,
                    'is_today' => $day->isToday(),
                    'is_selected' => $day->isSameDay($this->resolveAnchorDate()),
                    'events' => $events->get($key, collect())
                        ->map(fn (Appointment $appointment): array => $this->mapAppointmentEvent($appointment, true))
                        ->all(),
                ];
            })
            ->all();
    }

    public function getDayAgenda(): array
    {
        $anchor = $this->resolveAnchorDate();

        return $this->appointmentsQuery()
            ->whereDate('appointment_date', $anchor->toDateString())
            ->orderBy('start_time')
            ->get()
            ->map(fn (Appointment $appointment): array => $this->mapAppointmentEvent($appointment, true))
            ->all();
    }

    public function getSelectedClinicName(): ?string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name;
    }

    public function getListUrl(): string
    {
        return AppointmentResource::getUrl('index');
    }

    public function getProviderOptions(): array
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $clinicId) {
            return [];
        }

        return Provider::query()
            ->with('user')
            ->where('clinic_id', $clinicId)
            ->where('status', true)
            ->get()
            ->sortBy('display_name')
            ->mapWithKeys(fn (Provider $provider): array => [$provider->getKey() => $provider->display_name])
            ->all();
    }

    public function getLocationOptions(): array
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $clinicId) {
            return [];
        }

        return Location::query()
            ->where('clinic_id', $clinicId)
            ->where('status', true)
            ->orderBy('location_name')
            ->pluck('location_name', 'id')
            ->all();
    }

    public function clearFilters(): void
    {
        $this->reset(['providerFilter', 'locationFilter', 'statusFilter', 'verificationFilter']);
    }

    protected function resolveAnchorDate(): Carbon
    {
        return filled($this->anchorDate)
            ? Carbon::parse((string) $this->anchorDate)
            : now();
    }

    protected function appointmentsQuery()
    {
        $selectedClinicId = ClinicPanelScope::selectedClinicId();
        $selectedOrganizationId = ClinicPanelScope::selectedOrganizationId();

        $query = Appointment::query()
            ->with(['patient', 'provider.user', 'location', 'verificationWorkItem']);

        if (! $selectedOrganizationId || ! $selectedClinicId) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('organization_id', $selectedOrganizationId)
            ->where('clinic_id', $selectedClinicId)
            ->when($this->providerFilter, fn ($builder, $providerId) => $builder->where('provider_id', $providerId))
            ->when($this->locationFilter, fn ($builder, $locationId) => $builder->where('location_id', $locationId))
            ->when($this->statusFilter, fn ($builder, $status) => $builder->where('status', $status))
            ->when($this->verificationFilter, fn ($builder, $status) => $builder->where('verification_status', $status))
            ->whereNull('deleted_at');
    }

    protected function getAppointmentsByDate(Carbon $start, Carbon $end): Collection
    {
        return $this->appointmentsQuery()
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (Appointment $appointment): string => $appointment->appointment_date?->toDateString() ?? '');
    }

    protected function mapAppointmentEvent(Appointment $appointment, bool $includeTime = false): array
    {
        $provider = $appointment->provider?->display_name ?: 'Provider';
        $patient = $appointment->patient?->full_name ?: 'Patient';
        $title = trim($provider.' - '.$patient);
        $time = collect([
            $appointment->start_time ? Carbon::parse((string) $appointment->start_time)->format('g:i A') : null,
            $appointment->end_time ? Carbon::parse((string) $appointment->end_time)->format('g:i A') : null,
        ])->filter()->implode(' - ');

        return [
            'id' => $appointment->getKey(),
            'title' => $title,
            'time' => $time,
            'status' => filled($appointment->status) ? str($appointment->status)->replace('_', ' ')->title()->toString() : 'Scheduled',
            'verification_status' => Appointment::VERIFICATION_STATUS_OPTIONS[$appointment->verification_status]
                ?? Appointment::VERIFICATION_STATUS_OPTIONS[Appointment::VERIFICATION_STATUS_NOT_SENT],
            'type' => $appointment->appointment_type ?: 'Appointment',
            'color' => $this->resolveEventColor($appointment),
            'url' => AppointmentResource::getUrl('view', ['record' => $appointment]),
            'include_time' => $includeTime,
        ];
    }

    protected function resolveEventColor(Appointment $appointment): array
    {
        $type = str((string) $appointment->appointment_type)->lower()->toString();
        $palette = [
            ['bg' => '#0f8ab8', 'text' => '#ffffff', 'soft' => '#dff6ff', 'border' => '#83d2ee'],
            ['bg' => '#3b82f6', 'text' => '#ffffff', 'soft' => '#dbeafe', 'border' => '#93c5fd'],
            ['bg' => '#16a34a', 'text' => '#ffffff', 'soft' => '#dcfce7', 'border' => '#86efac'],
            ['bg' => '#7c3aed', 'text' => '#ffffff', 'soft' => '#ede9fe', 'border' => '#c4b5fd'],
        ];

        if (str_contains($type, 'follow')) {
            return ['bg' => '#fff7ed', 'text' => '#c2410c', 'soft' => '#fff7ed', 'border' => '#fdba74'];
        }

        if ($appointment->status === 'completed') {
            return ['bg' => '#16a34a', 'text' => '#ffffff', 'soft' => '#dcfce7', 'border' => '#86efac'];
        }

        if (in_array($appointment->status, ['cancelled', 'no_show'], true)) {
            return ['bg' => '#fee2e2', 'text' => '#b91c1c', 'soft' => '#fef2f2', 'border' => '#fca5a5'];
        }

        return $palette[$appointment->provider_id % count($palette)];
    }
}
