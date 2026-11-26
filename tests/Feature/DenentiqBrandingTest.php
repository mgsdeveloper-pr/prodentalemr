<?php

use App\Models\SaasSetting;
use Filament\PanelRegistry;

it('renders Denentiq without rewriting stored platform settings', function () {
    $settings = SaasSetting::query()->updateOrCreate(['id' => 1], ['platform_name' => 'ProDental EMR', 'logo_path' => 'branding/legacy.png']);
    expect($settings->brandName())->toBe('Denentiq');
    expect($settings->brandLogoUrl())->toEndWith('/images/brand/denentiq-horizontal-primary.svg');
    $this->get('/login')->assertOk()->assertSee('Denentiq')->assertSee('denentiq-horizontal-primary.svg')->assertSee('favicon.svg');
    expect($settings->fresh()->platform_name)->toBe('ProDental EMR');
    expect($settings->fresh()->logo_path)->toBe('branding/legacy.png');
});

it('uses the supplied brand on all panel shells and has deployable assets', function () {
    foreach (['saas', 'admin', 'clinic', 'dso'] as $id) {
        $panel = app(PanelRegistry::class)->get($id);
        expect($panel->getBrandName())->toBe('Denentiq');
        expect($panel->getFavicon())->toEndWith('/images/brand/favicon.svg');
    }
    foreach (['denentiq-horizontal-primary.svg', 'denentiq-horizontal-white.svg', 'denentiq-icon-primary.svg', 'favicon.svg', 'favicon.ico', 'apple-touch-icon-180x180.png'] as $file) {
        expect(is_file(public_path('images/brand/'.$file)))->toBeTrue();
    }
    expect(view('filament.shared.partials.sidebar-greeting')->render())->toContain('Denentiq home')->toContain('denentiq-icon-primary.svg')->not->toContain('>PD<');
});
