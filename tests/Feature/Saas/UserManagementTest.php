<?php

use App\Filament\Saas\Pages\UserManagement;
use App\Models\TelephonyAccount;
use App\Models\TelephonyUserAssignment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $this->admin = User::factory()->create([
        'name' => 'MGS Admin',
        'email' => 'admin@example.com',
        'status' => true,
        'last_login_at' => now(),
    ]);
    $this->admin->assignRole('saas_admin');
});

it('renders the operational platform user workspace', function (): void {
    $account = TelephonyAccount::create([
        'name' => 'Platform Calling',
        'api_key' => 'test-api-key',
        'is_platform_default' => true,
        'is_active' => true,
    ]);

    TelephonyUserAssignment::create([
        'telephony_account_id' => $account->id,
        'user_id' => $this->admin->id,
        'provider_user_id' => 'admin-caller',
        'can_call' => true,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get('/saas/user-management')
        ->assertOk()
        ->assertSee('User Management')
        ->assertSee('Platform Workspace')
        ->assertSee('List')
        ->assertSee('Add User')
        ->assertSee('Roles in use')
        ->assertSee('Roles &amp; Permissions', false)
        ->assertSee('Calling Access')
        ->assertSee('MGS Admin')
        ->assertSee('All organizations')
        ->assertSee('Enabled')
        ->assertSee('Last active')
        ->assertDontSee('Open Users')
        ->assertDontSee('saas-user-hero');
});

it('searches and filters platform users without exposing unrelated accounts', function (): void {
    $active = User::factory()->create([
        'name' => 'Alex Active',
        'email' => 'alex@example.com',
        'email_verified_at' => now(),
        'status' => true,
    ]);
    $active->assignRole('saas_user');

    $invited = User::factory()->create([
        'name' => 'Ivy Invited',
        'email' => 'ivy@example.com',
        'email_verified_at' => null,
        'status' => true,
    ]);
    $invited->assignRole('saas_user');

    $clinicUser = User::factory()->create([
        'name' => 'Clinic Only',
        'status' => true,
    ]);
    $clinicUser->assignRole('clinic_admin');

    Livewire::actingAs($this->admin)
        ->test(UserManagement::class)
        ->set('search', 'Alex')
        ->assertSee('Alex Active')
        ->assertDontSee('Ivy Invited')
        ->assertDontSee('Clinic Only')
        ->set('search', '')
        ->set('status', 'invited')
        ->assertSee('Ivy Invited')
        ->assertDontSee('Alex Active')
        ->assertDontSee('Clinic Only');
});

it('keeps the user management presentation aligned with the global workspace pattern', function (): void {
    $view = file_get_contents(resource_path('views/filament/saas/pages/user-management.blade.php'));

    expect($view)
        ->toContain('<x-filament-panels::page>')
        ->not->toContain('saas-users__header')
        ->toContain('saas-users__summary')
        ->toContain('saas-users__tabs')
        ->toContain('saas-users__table-wrap')
        ->not->toContain('saas-user-hero')
        ->not->toContain('saas-user-card')
        ->not->toContain('border-radius: 24px')
        ->not->toContain('border-radius: 26px');
});
