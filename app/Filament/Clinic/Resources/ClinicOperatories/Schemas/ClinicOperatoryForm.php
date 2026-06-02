<?php

namespace App\Filament\Clinic\Resources\ClinicOperatories\Schemas;

use App\Models\Location;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClinicOperatoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth()->user()?->organization_id),
                Hidden::make('clinic_id')
                    ->default(fn () => auth()->user()?->clinic_id),
                Section::make('Operatory Setup')
                    ->description('Define treatment chairs and operatories so the schedule can reflect how work actually flows across the clinic floor.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Operatory name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('code')
                                    ->label('Operatory code')
                                    ->maxLength(50),
                                TextInput::make('display_order')
                                    ->label('Display order')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),
                                Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn (): array => Location::query()
                                        ->where('clinic_id', auth()->user()?->clinic_id)
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Toggle::make('status')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
