<?php

use App\Filament\Clinic\Pages\VerificationSettings;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationTemplateVersion;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $organization = Organization::create([
        'name' => 'Template Group', 'owner_name' => 'Owner',
        'email' => 'templates@example.test', 'phone' => '5551002000', 'status' => true,
    ]);
    $this->clinic = Clinic::create([
        'organization_id' => $organization->id, 'clinic_name' => 'Template Clinic',
        'clinic_code' => 'TPL-EMPTY', 'status' => true, 'verification_services_enabled' => true,
    ]);
    $user = User::factory()->create([
        'organization_id' => $organization->id, 'clinic_id' => $this->clinic->id, 'status' => true,
    ]);
    $user->assignRole('clinic_admin');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('clinic'));
});

it('opens clinic settings without provisioning templates when masters are unavailable', function ($visibility) {
    if ($visibility !== null) {
        VerificationTemplateVersion::create([
            'scope' => 'master', 'template_key' => 'template_3', 'version_number' => 1,
            'name' => 'Hidden Master', 'status' => 'published', 'is_active' => true,
            'clinic_visibility' => $visibility, 'form_type' => 'both',
        ]);
    }
    $before = VerificationTemplateVersion::count();
    Livewire::test(VerificationSettings::class)
        ->assertSee('No published clinic template is available')
        ->assertSet('data.verification_template_version_id', null)
        ->call('showSettingsSection', 'template-management')->assertHasNoErrors()
        ->assertSee('Import Template');
    expect(VerificationTemplateVersion::count())->toBe($before);
})->with([null, 'hidden']);

it('keeps an existing published clinic template selected without changing it', function () {
    $version = VerificationTemplateVersion::create([
        'scope' => 'clinic', 'clinic_id' => $this->clinic->id,
        'organization_id' => $this->clinic->organization_id,
        'template_key' => 'template_3', 'version_number' => 1, 'name' => 'Approved Clinic Form',
        'status' => 'published', 'is_active' => true, 'clinic_visibility' => 'visible', 'form_type' => 'both',
    ]);
    Livewire::test(VerificationSettings::class)
        ->assertSet('data.verification_template_version_id', $version->id)
        ->assertSee('Approved Clinic Form')
        ->assertDontSee('No published clinic template is available');
    expect(VerificationTemplateVersion::count())->toBe(1)
        ->and($version->fresh()->is_active)->toBeTrue();
});

it('saves the short selection without changing the full selection', function () {
    foreach (['view', 'update'] as $action) {
        auth()->user()->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('clinic.template_publishing.'.$action, 'web'));
    }
    $combined = VerificationTemplateVersion::create([
        'scope' => 'clinic', 'clinic_id' => $this->clinic->id, 'organization_id' => $this->clinic->organization_id,
        'template_key' => 'template_3', 'version_number' => 1, 'name' => 'Combined',
        'status' => 'published', 'is_active' => true, 'clinic_visibility' => 'visible_to_clinics', 'form_type' => 'both',
    ]);
    $short = VerificationTemplateVersion::create([
        'scope' => 'clinic', 'clinic_id' => $this->clinic->id, 'organization_id' => $this->clinic->organization_id,
        'template_key' => 'template_3', 'version_number' => 2, 'name' => 'Short',
        'status' => 'published', 'is_active' => false, 'clinic_visibility' => 'visible_to_clinics', 'form_type' => 'short_form',
    ]);
    $page = Livewire::test(VerificationSettings::class);
    $presetCount = \App\Models\VerificationPdfPreset::count();
    $page
        ->assertSet('data.template_full_form_id', $combined->id)
        ->set('data.verification_pdf_output_mode', 'custom_landscape')
        ->set('data.verification_pdf_output_sections', [])
        ->set('data.template_short_form_id', $short->id)->call('save')->assertHasNoErrors()
        ->assertSet('showActivationReview', true);
    expect($short->fresh()->active_short_form)->toBeFalse()
        ->and($combined->fresh()->active_short_form)->toBeTrue();
    $page->call('confirmActiveForms')->assertHasNoErrors();
    expect($short->fresh()->active_short_form)->toBeTrue()
        ->and($combined->fresh()->active_full_form)->toBeTrue()->and($combined->fresh()->active_short_form)->toBeFalse();
    Livewire::test(VerificationSettings::class)->set('data.template_full_form_id', $short->id)
        ->call('save')->assertHasErrors('data.template_full_form_id');
    expect($combined->fresh()->active_full_form)->toBeTrue();
    expect(\App\Models\VerificationPdfPreset::count())->toBe($presetCount);
    $full = $short->replicate();
    $full->fill(['name' => 'Full', 'form_type' => 'full_form', 'version_number' => 3,
        'is_active' => false, 'active_short_form' => false, 'active_full_form' => false])->save();
    Livewire::test(VerificationSettings::class)
        ->set('data.verification_pdf_output_mode', 'custom_landscape')
        ->set('data.verification_pdf_output_sections', [])
        ->set('data.template_full_form_id', $full->id)->call('save')->assertHasNoErrors()
        ->call('confirmActiveForms')->assertHasNoErrors();
    expect($full->fresh()->active_full_form)->toBeTrue()
        ->and($short->fresh()->active_short_form)->toBeTrue()
        ->and($combined->fresh()->active_full_form)->toBeFalse()
        ->and(\App\Models\VerificationPdfPreset::count())->toBe($presetCount);
});

it('rejects stale activation reviews and rolls back both slots when a selection becomes ineligible', function () {
    foreach (['view', 'update'] as $action) {
        auth()->user()->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('clinic.template_publishing.'.$action, 'web'));
    }
    $original = VerificationTemplateVersion::create([
        'scope' => 'clinic', 'clinic_id' => $this->clinic->id, 'organization_id' => $this->clinic->organization_id,
        'template_key' => 'template_3', 'version_number' => 1, 'name' => 'Original',
        'status' => 'published', 'is_active' => true, 'clinic_visibility' => 'visible_to_clinics', 'form_type' => 'both',
    ]);
    $next = $original->replicate();
    $next->fill(['name' => 'Next', 'version_number' => 2, 'is_active' => false,
        'active_short_form' => false, 'active_full_form' => false])->save();
    $page = Livewire::test(VerificationSettings::class)->set('data.template_full_form_id', $next->id)
        ->call('save')->assertSet('showActivationReview', true);
    VerificationTemplateVersion::whereKey($original->id)->update(['active_short_form' => false]);
    $page->call('confirmActiveForms')->assertHasErrors('activation');
    expect($next->fresh()->active_full_form)->toBeFalse()->and($original->fresh()->active_full_form)->toBeTrue();

    $retired = $next->replicate();
    $retired->fill(['name' => 'Retired', 'version_number' => 3, 'clinic_visibility' => 'retired'])->save();
    $service = app(\App\Support\VerificationTemplateVersionService::class);
    expect(fn () => $service->activateClinicForms($this->clinic,
        ['short_form' => $next->id, 'full_form' => $retired->id], $service->activeClinicForms($this->clinic->id)))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect($next->fresh()->active_short_form)->toBeFalse()->and($original->fresh()->active_full_form)->toBeTrue();
});

it('saves PDF configuration without activating a pending form selection', function () {
    $version = VerificationTemplateVersion::create([
        'scope' => 'clinic', 'clinic_id' => $this->clinic->id, 'organization_id' => $this->clinic->organization_id,
        'template_key' => 'template_3', 'version_number' => 1, 'name' => 'Unselected',
        'status' => 'published', 'is_active' => false, 'clinic_visibility' => 'visible_to_clinics', 'form_type' => 'full_form',
    ]);
    Livewire::test(VerificationSettings::class)->call('showSettingsSection', 'pdf-settings')
        ->set('data.template_full_form_id', $version->id)
        ->set('data.verification_pdf_output_mode', 'standard')->call('save')->assertHasNoErrors();
    expect($version->fresh()->active_full_form)->toBeFalse()
        ->and($this->clinic->fresh()->default_verification_pdf_preset_id)->not->toBeNull();
});

it('rechecks publishing permissions and clinic ownership when confirming activation', function () {
    $permission = \Spatie\Permission\Models\Permission::findOrCreate('clinic.template_publishing.update', 'web');
    auth()->user()->givePermissionTo($permission);
    $version = VerificationTemplateVersion::create([
        'scope' => 'clinic', 'clinic_id' => $this->clinic->id, 'organization_id' => $this->clinic->organization_id,
        'template_key' => 'template_3', 'version_number' => 1, 'name' => 'Permission test',
        'status' => 'published', 'is_active' => false, 'clinic_visibility' => 'visible_to_clinics', 'form_type' => 'full_form',
    ]);
    $page = Livewire::test(VerificationSettings::class)->set('data.template_full_form_id', $version->id)
        ->call('save')->assertSet('showActivationReview', true);
    auth()->user()->revokePermissionTo($permission);
    foreach (auth()->user()->roles as $role) {
        $role->revokePermissionTo($permission);
    }
    $this->actingAs(auth()->user()->fresh());
    $page->call('confirmActiveForms')->assertForbidden();
    expect($version->fresh()->active_full_form)->toBeFalse();

    auth()->user()->givePermissionTo($permission);
    $other = Clinic::create(['organization_id' => $this->clinic->organization_id, 'clinic_name' => 'Other Clinic',
        'clinic_code' => 'OTHER-SCOPE', 'status' => true]);
    $foreign = $version->replicate();
    $foreign->fill(['clinic_id' => $other->id])->save();
    Livewire::test(VerificationSettings::class)->set('data.template_full_form_id', $foreign->id)
        ->call('save')->assertHasErrors('data.template_full_form_id');
    $service = app(\App\Support\VerificationTemplateVersionService::class);
    expect(fn () => $service->activateClinicForms($this->clinic,
        ['full_form' => $foreign->id], $service->activeClinicForms($this->clinic->id)))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect($foreign->fresh()->active_full_form)->toBeFalse();
});

it('accepts both old and renamed settings URLs', function ($section, $expected) {
    Livewire::withQueryParams(['section' => $section])->test(VerificationSettings::class)
        ->assertSet('activeSettingsSection', $expected);
})->with([
    ['active-forms', 'template-selection'], ['template-selection', 'template-selection'],
    ['template-library', 'template-management'], ['template-management', 'template-management'],
]);
