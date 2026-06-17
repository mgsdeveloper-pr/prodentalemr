<?php

namespace App\Filament\Clinic\Pages;

use App\Support\ClinicWorkspace;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Dashboard';

    public static function canAccess(): bool
    {
        $clinic = ClinicWorkspace::clinicForUser();
        $workspace = ClinicWorkspace::selectedOrDefault($clinic);

        return in_array($workspace, [ClinicWorkspace::VERIFICATION, ClinicWorkspace::CLINIC_PMS], true);
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return ClinicWorkspace::selected() === ClinicWorkspace::VERIFICATION
            ? 'Dashboard'
            : 'Dashboard';
    }

    public static function getNavigationSort(): ?int
    {
        return ClinicWorkspace::selected() === ClinicWorkspace::VERIFICATION ? 1 : 1;
    }
}
