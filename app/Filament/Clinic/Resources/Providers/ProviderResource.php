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

    protected static string|UnitEnum|null $navigationGroup = 'Patient Care';

    protected static ?int $navigationSort = 2;

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

        $user = auth()->user();

        if (! $user?->organization_id || ! $user?->clinic_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('organization_id', $user->organization_id)
            ->where('clinic_id', $user->clinic_id);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessClinicProviders() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicProviders() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicProviders() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicProviders() ?? false;
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
