<?php

use App\Filament\Saas\Pages\EligibilityConnections;
use App\Filament\Saas\Pages\EligibilityConnectionSettings;
use App\Models\EligibilityConnection;
use App\Models\User;
use App\Services\Eligibility\EligibilityProviderCatalog;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create(['status' => true]);
    $this->admin->assignRole('saas_admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('saas'));
    Http::fake([]);
    Http::preventStrayRequests();
});

it('lists five disabled providers without creating credentials or making requests', function () {
    $page = Livewire::test(EligibilityConnections::class)->assertSuccessful();
    foreach (EligibilityProviderCatalog::PROVIDERS as $id => $name) {
        $page->assertSee($name);
    }
    expect(app(EligibilityProviderCatalog::class)->rows())->toHaveCount(5);
    expect(collect(app(EligibilityProviderCatalog::class)->rows())->where('enabled', true))->toBeEmpty();
    expect(EligibilityConnection::count())->toBe(0);
    Http::assertNothingSent();
});

it('opens each provider detail page and rejects unsupported providers', function () {
    foreach (EligibilityProviderCatalog::PROVIDERS as $id => $name) {
        $this->get('/saas/eligibility-connection?provider='.$id)->assertOk()->assertSee($name);
    }
    $this->get('/saas/eligibility-connection?provider=unknown')->assertNotFound();
    $this->get('/saas/eligibility-connection')->assertOk()->assertSee('Zuub');
});

it('blocks activation even when called directly and does not confuse test enablement with eligibility', function () {
    EligibilityConnection::create(['provider' => 'zuub', 'environment' => 'production', 'api_key' => 'saved-key', 'enabled' => true, 'last_check_status' => 'reachable']);
    foreach (array_keys(EligibilityProviderCatalog::PROVIDERS) as $id) {
        Livewire::test(EligibilityConnections::class)
            ->call('setProviderEnabled', $id, true)
            ->assertHasErrors('provider_activation');
    }
    expect(collect(app(EligibilityProviderCatalog::class)->rows())->where('enabled', true))->toBeEmpty();
    Http::assertNothingSent();
});

it('disables an existing connection without deleting credentials test settings or history', function () {
    $connection = EligibilityConnection::create(['provider' => 'zuub', 'environment' => 'production', 'api_key' => 'retain-key', 'enabled' => true]);
    $connection->forceFill(['eligibility_enabled' => true])->save();
    $connection->events()->create(['event' => 'connection_test', 'status' => 'reachable', 'user_id' => $this->admin->id]);
    Livewire::test(EligibilityConnections::class)->call('setProviderEnabled', 'zuub', false)->assertHasNoErrors();
    expect($connection->fresh()->eligibility_enabled)->toBeFalse()
        ->and($connection->fresh()->api_key)->toBe('retain-key')
        ->and($connection->fresh()->enabled)->toBeTrue()
        ->and($connection->events()->count())->toBe(2);
    Http::assertNothingSent();
});

it('denies other users access to overview and configuration', function () {
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $this->actingAs($user);
    $this->get('/saas/eligibility-connections')->assertForbidden();
    $this->get('/saas/eligibility-connection?provider=stedi')->assertForbidden();
});

it('does not expose Zuub demonstration actions in other provider settings', function () {
    Livewire::withQueryParams(['provider' => 'stedi'])->test(EligibilityConnectionSettings::class)
        ->assertSee('Stedi Settings')
        ->assertDontSee('New API credential')
        ->call('runDemonstration')->assertForbidden();
    Http::assertNothingSent();
});
