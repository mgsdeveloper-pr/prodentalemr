<?php

use App\Models\User;
use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (array_keys(User::dsoRoleOptions()) as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        foreach (PanelPermissionMatrix::permissionNamesForPanel('dso') as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $this->grantPermissions('dso_admin', PanelPermissionMatrix::permissionNamesForPanel('dso'));
        $this->grantPermissions('dso_manager', [
            'dso.dashboard.view',
            'dso.clinics.view',
            'dso.clinics.update',
            'dso.reports.view',
            'dso.users.view',
            'dso.users.add',
            'dso.users.update',
        ]);
        $this->grantPermissions('dso_viewer', [
            'dso.dashboard.view',
            'dso.clinics.view',
            'dso.reports.view',
        ]);

        $this->grantPermissions('saas_admin', PanelPermissionMatrix::permissionNamesForPanel('dso'));
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', PanelPermissionMatrix::permissionNamesForPanel('dso'))
            ->delete();

        Role::query()
            ->whereIn('name', array_keys(User::dsoRoleOptions()))
            ->delete();
    }

    protected function grantPermissions(string $roleName, array $permissionNames): void
    {
        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            return;
        }

        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->get();

        $role->givePermissionTo($permissions);
    }
};
