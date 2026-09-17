<?php

use App\Filament\Saas\Pages\EligibilityConnectionSettings;
use App\Models\EligibilityConnection;
use App\Models\User;
use App\Services\Eligibility\VyneConnectionService;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create(['status' => true, 'password' => bcrypt('test-password')]);
    $this->admin->assignRole('saas_admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('saas'));
    Http::fake([]);
    Http::preventStrayRequests();
    $this->service = app(VyneConnectionService::class);
});

it('stores the OAuth pair encrypted and preserves blank credentials', function () {
    $this->service->save($this->admin, 'production', ['client_id' => 'private-id', 'client_secret' => 'private-secret', 'enabled' => true]);
    $connection = $this->service->connection('production');
    expect(DB::table('eligibility_connections')->where('id', $connection->id)->value('api_key'))->not->toContain('private-secret');
    expect($connection->toArray())->not->toHaveKey('api_key');
    $this->service->save($this->admin, 'production', ['enabled' => true]);
    expect(json_decode($connection->fresh()->api_key, true)['client_id'])->toBe('private-id');
    expect($this->service->connection('sandbox')->api_key)->toBeNull();
    $this->service->save($this->admin, 'production', ['remove_key' => true, 'enabled' => false]);
    expect($connection->fresh()->api_key)->toBeNull();
    Http::assertNothingSent();
});

it('rejects incomplete credential pairs', function () {
    $this->service->save($this->admin, 'production', ['client_id' => 'only-id', 'enabled' => true]);
})->throws(ValidationException::class);

it('blocks disabled tests without sending requests', function () {
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('blocked');
    Http::assertNothingSent();
});

it('sends only the documented fictional sandbox identity without credentials', function () {
    Http::fake(['https://sandbox.onederful.co/sandbox/eligibility' => Http::response([
        'patient' => ['coverage' => ['status' => 'active']],
        'deductible' => [['amount' => '0.00', 'network' => 'in_network', 'coverage_level' => 'individual']],
        'coinsurance' => [['category' => 'preventive', 'percent' => '100'], ['category' => 'major']],
    ])]);
    $this->service->save($this->admin, 'sandbox', ['enabled' => true]);
    $result = $this->service->probe($this->admin, 'sandbox', 'METLIFE');
    expect($result['status'])->toBe('sandbox_received')
        ->and($result['benefits'][0]['value'])->toBe('0.00')
        ->and($result['benefits'][0]['level'])->toBe('individual')
        ->and($result['benefits'][2]['value'])->toBe('Not returned');
    Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.onederful.co/sandbox/eligibility'
        && $request['subscriber']['first_name'] === 'TEST' && $request['payer']['id'] === 'METLIFE'
        && ! $request->hasHeader('Authorization') && ! isset($request['client_secret']));
    Http::assertSentCount(1);
    expect(EligibilityConnection::where('provider', 'zuub')->exists())->toBeFalse();
});

it('authenticates production without retaining or exposing the token or sending patients', function () {
    Http::fake(['https://production.onederful.co/oauth2/token' => Http::response(['access_token' => 'private-token', 'token_type' => 'Bearer', 'scope' => 'feature:eligibility', 'expires_in' => 3600])]);
    $this->service->save($this->admin, 'production', ['client_id' => 'id', 'client_secret' => 'secret', 'enabled' => true]);
    $result = $this->service->probe($this->admin, 'production');
    expect($result['status'])->toBe('authenticated')->and(json_encode($result))->not->toContain('private-token');
    Http::assertSent(fn ($request) => $request->data() === ['client_id' => 'id', 'client_secret' => 'secret']);
    Http::assertSentCount(1);
    expect($this->service->connection('production')->eligibility_enabled)->toBeFalse();
});

it('handles errors malformed responses and missing scope without retries', function ($body, $httpStatus, $expected) {
    Http::fake(['https://production.onederful.co/oauth2/token' => Http::response($body, $httpStatus)]);
    $this->service->save($this->admin, 'production', ['client_id' => 'id', 'client_secret' => 'secret', 'enabled' => true]);
    expect($this->service->probe($this->admin, 'production')['status'])->toBe($expected);
    Http::assertSentCount(1);
})->with([
    [[], 401, 'unauthorized'], [[], 429, 'limited'], [[], 302, 'failed'], [[], 500, 'failed'],
    ['not-json', 200, 'failed'],
    [['access_token' => 'token', 'token_type' => 'Bearer', 'scope' => 'other'], 200, 'scope_missing'],
]);

it('requires password confirmation and clears the pair from the page', function () {
    Livewire::withQueryParams(['provider' => 'vyne'])->test(EligibilityConnectionSettings::class)
        ->assertSee('Sandbox API')->call('selectEnvironment', 'production')
        ->set('data.client_id', 'new-id')->set('data.client_secret', 'new-secret')
        ->set('data.enabled', true)->set('data.password', 'test-password')->call('save')
        ->assertHasNoErrors()->assertSet('data.client_secret', null)->assertSet('data.client_id', null)
        ->assertDontSee('new-secret');
    Livewire::withQueryParams(['provider' => 'vyne'])->test(EligibilityConnectionSettings::class)
        ->call('selectEnvironment', 'production')->set('data.client_id', 'replace-id')
        ->set('data.client_secret', 'replace-secret')->set('data.password', 'wrong')->call('save')
        ->assertHasErrors()->assertSet('data.client_secret', null);
    expect(json_decode($this->service->connection('production')->api_key, true)['client_id'])->toBe('new-id');
    Http::assertNothingSent();
});

it('rate limits sandbox tests and rejects unknown payer choices', function () {
    Http::fake(['https://sandbox.onederful.co/sandbox/eligibility' => Http::response(['patient' => []])]);
    $this->service->save($this->admin, 'sandbox', ['enabled' => true]);
    $this->service->probe($this->admin, 'sandbox');
    $this->service->probe($this->admin, 'sandbox');
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('limited');
    Http::assertSentCount(2);
    Livewire::withQueryParams(['provider' => 'vyne'])->test(EligibilityConnectionSettings::class)
        ->set('sandboxPayer', 'https://unapproved.test')->call('testConnection')->assertStatus(422);
});

it('denies nonadministrators direct Vyne actions', function () {
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $this->actingAs($user);
    $this->get('/saas/eligibility-connection?provider=vyne')->assertForbidden();
    Http::assertNothingSent();
});
