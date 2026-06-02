<?php

namespace App\Filament\Clinic\Resources\PatientInsuranceClaims\Schemas;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientInsuranceClaim;
use App\Models\PatientInsuranceClaimLineItem;
use App\Models\PatientInsurancePolicy;
use App\Models\Provider;
use App\Models\ClinicService;
use App\Models\TreatmentPlanItem;
use App\Models\TreatmentPlan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PatientInsuranceClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Claim Snapshot')
                    ->description('Track claim and pre-authorization work in a clean operational format before deeper clearinghouse or ERA workflows are added.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('claim_number')
                                    ->label('Claim number')
                                    ->default(fn (): string => PatientInsuranceClaim::generateClaimNumber())
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(),
                                Select::make('claim_type')
                                    ->label('Claim type')
                                    ->options(PatientInsuranceClaim::CLAIM_TYPE_OPTIONS)
                                    ->default('claim')
                                    ->native(false)
                                    ->required(),
                                Select::make('status')
                                    ->options(PatientInsuranceClaim::STATUS_OPTIONS)
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                                Select::make('patient_id')
                                    ->label('Patient')
                                    ->options(fn (): array => Patient::query()
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('last_name')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Patient $patient) => [$patient->id => $patient->full_name])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required(),
                                Select::make('patient_insurance_policy_id')
                                    ->label('Insurance policy')
                                    ->options(function (Get $get): array {
                                        $patientId = $get('patient_id');

                                        return PatientInsurancePolicy::query()
                                            ->where('organization_id', auth()->user()?->organization_id)
                                            ->where('clinic_id', auth()->user()?->clinic_id)
                                            ->when(filled($patientId), fn ($query) => $query->where('patient_id', $patientId))
                                            ->orderBy('coverage_priority')
                                            ->get()
                                            ->mapWithKeys(fn (PatientInsurancePolicy $policy) => [
                                                $policy->id => ($policy->coverage_priority ? str($policy->coverage_priority)->title()->toString() . ' - ' : '') . $policy->insurance_company . ' - ' . $policy->member_id,
                                            ])
                                            ->all();
                                    })
                                    ->searchable()
                                    ->preload(),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('provider_id')
                                    ->label('Provider')
                                    ->options(fn (): array => Provider::query()
                                        ->with('user')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('id')
                                        ->get()
                                        ->mapWithKeys(fn (Provider $provider) => [$provider->id => $provider->display_name])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                DatePicker::make('claim_date')
                                    ->label('Claim date')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('service_date')
                                    ->label('Service date')
                                    ->native(false),
                                DatePicker::make('submitted_at')
                                    ->label('Submitted on')
                                    ->native(false),
                                TextInput::make('preauth_number')
                                    ->label('Pre-auth number')
                                    ->maxLength(255),
                                TextInput::make('payer_reference')
                                    ->label('Payer reference')
                                    ->maxLength(255),
                                Select::make('appointment_id')
                                    ->label('Appointment')
                                    ->options(fn (): array => Appointment::query()
                                        ->with('patient')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderByDesc('appointment_date')
                                        ->get()
                                        ->mapWithKeys(fn (Appointment $appointment) => [
                                            $appointment->id => $appointment->appointment_date?->format('M d, Y') . ' - ' . ($appointment->patient?->full_name ?? 'Patient'),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('encounter_id')
                                    ->label('Encounter')
                                    ->options(fn (): array => Encounter::query()
                                        ->with('patient')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderByDesc('encounter_date')
                                        ->get()
                                        ->mapWithKeys(fn (Encounter $encounter) => [
                                            $encounter->id => $encounter->encounter_date?->format('M d, Y') . ' - ' . ($encounter->patient?->full_name ?? 'Patient'),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('treatment_plan_id')
                                    ->label('Treatment plan')
                                    ->options(fn (): array => TreatmentPlan::query()
                                        ->with('patient')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderByDesc('plan_date')
                                        ->get()
                                        ->mapWithKeys(fn (TreatmentPlan $plan) => [
                                            $plan->id => ($plan->plan_number ?? 'Plan') . ' - ' . ($plan->patient?->full_name ?? 'Patient'),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),
                Section::make('Claim Procedures')
                    ->description('Post the billed procedures at the tooth and surface level so claims can be tracked closer to a real dental PMS workflow.')
                    ->schema([
                        Repeater::make('lineItems')
                            ->relationship()
                            ->label('Claim line items')
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::syncClaimTotals($get, $set))
                            ->schema([
                                Select::make('treatment_plan_item_id')
                                    ->label('Treatment plan item')
                                    ->options(fn (): array => TreatmentPlanItem::query()
                                        ->with(['treatmentPlan.patient', 'clinicService'])
                                        ->whereHas('treatmentPlan', function ($query): void {
                                            $query
                                                ->where('organization_id', auth()->user()?->organization_id)
                                                ->where('clinic_id', auth()->user()?->clinic_id);
                                        })
                                        ->orderByDesc('id')
                                        ->get()
                                        ->mapWithKeys(fn (TreatmentPlanItem $item) => [
                                            $item->id => collect([
                                                $item->treatmentPlan?->patient?->full_name,
                                                $item->clinicService?->name ?: $item->description,
                                                $item->tooth_number ? "Tooth {$item->tooth_number}" : null,
                                            ])->filter()->implode(' - '),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $item = TreatmentPlanItem::query()->with('clinicService')->find($state);

                                        if (! $item) {
                                            return;
                                        }

                                        $set('clinic_service_id', $item->clinic_service_id);
                                        $set('description', $item->description);
                                        $set('tooth_number', $item->tooth_number);
                                        $set('tooth_surface', $item->tooth_surface);
                                        $set('quantity', number_format((float) $item->quantity, 2, '.', ''));
                                        $set('unit_fee', number_format((float) $item->unit_fee, 2, '.', ''));
                                        $set('estimated_coverage', number_format((float) $item->estimated_insurance, 2, '.', ''));
                                        $set('patient_responsibility', number_format((float) $item->estimated_patient, 2, '.', ''));

                                        static::syncClaimLineItem($get, $set);
                                    })
                                    ->columnSpan(3),
                                Select::make('clinic_service_id')
                                    ->label('Service')
                                    ->options(fn (): array => ClinicService::query()
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->where('status', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $item = ClinicService::query()
                                            ->where('organization_id', auth()->user()?->organization_id)
                                            ->where('clinic_id', auth()->user()?->clinic_id)
                                            ->find($state);

                                        if (! $item) {
                                            return;
                                        }

                                        if (blank($get('description'))) {
                                            $set('description', $item->name);
                                        }

                                        $set('unit_fee', number_format((float) $item->default_price, 2, '.', ''));

                                        static::syncClaimLineItem($get, $set);
                                    })
                                    ->columnSpan(2),
                                TextInput::make('procedure_code')
                                    ->label('Procedure code')
                                    ->placeholder('D2392')
                                    ->maxLength(50),
                                TextInput::make('tooth_number')
                                    ->label('Tooth')
                                    ->maxLength(50),
                                TextInput::make('tooth_surface')
                                    ->label('Surface')
                                    ->placeholder('MOD')
                                    ->maxLength(50),
                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(3),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncClaimLineItem($get, $set)),
                                TextInput::make('unit_fee')
                                    ->label('Unit fee')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncClaimLineItem($get, $set)),
                                TextInput::make('billed_amount')
                                    ->label('Billed')
                                    ->prefix('$')
                                    ->readOnly()
                                    ->dehydrated(),
                                TextInput::make('estimated_coverage')
                                    ->label('Est. coverage')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncClaimLineItem($get, $set)),
                                TextInput::make('insurance_paid')
                                    ->label('Ins. paid')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncClaimTotals($get, $set)),
                                TextInput::make('patient_responsibility')
                                    ->label('Patient resp.')
                                    ->prefix('$')
                                    ->readOnly()
                                    ->dehydrated(),
                                Select::make('status')
                                    ->options(PatientInsuranceClaimLineItem::STATUS_OPTIONS)
                                    ->default('ready')
                                    ->native(false),
                                Textarea::make('notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpanFull(),
                    ]),
                Section::make('Amounts and Narrative')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('billed_amount')
                                    ->label('Billed amount')
                                    ->prefix('$')
                                    ->readOnly()
                                    ->dehydrated(),
                                TextInput::make('estimated_coverage')
                                    ->label('Estimated coverage')
                                    ->prefix('$')
                                    ->readOnly()
                                    ->dehydrated(),
                                TextInput::make('insurance_paid')
                                    ->label('Insurance paid')
                                    ->prefix('$')
                                    ->readOnly()
                                    ->dehydrated(),
                                TextInput::make('patient_responsibility')
                                    ->label('Patient responsibility')
                                    ->prefix('$')
                                    ->readOnly()
                                    ->dehydrated(),
                            ]),
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('line_items_total_preview')
                                    ->label('Billed total')
                                    ->content(fn (Get $get): string => '$' . number_format(static::calculateLineItemsTotal($get('lineItems')), 2)),
                                Placeholder::make('line_items_coverage_preview')
                                    ->label('Estimated coverage')
                                    ->content(fn (Get $get): string => '$' . number_format(static::calculateEstimatedCoverage($get('lineItems')), 2)),
                                Placeholder::make('line_items_paid_preview')
                                    ->label('Insurance paid')
                                    ->content(fn (Get $get): string => '$' . number_format(static::calculateInsurancePaid($get('lineItems')), 2)),
                                Placeholder::make('line_items_patient_preview')
                                    ->label('Patient responsibility')
                                    ->content(fn (Get $get): string => '$' . number_format(static::calculatePatientPortion($get('lineItems')), 2)),
                            ]),
                        Textarea::make('procedure_summary')
                            ->label('Procedure summary')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    protected static function syncClaimLineItem(Get $get, Set $set): void
    {
        $billed = round(max((float) ($get('quantity') ?? 0), 0.01) * max((float) ($get('unit_fee') ?? 0), 0), 2);
        $coverage = min(max((float) ($get('estimated_coverage') ?? 0), 0), $billed);

        $set('billed_amount', number_format($billed, 2, '.', ''));
        $set('estimated_coverage', number_format($coverage, 2, '.', ''));
        $set('patient_responsibility', number_format(max($billed - $coverage, 0), 2, '.', ''));
    }

    protected static function syncClaimTotals(Get $get, Set $set): void
    {
        $set('billed_amount', number_format(static::calculateLineItemsTotal($get('lineItems')), 2, '.', ''));
        $set('estimated_coverage', number_format(static::calculateEstimatedCoverage($get('lineItems')), 2, '.', ''));
        $set('insurance_paid', number_format(static::calculateInsurancePaid($get('lineItems')), 2, '.', ''));
        $set('patient_responsibility', number_format(static::calculatePatientPortion($get('lineItems')), 2, '.', ''));
        $set('procedure_summary', static::calculateProcedureSummary($get('lineItems')));
    }

    protected static function calculateLineItemsTotal(?array $items): float
    {
        return collect($items ?? [])
            ->sum(fn (array $item): float => (float) ($item['billed_amount'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_fee'] ?? 0))));
    }

    protected static function calculateEstimatedCoverage(?array $items): float
    {
        return collect($items ?? [])
            ->sum(fn (array $item): float => min((float) ($item['estimated_coverage'] ?? 0), (float) ($item['billed_amount'] ?? 0)));
    }

    protected static function calculateInsurancePaid(?array $items): float
    {
        return collect($items ?? [])
            ->sum(fn (array $item): float => min((float) ($item['insurance_paid'] ?? 0), (float) ($item['billed_amount'] ?? 0)));
    }

    protected static function calculatePatientPortion(?array $items): float
    {
        return collect($items ?? [])
            ->sum(fn (array $item): float => (float) ($item['patient_responsibility'] ?? max(((float) ($item['billed_amount'] ?? 0)) - ((float) ($item['estimated_coverage'] ?? 0)), 0)));
    }

    protected static function calculateProcedureSummary(?array $items): string
    {
        return collect($items ?? [])
            ->map(function (array $item): ?string {
                $description = $item['description'] ?? null;

                if (blank($description)) {
                    return null;
                }

                $tooth = filled($item['tooth_number'] ?? null) ? 'Tooth ' . $item['tooth_number'] : null;
                $surface = $item['tooth_surface'] ?? null;

                return collect([$description, $tooth, $surface])
                    ->filter()
                    ->implode(' - ');
            })
            ->filter()
            ->implode("\n");
    }
}
