<?php

namespace App\Filament\Saas\Resources\ManagedBillingServices;

use App\Filament\Saas\Resources\ManagedBillingServices\Pages\CreateManagedBillingService;
use App\Filament\Saas\Resources\ManagedBillingServices\Pages\EditManagedBillingService;
use App\Filament\Saas\Resources\ManagedBillingServices\Pages\ListManagedBillingServices;
use App\Filament\Saas\Resources\ManagedBillingServices\Pages\ViewManagedBillingService;
use App\Filament\Saas\Resources\ManagedBillingServices\Schemas\ManagedBillingServiceForm;
use App\Filament\Saas\Resources\ManagedBillingServices\Schemas\ManagedBillingServiceInfolist;
use App\Filament\Saas\Resources\ManagedBillingServices\Tables\ManagedBillingServicesTable;
use App\Models\ManagedBillingService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ManagedBillingServiceResource extends Resource
{
    protected static ?string $model = ManagedBillingService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Managed Services';

    protected static string|UnitEnum|null $navigationGroup = 'Managed Services';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ManagedBillingServiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ManagedBillingServiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ManagedBillingServicesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->withCount(['enrollments', 'workItems']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('managed_services') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('managed_services', 'add') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('managed_services', 'update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('managed_services', 'delete') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManagedBillingServices::route('/'),
            'create' => CreateManagedBillingService::route('/create'),
            'view' => ViewManagedBillingService::route('/{record}'),
            'edit' => EditManagedBillingService::route('/{record}/edit'),
        ];
    }
}
