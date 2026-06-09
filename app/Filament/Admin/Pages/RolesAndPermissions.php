<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Shared\Pages\RolePermissionsPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RolesAndPermissions extends RolePermissionsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Access Management';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'roles-permissions';

    protected string $view = 'filament.shared.pages.roles-permissions';

    protected static function panelKey(): string
    {
        return 'verification';
    }

    protected static function panelLabel(): string
    {
        return 'Verification';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->canManageVerificationRolePermissions()
            && $user?->canAccessVerificationModule('roles_permissions'));
    }
}
