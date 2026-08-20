<?php

namespace App\Filament\Saas\Resources\Clinics\Schemas;

use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Support\UsTimezoneOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ClinicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Clinic Information')
                    ->description('Add the clinic under the correct client and confirm its basic operating details.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('organization_id')
                                    ->label('Organization')
                                    ->relationship('organization', 'name')
                                    ->default(fn (): ?int => request()->integer('organization_id') ?: null)
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('clinic_name')
                                    ->label('Clinic name')
                                    ->placeholder('Enter the clinic name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('timezone')
                                    ->label('Timezone')
                                    ->options(UsTimezoneOptions::options())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default('America/New_York')
                                    ->native(false),
                                Toggle::make('status')
                                    ->label('Clinic active')
                                    ->helperText('Active clinics are available to assigned users after setup is complete.')
                                    ->default(true)
                                    ->required()
                                    ->inline(false),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Verification Setup')
                    ->description('Choose how verification work will be handled for this clinic.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('verification_services_enabled')
                                    ->label('Enable verification services')
                                    ->helperText('Provides access to verification requests, clinic responses, portal credentials, and verification settings.')
                                    ->default(true)
                                    ->live()
                                    ->inline(false),
                                Select::make('managed_services_status')
                                    ->label('Verification model')
                                    ->options([
                                        'active' => 'Managed Service',
                                        'not_enabled' => 'Self-Managed',
                                        'requested' => 'Hybrid',
                                        'paused' => 'Managed Service - Paused',
                                        'cancelled' => 'Managed Service - Cancelled',
                                    ])
                                    ->default('not_enabled')
                                    ->helperText('Defines whether verification is completed by MGS, the clinic, or both teams.')
                                    ->visible(fn (Get $get): bool => (bool) $get('verification_services_enabled'))
                                    ->required(fn (Get $get): bool => (bool) $get('verification_services_enabled'))
                                    ->native(false),
                                Select::make('verification_default_form_template')
                                    ->label('Default verification template')
                                    ->options(VerificationFormQuestion::ACTIVE_TEMPLATE_OPTIONS)
                                    ->default(VerificationFormQuestion::defaultTemplateKey())
                                    ->visible(fn (Get $get): bool => (bool) $get('verification_services_enabled'))
                                    ->required(fn (Get $get): bool => (bool) $get('verification_services_enabled'))
                                    ->native(false),
                                Toggle::make('allow_verification_manager_template_edits')
                                    ->label('Allow Verification Manager template changes')
                                    ->helperText('Allows an assigned Verification Manager to create and publish clinic-specific template drafts.')
                                    ->default(false)
                                    ->visible(fn (Get $get): bool => (bool) $get('verification_services_enabled'))
                                    ->inline(false),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Advanced Settings')
                    ->description('Internal identifiers, service state, trial settings, and ownership controls.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('clinic_code')
                                    ->label('Clinic code')
                                    ->default(fn (): string => self::generateClinicCode())
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('service_status')
                                    ->label('Overall service status')
                                    ->default('active')
                                    ->options([
                                        'active' => 'Active',
                                        'trial' => 'Trial',
                                        'pending_setup' => 'Pending setup',
                                        'suspended' => 'Suspended',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->native(false),
                                Select::make('verification_service_status')
                                    ->label('Verification access status')
                                    ->default('active')
                                    ->options([
                                        'active' => 'Active',
                                        'trial' => 'Trial',
                                        'pending_setup' => 'Pending setup',
                                        'suspended' => 'Suspended',
                                        'cancelled' => 'Cancelled',
                                        'not_enabled' => 'Not enabled',
                                    ])
                                    ->required()
                                    ->native(false),
                                DatePicker::make('trial_ends_at')
                                    ->label('Trial ends'),
                                Toggle::make('demo_mode')
                                    ->label('Demo clinic')
                                    ->default(false)
                                    ->inline(false),
                                Select::make('account_manager_user_id')
                                    ->label('Account manager')
                                    ->relationship('accountManager', 'name')
                                    ->searchable()
                                    ->preload(),
                                Textarea::make('service_notes')
                                    ->label('Internal service notes')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public static function normalizeForCreate(array $data): array
    {
        $verificationEnabled = (bool) ($data['verification_services_enabled'] ?? true);

        return [
            ...$data,
            'verification_services_enabled' => $verificationEnabled,
            'verification_service_status' => $verificationEnabled
                ? ($data['verification_service_status'] ?? 'active')
                : 'not_enabled',
            'managed_services_status' => $verificationEnabled
                ? ($data['managed_services_status'] ?? 'not_enabled')
                : 'not_enabled',
            'clinic_operations_enabled' => false,
            'pms_service_status' => 'not_enabled',
        ];
    }

    public static function normalizeForSave(array $data): array
    {
        $verificationEnabled = (bool) ($data['verification_services_enabled'] ?? false);

        return [
            ...$data,
            'verification_service_status' => $verificationEnabled
                ? ($data['verification_service_status'] ?? 'active')
                : 'not_enabled',
            'managed_services_status' => $verificationEnabled
                ? ($data['managed_services_status'] ?? 'not_enabled')
                : 'not_enabled',
        ];
    }

    protected static function generateClinicCode(): string
    {
        do {
            $code = 'CLN-' . Str::upper(Str::random(6));
        } while (Clinic::query()->where('clinic_code', $code)->exists());

        return $code;
    }
}
