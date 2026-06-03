<?php

namespace App\Providers;

use App\Support\SaasMailSettings;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            // Use Notification Centre SMTP settings as the primary runtime mail
            // source whenever they are fully configured, while keeping .env as a
            // safe fallback during install or partial setup.
            SaasMailSettings::applyRuntimeDefaultsFromSettings();
        } catch (Throwable) {
            // Fall back silently to the base Laravel mail configuration.
        }
    }
}
