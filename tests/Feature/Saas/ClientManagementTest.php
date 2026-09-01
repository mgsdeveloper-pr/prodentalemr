<?php

use App\Filament\Saas\Pages\OrganizationWorkspace;
use App\Filament\Saas\Resources\Clinics\Schemas\ClinicForm;
use App\Models\Clinic;
use App\Models\OnboardingDraft;
use App\Models\Organization;
use App\Models\User;
use App\Services\ClientOnboardingService;
use App\Support\PanelPermissionMatrix;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('renders the focused client directory for an authorized SaaS user', function (): void {
    $this->seed(RoleSeeder::class);

    $organization = Organization::create([
        'name' => 'Focused Dental Group',
        'owner_name' => 'Client Owner',
        'email' => 'owner@focused.test',
        'status' => true,
        'lifecycle_status' => 'active',
        'onboarding_status' => 'complete',
    ]);

    Clinic::create([
        'organization_id' => $organization->id,
        'clinic_name' => 'Focused Dental Main',
        'clinic_code' => 'FOCUSED-01',
        'timezone' => 'America/New_York',
        'status' => true,
        'verification_services_enabled' => true,
        'managed_services_status' => 'active',
    ]);

    $user = User::factory()->create(['status' => true]);
    $user->assignRole('saas_admin');

    $this->actingAs($user)
        ->get('/saas/client-management')
        ->assertOk()
        ->assertSee('Clients')
        ->assertSee('New Client')
        ->assertSee('Focused Dental Group')
        ->assertSee('Self-Managed')
        ->assertSee('Manage Client')
        ->assertDontSee('Shared vs Local Ownership')
        ->assertDontSee('Quick Registration Paths')
        ->assertDontSee('Open Workspace');
});

it('renders the simplified clinic setup form', function (): void {
    $this->seed(RoleSeeder::class);

    $organization = Organization::create([
        'name' => 'Clinic Setup Group',
        'owner_name' => 'Setup Owner',
        'status' => true,
    ]);

    $user = User::factory()->create(['status' => true]);
    $user->assignRole('saas_admin');

    $this->actingAs($user)
        ->get('/saas/clinics/create?organization_id='.$organization->id)
        ->assertOk()
        ->assertSee('Clinic Information')
        ->assertSee('Verification Setup')
        ->assertSee('Verification model')
        ->assertSee('Default verification template')
        ->assertSee('Advanced Settings')
        ->assertDontSee('Customer Services')
        ->assertDontSee('Clinic PMS status');
});

it('shows client roles and access readiness in the manage client workspace', function (): void {
    $this->seed(RoleSeeder::class);

    $organization = Organization::create([
        'name' => 'Access Review Dental',
        'owner_name' => 'Client Owner',
        'status' => true,
        'lifecycle_status' => 'active',
        'onboarding_status' => 'complete',
    ]);

    $clinic = Clinic::create([
        'organization_id' => $organization->id,
        'clinic_name' => 'Access Review Main',
        'clinic_code' => 'ACCESS-01',
        'timezone' => 'America/New_York',
        'status' => true,
        'verification_services_enabled' => true,
        'verification_service_status' => 'active',
        'service_status' => 'active',
    ]);

    $clientUser = User::factory()->create([
        'name' => 'Dr. Access Review',
        'organization_id' => $organization->id,
        'clinic_id' => $clinic->id,
        'status' => true,
        'email_verified_at' => now(),
    ]);
    $clientUser->assignRole('clinic_admin');

    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $admin->givePermissionTo(Permission::findOrCreate(
        PanelPermissionMatrix::permissionName('saas', 'organizations', 'view'),
        'web',
    ));
    $admin->givePermissionTo(Permission::findOrCreate(
        PanelPermissionMatrix::permissionName('saas', 'users', 'update'),
        'web',
    ));

    $this->actingAs($admin)
        ->get('/saas/client-workspace/'.$organization->id.'?tab=users')
        ->assertOk()
        ->assertSee('Users &amp; Access', false)
        ->assertSee('Dr. Access Review')
        ->assertSee('Clinic Admin')
        ->assertSee('Access Review Main')
        ->assertSee('Verified')
        ->assertSee('Manage');

    Filament::setCurrentPanel(Filament::getPanel('saas'));

    Livewire::test(OrganizationWorkspace::class, ['record' => (string) $organization->id])
        ->call('mountAction', 'manageClientUser', ['user' => $clientUser->id])
        ->assertActionMounted('manageClientUser')
        ->assertSchemaComponentExists('selected_role', 'mountedActionSchema0')
        ->fillForm([
            'selected_role' => 'clinic_manager',
            'clinic_id' => $clinic->id,
            'location_id' => null,
            'status' => true,
        ])
        ->callMountedAction()
        ->assertHasNoErrors();

    expect($clientUser->fresh()->hasRole('clinic_manager'))->toBeTrue()
        ->and($clientUser->fresh()->clinic_id)->toBe($clinic->id)
        ->and($clientUser->fresh()->status)->toBeTrue();
});

it('normalizes a new clinic for verification-only operation', function (): void {
    $data = ClinicForm::normalizeForCreate([
        'verification_services_enabled' => true,
        'managed_services_status' => 'active',
    ]);

    expect($data)
        ->verification_services_enabled->toBeTrue()
        ->managed_services_status->toBe('active')
        ->verification_service_status->toBe('active')
        ->clinic_operations_enabled->toBeFalse()
        ->pms_service_status->toBe('not_enabled');
});

it('supports multiple resumable client onboarding records without storing passwords', function (): void {
    $user = User::factory()->create(['status' => true]);
    $onboarding = app(ClientOnboardingService::class);

    $solo = $onboarding->start($user, 'single_clinic', 'self_service');
    $dso = $onboarding->start($user, 'dso', 'managed_service');

    $onboarding->save($solo, [
        'organization_name' => 'Future Solo Dental',
        'owner_email' => 'owner@future-solo.test',
        'owner_password' => 'NeverPersistThis',
        'owner_password_confirmation' => 'NeverPersistThis',
    ], 2);

    expect(OnboardingDraft::query()->where('user_id', $user->id)->count())->toBe(2)
        ->and($solo->fresh()->public_id)->toHaveLength(26)
        ->and($solo->fresh()->data)->toHaveKey('organization_name', 'Future Solo Dental')
        ->and($solo->fresh()->data)->not->toHaveKey('owner_password')
        ->and($solo->fresh()->data)->not->toHaveKey('owner_password_confirmation')
        ->and($onboarding->resumeUrl($dso))->toContain('onboarding='.$dso->public_id);
});

it('renders resumable organization and dso onboarding workflows', function (): void {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create(['status' => true]);
    $user->assignRole('saas_admin');
    $onboarding = app(ClientOnboardingService::class);
    $organization = $onboarding->start($user, 'organization', 'hybrid');
    $dso = $onboarding->start($user, 'dso', 'managed_service');

    $this->actingAs($user)
        ->get($onboarding->resumeUrl($organization))
        ->assertOk()
        ->assertSee('Multi Location Organization Setup')
        ->assertSee('Review & Activate');

    $this->get($onboarding->resumeUrl($dso))
        ->assertOk()
        ->assertSee('DSO Setup')
        ->assertSee('Review & Activate');
});
