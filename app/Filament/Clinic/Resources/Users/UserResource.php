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

    protected static string|UnitEnum|null $navigationGroup = 'Access Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Access Management';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
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

        $user = auth()->user();

        if ($user?->shouldBypassClinicScope()) {
            return $query;
        }

        if (! $user?->organization_id || ! $user?->clinic_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('organization_id', $user->organization_id)
            ->where('clinic_id', $user->clinic_id);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return filled($user?->organization_id)
            && filled($user?->clinic_id)
            && ($user?->canManageClinicUsers() ?? false)
            && ($user?->canAccessClinicModule('users') ?? false);
    }

    public static function canCreate(): bool
    {
        return static::canAccess()
            && (auth()->user()?->canPerformClinicModuleAction('users', 'add') ?? false);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView($record): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess()
            && (auth()->user()?->canPerformClinicModuleAction('users', 'update') ?? false);
    }

    public static function canDelete($record): bool
    {
        return static::canAccess()
            && (auth()->user()?->canPerformClinicModuleAction('users', 'delete') ?? false)
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
