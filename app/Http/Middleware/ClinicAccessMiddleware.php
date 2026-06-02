<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClinicAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect('/login');
        }

        if (
            $user->hasRole('saas_admin') ||
            $user->hasRole('saas_manager') ||
            $user->hasRole('saas_user') ||
            $user->hasRole('clinic_admin') ||
            $user->hasRole('clinic_manager') ||
            $user->hasRole('doctor') ||
            $user->hasRole('receptionist') ||
            $user->hasRole('staff')
        ) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to Clinic Panel.');
    }
}


// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Symfony\Component\HttpFoundation\Response;

// class ClinicAccessMiddleware
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  Closure(Request): (Response)  $next
//      */
//     public function handle(Request $request, Closure $next): Response
//     {
//         return $next($request);
//     }
// }
