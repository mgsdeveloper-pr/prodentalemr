<?php

use App\Filament\Saas\Resources\Verifications\Pages\EditVerificationRequest;
use App\Models\BillingWorkItem;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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

it('keeps each client on its own connection before using the platform default', function (): void {
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

    expect(TelephonyAccess::accountFor($this->organization)->is($client))->toBeTrue()
        ->and(TelephonyAccess::accountFor(Organization::create([
            'name' => 'Other Dental',
            'owner_name' => 'Other Owner',
            'status' => true,
        ]))->is($default))->toBeTrue();
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
        'DurationSeconds' => 125,
        'RecordingUrl' => 'https://recordings.example.test/call-123.mp3',
    ])->assertNoContent();

    $call->refresh();

    expect($call->status)->toBe('completed')
        ->and($call->provider_call_id)->toBe('mc-call-123')
        ->and($call->duration_seconds)->toBe(125)
        ->and($call->recording_url)->toBe('https://recordings.example.test/call-123.mp3')
        ->and($call->ended_at)->not->toBeNull();

    $this->postJson(route('webhooks.telephony.mightycall', [
        'account' => $account->public_id,
        'token' => 'wrong-token',
    ]), [])->assertNotFound();
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
        ->toContain('class="vt3-call-drawer"')
        ->toContain('class="vt3-call-drawer__body"')
        ->toContain('class="vt3-call-drawer__footer"')
        ->toContain('aria-label="Minimize insurance call"')
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
        ->toContain("const readyStatuses = ['ready', 'registered'];")
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
        ->toContain('>Keypad</button>')
        ->toContain('x-bind:aria-pressed="dialPadOpen"')
        ->toContain('grid-template-columns:repeat(3,minmax(0,1fr))')
        ->toContain('aria-controls="mightycall-webphone-container"')
        ->toContain('providerCallObserved: false')
        ->toContain('cancelRequested: false')
        ->toContain('pendingCallingTarget: null')
        ->toContain('updateCallingTarget(target = {})')
        ->toContain('if (! this.hasCallingDestination)')
        ->toContain('if (this.cancelRequested)')
        ->toContain('startPhoneMonitor(attemptId)')
        ->toContain("'status_ready',")
        ->toContain('async rearmPhone(attemptId)')
        ->toContain('this.config.destination = call.destination;')
        ->toContain('x-show="loading || active || ending"')
        ->toContain('! this.requestedEndStatus')
        ->toContain('The call remains active until MightyCall confirms it ended.')
        ->and(strpos($control, 'await this.waitForPhoneReady();'))
        ->toBeLessThan(strpos($control, 'Phone.Call(config.destination)'));
});
