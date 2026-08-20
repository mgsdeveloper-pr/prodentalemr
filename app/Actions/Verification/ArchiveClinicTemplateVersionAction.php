<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\User;
use App\Models\VerificationTemplateVersion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class ArchiveClinicTemplateVersionAction
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $user, Clinic $clinic, VerificationTemplateVersion $version): VerificationTemplateVersion
    {
        if (! $user->canManageClinicTemplateSections($clinic)) {
            throw new AuthorizationException('You do not have permission to archive clinic templates.');
        }

        $usedRequestCount = BillingWorkItem::query()
            ->where('verification_template_version_id', $version->getKey())
            ->count();

        if ($version->scope !== VerificationTemplateVersion::SCOPE_CLINIC
            || (int) $version->clinic_id !== (int) $clinic->getKey()
            || $version->is_active
            || $version->status === VerificationTemplateVersion::STATUS_ARCHIVED
            || $usedRequestCount > 0) {
            throw ValidationException::withMessages([
                'template' => 'Only inactive and unused clinic templates can be archived.',
            ]);
        }

        $version->forceFill([
            'status' => VerificationTemplateVersion::STATUS_ARCHIVED,
            'is_active' => false,
            'is_working_draft' => false,
        ])->save();

        return $version->refresh();
    }
}
