<?php

it('renders the enterprise appshell foundation partials', function (): void {
    $globalHeader = view('filament.appshell.global-header', ['workspace' => 'verification'])->render();

    expect($globalHeader)
        ->toContain('pd-appshell-global-header')
        ->toContain('pd-appshell-global-header__brand')
        ->toContain('pd-appshell-workspace-switcher')
        ->toContain('pd-appshell-global-header__search')
        ->toContain('pd-appshell-global-header__utilities')
        ->toContain('pd-appshell-user-menu')
        ->toContain('role="dialog"')
        ->toContain('aria-label="Close global search"')
        ->toContain('@click.self="closeSearch()"')
        ->toContain("event.key === 'Escape'")
        ->toContain("endpoint: '\\/global-search'")
        ->toContain('ProDental')
        ->toContain('Verification')
        ->not->toContain('ProDental EMR');

    expect($globalHeader)->not->toContain('<x-heroicon');

    expect(substr_count($globalHeader, 'pd-appshell-global-header__brand-name'))->toBe(1);
    expect(substr_count($globalHeader, 'pd-appshell-global-header__logo'))->toBe(1);
    expect(substr_count($globalHeader, 'data-appshell-slot="global-search"'))->toBe(1);
    expect(substr_count($globalHeader, 'pd-global-search-backdrop'))->toBeGreaterThanOrEqual(1);
    expect(substr_count($globalHeader, 'pd-appshell-workspace-switcher'))->toBeGreaterThanOrEqual(1);
    expect($globalHeader)->not->toContain('data-appshell-slot="user-profile"');

    expect(view('filament.appshell.workspace-header', ['workspace' => 'verification'])->render())
        ->toContain('pd-appshell-workspace-header')
        ->toContain('Verification Workspace');

    expect(view('filament.appshell.action-toolbar')->render())
        ->toContain('pd-appshell-action-toolbar');

    expect(view('filament.appshell.compact-footer', ['workspace' => 'verification'])->render())
        ->toContain('pd-appshell-footer')
        ->toContain('ProDental')
        ->toContain('&copy; '.now()->year)
        ->not->toContain('Verification')
        ->not->toContain('Development')
        ->not->toContain('Build local')
        ->not->toContain('ProDental v');
});

it('keeps appshell styling available as a shared shell asset', function (): void {
    expect(view('filament.appshell.styles')->render())
        ->toContain('--pd-sidebar-expanded: 260px')
        ->toContain('--pd-sidebar-collapsed: 72px')
        ->toContain('.fi-topbar:has(.pd-appshell-global-header) .fi-topbar-start')
        ->toContain('.fi-topbar:has(.pd-appshell-global-header) .fi-topbar-end')
        ->toContain('pd-appshell-status-pill')
        ->toContain('pwdl-workspace-toolbar');
});

it('keeps the desktop navigation mounted when the page is refreshed', function (): void {
    $toggle = view('filament.shared.partials.sidebar-toggle')->render();
    $theme = view('filament.shared.partials.sidebar-theme')->render();

    expect($toggle)
        ->toContain('$store.sidebar.open()')
        ->toContain("window.Alpine.store('sidebar').open()")
        ->toContain("window.addEventListener('pageshow', applyState)")
        ->toContain("document.addEventListener('livewire:navigated', applyState)");

    expect($toggle)->not->toContain('<x-heroicon');

    expect($theme)
        ->toContain('.fi-main-sidebar[x-cloak]')
        ->toContain('.fi-main-sidebar[x-cloak="-lg"]')
        ->toContain('Render-safe desktop shell');
});

it('keeps clinic scope compact while retaining detailed dropdown labels', function (): void {
    $clinicScope = file_get_contents(resource_path('views/filament/clinic/partials/clinic-scope-switcher.blade.php'));
    $verificationScope = file_get_contents(resource_path('views/filament/admin/partials/clinic-scope-switcher.blade.php'));

    expect($clinicScope)
        ->toContain('selectedClinic()?->clinic_name')
        ->toContain('<span>{{ $activeScopeName }}</span>')
        ->toContain('{{ $clinicLabel }}')
        ->toContain('Active: <strong>{{ $activeScopeName }}</strong>')
        ->not->toContain('Choose one clinic to work inside this panel');

    expect($verificationScope)
        ->toContain('selectedClinic()?->clinic_name')
        ->toContain('<span>{{ $activeScopeLabel }}</span>')
        ->toContain('{{ $clinicLabel }}')
        ->toContain('Active: <strong>{{ $activeScopeLabel }}</strong>')
        ->not->toContain('Choose one clinic or keep');
});
