<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyClinicDataSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::query()
            ->with('organization')
            ->where('organization_id', 1)
            ->where('clinic_name', 'Meditya Global Services LLC')
            ->first();

        if (! $clinic) {
            $this->command?->warn('Target demo clinic was not found. No dummy data inserted.');

            return;
        }

        $location = Location::query()
            ->where('clinic_id', $clinic->id)
            ->where('location_name', 'New York')
            ->first();

        if (! $location) {
            $this->command?->warn('Target demo location was not found. No dummy data inserted.');

            return;
        }

        $creator = User::query()
            ->where('organization_id', $clinic->organization_id)
            ->where('clinic_id', $clinic->id)
            ->orderBy('id')
            ->first();

        $providerUser = User::query()->firstOrCreate(
            ['email' => 'dr.emma.carter@demo-clinic.test'],
            [
                'name' => 'Dr. Emma Carter',
                'phone' => '5550102001',
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
                'location_id' => $location->id,
                'created_by' => $creator?->id,
                'status' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        if (! $providerUser->hasRole('doctor')) {
            $providerUser->assignRole('doctor');
        }

        Provider::query()->firstOrCreate(
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

        Patient::query()->firstOrCreate(
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

        $this->command?->info('Dummy provider and patient added to Meditya Global Services LLC / New York.');
    }
}
