<?php

namespace App\Filament\Clinic\Resources\PatientLedgerEntries\Schemas;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientLedgerEntry;
use App\Models\Provider;
use App\Models\ClinicService;
use App\Models\TreatmentPlan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PatientLedgerEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Posting Snapshot')
                    ->description('Post charges, payments, and adjustments in a way that keeps the patient balance easy to follow for front-desk and billing teams.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
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
                                    ->required(),
                                DatePicker::make('posted_on')
                                    ->label('Posted on')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                                Select::make('entry_type')
                                    ->label('Entry type')
                                    ->options(PatientLedgerEntry::ENTRY_TYPE_OPTIONS)
                                    ->default('charge')
                                    ->native(false)
                                    ->live()
                                    ->required(),
                                Select::make('status')
                                    ->options(PatientLedgerEntry::STATUS_OPTIONS)
                                    ->default('posted')
                                    ->native(false)
                                    ->required(),
                                TextInput::make('reference_number')
                                    ->label('Reference number')
                                    ->maxLength(255),
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

                                        $serviceItem = ClinicService::query()
                                            ->where('organization_id', auth()->user()?->organization_id)
                                            ->where('clinic_id', auth()->user()?->clinic_id)
                                            ->find($state);

                                        if (! $serviceItem) {
                                            return;
                                        }

                                        if (blank($get('description'))) {
                                            $set('description', $serviceItem->name);
                                        }

                                        $set('unit_amount', number_format((float) $serviceItem->default_price, 2, '.', ''));
                                        static::syncFinancials($get, $set);
                                    }),
                            ]),
                    ]),
                Section::make('Financial Breakdown')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('description')
                                    ->label('Description')
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncFinancials($get, $set)),
                                TextInput::make('unit_amount')
                                    ->label('Unit amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncFinancials($get, $set)),
                                TextInput::make('debit_amount')
                                    ->label('Debit')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncPatientPortion($get, $set)),
                                TextInput::make('credit_amount')
                                    ->label('Credit')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01'),
                                TextInput::make('insurance_portion')
                                    ->label('Insurance portion')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncPatientPortion($get, $set)),
                                TextInput::make('patient_portion')
                                    ->label('Patient portion')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01'),
                            ]),
                        Placeholder::make('balance_impact_preview')
                            ->label('Balance impact')
                            ->content(function (Get $get): string {
                                $impact = ((float) ($get('debit_amount') ?? 0)) - ((float) ($get('credit_amount') ?? 0));

                                return sprintf('%s$%0.2f', $impact >= 0 ? '+' : '-', abs($impact));
                            }),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    protected static function syncFinancials(Get $get, Set $set): void
    {
        $entryType = (string) ($get('entry_type') ?? 'charge');
        $quantity = max((float) ($get('quantity') ?? 1), 0.01);
        $unitAmount = max((float) ($get('unit_amount') ?? 0), 0);
        $lineTotal = round($quantity * $unitAmount, 2);

        if ($entryType === 'charge') {
            $set('debit_amount', number_format($lineTotal, 2, '.', ''));
        }

        static::syncPatientPortion($get, $set);
    }

    protected static function syncPatientPortion(Get $get, Set $set): void
    {
        $debit = max((float) ($get('debit_amount') ?? 0), 0);
        $insurance = min(max((float) ($get('insurance_portion') ?? 0), 0), $debit);
        $patient = max($debit - $insurance, 0);

        $set('insurance_portion', number_format($insurance, 2, '.', ''));
        $set('patient_portion', number_format($patient, 2, '.', ''));
    }
}
