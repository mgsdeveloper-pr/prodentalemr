<?php

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Support\WorkContext\Providers\OrganizationContextProvider;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

it('builds a generic work context from the organization provider', function (): void {
    $organization = new Organization([
        'name' => 'Demo Dental Group',
        'status' => true,
        'lifecycle_status' => 'active',
    ]);

    $clinic = new Clinic([
        'clinic_name' => 'Downtown Dental',
        'status' => true,
    ]);

    $context = (new OrganizationContextProvider(
        organization: $organization,
        clinic: $clinic,
        summary: [
            'clinic_count' => 3,
            'active_clinic_count' => 2,
            'active_user_count' => 14,
            'clinic_user_count' => 6,
            'verification_document_count' => 27,
            'verification_documents_this_month' => 5,
            'portal_credential_count' => 2,
            'template_question_count' => 18,
            'unread_notification_count' => 1,
        ],
        recentActivity: [
            ['label' => 'Settings', 'value' => 'Updated', 'meta' => 'Aug 05, 2026'],
        ],
        readiness: [
            ['label' => 'Organization Configured', 'status' => 'ready', 'description' => 'Organization is configured.'],
        ],
        links: [
            'users' => ['label' => 'Manage Users', 'url' => '/clinic/users'],
        ],
    ))->context();

    expect($context->title)->toBe('Organization Context')
        ->and($context->cards())->toHaveCount(8)
        ->and($context->cards()->first()->title)->toBe('Organization Summary')
        ->and($context->cards()->first()->items[0]['value'])->toBe('Demo Dental Group');
});

it('renders the organization work context through the shared pds panel', function (): void {
    $context = (new OrganizationContextProvider(
        organization: new Organization(['name' => 'Demo Dental Group', 'status' => true]),
        clinic: new Clinic(['clinic_name' => 'Downtown Dental', 'status' => true]),
        summary: [
            'clinic_count' => 1,
            'active_clinic_count' => 1,
            'active_user_count' => 4,
            'clinic_user_count' => 4,
            'verification_document_count' => 9,
        ],
    ))->context();

    $html = Blade::render(<<<'BLADE'
        <x-pds.work-context-panel :context="$context" />
    BLADE, ['context' => $context]);

    expect($html)
        ->toContain('pds-work-context-panel')
        ->toContain('Organization Summary')
        ->toContain('Demo Dental Group')
        ->toContain('Clinics')
        ->toContain('Future Workspace Intelligence');
});

it('renders the reusable workspace readiness card', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-pds.workspace-readiness-card
            title="Operational Readiness"
            :items="[
                ['label' => 'Organization Configured', 'status' => 'ready', 'description' => 'Organization record exists.'],
                ['label' => 'Portal Credentials', 'status' => 'attention', 'description' => 'Credentials are missing.'],
            ]"
        />
    BLADE);

    expect($html)
        ->toContain('pds-workspace-readiness-card')
        ->toContain('Operational Readiness')
        ->toContain('1/2 Ready')
        ->toContain('Portal Credentials');
});

it('renders the clinic dashboard as the organization workspace for an authorized clinic admin', function (): void {
    $this->seed(RoleSeeder::class);

    $organization = Organization::create([
        'name' => 'Bright Dental Group',
        'owner_name' => 'Clinic Owner',
        'email' => 'owner@example.com',
        'phone' => '5551001000',
        'status' => true,
    ]);

    $clinic = Clinic::create([
        'organization_id' => $organization->id,
        'clinic_name' => 'Bright Dental Downtown',
        'clinic_code' => 'CLN-ORG',
        'timezone' => 'America/New_York',
        'status' => true,
        'verification_services_enabled' => true,
        'clinic_operations_enabled' => false,
        'service_status' => 'active',
        'pms_service_status' => 'inactive',
        'verification_service_status' => 'active',
    ]);

    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'clinic_id' => $clinic->id,
        'status' => true,
    ]);

    $user->assignRole('clinic_admin');

    $this
        ->actingAs($user)
        ->get('/clinic/organization-operations')
        ->assertOk()
        ->assertSee('Bright Dental Group')
        ->assertSee('Bright Dental Downtown')
        ->assertSee('Organization Context')
        ->assertSee('Verification Readiness');
});
