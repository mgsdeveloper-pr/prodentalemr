<?php

use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\User;
use App\Services\Verification\StatusService;
use App\Support\AdminClinicScope;
use App\Support\PanelPermissionMatrix;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::create([
        'name' => 'Role Matrix Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@role-matrix.test',
        'phone' => '5551002000',
        'status' => true,
    ]);

    $this->clinicA = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Role Matrix Clinic A',
        'clinic_code' => 'RM-A',
        'timezone' => 'America/New_York',
        'verification_services_enabled' => true,
        'status' => true,
    ]);

    $this->clinicB = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Role Matrix Clinic B',
        'clinic_code' => 'RM-B',
        'timezone' => 'America/New_York',
        'verification_services_enabled' => true,
        'status' => true,
    ]);

    $this->service = ManagedBillingService::create([
        'name' => 'Role Matrix Verification',
        'slug' => 'role-matrix-verification',
        'category' => 'verification',
        'service_level_agreement_hours' => 24,
        'default_priority' => 'normal',
        'requires_appointment' => false,
        'requires_patient' => false,
        'requires_policy' => false,
        'requires_claim' => false,
        'status' => true,
    ]);

    foreach ([$this->clinicA, $this->clinicB] as $clinic) {
        ClientServiceEnrollment::create([
            'organization_id' => $this->organization->id,
            'clinic_id' => $clinic->id,
            'managed_billing_service_id' => $this->service->id,
            'status' => 'active',
            'start_date' => today(),
        ]);
    }

    $verificationPermissions = collect(['view', 'add', 'update'])
        ->map(fn (string $action) => Permission::findOrCreate(
            PanelPermissionMatrix::permissionName('verification', 'verification', $action),
            'web',
        ));

    $this->specialist = User::factory()->create(['status' => true]);
    $this->specialist->assignRole('verification_user');
    $this->specialist->givePermissionTo($verificationPermissions);
    $this->specialist->verificationClinics()->attach($this->clinicA->id);

    $this->otherSpecialist = User::factory()->create(['status' => true]);
    $this->otherSpecialist->assignRole('verification_user');
    $this->otherSpecialist->givePermissionTo($verificationPermissions);
    $this->otherSpecialist->verificationClinics()->attach($this->clinicA->id);

    $this->manager = User::factory()->create(['status' => true]);
    $this->manager->assignRole('verification_manager');
    $this->manager->givePermissionTo($verificationPermissions);
    $this->manager->verificationClinics()->attach($this->clinicA->id);

    $this->saasAdmin = User::factory()->create(['status' => true]);
    $this->saasAdmin->assignRole('saas_admin');
    $this->saasAdmin->givePermissionTo($verificationPermissions);

    $clinicViewPermission = Permission::findOrCreate(
        PanelPermissionMatrix::permissionName('clinic', 'verification_requests', 'view'),
        'web',
    );

    $this->clinicUser = User::factory()->create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinicA->id,
        'status' => true,
    ]);
    $this->clinicUser->assignRole('staff');
    $this->clinicUser->givePermissionTo($clinicViewPermission);

    $this->clinicAdmin = User::factory()->create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinicA->id,
        'status' => true,
    ]);
    $this->clinicAdmin->assignRole('clinic_admin');
    $this->clinicAdmin->givePermissionTo($clinicViewPermission);

    $makeRequest = fn (Clinic $clinic, User $assignee, string $title): BillingWorkItem => BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $clinic->id,
        'managed_billing_service_id' => $this->service->id,
        'client_service_enrollment_id' => ClientServiceEnrollment::query()
            ->where('clinic_id', $clinic->id)
            ->value('id'),
        'assigned_to' => $assignee->id,
        'title' => $title,
        'status' => BillingWorkItem::STATUS_PENDING,
        'priority' => 'normal',
        'source' => 'clinic_request',
        'processing_mode' => BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE,
    ]);

    $this->ownRequest = $makeRequest($this->clinicA, $this->specialist, 'Assigned to specialist');
    $this->otherRequest = $makeRequest($this->clinicA, $this->otherSpecialist, 'Assigned to another specialist');
    $this->outsideRequest = $makeRequest($this->clinicB, $this->otherSpecialist, 'Outside assigned clinic');
});

it('enforces the verification request boundary for every verification role', function (): void {
    expect($this->specialist->can('view', $this->ownRequest))->toBeTrue()
        ->and($this->specialist->can('view', $this->otherRequest))->toBeFalse()
        ->and($this->specialist->can('view', $this->outsideRequest))->toBeFalse()
        ->and($this->manager->can('view', $this->ownRequest))->toBeTrue()
        ->and($this->manager->can('view', $this->otherRequest))->toBeTrue()
        ->and($this->manager->can('view', $this->outsideRequest))->toBeFalse()
        ->and($this->saasAdmin->can('view', $this->ownRequest))->toBeTrue()
        ->and($this->saasAdmin->can('view', $this->outsideRequest))->toBeTrue();

    $this->actingAs($this->specialist);

    expect(AdminClinicScope::applyVerificationRequests(BillingWorkItem::query())->pluck('id')->all())
        ->toBe([$this->ownRequest->id]);

    expect(VerificationRequestResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([$this->ownRequest->id]);

    $this->get(route('admin.verifications.pdf.download', $this->otherRequest))->assertForbidden();
    $this->get(route('admin.verifications.audit.download', $this->outsideRequest))->assertForbidden();
});

it('keeps clinic users inside their clinic while managers retain only their intended controls', function (): void {
    $this->otherRequest->forceFill(['status' => BillingWorkItem::STATUS_INCOMPLETE])->save();

    expect($this->clinicUser->can('view', $this->ownRequest))->toBeTrue()
        ->and($this->clinicUser->can('view', $this->outsideRequest))->toBeFalse()
        ->and($this->clinicAdmin->can('view', $this->ownRequest))->toBeTrue()
        ->and($this->clinicAdmin->can('view', $this->outsideRequest))->toBeFalse()
        ->and($this->specialist->canManageVerificationQueue())->toBeFalse()
        ->and($this->specialist->canManageVerificationUsers())->toBeFalse()
        ->and($this->manager->canManageVerificationQueue())->toBeTrue()
        ->and($this->manager->canManageVerificationUsers())->toBeTrue()
        ->and($this->manager->canManageVerificationRolePermissions())->toBeFalse()
        ->and(app(StatusService::class)->canShowReopen($this->otherRequest->fresh(), $this->manager))->toBeFalse()
        ->and($this->saasAdmin->canManageVerificationQueue())->toBeTrue()
        ->and($this->saasAdmin->canManageVerificationRolePermissions())->toBeTrue();
});

it('keeps manager-only navigation unavailable to regular verification users', function (): void {
    $this->actingAs($this->specialist)
        ->get('/verification/verifications')
        ->assertOk();

    $this->get('/verification/users')->assertForbidden();
    $this->get('/verification/assign-clinic')->assertForbidden();
    $this->get('/verification/roles-permissions')->assertForbidden();
    $this->get('/verification/verification-settings')->assertForbidden();
});
