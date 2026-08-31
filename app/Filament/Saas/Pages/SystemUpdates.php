<?php

namespace App\Filament\Saas\Pages;

use App\Services\SystemUpdateManager;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
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

    public static function canAccess(): bool
    {
        return auth()->user()?->isSaasAdmin() ?? false;
    }

    public function getUpdateSummary(): array
    {
        $manager = app(SystemUpdateManager::class);

        return [
            'pending' => $manager->pendingMigrations(),
            'checks' => $manager->preflightChecks(),
            'run' => $manager->currentRun(),
            'history' => $manager->history(),
            'production_gates_pass' => $manager->productionGatesPass(),
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
            Notification::make()
                ->title('System update could not start')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
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
            Notification::make()
                ->title('System update stopped')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
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
            Notification::make()
                ->title('Recovery could not complete')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
