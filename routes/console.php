<?php

use App\Support\BillingAutomation;
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

Schedule::command('billing:run-automation')
    ->dailyAt('01:00');
