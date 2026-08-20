<?php

namespace App\Actions\Verification;

use App\Models\Clinic;
use App\Models\User;
use App\Models\VerificationTemplateVersion;
use App\Support\VerificationTemplateVersionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class PublishClinicTemplateDraftAction
{
    public function __construct(
        protected VerificationTemplateVersionService $versions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $user, Clinic $clinic, VerificationTemplateVersion $draft, array $data = []): VerificationTemplateVersion
    {
        if (! $user->canManageClinicTemplateSections($clinic)) {
            throw new AuthorizationException('You do not have permission to publish clinic template drafts.');
        }

        if ($draft->scope !== VerificationTemplateVersion::SCOPE_CLINIC
            || (int) $draft->clinic_id !== (int) $clinic->getKey()
            || $draft->status !== VerificationTemplateVersion::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'template' => 'Only a draft clinic template for the selected clinic can be published.',
            ]);
        }

        return $this->versions->publishDraft(
            $draft,
            $data['version_name'] ?? null,
            $data['change_description'] ?? null,
        );
    }
}
