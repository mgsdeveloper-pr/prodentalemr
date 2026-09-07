<?php

use App\Filament\Saas\Pages\Dashboard;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders an action-focused saas dashboard with trustworthy paid mrr', function (): void {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');

    $organization = Organization::create([
        'name' => 'Dashboard Dental Group',
        'owner_name' => 'Owner',
        'status' => true,
        'onboarding_status' => 'complete',
    ]);

    $activePlan = SubscriptionPlan::create([
        'name' => 'Active Plan',
        'price' => 125,
        'max_clinics' => 1,
        'max_users' => 5,
        'status' => true,
    ]);
    $trialPlan = SubscriptionPlan::create([
        'name' => 'Trial Plan',
        'price' => 75,
        'max_clinics' => 1,
        'max_users' => 5,
        'status' => true,
    ]);

    Subscription::create([
        'organization_id' => $organization->id,
        'subscription_plan_id' => $activePlan->id,
        'start_date' => today(),
        'status' => 'active',
        'service_status' => 'active',
    ]);
    Subscription::create([
        'organization_id' => $organization->id,
        'subscription_plan_id' => $trialPlan->id,
        'start_date' => today(),
        'trial_ends_at' => today()->addDays(7),
        'status' => 'trial',
        'service_status' => 'trial',
    ]);

    Invoice::create([
        'organization_id' => $organization->id,
        'issue_date' => today()->subDays(20),
        'due_date' => today()->subDays(5),
        'status' => 'overdue',
        'total_amount' => 240,
        'balance_due' => 240,
    ]);

    $this->actingAs($admin)
        ->get('/saas')
        ->assertSuccessful()
        ->assertSee('SaaS Dashboard')
        ->assertSee('Attention Required')
        ->assertSee('Revenue Trend')
        ->assertSee('Accounts Requiring Follow-Up')
        ->assertSee('$125.00')
        ->assertDontSee('$200.00');
});

it('updates the dashboard reporting period safely', function (): void {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');
    Filament::setCurrentPanel(Filament::getPanel('saas'));

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->set('period', 'quarter')
        ->assertSet('period', 'quarter')
        ->set('period', 'invalid')
        ->assertSet('period', 'month');
});
