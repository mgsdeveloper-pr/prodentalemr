<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SaasAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect('/login');
        }

        if ($user->hasAnyRole(['saas_admin', 'saas_manager', 'saas_user'])) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to SaaS Panel.');
    }
}
