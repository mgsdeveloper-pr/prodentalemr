<?php

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissionNames = PanelPermissionMatrix::permissionNamesForModule('clinic', 'locations');

        foreach ($permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        foreach (['clinic_admin', 'saas_admin'] as $roleName) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        $permissionNames = PanelPermissionMatrix::permissionNamesForModule('clinic', 'locations');
        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['clinic_admin', 'saas_admin'])
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));
    }
};
