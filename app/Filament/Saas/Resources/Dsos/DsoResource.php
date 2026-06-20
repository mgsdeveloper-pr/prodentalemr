<?php

namespace App\Filament\Saas\Resources\Dsos;

use App\Filament\Saas\Resources\Dsos\Pages\CreateDso;
use App\Filament\Saas\Resources\Dsos\Pages\EditDso;
use App\Filament\Saas\Resources\Dsos\Pages\ListDsos;
use App\Filament\Saas\Resources\Dsos\Pages\ViewDso;
use App\Filament\Saas\Resources\Dsos\RelationManagers\OrganizationsRelationManager;
use App\Filament\Saas\Resources\Dsos\Schemas\DsoForm;
use App\Filament\Saas\Resources\Dsos\Schemas\DsoInfolist;
use App\Filament\Saas\Resources\Dsos\Tables\DsosTable;
use App\Models\Dso;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DsoResource extends Resource
{
    protected static ?string $model = Dso::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'DSOs';

    protected static string|UnitEnum|null $navigationGroup = 'Organizations';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DsoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DsoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DsosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OrganizationsRelationManager::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('organizations') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('organizations', 'add') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('organizations', 'update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('organizations', 'delete') ?? false;
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
            'index' => ListDsos::route('/'),
            'create' => CreateDso::route('/create'),
            'view' => ViewDso::route('/{record}'),
            'edit' => EditDso::route('/{record}/edit'),
        ];
    }
}
