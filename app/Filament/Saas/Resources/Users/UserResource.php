<?php

namespace App\Filament\Saas\Resources\Users;

use App\Filament\Saas\Resources\Users\Pages\CreateUser;
use App\Filament\Saas\Resources\Users\Pages\EditUser;
use App\Filament\Saas\Resources\Users\Pages\ListUsers;
use App\Filament\Saas\Resources\Users\Pages\ViewUser;
use App\Filament\Saas\Resources\Users\Schemas\UserForm;
use App\Filament\Saas\Resources\Users\Schemas\UserInfolist;
use App\Filament\Saas\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Users';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 50;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'name';

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
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', array_keys(User::saasRoleOptions())))
            ->with(['roles', 'organization', 'clinic', 'location', 'creator']);

        if (! auth()->user()?->isSaasAdmin()) {
            $query->whereDoesntHave('roles', fn (Builder $roleQuery): Builder => $roleQuery->where('name', 'saas_admin'));
        }

        return $query;
    }

    public static function canView($record): bool
    {
        return (auth()->user()?->canAccessSaasModule('users') ?? false)
            && (! $record->hasRole('saas_admin') || (auth()->user()?->isSaasAdmin() ?? false));
    }

    public static function canEdit($record): bool
    {
        return (auth()->user()?->canPerformSaasModuleAction('users', 'update') ?? false)
            && (! $record->hasRole('saas_admin') || (auth()->user()?->isSaasAdmin() ?? false));
    }

    public static function canDelete($record): bool
    {
        return (auth()->user()?->canPerformSaasModuleAction('users', 'delete') ?? false)
            && (! $record->hasRole('saas_admin') || (auth()->user()?->isSaasAdmin() ?? false))
            && $record->id !== auth()->id();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('users') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('users', 'add') ?? false;
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
