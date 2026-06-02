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
        'price',
        'max_clinics',
        'max_users',
        'included_modules',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'included_modules' => 'array',
            'status' => 'boolean',
        ];
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
