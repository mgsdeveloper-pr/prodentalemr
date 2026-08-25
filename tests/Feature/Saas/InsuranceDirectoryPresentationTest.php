<?php

use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Models\InsuranceCarrier;
use App\Models\User;
use App\Support\InsuranceCarrierImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses one canonical insurance route while preserving previous links', function () {
    expect(route('filament.saas.resources.insurance.index', absolute: false))
        ->toBe('/saas/insurance')
        ->and(route('filament.clinic.resources.insurance.index', absolute: false))
        ->toBe('/clinic/insurance');

    $user = User::factory()->create(['status' => true]);

    $this->actingAs($user)
        ->get('/saas/insurance-carriers')
        ->assertRedirect('/saas/insurance');

    $this->actingAs($user)
        ->get('/clinic/insurance-carriers')
        ->assertRedirect('/clinic/insurance');
});

it('keeps the insurance directory inside the standard page shell', function () {
    $view = file_get_contents(resource_path('views/filament/saas/resources/insurance-carriers/pages/list-insurance-carriers.blade.php'));
    $page = file_get_contents(app_path('Filament/Saas/Resources/InsuranceCarriers/Pages/ListInsuranceCarriers.php'));
    $resource = file_get_contents(app_path('Filament/Saas/Resources/InsuranceCarriers/InsuranceCarrierResource.php'));

    expect($view)
        ->toContain('{{ $this->table }}')
        ->not->toContain('verification-management-shell')
        ->not->toContain('Verification Workspace')
        ->and($page)
        ->toContain("return 'Insurance Directory';")
        ->not->toContain('Verification Questions')
        ->and($resource)
        ->toContain("protected static ?string \$slug = 'insurance';")
        ->toContain('No insurance payers configured')
        ->toContain('Patient policy names are kept separate');
});

it('exposes the insurance import action on the canonical route', function () {
    expect(InsuranceCarrierResource::getUrl('import', panel: 'saas'))
        ->toEndWith('/saas/insurance/import');

    $page = file_get_contents(app_path('Filament/Saas/Resources/InsuranceCarriers/Pages/ListInsuranceCarriers.php'));

    expect($page)
        ->toContain("->label('Import')")
        ->toContain("InsuranceCarrierResource::getUrl('import')");
});

it('previews and imports insurance carriers without duplicating matches', function () {
    $path = tempnam(sys_get_temp_dir(), 'insurance-import-');
    file_put_contents($path, implode("\n", [
        'insurance_name,payer_id,payer_phone,is_active',
        'Aetna Dental,AETNA01,800-555-0101,Yes',
        'MetLife Dental,MET01,800-555-0102,Yes',
    ]));

    $service = app(InsuranceCarrierImportService::class);
    $preview = $service->preview($path, 'insurance.csv');

    expect($preview['created'])->toBe(2)
        ->and(InsuranceCarrier::query()->count())->toBe(0);

    $imported = $service->import($path, 'insurance.csv');

    expect($imported['created'])->toBe(2)
        ->and(InsuranceCarrier::query()->count())->toBe(2);

    file_put_contents($path, implode("\n", [
        'insurance_name,payer_id,payer_phone,is_active',
        'Aetna Dental,AETNA01,800-555-9999,Yes',
    ]));

    $updated = $service->import($path, 'insurance.csv');

    expect($updated['updated'])->toBe(1)
        ->and(InsuranceCarrier::query()->count())->toBe(2)
        ->and(InsuranceCarrier::query()->where('payer_id', 'AETNA01')->value('payer_phone'))->toBe('800-555-9999');

    @unlink($path);
});
