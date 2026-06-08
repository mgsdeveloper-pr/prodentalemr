<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::query()
            ->where('name', 'verification_user')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            return;
        }

        $permission = Permission::findOrCreate('verification.portal_credentials.update', 'web');

        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $role = Role::query()
            ->where('name', 'verification_user')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            return;
        }

        if ($role->hasPermissionTo('verification.portal_credentials.update')) {
            $role->revokePermissionTo('verification.portal_credentials.update');
        }
    }
};
