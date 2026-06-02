<?php

namespace App\Filament\Clinic\Resources\PatientStatements\Schemas;

use App\Models\Location;
use App\Models\Patient;
use App\Models\PatientStatement;
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

class PatientStatementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Statement Setup')
                    ->description('Generate a statement snapshot from the patient ledger so the balance can be reviewed, printed, or shared cleanly.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('statement_number')
                                    ->label('Statement number')
                                    ->default(fn (): string => PatientStatement::generateStatementNumber())
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(),
                                DatePicker::make('statement_date')
                                    ->label('Statement date')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                                Select::make('status')
                                    ->options(PatientStatement::STATUS_OPTIONS)
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
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        $patient = filled($state) ? Patient::query()->find($state) : null;
                                        $set('recipient_email', $patient?->email);
                                    })
                                    ->required(),
                                DatePicker::make('period_from')
                                    ->label('Period from')
                                    ->native(false)
                                    ->required(),
                                DatePicker::make('period_to')
                                    ->label('Period to')
                                    ->native(false)
                                    ->required(),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('recipient_email')
                                    ->label('Send statements to')
                                    ->email()
                                    ->placeholder('patient@example.com')
                                    ->maxLength(255),
                            ]),
                        Grid::make(5)
                            ->schema([
                                Placeholder::make('opening_balance_preview')
                                    ->label('Opening balance')
                                    ->content(fn (): string => 'Calculated on save'),
                                Placeholder::make('charges_total_preview')
                                    ->label('Charges')
                                    ->content(fn (): string => 'Calculated on save'),
                                Placeholder::make('payments_total_preview')
                                    ->label('Payments')
                                    ->content(fn (): string => 'Calculated on save'),
                                Placeholder::make('adjustments_total_preview')
                                    ->label('Adjustments')
                                    ->content(fn (): string => 'Calculated on save'),
                                Placeholder::make('closing_balance_preview')
                                    ->label('Closing balance')
                                    ->content(fn (): string => 'Calculated on save'),
                            ]),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
