<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

it('requires post for workflow and session mutations', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/saas/sign-out/perform')
        ->assertMethodNotAllowed();

    $this->actingAs($user)
        ->get('/choose-workspace/verification')
        ->assertMethodNotAllowed();

    expect(collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($route) => $route->getName() === 'clinic.switch-workspace')
        ?->methods())->toBe(['POST']);
});

it('requires email verification before a user can enter a product panel', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->unverified()->create(['status' => true]);
    $user->assignRole('saas_admin');

    $this->actingAs($user)
        ->get('/saas')
        ->assertRedirect(route('verification.notice'));
});

it('keeps one canonical portal credential workspace', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create(['status' => true]);
    $user->assignRole('saas_admin');

    $this->actingAs($user)
        ->get('/verification/portal-credential-settings')
        ->assertRedirect('/verification/portal-credentials');

    $this->actingAs($user)
        ->get('/verification/settings')
        ->assertSuccessful()
        ->assertSee('/verification/portal-credentials', escape: false);

    $this->actingAs($user)
        ->get('/clinic/portal-credential-settings')
        ->assertRedirect('/clinic/portal-credentials');
});

it('uses one compact audited portal credential workspace across clinic and verification panels', function () {
    $clinicView = file_get_contents(resource_path('views/filament/clinic/resources/portal-credentials/pages/portal-credential-workspace.blade.php'));
    $verificationView = file_get_contents(resource_path('views/filament/saas/resources/portal-credentials/pages/list-portal-credentials.blade.php'));
    $sharedView = file_get_contents(resource_path('views/filament/shared/portal-credential-workspace.blade.php'));
    $interactionEngine = file_get_contents(app_path('Filament/Concerns/ManagesPortalCredentialSecurityQuestions.php'));

    expect($clinicView)
        ->toContain("@include('filament.shared.portal-credential-workspace')")
        ->and($verificationView)->toContain("@include('filament.shared.portal-credential-workspace')")
        ->and($sharedView)
        ->toContain('pd-credential-table')
        ->toContain('revealCredentialSecret')
        ->toContain('copyCredentialSecret')
        ->toContain('openSecurityQuestions')
        ->toContain('revealSecurityQuestionAnswer')
        ->toContain('Security Questions &amp; Answers')
        ->and($interactionEngine)
        ->toContain('revealPortalCredentialValue')
        ->toContain('portalCredentialDisplayValue')
        ->toContain('clearExpiredPortalCredentialValues')
        ->and($sharedView)
        ->toContain('copyProtectedValue')
        ->toContain('navigator.clipboard?.writeText')
        ->toContain("document.execCommand('copy')")
        ->and($interactionEngine)
        ->toContain("'expires_at' => now()->addSeconds(30)->timestamp")
        ->not->toContain('window.pdPortalCredentialReveal')
        ->not->toContain('window.pdPortalCredentialCopy')
        ->not->toContain('__pdPortalCredentialEventsRegistered')
        ->not->toContain('@js($credential->password)')
        ->not->toContain('@js($question')
        ->not->toContain('portal-credential-card-grid');
});

it('keeps central master data out of the verification workspace', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create(['status' => true]);
    $user->assignRole('saas_admin');

    $this->actingAs($user)
        ->get('/verification/master-template/create')
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/verification/insurance-carriers/create')
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/verification/verification-form-lab')
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/saas/master-template/create')
        ->assertRedirect('/saas/master-template/questions/create');

    $this->actingAs($user)
        ->get('/saas/master-template/questions/create')
        ->assertNotFound();
});
