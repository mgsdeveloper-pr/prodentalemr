<?php

namespace App\Filament\Clinic\Resources\Encounters;

use App\Filament\Clinic\Resources\Encounters\Pages\CreateEncounter;
use App\Filament\Clinic\Resources\Encounters\Pages\EditEncounter;
use App\Filament\Clinic\Resources\Encounters\Pages\ListEncounters;
use App\Filament\Clinic\Resources\Encounters\Pages\ViewEncounter;
use App\Filament\Clinic\Resources\Encounters\Schemas\EncounterForm;
use App\Filament\Clinic\Resources\Encounters\Schemas\EncounterInfolist;
use App\Filament\Clinic\Resources\Encounters\Tables\EncountersTable;
use App\Models\Encounter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EncounterResource extends Resource
{
    protected static ?string $model = Encounter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Encounters';

    protected static string|UnitEnum|null $navigationGroup = 'Clinical Records';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return EncounterForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EncounterInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EncountersTable::configure($table);
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
            ->with(['patient', 'provider.user', 'appointment', 'location', 'creator']);

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
        return auth()->user()?->canAccessClinicEncounters() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicEncounters() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicEncounters() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicEncounters() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEncounters::route('/'),
            'create' => CreateEncounter::route('/create'),
            'view' => ViewEncounter::route('/{record}'),
            'edit' => EditEncounter::route('/{record}/edit'),
        ];
    }
}
