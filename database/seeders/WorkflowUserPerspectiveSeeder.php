<?php

namespace Database\Seeders;

use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WorkflowUserPerspectiveSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $clinic = Clinic::query()
            ->where('clinic_name', 'Demo Solo Dental Clinic')
            ->first();

        if (! $clinic) {
            $this->command?->warn('Demo Solo Dental Clinic was not found. Workflow users were not created.');

            return;
        }

        $clinicRole = Role::findOrCreate('receptionist', 'web');
        $verificationRole = Role::findOrCreate('verification_user', 'web');
        $verificationManagerRole = Role::findOrCreate('verification_manager', 'web');

        $clinicPermissions = collect(['view', 'add', 'update'])
            ->map(fn (string $action): Permission => Permission::findOrCreate("clinic.verification_requests.{$action}", 'web'))
            ->all();
        $clinicRole->givePermissionTo($clinicPermissions);

        $clinicUser = User::withTrashed()->updateOrCreate(
            ['email' => 'demo.clinic.user@prodental.test'],
            [
                'name' => 'Demo Clinic User',
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
                'location_id' => $clinic->locations()->value('id'),
                'password' => Hash::make('DemoUser@123'),
                'email_verified_at' => now(),
                'status' => true,
                'default_workspace' => 'clinic',
                'allowed_workspaces' => ['clinic'],
                'deleted_at' => null,
            ],
        );
        $clinicUser->syncRoles([$clinicRole]);

        $verificationUser = User::withTrashed()->updateOrCreate(
            ['email' => 'demo.verification.user@prodental.test'],
            [
                'name' => 'Demo Verification User',
                'password' => Hash::make('DemoUser@123'),
                'email_verified_at' => now(),
                'status' => true,
                'default_workspace' => 'verification',
                'allowed_workspaces' => ['verification'],
                'deleted_at' => null,
            ],
        );
        $verificationUser->syncRoles([$verificationRole]);
        $verificationUser->verificationClinics()->syncWithoutDetaching([$clinic->id]);

        $verificationManager = User::withTrashed()->updateOrCreate(
            ['email' => 'demo.verification.manager@prodental.test'],
            [
                'name' => 'Demo Verification Manager',
                'password' => Hash::make('DemoUser@123'),
                'email_verified_at' => now(),
                'status' => true,
                'default_workspace' => 'verification',
                'allowed_workspaces' => ['verification'],
                'deleted_at' => null,
            ],
        );
        $verificationManager->syncRoles([$verificationManagerRole]);
        $verificationManager->verificationClinics()->syncWithoutDetaching([$clinic->id]);

        BillingWorkItem::query()
            ->where('clinic_id', $clinic->id)
            ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, 'completed', 'cancelled'])
            ->oldest('id')
            ->limit(1)
            ->update(['assigned_to' => $verificationUser->id]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Normal Clinic and Verification workflow users are ready.');
    }
}
