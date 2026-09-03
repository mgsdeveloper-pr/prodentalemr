<?php

use App\Filament\Saas\Resources\Verifications\Pages\Concerns\InteractsWithVerificationWorkbench;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\User;
use App\Models\VerificationPlanSnapshot;
use App\Models\VerificationProfile;
use App\Support\PanelPermissionMatrix;
use App\Support\WorkContext\Providers\VerificationContextProvider;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('renders the verification request queue tabs', function (): void {
    $html = view('filament.saas.resources.verifications.pages.partials.verification-queue-header')->render();

    expect($html)
        ->toContain('Verification request queues')
        ->toContain('All requests')
        ->toContain('Unassigned')
        ->toContain('In progress')
        ->toContain('Waiting on clinic')
        ->toContain('Ready for audit')
        ->toContain('Overdue')
        ->toContain('verification-queue-kpi is-active');
});

it('keeps the shared request detail workspace clean across verification and clinic panels', function (): void {
    $template = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/view-verification-request.blade.php'
    ));

    expect($template)
        ->toContain('Patient &amp; Insurance Summary')
        ->toContain('Verification Result Summary')
        ->toContain('Eligibility status')
        ->toContain('Annual maximum')
        ->toContain('Preventive')
        ->toContain('Audit Result')
        ->toContain('Completed Form Snapshot')
        ->toContain('PDF Outputs')
        ->toContain('Documents &amp; Notes')
        ->toContain('<details class="request-card request-timeline">')
        ->toContain('Question / Field')
        ->toContain('Previous Answer')
        ->toContain('Current Answer')
        ->toContain('Handled by')
        ->not->toContain('Verification Operations')
        ->not->toContain('delivery-summary')
        ->not->toContain('linear-gradient');

    $clinicPage = file_get_contents(app_path(
        'Filament/Clinic/Resources/VerificationRequests/Pages/ViewVerificationRequest.php'
    ));
    $verificationPage = file_get_contents(app_path(
        'Filament/Saas/Resources/Verifications/Pages/ViewVerificationRequest.php'
    ));

    expect($clinicPage)
        ->toContain("->label('Open Form')")
        ->toContain("->label('Request Correction')")
        ->toContain('requestCorrection(')
        ->and($verificationPage)
        ->toContain("->label('Download PDF')");
});

it('renders the verification work context with existing record data', function (): void {
    $record = new BillingWorkItem([
        'reference_number' => 'BWI-TEST-0001',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'priority' => 'urgent',
        'due_at' => Carbon::parse('2026-08-05 12:00:00'),
    ]);

    $html = view('filament.saas.resources.verifications.pages.partials.work-context-summary', [
        'record' => $record,
        'quickReference' => [
            'patient' => 'Demo Patient',
            'member_id' => '123456',
            'insurance_name' => 'Demo Dental',
        ],
        'copyText' => 'Patient: Demo Patient',
    ])->render();

    expect($html)
        ->toContain('Work Context')
        ->toContain('Demo Patient')
        ->toContain('Demo Dental')
        ->toContain('Copy Context');
});

it('renders pds timeline components for verification activity', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-pds.timeline>
            <x-pds.timeline-item title="Assigned" meta="Aug 05, 2026">Assigned to verifier.</x-pds.timeline-item>
        </x-pds.timeline>
    BLADE);

    expect($html)
        ->toContain('pds-timeline')
        ->toContain('pds-timeline-item')
        ->toContain('Assigned to verifier.');
});

it('renders pds focus mode components for verification work', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-pds.focus-mode-topbar
            title="Verification Form"
            reference="BWI-FOCUS-0001"
            patient="Demo Patient"
            save-status="warning"
            save-label="Unsaved Changes"
        >
            <x-pds.button variant="secondary">Exit Focus Mode</x-pds.button>
        </x-pds.focus-mode-topbar>

        <x-pds.sticky-action-bar>
            <x-pds.button>Save as Draft</x-pds.button>
        </x-pds.sticky-action-bar>
    BLADE);

    expect($html)
        ->toContain('pds-focus-mode-topbar')
        ->toContain('Focus Mode')
        ->toContain('Unsaved Changes')
        ->toContain('Exit Focus Mode')
        ->toContain('pds-sticky-action-bar')
        ->toContain('Save as Draft');
});

it('builds a generic work context from the verification provider', function (): void {
    $record = new BillingWorkItem([
        'reference_number' => 'BWI-CONTEXT-0001',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'priority' => 'urgent',
        'internal_summary' => 'Needs payer confirmation.',
        'due_at' => Carbon::parse('2026-08-05 12:00:00'),
    ]);

    $context = (new VerificationContextProvider(
        record: $record,
        quickReference: [
            'patient' => 'Demo Patient',
            'dob' => '08-05-1990',
            'member_id' => '123456',
            'insurance_name' => 'Demo Dental',
        ],
        attachments: [
            ['title' => 'Insurance Card', 'subtitle' => 'card.pdf', 'download_url' => '/download/card'],
        ],
        timeline: [
            ['type' => 'Opened', 'description' => 'Verification opened.', 'author' => 'Verifier', 'created_at' => 'Aug 05, 2026'],
        ],
        copyText: 'Patient: Demo Patient',
    ))->context();

    expect($context->title)->toBe('Work Context')
        ->and($context->cards())->toHaveCount(9)
        ->and($context->cards()->first()->title)->toBe('Quick Reference');
});

it('renders the generic work context panel without verification-specific renderer logic', function (): void {
    $record = new BillingWorkItem([
        'reference_number' => 'BWI-CONTEXT-0002',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'priority' => 'normal',
    ]);

    $context = (new VerificationContextProvider(
        record: $record,
        quickReference: [
            'patient' => 'Demo Patient',
            'member_id' => '123456',
            'insurance_name' => 'Demo Dental',
        ],
        timeline: [
            ['type' => 'Opened', 'description' => 'Verification opened.', 'author' => 'Verifier', 'created_at' => 'Aug 05, 2026'],
        ],
    ))->context();

    $html = Blade::render(<<<'BLADE'
        <x-pds.work-context-panel :context="$context" />
    BLADE, ['context' => $context]);

    expect($html)
        ->toContain('pds-work-context-panel')
        ->toContain('pds-context-card')
        ->toContain('Quick Reference')
        ->toContain('Demo Patient')
        ->toContain('AI Assistant');
});

it('builds quick reference from live worksheet data before saved fallbacks', function (): void {
    $record = new BillingWorkItem([
        'reference_number' => 'BWI-QUICK-0001',
        'status' => BillingWorkItem::STATUS_IN_PROGRESS,
        'priority' => 'normal',
    ]);
    $record->setRelation('verificationProfile', new VerificationProfile([
        'patient_identifier' => null,
        'subscriber_id' => null,
        'insurance_provider_name' => null,
        'group_number' => null,
    ]));
    $record->setRelation('verificationPlanSnapshots', collect([
        new VerificationPlanSnapshot([
            'plan_priority' => 'primary',
            'member_id' => 'PLAN-123',
            'payer_name' => 'Plan Dental',
            'group_number' => 'PLAN-GRP',
        ]),
    ]));
    $record->setRelation('provider', new Provider([
        'npi_number' => '1790914729',
        'tax_id' => '12-3456789',
    ]));
    $record->setRelation('clinic', new Clinic(['clinic_name' => 'Demo Solo Dental Clinic']));

    $page = new class($record)
    {
        use InteractsWithVerificationWorkbench;

        public array $data = [
            'vf_patient_full_name' => 'Liam Bennett',
            'vf_patient_dob' => '1992-08-14',
            'vf_subscriber_id' => 'U63292952',
            'vf_insurance_provider_name' => 'Delta Dental of Kentucky',
            'vf_group_number' => 'GRP-42',
            'vf_insurance_company_phone_number' => '800-555-0199',
        ];

        public function __construct(private BillingWorkItem $record) {}

        public function getRecord(): BillingWorkItem
        {
            return $this->record;
        }
    };

    expect($page->getQuickReferenceCard())
        ->toMatchArray([
            'patient' => 'Liam Bennett',
            'dob' => '08-14-1992',
            'member_id' => 'U63292952',
            'subscriber_id' => 'U63292952',
            'insurance_name' => 'Delta Dental of Kentucky',
            'group_number' => 'GRP-42',
            'provider_npi' => '1790914729',
            'provider_tax_id' => '12-3456789',
            'clinic_name' => 'Demo Solo Dental Clinic',
            'phone' => '800-555-0199',
        ])
        ->not->toHaveKey('practice_npi');
});

it('renders template three quick reference as a fixed collapsible drawer', function (): void {
    $page = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/edit-verification-request.blade.php'
    ));
    $quickReference = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/partials/template-3-quick-reference-drawer.blade.php'
    ));

    expect($page)
        ->toContain('class="vt3-integrated-header"')
        ->toContain('quickReferenceDrawerOpen: true')
        ->toContain("@include('filament.saas.resources.verifications.pages.partials.template-3-quick-reference-drawer')")
        ->toContain("['Insurance Phone', \$quickReference['phone'] ?? '-']")
        ->toContain("['Location', \$templateThreePracticeContext->get('Location', '-')]")
        ->toContain("'Provider Information' => [")
        ->toContain("['Provider Name', \$quickReference['provider_name'] ?? '-']")
        ->toContain("['NPI', \$quickReference['provider_npi'] ?? '-']")
        ->toContain("['Tax ID / EIN', \$quickReference['provider_tax_id'] ?? '-']")
        ->toContain("['Clinic Name', \$quickReference['clinic_name'] ?? '-']")
        ->toContain('.vt3-reference-drawer {')
        ->toContain('position: fixed;')
        ->toContain('transform: translateX(100%);')
        ->toContain('.vt3-reference-drawer.is-open {')
        ->toContain('overflow-y: auto;');

    expect($quickReference)
        ->toContain('class="vt3-reference-drawer__tab"')
        ->toContain('class="vt3-reference-drawer__body"')
        ->toContain('Quick Reference')
        ->toContain('href="tel:');
});

it('allows template three monetary fields to fill equal responsive columns', function (): void {
    $template = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/partials/verification-form-template-3-content.blade.php'
    ));

    expect($template)
        ->toContain('.uel2-grid--money {')
        ->toContain('grid-template-columns: repeat(2, minmax(0, 1fr));')
        ->toContain('.uel2-input-addon > input {')
        ->toContain('width: 100%;')
        ->toContain('.uel2-input-addon:focus-within {')
        ->toContain('overflow: hidden;')
        ->toContain('border: 1px solid var(--uel2-line);')
        ->toContain('.uel2-input-addon--prefix > span {')
        ->toContain('border-right: 1px solid var(--uel2-line);')
        ->toContain('.uel2-input-addon--suffix > span {')
        ->toContain('border-left: 1px solid var(--uel2-line);')
        ->toContain('@media (max-width: 720px)')
        ->toContain('.uel2-grid--money { grid-template-columns: 1fr; }')
        ->not->toContain('repeat(auto-fit, minmax(220px, 320px))');
});

it('uses a slim identity summary with no embedded quick reference panel', function (): void {
    $template = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/partials/verification-form-template-3-content.blade.php'
    ));
    $page = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/edit-verification-request.blade.php'
    ));
    $managedQuestion = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/partials/template-3-managed-question-row.blade.php'
    ));

    expect($template)
        ->toContain('<div class="uel2-shell__inner">')
        ->toContain('min-height: 36px;')
        ->toContain('padding: 6px 12px;')
        ->toContain('<div class="uel2-layout">')
        ->not->toContain('requiredOnly', 'Audit-required only', 'uel2-form-header', 'Question-driven Response Form');

    expect($page)
        ->toContain('class="vt3-integrated-header"')
        ->toContain('class="vt3-header-context-item">Patient:')
        ->toContain('class="vt3-header-context-item">DOB:')
        ->toContain('class="vt3-header-context-item">Member ID:')
        ->toContain('class="vt3-header-context-item">Insurance:')
        ->toContain('class="vt3-header-context-item">Subscriber:')
        ->toContain('class="vt3-header-context-item">Subscriber DOB:')
        ->toContain('class="vt3-header-context-item">Subscriber ID:')
        ->not->toContain('class="vt3-header-context-item">Reference:')
        ->not->toContain("@include('filament.saas.resources.verifications.pages.partials.template-3-quick-reference-strip')\n            </section>")
        ->not->toContain('<section class="vt3-status-rail" aria-label="Verification request summary">\n                    <span class="vt3-status-chip');

    expect($managedQuestion)->not->toContain('requiredOnly');
});

it('renders template three identity actions and quick reference drawer together', function (): void {
    $template = file_get_contents(resource_path(
        'views/filament/saas/resources/verifications/pages/edit-verification-request.blade.php'
    ));

    expect($template)
        ->toContain('class="vt3-integrated-header"')
        ->toContain('class="vt3-compact-workbar__identity"')
        ->toContain('class="vt3-compact-workbar__context"')
        ->toContain("@include('filament.saas.resources.verifications.pages.partials.template-3-quick-reference-drawer')")
        ->toContain('! $this->focusMode && ! $isTemplateThreeVerificationForm');
});

it('renders the verification queue page with the operational table configuration', function (): void {
    $this->seed(RoleSeeder::class);

    $permission = Permission::findOrCreate(
        PanelPermissionMatrix::permissionName('verification', 'verification', 'view'),
        'web',
    );

    $user = User::factory()->create(['status' => true]);
    $user->assignRole('verification_manager');
    $user->givePermissionTo($permission);

    $this->actingAs($user)
        ->get('/verification/verifications')
        ->assertOk()
        ->assertSee('All requests')
        ->assertSee('Waiting on clinic')
        ->assertSee('Ready for audit')
        ->assertSee('No verification requests found')
        ->assertSee('Patient');
});
