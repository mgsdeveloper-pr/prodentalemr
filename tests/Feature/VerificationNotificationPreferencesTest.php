<?php

use App\Filament\Admin\Pages\VerificationNotificationControl;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\SaasSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

it('persists Urgent flagged across fresh reads and controls in-app delivery after the settings sequence has advanced', function (): void {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    SaasSetting::query()->get()->each->delete();
    $removed = new SaasSetting;
    $removed->forceFill(['id' => 25, 'platform_name' => 'Deleted synthetic settings'])->save();
    $removed->delete();

    $field = 'verification_notify_on_urgent_flagged';
    $clearReadCaches = function (): void {
        Cache::forget('saas_settings.current');
        request()->attributes->remove('prodental.saas_settings.current');
    };

    Livewire::test(VerificationNotificationControl::class)
        ->assertSet('data.'.$field, true)
        ->set('data.'.$field, false)
        ->call('save')->assertHasNoErrors();
    $this->assertDatabaseHas('saas_settings', ['id' => 1, $field => false]);
    $clearReadCaches();
    Livewire::test(VerificationNotificationControl::class)->assertSet('data.'.$field, false);

    $reviewer = User::factory()->create(['status' => true]);
    $reviewer->assignRole('verification_admin');
    $organization = Organization::create(['name' => 'Notification QA', 'owner_name' => 'QA Owner', 'status' => true]);
    $clinic = Clinic::create([
        'organization_id' => $organization->id, 'clinic_name' => 'Notification QA Clinic',
        'clinic_code' => 'NOTIFY-QA', 'timezone' => 'America/New_York',
        'verification_services_enabled' => true, 'status' => true,
    ]);
    $service = ManagedBillingService::create([
        'name' => 'Notification QA Verification', 'slug' => 'notification-qa-verification',
        'category' => 'verification', 'service_level_agreement_hours' => 24,
        'default_priority' => 'normal', 'status' => true,
    ]);
    $work = BillingWorkItem::create([
        'organization_id' => $organization->id, 'clinic_id' => $clinic->id,
        'managed_billing_service_id' => $service->id,
        'title' => 'Synthetic verification request', 'status' => BillingWorkItem::STATUS_PENDING,
        'priority' => 'normal',
    ]);
    $alert = [
        'billing_work_item_id' => $work->id, 'activity_type' => 'urgent_priority_flagged',
        'user_id' => $reviewer->id, 'panel' => 'verification',
    ];
    $event = ['event_type' => 'verification.urgent_priority_flagged', 'subject_id' => $work->id];

    $work->update(['priority' => 'urgent']);
    $this->assertDatabaseHas('billing_work_item_activities', [
        'billing_work_item_id' => $work->id, 'activity_type' => 'urgent_priority_flagged',
    ]);
    $this->assertDatabaseMissing('notification_events', $event);
    $this->assertDatabaseMissing('verification_notifications', $alert);

    Livewire::test(VerificationNotificationControl::class)
        ->assertSet('data.'.$field, false)
        ->set('data.'.$field, true)
        ->call('save')->assertHasNoErrors();
    $this->assertDatabaseHas('saas_settings', ['id' => 1, $field => true]);
    $clearReadCaches();
    Livewire::test(VerificationNotificationControl::class)->assertSet('data.'.$field, true);

    $work->update(['priority' => 'normal']);
    $work->update(['priority' => 'urgent']);
    $this->assertDatabaseHas('notification_events', $event);
    $this->assertDatabaseHas('verification_notifications', $alert);
    $this->assertDatabaseCount('saas_settings', 1);
});

it('reuses the original settings when old installations have extra rows', function (int $originalId): void {
    SaasSetting::query()->get()->each->delete();
    $original = new SaasSetting;
    $original->forceFill([
        'id' => $originalId,
        'platform_name' => 'Existing synthetic configuration',
        'verification_notify_on_urgent_flagged' => false,
    ])->save();

    $duplicate = new SaasSetting;
    $duplicate->forceFill(['id' => $originalId + 1, 'verification_notify_on_urgent_flagged' => true])->save();

    $current = SaasSetting::current();
    expect($current->getKey())->toBe($originalId)
        ->and($current->platform_name)->toBe('Existing synthetic configuration')
        ->and($current->verification_notify_on_urgent_flagged)->toBeFalse();

    $current->update(['verification_notify_on_urgent_assigned' => false]);
    $this->assertDatabaseHas('saas_settings', [
        'id' => $originalId, 'verification_notify_on_urgent_flagged' => false, 'verification_notify_on_urgent_assigned' => false,
    ]);
    $this->assertDatabaseHas('saas_settings', [
        'id' => $originalId + 1, 'verification_notify_on_urgent_flagged' => true, 'verification_notify_on_urgent_assigned' => true,
    ]);
    $this->assertDatabaseCount('saas_settings', 2);
})->with([1, 26]);
