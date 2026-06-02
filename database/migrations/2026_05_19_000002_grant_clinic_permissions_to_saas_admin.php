<?php

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::query()->where('name', 'saas_admin')->where('guard_name', 'web')->first();

        if (! $role) {
            return;
        }

        $permissions = Permission::query()
            ->whereIn('name', PanelPermissionMatrix::permissionNamesForPanel('clinic'))
            ->get();

        if ($permissions->isNotEmpty()) {
            $role->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        $role = Role::query()->where('name', 'saas_admin')->where('guard_name', 'web')->first();

        if (! $role) {
            return;
        }

        $role->revokePermissionTo(
            Permission::query()
                ->whereIn('name', PanelPermissionMatrix::permissionNamesForPanel('clinic'))
                ->get()
        );
    }
};
