<?php

namespace App\Http\Controllers\Clinic;

use App\Support\ClinicPanelScope;
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

        if (filled($clinicId)) {
            $request->session()->put(ClinicPanelScope::SESSION_KEY, (int) $clinicId);
        } else {
            $request->session()->forget(ClinicPanelScope::SESSION_KEY);
        }

        return redirect($validated['redirect'] ?? url('/clinic'));
    }
}
