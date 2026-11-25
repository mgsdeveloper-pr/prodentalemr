<?php

namespace App\Filament\Saas\Resources\SubscriptionPlans\Schemas;

use App\Models\SubscriptionPlan;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                            ->label('Price (in selected currency)')
                            ->minValue(0)
                            ->step('0.01'),
                        TextInput::make('plan_code')
                            ->label('Plan code')
                            ->maxLength(80)
                            ->unique(ignoreRecord: true),
                        Select::make('plan_type')
                            ->label('Plan type')
                            ->options(SubscriptionPlan::planTypeOptions())
                            ->default(SubscriptionPlan::PLAN_TYPE_VERIFICATION)
                            ->required()
                            ->native(false),
                        Select::make('workspace_mode')
                            ->label('Workspace routing')
                            ->options(SubscriptionPlan::workspaceModeOptions())
                            ->default(SubscriptionPlan::WORKSPACE_VERIFICATION)
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
                Section::make('Website Plan Options')
                    ->description('Only active Verification plans with Verification workspace routing and no trial appear on the website. Checkout collects the first billing period when configured; renewals are handled separately. Custom quotes require an agreed price before payment.')
                    ->schema([
                        Select::make('verification_model')->label('Offering')->options(fn (?SubscriptionPlan $record) => $record?->verification_model === 'hybrid'
                            ? ['self_service' => 'Verification Platform', 'hybrid' => 'Managed Verification — Platform Included']
                            : SubscriptionPlan::verificationModelOptions(false))->default('self_service')->required(),
                        Select::make('billing_interval')->options(['monthly' => 'Monthly', 'yearly' => 'Yearly', 'one_time' => 'One time', 'custom' => 'Custom agreement'])->default('monthly')->required(),
                        Select::make('currency')->options(['USD' => 'USD', 'INR' => 'INR', 'GBP' => 'GBP', 'EUR' => 'EUR', 'CAD' => 'CAD', 'AUD' => 'AUD'])->default('USD')->required(),
                        Toggle::make('quote_required')->label('Request a quote instead of displaying a price')->default(false),
                        Textarea::make('website_description')->label('Public description')->maxLength(1200)->columnSpanFull(),
                        TextInput::make('website_sort_order')->label('Website display order')->numeric()->integer()->minValue(0)->default(0),
                        Toggle::make('website_published')->label('Publish on website')->default(false)
                            ->rules([fn (\Filament\Schemas\Components\Utilities\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($value && ($get('plan_type') !== 'verification' || $get('workspace_mode') !== 'verification' || (int) $get('trial_days') > 0 || ! $get('status'))) {
                                    $fail('Published website plans must be active Verification plans, use Verification workspace routing, and have zero trial days.');
                                }
                            }]),
                    ])->columns(2)->columnSpanFull(),
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
                            ->default(0)
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
                        TextInput::make('plan_limits.monthly_call_minutes')
                            ->label('Monthly call minutes')
                            ->numeric()
                            ->default(SubscriptionPlan::defaultPlanLimits()['monthly_call_minutes'])
                            ->minValue(0),
                        TextInput::make('plan_limits.calling_users')
                            ->label('Calling users')
                            ->numeric()
                            ->default(SubscriptionPlan::defaultPlanLimits()['calling_users'])
                            ->minValue(0),
                        TextInput::make('plan_limits.recording_retention_days')
                            ->label('Recording retention (days)')
                            ->numeric()
                            ->default(SubscriptionPlan::defaultPlanLimits()['recording_retention_days'])
                            ->minValue(1),
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
                                'coreModules' => SubscriptionPlan::coreClinicModules(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }
}
