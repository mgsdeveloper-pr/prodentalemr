<?php

use App\Models\Subscription;
use App\Models\User;
use App\Services\Notifications\ProductNotificationService;
use App\Support\BillingAutomation;
use App\Support\VerificationInboxService;
use App\Support\VerificationNotificationCenter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
    $this->line('Overdue marked: '.$result['overdue_marked']);
    $this->line('Pre-due reminders sent: '.$result['pre_due_sent']);
    $this->line('Overdue reminders sent: '.$result['overdue_sent']);
})->purpose('Run automated billing reminders and overdue updates.');

Artisan::command('verification-inbox:sync {--force}', function (VerificationInboxService $service) {
    $result = $service->sync((bool) $this->option('force'));

    if (! ($result['ok'] ?? false) && ! ($result['skipped'] ?? false)) {
        app(ProductNotificationService::class)->integrationFailure(
            'verification inbox sync',
            (string) ($result['message'] ?? 'Verification inbox synchronization failed.'),
            idempotencyKey: 'integration.inbox-sync.'.now()->format('YmdHi'),
        );
    }

    $this->info($result['message'] ?? 'Inbox sync finished.');
})->purpose('Sync the shared verification inbox into the local mailbox workspace.');

Artisan::command('verification-inbox:cleanup', function (VerificationInboxService $service) {
    $result = $service->cleanup();

    $this->info($result['message'] ?? 'Inbox cleanup finished.');
})->purpose('Apply retention and cleanup rules to the synced verification inbox.');

Artisan::command('verification:sync-sla-notifications', function () {
    $verificationUsers = User::query()
        ->where('status', true)
        ->whereHas('roles', fn ($query) => $query->whereIn('name', ['verification_admin', 'verification_manager', 'verification_user']))
        ->get();

    foreach ($verificationUsers as $user) {
        VerificationNotificationCenter::syncSlaAlertsForUser($user, 'verification');
    }

    $clinicUsers = User::query()
        ->where('status', true)
        ->whereNotNull('organization_id')
        ->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::clinicRoleOptions())))
        ->get();

    foreach ($clinicUsers as $user) {
        VerificationNotificationCenter::syncSlaAlertsForUser($user, 'clinic', $user->clinic_id);
    }

    $this->info('Verification SLA notifications synchronized.');
})->purpose('Create scoped and deduplicated verification SLA notifications.');

Artisan::command('notifications:sync-business-events', function (ProductNotificationService $notifications) {
    foreach ([7, 3, 1, 0] as $daysRemaining) {
        Subscription::query()
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', today()->addDays($daysRemaining))
            ->whereNotIn('status', ['cancelled', 'expired'])
            ->get()
            ->each(fn (Subscription $subscription) => $notifications->trialEnding($subscription, $daysRemaining));
    }

    $this->info('Business notification events synchronized.');
})->purpose('Create deduplicated trial and subscription lifecycle notifications.');

Artisan::command('prodental:stability-check', function () {
    $this->info('ProDental database/login stability check');
    $this->line('Environment: '.app()->environment());
    $this->line('Database: '.(string) config('database.connections.mysql.database'));
    $this->line('Session driver: '.(string) config('session.driver'));
    $this->line('Cache store: '.(string) config('cache.default'));
    $this->newLine();

    $this->table(['Check', 'Result'], [
        ['Users', DB::table('users')->count()],
        ['admin@mgs.com active', DB::table('users')->where('email', 'admin@mgs.com')->whereNull('deleted_at')->where('status', true)->exists() ? 'yes' : 'no'],
        ['demo.verify.manager active', DB::table('users')->where('email', 'demo.verify.manager@prodental.test')->whereNull('deleted_at')->where('status', true)->exists() ? 'yes' : 'no'],
        ['Organizations', DB::table('organizations')->count()],
        ['Clinics', DB::table('clinics')->count()],
        ['Patients', DB::table('patients')->count()],
        ['Verification requests', DB::table('billing_work_items')->count()],
    ]);
})->purpose('Show non-destructive local database and login stability status.');

Artisan::command('prodental:production-check', function () {
    $checks = [
        ['Production environment', app()->environment('production'), 'Set APP_ENV=production.'],
        ['Debug mode disabled', ! config('app.debug'), 'Set APP_DEBUG=false.'],
        ['Application key present', filled(config('app.key')), 'Generate and securely store APP_KEY.'],
        ['HTTPS application URL', str_starts_with((string) config('app.url'), 'https://'), 'Set APP_URL to the HTTPS production URL.'],
        ['Public registration closed', ! config('prodental.public_registration'), 'Keep PRODENTAL_PUBLIC_REGISTRATION=false until approved onboarding is released.'],
        ['Persistent database', config('database.default') !== 'sqlite', 'Use the managed production database connection.'],
        ['Asynchronous queue', ! in_array(config('queue.default'), ['sync', 'null'], true), 'Use database, Redis, SQS, or another asynchronous queue.'],
        ['Production mail transport', ! in_array(config('mail.default'), ['log', 'array'], true), 'Configure and test a production mail provider.'],
        ['Session encryption', (bool) config('session.encrypt'), 'Set SESSION_ENCRYPT=true.'],
        ['Secure session cookie', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true.'],
    ];

    $warnings = [
        ['Private files use local storage', config('filesystems.default') === 'local', 'Confirm encrypted volume backups and restore testing, or configure private object storage.'],
        ['Local cache store', in_array(config('cache.default'), ['file', 'array'], true), 'Use a shared cache for multi-instance deployment.'],
    ];

    $this->info('ProDental production readiness');
    $this->table(['Gate', 'Status', 'Required action'], collect($checks)->map(fn (array $check) => [
        $check[0],
        $check[1] ? 'PASS' : 'FAIL',
        $check[1] ? '-' : $check[2],
    ])->all());

    $activeWarnings = collect($warnings)->filter(fn (array $warning) => $warning[1]);
    if ($activeWarnings->isNotEmpty()) {
        $this->warn('Deployment warnings');
        $this->table(['Warning', 'Action'], $activeWarnings->map(fn (array $warning) => [$warning[0], $warning[2]])->all());
    }

    $failed = collect($checks)->contains(fn (array $check) => ! $check[1]);
    $this->{$failed ? 'error' : 'info'}($failed
        ? 'Production gates failed. Do not deploy until every FAIL is resolved.'
        : 'All code-verifiable production gates passed.');

    return $failed ? 1 : 0;
})->purpose('Fail fast when production environment and security gates are incomplete.');

Schedule::command('billing:run-automation')
    ->dailyAt('01:00');

Schedule::command('verification-inbox:sync')
    ->everyFiveMinutes();

Schedule::command('verification-inbox:cleanup')
    ->dailyAt('02:30');

Schedule::command('verification:sync-sla-notifications')
    ->hourly();

Schedule::command('notifications:sync-business-events')
    ->dailyAt('08:00');
