<?php

namespace App\Filament\Saas\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription')
                    ->schema([
                        Select::make('subscription_scope')
                            ->label('Billing scope')
                            ->default('organization')
                            ->live()
                            ->required()
                            ->options([
                                'dso' => 'DSO-level subscription',
                                'organization' => 'Organization-level subscription',
                                'clinic' => 'Clinic-level subscription',
                            ])
                            ->native(false),
                        Select::make('dso_id')
                            ->label('DSO')
                            ->relationship('dso', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('subscription_scope') === 'dso')
                            ->required(fn (Get $get): bool => $get('subscription_scope') === 'dso'),
                        Select::make('organization_id')
                            ->label('Organization')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => in_array($get('subscription_scope'), ['organization', 'clinic'], true))
                            ->required(fn (Get $get): bool => in_array($get('subscription_scope'), ['organization', 'clinic'], true)),
                        Select::make('clinic_id')
                            ->label('Clinic')
                            ->relationship('clinic', 'clinic_name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('subscription_scope') === 'clinic')
                            ->required(fn (Get $get): bool => $get('subscription_scope') === 'clinic'),
                        Select::make('subscription_plan_id')
                            ->label('Subscription plan')
                            ->relationship('subscriptionPlan', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('previous_subscription_plan_id')
                            ->label('Previous plan')
                            ->relationship('previousSubscriptionPlan', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('change_type')
                            ->label('Plan change')
                            ->options([
                                'new' => 'New subscription',
                                'upgrade' => 'Upgrade',
                                'downgrade' => 'Downgrade',
                                'renewal' => 'Renewal',
                                'cancellation' => 'Cancellation',
                            ])
                            ->native(false),
                        DatePicker::make('start_date')->required(),
                        DatePicker::make('end_date'),
                        DatePicker::make('effective_date'),
                        DatePicker::make('renewal_date'),
                        Select::make('status')
                            ->required()
                            ->default('active')
                            ->options([
                                'active' => 'Active',
                                'trial' => 'Trial',
                                'paused' => 'Paused',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->native(false),
                        Select::make('service_status')
                            ->label('Service status')
                            ->default('active')
                            ->options([
                                'active' => 'Active',
                                'trial' => 'Trial',
                                'suspended' => 'Suspended',
                                'cancelled' => 'Cancelled',
                                'pending_setup' => 'Pending setup',
                            ])
                            ->native(false),
                        Toggle::make('cancel_at_period_end')
                            ->label('Cancel at period end')
                            ->default(false)
                            ->inline(false),
                        Toggle::make('is_demo')
                            ->label('Demo subscription')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make('Trial, Billing & Ownership')
                    ->schema([
                        DatePicker::make('trial_starts_at'),
                        DatePicker::make('trial_ends_at'),
                        Select::make('proration_mode')
                            ->options([
                                'none' => 'No proration',
                                'credit' => 'Credit',
                                'charge' => 'Charge',
                                'manual' => 'Manual adjustment',
                            ])
                            ->default('none')
                            ->native(false),
                        TextInput::make('proration_amount')
                            ->numeric()
                            ->prefix('$')
                            ->step('0.01'),
                        Select::make('account_manager_user_id')
                            ->label('Account manager')
                            ->relationship('accountManager', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('service_status_reason')
                            ->label('Service status reason')
                            ->maxLength(255),
                        Textarea::make('internal_notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('billing_notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }
}
