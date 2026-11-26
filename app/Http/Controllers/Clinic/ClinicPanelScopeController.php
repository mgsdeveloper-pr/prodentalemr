<?php

namespace App\Http\Controllers\Clinic;

use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateVersion;
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
            'template_version_id' => ['nullable', 'integer'],
        ]);

        $clinicId = $validated['clinic_id'] ?? null;
        abort_unless(! filled($clinicId) || array_key_exists((int) $clinicId, ClinicPanelScope::clinicOptions()), 403);
        $version = null;
        if (isset($validated['template_version_id'])) {
            $user = $request->user();
            abort_unless($clinicId && ($user?->isSaasAdmin() || $user?->canAccessClinicWorkspace()), 403);
            $version = VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                ->where('clinic_id', $clinicId)
                ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
                ->findOrFail($validated['template_version_id']);
            abort_unless($version->clinic?->hasActiveVerificationServices(), 403);
        }
        $changed = (int) $request->session()->get(ClinicPanelScope::SESSION_KEY) !== (int) $clinicId;

        if (filled($clinicId)) {
            $request->session()->put(ClinicPanelScope::SESSION_KEY, (int) $clinicId);
        } else {
            $request->session()->forget(ClinicPanelScope::SESSION_KEY);
        }

        $target = ClinicNavigation::destination($validated['redirect'] ?? $request->session()->pull(ClinicNavigation::RETURN_KEY), $changed);
        if ($version) {
            abort_unless(VerificationQuestionResource::canAccess(), 403);
            $target = VerificationQuestionResource::getUrl('index', [
                'version' => $version->id,
                'form' => $version->form_type === 'short_form' ? 'short_form' : 'full_form',
            ], panel: 'clinic');
        }
        $clinic = ClinicPanelScope::selectedClinic();
        if (! $clinic) {
            $request->session()->put(ClinicNavigation::RETURN_KEY, $target);
            return redirect()->route('clinic.choose-workspace');
        }
        $request->session()->forget(ClinicNavigation::RETURN_KEY);
        return redirect(ClinicNavigation::enter($clinic, $target));
    }
}
