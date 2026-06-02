<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Shared\Pages\RolePermissionsPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RolesAndPermissions extends RolePermissionsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Access Management';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'roles-permissions';

    protected string $view = 'filament.shared.pages.roles-permissions';

    protected static function panelKey(): string
    {
        return 'clinic';
    }

    protected static function panelLabel(): string
    {
        return 'Clinic';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->status
            && filled($user?->organization_id)
            && filled($user?->clinic_id)
            && $user->hasRole('clinic_admin');
    }
}
