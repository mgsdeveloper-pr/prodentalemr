<?php

namespace App\Filament\Saas\Resources\SubscriptionPlans\Schemas;

use App\Models\SubscriptionPlan;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Basics')
                    ->description('Define the commercial limits for this subscription plan.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step('0.01'),
                        TextInput::make('max_clinics')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        TextInput::make('max_users')
                            ->required()
                            ->numeric()
                            ->default(5)
                            ->minValue(1),
                        Toggle::make('status')
                            ->label('Active')
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Included Modules')
                    ->description('Choose the clinic-side product areas included in this subscription plan. This is the module bundle your customer is paying for.')
                    ->schema([
                        ViewField::make('included_modules')
                            ->label('')
                            ->default(SubscriptionPlan::defaultIncludedModules())
                            ->view('filament.saas.forms.subscription-plan-module-selector')
                            ->viewData([
                                'moduleGroups' => SubscriptionPlan::clinicModuleGroups(),
                                'moduleLabels' => SubscriptionPlan::clinicModuleOptions(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }
}
