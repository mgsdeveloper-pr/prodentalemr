<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ClinicWorkspace;
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

        $clinic = ClinicWorkspace::clinicForUser($user);

        if (! ClinicWorkspace::needsChoice($clinic)) {
            $workspace = ClinicWorkspace::defaultWorkspace($clinic);

            if ($workspace) {
                ClinicWorkspace::select($workspace);
            }

            return $next($request);
        }

        $selected = ClinicWorkspace::selected();

        if (! $selected || ! ClinicWorkspace::canUse($selected, $clinic)) {
            return redirect()->route('clinic.choose-workspace');
        }

        if ($request->is('clinic') && $selected === ClinicWorkspace::VERIFICATION) {
            return redirect(ClinicWorkspace::homeUrl(ClinicWorkspace::VERIFICATION));
        }

        return $next($request);
    }
}
