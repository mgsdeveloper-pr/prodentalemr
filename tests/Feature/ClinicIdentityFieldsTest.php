<?php

use App\Models\Clinic;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('stores clinic tax id encrypted and keeps clinic npi separate from provider identifiers', function (): void {
    $organization = Organization::query()->create([
        'name' => 'Identity Dental Group',
        'owner_name' => 'Identity Owner',
        'email' => 'identity@example.test',
        'status' => true,
    ]);

    $clinic = Clinic::query()->create([
        'organization_id' => $organization->id,
        'clinic_name' => 'Identity Dental Clinic',
        'clinic_code' => 'CLN-IDENTITY',
        'tax_id' => '12-3456789',
        'clinic_npi' => '1234567890',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $rawTaxId = DB::table('clinics')->where('id', $clinic->id)->value('tax_id');

    expect($clinic->fresh())
        ->tax_id->toBe('12-3456789')
        ->clinic_npi->toBe('1234567890')
        ->and($rawTaxId)->not->toBe('12-3456789');
});

it('presents clinic tax id and clinic npi in the saas clinic information', function (): void {
    $form = file_get_contents(app_path('Filament/Saas/Resources/Clinics/Schemas/ClinicForm.php'));
    $infolist = file_get_contents(app_path('Filament/Saas/Resources/Clinics/Schemas/ClinicInfolist.php'));

    expect($form)
        ->toContain("TextInput::make('tax_id')")
        ->toContain("TextInput::make('clinic_npi')")
        ->and($infolist)
        ->toContain("TextEntry::make('tax_id')")
        ->toContain("TextEntry::make('clinic_npi')");
});
