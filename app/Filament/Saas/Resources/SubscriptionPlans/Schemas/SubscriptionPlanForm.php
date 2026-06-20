<?php

namespace App\Filament\Saas\Resources\SubscriptionPlans\Schemas;

use App\Models\SubscriptionPlan;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
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
                        TextInput::make('plan_code')
                            ->label('Plan code')
                            ->maxLength(80)
                            ->unique(ignoreRecord: true),
                        Select::make('plan_type')
                            ->label('Plan type')
                            ->options(SubscriptionPlan::planTypeOptions())
                            ->default(SubscriptionPlan::PLAN_TYPE_PMS_VERIFICATION)
                            ->required()
                            ->native(false),
                        Select::make('workspace_mode')
                            ->label('Workspace routing')
                            ->options(SubscriptionPlan::workspaceModeOptions())
                            ->default(SubscriptionPlan::WORKSPACE_CHOOSE)
                            ->required()
                            ->native(false),
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
                Section::make('Plan Features & Limits')
                    ->description('Control optional feature access, managed services eligibility, trial availability, and usage limits.')
                    ->schema([
                        CheckboxList::make('included_features')
                            ->label('Included features')
                            ->options(SubscriptionPlan::featureOptions())
                            ->default(SubscriptionPlan::defaultIncludedFeatures())
                            ->columns(3)
                            ->bulkToggleable()
                            ->columnSpanFull(),
                        Toggle::make('managed_services_allowed')
                            ->label('Managed Services can be added')
                            ->default(false)
                            ->inline(false),
                        Toggle::make('demo_mode_available')
                            ->label('Demo mode available')
                            ->default(false)
                            ->inline(false),
                        TextInput::make('trial_days')
                            ->label('Trial days')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('plan_limits.storage_mb')
                            ->label('Storage limit (MB)')
                            ->numeric()
                            ->default(SubscriptionPlan::defaultPlanLimits()['storage_mb'])
                            ->minValue(0),
                        TextInput::make('plan_limits.monthly_verifications')
                            ->label('Monthly verifications')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('plan_limits.mailbox_storage_mb')
                            ->label('Mailbox storage (MB)')
                            ->numeric()
                            ->default(SubscriptionPlan::defaultPlanLimits()['mailbox_storage_mb'])
                            ->minValue(0),
                        TextInput::make('plan_limits.import_rows')
                            ->label('Import row limit')
                            ->numeric()
                            ->default(SubscriptionPlan::defaultPlanLimits()['import_rows'])
                            ->minValue(0),
                        TextInput::make('plan_limits.attachment_mb')
                            ->label('Attachment limit (MB)')
                            ->numeric()
                            ->default(SubscriptionPlan::defaultPlanLimits()['attachment_mb'])
                            ->minValue(0),
                    ])
                    ->columns(3)
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
