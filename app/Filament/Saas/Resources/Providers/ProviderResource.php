<?php

namespace App\Filament\Saas\Resources\Providers;

use App\Filament\Saas\Resources\Providers\Pages\CreateProvider;
use App\Filament\Saas\Resources\Providers\Pages\EditProvider;
use App\Filament\Saas\Resources\Providers\Pages\ListProviders;
use App\Filament\Saas\Resources\Providers\Pages\ViewProvider;
use App\Filament\Saas\Resources\Providers\Schemas\ProviderForm;
use App\Filament\Saas\Resources\Providers\Schemas\ProviderInfolist;
use App\Filament\Saas\Resources\Providers\Tables\ProvidersTable;
use App\Models\Provider;
use App\Support\SaasSupportAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Providers';

    protected static string|UnitEnum|null $navigationGroup = 'Client Management';

    protected static ?int $navigationSort = 35;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return ProviderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProviderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProvidersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->when(static::supportOrganizationId(), fn (Builder $query, int $organizationId): Builder => $query->where('organization_id', $organizationId))
            ->when(static::supportClinicId(), fn (Builder $query, int $clinicId): Builder => $query->where('clinic_id', $clinicId))
            ->with(['organization', 'clinic', 'location', 'user.roles'])
            ->withCount('appointments');
    }

    public static function supportOrganizationId(): ?int
    {
        return request()->integer('organization_id') ?: SaasSupportAccess::activeOrganizationId();
    }

    public static function supportClinicId(): ?int
    {
        return request()->integer('clinic_id') ?: SaasSupportAccess::activeClinicId();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('providers') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canRestore($record): bool
    {
        return static::canDelete($record);
    }

    public static function supportContextMatches(?Provider $provider): bool
    {
        if (! $provider) {
            return false;
        }

        $organizationId = SaasSupportAccess::activeOrganizationId();
        $clinicId = SaasSupportAccess::activeClinicId();

        if (! $organizationId || (int) $provider->organization_id !== $organizationId) {
            return false;
        }

        return ! $clinicId || (int) $provider->clinic_id === $clinicId;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviders::route('/'),
            'create' => CreateProvider::route('/create'),
            'view' => ViewProvider::route('/{record}'),
            'edit' => EditProvider::route('/{record}/edit'),
        ];
    }
}
