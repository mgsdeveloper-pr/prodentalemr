<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create([
        'status' => true,
    ]);

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->unverified()->create([
        'status' => true,
    ]);
    $user->assignRole('saas_admin');

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect('/saas?verified=1');
});

test('dashboard forwards verified users to their assigned workspace', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => true,
    ]);
    $user->assignRole('verification_user');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect('/verification');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
