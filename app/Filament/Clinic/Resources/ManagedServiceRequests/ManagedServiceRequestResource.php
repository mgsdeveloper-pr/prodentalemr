<?php

namespace App\Filament\Clinic\Resources\ManagedServiceRequests;

use App\Filament\Clinic\Resources\ManagedServiceRequests\Pages\CreateManagedServiceRequest;
use App\Filament\Clinic\Resources\ManagedServiceRequests\Pages\ListManagedServiceRequests;
use App\Filament\Clinic\Resources\ManagedServiceRequests\Pages\ViewManagedServiceRequest;
use App\Filament\Clinic\Resources\ManagedServiceRequests\Schemas\ManagedServiceRequestForm;
use App\Filament\Clinic\Resources\ManagedServiceRequests\Schemas\ManagedServiceRequestInfolist;
use App\Filament\Clinic\Resources\ManagedServiceRequests\Tables\ManagedServiceRequestsTable;
use App\Models\ClientServiceEnrollment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ManagedServiceRequestResource extends Resource
{
    protected static ?string $model = ClientServiceEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Managed Services';

    protected static string|UnitEnum|null $navigationGroup = 'Managed Services';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'display_title';

    protected static ?string $slug = 'service-requests';

    public static function getModelLabel(): string
    {
        return 'managed service';
    }

    public static function getPluralModelLabel(): string
    {
        return 'managed services';
    }

    public static function form(Schema $schema): Schema
    {
        return ManagedServiceRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ManagedServiceRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ManagedServiceRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['organization', 'clinic', 'location', 'managedBillingService', 'creator']);

        if (! $user?->organization_id || ! $user?->clinic_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('organization_id', $user->organization_id)
            ->where('clinic_id', $user->clinic_id);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessClinicManagedServices() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canCreateClinicManagedServices() ?? false;
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManagedServiceRequests::route('/'),
            'create' => CreateManagedServiceRequest::route('/create'),
            'view' => ViewManagedServiceRequest::route('/{record}'),
        ];
    }
}
