<?php

namespace App\Http\Controllers\Clinic;

use App\Support\ClinicPanelScope;
use App\Support\ClinicNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClinicPanelScopeController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'redirect' => ['nullable', 'string'],
        ]);

        $clinicId = $validated['clinic_id'] ?? null;
        abort_unless(! filled($clinicId) || array_key_exists((int) $clinicId, ClinicPanelScope::clinicOptions()), 403);
        $changed = (int) $request->session()->get(ClinicPanelScope::SESSION_KEY) !== (int) $clinicId;

        if (filled($clinicId)) {
            $request->session()->put(ClinicPanelScope::SESSION_KEY, (int) $clinicId);
        } else {
            $request->session()->forget(ClinicPanelScope::SESSION_KEY);
        }

        $target = ClinicNavigation::destination($validated['redirect'] ?? $request->session()->pull(ClinicNavigation::RETURN_KEY), $changed);
        $clinic = ClinicPanelScope::selectedClinic();
        if (! $clinic) {
            $request->session()->put(ClinicNavigation::RETURN_KEY, $target);
            return redirect()->route('clinic.choose-workspace');
        }
        $request->session()->forget(ClinicNavigation::RETURN_KEY);
        return redirect(ClinicNavigation::enter($clinic, $target));
    }
}
