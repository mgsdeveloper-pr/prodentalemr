<?php

use App\Filament\Clinic\Pages\VerificationSharedInboxSettings;
use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationInboxMailbox;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
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

    $clinicAdmin = User::factory()->create([
        'status' => true,
        'organization_id' => $organization->id,
        'clinic_id' => $clinic->id,
    ]);
    $clinicAdmin->assignRole('clinic_admin');
    $this->actingAs($clinicAdmin);

    $this->get('/clinic/verification-settings')
        ->assertSuccessful()
        ->assertSee('/clinic/shared-inbox-settings', false);
    $this->get('/clinic/shared-inbox-settings')
        ->assertSuccessful()
        ->assertSee('Mailbox Connection')
        ->assertSee('Inbox Test Clinic');

    Filament::setCurrentPanel(Filament::getPanel('clinic'));
    Livewire::test(VerificationSharedInboxSettings::class)
        ->set('data.verification_inbox_provider', 'Clinic test mailbox')
        ->set('data.verification_inbox_password', 'test-secret-not-a-real-password')
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertSet('data.verification_inbox_password', '');

    $mailbox = VerificationInboxMailbox::where('clinic_id', $clinic->id)->firstOrFail();
    expect($mailbox->verification_inbox_provider)->toBe('Clinic test mailbox');
    expect($mailbox->verification_inbox_password)->toBe('test-secret-not-a-real-password');

    $clinicAdmin->syncRoles(['staff']);
    $this->get('/clinic/shared-inbox-settings')->assertForbidden();

    Clinic::create([
        'organization_id' => $organization->id,
        'clinic_name' => 'Second Clinic',
        'clinic_code' => 'CLN-INBOX-SECOND',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);
    $this->actingAs($admin)->withSession([ClinicPanelScope::SESSION_KEY => null]);
    $this->get('/clinic/verification-settings')
        ->assertSuccessful()
        ->assertSee('/clinic/shared-inbox-settings', false);
    $this->get('/clinic/shared-inbox-settings')
        ->assertSuccessful()
        ->assertSee('Select a clinic to view inbox settings')
        ->assertDontSee('Mailbox Connection');
    $count = VerificationInboxMailbox::count();
    Livewire::test(VerificationSharedInboxSettings::class)->call('save');
    expect(VerificationInboxMailbox::count())->toBe($count);
});
