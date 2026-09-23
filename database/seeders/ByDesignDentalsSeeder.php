<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\ManagedBillingService;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class ByDesignDentalsSeeder extends Seeder
{
    public const CODE = 'BY-DESIGN-DENTALS-KOP';
    public const ADMIN_EMAIL = 'developer@medityaglobalservices.com';

    public function run(): void
    {
        DB::transaction(function (): void {
            $clinic = Clinic::withTrashed()->where('clinic_code', self::CODE)->first();
            $admin = User::withTrashed()->where('email', self::ADMIN_EMAIL)->first();
            if ($clinic) {
                if ($clinic->trashed() || ! $admin || $admin->trashed()
                    || (int) $admin->clinic_id !== (int) $clinic->id
                    || ! Provider::where('clinic_id', $clinic->id)->where('npi_number', '1689402257')->exists()) {
                    throw new RuntimeException('Existing By Design Dentals records need manual reconciliation. No changes made.');
                }

                $this->ensureEnrollment($clinic);

                return; // Never reset credentials or overwrite an established client on rerun.
            }

            $providerEmail = 'by-design-dentals-toral-patel@provider.invalid';
            if ($admin || User::withTrashed()->where('email', $providerEmail)->exists()
                || Organization::withTrashed()->where('name', 'By Design Dentals')->exists()
                || Clinic::withTrashed()->where('clinic_name', 'By Design Dentals')->exists()) {
                throw new RuntimeException('A matching client or user already exists. Reconcile it before provisioning; existing users will not be reassigned.');
            }
            $taxId = (string) config('client_provisioning.by_design_dentals_tax_id');
            if (! preg_match('/^\d{9}$/', $taxId)) {
                throw new RuntimeException('Set BY_DESIGN_DENTALS_TAX_ID securely to the supplied nine-digit tax ID before running this migration.');
            }
            $role = Role::findByName('clinic_admin', 'web');
            $address = [
                'address' => '234 Mall Blvd, Suite 180', 'city' => 'King of Prussia',
                'state' => 'PA', 'zip_code' => '19406', 'country' => 'USA',
                'phone' => '(484)232-1177',
            ];
            $organization = Organization::create([
                ...$address, 'name' => 'By Design Dentals', 'owner_name' => 'Not provided',
                'email' => self::ADMIN_EMAIL, 'status' => true,
                'lifecycle_status' => 'active', 'onboarding_status' => 'in_progress',
                'internal_notes' => 'Hybrid verification: clinic and internal verification teams both process requests. Legal owner and commercial terms pending confirmation.',
            ]);
            $clinic = Clinic::create([
                ...$address, 'organization_id' => $organization->id,
                'clinic_name' => 'By Design Dentals', 'clinic_code' => self::CODE,
                'email' => self::ADMIN_EMAIL, 'tax_id' => $taxId,
                'timezone' => 'America/New_York', 'status' => true,
                'verification_services_enabled' => true, 'verification_service_status' => 'active',
                'clinic_operations_enabled' => true, 'pms_service_status' => 'active',
                'service_status' => 'active', 'managed_services_status' => 'active',
                'verification_assignment_method' => 'unassigned',
                'verification_pdf_output_mode' => 'custom_landscape',
                'service_notes' => 'Hybrid: both clinic staff and assigned internal verifiers handle verification. One assignee per request. Pricing not agreed; no paid subscription created.',
                'demo_mode' => false,
            ]);
            $location = Location::create([
                ...$address, 'clinic_id' => $clinic->id, 'location_name' => 'Main Office', 'status' => true,
            ]);
            $clinic->update(['default_location_id' => $location->id]);
            $scope = ['organization_id' => $organization->id, 'clinic_id' => $clinic->id, 'location_id' => $location->id];
            $admin = User::create([
                ...$scope, 'name' => 'By Design Dentals Admin', 'email' => self::ADMIN_EMAIL,
                'phone' => $address['phone'], 'password' => Str::random(64),
                'status' => true, 'allowed_workspaces' => ['clinic'], 'default_workspace' => 'clinic',
            ]);
            $admin->assignRole($role);

            // Providers require a user identity; this placeholder cannot sign in or receive mail.
            $providerUser = User::create([
                ...$scope, 'name' => 'Dr. Toral Patel', 'email' => $providerEmail,
                'password' => Str::random(64), 'status' => false, 'allowed_workspaces' => [],
            ]);
            Provider::create([
                ...$scope, 'user_id' => $providerUser->id, 'license_number' => 'DS044820',
                'license_state' => 'PA', 'npi_number' => '1689402257', 'status' => true,
            ]);
            $this->ensureEnrollment($clinic);
        });
    }

    private function ensureEnrollment(Clinic $clinic): void
    {
        if (ClientServiceEnrollment::withTrashed()->where('clinic_id', $clinic->id)->exists()) {
            return;
        }
        $service = ManagedBillingService::where('category', 'verification')->where('status', true)->orderBy('id')->first();
        if (! $service) {
            $service = ManagedBillingService::firstOrCreate(['slug' => 'insurance-verification'], [
                'name' => 'Insurance Verification', 'category' => 'verification',
                'service_level_agreement_hours' => 48, 'default_priority' => 'normal',
                'requires_appointment' => false, 'requires_patient' => true,
                'requires_policy' => false, 'requires_claim' => false, 'status' => true,
            ]);
        }
        if (! $service->status || $service->category !== 'verification') {
            throw new RuntimeException('An active verification service is required for Hybrid onboarding.');
        }
        ClientServiceEnrollment::create([
            'organization_id' => $clinic->organization_id, 'clinic_id' => $clinic->id,
            'location_id' => $clinic->default_location_id, 'managed_billing_service_id' => $service->id,
            'status' => 'active', 'clinic_workspace_enabled' => true, 'start_date' => today(),
            'normal_sla_days' => 3, 'urgent_sla_hours' => 24,
            'notes' => 'Hybrid: clinic and internal verification teams. Existing default response targets apply pending agreement. No commercial pricing configured.',
        ]);
    }
}
