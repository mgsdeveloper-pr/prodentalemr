<?php

namespace App\Filament\Saas\Resources\Locations;

use App\Filament\Saas\Resources\Locations\Pages\CreateLocation;
use App\Filament\Saas\Resources\Locations\Pages\EditLocation;
use App\Filament\Saas\Resources\Locations\Pages\ListLocations;
use App\Filament\Saas\Resources\Locations\Pages\ViewLocation;
use App\Filament\Saas\Resources\Locations\Schemas\LocationForm;
use App\Filament\Saas\Resources\Locations\Schemas\LocationInfolist;
use App\Filament\Saas\Resources\Locations\Tables\LocationsTable;
use App\Models\Location;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Locations';

    protected static string|UnitEnum|null $navigationGroup = 'Organizations';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'location_name';

    public static function form(Schema $schema): Schema
    {
        return LocationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LocationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('locations') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('locations', 'add') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('locations', 'update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('locations', 'delete') ?? false;
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
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'view' => ViewLocation::route('/{record}'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
