<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;

class PanelAuthenticateRedirect extends Authenticate
{
    public function handle($request, \Closure $next, ...$guards)
    {
        return app(EnsureActiveAccount::class)->handle(
            $request,
            fn ($request) => parent::handle($request, $next, ...$guards),
        );
    }

    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
