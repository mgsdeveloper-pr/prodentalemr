<?php

namespace App\Filament\Saas\Resources\ServiceItems\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Service name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('default_price')
                    ->label('Default price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->minValue(0)
                    ->step('0.01'),
                Toggle::make('status')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ])
            ->columns(2);
    }
}
