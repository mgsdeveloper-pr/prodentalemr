<?php

use App\Filament\Saas\Resources\Providers\ProviderResource;
use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemAttachment;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\PortalCredential;
use App\Models\Provider;
use App\Models\SaasEntitlementAuditLog;
use App\Models\User;
use App\Support\AdminClinicScope;
use App\Support\ClinicTemplateSettingsSupport;
use App\Support\SaasSupportAccess;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->saasAdmin = User::factory()->create(['status' => true]);
    $this->saasAdmin->assignRole('saas_admin');

    $this->organization = Organization::create([
        'name' => 'Support Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@support.test',
        'phone' => '5551000000',
        'status' => true,
    ]);

    $this->clinic = Clinic::create([
        'organization_id' => $this->organization->id,
        'clinic_name' => 'Support Downtown',
        'clinic_code' => 'SUP-DTN',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);
});

it('starts and ends an audited saas support access session', function (): void {
    $this->actingAs($this->saasAdmin);

    $context = SaasSupportAccess::start(
        $this->saasAdmin,
        $this->organization,
        $this->clinic,
        'Assist clinic with provider setup.'
    );

    expect($context['organization_id'])->toBe($this->organization->id)
        ->and(SaasSupportAccess::activeOrganizationId())->toBe($this->organization->id)
        ->and(SaasSupportAccess::activeClinicId())->toBe($this->clinic->id)
        ->and(SaasEntitlementAuditLog::query()->where('event_type', 'support_access_started')->exists())->toBeTrue();

    SaasSupportAccess::end($this->saasAdmin);

    expect(SaasSupportAccess::active())->toBeNull()
        ->and(SaasEntitlementAuditLog::query()->where('event_type', 'support_access_ended')->exists())->toBeTrue();
});

it('scopes provider support records to the active support organization', function (): void {
    $otherOrganization = Organization::create([
        'name' => 'Other Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@other.test',
        'phone' => '5552000000',
        'status' => true,
    ]);

    $otherClinic = Clinic::create([
        'organization_id' => $otherOrganization->id,
        'clinic_name' => 'Other Downtown',
        'clinic_code' => 'OTH-DTN',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $location = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Support Main',
        'address' => '100 Main',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10001',
        'country' => 'USA',
        'status' => true,
    ]);

    $otherLocation = Location::create([
        'clinic_id' => $otherClinic->id,
        'location_name' => 'Other Main',
        'address' => '200 Main',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10002',
        'country' => 'USA',
        'status' => true,
    ]);

    $supportUser = User::factory()->create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $location->id,
        'status' => true,
    ]);

    $otherUser = User::factory()->create([
        'organization_id' => $otherOrganization->id,
        'clinic_id' => $otherClinic->id,
        'location_id' => $otherLocation->id,
        'status' => true,
    ]);

    $supportProvider = Provider::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $location->id,
        'user_id' => $supportUser->id,
        'specialization' => 'General Dentistry',
        'status' => true,
    ]);

    Provider::create([
        'organization_id' => $otherOrganization->id,
        'clinic_id' => $otherClinic->id,
        'location_id' => $otherLocation->id,
        'user_id' => $otherUser->id,
        'specialization' => 'Orthodontics',
        'status' => true,
    ]);

    $this->actingAs($this->saasAdmin);

    SaasSupportAccess::start(
        $this->saasAdmin,
        $this->organization,
        null,
        'Review provider records inside client boundary.'
    );

    expect(ProviderResource::getEloquentQuery()->pluck('id')->all())->toBe([$supportProvider->id]);
});

it('keeps provider support visibility read only from the saas workspace', function (): void {
    $otherOrganization = Organization::create([
        'name' => 'Wrong Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@wrong.test',
        'phone' => '5553000000',
        'status' => true,
    ]);

    $otherClinic = Clinic::create([
        'organization_id' => $otherOrganization->id,
        'clinic_name' => 'Wrong Downtown',
        'clinic_code' => 'WRG-DTN',
        'timezone' => 'America/New_York',
        'status' => true,
    ]);

    $location = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Support Main',
        'address' => '100 Main',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10001',
        'country' => 'USA',
        'status' => true,
    ]);

    $otherLocation = Location::create([
        'clinic_id' => $otherClinic->id,
        'location_name' => 'Wrong Main',
        'address' => '300 Main',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10003',
        'country' => 'USA',
        'status' => true,
    ]);

    $providerUser = User::factory()->create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $location->id,
        'status' => true,
    ]);

    $provider = Provider::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $location->id,
        'user_id' => $providerUser->id,
        'specialization' => 'General Dentistry',
        'status' => true,
    ]);

    $otherUser = User::factory()->create([
        'organization_id' => $otherOrganization->id,
        'clinic_id' => $otherClinic->id,
        'location_id' => $otherLocation->id,
        'status' => true,
    ]);

    $otherProvider = Provider::create([
        'organization_id' => $otherOrganization->id,
        'clinic_id' => $otherClinic->id,
        'location_id' => $otherLocation->id,
        'user_id' => $otherUser->id,
        'specialization' => 'Endodontics',
        'status' => true,
    ]);

    $this->actingAs($this->saasAdmin);

    expect(ProviderResource::canCreate())->toBeFalse()
        ->and(ProviderResource::canEdit($provider))->toBeFalse()
        ->and(ProviderResource::canDelete($provider))->toBeFalse();

    SaasSupportAccess::start(
        $this->saasAdmin,
        $this->organization,
        $this->clinic,
        'Update provider credential data for support.'
    );

    expect(ProviderResource::canCreate())->toBeFalse()
        ->and(ProviderResource::canEdit($provider))->toBeFalse()
        ->and(ProviderResource::canDelete($provider))->toBeFalse()
        ->and(ProviderResource::canEdit($otherProvider))->toBeFalse()
        ->and(ProviderResource::canDelete($otherProvider))->toBeFalse();
});

it('audits provider support changes with before and after details', function (): void {
    $location = Location::create([
        'clinic_id' => $this->clinic->id,
        'location_name' => 'Support Main',
        'address' => '100 Main',
        'city' => 'New York',
        'state' => 'NY',
        'zip_code' => '10001',
        'country' => 'USA',
        'status' => true,
    ]);

    $providerUser = User::factory()->create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $location->id,
        'status' => true,
    ]);

    $provider = Provider::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'location_id' => $location->id,
        'user_id' => $providerUser->id,
        'specialization' => 'General Dentistry',
        'npi_number' => '1111111111',
        'status' => true,
    ]);

    $this->actingAs($this->saasAdmin);

    $provider->update(['npi_number' => '2222222222']);

    expect(SaasEntitlementAuditLog::query()->where('event_type', 'support_provider_updated')->exists())->toBeFalse();

    SaasSupportAccess::start(
        $this->saasAdmin,
        $this->organization,
        $this->clinic,
        'Correct provider NPI during support.'
    );

    $provider->update(['npi_number' => '3333333333']);

    $updateLog = SaasEntitlementAuditLog::query()
        ->where('event_type', 'support_provider_updated')
        ->latest('id')
        ->first();

    expect($updateLog)->not->toBeNull()
        ->and($updateLog->entity_type)->toBe(Provider::class)
        ->and($updateLog->entity_id)->toBe($provider->id)
        ->and(data_get($updateLog->before_values, 'record.npi_number'))->toBe('2222222222')
        ->and(data_get($updateLog->after_values, 'record.npi_number'))->toBe('3333333333')
        ->and(data_get($updateLog->after_values, 'support_reason'))->toBe('Correct provider NPI during support.');

    $provider->delete();
    $provider->restore();

    expect(SaasEntitlementAuditLog::query()->where('event_type', 'support_provider_deleted')->exists())->toBeTrue()
        ->and(SaasEntitlementAuditLog::query()->where('event_type', 'support_provider_restored')->exists())->toBeTrue();
});

it('requires matching support mode before portal credential support writes are allowed', function (): void {
    $credential = PortalCredential::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'portal_name' => 'Delta Dental',
        'portal_category' => 'insurance',
        'username' => 'delta-user',
        'password' => 'SecretPass123',
        'is_active' => true,
    ]);

    $this->actingAs($this->saasAdmin);
    session([AdminClinicScope::SESSION_KEY => $this->clinic->id]);

    expect(PortalCredentialResource::canCreate())->toBeFalse()
        ->and(PortalCredentialResource::canEdit($credential))->toBeFalse()
        ->and(PortalCredentialResource::canDelete($credential))->toBeFalse();

    SaasSupportAccess::start(
        $this->saasAdmin,
        $this->organization,
        $this->clinic,
        'Assist with payer portal credential setup.'
    );

    expect(PortalCredentialResource::canCreate())->toBeTrue()
        ->and(PortalCredentialResource::canEdit($credential))->toBeTrue()
        ->and(PortalCredentialResource::canDelete($credential))->toBeTrue();
});

it('audits portal credential support changes without exposing secrets', function (): void {
    $credential = PortalCredential::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'portal_name' => 'Delta Dental',
        'portal_category' => 'insurance',
        'username' => 'delta-user',
        'password' => 'SecretPass123',
        'account_reference' => 'payer-123',
        'is_active' => true,
    ]);

    $this->actingAs($this->saasAdmin);
    session([AdminClinicScope::SESSION_KEY => $this->clinic->id]);

    $credential->update(['username' => 'outside-support']);

    expect(SaasEntitlementAuditLog::query()->where('event_type', 'support_portal_credential_updated')->exists())->toBeFalse();

    SaasSupportAccess::start(
        $this->saasAdmin,
        $this->organization,
        $this->clinic,
        'Rotate payer portal access.'
    );

    $credential->update([
        'username' => 'inside-support',
        'password' => 'NewSecretPass123',
        'account_reference' => 'payer-456',
        'mfa_required' => true,
    ]);

    $updateLog = SaasEntitlementAuditLog::query()
        ->where('event_type', 'support_portal_credential_updated')
        ->latest('id')
        ->first();

    expect($updateLog)->not->toBeNull()
        ->and($updateLog->entity_type)->toBe(PortalCredential::class)
        ->and(data_get($updateLog->before_values, 'record.username'))->toBe('[changed]')
        ->and(data_get($updateLog->after_values, 'record.username'))->toBe('[changed]')
        ->and(data_get($updateLog->after_values, 'record.password'))->toBe('[changed]')
        ->and(data_get($updateLog->after_values, 'record.account_reference'))->toBe('[changed]')
        ->and(data_get($updateLog->after_values, 'record.mfa_required'))->toBeTrue()
        ->and(json_encode($updateLog->before_values))->not->toContain('outside-support')
        ->and(json_encode($updateLog->after_values))->not->toContain('inside-support')
        ->and(json_encode($updateLog->after_values))->not->toContain('NewSecretPass123')
        ->and(data_get($updateLog->after_values, 'support_reason'))->toBe('Rotate payer portal access.');
});

it('requires support mode and audits metadata for saas document downloads', function (): void {
    Storage::fake('local');

    $service = ManagedBillingService::create([
        'name' => 'Eligibility Verification',
        'slug' => 'eligibility-verification-support-test',
        'category' => 'verification',
        'service_level_agreement_hours' => 24,
        'default_priority' => 'high',
        'requires_appointment' => false,
        'requires_patient' => false,
        'requires_policy' => false,
        'requires_claim' => false,
        'status' => true,
    ]);

    $enrollment = ClientServiceEnrollment::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $service->id,
        'created_by' => $this->saasAdmin->id,
        'status' => 'active',
        'start_date' => today(),
    ]);

    $workItem = BillingWorkItem::create([
        'organization_id' => $this->organization->id,
        'clinic_id' => $this->clinic->id,
        'managed_billing_service_id' => $service->id,
        'client_service_enrollment_id' => $enrollment->id,
        'title' => 'Sensitive verification proof',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'priority' => 'high',
        'source' => 'clinic_request',
    ]);

    Storage::disk('local')->put('billing-work-items/proof.pdf', 'sensitive document bytes');

    $attachment = BillingWorkItemAttachment::create([
        'billing_work_item_id' => $workItem->id,
        'user_id' => $this->saasAdmin->id,
        'title' => 'Insurance proof',
        'file_path' => 'billing-work-items/proof.pdf',
        'original_file_name' => 'proof.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 24,
    ]);

    $this->actingAs($this->saasAdmin);

    $this->get(route('saas.verification-request-attachments.download', $attachment))
        ->assertForbidden();

    SaasSupportAccess::start(
        $this->saasAdmin,
        $this->organization,
        $this->clinic,
        'Review verification proof for support.'
    );

    $this->get(route('saas.verification-request-attachments.download', $attachment))
        ->assertOk();

    $downloadLog = SaasEntitlementAuditLog::query()
        ->where('event_type', 'support_document_downloaded')
        ->latest('id')
        ->first();

    expect($downloadLog)->not->toBeNull()
        ->and($downloadLog->entity_type)->toBe(BillingWorkItemAttachment::class)
        ->and($downloadLog->entity_id)->toBe($attachment->id)
        ->and(data_get($downloadLog->after_values, 'document.original_file_name'))->toBe('proof.pdf')
        ->and(data_get($downloadLog->after_values, 'support_reason'))->toBe('Review verification proof for support.')
        ->and(json_encode($downloadLog->after_values))->not->toContain('billing-work-items/proof.pdf')
        ->and(json_encode($downloadLog->after_values))->not->toContain('sensitive document bytes');
});

it('requires support mode before saas changes clinic template settings', function (): void {
    expect(fn () => ClinicTemplateSettingsSupport::assertCanChange($this->clinic, [
        'clinic_name' => 'Support Downtown Updated',
    ]))->not->toThrow(AuthorizationException::class);

    expect(fn () => ClinicTemplateSettingsSupport::assertCanChange($this->clinic, [
        'allow_verification_manager_template_edits' => true,
    ]))->toThrow(AuthorizationException::class);

    $this->actingAs($this->saasAdmin);

    SaasSupportAccess::start(
        $this->saasAdmin,
        $this->organization,
        $this->clinic,
        'Enable clinic template governance during support.'
    );

    expect(fn () => ClinicTemplateSettingsSupport::assertCanChange($this->clinic, [
        'allow_verification_manager_template_edits' => true,
    ]))->not->toThrow(AuthorizationException::class);
});

it('audits clinic template setting changes made during support mode', function (): void {
    $this->actingAs($this->saasAdmin);

    $this->clinic->update(['allow_verification_manager_template_edits' => false]);

    expect(SaasEntitlementAuditLog::query()->where('event_type', 'support_clinic_template_settings_updated')->exists())
        ->toBeFalse();

    SaasSupportAccess::start(
        $this->saasAdmin,
        $this->organization,
        $this->clinic,
        'Allow verification managers to maintain clinic template drafts.'
    );

    $this->clinic->update(['allow_verification_manager_template_edits' => true]);

    $updateLog = SaasEntitlementAuditLog::query()
        ->where('event_type', 'support_clinic_template_settings_updated')
        ->latest('id')
        ->first();

    expect($updateLog)->not->toBeNull()
        ->and($updateLog->entity_type)->toBe(Clinic::class)
        ->and($updateLog->entity_id)->toBe($this->clinic->id)
        ->and(data_get($updateLog->before_values, 'record.allow_verification_manager_template_edits'))->toBeFalse()
        ->and(data_get($updateLog->after_values, 'record.allow_verification_manager_template_edits'))->toBeTrue()
        ->and(data_get($updateLog->after_values, 'support_reason'))->toBe('Allow verification managers to maintain clinic template drafts.');
});
