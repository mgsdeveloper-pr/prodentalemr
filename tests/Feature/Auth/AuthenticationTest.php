<?php

use App\Models\SaasSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertStatus(200)
        ->assertSee('Welcome back')
        ->assertSee('Dental operations, connected.')
        ->assertSee('images/login/dental-office-default.png');
});

test('saas admin login image replaces the default image', function () {
    SaasSetting::query()->updateOrCreate(
        ['id' => 1],
        [
            'platform_name' => 'ProDental EMR',
            'login_image_path' => 'branding/login/custom-clinic.webp',
        ],
    );

    $this->get('/login')
        ->assertOk()
        ->assertSee('uploads/branding/login/custom-clinic.webp');
});

test('users can authenticate using the login screen', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => true,
    ]);
    $user->assignRole('saas_admin');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/saas');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
