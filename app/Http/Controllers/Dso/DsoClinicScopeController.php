<?php

namespace App\Http\Controllers\Dso;

use App\Support\DsoWorkspaceScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DsoClinicScopeController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'redirect' => ['nullable', 'string'],
        ]);

        $clinicId = $validated['clinic_id'] ?? null;

        if (filled($clinicId)) {
            abort_unless(DsoWorkspaceScope::canSelect($request->user(), (int) $clinicId), 403);

            $request->session()->put(DsoWorkspaceScope::SESSION_KEY, (int) $clinicId);
        } else {
            $request->session()->forget(DsoWorkspaceScope::SESSION_KEY);
        }

        return redirect($validated['redirect'] ?? url('/dso/clinics'));
    }
}
