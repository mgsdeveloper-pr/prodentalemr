<?php

namespace App\Filament\Saas\Resources\Organizations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Organization name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('owner_name')
                    ->label('Owner name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null)
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->default(null)
                    ->maxLength(255),
                Textarea::make('address')
                    ->label('Billing address')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('city')
                    ->maxLength(255),
                TextInput::make('state')
                    ->maxLength(255),
                TextInput::make('zip_code')
                    ->label('ZIP code')
                    ->maxLength(255),
                TextInput::make('country')
                    ->default('USA')
                    ->maxLength(255),
                Toggle::make('status')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ])
            ->columns(2);
    }
}
