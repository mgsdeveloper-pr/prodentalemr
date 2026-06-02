<?php

namespace App\Filament\Clinic\Resources\TreatmentPlans\Schemas;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ClinicService;
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
use Illuminate\Support\Str;

class TreatmentPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Plan Setup')
                    ->description('Create a dental treatment plan that can later feed charting, case acceptance, and billing.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('plan_number')
                                    ->label('Plan number')
                                    ->default(fn (): string => TreatmentPlan::generatePlanNumber())
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(),
                                DatePicker::make('plan_date')
                                    ->label('Plan date')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'proposed' => 'Proposed',
                                        'accepted' => 'Accepted',
                                        'in_progress' => 'In progress',
                                        'completed' => 'Completed',
                                        'declined' => 'Declined',
                                    ])
                                    ->default('proposed')
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
                                    ->required(),
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
                                    ->preload()
                                    ->required(),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('title')
                                    ->label('Plan title')
                                    ->placeholder('Full mouth rehabilitation, phase 1 restorations')
                                    ->maxLength(255),
                                Select::make('phase')
                                    ->options([
                                        'urgent' => 'Urgent',
                                        'phase_1' => 'Phase 1',
                                        'phase_2' => 'Phase 2',
                                        'phase_3' => 'Phase 3',
                                        'maintenance' => 'Maintenance',
                                    ])
                                    ->native(false),
                                Select::make('priority')
                                    ->options([
                                        'low' => 'Low',
                                        'normal' => 'Normal',
                                        'high' => 'High',
                                        'urgent' => 'Urgent',
                                    ])
                                    ->default('normal')
                                    ->native(false)
                                    ->required(),
                                Select::make('appointment_id')
                                    ->label('Linked appointment')
                                    ->options(fn (): array => Appointment::query()
                                        ->with('patient')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderByDesc('appointment_date')
                                        ->get()
                                        ->mapWithKeys(fn (Appointment $appointment) => [
                                            $appointment->id => $appointment->appointment_date?->format('M d, Y') . ' · ' . ($appointment->patient?->full_name ?? 'Patient'),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Select::make('encounter_id')
                                    ->label('Linked encounter')
                                    ->options(fn (): array => Encounter::query()
                                        ->with('patient')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderByDesc('encounter_date')
                                        ->get()
                                        ->mapWithKeys(fn (Encounter $encounter) => [
                                            $encounter->id => $encounter->encounter_date?->format('M d, Y') . ' · ' . ($encounter->patient?->full_name ?? 'Patient'),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                DatePicker::make('accepted_at')
                                    ->label('Accepted on')
                                    ->native(false),
                            ]),
                        Textarea::make('notes')
                            ->label('Clinical / case notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('acceptance_notes')
                            ->label('Acceptance notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Planned Procedures')
                    ->description('Add the proposed dental services, tooth details, and estimate breakdown for each line item.')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('Treatment plan items')
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::syncTotals($get, $set))
                            ->schema([
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

                                        $set('description', Str::limit($serviceItem->description ?: $serviceItem->name, 255, ''));
                                        $set('unit_fee', number_format((float) $serviceItem->default_price, 2, '.', ''));

                                        static::syncLineItem($get, $set);
                                    })
                                    ->columnSpan(3),
                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(3),
                                TextInput::make('tooth_number')
                                    ->label('Tooth')
                                    ->placeholder('3, 14, 30')
                                    ->maxLength(50),
                                TextInput::make('tooth_surface')
                                    ->label('Surface')
                                    ->placeholder('MOD, B, O, ML')
                                    ->maxLength(50),
                                Select::make('appointment_id')
                                    ->label('Scheduled appointment')
                                    ->options(fn (): array => Appointment::query()
                                        ->with('patient')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderByDesc('appointment_date')
                                        ->get()
                                        ->mapWithKeys(fn (Appointment $appointment) => [
                                            $appointment->id => collect([
                                                $appointment->appointment_date?->format('M d, Y'),
                                                $appointment->patient?->full_name,
                                                $appointment->appointment_type,
                                            ])->filter()->implode(' - '),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncLineItem($get, $set)),
                                TextInput::make('unit_fee')
                                    ->label('Fee')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncLineItem($get, $set)),
                                TextInput::make('estimated_insurance')
                                    ->label('Ins. est.')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncLineItem($get, $set)),
                                TextInput::make('estimated_patient')
                                    ->label('Patient est.')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncTotals($get, $set)),
                                Select::make('status')
                                    ->options([
                                        'proposed' => 'Proposed',
                                        'accepted' => 'Accepted',
                                        'scheduled' => 'Scheduled',
                                        'completed' => 'Completed',
                                        'declined' => 'Declined',
                                    ])
                                    ->default('proposed')
                                    ->native(false),
                                DatePicker::make('target_date')
                                    ->label('Target date')
                                    ->native(false),
                                TextInput::make('line_total')
                                    ->label('Line total')
                                    ->prefix('$')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 2, '.', '')),
                                Textarea::make('notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpanFull(),
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('estimated_total_preview')
                                    ->label('Estimated total')
                                    ->content(fn (Get $get): string => '$' . number_format(static::calculateTotal($get('items')), 2)),
                                Placeholder::make('estimated_insurance_preview')
                                    ->label('Insurance estimate')
                                    ->content(fn (Get $get): string => '$' . number_format(static::calculateInsuranceEstimate($get('items')), 2)),
                                Placeholder::make('estimated_patient_preview')
                                    ->label('Patient estimate')
                                    ->content(fn (Get $get): string => '$' . number_format(static::calculatePatientEstimate($get('items')), 2)),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    protected static function syncLineItem(Get $get, Set $set): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $unitFee = (float) ($get('unit_fee') ?? 0);
        $lineTotal = max($quantity * $unitFee, 0);
        $insurance = min((float) ($get('estimated_insurance') ?? 0), $lineTotal);
        $patient = max($lineTotal - $insurance, 0);

        $set('line_total', number_format($lineTotal, 2, '.', ''));
        $set('estimated_patient', number_format($patient, 2, '.', ''));
    }

    protected static function syncTotals(Get $get, Set $set): void
    {
        $set('estimated_total', number_format(static::calculateTotal($get('items')), 2, '.', ''));
        $set('estimated_insurance', number_format(static::calculateInsuranceEstimate($get('items')), 2, '.', ''));
        $set('estimated_patient', number_format(static::calculatePatientEstimate($get('items')), 2, '.', ''));
    }

    protected static function calculateTotal(?array $items): float
    {
        return collect($items ?? [])
            ->sum(fn (array $item): float => (float) ($item['line_total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_fee'] ?? 0))));
    }

    protected static function calculateInsuranceEstimate(?array $items): float
    {
        return collect($items ?? [])
            ->sum(fn (array $item): float => min((float) ($item['estimated_insurance'] ?? 0), (float) ($item['line_total'] ?? 0)));
    }

    protected static function calculatePatientEstimate(?array $items): float
    {
        return collect($items ?? [])
            ->sum(fn (array $item): float => (float) ($item['estimated_patient'] ?? max(((float) ($item['line_total'] ?? 0)) - ((float) ($item['estimated_insurance'] ?? 0)), 0)));
    }
}
