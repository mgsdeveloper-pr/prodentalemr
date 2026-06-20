<?php

namespace App\Filament\Dso\Pages;

use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\Dso;
use App\Models\Organization;
use App\Models\User;
use App\Support\PanelPermissionMatrix;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class Dashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '';

    protected string $view = 'filament.dso.pages.dashboard';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canAccessDsoWorkspace() ?? false)
            && $user->hasPermissionTo(PanelPermissionMatrix::permissionName('dso', 'dashboard', 'view'));
    }

    public function getDso(): ?Dso
    {
        return auth()->user()?->dso;
    }

    public function getStats(): array
    {
        $clinicIds = $this->clinicIds();
        $organizationIds = $this->organizationIds();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        return [
            'organizations' => count($organizationIds),
            'clinics' => count($clinicIds),
            'active_clinics' => Clinic::query()
                ->whereIn('id', $clinicIds)
                ->where('status', true)
                ->count(),
            'users' => User::query()
                ->where(function (Builder $query) use ($organizationIds): void {
                    $query
                        ->where('dso_id', $this->getDso()?->id)
                        ->orWhereIn('organization_id', $organizationIds);
                })
                ->count(),
            'appointments_mtd' => Appointment::query()
                ->whereIn('clinic_id', $clinicIds)
                ->whereBetween('appointment_date', [$monthStart, $monthEnd])
                ->count(),
            'open_verifications' => BillingWorkItem::query()
                ->whereIn('clinic_id', $clinicIds)
                ->where('status', '!=', BillingWorkItem::STATUS_DONE)
                ->count(),
            'waiting_on_clinic' => BillingWorkItem::query()
                ->whereIn('clinic_id', $clinicIds)
                ->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
                ->count(),
            'completed_mtd' => BillingWorkItem::query()
                ->whereIn('clinic_id', $clinicIds)
                ->where('status', BillingWorkItem::STATUS_DONE)
                ->whereBetween('completed_at', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])
                ->count(),
            'pms_clinics' => Clinic::query()
                ->whereIn('id', $clinicIds)
                ->where('clinic_operations_enabled', true)
                ->count(),
            'verification_clinics' => Clinic::query()
                ->whereIn('id', $clinicIds)
                ->where('verification_services_enabled', true)
                ->count(),
        ];
    }

    public function getClinicRows(): Collection
    {
        $clinicIds = $this->clinicIds();

        return Clinic::query()
            ->with('organization')
            ->whereIn('id', $clinicIds)
            ->orderBy('clinic_name')
            ->limit(8)
            ->get()
            ->map(function (Clinic $clinic): array {
                return [
                    'name' => $clinic->clinic_name,
                    'organization' => $clinic->organization?->name ?? '-',
                    'status' => $clinic->status ? 'Active' : 'Inactive',
                    'services' => collect([
                        $clinic->clinic_operations_enabled ? 'Clinic Operations' : null,
                        $clinic->verification_services_enabled ? 'Verification' : null,
                    ])->filter()->implode(' + ') ?: 'No active module',
                    'open_verifications' => BillingWorkItem::query()
                        ->where('clinic_id', $clinic->id)
                        ->where('status', '!=', BillingWorkItem::STATUS_DONE)
                        ->count(),
                    'appointments_mtd' => Appointment::query()
                        ->where('clinic_id', $clinic->id)
                        ->whereBetween('appointment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                        ->count(),
                ];
            });
    }

    public function getRecentVerificationRows(): Collection
    {
        return BillingWorkItem::query()
            ->with(['clinic', 'patient'])
            ->whereIn('clinic_id', $this->clinicIds())
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (BillingWorkItem $workItem): array => [
                'reference' => $workItem->reference_number,
                'patient' => $workItem->patient?->full_name ?? $workItem->title ?? '-',
                'clinic' => $workItem->clinic?->clinic_name ?? '-',
                'status' => BillingWorkItem::STATUS_OPTIONS[$workItem->normalized_status] ?? str($workItem->normalized_status)->headline()->toString(),
                'priority' => str($workItem->priority ?? 'normal')->headline()->toString(),
                'updated' => optional($workItem->updated_at)->format('d M Y, h:i A') ?? '-',
            ]);
    }

    protected function organizationIds(): array
    {
        $dso = $this->getDso();

        if (! $dso) {
            return [];
        }

        return Organization::query()
            ->where('dso_id', $dso->id)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    protected function clinicIds(): array
    {
        $organizationIds = $this->organizationIds();

        if ($organizationIds === []) {
            return [];
        }

        return Clinic::query()
            ->whereIn('organization_id', $organizationIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
