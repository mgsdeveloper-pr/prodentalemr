<?php

namespace App\Filament\Saas\Resources\Users\Schemas;

use App\Models\User;
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
                    ->options(fn (): array => auth()->user()?->isSaasAdmin()
                        ? User::saasRoleOptions()
                        : collect(User::saasRoleOptions())
                            ->except('saas_admin')
                            ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
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
