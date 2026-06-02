<?php

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['saas', 'clinic'] as $panel) {
            foreach (PanelPermissionMatrix::permissionNamesForPanel($panel) as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            $adminRoleName = PanelPermissionMatrix::adminRole($panel);

            if (! $adminRoleName) {
                continue;
            }

            $adminRole = Role::findByName($adminRoleName, 'web');
            $panelPermissions = Permission::query()
                ->whereIn('name', PanelPermissionMatrix::permissionNamesForPanel($panel))
                ->get();

            $adminRole->givePermissionTo($panelPermissions);
        }
    }

    public function down(): void
    {
        foreach (['saas', 'clinic'] as $panel) {
            Permission::query()
                ->whereIn('name', PanelPermissionMatrix::permissionNamesForPanel($panel))
                ->delete();
        }
    }
};
