<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

it('requires post for workflow and session mutations', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/saas/sign-out/perform')
        ->assertMethodNotAllowed();

    $this->actingAs($user)
        ->get('/choose-workspace/verification')
        ->assertMethodNotAllowed();

    expect(collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($route) => $route->getName() === 'clinic.switch-workspace')
        ?->methods())->toBe(['POST']);
});

it('requires email verification before a user can enter a product panel', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->unverified()->create(['status' => true]);
    $user->assignRole('saas_admin');

    $this->actingAs($user)
        ->get('/saas')
        ->assertRedirect(route('verification.notice'));
});

it('keeps one canonical portal credential workspace', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create(['status' => true]);
    $user->assignRole('saas_admin');

    $this->actingAs($user)
        ->get('/verification/portal-credential-settings')
        ->assertRedirect('/verification/portal-credentials');

    $this->actingAs($user)
        ->get('/clinic/portal-credential-settings')
        ->assertRedirect('/clinic/portal-credentials');
});
