<?php

namespace App\Filament\Clinic\Resources\ClinicServices;

use App\Filament\Clinic\Resources\ClinicServices\Pages\CreateClinicService;
use App\Filament\Clinic\Resources\ClinicServices\Pages\EditClinicService;
use App\Filament\Clinic\Resources\ClinicServices\Pages\ListClinicServices;
use App\Filament\Clinic\Resources\ClinicServices\Pages\ViewClinicService;
use App\Filament\Clinic\Resources\ClinicServices\Schemas\ClinicServiceForm;
use App\Filament\Clinic\Resources\ClinicServices\Schemas\ClinicServiceInfolist;
use App\Filament\Clinic\Resources\ClinicServices\Tables\ClinicServicesTable;
use App\Models\ClinicService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ClinicServiceResource extends Resource
{
    protected static ?string $model = ClinicService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Services';

    protected static string|UnitEnum|null $navigationGroup = 'Treatment Planning';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ClinicServiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClinicServiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClinicServicesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with('location');

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
        return auth()->user()?->canAccessClinicServices() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicServices() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canEditClinicServices() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canDeleteClinicServices() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClinicServices::route('/'),
            'create' => CreateClinicService::route('/create'),
            'view' => ViewClinicService::route('/{record}'),
            'edit' => EditClinicService::route('/{record}/edit'),
        ];
    }
}
