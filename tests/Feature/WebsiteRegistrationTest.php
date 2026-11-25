<?php

use App\Models\OnboardingDraft;
use App\Models\SubscriptionPlan;
use App\Models\WebsiteRegistration;
use App\Services\ClientOnboardingService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['website_registration.enabled' => true, 'website_registration.bridge_secret' => str_repeat('s', 40), 'mail.default' => 'smtp']);
    $this->withHeader('X-Website-Key', str_repeat('s', 40));
});

function websitePlan(array $overrides = []): SubscriptionPlan
{
    return SubscriptionPlan::create(array_replace(['name' => 'Tool Starter', 'price' => 99, 'plan_type' => 'verification', 'workspace_mode' => 'verification', 'max_clinics' => 1, 'max_users' => 3, 'status' => true, 'website_published' => true, 'verification_model' => 'self_service', 'billing_interval' => 'monthly', 'currency' => 'USD', 'trial_days' => 0, 'quote_required' => false], $overrides));
}

function websiteApplicant(): WebsiteRegistration
{
    $draft = app(ClientOnboardingService::class)->start(null, 'single_clinic', 'self_service', 'website');
    return WebsiteRegistration::create(['onboarding_draft_id' => $draft->id, 'email' => 'owner@example.com', 'email_verified_at' => now(), 'access_hash' => hash('sha256', str_repeat('a', 64)), 'access_expires_at' => now()->addHour()]);
}

it('requires an enabled gateway and private shared secret', function () {
    $this->withHeader('X-Website-Key', 'wrong')->getJson('/website-api/plans')->assertUnauthorized();
    config(['website_registration.enabled' => false]);
    $this->getJson('/website-api/plans')->assertStatus(503);
});

it('only exposes published active verification plans without trials', function () {
    $plan = websitePlan();
    websitePlan(['website_published' => false]);
    websitePlan(['status' => false]);
    websitePlan(['plan_type' => 'pms']);
    websitePlan(['trial_days' => 7]);
    $this->getJson('/website-api/plans')->assertOk()->assertJsonCount(1, 'plans')->assertJsonPath('plans.0.id', $plan->id);
});

it('saves first contact and never overwrites an existing registration before verification', function () {
    Mail::shouldReceive('raw')->twice()->andReturnNull();
    $data = ['email' => 'OWNER@example.com', 'owner_name' => 'Owner', 'clinic_name' => 'Dental', 'account_structure' => 'single_clinic', 'verification_model' => 'self_service'];
    $this->postJson('/website-api/start', $data)->assertOk()->assertJsonMissingPath('registration');
    $this->postJson('/website-api/start', [...$data, 'owner_name' => 'Attacker'])->assertOk();
    expect(WebsiteRegistration::count())->toBe(1);
    $registration = WebsiteRegistration::first();
    expect($registration->draft->data['owner_name'])->toBe('Owner');
    expect($registration->draft->user_id)->toBeNull();
});

it('retains contact when mail is unavailable', function () {
    config(['mail.default' => 'log']);
    $this->postJson('/website-api/start', ['email' => 'offline@example.com', 'owner_name' => 'Owner', 'clinic_name' => 'Dental', 'account_structure' => 'single_clinic', 'verification_model' => 'hybrid'])->assertStatus(503);
    expect(WebsiteRegistration::where('email', 'offline@example.com')->exists())->toBeTrue();
});

it('redeems email tokens once and rejects expired links', function () {
    $registration = websiteApplicant();
    $registration->update(['resume_hash' => hash('sha256', str_repeat('r', 64)), 'resume_expires_at' => now()->addMinute()]);
    $result = $this->postJson('/website-api/redeem', ['token' => str_repeat('r', 64)])->assertOk();
    $this->postJson('/website-api/redeem', ['token' => str_repeat('r', 64)])->assertStatus(410);
    $this->withHeader('X-Registration-Token', $result->json('access_token'))->getJson('/website-api/draft')->assertOk();
    $registration->update(['resume_hash' => hash('sha256', str_repeat('x', 64)), 'resume_expires_at' => now()->subMinute()]);
    $this->postJson('/website-api/redeem', ['token' => str_repeat('x', 64)])->assertStatus(410);
});

it('saves incomplete fields while ignoring provisioning inputs and protecting other drafts', function () {
    $registration = websiteApplicant();
    $this->postJson('/website-api/save', ['owner_name' => 'No access'])->assertUnauthorized();
    $this->withHeader('X-Registration-Token', str_repeat('a', 64));
    $this->postJson('/website-api/save', ['location_city' => 'Mumbai', 'user_id' => 1, 'status' => 'activated', 'workspace_mode' => 'both', 'verification_model' => 'hybrid', 'owner_password' => 'unsafe'])->assertOk();
    $draft = $registration->draft->fresh();
    expect($draft->data['location_city'])->toBe('Mumbai')->and($draft->verification_model)->toBe('self_service')->and($draft->status)->toBe('draft');
    expect($draft->data)->not->toHaveKeys(['owner_password', 'workspace_mode', 'user_id']);
    $this->postJson('/website-api/submit')->assertUnprocessable();
});

it('validates plan changes and submits once without provisioning accounts', function () {
    $registration = websiteApplicant();
    $plan = websitePlan();
    $this->withHeader('X-Registration-Token', str_repeat('a', 64));
    $this->postJson('/website-api/save', ['owner_name' => 'Owner', 'owner_phone' => '1234567890', 'organization_name' => 'Dental LLC', 'clinic_name' => 'Dental', 'location_name' => 'Main', 'location_country' => 'USA', 'location_state' => 'Texas', 'location_city' => 'Austin', 'clinic_timezone' => 'America/New_York', 'subscription_plan_id' => $plan->id])->assertOk();
    $plan->update(['price' => 120]);
    $this->postJson('/website-api/submit')->assertStatus(409);
    $this->postJson('/website-api/save', ['subscription_plan_id' => $plan->id])->assertOk();
    $this->postJson('/website-api/submit')->assertOk()->assertJsonPath('registration.status', 'submitted');
    $this->postJson('/website-api/submit')->assertOk();
    $this->postJson('/website-api/save', ['owner_name' => 'Changed'])->assertStatus(409);
    expect($registration->draft->fresh()->organization_id)->toBeNull();
});

it('rejects wrong offerings and disabled plans', function () {
    websiteApplicant();
    $plan = websitePlan(['verification_model' => 'hybrid']);
    $this->withHeader('X-Registration-Token', str_repeat('a', 64))->postJson('/website-api/save', ['subscription_plan_id' => $plan->id])->assertUnprocessable();
});

function websiteStaff(): \App\Models\User
{
    $user = \App\Models\User::factory()->create(['status' => true]);
    $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('saas_admin', 'web'));
    foreach (['view', 'update', 'add'] as $action) {
        foreach (['organizations', 'subscription_plans'] as $module) {
            $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('saas.'.$module.'.'.$action, 'web'));
        }
    }
    return $user;
}

it('opens website submissions in existing onboarding and preserves their draft across updates', function () {
    $registration = websiteApplicant();
    $registration->draft->update(['status' => 'submitted', 'data' => ['organization_name' => 'Dental LLC', 'owner_name' => 'Owner', 'owner_email' => $registration->email, 'clinic_name' => 'Dental']]);
    $this->actingAs(websiteStaff());
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('saas'));
    \Livewire\Livewire::withQueryParams(['onboarding' => $registration->draft->public_id])
        ->test(\App\Filament\Saas\Pages\TenantOnboarding::class)
        ->assertSet('data.clinic_name', 'Dental')
        ->set('data.location_city', 'Austin')
        ->set('data.location_state', 'Texas')
        ->assertHasNoErrors();
    expect(OnboardingDraft::count())->toBe(1);
    expect($registration->draft->fresh()->status)->toBe('under_review');
    expect($registration->draft->fresh()->data['location_city'])->toBe('Austin');
    $this->withHeader('X-Registration-Token', str_repeat('a', 64))->postJson('/website-api/save', ['location_city' => 'Changed'])->assertStatus(409);
});

it('renders the staff registration page and existing plan creation page', function () {
    websiteApplicant();
    $this->actingAs(websiteStaff());
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('saas'));
    \Livewire\Livewire::test(\App\Filament\Saas\Pages\WebsiteRegistrations::class)->assertSuccessful()->assertSee('owner@example.com');
    \Livewire\Livewire::test(\App\Filament\Saas\Resources\SubscriptionPlans\Pages\CreateSubscriptionPlan::class)->assertSuccessful()->assertSee('Website Plan Options');
});

it('creates a publishable plan through the staff form and rejects trial publication', function () {
    $this->actingAs(websiteStaff());
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('saas'));
    \Livewire\Livewire::test(\App\Filament\Saas\Resources\SubscriptionPlans\Pages\CreateSubscriptionPlan::class)
        ->fillForm(['name' => 'Managed Verification Team', 'price' => 250, 'verification_model' => 'managed_service', 'website_published' => true, 'trial_days' => 7])
        ->call('create')->assertHasFormErrors(['website_published'])
        ->fillForm(['trial_days' => 0])->call('create')->assertHasNoFormErrors();
    expect(SubscriptionPlan::websiteAvailable()->where('name', 'Managed Verification Team')->count())->toBe(1);
});

it('keeps a DSO website draft attached across staff edits', function () {
    $draft = app(ClientOnboardingService::class)->start(null, 'dso', 'managed_service', 'website');
    $draft->update(['status' => 'submitted', 'data' => ['dso_name' => 'Dental Group', 'organization_name' => 'Dental LLC']]);
    WebsiteRegistration::create(['onboarding_draft_id' => $draft->id, 'email' => 'dso@example.com', 'email_verified_at' => now()]);
    $this->actingAs(websiteStaff());
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('saas'));
    \Livewire\Livewire::withQueryParams(['onboarding' => $draft->public_id])->test(\App\Filament\Saas\Pages\DsoOnboarding::class)
        ->assertSet('data.dso_name', 'Dental Group')->set('data.location_city', 'Austin')->set('data.location_state', 'Texas')->assertHasNoErrors();
    expect(OnboardingDraft::count())->toBe(1)->and($draft->fresh()->status)->toBe('under_review');
});

it('denies staff review of unverified or unsubmitted registrations', function () {
    $registration = websiteApplicant();
    $staff = websiteStaff();
    expect(fn () => app(ClientOnboardingService::class)->findForUser($registration->draft->public_id, $staff))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    $registration->draft->update(['status' => 'submitted']);
    $registration->update(['email_verified_at' => null]);
    expect(fn () => app(ClientOnboardingService::class)->findForUser($registration->draft->public_id, $staff))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});

it('activates a reviewed website draft through the existing wizard without allowing another activation', function () {
    Mail::fake();
    seedWorkflowMaster();
    \Spatie\Permission\Models\Role::findOrCreate('clinic_admin', 'web');
    $registration = websiteApplicant();
    $plan = websitePlan();
    $registration->draft->update(['status' => 'submitted', 'data' => ['organization_name' => 'Dental LLC', 'organization_owner_name' => 'Owner', 'owner_name' => 'Owner', 'owner_email' => $registration->email, 'owner_phone' => '1234567890', 'clinic_name' => 'Dental', 'location_name' => 'Main', 'location_country' => 'USA', 'location_state' => 'Texas', 'location_city' => 'Austin', 'clinic_timezone' => 'America/Chicago', 'subscription_plan_id' => $plan->id]]);
    $staff = websiteStaff();
    $this->actingAs($staff);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('saas'));
    \Livewire\Livewire::withQueryParams(['onboarding' => $registration->draft->public_id])->test(\App\Filament\Saas\Pages\TenantOnboarding::class)
        ->fillForm(['owner_password' => 'TemporaryTestOnly!123', 'owner_password_confirmation' => 'TemporaryTestOnly!123'])
        ->call('create')->assertHasNoFormErrors()->assertRedirect();
    expect($registration->draft->fresh()->status)->toBe('activated')->and($registration->draft->fresh()->organization_id)->not->toBeNull();
    expect(\App\Models\User::where('email', $registration->email)->count())->toBe(1);
    expect(fn () => app(ClientOnboardingService::class)->findForUser($registration->draft->public_id, $staff))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});
