<?php

use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\User;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;

it('shows and opens the clinic-scoped portal credential create action', function (): void {
    $this->seed(RoleSeeder::class);

    $organization = Organization::create([
        'name' => 'Credential Test Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@credentials.test',
        'status' => true,
    ]);

    $clinic = Clinic::create([
        'organization_id' => $organization->id,
        'clinic_name' => 'Credential Test Clinic',
        'clinic_code' => 'CLN-CREDENTIAL',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $service = ManagedBillingService::create([
        'name' => 'Credential Verification Service',
        'slug' => 'credential-verification-service',
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

    $admin = User::factory()->create([
        'organization_id' => null,
        'clinic_id' => null,
        'status' => true,
    ]);
    $admin->assignRole('saas_admin');
    $admin->givePermissionTo(collect(['view', 'add', 'update'])
        ->map(fn (string $action): Permission => Permission::findOrCreate("clinic.portal_credentials.{$action}", 'web')));

    $session = [
        ClinicPanelScope::SESSION_KEY => $clinic->getKey(),
        ClinicWorkspace::SESSION_KEY => ClinicWorkspace::VERIFICATION,
    ];

    $this->actingAs($admin)
        ->withSession($session)
        ->get('/clinic/portal-credentials')
        ->assertSuccessful()
        ->assertSee('Add Credential');

    $this->withSession($session)
        ->get('/clinic/portal-credentials/create')
        ->assertSuccessful()
        ->assertSee('Portal Name');
});
