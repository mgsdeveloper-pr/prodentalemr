<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\Schemas\UserInfolist;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Models\User;
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

    protected static ?string $navigationLabel = 'Users';

    protected static string|UnitEnum|null $navigationGroup = 'Access Management';

    protected static ?int $navigationSort = 1;

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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['roles', 'creator', 'verificationClinics'])
            ->whereHas('roles', fn (Builder $roleQuery): Builder => $roleQuery->whereIn('name', array_keys(User::verificationRoleOptions())));

        $user = auth()->user();

        if ($user?->isSaasAdmin()) {
            return $query;
        }

        if ($user?->isVerificationAdmin()) {
            return $query->whereHas('roles', fn (Builder $roleQuery): Builder => $roleQuery->where('name', '!=', 'verification_admin'));
        }

        if ($user?->isVerificationManager()) {
            $clinicIds = $user->verificationAccessibleClinicIds();

            if ($clinicIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query
                ->whereHas('roles', fn (Builder $roleQuery): Builder => $roleQuery->where('name', 'verification_user'))
                ->whereHas('verificationClinics', fn (Builder $clinicQuery): Builder => $clinicQuery->whereIn('clinics.id', $clinicIds));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->canManageVerificationUsers()
            && $user?->canAccessVerificationModule('users'));
    }

    public static function canCreate(): bool
    {
        return static::canAccess()
            && (auth()->user()?->canPerformVerificationModuleAction('users', 'add') ?? false);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView($record): bool
    {
        return static::canAccess()
            && static::canManageRecord($record);
    }

    public static function canEdit($record): bool
    {
        return static::canAccess()
            && (auth()->user()?->canPerformVerificationModuleAction('users', 'update') ?? false)
            && static::canManageRecord($record);
    }

    public static function canDelete($record): bool
    {
        return static::canAccess()
            && (auth()->user()?->canPerformVerificationModuleAction('users', 'delete') ?? false)
            && static::canManageRecord($record)
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

    public static function canManageRecord(User $record): bool
    {
        $user = auth()->user();
        $recordRole = $record->getPrimaryRoleName();

        if (! $user instanceof User || ! User::isVerificationRole($recordRole)) {
            return false;
        }

        if ($user->isSaasAdmin()) {
            return true;
        }

        if ($user->isVerificationAdmin()) {
            return $recordRole !== 'verification_admin';
        }

        if ($user->isVerificationManager()) {
            if ($recordRole !== 'verification_user') {
                return false;
            }

            $managerClinicIds = $user->verificationAccessibleClinicIds();
            $recordClinicIds = $record->verificationClinics()->pluck('clinics.id')->map(fn ($id): int => (int) $id)->all();

            return $managerClinicIds !== []
                && $recordClinicIds !== []
                && count(array_intersect($managerClinicIds, $recordClinicIds)) > 0;
        }

        return false;
    }
}
