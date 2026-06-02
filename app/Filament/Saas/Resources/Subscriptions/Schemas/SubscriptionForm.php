<?php

namespace App\Filament\Saas\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('subscription_plan_id')
                    ->label('Subscription plan')
                    ->relationship('subscriptionPlan', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date'),
                Select::make('status')
                    ->required()
                    ->default('active')
                    ->options([
                        'active' => 'Active',
                        'trial' => 'Trial',
                        'paused' => 'Paused',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),
            ]);
    }
}
