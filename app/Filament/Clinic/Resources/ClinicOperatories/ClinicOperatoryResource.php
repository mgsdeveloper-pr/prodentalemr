<?php

namespace App\Filament\Clinic\Resources\ClinicOperatories;

use App\Filament\Clinic\Resources\ClinicOperatories\Pages\CreateClinicOperatory;
use App\Filament\Clinic\Resources\ClinicOperatories\Pages\EditClinicOperatory;
use App\Filament\Clinic\Resources\ClinicOperatories\Pages\ListClinicOperatories;
use App\Filament\Clinic\Resources\ClinicOperatories\Pages\ViewClinicOperatory;
use App\Filament\Clinic\Resources\ClinicOperatories\Schemas\ClinicOperatoryForm;
use App\Filament\Clinic\Resources\ClinicOperatories\Schemas\ClinicOperatoryInfolist;
use App\Filament\Clinic\Resources\ClinicOperatories\Tables\ClinicOperatoriesTable;
use App\Models\ClinicOperatory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ClinicOperatoryResource extends Resource
{
    protected static ?string $model = ClinicOperatory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Operatories';

    protected static string|UnitEnum|null $navigationGroup = 'Scheduling';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ClinicOperatoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClinicOperatoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClinicOperatoriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['location'])
            ->withCount('appointments');

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
        return auth()->user()?->canAccessClinicOperatories() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicOperatories() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicOperatories() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicOperatories() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClinicOperatories::route('/'),
            'create' => CreateClinicOperatory::route('/create'),
            'view' => ViewClinicOperatory::route('/{record}'),
            'edit' => EditClinicOperatory::route('/{record}/edit'),
        ];
    }
}
