<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LocalAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            Role::findOrCreate('saas_admin', 'web')->name,
            Role::findOrCreate('verification_admin', 'web')->name,
        ];

        $user = User::withTrashed()->updateOrCreate(
            ['email' => 'admin@mgs.com'],
            [
                'name' => 'MGS Admin',
                'password' => Hash::make('Admin@12345'),
                'email_verified_at' => now(),
                'status' => true,
                'default_workspace' => 'verification',
                'allowed_workspaces' => ['saas', 'verification'],
                'deleted_at' => null,
            ],
        );

        $user->syncRoles($roles);
    }
}
