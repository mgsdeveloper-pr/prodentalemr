<?php

namespace Database\Seeders;

use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Support\VerificationTemplateVersionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VerificationWorkflowSmokeSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::query()
            ->where('clinic_name', 'Demo Solo Dental Clinic')
            ->first();

        if (! $clinic) {
            $this->command?->warn('Demo Solo Dental Clinic was not found. No smoke verification request inserted.');

            return;
        }

        $location = Location::query()
            ->where('clinic_id', $clinic->id)
            ->where('location_name', 'Main Office')
            ->first();

        if (! $location) {
            $this->command?->warn('Main Office was not found for Demo Solo Dental Clinic. No smoke verification request inserted.');

            return;
        }

        $admin = User::query()->where('email', 'admin@mgs.com')->first();

        $service = ManagedBillingService::query()->firstOrCreate(
            ['slug' => 'insurance-verification'],
            [
                'name' => 'Insurance Verification',
                'category' => 'verification',
                'description' => 'Eligibility and benefits verification workflow.',
                'service_level_agreement_hours' => 48,
                'default_priority' => 'normal',
                'requires_appointment' => false,
                'requires_patient' => true,
                'requires_policy' => false,
                'requires_claim' => false,
                'status' => true,
            ],
        );

        $enrollment = ClientServiceEnrollment::query()->firstOrCreate(
            [
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
                'location_id' => $location->id,
                'managed_billing_service_id' => $service->id,
            ],
            [
                'created_by' => $admin?->id,
                'status' => 'active',
                'clinic_workspace_enabled' => true,
                'normal_sla_days' => 3,
                'urgent_sla_hours' => 24,
                'start_date' => today(),
                'notes' => 'Local smoke-test enrollment for verification workflow validation.',
            ],
        );

        $providerUser = User::query()->firstOrCreate(
            ['email' => 'dr.emma.carter@demo-clinic.test'],
            [
                'name' => 'Dr. Emma Carter',
                'phone' => '5550102001',
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
                'location_id' => $location->id,
                'created_by' => $admin?->id,
                'status' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $provider = Provider::query()->firstOrCreate(
            ['user_id' => $providerUser->id],
            [
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
                'location_id' => $location->id,
                'specialization' => 'General Dentistry',
                'license_number' => 'LIC-DEMO-SMOKE-1001',
                'npi_number' => '1790914729',
                'tax_id' => '45-2652472',
                'status' => true,
            ],
        );

        $patient = Patient::query()->firstOrCreate(
            [
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
                'location_id' => $location->id,
                'pms_patient_id' => 'PMS-DEMO-1001',
            ],
            [
                'created_by' => $admin?->id,
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

        $workItem = BillingWorkItem::query()->firstOrCreate(
            [
                'patient_id' => $patient->id,
                'title' => 'Smoke Verification - Liam Bennett',
                'source' => 'manual',
            ],
            [
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
                'location_id' => $location->id,
                'managed_billing_service_id' => $service->id,
                'client_service_enrollment_id' => $enrollment->id,
                'provider_id' => $provider->id,
                'assigned_to' => $admin?->id,
                'created_by' => $admin?->id,
                'status' => BillingWorkItem::STATUS_PENDING,
                'outcome_status' => 'pending',
                'priority' => 'normal',
                'pms_sync_status' => 'pending',
                'writeback_status' => 'not_requested',
                'due_at' => $enrollment->calculateDueAt('normal'),
                'notes' => 'Local smoke-test request used to validate verification workflow screens.',
            ],
        );

        app(VerificationTemplateVersionService::class)->attachSnapshotToWorkItem($workItem);

        $this->command?->info("Smoke verification request ready: {$workItem->reference_number} ({$workItem->public_id}).");
    }
}
