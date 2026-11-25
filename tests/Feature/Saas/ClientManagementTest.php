<?php

use App\Filament\Saas\Pages\OrganizationWorkspace;
use App\Filament\Saas\Pages\TenantOnboarding;
use App\Filament\Saas\Pages\DsoOnboarding;
use App\Filament\Saas\Resources\Clinics\Pages\CreateClinic;
use App\Models\Dso;
use App\Filament\Saas\Resources\Clinics\Schemas\ClinicForm;
use App\Models\Clinic;
use App\Models\OnboardingDraft;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationTemplateVersion;
use App\Services\ClientOnboardingService;
use App\Support\PanelPermissionMatrix;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('keeps completed onboarding immutable even when a stale draft is saved or activated', function (): void {
    $user = User::factory()->create();
    $service = app(ClientOnboardingService::class);
    $draft = $service->start($user, 'single_clinic', 'managed_service');
    $stale = $draft->fresh();
    $organization = Organization::create(['name' => 'Completed QA', 'owner_name' => 'QA Owner', 'status' => true]);
    $service->activate($draft, $organization);
    $before = $draft->fresh()->getAttributes();

    foreach (['save', 'activate'] as $operation) {
        try {
            $operation === 'save'
                ? $service->save($stale, ['organization_name' => 'Changed'], 1)
                : $service->activate($stale, $organization);
            $this->fail('Completed onboarding accepted '.$operation);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            expect($exception->getStatusCode())->toBe(409);
        }
        expect($draft->fresh()->getAttributes())->toBe($before);
    }
});

it('commits internal onboarding and completion together', function (bool $failCompletion): void {
    $this->seed(RoleSeeder::class);
    seedWorkflowMaster();
    $user = User::factory()->create();
    $user->assignRole('saas_admin');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('saas'));
    $service = app(ClientOnboardingService::class);
    $draft = $service->start($user, 'single_clinic', 'managed_service');
    $service->save($draft, [
        'organization_name' => 'Atomic QA', 'organization_owner_name' => 'QA Owner',
        'owner_name' => 'QA Owner', 'owner_email' => 'atomic-owner@example.test',
        'owner_phone' => '2025550146',
        'clinic_name' => 'Atomic Clinic', 'location_name' => 'Main',
        'location_country' => 'USA', 'location_state' => 'Texas', 'location_city' => 'Austin',
        'clinic_timezone' => 'America/Chicago',
    ], 4);
    if ($failCompletion) {
        $this->partialMock(ClientOnboardingService::class, function ($mock): void {
            $mock->shouldReceive('activate')->once()->andThrow(new \RuntimeException('Completion failed for QA'));
        });
    }
    $browser = Livewire::withQueryParams(['onboarding' => $draft->public_id])->test(TenantOnboarding::class)
        ->fillForm(['attach_subscription' => false, 'owner_password' => 'SyntheticOnly!123', 'owner_password_confirmation' => 'SyntheticOnly!123'])
        ->call('create')->assertHasNoFormErrors();
    if (! $failCompletion) {
        $browser->assertRedirect();
    }
    expect(Organization::count())->toBe($failCompletion ? 0 : 1)
        ->and(Clinic::count())->toBe($failCompletion ? 0 : 1)
        ->and(User::where('email', 'atomic-owner@example.test')->count())->toBe($failCompletion ? 0 : 1)
        ->and($draft->fresh()->status)->toBe($failCompletion ? 'draft' : 'activated');
})->with([false, true]);

it('requires both released forms when creating a verification clinic', function (string $entry, bool $missingShort): void {
    $this->seed(RoleSeeder::class);
    $master = seedWorkflowMaster();
    if ($missingShort) {
        $master->update(['active_short_form' => false]);
    }
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('saas_admin');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('saas'));
    $provisioner = Mockery::mock(\App\Support\VerificationTemplateVersionService::class)->makePartial();
    $provisioner->shouldReceive('provisionRegisteredClinic')
        ->once()->with(Mockery::type(Clinic::class), true)->passthru();
    $this->instance(\App\Support\VerificationTemplateVersionService::class, $provisioner);


    $data = [
        'organization_name' => 'Template QA Organization', 'organization_owner_name' => 'QA Owner',
        'clinic_name' => 'Template QA Clinic', 'clinic_timezone' => 'America/Chicago',
        'location_name' => 'Main', 'location_country' => 'USA', 'location_state' => 'Texas', 'location_city' => 'Austin',
        'attach_subscription' => false,
    ];
    if ($entry === 'direct') {
        $organization = Organization::create(['name' => 'Existing QA Organization', 'owner_name' => 'QA Owner', 'status' => true]);
        $browser = Livewire::test(CreateClinic::class)->fillForm([
            'organization_id' => $organization->id, 'clinic_name' => $data['clinic_name'],
            'verification_services_enabled' => true,
        ])->call('create');
        if ($missingShort) {
            $browser->assertHasFormErrors(['verification_default_form_template']);
        }
    } else {
        $dso = $entry === 'dso';
        $data += $dso ? [
            'dso_name' => 'Template QA DSO', 'dso_primary_contact_name' => 'QA Contact', 'dso_email' => 'dso@example.test',
            'dso_admin_name' => 'QA Admin', 'dso_admin_email' => 'dso-admin@example.test',
            'dso_admin_password' => 'SyntheticOnly!123', 'dso_admin_password_confirmation' => 'SyntheticOnly!123',
        ] : [
            'owner_name' => 'QA Owner', 'owner_email' => 'owner@example.test', 'owner_phone' => '2025550146',
            'owner_password' => 'SyntheticOnly!123', 'owner_password_confirmation' => 'SyntheticOnly!123',
        ];
        $browser = Livewire::test($dso ? DsoOnboarding::class : TenantOnboarding::class)
            ->fillForm($data)->call('create')->assertHasNoFormErrors();
        if ($missingShort) {
            $browser->assertNotified($dso ? 'DSO onboarding failed' : 'Organization onboarding failed');
            expect(Organization::count())->toBe(0)->and(Dso::count())->toBe(0);
        }
    }

    if ($missingShort) {
        expect(Clinic::count())->toBe(0)
            ->and(VerificationTemplateVersion::where('scope', 'clinic')->count())->toBe(0);

        return;
    }

    $browser->assertRedirect();
    $clinic = Clinic::where('clinic_name', $data['clinic_name'])->sole();
    $versions = VerificationTemplateVersion::where('clinic_id', $clinic->id)->get()->keyBy('form_type');
    expect($versions)->toHaveCount(2);
    foreach (['full_form', 'short_form'] as $form) {
        $version = $versions[$form];
        expect($version->source_version_id)->toBe($master->id)
            ->and($version->status)->toBe('published')
            ->and($version->{'active_'.$form})->toBeTrue()
            ->and($version->questions()->count())->toBeGreaterThan(0);
    }
    (new \App\Support\VerificationTemplateVersionService)->provisionRegisteredClinic($clinic, requireBoth: true);
    expect(VerificationTemplateVersion::where('clinic_id', $clinic->id)->pluck('id')->sort()->values()->all())
        ->toBe($versions->pluck('id')->sort()->values()->all());
})->with(['organization', 'dso', 'direct'])->with([false, true]);

it('redirects completed onboarding and rejects stale browser actions', function (string $structure, string $page): void {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('saas_admin');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('saas'));
    $service = app(ClientOnboardingService::class);
    $draft = $service->start($user, $structure, 'managed_service');
    $browser = Livewire::withQueryParams(['onboarding' => $draft->public_id])->test($page);
    $organization = Organization::create(['name' => 'Completed Browser QA', 'owner_name' => 'QA Owner', 'status' => true]);
    $dso = $structure === 'dso' ? Dso::create(['name' => 'Completed DSO QA', 'status' => true]) : null;
    $service->activate($draft, $organization, $dso);
    $before = $draft->fresh()->getAttributes();

    Livewire::withQueryParams(['onboarding' => $draft->public_id])->test($page)->assertRedirect($dso
        ? \App\Filament\Saas\Resources\Dsos\DsoResource::getUrl('view', ['record' => $dso])
        : \App\Filament\Saas\Resources\Organizations\OrganizationResource::getUrl('view', ['record' => $organization]));
    $browser->call('create')->assertStatus(409);
    expect($draft->fresh()->getAttributes())->toBe($before)
        ->and(Organization::count())->toBe(1)
        ->and(Clinic::count())->toBe(0);
})->with([
    ['single_clinic', TenantOnboarding::class],
    ['dso', DsoOnboarding::class],
]);

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

it('keeps template setup best-effort when a new clinic has verification disabled', function () {
    $master = seedWorkflowMaster();
    $master->update(['active_short_form' => false]);
    $organization = Organization::create(['name' => 'Operations Only QA', 'owner_name' => 'QA Owner', 'status' => true]);
    $provisioner = Mockery::mock(\App\Support\VerificationTemplateVersionService::class)->makePartial();
    $provisioner->shouldReceive('provisionRegisteredClinic')
        ->once()->with(Mockery::type(Clinic::class), false)->passthru();
    $this->instance(\App\Support\VerificationTemplateVersionService::class, $provisioner);

    $clinic = Clinic::createWithRequiredVerificationTemplates([
        'organization_id' => $organization->id,
        'clinic_name' => 'Operations Only QA Clinic',
        'clinic_code' => 'TB07-OPS-QA',
        'status' => true,
        'verification_services_enabled' => false,
    ]);

    $copy = VerificationTemplateVersion::where('clinic_id', $clinic->id)->sole();
    expect($clinic->fresh()->hasVerificationServices())->toBeFalse()
        ->and($copy->source_version_id)->toBe($master->id)
        ->and($copy->active_full_form)->toBeTrue()
        ->and($copy->active_short_form)->toBeFalse();
});
