<?php

use App\Filament\Saas\Pages\SystemUpdates;
use App\Models\User;
use App\Services\SystemUpdateManager;
use Database\Seeders\RoleSeeder;
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
