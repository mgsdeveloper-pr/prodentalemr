<?php

use App\Support\BillingAutomation;
use App\Support\VerificationInboxService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('billing:run-automation', function (BillingAutomation $automation) {
    $result = $automation->run();

    if ($result['skipped'] ?? false) {
        $this->info('Billing automation is disabled in SaaS Settings.');

        return;
    }

    $this->info('Billing automation completed.');
    $this->line('Overdue marked: ' . $result['overdue_marked']);
    $this->line('Pre-due reminders sent: ' . $result['pre_due_sent']);
    $this->line('Overdue reminders sent: ' . $result['overdue_sent']);
})->purpose('Run automated billing reminders and overdue updates.');

Artisan::command('verification-inbox:sync {--force}', function (VerificationInboxService $service) {
    $result = $service->sync((bool) $this->option('force'));

    $this->info($result['message'] ?? 'Inbox sync finished.');
})->purpose('Sync the shared verification inbox into the local mailbox workspace.');

Artisan::command('verification-inbox:cleanup', function (VerificationInboxService $service) {
    $result = $service->cleanup();

    $this->info($result['message'] ?? 'Inbox cleanup finished.');
})->purpose('Apply retention and cleanup rules to the synced verification inbox.');

Schedule::command('billing:run-automation')
    ->dailyAt('01:00');

Schedule::command('verification-inbox:sync')
    ->everyFiveMinutes();

Schedule::command('verification-inbox:cleanup')
    ->dailyAt('02:30');
