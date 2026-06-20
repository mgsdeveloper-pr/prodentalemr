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

class ClinicDirectory extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Network';

    protected static ?string $navigationLabel = 'Clinic Directory';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '';

    protected static ?string $slug = 'clinics';

    protected string $view = 'filament.dso.pages.clinic-directory';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessDsoWorkspace()
            && auth()->user()?->hasPermissionTo('dso.clinics.view');
    }

    public function getSelectedClinic(): ?Clinic
    {
        return DsoWorkspaceScope::selectedClinic();
    }

    public function getClinicOptions(): array
    {
        return DsoWorkspaceScope::clinicOptions();
    }

    public function getClinicRows(): Collection
    {
        return Clinic::query()
            ->with('organization')
            ->whereHas('organization', fn ($query) => $query->where('dso_id', auth()->user()?->dso_id))
            ->orderBy('clinic_name')
            ->get()
            ->map(function (Clinic $clinic): array {
                $monthStart = now()->startOfMonth()->toDateString();
                $monthEnd = now()->endOfMonth()->toDateString();

                return [
                    'id' => $clinic->id,
                    'name' => $clinic->clinic_name,
                    'organization' => $clinic->organization?->name ?? '-',
                    'timezone' => $clinic->timezone ?? '-',
                    'status' => $clinic->status ? 'Active' : 'Inactive',
                    'services' => collect([
                        $clinic->clinic_operations_enabled ? 'Clinic Operations' : null,
                        $clinic->verification_services_enabled ? 'Verification' : null,
                    ])->filter()->implode(' + ') ?: 'No active module',
                    'appointments_mtd' => Appointment::query()
                        ->where('clinic_id', $clinic->id)
                        ->whereBetween('appointment_date', [$monthStart, $monthEnd])
                        ->count(),
                    'open_verifications' => BillingWorkItem::query()
                        ->where('clinic_id', $clinic->id)
                        ->where('status', '!=', BillingWorkItem::STATUS_DONE)
                        ->count(),
                    'waiting_on_clinic' => BillingWorkItem::query()
                        ->where('clinic_id', $clinic->id)
                        ->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
                        ->count(),
                    'is_selected' => DsoWorkspaceScope::selectedClinicId() === (int) $clinic->id,
                ];
            });
    }
}
