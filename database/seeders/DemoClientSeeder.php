<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Dso;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoClientSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            $manager = $this->verificationManager();
            $plan = $this->demoPlan();

            $solo = $this->organization([
                'name' => 'Demo Solo Dental',
                'owner_name' => 'Dr. Sarah Solo',
                'email' => 'solo@example.test',
                'phone' => '555-0101',
                'city' => 'Austin',
                'state' => 'TX',
                'zip_code' => '78701',
                'internal_notes' => 'Demo solo practice for local testing.',
            ], $manager);

            $soloClinic = $this->clinic($solo, [
                'clinic_name' => 'Demo Solo Dental Clinic',
                'clinic_code' => 'DEMO-SOLO-001',
                'managed_services_status' => 'active',
                'service_notes' => 'Managed service demo clinic.',
            ], $manager);
            $this->location($soloClinic, [
                'location_name' => 'Main Office',
                'address' => '100 Solo Way',
                'city' => 'Austin',
                'state' => 'TX',
                'zip_code' => '78701',
                'phone' => '555-0101',
            ]);
            $this->subscription($solo, $soloClinic, $plan, $manager);

            $multi = $this->organization([
                'name' => 'Demo Multi Location Dental Group',
                'owner_name' => 'Mia Patel',
                'email' => 'multi@example.test',
                'phone' => '555-0200',
                'city' => 'Denver',
                'state' => 'CO',
                'zip_code' => '80202',
                'internal_notes' => 'Demo multi-location organization for local testing.',
            ], $manager);

            foreach ([
                ['Demo Multi Downtown', 'DEMO-MULTI-001', '100 Market Street', 'Denver', 'CO', '80202', 'requested'],
                ['Demo Multi Westside', 'DEMO-MULTI-002', '200 West Dental Ave', 'Lakewood', 'CO', '80226', 'not_enabled'],
                ['Demo Multi North', 'DEMO-MULTI-003', '300 North Care Blvd', 'Boulder', 'CO', '80301', 'requested'],
            ] as [$name, $code, $address, $city, $state, $zip, $managedStatus]) {
                $clinic = $this->clinic($multi, [
                    'clinic_name' => $name,
                    'clinic_code' => $code,
                    'managed_services_status' => $managedStatus,
                    'service_notes' => 'Hybrid/self-service demo clinic.',
                ], $manager);
                $this->location($clinic, [
                    'location_name' => $name . ' Location',
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'zip_code' => $zip,
                    'phone' => '555-0200',
                ]);
            }
            $this->subscription($multi, null, $plan, $manager);

            $dso = Dso::query()->updateOrCreate(
                ['account_code' => 'DEMO-DSO-001'],
                [
                    'name' => 'Demo BrightSmile DSO',
                    'legal_name' => 'Demo BrightSmile Dental Support Organization LLC',
                    'primary_contact_name' => 'Jordan Bright',
                    'email' => 'dso@example.test',
                    'phone' => '555-0300',
                    'address' => '500 Enterprise Plaza',
                    'city' => 'Chicago',
                    'state' => 'IL',
                    'zip_code' => '60601',
                    'country' => 'USA',
                    'lifecycle_status' => 'active',
                    'billing_mode' => 'centralized',
                    'service_status' => 'active',
                    'status' => true,
                    'account_manager_user_id' => $manager->id,
                    'internal_notes' => 'Demo DSO account for local testing.',
                ]
            );

            $dsoOrg = $this->organization([
                'dso_id' => $dso->id,
                'name' => 'Demo BrightSmile Dental Group',
                'owner_name' => 'Jordan Bright',
                'email' => 'brightsmile@example.test',
                'phone' => '555-0301',
                'city' => 'Chicago',
                'state' => 'IL',
                'zip_code' => '60601',
                'internal_notes' => 'Demo DSO child organization for local testing.',
            ], $manager);

            foreach ([
                ['Demo BrightSmile Chicago', 'DEMO-DSO-CHI', '600 Lake Street', 'Chicago', 'IL', '60601'],
                ['Demo BrightSmile Naperville', 'DEMO-DSO-NAP', '700 Dental Park', 'Naperville', 'IL', '60540'],
                ['Demo BrightSmile Milwaukee', 'DEMO-DSO-MKE', '800 Care Avenue', 'Milwaukee', 'WI', '53202'],
            ] as [$name, $code, $address, $city, $state, $zip]) {
                $clinic = $this->clinic($dsoOrg, [
                    'clinic_name' => $name,
                    'clinic_code' => $code,
                    'managed_services_status' => 'active',
                    'service_notes' => 'Managed service DSO demo clinic.',
                ], $manager);
                $this->location($clinic, [
                    'location_name' => $name . ' Location',
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'zip_code' => $zip,
                    'phone' => '555-0300',
                ]);
            }
            $this->subscription($dsoOrg, null, $plan, $manager, $dso);

            $manager->verificationClinics()->syncWithoutDetaching(
                Clinic::query()
                    ->whereIn('organization_id', [$solo->id, $multi->id, $dsoOrg->id])
                    ->pluck('id')
                    ->all()
            );
        });
    }

    protected function verificationManager(): User
    {
        Role::findOrCreate('saas_admin', 'web');
        Role::findOrCreate('verification_admin', 'web');
        Role::findOrCreate('verification_manager', 'web');

        $user = User::withTrashed()->updateOrCreate(
            ['email' => 'demo.verify.manager@prodental.test'],
            [
                'name' => 'Demo Verification Manager',
                'phone' => '555-0001',
                'password' => Hash::make('DemoVerify@123'),
                'status' => true,
                'default_workspace' => 'verification',
                'allowed_workspaces' => ['saas', 'verification'],
                'deleted_at' => null,
            ]
        );

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles(['saas_admin', 'verification_admin', 'verification_manager']);

        return $user;
    }

    protected function demoPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::query()->updateOrCreate(
            ['plan_code' => 'DEMO-VERIFY-MANAGED'],
            [
                'name' => 'Demo Verification Managed',
                'price' => 2500,
                'plan_type' => SubscriptionPlan::PLAN_TYPE_VERIFICATION,
                'workspace_mode' => SubscriptionPlan::WORKSPACE_VERIFICATION,
                'max_clinics' => 25,
                'max_users' => 100,
                'included_modules' => SubscriptionPlan::defaultIncludedModules(),
                'included_features' => SubscriptionPlan::defaultIncludedFeatures(),
                'plan_limits' => SubscriptionPlan::defaultPlanLimits(),
                'managed_services_allowed' => true,
                'trial_days' => null,
                'demo_mode_available' => true,
                'status' => true,
            ]
        );
    }

    protected function organization(array $data, User $manager): Organization
    {
        return Organization::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                ...$data,
                'country' => 'USA',
                'status' => true,
                'lifecycle_status' => 'active',
                'onboarding_status' => 'complete',
                'account_manager_user_id' => $manager->id,
            ]
        );
    }

    protected function clinic(Organization $organization, array $data, User $manager): Clinic
    {
        return Clinic::query()->updateOrCreate(
            ['clinic_code' => $data['clinic_code']],
            [
                ...$data,
                'organization_id' => $organization->id,
                'timezone' => 'America/New_York',
                'status' => true,
                'verification_services_enabled' => true,
                'clinic_operations_enabled' => true,
                'service_status' => 'active',
                'pms_service_status' => 'active',
                'verification_service_status' => 'active',
                'demo_mode' => true,
                'account_manager_user_id' => $manager->id,
            ]
        );
    }

    protected function location(Clinic $clinic, array $data): Location
    {
        return Location::query()->updateOrCreate(
            [
                'clinic_id' => $clinic->id,
                'location_name' => $data['location_name'],
            ],
            [
                ...$data,
                'country' => 'USA',
                'status' => true,
            ]
        );
    }

    protected function subscription(Organization $organization, ?Clinic $clinic, SubscriptionPlan $plan, User $manager, ?Dso $dso = null): Subscription
    {
        return Subscription::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'clinic_id' => $clinic?->id,
                'subscription_plan_id' => $plan->id,
            ],
            [
                'dso_id' => $dso?->id,
                'subscription_scope' => $clinic ? 'clinic' : ($dso ? 'dso' : 'organization'),
                'start_date' => now()->toDateString(),
                'status' => 'active',
                'service_status' => 'active',
                'change_type' => 'new',
                'effective_date' => now()->toDateString(),
                'is_demo' => true,
                'account_manager_user_id' => $manager->id,
                'internal_notes' => 'Demo subscription generated by DemoClientSeeder.',
            ]
        );
    }
}
