<?php

use App\Models\Clinic;
use App\Models\OnboardingDraft;
use App\Models\Organization;
use App\Models\User;
use App\Filament\Saas\Resources\Clinics\Schemas\ClinicForm;
use App\Services\ClientOnboardingService;
use Database\Seeders\RoleSeeder;

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
        ->assertSee('Managed Service')
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
        ->get('/saas/clinics/create?organization_id=' . $organization->id)
        ->assertOk()
        ->assertSee('Clinic Information')
        ->assertSee('Verification Setup')
        ->assertSee('Verification model')
        ->assertSee('Default verification template')
        ->assertSee('Advanced Settings')
        ->assertDontSee('Customer Services')
        ->assertDontSee('Clinic PMS status');
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
