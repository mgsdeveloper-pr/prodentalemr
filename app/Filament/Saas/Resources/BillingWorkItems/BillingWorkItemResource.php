<?php

namespace App\Filament\Saas\Resources\BillingWorkItems;

use App\Filament\Saas\Resources\BillingWorkItems\Pages\CreateBillingWorkItem;
use App\Filament\Saas\Resources\BillingWorkItems\Pages\EditBillingWorkItem;
use App\Filament\Saas\Resources\BillingWorkItems\Pages\ListBillingWorkItems;
use App\Filament\Saas\Resources\BillingWorkItems\Pages\ViewBillingWorkItem;
use App\Filament\Saas\Resources\BillingWorkItems\RelationManagers\ActivitiesRelationManager;
use App\Filament\Saas\Resources\BillingWorkItems\RelationManagers\AttachmentsRelationManager;
use App\Filament\Saas\Resources\BillingWorkItems\RelationManagers\NotesRelationManager;
use App\Filament\Saas\Resources\BillingWorkItems\Schemas\BillingWorkItemForm;
use App\Filament\Saas\Resources\BillingWorkItems\Schemas\BillingWorkItemInfolist;
use App\Filament\Saas\Resources\BillingWorkItems\Tables\BillingWorkItemsTable;
use App\Models\BillingWorkItem;
use App\Support\AdminClinicScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BillingWorkItemResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = BillingWorkItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Work Queue';

    protected static string|UnitEnum|null $navigationGroup = 'Insurance Verification';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'display_title';

    public static function form(Schema $schema): Schema
    {
        return BillingWorkItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BillingWorkItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingWorkItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
            AttachmentsRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return AdminClinicScope::apply(parent::getEloquentQuery(), 'clinic_id')
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'managedBillingService',
                'organization',
                'clinic',
                'location',
                'patient',
                'provider.user',
                'insurancePolicy',
                'insuranceClaim',
                'appointment',
                'assignedTo',
                'reviewedBy',
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasRevenueOperations() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canAccessSaasRevenueOperations() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canAccessSaasRevenueOperations() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canManageSaasRevenueOperations() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingWorkItems::route('/'),
            'create' => CreateBillingWorkItem::route('/create'),
            'view' => ViewBillingWorkItem::route('/{record}'),
            'edit' => EditBillingWorkItem::route('/{record}/edit'),
        ];
    }
}
