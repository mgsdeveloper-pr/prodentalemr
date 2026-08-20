<?php

namespace App\Support;

use App\Models\Clinic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;

class ClinicTemplateSettingsSupport
{
    public static function sensitiveFields(): array
    {
        return [
            'allow_verification_manager_template_edits',
            'verification_pdf_output_mode',
            'verification_default_form_template',
            'verification_pdf_output_sections',
            'verification_pdf_output_question_ids',
            'default_verification_pdf_preset_id',
        ];
    }

    public static function assertCanChange(Clinic $clinic, array $data): void
    {
        if (! self::hasSensitiveChanges($clinic, $data)) {
            return;
        }

        if (SaasSupportAccess::matchesScope((int) $clinic->organization_id, (int) $clinic->id)) {
            return;
        }

        throw new AuthorizationException('Support Mode is required before changing clinic template settings.');
    }

    public static function hasSensitiveChanges(Clinic $clinic, array $data): bool
    {
        foreach (self::sensitiveFields() as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            if (self::normalizeValue($clinic->getAttribute($field)) !== self::normalizeValue($data[$field])) {
                return true;
            }
        }

        return false;
    }

    public static function changedBeforeValues(Clinic $clinic): array
    {
        return self::normalizeValues(Arr::only($clinic->getOriginal(), self::changedFields($clinic)));
    }

    public static function changedAfterValues(Clinic $clinic): array
    {
        return self::normalizeValues(Arr::only($clinic->getAttributes(), self::changedFields($clinic)));
    }

    protected static function changedFields(Clinic $clinic): array
    {
        return array_values(array_intersect(array_keys($clinic->getChanges()), self::sensitiveFields()));
    }

    protected static function normalizeValues(array $values): array
    {
        foreach ($values as $field => $value) {
            $values[$field] = self::normalizeValue($value);
        }

        return $values;
    }

    protected static function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            ksort($value);

            return array_map(fn (mixed $item): mixed => self::normalizeValue($item), $value);
        }

        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
