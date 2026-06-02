<?php

namespace App\Filament\Saas\Resources\Subscriptions;

use App\Filament\Saas\Resources\Subscriptions\Pages\CreateSubscription;
use App\Filament\Saas\Resources\Subscriptions\Pages\EditSubscription;
use App\Filament\Saas\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Saas\Resources\Subscriptions\Pages\ViewSubscription;
use App\Filament\Saas\Resources\Subscriptions\Schemas\SubscriptionForm;
use App\Filament\Saas\Resources\Subscriptions\Schemas\SubscriptionInfolist;
use App\Filament\Saas\Resources\Subscriptions\Tables\SubscriptionsTable;
use App\Models\Subscription;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static string|UnitEnum|null $navigationGroup = 'Plans';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return SubscriptionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SubscriptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('subscriptions') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('subscriptions', 'add') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('subscriptions', 'update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('subscriptions', 'delete') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
            'view' => ViewSubscription::route('/{record}'),
            'edit' => EditSubscription::route('/{record}/edit'),
        ];
    }
}
