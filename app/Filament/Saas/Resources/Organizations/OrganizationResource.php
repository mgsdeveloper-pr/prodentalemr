<?php

namespace App\Filament\Saas\Resources\Organizations;

use App\Filament\Saas\Resources\Organizations\RelationManagers\ClinicsRelationManager;
use App\Filament\Saas\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Saas\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Saas\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Saas\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Saas\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Saas\Resources\Organizations\Schemas\OrganizationInfolist;
use App\Filament\Saas\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Organizations';

    protected static string|UnitEnum|null $navigationGroup = 'Organizations';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrganizationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ClinicsRelationManager::class,
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
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'view' => ViewOrganization::route('/{record}'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }
}
