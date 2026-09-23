<?php

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\User;
use Database\Seeders\ByDesignDentalsSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    config(['client_provisioning.by_design_dentals_tax_id' => '123456789']);
});

it('creates a scoped clinic administrator and separate non-login provider without billing', function () {
    $this->seed(ByDesignDentalsSeeder::class);
    $clinic = Clinic::where('clinic_code', ByDesignDentalsSeeder::CODE)->firstOrFail();
    $admin = User::where('email', ByDesignDentalsSeeder::ADMIN_EMAIL)->firstOrFail();
    $provider = Provider::where('clinic_id', $clinic->id)->firstOrFail();
    expect($admin->hasRole('clinic_admin'))->toBeTrue();
    expect($admin->clinic_id)->toBe($clinic->id);
    expect($admin->allowed_workspaces)->toBe(['clinic']);
    expect($clinic->allowsManagedServices())->toBeTrue();
    expect($clinic->hasActiveVerificationServices())->toBeTrue();
    $modes = \App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm::processingModeOptions($clinic->organization_id, $clinic->id, $clinic->default_location_id);
    expect(array_values($modes))->toBe(['Managed Service', 'Self-Managed']);
    expect($clinic->tax_id)->toBe('123456789');
    expect($clinic->getRawOriginal('tax_id'))->not->toBe('123456789');
    expect($clinic->clinic_npi)->toBeNull();
    expect($provider->npi_number)->toBe('1689402257');
    expect($provider->user->status)->toBeFalse();
    expect($provider->user_id)->not->toBe($admin->id);
    expect($clinic->organization->subscriptions()->count())->toBe(0);
    $password = $admin->getRawOriginal('password');
    $this->seed(ByDesignDentalsSeeder::class);
    expect(Clinic::where('clinic_code', ByDesignDentalsSeeder::CODE)->count())->toBe(1);
    expect($admin->fresh()->getRawOriginal('password'))->toBe($password);
});

it('refuses to reassign an existing email and rolls back client creation', function () {
    $existing = User::factory()->create(['email' => ByDesignDentalsSeeder::ADMIN_EMAIL]);
    expect(fn () => $this->seed(ByDesignDentalsSeeder::class))->toThrow(RuntimeException::class);
    expect(Organization::where('name', 'By Design Dentals')->exists())->toBeFalse();
    expect($existing->fresh()->clinic_id)->toBeNull();
});

it('provisions with a clearly pending tax ID when deployment configuration is absent', function () {
    config(['client_provisioning.by_design_dentals_tax_id' => null]);
    $this->seed(ByDesignDentalsSeeder::class);
    $clinic = Clinic::where('clinic_code', ByDesignDentalsSeeder::CODE)->firstOrFail();
    expect($clinic->tax_id)->toBeNull();
    expect($clinic->service_notes)->toContain('Tax ID pending');
    expect($clinic->organization->onboarding_status)->toBe('in_progress');
    $clinic->update(['tax_id' => '987654321']);
    $this->seed(ByDesignDentalsSeeder::class);
    expect($clinic->fresh()->tax_id)->toBe('987654321');
});

it('rejects an invalid supplied tax ID without partial records', function () {
    config(['client_provisioning.by_design_dentals_tax_id' => 'invalid']);
    expect(fn () => $this->seed(ByDesignDentalsSeeder::class))->toThrow(RuntimeException::class);
    expect(Organization::where('name', 'By Design Dentals')->exists())->toBeFalse();
});
