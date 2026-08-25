<?php

use App\Models\AdaProcedureCode;
use App\Models\SaasEntitlementAuditLog;
use App\Models\User;
use App\Filament\Saas\Pages\AdaProcedureCodeImport;
use App\Support\AdaProcedureCodeGovernance;
use App\Support\AdaProcedureCodeImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->saasAdmin = User::factory()->create(['status' => true]);
    $this->saasAdmin->assignRole('saas_admin');
});

it('allows governed single-code updates but blocks hard deletes through policy', function (): void {
    $code = AdaProcedureCode::create([
        'procedure_code' => 'T9001',
        'description' => 'Periodic oral evaluation',
        'class' => 'Diagnostic',
        'is_active' => true,
        'lifecycle_status' => AdaProcedureCode::LIFECYCLE_ACTIVE,
        'source_year' => 2026,
        'source_document' => 'ADA official source',
    ]);

    expect(Gate::forUser($this->saasAdmin)->allows('view', $code))->toBeTrue()
        ->and(Gate::forUser($this->saasAdmin)->allows('create', AdaProcedureCode::class))->toBeTrue()
        ->and(Gate::forUser($this->saasAdmin)->allows('update', $code))->toBeTrue()
        ->and(Gate::forUser($this->saasAdmin)->allows('delete', $code))->toBeFalse();
});

it('imports only new official codes and leaves existing codes unchanged', function (): void {
    Storage::fake('local');

    $existing = AdaProcedureCode::create([
        'procedure_code' => 'T9002',
        'description' => 'Original ADA description',
        'class' => 'Diagnostic',
        'is_active' => true,
        'lifecycle_status' => AdaProcedureCode::LIFECYCLE_ACTIVE,
        'source_year' => 2026,
        'source_document' => 'ADA official source',
    ]);

    Storage::disk('local')->put(
        'imports/ada-cdt/official-additions.csv',
        "Code,Description,Class\nT9002,Changed description should not apply,Diagnostic\nT9003,New official ADA addition,Adjunctive"
    );

    $result = app(AdaProcedureCodeImportService::class)
        ->importFromStoredFile('local', 'imports/ada-cdt/official-additions.csv', 'official-additions.csv');

    expect($result['imported'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and($existing->fresh()->description)->toBe('Original ADA description')
        ->and(AdaProcedureCode::query()->where('procedure_code', 'T9003')->exists())->toBeTrue();
});

it('updates a single code with audit history', function (): void {
    $code = AdaProcedureCode::create([
        'procedure_code' => 'T9004',
        'description' => 'Old description',
        'class' => 'Diagnostic',
        'is_active' => true,
        'lifecycle_status' => AdaProcedureCode::LIFECYCLE_ACTIVE,
        'source_year' => 2026,
        'source_document' => 'ADA official source',
    ]);

    app(AdaProcedureCodeGovernance::class)->update($this->saasAdmin, $code, [
        'description' => 'Updated ADA description',
        'class' => 'Preventive',
        'source_year' => 2027,
        'source_document' => 'ADA CDT 2027 update',
        'source_page' => 12,
        'effective_date' => '2027-01-01',
        'governance_notes' => 'ADA published a correction for this code.',
    ]);

    $code->refresh();

    expect($code->description)->toBe('Updated ADA description')
        ->and($code->class)->toBe('Preventive')
        ->and(SaasEntitlementAuditLog::query()->where('event_type', 'ada_code_updated')->exists())->toBeTrue();
});

it('marks ADA-removed codes inactive without deleting historical records', function (): void {
    $code = AdaProcedureCode::create([
        'procedure_code' => 'T9005',
        'description' => 'Historically used procedure',
        'class' => 'Diagnostic',
        'is_active' => true,
        'lifecycle_status' => AdaProcedureCode::LIFECYCLE_ACTIVE,
        'source_year' => 2026,
        'source_document' => 'ADA official source',
    ]);

    app(AdaProcedureCodeGovernance::class)->retireByAda($this->saasAdmin, $code, [
        'retirement_reason' => 'ADA removed this code from the current CDT publication.',
        'source_year' => 2027,
        'source_document' => 'ADA CDT 2027 update',
        'effective_date' => '2027-01-01',
        'governance_notes' => 'Retired after ADA publication review.',
    ]);

    $code->refresh();

    expect($code->exists)->toBeTrue()
        ->and($code->is_active)->toBeFalse()
        ->and($code->lifecycle_status)->toBe(AdaProcedureCode::LIFECYCLE_REMOVED_BY_ADA)
        ->and(AdaProcedureCode::query()->active()->whereKey($code->id)->exists())->toBeFalse()
        ->and(SaasEntitlementAuditLog::query()->where('event_type', 'ada_code_removed_by_ada')->exists())->toBeTrue();
});

it('supports code management lifecycle filters and audit selection', function (): void {
    $active = AdaProcedureCode::create([
        'procedure_code' => 'T9010',
        'description' => 'Active managed code',
        'class' => 'Diagnostic',
        'is_active' => true,
        'lifecycle_status' => AdaProcedureCode::LIFECYCLE_ACTIVE,
        'source_year' => 2026,
        'source_document' => 'ADA official source',
    ]);

    $removed = AdaProcedureCode::create([
        'procedure_code' => 'T9011',
        'description' => 'Removed managed code',
        'class' => 'Diagnostic',
        'is_active' => false,
        'lifecycle_status' => AdaProcedureCode::LIFECYCLE_REMOVED_BY_ADA,
        'source_year' => 2026,
        'source_document' => 'ADA official source',
    ]);

    SaasEntitlementAuditLog::create([
        'actor_user_id' => $this->saasAdmin->id,
        'event_type' => 'ada_code_updated',
        'entity_type' => AdaProcedureCode::class,
        'entity_id' => $active->id,
        'notes' => 'Code management audit note.',
    ]);

    $page = new AdaProcedureCodeImport();
    $page->codeSearch = 'T901';

    expect($page->getManagedCodes()->pluck('procedure_code')->contains('T9010'))->toBeTrue()
        ->and($page->getManagedCodes()->pluck('procedure_code')->contains('T9011'))->toBeFalse();

    $page->lifecycleFilter = AdaProcedureCode::LIFECYCLE_REMOVED_BY_ADA;

    expect($page->getManagedCodes()->pluck('procedure_code')->all())->toContain('T9011');

    $page->selectAuditCode($active->id);

    expect($page->getSelectedAuditCode()?->id)->toBe($active->id)
        ->and($page->getSelectedCodeAuditEntries()->first()?->notes)->toBe('Code management audit note.');

    $page->clearAuditCode();

    expect($page->selectedAuditCodeId)->toBeNull();
});

it('uses the compact master data workspace without the previous promotional hero', function (): void {
    $page = file_get_contents(resource_path('views/filament/saas/pages/ada-procedure-code-import.blade.php'));
    $pageClass = file_get_contents(app_path('Filament/Saas/Pages/AdaProcedureCodeImport.php'));

    expect($page)
        ->toContain('Code Library')
        ->toContain('View History')
        ->toContain('Import ADA/CDT Codes')
        ->not->toContain('Govern ADA/CDT codes cleanly')
        ->not->toContain('ada-import-hero')
        ->and($pageClass)
        ->toContain("return 'ADA/CDT Codes';")
        ->toContain("->label('Import Codes')");
});
