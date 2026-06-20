<?php

namespace App\Models;

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'plan_code',
        'price',
        'plan_type',
        'workspace_mode',
        'max_clinics',
        'max_users',
        'included_modules',
        'included_features',
        'plan_limits',
        'managed_services_allowed',
        'trial_days',
        'demo_mode_available',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'included_modules' => 'array',
            'included_features' => 'array',
            'plan_limits' => 'array',
            'managed_services_allowed' => 'boolean',
            'demo_mode_available' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public const PLAN_TYPE_PMS = 'pms';

    public const PLAN_TYPE_VERIFICATION = 'verification';

    public const PLAN_TYPE_PMS_VERIFICATION = 'pms_verification';

    public const WORKSPACE_AUTO = 'auto';

    public const WORKSPACE_CHOOSE = 'choose';

    public const WORKSPACE_PMS = 'clinic';

    public const WORKSPACE_VERIFICATION = 'verification';

    public static function planTypeOptions(): array
    {
        return [
            self::PLAN_TYPE_PMS => 'Clinic Operations Module',
            self::PLAN_TYPE_VERIFICATION => 'Verification Module',
            self::PLAN_TYPE_PMS_VERIFICATION => 'Complete Module Suite',
        ];
    }

    public static function workspaceModeOptions(): array
    {
        return [
            self::WORKSPACE_AUTO => 'Auto-route by plan',
            self::WORKSPACE_CHOOSE => 'Choose Workspace',
            self::WORKSPACE_PMS => 'Clinic Operations',
            self::WORKSPACE_VERIFICATION => 'Verification',
        ];
    }

    public static function featureOptions(): array
    {
        return [
            'appointment_import' => 'Appointment Import',
            'mailbox' => 'Mailbox',
            'clinic_inbox' => 'Clinic Inbox',
            'document_center' => 'Document Center',
            'audit_export' => 'Audit Export',
            'bulk_actions' => 'Bulk Actions',
            'advanced_reports' => 'Advanced Reports',
            'request_response' => 'Request & Response',
            'portal_credentials' => 'Portal Credentials',
        ];
    }

    public static function defaultIncludedFeatures(): array
    {
        return array_keys(static::featureOptions());
    }

    public static function defaultPlanLimits(): array
    {
        return [
            'storage_mb' => 10240,
            'monthly_verifications' => null,
            'mailbox_storage_mb' => 1024,
            'import_rows' => 5000,
            'attachment_mb' => 25,
        ];
    }

    public function includesPms(): bool
    {
        return in_array($this->plan_type, [self::PLAN_TYPE_PMS, self::PLAN_TYPE_PMS_VERIFICATION], true);
    }

    public function includesVerification(): bool
    {
        return in_array($this->plan_type, [self::PLAN_TYPE_VERIFICATION, self::PLAN_TYPE_PMS_VERIFICATION], true);
    }

    public function allowsFeature(string $feature): bool
    {
        return in_array($feature, $this->included_features ?? [], true);
    }

    public function limitValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->plan_limits ?? [], $key, $default);
    }

    public static function clinicModuleOptions(): array
    {
        return PanelPermissionMatrix::modules('clinic');
    }

    public static function defaultIncludedModules(): array
    {
        return array_keys(static::clinicModuleOptions());
    }

    public static function clinicModuleGroups(): array
    {
        return [
            'Practice Administration' => [
                'description' => 'Core clinic administration, staff access, and internal setup controls.',
                'modules' => [
                    'users',
                    'roles_permissions',
                    'clinic_services',
                    'clinic_operatories',
                ],
            ],
            'Patient & Records' => [
                'description' => 'Patient registration, records, consent, and document management.',
                'modules' => [
                    'patients',
                    'patient_documents',
                    'patient_consent_forms',
                ],
            ],
            'Clinical Workflow' => [
                'description' => 'Scheduling, clinical documentation, treatment planning, and charting workflows.',
                'modules' => [
                    'providers',
                    'appointments',
                    'encounters',
                    'treatment_plans',
                    'dental_chart_entries',
                    'perio_charts',
                ],
            ],
            'Insurance & Billing' => [
                'description' => 'Insurance policies, verifications, claims, statements, and patient financial activity.',
                'modules' => [
                    'patient_insurance_policies',
                    'verification_requests',
                    'patient_insurance_claims',
                    'patient_ledger_entries',
                    'patient_statements',
                ],
            ],
        ];
    }

    public function getIncludedModuleLabelsAttribute(): array
    {
        return collect($this->included_modules ?? [])
            ->map(fn (string $module): string => static::clinicModuleOptions()[$module] ?? str($module)->replace('_', ' ')->headline())
            ->values()
            ->all();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
