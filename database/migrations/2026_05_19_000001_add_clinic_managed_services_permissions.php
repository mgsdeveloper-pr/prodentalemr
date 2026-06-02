<?php

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $panel = 'clinic';
        $module = 'managed_services';

        foreach (array_keys(PanelPermissionMatrix::ACTIONS) as $action) {
            Permission::findOrCreate(
                PanelPermissionMatrix::permissionName($panel, $module, $action),
                'web',
            );
        }

        $allModulePermissions = Permission::query()
            ->whereIn('name', collect(array_keys(PanelPermissionMatrix::ACTIONS))
                ->map(fn (string $action): string => PanelPermissionMatrix::permissionName($panel, $module, $action))
                ->all())
            ->get();

        if ($clinicAdmin = Role::query()->where('name', 'clinic_admin')->where('guard_name', 'web')->first()) {
            $clinicAdmin->givePermissionTo($allModulePermissions);
        }

        $viewAndAddPermissions = Permission::query()
            ->whereIn('name', [
                PanelPermissionMatrix::permissionName($panel, $module, 'view'),
                PanelPermissionMatrix::permissionName($panel, $module, 'add'),
            ])
            ->get();

        foreach (['clinic_manager', 'receptionist'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo($viewAndAddPermissions);
            }
        }
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', collect(array_keys(PanelPermissionMatrix::ACTIONS))
                ->map(fn (string $action): string => PanelPermissionMatrix::permissionName('clinic', 'managed_services', $action))
                ->all())
            ->delete();
    }
};
