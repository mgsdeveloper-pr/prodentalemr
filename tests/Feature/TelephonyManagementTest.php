<?php

use App\Filament\Saas\Resources\TelephonyAccounts\Pages\EditTelephonyAccount;
use App\Filament\Saas\Resources\Verifications\Pages\EditVerificationRequest;
use App\Filament\Saas\Resources\Verifications\Pages\ViewVerificationRequest;
use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\TelephonyAccount;
use App\Models\TelephonyCall;
use App\Models\TelephonyUserAssignment;
use App\Models\User;
use App\Support\TelephonyAccess;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::create([
        'name' => 'Calling Test Dental',
        'owner_name' => 'Test Owner',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Calling Test Clinic',
        'clinic_code' => 'CALL-TEST',
        'timezone' => 'America/New_York',
        'verification_services_enabled' => true,
        'status' => true,
    ]);

    $this->plan = SubscriptionPlan::create([
        'name' => 'Calling Plan',
        'price' => 99,
        'plan_type' => SubscriptionPlan::PLAN_TYPE_VERIFICATION,
        'workspace_mode' => SubscriptionPlan::WORKSPACE_VERIFICATION,
        'max_clinics' => 1,
        'max_users' => 5,
        'included_modules' => ['verification_requests', 'calling'],
        'included_features' => ['calling', 'call_recording', 'call_ai_summary'],
        'plan_limits' => ['monthly_call_minutes' => 500],
        'status' => true,
    ]);

    Subscription::create([
        'organization_id' => $this->organization->id,
        'subscription_scope' => 'organization',
        'subscription_plan_id' => $this->plan->id,
        'start_date' => today(),
        'status' => 'active',
        'service_status' => 'active',
    ]);

    $this->user = User::factory()->create(['status' => true]);
    $this->user->assignRole('verification_user');
    $this->user->verificationClinics()->attach($this->clinic->id);

    $this->service = ManagedBillingService::create([
        'name' => 'Calling Verification',
        'slug' => 'calling-verification',
        'category' => 'verification',
        'service_level_agreement_hours' => 24,
        'default_priority' => 'normal',
        'requires_appointment' => false,
        'requires_patient' => false,
        'requires_policy' => false,
        'requires_claim' => false,
        'status' => true,
    ]);

    ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'status' => 'active',
        'start_date' => today(),
    ]);

    $this->workItem = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'assigned_to' => $this->user->id,
        'title' => 'Calling test request',
        'status' => BillingWorkItem::STATUS_PENDING,
        'priority' => 'normal',
        'source' => 'manual',
    ]);
});

it('keeps provider credentials encrypted and requires an active assigned user', function (): void {
    $account = TelephonyAccount::create([
        'organization_id' => $this->organization->id,
        'name' => 'MightyCall Test',
        'api_key' => 'mighty-api-key',
        'api_secret' => 'mighty-secret',
        'business_number' => '+15551230000',
        'ai_summary_enabled' => true,
        'is_active' => true,
    ]);

    $unavailableWorkspace = TelephonyAccess::workspace($this->user, $this->workItem);

    expect(DB::table('telephony_accounts')->where('id', $account->id)->value('api_key'))
        ->not->toBe('mighty-api-key')
        ->and(TelephonyAccess::canCall($this->user, $this->workItem))->toBeFalse()
        ->and($unavailableWorkspace['visible'])->toBeTrue()
        ->and($unavailableWorkspace['reason'])->toBe('Your portal user is not assigned under User Calling Access.');

    TelephonyUserAssignment::create([
        'telephony_account_id' => $account->id,
        'user_id' => $this->user->id,
        'user_key' => 'user-secret',
        'can_call' => true,
        'can_access_recordings' => true,
        'can_use_ai_summary' => true,
        'is_active' => true,
    ]);

    $workspace = TelephonyAccess::workspace($this->user->fresh(), $this->workItem->fresh());

    expect($workspace['available'])->toBeTrue()
        ->and($workspace['api_key'])->toBe('mighty-api-key')
        ->and($workspace['user_key'])->toBe('user-secret')
        ->and($workspace['recording_enabled'])->toBeTrue()
        ->and($workspace['ai_summary_enabled'])->toBeTrue();
});

it('keeps an existing MightyCall user key when other calling settings change', function (): void {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');

    $account = TelephonyAccount::create([
        'organization_id' => $this->organization->id,
        'name' => 'Editable MightyCall',
        'api_key' => 'editable-api-key',
        'is_active' => true,
    ]);

    $assignment = TelephonyUserAssignment::create([
        'telephony_account_id' => $account->id,
        'user_id' => $this->user->id,
        'user_key' => 'keep-this-user-key',
        'can_call' => true,
        'is_active' => true,
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    Livewire::test(EditTelephonyAccount::class, ['record' => $account->getRouteKey()])
        ->fillForm(['name' => 'Renamed MightyCall'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($account->fresh()->name)->toBe('Renamed MightyCall')
        ->and($assignment->fresh()->user_key)->toBe('keep-this-user-key');
});

it('stores encrypted telephony payloads in text-compatible columns', function (): void {
    expect(Schema::getColumnType('telephony_calls', 'provider_payload'))->toBeIn(['text', 'longtext'])
        ->and(Schema::getColumnType('telephony_calls', 'ai_summary'))->toBeIn(['text', 'longtext']);

    $call = TelephonyCall::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'billing_work_item_id' => $this->workItem->id,
        'user_id' => $this->user->id,
        'to_number' => '+15557654321',
        'provider_payload' => ['event' => 'completed'],
        'ai_summary' => ['summary' => 'Coverage confirmed.'],
    ]);

    $storedPayload = DB::table('telephony_calls')->where('id', $call->id)->value('provider_payload');

    expect($storedPayload)->toBeString()
        ->and(str_contains($storedPayload, 'completed'))->toBeFalse()
        ->and($call->fresh()->provider_payload)->toBe(['event' => 'completed'])
        ->and($call->fresh()->ai_summary)->toBe(['summary' => 'Coverage confirmed.']);
});

it('resolves the users connection instead of a client or default connection', function (): void {
    $default = TelephonyAccount::create([
        'name' => 'Platform MightyCall',
        'api_key' => 'platform-key',
        'is_platform_default' => true,
        'is_active' => true,
    ]);

    $client = TelephonyAccount::create([
        'organization_id' => $this->organization->id,
        'name' => 'Client MightyCall',
        'api_key' => 'client-key',
        'is_active' => true,
    ]);

    expect(TelephonyAccess::accountForUser($this->user))->toBeNull();
    TelephonyUserAssignment::create([
        'telephony_account_id' => $default->id, 'user_id' => $this->user->id,
        'provider_user_id' => 'agent-one', 'user_key' => 'user-one-key',
        'can_call' => true, 'is_active' => true,
    ]);
    expect(TelephonyAccess::accountForUser($this->user)->is($default))->toBeTrue()
        ->and(TelephonyAccess::workspace($this->user, $this->workItem)['api_key'])->toBe('platform-key');
    $default->update(['is_active' => false]);
    expect(TelephonyAccess::canCall($this->user, $this->workItem))->toBeFalse();
});

it('retains one agent across assigned clinics and rejects unassigned clinic access', function (): void {
    $account = TelephonyAccount::create([
        'organization_id' => $this->organization->id, 'name' => 'User connection',
        'api_key' => 'same-api', 'is_active' => true,
    ]);
    TelephonyUserAssignment::create([
        'telephony_account_id' => $account->id, 'user_id' => $this->user->id,
        'provider_user_id' => 'same-agent', 'user_key' => 'same-key', 'can_call' => true, 'is_active' => true,
    ]);
    $organization = Organization::create(['name' => 'Second Client', 'owner_name' => 'Test Owner', 'status' => true]);
    $clinic = Clinic::create(['organization_id' => $organization->id, 'clinic_name' => 'Second Clinic',
        'clinic_code' => 'SECOND-CALL', 'status' => true, 'verification_services_enabled' => true]);
    Subscription::create(['organization_id' => $organization->id, 'subscription_scope' => 'organization',
        'subscription_plan_id' => $this->plan->id, 'start_date' => today(), 'status' => 'active', 'service_status' => 'active']);
    ClientServiceEnrollment::create(['organization_id' => $organization->id, 'clinic_id' => $clinic->id,
        'managed_billing_service_id' => $this->service->id, 'status' => 'active', 'start_date' => today()]);
    $request = BillingWorkItem::create([
        'organization_id' => $organization->id, 'clinic_id' => $clinic->id,
        'managed_billing_service_id' => $this->service->id, 'assigned_to' => $this->user->id,
        'title' => 'Second request', 'source' => 'manual', 'status' => 'pending',
    ]);
    expect(TelephonyAccess::workspace($this->user, $request))->not->toHaveKey('user_key');
    $this->user->verificationClinics()->attach($clinic->id);
    $user = $this->user->fresh();
    expect(TelephonyAccess::workspace($user, $request)['reason'] ?? null)->toBeNull();
    expect(TelephonyAccess::workspace($user, $request)['user_key'])->toBe('same-key')
        ->and(TelephonyAccess::workspace($user, $this->workItem)['user_key'])->toBe('same-key');
    $this->actingAs($user);
    $page = new class extends EditVerificationRequest {};
    $page->record = $request->fresh();
    $page->data = ['vf_insurance_provider_name' => 'Test Insurance', 'vf_insurance_company_phone_number' => '+15557654321'];
    $result = $page->startTelephonyCall('+15557654321');
    $call = TelephonyCall::where('public_id', $result['public_id'])->firstOrFail();
    expect($call->telephony_account_id)->toBe($account->id)
        ->and($call->clinic_id)->toBe($clinic->id)->and($call->user_id)->toBe($user->id);
    $call->update(['duration_seconds' => 60]);
    $account->update(['monthly_minute_limit' => 1]);
    $page->record = $this->workItem->fresh();
    expect(fn () => $page->startTelephonyCall('+15557654321'))
        ->toThrow(ValidationException::class, 'Your calling connection has reached its monthly allowance.');
});

it('rejects duplicate user and agent identities even across calling connections', function (): void {
    $first = TelephonyAccount::create(['name' => 'First', 'api_key' => 'first']);
    $second = TelephonyAccount::create(['name' => 'Second', 'api_key' => 'second']);
    $assignment = TelephonyUserAssignment::create(['telephony_account_id' => $first->id,
        'user_id' => $this->user->id, 'provider_user_id' => 'agent-123', 'user_key' => 'key']);
    expect(fn () => TelephonyUserAssignment::create(['telephony_account_id' => $second->id,
        'user_id' => $this->user->id, 'provider_user_id' => 'other-agent']))->toThrow(ValidationException::class);
    $other = User::factory()->create();
    expect(fn () => TelephonyUserAssignment::create(['telephony_account_id' => $second->id,
        'user_id' => $other->id, 'provider_user_id' => ' AGENT-123 ']))->toThrow(ValidationException::class);
    $assignment->update(['can_call' => false]);
    expect($assignment->fresh()->provider_user_id)->toBe('agent-123');
});

it('stops migration on legacy duplicate mappings without removing them', function (): void {
    $migration = require database_path('migrations/2026_09_08_000003_enforce_unique_calling_user_identities.php');
    $migration->down();
    $first = TelephonyAccount::create(['name' => 'Legacy First', 'api_key' => 'first']);
    $second = TelephonyAccount::create(['name' => 'Legacy Second', 'api_key' => 'second']);
    foreach ([$first, $second] as $account) {
        DB::table('telephony_user_assignments')->insert([
            'telephony_account_id' => $account->id, 'user_id' => $this->user->id,
            'provider_user_id' => 'legacy-'.$account->id,
        ]);
    }
    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'duplicate portal users')
        ->and(DB::table('telephony_user_assignments')->count())->toBe(2);
});

it('rejects a stale dialer number after the selected insurance changes', function (): void {
    $account = TelephonyAccount::create([
        'organization_id' => $this->organization->id,
        'name' => 'Target Guard MightyCall',
        'api_key' => 'target-guard-key',
        'is_active' => true,
    ]);

    TelephonyUserAssignment::create([
        'telephony_account_id' => $account->id,
        'user_id' => $this->user->id,
        'user_key' => 'target-guard-user-key',
        'can_call' => true,
        'is_active' => true,
    ]);

    $this->actingAs($this->user);

    $page = new class extends EditVerificationRequest {};
    $page->record = $this->workItem->fresh();
    $page->data = [
        'vf_insurance_provider_name' => 'Current Insurance',
        'vf_insurance_company_phone_number' => '+15557654321',
    ];

    expect(fn () => $page->startTelephonyCall('+18005550144'))
        ->toThrow(ValidationException::class, 'The insurance phone number changed.')
        ->and(TelephonyCall::query()->count())->toBe(0);

    $result = $page->startTelephonyCall('+15557654321');

    expect($result['destination'])->toBe('+15557654321')
        ->and(TelephonyCall::query()->where('to_number', '+15557654321')->exists())->toBeTrue();
});

it('accepts a secured MightyCall completion webhook and updates the call', function (): void {
    $account = TelephonyAccount::create([
        'organization_id' => $this->organization->id,
        'name' => 'Webhook MightyCall',
        'api_key' => 'webhook-key',
        'is_active' => true,
    ]);

    $call = TelephonyCall::create([
        'telephony_account_id' => $account->id,
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'billing_work_item_id' => $this->workItem->id,
        'user_id' => $this->user->id,
        'to_number' => '+15557654321',
        'status' => 'ringing',
        'started_at' => now(),
    ]);

    $this->postJson($account->webhookUrl(), [
        'EventType' => 'OutgoingCallCompleted',
        'CallId' => 'mc-call-123',
        'To' => '+15557654321',
        'CallDuration' => '00:02:05',
        'RecordingLink' => 'https://media.mightycall.com/call-123.mp3',
    ])->assertNoContent();

    $call->refresh();

    expect($call->status)->toBe('completed')
        ->and($call->provider_call_id)->toBe('mc-call-123')
        ->and($call->duration_seconds)->toBe(125)
        ->and($call->recording_url)->toBe('https://media.mightycall.com/call-123.mp3')
        ->and($call->recording_duration_seconds)->toBe(125)
        ->and($call->ended_at)->not->toBeNull();

    expect(TelephonyCall::normalizeMightyCallRecordingUrl('https:/mightycall.com/recordings/call.mp3'))
        ->toBe('https://mightycall.com/recordings/call.mp3')
        ->and(TelephonyCall::normalizeMightyCallRecordingUrl('https://example.test/recording.mp3'))
        ->toBeNull();

    $this->postJson(route('webhooks.telephony.mightycall', [
        'account' => $account->public_id,
        'token' => 'wrong-token',
    ]), [])->assertNotFound();
});

it('shows call history to verification users but streams recordings only with access', function (): void {
    $account = TelephonyAccount::create([
        'organization_id' => $this->organization->id,
        'name' => 'Recording Access MightyCall',
        'api_key' => 'recording-access-key',
        'is_active' => true,
    ]);

    $assignment = TelephonyUserAssignment::create([
        'telephony_account_id' => $account->id,
        'user_id' => $this->user->id,
        'user_key' => 'recording-user-key',
        'can_call' => true,
        'can_access_recordings' => false,
        'is_active' => true,
    ]);

    $call = TelephonyCall::create([
        'telephony_account_id' => $account->id,
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'billing_work_item_id' => $this->workItem->id,
        'user_id' => $this->user->id,
        'provider_call_id' => 'mc-recording-123',
        'to_number' => '+15557654321',
        'status' => 'completed',
        'started_at' => now()->subMinutes(3),
        'answered_at' => now()->subMinutes(2),
        'ended_at' => now()->subMinute(),
        'duration_seconds' => 60,
        'recording_url' => 'https://media.mightycall.com/recordings/mc-recording-123.mp3',
    ]);

    $this->actingAs($this->user);

    $page = new ViewVerificationRequest;
    $page->record = $this->workItem->fresh();

    expect($page->getTelephonyCalls()->pluck('id')->all())->toBe([$call->id])
        ->and($page->canAccessTelephonyRecording($call))->toBeFalse()
        ->and($call->recordingState(false))->toBe('restricted');

    $recordingRoute = route('admin.verifications.calls.recording', [
        'billingWorkItem' => $this->workItem,
        'telephonyCall' => $call,
    ]);

    $this->get($recordingRoute)->assertForbidden();

    $assignment->update(['can_access_recordings' => true]);
    Http::fake([
        'https://media.mightycall.com/*' => Http::response('test-audio', 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => '10',
        ]),
    ]);

    expect($page->canAccessTelephonyRecording($call))->toBeTrue()
        ->and($call->recordingState(true))->toBe('available');

    $this->get($recordingRoute)
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');

    expect($this->workItem->activities()
        ->where('activity_type', 'insurance_call_recording_played')
        ->exists())->toBeTrue();
});

it('gives SaaS Admin recording access and rejects a call from another verification', function (): void {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');

    $account = TelephonyAccount::create([
        'organization_id' => $this->organization->id,
        'name' => 'Admin Recording MightyCall',
        'api_key' => 'admin-recording-key',
        'is_active' => true,
    ]);

    $call = TelephonyCall::create([
        'telephony_account_id' => $account->id,
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'billing_work_item_id' => $this->workItem->id,
        'user_id' => $this->user->id,
        'provider_call_id' => 'mc-admin-recording-123',
        'to_number' => '+15557654321',
        'status' => 'completed',
        'started_at' => now()->subMinutes(2),
        'answered_at' => now()->subMinute(),
        'ended_at' => now(),
        'duration_seconds' => 60,
        'recording_url' => 'https://console.mightycall.com/recordings/mc-admin-recording-123.mp3',
    ]);

    $otherWorkItem = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'assigned_to' => $this->user->id,
        'title' => 'Different verification',
        'status' => BillingWorkItem::STATUS_PENDING,
        'priority' => 'normal',
        'source' => 'manual',
    ]);

    Http::fake(['https://console.mightycall.com/*' => Http::response('admin-audio', 200, ['Content-Type' => 'audio/mpeg'])]);
    $this->actingAs($admin);

    expect(TelephonyAccess::canAccessRecording($admin, $call))->toBeTrue();

    $this->get(route('admin.verifications.calls.recording', [
        'billingWorkItem' => $this->workItem,
        'telephonyCall' => $call,
    ]))->assertOk();

    $this->get(route('admin.verifications.calls.recording', [
        'billingWorkItem' => $otherWorkItem,
        'telephonyCall' => $call,
    ]))->assertNotFound();
});

it('does not regress a finished call when provider events arrive out of order', function (): void {
    $account = TelephonyAccount::create([
        'organization_id' => $this->organization->id,
        'name' => 'Ordered Webhook MightyCall',
        'api_key' => 'ordered-webhook-key',
        'is_active' => true,
    ]);

    $call = TelephonyCall::create([
        'telephony_account_id' => $account->id,
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'billing_work_item_id' => $this->workItem->id,
        'user_id' => $this->user->id,
        'provider_call_id' => 'mc-ordered-123',
        'to_number' => '+15557654321',
        'status' => 'completed',
        'started_at' => now()->subMinutes(2),
        'answered_at' => now()->subMinute(),
        'ended_at' => now(),
        'duration_seconds' => 60,
    ]);

    $this->postJson($account->webhookUrl(), [
        'EventType' => 'OutgoingCallStarted',
        'CallId' => 'mc-ordered-123',
        'To' => '+15557654321',
        'DurationSeconds' => 58,
    ])->assertNoContent();

    $call->refresh();

    expect($call->status)->toBe('completed')
        ->and($call->duration_seconds)->toBe(60)
        ->and(data_get($call->provider_payload, 'webhook_events.0.event'))->toBe('outgoingcallstarted');
});

it('allows call statuses to move forward but never out of a terminal state', function (): void {
    $call = new TelephonyCall(['status' => 'initiated']);

    expect($call->canTransitionTo('ringing'))->toBeTrue()
        ->and($call->canTransitionTo('connected'))->toBeTrue();

    $call->status = 'connected';
    expect($call->canTransitionTo('ringing'))->toBeFalse()
        ->and($call->canTransitionTo('completed'))->toBeTrue();

    $call->status = 'completed';
    expect($call->isTerminal())->toBeTrue()
        ->and($call->canTransitionTo('connected'))->toBeFalse()
        ->and($call->canTransitionTo('failed'))->toBeFalse();
});

it('renders the quick reference phone trigger without a dynamic icon dependency', function (): void {
    $html = view('filament.saas.resources.verifications.pages.partials.telephony-call-control', [
        'callingWorkspace' => [
            'provider' => 'mightycall',
            'available' => true,
            'visible' => true,
            'api_key' => 'test-api-key',
            'user_key' => 'test-user-key',
            'sdk_url' => 'https://ccapi.mightycall.com/v4/sdk/mightycall.webphone.sdk.js',
            'recording_enabled' => true,
            'ai_summary_enabled' => false,
        ],
        'destinationNumber' => '+18005550144',
        'insuranceName' => 'Test Insurance',
        'edgeTrigger' => true,
    ])->render();

    expect($html)
        ->toContain('class="vt3-call-tool"')
        ->toContain('class="vt3-call-tool__trigger"')
        ->toContain('aria-label="Call insurance"')
        ->toContain('verification-telephony-target-updated.window')
        ->toContain('verification-close-telephony-drawer.window')
        ->toContain("utilityDrawerMode = 'call'")
        ->toContain("open && utilityDrawerMode === 'call'")
        ->toContain('class="vt3-call-drawer"')
        ->toContain('class="vt3-call-drawer__body"')
        ->toContain('class="vt3-call-drawer__footer"')
        ->toContain('aria-label="Close insurance call drawer"')
        ->toContain('Insurance phone number required.')
        ->toContain('viewBox="0 0 24 24"')
        ->not->toContain('Call Insurance</button>');
});

it('exposes calling setup and usage only through the SaaS management portal', function (): void {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');

    $account = TelephonyAccount::create([
        'organization_id' => $this->organization->id,
        'name' => 'Managed MightyCall',
        'api_key' => 'managed-key',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get('/saas/telephony-accounts')
        ->assertOk()
        ->assertSee('Managed MightyCall')
        ->assertSee('Calling Setup');

    $this->get('/saas/telephony-accounts/'.$account->id.'/edit')
        ->assertOk()
        ->assertSee('User Calling Access')
        ->assertSee('MightyCall webhook URL');

    $this->get('/saas/telephony-calls')
        ->assertOk()
        ->assertSee('Call Usage');

    $this->get('/saas/user-management')
        ->assertOk()
        ->assertSee('Calling Access');
});

it('waits for MightyCall to be ready before placing an outbound call', function (): void {
    $control = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/partials/telephony-call-control.blade.php'
    ));

    expect($control)
        ->toContain('wire:ignore')
        ->toContain("\$rootClass = \$edgeTrigger ? 'vt3-call-tool' : ''")
        ->toContain('viewBox="0 0 24 24"')
        ->toContain('async waitForPhoneReady()')
        ->toContain('async ensureMicrophoneAccess()')
        ->toContain('navigator.mediaDevices.getUserMedia({ audio: true })')
        ->toContain('stream?.getTracks().forEach((track) => track.stop())')
        ->toContain('No working microphone was found.')
        ->toContain('Close other calling apps or select another input device')
        ->toContain("const readyStatuses = ['ready', 'registered'];")
        ->toContain('if (readyStatuses.includes(this.phoneStatus(phone)))')
        ->toContain('phoneStatus(phone = window.MightyCallWebPhone?.Phone)')
        ->toContain("let frame = document.getElementById('mightyCallWebPhoneFrame')")
        ->toContain('async waitForPhoneFrameLoad(frame)')
        ->toContain('await this.waitForPhoneFrameLoad(frame);')
        ->toContain('async waitForPhoneBridge()')
        ->toContain("if (this.phoneStatus(phone) === 'closed')")
        ->toContain('phone.SwitchOn();')
        ->toContain('position:fixed;left:-10000px')
        ->toContain('status: ${finalStatus}')
        ->toContain('Select an available microphone in the phone settings.')
        ->toContain('verify the WebPhone credential in User Calling Access.')
        ->toContain('this.phoneDiagnosticsOpen = true;')
        ->toContain('await this.waitForPhoneReady();')
        ->toContain('Cancel call')
        ->toContain("this.statusLabel = 'Ringing insurer';")
        ->toContain('this.subscribe(phone.OnCallCompleted')
        ->toContain('this.subscribe(phone.OnOffline')
        ->toContain('this.subscribe(phone.OnError')
        ->toContain('Phone.Focus?.();')
        ->toContain('pendingReports: []')
        ->toContain('while (this.pendingReports.length > 0)')
        ->toContain('phoneInitialized: false')
        ->toContain('attemptSequence: 0')
        ->toContain("callPhase: 'idle'")
        ->toContain('completedProviderCallIds: []')
        ->toContain('dialPadOpen: false')
        ->toContain('phoneDiagnosticsOpen: false')
        ->toContain('loading || active || ending || phoneDiagnosticsOpen')
        ->toContain("x-text=\"dialPadOpen ? 'Hide phone' : 'Phone'\"")
        ->toContain('title="Show or hide MightyCall phone"')
        ->toContain('x-bind:aria-pressed="dialPadOpen"')
        ->toContain('grid-template-columns:repeat(3,minmax(0,1fr))')
        ->toContain('aria-controls="mightycall-webphone-container"')
        ->toContain('providerCallObserved: false')
        ->toContain('cancelRequested: false')
        ->toContain('pendingCallingTarget: null')
        ->toContain('updateCallingTarget(target = {})')
        ->toContain('if (! this.hasCallingDestination)')
        ->toContain('if (this.cancelRequested || this.terminalReported) return;')
        ->toContain('if (this.cancelRequested)')
        ->toContain('startPhoneMonitor(attemptId)')
        ->toContain("'status_ready',")
        ->toContain('async rearmPhone(attemptId)')
        ->toContain('this.config.destination = call.destination;')
        ->toContain('x-show="loading || active || ending || phoneDiagnosticsOpen"')
        ->toContain('! this.requestedEndStatus')
        ->toContain('The call remains active until MightyCall confirms it ended.')
        ->and(strpos($control, 'await this.waitForPhoneReady();'))
        ->toBeLessThan(strpos($control, 'Phone.Call(config.destination)'));

    expect(strpos($control, 'await this.preparePhone();'))
        ->toBeLessThan(strpos($control, 'this.$wire.startTelephonyCall(config.destination)'));

    expect(strpos($control, 'await this.ensureMicrophoneAccess();'))
        ->toBeLessThan(strpos($control, 'await this.loadSdk();'));
});

it('provides self-service guidance for calling and audio problems', function (): void {
    $control = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/partials/telephony-call-control.blade.php'
    ));
    $guide = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/partials/telephony-user-guide.blade.php'
    ));

    expect($control)
        ->toContain("@include('filament.saas.resources.verifications.pages.partials.telephony-user-guide')")
        ->and($guide)
        ->toContain('Calling help')
        ->toContain('Before your first call')
        ->toContain('Microphone not detected:')
        ->toContain('Changed headset:')
        ->toContain('User Calling Access')
        ->toContain('Select <strong>Keyboard</strong> inside MightyCall');
});

it('renders permission-aware call history on the verification result', function (): void {
    $resultView = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/view-verification-request.blade.php'
    ));

    expect($resultView)
        ->toContain('Call History &amp; Recordings')
        ->toContain('Recordings remain securely stored with MightyCall.')
        ->toContain('canAccessTelephonyRecording')
        ->toContain('Recording access required')
        ->toContain('controlsList="nodownload"')
        ->toContain('getTelephonyRecordingUrl')
        ->not->toContain('$telephonyCall->recording_url');
});
