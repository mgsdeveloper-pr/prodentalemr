<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Patient;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\ValidationException;

class VerificationPatientSelector
{
    public static function components(Closure $applyPatient, Closure $applyAppointment, bool $clinicPanel = false): array
    {
        return [
            ToggleButtons::make('intake_mode')->hiddenLabel()->inline()->grouped()
                ->options(['existing' => 'Existing Patient', 'manual' => 'Manual Entry'])
                ->default('existing')->live()->dehydrated(false)
                ->afterStateUpdated(fn (Set $set) => VerificationCreationContext::clearImportedDetails($set)),
            Select::make('import_patient_id')->label('Select Patient')
                ->visible(fn (Get $get): bool => $get('intake_mode') === 'existing')
                ->required(fn (Get $get): bool => $get('intake_mode') === 'existing')
                ->options(fn (Get $get): array => VerificationCreationContext::patientOptions($get('clinic_id') ? (int) $get('clinic_id') : null, $clinicPanel, null, $get('patient_insurance_policy_id') ? (int) $get('patient_insurance_policy_id') : null))
                ->getSearchResultsUsing(fn (string $search, Get $get): array => VerificationCreationContext::patientOptions($get('clinic_id') ? (int) $get('clinic_id') : null, $clinicPanel, $search, $get('patient_insurance_policy_id') ? (int) $get('patient_insurance_policy_id') : null))
                ->getOptionLabelUsing(function ($value, Get $get) use ($clinicPanel): ?string {
                    $patient = VerificationCreationContext::scope(Patient::query(), $clinicPanel)->where('clinic_id', $get('clinic_id') ?: 0)->find($value);
                    if (! $patient) {
                        return null;
                    }
                    $patient->setRelation('insurancePolicies', VerificationCreationContext::patientPolicies($patient->id, $clinicPanel));

                    return VerificationCreationContext::patientLabel($patient, $get('patient_insurance_policy_id') ? (int) $get('patient_insurance_policy_id') : null);
                })
                ->searchable()->preload()->live()->dehydrated(false)
                ->placeholder('Search name, date of birth, or Member ID')
                ->afterStateUpdated(function ($state, Get $get, Set $set) use ($applyPatient, $clinicPanel): void {
                    $assignee = $get('assigned_to');
                    $clinicId = $get('clinic_id');
                    VerificationCreationContext::clearImportedDetails($set);
                    if (filled($state)) {
                        $applyPatient((int) $state, $get, $set);
                        $policyId = $get('patient_insurance_policy_id');
                        $set('selected_patient_policy', $policyId);
                        if ($policyId) {
                            VerificationCreationContext::applyPatientPolicy((int) $policyId, (int) $state, $set, $clinicPanel);
                        }
                    }
                    if ((string) $clinicId === (string) $get('clinic_id')) {
                        $set('assigned_to', $assignee);
                    }
                }),
            Grid::make(['md' => 3])->schema([
                Select::make('import_appointment_id')->label('Select Appointment')->columnSpan(['md' => 2])
                    ->options(fn (Get $get): array => filled($get('import_patient_id'))
                        ? VerificationCreationContext::appointments(
                            $get('clinic_id') ? (int) $get('clinic_id') : null, null,
                            (bool) $get('include_past_appointments'), $clinicPanel, (int) $get('import_patient_id'),
                        ) : [])
                    ->placeholder('No appointment selected: enter a date below')
                    ->disableOptionWhen(fn (string $label): bool => str_contains($label, ' | Cancelled'))
                    ->searchable()->preload()->live()->dehydrated(false)
                    ->afterStateUpdated(function ($state, Get $get, Set $set) use ($applyAppointment, $clinicPanel): void {
                        if (blank($state)) {
                            VerificationCreationContext::clearAppointmentDetails($set);
                            return;
                        }
                        $patientId = $get('import_patient_id');
                        $appointment = VerificationCreationContext::scope(Appointment::query(), $clinicPanel)
                            ->where('patient_id', $patientId ?? 0)->find($state);
                        if (! $appointment) {
                            $set('import_appointment_id', $get('appointment_id'));
                            throw ValidationException::withMessages(['data.import_appointment_id' => 'Select an appointment belonging to the selected patient.']);
                        }
                        $policyId = $get('patient_insurance_policy_id');
                        $assignee = $get('assigned_to');
                        $applyAppointment((int) $state, $get, $set);
                        $set('import_patient_id', $patientId);
                        $set('assigned_to', $assignee);
                        $set('selected_patient_policy', $policyId);
                        if ($policyId) {
                            VerificationCreationContext::applyPatientPolicy((int) $policyId, (int) $patientId, $set, $clinicPanel);
                        }
                    }),
                Checkbox::make('include_past_appointments')->label('Include past appointments')
                    ->default(false)->live()->dehydrated(false),
            ])->extraAttributes(['class' => 'pd-patient-appointments'])
                ->visible(fn (Get $get): bool => $get('intake_mode') === 'existing' && filled($get('import_patient_id'))),
            Select::make('selected_patient_policy')->label('Patient Insurance Policy')
                ->visible(fn (Get $get): bool => $get('intake_mode') === 'existing'
                    && VerificationCreationContext::patientPolicies($get('patient_id') ? (int) $get('patient_id') : null, $clinicPanel)->count() > 1)
                ->options(fn (Get $get): array => VerificationCreationContext::patientPolicies($get('patient_id') ? (int) $get('patient_id') : null, $clinicPanel)
                    ->mapWithKeys(fn ($policy): array => [$policy->id => $policy->insurance_company . ' (' . ($policy->member_id ?: 'Member ID unavailable') . ')'])->all())
                ->placeholder('Select a policy')->selectablePlaceholder(false)->native(false)->live()->dehydrated(false)
                ->afterStateUpdated(function ($state, Get $get, Set $set) use ($clinicPanel): void {
                    VerificationCreationContext::applyPatientPolicy($state ? (int) $state : null, $get('patient_id') ? (int) $get('patient_id') : null, $set, $clinicPanel);
                }),
        ];
    }
}
