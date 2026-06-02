<?php

namespace App\Filament\Clinic\Resources\DentalChartEntries;

use App\Filament\Clinic\Resources\DentalChartEntries\Pages\CreateDentalChartEntry;
use App\Filament\Clinic\Resources\DentalChartEntries\Pages\EditDentalChartEntry;
use App\Filament\Clinic\Resources\DentalChartEntries\Pages\ListDentalChartEntries;
use App\Filament\Clinic\Resources\DentalChartEntries\Pages\ViewDentalChartEntry;
use App\Filament\Clinic\Resources\DentalChartEntries\Schemas\DentalChartEntryForm;
use App\Filament\Clinic\Resources\DentalChartEntries\Schemas\DentalChartEntryInfolist;
use App\Filament\Clinic\Resources\DentalChartEntries\Tables\DentalChartEntriesTable;
use App\Models\DentalChartEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DentalChartEntryResource extends Resource
{
    protected static ?string $model = DentalChartEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Chart Entries';

    protected static string|UnitEnum|null $navigationGroup = 'Dental Charting';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return DentalChartEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DentalChartEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DentalChartEntriesTable::configure($table);
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
            ->with(['patient', 'provider.user', 'location', 'encounter', 'treatmentPlan', 'clinicService', 'creator']);

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
        return auth()->user()?->canAccessClinicDentalCharting() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicDentalCharting() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicDentalCharting() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicDentalCharting() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDentalChartEntries::route('/'),
            'create' => CreateDentalChartEntry::route('/create'),
            'view' => ViewDentalChartEntry::route('/{record}'),
            'edit' => EditDentalChartEntry::route('/{record}/edit'),
        ];
    }
}
