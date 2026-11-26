<?php

use App\Filament\Saas\Pages\SystemUpdates;
use App\Models\User;
use App\Services\SystemUpdateBlocked;
use App\Services\SystemUpdateManager;
use Database\Seeders\RoleSeeder;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

it('restricts the system update center to an active SaaS administrator', function (): void {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');

    $manager = User::factory()->create(['status' => true]);
    $manager->assignRole('saas_manager');

    $this->actingAs($admin)
        ->get('/saas/system-updates')
        ->assertOk()
        ->assertSee('Database and application updates')
        ->assertSee('Pending database changes')
        ->assertSee('Activate latest code')
        ->assertSee('Confirm SaaS Admin password');

    $this->actingAs($manager)
        ->get('/saas/system-updates')
        ->assertForbidden();
});

it('requires a verified backup and current password before starting', function (): void {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create([
        'status' => true,
        'password' => bcrypt('correct-password'),
    ]);
    $admin->assignRole('saas_admin');

    $this->actingAs($admin);

    Livewire::test(SystemUpdates::class)
        ->set('confirmationPassword', 'wrong-password')
        ->call('startUpdate')
        ->assertHasErrors(['backupConfirmed']);

    Livewire::test(SystemUpdates::class)
        ->set('backupConfirmed', true)
        ->set('confirmationPassword', 'wrong-password')
        ->call('startUpdate')
        ->assertHasErrors(['confirmationPassword']);
});

it('requires the current SaaS administrator password before activating code', function (): void {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create([
        'status' => true,
        'password' => bcrypt('correct-password'),
    ]);
    $admin->assignRole('saas_admin');

    $this->actingAs($admin);

    Livewire::test(SystemUpdates::class)
        ->call('activateLatestCode')
        ->assertHasErrors(['activationPassword']);

    Livewire::test(SystemUpdates::class)
        ->set('activationPassword', 'wrong-password')
        ->call('activateLatestCode')
        ->assertHasErrors(['activationPassword']);
});

it('returns safe pending migration metadata without exposing paths in the UI contract', function (): void {
    $pending = app(SystemUpdateManager::class)->pendingMigrations();

    foreach ($pending as $migration) {
        expect($migration['name'])
            ->not->toContain('C:\\')
            ->not->toContain('/var/')
            ->and($migration['file'])->toEndWith('.php');
    }

    $view = file_get_contents(resource_path('views/filament/saas/pages/system-updates.blade.php'));
    expect($view)->toContain('$'."migration['name']")->not->toContain('$'."migration['file']");
});

it('keeps the maintenance bypass cookie available for Laravel validation', function (): void {
    $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)->toContain("encryptCookies(except: ['laravel_maintenance'])");
});

it('refreshes generated release files without running console cache commands inside the web request', function (): void {
    $manager = file_get_contents(app_path('Services/SystemUpdateManager.php'));

    expect($manager)
        ->toContain('getCachedRoutesPath()')
        ->toContain("storage_path('framework/views')")
        ->toContain("Cache::forever('illuminate:queue:restart'")
        ->not->toContain("'filament:optimize-clear'")
        ->not->toContain("'optimize:clear'")
        ->not->toContain("'route:cache'")
        ->not->toContain("'view:cache'");
});

it('shows safe recovery guidance and logs details when a system update action fails', function (
    string $action, string $operation, string $title, string $message, string $level,
): void {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create(['status' => true, 'password' => bcrypt('correct-password')]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);

    $failure = new RuntimeException('UX02_INTERNAL_ONLY update failure /srv/private/qa.php');
    $manager = $this->partialMock(SystemUpdateManager::class);
    $releaseId = $action === 'continueUpdate' ? 'QA-release' : null;
    $manager->shouldReceive('currentRun')->andReturn($releaseId ? ['id' => $releaseId, 'status' => 'running'] : null);
    $manager->shouldReceive($operation)->once()->with($admin->id)->andThrow($failure);
    Log::spy();

    Livewire::test(SystemUpdates::class)
        ->set('backupConfirmed', true)
        ->set('confirmationPassword', 'correct-password')
        ->set('activationPassword', 'correct-password')
        ->call($action)
        ->assertHasNoErrors()
        ->assertNotified(Notification::make()->title($title)->body($message)->danger()->persistent())
        ->assertDontSee('UX02_INTERNAL_ONLY');

    Log::shouldHaveReceived('log')->once()->with($level, $title, [
        'release_id' => $releaseId, 'initiated_by' => $admin->id, 'exception' => $failure,
    ]);
})->with([
    ['startUpdate', 'start', 'System update could not start', 'Refresh this page and review the update status before retrying. If it still fails, ask your server administrator to check the logs.', 'error'],
    ['continueUpdate', 'processNextStep', 'System update stopped', 'Ask your server administrator to check the logs before retrying the update.', 'critical'],
    ['activateLatestCode', 'activateLatestCode', 'Latest code could not be activated', 'Ask your server administrator to check the logs and verify the deployed release before trying again.', 'error'],
    ['restoreApplication', 'restoreApplication', 'Recovery could not complete', 'Ask your server administrator to check the logs and restore application access from the server.', 'error'],
]);

it('shows actionable warnings for known update blockers', function (Throwable $blocker, string $message): void {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create(['status' => true, 'password' => bcrypt('correct-password')]);
    $admin->assignRole('saas_admin');
    $this->actingAs($admin);
    $this->partialMock(SystemUpdateManager::class)
        ->shouldReceive('start')->once()->with($admin->id)->andThrow($blocker);
    Log::spy();

    Livewire::test(SystemUpdates::class)
        ->set('backupConfirmed', true)
        ->set('confirmationPassword', 'correct-password')
        ->call('startUpdate')
        ->assertNotified(Notification::make()->title('System update could not start')->body($message)->warning()->persistent());
    Log::shouldNotHaveReceived('log');
})->with([
    [
        new SystemUpdateBlocked('Resolve the failed required preflight checks before starting an update.'),
        'Resolve the failed required preflight checks before starting an update.',
    ],
    [
        new LockTimeoutException('UX02_INTERNAL_ONLY cache lock details'),
        'An update or recovery action is in progress. Wait for it to finish, then refresh this page.',
    ],
]);

it('keeps diagnostic text from older failed runs out of the system update page', function (): void {
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    $legacyRun = [
        'id' => 'QA-legacy-release',
        'status' => 'failed',
        'error' => 'UX02_PRIVATE_SQL_ERROR',
        'message' => 'UX02_PRIVATE_FAILED_MESSAGE',
    ];
    $manager = $this->partialMock(SystemUpdateManager::class);
    $manager->shouldReceive('currentRun')->andReturn($legacyRun);
    $manager->shouldReceive('history')->andReturn([$legacyRun]);

    $this->actingAs($admin)
        ->get('/saas/system-updates')
        ->assertOk()
        ->assertSee('QA-legacy-release')
        ->assertSee('Restore application access')
        ->assertSee('Ask your server administrator to check the logs for this release')
        ->assertDontSee('UX02_PRIVATE_SQL_ERROR')
        ->assertDontSee('UX02_PRIVATE_FAILED_MESSAGE');
});
