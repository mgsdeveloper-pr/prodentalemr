<?php

namespace App\Filament\Clinic\Resources\Users;

use App\Filament\Clinic\Resources\Users\Pages\CreateUser;
use App\Filament\Clinic\Resources\Users\Pages\EditUser;
use App\Filament\Clinic\Resources\Users\Pages\ListUsers;
use App\Filament\Clinic\Resources\Users\Pages\ViewUser;
use App\Filament\Clinic\Resources\Users\Schemas\UserForm;
use App\Filament\Clinic\Resources\Users\Schemas\UserInfolist;
use App\Filament\Clinic\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Support\ClinicAdministrationAccess;
use App\Support\ClinicPanelScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Users & Access';

    protected static string|UnitEnum|null $navigationGroup = 'Clinic Management';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Clinic Management';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['roles', 'organization', 'clinic', 'location', 'creator'])
            ->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->whereIn('name', array_keys(User::clinicRoleOptions())));

        $organizationId = ClinicPanelScope::selectedOrganizationId();
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $organizationId || ! $clinicId) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId);
    }

    public static function canAccess(): bool
    {
        return ClinicAdministrationAccess::canView('users');
    }

    public static function canCreate(): bool
    {
        return ClinicAdministrationAccess::canMutate('users', 'add');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView($record): bool
    {
        return static::canAccess()
            && (int) $record->organization_id === ClinicPanelScope::selectedOrganizationId()
            && (int) $record->clinic_id === ClinicPanelScope::selectedClinicId();
    }

    public static function canEdit($record): bool
    {
        return static::canView($record) && ClinicAdministrationAccess::canMutate('users', 'update');
    }

    public static function canDelete($record): bool
    {
        return static::canView($record)
            && ClinicAdministrationAccess::canMutate('users', 'delete')
            && $record->id !== auth()->id();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
