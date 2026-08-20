<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::findOrCreate('verification_manager', 'web');

        foreach (['verification.settings.view', 'verification.settings.update'] as $permissionName) {
            $permission = Permission::findOrCreate($permissionName, 'web');
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $role = Role::query()
            ->where('name', 'verification_manager')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            return;
        }

        $role->revokePermissionTo([
            'verification.settings.view',
            'verification.settings.update',
        ]);
    }
};
