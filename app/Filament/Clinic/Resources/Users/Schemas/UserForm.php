<?php

namespace App\Filament\Clinic\Resources\Users\Schemas;

use App\Models\Location;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
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
                    ->options(User::clinicRoleOptions())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label('Location')
                    ->options(fn (): array => Location::query()
                        ->when(auth()->user()?->clinic_id, fn ($query, $clinicId) => $query->where('clinic_id', $clinicId))
                        ->orderBy('location_name')
                        ->pluck('location_name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
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
