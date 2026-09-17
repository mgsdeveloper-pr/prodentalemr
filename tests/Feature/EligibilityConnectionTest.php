<?php

use App\Filament\Saas\Pages\EligibilityConnectionSettings;
use App\Models\User;
use App\Services\Eligibility\EligibilityDemonstration;
use App\Services\Eligibility\ZuubConnectionService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create(['status' => true, 'password' => Hash::make('Local-test-password-123')]);
    $this->admin->assignRole('saas_admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('saas'));
    Http::preventStrayRequests();
    Http::fake([]);
    $this->service = app(ZuubConnectionService::class);
    RateLimiter::clear('zuub-probe:1');
});

it('encrypts credentials, hides them in serialization and isolates environments', function () {
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'sandbox-secret', 'enabled' => false]);
    $this->service->save($this->admin, 'production', ['api_key' => 'production-secret', 'enabled' => false]);
    $sandbox = $this->service->connection('sandbox');
    expect($sandbox->api_key)->toBe('sandbox-secret')
        ->and($sandbox->toArray())->not->toHaveKey('api_key')
        ->and(DB::table('eligibility_connections')->where('id', $sandbox->id)->value('api_key'))->not->toBe('sandbox-secret')
        ->and($this->service->connection('production')->api_key)->toBe('production-secret');
    $this->service->save($this->admin, 'sandbox', ['api_key' => '', 'enabled' => false]);
    expect($sandbox->fresh()->api_key)->toBe('sandbox-secret');
    $this->service->save($this->admin, 'sandbox', ['remove_key' => true, 'enabled' => false]);
    expect($sandbox->fresh()->api_key)->toBeNull();
    Http::assertNothingSent();
});

it('does not enable tests without a key', function () {
    expect(fn () => $this->service->save($this->admin, 'sandbox', ['enabled' => true]))->toThrow(ValidationException::class);
    Http::assertNothingSent();
});

it('blocks all network requests until the documented endpoint is approved', function () {
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'test-secret', 'enabled' => true]);
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('blocked');
    Http::assertNothingSent();
    expect(DB::table('eligibility_connection_events')->where('event', 'connection_test')->value('status'))->toBe('blocked');
});

it('rejects malformed or unapproved endpoint configurations', function (string $url, string $host) {
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'test-secret', 'enabled' => true]);
    config(['eligibility.zuub.sandbox' => [
        'probe_url' => $url, 'approved_host' => $host, 'credential_header' => 'X-Test-Key',
        'credential_prefix' => '', 'probe_documented_safe' => true,
    ]]);
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('blocked');
    Http::assertNothingSent();
})->with([
    ['http://api.example.com/test', 'api.example.com'],
    ['https://127.0.0.1/test', '127.0.0.1'],
    ['https://example.local/test', 'example.local'],
    ['https://wrong.example.com/test', 'api.example.com'],
    ['https://api.example.com/test?key=unsafe', 'api.example.com'],
    ['https://user:password@api.example.com/test', 'api.example.com'],
]);

it('uses only the configured sandbox credential and never returns response bodies', function () {
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'test-secret', 'enabled' => true]);
    $this->service->save($this->admin, 'production', ['api_key' => 'live-secret', 'enabled' => true]);
    config(['eligibility.zuub.sandbox' => [
        'probe_url' => 'https://api.example.com/test', 'approved_host' => 'api.example.com',
        'credential_header' => 'X-Test-Key', 'credential_prefix' => '', 'probe_documented_safe' => true,
    ]]);
    Http::fake(['https://api.example.com/test' => Http::response(['secret' => 'sensitive-response'], 200)]);
    $result = $this->service->probe($this->admin, 'sandbox');
    expect($result['status'])->toBe('reachable')
        ->and(json_encode($result))->not->toContain('sensitive-response', 'test-secret', 'live-secret')
        ->and(json_encode(DB::table('eligibility_connection_events')->get()))->not->toContain('test-secret');
    Http::assertSent(fn ($request) => $request->hasHeader('X-Test-Key', 'test-secret') && $request->method() === 'GET' && $request->data() === []);
    expect($this->service->probe($this->admin, 'production')['status'])->toBe('blocked');
    Http::assertSentCount(1);
});

it('handles rejection rate limits and redirects without retrying', function (int $httpStatus, string $expected) {
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'test-secret', 'enabled' => true]);
    config(['eligibility.zuub.sandbox' => [
        'probe_url' => 'https://api.example.com/test', 'approved_host' => 'api.example.com',
        'credential_header' => 'X-Test-Key', 'credential_prefix' => '', 'probe_documented_safe' => true,
    ]]);
    Http::fake(['*' => Http::response('Do not expose this body', $httpStatus, ['Location' => 'https://another.example.com'])]);
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe($expected);
    Http::assertSentCount(1);
})->with([[401, 'unauthorized'], [403, 'unauthorized'], [429, 'limited'], [302, 'failed'], [500, 'failed']]);

it('requires an active SaaS administrator for settings and actions', function () {
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $this->actingAs($user);
    expect(EligibilityConnectionSettings::canAccess())->toBeFalse();
    $this->get('/saas/eligibility-connection')->assertForbidden();
    expect(fn () => $this->service->save($user, 'sandbox', ['enabled' => false]))->toThrow(HttpException::class);
    expect(fn () => $this->service->probe($user, 'sandbox'))->toThrow(HttpException::class);
    Http::assertNothingSent();
});

it('renders settings without exposing saved credentials and runs fictional cases', function () {
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'hidden-secret', 'enabled' => false]);
    Livewire::test(EligibilityConnectionSettings::class)
        ->assertSuccessful()
        ->assertSet('data.api_key', null)
        ->assertDontSee('hidden-secret')
        ->call('runDemonstration')
        ->assertSee('Avery Example (TEST)')
        ->assertSee('Not returned')
        ->assertSee('not a Zuub response');
    Http::assertNothingSent();
});

it('requires password confirmation and clears secrets after saving', function () {
    Livewire::test(EligibilityConnectionSettings::class)
        ->set('data.api_key', 'new-secret')
        ->set('data.password', 'Local-test-password-123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('data.api_key', null)
        ->assertSet('data.password', null);
    expect($this->service->connection('sandbox')->api_key)->toBe('new-secret');
});

it('keeps missing benefits distinct from negative benefits and retrieval failures', function () {
    $demo = app(EligibilityDemonstration::class);
    $partial = $demo->run('partial');
    expect(collect($partial['fields'])->firstWhere('label', 'Orthodontic payment schedule')['value'])->toBeNull()
        ->and($demo->run('unavailable')['status'])->toBe('Unable to retrieve')
        ->and($demo->run('inactive')['status'])->toBe('Inactive coverage');
    Http::assertNothingSent();
});

it('rejects incorrect passwords without storing credentials', function () {
    Livewire::test(EligibilityConnectionSettings::class)
        ->set('data.api_key', 'never-save-this')
        ->set('data.password', 'incorrect-password')
        ->call('save')
        ->assertHasErrors(['password'])
        ->assertSet('data.api_key', null)
        ->assertSet('data.password', null);
    expect($this->service->connection('sandbox')->api_key)->toBeNull();
});

it('resets verification status on credential rotation and rejects header injection', function () {
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'first-key', 'enabled' => true]);
    $this->service->connection('sandbox')->update(['last_check_status' => 'reachable', 'last_checked_at' => now()]);
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'second-key', 'enabled' => true]);
    expect($this->service->connection('sandbox')->last_check_status)->toBeNull();
    expect(fn () => $this->service->save($this->admin, 'sandbox', ['api_key' => "unsafe\r\nHeader: value", 'enabled' => true]))->toThrow(ValidationException::class);
    Http::assertNothingSent();
});
