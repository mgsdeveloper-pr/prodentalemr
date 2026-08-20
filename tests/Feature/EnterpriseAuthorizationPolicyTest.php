<?php

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::create([
        'name' => 'Authorization Dental Group',
        'owner_name' => 'Owner',
        'email' => 'owner@authorization.test',
        'phone' => '5551001000',
        'status' => true,
    ]);
});

it('uses spatie permissions through invoice policies', function () {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');

    $limited = User::factory()->create(['status' => true]);
    $limited->assignRole('saas_user');

    $invoice = Invoice::create([
        'organization_id' => $this->organization->id,
        'issue_date' => today(),
        'status' => 'sent',
        'subtotal' => 100,
        'total_amount' => 100,
        'balance_due' => 100,
    ]);

    expect($admin->can('viewAny', Invoice::class))->toBeTrue()
        ->and($admin->can('download', $invoice))->toBeTrue()
        ->and($limited->can('viewAny', Invoice::class))->toBeFalse()
        ->and($limited->can('download', $invoice))->toBeFalse();

    $limited->givePermissionTo('saas.invoices.view');

    expect($limited->fresh()->can('viewAny', Invoice::class))->toBeTrue()
        ->and($limited->fresh()->can('download', $invoice))->toBeTrue()
        ->and($limited->fresh()->can('create', Invoice::class))->toBeFalse();
});

it('uses spatie permissions through payment policies', function () {
    $admin = User::factory()->create(['status' => true]);
    $admin->assignRole('saas_admin');

    $limited = User::factory()->create(['status' => true]);
    $limited->assignRole('saas_user');

    $invoice = Invoice::create([
        'organization_id' => $this->organization->id,
        'issue_date' => today(),
        'status' => 'sent',
        'subtotal' => 100,
        'total_amount' => 100,
        'balance_due' => 100,
    ]);

    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'organization_id' => $this->organization->id,
        'payment_date' => today(),
        'amount' => 25,
        'payment_method' => 'manual',
        'created_by' => $admin->id,
    ]);

    expect($admin->can('viewAny', Payment::class))->toBeTrue()
        ->and($admin->can('update', $payment))->toBeTrue()
        ->and($limited->can('viewAny', Payment::class))->toBeFalse()
        ->and($limited->can('update', $payment))->toBeFalse();

    $limited->givePermissionTo('saas.payments.view');

    expect($limited->fresh()->can('viewAny', Payment::class))->toBeTrue()
        ->and($limited->fresh()->can('view', $payment))->toBeTrue()
        ->and($limited->fresh()->can('update', $payment))->toBeFalse();
});
