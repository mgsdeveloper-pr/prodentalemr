<?php

namespace App\Actions\Verification;

use App\Models\Clinic;
use App\Models\User;
use App\Models\VerificationTemplateVersion;
use App\Support\VerificationTemplateLifecycleAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

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

        return DB::transaction(function () use ($user, $clinic, $version): VerificationTemplateVersion {
            // Share the activation lock so an active slot cannot be archived concurrently.
            Clinic::whereKey($clinic->id)->lockForUpdate()->firstOrFail();
            $locked = VerificationTemplateVersion::whereKey($version->id)->lockForUpdate()->firstOrFail();
            if ($locked->scope !== VerificationTemplateVersion::SCOPE_CLINIC
                || (int) $locked->clinic_id !== (int) $clinic->id
                || $locked->is_active || $locked->active_full_form || $locked->active_short_form
                || $locked->status !== VerificationTemplateVersion::STATUS_PUBLISHED) {
                throw ValidationException::withMessages([
                    'template' => 'Only published clinic templates inactive in both Full and Short can be archived.',
                ]);
            }

            $before = VerificationTemplateLifecycleAudit::state($locked);
            $locked->forceFill(['status' => VerificationTemplateVersion::STATUS_ARCHIVED,
                'is_working_draft' => false])->save();
            VerificationTemplateLifecycleAudit::record($user, $locked, 'archive', $before, VerificationTemplateLifecycleAudit::state($locked));

            return $locked;
        });
    }
}
