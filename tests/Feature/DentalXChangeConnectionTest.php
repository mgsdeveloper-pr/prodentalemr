<?php

use App\Filament\Saas\Pages\EligibilityConnectionSettings;
use App\Models\User;
use App\Services\Eligibility\DentalXChangeConnectionService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create(['status' => true, 'password' => bcrypt('test-password')]);
    $this->admin->assignRole('saas_admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('saas'));
    Http::fake([]);
    Http::preventStrayRequests();
    $this->service = app(DentalXChangeConnectionService::class);
});

it('isolates encrypted DentalXChange keys by environment and preserves blank input', function () {
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'sandbox-secret', 'enabled' => true]);
    $connection = $this->service->connection('sandbox');
    expect($connection->provider)->toBe('dentalxchange');
    expect(DB::table('eligibility_connections')->where('id', $connection->id)->value('api_key'))->not->toContain('sandbox-secret');
    $this->service->save($this->admin, 'sandbox', ['enabled' => true]);
    expect($connection->fresh()->api_key)->toBe('sandbox-secret')
        ->and($this->service->connection('production')->api_key)->toBeNull();
    $this->service->save($this->admin, 'sandbox', ['enabled' => false, 'remove_key' => true]);
    expect($connection->fresh()->api_key)->toBeNull();
    Http::assertNothingSent();
});

it('uses the exact environment endpoint and sends only its API key', function ($environment, $path) {
    Http::fake(['https://api.dentalxchange.com'.$path => Http::response(['healthy' => true, 'dependencies' => []])]);
    $this->service->save($this->admin, $environment, ['api_key' => 'selected-key', 'enabled' => true]);
    expect($this->service->probe($this->admin, $environment)['status'])->toBe('healthy');
    Http::assertSent(fn ($request) => $request->method() === 'GET' && $request->url() === 'https://api.dentalxchange.com'.$path
        && $request->hasHeader('API-Key', 'selected-key') && ! $request->hasHeader('username') && ! $request->hasHeader('password') && $request->body() === '');
    Http::assertSentCount(1);
    expect($this->service->connection($environment)->eligibility_enabled)->toBeFalse();
})->with([['sandbox', '/sandbox/eligibility/health'], ['production', '/eligibility/health']]);

it('handles failed health responses without retrying or exposing response bodies', function ($body, $code, $status) {
    Http::fake(['https://api.dentalxchange.com/sandbox/eligibility/health' => Http::response($body, $code)]);
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'saved-key', 'enabled' => true]);
    $result = $this->service->probe($this->admin, 'sandbox');
    expect($result['status'])->toBe($status)->and(json_encode($result))->not->toContain('private-response');
    Http::assertSentCount(1);
})->with([
    [['healthy' => false], 200, 'unhealthy'],
    [['healthy' => 'true'], 200, 'failed'],
    ['private-response', 200, 'failed'],
    [[], 401, 'unauthorized'], [[], 403, 'unauthorized'], [[], 429, 'limited'], [[], 302, 'failed'], [[], 500, 'failed'],
]);

it('blocks disabled checks and limits repeat requests', function () {
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('blocked');
    Http::assertNothingSent();
    Http::fake(['https://api.dentalxchange.com/sandbox/eligibility/health' => Http::response(['healthy' => true])]);
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'key', 'enabled' => true]);
    $this->service->probe($this->admin, 'sandbox');
    $this->service->probe($this->admin, 'sandbox');
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('limited');
    Http::assertSentCount(2);
});

it('requires administrator confirmation and clears keys from Livewire state', function () {
    Livewire::withQueryParams(['provider' => 'dentalxchange'])->test(EligibilityConnectionSettings::class)
        ->assertSee('XConnect Eligibility')->assertSee('New API key')
        ->set('data.api_key', 'new-key')->set('data.enabled', true)->set('data.password', 'test-password')
        ->call('save')->assertHasNoErrors()->assertSet('data.api_key', null)->assertDontSee('new-key');
    Livewire::withQueryParams(['provider' => 'dentalxchange'])->test(EligibilityConnectionSettings::class)
        ->set('data.api_key', 'wrong-key')->set('data.password', 'wrong')->call('save')
        ->assertHasErrors()->assertSet('data.api_key', null);
    expect($this->service->connection('sandbox')->api_key)->toBe('new-key');
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $this->actingAs($user)->get('/saas/eligibility-connection?provider=dentalxchange')->assertForbidden();
    Http::assertNothingSent();
});
