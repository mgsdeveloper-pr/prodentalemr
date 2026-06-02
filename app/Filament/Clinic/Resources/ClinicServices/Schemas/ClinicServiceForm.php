<?php

namespace App\Filament\Clinic\Resources\ClinicServices\Schemas;

use App\Models\Location;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClinicServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Select::make('location_id')
                    ->label('Location')
                    ->options(fn (): array => Location::query()
                        ->where('clinic_id', auth()->user()?->clinic_id)
                        ->orderBy('location_name')
                        ->pluck('location_name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                TextInput::make('service_code')
                    ->label('Service code')
                    ->maxLength(100),
                TextInput::make('name')
                    ->label('Service name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('category')
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('default_fee')
                    ->label('Default fee')
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
