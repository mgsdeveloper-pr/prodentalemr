<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ClinicPanelScope;
use App\Support\ClinicWorkspace;
use App\Support\ClinicNavigation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicWorkspaceSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $clinic = $user->shouldBypassClinicScope()
            ? ClinicPanelScope::initializeFor($user)
            : ClinicWorkspace::clinicForUser($user);

        if (! $clinic) {
            if ($request->isMethod('GET')) session([ClinicNavigation::RETURN_KEY => ClinicNavigation::destination($request->fullUrl(), true)]);
            return new \Illuminate\Http\RedirectResponse(route('clinic.choose-workspace'));
        }
        $target = ClinicNavigation::enter($clinic, ClinicNavigation::destination($request->fullUrl()));
        if ($target !== $request->fullUrl() && $request->isMethod('GET')) {
            return new \Illuminate\Http\RedirectResponse($target);
        }

        return $next($request);
    }
}
