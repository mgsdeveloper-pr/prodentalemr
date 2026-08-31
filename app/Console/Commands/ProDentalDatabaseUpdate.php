<?php

namespace App\Console\Commands;

use App\Services\SystemUpdateManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProDentalDatabaseUpdate extends Command
{
    protected $signature = 'prodental:database-update
        {--dry-run : List pending migrations without changing the application}
        {--force : Confirm that this update may run without an interactive prompt}
        {--backup-confirmed : Confirm that a current database backup has been verified}
        {--skip-optimize : Skip rebuilding Laravel caches after migration}';

    protected $description = 'Safely execute pending ProDental database migrations during a controlled deployment.';

    public function handle(): int
    {
        $pending = $this->pendingMigrations();

        $this->displayPendingMigrations($pending);

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database or application state was changed.');

            return self::SUCCESS;
        }

        if ($pending === []) {
            $this->info('The database is current. No migrations were executed.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->error('Update blocked. Run again with --force after reviewing the pending migrations.');

            return self::FAILURE;
        }

        if (! $this->option('backup-confirmed')) {
            $this->error('Update blocked. Create and verify a database backup, then add --backup-confirmed.');

            return self::FAILURE;
        }

        if (app()->environment('production') && Artisan::call('prodental:production-check') !== self::SUCCESS) {
            $this->output->write(Artisan::output());
            $this->error('Update blocked because one or more production gates failed.');

            return self::FAILURE;
        }

        $lock = Cache::lock('prodental:database-update', 1800);

        if (! $lock->get()) {
            $this->error('Another database update is already running. No changes were made.');

            return self::FAILURE;
        }

        $wasAlreadyDown = app()->isDownForMaintenance();
        $releaseId = now()->format('YmdHis').'-'.Str::lower(Str::random(6));

        try {
            Log::notice('ProDental database update started.', [
                'release_id' => $releaseId,
                'environment' => app()->environment(),
                'migrations' => $pending,
            ]);

            if (! $wasAlreadyDown) {
                $this->runRequiredTask('Enable maintenance mode', fn (): bool => Artisan::call('down', [
                    '--retry' => 60,
                    '--refresh' => 15,
                ]) === self::SUCCESS);
            }

            $this->runRequiredTask('Execute pending migrations', function (): bool {
                $exitCode = Artisan::call('migrate', ['--force' => true]);
                $output = trim(Artisan::output());

                if ($output !== '') {
                    $this->newLine();
                    $this->line($output);
                }

                return $exitCode === self::SUCCESS;
            });

            $remaining = $this->pendingMigrations();
            if ($remaining !== []) {
                throw new \RuntimeException('One or more migrations remain pending after execution.');
            }

            if (! $this->option('skip-optimize')) {
                $this->runRequiredTask('Rebuild Laravel caches', fn (): bool => Artisan::call('optimize') === self::SUCCESS);
            }

            $this->runRequiredTask('Restart queue workers', fn (): bool => Artisan::call('queue:restart') === self::SUCCESS);

            if (! $wasAlreadyDown) {
                $this->runRequiredTask('Disable maintenance mode', fn (): bool => Artisan::call('up') === self::SUCCESS);
            }

            Log::notice('ProDental database update completed.', [
                'release_id' => $releaseId,
                'migrations' => $pending,
            ]);

            $this->info("Database update completed successfully. Release ID: {$releaseId}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::critical('ProDental database update failed.', [
                'release_id' => $releaseId,
                'exception' => $exception,
            ]);

            $this->newLine();
            $this->error('Database update failed: '.$exception->getMessage());
            $this->warn(app()->isDownForMaintenance()
                ? 'Maintenance mode remains enabled for investigation and recovery.'
                : 'Verify application availability and complete recovery before allowing client access.');

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    private function runRequiredTask(string $description, callable $task): void
    {
        if ($this->components->task($description, $task) !== true) {
            throw new \RuntimeException("{$description} failed.");
        }
    }

    /**
     * @return array<int, string>
     */
    private function pendingMigrations(): array
    {
        return array_column(app(SystemUpdateManager::class)->pendingMigrations(), 'name');
    }

    /**
     * @param  array<int, string>  $pending
     */
    private function displayPendingMigrations(array $pending): void
    {
        $this->info('ProDental database update');

        if ($pending === []) {
            $this->line('Pending migrations: 0');

            return;
        }

        $this->table(
            ['#', 'Pending migration'],
            collect($pending)->values()->map(fn (string $migration, int $index): array => [
                $index + 1,
                $migration,
            ])->all(),
        );
    }
}
