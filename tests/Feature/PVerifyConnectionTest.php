<?php

use App\Filament\Saas\Pages\EligibilityConnectionSettings;
use App\Models\User;
use App\Services\Eligibility\PVerifyConnectionService;
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
    $this->service = app(PVerifyConnectionService::class);
});

it('encrypts the pair isolates environments and preserves blank input', function () {
    $this->service->save($this->admin, 'sandbox', ['client_id' => 'test-id', 'client_secret' => 'test-secret', 'enabled' => true]);
    $connection = $this->service->connection('sandbox');
    expect(DB::table('eligibility_connections')->where('id', $connection->id)->value('api_key'))->not->toContain('test-secret');
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'injected', 'enabled' => true]);
    expect(json_decode($connection->fresh()->api_key, true)['client_id'])->toBe('test-id')
        ->and($this->service->connection('production')->api_key)->toBeNull();
    $this->service->save($this->admin, 'sandbox', ['remove_key' => true, 'enabled' => false]);
    expect($connection->fresh()->api_key)->toBeNull();
    Http::assertNothingSent();
});

it('rejects partial pairs and header injection', function ($input) {
    $this->service->save($this->admin, 'sandbox', $input + ['enabled' => true]);
})->with([
    [['client_id' => 'id']],
    [['client_secret' => 'secret']],
    [['client_id' => "id\r\nInjected: true", 'client_secret' => 'secret']],
])->throws(ValidationException::class);

it('uses form encoding exact header names and the selected token endpoint', function ($environment, $url) {
    Http::fake([$url => Http::response(['access_token' => 'private-token', 'token_type' => 'bearer', 'expires_in' => 3600])]);
    $this->service->save($this->admin, $environment, ['client_id' => 'selected-id', 'client_secret' => 'secret&+=value', 'enabled' => true]);
    $result = $this->service->probe($this->admin, $environment);
    expect($result['status'])->toBe('authenticated')->and(json_encode($result))->not->toContain('private-token');
    Http::assertSent(function ($request) use ($url) {
        parse_str($request->body(), $body);

        return $request->url() === $url && $request->method() === 'POST'
            && $request->hasHeader('Client-API-Id', 'selected-id')
            && str_starts_with($request->header('Content-Type')[0], 'application/x-www-form-urlencoded')
            && $body === ['Client_Id' => 'selected-id', 'Client_Secret' => 'secret&+=value', 'grant_type' => 'client_credentials'];
    });
    Http::assertSentCount(1);
    expect($this->service->connection($environment)->eligibility_enabled)->toBeFalse();
})->with([['sandbox', 'https://testapi.pverify.com/Token'], ['production', 'https://api.pverify.com/Token']]);

it('rejects errors invalid tokens and redirects without retrying', function ($body, $code, $status) {
    Http::fake(['https://testapi.pverify.com/Token' => Http::response($body, $code)]);
    $this->service->save($this->admin, 'sandbox', ['client_id' => 'id', 'client_secret' => 'secret', 'enabled' => true]);
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe($status);
    Http::assertSentCount(1);
})->with([
    [[], 401, 'unauthorized'], [[], 403, 'unauthorized'], [[], 429, 'limited'], [[], 302, 'failed'], [[], 500, 'failed'],
    ['not-json', 200, 'failed'],
    [['access_token' => 'token', 'token_type' => 'bearer', 'expires_in' => 0], 200, 'failed'],
    [['error' => 'invalid_client'], 200, 'failed'],
]);

it('requires saved enablement and limits token calls', function () {
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('blocked');
    Http::assertNothingSent();
    Http::fake(['https://testapi.pverify.com/Token' => Http::response(['access_token' => 'token', 'token_type' => 'bearer', 'expires_in' => 3600])]);
    $this->service->save($this->admin, 'sandbox', ['client_id' => 'id', 'client_secret' => 'secret', 'enabled' => true]);
    $this->service->probe($this->admin, 'sandbox');
    $this->service->probe($this->admin, 'sandbox');
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('limited');
    Http::assertSentCount(2);
});

it('secures the settings page and clears secrets on success and failure', function () {
    Livewire::withQueryParams(['provider' => 'pverify'])->test(EligibilityConnectionSettings::class)
        ->assertSee('Test authentication')->assertSee('New Client API ID')
        ->set('data.client_id', 'new-id')->set('data.client_secret', 'new-secret')->set('data.enabled', true)
        ->set('data.password', 'test-password')->call('save')->assertHasNoErrors()
        ->assertSet('data.client_secret', null)->assertSet('data.client_id', null)->assertDontSee('new-secret');
    Livewire::withQueryParams(['provider' => 'pverify'])->test(EligibilityConnectionSettings::class)
        ->set('data.client_id', 'replace-id')->set('data.client_secret', 'replace-secret')
        ->set('data.password', 'wrong')->call('save')->assertHasErrors()->assertSet('data.client_secret', null);
    expect(json_decode($this->service->connection('sandbox')->api_key, true)['client_id'])->toBe('new-id');
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $this->actingAs($user)->get('/saas/eligibility-connection?provider=pverify')->assertForbidden();
    Http::assertNothingSent();
});
