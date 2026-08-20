<?php

namespace App\Actions\Verification;

use App\Models\Clinic;
use App\Models\User;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateVersion;
use App\Support\VerificationTemplateVersionService;
use Illuminate\Auth\Access\AuthorizationException;

class CreateClinicTemplateDraftAction
{
    public function __construct(
        protected VerificationTemplateVersionService $versions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function execute(User $user, Clinic $clinic, ?VerificationTemplateVersion $source, array $data): VerificationTemplateVersion
    {
        if (! $user->canManageClinicTemplateSections($clinic)) {
            throw new AuthorizationException('You do not have permission to create clinic template drafts.');
        }

        return $this->versions->createDraftFromSource($source, [
            'template_key' => VerificationFormQuestion::defaultTemplateKey(),
            'scope' => VerificationTemplateVersion::SCOPE_CLINIC,
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->getKey(),
            'name' => trim((string) ($data['template_name'] ?? 'Clinic Template Draft')),
            'form_type' => $data['form_type'] ?? VerificationTemplateVersion::FORM_TYPE_BOTH,
            'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
            'starting_point' => $data['starting_point'] ?? 'active',
        ]);
    }
}
