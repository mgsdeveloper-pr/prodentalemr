<?php

namespace App\Filament\Clinic\Resources\Providers;

use App\Filament\Clinic\Resources\Providers\Pages\CreateProvider;
use App\Filament\Clinic\Resources\Providers\Pages\EditProvider;
use App\Filament\Clinic\Resources\Providers\Pages\ListProviders;
use App\Filament\Clinic\Resources\Providers\Pages\ViewProvider;
use App\Filament\Clinic\Resources\Providers\Schemas\ProviderForm;
use App\Filament\Clinic\Resources\Providers\Schemas\ProviderInfolist;
use App\Filament\Clinic\Resources\Providers\Tables\ProvidersTable;
use App\Models\Provider;
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

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Providers';

    protected static string|UnitEnum|null $navigationGroup = 'Clinic Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return ProviderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProviderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProvidersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['user.roles', 'location'])
            ->withCount('appointments');

        $clinicId = ClinicPanelScope::selectedClinicId();
        $organizationId = ClinicPanelScope::selectedOrganizationId();

        if (! $organizationId || ! $clinicId) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId);
    }

    public static function canAccess(): bool
    {
        return ClinicAdministrationAccess::canView('providers');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return ClinicAdministrationAccess::canMutate('providers', 'add');
    }

    public static function canView($record): bool
    {
        return static::canAccess() && (int) $record->clinic_id === ClinicPanelScope::selectedClinicId();
    }

    public static function canEdit($record): bool
    {
        return static::canView($record) && ClinicAdministrationAccess::canMutate('providers', 'update');
    }

    public static function canDelete($record): bool
    {
        return static::canView($record) && ClinicAdministrationAccess::canMutate('providers', 'delete');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviders::route('/'),
            'create' => CreateProvider::route('/create'),
            'view' => ViewProvider::route('/{record}'),
            'edit' => EditProvider::route('/{record}/edit'),
        ];
    }
}
