<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Shared\Pages\RolePermissionsPage;
use App\Models\User;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RolesAndPermissions extends RolePermissionsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?int $navigationSort = 55;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'roles-permissions';

    protected string $view = 'filament.shared.pages.roles-permissions';

    protected static function panelKey(): string
    {
        return 'saas';
    }

    protected static function panelLabel(): string
    {
        return 'SaaS';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->status
            && auth()->user()?->hasRole('saas_admin');
    }
}
