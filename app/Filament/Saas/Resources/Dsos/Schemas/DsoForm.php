<?php

namespace App\Filament\Saas\Resources\Dsos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DsoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('DSO Account')
                    ->description('Enterprise-level customer account that can own multiple organizations, practices, and clinics.')
                    ->schema([
                        TextInput::make('name')
                            ->label('DSO name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('legal_name')
                            ->label('Legal name')
                            ->maxLength(255),
                        TextInput::make('account_code')
                            ->label('Account code')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('primary_contact_name')
                            ->label('Primary contact')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Select::make('account_manager_user_id')
                            ->label('Account manager')
                            ->relationship('accountManager', 'name')
                            ->searchable()
                            ->preload(),
                        Toggle::make('status')
                            ->label('Active')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Service & Billing')
                    ->schema([
                        Select::make('lifecycle_status')
                            ->label('Lifecycle')
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
                        Select::make('billing_mode')
                            ->label('Billing mode')
                            ->default('centralized')
                            ->options([
                                'centralized' => 'Centralized DSO billing',
                                'organization' => 'Organization-level billing',
                                'clinic' => 'Clinic-level billing',
                                'hybrid' => 'Hybrid billing',
                            ])
                            ->native(false),
                        Select::make('service_status')
                            ->label('Service status')
                            ->default('active')
                            ->options([
                                'active' => 'Active',
                                'trial' => 'Trial',
                                'pending_setup' => 'Pending setup',
                                'suspended' => 'Suspended',
                                'cancelled' => 'Cancelled',
                            ])
                            ->native(false),
                        Textarea::make('internal_notes')
                            ->label('Internal notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Address')
                    ->schema([
                        Textarea::make('address')
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
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }
}
