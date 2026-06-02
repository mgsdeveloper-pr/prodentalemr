<?php

namespace App\Filament\Saas\Resources\SubscriptionPlans;

use App\Filament\Saas\Resources\SubscriptionPlans\Pages\CreateSubscriptionPlan;
use App\Filament\Saas\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan;
use App\Filament\Saas\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use App\Filament\Saas\Resources\SubscriptionPlans\Pages\ViewSubscriptionPlan;
use App\Filament\Saas\Resources\SubscriptionPlans\Schemas\SubscriptionPlanForm;
use App\Filament\Saas\Resources\SubscriptionPlans\Schemas\SubscriptionPlanInfolist;
use App\Filament\Saas\Resources\SubscriptionPlans\Tables\SubscriptionPlansTable;
use App\Models\SubscriptionPlan;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Subscription Plans';

    protected static string|UnitEnum|null $navigationGroup = 'Plans';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SubscriptionPlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SubscriptionPlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionPlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('subscription_plans') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('subscription_plans', 'add') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('subscription_plans', 'update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('subscription_plans', 'delete') ?? false;
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
            'index' => ListSubscriptionPlans::route('/'),
            'create' => CreateSubscriptionPlan::route('/create'),
            'view' => ViewSubscriptionPlan::route('/{record}'),
            'edit' => EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
