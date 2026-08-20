<?php

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('generates public ids for new business models without replacing numeric ids', function (): void {
    $organization = Organization::create([
        'name' => 'Public ID Dental Group',
        'owner_name' => 'Avery Public',
        'email' => 'public-id@example.test',
        'phone' => '555-0100',
        'address' => '100 Main Street',
        'city' => 'Austin',
        'state' => 'TX',
        'zip_code' => '78701',
        'country' => 'USA',
        'status' => true,
    ]);

    expect($organization->id)->toBeInt()
        ->and($organization->public_id)->toBeString()
        ->and($organization->public_id)->toHaveLength(26);
});

it('does not overwrite an existing public id and resolves routes by either id or public id', function (): void {
    $publicId = (string) Str::ulid();

    $organization = new Organization([
        'name' => 'Route ID Dental Group',
        'owner_name' => 'Riley Route',
        'email' => 'route-id@example.test',
        'phone' => '555-0101',
        'address' => '200 Main Street',
        'city' => 'Dallas',
        'state' => 'TX',
        'zip_code' => '75201',
        'country' => 'USA',
        'status' => true,
    ]);
    $organization->public_id = $publicId;
    $organization->save();

    expect($organization->public_id)->toBe($publicId)
        ->and((new Organization())->resolveRouteBinding((string) $organization->id)?->is($organization))->toBeTrue()
        ->and((new Organization())->resolveRouteBinding($publicId)?->is($organization))->toBeTrue();
});
