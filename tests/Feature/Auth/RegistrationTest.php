<?php

use Database\Seeders\RoleSeeder;

test('registration screen can be rendered', function () {
    config()->set('prodental.public_registration', true);

    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    config()->set('prodental.public_registration', true);
    $this->seed(RoleSeeder::class);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '5551001234',
        'organization_name' => 'Test Dental Group',
        'clinic_name' => 'Test Dental Clinic',
        'location_name' => 'Main Office',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice'));
});

test('public registration is closed by default', function () {
    config()->set('prodental.public_registration', false);

    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});
