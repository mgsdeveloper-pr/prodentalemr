<?php

use App\Filament\Clinic\Resources\PortalCredentials\Pages\ListPortalCredentials;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\PortalCredential;
use App\Models\User;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('opens the clinic-scoped credential create page and renders accessible protected dialogs', function (): void {
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

    $credential = PortalCredential::create([
        'organization_id' => $organization->id,
        'clinic_id' => $clinic->id,
        'portal_name' => 'Synthetic Dental Portal',
        'username' => 'synthetic-user',
        'password' => 'synthetic-password',
        'visible_to_clinic' => true,
        'is_active' => true,
    ]);
    $credential->securityQuestions()->create([
        'question' => 'What is the synthetic answer?',
        'answer' => 'synthetic-secret-answer',
        'sort_order' => 1,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('clinic'));

    Livewire::test(ListPortalCredentials::class)
        ->assertSee('Actions for Synthetic Dental Portal')
        ->assertSeeHtml('aria-controls="portal-credential-options-'.$credential->id.'"')
        ->assertDontSee('synthetic-user')
        ->call('openCredentialInfo', $credential->id)
        ->assertSeeHtml('aria-labelledby="credential-details-title"')
        ->assertSeeHtml('x-trap.inert.noscroll="true"')
        ->assertDontSee('synthetic-password')
        ->call('closeCredentialInfo')
        ->assertDontSeeHtml('aria-labelledby="credential-details-title"')
        ->call('openPasswordEditor', $credential->id)
        ->assertSeeHtml('aria-labelledby="password-modal-title"')
        ->assertDontSee('synthetic-password')
        ->call('closePasswordEditor')
        ->assertDontSeeHtml('aria-labelledby="password-modal-title"')
        ->call('openSecurityQuestions', $credential->id)
        ->assertSeeHtml('aria-labelledby="security-questions-modal-title"')
        ->assertDontSee('synthetic-secret-answer')
        ->call('closeSecurityQuestions')
        ->assertDontSeeHtml('aria-labelledby="security-questions-modal-title"');
});
