<?php

namespace App\Filament\Clinic\Resources\PerioCharts\Schemas;

use App\Models\Encounter;
use App\Models\Location;
use App\Models\Patient;
use App\Models\PerioChart;
use App\Models\Provider;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PerioChartForm
{
    protected const TOOTH_OPTIONS = [
        'Permanent' => [
            '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8',
            '9' => '9', '10' => '10', '11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', '16' => '16',
            '17' => '17', '18' => '18', '19' => '19', '20' => '20', '21' => '21', '22' => '22', '23' => '23', '24' => '24',
            '25' => '25', '26' => '26', '27' => '27', '28' => '28', '29' => '29', '30' => '30', '31' => '31', '32' => '32',
        ],
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Perio Exam Setup')
                    ->description('Create a periodontal charting session tied to the patient, provider, and clinical visit context.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('chart_date')
                                    ->label('Chart date')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                                Select::make('status')
                                    ->options(PerioChart::STATUS_OPTIONS)
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),
                                TextInput::make('exam_type')
                                    ->label('Exam type')
                                    ->placeholder('Initial, recall, maintenance')
                                    ->maxLength(255),
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
                                    ->preload(),
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
                                TextInput::make('plaque_level')
                                    ->label('Plaque level')
                                    ->placeholder('Mild, Moderate, Heavy')
                                    ->maxLength(255),
                                Select::make('encounter_id')
                                    ->label('Linked encounter')
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
                        Textarea::make('bleeding_notes')
                            ->label('Bleeding notes')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('diagnosis_summary')
                            ->label('Diagnosis summary')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Perio Measurements')
                    ->description('Capture six-point probing, recession, bleeding, mobility, furcation, and suppuration by tooth.')
                    ->schema([
                        Repeater::make('entries')
                            ->relationship()
                            ->label('Perio chart entries')
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->schema([
                                Select::make('tooth_number')
                                    ->label('Tooth')
                                    ->options(self::TOOTH_OPTIONS)
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('mobility')
                                    ->label('Mobility')
                                    ->placeholder('0, 1, 2, 3'),
                                TextInput::make('furcation')
                                    ->label('Furcation')
                                    ->placeholder('I, II, III'),
                                Toggle::make('suppuration')
                                    ->label('Suppuration'),
                                TextInput::make('probing_depth_mb')->label('PD MB')->numeric()->minValue(0)->maxValue(15),
                                TextInput::make('probing_depth_b')->label('PD B')->numeric()->minValue(0)->maxValue(15),
                                TextInput::make('probing_depth_db')->label('PD DB')->numeric()->minValue(0)->maxValue(15),
                                TextInput::make('probing_depth_ml')->label('PD ML')->numeric()->minValue(0)->maxValue(15),
                                TextInput::make('probing_depth_l')->label('PD L')->numeric()->minValue(0)->maxValue(15),
                                TextInput::make('probing_depth_dl')->label('PD DL')->numeric()->minValue(0)->maxValue(15),
                                TextInput::make('recession_mb')->label('Rec MB')->numeric()->minValue(-10)->maxValue(10),
                                TextInput::make('recession_b')->label('Rec B')->numeric()->minValue(-10)->maxValue(10),
                                TextInput::make('recession_db')->label('Rec DB')->numeric()->minValue(-10)->maxValue(10),
                                TextInput::make('recession_ml')->label('Rec ML')->numeric()->minValue(-10)->maxValue(10),
                                TextInput::make('recession_l')->label('Rec L')->numeric()->minValue(-10)->maxValue(10),
                                TextInput::make('recession_dl')->label('Rec DL')->numeric()->minValue(-10)->maxValue(10),
                                Toggle::make('bleeding_mb')->label('BOP MB'),
                                Toggle::make('bleeding_b')->label('BOP B'),
                                Toggle::make('bleeding_db')->label('BOP DB'),
                                Toggle::make('bleeding_ml')->label('BOP ML'),
                                Toggle::make('bleeding_l')->label('BOP L'),
                                Toggle::make('bleeding_dl')->label('BOP DL'),
                                Textarea::make('notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
