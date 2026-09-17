<?php

use App\Filament\Saas\Pages\CallingProviders;
use App\Models\SaasSetting;
use App\Models\User;
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

it('defaults Twilio off and persists both switch states without calling a provider', function () {
    Livewire::test(CallingProviders::class)->assertSee('Twilio')->assertSee('Setup pending')->assertSee('Disabled')
        ->call('setTwilioEnabled', true)->assertHasNoErrors()->assertSee('Enabled');
    expect((bool) SaasSetting::current()->fresh()->twilio_option_enabled)->toBeTrue();
    Livewire::test(CallingProviders::class)->call('setTwilioEnabled', false)->assertSee('Disabled');
    expect((bool) SaasSetting::current()->fresh()->twilio_option_enabled)->toBeFalse();
    Http::assertNothingSent();
});

it('provides a working MightyCall management link', function () {
    $this->get('/saas/calling-providers')->assertOk()->assertSee('/saas/telephony-accounts');
});

it('denies nonadministrators access', function () {
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $this->actingAs($user)->get('/saas/calling-providers')->assertForbidden();
    Http::assertNothingSent();
});
