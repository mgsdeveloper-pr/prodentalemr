<?php

namespace App\Filament\Clinic\Resources\Locations;

use App\Filament\Clinic\Resources\Locations\Pages\CreateLocation;
use App\Filament\Clinic\Resources\Locations\Pages\EditLocation;
use App\Filament\Clinic\Resources\Locations\Pages\ListLocations;
use App\Filament\Clinic\Resources\Locations\Pages\ViewLocation;
use App\Filament\Clinic\Resources\Locations\Schemas\LocationForm;
use App\Filament\Clinic\Resources\Locations\Schemas\LocationInfolist;
use App\Filament\Clinic\Resources\Locations\Tables\LocationsTable;
use App\Models\Location;
use App\Support\ClinicAdministrationAccess;
use App\Support\ClinicPanelScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Locations';

    protected static string|UnitEnum|null $navigationGroup = 'Clinic Management';

    protected static ?int $navigationSort = 2;

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

    public static function getEloquentQuery(): Builder
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->when($clinicId, fn (Builder $query): Builder => $query->where('clinic_id', $clinicId))
            ->when(! $clinicId, fn (Builder $query): Builder => $query->whereRaw('1 = 0'));
    }

    public static function canViewAny(): bool
    {
        return ClinicAdministrationAccess::canView('locations');
    }

    public static function canView($record): bool
    {
        return static::canViewAny() && (int) $record->clinic_id === ClinicPanelScope::selectedClinicId();
    }

    public static function canCreate(): bool
    {
        return ClinicAdministrationAccess::canMutate('locations', 'add');
    }

    public static function canEdit($record): bool
    {
        return static::canView($record) && ClinicAdministrationAccess::canMutate('locations', 'update');
    }

    public static function canDelete($record): bool
    {
        return static::canView($record) && ClinicAdministrationAccess::canMutate('locations', 'delete');
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
