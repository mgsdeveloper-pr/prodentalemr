<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\VerificationTemplateVersion;

class VerificationTemplateLifecycleAudit
{
    public static function state(VerificationTemplateVersion $version): array
    {
        return [
            'template_version_id' => $version->id,
            'scope' => $version->scope,
            'organization_id' => $version->organization_id,
            'clinic_id' => $version->clinic_id,
            'template_key' => $version->template_key,
            'version_number' => $version->version_number,
            'form_type' => $version->form_type,
            'status' => $version->status,
            'clinic_visibility' => $version->clinic_visibility,
            'active_full_form' => (bool) $version->active_full_form,
            'active_short_form' => (bool) $version->active_short_form,
            'is_working_draft' => (bool) $version->is_working_draft,
            'published_at' => $version->published_at?->toIso8601String(),
        ];
    }

    public static function record(User $actor, VerificationTemplateVersion $version, string $action, array $before, array $after): void
    {
        (new AuditLog)->forceFill([
            'user_id' => $actor->id,
            'organization_id' => $version->organization_id,
            'clinic_id' => $version->clinic_id,
            'module' => 'verification_templates',
            'action' => $action,
            'old_values' => json_encode($before, JSON_THROW_ON_ERROR),
            'new_values' => json_encode($after + ['actor_user_id' => $actor->id], JSON_THROW_ON_ERROR),
        ])->save();
    }
}
