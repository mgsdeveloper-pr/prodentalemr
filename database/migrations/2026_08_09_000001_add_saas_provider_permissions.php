<?php

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissionNames = PanelPermissionMatrix::permissionNamesForModule('saas', 'providers');

        foreach ($permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        Role::findOrCreate('saas_admin', 'web')->givePermissionTo($permissions);
    }

    public function down(): void
    {
        $permissionNames = PanelPermissionMatrix::permissionNamesForModule('saas', 'providers');
        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'saas_admin')
            ->first()
            ?->revokePermissionTo($permissions);
    }
};
