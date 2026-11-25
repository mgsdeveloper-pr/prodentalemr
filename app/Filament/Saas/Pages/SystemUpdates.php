<?php

namespace App\Filament\Saas\Pages;

use App\Services\SystemUpdateBlocked;
use App\Services\SystemUpdateManager;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnitEnum;

class SystemUpdates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'System Updates';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'System Updates';

    protected static ?string $slug = 'system-updates';

    protected string $view = 'filament.saas.pages.system-updates';

    public bool $backupConfirmed = false;

    public string $confirmationPassword = '';

    public string $activationPassword = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSaasAdmin() ?? false;
    }

    protected function getViewData(): array
    {
        $manager = app(SystemUpdateManager::class);

        return [
            'summary' => [
                'pending' => $manager->pendingMigrations(),
                'checks' => $manager->preflightChecks(),
                'run' => $manager->currentRun(),
                'history' => $manager->history(),
                'production_gates_pass' => $manager->productionGatesPass(),
            ],
        ];
    }

    public function startUpdate(): void
    {
        $this->validate([
            'backupConfirmed' => ['accepted'],
            'confirmationPassword' => ['required', 'string'],
        ], [
            'backupConfirmed.accepted' => 'Confirm that a current database backup has been created and verified.',
        ]);

        if (! Hash::check($this->confirmationPassword, auth()->user()->password)) {
            $this->addError('confirmationPassword', 'The password is incorrect.');

            return;
        }

        try {
            $result = app(SystemUpdateManager::class)->start((int) auth()->id());

            if ($result['bypass_cookie'] !== null) {
                Cookie::queue($result['bypass_cookie']);
            }

            $this->reset('confirmationPassword');

            Notification::make()
                ->title('System update started')
                ->body('Keep this page open while each protected step completes.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $this->notifyUpdateFailure(
                $exception,
                'System update could not start',
                'Refresh this page and review the update status before retrying. If it still fails, ask your server administrator to check the logs.',
            );
        }
    }

    public function continueUpdate(): void
    {
        $run = app(SystemUpdateManager::class)->currentRun();
        if (($run['status'] ?? null) !== 'running') {
            return;
        }

        try {
            $run = app(SystemUpdateManager::class)->processNextStep((int) auth()->id());

            if (($run['status'] ?? null) === 'completed') {
                Cookie::queue(Cookie::forget('laravel_maintenance'));

                Notification::make()
                    ->title('System update completed')
                    ->body('All migrations and final application tasks completed successfully.')
                    ->success()
                    ->persistent()
                    ->send();
            }
        } catch (Throwable $exception) {
            $this->notifyUpdateFailure(
                $exception,
                'System update stopped',
                'Ask your server administrator to check the logs before retrying the update.',
                releaseId: $run['id'] ?? null,
                logLevel: 'critical',
            );
        }
    }

    public function activateLatestCode(): void
    {
        $this->validate([
            'activationPassword' => ['required', 'string'],
        ]);

        if (! Hash::check($this->activationPassword, auth()->user()->password)) {
            $this->addError('activationPassword', 'The password is incorrect.');

            return;
        }

        try {
            app(SystemUpdateManager::class)->activateLatestCode((int) auth()->id());
            $this->reset('activationPassword');

            Notification::make()
                ->title('Latest code activated')
                ->body('Generated routes, views, components, and background workers are now current.')
                ->success()
                ->persistent()
                ->send();

            $this->redirect(Dashboard::getUrl(), navigate: false);
        } catch (Throwable $exception) {
            $this->notifyUpdateFailure(
                $exception,
                'Latest code could not be activated',
                'Ask your server administrator to check the logs and verify the deployed release before trying again.',
            );
        }
    }

    public function restoreApplication(): void
    {
        $this->validate([
            'confirmationPassword' => ['required', 'string'],
        ]);

        if (! Hash::check($this->confirmationPassword, auth()->user()->password)) {
            $this->addError('confirmationPassword', 'The password is incorrect.');

            return;
        }

        try {
            app(SystemUpdateManager::class)->restoreApplication((int) auth()->id());
            Cookie::queue(Cookie::forget('laravel_maintenance'));
            $this->reset('confirmationPassword');

            Notification::make()
                ->title('Application access restored')
                ->body('Investigate the failed migration before starting another update.')
                ->warning()
                ->persistent()
                ->send();
        } catch (Throwable $exception) {
            $this->notifyUpdateFailure(
                $exception,
                'Recovery could not complete',
                'Ask your server administrator to check the logs and restore application access from the server.',
            );
        }
    }

    private function notifyUpdateFailure(
        Throwable $exception,
        string $title,
        string $nextStep,
        ?string $releaseId = null,
        string $logLevel = 'error',
    ): void {
        $notification = Notification::make()->title($title)->persistent();

        if ($exception instanceof SystemUpdateBlocked || $exception instanceof LockTimeoutException) {
            $message = $exception instanceof SystemUpdateBlocked
                ? $exception->staffMessage
                : 'An update or recovery action is in progress. Wait for it to finish, then refresh this page.';
            $notification->body($message)->warning()->send();

            return;
        }

        Log::log($logLevel, $title, [
            'release_id' => $releaseId,
            'initiated_by' => auth()->id(),
            'exception' => $exception,
        ]);
        $notification->body($nextStep)->danger()->send();
    }
}
