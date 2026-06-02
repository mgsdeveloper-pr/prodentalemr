<?php

namespace App\Filament\Clinic\Resources\DentalChartEntries\Schemas;

use App\Models\DentalChartEntry;
use App\Models\Encounter;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ClinicService;
use App\Models\TreatmentPlan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DentalChartEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Chart Entry Setup')
                    ->description('Record tooth-level clinical findings, existing work, or treatment progress in the chart.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('recorded_on')
                                    ->label('Recorded on')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                                Select::make('chart_type')
                                    ->label('Chart type')
                                    ->options(DentalChartEntry::CHART_TYPE_OPTIONS)
                                    ->default(fn (): string => in_array(request('chart_type'), array_keys(DentalChartEntry::CHART_TYPE_OPTIONS), true) ? request('chart_type') : 'condition')
                                    ->native(false)
                                    ->required(),
                                Select::make('status')
                                    ->options(DentalChartEntry::STATUS_OPTIONS)
                                    ->default(function (): string {
                                        $requestedStatus = request('status');

                                        if (in_array($requestedStatus, array_keys(DentalChartEntry::STATUS_OPTIONS), true)) {
                                            return $requestedStatus;
                                        }

                                        return match (request('chart_type')) {
                                            'planned' => 'planned',
                                            'completed' => 'completed',
                                            default => 'active',
                                        };
                                    })
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
                                    ->default(fn (): ?string => request('patient_id'))
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
                                    ->default(fn (): ?string => request('provider_id')),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->default(fn (): ?string => request('location_id') ?: (auth()->user()?->location_id ? (string) auth()->user()?->location_id : null))
                                    ->required(),
                                Select::make('tooth_number')
                                    ->label('Tooth')
                                    ->options(DentalChartEntry::toothOptions())
                                    ->searchable()
                                    ->preload()
                                    ->default(fn (): ?string => request('tooth_number'))
                                    ->required(),
                                TextInput::make('tooth_surface')
                                    ->label('Surface')
                                    ->placeholder('M, O, D, B, L, MOD')
                                    ->default(fn (): ?string => request('tooth_surface'))
                                    ->maxLength(50),
                                Select::make('condition_code')
                                    ->label('Condition / procedure')
                                    ->options(DentalChartEntry::CONDITION_CODE_OPTIONS)
                                    ->native(false)
                                    ->searchable(),
                                TextInput::make('description')
                                    ->label('Short description')
                                    ->placeholder('Occlusal composite, fractured cusp, missing tooth')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Clinical Context')
                    ->schema([
                        Grid::make(3)
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
                                    ->preload(),
                                Select::make('treatment_plan_id')
                                    ->label('Treatment plan')
                                    ->options(fn (): array => TreatmentPlan::query()
                                        ->with('patient')
                                        ->where('organization_id', auth()->user()?->organization_id)
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderByDesc('plan_date')
                                        ->get()
                                        ->mapWithKeys(fn (TreatmentPlan $plan) => [$plan->id => $plan->plan_number . ' · ' . ($plan->patient?->full_name ?? 'Patient')])
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
                                        ->mapWithKeys(fn (Encounter $encounter) => [$encounter->id => $encounter->encounter_date?->format('M d, Y') . ' · ' . ($encounter->patient?->full_name ?? 'Patient')])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Textarea::make('notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
