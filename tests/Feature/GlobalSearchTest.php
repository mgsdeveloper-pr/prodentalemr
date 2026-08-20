<?php

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('requires authentication for global search', function (): void {
    $this->getJson(route('app.global-search', ['workspace' => 'platform', 'q' => 'Demo']))
        ->assertUnauthorized();
});

it('returns permitted platform results with host-independent links', function (): void {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');

    Organization::create([
        'name' => 'Demo Search Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@search.test',
        'phone' => '5551002000',
        'status' => true,
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('app.global-search', ['workspace' => 'platform', 'q' => 'Demo']));

    $response
        ->assertOk()
        ->assertJsonPath('workspace', 'platform')
        ->assertJsonPath('result_count', 1)
        ->assertJsonPath('groups.0.label', 'Organizations')
        ->assertJsonPath('groups.0.items.0.title', 'Demo Search Dental Group');

    expect($response->json('groups.0.items.0.url'))
        ->toStartWith('/saas/organizations/')
        ->not->toContain('127.0.0.1');
});

it('rejects a workspace the user cannot access', function (): void {
    $clinicUser = User::factory()->create(['status' => true]);
    $clinicUser->assignRole('staff');

    $this->actingAs($clinicUser)
        ->getJson(route('app.global-search', ['workspace' => 'platform', 'q' => 'Demo']))
        ->assertForbidden();
});
