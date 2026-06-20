<?php

namespace App\Filament\Dso\Pages;

use App\Filament\Shared\Pages\RolePermissionsPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RolesAndPermissions extends RolePermissionsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'roles-permissions';

    protected string $view = 'filament.shared.pages.roles-permissions';

    protected static function panelKey(): string
    {
        return 'dso';
    }

    protected static function panelLabel(): string
    {
        return 'DSO';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->status
            && auth()->user()?->hasRole('dso_admin')
            && auth()->user()?->canAccessDsoWorkspace();
    }
}
