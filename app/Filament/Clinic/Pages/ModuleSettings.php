<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Shared\Pages\ModuleSettingsPage;
use App\Support\ClinicWorkspace;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ModuleSettings extends ModuleSettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Module Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'module-settings';

    protected string $view = 'filament.shared.pages.module-settings';

    protected static function panelKey(): string
    {
        return 'clinic';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->status
            && ClinicWorkspace::selectedOrDefault(ClinicWorkspace::clinicForUser()) !== ClinicWorkspace::VERIFICATION
            && filled($user?->organization_id)
            && filled($user?->clinic_id)
            && $user->hasRole('clinic_admin');
    }
}
