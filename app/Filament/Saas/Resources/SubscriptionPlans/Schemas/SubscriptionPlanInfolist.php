<?php

namespace App\Filament\Saas\Resources\SubscriptionPlans\Schemas;

use App\Models\SubscriptionPlan;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Overview')
                    ->description('Commercial structure and current usage footprint for this subscription plan.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Plan name')
                                    ->columnSpan(2),
                                TextEntry::make('plan_type')
                                    ->label('Plan type')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => SubscriptionPlan::planTypeOptions()[$state] ?? 'Not set')
                                    ->color('primary'),
                                TextEntry::make('workspace_mode')
                                    ->label('Workspace')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => SubscriptionPlan::workspaceModeOptions()[$state] ?? 'Not set')
                                    ->color('info'),
                                TextEntry::make('price')
                                    ->money('USD')
                                    ->badge()
                                    ->color('success'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('max_clinics')
                                    ->numeric()
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('max_users')
                                    ->numeric()
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('included_modules_count')
                                    ->label('Modules')
                                    ->state(fn ($record): int => count($record->included_modules ?? []))
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('subscriptions_count')
                                    ->label('Subscriptions')
                                    ->state(fn ($record): int => $record->subscriptions()->count())
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ]),
                Section::make('Included Modules')
                    ->description('Clinic-side product modules included in this subscription plan.')
                    ->schema([
                        TextEntry::make('included_module_labels')
                            ->label('')
                            ->badge()
                            ->separator(', ')
                            ->state(fn ($record): array => $record->included_module_labels)
                            ->columnSpanFull(),
                    ]),
                Section::make('Included Features')
                    ->description('Feature switches controlled by this plan.')
                    ->schema([
                        TextEntry::make('included_features')
                            ->label('')
                            ->badge()
                            ->separator(', ')
                            ->state(fn ($record): array => collect($record->included_features ?? [])
                                ->map(fn (string $feature): string => SubscriptionPlan::featureOptions()[$feature] ?? str($feature)->replace('_', ' ')->headline())
                                ->values()
                                ->all())
                            ->columnSpanFull(),
                    ]),
                Section::make('Limits & Add-ons')
                    ->description('Operational limits and optional service eligibility.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('plan_limits.storage_mb')
                                    ->label('Storage MB')
                                    ->badge(),
                                TextEntry::make('plan_limits.monthly_verifications')
                                    ->label('Monthly verifications')
                                    ->placeholder('Unlimited')
                                    ->badge(),
                                TextEntry::make('plan_limits.mailbox_storage_mb')
                                    ->label('Mailbox MB')
                                    ->badge(),
                                TextEntry::make('plan_limits.import_rows')
                                    ->label('Import rows')
                                    ->badge(),
                                TextEntry::make('plan_limits.attachment_mb')
                                    ->label('Attachment MB')
                                    ->badge(),
                                TextEntry::make('trial_days')
                                    ->label('Trial days')
                                    ->placeholder('No trial')
                                    ->badge(),
                                IconEntry::make('managed_services_allowed')
                                    ->label('Managed Services')
                                    ->boolean(),
                                IconEntry::make('demo_mode_available')
                                    ->label('Demo mode')
                                    ->boolean(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
