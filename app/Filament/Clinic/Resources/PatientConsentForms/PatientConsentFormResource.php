<?php

namespace App\Filament\Clinic\Resources\PatientConsentForms;

use App\Filament\Clinic\Resources\PatientConsentForms\Pages\CreatePatientConsentForm;
use App\Filament\Clinic\Resources\PatientConsentForms\Pages\EditPatientConsentForm;
use App\Filament\Clinic\Resources\PatientConsentForms\Pages\ListPatientConsentForms;
use App\Filament\Clinic\Resources\PatientConsentForms\Pages\ViewPatientConsentForm;
use App\Filament\Clinic\Resources\PatientConsentForms\Schemas\PatientConsentFormForm;
use App\Filament\Clinic\Resources\PatientConsentForms\Schemas\PatientConsentFormInfolist;
use App\Filament\Clinic\Resources\PatientConsentForms\Tables\PatientConsentFormsTable;
use App\Models\PatientConsentForm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PatientConsentFormResource extends Resource
{
    protected static ?string $model = PatientConsentForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Consent Forms';

    protected static string|UnitEnum|null $navigationGroup = 'Clinical Records';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return PatientConsentFormForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientConsentFormInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientConsentFormsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['patient', 'provider.user', 'location', 'uploader']);

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
        return auth()->user()?->canAccessClinicConsentForms() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicConsentForms() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicConsentForms() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicConsentForms() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatientConsentForms::route('/'),
            'create' => CreatePatientConsentForm::route('/create'),
            'view' => ViewPatientConsentForm::route('/{record}'),
            'edit' => EditPatientConsentForm::route('/{record}/edit'),
        ];
    }
}
