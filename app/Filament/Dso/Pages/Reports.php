<?php

namespace App\Filament\Dso\Pages;

use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Support\DsoWorkspaceScope;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '';

    protected static ?string $slug = 'reports';

    protected string $view = 'filament.dso.pages.reports';

    public string $range = 'current_month';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessDsoWorkspace()
            && auth()->user()?->hasPermissionTo('dso.reports.view');
    }

    public function getRangeLabel(): string
    {
        [$start, $end] = $this->dateRange();

        return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
    }

    public function getClinicReportRows(): Collection
    {
        [$start, $end] = $this->dateRange();
        $clinicIds = $this->clinicIds();

        return Clinic::query()
            ->with('organization')
            ->whereIn('id', $clinicIds)
            ->orderBy('clinic_name')
            ->get()
            ->map(fn (Clinic $clinic): array => [
                'clinic' => $clinic->clinic_name,
                'organization' => $clinic->organization?->name ?? '-',
                'appointments' => Appointment::query()
                    ->where('clinic_id', $clinic->id)
                    ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
                    ->count(),
                'open' => BillingWorkItem::query()
                    ->where('clinic_id', $clinic->id)
                    ->where('status', '!=', BillingWorkItem::STATUS_DONE)
                    ->count(),
                'completed' => BillingWorkItem::query()
                    ->where('clinic_id', $clinic->id)
                    ->where('status', BillingWorkItem::STATUS_DONE)
                    ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                    ->count(),
                'waiting' => BillingWorkItem::query()
                    ->where('clinic_id', $clinic->id)
                    ->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
                    ->count(),
            ]);
    }

    public function getServiceSummary(): array
    {
        $clinicIds = $this->clinicIds();

        return [
            'clinic_operations' => Clinic::query()
                ->whereIn('id', $clinicIds)
                ->where('clinic_operations_enabled', true)
                ->count(),
            'verification' => Clinic::query()
                ->whereIn('id', $clinicIds)
                ->where('verification_services_enabled', true)
                ->count(),
            'both' => Clinic::query()
                ->whereIn('id', $clinicIds)
                ->where('clinic_operations_enabled', true)
                ->where('verification_services_enabled', true)
                ->count(),
            'managed_services' => Clinic::query()
                ->whereIn('id', $clinicIds)
                ->whereIn('managed_services_status', ['active', 'trial'])
                ->count(),
        ];
    }

    protected function clinicIds(): array
    {
        $selectedClinicId = DsoWorkspaceScope::selectedClinicId();

        if ($selectedClinicId) {
            return [$selectedClinicId];
        }

        return Clinic::query()
            ->whereHas('organization', fn ($query) => $query->where('dso_id', auth()->user()?->dso_id))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    protected function dateRange(): array
    {
        return match ($this->range) {
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
