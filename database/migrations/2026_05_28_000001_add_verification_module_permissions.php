<?php

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $module = 'verification';

        foreach (array_keys(PanelPermissionMatrix::ACTIONS) as $action) {
            Permission::findOrCreate(PanelPermissionMatrix::permissionName('saas', $module, $action), 'web');
        }

        $this->grantPermissions('saas_admin', ['view', 'add', 'update', 'delete']);
        $this->grantPermissions('saas_manager', ['view', 'add', 'update', 'delete']);
        $this->grantPermissions('saas_user', ['view', 'add', 'update']);
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', collect(array_keys(PanelPermissionMatrix::ACTIONS))
                ->map(fn (string $action): string => PanelPermissionMatrix::permissionName('saas', 'verification', $action))
                ->all())
            ->delete();
    }

    protected function grantPermissions(string $roleName, array $actions): void
    {
        $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

        if (! $role) {
            return;
        }

        $permissions = Permission::query()
            ->whereIn('name', collect($actions)
                ->map(fn (string $action): string => PanelPermissionMatrix::permissionName('saas', 'verification', $action))
                ->all())
            ->get();

        $role->givePermissionTo($permissions);
    }
};
