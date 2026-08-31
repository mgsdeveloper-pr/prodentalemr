<?php

use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;

it('reports pending database migrations without changing application state', function (): void {
    expect(Artisan::call('prodental:database-update', ['--dry-run' => true]))
        ->toBe(0)
        ->and(Artisan::output())
        ->toContain('ProDental database update')
        ->toContain('Dry run complete');
});

it('does not expose a browser route for database updates', function (): void {
    $this->get('/prodental/database-update')->assertNotFound();
    $this->post('/prodental/database-update')->assertNotFound();
});

it('requires force and verified backup confirmation before executing pending migrations', function (): void {
    $repository = Mockery::mock(MigrationRepositoryInterface::class);
    $repository->shouldReceive('getRan')->twice()->andReturn([]);

    $migrator = Mockery::mock(Migrator::class);
    $migrator->shouldReceive('getMigrationFiles')->twice()->andReturn([
        '2099_01_01_000001_example_pending_migration' => database_path('migrations/2099_01_01_000001_example_pending_migration.php'),
    ]);
    $migrator->shouldReceive('repositoryExists')->twice()->andReturnTrue();
    $migrator->shouldReceive('getRepository')->twice()->andReturn($repository);

    app()->instance('migrator', $migrator);

    expect(Artisan::call('prodental:database-update'))
        ->toBe(1)
        ->and(Artisan::output())->toContain('Run again with --force');

    expect(Artisan::call('prodental:database-update', ['--force' => true]))
        ->toBe(1)
        ->and(Artisan::output())->toContain('Create and verify a database backup');
});
