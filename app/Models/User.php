<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\PanelPermissionMatrix;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    public const SAAS_ROLE_LABELS = [
        'saas_admin' => 'SaaS Admin',
        'saas_manager' => 'SaaS Manager',
        'saas_user' => 'SaaS User',
    ];

    public const CLINIC_ROLE_LABELS = [
        'clinic_admin' => 'Clinic Admin',
        'clinic_manager' => 'Clinic Manager',
        'doctor' => 'Doctor',
        'receptionist' => 'Receptionist',
        'staff' => 'Staff',
    ];

    public const VERIFICATION_ROLE_LABELS = [
        'verification_admin' => 'Verification Admin',
        'verification_manager' => 'Verification Manager',
        'verification_user' => 'Verification User',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'organization_id',
        'clinic_id',
        'location_id',
        'created_by',
        'status',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'status' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(self::class, 'created_by');
    }

    public function verificationClinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'verification_clinic_user')
            ->withTimestamps();
    }

    public static function saasRoleOptions(): array
    {
        return self::SAAS_ROLE_LABELS;
    }

    public static function clinicRoleOptions(): array
    {
        return self::CLINIC_ROLE_LABELS;
    }

    public static function verificationRoleOptions(): array
    {
        return self::VERIFICATION_ROLE_LABELS;
    }

    public static function verificationPanelAccessRoleOptions(): array
    {
        return [
            ...self::saasRoleOptions(),
            ...self::verificationRoleOptions(),
        ];
    }

    public static function allRoleOptions(): array
    {
        return [
            'SaaS Roles' => self::saasRoleOptions(),
            'Verification Roles' => self::verificationRoleOptions(),
            'Clinic Roles' => self::clinicRoleOptions(),
        ];
    }

    public static function isSaasRole(?string $role): bool
    {
        return filled($role) && array_key_exists($role, self::SAAS_ROLE_LABELS);
    }

    public static function isClinicRole(?string $role): bool
    {
        return filled($role) && array_key_exists($role, self::CLINIC_ROLE_LABELS);
    }

    public static function isVerificationRole(?string $role): bool
    {
        return filled($role) && array_key_exists($role, self::VERIFICATION_ROLE_LABELS);
    }

    public function getPrimaryRoleName(): ?string
    {
        return $this->roles->first()?->name;
    }

    public function getPrimaryRoleLabel(): ?string
    {
        $role = $this->getPrimaryRoleName();

        return self::SAAS_ROLE_LABELS[$role]
            ?? self::VERIFICATION_ROLE_LABELS[$role]
            ?? self::CLINIC_ROLE_LABELS[$role]
            ?? $role;
    }

    public function canManageClinicUsers(): bool
    {
        return $this->status
            && $this->hasAnyRole(['saas_admin', 'clinic_admin', 'clinic_manager']);
    }

    public function isVerificationAdmin(): bool
    {
        return $this->status
            && $this->hasRole('verification_admin');
    }

    public function isVerificationManager(): bool
    {
        return $this->status
            && $this->hasRole('verification_manager');
    }

    public function canManageVerificationUsers(): bool
    {
        return $this->status
            && $this->hasAnyRole(['saas_admin', 'verification_admin', 'verification_manager']);
    }

    public function canManageVerificationRolePermissions(): bool
    {
        return $this->status
            && $this->hasAnyRole(['saas_admin', 'verification_admin']);
    }

    public function hasFullVerificationClinicAccess(): bool
    {
        return $this->status
            && $this->hasAnyRole(['saas_admin', 'verification_admin']);
    }

    public function verificationAccessibleClinicIds(): array
    {
        if (! $this->status || ! $this->canAccessVerificationPanel()) {
            return [];
        }

        if ($this->hasFullVerificationClinicAccess()) {
            return Clinic::query()
                ->whereHas('serviceEnrollments', function ($query): void {
                    $query
                        ->where('status', 'active')
                        ->whereHas('managedBillingService', function ($serviceQuery): void {
                            $serviceQuery->where('category', 'verification');
                        });
                })
                ->pluck('clinics.id')
                ->all();
        }

        return $this->verificationClinics()
            ->whereHas('serviceEnrollments', function ($query): void {
                $query
                    ->where('status', 'active')
                    ->whereHas('managedBillingService', function ($serviceQuery): void {
                        $serviceQuery->where('category', 'verification');
                    });
            })
            ->pluck('clinics.id')
            ->all();
    }

    public function canAccessVerificationClinic(?int $clinicId): bool
    {
        if (! filled($clinicId)) {
            return false;
        }

        if ($this->hasFullVerificationClinicAccess()) {
            return true;
        }

        return in_array((int) $clinicId, $this->verificationAccessibleClinicIds(), true);
    }

    public function assignableVerificationClinicOptions(): array
    {
        if (! $this->canManageVerificationUsers()) {
            return [];
        }

        return Clinic::query()
            ->with('organization')
            ->whereHas('serviceEnrollments', function ($query): void {
                $query
                    ->where('status', 'active')
                    ->whereHas('managedBillingService', function ($serviceQuery): void {
                        $serviceQuery->where('category', 'verification');
                    });
            })
            ->when(
                ! $this->hasFullVerificationClinicAccess(),
                fn ($query) => $query->whereIn('clinics.id', $this->verificationAccessibleClinicIds())
            )
            ->orderBy('clinic_name')
            ->get()
            ->mapWithKeys(fn (Clinic $clinic): array => [
                $clinic->getKey() => trim($clinic->clinic_name . ' - ' . ($clinic->organization?->name ?? '')),
            ])
            ->all();
    }

    public function canAssignVerificationClinics(array $clinicIds): bool
    {
        if ($clinicIds === []) {
            return true;
        }

        $assignableClinicIds = array_map('intval', array_keys($this->assignableVerificationClinicOptions()));

        foreach ($clinicIds as $clinicId) {
            if (! in_array((int) $clinicId, $assignableClinicIds, true)) {
                return false;
            }
        }

        return true;
    }

    public function verificationAssignableRoleOptions(): array
    {
        if (! $this->status) {
            return [];
        }

        if ($this->isSaasAdmin()) {
            return self::verificationRoleOptions();
        }

        if ($this->isVerificationAdmin()) {
            return collect(self::verificationRoleOptions())
                ->except('verification_admin')
                ->all();
        }

        if ($this->isVerificationManager()) {
            return [
                'verification_user' => self::VERIFICATION_ROLE_LABELS['verification_user'],
            ];
        }

        return [];
    }

    public function canAssignVerificationRole(?string $role): bool
    {
        return filled($role) && array_key_exists($role, $this->verificationAssignableRoleOptions());
    }

    public function isSaasAdmin(): bool
    {
        return $this->status
            && $this->hasRole('saas_admin');
    }

    public function isClinicAdmin(): bool
    {
        return $this->status
            && $this->hasRole('clinic_admin');
    }

    public function canManageClinicVerificationSettings(): bool
    {
        return $this->status
            && $this->hasAnyRole([
                'saas_admin',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicModule(string $module): bool
    {
        if (! $this->status || ! $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'saas_user',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
                'staff',
            ])) {
            return false;
        }

        if ($this->isSaasAdmin()) {
            return $this->hasPermissionTo(PanelPermissionMatrix::permissionName('clinic', $module, 'view'));
        }

        return filled($this->organization_id)
            && filled($this->clinic_id)
            && $this->hasPermissionTo(PanelPermissionMatrix::permissionName('clinic', $module, 'view'));
    }

    public function shouldBypassClinicScope(): bool
    {
        return $this->isSaasAdmin();
    }

    public function canPerformClinicModuleAction(string $module, string $action): bool
    {
        return $this->canAccessClinicModule($module)
            && $this->hasPermissionTo(PanelPermissionMatrix::permissionName('clinic', $module, $action));
    }

    public function canAccessSaasModule(string $module): bool
    {
        return $this->status
            && $this->hasAnyRole(['saas_admin', 'saas_manager', 'saas_user'])
            && $this->hasPermissionTo(PanelPermissionMatrix::permissionName('saas', $module, 'view'));
    }

    public function canPerformSaasModuleAction(string $module, string $action): bool
    {
        return $this->canAccessSaasModule($module)
            && $this->hasPermissionTo(PanelPermissionMatrix::permissionName('saas', $module, $action));
    }

    public function canAccessClinicPatients(): bool
    {
        return $this->canAccessClinicModule('patients');
    }

    public function canCreateClinicPatients(): bool
    {
        return $this->canPerformClinicModuleAction('patients', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'saas_user',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicPatients(): bool
    {
        return $this->canCreateClinicPatients();
    }

    public function canDeleteClinicPatients(): bool
    {
        return $this->canPerformClinicModuleAction('patients', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicProviders(): bool
    {
        return $this->canAccessClinicModule('providers');
    }

    public function canCreateClinicProviders(): bool
    {
        return $this->canPerformClinicModuleAction('providers', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canEditClinicProviders(): bool
    {
        return $this->canCreateClinicProviders();
    }

    public function canDeleteClinicProviders(): bool
    {
        return $this->canCreateClinicProviders();
    }

    public function canAccessClinicAppointments(): bool
    {
        return $this->canAccessClinicModule('appointments');
    }

    public function canCreateClinicAppointments(): bool
    {
        return $this->canPerformClinicModuleAction('appointments', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicAppointments(): bool
    {
        return $this->canCreateClinicAppointments();
    }

    public function canDeleteClinicAppointments(): bool
    {
        return $this->canPerformClinicModuleAction('appointments', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'receptionist',
            ]);
    }

    public function canAccessClinicEncounters(): bool
    {
        return $this->canAccessClinicModule('encounters');
    }

    public function canCreateClinicEncounters(): bool
    {
        return $this->canPerformClinicModuleAction('encounters', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
            ]);
    }

    public function canEditClinicEncounters(): bool
    {
        return $this->canCreateClinicEncounters();
    }

    public function canDeleteClinicEncounters(): bool
    {
        return $this->canPerformClinicModuleAction('encounters', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicTreatmentPlans(): bool
    {
        return $this->canAccessClinicModule('treatment_plans');
    }

    public function canCreateClinicTreatmentPlans(): bool
    {
        return $this->canPerformClinicModuleAction('treatment_plans', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicTreatmentPlans(): bool
    {
        return $this->canCreateClinicTreatmentPlans();
    }

    public function canDeleteClinicTreatmentPlans(): bool
    {
        return $this->canPerformClinicModuleAction('treatment_plans', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicServices(): bool
    {
        return $this->canAccessClinicModule('clinic_services');
    }

    public function canCreateClinicServices(): bool
    {
        return $this->canPerformClinicModuleAction('clinic_services', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canEditClinicServices(): bool
    {
        return $this->canCreateClinicServices();
    }

    public function canDeleteClinicServices(): bool
    {
        return $this->canPerformClinicModuleAction('clinic_services', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicDentalCharting(): bool
    {
        return $this->canAccessClinicModule('dental_chart_entries');
    }

    public function canCreateClinicDentalCharting(): bool
    {
        return $this->canPerformClinicModuleAction('dental_chart_entries', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
            ]);
    }

    public function canEditClinicDentalCharting(): bool
    {
        return $this->canCreateClinicDentalCharting();
    }

    public function canDeleteClinicDentalCharting(): bool
    {
        return $this->canPerformClinicModuleAction('dental_chart_entries', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicPerioCharting(): bool
    {
        return $this->canAccessClinicModule('perio_charts');
    }

    public function canCreateClinicPerioCharting(): bool
    {
        return $this->canPerformClinicModuleAction('perio_charts', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicPerioCharting(): bool
    {
        return $this->canCreateClinicPerioCharting();
    }

    public function canDeleteClinicPerioCharting(): bool
    {
        return $this->canPerformClinicModuleAction('perio_charts', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicPatientDocuments(): bool
    {
        return $this->canAccessClinicModule('patient_documents');
    }

    public function canCreateClinicPatientDocuments(): bool
    {
        return $this->canPerformClinicModuleAction('patient_documents', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
                'staff',
            ]);
    }

    public function canEditClinicPatientDocuments(): bool
    {
        return $this->canCreateClinicPatientDocuments();
    }

    public function canDeleteClinicPatientDocuments(): bool
    {
        return $this->canPerformClinicModuleAction('patient_documents', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicInsurancePolicies(): bool
    {
        return $this->canAccessClinicModule('patient_insurance_policies');
    }

    public function canCreateClinicInsurancePolicies(): bool
    {
        return $this->canPerformClinicModuleAction('patient_insurance_policies', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicInsurancePolicies(): bool
    {
        return $this->canCreateClinicInsurancePolicies();
    }

    public function canDeleteClinicInsurancePolicies(): bool
    {
        return $this->canPerformClinicModuleAction('patient_insurance_policies', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicPatientLedger(): bool
    {
        return $this->canAccessClinicModule('patient_ledger_entries');
    }

    public function canCreateClinicPatientLedger(): bool
    {
        return $this->canPerformClinicModuleAction('patient_ledger_entries', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicPatientLedger(): bool
    {
        return $this->canCreateClinicPatientLedger();
    }

    public function canDeleteClinicPatientLedger(): bool
    {
        return $this->canPerformClinicModuleAction('patient_ledger_entries', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicInsuranceClaims(): bool
    {
        return $this->canAccessClinicModule('patient_insurance_claims');
    }

    public function canCreateClinicInsuranceClaims(): bool
    {
        return $this->canPerformClinicModuleAction('patient_insurance_claims', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicInsuranceClaims(): bool
    {
        return $this->canCreateClinicInsuranceClaims();
    }

    public function canDeleteClinicInsuranceClaims(): bool
    {
        return $this->canPerformClinicModuleAction('patient_insurance_claims', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicPatientStatements(): bool
    {
        return $this->canAccessClinicModule('patient_statements');
    }

    public function canCreateClinicPatientStatements(): bool
    {
        return $this->canPerformClinicModuleAction('patient_statements', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicPatientStatements(): bool
    {
        return $this->canCreateClinicPatientStatements();
    }

    public function canDeleteClinicPatientStatements(): bool
    {
        return $this->canPerformClinicModuleAction('patient_statements', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicOperatories(): bool
    {
        return $this->canAccessClinicModule('clinic_operatories');
    }

    public function canCreateClinicOperatories(): bool
    {
        return $this->canPerformClinicModuleAction('clinic_operatories', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'receptionist',
            ]);
    }

    public function canEditClinicOperatories(): bool
    {
        return $this->canCreateClinicOperatories();
    }

    public function canDeleteClinicOperatories(): bool
    {
        return $this->canCreateClinicOperatories();
    }

    public function canAccessClinicConsentForms(): bool
    {
        return $this->canAccessClinicModule('patient_consent_forms');
    }

    public function canCreateClinicConsentForms(): bool
    {
        return $this->canPerformClinicModuleAction('patient_consent_forms', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicConsentForms(): bool
    {
        return $this->canCreateClinicConsentForms();
    }

    public function canDeleteClinicConsentForms(): bool
    {
        return $this->canPerformClinicModuleAction('patient_consent_forms', 'delete')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
            ]);
    }

    public function canAccessClinicManagedServices(): bool
    {
        return $this->canAccessClinicModule('managed_services');
    }

    public function canCreateClinicManagedServices(): bool
    {
        return $this->canPerformClinicModuleAction('managed_services', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'receptionist',
            ]);
    }

    public function canAccessClinicVerificationRequests(): bool
    {
        return $this->canAccessClinicModule('verification_requests');
    }

    public function canCreateClinicVerificationRequests(): bool
    {
        return $this->canPerformClinicModuleAction('verification_requests', 'add')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canEditClinicVerificationRequests(): bool
    {
        return $this->canPerformClinicModuleAction('verification_requests', 'update')
            && $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
            ]);
    }

    public function canAccessSaasRevenueOperations(): bool
    {
        return $this->canAccessVerificationPanel();
    }

    public function canManageVerificationQueue(): bool
    {
        return $this->canAccessVerificationPanel()
            && $this->hasAnyRole(['saas_admin', 'saas_manager', 'verification_admin', 'verification_manager']);
    }

    public function canManageVerificationSettings(): bool
    {
        return $this->status
            && (
                $this->canPerformVerificationModuleAction('settings', 'update')
                || $this->isSaasAdmin()
            );
    }

    public function canManageVerificationNotifications(): bool
    {
        return $this->canManageVerificationSettings();
    }

    public function canAccessVerificationPanel(): bool
    {
        return $this->canAccessSaasModule('verification')
            || $this->canAccessVerificationModule('verification');
    }

    public function canAccessVerificationModule(string $module): bool
    {
        return $this->status
            && $this->hasAnyRole(array_keys(self::verificationPanelAccessRoleOptions()))
            && $this->hasPermissionTo(PanelPermissionMatrix::permissionName('verification', $module, 'view'));
    }

    public function canPerformVerificationModuleAction(string $module, string $action): bool
    {
        return $this->canAccessVerificationModule($module)
            && $this->hasPermissionTo(PanelPermissionMatrix::permissionName('verification', $module, $action));
    }

    public function hasAnyStandardSaasModuleAccess(): bool
    {
        foreach (array_keys(PanelPermissionMatrix::modules('saas')) as $module) {
            if ($module === 'verification') {
                continue;
            }

            if ($this->canAccessSaasModule($module)) {
                return true;
            }
        }

        return false;
    }

    public function canManageSaasRevenueOperations(): bool
    {
        return $this->canManageVerificationQueue();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->status) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->canAccessVerificationPanel(),
            'saas' => $this->hasAnyRole(['saas_admin', 'saas_manager', 'saas_user'])
                && $this->hasAnyStandardSaasModuleAccess(),
            'clinic' => $this->hasAnyRole([
                'saas_admin',
                'saas_manager',
                'saas_user',
                'clinic_admin',
                'clinic_manager',
                'doctor',
                'receptionist',
                'staff',
            ]),
            default => false,
        };
    }
}

// namespace App\Models;

// // use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Database\Factories\UserFactory;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;
// use Spatie\Permission\Traits\HasRoles;

// class User extends Authenticatable
// {
//     /** @use HasFactory<UserFactory> */
//     use HasFactory, Notifiable;

//     /**
//      * The attributes that are mass assignable.
//      *
//      * @var list<string>
//      */
//     protected $fillable = [
//         'name',
//         'email',
//         'password',
//     ];

//     /**
//      * The attributes that should be hidden for serialization.
//      *
//      * @var list<string>
//      */
//     protected $hidden = [
//         'password',
//         'remember_token',
//     ];

//     /**
//      * Get the attributes that should be cast.
//      *
//      * @return array<string, string>
//      */
//     protected function casts(): array
//     {
//         return [
//             'email_verified_at' => 'datetime',
//             'password' => 'hashed',
//         ];
//     }
// }
