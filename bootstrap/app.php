<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\EnsureActiveAccount::class]);
        // Laravel validates this short-lived HMAC cookie before allowing the
        // initiating administrator through maintenance mode during an update.
        $middleware->encryptCookies(except: ['laravel_maintenance']);

        $middleware->validateCsrfTokens(except: [
            // Server-to-server bridge; authenticated with a private shared key.
            // The website proxy separately validates its browser session CSRF.
            'website-api/*',
            // Provider signatures are verified before payment processing.
            'website-payments/webhook/*',
            'logout',
            'admin/logout',
            'clinic/logout',
            'saas/logout',
            'stripe/webhook',
            'paypal/webhook',
            'webhooks/telephony/mightycall/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->guest(route('login'))
                ->with('status', 'Your session expired. Please sign in again.');
        });
    })->create();
