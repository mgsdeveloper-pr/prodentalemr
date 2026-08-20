<?php

namespace App\Support;

use App\Models\PortalCredential;
use Illuminate\Support\Arr;

class PortalCredentialSupportAudit
{
    public static function register(): void
    {
        PortalCredential::created(function (PortalCredential $credential): void {
            if (! self::shouldAudit($credential)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                'support_portal_credential_created',
                $credential,
                [],
                self::sanitizeValues(Arr::only($credential->getAttributes(), self::auditedFields()))
            );
        });

        PortalCredential::updated(function (PortalCredential $credential): void {
            if (! self::shouldAudit($credential)) {
                return;
            }

            $changedFields = array_values(array_intersect(array_keys($credential->getChanges()), self::auditedFields()));

            if ($changedFields === []) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                'support_portal_credential_updated',
                $credential,
                self::sanitizeValues(Arr::only($credential->getOriginal(), $changedFields)),
                self::sanitizeValues(Arr::only($credential->getAttributes(), $changedFields))
            );
        });

        PortalCredential::deleted(function (PortalCredential $credential): void {
            if (! self::shouldAudit($credential)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                'support_portal_credential_deleted',
                $credential,
                self::sanitizeValues(Arr::only($credential->getOriginal(), self::auditedFields())),
                ['deleted_at' => optional($credential->deleted_at)->toIso8601String()]
            );
        });

        PortalCredential::restored(function (PortalCredential $credential): void {
            if (! self::shouldAudit($credential)) {
                return;
            }

            SaasSupportAccess::recordModelEvent(
                'support_portal_credential_restored',
                $credential,
                ['deleted_at' => $credential->getOriginal('deleted_at')],
                ['deleted_at' => null]
            );
        });
    }

    protected static function shouldAudit(PortalCredential $credential): bool
    {
        return SaasSupportAccess::matchesScope((int) $credential->organization_id, (int) $credential->clinic_id);
    }

    protected static function auditedFields(): array
    {
        return [
            'organization_id',
            'clinic_id',
            'portal_name',
            'portal_category',
            'login_url',
            'username',
            'password',
            'account_reference',
            'support_contact',
            'registration_qa_notes',
            'general_notes',
            'notes',
            'mfa_required',
            'mfa_method',
            'is_active',
            'visible_to_clinic',
        ];
    }

    protected static function sanitizeValues(array $values): array
    {
        foreach (['username', 'password', 'account_reference'] as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = filled($values[$field]) ? '[changed]' : '[blank]';
            }
        }

        return $values;
    }
}
