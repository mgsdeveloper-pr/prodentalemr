<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Schemas;

use App\Models\BillingWorkItem;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\VerificationPlanSnapshot;
use App\Models\VerificationProfile;
use App\Support\ClinicPanelScope;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class VerificationRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $scopedClinic = ClinicPanelScope::selectedClinic();
        $scopedClinicId = $scopedClinic?->id ?? $user?->clinic_id;
        $scopedOrganizationId = $scopedClinic?->organization_id ?? $user?->organization_id;
        $needsClinicSelection = $user?->shouldBypassClinicScope() && blank($scopedClinicId);

        return $schema
            ->columns(1)
            ->components([
                Hidden::make('organization_id')->default($scopedOrganizationId),
                Hidden::make('clinic_id')->default($scopedClinicId),
                Hidden::make('created_by')->default($user?->id),
                Hidden::make('source')->default('clinic_self_service'),
                Hidden::make('status')->default('pending'),
                Hidden::make('outcome_status')->default('pending'),
                Hidden::make('priority')->default('normal'),
                Hidden::make('pms_sync_status')->default('pending'),
                Hidden::make('writeback_status')->default('not_requested'),
                Hidden::make('managed_billing_service_id'),
                Hidden::make('client_service_enrollment_id'),
                Hidden::make('reference_number')
                    ->default(fn (): string => BillingWorkItem::generateReferenceNumber()),
                Hidden::make('patient_id'),
                Hidden::make('patient_insurance_policy_id'),
                Hidden::make('vf_requested_by_name')->default($user?->name),
                Hidden::make('vf_requested_by_role_slug')->default($user?->getPrimaryRoleName()),
                Hidden::make('vf_requested_from_panel')->default('clinic'),

                Grid::make(1)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('vf_form_type')
                            ->label('Verification Form')
                            ->options(VerificationProfile::FORM_TYPE_OPTIONS)
                            ->default('full_form')
                            ->native(false)
                            ->required(),
                    ]),

                Section::make('Patient 1')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('import_patient_id')
                            ->label('Import Patient')
                            ->helperText($needsClinicSelection ? 'Select a clinic from the Workspace menu first.' : 'Optional. Pick an existing patient to auto-fill demographics and insurance.')
                            ->options(fn (Get $get): array => static::patientImportOptions(
                                $scopedOrganizationId,
                                $scopedClinicId,
                                filled($get('location_id')) ? (int) $get('location_id') : null,
                            ))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($scopedOrganizationId, $scopedClinicId): void {
                                if (blank($state) || blank($scopedOrganizationId) || blank($scopedClinicId)) {
                                    return;
                                }

                                static::applyImportedPatient(
                                    (int) $state,
                                    $get,
                                    $set,
                                    (int) $scopedOrganizationId,
                                    (int) $scopedClinicId,
                                );
                            })
                            ->columnSpanFull(),
                        Select::make('import_appointment_id')
                            ->label('Import Appointment')
                            ->helperText($needsClinicSelection ? 'Select a clinic from the Workspace menu first.' : 'Optional. Pull location, provider, patient, date, and time from an existing appointment.')
                            ->options(fn (Get $get): array => static::appointmentImportOptions(
                                $scopedOrganizationId,
                                $scopedClinicId,
                                filled($get('location_id')) ? (int) $get('location_id') : null,
                            ))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($scopedOrganizationId, $scopedClinicId): void {
                                if (blank($state) || blank($scopedOrganizationId) || blank($scopedClinicId)) {
                                    return;
                                }

                                static::applyImportedAppointment(
                                    (int) $state,
                                    $get,
                                    $set,
                                    (int) $scopedOrganizationId,
                                    (int) $scopedClinicId,
                                );
                            })
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                Section::make('Appointment Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('location_id')
                                                    ->label('Location')
                                                    ->helperText($needsClinicSelection ? 'Select a clinic from the Workspace menu first.' : null)
                                                    ->options(fn (): array => filled($scopedClinicId)
                                                        ? Location::query()
                                                            ->where('clinic_id', $scopedClinicId)
                                                            ->orderBy('location_name')
                                                            ->pluck('location_name', 'id')
                                                            ->all()
                                                        : [])
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->afterStateUpdated(function (?string $state, Set $set) use ($user): void {
                                                        $location = filled($state)
                                                            ? Location::query()
                                                                ->where('clinic_id', ClinicPanelScope::selectedClinicId() ?? $user?->clinic_id)
                                                                ->find($state)
                                                            : null;

                                                        $set('provider_id', null);
                                                        $set('patient_id', null);
                                                        $set('patient_insurance_policy_id', null);
                                                        $set('managed_billing_service_id', null);
                                                        $set('client_service_enrollment_id', null);
                                                        $set('source', 'clinic_self_service');

                                                        if (! $location) {
                                                            return;
                                                        }

                                                        static::applyVerificationRouting($location, $set);
                                                    })
                                                    ->required()
                                                    ->columnSpanFull(),
                                                Checkbox::make('priority_flag')
                                                    ->label('Mark as urgent')
                                                    ->live()
                                                    ->dehydrated(false)
                                                    ->afterStateUpdated(fn (?bool $state, Set $set) => $set('priority', $state ? 'urgent' : 'normal')),
                                                Select::make('provider_id')
                                                    ->label('Provider')
                                                    ->helperText($needsClinicSelection ? 'Select a clinic from the Workspace menu first.' : null)
                                                    ->options(fn (Get $get): array => filled($scopedOrganizationId) && filled($scopedClinicId)
                                                        ? Provider::query()
                                                            ->with('user')
                                                            ->where('organization_id', $scopedOrganizationId)
                                                            ->where('clinic_id', $scopedClinicId)
                                                            ->when(filled($get('location_id')), fn ($query) => $query->where('location_id', $get('location_id')))
                                                            ->orderBy('id')
                                                            ->get()
                                                            ->mapWithKeys(fn (Provider $provider): array => [$provider->id => $provider->display_name])
                                                            ->all()
                                                        : [])
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->columnSpanFull(),
                                                DatePicker::make('vf_appointment_date')
                                                    ->label('Appointment Date')
                                                    ->native(false)
                                                    ->required(),
                                                TimePicker::make('vf_appointment_time')
                                                    ->label('Appointment Time')
                                                    ->seconds(false),
                                                TextInput::make('vf_pms_id')
                                                    ->label('PMS ID')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (?string $state, Get $get, Set $set) => static::applyPatientLookup($get, $set, ClinicPanelScope::selectedOrganizationId() ?? $user?->organization_id, ClinicPanelScope::selectedClinicId() ?? $user?->clinic_id, 'pms'))
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),
                                                Placeholder::make('matched_patient_hint')
                                                    ->label('')
                                                    ->content(fn (Get $get): ?string => static::matchedPatientHint($get))
                                                    ->hidden(fn (Get $get): bool => blank(static::matchedPatientHint($get)))
                                                    ->columnSpanFull(),
                                                Checkbox::make('vf_is_pre_registered')
                                                    ->label('Pre-registered'),
                                            ]),
                                    ]),
                                Section::make('Patient Information')
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                TextInput::make('vf_patient_full_name')
                                                    ->label('Full Name')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($user): void {
                                                        static::applyPatientLookup($get, $set, ClinicPanelScope::selectedOrganizationId() ?? $user?->organization_id, ClinicPanelScope::selectedClinicId() ?? $user?->clinic_id, 'name_dob');
                                                        static::syncSubscriberFromPatient($get, $set);
                                                    })
                                                    ->required()
                                                    ->maxLength(255),
                                                DatePicker::make('vf_patient_dob')
                                                    ->label('Date of Birth')
                                                    ->native(false)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($user): void {
                                                        static::applyPatientLookup($get, $set, ClinicPanelScope::selectedOrganizationId() ?? $user?->organization_id, ClinicPanelScope::selectedClinicId() ?? $user?->clinic_id, 'name_dob');
                                                        static::syncSubscriberFromPatient($get, $set);
                                                    })
                                                    ->required(),
                                                TextInput::make('vf_patient_identifier')
                                                    ->label('SSN / Member ID')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (?string $state, Get $get, Set $set) => static::applyPatientLookup($get, $set, ClinicPanelScope::selectedOrganizationId() ?? $user?->organization_id, ClinicPanelScope::selectedClinicId() ?? $user?->clinic_id, 'member'))
                                                    ->maxLength(255),
                                                TextInput::make('vf_patient_zip')
                                                    ->label('ZIP')
                                                    ->maxLength(20),
                                            ]),
                                    ]),
                            ]),
                    ]),

                Section::make('Insurance Plans')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Checkbox::make('vf_subscriber_same_as_patient')
                                    ->label('Subscriber same as Patient')
                                    ->helperText('Auto-copy the patient name and DOB into subscriber details for the plan below.')
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function (?bool $state, Get $get, Set $set): void {
                                        if ($state) {
                                            static::syncSubscriberFromPatient($get, $set);

                                            if (blank($get('vf_insured_relation'))) {
                                                $set('vf_insured_relation', 'self');
                                            }
                                        }
                                    }),
                                Select::make('vf_insured_relation')
                                    ->label('Relationship to Patient')
                                    ->options([
                                        'self' => 'Self',
                                        'child' => 'Child',
                                        'spouse' => 'Spouse',
                                        'other' => 'Other',
                                    ])
                                    ->native(false)
                                    ->searchable(),
                            ]),
                        Repeater::make('verification_plan_snapshots')
                            ->label('')
                            ->default([
                                ['plan_priority' => 'primary'],
                            ])
                            ->minItems(1)
                            ->addActionLabel('Add Primary Plan')
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => match ($state['plan_priority'] ?? 'primary') {
                                'secondary' => 'Secondary Plan',
                                'tertiary' => 'Tertiary Plan',
                                default => 'Primary Plan',
                            })
                            ->columnSpanFull()
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('plan_priority')
                                            ->label('Plan Type')
                                            ->options(VerificationPlanSnapshot::PRIORITY_OPTIONS)
                                            ->default('primary')
                                            ->native(false)
                                            ->required(),
                                        TextInput::make('payer_name')
                                            ->label('Insurance Provider')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('member_id')
                                            ->label('Member ID')
                                            ->maxLength(255),
                                        TextInput::make('group_number')
                                            ->label('Group Number')
                                            ->maxLength(255),
                                        TextInput::make('subscriber_name')
                                            ->label('Subscriber Name')
                                            ->maxLength(255),
                                        DatePicker::make('subscriber_dob')
                                            ->label('Subscriber DOB')
                                            ->native(false),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected static function applyPatientLookup(Get $get, Set $set, ?int $organizationId, ?int $clinicId, string $mode): void
    {
        $locationId = $get('location_id');

        if (blank($organizationId) || blank($clinicId) || blank($locationId)) {
            return;
        }

        $patient = match ($mode) {
            'pms' => static::findPatientByPmsId($get, (int) $organizationId, (int) $clinicId, (int) $locationId),
            'member' => static::findPatientByMemberId($get, (int) $organizationId, (int) $clinicId, (int) $locationId),
            'name_dob' => static::findPatientByNameAndDob($get, (int) $organizationId, (int) $clinicId, (int) $locationId),
            default => null,
        };

        if (! $patient) {
            $set('patient_id', null);
            $set('patient_insurance_policy_id', null);

            return;
        }

        static::applyMatchedPatient($patient, $set, $get);
    }

    protected static function patientImportOptions(?int $organizationId, ?int $clinicId, ?int $locationId): array
    {
        if (blank($organizationId) || blank($clinicId)) {
            return [];
        }

        return Patient::query()
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->when(filled($locationId), fn ($query) => $query->where('location_id', $locationId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn (Patient $patient): array => [
                $patient->id => collect([
                    $patient->full_name,
                    optional($patient->dob)->format('m/d/Y'),
                    filled($patient->pms_patient_id) ? 'PMS ' . $patient->pms_patient_id : null,
                ])->filter()->implode(' | '),
            ])
            ->all();
    }

    protected static function applyImportedPatient(int $patientId, Get $get, Set $set, int $organizationId, int $clinicId): void
    {
        $patient = Patient::query()
            ->with(['insurancePolicies' => function ($query): void {
                $query->orderByRaw("case when coverage_priority = 'primary' then 0 when coverage_priority = 'secondary' then 1 else 2 end");
            }])
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->find($patientId);

        if (! $patient) {
            return;
        }

        if (blank($get('location_id')) && filled($patient->location_id)) {
            $set('location_id', $patient->location_id);

            $location = Location::query()
                ->with('clinic')
                ->find($patient->location_id);

            if ($location) {
                static::applyVerificationRouting($location, $set);
            }
        }

        static::applyMatchedPatient($patient, $set, $get);
    }

    protected static function appointmentImportOptions(?int $organizationId, ?int $clinicId, ?int $locationId): array
    {
        if (blank($organizationId) || blank($clinicId)) {
            return [];
        }

        return Appointment::query()
            ->with(['patient', 'provider.user'])
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->when(filled($locationId), fn ($query) => $query->where('location_id', $locationId))
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->limit(100)
            ->get()
            ->mapWithKeys(fn (Appointment $appointment): array => [
                $appointment->id => collect([
                    optional($appointment->appointment_date)->format('M d, Y'),
                    $appointment->patient?->full_name,
                    $appointment->provider?->display_name,
                    $appointment->start_time,
                ])->filter()->implode(' | '),
            ])
            ->all();
    }

    protected static function applyImportedAppointment(int $appointmentId, Get $get, Set $set, int $organizationId, int $clinicId): void
    {
        $appointment = Appointment::query()
            ->with([
                'patient.insurancePolicies' => function ($query): void {
                    $query->orderByRaw("case when coverage_priority = 'primary' then 0 when coverage_priority = 'secondary' then 1 else 2 end");
                },
                'provider.user',
                'location.clinic',
            ])
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->find($appointmentId);

        if (! $appointment) {
            return;
        }

        if (filled($appointment->location_id)) {
            $set('location_id', $appointment->location_id);

            if ($appointment->location) {
                static::applyVerificationRouting($appointment->location, $set);
            }
        }

        if (filled($appointment->provider_id)) {
            $set('provider_id', $appointment->provider_id);
        }

        if ($appointment->appointment_date) {
            $set('vf_appointment_date', $appointment->appointment_date->format('Y-m-d'));
        }

        if (filled($appointment->start_time)) {
            $set('vf_appointment_time', $appointment->start_time);
        }

        $set('patient_id', $appointment->patient_id);

        if ($appointment->patient) {
            static::applyMatchedPatient($appointment->patient, $set, $get);
        }
    }

    protected static function findPatientByPmsId(Get $get, int $organizationId, int $clinicId, int $locationId): ?Patient
    {
        $pmsId = trim((string) ($get('vf_pms_id') ?? ''));

        if ($pmsId === '') {
            return null;
        }

        return static::patientScope($organizationId, $clinicId, $locationId)
            ->where('pms_patient_id', $pmsId)
            ->first();
    }

    protected static function findPatientByMemberId(Get $get, int $organizationId, int $clinicId, int $locationId): ?Patient
    {
        $memberId = trim((string) ($get('vf_patient_identifier') ?? ''));

        if ($memberId === '') {
            return null;
        }

        $policy = PatientInsurancePolicy::query()
            ->with('patient')
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where(function ($query) use ($locationId): void {
                $query->whereNull('location_id')->orWhere('location_id', $locationId);
            })
            ->where('member_id', $memberId)
            ->first();

        return $policy?->patient;
    }

    protected static function findPatientByNameAndDob(Get $get, int $organizationId, int $clinicId, int $locationId): ?Patient
    {
        $fullName = trim((string) ($get('vf_patient_full_name') ?? ''));
        $dob = $get('vf_patient_dob');

        if ($fullName === '' || blank($dob)) {
            return null;
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        $firstName = array_shift($parts) ?? null;
        $lastName = count($parts) > 0 ? implode(' ', $parts) : null;

        return static::patientScope($organizationId, $clinicId, $locationId)
            ->whereDate('dob', $dob)
            ->when(filled($firstName), fn ($query) => $query->where('first_name', 'like', $firstName))
            ->when(filled($lastName), fn ($query) => $query->where('last_name', 'like', $lastName))
            ->first();
    }

    protected static function patientScope(int $organizationId, int $clinicId, int $locationId)
    {
        return Patient::query()
            ->with(['insurancePolicies' => function ($query) use ($locationId): void {
                $query->where(function ($policyQuery) use ($locationId): void {
                    $policyQuery->whereNull('location_id')->orWhere('location_id', $locationId);
                })->orderByRaw("case when coverage_priority = 'primary' then 0 when coverage_priority = 'secondary' then 1 else 2 end");
            }])
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where('location_id', $locationId);
    }

    protected static function applyMatchedPatient(Patient $patient, Set $set, Get $get): void
    {
        $primaryPolicy = $patient->insurancePolicies->first();

        $set('patient_id', $patient->id);
        $set('patient_insurance_policy_id', $primaryPolicy?->id);
        $set('vf_patient_full_name', $patient->full_name);
        $set('vf_patient_dob', $patient->dob?->format('Y-m-d'));
        $set('vf_patient_zip', static::extractZipFromAddress($patient->address));

        if (blank($get('vf_pms_id')) && filled($patient->pms_patient_id)) {
            $set('vf_pms_id', $patient->pms_patient_id);
        }

        if (blank($get('vf_patient_identifier')) && filled($primaryPolicy?->member_id)) {
            $set('vf_patient_identifier', $primaryPolicy->member_id);
        }

        if ($primaryPolicy) {
            $set('verification_plan_snapshots', [[
                'plan_priority' => $primaryPolicy->coverage_priority ?: 'primary',
                'payer_name' => $primaryPolicy->insurance_company,
                'member_id' => $primaryPolicy->member_id,
                'group_number' => $primaryPolicy->group_number,
                'subscriber_name' => $primaryPolicy->subscriber_name,
                'subscriber_dob' => $primaryPolicy->subscriber_dob?->format('Y-m-d'),
            ]]);
        }

        if (filled($get('vf_subscriber_same_as_patient'))) {
            static::syncSubscriberFromPatient($get, $set);
        }
    }

    protected static function syncSubscriberFromPatient(Get $get, Set $set): void
    {
        if (! $get('vf_subscriber_same_as_patient')) {
            return;
        }

        $plans = $get('verification_plan_snapshots') ?? [];

        if (! is_array($plans) || $plans === []) {
            $plans = [['plan_priority' => 'primary']];
        }

        $patientName = trim((string) ($get('vf_patient_full_name') ?? ''));
        $patientDob = $get('vf_patient_dob');

        foreach ($plans as $index => $plan) {
            $plans[$index]['subscriber_name'] = $patientName !== '' ? $patientName : ($plan['subscriber_name'] ?? null);
            $plans[$index]['subscriber_dob'] = filled($patientDob) ? $patientDob : ($plan['subscriber_dob'] ?? null);
        }

        $set('verification_plan_snapshots', $plans);
    }

    protected static function extractZipFromAddress(?string $address): ?string
    {
        if (blank($address)) {
            return null;
        }

        if (preg_match('/\b(\d{5}(?:-\d{4})?)\b/', $address, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function resolveVerificationEnrollment(?int $organizationId, ?int $clinicId, ?int $locationId): ?ClientServiceEnrollment
    {
        if (blank($organizationId) || blank($clinicId)) {
            return null;
        }

        return ClientServiceEnrollment::query()
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where('status', 'active')
            ->when(
                filled($locationId),
                fn ($query) => $query->where(function ($innerQuery) use ($locationId): void {
                    $innerQuery->whereNull('location_id')->orWhere('location_id', $locationId);
                }),
                fn ($query) => $query->whereNull('location_id'),
            )
            ->whereHas('managedBillingService', fn ($query) => $query->where('category', 'verification'))
            ->orderByRaw('case when location_id is null then 1 else 0 end')
            ->first();
    }

    public static function resolveDefaultVerificationServiceId(): ?int
    {
        return ManagedBillingService::query()
            ->where('status', true)
            ->where('category', 'verification')
            ->orderBy('name')
            ->value('id');
    }

    public static function clinicWorkspaceEnabledForEnrollment(?ClientServiceEnrollment $enrollment): bool
    {
        return (bool) ($enrollment?->clinic_workspace_enabled ?? false);
    }

    protected static function applyVerificationRouting(Location $location, Set $set): void
    {
        $enrollment = static::resolveVerificationEnrollment(
            $location->clinic?->organization_id,
            $location->clinic_id,
            $location->id,
        );

        if ($enrollment) {
            $set('managed_billing_service_id', $enrollment->managed_billing_service_id);
            $set('client_service_enrollment_id', $enrollment->id);
            $set('source', 'clinic_request');

            return;
        }

        $set('managed_billing_service_id', static::resolveDefaultVerificationServiceId());
        $set('client_service_enrollment_id', null);
        $set('source', 'clinic_self_service');
    }

    protected static function matchedPatientHint(Get $get): ?string
    {
        $patientId = $get('patient_id');

        if (blank($patientId)) {
            return null;
        }

        $patient = Patient::query()->find($patientId);

        if (! $patient) {
            return null;
        }

        $parts = [
            'Matched internal patient',
            $patient->full_name,
        ];

        if ($patient->dob) {
            $parts[] = 'DOB ' . $patient->dob->format('M d, Y');
        }

        if (filled($patient->pms_patient_id)) {
            $parts[] = 'PMS ID ' . $patient->pms_patient_id;
        }

        return implode(' | ', $parts);
    }
}
