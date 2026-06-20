<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | SaaS Roles
        |--------------------------------------------------------------------------
        */
        Role::findOrCreate('saas_admin', 'web');
        Role::findOrCreate('saas_manager', 'web');
        Role::findOrCreate('saas_user', 'web');

        /*
        |--------------------------------------------------------------------------
        | DSO Roles
        |--------------------------------------------------------------------------
        */
        Role::findOrCreate('dso_admin', 'web');
        Role::findOrCreate('dso_manager', 'web');
        Role::findOrCreate('dso_viewer', 'web');

        /*
        |--------------------------------------------------------------------------
        | Verification Roles
        |--------------------------------------------------------------------------
        */
        Role::findOrCreate('verification_admin', 'web');
        Role::findOrCreate('verification_manager', 'web');
        Role::findOrCreate('verification_user', 'web');

         /*
        |--------------------------------------------------------------------------
        | Clinic Roles
        |--------------------------------------------------------------------------
        */

        Role::findOrCreate('clinic_admin', 'web');
        Role::findOrCreate('clinic_manager', 'web');
        Role::findOrCreate('doctor', 'web');
        Role::findOrCreate('receptionist', 'web');
        Role::findOrCreate('staff', 'web');
    }
    
}
