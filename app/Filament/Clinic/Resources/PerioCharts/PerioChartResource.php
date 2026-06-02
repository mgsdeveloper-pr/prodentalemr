<?php

namespace App\Filament\Clinic\Resources\PerioCharts;

use App\Filament\Clinic\Resources\PerioCharts\Pages\CreatePerioChart;
use App\Filament\Clinic\Resources\PerioCharts\Pages\EditPerioChart;
use App\Filament\Clinic\Resources\PerioCharts\Pages\ListPerioCharts;
use App\Filament\Clinic\Resources\PerioCharts\Pages\ViewPerioChart;
use App\Filament\Clinic\Resources\PerioCharts\Schemas\PerioChartForm;
use App\Filament\Clinic\Resources\PerioCharts\Schemas\PerioChartInfolist;
use App\Filament\Clinic\Resources\PerioCharts\Tables\PerioChartsTable;
use App\Models\PerioChart;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PerioChartResource extends Resource
{
    protected static ?string $model = PerioChart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Perio Charting';

    protected static string|UnitEnum|null $navigationGroup = 'Dental Charting';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return PerioChartForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PerioChartInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerioChartsTable::configure($table);
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
            ->with(['patient', 'provider.user', 'location', 'encounter', 'creator'])
            ->withCount('entries');

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
        return auth()->user()?->canAccessClinicPerioCharting() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicPerioCharting() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicPerioCharting() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicPerioCharting() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPerioCharts::route('/'),
            'create' => CreatePerioChart::route('/create'),
            'view' => ViewPerioChart::route('/{record}'),
            'edit' => EditPerioChart::route('/{record}/edit'),
        ];
    }
}
