<?php

namespace App\Http\Controllers\Admin;

use App\Support\AdminClinicScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminClinicScopeController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'redirect' => ['nullable', 'string'],
        ]);

        $clinicId = $validated['clinic_id'] ?? null;

        if (filled($clinicId)) {
            $request->session()->put(AdminClinicScope::SESSION_KEY, (int) $clinicId);
        } else {
            $request->session()->forget(AdminClinicScope::SESSION_KEY);
        }

        $redirect = $validated['redirect'] ?? url('/verification');

        return redirect($redirect);
    }
}
