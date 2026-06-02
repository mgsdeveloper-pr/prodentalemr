<?php

namespace App\Filament\Saas\Resources\Clinics;

use App\Filament\Saas\Resources\Clinics\RelationManagers\LocationsRelationManager;
use App\Filament\Saas\Resources\Clinics\Pages\CreateClinic;
use App\Filament\Saas\Resources\Clinics\Pages\EditClinic;
use App\Filament\Saas\Resources\Clinics\Pages\ListClinics;
use App\Filament\Saas\Resources\Clinics\Pages\ViewClinic;
use App\Filament\Saas\Resources\Clinics\Schemas\ClinicForm;
use App\Filament\Saas\Resources\Clinics\Schemas\ClinicInfolist;
use App\Filament\Saas\Resources\Clinics\Tables\ClinicsTable;
use App\Models\Clinic;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClinicResource extends Resource
{
    protected static ?string $model = Clinic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Clinics';

    protected static string|UnitEnum|null $navigationGroup = 'Organizations';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'clinic_name';

    public static function form(Schema $schema): Schema
    {
        return ClinicForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClinicInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClinicsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LocationsRelationManager::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('clinics') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('clinics', 'add') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('clinics', 'update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('clinics', 'delete') ?? false;
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
            'index' => ListClinics::route('/'),
            'create' => CreateClinic::route('/create'),
            'view' => ViewClinic::route('/{record}'),
            'edit' => EditClinic::route('/{record}/edit'),
        ];
    }
}
