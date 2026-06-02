<?php

namespace App\Filament\Saas\Resources\ServiceItems;

use App\Filament\Saas\Resources\ServiceItems\Pages\CreateServiceItem;
use App\Filament\Saas\Resources\ServiceItems\Pages\EditServiceItem;
use App\Filament\Saas\Resources\ServiceItems\Pages\ListServiceItems;
use App\Filament\Saas\Resources\ServiceItems\Pages\ViewServiceItem;
use App\Filament\Saas\Resources\ServiceItems\Schemas\ServiceItemForm;
use App\Filament\Saas\Resources\ServiceItems\Schemas\ServiceItemInfolist;
use App\Filament\Saas\Resources\ServiceItems\Tables\ServiceItemsTable;
use App\Models\ServiceItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ServiceItemResource extends Resource
{
    protected static ?string $model = ServiceItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Service List';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 12;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ServiceItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServiceItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('service_items') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('service_items', 'add') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('service_items', 'update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('service_items', 'delete') ?? false;
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
            'index' => ListServiceItems::route('/'),
            'create' => CreateServiceItem::route('/create'),
            'view' => ViewServiceItem::route('/{record}'),
            'edit' => EditServiceItem::route('/{record}/edit'),
        ];
    }
}
