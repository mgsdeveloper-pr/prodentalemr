<?php

use App\Filament\Clinic\Resources\Locations\LocationResource;
use App\Filament\Clinic\Resources\Users\UserResource;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\Organization;
use App\Models\SaasEntitlementAuditLog;
use App\Models\User;
use App\Support\ClinicPanelScope;
use App\Support\SaasSupportAccess;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::create([
        'name' => 'Administration Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@administration.test',
        'phone' => '5551000000',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Administration Clinic',
        'clinic_code' => 'ADM-CLN',
        'timezone' => 'America/New_York',
        'status' => true,
        'verification_services_enabled' => true,
        'verification_service_status' => 'active',
        'service_status' => 'active',
    ]);
});

it('lets a clinic administrator manage locations in its own clinic', function (): void {
    $clinicAdmin = User::factory()->create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'status' => true,
    ]);
    $clinicAdmin->assignRole('clinic_admin');

    $this->actingAs($clinicAdmin);

    expect(LocationResource::canViewAny())->toBeTrue()
        ->and(LocationResource::canCreate())->toBeTrue()
        ->and(UserResource::canViewAny())->toBeTrue()
        ->and(UserResource::canView($clinicAdmin))->toBeTrue();
});

it('keeps saas clinic administration read only until matching support mode is active', function (): void {
    $saasAdmin = User::factory()->create(['status' => true]);
    $saasAdmin->assignRole('saas_admin');

    $this->actingAs($saasAdmin);
    session([ClinicPanelScope::SESSION_KEY => $this->clinic->id]);

    expect(LocationResource::canViewAny())->toBeTrue()
        ->and(LocationResource::canCreate())->toBeFalse();

    SaasSupportAccess::start($saasAdmin, $this->organization, $this->clinic, 'Assist with clinic location setup.');

    expect(LocationResource::canCreate())->toBeTrue();

    Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Support Main',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10001',
        'country' => 'USA',
        'status' => true,
    ]);

    expect(SaasEntitlementAuditLog::query()->where('event_type', 'support_location_created')->exists())->toBeTrue();
});
