<?php

use App\Filament\Admin\Widgets\ManagedServicesQuickLinks;
use App\Filament\Admin\Widgets\VerificationAttentionQueue;
use App\Filament\Clinic\Resources\VerificationRequests\Pages\ListVerificationRequests;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\User;
use App\Support\AdminClinicScope;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('renders queue totals and toggles the existing attention filter', function (): void {
    Livewire::test(ManagedServicesQuickLinks::class)
        ->assertSee('Work Queues')
        ->assertSee('New & Pending')
        ->assertSee('Urgent Requests')
        ->assertSeeHtml('aria-pressed="false"')
        ->call('applyFilter', 'urgent_requests')
        ->assertSet('activeFilter', 'urgent_requests')
        ->assertSeeHtml('aria-pressed="true"')
        ->assertDispatched('verification-attention-filter-changed', filter: 'urgent_requests')
        ->call('applyFilter', 'urgent_requests')
        ->assertSet('activeFilter', null)
        ->assertDispatched('verification-attention-filter-changed', filter: null);
});

it('shows the same time zone on both queues and excludes retired PMS controls from Clinic', function (): void {
    $this->seed(RoleSeeder::class);
    $organization = Organization::create(['name' => 'Queue QA', 'owner_name' => 'QA Owner', 'status' => true]);
    $clinic = Clinic::create([
        'organization_id' => $organization->id, 'clinic_name' => 'Queue QA Clinic', 'clinic_code' => 'QUEUE-QA',
        'timezone' => 'America/New_York', 'verification_services_enabled' => true, 'status' => true,
    ]);
    $service = ManagedBillingService::create([
        'name' => 'Queue QA Verification', 'slug' => 'qa-queue-verification', 'category' => 'verification',
        'service_level_agreement_hours' => 24, 'default_priority' => 'normal', 'status' => true,
    ]);
    ClientServiceEnrollment::create([
        'organization_id' => $organization->id, 'clinic_id' => $clinic->id,
        'managed_billing_service_id' => $service->id, 'status' => 'active',
        'clinic_workspace_enabled' => true, 'start_date' => today(),
    ]);
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $clinicAdmin = User::factory()->create([
        'organization_id' => $organization->id, 'clinic_id' => $clinic->id, 'status' => true,
    ]);
    $clinicAdmin->assignRole('clinic_admin');
    $clinicAdmin->givePermissionTo(Permission::findOrCreate('clinic.verification_requests.view', 'web'));
    $work = BillingWorkItem::create([
        'organization_id' => $organization->id, 'clinic_id' => $clinic->id,
        'managed_billing_service_id' => $service->id, 'title' => 'Synthetic queue request',
        'status' => BillingWorkItem::STATUS_PENDING, 'priority' => 'normal',
        'due_at' => '2026-12-05 14:30:00', 'pms_sync_status' => 'unsynced',
    ]);

    $this->actingAs($clinicAdmin)->withSession([
        AdminClinicScope::SESSION_KEY => $clinic->id,
        ClinicPanelScope::SESSION_KEY => $clinic->id,
        ClinicWorkspace::SESSION_KEY => ClinicWorkspace::VERIFICATION,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('clinic'));
    Livewire::test(ListVerificationRequests::class)
        ->assertSee('Dec 05, 2026 02:30 PM UTC')
        ->assertDontSee('PMS sync')
        ->assertDontSee('Not Synced');

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Livewire::test(VerificationAttentionQueue::class)->assertSee('Dec 05, 2026 02:30 PM UTC');

    expect($work->fresh()->pms_sync_status)->toBe('unsynced');
});
