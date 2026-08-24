<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Shared\Pages\RolePermissionsPage;
use App\Support\ClinicAdministrationAccess;
use App\Support\ClinicPanelScope;
use App\Support\SaasSupportAccess;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RolesAndPermissions extends RolePermissionsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Clinic Management';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?int $navigationSort = 5;

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
        return ClinicAdministrationAccess::canView('roles_permissions');
    }

    public function canCreateRole(): bool
    {
        return ClinicAdministrationAccess::canMutate('roles_permissions', 'add');
    }

    public function canEditSelectedRole(): bool
    {
        return ClinicAdministrationAccess::canMutate('roles_permissions', 'update')
            && parent::canEditSelectedRole();
    }

    public function createRole(): void
    {
        parent::createRole();

        $role = filled($this->selectedRole) ? Role::findByName($this->selectedRole, 'web') : null;

        if ($role && static::supportModeMatchesClinic()) {
            SaasSupportAccess::recordModelEvent('support_clinic_role_created', $role, [], [
                'role_name' => $role->name,
            ]);
        }
    }

    public function savePermissions(): void
    {
        $role = filled($this->selectedRole) ? Role::findByName($this->selectedRole, 'web') : null;
        $before = $role?->permissions()->pluck('name')->sort()->values()->all() ?? [];

        parent::savePermissions();

        if ($role && static::supportModeMatchesClinic()) {
            SaasSupportAccess::recordModelEvent('support_clinic_role_permissions_updated', $role, [
                'permissions' => $before,
            ], [
                'permissions' => $role->fresh()->permissions()->pluck('name')->sort()->values()->all(),
            ]);
        }
    }

    protected static function supportModeMatchesClinic(): bool
    {
        $clinic = ClinicPanelScope::selectedClinic();

        return $clinic && SaasSupportAccess::matchesScope((int) $clinic->organization_id, (int) $clinic->getKey());
    }
}
