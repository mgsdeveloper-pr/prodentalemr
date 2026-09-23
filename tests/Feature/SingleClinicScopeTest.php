<?php

use App\Models\Clinic;
use App\Models\User;
use App\Support\AdminClinicScope;
use App\Support\ClinicPanelScope;
use Database\Seeders\ByDesignDentalsSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    config(['client_provisioning.by_design_dentals_tax_id' => null]);
    $this->seed(ByDesignDentalsSeeder::class);
    $this->clinic = Clinic::where('clinic_code', ByDesignDentalsSeeder::CODE)->firstOrFail();
    $this->manager = User::factory()->create(['status' => true]);
    $this->manager->assignRole('verification_manager');
    $this->manager->verificationClinics()->attach($this->clinic->id);
    $this->actingAs($this->manager);
});

it('resolves the only authorized clinic before a page or sidebar renders', function () {
    expect(AdminClinicScope::selectedClinicId())->toBe($this->clinic->id);
    expect(session(AdminClinicScope::SESSION_KEY))->toBe($this->clinic->id);
    expect(AdminClinicScope::selectedClinic()->id)->toBe($this->clinic->id);
    $html = view('filament.admin.partials.clinic-scope-switcher', ['clinicOptions' => AdminClinicScope::clinicOptions()])->render();
    expect($html)->toContain('By Design Dentals')->not->toContain('aria-haspopup="listbox"');
});

it('clears revoked access and shows an empty state', function () {
    AdminClinicScope::selectedClinicId();
    $this->manager->verificationClinics()->detach();
    expect(AdminClinicScope::selectedClinic())->toBeNull();
    expect(session(AdminClinicScope::SESSION_KEY))->toBeNull();
    expect(AdminClinicScope::clinicOptions())->toBe([]);
    $html = view('filament.admin.partials.clinic-scope-switcher', ['clinicOptions' => []])->render();
    expect($html)->toContain('No clinic assigned. Contact your administrator.');
});

it('does not choose arbitrarily among multiple clinics and restores a valid selection', function () {
    $other = Clinic::create([
        'organization_id' => $this->clinic->organization_id, 'clinic_name' => 'Second Clinic',
        'clinic_code' => 'SCOPE-TWO', 'timezone' => 'America/New_York',
        'status' => true, 'verification_services_enabled' => true,
    ]);
    $enrollment = $this->clinic->serviceEnrollments()->first()->replicate(['public_id']);
    $enrollment->clinic_id = $other->id;
    $enrollment->location_id = null;
    $enrollment->save();
    $this->manager->verificationClinics()->attach($other->id);
    expect(AdminClinicScope::selectedClinicId())->toBeNull();
    session([AdminClinicScope::SESSION_KEY => $other->id]);
    expect(AdminClinicScope::selectedClinicId())->toBe($other->id);
    $html = view('filament.admin.partials.clinic-scope-switcher', ['clinicOptions' => AdminClinicScope::clinicOptions()])->render();
    expect($html)->toContain('aria-haspopup="listbox"');
});

it('replaces stale selection with the sole authorized clinic and rejects disabled clinics', function () {
    session([AdminClinicScope::SESSION_KEY => 999999]);
    expect(AdminClinicScope::selectedClinicId())->toBe($this->clinic->id);
    $this->clinic->update(['status' => false]);
    expect(AdminClinicScope::selectedClinic())->toBeNull();
});

it('uses the clinic administrators own clinic regardless of a stale session', function () {
    $this->actingAs(User::where('email', ByDesignDentalsSeeder::ADMIN_EMAIL)->firstOrFail());
    session([ClinicPanelScope::SESSION_KEY => 999999]);
    expect(ClinicPanelScope::selectedClinicId())->toBe($this->clinic->id);
    $html = view('filament.clinic.partials.clinic-scope-switcher', ['clinicOptions' => ClinicPanelScope::clinicOptions()])->render();
    expect($html)->toContain('By Design Dentals')->not->toContain('aria-haspopup="listbox"');
    $this->clinic->delete();
    expect(ClinicPanelScope::selectedClinic())->toBeNull();
});

it('automatically selects a sole clinic for a platform administrator in the clinic panel', function () {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    expect(ClinicPanelScope::selectedClinicId())->toBe($this->clinic->id);
});

it('never selects a clinic for an unauthenticated or inactive user', function () {
    $this->manager->update(['status' => false]);
    session([AdminClinicScope::SESSION_KEY => $this->clinic->id]);
    expect(AdminClinicScope::selectedClinicId())->toBeNull();
    expect(ClinicPanelScope::selectedClinicId())->toBeNull();
    auth()->logout();
    expect(AdminClinicScope::clinicOptions())->toBe([]);
    expect(ClinicPanelScope::clinicOptions())->toBe([]);
});
