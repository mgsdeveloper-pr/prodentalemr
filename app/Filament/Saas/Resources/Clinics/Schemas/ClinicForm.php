<?php

namespace App\Filament\Saas\Resources\Clinics\Schemas;

use App\Models\Clinic;
use App\Support\UsTimezoneOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ClinicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('clinic_name')
                    ->label('Clinic name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('clinic_code')
                    ->label('Clinic code')
                    ->required()
                    ->default(fn (): string => self::generateClinicCode())
                    ->readOnly()
                    ->dehydrated()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('timezone')
                    ->label('Timezone')
                    ->options(UsTimezoneOptions::options())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default('America/New_York')
                    ->native(false),
                Toggle::make('status')
                    ->label('Active')
                    ->default(true)
                    ->required(),
                Section::make('Customer Services')
                    ->description('Choose which product areas this clinic can use.')
                    ->schema([
                        Toggle::make('verification_services_enabled')
                            ->label('Verification Services')
                            ->helperText('Allows access to verification requests, clinic inbox workflow, portal credentials, and related verification settings.')
                            ->default(true)
                            ->inline(false),
                        Toggle::make('clinic_operations_enabled')
                            ->label('Clinic PMS / Clinic Operations')
                            ->helperText('Allows access to clinic operations such as patients, appointments, treatment plans, documents, claims, ledger, and statements.')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }

    protected static function generateClinicCode(): string
    {
        do {
            $code = 'CLN-' . Str::upper(Str::random(6));
        } while (Clinic::query()->where('clinic_code', $code)->exists());

        return $code;
    }
}
