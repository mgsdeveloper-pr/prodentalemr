<?php

namespace App\Filament\Saas\Resources\Organizations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization')
                    ->schema([
                        Select::make('dso_id')
                            ->label('DSO / Enterprise account')
                            ->relationship('dso', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Optional. Use this when multiple organizations belong to the same DSO customer.'),
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
                    ->columns(2),
                Section::make('Lifecycle & Ownership')
                    ->schema([
                        Select::make('lifecycle_status')
                            ->label('Lifecycle status')
                            ->default('active')
                            ->options([
                                'prospect' => 'Prospect',
                                'onboarding' => 'Onboarding',
                                'active' => 'Active',
                                'at_risk' => 'At risk',
                                'paused' => 'Paused',
                                'cancelled' => 'Cancelled',
                            ])
                            ->native(false),
                        Select::make('onboarding_status')
                            ->label('Onboarding status')
                            ->default('pending')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In progress',
                                'blocked' => 'Blocked',
                                'complete' => 'Complete',
                            ])
                            ->native(false),
                        Select::make('account_manager_user_id')
                            ->label('Account manager')
                            ->relationship('accountManager', 'name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('internal_notes')
                            ->label('Internal notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }
}
