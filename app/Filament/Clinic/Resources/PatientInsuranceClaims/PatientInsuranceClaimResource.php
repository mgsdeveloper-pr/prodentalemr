<?php

namespace App\Filament\Clinic\Resources\PatientInsuranceClaims;

use App\Filament\Clinic\Resources\PatientInsuranceClaims\Pages\CreatePatientInsuranceClaim;
use App\Filament\Clinic\Resources\PatientInsuranceClaims\Pages\EditPatientInsuranceClaim;
use App\Filament\Clinic\Resources\PatientInsuranceClaims\Pages\ListPatientInsuranceClaims;
use App\Filament\Clinic\Resources\PatientInsuranceClaims\Pages\ViewPatientInsuranceClaim;
use App\Filament\Clinic\Resources\PatientInsuranceClaims\Schemas\PatientInsuranceClaimForm;
use App\Filament\Clinic\Resources\PatientInsuranceClaims\Schemas\PatientInsuranceClaimInfolist;
use App\Filament\Clinic\Resources\PatientInsuranceClaims\Tables\PatientInsuranceClaimsTable;
use App\Models\PatientInsuranceClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PatientInsuranceClaimResource extends Resource
{
    protected static ?string $model = PatientInsuranceClaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Insurance Claims';

    protected static string|UnitEnum|null $navigationGroup = 'Insurance Verification';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return PatientInsuranceClaimForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientInsuranceClaimInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientInsuranceClaimsTable::configure($table);
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
            ->with(['patient', 'insurancePolicy', 'provider.user', 'location', 'creator', 'lineItems']);

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
        return auth()->user()?->canAccessClinicInsuranceClaims() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicInsuranceClaims() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicInsuranceClaims() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicInsuranceClaims() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatientInsuranceClaims::route('/'),
            'create' => CreatePatientInsuranceClaim::route('/create'),
            'view' => ViewPatientInsuranceClaim::route('/{record}'),
            'edit' => EditPatientInsuranceClaim::route('/{record}/edit'),
        ];
    }
}
