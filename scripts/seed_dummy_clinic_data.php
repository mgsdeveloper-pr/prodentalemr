<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$clinic = App\Models\Clinic::query()
    ->with('organization')
    ->where('organization_id', 1)
    ->where('clinic_name', 'Meditya Global Services LLC')
    ->first();

if (! $clinic) {
    fwrite(STDERR, "Target demo clinic was not found.\n");
    exit(1);
}

$location = App\Models\Location::query()
    ->where('clinic_id', $clinic->id)
    ->where('location_name', 'New York')
    ->first();

if (! $location) {
    fwrite(STDERR, "Target demo location was not found.\n");
    exit(1);
}

$creator = App\Models\User::query()
    ->where('organization_id', $clinic->organization_id)
    ->where('clinic_id', $clinic->id)
    ->orderBy('id')
    ->first();

$providerUser = App\Models\User::query()->firstOrCreate(
    ['email' => 'dr.emma.carter@demo-clinic.test'],
    [
        'name' => 'Dr. Emma Carter',
        'phone' => '5550102001',
        'organization_id' => $clinic->organization_id,
        'clinic_id' => $clinic->id,
        'location_id' => $location->id,
        'created_by' => $creator?->id,
        'status' => true,
        'password' => Illuminate\Support\Facades\Hash::make('password'),
        'email_verified_at' => now(),
    ],
);

if (! $providerUser->hasRole('doctor')) {
    $providerUser->assignRole('doctor');
}

$provider = App\Models\Provider::query()->firstOrCreate(
    ['user_id' => $providerUser->id],
    [
        'organization_id' => $clinic->organization_id,
        'clinic_id' => $clinic->id,
        'location_id' => $location->id,
        'specialization' => 'General Dentistry',
        'license_number' => 'LIC-NY-DEMO-1001',
        'npi_number' => '1790914729',
        'tax_id' => '45-2652472',
        'status' => true,
    ],
);

$patient = App\Models\Patient::query()->firstOrCreate(
    [
        'organization_id' => $clinic->organization_id,
        'clinic_id' => $clinic->id,
        'location_id' => $location->id,
        'pms_patient_id' => 'PMS-DEMO-1001',
    ],
    [
        'created_by' => $creator?->id ?? $providerUser->id,
        'first_name' => 'Liam',
        'last_name' => 'Bennett',
        'dob' => '1992-08-14',
        'gender' => 'male',
        'phone' => '5550103001',
        'email' => 'liam.bennett@demo-patient.test',
        'address' => '1458 Madison Ave, New York, NY 10029',
        'insurance_provider' => 'Delta Dental of Kentucky',
        'insurance_number' => 'U63292952',
        'guarantor_name' => 'Olivia Bennett',
        'status' => true,
    ],
);

echo "Dummy clinic data ready.\n";
echo "Clinic: {$clinic->clinic_name} (ID {$clinic->id})\n";
echo "Location: {$location->location_name} (ID {$location->id})\n";
echo "Provider user: {$providerUser->name} <{$providerUser->email}> (User ID {$providerUser->id})\n";
echo "Provider: {$provider->specialization} (Provider ID {$provider->id})\n";
echo "Patient: {$patient->full_name} (Patient ID {$patient->id}, PMS ID {$patient->pms_patient_id})\n";
