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
            $module = 'portal_credentials';

            foreach (array_keys(PanelPermissionMatrix::ACTIONS) as $action) {
                Permission::findOrCreate(PanelPermissionMatrix::permissionName($panel, $module, $action), 'web');
            }
        }

        $this->grantPermissions('saas_admin', ['view', 'add', 'update', 'delete'], 'saas');
        $this->grantPermissions('saas_manager', ['view', 'add', 'update', 'delete'], 'saas');
        $this->grantPermissions('saas_user', ['view'], 'saas');

        $this->grantPermissions('clinic_admin', ['view', 'add', 'update', 'delete'], 'clinic');
        $this->grantPermissions('clinic_manager', ['view', 'update'], 'clinic');
    }

    public function down(): void
    {
        foreach (['saas', 'clinic'] as $panel) {
            Permission::query()
                ->whereIn('name', collect(array_keys(PanelPermissionMatrix::ACTIONS))
                    ->map(fn (string $action): string => PanelPermissionMatrix::permissionName($panel, 'portal_credentials', $action))
                    ->all())
                ->delete();
        }
    }

    protected function grantPermissions(string $roleName, array $actions, string $panel): void
    {
        $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

        if (! $role) {
            return;
        }

        $permissions = Permission::query()
            ->whereIn('name', collect($actions)
                ->map(fn (string $action): string => PanelPermissionMatrix::permissionName($panel, 'portal_credentials', $action))
                ->all())
            ->get();

        $role->givePermissionTo($permissions);
    }
};
