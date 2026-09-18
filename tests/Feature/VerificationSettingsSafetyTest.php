<?php

use App\Filament\Admin\Pages\UserMailboxSettingsPage;
use App\Filament\Admin\Pages\VerificationAssignmentManagement;
use App\Filament\Admin\Pages\VerificationInboxSettings;
use App\Filament\Admin\Pages\VerificationNotificationControl;
use App\Filament\Admin\Pages\VerificationSettings;
use App\Http\Controllers\Verification\VerificationResultPdfController;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\SaasSetting;
use App\Models\User;
use App\Models\VerificationInboxMessage;
use App\Services\Verification\PdfPresetService;
use App\Support\AdminClinicScope;
use App\Support\UserMailboxService;
use App\Support\VerificationInboxService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create(['status' => true]);
    $this->admin->assignRole('saas_admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $organization = Organization::create([
        'name' => 'Settings Group', 'owner_name' => 'Owner',
        'email' => 'settings@example.test', 'phone' => '5551002000', 'status' => true,
    ]);
    foreach (['A', 'B'] as $suffix) {
        $this->{'clinic'.$suffix} = Clinic::create([
            'organization_id' => $organization->id, 'clinic_name' => 'Clinic '.$suffix,
            'clinic_code' => 'SAFE-'.$suffix, 'timezone' => 'America/New_York',
            'verification_services_enabled' => true, 'status' => true,
        ]);
    }
    session([AdminClinicScope::SESSION_KEY => $this->clinicA->id]);
});

it('saves notification preferences after a reactive request', function () {
    Livewire::test(VerificationNotificationControl::class)
        ->set('data.verification_notify_on_urgent_flagged', false)
        ->call('save')->assertHasNoErrors();
    expect(SaasSetting::current()->fresh()->verification_notify_on_urgent_flagged)->toBeFalse();
});

it('restricts platform-wide controls to SaaS administrators', function () {
    $manager = User::factory()->create(['status' => true]);
    $manager->assignRole('verification_manager');
    $this->actingAs($manager);
    expect(VerificationNotificationControl::canAccess())->toBeFalse();
    expect(VerificationAssignmentManagement::canAccess())->toBeFalse();
});

it('blocks stale PDF settings after switching clinics', function () {
    $page = Livewire::test(VerificationSettings::class);
    session([AdminClinicScope::SESSION_KEY => $this->clinicB->id]);
    $page->call('save')->assertForbidden();
    expect($this->clinicB->pdfPresets()->count())->toBe(0);
});

it('shows a PDF empty state without a selected clinic', function () {
    session()->forget(AdminClinicScope::SESSION_KEY);
    Livewire::test(VerificationSettings::class)->assertSee('Select a clinic to view PDF settings')
        ->assertDontSee('Save Default')->call('save')->assertForbidden();
});

it('opens simplified output settings without creating presets or changing the saved mode', function () {
    $this->clinicA->update(['verification_pdf_output_mode' => 'custom_landscape']);
    Livewire::test(VerificationSettings::class)
        ->assertSet('data.verification_pdf_output_mode', 'custom_landscape')
        ->assertDontSee('Create New Preset')->assertDontSee('Preset name')
        ->assertDontSee('Custom output sections')
        ->set('data.verification_pdf_output_mode', 'standard')->assertSee('Unsaved selection');
    expect($this->clinicA->fresh()->verification_pdf_output_mode)->toBe('custom_landscape');
    expect($this->clinicA->pdfPresets()->count())->toBe(0);
});

it('saves only the selected output and preserves existing preset configuration', function () {
    $service = app(PdfPresetService::class);
    $preset = $service->saveForClinic($this->clinicA, [
        'name' => 'Approved clinic copy', 'description' => 'Keep this configuration',
        'output_mode' => 'custom_landscape', 'section_keys' => ['patient_information'],
        'question_ids' => [12, 34], 'show_blank_rows' => false, 'is_default' => true,
    ]);
    Livewire::test(VerificationSettings::class)
        ->set('data.verification_pdf_output_mode', 'custom_portrait')
        ->call('save')->assertHasNoErrors()->assertDontSee('Unsaved selection');
    expect($preset->fresh()->output_mode)->toBe('custom_portrait');
    expect($preset->fresh()->name)->toBe('Approved clinic copy');
    expect($preset->fresh()->question_ids)->toBe([12, 34]);
    expect($preset->fresh()->section_keys)->toBe(['patient_information']);
    expect($this->clinicA->fresh()->verification_pdf_output_mode)->toBe('custom_portrait');
    expect($this->clinicA->pdfPresets()->count())->toBe(1);
    expect($this->clinicB->pdfPresets()->count())->toBe(0);
});

it('rejects invalid output selections', function () {
    Livewire::test(VerificationSettings::class)
        ->set('data.verification_pdf_output_mode', 'invalid')
        ->call('save')->assertHasErrors(['data.verification_pdf_output_mode']);
});

it('preserves a legacy output when clinic presets are initialized', function () {
    $this->clinicA->update(['verification_pdf_output_mode' => 'custom_portrait']);
    $service = app(PdfPresetService::class);
    $service->seedDefaultsForClinic($this->clinicA);
    expect($this->clinicA->fresh()->verification_pdf_output_mode)->toBe('custom_portrait');
    expect($service->defaultForClinic($this->clinicA)->output_mode)->toBe('custom_portrait');
});

it('uses the clinic default preset for PDF requests and matches draft mode blank-row behavior', function () {
    $service = app(PdfPresetService::class);
    $preset = $service->saveForClinic($this->clinicA, [
        'name' => 'Default report', 'output_mode' => 'standard',
        'show_blank_rows' => true, 'is_default' => true,
    ]);
    $controller = new class extends VerificationResultPdfController
    {
        public function settings($request, $item): array
        {
            $preset = $this->resolvePreset($request, $item);
            $mode = $this->resolveMode($request, $item, $preset);

            return [$preset?->id, $mode, $this->resolveShowBlankRows($request, $mode, $preset)];
        }
    };
    $item = new BillingWorkItem(['clinic_id' => $this->clinicA->id]);
    $item->setRelation('clinic', $this->clinicA->fresh());
    expect($controller->settings(Request::create('/'), $item))
        ->toBe([$preset->id, 'standard', true]);
    expect($controller->settings(Request::create('/?mode=custom_landscape'), $item))
        ->toBe([$preset->id, 'custom_landscape', false]);
    $service->setDefaultOutputMode($this->clinicA, 'custom_landscape');
    $item->setRelation('clinic', $this->clinicA->fresh());
    expect($controller->settings(Request::create('/'), $item))
        ->toBe([$preset->id, 'custom_landscape', false]);
});

it('tests draft inbox settings without persisting them', function () {
    $mailbox = app(VerificationInboxService::class)->mailbox($this->clinicA->id, true);
    $mailbox->update(['verification_inbox_host' => 'saved.example.test', 'verification_inbox_password' => 'saved-secret']);
    $mock = Mockery::mock(VerificationInboxService::class)->makePartial();
    $mock->shouldReceive('testMailboxConnection')->once()->withArgs(fn ($draft) => $draft->verification_inbox_host === 'draft.example.test' && $draft->verification_inbox_password === 'saved-secret'
    )->andReturn(['ok' => false, 'message' => 'Test failed']);
    app()->instance(VerificationInboxService::class, $mock);
    Livewire::test(VerificationInboxSettings::class)
        ->set('data.verification_inbox_host', 'draft.example.test')
        ->call('testConnection')->assertHasNoErrors();
    expect($mailbox->fresh()->verification_inbox_host)->toBe('saved.example.test');
});

it('does not save inbox drafts during sync or after switching clinics', function () {
    $mailbox = app(VerificationInboxService::class)->mailbox($this->clinicA->id, true);
    $mailbox->update(['verification_inbox_provider' => 'Saved']);
    $mock = Mockery::mock(VerificationInboxService::class)->makePartial();
    $mock->shouldReceive('sync')->once()->with(true, $this->clinicA->id)
        ->andReturn(['ok' => true, 'message' => 'Test sync']);
    app()->instance(VerificationInboxService::class, $mock);
    $page = Livewire::test(VerificationInboxSettings::class)
        ->set('data.verification_inbox_provider', 'Draft')->call('syncNow');
    expect($mailbox->fresh()->verification_inbox_provider)->toBe('Saved');
    session([AdminClinicScope::SESSION_KEY => $this->clinicB->id]);
    $page->call('save')->assertSee('Select a clinic to view inbox settings');
    expect($mailbox->fresh()->verification_inbox_provider)->toBe('Saved');
    expect(app(VerificationInboxService::class)->mailbox($this->clinicB->id))->toBeNull();
});

it('previews cleanup and deletes only confirmed still-eligible messages', function () {
    $service = app(VerificationInboxService::class);
    $mailbox = $service->mailbox($this->clinicA->id, true);
    $create = fn ($uid, $extra = []) => VerificationInboxMessage::create(array_merge([
        'clinic_id' => $this->clinicA->id, 'mailbox_uid' => $uid,
        'folder_name' => 'INBOX', 'folder_type' => 'inbox', 'message_hash' => hash('sha256', (string) $uid),
        'received_at' => now()->subDays(120), 'is_protected' => false, 'is_flagged' => false,
    ], $extra));
    $old = $create(1);
    $protected = $create(2, ['is_protected' => true]);
    $flagged = $create(3, ['is_flagged' => true]);
    $ids = $service->cleanupMessageIds($mailbox);
    expect($ids)->toBe([$old->id]);
    $later = $create(4);
    $result = $service->cleanup($this->clinicA->id, $ids);
    expect($result['deleted_messages'])->toBe(1);
    expect($protected->fresh())->not->toBeNull();
    expect($flagged->fresh())->not->toBeNull();
    expect($later->fresh())->not->toBeNull();
});

it('requires confirmation before the inbox cleanup action', function () {
    $message = VerificationInboxMessage::create([
        'clinic_id' => $this->clinicA->id, 'mailbox_uid' => 100,
        'folder_name' => 'INBOX', 'folder_type' => 'inbox', 'message_hash' => hash('sha256', 'confirmation'),
        'received_at' => now()->subDays(120), 'is_protected' => false, 'is_flagged' => false,
    ]);
    $page = Livewire::test(VerificationInboxSettings::class)->mountAction('runCleanup')
        ->assertActionMounted('runCleanup');
    expect($page->getMountedActionModalHtml())->toContain('stored messages')->toContain('Deletion is permanent');
    expect($message->fresh())->not->toBeNull();
    $page->callMountedAction()->assertHasNoErrors();
    expect($message->fresh())->toBeNull();
});

it('rechecks protected messages after the cleanup preview', function () {
    $service = app(VerificationInboxService::class);
    $mailbox = $service->mailbox($this->clinicA->id, true);
    $message = VerificationInboxMessage::create([
        'clinic_id' => $this->clinicA->id, 'mailbox_uid' => 101,
        'folder_name' => 'INBOX', 'folder_type' => 'inbox', 'message_hash' => hash('sha256', 'protected'),
        'received_at' => now()->subDays(120), 'is_protected' => false, 'is_flagged' => false,
    ]);
    $ids = $service->cleanupMessageIds($mailbox);
    $message->update(['is_protected' => true]);
    expect($service->cleanup($this->clinicA->id, $ids)['deleted_messages'])->toBe(0);
    expect($message->fresh())->not->toBeNull();
});

it('rejects invalid retention values without saving', function () {
    Livewire::test(VerificationInboxSettings::class)
        ->set('data.verification_inbox_retention_days', -1)
        ->call('save')->assertHasErrors(['data.verification_inbox_retention_days']);
    expect(app(VerificationInboxService::class)->mailbox($this->clinicA->id)->verification_inbox_retention_days)->toBe(90);
});

it('reports configured mailboxes as untested until an IMAP test succeeds', function () {
    $mailbox = app(UserMailboxService::class)->mailbox($this->admin, true);
    $mailbox->update(['enabled' => true, 'imap_password' => 'test-secret']);
    $mock = Mockery::mock(UserMailboxService::class)->makePartial();
    $mock->shouldReceive('imapAvailable')->andReturn(true);
    $mock->shouldReceive('testConnection')->once()->andReturn(['ok' => true, 'message' => 'Test succeeded']);
    app()->instance(UserMailboxService::class, $mock);
    Livewire::test(UserMailboxSettingsPage::class)->assertSee('Configured, not tested')
        ->call('testConnection')->assertSee('IMAP verified');
});
