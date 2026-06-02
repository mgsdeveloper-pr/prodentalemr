<?php

namespace App\Filament\Clinic\Resources\PatientStatements;

use App\Filament\Clinic\Resources\PatientStatements\Pages\CreatePatientStatement;
use App\Filament\Clinic\Resources\PatientStatements\Pages\EditPatientStatement;
use App\Filament\Clinic\Resources\PatientStatements\Pages\ListPatientStatements;
use App\Filament\Clinic\Resources\PatientStatements\Pages\ViewPatientStatement;
use App\Filament\Clinic\Resources\PatientStatements\Schemas\PatientStatementForm;
use App\Filament\Clinic\Resources\PatientStatements\Schemas\PatientStatementInfolist;
use App\Filament\Clinic\Resources\PatientStatements\Tables\PatientStatementsTable;
use App\Models\PatientStatement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PatientStatementResource extends Resource
{
    protected static ?string $model = PatientStatement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Patient Statements';

    protected static string|UnitEnum|null $navigationGroup = 'Financial Records';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return PatientStatementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientStatementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientStatementsTable::configure($table);
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
            ->with(['patient', 'location', 'creator', 'sender']);

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
        return auth()->user()?->canAccessClinicPatientStatements() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicPatientStatements() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicPatientStatements() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicPatientStatements() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatientStatements::route('/'),
            'create' => CreatePatientStatement::route('/create'),
            'view' => ViewPatientStatement::route('/{record}'),
            'edit' => EditPatientStatement::route('/{record}/edit'),
        ];
    }
}
