<?php

namespace App\Filament\Clinic\Resources\PatientDocuments;

use App\Filament\Clinic\Resources\PatientDocuments\Pages\CreatePatientDocument;
use App\Filament\Clinic\Resources\PatientDocuments\Pages\EditPatientDocument;
use App\Filament\Clinic\Resources\PatientDocuments\Pages\ListPatientDocuments;
use App\Filament\Clinic\Resources\PatientDocuments\Pages\ViewPatientDocument;
use App\Filament\Clinic\Resources\PatientDocuments\Schemas\PatientDocumentForm;
use App\Filament\Clinic\Resources\PatientDocuments\Schemas\PatientDocumentInfolist;
use App\Filament\Clinic\Resources\PatientDocuments\Tables\PatientDocumentsTable;
use App\Models\PatientDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PatientDocumentResource extends Resource
{
    protected static ?string $model = PatientDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $navigationLabel = 'Patient Documents';

    protected static string|UnitEnum|null $navigationGroup = 'Clinical Records';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return PatientDocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientDocumentsTable::configure($table);
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
            ->with(['patient', 'provider.user', 'encounter', 'location', 'uploader']);

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
        return auth()->user()?->canAccessClinicPatientDocuments() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicPatientDocuments() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicPatientDocuments() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicPatientDocuments() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatientDocuments::route('/'),
            'create' => CreatePatientDocument::route('/create'),
            'view' => ViewPatientDocument::route('/{record}'),
            'edit' => EditPatientDocument::route('/{record}/edit'),
        ];
    }
}
