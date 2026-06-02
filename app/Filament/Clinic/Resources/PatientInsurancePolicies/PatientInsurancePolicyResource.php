<?php

namespace App\Filament\Clinic\Resources\PatientInsurancePolicies;

use App\Filament\Clinic\Resources\PatientInsurancePolicies\Pages\CreatePatientInsurancePolicy;
use App\Filament\Clinic\Resources\PatientInsurancePolicies\Pages\EditPatientInsurancePolicy;
use App\Filament\Clinic\Resources\PatientInsurancePolicies\Pages\ListPatientInsurancePolicies;
use App\Filament\Clinic\Resources\PatientInsurancePolicies\Pages\ViewPatientInsurancePolicy;
use App\Filament\Clinic\Resources\PatientInsurancePolicies\Schemas\PatientInsurancePolicyForm;
use App\Filament\Clinic\Resources\PatientInsurancePolicies\Schemas\PatientInsurancePolicyInfolist;
use App\Filament\Clinic\Resources\PatientInsurancePolicies\Tables\PatientInsurancePoliciesTable;
use App\Models\PatientInsurancePolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PatientInsurancePolicyResource extends Resource
{
    protected static ?string $model = PatientInsurancePolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Insurance Policies';

    protected static string|UnitEnum|null $navigationGroup = 'Insurance Verification';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return PatientInsurancePolicyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientInsurancePolicyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientInsurancePoliciesTable::configure($table);
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
            ->with(['patient', 'location', 'creator']);

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
        return auth()->user()?->canAccessClinicInsurancePolicies() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicInsurancePolicies() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicInsurancePolicies() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicInsurancePolicies() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatientInsurancePolicies::route('/'),
            'create' => CreatePatientInsurancePolicy::route('/create'),
            'view' => ViewPatientInsurancePolicy::route('/{record}'),
            'edit' => EditPatientInsurancePolicy::route('/{record}/edit'),
        ];
    }
}
