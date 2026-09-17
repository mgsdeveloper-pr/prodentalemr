<?php

use App\Filament\Admin\Pages\VerificationGeneralSettings;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Support\AdminClinicScope;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('saas_admin');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $organization = Organization::create([
        'name' => 'Settings Test Group', 'owner_name' => 'Owner',
        'email' => 'settings@example.test', 'phone' => '5551002000', 'status' => true,
    ]);
    $this->clinic = Clinic::create([
        'organization_id' => $organization->id, 'clinic_name' => 'Settings Clinic',
        'clinic_code' => 'SET-A', 'timezone' => 'America/New_York',
        'verification_services_enabled' => true, 'status' => true,
        'allow_verification_manager_template_edits' => false,
    ]);
});

it('shows an empty state and blocks saving without a clinic', function () {
    Livewire::test(VerificationGeneralSettings::class)
        ->assertSee('Select a clinic to view settings')
        ->assertDontSee('Self-Managed')
        ->assertDontSee('Save General Settings')
        ->call('save')->assertForbidden();
});

it('preserves clinic context across interactions and saves its preferences', function () {
    session([AdminClinicScope::SESSION_KEY => $this->clinic->id]);
    Livewire::test(VerificationGeneralSettings::class)
        ->assertSee('Settings Clinic')->assertSee('Settings Test Group')
        ->assertSee('Master Template')
        ->set('data.allow_verification_manager_template_edits', true)
        ->assertSee('Settings Clinic')->assertSee('Settings Test Group')
        ->call('save')->assertHasNoErrors()->assertSee('Settings Clinic');
    expect($this->clinic->fresh()->allow_verification_manager_template_edits)->toBeTrue();
    expect(AdminClinicScope::selectedClinic()->allow_verification_manager_template_edits)->toBeTrue();
});

it('rejects stale form saves after the selected clinic changes', function () {
    $otherClinic = Clinic::create([
        'organization_id' => $this->clinic->organization_id, 'clinic_name' => 'Other Settings Clinic',
        'clinic_code' => 'SET-B', 'timezone' => 'America/New_York',
        'verification_services_enabled' => true, 'status' => true,
        'allow_verification_manager_template_edits' => false,
    ]);
    session([AdminClinicScope::SESSION_KEY => $this->clinic->id]);
    $component = Livewire::test(VerificationGeneralSettings::class)
        ->set('data.allow_verification_manager_template_edits', true);
    session([AdminClinicScope::SESSION_KEY => $otherClinic->id]);
    $component->call('save')->assertForbidden();
    expect($this->clinic->fresh()->allow_verification_manager_template_edits)->toBeFalse();
    expect($otherClinic->fresh()->allow_verification_manager_template_edits)->toBeFalse();
});

it('does not expose a clinic the verifier cannot access', function () {
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_user');
    $this->actingAs($user);
    session([AdminClinicScope::SESSION_KEY => $this->clinic->id]);
    Livewire::test(VerificationGeneralSettings::class)
        ->assertDontSee('Settings Clinic')
        ->call('save')->assertForbidden();
});
