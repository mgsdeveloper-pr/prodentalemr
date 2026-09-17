<?php

use App\Filament\Saas\Pages\EligibilityConnectionSettings;
use App\Models\User;
use App\Services\Eligibility\StediConnectionService;
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
    $this->service = app(StediConnectionService::class);
});

it('isolates encrypted Stedi credentials and preserves or removes saved keys', function () {
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'test-secret', 'enabled' => true]);
    $connection = $this->service->connection('sandbox');
    expect($connection->provider)->toBe('stedi');
    expect(DB::table('eligibility_connections')->where('id', $connection->id)->value('api_key'))->not->toContain('test-secret');
    $this->service->save($this->admin, 'sandbox', ['enabled' => true]);
    expect($connection->fresh()->api_key)->toBe('test-secret')->and($this->service->connection('production')->api_key)->toBeNull();
    $this->service->save($this->admin, 'sandbox', ['remove_key' => true, 'enabled' => false]);
    expect($connection->fresh()->api_key)->toBeNull();
    Http::assertNothingSent();
});

it('makes one bounded read only request with the raw selected API key', function ($environment) {
    Http::fake(['https://payers.us.stedi.com/2024-04-01/payers*' => Http::response(['items' => [['stediId' => 'ABCDE', 'displayName' => 'Test payer']], 'nextPageToken' => 'do-not-follow'])]);
    $this->service->save($this->admin, $environment, ['api_key' => 'selected-key', 'enabled' => true]);
    expect($this->service->probe($this->admin, $environment)['status'])->toBe('directory_access');
    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://payers.us.stedi.com/2024-04-01/payers?pageSize=10'
        && $request->hasHeader('Authorization', 'selected-key') && $request->body() === '');
    Http::assertSentCount(1);
    expect($this->service->connection($environment)->eligibility_enabled)->toBeFalse();
})->with(['sandbox', 'production']);

it('rejects unexpected responses permission errors and redirects without retrying', function ($body, $code, $status) {
    Http::fake(['https://payers.us.stedi.com/2024-04-01/payers*' => Http::response($body, $code)]);
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'key', 'enabled' => true]);
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe($status);
    Http::assertSentCount(1);
})->with([
    [[], 401, 'unauthorized'], [[], 403, 'unauthorized'], [[], 429, 'limited'], [[], 302, 'failed'], [[], 500, 'failed'],
    ['not-json', 200, 'failed'], [['items' => [['unexpected' => true]]], 200, 'failed'],
    [['items' => []], 200, 'directory_access'],
]);

it('blocks disabled checks and rate limits directory requests', function () {
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('blocked');
    Http::assertNothingSent();
    Http::fake(['https://payers.us.stedi.com/2024-04-01/payers*' => Http::response(['items' => []])]);
    $this->service->save($this->admin, 'sandbox', ['api_key' => 'key', 'enabled' => true]);
    $this->service->probe($this->admin, 'sandbox');
    $this->service->probe($this->admin, 'sandbox');
    expect($this->service->probe($this->admin, 'sandbox')['status'])->toBe('limited');
    Http::assertSentCount(2);
});

it('requires administrator confirmation and never renders saved keys', function () {
    Livewire::withQueryParams(['provider' => 'stedi'])->test(EligibilityConnectionSettings::class)
        ->assertSee('Test directory access')->set('data.api_key', 'new-secret')->set('data.enabled', true)
        ->set('data.password', 'test-password')->call('save')->assertHasNoErrors()
        ->assertSet('data.api_key', null)->assertDontSee('new-secret');
    Livewire::withQueryParams(['provider' => 'stedi'])->test(EligibilityConnectionSettings::class)
        ->set('data.api_key', 'replacement')->set('data.password', 'wrong')->call('save')
        ->assertHasErrors()->assertSet('data.api_key', null);
    expect($this->service->connection('sandbox')->api_key)->toBe('new-secret');
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $this->actingAs($user)->get('/saas/eligibility-connection?provider=stedi')->assertForbidden();
    Http::assertNothingSent();
});
