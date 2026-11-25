<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WebsiteRegistrationBridge
{
    public function handle(Request $request, Closure $next)
    {
        $secret = (string) config('website_registration.bridge_secret');
        abort_unless(config('website_registration.enabled'), 503, 'Website registration is not yet available.');
        abort_unless(strlen($secret) >= 32 && hash_equals($secret, (string) $request->header('X-Website-Key')), 401);
        return $next($request);
    }
}
