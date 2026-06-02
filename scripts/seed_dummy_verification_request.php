<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$organizationId = 1;
$clinicId = 1;
$locationId = 1;

$patient = App\Models\Patient::query()
    ->where('organization_id', $organizationId)
    ->where('clinic_id', $clinicId)
    ->where('location_id', $locationId)
    ->where('pms_patient_id', 'PMS-DEMO-1001')
    ->first();

$provider = App\Models\Provider::query()
    ->where('organization_id', $organizationId)
    ->where('clinic_id', $clinicId)
    ->where('location_id', $locationId)
    ->whereHas('user', fn ($query) => $query->where('email', 'dr.emma.carter@demo-clinic.test'))
    ->first();

$service = App\Models\ManagedBillingService::query()
    ->where('slug', 'insurance-verification')
    ->first();

$enrollment = App\Models\ClientServiceEnrollment::query()
    ->where('organization_id', $organizationId)
    ->where('clinic_id', $clinicId)
    ->where('location_id', $locationId)
    ->where('managed_billing_service_id', $service?->id)
    ->where('status', 'active')
    ->first();

$requester = App\Models\User::query()->where('email', 'clinic@mgs.com')->first()
    ?? App\Models\User::query()->where('email', 'admin@mgs.com')->first();

$assignee = App\Models\User::query()->where('email', 'admin@mgs.com')->first();

if (! $patient || ! $provider || ! $service || ! $enrollment || ! $requester) {
    fwrite(STDERR, "Missing patient/provider/service/enrollment/requester prerequisites.\n");
    exit(1);
}

$title = 'Insurance Verification - Liam Bennett - 2026-05-05';

$workItem = App\Models\BillingWorkItem::query()
    ->where('organization_id', $organizationId)
    ->where('clinic_id', $clinicId)
    ->where('location_id', $locationId)
    ->where('managed_billing_service_id', $service->id)
    ->where('patient_id', $patient->id)
    ->where('title', $title)
    ->first();

if (! $workItem) {
    $workItem = App\Models\BillingWorkItem::create([
        'organization_id' => $organizationId,
        'clinic_id' => $clinicId,
        'location_id' => $locationId,
        'managed_billing_service_id' => $service->id,
        'client_service_enrollment_id' => $enrollment->id,
        'patient_id' => $patient->id,
        'provider_id' => $provider->id,
        'assigned_to' => $assignee?->id,
        'created_by' => $requester->id,
        'title' => $title,
        'status' => $assignee ? 'assigned' : 'unassigned',
        'outcome_status' => 'pending',
        'priority' => 'normal',
        'source' => 'clinic_request',
        'pms_sync_status' => 'pending',
        'writeback_status' => 'not_requested',
        'due_at' => now()->addDays(3),
        'notes' => 'Dummy verification request created for demo queue validation.',
        'internal_summary' => 'Demo verification request for Liam Bennett at New York location.',
    ]);

    $workItem->verificationProfile()->create([
        'form_type' => 'full_form',
        'requested_by_name' => $requester->name,
        'requested_by_role_slug' => $requester->getPrimaryRoleName(),
        'requested_from_panel' => 'clinic',
        'patient_full_name' => $patient->full_name,
        'patient_dob' => $patient->dob,
        'patient_identifier' => $patient->insurance_number,
        'patient_zip' => '10029',
        'appointment_date' => '2026-05-05',
        'appointment_time' => '10:30 AM',
        'pms_id' => $patient->pms_patient_id,
        'is_pre_registered' => true,
        'insurance_provider_name' => $patient->insurance_provider,
        'verification_notes' => 'Please verify active coverage, preventive benefits, and annual maximum before the scheduled visit.',
        'quick_reference' => 'Demo request for workflow testing.',
    ]);

    $workItem->verificationPlanSnapshots()->create([
        'plan_priority' => 'primary',
        'payer_name' => 'Delta Dental of Kentucky',
        'member_id' => 'U63292952',
        'group_number' => '707940-4001',
        'subscriber_name' => 'Olivia Bennett',
        'subscriber_dob' => '1988-04-19',
        'notes' => 'Primary plan for demo verification request.',
    ]);

    $workItem->notes()->create([
        'user_id' => $requester->id,
        'visibility' => 'internal',
        'body' => 'Clinic submitted this verification request for the upcoming restorative appointment.',
    ]);
}

$workItem->notes()->firstOrCreate(
    [
        'billing_work_item_id' => $workItem->id,
        'body' => 'Clinic submitted this verification request for the upcoming restorative appointment.',
    ],
    [
        'user_id' => $requester->id,
        'visibility' => 'internal',
    ],
);

echo "Dummy verification request ready.\n";
echo "Reference: {$workItem->reference_number}\n";
echo "Title: {$workItem->title}\n";
echo "Status: {$workItem->status}\n";
echo "Assigned to: " . ($workItem->assignedTo?->name ?? 'Unassigned') . "\n";
