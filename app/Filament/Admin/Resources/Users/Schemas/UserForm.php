<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Select::make('selected_role')
                    ->label('Role')
                    ->options(fn (): array => auth()->user()?->verificationAssignableRoleOptions() ?? [])
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                Select::make('assigned_clinic_ids')
                    ->label('Assigned Clinics')
                    ->options(fn (): array => auth()->user()?->assignableVerificationClinicOptions() ?? [])
                    ->helperText('Choose the managed-service clinics this verification manager or user can access in the Verification panel.')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array($get('selected_role'), ['verification_manager', 'verification_user'], true))
                    ->required(fn (Get $get): bool => in_array($get('selected_role'), ['verification_manager', 'verification_user'], true))
                    ->columnSpanFull(),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->confirmed()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8),
                TextInput::make('password_confirmation')
                    ->label('Confirm password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
                Toggle::make('status')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ])
            ->columns(2);
    }
}
