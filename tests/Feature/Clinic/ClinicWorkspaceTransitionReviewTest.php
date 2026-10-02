<?php

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->withoutVite();
    $org = Organization::create(['name' => 'Routing QA', 'owner_name' => 'QA', 'email' => 'routing@example.test', 'status' => true]);
    $this->clinics = [];
    foreach (['dual' => [true, true], 'verification' => [true, false], 'pms' => [false, true]] as $key => [$verification, $pms]) {
        $this->clinics[$key] = Clinic::create([
            'organization_id' => $org->id, 'clinic_name' => 'Routing '.$key, 'clinic_code' => 'ROUTE-'.$key,
            'status' => true, 'service_status' => 'active', 'verification_service_status' => 'active',
            'pms_service_status' => 'active', 'verification_services_enabled' => $verification,
            'clinic_operations_enabled' => $pms,
        ]);
    }
    $admin = User::factory()->create(['organization_id' => null, 'clinic_id' => null, 'status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    session()->forget([ClinicPanelScope::SESSION_KEY, ClinicWorkspace::SESSION_KEY]);
});

it('opens the requested workspace without a detour after selecting a dual-service clinic', function () {
    $target = url('/clinic/verification-settings');
    $this->get(route('clinic.clinic-scope', ['clinic_id' => $this->clinics['dual']->id, 'redirect' => $target]))
        ->assertRedirect($target);
    expect(session(ClinicWorkspace::SESSION_KEY))->toBe('verification');
    $this->get($target)->assertOk();
    $this->get(route('clinic.choose-workspace'))->assertOk();
    $this->post(route('clinic.switch-workspace', ['workspace' => 'verification']))
        ->assertRedirect(url('/clinic/verification-requests'));
});

it('preserves a compatible selected workspace when switching to a dual-service clinic', function () {
    $this->withSession([ClinicWorkspace::SESSION_KEY => 'verification', ClinicPanelScope::SESSION_KEY => $this->clinics['verification']->id]);
    $this->get(route('clinic.clinic-scope', ['clinic_id' => $this->clinics['dual']->id, 'redirect' => url('/clinic/verification-settings')]))
        ->assertRedirect(url('/clinic/verification-settings'));
    $this->get('/clinic/verification-settings')->assertOk();
    expect(session(ClinicWorkspace::SESSION_KEY))->toBe('verification');
});

it('automatically selects the only available workspace after a clinic switch', function ($clinicKey, $previous, $expected) {
    $this->withSession([ClinicWorkspace::SESSION_KEY => $previous, ClinicPanelScope::SESSION_KEY => $this->clinics['dual']->id]);
    $this->get(route('clinic.clinic-scope', ['clinic_id' => $this->clinics[$clinicKey]->id, 'redirect' => url('/clinic')]))
        ->assertRedirect(ClinicWorkspace::homeUrl($expected));
    $this->get(ClinicWorkspace::homeUrl($expected))->assertOk();
    expect(session(ClinicWorkspace::SESSION_KEY))->toBe($expected);
})->with([
    ['verification', 'clinic_pms', 'verification'],
    ['pms', 'verification', 'clinic_pms'],
]);

it('drops a previous clinics record identifiers when changing clinic', function () {
    $target = url('/clinic/verification-questions?version=999&draft=1&section=old_clinic_section');
    $this->get(route('clinic.clinic-scope', ['clinic_id' => $this->clinics['dual']->id, 'redirect' => $target]))
        ->assertRedirect(url('/clinic/verification-questions'));
});

it('offers a clinic picker on fresh multi-clinic entry', function () {
    $this->get('/clinic')->assertRedirect(route('clinic.choose-workspace'));
    expect(session(ClinicPanelScope::SESSION_KEY))->toBeNull();
    $this->get(route('clinic.choose-workspace'))->assertOk()->assertSee('Select Clinic');
});

it('rejects external return destinations', function () {
    $this->get(route('clinic.clinic-scope', ['clinic_id' => $this->clinics['dual']->id, 'redirect' => 'https://example.org/clinic']))
        ->assertRedirect(url('/clinic/verification-requests'));
});

it('returns from the clinic picker to the original settings page', function () {
    $this->get('/clinic/verification-settings')->assertRedirect(route('clinic.choose-workspace'));
    $this->get(route('clinic.clinic-scope', ['clinic_id' => $this->clinics['dual']->id]))
        ->assertRedirect(url('/clinic/verification-settings'));
    $this->get('/clinic/verification-settings')->assertOk();
});

it('falls back to a supported workspace when the destination module is unavailable', function () {
    $this->withSession([ClinicWorkspace::SESSION_KEY => 'verification']);
    $this->get(route('clinic.clinic-scope', ['clinic_id' => $this->clinics['pms']->id,
        'redirect' => url('/clinic/verification-settings')]))->assertRedirect(url('/clinic'));
    expect(session(ClinicWorkspace::SESSION_KEY))->toBe('clinic_pms');
});

it('removes a record path when changing clinic and preserves it when staying in the same clinic', function () {
    $clinic = $this->clinics['pms'];
    $target = url('/clinic/patients/123/edit');
    $this->get(route('clinic.clinic-scope', ['clinic_id' => $clinic->id, 'redirect' => $target]))
        ->assertRedirect(url('/clinic/patients'));
    $this->get(route('clinic.clinic-scope', ['clinic_id' => $clinic->id, 'redirect' => $target]))
        ->assertRedirect($target);
});

it('does not allow a clinic admin to switch into another clinic', function () {
    $clinic = $this->clinics['dual'];
    $user = User::factory()->create(['organization_id' => $clinic->organization_id, 'clinic_id' => $clinic->id, 'status' => true]);
    $user->assignRole('clinic_admin');
    $this->actingAs($user)->get(route('clinic.clinic-scope', ['clinic_id' => $this->clinics['pms']->id]))->assertForbidden();
});
