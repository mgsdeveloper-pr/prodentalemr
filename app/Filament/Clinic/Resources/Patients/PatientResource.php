<?php

namespace App\Filament\Clinic\Resources\Patients;

use App\Filament\Clinic\Resources\Patients\Pages\CreatePatient;
use App\Filament\Clinic\Resources\Patients\Pages\EditPatient;
use App\Filament\Clinic\Resources\Patients\Pages\ListPatients;
use App\Filament\Clinic\Resources\Patients\Pages\ViewPatient;
use App\Filament\Clinic\Resources\Patients\Schemas\PatientForm;
use App\Filament\Clinic\Resources\Patients\Schemas\PatientInfolist;
use App\Filament\Clinic\Resources\Patients\Tables\PatientsTable;
use App\Models\Patient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Patients';

    protected static string|UnitEnum|null $navigationGroup = 'Patient Care';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
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
            ->with(['location', 'creator', 'appointments'])
            ->withCount(['appointments', 'insurancePolicies'])
            ->withSum('ledgerEntries as ledger_debit_total', 'debit_amount')
            ->withSum('ledgerEntries as ledger_credit_total', 'credit_amount');

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
        return auth()->user()?->canAccessClinicPatients() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicPatients() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicPatients() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicPatients() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'view' => ViewPatient::route('/{record}'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }
}
