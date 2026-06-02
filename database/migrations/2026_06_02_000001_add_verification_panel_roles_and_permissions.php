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
        foreach (array_keys(User::verificationRoleOptions()) as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        foreach (PanelPermissionMatrix::permissionNamesForPanel('verification') as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $this->grantPermissions('verification_admin', PanelPermissionMatrix::permissionNamesForPanel('verification'));
        $this->grantPermissions('verification_manager', [
            'verification.verification.add',
            'verification.verification.view',
            'verification.verification.update',
            'verification.portal_credentials.view',
            'verification.portal_credentials.add',
            'verification.portal_credentials.update',
            'verification.insurance_directory.view',
            'verification.insurance_directory.add',
            'verification.insurance_directory.update',
            'verification.reports.view',
            'verification.notifications.view',
            'verification.users.add',
            'verification.users.view',
            'verification.users.update',
        ]);
        $this->grantPermissions('verification_user', [
            'verification.verification.add',
            'verification.verification.view',
            'verification.verification.update',
            'verification.portal_credentials.view',
            'verification.insurance_directory.view',
            'verification.reports.view',
            'verification.notifications.view',
        ]);

        $this->grantPermissions('saas_admin', PanelPermissionMatrix::permissionNamesForPanel('verification'));
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', PanelPermissionMatrix::permissionNamesForPanel('verification'))
            ->delete();

        Role::query()
            ->whereIn('name', array_keys(User::verificationRoleOptions()))
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
