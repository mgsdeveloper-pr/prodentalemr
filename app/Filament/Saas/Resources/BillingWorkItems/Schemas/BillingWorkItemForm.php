<?php

namespace App\Filament\Saas\Resources\BillingWorkItems\Schemas;

use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\ClientServiceEnrollment;
use App\Models\Location;
use App\Models\ManagedBillingService;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientInsuranceClaim;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BillingWorkItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
                Section::make('Work Item')
                    ->description('Track outsourced billing work against the exact client, appointment, patient, policy, and claim context.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('reference_number')
                                    ->label('Reference #')
                                    ->default(fn (): string => BillingWorkItem::generateReferenceNumber())
                                    ->readOnly()
                                    ->dehydrated(),
                                Select::make('managed_billing_service_id')
                                    ->label('Managed service')
                                    ->options(fn (): array => ManagedBillingService::query()->where('status', true)->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required(),
                                Select::make('organization_id')
                                    ->label('Organization')
                                    ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('clinic_id', null);
                                        $set('location_id', null);
                                        $set('patient_id', null);
                                        $set('provider_id', null);
                                        $set('appointment_id', null);
                                        $set('patient_insurance_policy_id', null);
                                        $set('patient_insurance_claim_id', null);
                                    })
                                    ->required(),
                                Select::make('clinic_id')
                                    ->label('Clinic')
                                    ->options(fn (Get $get): array => Clinic::query()
                                        ->when(filled($get('organization_id')), fn ($query) => $query->where('organization_id', $get('organization_id')))
                                        ->orderBy('clinic_name')
                                        ->pluck('clinic_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('location_id', null);
                                        $set('patient_id', null);
                                        $set('provider_id', null);
                                        $set('appointment_id', null);
                                        $set('patient_insurance_policy_id', null);
                                        $set('patient_insurance_claim_id', null);
                                    }),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (Get $get): array => Location::query()
                                        ->when(filled($get('clinic_id')), fn ($query) => $query->where('clinic_id', $get('clinic_id')))
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('client_service_enrollment_id')
                                    ->label('Enrollment')
                                    ->options(fn (Get $get): array => ClientServiceEnrollment::query()
                                        ->when(filled($get('organization_id')), fn ($query) => $query->where('organization_id', $get('organization_id')))
                                        ->when(filled($get('clinic_id')), fn ($query) => $query->where('clinic_id', $get('clinic_id')))
                                        ->when(filled($get('managed_billing_service_id')), fn ($query) => $query->where('managed_billing_service_id', $get('managed_billing_service_id')))
                                        ->where('status', 'active')
                                        ->get()
                                        ->mapWithKeys(fn (ClientServiceEnrollment $enrollment) => [$enrollment->id => $enrollment->display_title])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                Select::make('status')
                                    ->options(BillingWorkItem::STATUS_OPTIONS)
                                    ->default(BillingWorkItem::STATUS_PENDING)
                                    ->native(false)
                                    ->required(),
                                Select::make('outcome_status')
                                    ->label('Outcome')
                                    ->options(BillingWorkItem::OUTCOME_STATUS_OPTIONS)
                                    ->native(false),
                                Select::make('priority')
                                    ->options(BillingWorkItem::PRIORITY_OPTIONS)
                                    ->default('normal')
                                    ->native(false)
                                    ->required(),
                                Select::make('assigned_to')
                                    ->label('Assigned to')
                                    ->options(fn (): array => User::query()
                                        ->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::saasRoleOptions())))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('reviewed_by')
                                    ->label('Reviewer')
                                    ->options(fn (): array => User::query()
                                        ->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::saasRoleOptions())))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                DateTimePicker::make('due_at')
                                    ->label('Due at')
                                    ->seconds(false),
                                DateTimePicker::make('started_at')
                                    ->label('Started at')
                                    ->seconds(false),
                                DateTimePicker::make('completed_at')
                                    ->label('Completed at')
                                    ->seconds(false),
                                Select::make('source')
                                    ->options(BillingWorkItem::SOURCE_OPTIONS)
                                    ->default('manual')
                                    ->native(false)
                                    ->required(),
                                Select::make('pms_sync_status')
                                    ->label('PMS sync')
                                    ->options(BillingWorkItem::PMS_SYNC_STATUS_OPTIONS)
                                    ->default('not_applicable')
                                    ->native(false)
                                    ->required(),
                                Select::make('writeback_status')
                                    ->label('Writeback')
                                    ->options(BillingWorkItem::WRITEBACK_STATUS_OPTIONS)
                                    ->default('not_requested')
                                    ->native(false)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Client Context')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('patient_id')
                                    ->label('Patient')
                                    ->options(fn (Get $get): array => Patient::query()
                                        ->when(filled($get('organization_id')), fn ($query) => $query->where('organization_id', $get('organization_id')))
                                        ->when(filled($get('clinic_id')), fn ($query) => $query->where('clinic_id', $get('clinic_id')))
                                        ->orderBy('last_name')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Patient $patient) => [$patient->id => $patient->full_name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('patient_insurance_policy_id', null);
                                        $set('patient_insurance_claim_id', null);
                                        $set('appointment_id', null);
                                    }),
                                Select::make('provider_id')
                                    ->label('Provider')
                                    ->options(fn (Get $get): array => Provider::query()
                                        ->with('user')
                                        ->when(filled($get('organization_id')), fn ($query) => $query->where('organization_id', $get('organization_id')))
                                        ->when(filled($get('clinic_id')), fn ($query) => $query->where('clinic_id', $get('clinic_id')))
                                        ->orderBy('id')
                                        ->get()
                                        ->mapWithKeys(fn (Provider $provider) => [$provider->id => $provider->display_name])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('appointment_id')
                                    ->label('Appointment')
                                    ->options(fn (Get $get): array => Appointment::query()
                                        ->with('patient')
                                        ->when(filled($get('organization_id')), fn ($query) => $query->where('organization_id', $get('organization_id')))
                                        ->when(filled($get('clinic_id')), fn ($query) => $query->where('clinic_id', $get('clinic_id')))
                                        ->when(filled($get('patient_id')), fn ($query) => $query->where('patient_id', $get('patient_id')))
                                        ->orderByDesc('appointment_date')
                                        ->get()
                                        ->mapWithKeys(fn (Appointment $appointment) => [
                                            $appointment->id => collect([
                                                $appointment->appointment_date?->format('M d, Y'),
                                                $appointment->patient?->full_name,
                                                $appointment->start_time,
                                            ])->filter()->implode(' - '),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('patient_insurance_policy_id')
                                    ->label('Insurance policy')
                                    ->options(fn (Get $get): array => PatientInsurancePolicy::query()
                                        ->when(filled($get('organization_id')), fn ($query) => $query->where('organization_id', $get('organization_id')))
                                        ->when(filled($get('clinic_id')), fn ($query) => $query->where('clinic_id', $get('clinic_id')))
                                        ->when(filled($get('patient_id')), fn ($query) => $query->where('patient_id', $get('patient_id')))
                                        ->orderBy('coverage_priority')
                                        ->get()
                                        ->mapWithKeys(fn (PatientInsurancePolicy $policy) => [
                                            $policy->id => collect([
                                                str($policy->coverage_priority)->title()->toString(),
                                                $policy->insurance_company,
                                                $policy->member_id,
                                            ])->filter()->implode(' - '),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('patient_insurance_claim_id')
                                    ->label('Insurance claim')
                                    ->options(fn (Get $get): array => PatientInsuranceClaim::query()
                                        ->when(filled($get('organization_id')), fn ($query) => $query->where('organization_id', $get('organization_id')))
                                        ->when(filled($get('clinic_id')), fn ($query) => $query->where('clinic_id', $get('clinic_id')))
                                        ->when(filled($get('patient_id')), fn ($query) => $query->where('patient_id', $get('patient_id')))
                                        ->orderByDesc('claim_date')
                                        ->get()
                                        ->mapWithKeys(fn (PatientInsuranceClaim $claim) => [$claim->id => $claim->claim_number])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),
                Section::make('Operational Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3),
                        Textarea::make('internal_summary')
                            ->label('Internal summary')
                            ->rows(6),
                    ]),
            ]);
    }
}
