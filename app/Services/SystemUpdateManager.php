<?php

namespace App\Services;

use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Cookie;
use Throwable;

class SystemUpdateManager
{
    private const LOCK_NAME = 'prodental:system-update-step';

    private const STATE_DIRECTORY = 'app/private/system-updates';

    /**
     * @return array<int, array{name: string, file: string}>
     */
    public function pendingMigrations(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles(database_path('migrations'));
        $ran = $migrator->repositoryExists()
            ? $migrator->getRepository()->getRan()
            : [];

        return collect($files)
            ->reject(fn (string $file, string $name): bool => in_array($name, $ran, true))
            ->map(fn (string $file, string $name): array => [
                'name' => $name,
                'file' => $file,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, passed: bool, action: string, blocking: bool}>
     */
    public function preflightChecks(): array
    {
        $production = app()->environment('production');

        return [
            $this->check('Production environment', $production, 'Set APP_ENV=production on the live server.', true),
            $this->check('Debug mode disabled', ! config('app.debug'), 'Set APP_DEBUG=false.', true),
            $this->check('Application key present', filled(config('app.key')), 'Generate and securely retain APP_KEY.', true),
            $this->check('HTTPS application URL', str_starts_with((string) config('app.url'), 'https://'), 'Set APP_URL to the HTTPS production URL.', true),
            $this->check('Public registration closed', ! config('prodental.public_registration'), 'Keep public registration closed until approved onboarding is released.', true),
            $this->check('Persistent database', config('database.default') !== 'sqlite', 'Use the production MySQL or managed database connection.', true),
            $this->check('Asynchronous queue', ! in_array(config('queue.default'), ['sync', 'null'], true), 'Configure a database, Redis, SQS, or equivalent queue.', false),
            $this->check('Production mail transport', ! in_array(config('mail.default'), ['log', 'array'], true), 'Configure and test the production mail provider.', false),
            $this->check('Session encryption', (bool) config('session.encrypt'), 'Set SESSION_ENCRYPT=true.', true),
            $this->check('Secure session cookie', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true.', true),
            $this->check('Writable update storage', $this->storageIsWritable(), 'Make storage/app/private/system-updates writable.', true),
        ];
    }

    public function productionGatesPass(): bool
    {
        if (! app()->environment('production')) {
            return true;
        }

        return collect($this->preflightChecks())
            ->where('blocking', true)
            ->every(fn (array $check): bool => $check['passed']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function currentRun(): ?array
    {
        return $this->readJson($this->statePath());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(): array
    {
        return $this->readJson($this->historyPath()) ?? [];
    }

    /**
     * @return array{run: array<string, mixed>, bypass_cookie: Cookie|null}
     */
    public function start(int $userId): array
    {
        return Cache::lock(self::LOCK_NAME, 120)->block(5, function () use ($userId): array {
            $active = $this->currentRun();
            if (is_array($active) && in_array($active['status'] ?? null, ['preparing', 'running'], true)) {
                throw new RuntimeException('Another system update is already in progress.');
            }

            $pending = $this->pendingMigrations();
            if ($pending === []) {
                throw new RuntimeException('The database is already current.');
            }

            if (! $this->productionGatesPass()) {
                throw new RuntimeException('One or more required production checks failed.');
            }

            $run = [
                'id' => now()->format('YmdHis').'-'.Str::lower(Str::random(8)),
                'status' => 'preparing',
                'phase' => 'migrations',
                'initiated_by' => $userId,
                'started_at' => now()->toIso8601String(),
                'completed_at' => null,
                'initial_migrations' => array_column($pending, 'name'),
                'completed_migrations' => [],
                'current_migration' => null,
                'message' => 'Preparing the protected update session.',
                'error' => null,
                'maintenance_started_here' => ! app()->isDownForMaintenance(),
            ];

            $this->writeState($run);
            $cookie = null;

            try {
                if ($run['maintenance_started_here']) {
                    $secret = Str::random(48);
                    $exitCode = Artisan::call('down', [
                        '--secret' => $secret,
                        '--retry' => 60,
                        '--refresh' => 15,
                    ]);

                    if ($exitCode !== 0) {
                        throw new RuntimeException('Maintenance mode could not be enabled.');
                    }

                    $cookie = MaintenanceModeBypassCookie::create($secret);
                }

                $run['status'] = 'running';
                $run['message'] = 'Update started. Pending migrations will run one at a time.';
                $this->writeState($run);
            } catch (Throwable $exception) {
                $run['status'] = 'failed';
                $run['error'] = $exception->getMessage();
                $run['message'] = 'Update preparation failed. Restore access after reviewing the failure.';
                $this->writeState($run);
                $this->recordHistory($run);

                throw $exception;
            }

            Log::notice('SaaS system update started.', [
                'release_id' => $run['id'],
                'initiated_by' => $userId,
                'migrations' => $run['initial_migrations'],
            ]);

            return ['run' => $run, 'bypass_cookie' => $cookie];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function processNextStep(int $userId): array
    {
        return Cache::lock(self::LOCK_NAME, 120)->block(5, function () use ($userId): array {
            $run = $this->currentRun();
            if (! is_array($run) || ($run['status'] ?? null) !== 'running') {
                throw new RuntimeException('There is no active update to continue.');
            }

            try {
                if (($run['phase'] ?? 'migrations') === 'migrations') {
                    $pending = $this->pendingMigrations();

                    if ($pending !== []) {
                        $migration = $pending[0];
                        $run['current_migration'] = $migration['name'];
                        $run['message'] = 'Applying '.$migration['name'];
                        $this->writeState($run);

                        app('migrator')->run([$migration['file']], ['step' => true]);

                        $run['completed_migrations'][] = $migration['name'];
                        $run['completed_migrations'] = array_values(array_unique($run['completed_migrations']));
                        $run['current_migration'] = null;
                        $run['message'] = $migration['name'].' completed.';

                        if ($this->pendingMigrations() === []) {
                            $run['phase'] = 'optimize';
                        }

                        $this->writeState($run);

                        return $run;
                    }

                    $run['phase'] = 'optimize';
                }

                if ($run['phase'] === 'optimize') {
                    $run['message'] = 'Rebuilding application caches.';
                    $this->writeState($run);
                    $this->runArtisanTask('optimize');
                    $run['phase'] = 'queue';
                    $run['message'] = 'Application caches rebuilt.';
                    $this->writeState($run);

                    return $run;
                }

                if ($run['phase'] === 'queue') {
                    $run['message'] = 'Refreshing background workers.';
                    $this->writeState($run);
                    $this->runArtisanTask('queue:restart');
                    $run['phase'] = 'complete';
                    $this->writeState($run);

                    return $run;
                }

                return $this->complete($run, $userId);
            } catch (Throwable $exception) {
                $run['status'] = 'failed';
                $run['error'] = $exception->getMessage();
                $run['message'] = 'Update stopped. Review the failure before restoring access.';
                $this->writeState($run);
                $this->recordHistory($run);

                Log::critical('SaaS system update failed.', [
                    'release_id' => $run['id'] ?? null,
                    'initiated_by' => $userId,
                    'exception' => $exception,
                ]);

                throw $exception;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function restoreApplication(int $userId): array
    {
        return Cache::lock(self::LOCK_NAME, 120)->block(5, function () use ($userId): array {
            $run = $this->currentRun();
            if (! is_array($run) || ($run['status'] ?? null) !== 'failed') {
                throw new RuntimeException('Recovery is only available after a failed update.');
            }

            if (($run['maintenance_started_here'] ?? false) && app()->isDownForMaintenance()) {
                $this->runArtisanTask('up');
            }

            $run['status'] = 'recovered';
            $run['completed_at'] = now()->toIso8601String();
            $run['message'] = 'Application access restored. The failed update still requires investigation.';
            $run['recovered_by'] = $userId;
            $this->writeState($run);
            $this->recordHistory($run);

            return $run;
        });
    }

    /**
     * @param  array<string, mixed>  $run
     * @return array<string, mixed>
     */
    private function complete(array $run, int $userId): array
    {
        if ($this->pendingMigrations() !== []) {
            throw new RuntimeException('Migrations remain pending after the update sequence.');
        }

        if (($run['maintenance_started_here'] ?? false) && app()->isDownForMaintenance()) {
            $this->runArtisanTask('up');
        }

        $run['status'] = 'completed';
        $run['completed_at'] = now()->toIso8601String();
        $run['current_migration'] = null;
        $run['message'] = 'Database update completed successfully.';
        $run['completed_by'] = $userId;
        $this->writeState($run);
        $this->recordHistory($run);

        Log::notice('SaaS system update completed.', [
            'release_id' => $run['id'],
            'completed_by' => $userId,
            'migrations' => $run['completed_migrations'],
        ]);

        return $run;
    }

    private function runArtisanTask(string $command): void
    {
        if (Artisan::call($command) !== 0) {
            throw new RuntimeException("{$command} failed.");
        }
    }

    /**
     * @return array{label: string, passed: bool, action: string, blocking: bool}
     */
    private function check(string $label, bool $passed, string $action, bool $blocking): array
    {
        return compact('label', 'passed', 'action', 'blocking');
    }

    private function storageIsWritable(): bool
    {
        try {
            File::ensureDirectoryExists($this->directoryPath());

            return is_writable($this->directoryPath());
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function writeState(array $run): void
    {
        $this->writeJson($this->statePath(), $run);
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function recordHistory(array $run): void
    {
        $history = collect($this->history())
            ->reject(fn (array $item): bool => ($item['id'] ?? null) === ($run['id'] ?? null))
            ->prepend($run)
            ->take(20)
            ->values()
            ->all();

        $this->writeJson($this->historyPath(), $history);
    }

    /**
     * @return array<mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (! File::exists($path)) {
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<mixed>  $data
     */
    private function writeJson(string $path, array $data): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function directoryPath(): string
    {
        return storage_path(self::STATE_DIRECTORY);
    }

    private function statePath(): string
    {
        return $this->directoryPath().DIRECTORY_SEPARATOR.'current.json';
    }

    private function historyPath(): string
    {
        return $this->directoryPath().DIRECTORY_SEPARATOR.'history.json';
    }
}
