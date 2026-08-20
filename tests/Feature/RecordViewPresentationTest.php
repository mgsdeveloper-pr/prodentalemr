<?php

use Illuminate\Http\Request;

it('keeps the shared record view presentation available across panels', function (): void {
    $trait = file_get_contents(app_path('Filament/Saas/Resources/Pages/Concerns/HasCleanViewPageLabels.php'));
    $styles = file_get_contents(resource_path('views/filament/appshell/styles.blade.php'));

    expect($trait)
        ->toContain("'pd-standard-record-view'")
        ->and($styles)
        ->toContain('.pd-standard-record-view .fi-header')
        ->toContain('.pd-standard-record-view .fi-section')
        ->toContain('.pd-standard-record-view .fi-in-entry-wrp-label');
});

it('registers the client hierarchy detail routes', function (): void {
    $router = app('router');

    foreach ([
        '/saas/dsos/1' => 'saas/dsos/{record}',
        '/saas/organizations/1' => 'saas/organizations/{record}',
        '/saas/clinics/1' => 'saas/clinics/{record}',
        '/saas/locations/1' => 'saas/locations/{record}',
    ] as $uri => $routePattern) {
        expect($router->getRoutes()->match(Request::create($uri, 'GET'))->uri())
            ->toBe($routePattern);
    }
});

it('keeps record actions and service terminology user-facing', function (): void {
    $organizationPage = file_get_contents(app_path('Filament/Saas/Resources/Organizations/Pages/ViewOrganization.php'));
    $enrollmentView = file_get_contents(app_path('Filament/Saas/Resources/ClientServiceEnrollments/Schemas/ClientServiceEnrollmentInfolist.php'));
    $serviceView = file_get_contents(app_path('Filament/Saas/Resources/ManagedBillingServices/Schemas/ManagedBillingServiceInfolist.php'));

    expect($organizationPage)
        ->toContain("->label('Open Workspace')")
        ->and($enrollmentView)
        ->toContain("->label('Verification requests')")
        ->not->toContain("->label('Work items')")
        ->and($serviceView)
        ->toContain("->label('Service requests')")
        ->not->toContain("->label('Work items')");
});
