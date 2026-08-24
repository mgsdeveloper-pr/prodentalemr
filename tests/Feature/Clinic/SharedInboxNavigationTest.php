<?php

use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\User;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;

it('shows the clinic-scoped shared inbox in clinic navigation', function (): void {
    $this->seed(RoleSeeder::class);

    $organization = Organization::create([
        'name' => 'Inbox Test Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@inbox.test',
        'status' => true,
    ]);

    $clinic = Clinic::create([
        'organization_id' => $organization->id,
        'clinic_name' => 'Inbox Test Clinic',
        'clinic_code' => 'CLN-INBOX',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $service = ManagedBillingService::create([
        'name' => 'Inbox Verification Service',
        'slug' => 'inbox-verification-service',
        'category' => 'verification',
        'service_level_agreement_hours' => 24,
        'default_priority' => 'normal',
        'status' => true,
    ]);

    ClientServiceEnrollment::create([
        'organization_id' => $organization->id,
        'clinic_id' => $clinic->id,
        'managed_billing_service_id' => $service->id,
        'status' => 'active',
        'clinic_workspace_enabled' => true,
        'start_date' => today(),
    ]);

    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $admin->givePermissionTo(Permission::findOrCreate('clinic.verification_requests.view', 'web'));

    $this->actingAs($admin)
        ->withSession([
            ClinicPanelScope::SESSION_KEY => $clinic->getKey(),
            ClinicWorkspace::SESSION_KEY => ClinicWorkspace::VERIFICATION,
        ])
        ->get('/clinic/shared-inbox')
        ->assertSuccessful()
        ->assertSee('Shared Inbox')
        ->assertSee('Review payer notices');
});
