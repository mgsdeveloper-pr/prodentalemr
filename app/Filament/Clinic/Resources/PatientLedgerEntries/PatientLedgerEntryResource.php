<?php

namespace App\Filament\Clinic\Resources\PatientLedgerEntries;

use App\Filament\Clinic\Resources\PatientLedgerEntries\Pages\CreatePatientLedgerEntry;
use App\Filament\Clinic\Resources\PatientLedgerEntries\Pages\EditPatientLedgerEntry;
use App\Filament\Clinic\Resources\PatientLedgerEntries\Pages\ListPatientLedgerEntries;
use App\Filament\Clinic\Resources\PatientLedgerEntries\Pages\ViewPatientLedgerEntry;
use App\Filament\Clinic\Resources\PatientLedgerEntries\Schemas\PatientLedgerEntryForm;
use App\Filament\Clinic\Resources\PatientLedgerEntries\Schemas\PatientLedgerEntryInfolist;
use App\Filament\Clinic\Resources\PatientLedgerEntries\Tables\PatientLedgerEntriesTable;
use App\Models\PatientLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PatientLedgerEntryResource extends Resource
{
    protected static ?string $model = PatientLedgerEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Patient Ledger';

    protected static string|UnitEnum|null $navigationGroup = 'Financial Records';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return PatientLedgerEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientLedgerEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientLedgerEntriesTable::configure($table);
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
            ->with(['patient', 'provider.user', 'location', 'appointment', 'encounter', 'treatmentPlan', 'clinicService', 'creator']);

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
        return auth()->user()?->canAccessClinicPatientLedger() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicPatientLedger() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicPatientLedger() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicPatientLedger() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatientLedgerEntries::route('/'),
            'create' => CreatePatientLedgerEntry::route('/create'),
            'view' => ViewPatientLedgerEntry::route('/{record}'),
            'edit' => EditPatientLedgerEntry::route('/{record}/edit'),
        ];
    }
}
