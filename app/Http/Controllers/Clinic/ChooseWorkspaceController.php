<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Support\ClinicWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChooseWorkspaceController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $clinic = ClinicWorkspace::clinicForUser();

        if (! ClinicWorkspace::needsChoice($clinic)) {
            $workspace = ClinicWorkspace::defaultWorkspace($clinic);

            if ($workspace) {
                ClinicWorkspace::select($workspace);

                return redirect(ClinicWorkspace::homeUrl($workspace));
            }
        }

        return view('clinic.choose-workspace', [
            'clinic' => $clinic,
            'canUseVerification' => ClinicWorkspace::canUse(ClinicWorkspace::VERIFICATION, $clinic),
            'canUseClinicPms' => ClinicWorkspace::canUse(ClinicWorkspace::CLINIC_PMS, $clinic),
        ]);
    }

    public function switch(string $workspace): RedirectResponse
    {
        $clinic = ClinicWorkspace::clinicForUser();

        abort_unless(ClinicWorkspace::canUse($workspace, $clinic), 403);

        ClinicWorkspace::select($workspace);

        return redirect(ClinicWorkspace::homeUrl($workspace));
    }
}
