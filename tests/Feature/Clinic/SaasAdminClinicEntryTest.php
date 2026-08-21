<?php

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Support\ClinicPanelScope;
use Database\Seeders\RoleSeeder;

it('initializes a clinic scope when a SaaS administrator opens the clinic panel', function (): void {
    $this->seed(RoleSeeder::class);

    $organization = Organization::create([
        'name' => 'Access Test Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@access.test',
        'status' => true,
    ]);

    $clinic = Clinic::create([
        'organization_id' => $organization->id,
        'clinic_name' => 'Access Test Clinic',
        'clinic_code' => 'CLN-ACCESS',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $admin = User::factory()->create([
        'email' => 'admin@mgs.com',
        'organization_id' => null,
        'clinic_id' => null,
        'status' => true,
    ]);
    $admin->assignRole('saas_admin');

    $this->actingAs($admin)
        ->get('/clinic')
        ->assertRedirect(route('clinic.choose-workspace'));

    expect(session(ClinicPanelScope::SESSION_KEY))->toBe($clinic->getKey());

    $this->get(route('clinic.choose-workspace'))
        ->assertSuccessful();
});
